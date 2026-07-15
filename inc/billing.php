<?php
/**
 * Billing compliance: CPF/CNPJ field for Brazilian customers.
 *
 * Registers an additional checkout field (WooCommerce Blocks 8.7+) for the
 * Brazilian fiscal document. The field is optional in the UI but server-side
 * validation requires it when the billing country is Brazil, so the correct
 * fiscal document (NFS-e) can be issued instead of a standard invoice.
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
		error_log( 'libresign billing.php: country from get_customer_data()=' . $country . ', class=' . get_class( $document_object ) );
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
 * Show the CPF/CNPJ field only when the billing address country is Brazil.
 *
 * The decision is based exclusively on the billing address country (the
 * country the customer enters in the checkout form), NOT on the browser
 * language or site locale. A customer browsing in English from England with
 * a Brazilian billing address will see and be required to fill this field.
 *
 * WooCommerce Blocks renders additional fields for all countries; this script
 * hides the row when billing country ≠ BR and shows it when billing country = BR.
 * Uses a MutationObserver so it survives React re-renders.
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
		var FIELD_KEY     = 'billing_libresign/cpf-cnpj';
		var FIELD_CSS     = '.wc-block-components-address-form__libresign-cpf-cnpj';
		var CLEAN_LABEL   = <?php echo wp_json_encode( __( 'CPF or CNPJ', 'libresign' ) ); ?>;
		var ERROR_MSG     = <?php echo wp_json_encode( __( 'Please enter your CPF or CNPJ.', 'libresign' ) ); ?>;
		var COUNTRY_SELECTORS = [ '#billing-country', '[name="billing_country"]', '[data-testid="select-country"]' ];
		var OBS_OPTIONS   = { childList: true, subtree: true };

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

		// ---------------------------------------------------------------------------
		// Show / hide field based on billing country (same logic as before)
		// ---------------------------------------------------------------------------
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

		// ---------------------------------------------------------------------------
		// WooCommerce Blocks validation via wp.data
		// Uses isBeforeProcessing() + setValidationErrors() so the error appears
		// exactly the same way as other required billing fields (red border +
		// message below the field).
		// ---------------------------------------------------------------------------
		function initValidation() {
			if ( ! window.wp || ! window.wp.data ) return;

			var validationDispatch = wp.data.dispatch( 'wc/store/validation' );
			var checkoutSelect    = wp.data.select( 'wc/store/checkout' );
			if ( ! validationDispatch || ! checkoutSelect ) return;

			var wasBeforeProcessing = false;

			wp.data.subscribe( function () {
				var isBefore = checkoutSelect.isBeforeProcessing();

				if ( isBefore && ! wasBeforeProcessing ) {
					// Checkout just entered "before-processing" phase.
					// Validate CPF/CNPJ here — WooCommerce Blocks checks the
					// validation store AFTER this subscriber runs and stops
					// processing if errors are present.
					if ( getCountry() === 'BR' && ! getCpfValue() ) {
						validationDispatch.setValidationErrors( {
							[ FIELD_KEY ]: { message: ERROR_MSG, hidden: false }
						} );
					} else {
						validationDispatch.clearValidationError( FIELD_KEY );
					}
				}

				wasBeforeProcessing = isBefore;
			} );

			// Clear the error as soon as the user starts typing in the field.
			document.addEventListener( 'input', function ( e ) {
				if ( e.target && e.target.id === 'billing-libresign-cpf-cnpj' && e.target.value.trim() ) {
					validationDispatch.clearValidationError( FIELD_KEY );
				}
			} );
		}

		// ---------------------------------------------------------------------------
		// Bootstrap
		// ---------------------------------------------------------------------------
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
