<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Luciavineyards {

	/**
	* Theme Lucia Vineyards
	*
	* @since 3.11.1.4
	* @tested with theme version 1.3
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );
		
	}
}
