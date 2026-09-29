<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\StaticSite;
use WP_Error;
use WP_UnitTestCase;

final class FooterFragmentTest extends WP_UnitTestCase {

	private $site;

	public function set_up() {
		parent::set_up();

		$this->site = new StaticSite();
	}

	public function tear_down() {
		foreach ( array( 'header', 'footer' ) as $fragment_type ) {
			libresign_theme_site_fragment_recursive_delete( libresign_theme_site_fragment_storage_base_dir( $fragment_type ) );
		}

		parent::tear_down();
	}

	public function test_stores_each_fragment_type_in_its_own_folder() {
		$this->assertSame( array( 'header', 'footer' ), libresign_theme_site_fragment_supported_types() );
		$this->assertStringEndsWith( '/libresign-header', libresign_theme_site_fragment_storage_base_dir( 'header' ) );
		$this->assertStringEndsWith( '/libresign-footer', libresign_theme_site_fragment_storage_base_url( 'footer' ) );
	}

	public function test_falls_back_to_the_site_locale() {
		$this->assertSame( array( 'en-US', '' ), libresign_theme_site_fragment_fallback_locales() );
	}

	public function test_looks_the_locale_up_from_the_most_specific_to_the_default() {
		$this->assertSame( array( 'en-US', 'en', 'default' ), libresign_theme_site_fragment_locale_lookup_keys() );
	}

	public function test_only_a_not_found_is_an_optional_error() {
		$this->assertTrue( libresign_theme_site_fragment_is_optional_http_error( new WP_Error( 'x', 'x', array( 'status' => 404 ) ) ) );
		$this->assertFalse( libresign_theme_site_fragment_is_optional_http_error( new WP_Error( 'x', 'x', array( 'status' => 500 ) ) ) );
		$this->assertFalse( libresign_theme_site_fragment_is_optional_http_error( new WP_Error( 'x', 'x' ) ) );
	}

	public function test_fetches_a_url() {
		$this->site->serve( '/fragments/header', '<header></header>' );

		$this->assertSame(
			array(
				'code' => 200,
				'body' => '<header></header>',
			),
			libresign_theme_site_fragment_fetch_url( $this->site->origin() . '/fragments/header' )
		);
	}

	public function test_a_failing_status_is_an_error_with_the_status() {
		$error = libresign_theme_site_fragment_fetch_url( $this->site->origin() . '/fragments/header' );

		$this->assertWPError( $error );
		$this->assertSame( 'libresign_theme_site_fragment_http_status', $error->get_error_code() );
		$this->assertSame( 404, $error->get_error_data()['status'] );
	}

	public function test_a_transport_failure_is_an_error() {
		$this->assertWPError( libresign_theme_site_fragment_fetch_url( 'https://libresign.coop/fragments/header' ) );
	}

	public function test_syncing_needs_an_origin() {
		$error = libresign_theme_sync_site_fragments_from_origin( ' ' );

		$this->assertWPError( $error );
		$this->assertSame( 'libresign_theme_site_fragment_origin_missing', $error->get_error_code() );
	}

