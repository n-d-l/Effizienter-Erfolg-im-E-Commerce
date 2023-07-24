<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Aurum {

	/**
	* Theme aurom Support: Double price in loop and single product pages
	*
	* Tested with Theme Version 3.0.1
	* @access public
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// avoid double price in loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_price', 20 );
		add_action( 'woocommerce_after_shop_loop_item',		array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 20 );

		// avoid douple price in single product
		remove_filter( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );

		// avoid double taxes in cart
		add_filter( 'gm_cart_template_in_theme_show_taxes', '__return_false' );
	}

}