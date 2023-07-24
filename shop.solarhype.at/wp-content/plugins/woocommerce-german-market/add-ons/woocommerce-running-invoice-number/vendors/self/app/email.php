<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Email' ) ) {
	
	/**
	* WoocCommerce Email Customer Invoice: Replace heading and subject
	*
	* @WP_WC_Invoice_Pdf_Helper
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Email {
		
		/**
		* get default heading
		*
		* @since 1.0.1
		* @access private
		* @static
		* @return string
		*/
		private static function get_default_heading() {
			return __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' );
		}

		/**
		* get default subject
		*
		* @since 1.0.1
		* @access private
		* @static
		* @return string
		*/
		private static function get_default_subject() {
			return __( 'Invoice {{invoice-number}} for order {{order-number}} from ({{order-date}})', 'woocommerce-german-market' );
		}

		/**
		* get email heading for customer invoice
		*
		* @since 0.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_heading_customer_invoice
		* @return string
		*/
		public static function get_heading( $heading, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_header', self::get_default_heading() ), 
				'wp_wc_running_invoice_email_header',
				$order
			);
			return self::replace( $raw_value, $order );			
		}
		
		/**
		* get email subject for customer invoice
		*
		* @since 0.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_subject_customer_invoice
		* @return string
		*/
		public static function get_subject( $subject, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_subject', self::get_default_heading() ), 
				'wp_wc_running_invoice_email_subject',
				$order
			);
			return self::replace( $raw_value, $order, true );
		}

		/**
		* get email heading for completed order
		*
		* @since 1.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_heading_customer_completed_order
		* @return string
		*/
		public static function get_heading_completed_order( $heading, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_completed_order_email_header', self::get_default_heading() ), 
				'wp_wc_running_invoice_completed_order_email_header',
				$order
			);
			return self::replace( $raw_value, $order );			
		}
		
		/**
		* get email subject for completed order
		*
		* @since 1.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_subject_customer_completed_order
		* @return string
		*/
		public static function get_subject_completed_order( $subject, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_completed_order_email_subject', self::get_default_heading() ), 
				'wp_wc_running_invoice_completed_order_email_subject',
				$order
			);
			return self::replace( $raw_value, $order, true );
		}
		
		/**
		* get email heading for customer invoice (paid)
		*
		* @since 0.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_heading_customer_invoice_paid
		* @return string
		*/
		public static function get_heading_paid( $heading, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_header_paid', self::get_default_heading() ), 
				'wp_wc_running_invoice_email_header_paid',
				$order
			);
			return self::replace( $raw_value, $order );			
		}
		
		/**
		* get email subject for customer invoice (paid)
		*
		* @since 0.0.1
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_subject_customer_invoice_paid
		* @return string
		*/
		public static function get_subject_paid( $subject, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_subject_paid', self::get_default_heading() ), 
				'wp_wc_running_invoice_email_subject_paid',
				$order
			);
			return self::replace( $raw_value, $order, true );
		}

		/**
		* trigger refunded order to save refund id as temporally post meta in order
		*
		* @since WGM 3.0
		* @access public
		* @static
		* @hook woocommerce_order_fully_refunded_notification
		* @hook woocommerce_order_partially_refunded_notification
		* @param int $order_id
	 	* @param int $refund_id
		* @return void
		*/
		public static function refunded_trigger( $order_id, $refund_id ) {
			update_post_meta( $order_id, '_wp_wc_running_invoice_refund_id_for_email', $refund_id );
		}

		/**
		* get email heading for refunded order
		*
		* @since WGM 3.0
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_heading_customer_refunded_order
		* @return string
		*/
		public static function get_heading_refunded_order( $heading, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_header_refunded', __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' ) ), 
				'wp_wc_running_invoice_email_header_refunded',
				$order
			);
			return self::replace_refunded( $raw_value, $order );
		}
		
		/**
		* get email subject for refunded order
		*
		* @since WGM 3.0
		* @arguments string $heading, WC_Order $order
		* @access public
		* @static
		* @hook woocommerce_email_subject_customer_refunded_order
		* @return string
		*/
		public static function get_subject_refunded_order( $subject, $order ) {
			$raw_value = apply_filters( 
				'wp_wc_running_invoice_email_before_replace', 
				get_option( 'wp_wc_running_invoice_email_subject_refunded', __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' ) ), 
				'wp_wc_running_invoice_email_subject_refunded',
				$order
			);
			return self::replace_refunded( $raw_value, $order, true );
		}
		
		/**
		* replace placeholders
		*
		* @since 0.0.1
		* @arguments string $heading_or_subject, WC_Order $order
		* @access public
		* @static
		* @return string
		*/
		private static function replace( $heading_or_subject, $order, $is_subject = false ) {
			
			global $wp_locale;

			if ( ! WGM_Helper::method_exists( $order, 'get_id' ) ) {
				return $heading_or_subject;
			}
			
			if ( ! apply_filters( 'wp_wc_running_invoice_number_replace_email_headings', true, $order ) ) {
				$search 	= array( '{{order-number}}', '{{order-date}}', '{{site-title}}' );
				$replace	= array( $order->get_order_number(), date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
				return str_replace( $search, $replace, $heading_or_subject );
			}
			
			// test if we have to create invoice number and invoice date
			$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );
			$invoice_number = $running_invoice_number->get_invoice_number();
			$invoice_date	= $running_invoice_number->get_invoice_timestamp();	
			
			$search 	= array( '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{site-title}}' );
			$replace	= array( $order->get_order_number(), date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ), $invoice_number, date_i18n( get_option( 'date_format' ), intval( $invoice_date ) ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
			$return_string =  str_replace( $search, $replace, $heading_or_subject );

			if ( $is_subject ) {
				return $return_string;
			}

			return $return_string;
		}

		/**
		* replace placeholders for refund
		*
		* @since 0.0.1
		* @arguments string $heading_or_subject, WC_Order_Refund $order
		* @access public
		* @static
		* @return string
		*/
		private static function replace_refunded( $heading_or_subject, $order, $is_subject = false ) {
			
			global $wp_locale;
			
			// get refund
			$refund_id = get_post_meta( $order->get_id(), '_wp_wc_running_invoice_refund_id_for_email', true );
			$refund = wc_get_order( $refund_id );

			if ( ! WGM_Helper::method_exists( $refund, 'get_id' ) ) {
				return;
			}

			// test if we have to create invoice number and invoice date
			$refund_number = get_post_meta( $refund->get_id(), '_wp_wc_running_invoice_number', true );
			$refund_date = get_post_meta( $refund->get_id(), '_wp_wc_running_invoice_number_date', true );
			
			if ( trim( $refund_number ) == '' ) {
				$running_refund_number = new WP_WC_Running_Invoice_Number_Functions( $refund );
				$refund_number 	= $running_refund_number->get_invoice_number();
				$refund_date	= $running_refund_number->get_invoice_timestamp();	
			}

			// same test for the order

			$invoice_number = get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', true );
			$invoice_date	= get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', true );
			if ( trim( $invoice_number ) == '' || trim( $invoice_date ) == '' ) {
				$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );
				$invoice_number = $running_invoice_number->get_invoice_number();
				$invoice_date	= $running_invoice_number->get_invoice_timestamp();	
			}
			
			$search 	= array( '{{refund-number}}', '{{refund-id}}', '{{refund-date}}', '{{order-number}}', '{{order-date}}', '{{invoice-number}}', '{{invoice-date}}', '{{site-title}}' );
			$replace	= array( $refund_number, $refund_id, date_i18n( get_option( 'date_format' ), strtotime( $refund_date ) ), $order->get_order_number(), date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ), $invoice_number, date_i18n( get_option( 'date_format' ), intval( $invoice_date ) ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );

			$return_string =  str_replace( $search, $replace, $heading_or_subject );

			return $return_string;
		}
		
	} // end class
	
} // end if
