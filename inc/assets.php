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

add_action( 'wp_enqueue_scripts', function () {
	$vendor_styles = array(
		'bootstrap-css' => 'assets/vendor/bootstrap/bootstrap-grid.min.css',
		'lineicons'     => 'assets/vendor/lineicons/lineicons.css',
	);

	foreach ( $vendor_styles as $handle => $relative_path ) {
		wp_enqueue_style(
			$handle,
			get_theme_file_uri( $relative_path ),
			array(),
			(string) filemtime( get_theme_file_path( $relative_path ) )
		);
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	$relative_path = 'assets/css/header-footer.css';

	wp_enqueue_style(
		'libresign-header-footer',
		get_theme_file_uri( $relative_path ),
		array(),
		(string) filemtime( get_theme_file_path( $relative_path ) )
	);
}, 40 );
