<?php
/**
 * Subscription plans: entry point to the plans listing.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of the plans listing, falling back to the WooCommerce shop.
 */
function libresign_get_plans_url() {
	$page = get_page_by_path( 'plans' );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return (string) get_permalink( $page );
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( ! empty( $shop_url ) ) {
			return (string) $shop_url;
		}
	}

	return '';
}
