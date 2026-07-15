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
 * Require CPF/CNPJ only when the billing country is Brazil.
 */
add_action( 'woocommerce_validate_additional_field', function ( \WP_Error $errors, string $field_key, $field_value ) {
	if ( 'libresign/cpf-cnpj' !== $field_key ) {
		return;
	}

	$country = function_exists( 'WC' ) && WC()->customer ? WC()->customer->get_billing_country() : '';

	if ( 'BR' === $country && '' === trim( (string) $field_value ) ) {
		$errors->add(
			'libresign-cpf-cnpj-required',
			__( 'Please enter your CPF or CNPJ for tax invoice issuance.', 'libresign' )
		);
	}
}, 10, 3 );

/**
 * Conditionally show/hide the CPF/CNPJ field based on the billing country.
 *
 * WooCommerce Blocks renders additional fields for all countries; this script
 * hides the row when the country is not Brazil and marks it as required (with
 * a visible asterisk) when Brazil is selected.
 *
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

		// WooCommerce Blocks renders the billing country as a native select
		// inside the billing address section.
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

			row.style.display = isBrazil ? '' : 'none';

			var input = row.querySelector( 'input' );
			if ( input ) {
				input.required = isBrazil;
				input.setAttribute( 'aria-required', isBrazil ? 'true' : 'false' );
			}

			// Add/remove required asterisk beside the label.
			var label = row.querySelector( 'label' );
			if ( label ) {
				var existing = label.querySelector( '.libresign-required-star' );
				if ( isBrazil && ! existing ) {
					var star = document.createElement( 'span' );
					star.className = 'libresign-required-star required';
					star.setAttribute( 'aria-hidden', 'true' );
					star.textContent = ' *';
					label.appendChild( star );
				} else if ( ! isBrazil && existing ) {
					existing.remove();
				}
			}
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
