<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Technics {

	/**
	* Theme Technigs: Doubled Price in loop and product page
	*
	* @since v3.7.2
	* @tested with theme version 1.0.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// Loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 3 );

		// Single
		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
		add_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 12 );

	}
}
