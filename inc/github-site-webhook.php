<?php
/**
 * GitHub webhook receiver for production site fragment synchronization.
 */

defined( 'ABSPATH' ) || exit;

const LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_NAMESPACE = 'libresign/v1';
const LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_ROUTE     = '/site-deploy-webhook';

/**
 * Return the full webhook endpoint URL.
 *
 * @return string
 */
function libresign_theme_github_site_webhook_endpoint_url() {
	return rest_url( ltrim( LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_NAMESPACE . LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_ROUTE, '/' ) );
}

/**
 * Register site fragment settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function libresign_theme_site_fragment_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'libresign_site_fragment',
		array(
			'title'       => __( 'Site fragment integration', 'libresign' ),
			'priority'    => 160,
			'description' => sprintf(
				/* translators: %s: REST webhook endpoint URL. */
				__( 'Sync shared header and footer fragments after the GitHub production deploy finishes. Configure the repository webhook to post to %s using the workflow_run event.', 'libresign' ),
				libresign_theme_github_site_webhook_endpoint_url()
			),
		)
	);

	$wp_customize->add_setting(
		'libresign_github_webhook_secret',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	$wp_customize->add_control(
		'libresign_github_webhook_secret',
		array(
			'label'       => __( 'GitHub webhook secret', 'libresign' ),
			'section'     => 'libresign_site_fragment',
			'type'        => 'password',
			'description' => __( 'Use the same shared secret in the GitHub repository webhook and in this theme setting.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_site_origin',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'libresign_theme_site_fragment_sanitize_origin',
			'default'           => 'https://libresign.coop',
		)
	);

	$wp_customize->add_control(
		'libresign_site_origin',
		array(
			'label'       => __( 'Static site origin', 'libresign' ),
			'section'     => 'libresign_site_fragment',
			'type'        => 'url',
			'description' => __( 'Production URL used to fetch /fragments/header and /fragments/footer after a successful deploy.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_site_deploy_repository_name',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'LibreSign/site',
		)
	);

	$wp_customize->add_control(
		'libresign_site_deploy_repository_name',
		array(
			'label'       => __( 'GitHub repository', 'libresign' ),
			'section'     => 'libresign_site_fragment',
			'type'        => 'text',
			'description' => __( 'Expected repository full name for incoming workflow_run deliveries.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_site_deploy_workflow_name',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'pages build and deployment',
		)
	);

	$wp_customize->add_control(
		'libresign_site_deploy_workflow_name',
		array(
			'label'       => __( 'Monitored workflow name', 'libresign' ),
			'section'     => 'libresign_site_fragment',
			'type'        => 'text',
			'description' => __( 'Exact GitHub Actions workflow name that represents the production deploy.', 'libresign' ),
		)
	);

	$wp_customize->add_setting(
		'libresign_site_deploy_branch_name',
		array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'gh-pages',
		)
	);

	$wp_customize->add_control(
		'libresign_site_deploy_branch_name',
		array(
			'label'       => __( 'Monitored branch name', 'libresign' ),
			'section'     => 'libresign_site_fragment',
			'type'        => 'text',
			'description' => __( 'Expected branch name for the monitored workflow (e.g. gh-pages for pages build and deployment).', 'libresign' ),
		)
	);
}
add_action( 'customize_register', 'libresign_theme_site_fragment_customize_register' );

/**
 * Sanitize the configured site origin.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function libresign_theme_site_fragment_sanitize_origin( $value ) {
	$value = rtrim( esc_url_raw( trim( (string) $value ) ), '/' );

	return '' === $value ? 'https://libresign.coop' : $value;
}

/**
 * Register the GitHub site deploy webhook endpoint.
 *
 * @return void
 */
function libresign_theme_register_github_site_webhook_route() {
	register_rest_route(
		LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_NAMESPACE,
		LIBRESIGN_THEME_GITHUB_SITE_WEBHOOK_ROUTE,
		array(
			'methods'             => 'POST',
			'callback'            => 'libresign_theme_receive_github_site_deploy_webhook',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'libresign_theme_register_github_site_webhook_route' );

/**
 * Resolve the configured GitHub webhook secret.
 *
 * @return string
 */
function libresign_theme_github_webhook_secret() {
	$values = array(
		get_theme_mod( 'libresign_github_webhook_secret' ),
		getenv( 'LIBRESIGN_GITHUB_WEBHOOK_SECRET' ),
		get_theme_mod( 'libresign_footer_webhook_secret' ),
		getenv( 'LIBRESIGN_FOOTER_WEBHOOK_SECRET' ),
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
 * Resolve the configured static site origin.
 *
 * @return string
 */
function libresign_theme_site_origin() {
	$values = array(
		get_theme_mod( 'libresign_site_origin', '' ),
		getenv( 'LIBRESIGN_SITE_ORIGIN' ),
	);

	foreach ( $values as $value ) {
		$value = libresign_theme_site_fragment_normalize_site_origin( $value );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return 'https://libresign.coop';
}

/**
 * Resolve the expected workflow name.
 *
 * @return string
 */
function libresign_theme_site_deploy_workflow_name() {
	$values = array(
		get_theme_mod( 'libresign_site_deploy_workflow_name', '' ),
		getenv( 'LIBRESIGN_SITE_DEPLOY_WORKFLOW_NAME' ),
	);

	foreach ( $values as $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return 'Deploy';
}

/**
 * Resolve the expected site repository name.
 *
 * @return string
 */
function libresign_theme_site_deploy_repository_name() {
	$values = array(
		get_theme_mod( 'libresign_site_deploy_repository_name', '' ),
		getenv( 'LIBRESIGN_SITE_DEPLOY_REPOSITORY_NAME' ),
	);

	foreach ( $values as $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return 'LibreSign/site';
}

/**
 * Resolve the expected production branch.
 *
 * @return string
 */
function libresign_theme_site_deploy_branch_name() {
	$values = array(
		get_theme_mod( 'libresign_site_deploy_branch_name', '' ),
		getenv( 'LIBRESIGN_SITE_DEPLOY_BRANCH_NAME' ),
	);

	foreach ( $values as $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return 'gh-pages';
}

/**
 * Build a standardized ignored response.
 *
 * @param array<string, mixed> $data   Response data.
 * @param int                  $status HTTP status.
 * @return WP_REST_Response
 */
function libresign_theme_github_site_webhook_ignored_response( $data = array(), $status = 202 ) {
	$response = rest_ensure_response( array_merge( array( 'status' => 'ignored' ), $data ) );
	$response->set_status( $status );

	return $response;
}

/**
 * Verify the GitHub webhook HMAC signature.
 *
 * @param string $body      Raw request body.
 * @param string $signature Signature header value.
 * @param string $secret    Shared secret.
 * @return bool
 */
function libresign_theme_verify_github_webhook_signature( $body, $signature, $secret ) {
	$secret    = trim( (string) $secret );
	$signature = trim( (string) $signature );

	if ( '' === $body || '' === $secret || '' === $signature ) {
		return false;
	}

	if ( 0 === stripos( $signature, 'sha256=' ) ) {
		$signature = substr( $signature, 7 );
	}

	if ( ! ctype_xdigit( $signature ) ) {
		return false;
	}

	$expected = hash_hmac( 'sha256', $body, $secret );

	return hash_equals( $expected, strtolower( $signature ) );
}

/**
 * Check whether the webhook user agent looks like GitHub Hookshot.
 *
 * @param string $user_agent User agent header.
 * @return bool
 */
function libresign_theme_is_github_hookshot_user_agent( $user_agent ) {
	return 0 === strpos( trim( (string) $user_agent ), 'GitHub-Hookshot/' );
}

/**
 * Extract the workflow name from the payload.
 *
 * @param array<string, mixed> $payload Parsed payload.
 * @return string
 */
function libresign_theme_site_deploy_workflow_name_from_payload( $payload ) {
	$workflow_run_name = isset( $payload['workflow_run']['name'] ) ? trim( (string) $payload['workflow_run']['name'] ) : '';
	if ( '' !== $workflow_run_name ) {
		return $workflow_run_name;
	}

	return isset( $payload['workflow']['name'] ) ? trim( (string) $payload['workflow']['name'] ) : '';
}

/**
 * Determine whether the payload represents the production site deploy event.
 *
 * @param array<string, mixed> $payload Parsed payload.
 * @return bool
 */
function libresign_theme_is_production_site_deploy_workflow_run( $payload ) {
	$repository    = isset( $payload['repository']['full_name'] ) ? trim( (string) $payload['repository']['full_name'] ) : '';
	$action        = isset( $payload['action'] ) ? trim( (string) $payload['action'] ) : '';
	$conclusion    = isset( $payload['workflow_run']['conclusion'] ) ? trim( (string) $payload['workflow_run']['conclusion'] ) : '';
	$head_branch   = isset( $payload['workflow_run']['head_branch'] ) ? trim( (string) $payload['workflow_run']['head_branch'] ) : '';
	$workflow_name = libresign_theme_site_deploy_workflow_name_from_payload( $payload );

	if ( libresign_theme_site_deploy_repository_name() !== $repository ) {
		return false;
	}

	if ( 'completed' !== $action ) {
		return false;
	}

	if ( 'success' !== $conclusion ) {
		return false;
	}

	if ( libresign_theme_site_deploy_branch_name() !== $head_branch ) {
		return false;
	}

	return libresign_theme_site_deploy_workflow_name() === $workflow_name;
}

/**
 * Mark a webhook delivery as processed, returning false when duplicated.
 *
 * @param string $delivery_id Delivery GUID.
 * @return bool
 */
function libresign_theme_mark_github_delivery_once( $delivery_id ) {
	$delivery_id = trim( (string) $delivery_id );
	if ( '' === $delivery_id ) {
		return true;
	}

	$key = 'libresign_theme_github_delivery_' . md5( $delivery_id );
	if ( get_transient( $key ) ) {
		return false;
	}

	set_transient( $key, 1, DAY_IN_SECONDS );

	return true;
}

/**
 * Record the last fragment synchronization result.
 *
 * @param string               $status  Status label.
 * @param array<string, mixed> $payload Details.
 * @return void
 */
function libresign_theme_record_site_fragment_sync_result( $status, $payload ) {
	update_option(
		'libresign_site_fragment_last_sync',
		array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
			'details'    => $payload,
		),
		false
	);
}

/**
 * Receive the GitHub webhook and synchronize fragments after production deploys.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function libresign_theme_receive_github_site_deploy_webhook( $request ) {
	$secret = libresign_theme_github_webhook_secret();
	if ( '' === $secret ) {
		return new WP_Error(
			'libresign_theme_github_webhook_secret_missing',
			__( 'The GitHub webhook secret is not configured.', 'libresign' ),
			array( 'status' => 503 )
		);
	}

	$user_agent = (string) $request->get_header( 'user-agent' );
	if ( ! libresign_theme_is_github_hookshot_user_agent( $user_agent ) ) {
		return new WP_Error(
			'libresign_theme_github_webhook_invalid_agent',
			__( 'The webhook request does not look like a GitHub delivery.', 'libresign' ),
			array( 'status' => 403 )
		);
	}

	$body      = (string) $request->get_body();
	$signature = (string) $request->get_header( 'x-hub-signature-256' );
	if ( ! libresign_theme_verify_github_webhook_signature( $body, $signature, $secret ) ) {
		return new WP_Error(
			'libresign_theme_github_webhook_invalid_signature',
			__( 'Invalid GitHub webhook signature.', 'libresign' ),
			array( 'status' => 403 )
		);
	}

	$event = strtolower( trim( (string) $request->get_header( 'x-github-event' ) ) );
	if ( 'ping' === $event ) {
		return rest_ensure_response(
			array(
				'status'   => 'pong',
				'endpoint' => libresign_theme_github_site_webhook_endpoint_url(),
			)
		);
	}

	if ( 'workflow_run' !== $event ) {
		return libresign_theme_github_site_webhook_ignored_response(
			array(
				'reason' => 'unsupported_event',
				'event'  => $event,
			)
		);
	}

	$payload = json_decode( $body, true );
	if ( ! is_array( $payload ) ) {
		return new WP_Error(
			'libresign_theme_github_webhook_invalid_payload',
			__( 'The GitHub webhook payload must be valid JSON.', 'libresign' ),
			array( 'status' => 400 )
		);
	}

	if ( ! libresign_theme_is_production_site_deploy_workflow_run( $payload ) ) {
		return libresign_theme_github_site_webhook_ignored_response(
			array(
				'reason'        => 'not_production_deploy',
				'repository'    => isset( $payload['repository']['full_name'] ) ? (string) $payload['repository']['full_name'] : '',
				'workflow_name' => libresign_theme_site_deploy_workflow_name_from_payload( $payload ),
				'head_branch'   => isset( $payload['workflow_run']['head_branch'] ) ? (string) $payload['workflow_run']['head_branch'] : '',
				'conclusion'    => isset( $payload['workflow_run']['conclusion'] ) ? (string) $payload['workflow_run']['conclusion'] : '',
			)
		);
	}

	$delivery_id = (string) $request->get_header( 'x-github-delivery' );
	if ( ! libresign_theme_mark_github_delivery_once( $delivery_id ) ) {
		return libresign_theme_github_site_webhook_ignored_response(
			array(
				'reason'      => 'duplicate_delivery',
				'delivery_id' => $delivery_id,
			)
		);
	}

	$workflow_run = isset( $payload['workflow_run'] ) && is_array( $payload['workflow_run'] ) ? $payload['workflow_run'] : array();
	$sync_result  = libresign_theme_sync_site_fragments_from_origin(
		libresign_theme_site_origin(),
		libresign_theme_site_fragment_supported_types(),
		array(
			'generated_at' => isset( $workflow_run['updated_at'] ) ? (string) $workflow_run['updated_at'] : current_time( 'mysql', true ),
			'source_sha'   => isset( $workflow_run['head_sha'] ) ? (string) $workflow_run['head_sha'] : '',
			'source_url'   => isset( $workflow_run['html_url'] ) ? (string) $workflow_run['html_url'] : '',
		)
	);

	if ( is_wp_error( $sync_result ) ) {
		libresign_theme_record_site_fragment_sync_result(
			'error',
			array(
				'message' => $sync_result->get_error_message(),
				'code'    => $sync_result->get_error_code(),
			)
		);

		return $sync_result;
	}

	libresign_theme_record_site_fragment_sync_result(
		'synced',
		array(
			'delivery_id' => $delivery_id,
			'repository'  => libresign_theme_site_deploy_repository_name(),
			'workflow'    => libresign_theme_site_deploy_workflow_name_from_payload( $payload ),
			'head_branch' => isset( $workflow_run['head_branch'] ) ? (string) $workflow_run['head_branch'] : '',
			'source_sha'  => isset( $workflow_run['head_sha'] ) ? (string) $workflow_run['head_sha'] : '',
			'source_url'  => isset( $workflow_run['html_url'] ) ? (string) $workflow_run['html_url'] : '',
			'synced'      => $sync_result['synced'],
		)
	);

	libresign_theme_post_pr_sync_comment( libresign_theme_site_origin(), $sync_result );

	return rest_ensure_response(
		array(
			'status'      => 'synced',
			'delivery_id' => $delivery_id,
			'repository'  => libresign_theme_site_deploy_repository_name(),
			'workflow'    => libresign_theme_site_deploy_workflow_name_from_payload( $payload ),
			'origin'      => $sync_result['origin'],
			'synced'      => $sync_result['synced'],
		)
	);
}

/**
 * Decrypt the stored GitHub deploy token.
 *
 * Reads the token from the plugin's wp_option (libresign_github_deploy_token),
 * which the libresign-wp-customizations plugin stores AES-256-CBC encrypted.
 *
 * @return string Plain-text token, or empty string if unavailable.
 */
function libresign_theme_github_deploy_token(): string {
	$encrypted = get_option( 'libresign_github_deploy_token', '' );
	if ( '' === $encrypted || ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}

	$key       = hash( 'sha256', AUTH_KEY . SECURE_AUTH_SALT );
	$iv        = substr( hash( 'sha256', NONCE_SALT ), 0, 16 );
	$decrypted = openssl_decrypt( base64_decode( $encrypted ), 'AES-256-CBC', $key, 0, $iv );

	return is_string( $decrypted ) ? trim( $decrypted ) : '';
}

/**
 * Post a PR comment after fragments are synced.
 *
 * Fetches /_deployment-info.json from the static site (written by the Deploy
 * workflow) to find the PR number, then posts a summary comment via the
 * GitHub API using the stored deploy token.
 *
 * @param string               $site_origin Static site origin.
 * @param array<string, mixed> $sync_result Result from libresign_theme_sync_site_fragments_from_origin().
 * @return void
 */
function libresign_theme_post_pr_sync_comment( string $site_origin, array $sync_result ): void {
	$info_response = libresign_theme_site_fragment_fetch_url(
		rtrim( $site_origin, '/' ) . '/_deployment-info.json'
	);
	if ( is_wp_error( $info_response ) ) {
		return;
	}

	$info = json_decode( (string) $info_response['body'], true );
	if ( ! is_array( $info ) || empty( $info['pr_number'] ) ) {
		return;
	}

	$pr_number  = (int) $info['pr_number'];
	$repository = isset( $info['repository'] ) ? (string) $info['repository'] : libresign_theme_site_deploy_repository_name();

	$token = libresign_theme_github_deploy_token();
	if ( '' === $token ) {
		return;
	}

	$synced      = $sync_result['synced'] ?? array();
	$header_list = implode( ', ', (array) ( $synced['header'] ?? array() ) );
	$footer_list = implode( ', ', (array) ( $synced['footer'] ?? array() ) );

	$body = "✅ **Header and footer fragments updated in production!**\n\n" .
			"| Fragment | Synced locales |\n" .
			"|----------|----------------|\n" .
			"| Header | `{$header_list}` |\n" .
			"| Footer | `{$footer_list}` |\n\n" .
			"Source: {$site_origin}";

	$parts = explode( '/', $repository, 2 );
	if ( count( $parts ) !== 2 || '' === $parts[0] || '' === $parts[1] ) {
		return;
	}

	wp_remote_post(
		"https://api.github.com/repos/{$parts[0]}/{$parts[1]}/issues/{$pr_number}/comments",
		array(
			'headers' => array(
				'Authorization'        => 'Bearer ' . $token,
				'Accept'               => 'application/vnd.github+json',
				'Content-Type'         => 'application/json',
				'X-GitHub-Api-Version' => '2022-11-28',
			),
			'body'    => wp_json_encode( array( 'body' => $body ) ),
			'timeout' => 15,
		)
	);
}