<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Invoice_Pdf' ) ) {
	
	/**
	* Invoice PDF: Replace Filenames and Subjects
	*
	* @WP_WC_Running_Invoice_Number_Invoice_Pdf
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Invoice_Pdf {
		
		/**
		* Welcome Text Refund
		*
		* @since 3.8.2
		* @arguments string $welcome_text, WC_Order $order
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_backend_filename
		* @return string
		*/
		public static function extra_texts( $welcome_text, $order ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{refund-number}}', '{{refund-id}}', '{{refund-date}}' );
			return self::replace( $welcome_text, $order, $search );
		}
		
		/**
		* get invoice pdf backend filename
		*
		* @since 0.0.1
		* @arguments string $filename, WC_Order $order
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_backend_filename
		* @return string
		*/
		public static function get_backend_filename( $filename, $order ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_file_name_backend', __( 'Invoice-{{invoice-number}}-Order-{{order-number}}', 'woocommerce-german-market' ) ), $order, $search );			
		}
		
		/**
		* get invoice pdf frontend filename
		*
		* @since 0.0.1
		* @arguments string $filename, WC_Order $order
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_frontend_filename
		* @return string
		*/
		public static function get_frontend_filename( $filename, $order ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_file_name_frontend', __( 'Invoice-{{invoice-number}}', 'woocommerce-german-market' ) ), $order, $search );
		}
		
		/**
		* get invoice pdf subject
		*
		* @since 0.0.1
		* @arguments string $subject, WC_Order $order
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_subject
		* @return string
		*/
		public static function get_subject( $subject, $order ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{site-title}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_subject', __( 'Invoice {{invoice-number}}', 'woocommerce-german-market' ) ), $order, $search );
		}
		
		/**
		* get invoice date
		*
		* @since 0.0.1
		* @arguments string $subject, WC_Order $order
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_invoice_date
		* @return string
		*/
		public static function get_invoice_date( $invoice_date, $order ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_date', __( 'Invoice Date<br />{{invoice-date}}', 'woocommerce-german-market' ) ), $order, $search );
		}
		
		/**
		* get refund backend filename
		*
		* @since WGM 3.0
		* @param String $subject
		* @param WC_Order_Refund $refund
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_refund_backend_filename
		* @return string
		*/
		public static function get_backend_filename_refund( $filename, $refund ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{refund-number}}', '{{refund-id}}', '{{refund-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_file_name_backend_refund', __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' ) ), $refund, $search );
		}

		/**
		* get refund frontend filename
		*
		* @since WGM 3.0
		* @param String $subject
		* @param WC_Order_Refund $refund
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_refund_frontend_filename
		* @return string
		*/
		public static function get_frontend_filename_refund( $filename, $refund ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{refund-number}}', '{{refund-id}}', '{{refund-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_file_name_backend_refund', __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' ) ), $refund, $search );
		}

		/**
		* get refund frontend filename
		*
		* @since WGM 3.0
		* @param String $subject
		* @param WC_Order_Refund $refund
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_subject_line_1
		* @return string
		*/
		public static function get_subject_refund_line_1( $subject_line, $refund ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{refund-number}}', '{{refund-id}}', '{{refund-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_refund_start_subject_big', __( 'Refund {{refund-number}}', 'woocommerce-german-market' ) ), $refund, $search );
		}

		/**
		* get refund frontend filename
		*
		* @since WGM 3.0
		* @param String $subject
		* @param WC_Order_Refund $refund
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_subject_line_2
		* @return string
		*/
		public static function get_subject_refund_line_2( $subject_line, $refund ) {
			$search 		= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{refund-number}}', '{{refund-id}}', '{{refund-date}}' );
			return self::replace( get_option( 'wp_wc_running_invoice_pdf_refund_start_subject_small', __( 'For order {{order-number}}', 'woocommerce-german-market' ) ), $refund, $search );
		}

		/**
		* replace placeholders
		*
		* @since 0.0.1
		* @arguments string $filename_or_subject, WC_Order $order
		* @access public
		* @static
		* @return string
		*/
		private static function replace( $filename_or_subject, $order, $search ) {
			
			global $wp_locale;
			$return_value = $filename_or_subject;

			// Create invoice number if done yet
			$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );

			if ( $order == 'test'|| ! WGM_Helper::method_exists( $order, 'get_id' ) ) {
				
				$invoice_number_test = get_option( 'wp_wc_running_invoice_number_prefix', '' ) . str_pad( rand( 100, 99999 ), absint( get_option( 'wp_wc_running_invoice_number_digits', 0 ) ), '0', STR_PAD_LEFT ) . get_option( 'wp_wc_running_invoice_number_suffix', '' );
				$replace = array( rand( 100, 99999), date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ), $invoice_number_test, date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );

				
				$return_value = str_replace( $search, $replace, $filename_or_subject );
			
			} else {
				
				if ( WGM_Helper::method_exists( $order, 'get_type' ) ) {

					if ( $order->get_type() == 'shop_order' || $order->get_type() == 'shop_order_refund' ) {

						if ( $order->get_type() == 'shop_order_refund' ) {
							
							$refund = $order;
							$order = wc_get_order( $order->get_parent_id() );
							$replace = array( $order->get_order_number(), date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ), get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', true ), date_i18n( get_option( 'date_format' ), intval( get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', true ) ) ), get_post_meta( $refund->get_id(), '_wp_wc_running_invoice_number', true ), $refund->get_id(), date_i18n( get_option( 'date_format' ), $refund->get_date_created()->getTimestamp() ) );

						} else {
							
							$replace = array( $order->get_order_number(), date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ), get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', true ), date_i18n( get_option( 'date_format' ), intval(get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', true ) ) ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );

						}
						
						// payment method
						$payment = wc_get_payment_gateway_by_order( $order );
						if ( $payment ) {
							$filename_or_subject = str_replace( '{{payment-method}}', $payment->title, $filename_or_subject );
						} else {
							$filename_or_subject = str_replace( '{{payment-method}}', '', $filename_or_subject );
						}

						$return_value = str_replace( $search, $replace, $filename_or_subject );

						// custom filtered placeholders for invoice pdf
						$search 	= array();
						$replace 	= array(); 
						$custom_filtered_invoice_pdf_placeholders =  apply_filters( 'wp_wc_invoice_pdf_placeholders', array() );
						
						if ( ! empty( $custom_filtered_invoice_pdf_placeholders ) ) {
							foreach ( $custom_filtered_invoice_pdf_placeholders as $placeholder_key => $placeholder_value ) {
								$search[] = '{{' . $placeholder_key . '}}';
								$replace[] = apply_filters( 'wp_wc_invoice_pdf_placeholder_' . $placeholder_key, $placeholder_value, $placeholder_key, $order );
							}

							$return_value = str_replace( $search, $replace, $return_value );
						}

					}

				}
				
			}

			return apply_filters( 'wp_wc_invoice_pdf_replace_return', $return_value, $order );
		}
		
		/**
		* when this function is called by do_action the invoice will be attached to an email, so we have to be sure invoice number and date have been created
		*
		* @since 0.0.1
		* @arguments string $status, WC_Order $order
		* @access public
		* @hook wp_wc_invoice_before_adding_attachment
		* @static
		* @return string
		*/
		public static function before_adding_attachment( $status, $order ) {
			
			if ( is_a( $order, 'WC_Order_Refund' ) ) {
				$refund = $order;
				$order = wc_get_order( $order->get_parent_id() );
				$running_refund_number = new WP_WC_Running_Invoice_Number_Functions( $refund );	
			}

			$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );

		}
		
		
	} // end class
	
} // end if

?>