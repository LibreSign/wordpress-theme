<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\StoreFactory;
use WP_UnitTestCase;

final class AccountTest extends WP_UnitTestCase {

	private $store;

	public function set_up() {
		parent::set_up();

		$this->store = new StoreFactory();
	}

	public function tear_down() {
		$_GET     = array();
		$_REQUEST = array();
		$_SERVER['REQUEST_URI'] = '';
		unset( $GLOBALS['wp']->query_vars['lost-password'] );
		$this->store->empty_cart();
		add_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'shortcode_unautop' );
		set_current_screen( 'front' );

		parent::tear_down();
	}

	public function test_the_account_is_the_woocommerce_my_account_page() {
		$this->assertSame( wc_get_page_permalink( 'myaccount' ), libresign_theme_get_account_url() );
	}

	public function test_goes_back_to_the_page_asked_for() {
		$_REQUEST['redirect_to'] = '/store/';

		$this->assertSame( '/store/', libresign_theme_get_purchase_redirect_target() );
	}

	public function test_never_sends_to_another_site() {
		$_REQUEST['redirect_to'] = 'https://evil.example/';

		$this->assertSame( libresign_theme_get_account_url(), libresign_theme_get_purchase_redirect_target() );
	}

	public function test_ignores_a_redirect_that_is_not_a_single_url() {
		$_REQUEST['redirect_to'] = array( '/store/' );

		$this->assertSame( libresign_theme_get_account_url(), libresign_theme_get_purchase_redirect_target() );
	}

	public function test_goes_to_checkout_while_buying() {
		$this->store->add_to_cart( $this->store->plan() );

		$this->assertSame( wc_get_checkout_url(), libresign_theme_get_purchase_redirect_target() );
	}

	public function test_goes_to_the_account_otherwise() {
		wc_load_cart();

		$this->assertSame( libresign_theme_get_account_url(), libresign_theme_get_purchase_redirect_target() );
	}

	/**
	 * @dataProvider provide_lost_password_actions
	 */
	public function test_recognizes_the_lost_password_action( $action, $expected ) {
		$_GET['action'] = $action;

		$this->assertSame( $expected, libresign_theme_is_lost_password_request() );
	}

	public static function provide_lost_password_actions() {
		yield 'lost password'          => array( 'lostpassword', true );
		yield 'in another letter case' => array( 'LostPassword', true );
		yield 'another action'         => array( 'login', false );
	}

	public function test_recognizes_the_woocommerce_lost_password_endpoint() {
		$GLOBALS['wp']->query_vars['lost-password'] = '';

		$this->assertTrue( libresign_theme_is_lost_password_request() );
	}

	public function test_a_plain_request_is_not_a_lost_password_request() {
		$this->assertFalse( libresign_theme_is_lost_password_request() );
	}

	/**
	 * @dataProvider provide_request_uris
	 */
	public function test_recognizes_the_direct_lost_password_route( $request_uri, $expected ) {
		unset( $_SERVER['REQUEST_URI'] );
		if ( null !== $request_uri ) {
			$_SERVER['REQUEST_URI'] = $request_uri;
		}

		$this->assertSame( $expected, libresign_theme_is_direct_lost_password_route() );
	}

	public static function provide_request_uris() {
		yield 'with the trailing slash'    => array( '/lost-password/', true );
		yield 'without the trailing slash' => array( '/lost-password', true );
		yield 'with a query string'        => array( '/lost-password/?reset-link-sent=true', true );
		yield 'under the account page'     => array( '/account/lost-password/', false );
		yield 'the home page'              => array( '/', false );
		yield 'no request uri'             => array( null, false );
	}

	/**
	 * @dataProvider provide_reset_confirmations
	 */
	public function test_recognizes_a_password_reset_confirmation( $query, $expected ) {
		$_GET = $query;

		$this->assertSame( $expected, libresign_theme_is_password_reset_confirmation() );
	}

	public static function provide_reset_confirmations() {
		yield 'a reset link with the user id'    => array( array( 'key' => 'abc', 'id' => '7' ), true );
		yield 'a reset link with the user login' => array( array( 'key' => 'abc', 'login' => 'ana' ), true );
		yield 'the set new password step'        => array( array( 'show-reset-form' => 'true' ), true );
		yield 'the link sent step'               => array( array( 'reset-link-sent' => 'true' ), true );
		yield 'a key without its user'           => array( array( 'key' => 'abc' ), false );
		yield 'nothing'                          => array( array(), false );
	}

	public function test_the_lost_password_form_posts_to_woocommerce() {
		$_SERVER['REQUEST_URI'] = '/lost-password/';

		ob_start();
		libresign_theme_render_lost_password_form();
		$form = (string) ob_get_clean();

		$this->assertStringContainsString( '<input type="hidden" name="wc_reset_password" value="true" />', $form );
		$this->assertStringContainsString( 'name="woocommerce-lost-password-nonce"', $form );
		$this->assertStringContainsString( '<input type="text" name="user_login" id="user_login"', $form );
		$this->assertStringContainsString( '<a href="' . esc_url( libresign_theme_get_account_url() ) . '">Back to sign in</a>', $form );
	}

	public function test_the_account_page_renders_the_woocommerce_account() {
		$content = $this->account_page_content();

		$this->assertStringContainsString( 'woocommerce-form-login', $content );
		$this->assertFalse( has_filter( 'the_content', 'wpautop' ) );
	}

	public function test_the_account_page_renders_the_lost_password_form() {
		$this->assertStringContainsString( 'libresign-lost-password__card', $this->account_page_content( array( 'action' => 'lostpassword' ) ) );
	}

	public function test_the_account_page_leaves_the_reset_confirmation_to_woocommerce() {
		$content = $this->account_page_content(
			array(
				'action'          => 'lostpassword',
				'show-reset-form' => 'true',
			)
		);

		$this->assertStringNotContainsString( 'libresign-lost-password__card', $content );
		$this->assertStringContainsString( 'woocommerce', $content );
	}

	public function test_keeps_the_content_of_other_pages() {
		$this->in_the_loop_of( home_url() );

		$this->assertSame( '<p>Page</p>', libresign_theme_render_account_content( '<p>Page</p>' ) );
	}

	public function test_keeps_the_content_outside_the_loop() {
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		$this->go_to( home_url() );

		$this->assertSame( '<p>Page</p>', libresign_theme_render_account_content( '<p>Page</p>' ) );
	}

	public function test_keeps_the_content_in_the_admin() {
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		$this->in_the_loop_of( home_url() );
		set_current_screen( 'dashboard' );

		$this->assertSame( '<p>Page</p>', libresign_theme_render_account_content( '<p>Page</p>' ) );
	}

	/**
	 * @dataProvider provide_requests_the_direct_route_leaves_alone
	 */
	public function test_the_direct_lost_password_route_leaves_other_requests_alone( $request_uri, $query, $account_page ) {
		$_SERVER['REQUEST_URI'] = $request_uri;
		$_GET                   = $query;
		add_filter( 'woocommerce_is_account_page', $account_page ? '__return_true' : '__return_false' );

		$this->expectOutputString( '' );

		libresign_theme_render_direct_lost_password_route();
	}

	public static function provide_requests_the_direct_route_leaves_alone() {
		yield 'another route'               => array( '/store/', array(), false );
		yield 'a reset confirmation'        => array( '/lost-password/', array( 'show-reset-form' => 'true' ), false );
		yield 'the route of the account'    => array( '/lost-password/', array(), true );
	}

	private function account_page_content( $query = array() ) {
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		$this->in_the_loop_of( add_query_arg( $query, home_url( '/' ) ) );

		return libresign_theme_render_account_content( '' );
	}

	private function in_the_loop_of( $url ) {
		$this->go_to( $url );
		$GLOBALS['wp_query']->in_the_loop = true;
	}
}
