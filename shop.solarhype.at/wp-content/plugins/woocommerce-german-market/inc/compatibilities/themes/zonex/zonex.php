<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Zonex {

	/**
	* Theme Zonex
	*
	* @since Version: 3.10.5.0.1
	* @wp-hook after_setup_theme
	* @tested with theme version 1.1.2
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
	}
}
