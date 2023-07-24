<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Sentinal {

	/**
	* Theme Sentinal
	*
	* @since Version: 3.12.0.3-Support
	* @wp-hook after_setup_theme
	* @tested with theme version 1.0
	* @return void
	*/
	public static function init() {

		// single
		remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_price',5 );

		// loop
		add_filter( 's7upf_product_price', function( $html) {

			return '';
		});
	}
}
