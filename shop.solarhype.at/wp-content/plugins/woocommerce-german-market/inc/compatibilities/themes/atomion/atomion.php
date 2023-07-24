<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Atomion {

	/**
	* Theme Atomion
	*
	* @since 3.12.5
	* @tested with theme version 1.3.5
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		add_filter( 'atomion_wc_checkout_description_show_excerpt_get_excerpt', array( __CLASS__, 'short_description' ), 10, 3 );
	}

	/**
	* Get Shortdescription made by German Market
	*
	* @since 3.12.5
	* @tested with theme version 1.3.5
	* @wp-hook atomion_wc_checkout_description_show_excerpt_get_excerpt
	* @param WP_Post $post_excerpt
	* @param mixed $other_data
	* @param mixed $cart_item 
	* @return String
	*/
	public static function short_description( $post_excerpt, $other_data, $cart_item ) {

		if ( method_exists( 'WGM_Template', 'get_short_description_by_product_id' ) ) {

			if ( 'on' === get_option( 'woocommerce_de_show_show_short_desc', 'off' ) ) {
				if ( isset( $cart_item[ 'product_id' ] ) && intval( $cart_item[ 'product_id' ] ) > 0 ) {
					$product_id = $cart_item[ 'product_id' ];
				}
				
				if ( isset( $cart_item[ 'variation_id' ] ) && intval( $cart_item[ 'variation_id' ] ) > 0 ) {
					$product_id = $cart_item[ 'variation_id' ];
				}

				$post_excerpt = WGM_Template::get_short_description_by_product_id( $product_id );
			}

		}
		return $post_excerpt;
	}
}
