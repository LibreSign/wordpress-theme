<?php

WC_Install::create_pages();

$plan = new WC_Product_Simple();
$plan->set_name( 'Basic' );
$plan->set_slug( 'basic' );
$plan->set_regular_price( '55' );
$plan->set_virtual( true );
$plan->save();
