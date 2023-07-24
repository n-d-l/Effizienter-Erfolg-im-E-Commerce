<?php
/**
 * Inc Name: WooCommerce Price Per Unit For Variations
 * Description: 
 * Version:     1.0
 * Author:      MarketPress
 * Author URI:  http://marketpress.com
 * Licence:     GPLv3
 */

// check wp
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// define needed constants
define( 'WCPPUFV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPPUFV_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCPPUFV_BASEFILE', plugin_basename( __FILE__ ) );

/**
 * Initializes the plugin, registers
 * all the filters, hooks and loads
 * the files.
 * 
 * @wp-hook	plugins_loaded
 * @return	void
 */
function wcppufv_init() {

	// set the directory
	$application_directory = dirname( __FILE__ ) . '/application/';

	// include the helpers
	require_once( $application_directory . 'helper.php' );

	// add price per unit to single product view
	require_once( $application_directory . 'frontend/display-price-per-unit.php' );

	// backend stuff
	if ( is_admin() ) {

		// the variation fields
		require_once( $application_directory . 'backend/variation-fields.php' );
		
		add_action( 'woocommerce_process_product_meta',							'wcppufv_save_field', 10 );
		add_action( 'woocommerce_ajax_save_product_variations', 				'wcppufv_save_field', 10, 2 );

		add_action( 'woocommerce_product_after_variable_attributes', 'wcppufv_add_field', 10, 3 );

	}

}
