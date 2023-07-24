<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Woostroid {

	/**
	* Theme Woodstroid: Remove double price in shop
	*
	* @since v3.5.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_after_shop_loop_item', 'woostroid_woocommerce_template_loop_price_grid', 5 );
	}
}
