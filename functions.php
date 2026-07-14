<?php
/**
 * libresign functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package libresign
 * @since libresign 1.0
 */

/**
 * Load theme translations.
 * Polylang sets the locale from the URL before this runs, so __() resolves
 * to the correct language automatically.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'libresign', get_template_directory() . '/languages' );
} );

/**
 * Register block styles.
 */

if ( ! function_exists( 'libresign_block_styles' ) ) :
	/**
	 * Register custom block styles
	 *
	 * @since libresign 1.0
	 * @return void
	 */
	function libresign_block_styles() {

		register_block_style(
			'core/details',
			array(
				'name'         => 'arrow-icon-details',
				'label'        => __( 'Arrow icon', 'libresign' ),
				/*
				 * Styles for the custom Arrow icon style of the Details block
				 */
				'inline_style' => '
				.is-style-arrow-icon-details {
					padding-top: var(--wp--preset--spacing--10);
					padding-bottom: var(--wp--preset--spacing--10);
				}

				.is-style-arrow-icon-details summary {
					list-style-type: "\2193\00a0\00a0\00a0";
				}

				.is-style-arrow-icon-details[open]>summary {
					list-style-type: "\2192\00a0\00a0\00a0";
				}',
			)
		);
		register_block_style(
			'core/post-terms',
			array(
				'name'         => 'pill',
				'label'        => __( 'Pill', 'libresign' ),
				/*
				 * Styles variation for post terms
				 * https://github.com/WordPress/gutenberg/issues/24956
				 */
				'inline_style' => '
				.is-style-pill a,
				.is-style-pill span:not([class], [data-rich-text-placeholder]) {
					display: inline-block;
					background-color: var(--wp--preset--color--base-2);
					padding: 0.375rem 0.875rem;
					border-radius: var(--wp--preset--spacing--20);
				}

				.is-style-pill a:hover {
					background-color: var(--wp--preset--color--contrast-3);
				}',
			)
		);
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'libresign' ),
				/*
				 * Styles for the custom checkmark list block style
				 * https://github.com/WordPress/gutenberg/issues/51480
				 */
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
		register_block_style(
			'core/navigation-link',
			array(
				'name'         => 'arrow-link',
				'label'        => __( 'With arrow', 'libresign' ),
				/*
				 * Styles for the custom arrow nav link block style
				 */
				'inline_style' => '
				.is-style-arrow-link .wp-block-navigation-item__label:after {
					content: "\2197";
					padding-inline-start: 0.25rem;
					vertical-align: middle;
					text-decoration: none;
					display: inline-block;
				}',
			)
		);
		register_block_style(
			'core/heading',
			array(
				'name'         => 'asterisk',
				'label'        => __( 'With asterisk', 'libresign' ),
				'inline_style' => "
				.is-style-asterisk:before {
					content: '';
					width: 1.5rem;
					height: 3rem;
					background: var(--wp--preset--color--contrast-2, currentColor);
					clip-path: path('M11.93.684v8.039l5.633-5.633 1.216 1.23-5.66 5.66h8.04v1.737H13.2l5.701 5.701-1.23 1.23-5.742-5.742V21h-1.737v-8.094l-5.77 5.77-1.23-1.217 5.743-5.742H.842V9.98h8.162l-5.701-5.7 1.23-1.231 5.66 5.66V.684h1.737Z');
					display: block;
				}

				/* Hide the asterisk if the heading has no content, to avoid using empty headings to display the asterisk only, which is an A11Y issue */
				.is-style-asterisk:empty:before {
					content: none;
				}

				.is-style-asterisk:-moz-only-whitespace:before {
					content: none;
				}

				.is-style-asterisk.has-text-align-center:before {
					margin: 0 auto;
				}

				.is-style-asterisk.has-text-align-right:before {
					margin-left: auto;
				}

				.rtl .is-style-asterisk.has-text-align-left:before {
					margin-right: auto;
				}",
			)
		);
	}
endif;

add_action( 'init', 'libresign_block_styles' );

/**
 * Enqueue block stylesheets.
 */

