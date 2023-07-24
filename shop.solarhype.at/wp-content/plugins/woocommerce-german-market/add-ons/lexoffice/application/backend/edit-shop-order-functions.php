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
function lexoffice_woocommerce_edit_shop_order_icon( $order ) {
	
	if ( apply_filters( 'lexoffice_woocommerce_edit_shop_order_icon_return', false, $order ) ) {
		return ;
	}

	// is bulk transmission scheduled?
	$is_scheduled = $order->get_meta( '_lexoffice_woocomerce_scheduled_for_transmission' );
	if ( ! empty( $is_scheduled ) ) {
		return;
	}

	// manual order confirmation
	if ( get_option( 'woocommerce_de_manual_order_confirmation' ) == 'on' ) {
		if ( $order->get_meta( '_gm_needs_conirmation' ) === 'yes' ) {
			return;
		}
	}

	$lexoffice_voucher_id = $order->get_meta( '_lexoffice_woocomerce_has_transmission' );

	// has transmission?
	$has_transmission = $lexoffice_voucher_id != '';

	// is voucher still available?
	$is_valid = true;

	// order status
	if ( ! $has_transmission ) {
		$completed_class = ( $order->get_status() != 'completed' ) ? ' lexoffice-not-completed' : '';

		if ( apply_filters( 'woocommerce_de_lexoffice_force_transmission_even_if_not_completed', false, $order ) ) {
			$completed_class = '';
		}
		
	}
	
	// load correct icon ()
	$classes = ( $has_transmission ) ? 'lexoffice-woocommerce-yes dashicons dashicons-yes' : 'lexoffice-woocommerce-x' . $completed_class;
	$classes = apply_filters( 'lexoffice_woocommerce_edit_shop_order_icon_classes', $classes, $has_transmission, $order );

	// markup
	?><a class="button lexoffice-woocomerce-default <?php echo $classes; ?>" data-order-id="<?php echo $order->get_id(); ?>" title="<?php echo __( 'Lexoffice', 'woocommerce-german-market' ); ?>"></a><?php
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
function lexoffice_woocommerce_edit_refund_icon( $actions, $refund ) {

	// is bulk transmission scheduled?
	$is_scheduled = $refund->get_meta( '_lexoffice_woocomerce_scheduled_for_transmission' );
	if ( ! empty( $is_scheduled ) ) {
		return $actions;
	}

	if ( isset( $_REQUEST[ 'transmit-to-lexoffice' ] ) && isset( $_REQUEST[ 'refunds' ] ) ) {
		if ( is_array( $_REQUEST[ 'refunds' ] ) ) {
			if ( in_array( $refund->get_id(), $_REQUEST[ 'refunds' ] ) ) {
				return $actions;
			}
		}
	}
	
	$lexoffice_voucher_id = $refund->get_meta( '_lexoffice_woocomerce_has_transmission' );

	// has transmission?
	$has_transmission = $lexoffice_voucher_id != '';

	// is voucher still available?
	$is_valid = true;

	// load correct icon ()
	$classes = ( $has_transmission ) ? 'lexoffice-woocommerce-yes dashicons dashicons-yes' : 'lexoffice-woocommerce-x';
	$classes = apply_filters( 'lexoffice_woocommerce_edit_shop_order_icon_classes_refund', $classes, $has_transmission, $refund );
	$name = ( $has_transmission ) ? '' : __( 'Send refund data to lexoffice', 'woocommerce-german-market' );

	$actions[ 'lexoffice' ] = array(
		'class' => 'lexoffice-woocomerce-default lexoffice-refund ' . $classes,
		'data'	=> array(
						'order-id' => $refund->get_parent_id(),
						'refund-id'=> $refund->get_id()
					),
		'name'	=> $name
	);

	return $actions;
}

/**
* Enqueue scripts and styles
*
* wp-hook admin_enqueue_scripts
* @return void
*/
function lexoffice_woocommerce_edit_shop_order_styles_and_scripts() {

	// set directories
	$assets_dir 	= untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets';
	$styles_dir 	= $assets_dir . '/styles';
	$scripts_dir	= $assets_dir . '/scripts';

	// script debug
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

	// enqueue style
	wp_enqueue_style( 'lexoffice_woocommerce_edit_shop_order_style', $styles_dir . '/edit-shop-order.' . $min . 'css', array(), '0.1' );

	// enqueue script
	wp_enqueue_script( 'lexoffice_woocommerce_edit_shop_order_script', $scripts_dir . '/edit-shop-order.' . $min . 'js', array( 'jquery' ), '0.1' );

	// localize script for ajax
	wp_localize_script( 'lexoffice_woocommerce_edit_shop_order_script', 'lexoffice_ajax', 
		array(
			'url' 	=> admin_url( 'admin-ajax.php' ),
			'nonce'	=> wp_create_nonce( 'lexoffice_woocommerce_edit_shop_order_script' )
		)
	);

}
