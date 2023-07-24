<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Bazien {

	/**
	* Theme Bazien: Doubled Price in loop and product page
	*
	* @since v3.7.2
	* @tested with theme version 2.5
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// Loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// Single
		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
		add_action( 'woocommerce_single_product_summary_single_price', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 11 );

		// Bakery Exception
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

	}
}
