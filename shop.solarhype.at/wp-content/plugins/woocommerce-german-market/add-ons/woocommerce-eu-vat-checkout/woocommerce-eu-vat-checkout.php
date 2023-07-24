<?php
/**
 * Plugin Name:     WooCommerce EU VAT Checkout
 * Description:     From 1 January 2015, digital supplies in B2C transactions to EU countries need to apply taxation according to the VAT rate applicable in the consumer’s billing country. This plugin will fixate product prices you have set in your WooCommerce online store. It will display prices in your shop according to your WooCommerce settings. During  checkout it will then dynamically calculate taxes included in line item prices and totals according to the shipping country entered by your customer and the tax rates you have set for that particular country.
 * Author:          MarketPress
 */

if ( ! function_exists( 'add_action' ) ) {
	return;
}

// don't load add-on if plug is activated
if ( ! function_exists( 'wcevc_setup' ) ) {


	require_once( 'inc' . DIRECTORY_SEPARATOR . 'class-wcevc-plugin.php' );

	/**
	 * Setup function for our plugin.
	 *
	 * @wp-hook plugins_loaded
	 *
	 * @return  void
	 */
	function wcevc_setup() {
		$plugin = WCEVC_Plugin::get_instance();
		$plugin->run();
	}

	/**
	 * Callback for activating the plugin.
	 *
	 * @return  void
	 */
	function wcevc_activate() {
		$plugin = WCEVC_Plugin::get_instance();
		$plugin->activate();
	}

	if ( ! function_exists( 'pre' ) ) {
		/**
		 * Debugging-Helper to print some args.
		 *
		 * @return void
		 */
		function pre( ) {
			$args = func_get_args();
			foreach ( $args as $arg ) {
				echo "<pre>" . print_r( $arg, TRUE ) . "</pre>";
			}
		}
	}

	wcevc_setup();
	register_activation_hook( __FILE__, 'wcevc_activate' );

}
