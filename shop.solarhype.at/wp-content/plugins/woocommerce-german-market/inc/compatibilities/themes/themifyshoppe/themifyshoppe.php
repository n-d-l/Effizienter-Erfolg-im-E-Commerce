<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Themifyshoppe {

	/**
	* Theme Themify Shoppe
	*
	* @since Version: 3.10.4.1
	* @updated Version: 3.10.5.1
	* @updated Version: 3.11.0.1
	* @wp-hook after_setup_theme
	* @tested with theme version 5.2.2
	* @return void
	*/
	public static function init() {

		// loop
		//add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		//remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'themify_product_price_end', function() {
			global $product;
			
			if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
				return;
			}

			$call_funtion = 'price_theme_themify_shoppe';

			if ( $product instanceof WC_Product_Grouped ) {
				return;
			}
			
			$return_because_in_loop = false;

			$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 20 );
			foreach ( $debug_backtrace as $elem ) {

				if ( 'woocommerce_template_loop_price' === $elem[ 'function' ] ) {
					$return_because_in_loop = true;
				}
			}

			if ( $return_because_in_loop ) {
				WGM_Template::woocommerce_de_price_with_tax_hint_loop();
			}

		} );
	}
}
