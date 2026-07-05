<?php
/**
 * Footer fragment integration for the LibreSign theme.
 *
 * The static site pushes footer artifacts to WordPress through a signed webhook.
 * WordPress persists those artifacts locally and the theme renders only the
 * stored files, avoiding runtime fetches back to the static site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LIBRESIGN_FOOTER_WEBHOOK_ROUTE      = '/libresign/v1/footer-fragment';
const LIBRESIGN_FOOTER_ASSET_BASE_TOKEN   = '__LIBRESIGN_FOOTER_ASSET_BASE_URL__';
const LIBRESIGN_FOOTER_DEFAULT_LOCALE_KEY = 'default';

/**
 * Sanitize multi-line origin text.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function libresign_footer_fragment_sanitize_multiline_urls( $value ) {
	$values = libresign_footer_fragment_normalize_urls( $value );

	return implode( PHP_EOL, $values );
}

/**
 * Sanitize multi-line IP/CIDR allowlist entries.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function libresign_footer_fragment_sanitize_multiline_networks( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/[\r\n,]+/', $value ) ?: array();
	}

	if ( ! is_array( $value ) ) {
		return '';
	}

	$entries = array();
	foreach ( $value as $entry ) {
		$entry = trim( (string) $entry );
		if ( '' === $entry ) {
			continue;
		}
		$entries[] = $entry;
	}

	return implode( PHP_EOL, array_values( array_unique( $entries ) ) );
}

/**
 * Register footer integration settings in the Customizer.
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
			'description' => __( 'Receive footer artifacts from the static LibreSign site.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_footer_webhook_secret',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	$wp_customize->add_control(
		'libresign_footer_webhook_secret',
		array(
			'label'       => __( 'Footer webhook secret', 'libresign' ),
			'section'     => 'libresign_footer_fragment',
			'type'        => 'text',
			'description' => sprintf(
				/* translators: %s: REST route path */
				__( 'Shared secret used to validate webhook requests sent to %s', 'libresign' ),
				LIBRESIGN_FOOTER_WEBHOOK_ROUTE
			),
		)
	);

	$wp_customize->add_setting(
		'libresign_footer_webhook_allowed_ips',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'libresign_footer_fragment_sanitize_multiline_networks',
			'default'           => '',
		)
	);

	$wp_customize->add_control(
		'libresign_footer_webhook_allowed_ips',
		array(
			'label'       => __( 'Allowed webhook IPs', 'libresign' ),
			'section'     => 'libresign_footer_fragment',
			'type'        => 'textarea',
			'description' => __( 'Optional allowlist. Use one IP or CIDR per line. Leave blank to allow any source IP.', 'libresign' ),
		)
	);
}
add_action( 'customize_register', 'libresign_footer_fragment_customize_register' );

/**
 * Flush persisted footer artifacts when config changes.
 *
 * @return void
 */
function libresign_footer_fragment_flush_cache() {
	$base = libresign_footer_fragment_storage_base_dir();
	if ( is_dir( $base ) ) {
		libresign_footer_fragment_recursive_delete( $base );
	}
}

add_action( 'customize_save_after', 'libresign_footer_fragment_flush_cache' );

/**
 * Normalize URL configuration values.
 *
 * @param mixed $value Raw configured value.
 * @return array<int, string>
 */
function libresign_footer_fragment_normalize_urls( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/[\r\n,]+/', $value ) ?: array();
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$urls = array();
	foreach ( $value as $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			continue;
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			continue;
		}
		$urls[] = rtrim( $url, '/' );
	}

	return array_values( array_unique( $urls ) );
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
 * Resolve webhook secret.
 *
 * @return string
 */
