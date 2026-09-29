<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\GithubApi;
use LibreSign\WordPressTheme\Tests\Support\StaticSite;
use WP_Customize_Manager;
use WP_REST_Request;
use WP_UnitTestCase;

final class GithubSiteWebhookTest extends WP_UnitTestCase {

	private const SECRET = 'webhook-secret';

	private const ENVIRONMENT = array(
		'LIBRESIGN_GITHUB_WEBHOOK_SECRET',
		'LIBRESIGN_FOOTER_WEBHOOK_SECRET',
		'LIBRESIGN_SITE_ORIGIN',
		'LIBRESIGN_SITE_DEPLOY_WORKFLOW_NAME',
		'LIBRESIGN_SITE_DEPLOY_REPOSITORY_NAME',
		'LIBRESIGN_SITE_DEPLOY_BRANCH_NAME',
	);

	private $site;

	private $github;

	public function set_up() {
		parent::set_up();

		$this->clear_environment();
		$this->site   = new StaticSite();
		$this->github = new GithubApi();
		set_theme_mod( 'libresign_github_webhook_secret', self::SECRET );
		set_theme_mod( 'libresign_site_origin', $this->site->origin() );
	}

	public function tear_down() {
		$this->clear_environment();

		foreach ( array( 'header', 'footer' ) as $fragment_type ) {
			libresign_theme_site_fragment_recursive_delete( libresign_theme_site_fragment_storage_base_dir( $fragment_type ) );
		}

		parent::tear_down();
	}

	public function test_the_endpoint_is_under_the_rest_api() {
		$this->assertSame( rest_url( 'libresign/v1/site-deploy-webhook' ), libresign_theme_github_site_webhook_endpoint_url() );
	}

	public function test_registers_the_settings_in_the_customizer() {
		require_once ABSPATH . 'wp-includes/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();

		libresign_theme_site_fragment_customize_register( $manager );

		$this->assertNotNull( $manager->get_section( 'libresign_site_fragment' ) );
		$this->assertSame( 'password', $manager->get_control( 'libresign_github_webhook_secret' )->type );
		$this->assertSame( 'https://libresign.coop', $manager->get_setting( 'libresign_site_origin' )->default );
		$this->assertSame( 'pages build and deployment', $manager->get_setting( 'libresign_site_deploy_workflow_name' )->default );
		$this->assertSame( 'gh-pages', $manager->get_setting( 'libresign_site_deploy_branch_name' )->default );
	}

	/**
	 * @dataProvider provide_origins
	 */
	public function test_sanitizes_the_site_origin( $value, $origin ) {
		$this->assertSame( $origin, libresign_theme_site_fragment_sanitize_origin( $value ) );
	}

	public static function provide_origins() {
		yield 'an origin'                => array( 'https://libresign.coop', 'https://libresign.coop' );
		yield 'the trailing slash'       => array( ' https://staging.libresign.coop/ ', 'https://staging.libresign.coop' );
		yield 'empty uses the default'   => array( '', 'https://libresign.coop' );
		yield 'a javascript url'         => array( 'javascript:alert(1)', 'https://libresign.coop' );
	}

	public function test_the_secret_comes_from_the_theme_setting_first() {
		putenv( 'LIBRESIGN_GITHUB_WEBHOOK_SECRET=from-environment' );

		$this->assertSame( self::SECRET, libresign_theme_github_webhook_secret() );
	}

	/**
	 * @dataProvider provide_secret_sources
	 */
	public function test_falls_back_through_the_secret_sources( $theme_mods, $environment, $secret ) {
		remove_theme_mod( 'libresign_github_webhook_secret' );
		foreach ( $theme_mods as $name => $value ) {
			set_theme_mod( $name, $value );
		}
		foreach ( $environment as $name => $value ) {
			putenv( $name . '=' . $value );
		}

		$this->assertSame( $secret, libresign_theme_github_webhook_secret() );
	}

