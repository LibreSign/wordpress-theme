<?php
/**
 * Product page display customizations.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove attributes used for variations from the "Additional information" tab.
 *
 * WooCommerce shows all attributes in that tab by default, even those that
 * already appear as variation selectors in the add-to-cart form.
 * Displaying them twice is redundant and confusing.
 */
add_filter( 'woocommerce_display_product_attributes', function ( array $attributes, $product ) {
	if ( ! method_exists( $product, 'get_variation_attributes' ) ) {
		return $attributes;
	}

	foreach ( array_keys( $product->get_variation_attributes() ) as $attribute_name ) {
		// wc_display_product_attributes() builds keys as 'attribute_' + sanitize_title_with_dashes( name ).
		unset( $attributes[ 'attribute_' . sanitize_title( $attribute_name ) ] );
	}

	return $attributes;
}, 10, 2 );
