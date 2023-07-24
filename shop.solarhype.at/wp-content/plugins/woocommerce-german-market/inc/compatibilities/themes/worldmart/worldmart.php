<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Worldmart {

	/**
	* Theme Worldmart: Price in Product Pages
	*
	* @since v3.5.7
	* @wp-hook wp
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		add_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 21 );
		add_action( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_wgm_remove_price_in_summary_parts_in_shop' ), 10, 3 );
	}
}
