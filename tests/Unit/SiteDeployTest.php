<?php

namespace LibreSign\WordPressTheme\Tests\Unit;

use LibreSign\WordPressTheme\SiteDeploy;
use PHPUnit\Framework\TestCase;

final class SiteDeployTest extends TestCase {

	private const SECRET = 'webhook-secret';

	/**
	 * @dataProvider provide_signatures
	 */
	public function test_verifies_the_signature( $body, $signature, $secret, $valid ) {
		$this->assertSame( $valid, SiteDeploy::signature_matches( $body, $signature, $secret ) );
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
		$this->assertSame( $expected, SiteDeploy::is_github_delivery( $user_agent ) );
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
		$this->assertSame( $name, SiteDeploy::workflow_name( $payload ) );
	}

	public static function provide_workflow_names() {
		yield 'from the run'                    => array( array( 'workflow_run' => array( 'name' => ' Deploy ' ) ), 'Deploy' );
		yield 'from the workflow'               => array( array( 'workflow' => array( 'name' => 'Deploy' ) ), 'Deploy' );
		yield 'the run wins over the workflow'  => array( array( 'workflow_run' => array( 'name' => 'Run' ), 'workflow' => array( 'name' => 'Workflow' ) ), 'Run' );
		yield 'none'                            => array( array(), '' );
		yield 'a name that is not text'         => array( array( 'workflow_run' => array( 'name' => array( 'Deploy' ) ) ), '' );
	}

	/**
	 * @dataProvider provide_deploy_runs
	 */
	public function test_recognizes_the_production_deploy( $changes, $expected ) {
		$this->assertSame( $expected, SiteDeploy::is_production_deploy( $this->production_deploy( $changes ), 'LibreSign/site', 'gh-pages', 'pages build and deployment' ) );
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
		$this->assertSame( $expected, SiteDeploy::is_deploy_starting( $this->deploy_starting( $changes ), 'LibreSign/site' ) );
	}

	public static function provide_deploy_starts() {
		yield 'the deploy starting on main'  => array( array(), true );
		yield 'another repository'           => array( array( 'repository' => array( 'full_name' => 'LibreSign/other' ) ), false );
		yield 'completed'                    => array( array( 'action' => 'completed' ), false );
		yield 'another branch'               => array( array( 'workflow_run' => array( 'head_branch' => 'gh-pages' ) ), false );
		yield 'another workflow'             => array( array( 'workflow_run' => array( 'name' => 'Tests' ) ), false );
	}

	/**
	 * @dataProvider provide_pull_requests
	 */
	public function test_finds_the_merged_pull_request( $pull_requests, $number ) {
		$this->assertSame( $number, SiteDeploy::merged_pull_request( $pull_requests ) );
	}

	public static function provide_pull_requests() {
		yield 'the merged one among others' => array( array( array( 'number' => 41, 'merged_at' => null ), array( 'number' => 42, 'merged_at' => '2026-09-01T00:00:00Z' ) ), 42 );
		yield 'none merged'                 => array( array( array( 'number' => 41, 'merged_at' => null ) ), 0 );
		yield 'no pull requests'            => array( array(), 0 );
		yield 'not a list'                  => array( null, 0 );
	}

	public function test_lists_the_synced_locales_in_the_comment() {
		$this->assertSame(
			"✅ **Header and footer fragments updated in production!**\n\n| Fragment | Synced locales |\n|----------|----------------|\n| Header | `default, pt-BR` |\n| Footer | `` |\n\nSource: https://libresign.coop",
			SiteDeploy::sync_comment( array( 'header' => array( 'default', 'pt-BR' ) ), 'https://libresign.coop' )
		);
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
				),
			),
			$changes
		);
	}
}
