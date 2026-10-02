<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Woocommerce\Myaccount;

use LibreSign\WordPressTheme\Tests\Support\StoreFactory;
use WP_UnitTestCase;

final class FormLoginTest extends WP_UnitTestCase {

	private $store;

	public function set_up() {
		parent::set_up();

		$this->store = new StoreFactory();
		wc_load_cart();
		update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
		update_option( 'woocommerce_registration_generate_username', 'yes' );
		update_option( 'woocommerce_registration_generate_password', 'yes' );
	}

	public function tear_down() {
		unset( $_POST['username'], $_POST['email'] );
		$this->store->empty_cart();

		parent::tear_down();
	}

	private function form() {
		ob_start();
		wc_get_template( 'myaccount/form-login.php' );

		return (string) ob_get_clean();
	}

	private function buying() {
		$this->store->add_to_cart( $this->store->plan() );
	}

	public function test_offers_the_plans_to_a_visitor_who_is_not_buying() {
		$form = $this->form();

		$this->assertStringContainsString( 'id="customer_login"', $form );
		$this->assertStringContainsString( 'New to LibreSign?', $form );
		$this->assertStringContainsString( 'href="' . esc_url( libresign_theme_get_plans_url() ) . '">Choose a plan</a>', $form );
		$this->assertStringNotContainsString( 'woocommerce-form-register', $form );
	}

	public function test_login_leads_to_the_account_when_not_buying() {
		$this->assertStringContainsString(
			'<input type="hidden" name="redirect" value="' . esc_url( libresign_theme_get_account_url() ) . '" />',
			$this->form()
		);
	}

	public function test_creates_the_workspace_while_buying() {
		$this->buying();

		$form = $this->form();

		$this->assertStringContainsString( 'Create your workspace', $form );
		$this->assertStringContainsString( 'name="libresign_workspace_terms"', $form );
		$this->assertStringContainsString( '<a href="https://libresign.coop/privacy-policy" target="_blank" rel="noopener noreferrer">', $form );
		$this->assertStringContainsString( '>Continue to checkout</button>', $form );
		$this->assertStringNotContainsString( 'New to LibreSign?', $form );
	}

	public function test_both_forms_lead_to_checkout_while_buying() {
		$this->buying();

		$this->assertSame(
			2,
			substr_count( $this->form(), '<input type="hidden" name="redirect" value="' . esc_url( wc_get_checkout_url() ) . '" />' )
		);
	}

	public function test_shows_only_the_login_while_buying_when_registration_is_off() {
		$this->buying();
		update_option( 'woocommerce_enable_myaccount_registration', 'no' );

		$form = $this->form();

		$this->assertStringNotContainsString( 'id="customer_login"', $form );
		$this->assertStringNotContainsString( 'woocommerce-form-register', $form );
		$this->assertStringNotContainsString( 'New to LibreSign?', $form );
	}

	public function test_asks_for_a_password_when_woocommerce_does_not_generate_one() {
		$this->buying();
		update_option( 'woocommerce_registration_generate_password', 'no' );

		$form = $this->form();

		$this->assertStringContainsString( 'Set the email and password of your workspace to continue to checkout.', $form );
		$this->assertStringContainsString( 'id="reg_password"', $form );
	}

	public function test_sends_a_password_link_when_woocommerce_generates_the_password() {
		$this->buying();

		$form = $this->form();

		$this->assertStringContainsString( 'Set the email of your workspace to continue to checkout.', $form );
		$this->assertStringContainsString( 'A link to set a new password will be sent to your email address.', $form );
		$this->assertStringNotContainsString( 'id="reg_password"', $form );
	}

	public function test_asks_for_a_username_when_woocommerce_does_not_generate_one() {
		$this->buying();
		update_option( 'woocommerce_registration_generate_username', 'no' );

		$this->assertStringContainsString( 'id="reg_username"', $this->form() );
	}

	public function test_keeps_what_was_typed_escaped() {
		$this->buying();
		update_option( 'woocommerce_registration_generate_username', 'no' );
		$_POST['username'] = 'ana"><b>';
		$_POST['email']    = 'ana@example.org';

		$form = $this->form();

		$this->assertStringContainsString( 'id="username" autocomplete="username" value="ana&quot;&gt;&lt;b&gt;"', $form );
		$this->assertStringContainsString( 'id="reg_username" autocomplete="username" value="ana&quot;&gt;&lt;b&gt;"', $form );
		$this->assertStringContainsString( 'id="reg_email" autocomplete="email" value="ana@example.org"', $form );
	}
}
