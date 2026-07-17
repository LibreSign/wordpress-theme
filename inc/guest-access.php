<?php
/**
 * Guest access control: redirect unauthenticated visitors away from
 * cart and checkout so they must sign in before completing a purchase.
 *
 * After login the visitor is sent back to their original destination via the
 * redirect_to query parameter, preserving any items already in the session
 * cart.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect guest visitors from cart or checkout to the account page.
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
