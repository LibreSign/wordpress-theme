<?php

namespace LibreSign\WordPressTheme\Tests\Unit;

use LibreSign\WordPressTheme\SiteFragment;
use PHPUnit\Framework\TestCase;

final class SiteFragmentTest extends TestCase {

	/**
	 * @dataProvider provide_locales
	 */
	public function test_normalizes_a_locale_into_a_language_tag( $locale, $tag, $storage_key ) {
		$this->assertSame( $tag, SiteFragment::language_tag( $locale ) );
		$this->assertSame( $storage_key, SiteFragment::storage_key( $locale ) );
	}

	public static function provide_locales() {
		yield 'a wordpress locale'           => array( 'pt_BR', 'pt-BR', 'pt-BR' );
		yield 'a language alone'             => array( 'FR', 'fr', 'fr' );
		yield 'a script subtag'              => array( 'zh_hant_tw', 'zh-Hant-TW', 'zh-Hant-TW' );
		yield 'a numeric region'             => array( 'es-419', 'es-419', 'es-419' );
		yield 'a long variant stays as is'   => array( 'de-DE-1901x', 'de-DE-1901x', 'de-DE-1901x' );
		yield 'repeated separators'          => array( 'en--us', 'en-US', 'en-US' );
		yield 'surrounding whitespace'       => array( ' nb_NO ', 'nb-NO', 'nb-NO' );
		yield 'only separators'              => array( '-_', '', 'default' );
		yield 'empty'                        => array( '', '', 'default' );
	}

	/**
	 * @dataProvider provide_fragment_urls
	 */
	public function test_builds_the_fragment_url( $origin, $fragment_type, $locale, $url ) {
		$this->assertSame( $url, SiteFragment::url( $origin, $fragment_type, $locale ) );
	}

	public static function provide_fragment_urls() {
		yield 'the default header'          => array( 'https://libresign.coop', 'header', '', 'https://libresign.coop/fragments/header' );
		yield 'a localized footer'          => array( 'https://libresign.coop/', 'footer', 'pt_BR', 'https://libresign.coop/fragments/pt-BR/footer' );
		yield 'an origin with whitespace'   => array( ' https://libresign.coop/ ', 'header', 'fr', 'https://libresign.coop/fragments/fr/header' );
	}

	public function test_reads_the_asset_urls_of_a_fragment() {
		$this->assertSame(
			array(
				'css' => 'https://libresign.coop/header.css',
				'js'  => 'https://libresign.coop/header.js',
			),
			SiteFragment::asset_urls( "<header data-fragment-css='https://libresign.coop/header.css' data-fragment-js=\"https://libresign.coop/header.js\"></header>" )
		);
	}

	public function test_a_fragment_without_both_asset_urls_has_none() {
		$this->assertNull( SiteFragment::asset_urls( '<header data-fragment-css="/header.css"></header>' ) );
	}

	public function test_reads_the_locales_the_header_links_to() {
		$this->assertSame(
			array( 'pt-BR', 'fr', '' ),
			SiteFragment::linked_locales(
				'<a href="/fragments/pt_BR/header">pt</a><a href="https://libresign.coop/fragments/fr/header#top">fr</a><a href="/fragments/header?x=1">en</a><a href="/fragments/footer">footer</a>'
			)
		);
	}

	public function test_drops_the_asset_attributes_after_syncing() {
		$this->assertSame(
			'<header class="site">x</header>',
			SiteFragment::without_asset_attributes( '<header data-fragment-css="/a.css" class="site" data-fragment-js=\'/a.js\'>x</header>' )
		);
	}

	/**
	 * @dataProvider provide_root_relative_urls
	 */
	public function test_points_root_relative_urls_to_the_static_site( $content, $rewritten ) {
		$this->assertSame( $rewritten, SiteFragment::with_absolute_urls( $content, 'https://libresign.coop/' ) );
	}

	public static function provide_root_relative_urls() {
		yield 'a link'                          => array( '<a href="/pricing/">', '<a href="https://libresign.coop/pricing/">' );
		yield 'an image with single quotes'     => array( "<img src='/logo.svg'>", "<img src='https://libresign.coop/logo.svg'>" );
		yield 'a form and a video poster'       => array( '<form action="/s"><video poster="/p.png">', '<form action="https://libresign.coop/s"><video poster="https://libresign.coop/p.png">' );
		yield 'a css url without quotes'        => array( 'a{background:url(/bg.png)}', 'a{background:url(https://libresign.coop/bg.png)}' );
		yield 'a css url with quotes'           => array( 'a{background:url( "/bg.png" )}', 'a{background:url("https://libresign.coop/bg.png")}' );
		yield 'a protocol relative url'         => array( '<img src="//cdn.example.com/a.png">', '<img src="//cdn.example.com/a.png">' );
		yield 'an absolute url'                 => array( '<a href="https://github.com/">', '<a href="https://github.com/">' );
		yield 'a relative path'                 => array( '<a href="pricing/">', '<a href="pricing/">' );
	}

	/**
	 * @dataProvider provide_origins
	 */
	public function test_normalizes_an_origin( $origin, $normalized ) {
		$this->assertSame( $normalized, SiteFragment::origin( $origin ) );
	}

	public static function provide_origins() {
		yield 'already normalized'   => array( 'https://libresign.coop', 'https://libresign.coop' );
		yield 'the trailing slashes' => array( ' https://libresign.coop// ', 'https://libresign.coop' );
		yield 'empty'                => array( '', '' );
	}

	/**
	 * @dataProvider provide_locale_candidates
	 */
	public function test_looks_the_locale_up_from_the_most_specific_to_the_default( $candidates, $keys ) {
		$this->assertSame( $keys, SiteFragment::lookup_keys( $candidates ) );
	}

	public static function provide_locale_candidates() {
		yield 'a polylang slug and locale'     => array( array( 'pt', 'pt_BR', 'en_US', 'en_US' ), array( 'pt', 'pt-BR', 'en-US', 'en', 'default' ) );
		yield 'a script subtag'                => array( array( 'zh_Hant_TW' ), array( 'zh-Hant-TW', 'zh', 'default' ) );
		yield 'nothing usable'                 => array( array( '', '-' ), array( 'default' ) );
	}
}
