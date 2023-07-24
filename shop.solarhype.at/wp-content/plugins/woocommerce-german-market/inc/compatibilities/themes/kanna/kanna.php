<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Kanna {

	/**
	* Theme Kanna: Double Price
	*
	* @since v3.8.2
	* @tested with theme version 1.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'kanna_mikado_action_woo_pl_info_below_image', 'woocommerce_template_loop_price', 27 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 29 );
	}
}
