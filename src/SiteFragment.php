<?php

namespace LibreSign\WordPressTheme;

final class SiteFragment {

	public const DEFAULT_LOCALE_KEY = 'default';

	public static function origin( string $origin ): string {
		return rtrim( trim( $origin ), '/' );
	}

	public static function language_tag( string $locale ): string {
		$parts = array_values( array_filter( explode( '-', trim( str_replace( '_', '-', $locale ) ) ), static fn ( $part ) => '' !== $part ) );
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
			} elseif ( 4 === strlen( $part ) ) {
				$parts[ $index ] = ucfirst( strtolower( $part ) );
			}
		}

		return implode( '-', $parts );
	}

	public static function storage_key( string $locale ): string {
		$tag = self::language_tag( $locale );

		return '' === $tag ? self::DEFAULT_LOCALE_KEY : $tag;
	}

	public static function url( string $origin, string $fragment_type, string $locale ): string {
		$tag = self::language_tag( $locale );

		return self::origin( $origin ) . '/fragments/' . ( '' === $tag ? '' : rawurlencode( $tag ) . '/' ) . $fragment_type;
	}

	/**
	 * @return array{css: string, js: string}|null
	 */
	public static function asset_urls( string $html ): ?array {
		if (
			! preg_match( '/\bdata-fragment-css=("|\')([^"\']+)\1/i', $html, $css_matches )
			|| ! preg_match( '/\bdata-fragment-js=("|\')([^"\']+)\1/i', $html, $js_matches )
		) {
			return null;
		}

		return array(
			'css' => $css_matches[2],
			'js'  => $js_matches[2],
		);
	}

	/**
	 * @return string[]
	 */
	public static function linked_locales( string $header_html ): array {
		$locales = array();

		if ( preg_match_all( '~\/fragments(?:\/([^\/#?"\']+))?\/header(?:[\/#?"\']|$)~i', $header_html, $matches ) ) {
			foreach ( $matches[1] as $locale ) {
				$locales[] = self::language_tag( rawurldecode( $locale ) );
			}
		}

		$locales[] = '';

		return array_values( array_unique( $locales ) );
	}

	/**
	 * @param string[] $candidates
	 * @return string[]
	 */
	public static function lookup_keys( array $candidates ): array {
		$keys = array();

		foreach ( $candidates as $candidate ) {
			$tag = self::language_tag( $candidate );
			if ( '' === $tag ) {
				continue;
			}

			$keys[] = $tag;
			$keys[] = strtolower( (string) strtok( $tag, '-' ) );
		}

		$keys[] = self::DEFAULT_LOCALE_KEY;

		return array_values( array_unique( $keys ) );
	}

	public static function without_asset_attributes( string $html ): string {
		$html = preg_replace( '/\s+data-fragment-css=("|\')[^"\']+\1/i', '', $html ) ?? $html;

		return preg_replace( '/\s+data-fragment-js=("|\')[^"\']+\1/i', '', $html ) ?? $html;
	}

	public static function with_absolute_urls( string $content, string $origin ): string {
		$origin = self::origin( $origin );

		$content = preg_replace_callback(
			'/\b(href|src|action|poster)=("|\')(\/(?!\/)[^"\']*)\2/i',
			static fn ( $matches ) => $matches[1] . '=' . $matches[2] . $origin . $matches[3] . $matches[2],
			$content
		) ?? $content;

		return preg_replace_callback(
			'~url\(\s*(?:("|\')\s*)?(\/(?!\/)[^)"\']+)(?:\s*\1)?\s*\)~i',
			static fn ( $matches ) => 'url(' . $matches[1] . $origin . $matches[2] . $matches[1] . ')',
			$content
		) ?? $content;
	}
}
