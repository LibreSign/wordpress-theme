<?php
/**
 * Checkout and purchase flow: CPF/CNPJ field, policy terms, guest redirect
 * and guest purchase CTA replacement.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// CPF / CNPJ billing field (WooCommerce Blocks 8.7+)
// ---------------------------------------------------------------------------

/**
 * Register CPF/CNPJ as an additional checkout field.
 * The field is optional in the UI; server-side validation requires it for
 * Brazilian customers so the correct fiscal document (NFS-e) can be issued.
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

// ---------------------------------------------------------------------------
// Checkout policy terms
// ---------------------------------------------------------------------------

/**
 * Customize the checkout terms checkbox text to point at the policy page.
 */
function libresign_checkout_policy_checkbox_text( $text ) {
	$policy_url = libresign_get_policy_url();

	return sprintf(
		/* translators: %s: policy page link */
		__( 'I agree to the %s before placing the order.', 'libresign' ),
		sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $policy_url ),
			esc_html__( 'terms and privacy policy', 'libresign' )
		)
	);
}
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'libresign_checkout_policy_checkbox_text' );

/**
 * Make sure checkout cannot be submitted without accepting the policy terms.
 */
function libresign_validate_checkout_policy_consent( $data, $errors ) {
	if ( empty( $data['terms'] ) ) {
		$errors->add(
			'libresign_policy_consent',
			__( 'You must agree to the policies before completing the purchase.', 'libresign' )
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'libresign_validate_checkout_policy_consent', 10, 2 );

// ---------------------------------------------------------------------------
// Guest purchase flow
// ---------------------------------------------------------------------------

/**
 * Force visitors to authenticate before accessing cart or checkout.
 */
function libresign_redirect_guests_from_purchase_flow() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	$is_cart     = function_exists( 'is_cart' ) && is_cart();
	$is_checkout = function_exists( 'is_checkout' ) && is_checkout();

	if ( ! $is_cart && ! $is_checkout ) {
		return;
	}

	wc_add_notice(
		__( 'Sign in to continue with your purchase.', 'libresign' ),
		'notice'
	);

	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/account/' );
	$redirect_to = rawurlencode( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );

	wp_safe_redirect( add_query_arg( 'redirect_to', $redirect_to, $account_url ) );
	exit;
}
add_action( 'template_redirect', 'libresign_redirect_guests_from_purchase_flow', 1 );

