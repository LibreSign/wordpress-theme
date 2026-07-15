<?php
/**
 * Theme setup: text domain loading.
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

