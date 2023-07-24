<?php
/* 
 * Add-on Name:	Billbee
 * Description:	This add-on adds an icon to each order in your ordering overview as a link to Billbee.
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'german_market_billbee_init' ) ) {

	/**
	* init
	*
	* @return void
	*/
	function german_market_billbee_init() {

		if ( is_admin() ) {
			add_action( 'woocommerce_admin_order_actions', 'gm_billbee_order_action', 200, 2 );
			add_action( 'admin_enqueue_scripts', 'gm_billbee_styles_and_scripts' ); 
		}
		
	}

	/**
	* adds a small download button to the admin page for orders
	*
	* @hook woocommerce_admin_order_actions
	* @param Array $actions
	* @param WC_Order $order
	* @return Array
	*/
	function gm_billbee_order_action( $actions, $order ) {

		$actions[ 'german_market_billbee' ] = array(

			'url' 		=>	'https://app.billbee.io/order?openOrderByExtRef=' . $order->get_id(), 
			'name' 		=> __( 'See Order on Billbee', 'woocommerce-german-market' ),
			'action'	=> 'german-market-billbee'

		);

		return $actions;
	}

	/**
	* enqueue css and file for link on shop order page
	*
	* @since 0.0.1
	* @access public
	* @static
	* @hook admin_enqueue_scripts
	* @return void
	*/				
	function gm_billbee_styles_and_scripts() {
		
		if ( WGM_Hpos::is_edit_shop_order_screen() ) {
			
			wp_enqueue_style( 'german-market-billbee-css', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/styles/edit-shop-order.min.css' );

			wp_register_script( 'german-market-billbee-script', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/scripts/admin.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'german-market-billbee-script' );

		}

	}
	
	german_market_billbee_init();

}
