<?php
/**
 * Front-end stylesheet.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the SaaS stylesheet after WooCommerce's own styles so it can refine them.
 */
add_action( 'wp_enqueue_scripts', function () {
	$relative_path = 'assets/css/saas.css';
	$absolute_path = get_theme_file_path( $relative_path );

	wp_enqueue_style(
		'libresign-saas',
		get_theme_file_uri( $relative_path ),
		wp_style_is( 'woocommerce-general', 'registered' ) ? array( 'woocommerce-general' ) : array(),
		file_exists( $absolute_path ) ? (string) filemtime( $absolute_path ) : null
	);
}, 20 );
