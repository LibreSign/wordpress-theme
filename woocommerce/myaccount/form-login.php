<?php
/**
 * Login Form — LibreSign override
 *
 * Overrides woocommerce/templates/myaccount/form-login.php.
 *
 * The registration column follows the cart: account creation while a plan is in
 * the cart, a call to action to the plans page otherwise.
 *
 * Standard WooCommerce strings reuse the 'woocommerce' text domain so they are
 * covered by WooCommerce's own translation files; LibreSign-specific strings use
 * the 'libresign' text domain.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_customer_login_form' );

$purchase_in_progress = function_exists( 'libresign_cart_has_items' ) && libresign_cart_has_items();
$show_registration    = $purchase_in_progress && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$redirect_to          = function_exists( 'libresign_get_purchase_redirect_target' ) ? libresign_get_purchase_redirect_target() : '';
$policy_url           = function_exists( 'libresign_get_policy_url' ) ? libresign_get_policy_url() : home_url( '/privacy-policy/' );
$lost_password_url    = function_exists( 'wc_lostpassword_url' ) ? wc_lostpassword_url() : wp_lostpassword_url();
$plans_url            = function_exists( 'libresign_get_plans_url' ) ? libresign_get_plans_url() : '';
$show_plans_cta       = ! $purchase_in_progress && '' !== $plans_url;
$two_columns          = $show_registration || $show_plans_cta;
?>

<?php if ( $two_columns ) : ?>
<div class="u-columns col2-set" id="customer_login">
	<div class="u-column1 col-1">
<?php endif; ?>

		<h2><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username">
					<?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
				</label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /> <?php // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>
			</p>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password">
					<?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
				</label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<?php if ( $redirect_to ) : ?>
					<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_to ); ?>" />
				<?php endif; ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
			</p>

			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( $lost_password_url ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

<?php if ( $two_columns ) : ?>
	</div>

	<div class="u-column2 col-2">

<?php endif; ?>

<?php if ( $show_registration ) : ?>

		<h2><?php esc_html_e( 'Create your workspace', 'libresign' ); ?></h2>

		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<p class="libresign-workspace-intro">
				<?php
				if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
					esc_html_e( 'Set the email and password of your workspace to continue to checkout.', 'libresign' );
				} else {
					esc_html_e( 'Set the email of your workspace to continue to checkout.', 'libresign' );
				}
				?>
			</p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username">
						<?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
					</label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /> <?php // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>
				</p>

			<?php endif; ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email">
					<?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
				</label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /> <?php // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>
			</p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password">
						<?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
					</label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
				</p>

			<?php else : ?>

				<p><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>

			<?php endif; ?>

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
						<?php
						printf(
							/* translators: %s: URL of the terms and privacy policy page */
							wp_kses_post( __( 'I have read and agree to the <a href="%s" target="_blank" rel="noopener noreferrer">Terms of Use and Privacy Policy of LibreSign</a>, including the processing of data for service operation.', 'libresign' ) ),
							esc_url( $policy_url )
						);
						?>
					</span>
				</label>
			</p>

			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<?php if ( $redirect_to ) : ?>
				<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_to ); ?>" />
			<?php endif; ?>

			<p class="woocommerce-form-row form-row">
				<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Continue to checkout', 'libresign' ); ?></button>
			</p>

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>

<?php elseif ( $show_plans_cta ) : ?>

		<h2><?php esc_html_e( 'New to LibreSign?', 'libresign' ); ?></h2>

		<div class="libresign-plans-cta">
			<p><?php esc_html_e( 'Your workspace is created together with your subscription. Choose a plan to get started.', 'libresign' ); ?></p>
			<p>
				<a class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( $plans_url ); ?>"><?php esc_html_e( 'Choose a plan', 'libresign' ); ?></a>
			</p>
		</div>

<?php endif; ?>

<?php if ( $two_columns ) : ?>
	</div>

</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
