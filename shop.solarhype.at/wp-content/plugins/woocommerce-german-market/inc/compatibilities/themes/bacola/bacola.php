<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Bacola {

	/**
	* Theme Bacola
	*
	* @since v3.13.2.0.1
	* @tested with theme version 1.1.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'woocommerce_get_price_html' ), 10, 2  );
	}

	/**
	* Add German Market Price Data in Loop
	*
	* @wp-hook woocommerce_get_price_html
	* @param String $price
	* @param WC_Product $product
	* @return String
	*/
	public static function woocommerce_get_price_html( $price, $product ) {
		if ( WGM_Helper::method_exists( $product, 'get_type' ) ) {
			
			$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
			foreach ( $debug_backtrace as $elem ) {
				if ( str_replace( 'bacola_product_type', '', $elem[ 'function' ]  ) != $elem[ 'function' ] ) {
				
					ob_start();
					echo WGM_Template::get_wgm_product_summary( $product, 'bacola_theme' );
					$price = ob_get_clean();
					break;
				}
			}

		}

		return $price;
	}
}
