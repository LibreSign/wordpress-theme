<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use WP_Error;
use WP_UnitTestCase;

final class CheckoutTest extends WP_UnitTestCase {

	private function checkout_errors( $data ) {
		$errors = new WP_Error();
		do_action( 'woocommerce_after_checkout_validation', $data, $errors );

		return $errors;
	}

	public function test_the_terms_checkbox_links_to_the_policy_in_a_new_tab() {
		$this->assertSame(
			'I agree to the <a href="https://libresign.coop/privacy-policy" target="_blank" rel="noopener noreferrer">terms and privacy policy</a> before placing the order.',
			apply_filters( 'woocommerce_get_terms_and_conditions_checkbox_text', '' )
		);
	}

	public function test_refuses_an_order_without_the_policy_consent() {
		$errors = $this->checkout_errors( array( 'terms' => 0 ) );

		$this->assertContains( 'libresign_policy_consent', $errors->get_error_codes() );
	}

	public function test_accepts_an_order_with_the_policy_consent() {
		$errors = $this->checkout_errors( array( 'terms' => 1 ) );

		$this->assertNotContains( 'libresign_policy_consent', $errors->get_error_codes() );
	}
}