function libresign_footer_fragment_webhook_secret() {
	$values = array(
		get_theme_mod( 'libresign_footer_webhook_secret' ),
		getenv( 'LIBRESIGN_FOOTER_WEBHOOK_SECRET' ),
		get_option( 'libresign_footer_webhook_secret' ),
	);

	foreach ( $values as $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return '';
}

/**
 * Resolve allowed webhook IP/CIDR entries.
 *
 * @return array<int, string>
 */
function libresign_footer_fragment_allowed_ips() {
	$values = array(
		get_theme_mod( 'libresign_footer_webhook_allowed_ips' ),
		getenv( 'LIBRESIGN_FOOTER_WEBHOOK_ALLOWED_IPS' ),
		get_option( 'libresign_footer_webhook_allowed_ips' ),
	);

	foreach ( $values as $value ) {
		$sanitized = libresign_footer_fragment_sanitize_multiline_networks( $value );
		if ( '' !== $sanitized ) {
			return preg_split( '/[\r\n,]+/', $sanitized ) ?: array();
		}
	}

	return array();
}

/**
 * Register the webhook endpoint.
 *
 * @return void
 */
function libresign_footer_fragment_register_rest_route() {
	register_rest_route(
		'libresign/v1',
		'/footer-fragment',
		array(
			'methods'             => 'POST',
			'callback'            => 'libresign_footer_fragment_receive_webhook',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'libresign_footer_fragment_register_rest_route' );

/**
 * Receive and persist footer artifacts pushed by the static site.
 *
 * @param \WP_REST_Request $request REST request.
 * @return \WP_REST_Response|\WP_Error
 */
function libresign_footer_fragment_receive_webhook( $request ) {
	$secret = libresign_footer_fragment_webhook_secret();
	if ( '' === $secret ) {
		return new \WP_Error( 'libresign_footer_secret_missing', __( 'Footer webhook secret is not configured.', 'libresign' ), array( 'status' => 503 ) );
	}

	$ip = libresign_footer_fragment_request_ip();
	if ( ! libresign_footer_fragment_ip_allowed( $ip ) ) {
		return new \WP_Error( 'libresign_footer_ip_denied', __( 'Webhook source IP is not allowed.', 'libresign' ), array( 'status' => 403 ) );
	}

	$body      = (string) $request->get_body();

	if ( ! libresign_footer_fragment_check_rate_limit( $ip, $body ) ) {
		return new \WP_Error( 'libresign_footer_rate_limited', __( 'Too many footer webhook requests.', 'libresign' ), array( 'status' => 429 ) );
	}
	$timestamp = (string) $request->get_header( 'x-libresign-timestamp' );
	$signature = (string) $request->get_header( 'x-libresign-signature' );

	if ( ! libresign_footer_fragment_verify_signature( $body, $timestamp, $signature, $secret ) ) {
		return new \WP_Error( 'libresign_footer_invalid_signature', __( 'Invalid footer webhook signature.', 'libresign' ), array( 'status' => 403 ) );
	}

	$payload = json_decode( $body, true );
	if ( ! is_array( $payload ) ) {
		return new \WP_Error( 'libresign_footer_invalid_payload', __( 'Footer webhook payload must be valid JSON.', 'libresign' ), array( 'status' => 400 ) );
	}

	$validated = libresign_footer_fragment_validate_payload( $payload );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}

	$stored = libresign_footer_fragment_persist_payload( $validated );
	if ( is_wp_error( $stored ) ) {
		return $stored;
	}

	return rest_ensure_response(
		array(
			'status' => 'ok',
			'locale' => $stored['locale'],
			'version' => $stored['version'],
		)
	);
}

/**
 * Validate payload shape.
 *
 * @param array<string, mixed> $payload Payload.
 * @return array<string, mixed>|\WP_Error
 */
function libresign_footer_fragment_validate_payload( $payload ) {
	$required = array( 'locale', 'version', 'generated_at', 'html', 'css', 'js', 'assets' );
	foreach ( $required as $field ) {
		if ( ! array_key_exists( $field, $payload ) ) {
			return new \WP_Error( 'libresign_footer_missing_field', sprintf( __( 'Missing footer payload field: %s', 'libresign' ), $field ), array( 'status' => 400 ) );
		}
	}

	if ( ! is_array( $payload['assets'] ) ) {
		return new \WP_Error( 'libresign_footer_invalid_assets', __( 'Footer payload assets must be an array.', 'libresign' ), array( 'status' => 400 ) );
	}

	$payload['locale']       = libresign_footer_fragment_normalize_locale_tag( (string) $payload['locale'] );
	$payload['version']      = sanitize_text_field( (string) $payload['version'] );
	$payload['generated_at'] = sanitize_text_field( (string) $payload['generated_at'] );
	$payload['html']         = (string) $payload['html'];
	$payload['css']          = (string) $payload['css'];
	$payload['js']           = (string) $payload['js'];

	return $payload;
}

/**
 * Get the storage base dir for footer artifacts.
 *
 * @return string
 */
function libresign_footer_fragment_storage_base_dir() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'libresign-footer';
}

/**
 * Get the storage base URL for footer artifacts.
 *
 * @return string
 */
function libresign_footer_fragment_storage_base_url() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['baseurl'] ) . 'libresign-footer';
}

