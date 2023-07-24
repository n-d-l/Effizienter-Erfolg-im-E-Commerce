<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Return_Delivery_Pdf' ) ) {
	
	/**
	* Return Delivery PDF: Replace Small Headline
	*
	* @WP_WC_Running_Invoice_Number_Invoice_Pdf
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Return_Delivery_Pdf {	

		/**
		* Replace Small Headline - Backend Placeholders
		*
		* @wp-hook wcreapdf_pdf_placeholders_backend_string
		* @param String $string
		* @return $string
		**/
		public static function wcreapdf_pdf_placeholders_backend_string( $string ) {
			
			if ( apply_filters( 'wp_wc_running_invoice_number_support_return_delivery', true ) ) {
				return $string . __( ', Invoice Number: <code>{{invoice-number}}</code>, Invoice Date: <code>{{invoice-date}}</code>', 'woocommerce-german-market' );
			} else { // user can disable support for delivery pdf
				return $string;
			}

		}

		/**
		* Replace Small Headline - Frontend Replace
		*
		* @wp-hook wcreapdf_pdf_placeholders_frontend_string
		* @param String $string
		* @return $string
		**/
		public static function wcreapdf_pdf_placeholders_frontend_string( $string, $order = NULL ) {

			// user can disable support for delivery pdf
			if ( ! apply_filters( 'wp_wc_running_invoice_number_support_return_delivery', true ) ) {
				return $string;
			}

			$search = array( '{{invoice-number}}', '{{invoice-date}}' );

			// only do replacement if necceassary
			$necceassary = false;

			foreach ( $search as $search_placeholder ) {
				if ( str_replace( $search_placeholder, '', $string ) != $string ) {
					$necceassary = true;
					break;
				}
			}

			if ( $necceassary ) {
				if ( $order ) {
					$invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );
					$replace = array( $invoice_number->get_invoice_number(), $invoice_number->get_invoice_date() );
				} else {
					$replace = array( utf8_decode( rand( 1, 99 ) ), date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) );
				}

				$string = str_replace( $search, $replace, $string );
			}

			return $string;

		}
		
	} // end class
	
} // end if
