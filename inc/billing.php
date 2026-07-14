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
