<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Lotusgreen {

	/**
	* Theme Lotusgreen
	*
	* @since 3.11.0.1
	* @tested with theme version 1.7
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// single
		remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_price',5 );

		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 1 );

		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}
	}
}
