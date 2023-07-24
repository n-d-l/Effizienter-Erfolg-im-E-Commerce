<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Erado {

	/**
	* Theme Erado: Doubled Price on product page
	*
	* @since v3.7.2
	* @tested with theme version 1.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 7 );
	}
}
