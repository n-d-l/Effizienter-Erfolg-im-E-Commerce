<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Output Icon
*
* wp-hook woocommerce_admin_order_actions_end
* @param WC_Order $order
* @return void
*/
function sevdesk_woocommerce_edit_shop_order_icon( $order ) {
	
	// is bulk transmission scheduled?
	$is_scheduled = $order->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );
	if ( ! empty( $is_scheduled ) ) {
		return;
	}

	// manual order confirmation
	if ( get_option( 'woocommerce_de_manual_order_confirmation' ) == 'on' ) {
		if ( $order->get_meta( '_gm_needs_conirmation' ) === 'yes' ) {
			return;
		}
	}

	$sevdesk_voucher_id = $order->get_meta( '_sevdesk_woocomerce_has_transmission' );

	// has transmission?
	$has_transmission = $sevdesk_voucher_id != '';

	// is voucher still available?
	$is_valid = true;
	if ( $has_transmission ) {
		if ( get_option( 'woocommerce_de_sevdesk_backend_sync', 'on' ) == 'on' ) {
			$is_valid = sevdesk_woocommerce_api_get_vouchers_status( $sevdesk_voucher_id );
		}
	}
	
	// if not, remove post meta
	if ( ! $is_valid ) {
		$order->delete_meta_data( '_sevdesk_woocomerce_has_transmission' );
		$order->save_meta_data();
		$has_transmission = false;
	}
	
	// load correct icon ()
	$classes = ( $has_transmission ) ? 'sevdesk-woocommerce-yes' : 'sevdesk-woocommerce-x';
	$classes = apply_filters( 'sevdesk_woocommerce_edit_shop_order_icon_classes', $classes, $has_transmission, $order );

	// markup
	?><a class="button sevdesk-woocomerce-default <?php echo $classes; ?>" data-order-id="<?php echo $order->get_id(); ?>" title="<?php echo __( 'sevDesk', 'woocommerce-german-market' ); ?>"></a><?php
}

/**
* adds a small download button to the admin page for refunds
*
* @since WGM 3.0
* @access public
* @static 
* @hook wgm_refunds_actions
* @param String $string
* @param shop_order_refund $refund
* @return String
*/
function sevdesk_woocommerce_edit_refund_icon( $actions, $refund ) {

	// is bulk transmission scheduled?
	$is_scheduled = $refund->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );
	if ( ! empty( $is_scheduled ) ) {
		return $actions;
	}

	if ( isset( $_REQUEST[ 'transmit-to-sevdesk' ] ) && isset( $_REQUEST[ 'refunds' ] ) ) {
		if ( is_array( $_REQUEST[ 'refunds' ] ) ) {
			if ( in_array( $refund->get_id(), $_REQUEST[ 'refunds' ] ) ) {
				return $actions;
			}
		}
	}
	
	$sevdesk_voucher_id = intval( $refund->get_meta( '_sevdesk_woocomerce_has_transmission' ) );

	// has transmission?
	$has_transmission = $sevdesk_voucher_id != 0;

	// is voucher still available?
	$is_valid = true;
	if ( $has_transmission ) {
		if ( get_option( 'woocommerce_de_sevdesk_backend_sync', 'on' ) == 'on' ) {
			$is_valid = sevdesk_woocommerce_api_get_vouchers_status( $sevdesk_voucher_id );
		}
	}

	// if not, remove post meta
	if ( ! $is_valid ) {
		$refund->delete_meta_data( '_sevdesk_woocomerce_has_transmission' );
		$refund->save_meta_data();
		$has_transmission = false;
	}

	// load correct icon ()
	$classes = ( $has_transmission ) ? 'sevdesk-woocommerce-yes' : 'sevdesk-woocommerce-x';
	$classes = apply_filters( 'sevdesk_woocommerce_edit_shop_order_icon_classes_refund', $classes, $has_transmission, $refund );

	$actions[ 'sevdesk' ] = array(
		'name' 	=> '',
		'class' => 'sevdesk-woocomerce-default sevdesk-refund ' . $classes,
		'data'	=> array(
						'order-id' => $refund->get_parent_id(),
						'refund-id'=> $refund->get_id()
					),
		'name'	=> __( 'Send refund data to sevDesk', 'woocommerce-german-market' )
	);

	return $actions;
}

/**
* Enqueue scripts and styles
*
* wp-hook admin_enqueue_scripts
* @return void
*/
function sevdesk_woocommerce_edit_shop_order_styles_and_scripts() {

	// set directories
	$assets_dir 	= untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets';
	$styles_dir 	= $assets_dir . '/styles';
	$scripts_dir	= $assets_dir . '/scripts';

	// script debug
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

	// enqueue style
	wp_enqueue_style( 'sevdesk_woocommerce_edit_shop_order_style', $styles_dir . '/edit-shop-order.' . $min . 'css', array(), '0.1' );

	// enqueue script
	wp_enqueue_script( 'sevdesk_woocommerce_edit_shop_order_script', $scripts_dir . '/edit-shop-order.' . $min . 'js', array( 'jquery' ), '0.1' );

	// localize script for ajax
	wp_localize_script( 'sevdesk_woocommerce_edit_shop_order_script', 'sevdesk_ajax', 
		array(
			'url' 	=> admin_url( 'admin-ajax.php' ),
			'nonce'	=> wp_create_nonce( 'sevdesk_woocommerce_edit_shop_order_script' ),
		)
	);

}
