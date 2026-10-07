<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Patterns;

use WP_UnitTestCase;

final class SaasOnboardingTest extends WP_UnitTestCase {

	public function test_the_pattern_is_registered() {
		$this->assertTrue( \WP_Block_Patterns_Registry::get_instance()->is_registered( 'libresign/saas-onboarding' ) );
	}

	/**
	 * @dataProvider provide_templates_of_the_purchase
	 */
	public function test_the_template_shows_the_onboarding( $template ) {
		$html = do_blocks( get_block_template( get_stylesheet() . '//' . $template )->content );

		$this->assertSame( 1, substr_count( $html, 'How your LibreSign workspace works' ) );
		$this->assertStringContainsString( '1. Choose a plan', $html );
		$this->assertStringContainsString( '2. Create your workspace', $html );
		$this->assertStringContainsString( '3. Start signing', $html );
	}

	public static function provide_templates_of_the_purchase() {
		yield 'the shop'           => array( 'archive-product' );
		yield 'the checkout'       => array( 'page-checkout' );
		yield 'the order received' => array( 'order-confirmation' );
	}
}
