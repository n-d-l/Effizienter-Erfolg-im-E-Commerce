<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Backend_Output_Post' ) ) {

	/**
	* output on post.php
	*
	* @class WP_WC_Running_Invoice_Number_Backend_Output_Post
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Backend_Output_Post {
		
		
		/**
		* add 'Invoice Number' and 'Invoice Date' to order data
		*
		* @since 0.0.1
		* @access public
		* @static		
		* @arguments WC_Order $order
		* @hook woocommerce_admin_order_data_after_order_details
		* @return void
		*/
		public static function order_data_after_order_details( $order ) {
	
			// output if this is no 'new' order		
			if ( get_current_screen()->action != 'add' ) {
				
				$invoice_number = get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', true );
				if ( empty( $invoice_number ) ) {
					delete_post_meta( $order->get_id(), '_wp_wc_running_invoice_number' );
				}	
				$invoice_date	= get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', true );
				$invoice_date	= ( $invoice_date == '' ) ? '' : date_i18n( 'Y-m-d', $invoice_date );

				?>
				<p class="form-field form-field-wide">
					<label for="order_invoice_number"><?php echo __( 'Invoice Number', 'woocommerce-german-market' ) ?>:</label>
					<input type="text" name="order_invoice_number" id="order_invoice_number" value="<?php echo $invoice_number; ?>"<?php echo ( $invoice_number != '' ) ? ' readonly' : ''; ?> />
                    
                    <?php
                    if ( apply_filters( 'german_market_invoice_number_edit_number_in_order', true, $order ) ) { 
						$style_display = ( $invoice_number == '' ) ? 'none' : 'inline-block';
						?>
						<a class="wp_wc_invoice_remove_read_only" id="wp_wc_invoice_remove_read_only" style="text-decoration: underline; cursor: pointer; display: <?php echo $style_display; ?>"><?php echo __( 'Edit invoice number', 'woocommerce-german-market' ); ?></a>

						<span class="wp_wc_invoice_remove_read_only_sep_delete" style="display: <?php echo $style_display; ?>">|</span>

						<a class="wp_wc_invoice_number_delete" id="wp_wc_invoice_number_delete" data-order-id="<?php echo $order->get_id(); ?>" style="text-decoration: underline; cursor: pointer; display: <?php echo $style_display; ?>"><?php echo __( 'Delete invoice number', 'woocommerce-german-market' ); ?></a><?php
					}
					?>
				</p>			
                <p class="form-field form-field-wide">
					<label for="order_invoice_date"><?php echo __( 'Invoice Date', 'woocommerce-german-market' ) ?>:</label>
					<input type="text" class="date-picker-field" name="order_invoice_date" id="order_invoice_date" value="<?php echo $invoice_date; ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					<?php    
					if ( $invoice_number == '' && apply_filters( 'german_market_invoice_number_edit_number_in_order', true, $order ) ) {
						?><br /><a name="<?php echo $order->get_id(); ?>" class="wp_wc_invoice_generate" style="text-decoration: underline; cursor: pointer;"><?php echo __( 'Generate and save invoice number and invoice date', 'woocommerce-german-market' ); ?></a><?php
					}
					?>
				</p>
				<?php
			
			// output if it is a new order
			} else {
				
				if ( apply_filters( 'german_market_invoice_number_edit_number_in_order', true, $order ) ) { 
					$checked = get_option( 'wp_wc_running_invoice_number_generate_when_order_is_created', 'off' );
					$checked_attribute = ( $checked == 'on' ) ? ' checked="checked" ' : '';
					
					?>
	                <p class="form-field form-field-wide">
	                	<input type="checkbox" id="order_generate_invoice" name="order_generate_invoice"<?php echo $checked_attribute; ?>style="width: auto; float: left;"/>
	                    <label for="order_generate_invoice"><?php echo __( 'Generate invoice number and invoice date when saving the order', 'woocommerce-german-market' ); ?></label>
	                </p>
	                <?php
	            }
			}
		}
		
		/**
		* generate and save invoice number and invoice date when clicked on generate
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_ajax_wp_wc_running_invoice_number_ajax_backend_post
		* @return void
		*/
		public static function post_ajax() {
			if ( check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security' ) ) {
				global $wp_locale;
				$order_id 				= absint( $_REQUEST[ 'order_id' ] );
				$order 					= wc_get_order( $_REQUEST[ 'order_id' ] );
				$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );	
				echo $running_invoice_number->get_invoice_number() . '[[SEPARATOR]]' . date_i18n( 'Y-m-d', intval( $running_invoice_number->get_invoice_timestamp() ) );	
				exit();
			}
		}

		/**
		* delete invoice number when clicked
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_ajax_wp_wc_running_invoice_number_delete
		* @return void
		*/
		public static function delete_invoice_number() {
			if ( check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security' ) ) {
				
				$order_id = absint( $_REQUEST[ 'order_id' ] );
				delete_post_meta( $_REQUEST[ 'order_id' ], '_wp_wc_running_invoice_number' );

				$logging = apply_filters( 'german_market_invoice_number_logging', false );
				if ( $logging ) {			
					$new_log = sprintf( 'Order: %s, Manual Deleted Invoice Number.', $_REQUEST[ 'order_id' ] );
					$logger = wc_get_logger();
					$context = array( 'source' => 'german-market-invoice-number' );
					$logger->info( $new_log, $context );
				}

				echo 'SUCCESS';	
				exit();
			}
		}
		
		/**
		* save meta data
		*
		* @since 0.0.1
		* @access public
		* @static		
		* @hook woocommerce_process_shop_order_meta
		* @return void
		*/
		public static function save_meta_data( $post_id, $post ) {

			// if it's a new order	
			if ( isset( $_REQUEST[ 'order_generate_invoice' ] ) ) {
				$order 					= new WC_Order( $post_id );
				$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );	
			} else {

				if ( isset( $_REQUEST[ 'wc_order_action' ] ) && $_REQUEST[ 'wc_order_action' ] == 'wp_wc_invoice_pdf_invoice' ) {
					return;
				}

				// invoice number
				if ( isset( $_REQUEST[ 'order_invoice_number' ] ) ) {

					if ( ! empty( trim( $_REQUEST[ 'order_invoice_number' ] ) ) ) {
						update_post_meta( $post->ID, '_wp_wc_running_invoice_number', $_REQUEST[ 'order_invoice_number' ] );

						$logging = apply_filters( 'german_market_invoice_number_logging', false );
						if ( $logging ) {
							$current_number = get_post_meta( $post->ID, '_wp_wc_running_invoice_number', true );
							if ( $current_number != $_REQUEST[ 'order_invoice_number' ] ) {
								$new_log = sprintf( 'Order: %s, Manual Saved Invoice Number Meta from %s to %s.', $post->ID, $current_number, $_REQUEST[ 'order_invoice_number' ] );
								$logger = wc_get_logger();
								$context = array( 'source' => 'german-market-invoice-number' );
								$logger->info( $new_log, $context );
							}
						}
					}
				}

				// invoice date
				if ( isset( $_REQUEST[ 'order_invoice_date' ] ) ) {
					
					if ( empty( $_REQUEST[ 'order_invoice_date' ] ) ) {
						delete_post_meta( $post->ID, '_wp_wc_running_invoice_number_date' );
					
					} else {

						$current_date 			= get_post_meta( $post_id, '_wp_wc_running_invoice_number_date', true );
						$update_invoice_date 	= true;

						if ( ! empty( $current_date ) ) {
							if ( date_i18n( 'Y-m-d', $current_date ) === $_REQUEST[ 'order_invoice_date' ] ) {
								$update_invoice_date = false;
							}
						}
						if ( $update_invoice_date ) {
							update_post_meta( $post->ID, '_wp_wc_running_invoice_number_date', strtotime( $_REQUEST[ 'order_invoice_date' ] . ' ' . current_time( 'H:i' ) ) );	
						}
					}
				}
			}
		}

		/**
		*
		* Show refund number in admin order
		*
		* @since 3.11.1.9
		* @wp-hook woocommerce_after_order_refund_item_name
		* @param WC_Order_Refund $refund
		* @return void
		*/
		public static function show_refund_invoice_number_in_order_refunds( $refund ) {
			if ( is_object( $refund ) && method_exists( $refund, 'get_meta' ) ) {
				$refund_number = $refund->get_meta( '_wp_wc_running_invoice_number' );
				if ( ! empty( $refund_number ) ) {
					echo apply_filters( 'german_market_admin_refund_number_after_order_refund_item_name', 
						sprintf(
							'<p class="german-market-refund-invoice-number description">%s</div>',
							$refund_number
						),
						$refund_number,
						$refund
					);
				}
			}
			
		}
		
	} // end class
	
} // end if class exists 
