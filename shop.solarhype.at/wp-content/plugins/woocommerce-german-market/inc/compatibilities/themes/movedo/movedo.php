<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Movedo {

	/**
	* Theme Movedo
	*
	* @since 3.11.1.4
	* @tested with theme version 3.4.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// single
		add_action( 'woocommerce_single_product_summary', function() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 4 );
		}, 1 );
		
	}
}
