<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Mybag {

	/**
	* Theme MyBag
	*
	* @since v3.10.2
	* @tested with theme version 1.2.6
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		}
	}
}
