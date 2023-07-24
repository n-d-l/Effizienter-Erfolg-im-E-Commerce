<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Alishop {

	/**
	* Theme Ecode: Doubled Price in loop and product page
	*
	* @since v3.7.1
	* @tested with theme version 1.1.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'alishop_template_loop_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'alishop_woocommerce_single_price', 10 );
	}
}
