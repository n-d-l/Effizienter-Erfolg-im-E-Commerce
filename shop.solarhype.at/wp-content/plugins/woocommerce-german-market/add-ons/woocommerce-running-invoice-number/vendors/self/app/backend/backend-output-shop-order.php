<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order' ) ) {

	/**
	* output on shop_order
	*
	* @class WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order {
		
		/**
		* add column 'Invoice Number' to table at screen shop_order
		*
		* @since 0.0.1
		* @access public
		* @arguments Array $columns
		* @hook manage_edit-shop_order_columns
		* @return Array
		*/
		public static function shop_order_columns( $columns ) {

			if ( array_key_exists( 'order_number', $columns ) ) {
				// find 'order_title'
				$new_coloumns = array();
				$i = 0;
				foreach ( $columns as $column_key => $column_value ) {
					$i++;
					if ( $column_key == 'order_number' ) {
						break;	
					}
				}
				$rest_columns = array_splice( $columns, $i ) ;
				$columns = $columns + array( 'invoice_number' => __( 'Invoice Number', 'woocommerce-german-market' ) ) + $rest_columns;
			}

			return $columns;
		}
		
		/**
		* make the column 'Invoice Number' sortable at screen shop_order
		*
		* @since 0.0.1
		* @access public
		* @arguments Array $columns
		* @hook manage_edit-shop_order_sortable_columns
		* @return Array
		*/
		public static function shop_order_sortable_columns( $columns ) {
			$custom = array(
				'invoice_number' => 'running_invoice_number',
			);
			return wp_parse_args( $custom, $columns );
		}
		
		/**
		* render the content of column 'Invoice Number' at screen shop_order
		*
		* @since 0.0.1
		* @access public
		* @arguments Array $columns
		* @hook manage_shop_order_posts_custom_column
		* @return Array
		*/
		public static function render_shop_order_columns( $column ) {
			global $post;
			global $wp_locale;
			
			$invoice_number = get_post_meta( $post->ID, '_wp_wc_running_invoice_number', true );

			if ( strlen( $invoice_number ) > 500 ) { // issue #1732
				delete_post_meta( $post->ID, '_wp_wc_running_invoice_number' );
				delete_post_meta( $post->ID, '_wp_wc_running_invoice_number_date' );
			}
			
			switch ( $column ) {
				case 'invoice_number' :
					?><div id="invoice_number_<?php echo $post->ID; ?>"><?php
					if ( $invoice_number == '' && apply_filters( 'wp_wc_running_invoice_number_backend_output_shop_order_render_shop_order_columns', true, $post->ID ) ) {
						
						?><a class="wp_wc_invoice_generate_orders_page generate" data-order-id="<?php echo $post->ID;?>"><?php echo __( 'Generate Invoice Number', 'woocommerce-german-market' ); ?></a><?php

					} else {
						echo $invoice_number;
						$invoice_date = get_post_meta( $post->ID, '_wp_wc_running_invoice_number_date', true );
						if ( $invoice_date != '' ) {
							?><br /><?php
							echo date_i18n( get_option( 'date_format' ), intval( $invoice_date ) );
						}
					}
					?></div><?php
					break;
			}
		}
		
		/**
		* how to sort column invoice_number
		*
		* @since 0.0.1
		* @access public
		* @arguments $query
		* @hook pre_get_posts
		* @return void
		*/
		public static function shop_order_sort( $query ) {
			$orderby = $query->get( 'orderby');
			if ( $orderby == 'running_invoice_number' ) {
				$query->set( 'meta_key', '_wp_wc_running_invoice_number' );
				$query->set( 'orderby', 'meta_value' );
			}
		}
		
		/**
		* show invoice number in "WooCommerce -> Orders" after invoice pdf donwnload has been clicked
		*
		* @since 0.0.1
		* @access public
		* @hook wp_ajax_wp_wc_running_invoice_number_show_after_invoice_pdf_clicked
		* @return void
		*/
		public static function shop_order_ajax_after_invoice_pdf_clicked() {

			if ( check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security' ) ) {

				$order_id = '';

				// if clicked on download button from pdf invoice
				if ( isset( $_REQUEST[ 'href' ] ) ) {
					
					// get order_id from href. we build the href, so we can be sure that works
					$href = $_REQUEST[ 'href' ];
					$check_url = parse_url( $href );
					$check_url = explode( '&', $check_url[ 'query' ] );
					foreach ( $check_url as $query ) {
						$check = explode( '=', $query );
						if ( $check[ 0 ] == 'order_id' ) {
							$order_id = 	$check[ 1 ];
							break;
						}
					}
				}

				if ( $order_id !== '' ) {

					$order_number 	= get_post_meta( $order_id, '_wp_wc_running_invoice_number', true );
					$order_date 	= get_post_meta( $order_id, '_wp_wc_running_invoice_number_date', true );

					if ( ! empty( $order_number ) ) {
						echo $order_id . '[[SEPARATOR]]' . $order_number;
					}

					if ( ! empty( $order_date ) ) {
						echo '<br />' . date_i18n( get_option( 'date_format' ), ( intval( $order_date ) ) );
					}

				}
			}

			exit();
		}

		/**
		* update invoice number in shop_order if there was no invoice number before
		*
		* @since 0.0.1
		* @access public
		* @hook wp_ajax_wp_wc_running_invoice_number_ajax_backend_shop_order
		* @return void
		*/
		public static function shop_order_ajax() {
						
			if ( check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security' ) ) {

				$order_id 				= absint( $_REQUEST[ 'order_id' ] );
				$order 					= wc_get_order( $_REQUEST[ 'order_id' ] );
				$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );	

				echo $running_invoice_number->get_invoice_number();
				
				?><br /><?php
				echo date_i18n( get_option( 'date_format' ), intval( $running_invoice_number->get_invoice_timestamp() ) );

			} else {
				echo 'ERROR';
			}
			
			exit();
		}
		
		/**
		* include invoice number to search query
		*
		* @since 0.0.1
		* @access public
		* @hook pre_get_posts
		* @return void
		*/
		public static function search_query( $fields ) {
			$fields[] = '_wp_wc_running_invoice_number';			
			return $fields;
		}

		/**
		* add coloumn for WP_List refunds
		*
		* @since WGM 3.0
		* @access public
		* @hook wgm_refunds_backend_columns
		* @param Array $columns
		* @return Array
		*/
		public static function refund_columns( $columns ) {

			$new_columns = array();

			foreach ( $columns as $key => $value ) {

				$new_columns[ $key ] = $value;
				
				if ( $key == 'refund' ) {
					$new_columns[ 'refund_number' ] = __( 'Refund Number', 'woocommerce-german-market' );
				}

			}

			return $new_columns;

		}

		/**
		* add coloumn content for refund_number in WP_List refunds
		*
		* @since WGM 3.0
		* @access public
		* @hook wgm_refunds_array
		* @param Array $item
		* @return Array
		*/
		public static function refund_item( $item ) {
			
			$refund_id = str_replace( '#', '', $item[ 'refund' ] );
			$refund_number = get_post_meta( $refund_id, '_wp_wc_running_invoice_number', true );
			$item[ 'refund_number' ] = '<span id="refund-number-' . $refund_id . '">' . $refund_number . '</span><a href="#" class="edit-refund-number" id="edit-refund-number-a-' . $refund_id . '" data-refund-id="' . $refund_id . '"></a><input type="text" style="width: 100%; display:none;" data-refund-id="' . $refund_id . '" id="edit-refund-number-text-field-' . $refund_id . '" value="' . $refund_number . '"/><input type="button" id="edit-refund-number-button-' . $refund_id . '" class="button-secondary refund-number-save-button" style="display: none;" data-refund-id="' . $refund_id . '" value="' . __( 'Save', 'woocommerce-german-market' ) . '" />';

			return $item;

		}

		/**
		* Show refund number in refund list after pdf has been downloaded
		*
		* @since WGM 3.0
		* @access public
		* @hook wp_ajax_wp_wc_running_invoice_number_ajax_backend_post
		* @return void (exit)
		*/
		public static function ajax_show_refund_number() {

			if ( ! check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security', false ) ) {
				wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-invoice-pdf' ), '', array( 'response' => 403 ) );
			}

			$refund_id = $_REQUEST[ 'refund_id' ];
			$refund_number = get_post_meta( $refund_id, '_wp_wc_running_invoice_number', true );
			echo $refund_number;
			exit();

		}

		/**
		* Update refund number in refund list after save button has been pressed
		*
		* @since WGM 3.3.1
		* @access public
		* @hook wp_ajax_wp_wc_running_invoice_number_update_refund_number
		* @return void (exit)
		*/
		public static function ajax_update_refund_number() {

			if ( ! check_ajax_referer( 'wp_wc_running_invoice_number_nonce', 'security', false ) ) {
				wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-invoice-pdf' ), '', array( 'response' => 403 ) );
			}

			$refund_id = $_REQUEST[ 'refund_id' ];
			$refund_number = $_REQUEST[ 'new_refund_number' ];
			$refund = wc_get_order( $refund_id );

			$logging = apply_filters( 'german_market_invoice_number_logging', false );
			if ( $logging ) {
				$current_number = $refund->get_meta( '_wp_wc_running_invoice_number' ); 
				if ( $current_number != $refund_number ) {
					$new_log = sprintf( 'Refund: %s, Manual Saved Invoice Number Meta from %s to %s.', $refund_id, $current_number, $refund_number );
					$logger = wc_get_logger();
					$context = array( 'source' => 'german-market-invoice-number' );
					$logger->info( $new_log, $context );
				}
			}
			
			$refund->update_meta_data( '_wp_wc_running_invoice_number', $refund_number );
			$refund->save_meta_data();

			exit();

		}
		
	} // end class
	
} // end if class exists ?>