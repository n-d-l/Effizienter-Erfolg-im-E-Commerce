<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Goya {

	/**
	* Theme Goya
	*
	* @since 3.10.6.0.6
	* @tested with theme version 1.0.4.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// single
		add_action( 'woocommerce_before_single_product', function() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );
		}, 20 );
		
		// quickview
		add_action( 'goya_quickview_woocommerce_before_single_product', function() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );
		}, 20 );
	}

}
