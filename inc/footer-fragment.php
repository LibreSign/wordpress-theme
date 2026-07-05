<?php
/**
 * Footer fragment integration for the LibreSign theme.
 *
 * Keeps the static-site footer bridge isolated from the general theme bootstrap
 * so `functions.php` stays readable and responsibilities remain grouped.
 *
 * Primary configuration lives in the theme Customizer. Environment variables
 * are supported as infrastructure-level fallbacks only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize footer fragment origins stored in theme configuration.
 *
 * @param mixed $value Raw value from the Customizer.
 * @return string
 */
function libresign_footer_fragment_sanitize_origins( $value ) {
	$origins = libresign_footer_fragment_normalize_origins( $value );

	return implode( PHP_EOL, $origins );
}

/**
 * Register footer fragment settings in the WordPress Customizer.
 *
 * @param \WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function libresign_footer_fragment_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'libresign_footer_fragment',
		array(
			'title'       => __( 'Footer integration', 'libresign' ),
			'priority'    => 160,
			'description' => __( 'Configure where the theme should fetch the shared static-site footer fragment.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_footer_fragment_origins',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'libresign_footer_fragment_sanitize_origins',
			'default'           => '',
		)
	);

	$wp_customize->add_control(
		'libresign_footer_fragment_origins',
		array(
			'label'       => __( 'Footer fragment origins', 'libresign' ),
			'section'     => 'libresign_footer_fragment',
			'type'        => 'textarea',
			'description' => __( 'One origin per line or comma separated. Example: http://localhost:8081 or https://libresign.coop', 'libresign' ),
		)
	);
}
add_action( 'customize_register', 'libresign_footer_fragment_customize_register' );

/**
 * Clear cached footer payload when theme-level configuration changes.
 *
 * @return void
 */
function libresign_footer_fragment_flush_cache() {
	delete_transient( libresign_footer_fragment_cache_key() );
	delete_option( libresign_footer_fragment_backup_key() );
}

add_action( 'customize_save_after', 'libresign_footer_fragment_flush_cache' );

/**
 * Normalize a footer fragment origin list from theme configuration or env.
 *
 * Accepted formats:
 * - string with one URL
 * - string with URLs separated by commas or new lines
 * - array of URLs
 *
 * @param mixed $value Raw configured value.
 * @return array<int, string>
 */
function libresign_footer_fragment_normalize_origins( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/[\r\n,]+/', $value ) ?: array();
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$origins = array();

	foreach ( $value as $origin ) {
		$origin = trim( (string) $origin );

		if ( '' === $origin ) {
			continue;
		}

		$parts = wp_parse_url( $origin );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			continue;
		}

		$origins[] = rtrim( $origin, '/' );
	}

	return array_values( array_unique( $origins ) );
}

/**
 * Resolve candidate origins for the static-site footer fragment.
 *
 * Configuration priority:
 * 1. Theme mod `libresign_footer_fragment_origins`
 * 2. Theme mod `libresign_footer_fragment_origin`
 * 3. Env `LIBRESIGN_FOOTER_FRAGMENT_ORIGINS`
 * 4. Env `LIBRESIGN_FOOTER_FRAGMENT_ORIGIN`
 * 5. Option `libresign_footer_fragment_origins` (legacy fallback)
 * 6. Option `libresign_footer_fragment_origin` (legacy fallback)
 * 7. Public production footer
 *
 * @return array<int, string>
 */
function libresign_footer_fragment_origins() {
	$configured_origins = array(
		get_theme_mod( 'libresign_footer_fragment_origins' ),
		get_theme_mod( 'libresign_footer_fragment_origin' ),
		getenv( 'LIBRESIGN_FOOTER_FRAGMENT_ORIGINS' ),
		getenv( 'LIBRESIGN_FOOTER_FRAGMENT_ORIGIN' ),
		get_option( 'libresign_footer_fragment_origins' ),
		get_option( 'libresign_footer_fragment_origin' ),
	);

	foreach ( $configured_origins as $configured_origin ) {
		$origins = libresign_footer_fragment_normalize_origins( $configured_origin );

		if ( ! empty( $origins ) ) {
			return apply_filters( 'libresign_footer_fragment_origins', $origins );
		}
	}

	return apply_filters(
		'libresign_footer_fragment_origins',
		array( 'https://libresign.coop' )
	);
}

/**
 * Normalize a locale/tag into a path-friendly BCP47-style value.
 *
 * @param string $locale Raw locale/tag.
 * @return string
 */
function libresign_footer_fragment_normalize_locale_tag( $locale ) {
	$locale = trim( str_replace( '_', '-', (string) $locale ) );

	if ( '' === $locale ) {
		return '';
	}

	$parts = array_values( array_filter( explode( '-', $locale ), 'strlen' ) );

	if ( empty( $parts ) ) {
		return '';
	}

	$parts[0] = strtolower( $parts[0] );

	foreach ( $parts as $index => $part ) {
		if ( 0 === $index ) {
			continue;
		}

		if ( 2 === strlen( $part ) || 3 === strlen( $part ) ) {
			$parts[ $index ] = strtoupper( $part );
			continue;
		}

		if ( 4 === strlen( $part ) ) {
			$parts[ $index ] = ucfirst( strtolower( $part ) );
			continue;
		}

		$parts[ $index ] = $part;
	}

	return implode( '-', $parts );
}

/**
 * Resolve dynamic locale segments that may exist in the static site.
 *
 * @return array<int, string>
 */
