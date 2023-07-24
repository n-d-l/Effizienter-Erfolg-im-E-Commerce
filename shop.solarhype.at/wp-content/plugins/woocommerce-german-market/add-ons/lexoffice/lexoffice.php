<?php
/* 
 * Add-on Name:	Lexoffice
 * Description:	Lexoffice API for Woocommerce
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! function_exists( 'lexoffice_woocommerce_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function lexoffice_woocommerce_init() {

		// load api
		$backend_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';
		require_once( $backend_dir . DIRECTORY_SEPARATOR . 'api.php' );
			
		if ( is_admin() ) {

			// stuff that is only needed in the shop order table
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'edit-shop-order.php' );
			add_action( 'current_screen', 'lexoffice_woocommerce_edit_shop_order' );

			// settings
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'settings.php' );
			add_filter( 'woocommerce_de_ui_left_menu_items', 'lexoffice_woocommerce_de_ui_left_menu_items' );
			add_action( 'woocommerce_de_ui_update_options', 'lexoffice_woocommerce_de_ui_update_options' );

			// ajax handler
			if ( function_exists( 'curl_init' ) ) {
				
				require_once( $backend_dir . DIRECTORY_SEPARATOR . 'ajax-handler.php' );
				add_action( 'wp_ajax_lexoffice_woocommerce_edit_shop_order', 'lexoffice_woocommerce_edit_shop_order_ajax' );
				add_action( 'wp_ajax_lexoffice_woocommerce_edit_shop_order_refund', 'lexoffice_woocommerce_edit_shop_order_ajax_refund' );

				// payment gateways option: due date
				require_once( $backend_dir . DIRECTORY_SEPARATOR . 'due-date.php' );
				add_action( 'init', 'lexoffice_woocommerce_due_date_init' );

				// user profile
				if ( get_option( 'woocommerce_de_lexoffice_contacts', 'collective_contact' ) != 'collective_contact' ) {
					require_once( $backend_dir . DIRECTORY_SEPARATOR . 'user-profile.php' );
					add_action( 'show_user_profile', 'lexoffice_woocommerce_profile_fields', 21 );
					add_action( 'edit_user_profile', 'lexoffice_woocommerce_profile_fields', 21 );

					add_action( 'personal_options_update', 'lexoffice_woocommerce_save_profile_fields' );
					add_action( 'edit_user_profile_update', 'lexoffice_woocommerce_save_profile_fields' );
				}
			}		
		}

		// handling orders with refunds
		if ( get_option( 'woocommerce_de_lexoffice_transmit_order_before_refund', 'on' ) === 'on' ) {
			add_action( 'woocommerce_de_lexoffice_api_before_send_refund', 'lexoffice_send_order_before_refund', 10, 2 );
		}

		// allow to transmit orders with refunds
		add_filter( 'woocommerce_de_lexoffice_force_transmission_even_if_not_completed', function( $allow = false, $order = null ) {

			if ( ! $allow ) {
				if ( is_object( $order ) && method_exists( $order, 'get_refunds' ) ) {
					$count_refunds = count( $order->get_refunds() );
					if ( $count_refunds > 0 ) {
						$allow = true;
					}
				}
			}

			return $allow;
		}, 9, 2 );

		// automatic transmission
		require_once( untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'auto-transmission.php' );

		// bulk transmission
		require_once( untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'bulk-transmission.php' );

	}
	
	lexoffice_woocommerce_init();

}
