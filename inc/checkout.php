<?php
/**
 * Checkout policy terms: require the consent and link the policy.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Require the policy consent on the checkout.
 */
function libresign_theme_register_policy_consent_field() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'            => 'libresign/policy-consent',
			'label'         => __( 'I agree to the terms and privacy policy before placing the order.', 'libresign' ),
			'location'      => 'order',
			'type'          => 'checkbox',
			'required'      => true,
			'error_message' => __( 'You must agree to the policies before completing the purchase.', 'libresign' ),
		)
	);
}
add_action( 'woocommerce_init', 'libresign_theme_register_policy_consent_field' );

/**
 * Refuse an order placed through the classic checkout without the policy consent.
 */
function libresign_theme_validate_checkout_policy_consent( $data, $errors ) {
	if ( empty( $data['terms'] ) ) {
		$errors->add(
			'libresign_policy_consent',
			__( 'You must agree to the policies before completing the purchase.', 'libresign' )
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'libresign_theme_validate_checkout_policy_consent', 10, 2 );

/**
 * Link the checkout terms text to the policy.
 */
function libresign_theme_link_checkout_terms_to_policy( $block_content ) {
	$text = sprintf(
		/* translators: %s: policy page link */
		__( 'Read the %s.', 'libresign' ),
		sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( libresign_theme_get_policy_url() ),
			esc_html__( 'terms and privacy policy', 'libresign' )
		)
	);

	return (string) preg_replace(
		'/<div(?=[^>]*\bwp-block-woocommerce-checkout-terms-block\b)/',
		'<div data-text="' . esc_attr( $text ) . '"',
		(string) $block_content,
		1
	);
}
add_filter( 'render_block_woocommerce/checkout-terms-block', 'libresign_theme_link_checkout_terms_to_policy' );
