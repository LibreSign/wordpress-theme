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
function libresign_get_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account_url = wc_get_page_permalink( 'myaccount' );
		if ( ! empty( $account_url ) ) {
			return $account_url;
		}
	}

	return home_url( '/account/' );
}

/**
 * Preserve the intended destination after authentication when coming from a
 * product or purchase page.
 */
function libresign_get_purchase_redirect_target() {
	$redirect_to = '';

	if ( isset( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirect_to = wp_unslash( $_REQUEST['redirect_to'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$redirect_to = wp_validate_redirect( $redirect_to, '' );

	if ( '' !== $redirect_to ) {
		return $redirect_to;
	}

	return libresign_get_account_url();
}

// ---------------------------------------------------------------------------
// Lost-password detection helpers
// ---------------------------------------------------------------------------

/**
 * Detect if the current request targets the WooCommerce lost-password endpoint.
 */
function libresign_is_lost_password_request() {
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
function libresign_is_direct_lost_password_route() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

	return '/lost-password/' === $path || '/lost-password' === $path;
}

/**
 * Detect a WooCommerce password-reset confirmation request (reset link,
 * set-new-password or link-sent step).
 */
function libresign_is_password_reset_confirmation() {
	if ( isset( $_GET['key'] ) && ( isset( $_GET['id'] ) || isset( $_GET['login'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	return isset( $_GET['show-reset-form'] ) || isset( $_GET['reset-link-sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

// ---------------------------------------------------------------------------
// Lost-password form
// ---------------------------------------------------------------------------

/**
 * Render the lost-password form used on the account endpoint.
 */
function libresign_render_lost_password_form() {
	$lost_password_url = function_exists( 'wc_lostpassword_url' ) ? wc_lostpassword_url() : wp_lostpassword_url();
	$account_url       = libresign_get_account_url();
	?>
	<section class="libresign-account-shell" id="libresign-account-shell">
		<aside class="libresign-account-shell__aside">
			<div class="libresign-account-shell__brand">
				<div class="libresign-account-shell__mark" aria-hidden="true">L</div>
				<div>
					<p class="libresign-account-shell__brand-name">LibreSign</p>
				</div>
			</div>

			<div class="libresign-account-shell__hero">
				<p class="libresign-account-shell__eyebrow"><?php esc_html_e( 'Access', 'libresign' ); ?></p>
				<h2><?php esc_html_e( 'Reset password', 'libresign' ); ?></h2>
				<p><?php esc_html_e( 'Enter your email or username to receive a password reset link.', 'libresign' ); ?></p>
			</div>

			<div class="libresign-account-shell__actions">
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'tab', 'register', $account_url ) ); ?>#libresign-account-shell" data-libresign-open-tab="register"><?php esc_html_e( 'Create free workspace', 'libresign' ); ?></a>
				<a class="button" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Back to sign in', 'libresign' ); ?></a>
			</div>
		</aside>

		<main class="libresign-account-shell__main" tabindex="-1">
			<div class="libresign-account-shell__tabs" role="tablist" aria-label="<?php esc_attr_e( 'LibreSign access', 'libresign' ); ?>">
				<a class="libresign-account-shell__tab is-active" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Sign in', 'libresign' ); ?></a>
				<a class="libresign-account-shell__tab" href="<?php echo esc_url( $lost_password_url ); ?>" aria-selected="true"><?php esc_html_e( 'Forgot password', 'libresign' ); ?></a>
			</div>

			<div class="libresign-account-shell__panels">
				<section class="libresign-account-shell__panel is-active" data-libresign-panel="lost-password" role="tabpanel">
					<div class="libresign-account-shell__panel-header">
						<p class="libresign-account-shell__eyebrow"><?php esc_html_e( 'Forgot password', 'libresign' ); ?></p>
						<h2><?php esc_html_e( 'Send reset link', 'libresign' ); ?></h2>
						<p><?php esc_html_e( 'If your account exists, we will send you an email with the next step.', 'libresign' ); ?></p>
					</div>

					<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
						<div class="libresign-account-shell__notices">
							<?php wc_print_notices(); ?>
						</div>
					<?php endif; ?>

					<form method="post" class="woocommerce-form woocommerce-form-login login lost_reset_password">
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="user_login"><?php esc_html_e( 'Email or username', 'libresign' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
							<input type="text" name="user_login" id="user_login" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="username" required aria-required="true" />
						</p>

						<input type="hidden" name="wc_reset_password" value="true" />
						<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

						<p class="form-row">
							<button type="submit" class="woocommerce-button button woocommerce-form-login__submit"><?php esc_html_e( 'Send reset link', 'libresign' ); ?></button>
						</p>
					</form>
				</section>
			</div>
		</main>
	</section>
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
function libresign_prepend_saas_onboarding_to_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() && libresign_is_lost_password_request() ) {
		if ( libresign_is_password_reset_confirmation() ) {
			return $content;
		}

		ob_start();
		libresign_render_lost_password_form();
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
add_filter( 'the_content', 'libresign_prepend_saas_onboarding_to_content', 5 );

// ---------------------------------------------------------------------------
// Direct /lost-password/ route handler
// ---------------------------------------------------------------------------

/**
 * Render the lost-password page when the route is visited outside the
 * WooCommerce account page (e.g. /lost-password/ directly).
 */
function libresign_render_direct_lost_password_route() {
	if ( is_admin() || wp_doing_ajax() || ! libresign_is_direct_lost_password_route() ) {
		return;
	}

	// Let WooCommerce's redirect_reset_password_link() (priority 10) handle
	// confirmation links.
	if ( libresign_is_password_reset_confirmation() ) {
		return;
	}

	status_header( 200 );
	nocache_headers();

	get_header();
	echo '<main class="wp-block-group" style="margin-top:0">';
	libresign_render_lost_password_form();
	echo '</main>';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'libresign_render_direct_lost_password_route', 1 );
