<?php
/**
 * Theme setup: text domain, block stylesheets, pattern categories and logo fallback.
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

// ---------------------------------------------------------------------------
// Logo fallback
// ---------------------------------------------------------------------------

/**
 * Return the LibreSign logo URL used as a theme-level fallback.
 */
function libresign_get_theme_logo_url() {
	return 'https://github.com/LibreSign/site/raw/refs/heads/main/source/assets/images/logo/logo.svg';
}

/**
 * Detect whether the rendered custom logo points to a missing local upload.
 *
 * Checks every URL referenced by both the src attribute and the srcset
 * attribute. A single missing file is enough to trigger the fallback, because
 * the browser may select any of the srcset candidates based on viewport and
 * device pixel ratio.
 */
function libresign_custom_logo_needs_fallback( $custom_logo_html ) {
	if ( '' === trim( (string) $custom_logo_html ) ) {
		return true;
	}

	if ( ! preg_match( '/<img[^>]+>/', (string) $custom_logo_html, $tag_matches ) ) {
		return true;
	}

	$img_tag = $tag_matches[0];

	// Collect every image URL from src and srcset.
	$urls = array();

	if ( preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $m ) ) {
		$urls[] = html_entity_decode( $m[1] );
	}

	if ( preg_match( '/srcset=["\']([^"\']+)["\']/', $img_tag, $m ) ) {
		foreach ( explode( ',', html_entity_decode( $m[1] ) ) as $part ) {
			$candidate = trim( explode( ' ', trim( $part ) )[0] );
			if ( $candidate ) {
				$urls[] = $candidate;
			}
		}
	}

	if ( empty( $urls ) ) {
		return true;
	}

	$home_parts = wp_parse_url( home_url( '/' ) );

	foreach ( $urls as $url ) {
		$logo_parts = wp_parse_url( $url );

		if ( empty( $logo_parts['host'] ) || empty( $logo_parts['path'] ) ) {
			continue;
		}

		if ( empty( $home_parts['host'] ) || $logo_parts['host'] !== $home_parts['host'] ) {
			continue;
		}

		if ( 0 !== strpos( $logo_parts['path'], '/wp-content/uploads/' ) ) {
			continue;
		}

		$local_file = trailingslashit( ABSPATH ) . ltrim( $logo_parts['path'], '/' );

		if ( ! file_exists( $local_file ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Provide a stable fallback logo when the current site logo upload is missing.
 *
 * The checkout page (and any other page using the simplified block header) does
 * not load the webhook fragment header, so `get_custom_logo()` is called and
 * must resolve to a usable image even in environments where the local uploads
 * directory has not been seeded (fresh Docker volumes, staging, etc.).
 */
function libresign_filter_custom_logo( $custom_logo_html, $blog_id ) {
	if ( ! libresign_custom_logo_needs_fallback( $custom_logo_html ) ) {
		return $custom_logo_html;
	}

	// Replace only src/srcset in the existing logo HTML so the fallback keeps
	// the same width, height, and CSS classes that WordPress already generated.
	$fallback_src = esc_url( libresign_get_theme_logo_url() );
	$patched      = preg_replace( '/\ssrcset=["\'][^"\']*["\']/', '', $custom_logo_html );
	$patched      = preg_replace( '/\ssizes=["\'][^"\']*["\']/', '', $patched );
	$patched      = preg_replace( '/(<img[^>]+)src=["\'][^"\']*["\']/', '$1src="' . $fallback_src . '"', $patched );

	return $patched ?: $custom_logo_html;
}
add_filter( 'get_custom_logo', 'libresign_filter_custom_logo', 10, 2 );