	public static function provide_secret_sources() {
		yield 'the environment'                => array( array(), array( 'LIBRESIGN_GITHUB_WEBHOOK_SECRET' => ' env ' ), 'env' );
		yield 'the old footer setting'         => array( array( 'libresign_footer_webhook_secret' => 'footer' ), array(), 'footer' );
		yield 'the old footer environment'     => array( array(), array( 'LIBRESIGN_FOOTER_WEBHOOK_SECRET' => 'footer-env' ), 'footer-env' );
		yield 'nothing configured'             => array( array(), array(), '' );
	}

	/**
	 * @dataProvider provide_deploy_settings
	 */
	public function test_reads_the_deploy_settings_from_the_theme_then_the_environment_then_the_default( $setting, $variable, $default, $function ) {
		remove_theme_mod( $setting );
		$this->assertSame( $default, $function() );

		putenv( $variable . '=from-environment' );
		$this->assertStringEndsWith( 'from-environment', $function() );

		set_theme_mod( $setting, 'from-theme' );
		$this->assertStringEndsWith( 'from-theme', $function() );
	}

	public static function provide_deploy_settings() {
		yield 'site origin'      => array( 'libresign_site_origin', 'LIBRESIGN_SITE_ORIGIN', 'https://libresign.coop', 'libresign_theme_site_origin' );
		yield 'workflow name'    => array( 'libresign_site_deploy_workflow_name', 'LIBRESIGN_SITE_DEPLOY_WORKFLOW_NAME', 'pages build and deployment', 'libresign_theme_site_deploy_workflow_name' );
		yield 'repository name'  => array( 'libresign_site_deploy_repository_name', 'LIBRESIGN_SITE_DEPLOY_REPOSITORY_NAME', 'LibreSign/site', 'libresign_theme_site_deploy_repository_name' );
		yield 'branch name'      => array( 'libresign_site_deploy_branch_name', 'LIBRESIGN_SITE_DEPLOY_BRANCH_NAME', 'gh-pages', 'libresign_theme_site_deploy_branch_name' );
	}

	/**
	 * @dataProvider provide_signatures
	 */
	public function test_verifies_the_signature( $body, $signature, $secret, $valid ) {
		$this->assertSame( $valid, libresign_theme_verify_github_webhook_signature( $body, $signature, $secret ) );
	}

	public static function provide_signatures() {
		$body      = '{"zen":"Keep it logically awesome."}';
		$signature = hash_hmac( 'sha256', $body, self::SECRET );

		yield 'with the sha256 prefix'      => array( $body, 'sha256=' . $signature, self::SECRET, true );
		yield 'without the prefix'          => array( $body, $signature, self::SECRET, true );
		yield 'in upper case'               => array( $body, 'SHA256=' . strtoupper( $signature ), self::SECRET, true );
		yield 'another secret'              => array( $body, 'sha256=' . $signature, 'other', false );
		yield 'another body'                => array( '{}', 'sha256=' . $signature, self::SECRET, false );
		yield 'not hexadecimal'             => array( $body, 'sha256=zz', self::SECRET, false );
		yield 'no signature'                => array( $body, '', self::SECRET, false );
		yield 'no secret'                   => array( $body, 'sha256=' . $signature, ' ', false );
		yield 'no body'                     => array( '', 'sha256=' . $signature, self::SECRET, false );
	}

	/**
	 * @dataProvider provide_user_agents
	 */
	public function test_recognizes_the_github_user_agent( $user_agent, $expected ) {
		$this->assertSame( $expected, libresign_theme_is_github_hookshot_user_agent( $user_agent ) );
	}

	public static function provide_user_agents() {
		yield 'github'                  => array( 'GitHub-Hookshot/044aadd', true );
		yield 'github with whitespace'  => array( ' GitHub-Hookshot/044aadd ', true );
		yield 'a browser'               => array( 'Mozilla/5.0', false );
		yield 'github in the middle'    => array( 'curl GitHub-Hookshot/1', false );
	}

