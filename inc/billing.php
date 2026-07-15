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
	if ( is_object( $document_object ) && method_exists( $document_object, 'get_billing_address' ) ) {
		$billing  = $document_object->get_billing_address();
		$country  = $billing['country'] ?? '';
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
		var FIELD_SELECTOR = '.wc-block-components-address-form__libresign-cpf-cnpj';

		// WooCommerce Blocks renders the billing country as a native <select>.
		var COUNTRY_SELECTORS = [
			'#billing-country',
			'[name="billing_country"]',
			'[data-testid="select-country"]',
		];

		function getCountryField() {
			for ( var i = 0; i < COUNTRY_SELECTORS.length; i++ ) {
				var el = document.querySelector( COUNTRY_SELECTORS[ i ] );
				if ( el ) return el;
			}
			return null;
		}

		function update() {
			var row = document.querySelector( FIELD_SELECTOR );
			if ( ! row ) return;

			var countryField = getCountryField();
			var isBrazil = countryField ? countryField.value === 'BR' : false;

			// Show/hide only — no manual * addition.
			// Server-side (woocommerce_get_contextual_fields_for_location) marks
			// the field as required: true for BR, so WooCommerce handles the
			// error message if left empty.
			row.style.display = isBrazil ? 'block' : 'none';
		}

		// Re-run on any DOM change (React re-renders) and on country change events.
		var observer = new MutationObserver( update );

		function init() {
			update();
			observer.observe( document.body, { childList: true, subtree: true } );
			document.body.addEventListener( 'change', function ( e ) {
				if ( e.target && COUNTRY_SELECTORS.some( function ( s ) {
					return e.target.matches( s );
				} ) ) {
					update();
				}
			} );
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
