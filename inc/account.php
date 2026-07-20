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
 * Render the lost-password form.
 */
function libresign_render_lost_password_form() {
	$account_url  = libresign_get_account_url();
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

/**
 * Print the scoped styles for the lost-password form.
 */
function libresign_lost_password_styles() {
	if ( is_admin() ) {
		return;
	}

	$is_lost_password = ( function_exists( 'is_account_page' ) && is_account_page() && libresign_is_lost_password_request() && ! libresign_is_password_reset_confirmation() )
		|| libresign_is_direct_lost_password_route();

	if ( ! $is_lost_password ) {
		return;
	}
	?>
	<style id="libresign-lost-password-styles">
		.libresign-lost-password {
			display: flex;
			justify-content: center;
		}
		.libresign-lost-password__card {
			width: 100%;
			max-width: 26rem;
			margin: var(--wp--preset--spacing--30, 2rem) auto var(--wp--preset--spacing--40, 4rem);
			padding: clamp(1.5rem, 4vw, 2.5rem);
			background: var(--wp--preset--color--base-2, #ffffff);
			border: 1px solid var(--wp--preset--color--accent, #cfcabe);
			border-radius: .75rem;
			box-shadow: 0 1px 2px rgba(17, 17, 17, .04), 0 12px 32px rgba(17, 17, 17, .06);
		}
		.libresign-lost-password__intro {
			margin: 0 0 1.5rem;
			color: var(--wp--preset--color--contrast-2, #636363);
			font-size: var(--wp--preset--font-size--medium, 1.05rem);
			line-height: 1.5;
		}
		.libresign-lost-password form.login {
			margin: 0;
			padding: 0;
			border: 0;
			border-radius: 0;
		}
		.libresign-lost-password .woocommerce-form-row {
			margin-bottom: 1.25rem;
		}
		.libresign-lost-password label {
			display: block;
			margin-bottom: .4rem;
			font-weight: 600;
			color: var(--wp--preset--color--contrast, #111111);
		}
		.libresign-lost-password input.input-text {
			box-sizing: border-box;
			width: 100%;
			padding: .7rem .9rem;
			border: 1px solid var(--wp--preset--color--contrast-3, #a4a4a4);
			border-radius: .33rem;
			background: var(--wp--preset--color--base-2, #ffffff);
			color: var(--wp--preset--color--contrast, #111111);
			font-size: 1rem;
		}
		.libresign-lost-password input.input-text:focus {
			outline: 2px solid var(--wp--preset--color--contrast, #111111);
			outline-offset: 1px;
			border-color: var(--wp--preset--color--contrast, #111111);
		}
		.libresign-lost-password .woocommerce-form-login__submit {
			width: 100%;
			padding: .75rem 1rem;
			font-size: 1rem;
			cursor: pointer;
		}
		.libresign-lost-password__back {
			margin: 1.25rem 0 0;
			text-align: center;
			font-size: var(--wp--preset--font-size--small, .9rem);
		}
	</style>
	<?php
}
add_action( 'wp_head', 'libresign_lost_password_styles' );

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

	// wpautop/shortcode_unautop would inject <br> tags inside form HTML on
	// account pages; remove them before returning any account page output.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'shortcode_unautop' );
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() && libresign_is_lost_password_request() ) {
		if ( libresign_is_password_reset_confirmation() ) {
			return function_exists( 'do_shortcode' ) ? do_shortcode( '[woocommerce_my_account]' ) : $content;
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
					<?php libresign_render_lost_password_form(); ?>
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
add_action( 'template_redirect', 'libresign_render_direct_lost_password_route', 1 );
