<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use WP_UnitTestCase;

final class PlansTest extends WP_UnitTestCase {

	public function test_falls_back_to_the_shop_when_there_is_no_plans_page() {
		$this->assertSame( wc_get_page_permalink( 'shop' ), libresign_theme_get_plans_url() );
	}

	public function test_links_to_the_published_plans_page() {
		$plans = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'plans',
			)
		);

		$this->assertSame( get_permalink( $plans ), libresign_theme_get_plans_url() );
	}

	public function test_ignores_a_plans_page_that_is_not_published() {
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'plans',
				'post_status' => 'draft',
			)
		);

		$this->assertSame( wc_get_page_permalink( 'shop' ), libresign_theme_get_plans_url() );
	}

	public function test_falls_back_to_the_home_page_without_a_shop_page() {
		update_option( 'woocommerce_shop_page_id', 0 );

		$this->assertSame( home_url(), libresign_theme_get_plans_url() );
	}
}
