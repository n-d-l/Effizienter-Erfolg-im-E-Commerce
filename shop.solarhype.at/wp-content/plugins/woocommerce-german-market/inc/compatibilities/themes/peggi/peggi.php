<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Peggi {

	/**
	* Theme peggi: 2x price in product
	*
	* @since v3.9.1.1
	* @tested with theme version 1.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		}
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );

	}

}
