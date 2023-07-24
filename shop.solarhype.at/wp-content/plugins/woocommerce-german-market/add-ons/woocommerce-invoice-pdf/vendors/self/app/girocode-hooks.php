<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Girocode_Hooks' ) ) {

	/**
	* inlcude girocode in invoice pdf
	*
	* @WP_WC_Invoice_Pdf_Girocode_Hooks
	* @version 1.0.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Girocode_Hooks {

		static $instance = NULL;
		private $instructions_hooks = array();
		private $set_global_avoid_payment_instructions = null;
		private $before_order_table_hooks = array();
		private $hook_prio = 0;
		private $added_hook = false;

		/**
		 * singleton getInstance
		 *
		 * @access public
		 * @static
		 *
		 * @return WP_WC_Invoice_Pdf_Girocode_Hooks
		 */
		public static function get_instance() {

			if ( self::$instance == NULL) {
				self::$instance = new WP_WC_Invoice_Pdf_Girocode_Hooks();
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
			$this->hook_prio = intval( get_option( 'wp_wc_invoice_pdf_girocode_position_prio', '0' ) );
			add_action( 'wp_wc_invoice_pdf_start_template', array( $this, 'maybe_add_hooks' ) );
			add_action( 'wp_wc_invoice_pdf_end_template', array( $this, 'remove_hooks' ) );
		}

		/**
		* check if girocode should be added
		* 
		* @wp-hook wp_wc_invoice_pdf_start_template
		* @param Array $args
		* @return void
		*/
		public function maybe_add_hooks( $args ) {

			$add_hooks = false;

			// do not add qr-code if it's a refund pdf
			if ( isset( $args[ 'order' ] ) && ( ! isset( $args[ 'refund' ] ) ) ) {

				$order = $args[ 'order' ];
				if ( is_object( $order ) && method_exists( $order, 'get_payment_method' ) ) {
					
					if ( $this->is_enabled_for_billing_country( $order ) ) {
						
						// only EUR as currency is allowed, only for orders that needs a payment
						if ( $order->get_total() > 0.0 && 'EUR' === $order->get_currency() ) {
							$payment_method = $order->get_payment_method();

							$allowed_payment_methods = apply_filters( 'wp_wc_invoice_pdf_girocode_supported_gateways', array(
								'german_market_purchase_on_account' => __( 'Purchase On Acccount', 'woocommerce-german-market' ),
								'bacs'								=> __( 'Direct bank transfer', 'woocommerce-german-market' ),
							));

							if ( isset( $allowed_payment_methods[ $payment_method ] ) ) {
								if ( 'on' === get_option( 'wp_wc_invoice_pdf_girocode_gateway_' . $payment_method, 'off' ) ) {
									$add_hooks = true;
								}
							}
						}
					}

				} else {
					
					// show qr-code in test pdf, if girocode subtab is opened
					if ( isset( $args[ 'subtab' ] ) && 'girocode' === $args[ 'subtab' ] ) {
						$add_hooks = true;
					}
				}

				if ( $add_hooks ) {
					$this->add_hooks( $order );
				}
			}
		}

		/**
		* add hooks
		* 
		* @param WC_Order $order
		* @return void
		*/
		public function add_hooks( $order ) {

			$position = get_option( 'wp_wc_invoice_pdf_girocode_position', 'after' );
			if ( 'after' === $position ) {
				add_action( 'woocommerce_email_after_order_table', array( 'WP_WC_Invoice_Pdf_Girocode', 'make_markup' ), $this->hook_prio );
				$this->added_hook = 'woocommerce_email_after_order_table';
			} else if ( 'before' === $position ) {
				
				$this->added_hook = 'woocommerce_email_before_order_table';
				
				/*
				* global option in invoice pdf add-on is enabled to hide payment instructions
				* the hook "woocommerce_email_before_order_table" would not be executed.
				* temporailly, we turn off this option, remove all other actions,
				* save these actions temporailly and add qr code action. Later, we add the saved actions again. 
				*/
				if ( 'on' === get_option( 'wp_wc_invoice_pdf_avoid_payment_instructions', 'off' ) ) { 
					$this->set_global_avoid_payment_instructions = 'on';
					$this->remove_hooks_woocommerce_email_before_order_table( $order );
					add_filter( 'pre_option_wp_wc_invoice_pdf_avoid_payment_instructions', array( $this, 'pre_option_wp_wc_invoice_pdf_avoid_payment_instructions' ) );
					
				}

				// add qc-code hook
				add_action( 'woocommerce_email_before_order_table', array( 'WP_WC_Invoice_Pdf_Girocode', 'make_markup' ), $this->hook_prio, 2 );
			}
			
			// hide payment instructions (not the global option, but the one from the qr code settings)
			if ( 'off' === get_option( 'wp_wc_invoice_pdf_avoid_payment_instructions', 'off' ) ) {
				if ( ( 'on' === get_option( 'wp_wc_invoice_pdf_girocode_hide_default_payment_instructions' ) ) || ( ! is_null( $this->set_global_avoid_payment_instructions ) ) ) {
					$payment_gateways = WC()->payment_gateways->payment_gateways();
					
					if ( is_object( $order ) && method_exists( $order, 'get_payment_method' ) ) {
						if ( isset( $payment_gateways[ $order->get_payment_method() ] ) ) {
							$payment_method = $order->get_payment_method();
							$payment_method_object = $payment_gateways[ $payment_method ];
							$prio_of_instructions = has_action( 'woocommerce_email_before_order_table', array( $payment_method_object, 'email_instructions' ) );
							
							if ( $prio_of_instructions ) {
								if ( 'german_market_purchase_on_account' === $payment_method ) {
									$nr_of_parameters = 4;
								} else {
									$nr_of_parameters = 3;
								}

								$this->instructions_hooks[ $order->get_id() ] = array(
									'object' => $payment_method_object,
									'prio'	 => $prio_of_instructions,
									'params' => $nr_of_parameters,
								);

								remove_action( 'woocommerce_email_before_order_table', array( $payment_method_object, 'email_instructions' ), $prio_of_instructions, $nr_of_parameters );
							}
						}
					} else {

						// test pdf
						// we can remove the instructions of every payment method, no need to restore action
						global $wp_filter;
						$allowed_payment_methods = apply_filters( 'wp_wc_invoice_pdf_girocode_supported_gateways', array(
							'german_market_purchase_on_account' => __( 'Purchase On Acccount', 'woocommerce-german-market' ),
							'bacs'								=> __( 'Direct bank transfer', 'woocommerce-german-market' ),
						));

						foreach ( $allowed_payment_methods as $allowed_payment_method => $payment_object ) {
							if ( isset( $payment_gateways[ $allowed_payment_method ] ) ) {
								$payment_method_object = $payment_gateways[ $allowed_payment_method ];
								$prio_of_instructions = has_action( 'woocommerce_email_before_order_table', array( $payment_method_object, 'email_instructions' ) );
								if ( 'german_market_purchase_on_account' === $allowed_payment_method ) {
									$nr_of_parameters = 4;
								} else {
									$nr_of_parameters = 3;
								}
								remove_action( 'woocommerce_email_before_order_table', array( $payment_method_object, 'email_instructions' ), $prio_of_instructions, $nr_of_parameters );
							}
						}

					}
				}
			}
		}

		/**
		* temporailly, set option to off
		* 
		* @wp-hook pre_option_wp_wc_invoice_pdf_avoid_payment_instructions
		* @param
		* @return String
		*/
		public function pre_option_wp_wc_invoice_pdf_avoid_payment_instructions() {
			return 'off';
		}

		/**
		* undo what happend in the method "add_hooks"
		* so that the qr-code is only activated for this pdf
		* and other hooks are restored
		* 
		* @wp-hook wp_wc_invoice_pdf_end_template
		* @param Array $args
		* @return void
		*/
		public function remove_hooks( $args ) {
			
			if ( 'woocommerce_email_after_order_table' === $this->added_hook) {
				remove_action( 'woocommerce_email_after_order_table', array( 'WP_WC_Invoice_Pdf_Girocode', 'make_markup' ), $this->hook_prio );
				
			} else if ( 'woocommerce_email_before_order_table' === $this->added_hook ) {
				remove_action( 'woocommerce_email_before_order_table', array( 'WP_WC_Invoice_Pdf_Girocode', 'make_markup' ), $this->hook_prio );
				
				if ( ! is_null( $this->set_global_avoid_payment_instructions ) ) {
					
					remove_filter( 'pre_option_wp_wc_invoice_pdf_avoid_payment_instructions', array( $this, 'pre_option_wp_wc_invoice_pdf_avoid_payment_instructions' ) );
					$this->add_hooks_woocommerce_email_before_order_table( $args[ 'order' ] );
				}
			}

			$this->added_hook = false;
			$this->set_global_avoid_payment_instructions = null;

			// add payment instructions again (to be shown in emails or other pdfs(bulk download))
			if ( isset( $args[ 'order' ] ) && ( ! isset( $args[ 'refund' ] ) ) ) {
				$order = $args[ 'order' ];
				if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
					if ( isset( $this->instructions_hooks[ $order->get_id() ] ) ) {
						$payment_method_object = $this->instructions_hooks[ $order->get_id() ][ 'object' ];
						$prio_of_instructions = $this->instructions_hooks[ $order->get_id() ][ 'prio' ];
						$nr_of_parameters = $this->instructions_hooks[ $order->get_id() ][ 'params' ];
						add_action( 'woocommerce_email_before_order_table', array( $payment_method_object, 'email_instructions' ), $prio_of_instructions, $nr_of_parameters );
					}
				}
			}
		}

		/**
		* used when the global option to hide payment instructions is enabled
		* save all other hooks and remove them
		* 
		* @param Integer $order_id
		* @return void
		*/
		private function remove_hooks_woocommerce_email_before_order_table( $order ) {

			 global $wp_filter;

			 $order_id = ( is_object( $order ) && method_exists( $order, 'get_id' ) ) ? $order->get_id() : 'test';

			 if ( isset( $wp_filter[ 'woocommerce_email_before_order_table' ] ) && isset( $wp_filter[ 'woocommerce_email_before_order_table' ]->callbacks ) ) {
			 	
			 	$this->before_order_table_hooks[ $order_id ] = array();

			 	foreach ( $wp_filter[ 'woocommerce_email_before_order_table' ]->callbacks as $prio => $hook_infos ) {
			 		
			 		foreach ( $hook_infos as $hook_info ) {

				 		$this->before_order_table_hooks[ $order_id ][] = array(
				 			'prio'					=> $prio,
				 			'nr_of_parameters'		=> isset( $hook_info[ 'accepted_args' ] ) ? $hook_info[ 'accepted_args' ] : 1,
				 			'function'				=> $hook_info[ 'function' ],
				 			
				 		);

				 		remove_action( 'woocommerce_email_before_order_table', $hook_info[ 'function' ], $prio, $hook_info[ 'accepted_args' ] );
				 	}
			 	}
			}
		}

		/**
		* used when the global option to hide payment instructions is enabled
		* restore all saved hooks
		* 
		* @param Integer $order_id
		* @return void
		*/
		private function add_hooks_woocommerce_email_before_order_table( $order ) {

			$order_id = ( is_object( $order ) && method_exists( $order, 'get_id' ) ) ? $order->get_id() : 'test';

			if ( isset( $this->before_order_table_hooks[ $order_id ] ) ) {
				foreach ( $this->before_order_table_hooks[ $order_id ] as $hook ) {
					add_action( 'woocommerce_email_before_order_table', $hook[ 'function' ], $hook[ 'prio' ], $hook[ 'nr_of_parameters' ] );
				}
			}
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

			$option = get_option( 'wp_wc_invoice_pdf_girocode_billing_countries_option', 'all' );
			
			if ( 'all' !== $option ) {
				
				$countries = get_option( 'wp_wc_invoice_pdf_girocode_billing_countries', array() );

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

			return apply_filters( 'wp_wc_invoice_pdf_girocode_enabled_for_billing_country', $is_enabled, $order );
		}

	} // end class

} // end if
