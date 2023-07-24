<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Rttheme20 {

	/**
	* Theme RT-Theme-20
	*
	* @since Version: 3.15
	* @wp-hook after_setup_theme
	* @tested with theme version 2.4
	* @return void
	*/
	public static function init() {

		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		remove_action( 'rt_product_info_footer', 'woocommerce_template_loop_price', 5 );
	}
}
