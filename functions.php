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

/**
 * Resolve the active account tab for the guest experience.
 */
function libresign_get_account_active_tab() {
	$tab = 'register';

	if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = sanitize_key( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $requested, array( 'login', 'register' ), true ) ) {
			$tab = $requested;
		}
	}

	if ( isset( $_POST['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tab = 'login';
	} elseif ( isset( $_POST['register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tab = 'register';
	}

	return $tab;
}

/**
 * Render the product grid from the store page inside the account page.
 */
function libresign_render_account_store_preview() {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="libresign-account-store-preview" id="libresign-account-shell">
		<div class="libresign-account-store-preview__header">
			<p class="libresign-account-store-preview__eyebrow">Planos disponíveis</p>
			<h2>Escolha um plano</h2>
			<p>Veja abaixo a listagem dos produtos disponíveis para contratação.</p>
		</div>

		<div class="libresign-account-store-preview__products">
			<?php echo libresign_render_account_plans_list(); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Render the account login and registration forms in Portuguese.
 */
function libresign_render_account_login_forms( $active_tab = 'register' ): void {
	$show_registration = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
	$account_url       = libresign_get_account_url();
	$lost_password_url = function_exists( 'wc_lostpassword_url' ) ? wc_lostpassword_url() : wp_lostpassword_url();
	?>
	<div class="libresign-account-shell__panels">
		<section class="libresign-account-shell__panel<?php echo 'login' === $active_tab ? ' is-active' : ''; ?>" data-libresign-panel="login" role="tabpanel">
			<div class="libresign-account-shell__panel-header">
				<p class="libresign-account-shell__eyebrow">Já tenho acesso</p>
				<h2>Entrar</h2>
				<p>Retome sua conta para gerenciar dados, métodos de pagamento e pedidos.</p>
			</div>

			<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="username">E-mail ou usuário&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" required aria-required="true" />
				</p>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="password">Senha&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>
				<p class="form-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span>Lembrar de mim</span>
					</label>
					<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
					<input type="hidden" name="redirect" value="<?php echo esc_url( $account_url ); ?>" />
					<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="Entrar">Entrar</button>
				</p>
				<p class="woocommerce-LostPassword lost_password">
					<a href="<?php echo esc_url( $lost_password_url ); ?>">Esqueceu a senha?</a>
				</p>
			</form>
		</section>

		<?php if ( $show_registration ) : ?>
			<section class="libresign-account-shell__panel<?php echo 'register' === $active_tab ? ' is-active' : ''; ?>" data-libresign-panel="register" role="tabpanel">
				<div class="libresign-account-shell__panel-header">
					<p class="libresign-account-shell__eyebrow">Comece aqui</p>
					<h2>Criar workspace</h2>
					<p>Preencha os dados do workspace e siga direto para a conta com tudo pronto.</p>
				</div>

				<form method="post" class="woocommerce-form woocommerce-form-register register">
					<input type="hidden" name="username" id="reg_username" value="" />

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_full_name">Nome completo&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="full_name" id="reg_full_name" autocomplete="name" required aria-required="true" />
					</p>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_organization">Organização&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="organization" id="reg_organization" autocomplete="organization" required aria-required="true" />
					</p>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_email">E-mail&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" required aria-required="true" />
					</p>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password">Senha&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>

					<p class="form-row form-row-wide validate-required">
						<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
							<input
								type="checkbox"
								class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
								name="libresign_workspace_terms"
								id="libresign_workspace_terms"
								value="1"
								required
							/>
							<span>
								Li e aceito os <a href="<?php echo esc_url( libresign_get_policy_url() ); ?>" target="_blank" rel="noopener noreferrer">Termos de Uso e a Política de Privacidade do LibreSign</a>, incluindo o tratamento de dados para operação do serviço.
							</span>
						</label>
					</p>

					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<input type="hidden" name="redirect" value="<?php echo esc_url( $account_url ); ?>" />

					<p class="form-row">
						<button type="submit" class="woocommerce-Button button woocommerce-button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="Criar workspace">Criar workspace</button>
					</p>
				</form>
			</section>
		<?php endif; ?>
	</div>
	<?php
}

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
				<p class="libresign-account-shell__eyebrow">Acesso</p>
				<h2>Recuperar senha</h2>
				<p>Informe seu e-mail ou usuário para receber o link de redefinição de senha.</p>
			</div>

			<div class="libresign-account-shell__actions">
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'tab', 'register', $account_url ) ); ?>#libresign-account-shell" data-libresign-open-tab="register">Criar workspace grátis</a>
				<a class="button" href="<?php echo esc_url( $account_url ); ?>">Voltar para entrar</a>
			</div>
		</aside>

		<main class="libresign-account-shell__main" tabindex="-1">
			<div class="libresign-account-shell__tabs" role="tablist" aria-label="Acesso LibreSign">
				<a class="libresign-account-shell__tab is-active" href="<?php echo esc_url( $account_url ); ?>">Entrar</a>
				<a class="libresign-account-shell__tab" href="<?php echo esc_url( $lost_password_url ); ?>" aria-selected="true">Esqueci a senha</a>
			</div>

			<div class="libresign-account-shell__panels">
				<section class="libresign-account-shell__panel is-active" data-libresign-panel="lost-password" role="tabpanel">
					<div class="libresign-account-shell__panel-header">
						<p class="libresign-account-shell__eyebrow">Esqueci a senha</p>
						<h2>Enviar link de redefinição</h2>
						<p>Se sua conta existir, enviaremos um e-mail com o próximo passo.</p>
					</div>

					<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
						<div class="libresign-account-shell__notices">
							<?php wc_print_notices(); ?>
						</div>
					<?php endif; ?>

					<form method="post" class="woocommerce-form woocommerce-form-login login lost_reset_password">
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="user_login">E-mail ou usuário&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Obrigatório</span></label>
							<input type="text" name="user_login" id="user_login" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="username" required aria-required="true" />
						</p>

						<input type="hidden" name="wc_reset_password" value="true" />
						<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

						<p class="form-row">
							<button type="submit" class="woocommerce-button button woocommerce-form-login__submit">Enviar link</button>
						</p>
					</form>
				</section>
			</div>
		</main>
	</section>
	<?php
}

/**
 * Render the active shop products on the account page.
 */
function libresign_render_account_plans_list() {
	if ( ! function_exists( 'do_shortcode' ) ) {
		return '';
	}

	$products = do_shortcode( '[products limit="3" columns="1" orderby="menu_order" order="ASC"]' );

	if ( '' === trim( (string) $products ) ) {
		return '<p class="libresign-account-store-preview__empty">Nenhum plano encontrado no momento.</p>';
	}

	return '<div class="libresign-account-store-preview__products">' . $products . '</div>';
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
		return libresign_render_account_store_preview();
	}

	$account_store_preview = '';
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

	if ( function_exists( 'is_account_page' ) && is_account_page() && false === strpos( $content, 'libresign-account-store-preview' ) ) {
		$account_store_preview = libresign_render_account_store_preview();
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$content = preg_replace( '#<p[^>]*>\\s*</p>#', '', $content );
	}

	return $account_store_preview . $content;
}
add_filter( 'the_content', 'libresign_prepend_saas_onboarding_to_content', 5 );

