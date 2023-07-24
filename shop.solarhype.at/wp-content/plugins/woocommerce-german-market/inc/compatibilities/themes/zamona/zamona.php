<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Zamona {

	/**
	* Theme Zamona
	*
	* @since v3.14
	* @tested with theme version 1.2.1
	* @wp-hook german_market_after_frontend_init
	* @return void
	*/
	public static function init() {
		
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_price', 40);
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 40 );
		
		// single
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 20);
	}

}
