<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Techmarket {

	/**
	* Theme techmarket
	*
	* @since v3.10.0.1
	* @tested with theme version 1.4.3
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// elementor
		add_filter( 'german_market_compatibility_elementor_price_data', '__return_false' );

		// remove theme price from loop
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_price', 20 );

		// remove german market price and additonal data from loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );

		// add german market price and additional data to the place where the theme price had been
		add_action( 'woocommerce_before_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 20 );
	}
}