if ( ! function_exists( 'libresign_block_stylesheets' ) ) :
	/**
	 * Enqueue custom block stylesheets
	 *
	 * @since libresign 1.0
	 * @return void
	 */
	function libresign_block_stylesheets() {
		/**
		 * The wp_enqueue_block_style() function allows us to enqueue a stylesheet
		 * for a specific block. These will only get loaded when the block is rendered
		 * (both in the editor and on the front end), improving performance
		 * and reducing the amount of data requested by visitors.
		 *
		 * See https://make.wordpress.org/core/2021/12/15/using-multiple-stylesheets-per-block/ for more info.
		 */
		wp_enqueue_block_style(
			'core/button',
			array(
				'handle' => 'libresign-button-style-outline',
				'src'    => get_parent_theme_file_uri( 'assets/css/button-outline.css' ),
				'ver'    => wp_get_theme( get_template() )->get( 'Version' ),
				'path'   => get_parent_theme_file_path( 'assets/css/button-outline.css' ),
			)
		);
	}
endif;

add_action( 'init', 'libresign_block_stylesheets' );

/**
 * Register pattern categories.
 */

if ( ! function_exists( 'libresign_pattern_categories' ) ) :
	/**
	 * Register pattern categories
	 *
	 * @since libresign 1.0
	 * @return void
	 */
	function libresign_pattern_categories() {

		register_block_pattern_category(
			'libresign_page',
			array(
				'label'       => _x( 'Pages', 'Block pattern category', 'libresign' ),
				'description' => __( 'A collection of full page layouts.', 'libresign' ),
			)
		);
	}
endif;

add_action( 'init', 'libresign_pattern_categories' );

require_once __DIR__ . '/inc/footer-fragment.php';
require_once __DIR__ . '/inc/github-site-webhook.php';

/**
 * Return the LibreSign logo URL used as a theme-level fallback.
 */
function libresign_get_theme_logo_url() {
	return 'https://github.com/LibreSign/site/raw/refs/heads/main/source/assets/images/logo/logo.svg';
}

/**
 * Detect whether the rendered custom logo points to a missing local upload.
 */
function libresign_custom_logo_needs_fallback( $custom_logo_html ) {
	if ( '' === trim( (string) $custom_logo_html ) ) {
		return true;
	}

	if ( ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', (string) $custom_logo_html, $matches ) ) {
		return true;
	}

	$logo_url   = html_entity_decode( $matches[1] );
	$logo_parts = wp_parse_url( $logo_url );
	$home_parts = wp_parse_url( home_url( '/' ) );

	if ( empty( $logo_parts['host'] ) || empty( $logo_parts['path'] ) ) {
		return false;
	}

	if ( empty( $home_parts['host'] ) || $logo_parts['host'] !== $home_parts['host'] ) {
		return false;
	}

	if ( 0 !== strpos( $logo_parts['path'], '/wp-content/uploads/' ) ) {
		return false;
	}

	$local_file = trailingslashit( ABSPATH ) . ltrim( $logo_parts['path'], '/' );

	return ! file_exists( $local_file );
}

/**
 * Provide a stable fallback logo when the current site logo upload is missing.
 */
function libresign_filter_custom_logo( $custom_logo_html, $blog_id ) {
	if ( ! libresign_custom_logo_needs_fallback( $custom_logo_html ) ) {
		return $custom_logo_html;
	}

	$aria_current = is_front_page() && ! is_paged() ? ' aria-current="page"' : '';
	$alt_text     = get_bloginfo( 'name' );

	return sprintf(
		'<a href="%1$s" class="custom-logo-link" rel="home"%2$s><img src="%3$s" class="custom-logo" alt="%4$s" decoding="async" fetchpriority="high" /></a>',
		esc_url( home_url( '/' ) ),
		$aria_current,
		esc_url( libresign_get_theme_logo_url() ),
		esc_attr( $alt_text )
	);
}
add_filter( 'get_custom_logo', 'libresign_filter_custom_logo', 10, 2 );

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
 * Detect a WooCommerce password-reset confirmation request (reset link, set-new-password
 * or link-sent step), which must be handled by WooCommerce, not the custom lost-password form.
 */
