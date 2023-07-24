<?php
/* 
 * Add-on Name:	Protected Shops
 * Description:	Protected Shops API for Woocommerce
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! function_exists( 'gm_protected_shops_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function gm_protected_shops_init() {

		// load api
		$backend_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';
		require_once( $backend_dir . DIRECTORY_SEPARATOR . 'class-api.php' );

		if ( is_admin() ) {
			
			// settings
			require_once( $backend_dir . DIRECTORY_SEPARATOR . 'settings.php' );
			add_filter( 'woocommerce_de_ui_left_menu_items', 'gm_protected_shops_ui_left_menu_items' );

			// load questionary library
			define( 'GM_PROTECTED_SHOPS_LIBRARY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/inc/ps_questionary_integration' );
			define( 'GM_PROTECTED_SHOPS_ASSETS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets' );
			add_action( 'current_screen', 'gm_protected_shops_questionary_library' );
			add_action( 'wp_ajax_gm_ps_get_questionary', 'gm_protected_shops_ajax_get_questionary' );
			add_action( 'wp_ajax_gm_ps_save_questionary', 'gm_protected_shops_ajax_save_questionary' );

			// ajax for downloading pdf
			add_action( 'wp_ajax_gm_ps_download_documents', 'gm_protected_shops_ajax_download_documents' );

			// ajax for saving into WordPress pages
			add_action( 'wp_ajax_gm_ps_save_page', 'gm_protected_shops_ajax_save_page' );

			// cronjob helper: save settings
			add_action( 'woocommerce_de_ui_update_options', 'gm_protected_shops_cronjob_helper_save_settings' );
			

		}

		$application_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'application';
		require_once( $application_dir . DIRECTORY_SEPARATOR . 'cronjob-checker.php' );

		// cronjob checker
		add_action( 'wp_loaded', 'gm_protected_shops_cronjob_checker' );

		// email attachments
		require_once( $application_dir . DIRECTORY_SEPARATOR . 'email-attachments.php' );
		add_action( 'woocommerce_email_attachments', 'gm_protected_shops_email_attachments', 10, 3 );
 
	}
	
	gm_protected_shops_init();

}
