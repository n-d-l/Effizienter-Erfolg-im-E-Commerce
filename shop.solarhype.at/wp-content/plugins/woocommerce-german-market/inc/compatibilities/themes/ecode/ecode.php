<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Ecode {

	/**
	* Theme Ecode: Doubled Price in loop
	*
	* @since v3.7.1
	* @tested with theme version 1.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// shop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 10 );
	}
}
