<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Ciloe {

	/**
	* Theme Ciloe: In Loop: Change order of data
	*
	* @since v3.8.1
	* @tested with theme Version 1.5.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );

	}
}
