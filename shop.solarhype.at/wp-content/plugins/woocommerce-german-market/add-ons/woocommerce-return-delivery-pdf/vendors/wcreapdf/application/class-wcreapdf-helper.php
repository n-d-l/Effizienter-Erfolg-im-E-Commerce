<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WCREAPDF_Helper' ) ) {
	
	/**
	* some functions that helps handling temp files, option names and if an order needs the pdf as attachment
	*
	* @class WCREAPDF_Helper
	* @version 1.0
	* @category	Class
	*/
	class WCREAPDF_Helper {	
		
		/**
		* get option value by my option name
		*
		* @since 0.0.1
		* @access public
		* @arguments string $option (my option name)	
		* @return mixed: boolean false or string
		*/	
		public static function get_wcreapdf_optionname( $option ) {
			if ( $option ) {
				return 'woocomerce_wcreapdf_wgm_' . sanitize_title( $option );		
			} else {
				return false;	
			}
		}
		
		/**
		* checks whether $order needs the retoure pdf (equiv. to needs shipping)
		*
		* @since 0.0.1
		* @access public
		* @arguments WC_Order $order
		* @return boolean
		*/	
		public static function check_if_needs_attachement( $order ) {
			
			if ( ! ( is_object( $order ) && method_exists( $order, 'get_items' ) ) ) {
				return $order;
			}
			
			$items = $order->get_items();
			foreach ( $items as $item_id => $item ) {
				
				if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
					$_product = $item->get_product();
				} else {
					$_product = $order_obj->get_product_from_item( $item );
				}
				
				if ( ! WGM_Helper::method_exists( $_product, 'needs_shipping' ) ) { // some items aren't products (probably romoved from shop)
					return true;
				}
				
				if ( $_product->needs_shipping() ) {
					return true;	
				}
				
			}

			return false;
		}

		/**
		* Remove Prices in PDF
		*
		* @since 3.6.3
		* @access public
		* @param String $retoure_or_delivery
		* @return void
		*/	
		public static function remove_each_price( $retoure_or_delivery ) {
			add_filter( 'gm_force_each_string_to_miss', '__return_true' );
		}

		/**
		* Remove Prices in PDF
		*
		* @since 3.6.3
		* @access public
		* @param String $retoure_or_delivery
		* @return void
		*/
		public static function add_each_price( $retoure_or_delivery ) {
			remove_filter( 'gm_force_each_string_to_miss', '__return_true' );
		}

		/**
		* Don't show delivery time in pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook wcreapdf_pdf_before_create
		* @return void
		*/
		public static function shipping_time_management_start( $retoure_or_delivery ) {

			if ( $retoure_or_delivery == 'retoure' ) { 	// retoure
				
				if ( get_option( 'woocommerce_de_show_delivery_time_retoure_pdf', 'off' ) == 'off' ) {
					add_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
				}

			} else if ( $retoure_or_delivery == 'delivery' ) { // delivery
				
				if ( get_option( 'woocommerce_de_show_delivery_time_delivery_pdf', 'off' ) == 'off' ) {
					add_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
				}

			}

		}

		/**
		* Don't show delivery time in pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook wcreapdf_pdf_after_create
		* @return void
		*/
		public static function shipping_time_management_end( $retoure_or_delivery ) {
			
			if ( $retoure_or_delivery == 'retoure' ) { // retoure
				
				if ( get_option( 'woocommerce_de_show_delivery_time_retoure_pdf', 'off' ) == 'off' ) {
					remove_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
				}

			} else if ( $retoure_or_delivery == 'delivery' ) { // delivery
				
				if ( get_option( 'woocommerce_de_show_delivery_time_delivery_pdf', 'off' ) == 'off' ) {
					remove_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
				}
			}
		}

		/**
		* Don't show delivery time in pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook wgm_shipping_time_product_string
		* @param String $shipping_time_output
		* @param String shipping_time
		* @param WC_Order_Item $item
		* @return String
		*/
		public static function remove_delivery_time_in_pdf( $shipping_time_output, $shipping_time, $item ) {
			return '';
		}

	} // end class
	
} // end if
