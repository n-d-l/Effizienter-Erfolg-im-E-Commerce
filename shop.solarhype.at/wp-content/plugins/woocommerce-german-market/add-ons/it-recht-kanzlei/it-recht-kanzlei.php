<?php
/* 
 * Add-on Name:	IT-Recht Kanzlei
 * Description:	IT-Recht Kanzlei API for Woocommerce
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! function_exists( 'gm_it_recht_kanzlei_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function gm_it_recht_kanzlei_init() {

		// load api
		$backend_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';
		require_once( $backend_dir . DIRECTORY_SEPARATOR . 'class-api.php' );

		if ( is_admin() ) {
			
			// settings
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'settings.php' );
			add_filter( 'woocommerce_de_ui_left_menu_items', 'gm_it_recht_kanzlei_ui_left_menu_items' );

			// javascript
			define( 'GM_it_recht_kanzlei_ASSETS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets' );
			add_action( 'current_screen', 'gm_it_recht_kanzlei_backend_scripts' );

		}

		// API Request
		add_action( 'wp_loaded', array( 'GM_IT_Recht_Kanzlei_Api', 'check_api_request' ) );

		// Email Attachments
		$application_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application';
		require_once( $application_dir . DIRECTORY_SEPARATOR . 'email-attachments.php' );
		add_action( 'woocommerce_email_attachments', 'gm_it_recht_kanzlei_email_attachments', 10, 3 );

	}
	
	gm_it_recht_kanzlei_init();

}
