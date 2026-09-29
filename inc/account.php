<?php
/**
 * Account page: URL helpers, content filter, lost-password form and
 * direct lost-password route handler.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// URL / redirect helpers
// ---------------------------------------------------------------------------

/**
 * Resolve the canonical WooCommerce My Account URL.
 */
function libresign_theme_get_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account_url = wc_get_page_permalink( 'myaccount' );
		if ( ! empty( $account_url ) ) {
			return $account_url;
		}
	}

	return home_url( '/account/' );
}

/**
 * Preserve the intended destination after authentication: an explicit
 * `redirect_to`, then checkout while a purchase is in progress, then the
 * account dashboard.
 */
function libresign_theme_get_purchase_redirect_target() {
	$redirect_to = '';

	if ( isset( $_REQUEST['redirect_to'] ) && is_string( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirect_to = wp_sanitize_redirect( wp_unslash( $_REQUEST['redirect_to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$redirect_to = wp_validate_redirect( $redirect_to, '' );

	if ( '' !== $redirect_to ) {
		return $redirect_to;
	}

	if ( function_exists( 'libresign_theme_cart_has_items' ) && libresign_theme_cart_has_items() && function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}

	return libresign_theme_get_account_url();
}

// ---------------------------------------------------------------------------
// Lost-password detection helpers
// ---------------------------------------------------------------------------

/**
 * Detect if the current request targets the WooCommerce lost-password endpoint.
 */
function libresign_theme_is_lost_password_request() {
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
		return true;
	}

	if ( isset( $_GET['action'] ) && 'lostpassword' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	return false;
}

/**
 * Detect the direct /lost-password/ route used outside the WooCommerce account page.
 */
function libresign_theme_is_direct_lost_password_route() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

	return '/lost-password/' === $path || '/lost-password' === $path;
}

/**
 * Detect a WooCommerce password-reset confirmation request (reset link,
 * set-new-password or link-sent step).
 */
function libresign_theme_is_password_reset_confirmation() {
	if ( isset( $_GET['key'] ) && ( isset( $_GET['id'] ) || isset( $_GET['login'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	return isset( $_GET['show-reset-form'] ) || isset( $_GET['reset-link-sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

// ---------------------------------------------------------------------------
// Lost-password form
// ---------------------------------------------------------------------------

/**
 * Render the lost-password form.
 */
function libresign_theme_render_lost_password_form() {
	$account_url  = libresign_theme_get_account_url();
	$button_class = function_exists( 'wc_wp_theme_get_element_class_name' ) && wc_wp_theme_get_element_class_name( 'button' )
		? ' ' . wc_wp_theme_get_element_class_name( 'button' )
		: '';
	?>
	<div class="woocommerce libresign-lost-password">
		<div class="libresign-lost-password__card">
			<p class="libresign-lost-password__intro">
				<?php esc_html_e( 'Enter your email or username and we will send you a link to reset your password.', 'libresign' ); ?>
			</p>

			<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
				<?php wc_print_notices(); ?>
			<?php endif; ?>

			<form method="post" class="woocommerce-form woocommerce-form-login login lost_reset_password">
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="user_login"><?php esc_html_e( 'Email or username', 'libresign' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
					<input type="text" name="user_login" id="user_login" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="username" required aria-required="true" />
				</p>

				<input type="hidden" name="wc_reset_password" value="true" />
				<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

				<p class="woocommerce-form-row form-row">
					<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( $button_class ); ?>"><?php esc_html_e( 'Send reset link', 'libresign' ); ?></button>
				</p>

				<p class="woocommerce-LostPassword lost_password libresign-lost-password__back">
					<a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Back to sign in', 'libresign' ); ?></a>
				</p>
			</form>
		</div>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Content filter — account page
// ---------------------------------------------------------------------------

/**
 * Render account content for pages with empty post_content.
 *
 * - Lost-password request → custom shell form.
 * - Any other account page → WooCommerce my-account shortcode (which uses
 *   the woocommerce/myaccount/form-login.php template override for guests
 *   and the standard dashboard for logged-in users).
 * - Shop / checkout → prepend the SaaS onboarding block pattern.
 */
function libresign_theme_prepend_saas_onboarding_to_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	// wpautop/shortcode_unautop would inject <br> tags inside form HTML on
	// account pages; remove them before returning any account page output.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'shortcode_unautop' );
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() && libresign_theme_is_lost_password_request() ) {
		if ( libresign_theme_is_password_reset_confirmation() ) {
			return function_exists( 'do_shortcode' ) ? do_shortcode( '[woocommerce_my_account]' ) : $content;
		}

		ob_start();
		libresign_theme_render_lost_password_form();
		return ob_get_clean();
	}

	// The My Account page has empty post_content; delegate rendering to
	// WooCommerce so the template override is respected.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return function_exists( 'do_shortcode' ) ? do_shortcode( '[woocommerce_my_account]' ) : $content;
	}

	$should_prepend = ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() );

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		$should_prepend = true;
	}

	if ( $should_prepend ) {
		$onboarding_block = '<!-- wp:pattern {"slug":"libresign/saas-onboarding"} /-->';
		if ( false === strpos( $content, 'libresign/saas-onboarding' ) ) {
			$content = do_blocks( $onboarding_block ) . $content;
		}
	}

	return $content;
}
add_filter( 'the_content', 'libresign_theme_prepend_saas_onboarding_to_content', 5 );

// ---------------------------------------------------------------------------
// Direct /lost-password/ route handler
// ---------------------------------------------------------------------------

/**
 * Render the lost-password page when the route is visited outside the
 * WooCommerce account page (e.g. /lost-password/ directly).
 */
function libresign_theme_render_direct_lost_password_route() {
	if ( is_admin() || wp_doing_ajax() || ! libresign_theme_is_direct_lost_password_route() ) {
		return;
	}

	// Let WooCommerce's redirect_reset_password_link() (priority 10) handle
	// confirmation links.
	if ( libresign_theme_is_password_reset_confirmation() ) {
		return;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return;
	}

	status_header( 200 );
	nocache_headers();

	$header = do_blocks( '<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->' );
	$footer = do_blocks( '<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->' );
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'libresign-lost-password-page' ); ?>>
		<?php wp_body_open(); ?>
		<div class="wp-site-blocks">
			<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<main class="wp-block-group">
				<div class="wp-block-group is-layout-constrained">
					<h1 class="wp-block-post-title has-text-align-center"><?php esc_html_e( 'Lost password', 'libresign' ); ?></h1>
					<?php libresign_theme_render_lost_password_form(); ?>
				</div>
			</main>
			<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'libresign_theme_render_direct_lost_password_route', 1 );
