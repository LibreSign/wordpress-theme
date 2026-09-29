<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use WC_Customer;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_UnitTestCase;

final class CpfCnpjTest extends WP_UnitTestCase {

	/**
	 * @dataProvider provide_cpfs
	 */
	public function test_validates_a_cpf( $cpf, $valid ) {
		$this->assertSame( $valid, libresign_theme_validate_cpf( $cpf ) );
	}

	public static function provide_cpfs() {
		yield 'digits only'                  => array( '52998224725', true );
		yield 'formatted'                    => array( '529.982.247-25', true );
		yield 'another valid one'            => array( '123.456.789-09', true );
		yield 'wrong first check digit'      => array( '529.982.247-35', false );
		yield 'wrong second check digit'     => array( '529.982.247-24', false );
		yield 'every digit the same'         => array( '111.111.111-11', false );
		yield 'too short'                    => array( '5299822472', false );
		yield 'too long'                     => array( '529982247250', false );
		yield 'empty'                        => array( '', false );
	}

	/**
	 * @dataProvider provide_cnpjs
	 */
	public function test_validates_a_cnpj( $cnpj, $valid ) {
		$this->assertSame( $valid, libresign_theme_validate_cnpj( $cnpj ) );
	}

	public static function provide_cnpjs() {
		yield 'digits only'                           => array( '11222333000181', true );
		yield 'formatted'                             => array( '11.222.333/0001-81', true );
		yield 'alphanumeric'                          => array( '12.ABC.345/01DE-35', true );
		yield 'alphanumeric in lowercase'             => array( '12.abc.345/01de-35', true );
		yield 'wrong second check digit'              => array( '11.222.333/0001-80', false );
		yield 'wrong alphanumeric check digit'        => array( '12.ABC.345/01DE-34', false );
		yield 'letters in the check digits'           => array( '12.ABC.345/01DE-3A', false );
		yield 'every digit the same'                  => array( '00.000.000/0000-00', false );
		yield 'symbols other than the formatting'     => array( '12.ABC.345/01D*-35', false );
		yield 'too short'                             => array( '1122233300018', false );
		yield 'empty'                                 => array( '', false );
	}

	public function test_registers_the_field_in_the_address_form() {
		__internal_woocommerce_blocks_deregister_checkout_field( 'libresign/cpf-cnpj' );

		libresign_theme_register_cpf_cnpj_field();

		$field = Package::container()->get( CheckoutFields::class )->get_additional_fields()['libresign/cpf-cnpj'];

		$this->assertSame( 'CPF or CNPJ', $field['label'] );
		$this->assertSame( 'address', $field['location'] );
		$this->assertFalse( $field['required'] );
		$this->assertSame( array( 'autocomplete' => 'off' ), $field['attributes'] );
	}

	/**
	 * @dataProvider provide_addresses
	 */
	public function test_validates_the_field_of_a_brazilian_billing_address( $group, $fields, $errors ) {
		$validation = new WP_Error();

		do_action( 'woocommerce_blocks_validate_location_address_fields', $validation, $fields, $group );

		$this->assertSame( $errors, $validation->get_error_codes() );
	}

	public static function provide_addresses() {
		yield 'a shipping address is not checked'   => array( 'shipping', array( 'country' => 'BR' ), array() );
		yield 'another country is not checked'      => array( 'billing', array( 'country' => 'US' ), array() );
		yield 'an address without fields'           => array( 'billing', null, array() );
		yield 'missing in Brazil'                   => array( 'billing', array( 'country' => 'BR' ), array( 'libresign_cpf_cnpj_required' ) );
		yield 'blank in Brazil'                     => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '  ' ), array( 'libresign_cpf_cnpj_required' ) );
		yield 'a valid cpf'                         => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '529.982.247-25' ), array() );
		yield 'an invalid cpf'                      => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '529.982.247-24' ), array( 'invalid_cpf' ) );
		yield 'a valid cnpj'                        => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '11.222.333/0001-81' ), array() );
		yield 'a valid alphanumeric cnpj'           => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '12.ABC.345/01DE-35' ), array() );
		yield 'an invalid cnpj'                     => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '11.222.333/0001-80' ), array( 'invalid_cnpj' ) );
		yield 'neither a cpf nor a cnpj in length'  => array( 'billing', array( 'country' => 'BR', 'libresign/cpf-cnpj' => '123456789' ), array( 'invalid_cpf_cnpj' ) );
	}

	public function test_drops_the_field_from_the_shipping_address_and_keeps_it_on_a_brazilian_billing() {
		$order = $this->order_with_cpf_cnpj( 'BR' );

		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $order, new WP_REST_Request() );

		$this->assertSame( '', $order->get_meta( '_wc_shipping/libresign/cpf-cnpj' ) );
		$this->assertSame( '529.982.247-25', $order->get_meta( '_wc_billing/libresign/cpf-cnpj' ) );
	}

	public function test_drops_the_field_from_an_order_billed_outside_brazil() {
		$order = $this->order_with_cpf_cnpj( 'US' );

		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $order, new WP_REST_Request() );

		$this->assertSame( '', $order->get_meta( '_wc_shipping/libresign/cpf-cnpj' ) );
		$this->assertSame( '', $order->get_meta( '_wc_billing/libresign/cpf-cnpj' ) );
	}

	public function test_drops_the_field_from_a_customer_billed_outside_brazil() {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'US' );
		$customer->update_meta_data( '_wc_billing/libresign/cpf-cnpj', '529.982.247-25' );

		do_action( 'woocommerce_store_api_checkout_update_customer_from_request', $customer, new WP_REST_Request() );

		$this->assertSame( '', $customer->get_meta( '_wc_billing/libresign/cpf-cnpj' ) );
	}

	public function test_ignores_what_is_not_a_woocommerce_object() {
		$this->expectNotToPerformAssertions();

		libresign_theme_strip_irrelevant_cpf_cnpj( 'not an order' );
	}

	public function test_prints_the_field_script_on_the_checkout() {
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$footer = $this->footer();

		$this->assertStringContainsString( "var FIELD_KEY         = 'billing_libresign/cpf-cnpj';", $footer );
		$this->assertStringContainsString( 'var ERROR_MSG         = "Please enter your CPF or CNPJ.";', $footer );
	}

	public function test_prints_no_field_script_outside_the_checkout() {
		$this->go_to( home_url() );

		$this->assertStringNotContainsString( 'billing_libresign/cpf-cnpj', $this->footer() );
	}

	private function footer() {
		$this->setExpectedDeprecated( 'the_block_template_skip_link' );

		ob_start();
		do_action( 'wp_footer' );

		return (string) ob_get_clean();
	}

	private function order_with_cpf_cnpj( $country ) {
		$order = new WC_Order();
		$order->set_billing_country( $country );
		$order->update_meta_data( '_wc_billing/libresign/cpf-cnpj', '529.982.247-25' );
		$order->update_meta_data( '_wc_shipping/libresign/cpf-cnpj', '529.982.247-25' );

		return $order;
	}
}
