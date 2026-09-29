<?php

namespace LibreSign\WordPressTheme\Tests\Integration\Inc;

use LibreSign\WordPressTheme\Tests\Support\StoreFactory;
use WP_UnitTestCase;

final class ProductTest extends WP_UnitTestCase {

	private function additional_information( $product ) {
		ob_start();
		wc_display_product_attributes( $product );

		return (string) ob_get_clean();
	}

	public function test_hides_the_attributes_already_offered_as_variations() {
		$information = $this->additional_information( ( new StoreFactory() )->plan_with_seats() );

		$this->assertStringContainsString( 'Support', $information );
		$this->assertStringNotContainsString( 'Seats', $information );
	}

	public function test_keeps_the_attributes_of_a_product_without_variations() {
		$attributes = array( 'attribute_seats' => array( 'label' => 'Seats' ) );

		$this->assertSame(
			$attributes,
			apply_filters( 'woocommerce_display_product_attributes', $attributes, ( new StoreFactory() )->plan() )
		);
	}
}
