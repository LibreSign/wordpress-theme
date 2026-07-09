<?php
/**
 * Shared site fragment synchronization and theme rendering.
 *
 * The theme keeps locally stored header/footer fragments fetched from the
 * deployed static site. Runtime requests never call back into the static site;
 * only the GitHub deploy webhook triggers a background refresh.
 */

defined( 'ABSPATH' ) || exit;

const LIBRESIGN_THEME_SITE_FRAGMENT_DEFAULT_LOCALE_KEY = 'default';

/**
 * Return the supported fragment types.
 *
 * @return array<int, string>
 */
function libresign_theme_site_fragment_supported_types() {
	return array( 'header', 'footer' );
}

/**
 * Resolve the storage directory slug for a fragment type.
 *
 * @param string $fragment_type Fragment type.
 * @return string
 */
function libresign_theme_site_fragment_storage_directory_name( $fragment_type ) {
	return 'header' === $fragment_type ? 'libresign-header' : 'libresign-footer';
}

/**
 * Normalize a site origin string.
 *
 * @param string $site_origin Raw site origin.
 * @return string
 */
function libresign_theme_site_fragment_normalize_site_origin( $site_origin ) {
	return rtrim( trim( (string) $site_origin ), '/' );
}

/**
 * Normalize a locale/tag into a path-friendly BCP47-style value.
 *
 * @param string $locale Raw locale/tag.
 * @return string
 */
function libresign_theme_site_fragment_normalize_locale_tag( $locale ) {
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
 * Convert locale to storage key.
 *
 * @param string $locale Locale.
 * @return string
 */
function libresign_theme_site_fragment_storage_key( $locale ) {
	$locale = libresign_theme_site_fragment_normalize_locale_tag( $locale );

	return '' === $locale ? LIBRESIGN_THEME_SITE_FRAGMENT_DEFAULT_LOCALE_KEY : $locale;
}

/**
 * Get the storage base dir for a fragment type.
 *
 * @param string $fragment_type Fragment type.
 * @return string
 */
function libresign_theme_site_fragment_storage_base_dir( $fragment_type ) {
	$uploads = wp_upload_dir();

	return trailingslashit( $uploads['basedir'] ) . libresign_theme_site_fragment_storage_directory_name( $fragment_type );
}

/**
 * Get the storage base URL for a fragment type.
 *
 * @param string $fragment_type Fragment type.
 * @return string
 */
function libresign_theme_site_fragment_storage_base_url( $fragment_type ) {
	$uploads = wp_upload_dir();

	return trailingslashit( $uploads['baseurl'] ) . libresign_theme_site_fragment_storage_directory_name( $fragment_type );
}

/**
 * Build the published fragment URL.
 *
 * @param string $site_origin   Static site origin.
 * @param string $fragment_type Fragment type.
 * @param string $locale        Locale.
 * @return string
 */
function libresign_theme_site_fragment_url( $site_origin, $fragment_type, $locale ) {
	$site_origin = libresign_theme_site_fragment_normalize_site_origin( $site_origin );
	$locale      = libresign_theme_site_fragment_normalize_locale_tag( $locale );
	$path        = '/fragments/';

	if ( '' !== $locale ) {
		$path .= rawurlencode( $locale ) . '/';
	}

	$path .= $fragment_type;

	return $site_origin . $path;
}

/**
 * Fetch a remote URL and return the HTTP code/body.
 *
 * @param string $url Target URL.
 * @return array<string, mixed>|WP_Error
 */
function libresign_theme_site_fragment_fetch_url( $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 5,
			'headers'     => array(
				'Accept' => 'text/html, text/css, application/javascript;q=0.9, */*;q=0.8',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = (string) wp_remote_retrieve_body( $response );

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error(
			'libresign_theme_site_fragment_http_status',
			sprintf(
				/* translators: 1: HTTP status code, 2: requested URL. */
				__( 'Unexpected HTTP status %1$d while fetching %2$s.', 'libresign' ),
				$code,
				$url
			),
			array(
				'status' => $code,
				'url'    => $url,
			)
		);
	}

	return array(
		'code' => $code,
		'body' => $body,
	);
}

/**
 * Determine whether an HTTP error can be ignored for an optional locale.
 *
 * @param WP_Error $error Error instance.
 * @return bool
 */
function libresign_theme_site_fragment_is_optional_http_error( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return false;
	}

	$data = $error->get_error_data();

	return is_array( $data ) && isset( $data['status'] ) && 404 === (int) $data['status'];
}

