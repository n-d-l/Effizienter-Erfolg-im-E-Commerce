<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Ambient {

	/**
	* Theme Ambient
	*
	* @since v3.13.1
	* @tested with theme version 1.9.1
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );
	}
}
