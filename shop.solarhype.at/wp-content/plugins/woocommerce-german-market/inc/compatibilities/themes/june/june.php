<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_June {

	/**
	* Theme June
	*
	* @since v3.10.1
	* @tested with theme version 1.8.1
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// elementor
		add_filter( 'german_market_compatibility_elementor_price_data', '__return_false' );

		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 15 );

		// single product
		add_action( 'woocommerce_before_single_product', function() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 6 );
		});

	}
}