/**
 * Extract CSS/JS fragment asset URLs from fragment HTML.
 *
 * @param string $html Fragment HTML.
 * @return array<string, string>|WP_Error
 */
function libresign_theme_site_fragment_extract_asset_urls( $html ) {
	if (
		! preg_match( '/\bdata-fragment-css=("|\')([^"\']+)\1/i', $html, $css_matches )
		|| ! preg_match( '/\bdata-fragment-js=("|\')([^"\']+)\1/i', $html, $js_matches )
	) {
		return new WP_Error(
			'libresign_theme_site_fragment_missing_assets',
			__( 'Fragment HTML is missing the expected CSS/JS references.', 'libresign' )
		);
	}

	return array(
		'css' => (string) $css_matches[2],
		'js'  => (string) $js_matches[2],
	);
}

/**
 * Extract discovered locales from the default header fragment HTML.
 *
 * @param string $html Header fragment HTML.
 * @return array<int, string>
 */
function libresign_theme_site_fragment_extract_locales_from_header_html( $html ) {
	$locales = array();

	if ( preg_match_all( '~\/fragments(?:\/([^\/#?"\']+))?\/header(?:[\/#?"\']|$)~i', $html, $matches ) ) {
		foreach ( $matches[1] as $locale ) {
			$locale = libresign_theme_site_fragment_normalize_locale_tag( rawurldecode( (string) $locale ) );
			$locales[] = $locale;
		}
	}

	$locales[] = '';

	return array_values( array_unique( array_filter( $locales, 'is_string' ) ) );
}

/**
 * Build fallback locales from the local WordPress language context.
 *
 * @return array<int, string>
 */
function libresign_theme_site_fragment_fallback_locales() {
	$candidates = array();

	if ( function_exists( 'pll_languages_list' ) ) {
		$slugs = pll_languages_list( array( 'fields' => 'slug' ) );
		if ( is_array( $slugs ) ) {
			$candidates = array_merge( $candidates, $slugs );
		}

		$locales = pll_languages_list( array( 'fields' => 'locale' ) );
		if ( is_array( $locales ) ) {
			$candidates = array_merge( $candidates, $locales );
		}
	}

	$candidates[] = determine_locale();
	$candidates[] = get_locale();
	$candidates[] = '';

	$candidates = array_map( 'libresign_theme_site_fragment_normalize_locale_tag', $candidates );

	return array_values( array_unique( array_filter( $candidates, 'is_string' ) ) );
}

/**
 * Remove fragment asset data attributes after syncing.
 *
 * @param string $html Fragment HTML.
 * @return string
 */
function libresign_theme_site_fragment_strip_runtime_asset_attributes( $html ) {
	$html = preg_replace( '/\s+data-fragment-css=("|\')[^"\']+\1/i', '', $html ) ?? $html;
	$html = preg_replace( '/\s+data-fragment-js=("|\')[^"\']+\1/i', '', $html ) ?? $html;

	return $html;
}

/**
 * Rewrite root-relative URLs so stored artifacts continue pointing at the static site.
 *
 * @param string $content     Content to rewrite.
 * @param string $site_origin Static site origin.
 * @return string
 */
function libresign_theme_site_fragment_rewrite_root_relative_urls( $content, $site_origin ) {
	$site_origin = libresign_theme_site_fragment_normalize_site_origin( $site_origin );

	$content = preg_replace_callback(
		'/\b(href|src|action|poster)=("|\')(\/(?!\/)[^"\']*)\2/i',
		static function ( $matches ) use ( $site_origin ) {
			return $matches[1] . '=' . $matches[2] . $site_origin . $matches[3] . $matches[2];
		},
		$content
	) ?? $content;

	$content = preg_replace_callback(
		'~url\(\s*(?:("|\')\s*)?(\/(?!\/)[^)"\']+)(?:\s*\1)?\s*\)~i',
		static function ( $matches ) use ( $site_origin ) {
			$quote = isset( $matches[1] ) ? (string) $matches[1] : '';

			return 'url(' . $quote . $site_origin . $matches[2] . $quote . ')';
		},
		$content
	) ?? $content;

	return $content;
}

/**
 * Collect all fragment artifacts from the static site.
 *
 * @param string               $site_origin    Static site origin.
 * @param array<int, string>   $fragment_types Fragment types.
 * @param array<string, mixed> $context        Sync context.
 * @return array<string, mixed>|WP_Error
 */
