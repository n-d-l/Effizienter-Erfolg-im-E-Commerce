<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Woovina {

	/**
	* Theme Woovina
	*
	* @since v3.9.1.9
	* @tested with theme version 4.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		add_action( 'woovina_after_archive_product_inner', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
	}
}
