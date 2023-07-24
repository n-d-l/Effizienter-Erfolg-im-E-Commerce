<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Kitring {

	/**
	* Theme Kitring
	*
	* @since 3.10.6.0.6
	* @tested with theme version 2.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );

		add_action( 'woocommerce_after_template_part', function( $template_name, $template_path, $located, $args ) {

			if ( $template_name == 'loop/price.php' ) {
				echo WGM_Template::get_wgm_product_summary( NULL, 'theme_ktring_loop_price', false );
			}

		}, 20, 4 );

	}
}