function libresign_theme_site_fragment_collect_artifacts( $site_origin, $fragment_types, $context = array() ) {
	$site_origin = libresign_theme_site_fragment_normalize_site_origin( $site_origin );
	if ( '' === $site_origin ) {
		return new WP_Error(
			'libresign_theme_site_fragment_origin_missing',
			__( 'A static site origin is required to synchronize fragments.', 'libresign' )
		);
	}

	$default_header_url      = libresign_theme_site_fragment_url( $site_origin, 'header', '' );
	$default_header_response = libresign_theme_site_fragment_fetch_url( $default_header_url );
	if ( is_wp_error( $default_header_response ) ) {
		return $default_header_response;
	}

	$locales = array_merge(
		array( '' ),
		libresign_theme_site_fragment_extract_locales_from_header_html( (string) $default_header_response['body'] ),
		libresign_theme_site_fragment_fallback_locales()
	);
	$locales = array_values( array_unique( $locales ) );

	$asset_cache = array();
	$artifacts   = array();
	$summary     = array();

	foreach ( $fragment_types as $fragment_type ) {
		$summary[ $fragment_type ] = array();

		foreach ( $locales as $locale ) {
			$fragment_url = libresign_theme_site_fragment_url( $site_origin, $fragment_type, $locale );

			if ( 'header' === $fragment_type && '' === $locale ) {
				$fragment_response = $default_header_response;
			} else {
				$fragment_response = libresign_theme_site_fragment_fetch_url( $fragment_url );
			}

			if ( is_wp_error( $fragment_response ) ) {
				if ( '' !== $locale && libresign_theme_site_fragment_is_optional_http_error( $fragment_response ) ) {
					continue;
				}

				return $fragment_response;
			}

			$asset_urls = libresign_theme_site_fragment_extract_asset_urls( (string) $fragment_response['body'] );
			if ( is_wp_error( $asset_urls ) ) {
				if ( '' !== $locale ) {
					continue;
				}

				return $asset_urls;
			}

			foreach ( array( 'css', 'js' ) as $asset_type ) {
				$asset_url = (string) $asset_urls[ $asset_type ];
				if ( ! isset( $asset_cache[ $asset_url ] ) ) {
					$asset_response = libresign_theme_site_fragment_fetch_url( $asset_url );
					if ( is_wp_error( $asset_response ) ) {
						return $asset_response;
					}

					$asset_cache[ $asset_url ] = (string) $asset_response['body'];
				}
			}

			$html = libresign_theme_site_fragment_strip_runtime_asset_attributes( (string) $fragment_response['body'] );
			$html = libresign_theme_site_fragment_rewrite_root_relative_urls( $html, $site_origin );
			$css  = libresign_theme_site_fragment_rewrite_root_relative_urls( (string) $asset_cache[ $asset_urls['css'] ], $site_origin );
			$js   = libresign_theme_site_fragment_rewrite_root_relative_urls( (string) $asset_cache[ $asset_urls['js'] ], $site_origin );

			$artifact = array(
				'fragment_type' => $fragment_type,
				'locale'        => libresign_theme_site_fragment_normalize_locale_tag( $locale ),
				'locale_key'    => libresign_theme_site_fragment_storage_key( $locale ),
				'fragment_url'  => $fragment_url,
				'css_url'       => (string) $asset_urls['css'],
				'js_url'        => (string) $asset_urls['js'],
				'generated_at'  => isset( $context['generated_at'] ) ? (string) $context['generated_at'] : current_time( 'mysql', true ),
				'source_sha'    => isset( $context['source_sha'] ) ? (string) $context['source_sha'] : '',
				'source_url'    => isset( $context['source_url'] ) ? (string) $context['source_url'] : '',
				'html'          => $html,
				'css'           => $css,
				'js'            => $js,
			);
			$artifact['version'] = hash( 'sha256', $artifact['html'] . "\n" . $artifact['css'] . "\n" . $artifact['js'] );

			$artifacts[] = $artifact;
			$summary[ $fragment_type ][] = $artifact['locale_key'];
		}
	}

	foreach ( $summary as $fragment_type => $locales_for_type ) {
		$summary[ $fragment_type ] = array_values( array_unique( $locales_for_type ) );
	}

	return array(
		'locales'   => $locales,
		'artifacts' => $artifacts,
		'summary'   => $summary,
	);
}

/**
 * Persist a fully collected artifact set.
 *
 * @param array<string, mixed> $collected Collected artifact set.
 * @return array<string, mixed>|WP_Error
 */
