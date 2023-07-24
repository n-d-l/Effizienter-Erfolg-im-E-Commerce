<?php
/**
 * Customer confirm order email
 *
 * @author        MarketPress
 * @package       WooCommerce_German_Market
 * @version       2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo apply_filters(
	     'wgm_customer_received_order_email_text',
	     str_replace(
					array( '{first-name}', '{last-name}' ),
					array( $order->get_billing_first_name(), $order->get_billing_last_name() ),
					get_option( 'gm_order_confirmation_mail_text', __( 'With this e-mail we confirm that we have received your order. However, this is not a legally binding offer until payment is received.', 'woocommerce-german-market' ) )
				)
     ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

echo strtoupper( sprintf( __( 'Order number: %s', 'woocommerce-german-market' ), $order->get_order_number() ) ) . "\n";
echo date_i18n( __( 'jS F Y', 'woocommerce-german-market' ), $order->get_date_created()->getTimestamp() ) . "\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
echo "\n";

if ( WGM_Helper::woocommerce_version_check() ) {
	
	if ( function_exists( 'wc_get_email_order_items' ) ) {

		// WC 2.7
		echo wc_get_email_order_items( $order, array(
		                                      'show_sku'    => FALSE,
		                                      'show_image'  => FALSE,
		                                      '$image_size' => array( 32, 32 ),
		                                      'plain_text'  => $plain_text
	                                      ) );
	} else {
		echo $order->email_order_items_table( array(
		                                      'show_sku'    => FALSE,
		                                      'show_image'  => FALSE,
		                                      '$image_size' => array( 32, 32 ),
		                                      'plain_text'  => $plain_text
	                                      ) );
	}

} else {
	/**
	 * Deprecated since 2.5
	 */
	echo $order->email_order_items_table( $order->is_download_permitted(), TRUE,
	                                      $order->has_status( 'processing' ), '', '', TRUE  );
}

echo "==========\n\n";

if ( $totals = $order->get_order_item_totals() ) {
	foreach ( $totals as $total ) {
		echo $total[ 'label' ] . "\t " . $total[ 'value' ] . "\n";
	}
}

if ( $order->get_customer_note() ) {
	echo esc_html__( 'Note:', 'woocommerce-german-market' ) . "\t " . wp_kses_post( wptexturize( $order->get_customer_note() ) ) . "\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