/**
 * Render the lost-password page directly when the route is visited outside the account page.
 */
function libresign_render_direct_lost_password_route() {
	if ( is_admin() || wp_doing_ajax() || ! libresign_is_direct_lost_password_route() ) {
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
 * Print account-page-specific styles inline so cache and specificity do not hide them.
 */
function libresign_account_store_preview_head_styles() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}

	?>
	<style id="libresign-account-store-preview-inline-css">
		.woocommerce-account .wp-block-post-title {
			margin-bottom: 1rem;
			font-size: clamp(1.9rem, 3.2vw, 3rem);
			font-weight: 800;
			letter-spacing: -0.04em;
			line-height: 1;
			color: #0f172a;
			text-wrap: balance;
		}

		.libresign-account-store-preview {
			position: relative;
			overflow: hidden;
		}

		.libresign-account-store-preview::before {
			content: "";
			position: absolute;
			inset: 0;
			background: linear-gradient(135deg, rgba(255,255,255,0.7), rgba(255,255,255,0));
			pointer-events: none;
		}

		.libresign-account-store-preview__header {
			position: relative;
			z-index: 1;
		}

		.libresign-account-store-preview__eyebrow {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			padding: 0.35rem 0.65rem;
			border-radius: 999px;
			background: rgba(15, 118, 110, 0.08);
			color: #0f766e;
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
		}

		.libresign-account-store-preview__header h2 {
			max-width: 14ch;
			margin: 0.4rem 0 0.65rem;
			font-size: clamp(1.65rem, 3vw, 2.55rem);
			line-height: 1.05;
		}

		.libresign-account-store-preview__header p {
			max-width: 58rem;
			color: #334155;
			font-size: 0.98rem;
			line-height: 1.55;
		}

		.libresign-account-store-preview__actions {
			display: flex;
			flex-wrap: wrap;
			gap: 0.85rem;
			margin-top: 1.5rem;
			position: relative;
			z-index: 1;
		}

		.libresign-account-store-preview__actions .button {
			border-radius: 999px;
			padding: 0.72rem 1rem;
			font-size: 0.92rem;
			font-weight: 700;
			letter-spacing: -0.01em;
			box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
		}

		.libresign-account-store-preview__actions .button-primary {
			background: #0f766e;
			border-color: #0f766e;
			color: #fff;
		}

		.libresign-account-store-preview__actions .button-primary:hover,
		.libresign-account-store-preview__actions .button-primary:focus {
			background: #115e59;
			border-color: #115e59;
			color: #fff;
		}

		.libresign-account-store-preview__note {
			margin: 0.85rem 0 0;
			color: #475569;
			font-size: 0.88rem;
			line-height: 1.55;
			position: relative;
			z-index: 1;
		}

		.libresign-account-store-preview__plans {
			margin-top: 1.5rem;
			padding: 1rem;
			border-radius: 18px;
			background: rgba(255, 255, 255, 0.68);
			border: 1px solid rgba(15, 23, 42, 0.06);
			position: relative;
			z-index: 1;
		}

		.libresign-account-store-preview__plans-header h3 {
			margin: 0.25rem 0 0.4rem;
			font-size: clamp(1rem, 1.5vw, 1.15rem);
			font-weight: 800;
			letter-spacing: -0.01em;
			color: #0f172a;
		}

		.libresign-account-store-preview__plans-header p {
			margin: 0;
			max-width: 58rem;
			color: #475569;
			font-size: 0.9rem;
			line-height: 1.5;
		}

		.libresign-account-store-preview__products {
			margin-top: 0.9rem;
		}

		.libresign-account-login {
			margin-top: 1.5rem;
			padding: 1rem 1rem 0.35rem;
			border-radius: 18px;
			background: rgba(255, 255, 255, 0.7);
			border: 1px solid rgba(15, 23, 42, 0.06);
			scroll-margin-top: 2rem;
			position: relative;
			z-index: 1;
		}

		.libresign-account-login__eyebrow {
			margin: 0 0 0.25rem;
			font-size: 0.68rem;
			font-weight: 700;
			letter-spacing: 0.1em;
			text-transform: uppercase;
			color: #0f766e;
		}

		.libresign-account-login h3 {
			margin: 0 0 0.8rem;
			font-size: clamp(1rem, 1.8vw, 1.25rem);
			font-weight: 800;
			letter-spacing: -0.01em;
			color: #0f172a;
		}

		.libresign-account-login__helper {
			margin: -0.2rem 0 0.9rem;
			max-width: 38rem;
			color: #475569;
			font-size: 0.88rem;
			line-height: 1.5;
		}

		.libresign-account-login .woocommerce-form-login,
		.libresign-account-login .woocommerce-form-register {
			margin-bottom: 0;
		}

		.libresign-account-login .woocommerce-form-register .form-row,
		.libresign-account-login .woocommerce-form-login .form-row {
			margin-bottom: 0.75rem;
		}

		.libresign-account-login .woocommerce-form-register label,
		.libresign-account-login .woocommerce-form-login label {
			display: inline-block;
			margin-bottom: 0.25rem;
			font-size: 0.84rem;
			font-weight: 600;
			color: #0f172a;
		}

		.libresign-account-login .woocommerce-form-register .input-text,
		.libresign-account-login .woocommerce-form-login .input-text {
			border-radius: 12px;
			border-color: rgba(15, 23, 42, 0.12);
			background: rgba(255, 255, 255, 0.9);
			box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
			min-height: 2.6rem;
			font-size: 0.94rem;
		}

		.libresign-account-login .woocommerce-form-register .input-text:focus,
		.libresign-account-login .woocommerce-form-login .input-text:focus {
			border-color: #0f766e;
			box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
		}

		.libresign-account-login .woocommerce-form-register .checkbox {
			align-items: flex-start;
			line-height: 1.45;
			color: #334155;
			font-size: 0.86rem;
		}

		.libresign-account-login .woocommerce-form-register .checkbox a {
			color: #0f766e;
			font-weight: 700;
			text-decoration-thickness: 0.1em;
			text-underline-offset: 0.18em;
		}

		.libresign-account-login .woocommerce-form-register .woocommerce-form-register__submit,
		.libresign-account-login .woocommerce-form-login .woocommerce-form-login__submit {
			border-radius: 999px;
			padding-inline: 1.15rem;
			min-height: 2.6rem;
			font-size: 0.92rem;
			font-weight: 800;
			letter-spacing: -0.01em;
		}

		.libresign-account-store-preview .woocommerce ul.products {
			gap: 0.5rem;
		}

		.libresign-account-store-preview .woocommerce ul.products li.product {
			padding: 0.6rem;
			border-radius: 14px;
			background: rgba(255, 255, 255, 0.72);
			backdrop-filter: blur(6px);
			box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
		}

		.libresign-account-store-preview .woocommerce ul.products li.product img {
			max-height: 72px;
			width: auto;
			object-fit: contain;
			margin: 0 auto 0.45rem;
		}

		.libresign-account-store-preview .woocommerce ul.products li.product .woocommerce-loop-product__title,
		.libresign-account-store-preview .woocommerce ul.products li.product h2 {
			font-size: 0.88rem;
			line-height: 1.3;
			margin-bottom: 0.25rem;
		}

		.libresign-account-store-preview .woocommerce ul.products li.product .price {
			font-size: 0.84rem;
			margin-bottom: 0.35rem;
		}

		.libresign-account-store-preview .woocommerce ul.products li.product .button {
			width: 100%;
			margin-top: 0.4rem;
			border-radius: 999px;
			padding: 0.5rem 0.75rem;
			font-size: 0.8rem;
		}

		.libresign-account-store-preview__empty {
			margin: 0.75rem 0 0;
			color: #64748b;
			font-size: 0.88rem;
		}

		.libresign-account-shell {
			display: grid;
			grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.05fr);
			min-height: 640px;
			margin: 2rem 0 3rem;
			border: 1px solid rgba(17, 24, 39, 0.08);
			border-radius: 30px;
			overflow: hidden;
			background: var(--wp--preset--color--base-2, #fff);
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
		}

		.libresign-account-shell__aside {
			position: relative;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			min-height: 100%;
			height: 100%;
			gap: 1.5rem;
			padding: clamp(1.5rem, 3vw, 2.5rem);
			background:
				radial-gradient(circle at top right, rgba(181, 189, 188, 0.18), transparent 24%),
				radial-gradient(circle at 20% 20%, rgba(178, 197, 164, 0.16), transparent 22%),
				linear-gradient(180deg, #111827 0%, #0f172a 100%);
			color: var(--wp--preset--color--base-2, #fff);
		}

		.libresign-account-shell__aside::after {
			content: "";
			position: absolute;
			inset: 0;
			background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0));
			pointer-events: none;
		}

		.libresign-account-shell__brand,
		.libresign-account-shell__hero,
		.libresign-account-shell__plans,
		.libresign-account-shell__trust,
		.libresign-account-shell__actions {
			position: relative;
			z-index: 1;
		}

		.libresign-account-shell__brand {
			display: flex;
			align-items: center;
			gap: 0.8rem;
		}

		.libresign-account-shell__mark {
			width: 3rem;
			height: 3rem;
			display: grid;
			place-items: center;
			border-radius: 0.9rem;
			background: var(--wp--preset--color--accent-4, #b1c5a4);
			color: #111827;
			font-weight: 800;
			font-size: 1rem;
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
		}

		.libresign-account-shell__brand-name {
			margin: 0;
			font-size: 1rem;
			font-weight: 800;
			letter-spacing: -0.02em;
		}

		.libresign-account-shell__brand-subtitle {
			margin: 0.15rem 0 0;
			font-size: 0.82rem;
			color: rgba(249, 249, 249, 0.68);
		}

		.libresign-account-shell__eyebrow,
		.libresign-account-shell__section-label {
			margin: 0 0 0.55rem;
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: var(--wp--preset--color--accent-4, #b1c5a4);
		}

		.libresign-account-shell__hero h2 {
			margin: 0 0 0.75rem;
			font-size: clamp(2rem, 3.5vw, 3.4rem);
			line-height: 1.03;
			letter-spacing: -0.05em;
			color: var(--wp--preset--color--base-2, #fff);
		}

		.libresign-account-shell__hero {
			max-width: 30rem;
			margin-bottom: auto;
		}

		.libresign-account-shell__hero p {
			margin: 0;
			max-width: 34rem;
			font-size: 0.98rem;
			line-height: 1.65;
			color: rgba(249, 249, 249, 0.72);
		}

		.libresign-account-shell__plans {
			display: grid;
			gap: 0.75rem;
			margin-top: 0;
		}

		.libresign-account-shell__plans-list .woocommerce ul.products {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.75rem;
			margin-bottom: 0;
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product {
			width: 100%;
			margin: 0;
			padding: 0.95rem;
			border-radius: 18px;
			background: rgba(255, 255, 255, 0.06);
			border: 1px solid rgba(255, 255, 255, 0.12);
			box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
			backdrop-filter: blur(10px);
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product a {
			color: inherit;
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product img {
			max-height: 72px;
			width: auto;
			margin: 0 auto 0.55rem;
			object-fit: contain;
			opacity: 0.95;
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product .woocommerce-loop-product__title,
		.libresign-account-shell__plans-list .woocommerce ul.products li.product h2 {
			margin: 0 0 0.35rem;
			font-size: 1rem;
			line-height: 1.3;
			letter-spacing: -0.02em;
			color: var(--wp--preset--color--base-2, #fff);
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product .price {
			margin: 0 0 0.55rem;
			font-size: 0.88rem;
			font-weight: 700;
			color: var(--wp--preset--color--accent-4, #b1c5a4);
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product .button {
			width: 100%;
			margin-top: 0.35rem;
			border-radius: 999px;
			padding: 0.62rem 0.9rem;
			font-size: 0.84rem;
			font-weight: 700;
			background: rgba(255, 255, 255, 0.94);
			color: #111827;
			border-color: transparent;
		}

		.libresign-account-shell__plans-list .woocommerce ul.products li.product .button:hover,
		.libresign-account-shell__plans-list .woocommerce ul.products li.product .button:focus {
			background: var(--wp--preset--color--accent-4, #b1c5a4);
			color: #111827;
		}

		.libresign-account-shell__trust {
			display: flex;
			align-items: center;
			gap: 0.55rem;
			margin-top: 0;
			font-size: 0.84rem;
			color: rgba(249, 249, 249, 0.66);
		}

		.libresign-account-shell__trust-dot {
			width: 0.45rem;
			height: 0.45rem;
			border-radius: 999px;
			background: var(--wp--preset--color--accent-4, #b1c5a4);
			box-shadow: 0 0 0 4px rgba(177, 197, 164, 0.14);
		}

		.libresign-account-shell__actions {
			display: flex;
			flex-wrap: wrap;
			gap: 0.75rem;
			margin-top: 0;
		}

		.libresign-account-shell__actions .button {
			border-radius: 999px;
			padding: 0.85rem 1.15rem;
			font-size: 0.9rem;
			font-weight: 700;
		}

		.libresign-account-shell__actions .button-primary {
			background: var(--wp--preset--color--accent-4, #b1c5a4);
			border-color: var(--wp--preset--color--accent-4, #b1c5a4);
			color: #111827;
		}

		.libresign-account-shell__actions .button-primary:hover,
		.libresign-account-shell__actions .button-primary:focus {
			background: var(--wp--preset--color--base-2, #fff);
			border-color: var(--wp--preset--color--base-2, #fff);
			color: #111827;
		}

		.libresign-account-shell__main {
			padding: clamp(1.5rem, 3vw, 2.5rem);
			background:
				radial-gradient(circle at top right, rgba(193, 169, 144, 0.1), transparent 22%),
				linear-gradient(180deg, var(--wp--preset--color--base-2, #fff), var(--wp--preset--color--base, #f9f9f9));
		}

		.libresign-account-shell__tabs {
			display: inline-flex;
			flex-wrap: wrap;
			gap: 0.35rem;
			margin-bottom: 1.15rem;
			padding: 0.3rem;
			border-radius: 999px;
			background: rgba(17, 24, 39, 0.04);
		}

		.libresign-account-shell__tab {
			border: 0;
			background: transparent;
			color: var(--wp--preset--color--contrast-2, #636363);
			font-size: 0.88rem;
			font-weight: 700;
			padding: 0.7rem 1rem;
			border-radius: 999px;
			cursor: pointer;
			transition: background 0.15s ease, color 0.15s ease;
		}

		.libresign-account-shell__tab.is-active {
			background: var(--wp--preset--color--contrast, #111111);
			color: var(--wp--preset--color--base-2, #fff);
			box-shadow: 0 10px 22px rgba(17, 17, 17, 0.16);
		}

		.libresign-account-shell__notices {
			margin-bottom: 1rem;
		}

		.libresign-account-shell__notices .woocommerce-error,
		.libresign-account-shell__notices .woocommerce-message,
		.libresign-account-shell__notices .woocommerce-info {
			margin: 0 0 0.75rem;
			border-radius: 16px;
		}

		.libresign-account-shell__panels {
			display: grid;
			gap: 1rem;
		}

		.libresign-account-shell__panel {
			display: none;
			padding: 1.25rem;
			border: 1px solid rgba(17, 24, 39, 0.08);
			border-radius: 22px;
			background: rgba(255, 255, 255, 0.8);
			box-shadow: 0 18px 32px rgba(15, 23, 42, 0.05);
		}

		.libresign-account-shell__panel.is-active {
			display: block;
		}

		.libresign-account-shell__panel-header {
			margin-bottom: 1rem;
		}

		.libresign-account-shell__panel-header h2 {
			margin: 0 0 0.35rem;
			font-size: clamp(1.25rem, 2vw, 1.75rem);
			letter-spacing: -0.03em;
			line-height: 1.15;
			color: var(--wp--preset--color--contrast, #111111);
		}

		.libresign-account-shell__panel-header p {
			margin: 0;
			max-width: 34rem;
			color: var(--wp--preset--color--contrast-2, #636363);
			font-size: 0.94rem;
			line-height: 1.6;
		}

		.libresign-account-shell .woocommerce-form-login,
		.libresign-account-shell .woocommerce-form-register {
			margin-bottom: 0;
		}

		.libresign-account-shell .woocommerce-form-register .form-row,
		.libresign-account-shell .woocommerce-form-login .form-row {
			margin-bottom: 0.85rem;
		}

		.libresign-account-shell .woocommerce-form-register label,
		.libresign-account-shell .woocommerce-form-login label {
			display: inline-block;
			margin-bottom: 0.3rem;
			font-size: 0.86rem;
			font-weight: 600;
			color: var(--wp--preset--color--contrast, #111111);
		}

		.libresign-account-shell .woocommerce-form-register .input-text,
		.libresign-account-shell .woocommerce-form-login .input-text {
			min-height: 2.7rem;
			border-radius: 14px;
			border-color: rgba(17, 24, 39, 0.12);
			background: rgba(249, 249, 249, 0.9);
			box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
		}

		.libresign-account-shell .woocommerce-form-register .input-text:focus,
		.libresign-account-shell .woocommerce-form-login .input-text:focus {
			border-color: var(--wp--preset--color--accent-4, #b1c5a4);
			box-shadow: 0 0 0 3px rgba(177, 197, 164, 0.16);
		}

		.libresign-account-shell .woocommerce-form-register .checkbox {
			align-items: flex-start;
			gap: 0.55rem;
			line-height: 1.5;
			color: var(--wp--preset--color--contrast-2, #636363);
			font-size: 0.88rem;
		}

		.libresign-account-shell .woocommerce-form-register .checkbox a {
			color: var(--wp--preset--color--contrast, #111111);
			font-weight: 700;
			text-decoration-thickness: 0.1em;
			text-underline-offset: 0.18em;
		}

		.libresign-account-shell .woocommerce-form-register .woocommerce-form-register__submit,
		.libresign-account-shell .woocommerce-form-login .woocommerce-form-login__submit {
			border-radius: 999px;
			padding-inline: 1.2rem;
			min-height: 2.8rem;
			font-size: 0.92rem;
			font-weight: 800;
			letter-spacing: -0.01em;
		}

		.libresign-account-shell .woocommerce-LostPassword {
			margin: 0.2rem 0 0;
		}

		.libresign-account-shell .woocommerce-LostPassword a {
			color: var(--wp--preset--color--contrast-2, #636363);
		}

		@media (max-width: 1024px) {
			.libresign-account-shell {
				grid-template-columns: 1fr;
			}

			.libresign-account-shell__trust {
				margin-top: 0;
			}
		}

		@media (max-width: 768px) {
			.libresign-account-shell__main {
				padding: 1.1rem;
			}

			.libresign-account-shell__aside {
				padding: 1.1rem;
			}

			.libresign-account-shell__panel {
				padding: 1rem;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'libresign_account_store_preview_head_styles', 99 );

/**
 * Reveal the hidden login and registration area when the CTA is clicked.
 */
function libresign_account_store_preview_toggle_script() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	?>
	<script>
		(function () {
			const shell = document.querySelector('.libresign-account-shell');

			if (!shell) {
				return;
			}

			const tabs = Array.from(shell.querySelectorAll('[data-libresign-tab]'));
			const panels = Array.from(shell.querySelectorAll('[data-libresign-panel]'));
			const openers = Array.from(shell.querySelectorAll('[data-libresign-open-tab]'));
			const focusPanel = function (panel) {
				const firstField = panel.querySelector('input, button, select, textarea, a[href]');
				if (firstField) {
					firstField.focus({ preventScroll: true });
				}
			};

			const activateTab = function (tabName, shouldFocus) {
				tabs.forEach(function (tab) {
					const isActive = tab.dataset.libresignTab === tabName;
					tab.classList.toggle('is-active', isActive);
					tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
				});

				panels.forEach(function (panel) {
					const isActive = panel.dataset.libresignPanel === tabName;
					panel.classList.toggle('is-active', isActive);
					panel.hidden = !isActive;
				});

				if (shouldFocus) {
					const targetPanel = panels.find(function (panel) {
						return panel.dataset.libresignPanel === tabName;
					});
					if (targetPanel) {
						focusPanel(targetPanel);
					}
				}
			};

			tabs.forEach(function (tab) {
				tab.addEventListener('click', function () {
					activateTab(tab.dataset.libresignTab, true);
				});
			});

			openers.forEach(function (opener) {
				opener.addEventListener('click', function (event) {
					const tabName = opener.dataset.libresignOpenTab || 'register';
					const targetPanel = shell.querySelector('[data-libresign-panel="' + tabName + '"]');
					if (event) {
						event.preventDefault();
					}

					if (!targetPanel) {
						window.location.href = opener.href;
						return;
					}

					activateTab(tabName, false);
					shell.scrollIntoView({ behavior: 'smooth', block: 'start' });
					focusPanel(targetPanel);
				});
			});

			if (window.location.hash === '#libresign-account-shell') {
				shell.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		})();
	</script>
	<?php
}
add_action( 'wp_footer', 'libresign_account_store_preview_toggle_script', 99 );

/**
 * Validate the custom workspace registration fields.
 */
function libresign_validate_workspace_registration_fields( $errors, $username, $email ) {
	$full_name    = isset( $_POST['full_name'] ) ? trim( (string) wp_unslash( $_POST['full_name'] ) ) : '';
	$organization = isset( $_POST['organization'] ) ? trim( (string) wp_unslash( $_POST['organization'] ) ) : '';
	$password     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( '' === $username && ! empty( $email ) ) {
		$generated_username = libresign_generate_workspace_username( $email );
		if ( '' !== $generated_username ) {
			$_POST['username'] = $generated_username; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	if ( '' === $full_name ) {
		$errors->add( 'full_name_required', 'Informe seu nome completo.' );
	}

	if ( '' === $organization ) {
		$errors->add( 'organization_required', 'Informe sua organização.' );
	}

	if ( '' === $email ) {
		$errors->add( 'email_required', 'Informe um e-mail válido.' );
	}

	if ( '' === $password ) {
		$errors->add( 'password_required', 'Informe uma senha.' );
	}

	if ( empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			'libresign_workspace_terms',
			'Você precisa aceitar os termos para criar o workspace.'
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
	$full_name    = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$organization = isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '';

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

	if ( '' !== $organization ) {
		update_user_meta( $customer_id, 'billing_company', $organization );
		update_user_meta( $customer_id, 'libresign_organization', $organization );
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
	if ( isset( $_POST["full_name"] ) || isset( $_POST["organization"] ) || isset( $_POST["password"] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
	return libresign_get_account_url();
}
add_filter( 'woocommerce_registration_redirect', 'libresign_registration_redirect_to_account', 10, 1 );

/**
 * Send authenticated users to the account dashboard after login.
 */
function libresign_login_redirect_to_account( $redirect, $user ) {
	return libresign_get_account_url();
}
add_filter( 'woocommerce_login_redirect', 'libresign_login_redirect_to_account', 10, 2 );

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
 * Translate the WooCommerce account navigation into Portuguese.
 */
function libresign_translate_account_menu_items( $items ) {
	$translations = array(
		'dashboard'       => 'Painel',
		'orders'          => 'Pedidos',
		'downloads'       => 'Downloads',
		'edit-address'    => 'Endereços',
		'payment-methods'  => 'Métodos de pagamento',
		'edit-account'    => 'Dados da conta',
		'subscriptions'   => 'Assinaturas',
		'customer-logout' => 'Sair',
	);

	foreach ( $items as $key => $label ) {
		if ( isset( $translations[ $key ] ) ) {
			$items[ $key ] = $translations[ $key ];
		}
	}

	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'libresign_translate_account_menu_items' );

/**
 * Translate WooCommerce account dashboard copy when the account page is loaded.
 */
function libresign_translate_account_gettext( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return $translated;
	}

	$replacements = array(
		'Dashboard' => 'Painel',
		'Orders' => 'Pedidos',
		'Payment methods' => 'Métodos de pagamento',
		'Account details' => 'Dados da conta',
		'Addresses' => 'Endereços',
		'Logout' => 'Sair',
		'From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.' =>
			'No painel da sua conta, você pode ver seus pedidos recentes, gerenciar seus endereços de cobrança e entrega e editar sua senha e os detalhes da conta.',
	);

	if ( isset( $replacements[ $text ] ) ) {
		return $replacements[ $text ];
	}

	return $translated;
}
add_filter( 'gettext', 'libresign_translate_account_gettext', 20, 3 );

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
	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/account/' );
	$redirect_to  = rawurlencode( libresign_get_guest_purchase_checkout_url() );
	$button_label = '' !== $label ? $label : __( 'Entrar para assinar', 'libresign' );
	$notice       = __( 'Crie sua conta ou entre para continuar com a compra.', 'libresign' );

	return sprintf(
		'<div class="libresign-guest-purchase-cta" style="display:grid;gap:.75rem;justify-items:start;margin-top:.5rem;"><p style="margin:0;">%s</p><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
		esc_html( $notice ),
		esc_url( add_query_arg( 'redirect_to', $redirect_to, $account_url ) ),
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
