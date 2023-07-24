<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Klippe {

	/**
	* Theme Klippe
	*
	* @since v3.9.2
	* @tested with theme version 1.4
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'german_market_wp_bakery_price_html_exception' , '__return_true' );
		}

		add_action( 'wp_head', array( __CLASS__, 'theme_support_css_for_theme_klippe' ) );
		remove_action( 'klippe_mikado_woo_pl_info_below_image', 'woocommerce_template_loop_price', 26 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 11 );

	}

	/**
	* Theme Klippe: CSS for Loop Price
	*
	* @since v3.9.2
	* @tested with theme version 3.29.2
	* @wp-hook wp_head
	* @return void
	*/
	public static function theme_support_css_for_theme_klippe() {

		?>
		<style>
			ul.products>.product .mkdf-pl-text-wrapper .mkdf-product-list-title{ width: 100%; }
			ul.products>.product .mkdf-pl-text-wrapper .mkdf-pl-category{ width: 100%; text-align: left; }
		</style>

		<?php

	}
}
