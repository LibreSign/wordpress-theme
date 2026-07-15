<?php
/**
 * Site logo fallback for environments with missing upload thumbnails.
 *
 * WordPress stores the site logo as a media library upload and generates
 * several resized thumbnails referenced in the <img srcset> attribute. In
 * fresh Docker volumes or staging environments those thumbnails may not
 * exist, causing the browser to pick a srcset candidate that returns 404
 * and rendering the logo as a broken image.
 *
 * This file hooks into get_custom_logo to replace the broken upload with a
 * stable remote SVG whenever any of the referenced local files is absent.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

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
 * not load the webhook fragment header, so get_custom_logo() is called and
 * must resolve to a usable image even in environments where the local uploads
 * directory has not been seeded (fresh Docker volumes, staging, etc.).
 *
 * Patches the existing logo HTML in-place — replaces only src and removes
 * srcset/sizes — so the fallback keeps the width, height, and CSS classes
 * that WordPress generated from the block settings.
 */
function libresign_filter_custom_logo( $custom_logo_html, $blog_id ) {
	if ( ! libresign_custom_logo_needs_fallback( $custom_logo_html ) ) {
		return $custom_logo_html;
	}

	$fallback_src = esc_url( libresign_get_theme_logo_url() );
	$patched      = preg_replace( '/\ssrcset=["\'][^"\']*["\']/', '', $custom_logo_html );
	$patched      = preg_replace( '/\ssizes=["\'][^"\']*["\']/', '', $patched );
	$patched      = preg_replace( '/(<img[^>]+)src=["\'][^"\']*["\']/', '$1src="' . $fallback_src . '"', $patched );

	return $patched ?: $custom_logo_html;
}
add_filter( 'get_custom_logo', 'libresign_filter_custom_logo', 10, 2 );
