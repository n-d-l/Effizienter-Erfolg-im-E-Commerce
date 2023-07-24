<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks' ) ) {

	/**
	* inlcude swiss qr invoice in invoice pdf
	*
	* @WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks
	* @version 1.0.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks {

		static $instance = NULL;
		static $orders_needs_swiss_qr_invoice = array();

		/**
		 * singleton getInstance
		 *
		 * @access public
		 * @static
		 *
		 * @return WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks
		 */
		public static function get_instance() {

			if ( self::$instance == NULL) {
				self::$instance = new WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks();
			}

			return self::$instance;
		}

		/**
		* constuct
		* 
		* add girocde to invoice pdf
		* @return void
		*/
		public function __construct() {
			add_action( 'wp_wc_invoice_pdf_after_new_dompdf', array( $this, 'save_last_position_before_swiss_qr_invoice_in_pdf' ), 10, 2 );
			add_action( 'wp_wc_invoice_pdf_before_fine_print', array( $this, 'insert_swiss_qr_invoice_in_pdf' ), 10, 2 );
			add_filter( 'wp_wc_invoice_pdf_before_pdf_generation_invoice_var', array( $this, 'rerender_pdf_with_new_page' ), 10, 3 );
		}

		/**
		* if we need a new page before swiss qr invoice
		* manipulate html, add page break, load html again and render dompdf again
		* 
		* @wp-hook wp_wc_invoice_pdf_before_pdf_generation_invoice_var
		* @param Ebs_Pdf_Wordpress $invoice
		* @param String $html
		* @param Array $args
		* @return Ebs_Pdf_Wordpress
		*/
		public function rerender_pdf_with_new_page( $invoice, $html, $args ) {

			if ( $this->order_needs_swiss_qr_invoice( $args[ 'order' ], $args ) ) {

				if ( isset( $GLOBALS[ "new_page_before_swiss_qr_code" ] ) && 'yes' === $GLOBALS[ "new_page_before_swiss_qr_code" ] ) {
					
					$html = str_replace( 
								'<span class="before-fine-print" style="height: 0; line-height: 0; font-size: 0;"></span>',
								'<span class="before-fine-print" style="height: 0; line-height: 0; font-size: 0;"></span><div style="page-break-after: always;"></div>',
								$html
					);

					unset( $invoice );

					$invoice = new Ebs_Pdf_Wordpress( 'wp_wc_invoice_pdf_' );

					// set paper size
					$orientation = get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );
					if ( $orientation == 'portrait' ) {
						$invoice->pdf->set_paper( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ), 'portrait' );
					} else {
						$invoice->pdf->set_paper( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ), 'landscape' );
					}

					unset( $GLOBALS[ "hide_page_number_pages" ] );

					$invoice->pdf->load_html( $html );
					$invoice->pdf->render();

					unset( $GLOBALS[ "new_page_before_swiss_qr_code" ] );
				}
			}

			return $invoice;
		}

		/**
		* save position of last element before swiss qr invoice
		* to check later, if we need a page break
		* 
		* @wp-hook wp_wc_invoice_pdf_after_new_dompdf
		* @param Ebs_Pdf_Wordpress $invoice
		* @param Array $args
		* @return void
		*/
		public function save_last_position_before_swiss_qr_invoice_in_pdf( $invoice, $args ) {

			if ( isset( $args[ 'order' ] ) && ( ! isset( $args[ 'refund' ] ) ) ) {
				if ( $this->order_needs_swiss_qr_invoice( $args[ 'order' ], $args ) ) {

					$invoice->pdf->setCallbacks(
						array(
							array(
								'event' => 'end_frame', 'f' => function ( $infos ) {
									
									$frame = $infos[ 'frame' ];
									$pdf   = $infos[ 'canvas' ];
									$class = '';
									if ( is_object( $frame->get_node() ) && method_exists( $frame->get_node(), 'getAttribute' ) ){
										$class = $frame->get_node()->getAttribute("class");
									}

									if ( 'before-fine-print' === $class ) {

										$padding_box = $frame->get_padding_box();
										// position is saved in points, dpi 72
										if ( isset( $padding_box[ 'y' ] ) ) {
											
											$page_height_in_cm = $pdf->get_height() / 72 * 2.54;
											$position_in_cm = $padding_box[ 'y' ] / 72  * 2.54;

			  								if ( $page_height_in_cm - $position_in_cm < apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_height', 10.5 ) ) {
												$GLOBALS[ "new_page_before_swiss_qr_code" ] = 'yes';
											} 
										}
									}
								}
							)
						)
					);
				}
			}
		}

		/**
		* insert swiss qc invoice
		* 
		* @param WC_Order $order 
		* @param Array $args // $args[ 'order' ] can be test, but $order is a real WC_Order test instance
		* @return void
		*/
		public function insert_swiss_qr_invoice_in_pdf( $order, $args ) {

			if ( isset( $args[ 'order' ] ) && ( ! isset( $args[ 'refund' ] ) ) ) {
				if ( $this->order_needs_swiss_qr_invoice( $args[ 'order' ], $args ) ) {

					$swiss_qr_invoice = new WP_WC_Invoice_Pdf_Swiss_Qr_Invoice( $order, $args );
					$swiss_qr_invoice->make_markup();

				}
			}
		}

		/**
		* check if invoice pdf need swiss qc invoice
		* 
		* @param WC_Order $order
		* @param Array $args
		* @return Boolean
		*/
		public function order_needs_swiss_qr_invoice( $order, $args ) {

			$needs_swiss_qr_invoice = false;
			$order_id = ( is_object( $order ) && method_exists( $order, 'get_id' ) ) ? $order->get_id() : 'test';

			// use runtime cache
			if ( isset( self::$orders_needs_swiss_qr_invoice[ $order_id ] ) ) {
				return self::$orders_needs_swiss_qr_invoice[ $order_id ];
			}

			if ( isset( $args[ 'subtab' ] ) && 'swiss_qr_invoice' === $args[ 'subtab' ] ) {
				$needs_swiss_qr_invoice = true;
			} else {

				if ( is_object( $order ) && method_exists( $order, 'get_total' ) ) {
					if ( $this->is_enabled_for_billing_country( $order ) ) {
						if ( $order->get_total() > 0.0 && ( 'CHF' === $order->get_currency() || 'EUR' === $order->get_currency() ) ) {

							$payment_method = $order->get_payment_method();

							$allowed_payment_methods = apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_supported_gateways', array(
								'german_market_purchase_on_account' => __( 'Purchase On Acccount', 'woocommerce-german-market' ),
								'bacs'								=> __( 'Direct bank transfer', 'woocommerce-german-market' ),
							));

							if ( isset( $allowed_payment_methods[ $payment_method ] ) ) {
								if ( 'on' === get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_gateway_' . $payment_method, 'off' ) ) {
									$needs_swiss_qr_invoice = true;
								}
							}
						}
					}
				}
			}

			self::$orders_needs_swiss_qr_invoice[ $order_id ] = $needs_swiss_qr_invoice;			
			return $needs_swiss_qr_invoice;
		}

		/**
		* if the option "enabled for billing countries" is used
		* check billing country of order
		* 
		* @param WC_Order $order
		* @return Boolean
		*/
		public function is_enabled_for_billing_country( $order ) {

			$is_enabled = true;

			$option = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries_option', 'all' );
			
			if ( 'all' !== $option ) {
				
				$countries = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries', array() );

				if ( ! is_array( $countries ) ) {
					$countries = array();
				}

				if ( is_object( $order ) && method_exists( $order, 'get_billing_country' ) ) {
					$billing_country = $order->get_billing_country();

					if ( 'all_except' === $option ) {
						if ( in_array( $billing_country, $countries ) ) {
							$is_enabled = false;
						}
					} else if ( 'specific' === $option ) {
						if ( ! in_array( $billing_country, $countries ) ) {
							$is_enabled = false;
						}
					}
				}
				
			} 

			return apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_enabled_for_billing_country', $is_enabled, $order );
		}

	} // end class

} // end if
