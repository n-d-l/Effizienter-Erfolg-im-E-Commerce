<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Mrtailor {

	/**
	* Theme Mr. Tailor
	*
	* @since v3.10.1
	* @tested with theme version 2.9.15
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		add_action( 'woocommerce_before_single_product_summary', function() {
			remove_action( 'woocommerce_single_product_summary_single_price', 'woocommerce_template_single_price', 10 );
		});

		add_action( 'woocommerce_before_shop_loop_item', function() {
			remove_action( 'woocommerce_after_shop_loop_item_title_loop_price', 'woocommerce_template_loop_price', 10 );
		});
	}
}
