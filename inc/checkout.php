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
