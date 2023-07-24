<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Eco {

	/**
	* Theme Eat Eco: 2x price
	*
	* @since v3.9.2
	* @tested with theme version 1.0.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );

		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		add_action( 'woocommerce_after_template_part', function( $template_name, $template_path, $located, $args ) {

			if ( $template_name == 'single-product/price.php' ) {
				echo WGM_Template::get_wgm_product_summary();
			}

		}, 20, 4 );

	}
}
