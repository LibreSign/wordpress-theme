<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\StoreFactory;
use WCS_Related_Order_Store;
use WP_UnitTestCase;

final class OnboardingTest extends WP_UnitTestCase {

	private const HEADING = 'How your LibreSign workspace works';

	private $store;

	public function set_up() {
		parent::set_up();

		$this->store = new StoreFactory();
	}

	public function tear_down() {
		unset( $GLOBALS['wp']->query_vars['order-pay'], $GLOBALS['wp']->query_vars['order-received'] );
		set_query_var( 'order-received', '' );

		parent::tear_down();
	}

	public function test_shows_the_onboarding_while_buying() {
		$this->assertStringContainsString( self::HEADING, $this->onboarding() );
	}

	public function test_shows_the_onboarding_when_a_new_order_is_received() {
		$this->receive( $this->order() );

		$this->assertStringContainsString( self::HEADING, $this->onboarding() );
	}

	public function test_hides_the_onboarding_when_paying_an_existing_order() {
		$GLOBALS['wp']->query_vars['order-pay'] = (string) $this->order()->get_id();

		$this->assertSame( '', trim( $this->onboarding() ) );
	}

	/**
	 * @dataProvider provide_orders_of_existing_subscriptions
	 */
	public function test_hides_the_onboarding_when_an_order_of_an_existing_subscription_is_received( $relation ) {
		$order = $this->order();
		WCS_Related_Order_Store::instance()->add_relation( $order, $this->subscription(), $relation );
		$this->receive( $order );

		$this->assertSame( '', trim( $this->onboarding() ) );
	}

	public static function provide_orders_of_existing_subscriptions() {
		yield 'a renewal'     => array( 'renewal' );
		yield 'a switch'      => array( 'switch' );
		yield 'a resubscribe' => array( 'resubscribe' );
	}

	public function test_keeps_other_groups_while_hiding_the_onboarding() {
		$GLOBALS['wp']->query_vars['order-pay'] = (string) $this->order()->get_id();

		$this->assertStringContainsString( 'Other group', do_blocks( '<!-- wp:group --><div class="wp-block-group"><p>Other group</p></div><!-- /wp:group -->' ) );
	}

	private function onboarding() {
		return do_blocks( '<!-- wp:pattern {"slug":"libresign/saas-onboarding"} /-->' );
	}

	private function order() {
		$order = wc_create_order();
		$order->add_product( $this->store->plan() );
		$order->save();

		return $order;
	}

	private function subscription() {
		return wcs_create_subscription(
			array(
				'customer_id'      => self::factory()->user->create(),
				'billing_period'   => 'month',
				'billing_interval' => 1,
			)
		);
	}

	private function receive( $order ) {
		$GLOBALS['wp']->query_vars['order-received'] = (string) $order->get_id();
		set_query_var( 'order-received', (string) $order->get_id() );
	}
}
