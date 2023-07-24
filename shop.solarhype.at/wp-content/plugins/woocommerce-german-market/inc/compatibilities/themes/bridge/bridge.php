<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Bridge {

	/**
	* Theme Bridge
	*
	* @since v3.10.1
	* @tested with theme version 21.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// bakery
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}
	}
}
