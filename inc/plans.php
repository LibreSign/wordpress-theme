<?php
/**
 * Subscription plans: discovery, variation resolution and the plan selector
 * rendered on the workspace registration form.
 *
 * A workspace can only be created after a plan is chosen (see the validation in
 * inc/registration.php), so the registration form always offers the available
 * subscription plans and the billing period for the current language.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variation attribute (custom, non-taxonomy) that stores the billing period.
 * WooCommerce exposes it on variations as 'attribute_term-length'.
 */
const LIBRESIGN_PLAN_TERM_ATTRIBUTE = 'term-length';

/**
 * Product types considered subscription "plans".
 *
 * @return string[]
 */
function libresign_plan_product_types() {
	/**
	 * Filter the WooCommerce product types treated as subscription plans.
	 *
	 * @param string[] $types Product type slugs.
	 */
	return (array) apply_filters(
		'libresign_plan_product_types',
		array( 'subscription', 'variable-subscription' )
	);
}

/**
 * Whether a product is a subscription plan the workspace can be created against.
 *
 * @param WC_Product|null $product Product to test.
 * @return bool
 */
function libresign_is_plan_product( $product ) {
	return $product instanceof WC_Product
		&& in_array( $product->get_type(), libresign_plan_product_types(), true );
}

/**
 * Return the purchasable subscription plans available for the current request.
 *
 * On the frontend Polylang scopes the query to the current language, so callers
 * receive the plans in the visitor's language.
 *
 * @return WC_Product[]
 */
function libresign_get_available_plans() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => -1,
			'type'    => libresign_plan_product_types(),
			'orderby' => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);

	$plans = array();
	foreach ( $products as $product ) {
		if ( libresign_is_plan_product( $product ) && $product->is_purchasable() ) {
			$plans[] = $product;
		}
	}

	return $plans;
}

/**
 * Resolve the variation matching a billing period for a variable subscription.
 *
 * @param WC_Product $product Variable subscription product.
 * @param string     $term    Billing period (e.g. 'monthly', 'yearly').
 * @return int Variation ID, or 0 when no matching purchasable variation exists.
 */
function libresign_resolve_plan_variation_id( $product, $term ) {
	if ( ! libresign_is_plan_product( $product ) || 'variable-subscription' !== $product->get_type() ) {
		return 0;
	}

	if ( '' === $term ) {
		return 0;
	}

	$data_store   = WC_Data_Store::load( 'product' );
	$variation_id = $data_store->find_matching_product_variation(
		$product,
		array( 'attribute_' . LIBRESIGN_PLAN_TERM_ATTRIBUTE => $term )
	);

	return $variation_id ? (int) $variation_id : 0;
}

/**
 * URL of the plans listing users can open to compare plans in detail.
 *
 * Prefers a published "Plans" page and falls back to the WooCommerce shop.
 *
 * @return string
 */
function libresign_get_plans_url() {
	$page = get_page_by_path( 'plans' );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return (string) get_permalink( $page );
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( ! empty( $shop_url ) ) {
			return (string) $shop_url;
		}
	}

	return '';
}

/**
 * Render the required plan + billing period selector on the registration form.
 *
 * Renders nothing when no plans are configured so registration is not made
 * impossible by a misconfiguration.
 */
function libresign_render_plan_selector() {
	$plans = libresign_get_available_plans();
	if ( empty( $plans ) ) {
		return;
	}

	$selected_plan = isset( $_POST['libresign_plan'] ) ? absint( wp_unslash( $_POST['libresign_plan'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$selected_term = isset( $_POST['libresign_plan_term'] ) ? sanitize_key( wp_unslash( $_POST['libresign_plan_term'] ) ) : 'monthly'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	$plans_url = libresign_get_plans_url();
	?>
	<fieldset class="libresign-plan-selector form-row form-row-wide validate-required">
		<legend class="libresign-plan-selector__legend">
			<?php esc_html_e( 'Choose a plan', 'libresign' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span>
		</legend>

		<?php foreach ( $plans as $plan ) : ?>
			<?php $plan_id = $plan->get_id(); ?>
			<label class="libresign-plan-selector__option">
				<input
					type="radio"
					class="woocommerce-form__input woocommerce-form__input-radio input-radio"
					name="libresign_plan"
					value="<?php echo esc_attr( $plan_id ); ?>"
					<?php checked( $selected_plan, $plan_id ); ?>
					required
					aria-required="true"
				/>
				<span class="libresign-plan-selector__name"><?php echo esc_html( $plan->get_name() ); ?></span>
				<span class="libresign-plan-selector__price"><?php echo wp_kses_post( $plan->get_price_html() ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<?php // Radio values must match the "Term length" variation slugs exactly, case-sensitive ('monthly' / 'yearly'). ?>
	<fieldset class="libresign-plan-term form-row form-row-wide">
		<legend class="libresign-plan-term__legend"><?php esc_html_e( 'Billing period', 'libresign' ); ?></legend>

		<label class="libresign-plan-term__option">
			<input type="radio" class="woocommerce-form__input woocommerce-form__input-radio input-radio" name="libresign_plan_term" value="monthly" <?php checked( $selected_term, 'monthly' ); ?> />
			<span><?php esc_html_e( 'Monthly', 'libresign' ); ?></span>
		</label>
		<label class="libresign-plan-term__option">
			<input type="radio" class="woocommerce-form__input woocommerce-form__input-radio input-radio" name="libresign_plan_term" value="yearly" <?php checked( $selected_term, 'yearly' ); ?> />
			<span><?php esc_html_e( 'Yearly', 'libresign' ); ?></span>
		</label>
	</fieldset>

	<?php if ( '' !== $plans_url ) : ?>
		<p class="libresign-plan-selector__more">
			<?php
			printf(
				/* translators: %s: URL of the plans listing page */
				wp_kses_post( __( 'Not sure which one to pick? <a href="%s" target="_blank" rel="noopener noreferrer">Compare all plans</a>.', 'libresign' ) ),
				esc_url( $plans_url )
			);
			?>
		</p>
	<?php endif; ?>
	<?php
}
