<?php

namespace LibreSign\WordPressTheme\Tests\Unit;

use LibreSign\WordPressTheme\Logo;
use PHPUnit\Framework\TestCase;

final class LogoTest extends TestCase {

	/**
	 * @dataProvider provide_logo_markup
	 */
	public function test_reads_every_url_the_browser_may_load( $html, $urls ) {
		$this->assertSame( $urls, Logo::image_urls( $html ) );
	}

	public static function provide_logo_markup() {
		yield 'no markup'                   => array( '', array() );
		yield 'no image'                    => array( '<a href="/">LibreSign</a>', array() );
		yield 'an image without sources'    => array( '<img alt="LibreSign">', array() );
		yield 'only src'                    => array( '<img src="/logo.png">', array( '/logo.png' ) );
		yield 'src and srcset'              => array( '<img src="/logo.png" srcset="/logo.png 1x, /logo-2x.png 2x">', array( '/logo.png', '/logo.png', '/logo-2x.png' ) );
		yield 'width descriptors'           => array( "<img srcset='/a.png 100w,/b.png 200w'>", array( '/a.png', '/b.png' ) );
		yield 'encoded entities'            => array( '<img src="/logo.png?a=1&amp;b=2">', array( '/logo.png?a=1&b=2' ) );
		yield 'only the first image counts' => array( '<img src="/a.png"><img src="/b.png">', array( '/a.png' ) );
	}

	public function test_replaces_the_image_with_a_logo_for_each_color_scheme() {
		$this->assertSame(
			'<a href="/"><picture><source media="(prefers-color-scheme: dark)" srcset="https://example.org/dark.svg"><source media="(prefers-color-scheme: light)" srcset="https://example.org/light.svg"><img src="https://example.org/light.svg" class="custom-logo"></picture></a>',
			Logo::with_fallback(
				'<a href="/"><img src="/missing.png" srcset="/missing-2x.png 2x" sizes="100px" class="custom-logo"></a>',
				'https://example.org/light.svg',
				'https://example.org/dark.svg'
			)
		);
	}

	public function test_leaves_markup_without_an_image_alone() {
		$this->assertSame( '<a href="/">LibreSign</a>', Logo::with_fallback( '<a href="/">LibreSign</a>', 'light.svg', 'dark.svg' ) );
	}
}
