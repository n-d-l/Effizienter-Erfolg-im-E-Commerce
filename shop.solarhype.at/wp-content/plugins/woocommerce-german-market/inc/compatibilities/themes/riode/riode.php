<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Riode {

	/**
	* Theme Riode
	*
	* @since Version: 3.11.1.4
	* @wp-hook after_setup_theme
	* @tested with theme version 1.1.0
	* @return void
	*/
	public static function init() {

		// single
		remove_action( 'woocommerce_single_product_summary', 'riode_wc_template_single_price', 9 );

	}
}
