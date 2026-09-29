<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use WP_UnitTestCase;

final class LogoTest extends WP_UnitTestCase {

	private const UPLOADED_LOGO = 'http://example.org/wp-content/uploads/libresign-logo-test/logo.png';

	private const LIGHT_LOGO = 'https://github.com/LibreSign/site/raw/refs/heads/main/source/assets/images/logo/logo.svg';

	private const DARK_LOGO = 'https://github.com/LibreSign/site/raw/refs/heads/main/source/assets/images/logo/logo-2.svg';

	public function set_up() {
		parent::set_up();

		wp_mkdir_p( ABSPATH . 'wp-content/uploads/libresign-logo-test' );
		file_put_contents( ABSPATH . 'wp-content/uploads/libresign-logo-test/logo.png', 'png' );
	}

	public function tear_down() {
		unlink( ABSPATH . 'wp-content/uploads/libresign-logo-test/logo.png' );
		rmdir( ABSPATH . 'wp-content/uploads/libresign-logo-test' );

		parent::tear_down();
	}

	/**
	 * @dataProvider provide_logos
	 */
	public function test_decides_when_the_logo_needs_the_fallback( $html, $needs_fallback ) {
		$this->assertSame( $needs_fallback, libresign_theme_custom_logo_needs_fallback( $html ) );
	}

	public static function provide_logos() {
		yield 'no logo'                                  => array( '', true );
		yield 'markup without an image'                  => array( '<a href="/">LibreSign</a>', true );
		yield 'an image without sources'                 => array( '<img alt="LibreSign">', true );
		yield 'an uploaded logo that exists'             => array( '<img src="' . self::UPLOADED_LOGO . '">', false );
		yield 'an uploaded logo that is missing'         => array( '<img src="http://example.org/wp-content/uploads/missing.png">', true );
		yield 'a missing size among the srcset'          => array( '<img src="' . self::UPLOADED_LOGO . '" srcset="' . self::UPLOADED_LOGO . ' 1x, http://example.org/wp-content/uploads/missing.png 2x">', true );
		yield 'every srcset size exists'                 => array( '<img src="' . self::UPLOADED_LOGO . '" srcset="' . self::UPLOADED_LOGO . ' 1x, ' . self::UPLOADED_LOGO . ' 2x">', false );
		yield 'a logo on another host'                   => array( '<img src="https://cdn.example.com/wp-content/uploads/missing.png">', false );
		yield 'a logo outside the uploads folder'        => array( '<img src="http://example.org/wp-content/themes/missing.png">', false );
		yield 'a relative logo url'                      => array( '<img src="/wp-content/uploads/missing.png">', false );
	}

	public function test_keeps_a_logo_whose_files_exist() {
		$html = '<a href="http://example.org/" class="custom-logo-link"><img src="' . self::UPLOADED_LOGO . '" class="custom-logo"></a>';

		$this->assertSame( $html, apply_filters( 'get_custom_logo', $html, 1 ) );
	}

	public function test_replaces_a_missing_logo_with_the_libresign_logo_for_each_color_scheme() {
		$html = '<a href="http://example.org/" class="custom-logo-link"><img src="http://example.org/wp-content/uploads/missing.png" srcset="http://example.org/wp-content/uploads/missing-2x.png 2x" sizes="100px" class="custom-logo"></a>';

		$this->assertSame(
			'<a href="http://example.org/" class="custom-logo-link"><picture><source media="(prefers-color-scheme: dark)" srcset="' . self::DARK_LOGO . '"><source media="(prefers-color-scheme: light)" srcset="' . self::LIGHT_LOGO . '"><img src="' . self::LIGHT_LOGO . '" class="custom-logo"></picture></a>',
			apply_filters( 'get_custom_logo', $html, 1 )
		);
	}

	public function test_keeps_markup_the_fallback_cannot_patch() {
		$this->assertSame( '', apply_filters( 'get_custom_logo', '', 1 ) );
	}
}
