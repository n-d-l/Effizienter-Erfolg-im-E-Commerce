<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Online_Bookkeeping' ) ) {
	
	class WP_WC_Running_Invoice_Number_Online_Bookkeeping {
		
		/**
		* lexoffice Support: Send invoice number as voucher number
		*
		* @since 3.5.2
		* @access public
		* wp-hook lexoffice_woocommerce_api_order_voucher_number
		* @param String $voucher_number
		* @param WC_Order $order_or_refund
		* @return String
		*/
		public static function lexoffice_voucher_number( $voucher_number, $order_or_refund ) {

			if ( get_option( 'woocommerce_de_lexoffice_voucher_number', 'order_number' ) == 'invoice_number' ) {

				$invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order_or_refund );
				$voucher_number = $invoice_number->get_invoice_number();

			}

			return $voucher_number;

		}

		/**
		* lexoffice Support: Send invoice date as voucher date
		*
		* @since 3.6
		* @access public
		* wp-hook lexoffice_woocommerce_api_order_voucher_date, sevdesk_woocommerce_api_voucher_date
		* @param String $voucher_date
		* @param WC_Order $order_or_refund
		* @return String
		*/
		public static function lexoffice_sevdesk_voucher_date( $voucher_date, $order_or_refund ) {

			$option = 'order_date';

			if ( current_filter() == 'lexoffice_woocommerce_api_order_voucher_date' ) {
				$option = get_option( 'woocommerce_de_lexoffice_voucher_date', 'order_date' );
			} else if ( current_filter() == 'sevdesk_woocommerce_api_voucher_date' ) {
				$option = get_option( 'woocommerce_de_sevdesk_voucher_date', 'order_number' );
			}

			if ( $option == 'invoice_date' ) {

				$invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order_or_refund );
				$voucher_date 	= date( 'Y-m-d', $invoice_number->get_invoice_timestamp() );

			}

			return $voucher_date;

		}

		/**
		* sevDesk and 1&1 Online-Buchhaltung Support: Send invoice number as voucher number
		*
		* @since 3.5.2
		* @access public
		* wp-hook sevdesk_woocommerce_api_voucher_description
		* @param String $string
		* @param Array $args
		* @return String
		*/
		public static function sevdesk_voucher_number( $string, $args ) {

			if ( current_filter() == 'sevdesk_woocommerce_api_voucher_description' ) {

				$order_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $args[ 'order' ] );

				if ( isset( $args[ 'refund' ] ) ) {

					$refund_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $args[ 'refund' ] );
					$string = str_replace(

								array(
									'{{invoice-number}}',
									'{{refund-number}}',
								),

								array(
									$order_invoice_number->get_invoice_number(),
									$refund_invoice_number->get_invoice_number(),
								),

								$string

						); 

				} else {

					$string = str_replace( '{{invoice-number}}', $order_invoice_number->get_invoice_number(), $string );
				}


			}
			
			return $string;

		}

	} // end class
	
} // end if
