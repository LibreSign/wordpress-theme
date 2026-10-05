<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use WP_UnitTestCase;

final class ThemeSetupTest extends WP_UnitTestCase {

	public function test_the_theme_is_active() {
		$this->assertSame( basename( dirname( __DIR__, 3 ) ), get_stylesheet() );
	}

	public function test_translations_are_read_from_the_theme_languages_folder() {
		global $wp_textdomain_registry;

		$this->assertSame(
			trailingslashit( get_template_directory() . '/languages' ),
			$wp_textdomain_registry->get( 'libresign', 'pt_BR' )
		);
	}
}
