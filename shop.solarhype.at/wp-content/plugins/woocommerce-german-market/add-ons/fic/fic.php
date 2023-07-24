<?php
/* 
 * Add-on Name:	FIC
 * Description:	EU Food Information for Consumers Regulation (EU FIC) Woocommerce
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! function_exists( 'german_market_fic_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function german_market_fic_init() {

		$app_dir 		= untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'app';

		require_once( $app_dir . DIRECTORY_SEPARATOR . 'defaults.php' );
		
		if ( is_admin() || apply_filters( 'german_market_fic_init_admin_in_frontend', false ) ) {

			$backend_dir = $app_dir . DIRECTORY_SEPARATOR . 'be';

			// settings
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'settings.php' );
			add_filter( 'woocommerce_de_ui_left_menu_items', 'gm_fic_woocommerce_de_ui_left_menu_items' );

			// product meta data in edit product
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'edit-product.php' );
			add_action( 'woocommerce_product_options_general_product_data',	'gm_fic_product_options',  10, 3 );
			add_action( 'woocommerce_product_after_variable_attributes', 'gm_fic_product_options',  10, 3 );
			add_action( 'woocommerce_process_product_meta',	'gm_fic_product_options_save', 10 );
			add_action( 'woocommerce_ajax_save_product_variations', 'gm_fic_product_options_save', 10, 2 );

		}

		$frontend_dir = $app_dir . DIRECTORY_SEPARATOR . 'fe';

		// new product tabs
		require_once( $frontend_dir . DIRECTORY_SEPARATOR . 'tabs.php' );
		add_filter( 'woocommerce_product_tabs', 'gm_fic_product_tab' );

		// shortcodes
		require_once( $frontend_dir . DIRECTORY_SEPARATOR . 'shortcodes.php' );
		$shortcodes = WGM_FIC_Shortcodes::get_instance();

		// Register Script
		add_action( 'wp_enqueue_scripts', 'gm_fic_product_tab_scripts' );

		// Ajax
		require_once( $app_dir . DIRECTORY_SEPARATOR . 'ajax.php' );
		add_action( 'wp_ajax_gm_fic_product_update_variation', 'gm_fic_product_update_variation' );
		add_action( 'wp_ajax_nopriv_gm_fic_product_update_variation', 'gm_fic_product_update_variation' );
		add_action( 'wp_ajax_gm_fic_product_update_variation_allergens', 'gm_fic_product_update_variation_allergens' );
		add_action( 'wp_ajax_nopriv_gm_fic_product_update_variation_allergens', 'gm_fic_product_update_variation_allergens' );
		add_action( 'wp_ajax_gm_fic_product_update_variation_ingredients', 'gm_fic_product_update_variation_ingredients' );
		add_action( 'wp_ajax_nopriv_gm_fic_product_update_variation_ingredients', 'gm_fic_product_update_variation_ingredients' );

		// taxonomies
		require_once( $app_dir . DIRECTORY_SEPARATOR . 'taxonomies.php' );
		add_filter( 'woocommerce_register_taxonomy', 'gm_fic_register_taxonomies' );

		// alcohol content in frontend
		require_once( $frontend_dir . DIRECTORY_SEPARATOR . 'alcohol.php' );
		add_filter( 'wgm_product_summary_parts', 'gm_fic_add_alcohol_content_to_product_info', 10, 3 );
		
		if ( get_option( 'gm_fic_ui_alocohol_checkout', 'off' ) == 'on' ) {
			add_filter( 'woocommerce_add_cart_item_data', 'gm_fic_woocommerce_add_cart_item_data', 10, 3 );
			add_filter( 'woocommerce_get_cart_item_from_session', 'gm_fic_woocommerce_get_cart_item_from_session', 10, 3 );
			add_filter( 'woocommerce_get_item_data', 'gm_fic_woocommerce_get_item_data', 10, 2 );
			add_action( 'woocommerce_new_order_item', 'gm_fic_woocommerce_add_order_item_meta_wc_3', 10, 3 );
		}
	}
	
	german_market_fic_init();
}
