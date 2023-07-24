<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( get_option( 'woocommerce_de_sevdesk_automatic_completed_order', 'off' ) == 'on' ) {
	add_action( 'woocommerce_order_status_changed', 'sevdesk_woocommerce_status_completed', 10, 3 );
}

if ( get_option( 'woocommerce_de_sevdesk_automatic_refund', 'off' ) == 'on' ) {
	add_action( 'woocommerce_create_refund', 'sevdesk_woocommerce_create_refund', 10, 2 );
}

/**
* Send Voucher to sevdesk if order is marked as completed
*
* @since 	GM 3.7.1
* @wp-hook 	woocommerce_order_status_completed
* @param 	Integer $order_id
* @return 	void
*/
function sevdesk_woocommerce_status_completed( $order_id, $old_status, $new_status ) {

	// is bulk transmission scheduled?
	$order = wc_get_order( $order_id );
	if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {

		$is_scheduled = $order->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );
		if ( ! empty( $is_scheduled ) ) {
			return;
		}

		if ( ! class_exists( 'WP_WC_Invoice_Pdf_Create_Pdf' ) ) {
			return;
		}

		$sevdesk_voucher_id = $order->get_meta( '_sevdesk_woocomerce_has_transmission' );

		// has transmission?
		$has_transmission = $sevdesk_voucher_id != '';
		$is_valid = true;

		// is voucher still available?
		if ( $has_transmission ) {
			$is_valid = sevdesk_woocommerce_api_get_vouchers_status( $sevdesk_voucher_id );
		}

		// if not, remove post meta
		if ( ! $is_valid ) {
			$order->delete_meta_data( '_sevdesk_woocomerce_has_transmission' );
			$order->save_meta_data();
			$has_transmission = false;
		}

		if ( $has_transmission ) {
			return;
		}

		if ( $new_status == "completed" ) {
			$order = wc_get_order( $order_id );
			$response = sevdesk_woocomerce_api_send_order( $order, false );
		}
	}
}

/**
* Send Voucher to sevdesk if refund is created
*
* @since 	GM 3.7.1
* @wp-hook 	woocommerce_create_refund
* @param 	WC_Order_Refund $refund
* @param 	Array $args
* @return 	void
*/
function sevdesk_woocommerce_create_refund( $refund, $args ) {

	// is bulk transmission scheduled?
	$is_scheduled = $refund->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );
	if ( ! empty( $is_scheduled ) ) {
		return;
	}

	if ( ! class_exists( 'WP_WC_Invoice_Pdf_Create_Pdf' ) ) {
		return;
	}

	$response = sevdesk_woocommerce_api_send_refund( $refund, false );
}
