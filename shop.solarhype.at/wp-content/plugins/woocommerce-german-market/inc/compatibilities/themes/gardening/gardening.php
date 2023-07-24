<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Gardening {

	/**
	* Theme Gardening
	*
	* @since v3.9.1.9
	* @tested with theme version 1.8
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		if ( class_exists( 'Vc_Manager' ) ) {
			remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		} else {
			remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
			add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
			add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 11 );
		}
	}
}
