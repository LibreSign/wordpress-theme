<?php
/**
 * Workspace creation: an account only exists as part of a purchase, so
 * registration is offered only while the cart holds a plan and leads to
 * checkout.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the site policy / privacy page URL.
 */
function libresign_get_policy_url() {
	$policy_page_id = 3;
	$policy_url     = get_permalink( $policy_page_id );

	if ( ! empty( $policy_url ) ) {
		return $policy_url;
	}

	return home_url( '/privacy-police/' );
}

/**
 * Whether a purchase is in progress, which decides what the account page offers
 * and where authentication leads.
 */
function libresign_cart_has_items() {
	if ( ! function_exists( 'WC' ) ) {
		return false;
	}

	$wc = WC();

	return isset( $wc->cart ) && $wc->cart && ! $wc->cart->is_empty();
}

/**
 * Require the terms consent on the workspace registration form.
 *
 * Hooked on 'woocommerce_process_registration_errors' rather than
 * 'woocommerce_registration_errors': the latter runs inside
 * wc_create_new_customer(), which checkout also uses, and would reject every
 * account created there since the consent field only exists on this form.
 */
function libresign_validate_workspace_terms( $errors ) {
	if ( empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			'libresign_workspace_terms',
			__( 'You must accept the terms to create your workspace.', 'libresign' )
		);
	}

	return $errors;
}
add_filter( 'woocommerce_process_registration_errors', 'libresign_validate_workspace_terms', 10, 1 );

/**
 * Persist the terms consent so the approval is auditable.
 */
function libresign_persist_workspace_consent( $customer_id ) {
	if ( empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	update_user_meta( $customer_id, 'libresign_workspace_terms', 'yes' );
	update_user_meta( $customer_id, 'libresign_workspace_terms_date', current_time( 'mysql' ) );
}
add_action( 'woocommerce_created_customer', 'libresign_persist_workspace_consent', 10, 1 );

/**
 * Send users to their destination after logging in.
 */
function libresign_login_redirect( $redirect, $user ) {
	return libresign_get_purchase_redirect_target();
}
add_filter( 'woocommerce_login_redirect', 'libresign_login_redirect', 10, 2 );

/**
 * Send users to their destination after registering.
 */
function libresign_registration_redirect( $redirect ) {
	return libresign_get_purchase_redirect_target();
}
add_filter( 'woocommerce_registration_redirect', 'libresign_registration_redirect', 10, 1 );
