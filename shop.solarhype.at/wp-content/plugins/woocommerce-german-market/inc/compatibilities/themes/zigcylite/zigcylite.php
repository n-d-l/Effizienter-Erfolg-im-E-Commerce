<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Zigcylite{

	/**
	* Theme Zigcy Lite
	*
	* @since Version: 3.11.1.4
	* @wp-hook after_setup_theme
	* @tested with theme version 2.0.6
	* @return void
	*/
	public static function init() {
		
		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// single
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 21 );
	}
}

// loop
if ( ! function_exists( 'zigcy_lite_product_title_wrap' ) ) {
	function zigcy_lite_product_title_wrap(){
		echo '<div class="sml-product-title-wrapp">';
		zigcy_lite_product_title();
		echo '<div class="sml-price-wrap">';
		woocommerce_template_loop_price();
		echo '<span class="price">';
		WGM_Template::woocommerce_de_price_with_tax_hint_loop();
		echo '</span>';
		woocommerce_template_loop_rating();
		zigcy_lite_add_to_cart_wrap();
		echo '</div>';
		echo '</div>';
	}
}
