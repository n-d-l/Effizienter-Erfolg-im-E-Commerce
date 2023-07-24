<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Sport {

	/**
	* Theme Sport
	*
	* @since Version: 3.10.5.0.1
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

		// single
		add_action( 'woocommerce_after_template_part', function( $template_name, $template_path, $located, $args ) {

			if ( $template_name == 'single-product/price.php' ) {
				echo WGM_Template::get_wgm_product_summary( null, 'theme_sport_single', false );
			}

		}, 20, 4 );

	}
}
