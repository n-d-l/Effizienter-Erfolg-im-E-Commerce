<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Dokan{

	/**
	* Theme Dokan
	*
	* @since Version: 3.11.0.1
	* @wp-hook after_setup_theme
	* @tested with theme version 2.3.6
	* @return void
	*/
	public static function init() {

		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 11 );
	}
}
