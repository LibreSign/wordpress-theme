<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use WP_UnitTestCase;

final class AssetsTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		foreach ( array( 'libresign-saas', 'bootstrap-css', 'lineicons', 'libresign-header-footer' ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
		remove_action( 'wp_enqueue_scripts', 'libresign_theme_site_fragment_enqueue_assets', 30 );
	}

	public function tear_down() {
		add_action( 'wp_enqueue_scripts', 'libresign_theme_site_fragment_enqueue_assets', 30 );

		parent::tear_down();
	}

	private function enqueue() {
		do_action( 'wp_enqueue_scripts' );

		return wp_styles();
	}

	public function test_the_saas_stylesheet_follows_the_woocommerce_styles() {
		$style = $this->enqueue()->registered['libresign-saas'];

		$this->assertSame( get_theme_file_uri( 'assets/css/saas.css' ), $style->src );
		$this->assertSame( array( 'woocommerce-general' ), $style->deps );
		$this->assertSame( (string) filemtime( get_theme_file_path( 'assets/css/saas.css' ) ), $style->ver );
	}

	public function test_the_saas_stylesheet_stands_alone_without_the_woocommerce_styles() {
		$woocommerce_general = wp_styles()->registered['woocommerce-general'] ?? null;
		remove_action( 'wp_enqueue_scripts', array( 'WC_Frontend_Scripts', 'load_scripts' ) );
		wp_deregister_style( 'woocommerce-general' );

		$deps = $this->enqueue()->registered['libresign-saas']->deps;

		if ( null !== $woocommerce_general ) {
			wp_styles()->registered['woocommerce-general'] = $woocommerce_general;
		}

		$this->assertSame( array(), $deps );
	}

	public function test_loads_the_assets_the_site_header_and_footer_use() {
		$styles = $this->enqueue();

		$this->assertSame( 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css', $styles->registered['bootstrap-css']->src );
		$this->assertSame( 'https://cdn.lineicons.com/4.0/lineicons.css', $styles->registered['lineicons']->src );
		$this->assertSame( get_theme_file_uri( 'assets/css/header-footer.css' ), $styles->registered['libresign-header-footer']->src );
		$this->assertContains( 'libresign-header-footer', $styles->queue );
	}

	public function test_the_header_and_footer_styles_come_after_the_other_stylesheets() {
		$queue = $this->enqueue()->queue;

		$this->assertSame( 'libresign-header-footer', end( $queue ) );
	}
}