/**
 * Convert locale to storage key.
 *
 * @param string $locale Locale.
 * @return string
 */
function libresign_footer_fragment_storage_key( $locale ) {
	$locale = libresign_footer_fragment_normalize_locale_tag( $locale );
	return '' === $locale ? LIBRESIGN_FOOTER_DEFAULT_LOCALE_KEY : $locale;
}

/**
 * Persist a validated footer payload.
 *
 * @param array<string, mixed> $payload Validated payload.
 * @return array<string, string>|\WP_Error
 */
function libresign_footer_fragment_persist_payload( $payload ) {
	$locale_key = libresign_footer_fragment_storage_key( (string) $payload['locale'] );
	$base_dir   = trailingslashit( libresign_footer_fragment_storage_base_dir() ) . $locale_key;
	$base_url   = trailingslashit( libresign_footer_fragment_storage_base_url() ) . $locale_key;
	$asset_dir  = trailingslashit( $base_dir ) . 'assets';
	$asset_url  = trailingslashit( $base_url ) . 'assets';

	if ( ! wp_mkdir_p( $asset_dir ) ) {
		return new \WP_Error( 'libresign_footer_storage_failed', __( 'Unable to create footer artifact storage directory.', 'libresign' ), array( 'status' => 500 ) );
	}

	foreach ( $payload['assets'] as $asset ) {
		$relative_path = libresign_footer_fragment_normalize_asset_path( $asset['path'] ?? '' );
		if ( '' === $relative_path ) {
			continue;
		}

		$content = base64_decode( (string) ( $asset['content_base64'] ?? '' ), true );
		if ( false === $content ) {
			return new \WP_Error( 'libresign_footer_invalid_asset', __( 'Unable to decode footer asset.', 'libresign' ), array( 'status' => 400 ) );
		}

		$destination = trailingslashit( $asset_dir ) . $relative_path;
		wp_mkdir_p( dirname( $destination ) );
		file_put_contents( $destination, $content );
	}

	$html = str_replace( LIBRESIGN_FOOTER_ASSET_BASE_TOKEN, $asset_url, (string) $payload['html'] );
	$css  = str_replace( LIBRESIGN_FOOTER_ASSET_BASE_TOKEN, $asset_url, (string) $payload['css'] );
	$js   = str_replace( LIBRESIGN_FOOTER_ASSET_BASE_TOKEN, $asset_url, (string) $payload['js'] );

	file_put_contents( trailingslashit( $base_dir ) . 'footer.html', $html );
	file_put_contents( trailingslashit( $base_dir ) . 'footer.css', $css );
	file_put_contents( trailingslashit( $base_dir ) . 'footer.js', $js );

	$manifest = array(
		'locale'       => (string) $payload['locale'],
		'locale_key'   => $locale_key,
		'version'      => (string) $payload['version'],
		'generated_at' => (string) $payload['generated_at'],
		'html_file'    => 'footer.html',
		'css_file'     => 'footer.css',
		'js_file'      => 'footer.js',
		'asset_url'    => $asset_url,
	);

	file_put_contents( trailingslashit( $base_dir ) . 'manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

	return array(
		'locale'  => (string) $payload['locale'],
		'version' => (string) $payload['version'],
	);
}

/**
 * Normalize a relative asset path.
 *
 * @param string $path Path.
 * @return string
 */
function libresign_footer_fragment_normalize_asset_path( $path ) {
	$path = trim( str_replace( '\\', '/', (string) $path ) );
	$path = ltrim( $path, '/' );

	if ( '' === $path || false !== strpos( $path, '..' ) ) {
		return '';
	}

	return $path;
}

/**
 * Resolve locale lookup keys for the current request.
 *
 * @return array<int, string>
 */
function libresign_footer_fragment_locale_lookup_keys() {
	$candidates = array();
	if ( function_exists( 'pll_current_language' ) ) {
		$candidates[] = (string) pll_current_language( 'slug' );
		$candidates[] = (string) pll_current_language( 'locale' );
	}
	$candidates[] = (string) determine_locale();
	$candidates[] = (string) get_locale();

	$keys = array();
	foreach ( $candidates as $candidate ) {
		$normalized = libresign_footer_fragment_normalize_locale_tag( $candidate );
		if ( '' === $normalized ) {
			continue;
		}
		$keys[] = $normalized;
		$language_only = strtok( $normalized, '-' );
		if ( is_string( $language_only ) && '' !== $language_only ) {
			$keys[] = strtolower( $language_only );
		}
	}

	$keys[] = LIBRESIGN_FOOTER_DEFAULT_LOCALE_KEY;
	return array_values( array_unique( $keys ) );
}

/**
 * Load the best locally stored footer artifact.
 *
 * @return array<string, mixed>|null
 */
function libresign_footer_fragment_load_artifact() {
	static $artifact = false;
	if ( false !== $artifact ) {
		return $artifact;
	}

	$base = libresign_footer_fragment_storage_base_dir();
	foreach ( libresign_footer_fragment_locale_lookup_keys() as $locale_key ) {
		$manifest_path = trailingslashit( $base ) . $locale_key . '/manifest.json';
		if ( ! is_file( $manifest_path ) ) {
			continue;
		}

		$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
		if ( ! is_array( $manifest ) ) {
			continue;
		}

		$dir       = dirname( $manifest_path );
		$html_path = trailingslashit( $dir ) . ( $manifest['html_file'] ?? 'footer.html' );
		$css_path  = trailingslashit( $dir ) . ( $manifest['css_file'] ?? 'footer.css' );
		$js_path   = trailingslashit( $dir ) . ( $manifest['js_file'] ?? 'footer.js' );

		if ( ! is_file( $html_path ) ) {
			continue;
		}

		$manifest['html_path'] = $html_path;
		$manifest['css_path']  = is_file( $css_path ) ? $css_path : '';
		$manifest['js_path']   = is_file( $js_path ) ? $js_path : '';
		$artifact              = $manifest;
		return $artifact;
	}

	$artifact = null;
	return $artifact;
}

/**
 * Enqueue locally stored footer assets.
 *
 * @return void
 */
function libresign_footer_fragment_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	$artifact = libresign_footer_fragment_load_artifact();
	if ( ! is_array( $artifact ) ) {
		return;
	}

	$base_url = trailingslashit( libresign_footer_fragment_storage_base_url() ) . $artifact['locale_key'];

	if ( ! empty( $artifact['css_path'] ) ) {
		wp_enqueue_style(
			'libresign-site-footer-fragment',
			trailingslashit( $base_url ) . $artifact['css_file'],
			array(),
			$artifact['version'] ?? filemtime( $artifact['css_path'] )
		);
	}

	if ( ! empty( $artifact['js_path'] ) ) {
		wp_enqueue_script(
			'libresign-site-footer-fragment',
			trailingslashit( $base_url ) . $artifact['js_file'],
			array(),
			$artifact['version'] ?? filemtime( $artifact['js_path'] ),
			true
		);
		wp_script_add_data( 'libresign-site-footer-fragment', 'type', 'module' );
	}
}
add_action( 'wp_enqueue_scripts', 'libresign_footer_fragment_enqueue_assets', 30 );

