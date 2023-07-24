<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Shopkeeper {

	/**
	* Theme Shopkeeper: Price and Bakery Builder
	*
	* @since v3.9.1
	* @tested with theme version 2.8.3
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

	}
}