	/**
	 * @dataProvider provide_workflow_names
	 */
	public function test_reads_the_workflow_name( $payload, $name ) {
		$this->assertSame( $name, libresign_theme_site_deploy_workflow_name_from_payload( $payload ) );
	}

	public static function provide_workflow_names() {
		yield 'from the run'                    => array( array( 'workflow_run' => array( 'name' => ' Deploy ' ) ), 'Deploy' );
		yield 'from the workflow'               => array( array( 'workflow' => array( 'name' => 'Deploy' ) ), 'Deploy' );
		yield 'the run wins over the workflow'  => array( array( 'workflow_run' => array( 'name' => 'Run' ), 'workflow' => array( 'name' => 'Workflow' ) ), 'Run' );
		yield 'none'                            => array( array(), '' );
	}

	/**
	 * @dataProvider provide_deploy_runs
	 */
	public function test_recognizes_the_production_deploy( $changes, $expected ) {
		$this->assertSame( $expected, libresign_theme_is_production_site_deploy_workflow_run( $this->production_deploy( $changes ) ) );
	}

	public static function provide_deploy_runs() {
		yield 'the production deploy'   => array( array(), true );
		yield 'another repository'      => array( array( 'repository' => array( 'full_name' => 'LibreSign/other' ) ), false );
		yield 'still running'           => array( array( 'action' => 'in_progress' ), false );
		yield 'failed'                  => array( array( 'workflow_run' => array( 'conclusion' => 'failure' ) ), false );
		yield 'another branch'          => array( array( 'workflow_run' => array( 'head_branch' => 'main' ) ), false );
		yield 'another workflow'        => array( array( 'workflow_run' => array( 'name' => 'Tests' ) ), false );
	}

	/**
	 * @dataProvider provide_deploy_starts
	 */
	public function test_recognizes_the_deploy_starting( $changes, $expected ) {
		$this->assertSame( $expected, libresign_theme_is_site_deploy_starting( $this->deploy_starting( $changes ) ) );
	}

	public static function provide_deploy_starts() {
		yield 'the deploy starting on main'  => array( array(), true );
		yield 'another repository'           => array( array( 'repository' => array( 'full_name' => 'LibreSign/other' ) ), false );
		yield 'completed'                    => array( array( 'action' => 'completed' ), false );
		yield 'another branch'               => array( array( 'workflow_run' => array( 'head_branch' => 'gh-pages' ) ), false );
		yield 'another workflow'             => array( array( 'workflow_run' => array( 'name' => 'Tests' ) ), false );
	}

	public function test_handles_each_delivery_once() {
		$this->assertTrue( libresign_theme_mark_github_delivery_once( 'delivery-1' ) );
		$this->assertFalse( libresign_theme_mark_github_delivery_once( 'delivery-1' ) );
		$this->assertTrue( libresign_theme_mark_github_delivery_once( 'delivery-2' ) );
	}

	public function test_a_delivery_without_id_is_always_handled() {
		$this->assertTrue( libresign_theme_mark_github_delivery_once( '' ) );
		$this->assertTrue( libresign_theme_mark_github_delivery_once( '' ) );
	}

	public function test_refuses_deliveries_while_the_secret_is_missing() {
		remove_theme_mod( 'libresign_github_webhook_secret' );

		$response = $this->deliver( 'ping', array() );

		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( 'libresign_theme_github_webhook_secret_missing', $response->get_data()['code'] );
	}

