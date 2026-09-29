<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use WP_Error;
use WP_UnitTestCase;

final class CheckoutTest extends WP_UnitTestCase {

	public function test_requires_the_policy_consent_to_place_an_order() {
		__internal_woocommerce_blocks_deregister_checkout_field( 'libresign/policy-consent' );

		libresign_theme_register_policy_consent_field();

		$field = Package::container()->get( CheckoutFields::class )->get_additional_fields()['libresign/policy-consent'];

		$this->assertSame( 'checkbox', $field['type'] );
		$this->assertSame( 'order', $field['location'] );
		$this->assertTrue( $field['required'] );
		$this->assertSame( 'I agree to the terms and privacy policy before placing the order.', $field['label'] );
		$this->assertSame( 'You must agree to the policies before completing the purchase.', $field['errorMessage'] );
	}

	public function test_the_classic_checkout_refuses_an_order_without_the_consent() {
		$errors = new WP_Error();

		do_action( 'woocommerce_after_checkout_validation', array( 'terms' => 0 ), $errors );

		$this->assertContains( 'libresign_policy_consent', $errors->get_error_codes() );
	}

	public function test_the_classic_checkout_accepts_an_order_with_the_consent() {
		$errors = new WP_Error();

		do_action( 'woocommerce_after_checkout_validation', array( 'terms' => 1 ), $errors );

		$this->assertNotContains( 'libresign_policy_consent', $errors->get_error_codes() );
	}

	/**
	 * @dataProvider provide_terms_blocks
	 */
	public function test_links_the_checkout_terms_to_the_policy( $block_content ) {
		$this->assertSame(
			'<div data-text="Read the &lt;a href=&quot;https://libresign.coop/privacy-policy&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;&gt;terms and privacy policy&lt;/a&gt;."' . substr( $block_content, 4 ),
			apply_filters( 'render_block_woocommerce/checkout-terms-block', $block_content )
		);
	}

	public static function provide_terms_blocks() {
		yield 'as saved'                  => array( '<div class="wp-block-woocommerce-checkout-terms-block"></div>' );
		yield 'as woocommerce renders it' => array( '<div data-block-name="woocommerce/checkout-terms-block" class="wp-block-woocommerce-checkout-terms-block"></div>' );
	}

	public function test_leaves_other_markup_alone() {
		$this->assertSame( '<div class="wp-block-group"></div>', libresign_theme_link_checkout_terms_to_policy( '<div class="wp-block-group"></div>' ) );
	}
}
