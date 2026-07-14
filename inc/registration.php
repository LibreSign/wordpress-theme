<?php
/**
 * Workspace registration: field validation, username generation, customer
 * creation hooks, policy consent and post-registration redirects.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Policy URL helper (shared with checkout)
// ---------------------------------------------------------------------------

/**
 * Return the site policy / privacy page URL.
 */
function libresign_get_policy_url() {
	$policy_page_id = 3;
	$policy_url     = get_permalink( $policy_page_id );

	if ( ! empty( $policy_url ) ) {
		return $policy_url;
	}

	return home_url( '/privacy-police/' );
}

// ---------------------------------------------------------------------------
// Workspace registration fields
// ---------------------------------------------------------------------------

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
		$errors->add( 'full_name_required', __( 'Please enter your full name.', 'libresign' ) );
	}

	if ( '' === $email ) {
		$errors->add( 'email_required', __( 'Please enter a valid email address.', 'libresign' ) );
	}

	if ( '' === $password ) {
		$errors->add( 'password_required', __( 'Please enter a password.', 'libresign' ) );
	}

	if ( empty( $_POST['libresign_workspace_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			'libresign_workspace_terms',
			__( 'You must accept the terms to create your workspace.', 'libresign' )
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
 * Make sure the newly created customer ends up logged in immediately.
 */
function libresign_authenticate_new_workspace_customer( $customer_id ) {
	wp_set_current_user( $customer_id );
	wp_set_auth_cookie( $customer_id, true );
}
add_action( 'woocommerce_created_customer', 'libresign_authenticate_new_workspace_customer', 20, 1 );

// ---------------------------------------------------------------------------
// Policy consent (standard WooCommerce registration form)
// ---------------------------------------------------------------------------

/**
 * Render the policy consent checkbox on the WooCommerce registration form.
 * Used when the standard (non-workspace) registration form is rendered.
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
 * Require policy consent before creating an account (standard form only;
 * skipped when the workspace form fields are present).
 */
function libresign_validate_registration_policy_consent( $errors, $username, $email ) {
	if ( isset( $_POST['full_name'] ) || isset( $_POST['password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $errors;
	}

	if ( empty( $_POST['libresign_policy_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add(
			'libresign_policy_consent',
			__( 'Please confirm that you agree with the policies before creating an account.', 'libresign' )
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

// ---------------------------------------------------------------------------
// Post-registration / login redirects
// ---------------------------------------------------------------------------

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
