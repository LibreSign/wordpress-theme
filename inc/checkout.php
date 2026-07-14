<?php
/**
 * Checkout policy terms: customize the terms checkbox and validate consent.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

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
 * Prevent checkout submission without policy terms acceptance.
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
