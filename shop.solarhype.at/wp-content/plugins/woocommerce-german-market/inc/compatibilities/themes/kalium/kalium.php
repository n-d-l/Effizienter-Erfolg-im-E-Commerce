<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Kalium {

	/**
	* Theme Kalium: Price in Loop & Product Pages
	*
	* @since v3.6.3
	* @tested with theme version 2.5.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// Loop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_template_part', array( __CLASS__, 'theme_support_kalium_loop_price' ), 10, 4 );

		// Shop
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 29 );

		// Bakery Exception
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}
	}

	/**
	* Theme Kalium: Price in Loop
	*
	* @since v3.6.3
	* @tested with theme version 2.5.0
	* @wp-hook woocommerce_after_template_part
	* @param String $template_name
	* @param String $template_path
	* @param String $located
	* @param Array $args
	* @return void
	*/
	public static function theme_support_kalium_loop_price( $template_name, $template_path, $located, $args ){

		if ( $template_name == 'loop/price.php' ) {
			WGM_Template::woocommerce_de_price_with_tax_hint_loop();
		}

	}
}
