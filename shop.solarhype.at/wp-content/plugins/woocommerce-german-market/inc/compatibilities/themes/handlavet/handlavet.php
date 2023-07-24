<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Handlavet {

	/**
	* Theme Handlavet
	*
	* @since Version: 3.10.5.0.1
	* @wp-hook after_setup_theme
	* @tested with theme version 1.1
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// single
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );

	}
}