function libresign_footer_fragment_locale_segments() {
	$candidates = array();

	if ( function_exists( 'pll_current_language' ) ) {
		$candidates[] = (string) pll_current_language( 'slug' );
		$candidates[] = (string) pll_current_language( 'locale' );
	}

	$candidates[] = (string) determine_locale();
	$candidates[] = (string) get_locale();

	$segments = array( '' );

	foreach ( $candidates as $candidate ) {
		$normalized = libresign_footer_fragment_normalize_locale_tag( $candidate );

		if ( '' === $normalized ) {
			continue;
		}

		$segments[] = '/' . $normalized;

		$language_only = strtok( $normalized, '-' );
		if ( is_string( $language_only ) && '' !== $language_only ) {
			$segments[] = '/' . strtolower( $language_only );
		}
	}

	return array_values( array_unique( $segments ) );
}

/**
 * Resolve candidate URLs for the embeddable footer fragment.
 *
 * @return array<int, string>
 */
function libresign_footer_fragment_urls() {
	$urls            = array();
	$locale_segments = libresign_footer_fragment_locale_segments();

	foreach ( libresign_footer_fragment_origins() as $origin ) {
		$origin = rtrim( (string) $origin, '/' );

		foreach ( $locale_segments as $locale_segment ) {
			if ( '' === $locale_segment ) {
				$urls[] = $origin . '/fragments/footer/';
				continue;
			}

			$urls[] = $origin . $locale_segment . '/fragments/footer/';
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Build the cache key for the resolved footer fragment.
 *
 * @return string
 */
function libresign_footer_fragment_cache_key() {
	return 'libresign_footer_fragment_' . md5( wp_json_encode( libresign_footer_fragment_urls() ) );
}

/**
 * Build the backup option key for the footer fragment.
 *
 * @return string
 */
function libresign_footer_fragment_backup_key() {
	return libresign_footer_fragment_cache_key() . '_backup';
}

/**
 * Parse the footer fragment response.
 *
 * @param string $html Raw fragment HTML.
 * @return array<string, string>
 */
function libresign_footer_fragment_parse_payload( $html ) {
	$payload = array(
		'html' => trim( (string) $html ),
		'css'  => '',
		'js'   => '',
	);

	if ( preg_match( '/data-fragment-css=["\']([^"\']+)["\']/', $payload['html'], $matches ) ) {
		$payload['css'] = $matches[1];
	}

	if ( preg_match( '/data-fragment-js=["\']([^"\']+)["\']/', $payload['html'], $matches ) ) {
		$payload['js'] = $matches[1];
	}

	return $payload;
}

/**
 * Fetch the first available footer fragment from the static site.
 *
 * @return array<string, string>
 */
function libresign_footer_fragment_fetch_remote_payload() {
	foreach ( libresign_footer_fragment_urls() as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept' => 'text/html',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			continue;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( 200 === $code && '' !== trim( $body ) ) {
			return libresign_footer_fragment_parse_payload( $body );
		}
	}

	return array();
}

/**
 * Resolve the cached footer fragment payload.
 *
 * @return array<string, string>
 */
function libresign_footer_fragment_get_payload() {
	static $payload = null;

	if ( null !== $payload ) {
		return $payload;
	}

	$cache_key = libresign_footer_fragment_cache_key();
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) && ! empty( $cached['html'] ) ) {
		$payload = $cached;
		return $payload;
	}

	$fetched = libresign_footer_fragment_fetch_remote_payload();

	if ( ! empty( $fetched['html'] ) ) {
		set_transient( $cache_key, $fetched, 6 * HOUR_IN_SECONDS );
		update_option( libresign_footer_fragment_backup_key(), $fetched, false );
		$payload = $fetched;
		return $payload;
	}

	$backup = get_option( libresign_footer_fragment_backup_key(), array() );
	if ( is_array( $backup ) && ! empty( $backup['html'] ) ) {
		$payload = $backup;
		return $payload;
	}

	$payload = array();
	return $payload;
}

/**
 * Enqueue the footer fragment assets.
 *
 * @return void
 */
function libresign_footer_fragment_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	$payload = libresign_footer_fragment_get_payload();

	if ( ! empty( $payload['css'] ) ) {
		wp_enqueue_style(
			'libresign-site-footer-fragment',
			$payload['css'],
			array(),
			null
		);
	}

	if ( ! empty( $payload['js'] ) ) {
		wp_enqueue_script(
			'libresign-site-footer-fragment',
			$payload['js'],
			array(),
			null,
			true
		);
		wp_script_add_data( 'libresign-site-footer-fragment', 'type', 'module' );
	}
}
add_action( 'wp_enqueue_scripts', 'libresign_footer_fragment_enqueue_assets', 30 );

/**
 * Render the footer fragment HTML.
 *
 * @return string
 */
function libresign_render_footer_fragment() {
	$payload = libresign_footer_fragment_get_payload();

	if ( empty( $payload['html'] ) ) {
		return '<!-- LibreSign footer fragment unavailable -->';
	}

	return $payload['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Replace the footer template part with the static-site fragment.
 *
 * @param string $block_content Rendered block content.
 * @param array  $block Parsed block data.
 * @return string
 */
function libresign_replace_footer_template_part( $block_content, $block ) {
	$slug = isset( $block['attrs']['slug'] ) ? (string) $block['attrs']['slug'] : '';

	if ( 'footer' !== $slug ) {
		return $block_content;
	}

	return libresign_render_footer_fragment();
}
add_filter( 'render_block_core/template-part', 'libresign_replace_footer_template_part', 20, 2 );