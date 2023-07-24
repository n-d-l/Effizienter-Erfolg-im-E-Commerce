<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Blaze {

	/**
	* Theme Blaze
	*
	* @since Version: 3.10.4.1
	* @wp-hook after_setup_theme
	* @tested with theme version 1.4
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// single
		remove_action('woocommerce_single_product_summary','blaze_edge_woocommerce_out_of_stock_price_single',1);

	}
}
