<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Zass {

	/**
	* Theme Zass: Price in Loop & Product Pages
	*
	* @since v3.6.3
	* @tested with theme version 2.7.0
	* @wp-hook german_market_after_frontend_init
	* @return void
	*/
	public static function init() {
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'woocommerce_after_template_part', array( __CLASS__, 'theme_support_zass_after_price_loop' ), 10, 1 );
	}

	/**
	* Theme Zass: Price in Loop & Product Pages
	*
	* @since v3.6.3
	* @tested with theme version 2.7.0
	* @wp-hook woocommerce_after_template_part
	* @param String $template_name
	* @return void
	*/
	public static function theme_support_zass_after_price_loop( $template_name ) {

		if ( $template_name == 'loop/price.php' ) {
			echo '<div style="german-market-product-info-loop-price">';
				WGM_Template::woocommerce_de_price_with_tax_hint_loop();
			echo '</div>';
		}
	}

}
