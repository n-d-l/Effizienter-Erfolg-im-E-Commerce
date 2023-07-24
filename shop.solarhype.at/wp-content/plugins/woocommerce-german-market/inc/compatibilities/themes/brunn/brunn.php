<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Brunn {

	/**
	* Theme Brunn
	*
	* @since v3.12.5
	* @tested with theme version 1.9
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 15 );
	}
}