	public function test_collects_every_fragment_in_every_locale_the_site_publishes() {
		$this->publish_site();

		$collected = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'header', 'footer' ), array( 'source_sha' => 'abc123' ) );

		$this->assertSame( array( '', 'pt-BR', 'en-US' ), $collected['locales'] );
		$this->assertSame(
			array(
				'header' => array( 'default', 'pt-BR' ),
				'footer' => array( 'default', 'pt-BR' ),
			),
			$collected['summary']
		);
		$this->assertSame(
			array(
				'/fragments/header',
				'/assets/header.css',
				'/assets/header.js',
				'/fragments/pt-BR/header',
				'/fragments/en-US/header',
				'/fragments/footer',
				'/assets/footer.css',
				'/assets/footer.js',
				'/fragments/pt-BR/footer',
				'/fragments/en-US/footer',
			),
			$this->site->paths()
		);

		$header = $collected['artifacts'][0];
		$this->assertSame( '<header><a href="' . $this->site->origin() . '/fragments/pt-BR/header">pt</a><img src="' . $this->site->origin() . '/logo.svg"></header>', $header['html'] );
		$this->assertSame( 'a{background:url(' . $this->site->origin() . '/bg.png)}', $header['css'] );
		$this->assertSame( 'console.log("header");', $header['js'] );
		$this->assertSame( 'abc123', $header['source_sha'] );
		$this->assertSame( hash( 'sha256', $header['html'] . "\n" . $header['css'] . "\n" . $header['js'] ), $header['version'] );
	}

	public function test_a_missing_default_header_stops_the_sync() {
		$error = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'header' ) );

		$this->assertWPError( $error );
		$this->assertSame( 404, $error->get_error_data()['status'] );
	}

	public function test_a_default_fragment_without_assets_stops_the_sync() {
		$this->publish_site();
		$this->site->serve( '/fragments/footer', '<footer></footer>' );

		$error = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'header', 'footer' ) );

		$this->assertSame( 'libresign_theme_site_fragment_missing_assets', $error->get_error_code() );
	}

	public function test_skips_a_locale_whose_fragment_has_no_assets() {
		$this->publish_site();
		$this->site->serve( '/fragments/pt-BR/footer', '<footer></footer>' );

		$collected = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'footer' ) );

		$this->assertSame( array( 'footer' => array( 'default' ) ), $collected['summary'] );
	}

	public function test_a_locale_that_fails_for_another_reason_stops_the_sync() {
		$this->publish_site();
		$this->site->serve( '/fragments/pt-BR/footer', 'error', 500 );

		$error = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'footer' ) );

		$this->assertSame( 500, $error->get_error_data()['status'] );
	}

	public function test_a_missing_asset_stops_the_sync() {
		$this->publish_site();
		$this->site->serve( '/assets/footer.js', 'gone', 404 );

		$error = libresign_theme_site_fragment_collect_artifacts( $this->site->origin(), array( 'footer' ) );

		$this->assertSame( 404, $error->get_error_data()['status'] );
	}

	public function test_stores_the_synced_fragments_with_their_manifest() {
		$this->publish_site();

		$result = libresign_theme_sync_site_fragments_from_origin( $this->site->origin() . '/', array(), array( 'source_url' => 'https://github.com/run/1' ) );

		$this->assertSame( $this->site->origin(), $result['origin'] );
		$this->assertSame( array( 'default', 'pt-BR' ), $result['synced']['footer'] );

		$folder   = libresign_theme_site_fragment_storage_base_dir( 'footer' ) . '/pt-BR';
		$manifest = json_decode( (string) file_get_contents( $folder . '/manifest.json' ), true );

		$this->assertSame( '<footer lang="pt-BR"></footer>', file_get_contents( $folder . '/footer.html' ) );
		$this->assertSame( 'footer{}', file_get_contents( $folder . '/footer.css' ) );
		$this->assertSame( 'console.log("footer");', file_get_contents( $folder . '/footer.js' ) );
		$this->assertSame( 'pt-BR', $manifest['locale_key'] );
		$this->assertSame( 'https://github.com/run/1', $manifest['source_url'] );
		$this->assertSame( libresign_theme_site_fragment_storage_base_url( 'footer' ) . '/pt-BR', $manifest['base_url'] );
	}

	public function test_a_new_sync_replaces_the_locales_stored_before() {
		wp_mkdir_p( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/fr' );
		$this->publish_site();

		libresign_theme_sync_site_fragments_from_origin( $this->site->origin() );

		$this->assertDirectoryDoesNotExist( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/fr' );
		$this->assertDirectoryExists( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/default' );
	}

	public function test_a_failed_sync_keeps_the_stored_fragments() {
		wp_mkdir_p( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/fr' );

		$this->assertWPError( libresign_theme_sync_site_fragments_from_origin( $this->site->origin() ) );
		$this->assertDirectoryExists( libresign_theme_site_fragment_storage_base_dir( 'header' ) . '/fr' );
	}

	public function test_renders_and_enqueues_the_stored_fragments_in_place_of_the_template_parts() {
		$this->store(
			array(
				array( 'header', 'default', '<header>stored</header>' ),
				array( 'footer', 'default', '<footer>en</footer>' ),
				array( 'footer', 'en', '<footer>en-generic</footer>' ),
				array( 'footer', 'en-US', '<footer>en-US</footer>' ),
			)
		);

		do_action( 'wp_enqueue_scripts' );

		$this->assertSame( '<header>stored</header>', apply_filters( 'render_block_core/template-part', '<header>theme</header>', array( 'attrs' => array( 'slug' => 'header' ) ) ) );
		$this->assertSame( '<footer>en-US</footer>', apply_filters( 'render_block_core/template-part', '<footer>theme</footer>', array( 'attrs' => array( 'slug' => 'footer' ) ) ) );
		$this->assertSame( '<aside>theme</aside>', apply_filters( 'render_block_core/template-part', '<aside>theme</aside>', array( 'attrs' => array( 'slug' => 'sidebar' ) ) ) );
		$this->assertSame( libresign_theme_site_fragment_storage_base_url( 'header' ) . '/default/header.css', wp_styles()->registered['libresign-site-header-fragment']->src );
		$this->assertSame( libresign_theme_site_fragment_storage_base_url( 'footer' ) . '/en-US/footer.js', wp_scripts()->registered['libresign-site-footer-fragment']->src );
		$this->assertSame( 'module', wp_scripts()->get_data( 'libresign-site-header-fragment', 'type' ) );
	}

	private function store( $fragments ) {
		$artifacts = array();

		foreach ( $fragments as list( $fragment_type, $locale_key, $html ) ) {
			$artifacts[] = array(
				'fragment_type' => $fragment_type,
				'locale'        => 'default' === $locale_key ? '' : $locale_key,
				'locale_key'    => $locale_key,
				'fragment_url'  => '',
				'css_url'       => '',
				'js_url'        => '',
				'generated_at'  => '',
				'source_sha'    => '',
				'source_url'    => '',
				'html'          => $html,
				'css'           => '',
				'js'            => '',
				'version'       => '1',
			);
		}

		libresign_theme_site_fragment_persist_artifacts(
			array(
				'artifacts' => $artifacts,
				'summary'   => array(),
			)
		);
	}

	private function publish_site() {
		$origin = $this->site->origin();
		$header = '<header data-fragment-css="' . $origin . '/assets/header.css" data-fragment-js="' . $origin . '/assets/header.js"><a href="/fragments/pt-BR/header">pt</a><img src="/logo.svg"></header>';
		$footer = '<footer data-fragment-css="' . $origin . '/assets/footer.css" data-fragment-js="' . $origin . '/assets/footer.js"></footer>';

		$this->site->serve( '/fragments/header', $header );
		$this->site->serve( '/fragments/pt-BR/header', $header );
		$this->site->serve( '/fragments/footer', $footer );
		$this->site->serve( '/fragments/pt-BR/footer', str_replace( '<footer ', '<footer lang="pt-BR" ', $footer ) );
		$this->site->serve( '/assets/header.css', 'a{background:url(/bg.png)}' );
		$this->site->serve( '/assets/header.js', 'console.log("header");' );
		$this->site->serve( '/assets/footer.css', 'footer{}' );
		$this->site->serve( '/assets/footer.js', 'console.log("footer");' );
	}
}