/**
 * Render the locally stored footer HTML.
 *
 * @return string
 */
function libresign_render_footer_fragment() {
	$artifact = libresign_footer_fragment_load_artifact();
	if ( ! is_array( $artifact ) || empty( $artifact['html_path'] ) || ! is_file( $artifact['html_path'] ) ) {
		return '';
	}

	return (string) file_get_contents( $artifact['html_path'] );
}

/**
 * Replace the footer template part with the stored footer artifact.
 *
 * @param string $block_content Rendered block content.
 * @param array  $block Parsed block.
 * @return string
 */
function libresign_replace_footer_template_part( $block_content, $block ) {
	$slug = isset( $block['attrs']['slug'] ) ? (string) $block['attrs']['slug'] : '';
	if ( 'footer' !== $slug ) {
		return $block_content;
	}

	$footer = libresign_render_footer_fragment();
	return '' !== $footer ? $footer : $block_content;
}
add_filter( 'render_block_core/template-part', 'libresign_replace_footer_template_part', 20, 2 );

/**
 * Get the current webhook source IP.
 *
 * @return string
 */
function libresign_footer_fragment_request_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
}

/**
 * Check whether the webhook IP is allowed.
 *
 * @param string $ip IP.
 * @return bool
 */
function libresign_footer_fragment_ip_allowed( $ip ) {
	$allowed = libresign_footer_fragment_allowed_ips();
	if ( empty( $allowed ) ) {
		return true;
	}

	foreach ( $allowed as $entry ) {
		$entry = trim( (string) $entry );
		if ( '' === $entry ) {
			continue;
		}
		if ( false !== strpos( $entry, '/' ) ) {
			if ( libresign_footer_fragment_ip_matches_cidr( $ip, $entry ) ) {
				return true;
			}
			continue;
		}
		if ( $ip === $entry ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if an IP matches a CIDR range.
 *
 * @param string $ip IP.
 * @param string $cidr CIDR.
 * @return bool
 */
function libresign_footer_fragment_ip_matches_cidr( $ip, $cidr ) {
	list( $subnet, $mask ) = array_pad( explode( '/', $cidr, 2 ), 2, null );
	if ( null === $mask ) {
		return false;
	}

	$ip_bin     = @inet_pton( $ip );
	$subnet_bin = @inet_pton( $subnet );
	if ( false === $ip_bin || false === $subnet_bin || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
		return false;
	}

	$mask = (int) $mask;
	$bytes = intdiv( $mask, 8 );
	$bits  = $mask % 8;

	if ( 0 !== $bytes && substr( $ip_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
		return false;
	}

	if ( 0 === $bits ) {
		return true;
	}

	$mask_byte = ~( 0xff >> $bits ) & 0xff;
	return ( ord( $ip_bin[ $bytes ] ) & $mask_byte ) === ( ord( $subnet_bin[ $bytes ] ) & $mask_byte );
}

/**
 * Simple webhook rate-limit gate.
 *
 * @param string $ip Source IP.
 * @param string $body Raw request body.
 * @return bool
 */
function libresign_footer_fragment_check_rate_limit( $ip, $body ) {
	$key = 'libresign_footer_webhook_rl_' . md5( ($ip ?: 'unknown') . '|' . hash( 'sha256', (string) $body ) );
	if ( get_transient( $key ) ) {
		return false;
	}
	set_transient( $key, 1, 5 );
	return true;
}

/**
 * Verify timestamped HMAC signature.
 *
 * @param string $body Raw request body.
 * @param string $timestamp Header timestamp.
 * @param string $signature Header signature.
 * @param string $secret Shared secret.
 * @return bool
 */
function libresign_footer_fragment_verify_signature( $body, $timestamp, $signature, $secret ) {
	if ( '' === $timestamp || '' === $signature || '' === $secret ) {
		return false;
	}

	if ( ! ctype_digit( $timestamp ) ) {
		return false;
	}

	if ( abs( time() - (int) $timestamp ) > 300 ) {
		return false;
	}

	$signature = trim( $signature );
	if ( 0 === strpos( $signature, 'sha256=' ) ) {
		$signature = substr( $signature, 7 );
	}

	$expected = hash_hmac( 'sha256', $timestamp . "\n" . $body, $secret );
	return hash_equals( $expected, $signature );
}

/**
 * Recursively delete a directory.
 *
 * @param string $path Directory path.
 * @return void
 */
function libresign_footer_fragment_recursive_delete( $path ) {
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
