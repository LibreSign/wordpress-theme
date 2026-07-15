<?php
/**
 * CPF and CNPJ validation for Brazilian customers.
 *
 * Owns the complete lifecycle of the libresign/cpf-cnpj checkout field:
 * registration, required-for-BR rule, Módulo 11 validation (PHP + JS), and
 * the WooCommerce Blocks hooks that enforce it. Show/hide display logic
 * lives in inc/billing.php.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the CPF/CNPJ field in the checkout address section.
 */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'         => 'libresign/cpf-cnpj',
			'label'      => __( 'CPF or CNPJ', 'libresign' ),
			'location'   => 'address',
			'required'   => false,
			'type'       => 'text',
			'attributes' => array(
				'autocomplete' => 'off',
				'placeholder'  => __( 'Required for customers in Brazil', 'libresign' ),
			),
		)
	);
} );

/**
 * Require CPF/CNPJ only when the billing country is Brazil — server-side.
 *
 * woocommerce_get_contextual_fields_for_location lets us override the field
 * required flag at request time based on the billing address country, so we
 * can make it truly required for BR without breaking checkout for other
 * countries.
 *
 * The decision is based exclusively on the billing address country, NOT on
 * browser language or site locale. A customer browsing in English from England
 * with a Brazilian billing address will have this field required.
 *
 * The field is registered as required: false so the React client does not
 * block non-BR customers. This filter flips required to true for BR before
 * WooCommerce runs its own "required field is empty" check.
 */
add_filter( 'woocommerce_get_contextual_fields_for_location', function ( array $fields, string $location, $document_object ) {
	if ( ! isset( $fields['libresign/cpf-cnpj'] ) ) {
		return $fields;
	}

	$country = '';

	// DocumentObject (WooCommerce Blocks Store API) exposes the request's
	// billing address via get_customer_data()['billing_address']['country'].
	// This is populated directly from the checkout REST request, so it
	// reflects the country the customer is submitting — not the browser
	// language and not any previously saved billing address.
	if ( is_object( $document_object ) && method_exists( $document_object, 'get_customer_data' ) ) {
		$customer = $document_object->get_customer_data();
		$country  = $customer['billing_address']['country'] ?? '';
	}

	// Fallback for classic WC_Customer / WC_Order objects (non-Blocks contexts).
	if ( '' === $country ) {
		if ( is_object( $document_object ) && method_exists( $document_object, 'get_billing_address' ) ) {
			$billing = $document_object->get_billing_address();
			$country = $billing['country'] ?? '';
		}
	}

	if ( '' === $country && function_exists( 'WC' ) && WC()->customer ) {
		$country = WC()->customer->get_billing_country();
	}

	$fields['libresign/cpf-cnpj']['required'] = ( 'BR' === $country );

	return $fields;
}, 10, 3 );

/**
 * Validate CPF — 11-digit Brazilian individual taxpayer ID.
 *
 * Uses the standard Módulo 11 algorithm with decreasing weights 10..2 / 11..2.
 *
 * @param string $cpf Raw or formatted CPF (dots and dash are stripped).
 * @return bool
 */
function libresign_validate_cpf( string $cpf ): bool {
	$cpf = preg_replace( '/[^0-9]/', '', $cpf );
	if ( strlen( $cpf ) !== 11 ) {
		return false;
	}
	// All identical digits (e.g. 000.000.000-00) are never valid.
	if ( preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
		return false;
	}

	$mod11 = function ( string $str, int $start_weight ): int {
		$sum = 0;
		$w   = $start_weight;
		for ( $i = 0; $i < strlen( $str ); $i++ ) {
			$sum += (int) $str[ $i ] * $w--;
		}
		$rem = $sum % 11;
		return $rem < 2 ? 0 : 11 - $rem;
	};

	$dv1 = $mod11( substr( $cpf, 0, 9 ), 10 );
	if ( $dv1 !== (int) $cpf[9] ) {
		return false;
	}
	return $mod11( substr( $cpf, 0, 10 ), 11 ) === (int) $cpf[10];
}

/**
 * Validate CNPJ — 14-character Brazilian company taxpayer ID.
 *
 * Handles both the legacy numeric format and the new alphanumeric format
 * introduced by Instrução Normativa RFB nº 2.119/2022 (Anexo Único):
 *  - Positions  1–12: alphanumeric  (root 8 chars + order 4 chars)
 *  - Positions 13–14: numeric check digits
 *
 * Algorithm: Módulo 11 with weights 2–9 assigned right-to-left, cycling
 * back to 2 after reaching 9. Character value = ord(char) − 48, which gives
 * the digit value for '0'–'9' (ASCII 48–57) and 17–42 for 'A'–'Z'
 * (ASCII 65–90), exactly as specified in the Receita Federal table.
 *
 * @param string $cnpj Raw or formatted CNPJ (dots, slash, dash are stripped).
 * @return bool
 */
