<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Hyperon {

	/**
	* Theme Hyperon
	*
	* @since v3.9.2
	* @tested with theme version 1.3
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		add_action('hyperon_edgtf_woo_pl_info_below_image', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 28 );
		add_filter( 'gm_add_price_in_loop_for_grouped_products_again', '__return_false' );

		// single
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 8 );

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}
	}
}