function libresign_theme_site_fragment_persist_artifacts( $collected ) {
	foreach ( libresign_theme_site_fragment_supported_types() as $fragment_type ) {
		$base_dir = libresign_theme_site_fragment_storage_base_dir( $fragment_type );
		if ( is_dir( $base_dir ) ) {
			libresign_theme_site_fragment_recursive_delete( $base_dir );
		}
	}

	foreach ( $collected['artifacts'] as $artifact ) {
		$fragment_type = (string) $artifact['fragment_type'];
		$base_dir      = trailingslashit( libresign_theme_site_fragment_storage_base_dir( $fragment_type ) ) . $artifact['locale_key'];
		$base_url      = trailingslashit( libresign_theme_site_fragment_storage_base_url( $fragment_type ) ) . $artifact['locale_key'];
		$prefix        = 'header' === $fragment_type ? 'header' : 'footer';

		if ( ! wp_mkdir_p( $base_dir ) ) {
			return new WP_Error(
				'libresign_theme_site_fragment_storage_failed',
				sprintf(
					/* translators: %s: fragment type. */
					__( 'Unable to create local storage for the %s fragment.', 'libresign' ),
					$fragment_type
				)
			);
		}

		$html_file = $prefix . '.html';
		$css_file  = $prefix . '.css';
		$js_file   = $prefix . '.js';

		file_put_contents( trailingslashit( $base_dir ) . $html_file, (string) $artifact['html'] );
		file_put_contents( trailingslashit( $base_dir ) . $css_file, (string) $artifact['css'] );
		file_put_contents( trailingslashit( $base_dir ) . $js_file, (string) $artifact['js'] );

		$manifest = array(
			'fragment_type' => $fragment_type,
			'locale'        => (string) $artifact['locale'],
			'locale_key'    => (string) $artifact['locale_key'],
			'version'       => (string) $artifact['version'],
			'generated_at'  => (string) $artifact['generated_at'],
			'source_sha'    => (string) $artifact['source_sha'],
			'source_url'    => (string) $artifact['source_url'],
			'fragment_url'  => (string) $artifact['fragment_url'],
			'css_url'       => (string) $artifact['css_url'],
			'js_url'        => (string) $artifact['js_url'],
			'base_url'      => $base_url,
			'html_file'     => $html_file,
			'css_file'      => $css_file,
			'js_file'       => $js_file,
		);

		file_put_contents(
			trailingslashit( $base_dir ) . 'manifest.json',
			wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
		);
	}

	return $collected['summary'];
}

/**
 * Synchronize site fragments from the configured origin.
 *
 * @param string               $site_origin     Static site origin.
 * @param array<int, string>   $fragment_types  Fragment types.
 * @param array<string, mixed> $context         Sync context.
 * @return array<string, mixed>|WP_Error
 */
function libresign_theme_sync_site_fragments_from_origin( $site_origin, $fragment_types = array(), $context = array() ) {
	if ( empty( $fragment_types ) ) {
		$fragment_types = libresign_theme_site_fragment_supported_types();
	}

	$collected = libresign_theme_site_fragment_collect_artifacts( $site_origin, $fragment_types, $context );
	if ( is_wp_error( $collected ) ) {
		return $collected;
	}

	$persisted = libresign_theme_site_fragment_persist_artifacts( $collected );
	if ( is_wp_error( $persisted ) ) {
		return $persisted;
	}

	return array(
		'origin'  => libresign_theme_site_fragment_normalize_site_origin( $site_origin ),
		'synced'  => $persisted,
		'locales' => $collected['locales'],
	);
}

/**
 * Resolve locale lookup keys for the current request.
 *
 * @return array<int, string>
 */
function libresign_theme_site_fragment_locale_lookup_keys() {
	$candidates = array();

	if ( function_exists( 'pll_current_language' ) ) {
		$candidates[] = (string) pll_current_language( 'slug' );
		$candidates[] = (string) pll_current_language( 'locale' );
	}

	$candidates[] = (string) determine_locale();
	$candidates[] = (string) get_locale();

	$keys = array();
	foreach ( $candidates as $candidate ) {
		$normalized = libresign_theme_site_fragment_normalize_locale_tag( $candidate );
		if ( '' === $normalized ) {
			continue;
		}

		$keys[] = $normalized;
		$language_only = strtok( $normalized, '-' );
		if ( is_string( $language_only ) && '' !== $language_only ) {
			$keys[] = strtolower( $language_only );
		}
	}

	$keys[] = LIBRESIGN_THEME_SITE_FRAGMENT_DEFAULT_LOCALE_KEY;

	return array_values( array_unique( $keys ) );
}

/**
 * Load the best locally stored fragment artifact.
 *
 * @param string $fragment_type Fragment type.
 * @return array<string, mixed>|null
 */
