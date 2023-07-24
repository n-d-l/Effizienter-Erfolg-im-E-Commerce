<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Pearl {

	/**
	* Theme Pearl
	*
	* @since v3.10.2
	* @tested with theme version 3.1.5
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 5 );
	}
}
