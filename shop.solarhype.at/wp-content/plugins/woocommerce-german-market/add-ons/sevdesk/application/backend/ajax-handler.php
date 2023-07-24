<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* ajax handler, click on button on edit_shop-order screen
*
* wp-hook wp_ajax_$action (wp_ajax_sevdesk_woocommerce_edit_shop_order)
* @return exit();
*/
function sevdesk_woocommerce_edit_shop_order_ajax() {

	if ( check_ajax_referer( 'sevdesk_woocommerce_edit_shop_order_script', 'security', false ) ) {
		
		// get order
		$order_id = $_REQUEST[ 'order_id' ];
		$order = wc_get_order( $order_id );

		// api
		$response = sevdesk_woocomerce_api_send_order( $order );

		// echo response
		echo apply_filters( 'sevdesk_woocommerce_edit_shop_order_ajax_api', $response, $order_id );
	
	} else {
		echo "<b>ERROR: </b>" . __( 'Ajax nonce check failed.', 'woocommerce-german-market' );
	}

	exit();

}

/**
* ajax handler, click on button on page=wgm-refunds screen
*
* wp-hook wp_ajax_$action (wp_ajax_sevdesk_woocommerce_edit_shop_order_refund)
* @return exit();
*/
function sevdesk_woocommerce_edit_shop_order_ajax_refund() {

	if ( check_ajax_referer( 'sevdesk_woocommerce_edit_shop_order_script', 'security', false ) ) {
		
		// get refund
		$refund_id = $_REQUEST[ 'refund_id' ];
		$refund = wc_get_order( $refund_id );

		// api
		$response = sevdesk_woocommerce_api_send_refund( $refund );

		// echo response
		echo apply_filters( 'sevdesk_woocommerce_edit_shop_order_ajax_api', $response, $refund_id );
	

	} else {
		echo "<b>ERROR: </b>" . __( 'Ajax nonce check failed.', 'woocommerce-german-market' );
	}

	exit();

}