function libresign_theme_site_fragment_load_artifact( $fragment_type ) {
	static $artifacts = array();

	if ( array_key_exists( $fragment_type, $artifacts ) ) {
		return $artifacts[ $fragment_type ];
	}

	$base = libresign_theme_site_fragment_storage_base_dir( $fragment_type );
	foreach ( libresign_theme_site_fragment_locale_lookup_keys() as $locale_key ) {
		$manifest_path = trailingslashit( $base ) . $locale_key . '/manifest.json';
		if ( ! is_file( $manifest_path ) ) {
			continue;
		}

		$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
		if ( ! is_array( $manifest ) ) {
			continue;
		}

		$dir       = dirname( $manifest_path );
		$prefix    = 'header' === $fragment_type ? 'header' : 'footer';
		$html_path = trailingslashit( $dir ) . ( $manifest['html_file'] ?? $prefix . '.html' );
		$css_path  = trailingslashit( $dir ) . ( $manifest['css_file'] ?? $prefix . '.css' );
		$js_path   = trailingslashit( $dir ) . ( $manifest['js_file'] ?? $prefix . '.js' );

		if ( ! is_file( $html_path ) ) {
			continue;
		}

		$manifest['html_path'] = $html_path;
		$manifest['css_path']  = is_file( $css_path ) ? $css_path : '';
		$manifest['js_path']   = is_file( $js_path ) ? $js_path : '';

		$artifacts[ $fragment_type ] = $manifest;

		return $artifacts[ $fragment_type ];
	}

	$artifacts[ $fragment_type ] = null;

	return null;
}

/**
 * Enqueue locally stored site fragment assets.
 *
 * @return void
 */
function libresign_theme_site_fragment_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	foreach ( libresign_theme_site_fragment_supported_types() as $fragment_type ) {
		$artifact = libresign_theme_site_fragment_load_artifact( $fragment_type );
		if ( ! is_array( $artifact ) ) {
			continue;
		}

		$handle   = 'libresign-site-' . $fragment_type . '-fragment';
		$base_url = trailingslashit( libresign_theme_site_fragment_storage_base_url( $fragment_type ) ) . $artifact['locale_key'];

		if ( ! empty( $artifact['css_path'] ) ) {
			wp_enqueue_style(
				$handle,
				trailingslashit( $base_url ) . $artifact['css_file'],
				array(),
				$artifact['version'] ?? filemtime( $artifact['css_path'] )
			);
		}

		if ( ! empty( $artifact['js_path'] ) ) {
			wp_enqueue_script(
				$handle,
				trailingslashit( $base_url ) . $artifact['js_file'],
				array(),
				$artifact['version'] ?? filemtime( $artifact['js_path'] ),
				true
			);
			wp_script_add_data( $handle, 'type', 'module' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'libresign_theme_site_fragment_enqueue_assets', 30 );

/**
 * Render the locally stored fragment HTML.
 *
 * @param string $fragment_type Fragment type.
 * @return string
 */
function libresign_theme_render_site_fragment( $fragment_type ) {
	$artifact = libresign_theme_site_fragment_load_artifact( $fragment_type );
	if ( ! is_array( $artifact ) || empty( $artifact['html_path'] ) || ! is_file( $artifact['html_path'] ) ) {
		return '';
	}

	return (string) file_get_contents( $artifact['html_path'] );
}

/**
 * Replace the header/footer template part with the locally stored fragment.
 *
 * @param string $block_content Rendered block content.
 * @param array  $block Parsed block.
 * @return string
 */
function libresign_theme_replace_site_fragment_template_part( $block_content, $block ) {
	$slug = isset( $block['attrs']['slug'] ) ? (string) $block['attrs']['slug'] : '';
	if ( ! in_array( $slug, libresign_theme_site_fragment_supported_types(), true ) ) {
		return $block_content;
	}

	$fragment = libresign_theme_render_site_fragment( $slug );

	return '' !== $fragment ? $fragment : $block_content;
}
add_filter( 'render_block_core/template-part', 'libresign_theme_replace_site_fragment_template_part', 20, 2 );

/**
 * Recursively delete a directory.
 *
 * @param string $path Directory path.
 * @return void
 */
function libresign_theme_site_fragment_recursive_delete( $path ) {
	if ( ! is_dir( $path ) ) {
		return;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $items as $item ) {
		$item_path = $item->getPathname();
		if ( $item->isDir() ) {
			rmdir( $item_path );
		} else {
			unlink( $item_path );
		}
	}

	rmdir( $path );
}
