<?php
/**
 * Theme setup: text domain, block styles, block stylesheets, pattern
 * categories and logo fallback.
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
// Block styles
// ---------------------------------------------------------------------------

if ( ! function_exists( 'libresign_block_styles' ) ) :
	/**
	 * Register custom block styles.
	 *
	 * @since libresign 1.0
	 */
	function libresign_block_styles() {
		register_block_style(
			'core/post-terms',
			array(
				'name'         => 'pill',
				'label'        => __( 'Pill', 'libresign' ),
				'inline_style' => '
				.is-style-pill a,
				.is-style-pill span:not([class], [data-rich-text-placeholder]) {
					display: inline-block;
					background-color: var(--wp--preset--color--base-2);
					padding: 0.375rem 0.875rem;
					border-radius: var(--wp--preset--spacing--20);
				}
				.is-style-pill a:hover {
					background-color: var(--wp--preset--color--contrast-3);
				}',
			)
		);
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'libresign' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}
				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
		register_block_style(
			'core/navigation-link',
			array(
				'name'         => 'arrow-link',
				'label'        => __( 'With arrow', 'libresign' ),
				'inline_style' => '
				.is-style-arrow-link .wp-block-navigation-item__label:after {
					content: "\2197";
					padding-inline-start: 0.25rem;
					vertical-align: middle;
					text-decoration: none;
					display: inline-block;
				}',
			)
		);
		register_block_style(
			'core/heading',
			array(
				'name'         => 'asterisk',
				'label'        => __( 'With asterisk', 'libresign' ),
				'inline_style' => "
				.is-style-asterisk:before {
					content: '';
					width: 1.5rem;
					height: 3rem;
					background: var(--wp--preset--color--contrast-2, currentColor);
					clip-path: path('M11.93.684v8.039l5.633-5.633 1.216 1.23-5.66 5.66h8.04v1.737H13.2l5.701 5.701-1.23 1.23-5.742-5.742V21h-1.737v-8.094l-5.77 5.77-1.23-1.217 5.743-5.742H.842V9.98h8.162l-5.701-5.7 1.23-1.231 5.66 5.66V.684h1.737Z');
					display: block;
				}
				.is-style-asterisk:empty:before { content: none; }
				.is-style-asterisk:-moz-only-whitespace:before { content: none; }
				.is-style-asterisk.has-text-align-center:before { margin: 0 auto; }
				.is-style-asterisk.has-text-align-right:before { margin-left: auto; }
				.rtl .is-style-asterisk.has-text-align-left:before { margin-right: auto; }",
			)
		);
	}
endif;

add_action( 'init', 'libresign_block_styles' );

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
 */
function libresign_custom_logo_needs_fallback( $custom_logo_html ) {
	if ( '' === trim( (string) $custom_logo_html ) ) {
		return true;
	}

	if ( ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', (string) $custom_logo_html, $matches ) ) {
		return true;
	}

	$logo_url   = html_entity_decode( $matches[1] );
	$logo_parts = wp_parse_url( $logo_url );
	$home_parts = wp_parse_url( home_url( '/' ) );

	if ( empty( $logo_parts['host'] ) || empty( $logo_parts['path'] ) ) {
		return false;
	}

	if ( empty( $home_parts['host'] ) || $logo_parts['host'] !== $home_parts['host'] ) {
		return false;
	}

	if ( 0 !== strpos( $logo_parts['path'], '/wp-content/uploads/' ) ) {
		return false;
	}

	$local_file = trailingslashit( ABSPATH ) . ltrim( $logo_parts['path'], '/' );

	return ! file_exists( $local_file );
}

/**
 * Provide a stable fallback logo when the current site logo upload is missing.
 */
function libresign_filter_custom_logo( $custom_logo_html, $blog_id ) {
	if ( ! libresign_custom_logo_needs_fallback( $custom_logo_html ) ) {
		return $custom_logo_html;
	}

	$aria_current = is_front_page() && ! is_paged() ? ' aria-current="page"' : '';
	$alt_text     = get_bloginfo( 'name' );

	return sprintf(
		'<a href="%1$s" class="custom-logo-link" rel="home"%2$s><img src="%3$s" class="custom-logo" alt="%4$s" decoding="async" fetchpriority="high" /></a>',
		esc_url( home_url( '/' ) ),
		$aria_current,
		esc_url( libresign_get_theme_logo_url() ),
		esc_attr( $alt_text )
	);
}
add_filter( 'get_custom_logo', 'libresign_filter_custom_logo', 10, 2 );
