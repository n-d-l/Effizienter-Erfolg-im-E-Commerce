<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Rigid {

	/**
	* Theme Rigid
	*
	* @since Version: 3.10.5.0.1
	* @tested with theme version 5.6.6.1
	* @wp-hook after_setup_theme
	* @tested with theme version 2.8
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

	}
}