function libresign_validate_cnpj( string $cnpj ): bool {
	$cnpj = strtoupper( preg_replace( '/[\.\-\/]/', '', $cnpj ) );
	if ( strlen( $cnpj ) !== 14 ) {
		return false;
	}
	// All identical digits — applies to legacy numeric CNPJs.
	if ( preg_match( '/^(\d)\1{13}$/', $cnpj ) ) {
		return false;
	}
	// Positions 1–12: uppercase letters or digits; positions 13–14: digits.
	if ( ! preg_match( '/^[A-Z0-9]{12}\d{2}$/', $cnpj ) ) {
		return false;
	}

	$mod11 = function ( string $str ): int {
		$sum = 0;
		$w   = 2;
		for ( $i = strlen( $str ) - 1; $i >= 0; $i-- ) {
			$sum += ( ord( $str[ $i ] ) - 48 ) * $w;
			$w    = 9 === $w ? 2 : $w + 1;
		}
		$rem = $sum % 11;
		return $rem < 2 ? 0 : 11 - $rem;
	};

	$dv1 = $mod11( substr( $cnpj, 0, 12 ) );
	if ( $dv1 !== (int) $cnpj[12] ) {
		return false;
	}
	return $mod11( substr( $cnpj, 0, 13 ) ) === (int) $cnpj[13];
}

/**
 * Server-side format validation for the CPF/CNPJ checkout field.
 *
 * Fires for every additional checkout field submitted via the WooCommerce
 * Blocks Store API. Adding an error code causes WooCommerce to reject the
 * order with HTTP 400 and surface the message in the checkout UI.
 *
 * Note: the empty-field check is already handled by the required flag set in
 * the woocommerce_get_contextual_fields_for_location filter in billing.php;
 * this hook only validates format when a value is present.
 */
add_action( 'woocommerce_blocks_validate_additional_checkout_field', function ( \WP_Error $errors, string $field_key, $field_value ) {
	if ( 'libresign/cpf-cnpj' !== $field_key ) {
		return;
	}

	$value = trim( (string) $field_value );
	if ( '' === $value ) {
		return;
	}

	$digits_only = preg_replace( '/[^0-9]/', '', $value );
	$stripped    = strtoupper( preg_replace( '/[\.\-\/]/', '', $value ) );

	if ( 11 === strlen( $digits_only ) ) {
		if ( ! libresign_validate_cpf( $value ) ) {
			$errors->add( 'invalid_cpf', __( 'Please enter a valid CPF.', 'libresign' ) );
		}
	} elseif ( 14 === strlen( $stripped ) ) {
		if ( ! libresign_validate_cnpj( $value ) ) {
			$errors->add( 'invalid_cnpj', __( 'Please enter a valid CNPJ.', 'libresign' ) );
		}
	} else {
		$errors->add( 'invalid_cpf_cnpj', __( 'Please enter a valid CPF (11 digits) or CNPJ (14 characters).', 'libresign' ) );
	}
}, 10, 3 );

