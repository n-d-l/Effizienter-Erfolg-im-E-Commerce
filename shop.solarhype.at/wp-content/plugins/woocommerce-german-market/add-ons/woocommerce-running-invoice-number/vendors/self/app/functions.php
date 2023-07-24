<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Functions' ) ) {
	
	/**
	* core functions
	*
	* @WP_WC_Running_Invoice_Number_Functions
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Functions {
		
		/**
		 * @var string
		 * @access private
		 */
		private $prefix;
		
		/**
		 * @var string
		 * @access private
		 */
		private $suffix;
		
		/**
		 * @var integer
		 * @access private
		 */
		private $next_running_number;
		
		/**
		 * @var string
		 * @access private
		 */
		private $invoice_number;
		
		/**
		 * @var int
		 * @access private
		 */
		private $invoice_date;

		public static $currently_used_orders = array();
		
		/**
		* constructor
		*
		* @since 0.0.1
		* @access public
		* @param WC_Order
		* @return void
		*/	
		public function __construct( $order ) {
			
			// test order
			if ( ! WGM_Helper::method_exists( $order, 'get_id' ) ) {
				return;
			}

			if ( apply_filters( 'wc_running_invoice_number_functions_return_before_creation', false, $order ) ) {
				return;
			}

			$currently_used_orders = get_transient( 'wp_wc_invoice_number' );

			if ( is_array( $currently_used_orders ) ) {
				self::$currently_used_orders = $currently_used_orders;
			}

			if ( isset( self::$currently_used_orders[ $order->get_id() ] ) ) {
				$old_object = self::$currently_used_orders[ $order->get_id() ];
				$this->invoice_number = $old_object->get_invoice_number();
				$this->invoice_date = $old_object->get_invoice_timestamp();
				return;
			}
			
			// get semaphore, see class WP_WC_Running_Invoice_Number_Semaphore
			WP_WC_Running_Invoice_Number_Semaphore::sem_get();

			if ( WP_WC_Running_Invoice_Number_Semaphore::sem_acquire() ) {
				$option_prefix = apply_filters( 'wc_running_invoice_number_functions_option_prefix', '', $order );

				// Delete Options from Cace
				$no_caching_options = array(
					'wp_wc_invoice_number_construct_running',
					'wp_wc_running_invoice_number_next_refund' . $option_prefix,
					'wp_wc_running_invoice_number_next' . $option_prefix,
					'wp_wc_running_invoice_number_multisite_global' . $option_prefix,
					'_wp_wc_runninv_invoice_reset_last_date' . $option_prefix,
				);

				foreach ( $no_caching_options as $option_name ) {
					wp_cache_delete( $option_name, 'options');
				}

				WP_WC_Running_Invoice_Number_Semaphore::init_option_lock();

				// maybe reset invoice number
				self::reset_number();

				do_action( 'wp_wc_invoice_number_before_construct', $this, $order );

				// which options do we have to load? invoice number or refund numbers
				$load_refund_options = false;

				if ( is_a( $order, 'WC_Order_Refund' ) ) {
					if ( get_option( 'wp_wc_running_invoice_refund_separation', 'on' ) == 'on' ) {
						$load_refund_options = true;
					}
				}

				if ( $load_refund_options ) {
					
					$this->prefix				= get_option( 'wp_wc_running_invoice_number_prefix_refund' . $option_prefix );
					$this->suffix				= get_option( 'wp_wc_running_invoice_number_suffix_refund' . $option_prefix );
					$this->number_of_digits		= absint( get_option( 'wp_wc_running_invoice_number_digits_refund' . $option_prefix ) );
					$next_running_invoice_number = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global' . $option_prefix, 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next_refund' . $option_prefix, 1 ) : get_option( 'wp_wc_running_invoice_number_next_refund' . $option_prefix, 1 );
				
				} else {
					
					$this->prefix				= get_option( 'wp_wc_running_invoice_number_prefix' . $option_prefix );
					$this->suffix				= get_option( 'wp_wc_running_invoice_number_suffix' . $option_prefix );
					$this->number_of_digits		= absint( get_option( 'wp_wc_running_invoice_number_digits' . $option_prefix ) );
					$next_running_invoice_number = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global' . $option_prefix, 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next' . $option_prefix, 1 ) : get_option( 'wp_wc_running_invoice_number_next' . $option_prefix, 1 );

				}

				// invoice date
				if ( ! is_a( $order, 'WC_Order_Refund' ) ) {
					$post_meta_date	= get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', true );
					if ( $post_meta_date == '' ) {
						$this->invoice_date		= current_time( 'timestamp' );
						update_post_meta( $order->get_id(), '_wp_wc_running_invoice_number_date', $this->invoice_date );
					} else {
						$this->invoice_date		= $post_meta_date;
					}
				} else {
					$this->invoice_date = $order->get_date_created()->getTimestamp();
				}

				// Change Placeholdes
				$placeholder_date_time = new DateTime( current_time( 'Y-m-d H:i:s' ) );
				$search 		= array( '{{year}}', '{{year-2}}', '{{month}}', '{{day}}', '{{hour}}', '{{minute}}', '{{second}}' );
				$replace 		= array( $placeholder_date_time->format( 'Y' ), $placeholder_date_time->format( 'y' ), $placeholder_date_time->format( 'm' ), $placeholder_date_time->format( 'd' ), $placeholder_date_time->format( 'H' ), $placeholder_date_time->format( 'i' ), $placeholder_date_time->format( 's' ) );
				$this->suffix 	= str_replace( $search, $replace, $this->suffix );
				$this->prefix 	= str_replace( $search, $replace, $this->prefix ); 

				$this->next_running_number	= absint( $next_running_invoice_number );

				// Filter
				$this->suffix 	= apply_filters( 'wp_wc_invoice_number_before_construct_suffix', $this->suffix, $order, $this );
				$this->prefix 	= apply_filters( 'wp_wc_invoice_number_before_construct_prefix', $this->prefix, $order, $this );
				$running_number = apply_filters( 'wp_wc_invoice_number_before_construct_number', str_pad( $this->next_running_number, $this->number_of_digits, '0', STR_PAD_LEFT ), $this );
				
				$this->invoice_number	= $this->prefix . $running_number . $this->suffix;

				if ( false !== add_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', apply_filters( 'wp_wc_invoice_number_update_post_meta', $this->invoice_number, $this, $order ), true ) ) {
					
					if ( $load_refund_options ) {
						if ( is_multisite() && get_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) {
							update_site_option( 'wp_wc_running_invoice_number_next_refund' . $option_prefix, ( $this->next_running_number + 1 ) );
						} else {
							update_option( 'wp_wc_running_invoice_number_next_refund' . $option_prefix, ( $this->next_running_number + 1 ), 'no' );
						}
					} else {
						if ( is_multisite() && get_option( 'wp_wc_running_invoice_number_multisite_global' . $option_prefix, 'no' ) == 'yes' ) {
							update_site_option( 'wp_wc_running_invoice_number_next' . $option_prefix, ( $this->next_running_number + 1 ) );
						} else {
							update_option( 'wp_wc_running_invoice_number_next' . $option_prefix, ( $this->next_running_number + 1 ), 'no' );
						}
					}

					$logging = apply_filters( 'german_market_invoice_number_logging', false );

					if ( $logging ) {
						$logger = wc_get_logger();
						$context = array( 'source' => 'german-market-invoice-number' );
						$logger->info( sprintf( '%s: %s, Invoice Number saved in meta: %s, Next Running Number for %s set to %s.', $load_refund_options ? 'Refund' : 'Order', $order->get_id(), $this->invoice_number, $load_refund_options ? 'refunds' : 'orders', ( $this->next_running_number + 1 ) ), $context );
					}

				} else {
					$this->invoice_number = get_post_meta( $order->get_id(), '_wp_wc_running_invoice_number', true );
				}

				do_action( 'wp_wc_invoice_number_after_construct', $this, $order );

				// release semaphore
				WP_WC_Running_Invoice_Number_Semaphore::sem_release(); 

				// this can only happens once and only if empty string has been saved in meta in legacy version
				if ( empty( $this->invoice_number ) && 'yes' !== get_transient( 'wp_wc_invoice_number_recursive_call_' . $order->get_id() ) ) {
					set_transient( 'wp_wc_invoice_number_recursive_call_' . $order->get_id(), 'yes', 60 ); // avoid infinite loop in case of an unpredictable error
					delete_post_meta( $order->get_id(), '_wp_wc_running_invoice_number' );
					self::static_construct( $order );
				}

				self::$currently_used_orders[ $order->get_id() ] = $this;
				set_transient( 'wp_wc_invoice_number', self::$currently_used_orders, 5 );
			}
		}

		/**
		* set invoice number property, this will not save meta data
		*
		* @since 3.11.0.1
		* @access public
		* @param String $invoice_number
		* @return String
		*/	
		public function set_invoice_number( $invoice_number ) {
			$this->invoice_number = $invoice_number;
		}

		/**
		* set invoice date property, this will not save meta data
		*
		* @since 3.11.0.1
		* @access public
		* @param Integer $invoice_date
		* @return Integer
		*/	
		public function set_invoice_date( $timestamp ) {
			$this->invoice_date = $timestamp;
		}

		/**
		* get invoice number
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/	
		public function get_invoice_number() {
			return $this->invoice_number;	
		}
		
		/**
		* get formated invoice date
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/	
		public function get_invoice_date() {
			global $wp_locale;
			return date_i18n( get_option( 'date_format' ), ( intval( $this->invoice_date ) == 0 ) ? current_time( 'timestamp' ) : $this->invoice_date );	// some error handling, in very few cases ajax-request on shop-order $this->invoice_date is still 0 
		}
		
		/**
		* get invoice timestamp
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/	
		public function get_invoice_timestamp() {
			return ( intval( $this->invoice_date ) == 0 ) ? current_time( 'timestamp' ) : $this->invoice_date;	// some error handling, in very few cases ajax-request on shop-order $this->invoice_date is still 0 
		}
		
		/**
		* constructor
		*
		* @since 0.0.1
		* @static
		* @access public
		* @return void
		*/
		public static function static_construct( $order ) {
			$object	= new WP_WC_Running_Invoice_Number_Functions( $order );
		}
		
		/**
		* constructor with $order_id
		*
		* @since 0.0.1
		* @static
		* @access public
		* @return void
		*/
		public static function static_construct_by_order_id( $order_id ) {
			$order 	= wc_get_order( $order_id );
			$object	= new WP_WC_Running_Invoice_Number_Functions( $order );
		}

		/**
		* Reset Invoice Number monthly / annually
		*
		* @since GM 3.2
		* @static
		* @access public
		* @return void
		*/
		public static function reset_number() {

			$logging = apply_filters( 'german_market_invoice_number_logging', false );
			if ( $logging ) {
				$log = get_option( 'german_market_invoice_number_log', '' );
				$new_log = '';
			}

			$reset_option = get_option( 'wp_wc_running_invoice_number_reset_interval', 'off' );
			$reset_option = apply_filters( 'wp_wc_running_invoice_number_reset_option', $reset_option );

			$now = new DateTime( current_time( 'Y-m-d H:i:s' ) );
			
			if ( $reset_option != 'off' ) {

				$last_date_string = get_option( '_wp_wc_runninv_invoice_reset_last_date', $now->format( 'Y-m-d' ) );
				$last_date_date_time = new DateTime( $last_date_string );

				$format = 'Y-m';
				
				if ( $reset_option == 'annually' ) {
					$format = 'Y';
				} else if ( $reset_option == 'monthly' ) {
					$format = 'Y-m';
				} else if ( $reset_option == 'daily' ) {
					$format = 'Y-m-d';
				} else {
					$format = apply_filters( 'wp_wc_running_invoice_number_reset_format', 'Y-m-d', $reset_option );
				}

				if ( $now->format( $format ) != $last_date_date_time->format( $format ) ) {

					$reset_number = apply_filters( 'wp_wc_running_invoice_number_first_number_after_reset', 1 );

					if ( is_multisite() && get_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) {
						update_site_option( 'wp_wc_running_invoice_number_next', $reset_number );
						update_site_option( 'wp_wc_running_invoice_number_next_refund', $reset_number );
					} else {
						update_option( 'wp_wc_running_invoice_number_next', $reset_number, 'no' );
						update_option( 'wp_wc_running_invoice_number_next_refund', $reset_number, 'no' );
					}

					if ( $logging ) {
						$new_log = sprintf( '<strong>%s</strong>:, Reset Invoice Number to %s.', current_time( 'Y-m-d H:i' ), $reset_number );
						$log = empty( $log ) ? $new_log : $new_log . '<br>' . $log;
						update_option( 'german_market_invoice_number_log', $log, 'no' );
					}

				}

			}

			update_option( '_wp_wc_runninv_invoice_reset_last_date', $now->format( 'Y-m-d' ), 'no' );

		}
		
	} // end class
	
} // end if