	public function test_refuses_a_request_that_is_not_from_github() {
		$response = $this->deliver( 'ping', array(), array( 'user-agent' => 'curl/8.0' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'libresign_theme_github_webhook_invalid_agent', $response->get_data()['code'] );
	}

	public function test_refuses_a_wrong_signature() {
		$response = $this->deliver( 'ping', array(), array( 'x-hub-signature-256' => 'sha256=' . str_repeat( '0', 64 ) ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'libresign_theme_github_webhook_invalid_signature', $response->get_data()['code'] );
	}

	public function test_answers_the_ping() {
		$response = $this->deliver( 'ping', array( 'zen' => 'Design for failure.' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'status'   => 'pong',
				'endpoint' => libresign_theme_github_site_webhook_endpoint_url(),
			),
			$response->get_data()
		);
	}

	public function test_ignores_other_events() {
		$response = $this->deliver( 'push', array() );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame(
			array(
				'status' => 'ignored',
				'reason' => 'unsupported_event',
				'event'  => 'push',
			),
			$response->get_data()
		);
	}

	public function test_refuses_a_body_that_is_not_json() {
		$response = $this->deliver( 'workflow_run', 'not json' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'libresign_theme_github_webhook_invalid_payload', $response->get_data()['code'] );
	}

	public function test_ignores_a_run_that_is_not_the_production_deploy() {
		$response = $this->deliver( 'workflow_run', $this->production_deploy( array( 'workflow_run' => array( 'conclusion' => 'failure' ) ) ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame(
			array(
				'status'        => 'ignored',
				'reason'        => 'not_production_deploy',
				'repository'    => 'LibreSign/site',
				'workflow_name' => 'pages build and deployment',
				'head_branch'   => 'gh-pages',
				'conclusion'    => 'failure',
			),
			$response->get_data()
		);
		$this->assertSame( array(), $this->site->paths() );
	}

	public function test_syncs_the_fragments_after_the_production_deploy() {
		$this->publish_site();

		$response = $this->deliver( 'workflow_run', $this->production_deploy(), array( 'x-github-delivery' => 'delivery-1' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'status'      => 'synced',
				'delivery_id' => 'delivery-1',
				'repository'  => 'LibreSign/site',
				'workflow'    => 'pages build and deployment',
				'origin'      => $this->site->origin(),
				'synced'      => array(
					'header' => array( 'default' ),
					'footer' => array( 'default' ),
				),
			),
			$response->get_data()
		);
		$this->assertSame( '<header></header>', file_get_contents( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/default/header.html' ) );
		$this->assertSame( 'synced', get_option( 'libresign_site_fragment_last_sync' )['status'] );
		$this->assertSame( 'abc123', get_option( 'libresign_site_fragment_last_sync' )['details']['source_sha'] );
	}

	public function test_ignores_a_delivery_it_already_handled() {
		$this->publish_site();
		$this->deliver( 'workflow_run', $this->production_deploy(), array( 'x-github-delivery' => 'delivery-1' ) );

		$response = $this->deliver( 'workflow_run', $this->production_deploy(), array( 'x-github-delivery' => 'delivery-1' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 'duplicate_delivery', $response->get_data()['reason'] );
	}

	public function test_reports_and_records_a_failed_sync() {
		$response = $this->deliver( 'workflow_run', $this->production_deploy() );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'libresign_theme_site_fragment_http_status', $response->get_data()['code'] );
		$this->assertSame( 'error', get_option( 'libresign_site_fragment_last_sync' )['status'] );
		$this->assertSame( 'libresign_theme_site_fragment_http_status', get_option( 'libresign_site_fragment_last_sync' )['details']['code'] );
	}

	public function test_comments_on_the_deployed_pull_request_after_the_sync() {
		$this->publish_site();
		$this->site->serve( '/_deployment-info.json', '{"pr_number":42,"repository":"LibreSign/site"}' );
		update_option( 'libresign_github_deploy_token', $this->encrypted( 'ghp_token' ) );

		$this->deliver( 'workflow_run', $this->production_deploy() );

		$comment = $this->github->requests()[0];
		$this->assertSame( 'POST', $comment['method'] );
		$this->assertSame( '/repos/LibreSign/site/issues/42/comments', $comment['path'] );
		$this->assertSame( 'Bearer ghp_token', $comment['headers']['Authorization'] );
		$this->assertSame(
			"✅ **Header and footer fragments updated in production!**\n\n| Fragment | Synced locales |\n|----------|----------------|\n| Header | `default` |\n| Footer | `default` |\n\nSource: " . $this->site->origin(),
			json_decode( $comment['body'], true )['body']
		);
	}

	/**
	 * @dataProvider provide_deployments_without_comment
	 */
	public function test_does_not_comment_without_a_pull_request_or_a_token( $deployment_info, $token ) {
		$this->publish_site();
		if ( null !== $deployment_info ) {
			$this->site->serve( '/_deployment-info.json', $deployment_info );
		}
		if ( null !== $token ) {
			update_option( 'libresign_github_deploy_token', $this->encrypted( $token ) );
		}

		$this->deliver( 'workflow_run', $this->production_deploy() );

		$this->assertSame( array(), $this->github->requests() );
	}

	public static function provide_deployments_without_comment() {
		yield 'no deployment info'             => array( null, 'ghp_token' );
		yield 'a deployment without a pr'      => array( '{"repository":"LibreSign/site"}', 'ghp_token' );
		yield 'a repository without an owner'  => array( '{"pr_number":42,"repository":"site"}', 'ghp_token' );
		yield 'no token'                       => array( '{"pr_number":42}', null );
	}

	public function test_comments_on_the_merged_pull_request_when_the_deploy_starts() {
		update_option( 'libresign_github_deploy_token', $this->encrypted( 'ghp_token' ) );
		$this->github->answer_with( '/repos/LibreSign/site/commits/abc123/pulls', '[{"number":41,"merged_at":null},{"number":42,"merged_at":"2026-09-01T00:00:00Z"}]' );

		$response = $this->deliver( 'workflow_run', $this->deploy_starting() );

		$this->assertSame(
			array(
				'status' => 'acknowledged',
				'action' => 'deploy_starting',
			),
			$response->get_data()
		);
		$this->assertSame( array( '/repos/LibreSign/site/commits/abc123/pulls', '/repos/LibreSign/site/issues/42/comments' ), $this->github->paths() );
		$this->assertSame(
			'🚀 Starting production deploy of header and footer fragments...',
			json_decode( $this->github->requests()[1]['body'], true )['body']
		);
	}

	public function test_does_not_comment_when_the_commit_has_no_merged_pull_request() {
		update_option( 'libresign_github_deploy_token', $this->encrypted( 'ghp_token' ) );
		$this->github->answer_with( '/repos/LibreSign/site/commits/abc123/pulls', '[{"number":41,"merged_at":null}]' );

		$this->deliver( 'workflow_run', $this->deploy_starting() );

		$this->assertSame( array( '/repos/LibreSign/site/commits/abc123/pulls' ), $this->github->paths() );
	}

	public function test_does_not_look_for_the_pull_request_without_a_token() {
		$this->deliver( 'workflow_run', $this->deploy_starting() );

		$this->assertSame( array(), $this->github->requests() );
	}

	public function test_acknowledges_the_deploy_start_once() {
		$this->deliver( 'workflow_run', $this->deploy_starting(), array( 'x-github-delivery' => 'delivery-1' ) );

		$response = $this->deliver( 'workflow_run', $this->deploy_starting(), array( 'x-github-delivery' => 'delivery-1' ) );

		$this->assertSame( 'duplicate_delivery', $response->get_data()['reason'] );
	}

	/**
	 * @dataProvider provide_pull_request_lookups
	 */
	public function test_finds_the_merged_pull_request_of_a_commit( $repository, $response, $pull_request ) {
		if ( null !== $response ) {
			$this->github->answer_with( '/repos/LibreSign/site/commits/abc123/pulls', $response );
		}

		$this->assertSame( $pull_request, libresign_theme_find_pr_for_commit( $repository, 'abc123', 'ghp_token' ) );
	}

	public static function provide_pull_request_lookups() {
		yield 'the merged one'               => array( 'LibreSign/site', '[{"number":7,"merged_at":"2026-09-01T00:00:00Z"}]', 7 );
		yield 'none merged'                  => array( 'LibreSign/site', '[{"number":7,"merged_at":null}]', 0 );
		yield 'an answer that is not a list' => array( 'LibreSign/site', 'oops', 0 );
		yield 'the api answers not found'    => array( 'LibreSign/site', null, 0 );
		yield 'a repository without owner'   => array( 'site', '[]', 0 );
	}

	public function test_the_pull_request_lookup_survives_a_transport_failure() {
		remove_filter( 'pre_http_request', array( $this->github, 'answer' ) );

		$this->assertSame( 0, libresign_theme_find_pr_for_commit( 'LibreSign/site', 'abc123', 'ghp_token' ) );
	}

	public function test_decrypts_the_token_the_plugin_stores() {
		update_option( 'libresign_github_deploy_token', $this->encrypted( ' ghp_token ' ) );

		$this->assertSame( 'ghp_token', libresign_theme_github_deploy_token() );
	}

	public function test_has_no_token_until_the_plugin_stores_one() {
		$this->assertSame( '', libresign_theme_github_deploy_token() );
	}

	public function test_has_no_token_when_it_cannot_be_decrypted() {
		update_option( 'libresign_github_deploy_token', base64_encode( 'not encrypted' ) );

		$this->assertSame( '', libresign_theme_github_deploy_token() );
	}

	private function clear_environment() {
		foreach ( self::ENVIRONMENT as $variable ) {
			putenv( $variable );
		}
	}

	private function deliver( $event, $payload, $headers = array() ) {
		$body    = is_string( $payload ) ? $payload : (string) wp_json_encode( $payload );
		$request = new WP_REST_Request( 'POST', '/libresign/v1/site-deploy-webhook' );
		$request->set_body( $body );

		$headers = array_merge(
			array(
				'user-agent'          => 'GitHub-Hookshot/044aadd',
				'x-github-event'      => $event,
				'x-github-delivery'   => wp_generate_uuid4(),
				'x-hub-signature-256' => 'sha256=' . hash_hmac( 'sha256', $body, self::SECRET ),
			),
			$headers
		);
		foreach ( $headers as $name => $value ) {
			$request->set_header( $name, $value );
		}

		return rest_get_server()->dispatch( $request );
	}

	private function production_deploy( $changes = array() ) {
		return array_replace_recursive(
			array(
				'action'       => 'completed',
				'repository'   => array( 'full_name' => 'LibreSign/site' ),
				'workflow_run' => array(
					'name'        => 'pages build and deployment',
					'conclusion'  => 'success',
					'head_branch' => 'gh-pages',
					'head_sha'    => 'abc123',
					'html_url'    => 'https://github.com/LibreSign/site/actions/runs/1',
					'updated_at'  => '2026-09-01T00:00:00Z',
				),
			),
			$changes
		);
	}

	private function deploy_starting( $changes = array() ) {
		return array_replace_recursive(
			array(
				'action'       => 'in_progress',
				'repository'   => array( 'full_name' => 'LibreSign/site' ),
				'workflow_run' => array(
					'name'        => 'Deploy',
					'head_branch' => 'main',
					'head_sha'    => 'abc123',
				),
			),
			$changes
		);
	}

	private function encrypted( $token ) {
		return base64_encode(
			(string) openssl_encrypt(
				$token,
				'AES-256-CBC',
				hash( 'sha256', AUTH_KEY . SECURE_AUTH_SALT ),
				0,
				substr( hash( 'sha256', NONCE_SALT ), 0, 16 )
			)
		);
	}

	private function publish_site() {
		$origin = $this->site->origin();

		$this->site->serve( '/fragments/header', '<header data-fragment-css="' . $origin . '/header.css" data-fragment-js="' . $origin . '/header.js"></header>' );
		$this->site->serve( '/fragments/footer', '<footer data-fragment-css="' . $origin . '/footer.css" data-fragment-js="' . $origin . '/footer.js"></footer>' );
		foreach ( array( '/header.css', '/header.js', '/footer.css', '/footer.js' ) as $asset ) {
			$this->site->serve( $asset, '' );
		}
	}
}
