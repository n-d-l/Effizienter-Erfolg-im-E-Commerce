<?php
/**
 * Customer confirmation order email
 *
 * @author      MarketPress
 * @package     WooCommerce_German_Market
 * @version     2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$text_align = is_rtl() ? 'right' : 'left';

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php do_action( 'gm_before_email_customer_confirm_order', $order, $sent_to_admin, $plain_text ); ?>

<p><?php echo nl2br( apply_filters(
				'wgm_customer_received_order_email_text',
				str_replace(
					array( '{first-name}', '{last-name}' ),
					array( $order->get_billing_first_name(), $order->get_billing_last_name() ),
					get_option( 'gm_order_confirmation_mail_text', __( 'With this e-mail we confirm that we have received your order. However, this is not a legally binding offer until payment is received.', 'woocommerce-german-market' ) )
				)
			) ); ?></p>


<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<h2><?php printf( __( 'Order #%s', 'woocommerce-german-market' ), $order->get_order_number() ); ?></h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
		<tr>
			<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Product', 'woocommerce-german-market' ); ?></th>
			<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Quantity', 'woocommerce-german-market' ); ?></th>
			<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Price', 'woocommerce-german-market' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
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
			                                      $order->has_status( 'processing' ) );
		}
		?>
		</tbody>
		<tfoot>
		<?php
		if ( $totals = $order->get_order_item_totals() ) {
			$i = 0;
			foreach ( $totals as $total ) {
				$i++;
				?><tr>
				<th class="td" scope="row" colspan="2" style="text-align:<?php echo $text_align; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo $total['label']; ?></th>
				<td class="td" style="text-align:<?php echo $text_align; ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo $total['value']; ?></td>
				</tr><?php
			}
		}

		if ( $order->get_customer_note() ) {
			?>
			<tr>
				<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Note:', 'woocommerce-german-market' ); ?></th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
			</tr>
			<?php
		}

		?>
		</tfoot>
	</table>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_footer', $email  ); ?>
