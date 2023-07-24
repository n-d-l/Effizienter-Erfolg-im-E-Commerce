<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Supro {

	/**
	* Theme Supro: Doubled Price on product page
	*
	* @since v3.9.2
	* @tested with theme version 1.4.6
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		global $supro_woocommerce;

		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		remove_action( 'woocommerce_single_product_summary', array( $supro_woocommerce, 'product_header_summary' ), 10 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
		add_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 11 );

		// JavaScript Extra Element for variable Products and variations
		add_filter( 'german_market_price_variable_theme_extra_element', function( $exra_element ) {
			return '.supro-single-product-detail p.price';
		});

		add_action( 'woocommerce_review_order_before_submit', function() {
			echo '<div class="woocommerce-terms-and-conditions-wrapper">';
		}, 0 );


		add_action( 'woocommerce_review_order_before_submit', function() {
			echo '</div>';
		}, 999 );

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

	}
}
