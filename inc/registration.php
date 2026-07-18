<?php
/**
 * Workspace creation: the plan-selection form only fills the cart and forwards
 * to checkout. The customer account is created by WooCommerce at checkout (with
 * Subscriptions requiring an account for subscription carts), so an abandoned
 * checkout never leaves an account behind.
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
// Workspace creation form: add plan to cart and forward to checkout
// ---------------------------------------------------------------------------

/**
 * Handle the "create workspace" form submission.
 *
 * The account is intentionally NOT created here: the form only validates the
 * plan choice, adds the chosen plan to the cart and forwards the visitor to
 * checkout, where WooCommerce creates the account when the order is placed.
 */
function libresign_handle_create_workspace_submission() {
	if ( empty( $_POST['libresign_create_workspace'] ) ) {
		return;
	}

	if ( ! isset( $_POST['libresign_workspace_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['libresign_workspace_nonce'] ) ), 'libresign_create_workspace' ) ) {
		return;
	}

	if ( is_null( WC()->cart ) || is_null( WC()->session ) ) {
		return;
	}

	$plans   = libresign_get_available_plans();
	$plan_id = isset( $_POST['libresign_plan'] ) ? absint( wp_unslash( $_POST['libresign_plan'] ) ) : 0;
	$plan    = $plan_id ? wc_get_product( $plan_id ) : null;
	$term    = isset( $_POST['libresign_plan_term'] ) ? sanitize_key( wp_unslash( $_POST['libresign_plan_term'] ) ) : '';

	$has_plans     = ! empty( $plans );
	$is_valid_plan = libresign_is_plan_product( $plan ) && $plan->is_purchasable();
	$variation_id  = 0;

	if ( $has_plans && ! $is_valid_plan ) {
		wc_add_notice( __( 'You must choose a subscription plan before continuing.', 'libresign' ), 'error' );
	} elseif ( $is_valid_plan && 'variable-subscription' === $plan->get_type() ) {
		$variation_id = libresign_resolve_plan_variation_id( $plan, $term );
		if ( ! $variation_id ) {
			wc_add_notice( __( 'Please choose a billing period for the selected plan.', 'libresign' ), 'error' );
		}
	}

	if ( empty( $_POST['libresign_workspace_terms'] ) ) {
		wc_add_notice( __( 'You must accept the terms to create your workspace.', 'libresign' ), 'error' );
	}

	if ( wc_notice_count( 'error' ) > 0 ) {
		return; // Re-render the form with the validation notices.
	}

	WC()->cart->empty_cart();

	if ( $variation_id ) {
		WC()->cart->add_to_cart(
			$plan_id,
			1,
			$variation_id,
			array( 'attribute_' . LIBRESIGN_PLAN_TERM_ATTRIBUTE => $term )
		);
	} elseif ( $is_valid_plan ) {
		WC()->cart->add_to_cart( $plan_id );
	}

	// Remember the consent so it can be persisted once the account is created at
	// checkout.
	WC()->session->set( 'libresign_workspace_terms', 'yes' );

	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}
add_action( 'template_redirect', 'libresign_handle_create_workspace_submission' );

/**
 * Persist the workspace terms consent captured on the plan form once the account
 * is created at checkout.
 *
 * @param int $customer_id New customer ID.
 */
function libresign_persist_workspace_consent_from_session( $customer_id ) {
	if ( is_null( WC()->session ) ) {
		return;
	}

	if ( 'yes' !== WC()->session->get( 'libresign_workspace_terms' ) ) {
		return;
	}

	update_user_meta( $customer_id, 'libresign_workspace_terms', 'yes' );
	update_user_meta( $customer_id, 'libresign_workspace_terms_date', current_time( 'mysql' ) );

	WC()->session->__unset( 'libresign_workspace_terms' );
}
add_action( 'woocommerce_created_customer', 'libresign_persist_workspace_consent_from_session', 10, 1 );

// ---------------------------------------------------------------------------
// Login redirect
// ---------------------------------------------------------------------------

/**
 * Send authenticated users to the account dashboard after login.
 */
function libresign_login_redirect_to_account( $redirect, $user ) {
	return libresign_get_purchase_redirect_target();
}
add_filter( 'woocommerce_login_redirect', 'libresign_login_redirect_to_account', 10, 2 );
