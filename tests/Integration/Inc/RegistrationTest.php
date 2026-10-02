<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\StoreFactory;
use WP_Error;
use WP_UnitTestCase;

final class RegistrationTest extends WP_UnitTestCase {

	private $store;

	public function set_up() {
		parent::set_up();

		$this->store = new StoreFactory();
	}

	public function tear_down() {
		unset( $_POST['libresign_workspace_terms'] );
		$this->store->empty_cart();

		parent::tear_down();
	}

	private function registration_errors() {
		return apply_filters( 'woocommerce_process_registration_errors', new WP_Error(), 'ana', 'secret', 'ana@example.org' );
	}

	public function test_the_policy_is_the_one_the_footer_links_to() {
		$this->assertSame( 'https://libresign.coop/privacy-policy', libresign_theme_get_policy_url() );
	}

	public function test_an_empty_cart_has_no_purchase_in_progress() {
		wc_load_cart();

		$this->assertFalse( libresign_theme_cart_has_items() );
	}

	public function test_a_cart_with_a_plan_is_a_purchase_in_progress() {
		$this->store->add_to_cart( $this->store->plan() );

		$this->assertTrue( libresign_theme_cart_has_items() );
	}

	public function test_a_cart_that_was_not_loaded_has_no_purchase_in_progress() {
		WC()->cart = null;

		$this->assertFalse( libresign_theme_cart_has_items() );
	}

	public function test_refuses_a_workspace_without_the_terms_consent() {
		$this->assertContains( 'libresign_workspace_terms', $this->registration_errors()->get_error_codes() );
	}

	public function test_accepts_a_workspace_with_the_terms_consent() {
		$_POST['libresign_workspace_terms'] = '1';

		$this->assertFalse( $this->registration_errors()->has_errors() );
	}

	public function test_the_checkout_account_creation_does_not_ask_for_the_workspace_terms() {
		$errors = apply_filters( 'woocommerce_registration_errors', new WP_Error(), 'ana', 'ana@example.org' );

		$this->assertNotContains( 'libresign_workspace_terms', $errors->get_error_codes() );
	}

	public function test_records_when_the_customer_agreed_to_the_terms() {
		$_POST['libresign_workspace_terms'] = '1';
		$customer                           = self::factory()->user->create();

		do_action( 'woocommerce_created_customer', $customer, array(), false );

		$this->assertSame( 'yes', get_user_meta( $customer, 'libresign_workspace_terms', true ) );
		$this->assertEqualsWithDelta(
			strtotime( current_time( 'mysql' ) ),
			strtotime( get_user_meta( $customer, 'libresign_workspace_terms_date', true ) ),
			5
		);
	}

	public function test_records_nothing_for_a_customer_created_without_the_consent() {
		$customer = self::factory()->user->create();

		do_action( 'woocommerce_created_customer', $customer, array(), false );

		$this->assertSame( '', get_user_meta( $customer, 'libresign_workspace_terms', true ) );
	}

	public function test_login_and_registration_lead_to_checkout_while_buying() {
		$this->store->add_to_cart( $this->store->plan() );

		$this->assertSame( wc_get_checkout_url(), apply_filters( 'woocommerce_login_redirect', home_url(), null ) );
		$this->assertSame( wc_get_checkout_url(), apply_filters( 'woocommerce_registration_redirect', home_url() ) );
	}

	public function test_login_and_registration_lead_to_the_account_otherwise() {
		wc_load_cart();

		$this->assertSame( wc_get_page_permalink( 'myaccount' ), apply_filters( 'woocommerce_login_redirect', home_url(), null ) );
		$this->assertSame( wc_get_page_permalink( 'myaccount' ), apply_filters( 'woocommerce_registration_redirect', home_url() ) );
	}
}
