<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Elessitheme {

	/**
	* Theme Elessi: Price in Loop & Product Pages
	*
	* @since v3.6.2
	* @last updated v3.10.6.0.10
	* @tested with theme version 4.1.6.1
	* @wp-hook ini
	* @return void
	*/
	public static function init() {

		add_action( 'init', function() {
			remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 20);
			remove_action('woocommerce_after_shop_loop_item_title', 'elessi_loop_product_price', 10);
		}, 20 );
	}
}
