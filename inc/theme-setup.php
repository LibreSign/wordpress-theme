<?php
/**
 * Theme setup: text domain, block stylesheets and pattern categories.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load theme translations.
 * Polylang sets the locale from the URL before this runs, so __() resolves
 * to the correct language automatically.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'libresign', get_template_directory() . '/languages' );
} );

// ---------------------------------------------------------------------------
// Block stylesheets
// ---------------------------------------------------------------------------

if ( ! function_exists( 'libresign_block_stylesheets' ) ) :
	/**
	 * Enqueue custom block stylesheets (loaded only when block is rendered).
	 *
	 * @since libresign 1.0
	 */
	function libresign_block_stylesheets() {
		wp_enqueue_block_style(
			'core/button',
			array(
				'handle' => 'libresign-button-style-outline',
				'src'    => get_parent_theme_file_uri( 'assets/css/button-outline.css' ),
				'ver'    => wp_get_theme( get_template() )->get( 'Version' ),
				'path'   => get_parent_theme_file_path( 'assets/css/button-outline.css' ),
			)
		);
	}
endif;

add_action( 'init', 'libresign_block_stylesheets' );

// ---------------------------------------------------------------------------
// Block pattern categories
// ---------------------------------------------------------------------------

if ( ! function_exists( 'libresign_pattern_categories' ) ) :
	/**
	 * Register block pattern categories.
	 *
	 * @since libresign 1.0
	 */
	function libresign_pattern_categories() {
		register_block_pattern_category(
			'libresign_page',
			array(
				'label'       => _x( 'Pages', 'Block pattern category', 'libresign' ),
				'description' => __( 'A collection of full page layouts.', 'libresign' ),
			)
		);
	}
endif;

add_action( 'init', 'libresign_pattern_categories' );
