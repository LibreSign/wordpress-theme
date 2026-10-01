<?php

namespace LibreSign\WordPressTheme;

final class Logo {

	/**
	 * @return string[]
	 */
	public static function image_urls( string $html ): array {
		if ( ! preg_match( '/<img[^>]+>/', $html, $tag_matches ) ) {
			return array();
		}

		$urls = array();

		if ( preg_match( '/src=["\']([^"\']+)["\']/', $tag_matches[0], $matches ) ) {
			$urls[] = html_entity_decode( $matches[1] );
		}

		if ( preg_match( '/srcset=["\']([^"\']+)["\']/', $tag_matches[0], $matches ) ) {
			foreach ( explode( ',', html_entity_decode( $matches[1] ) ) as $candidate ) {
				$url = trim( explode( ' ', trim( $candidate ) )[0] );
				if ( $url ) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	public static function with_fallback( string $html, string $light_logo_url, string $dark_logo_url ): string {
		$patched = preg_replace( '/\ssrcset=["\'][^"\']*["\']/', '', $html ) ?? $html;
		$patched = preg_replace( '/\ssizes=["\'][^"\']*["\']/', '', $patched ) ?? $patched;
		$patched = preg_replace( '/(<img[^>]+)src=["\'][^"\']*["\']/', '$1src="' . $light_logo_url . '"', $patched ) ?? $patched;

		return preg_replace(
			'/(<img[^>]+>)/',
			'<picture>'
				. '<source media="(prefers-color-scheme: dark)" srcset="' . $dark_logo_url . '">'
				. '<source media="(prefers-color-scheme: light)" srcset="' . $light_logo_url . '">'
				. '$1'
				. '</picture>',
			$patched
		) ?? $patched;
	}
}
