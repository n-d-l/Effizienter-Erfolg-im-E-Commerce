<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Crexis {

	/**
	* Theme Crexis
	*
	* @since v3.10.2
	* @tested with theme version 3.1.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		add_action( 'woocommerce_after_template_part', function( $template_name, $template_path, $located, $args ) {

			if ( $template_name == 'loop/price.php' ) {
				echo WGM_Template::get_wgm_product_summary();
			}

		}, 20, 4 );
	}
}
