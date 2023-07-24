<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Waveride {

	/**
	* Theme Waveride
	*
	* @since Version: 3.15
	* @wp-hook after_setup_theme
	* @tested with theme version 1.3
	* @return void
	*/
	public static function init() {
		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// single
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 12 );
	}
}
