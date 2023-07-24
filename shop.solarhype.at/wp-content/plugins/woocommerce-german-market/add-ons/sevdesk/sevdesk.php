<?php
/* 
 * Add-on Name:	sevDesk
 * Description:	sevDesk API for Woocommerce
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! function_exists( 'sevdesk_woocommerce_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function sevdesk_woocommerce_init() {

		// load api
		$backend_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';
		require_once( $backend_dir . DIRECTORY_SEPARATOR . 'api.php' );

		if ( is_admin() ) {
			
			// stuff that is only needed in the shop order table
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'edit-shop-order.php' );
			add_action( 'current_screen', 'sevdesk_woocommerce_edit_shop_order' );

			// settings
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'settings.php' );
			add_filter( 'woocommerce_de_ui_left_menu_items', 'sevdesk_woocommerce_de_ui_left_menu_items' );

			// ajax handler
			if ( function_exists( 'curl_init' ) ) {
				require_once( $backend_dir . DIRECTORY_SEPARATOR . 'ajax-handler.php' );
				add_action( 'wp_ajax_sevdesk_woocommerce_edit_shop_order', 'sevdesk_woocommerce_edit_shop_order_ajax' );
				add_action( 'wp_ajax_sevdesk_woocommerce_edit_shop_order_refund', 'sevdesk_woocommerce_edit_shop_order_ajax_refund' );
			}

			// individual product booking accounts
			if ( get_option( 'woocommerce_de_sevdesk_individual_product_booking_accounts', 'off' ) == 'on' ) {
				if ( get_option( 'woocommerce_de_sevdesk_api_token' ) != '' ) {
					add_action( 'woocommerce_product_data_tabs', 		'sevdesk_woocommerce_accounts_product_tab' , 20 );
					add_action( 'woocommerce_product_data_panels', 		'sevdesk_woocommerce_accounts_product_panel' );
					add_action( 'woocommerce_process_product_meta',		'sevdesk_woocommerce_accounts_save_meta', 10 );
				}
			}

			// individual check accounts for payment gateways
			if ( get_option( 'woocommerce_de_sevdesk_individual_gateway_check_accounts', 'off' ) == 'on' ) {
				if ( get_option( 'woocommerce_de_sevdesk_api_token' ) != '' ) {
					add_action( 'init', 'woocommerce_de_sevdesk_gateway_check_accounts_init' );
				}
			}
				
		}

		// automatic transmission
		require_once( untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'auto-transmission.php' );

		// bulk transmission
		require_once( untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'bulk-transmission.php' );

	}
	
	sevdesk_woocommerce_init();

}
