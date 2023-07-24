<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Helloelementor {

	/**
	* Theme Hello Elementor
	*
	* @since Version: 3.10.6.0.6
	* @wp-hook after_setup_theme
	* @tested with theme version 2.3.1
	* @return void
	*/
	public static function init() {
		
		if ( defined( 'ELEMENTOR_VERSION' ) ) {

			add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
			add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

			add_filter( 'german_market_price_variable_theme_extra_element', function( $extra_element ) {
				return '.elementor-jet-single-price.jet-woo-builder .price:first-child';
			});

		}
	}
}
