<?php

namespace LibreSign\WordPressTheme\Tests\Support;

use WC_Product_Attribute;
use WC_Product_Simple;
use WC_Product_Variable;

final class StoreFactory {

	public function plan( $price = '10' ) {
		$plan = new WC_Product_Simple();
		$plan->set_name( 'LibreSign plan' );
		$plan->set_regular_price( $price );
		$plan->save();

		return $plan;
	}

	public function plan_with_seats() {
		$plan = new WC_Product_Variable();
		$plan->set_name( 'LibreSign plan with seats' );
		$plan->set_attributes(
			array(
				$this->attribute( 'Seats', array( '5', '10' ), true ),
				$this->attribute( 'Support', array( 'Email' ), false ),
			)
		);
		$plan->save();

		return $plan;
	}

	public function add_to_cart( $product ) {
		wc_load_cart();
		WC()->cart->add_to_cart( $product->get_id() );
	}

	public function empty_cart() {
		wc_load_cart();
		WC()->cart->empty_cart();
	}

	private function attribute( $name, $options, $used_for_variations ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( $name );
		$attribute->set_options( $options );
		$attribute->set_visible( true );
		$attribute->set_variation( $used_for_variations );

		return $attribute;
	}
}
