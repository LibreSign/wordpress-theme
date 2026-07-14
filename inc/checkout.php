<?php
/**
 * Checkout and purchase flow: CPF/CNPJ field, policy terms, guest redirect
 * and guest purchase CTA replacement.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// CPF / CNPJ billing field (WooCommerce Blocks 8.7+)
// ---------------------------------------------------------------------------

/**
 * Register CPF/CNPJ as an additional checkout field.
 * The field is optional in the UI; server-side validation requires it for
 * Brazilian customers so the correct fiscal document (NFS-e) can be issued.
 */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'         => 'libresign/cpf-cnpj',
			'label'      => __( 'CPF or CNPJ', 'libresign' ),
			'location'   => 'address',
			'required'   => false,
			'type'       => 'text',
			'attributes' => array(
				'autocomplete' => 'off',
				'placeholder'  => __( 'Required for customers in Brazil', 'libresign' ),
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
			__( 'Please enter your CPF or CNPJ for tax invoice issuance.', 'libresign' )
		);
	}
}, 10, 3 );

// ---------------------------------------------------------------------------
// Checkout policy terms
// ---------------------------------------------------------------------------

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
			esc_html__( 'terms and privacy policy', 'libresign' )
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

// ---------------------------------------------------------------------------
// Guest purchase flow
// ---------------------------------------------------------------------------

/**
 * Force visitors to authenticate before accessing cart or checkout.
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
		__( 'Sign in to continue with your purchase.', 'libresign' ),
		'notice'
	);

	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/account/' );
	$redirect_to = rawurlencode( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );

	wp_safe_redirect( add_query_arg( 'redirect_to', $redirect_to, $account_url ) );
	exit;
}
add_action( 'template_redirect', 'libresign_redirect_guests_from_purchase_flow', 1 );

/**
 * Build the checkout URL for a guest CTA, pre-filling cart with the current
 * product (and its default variation if applicable).
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

	$add_to_cart_args = array( 'add-to-cart' => $product->get_id() );

	if ( $product->is_type( 'variable' ) ) {
		$default_attributes = $product->get_default_attributes();
		$selected_attributes = array();

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

/**
 * Return HTML for a guest purchase CTA button.
 */
function libresign_get_guest_purchase_cta( $label = '' ) {
	$checkout_url = libresign_get_guest_purchase_checkout_url();
	$button_label = '' !== $label ? $label : __( 'Purchase', 'libresign' );

	return sprintf(
		'<div class="libresign-guest-purchase-cta" style="display:grid;justify-items:start;margin-top:.5rem;"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
		esc_url( $checkout_url ),
		esc_html( $button_label )
	);
}

/**
 * Replace WooCommerce add-to-cart form with an account CTA for visitors who
 * are not logged in.
 *
 * Only the add-to-cart form on single product pages is replaced; the
 * product-button block in archive/shop listings is left untouched so guests
 * can still navigate to the product page to configure options before being
 * asked to sign in.
 */
function libresign_replace_guest_purchase_ctas( $block_content, $block ) {
	if ( is_admin() || is_user_logged_in() ) {
		return $block_content;
	}

	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

	if ( 'woocommerce/add-to-cart-form' === $block_name ) {
		return libresign_get_guest_purchase_cta();
	}

	return $block_content;
}
add_filter( 'render_block', 'libresign_replace_guest_purchase_ctas', 10, 2 );