function libresign_is_password_reset_confirmation() {
	if ( isset( $_GET['key'] ) && ( isset( $_GET['id'] ) || isset( $_GET['login'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	return isset( $_GET['show-reset-form'] ) || isset( $_GET['reset-link-sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

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
				<p class="libresign-account-shell__eyebrow"><?php esc_html_e( 'Acesso', 'libresign' ); ?></p>
				<h2><?php esc_html_e( 'Recuperar senha', 'libresign' ); ?></h2>
				<p><?php esc_html_e( 'Informe seu e-mail ou usuário para receber o link de redefinição de senha.', 'libresign' ); ?></p>
			</div>

			<div class="libresign-account-shell__actions">
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'tab', 'register', $account_url ) ); ?>#libresign-account-shell" data-libresign-open-tab="register"><?php esc_html_e( 'Criar workspace grátis', 'libresign' ); ?></a>
				<a class="button" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Voltar para entrar', 'libresign' ); ?></a>
			</div>
		</aside>

		<main class="libresign-account-shell__main" tabindex="-1">
			<div class="libresign-account-shell__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Acesso LibreSign', 'libresign' ); ?>">
				<a class="libresign-account-shell__tab is-active" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Entrar', 'libresign' ); ?></a>
				<a class="libresign-account-shell__tab" href="<?php echo esc_url( $lost_password_url ); ?>" aria-selected="true"><?php esc_html_e( 'Esqueci a senha', 'libresign' ); ?></a>
			</div>

			<div class="libresign-account-shell__panels">
				<section class="libresign-account-shell__panel is-active" data-libresign-panel="lost-password" role="tabpanel">
					<div class="libresign-account-shell__panel-header">
						<p class="libresign-account-shell__eyebrow"><?php esc_html_e( 'Esqueci a senha', 'libresign' ); ?></p>
						<h2><?php esc_html_e( 'Enviar link de redefinição', 'libresign' ); ?></h2>
						<p><?php esc_html_e( 'Se sua conta existir, enviaremos um e-mail com o próximo passo.', 'libresign' ); ?></p>
					</div>

					<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
						<div class="libresign-account-shell__notices">
							<?php wc_print_notices(); ?>
						</div>
					<?php endif; ?>

					<form method="post" class="woocommerce-form woocommerce-form-login login lost_reset_password">
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="user_login"><?php esc_html_e( 'E-mail ou usuário', 'libresign' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obrigatório', 'libresign' ); ?></span></label>
							<input type="text" name="user_login" id="user_login" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="username" required aria-required="true" />
						</p>

						<input type="hidden" name="wc_reset_password" value="true" />
						<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

						<p class="form-row">
							<button type="submit" class="woocommerce-button button woocommerce-form-login__submit"><?php esc_html_e( 'Enviar link', 'libresign' ); ?></button>
						</p>
					</form>
				</section>
			</div>
		</main>
	</section>
	<?php
}

/**
 * Prepend the SaaS onboarding block to the key WooCommerce pages.
 *
 * This keeps the guidance close to the user journey without having to
 * maintain duplicated page content in the editor.
 */
function libresign_disable_wpautop_on_account_page( $content ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'shortcode_unautop' );
	}

	return $content;
}
add_filter( 'the_content', 'libresign_disable_wpautop_on_account_page', 0 );

function libresign_prepend_saas_onboarding_to_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() && libresign_is_lost_password_request() ) {
		// Confirmation steps need WooCommerce's set-new-password form; only the initial request uses the custom form.
		if ( libresign_is_password_reset_confirmation() ) {
			return function_exists( 'do_shortcode' ) ? do_shortcode( '[woocommerce_my_account]' ) : $content;
		}

		ob_start();
		libresign_render_lost_password_form();
		return ob_get_clean();
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
		if ( function_exists( 'do_shortcode' ) ) {
			return do_shortcode( '[woocommerce_my_account]' );
		}

		return $content;
	}

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

/**
 * Render the lost-password page directly when the route is visited outside the account page.
 */
function libresign_render_direct_lost_password_route() {
	if ( is_admin() || wp_doing_ajax() || ! libresign_is_direct_lost_password_route() ) {
		return;
	}

	// Let WooCommerce's redirect_reset_password_link() (template_redirect priority 10) handle confirmation links.
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

/**
 * Validate the custom workspace registration fields.
 */

/**
 * Validate the custom workspace registration fields.
 */
function libresign_validate_workspace_registration_fields( $errors, $username, $email ) {
	$full_name = isset( $_POST['full_name'] ) ? trim( (string) wp_unslash( $_POST['full_name'] ) ) : '';
	$password  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( '' === $username && ! empty( $email ) ) {
		$generated_username = libresign_generate_workspace_username( $email );
		if ( '' !== $generated_username ) {
			$_POST['username'] = $generated_username; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	if ( '' === $full_name ) {
		$errors->add( 'full_name_required', __( 'Informe seu nome completo.', 'libresign' ) );
	}

	if ( '' === $email ) {
		$errors->add( 'email_required', __( 'Informe um e-mail válido.', 'libresign' ) );
	}

	if ( '' === $password ) {
		$errors->add( 'password_required', __( 'Informe uma senha.', 'libresign' ) );
	}

	if ( empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			'libresign_workspace_terms',
			__( 'Você precisa aceitar os termos para criar o workspace.', 'libresign' )
		);
	}

	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'libresign_validate_workspace_registration_fields', 10, 3 );

/**
 * Generate a unique username from the registration e-mail.
 */
function libresign_generate_workspace_username( $email ) {
	$base = sanitize_user( current( explode( '@', sanitize_email( $email ) ) ), true );

	if ( '' === $base ) {
		$base = 'workspace';
	}

	$username = $base;
	$suffix   = 1;

	while ( username_exists( $username ) ) {
		$username = $base . '-' . $suffix;
		$suffix++;
	}

	return $username;
}

/**
 * Persist the custom workspace fields on customer creation.
 */
function libresign_save_workspace_registration_fields( $customer_id ) {
	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';

	if ( '' !== $full_name ) {
		$parts = preg_split( '/\s+/', $full_name );
		$first = array_shift( $parts );
		$last  = trim( implode( ' ', (array) $parts ) );

		if ( $first ) {
			update_user_meta( $customer_id, 'first_name', $first );
			update_user_meta( $customer_id, 'billing_first_name', $first );
		}

		if ( $last ) {
			update_user_meta( $customer_id, 'last_name', $last );
			update_user_meta( $customer_id, 'billing_last_name', $last );
		}

		update_user_meta( $customer_id, 'libresign_full_name', $full_name );
		update_user_meta( $customer_id, 'billing_name', $full_name );
	}

	if ( ! empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_user_meta( $customer_id, 'libresign_workspace_terms', 'yes' );
		update_user_meta( $customer_id, 'libresign_workspace_terms_date', current_time( 'mysql' ) );
	}
}
add_action( 'woocommerce_created_customer', 'libresign_save_workspace_registration_fields', 10, 1 );

/**
 * Keep the visitor-provided password for the custom workspace form.
 */
function libresign_use_custom_workspace_password( $generate_password ) {
	if ( ! empty( $_POST['password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	return $generate_password;
}
add_filter( 'woocommerce_registration_generate_password', 'libresign_use_custom_workspace_password' );

/**
 * Make sure the newly created customer ends up logged in.
 */
function libresign_authenticate_new_workspace_customer( $customer_id ) {
	wp_set_current_user( $customer_id );
	wp_set_auth_cookie( $customer_id, true );
}
add_action( 'woocommerce_created_customer', 'libresign_authenticate_new_workspace_customer', 20, 1 );

/**
 * Get the site policy URL used by the consent checkboxes.
 */
function libresign_get_policy_url() {
	$policy_page_id = 3;
	$policy_url     = get_permalink( $policy_page_id );

	if ( ! empty( $policy_url ) ) {
		return $policy_url;
	}

	return home_url( '/privacy-police/' );
}

/**
 * Render the policy consent checkbox on the WooCommerce registration form.
 */
function libresign_render_registration_policy_checkbox() {
	$policy_url = libresign_get_policy_url();
	?>
	<p class="form-row form-row-wide validate-required">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input
				type="checkbox"
				class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
				name="libresign_policy_consent"
				id="libresign_policy_consent"
				value="1"
			/>
			<span>
				<?php
				printf(
					/* translators: %s: policy page link */
					wp_kses_post( __( 'I agree to the <a href="%s" target="_blank" rel="noopener noreferrer">policies and privacy policy</a>.', 'libresign' ) ),
					esc_url( $policy_url )
				);
				?>
			</span>
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_register_form', 'libresign_render_registration_policy_checkbox', 25 );

/**
 * Require policy consent before creating an account.
 */
function libresign_validate_registration_policy_consent( $errors, $username, $email ) {
	if ( isset( $_POST['full_name'] ) || isset( $_POST['password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $errors;
	}

	if ( empty( $_POST["libresign_policy_consent"] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			"libresign_policy_consent",
			__( "Please confirm that you agree with the policies before creating an account.", "libresign" )
		);
	}

	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'libresign_validate_registration_policy_consent', 10, 3 );

/**
 * Persist the consent flag so the approval is auditable.
 */
function libresign_save_registration_policy_consent( $customer_id ) {
	if ( ! empty( $_POST['libresign_policy_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_user_meta( $customer_id, 'libresign_policy_consent', 'yes' );
		update_user_meta( $customer_id, 'libresign_policy_consent_date', current_time( 'mysql' ) );
	}
}
add_action( 'woocommerce_created_customer', 'libresign_save_registration_policy_consent' );

/**
 * Send newly registered users to their WooCommerce account area.
 */
function libresign_registration_redirect_to_account( $redirect ) {
	return libresign_get_purchase_redirect_target();
}
add_filter( 'woocommerce_registration_redirect', 'libresign_registration_redirect_to_account', 10, 1 );

/**
 * Send authenticated users to the account dashboard after login.
 */
function libresign_login_redirect_to_account( $redirect, $user ) {
	return libresign_get_purchase_redirect_target();
}
add_filter( 'woocommerce_login_redirect', 'libresign_login_redirect_to_account', 10, 2 );

/**
 * Preserve the intended destination after authentication when coming from a product page.
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

/**
 * Resolve the canonical account URL.
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
 * Register CPF/CNPJ as an additional checkout field (WooCommerce Blocks 8.7+).
 * The field is optional in the UI; server-side validation requires it for Brazil.
 */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'         => 'libresign/cpf-cnpj',
			'label'      => __( 'CPF ou CNPJ', 'libresign' ),
			'location'   => 'address',
			'required'   => false,
			'type'       => 'text',
			'attributes' => array(
				'autocomplete' => 'off',
				'placeholder'  => __( 'Obrigatório para clientes no Brasil', 'libresign' ),
			),
		)
	);
} );

/**
 * Require CPF/CNPJ only when the billing country is Brazil.
 */
add_action( 'woocommerce_validate_additional_field', function ( \WP_Error $errors, string $field_key, $field_value ) {
	if ( 'libresign/cpf-cnpj' !== $field_key ) {
		return;
	}

	$country = function_exists( 'WC' ) && WC()->customer ? WC()->customer->get_billing_country() : '';

	if ( 'BR' === $country && '' === trim( (string) $field_value ) ) {
		$errors->add(
			'libresign-cpf-cnpj-required',
			__( 'Informe seu CPF ou CNPJ para emissão da nota fiscal.', 'libresign' )
		);
	}
}, 10, 3 );

/**
 * Customize the checkout terms checkbox text to point at the policy page.
 */
function libresign_checkout_policy_checkbox_text( $text ) {
	$policy_url = libresign_get_policy_url();

	return sprintf(
		/* translators: %s: policy page link */
		__( 'I agree to the %s before placing the order.', 'libresign' ),
		sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $policy_url ),
			esc_html__( 'policies and privacy policy', 'libresign' )
		)
	);
}
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'libresign_checkout_policy_checkbox_text' );

/**
 * Make sure checkout cannot be submitted without accepting the policy terms.
 */
function libresign_validate_checkout_policy_consent( $data, $errors ) {
	if ( empty( $data['terms'] ) ) {
		$errors->add(
			'libresign_policy_consent',
			__( 'You must agree to the policies before completing the purchase.', 'libresign' )
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'libresign_validate_checkout_policy_consent', 10, 2 );

/**
 * Force visitors to authenticate before using cart or checkout.
 */
function libresign_redirect_guests_from_purchase_flow() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	$is_cart     = function_exists( 'is_cart' ) && is_cart();
	$is_checkout = function_exists( 'is_checkout' ) && is_checkout();

	if ( ! $is_cart && ! $is_checkout ) {
		return;
	}

	wc_add_notice(
		__( 'Entre para continuar com a compra.', 'libresign' ),
		'notice'
	);

	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/account/' );
	$redirect_to  = rawurlencode( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );

	wp_safe_redirect( add_query_arg( 'redirect_to', $redirect_to, $account_url ) );
	exit;
}
add_action( 'template_redirect', 'libresign_redirect_guests_from_purchase_flow', 1 );

/**
 * Build the account CTA used for guests in product purchase areas.
 */
function libresign_get_guest_purchase_checkout_url() {
	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );

	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
		return $checkout_url;
	}

	$product_id = get_queried_object_id();
	$product    = wc_get_product( $product_id );

	if ( ! $product ) {
		return $checkout_url;
	}

	$add_to_cart_args = array(
		'add-to-cart' => $product->get_id(),
	);

	if ( $product->is_type( 'variable' ) ) {
		$selected_attributes = array();
		$default_attributes  = $product->get_default_attributes();

		foreach ( $default_attributes as $attribute_name => $attribute_value ) {
			if ( '' === (string) $attribute_value ) {
				continue;
			}

			$selected_attributes[ 'attribute_' . sanitize_title( $attribute_name ) ] = (string) $attribute_value;
		}

		if ( ! empty( $selected_attributes ) ) {
			$matching_variation_id = 0;

			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );

				if ( ! $variation ) {
					continue;
				}

				$variation_attributes = $variation->get_attributes();
				$is_match             = true;

				foreach ( $default_attributes as $attribute_name => $attribute_value ) {
					if ( '' === (string) $attribute_value ) {
						continue;
					}

					$variation_key = sanitize_title( $attribute_name );

					if ( ! isset( $variation_attributes[ $variation_key ] ) || (string) $variation_attributes[ $variation_key ] !== (string) $attribute_value ) {
						$is_match = false;
						break;
					}
				}

				if ( $is_match ) {
					$matching_variation_id = $variation_id;
					break;
				}
			}

			if ( $matching_variation_id ) {
				$add_to_cart_args['variation_id'] = $matching_variation_id;
				$add_to_cart_args = array_merge( $add_to_cart_args, $selected_attributes );
			}
		}
	}

	return add_query_arg( $add_to_cart_args, $checkout_url );
}

function libresign_get_guest_purchase_cta( $label = '' ) {
	$checkout_url = libresign_get_guest_purchase_checkout_url();
	$button_label = '' !== $label ? $label : __( 'Contratar', 'libresign' );

	return sprintf(
		'<div class="libresign-guest-purchase-cta" style="display:grid;justify-items:start;margin-top:.5rem;"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
		esc_url( $checkout_url ),
		esc_html( $button_label )
	);
}

/**
 * Replace purchase CTAs with account CTA for visitors who are not logged in.
 */
function libresign_replace_guest_purchase_ctas( $block_content, $block ) {
	if ( is_admin() || is_user_logged_in() ) {
		return $block_content;
	}

	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

	if ( 'woocommerce/product-button' === $block_name ) {
		return libresign_get_guest_purchase_cta();
	}

	if ( 'woocommerce/add-to-cart-form' === $block_name ) {
		return libresign_get_guest_purchase_cta();
	}

	return $block_content;
}
add_filter( 'render_block', 'libresign_replace_guest_purchase_ctas', 10, 2 );