/**
 * Client-side CPF/CNPJ validation via WooCommerce Blocks wp.data stores.
 *
 * Mirrors the PHP algorithms above so the user gets native WooCommerce Blocks
 * error feedback (red border + message below the field) on the first submit
 * attempt, without waiting for a server round-trip. The same re-entrancy guard
 * that protects the wp.data subscriber also guards the input event listener so
 * that neither can trigger the other in an infinite dispatch loop.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	?>
	<script>
	( function () {
		var FIELD_KEY         = 'billing_libresign/cpf-cnpj';
		var COUNTRY_SELECTORS = [ '#billing-country', '[name="billing_country"]', '[data-testid="select-country"]' ];
		var ERROR_MSG         = <?php echo wp_json_encode( __( 'Please enter your CPF or CNPJ.', 'libresign' ) ); ?>;
		var INVALID_MSG       = <?php echo wp_json_encode( __( 'Please enter a valid CPF or CNPJ.', 'libresign' ) ); ?>;

		function getCountry() {
			for ( var i = 0; i < COUNTRY_SELECTORS.length; i++ ) {
				var el = document.querySelector( COUNTRY_SELECTORS[ i ] );
				if ( el ) return el.value;
			}
			return '';
		}

		function getCpfValue() {
			var input = document.querySelector( '#billing-libresign-cpf-cnpj' );
			return input ? input.value.trim() : '';
		}

		// -----------------------------------------------------------------------
		// Módulo 11 validation — mirrors the PHP functions in this file.
		// -----------------------------------------------------------------------

		/**
		 * Validate CPF — standard Módulo 11 with decreasing weights 10..2 / 11..2.
		 * @param {string} cpf 11 digits (already stripped of formatting).
		 * @returns {boolean}
		 */
		function validateCpf( cpf ) {
			if ( /^(\d)\1{10}$/.test( cpf ) ) return false;
			function mod11( str, startWeight ) {
				var sum = 0, w = startWeight;
				for ( var i = 0; i < str.length; i++ ) sum += parseInt( str[ i ], 10 ) * w--;
				var rem = sum % 11;
				return rem < 2 ? 0 : 11 - rem;
			}
			if ( mod11( cpf.slice( 0, 9 ), 10 ) !== parseInt( cpf[ 9 ], 10 ) ) return false;
			return mod11( cpf.slice( 0, 10 ), 11 ) === parseInt( cpf[ 10 ], 10 );
		}

		/**
		 * Validate CNPJ — Módulo 11, weights 2–9 right-to-left (cycling), ASCII−48.
		 * Handles both legacy numeric and new alphanumeric format (RFB IN 2.119/2022).
		 * @param {string} cnpj 14 chars, already stripped of formatting and uppercased.
		 * @returns {boolean}
		 */
		function validateCnpj( cnpj ) {
			if ( /^(\d)\1{13}$/.test( cnpj ) ) return false;
			if ( ! /^[A-Z0-9]{12}\d{2}$/.test( cnpj ) ) return false;
			function mod11( str ) {
				var sum = 0, w = 2;
				for ( var i = str.length - 1; i >= 0; i-- ) {
					sum += ( str.charCodeAt( i ) - 48 ) * w;
					w = w === 9 ? 2 : w + 1;
				}
				var rem = sum % 11;
				return rem < 2 ? 0 : 11 - rem;
			}
			if ( mod11( cnpj.slice( 0, 12 ) ) !== parseInt( cnpj[ 12 ], 10 ) ) return false;
			return mod11( cnpj.slice( 0, 13 ) ) === parseInt( cnpj[ 13 ], 10 );
		}

		/**
		 * Detect CPF vs CNPJ from raw input and validate accordingly.
		 * @param {string} value Raw field value (may include formatting chars).
		 * @returns {boolean} true if valid CPF or CNPJ.
		 */
		function validateCpfCnpj( value ) {
			var digitsOnly = value.replace( /[^0-9]/g, '' );
			var stripped   = value.replace( /[.\-\/]/g, '' ).toUpperCase();
			if ( digitsOnly.length === 11 ) return validateCpf( digitsOnly );
			if ( stripped.length === 14 )   return validateCnpj( stripped );
			return false;
		}

		// -----------------------------------------------------------------------
		// WooCommerce Blocks validation via wp.data
		// Uses isBeforeProcessing() + setValidationErrors() so the error renders
		// identically to other required billing fields (red border + message below).
		// -----------------------------------------------------------------------
		function initValidation() {
			if ( ! window.wp || ! window.wp.data ) return;

			var validationDispatch = wp.data.dispatch( 'wc/store/validation' );
			var checkoutSelect    = wp.data.select( 'wc/store/checkout' );
			if ( ! validationDispatch || ! checkoutSelect ) return;

			var wasBeforeProcessing = false;
			// Re-entrancy guard: wp.data notifies subscribers synchronously inside
			// dispatch(). Without this flag, calling setValidationErrors /
			// clearValidationError from the subscriber triggers the subscriber again
			// before wasBeforeProcessing is updated, causing infinite recursion and
			// a "Maximum call stack size exceeded" error that permanently freezes
			// the form until the page is refreshed.
			var isDispatching = false;

			wp.data.subscribe( function () {
				if ( isDispatching ) return;

				var isBefore = checkoutSelect.isBeforeProcessing();
				var prevWas  = wasBeforeProcessing;
				// Update wasBeforeProcessing BEFORE dispatching so that any
				// re-entrant subscriber call sees the updated value and exits early
				// via the isDispatching guard above.
				wasBeforeProcessing = isBefore;

				if ( isBefore && ! prevWas ) {
					isDispatching = true;
					try {
						if ( getCountry() === 'BR' ) {
							var cpfVal = getCpfValue();
							if ( ! cpfVal ) {
								validationDispatch.setValidationErrors( {
									[ FIELD_KEY ]: { message: ERROR_MSG, hidden: false }
								} );
							} else if ( ! validateCpfCnpj( cpfVal ) ) {
								validationDispatch.setValidationErrors( {
									[ FIELD_KEY ]: { message: INVALID_MSG, hidden: false }
								} );
							} else {
								validationDispatch.clearValidationError( FIELD_KEY );
							}
						} else {
							validationDispatch.clearValidationError( FIELD_KEY );
						}
					} finally {
						isDispatching = false;
					}
				}
			} );

			// Clear the error as soon as the user starts typing in the field.
			// Guard against triggering the re-entrancy scenario in the subscriber.
			document.addEventListener( 'input', function ( e ) {
				if ( e.target && e.target.id === 'billing-libresign-cpf-cnpj'
						&& e.target.value.trim() && ! isDispatching ) {
					isDispatching = true;
					try {
						validationDispatch.clearValidationError( FIELD_KEY );
					} finally {
						isDispatching = false;
					}
				}
			} );
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', initValidation );
		} else {
			initValidation();
		}
	} )();
	</script>
	<?php
} );
