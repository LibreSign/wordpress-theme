<?php
/**
 * CPF and CNPJ validation for Brazilian customers.
 *
 * Owns the complete lifecycle of the libresign/cpf-cnpj checkout field:
 * registration, required-for-BR rule, Módulo 11 validation (PHP + JS),
 * field visibility (show/hide by billing country), and the WooCommerce Blocks
 * hooks that enforce it.
 *
 * @package libresign
 */

use LibreSign\WordPressTheme\TaxId;

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/src/TaxId.php';

/**
 * Register the CPF/CNPJ field in the checkout address section.
 */
function libresign_theme_register_cpf_cnpj_field() {
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
			),
		)
	);
}
add_action( 'woocommerce_init', 'libresign_theme_register_cpf_cnpj_field' );

/**
 * Server-side enforcement via the address-location validation hook.
 *
 * This is the WooCommerce Blocks hook that receives the whole address group
 * (including the country), so the field can be required and format-validated
 * only for Brazilian billing addresses without blocking other countries.
 */
add_action( 'woocommerce_blocks_validate_location_address_fields', function ( \WP_Error $errors, $fields, $group ) {
	if ( 'billing' !== $group ) {
		return;
	}

	$country = ( is_array( $fields ) && isset( $fields['country'] ) ) ? (string) $fields['country'] : '';
	if ( 'BR' !== $country ) {
		return;
	}

	$messages = array(
		'libresign_cpf_cnpj_required' => __( 'Please enter your CPF or CNPJ.', 'libresign' ),
		'invalid_cnpj'                => __( 'Please enter a valid CNPJ.', 'libresign' ),
		'invalid_cpf'                 => __( 'Please enter a valid CPF.', 'libresign' ),
		'invalid_cpf_cnpj'            => __( 'Please enter a valid CPF (11 digits) or CNPJ (14 characters).', 'libresign' ),
	);

	$error_code = TaxId::error_code( isset( $fields['libresign/cpf-cnpj'] ) ? (string) $fields['libresign/cpf-cnpj'] : '' );
	if ( '' !== $error_code ) {
		$errors->add( $error_code, $messages[ $error_code ] );
	}
}, 10, 3 );

/**
 * CPF/CNPJ is a Brazilian billing concept: never keep it on a shipping address,
 * and drop it entirely when the billing country is not Brazil. Runs on the
 * Store API update hooks, which fire after the value is set on the object but
 * before it is saved.
 */
function libresign_theme_strip_irrelevant_cpf_cnpj( $wc_object ) {
	if ( ! is_object( $wc_object ) || ! method_exists( $wc_object, 'delete_meta_data' ) ) {
		return;
	}

	$wc_object->delete_meta_data( '_wc_shipping/libresign/cpf-cnpj' );

	if ( method_exists( $wc_object, 'get_billing_country' ) && 'BR' !== $wc_object->get_billing_country() ) {
		$wc_object->delete_meta_data( '_wc_billing/libresign/cpf-cnpj' );
	}
}

add_action( 'woocommerce_store_api_checkout_update_order_from_request', function ( $order ) {
	libresign_theme_strip_irrelevant_cpf_cnpj( $order );
}, 100, 1 );

add_action( 'woocommerce_store_api_checkout_update_customer_from_request', function ( $customer ) {
	libresign_theme_strip_irrelevant_cpf_cnpj( $customer );
}, 100, 1 );

/**
 * Client-side script for the CPF/CNPJ checkout field.
 *
 * Outputs a single inline script that handles two concerns:
 *  1. Visibility — hides the field for non-BR customers and reveals it when
 *     the billing country is Brazil, surviving React re-renders via a
 *     MutationObserver.
 *  2. Validation — mirrors the Módulo 11 algorithms of src/TaxId.php so the user
 *     gets native WooCommerce Blocks error feedback (red border + message
 *     below the field) without a server round-trip. A re-entrancy guard
 *     prevents the wp.data subscriber from triggering itself in an infinite
 *     dispatch loop.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	?>
	<style>
		/* Initially hidden; JavaScript reveals it only for Brazil */
		.wc-block-components-address-form__libresign-cpf-cnpj { display: none; }
	</style>
	<script>
	( function () {
		var FIELD_KEY         = 'billing_libresign/cpf-cnpj';
		var FIELD_CSS         = '.wc-block-components-address-form__libresign-cpf-cnpj';
		var CLEAN_LABEL       = <?php echo wp_json_encode( __( 'CPF or CNPJ', 'libresign' ) ); ?>;
		var COUNTRY_SELECTORS = [ '#billing-country', '[name="billing_country"]', '[data-testid="select-country"]' ];
		var OBS_OPTIONS       = { childList: true, subtree: true };
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
		// Show / hide field based on billing country
		// -----------------------------------------------------------------------
		var observer = new MutationObserver( updateVisibility );

		function updateVisibility() {
			var row = document.querySelector( FIELD_CSS );
			if ( ! row ) return;
			var isBrazil = getCountry() === 'BR';
			row.style.display = isBrazil ? 'block' : 'none';

			// Remove "(optional)" suffix — disconnect observer first to prevent loop
			var label = row.querySelector( 'label' );
			if ( isBrazil && label && label.textContent.trim() !== CLEAN_LABEL ) {
				observer.disconnect();
				label.textContent = CLEAN_LABEL;
				observer.observe( document.body, OBS_OPTIONS );
			}
		}

		// -----------------------------------------------------------------------
		// Módulo 11 validation — mirrors src/TaxId.php.
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
			if ( stripped.length === 14 )   return validateCnpj( stripped );
			if ( digitsOnly.length === 11 ) return validateCpf( digitsOnly );
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

		// -----------------------------------------------------------------------
		// Bootstrap
		// -----------------------------------------------------------------------
		function init() {
			updateVisibility();
			observer.observe( document.body, OBS_OPTIONS );
			document.body.addEventListener( 'change', function ( e ) {
				if ( e.target && COUNTRY_SELECTORS.some( function ( s ) {
					return e.target.matches( s );
				} ) ) {
					updateVisibility();
				}
			} );
			initValidation();
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', init );
		} else {
			init();
		}
	} )();
	</script>
	<?php
} );
