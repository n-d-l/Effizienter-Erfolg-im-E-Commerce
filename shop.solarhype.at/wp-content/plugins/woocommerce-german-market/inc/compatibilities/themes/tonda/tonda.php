<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Tonda {

	/**
	* Theme Tonda
	*
	* @since v3.10.2
	* @tested with theme version 1.6
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'tonda_select_action_woo_pl_info_below_image', 'woocommerce_template_loop_price', 24 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );
	}
}
