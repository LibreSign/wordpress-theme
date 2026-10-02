<?php
/**
 * SaaS onboarding: shown only while buying a new plan.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

function libresign_theme_show_saas_onboarding_only_on_new_purchases( $block_content, $block ) {
	$class_names = explode( ' ', $block['attrs']['className'] ?? '' );
	if ( ! in_array( 'libresign-saas-onboarding', $class_names, true ) ) {
		return $block_content;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		return '';
	}

	$order_id = absint( get_query_var( 'order-received' ) );
	if ( $order_id && function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id, array( 'renewal', 'switch', 'resubscribe' ) ) ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block_core/group', 'libresign_theme_show_saas_onboarding_only_on_new_purchases', 10, 2 );
