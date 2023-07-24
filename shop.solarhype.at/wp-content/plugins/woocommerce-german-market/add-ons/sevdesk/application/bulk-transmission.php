<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! class_exists( 'Bulk_Transmission_sevDesk' ) ) {

	class Bulk_Transmission_sevDesk {

		static $instance_counter = 0;

		function __construct() {

			if ( ! class_exists( 'WC_Action_Queue' ) ) {
				return;
			}

			if ( self::$instance_counter == 0 ) {

				if ( is_admin() ) {

					// orders
					add_action( 'admin_init', function() {
						add_action( WGM_Hpos::get_hook_for_order_bulk_actions(), array( __CLASS__ , 'add_bulk_actions' ), 10 );
						add_action( WGM_Hpos::get_hook_for_order_handle_bulk_actions(), array( __CLASS__, 'bulk_action' ), 10, 3 );
						add_action( 'admin_notices', array( __CLASS__, 'info_about_scheduled_transmissions' ) );
					});
					
					// refunds
					add_action( 'woocommerc_de_refund_before_list',				array( __CLASS__, 'refund_button' ), 20 );
					add_action( 'woocommerc_de_refund_after_list',				array( __CLASS__, 'refund_button' ), 20 );
					add_action( 'admin_init',									array( __CLASS__, 'bulk_action_refunds' ) );
					add_action( 'woocommerc_de_refund_before_list',				array( __CLASS__, 'info_about_scheduled_transmissions_refunds' ), 100 );

				}

				add_action( 'german_market_sevdesk_bulk_transmission', 			array( __CLASS__, 'transmit_one_order_via_bulk' ) );
				add_action( 'german_market_sevdesk_bulk_transmission_refund', 	array( __CLASS__, 'transmit_one_refund_via_bulk' ) );

			}

			self::$instance_counter++;

		}

		/**
		* submit button for refunds
		*
		* @since 3.1
		* @access public
		* @static 
		* @hook woocommerc_de_refund_after_list, woocommerc_de_refund_before_list
		* @return void
		*/
		public static function refund_button() {
			?><input class="button-primary" type="submit" name="transmit-to-sevdesk" value="<?php echo __( 'Transmit to sevDesk', 'woocommerce-german-market' ); ?>"/><?php
		}

		/**
		* bulk download for refunds
		*
		* @access public
		* @static 
		* @hook admin_init
		* @return void
		*/
		public static function bulk_action_refunds() {
			
			if ( isset( $_REQUEST[ 'transmit-to-sevdesk' ] ) ) {

				// check nonce
				if ( ! isset( $_REQUEST[ 'wgm_refund_list_nonce' ] ) ) {
					return;
				}

				if ( ! wp_verify_nonce( $_POST[ 'wgm_refund_list_nonce' ], 'wgm_refund_list' ) ) {
					?><div id="message" class="error notice" style="display: block;"><p><?php echo __( 'Sorry, something went wrong while downloading your refunds. Please, try again.', 'woocommerce-german-market' ); ?></p></div><?php
					return;
				} 

				// init refunds
				if ( ! isset( $_REQUEST[ 'refunds' ] ) ) {
					return;
				}

				$refunds = $_REQUEST[ 'refunds' ];

				// return if no order is checked
				if ( empty( $refunds ) ) {
					return;
				}

				foreach ( $refunds as $refund_id ) {

					$refund = wc_get_order( $refund_id );
					if ( is_object( $refund ) && method_exists( $refund, 'get_meta' ) ) {
						$is_scheduled = $refund->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );

						if ( empty( $is_scheduled ) ) {

							$sevdesk_voucher_id = intval( $refund->get_meta( '_sevdesk_woocomerce_has_transmission' ) );

							// has transmission?
							$has_transmission = $sevdesk_voucher_id != 0;

							// is voucher still available?
							$is_valid = true;
							if ( $has_transmission ) {
								$is_valid = sevdesk_woocommerce_api_get_vouchers_status( $sevdesk_voucher_id, false );
							}

							// if not, remove meta
							if ( ! $is_valid ) {
								$refund->delete_meta_data( '_sevdesk_woocomerce_has_transmission' );
								$refund->save_meta_data();
								$has_transmission = false;
							}

							if ( ! $has_transmission ) {
								WC()->queue()->add( 'german_market_sevdesk_bulk_transmission_refund', array( 'refund_id' => $refund_id ), 'german_market_sevdesk' );
								$refund->update_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission', 'yes' );
								$refund->save_meta_data();
							}
						}
					}
				}
			}
		}

		/**
		* show info of background transmission for refunds
		*
		* @access public
		* @static 
		* @hook woocommerc_de_refund_before_list
		* @return void
		*/
		public static function info_about_scheduled_transmissions_refunds() {

			$search_args = array(
				'hook' 		=> 'german_market_sevdesk_bulk_transmission_refund',
				'status'	=> ActionScheduler_Store::STATUS_PENDING,
				'per_page'	=> -1,
			);

			$search = WC()->queue()->search( $search_args );
			$nr_in_queue = count( $search );

			if ( $nr_in_queue > 0 ) {

				?><div class="sevdesk-info-bulk refunds"><p><?php
					echo sprintf( _n( 'In the background %s refund is currently transmitted to sevDesk.', 'In the background %s refunds are currently transferred to sevDesk.', $nr_in_queue, 'woocommerce-german-market' ), $nr_in_queue );
				?></p></div><?php

			} else {

				$args = array(
					'meta_key'     	=> '_sevdesk_woocomerce_scheduled_for_transmission',
					'meta_compare' 	=> 'EXISTS',
					'type' 			=> 'shop_order_refund',
				);

				$orders = wc_get_orders( $args );

				foreach ( $orders as $order ) {
					$order->delete_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission' );
					$order->save_meta_data();
				}
			}
		}

		/**
		* show info of background transmission for orders
		*
		* @access public
		* @static 
		* @hook admin_notices
		* @return void
		*/
		public static function info_about_scheduled_transmissions( $which ) {

			if ( WGM_Hpos::is_edit_shop_order_screen() ) {

				$search_args = array(
					'hook' 		=> 'german_market_sevdesk_bulk_transmission',
					'status'	=> ActionScheduler_Store::STATUS_PENDING,
					'per_page'	=> -1,
				);

				$search = WC()->queue()->search( $search_args );
				$nr_in_queue = count( $search );

				if ( $nr_in_queue > 0 ) {

					?><div class="notice notice-success"><p><?php
						echo sprintf( _n( 'In the background %s order is currently transmitted to sevDesk.', 'In the background %s orders are currently transferred to sevDesk.', $nr_in_queue, 'woocommerce-german-market' ), $nr_in_queue );
					?></p></div><?php

				} else {

					$args = array(
						'meta_key'     	=> '_sevdesk_woocomerce_scheduled_for_transmission',
						'meta_compare' 	=> 'EXISTS',
					);

					$orders = wc_get_orders( $args );

					foreach ( $orders as $order ) {
						$order->delete_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission' );
						$order->save_meta_data();
					}
				}
			}
		}

		/**
		* add bulk action
		*
		* @access public
		* @static 
		* @hook WGM_Hpos::get_hook_for_order_bulk_actions()
		* @param Array $actions
		* @return Array
		*/
		public static function add_bulk_actions( $actions ) {
			$actions[ 'gm_sevdesk_bulk_transmission' ] = __( 'Transmit to sevDesk', 'woocommerce-german-market' );
			return $actions;
		}

		/**
		* do bulk action
		*
		* @access public
		* @static 
		* @hook WGM_Hpos::get_hook_for_order_handle_bulk_actions()
		* @param String $redirect_to
		* @param String $action
		* @param Array $order_ids
		* @return String
		*/
		public static function bulk_action( $redirect_to, $action, $order_ids ) {

			if ( empty( $order_ids ) ) {
				return $redirect_to;
			}

			if ( $action == 'gm_sevdesk_bulk_transmission' ) {

				foreach ( $order_ids as $order_id ) {

					$order = wc_get_order( $order_id );

					if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {
						$is_scheduled = $order->get_meta( '_sevdesk_woocomerce_scheduled_for_transmission' );

						if ( empty( $is_scheduled ) ) {

							// manual order confirmation
							if ( get_option( 'woocommerce_de_manual_order_confirmation' ) === 'on' ) {
								if ( $order->get_meta( '_gm_needs_conirmation' ) === 'yes' ) {
									continue;
								}
							}
							
							$sevdesk_voucher_id = $order->get_meta( '_sevdesk_woocomerce_has_transmission' );

							// has transmission?
							$has_transmission = $sevdesk_voucher_id != '';

							// is voucher still available?
							$is_valid = true;
							if ( $has_transmission ) {
								$is_valid = sevdesk_woocommerce_api_get_vouchers_status( $sevdesk_voucher_id, false );
							}
							
							// if not, remove meta
							if ( ! $is_valid ) {
								$order->delete_meta_data( '_sevdesk_woocomerce_has_transmission' );
								$order->save_meta_data();
								$has_transmission = false;
							}

							if ( ! $has_transmission ) {
								WC()->queue()->add( 'german_market_sevdesk_bulk_transmission', array( 'order_id' => $order_id ), 'german_market_sevdesk' );
								$order->update_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission', 'yes' );
								$order->save_meta_data();
							}
						}
					}
				}
			}

			return $redirect_to;
		}

		/**
		* transmit one order to sevDesk via bulk
		*
		* @access public
		* @static 
		* @hook german_market_sevdesk_bulk_transmission
		* @param Integer $order_id
		* @return void
		*/
		public static function transmit_one_order_via_bulk( $order_id ) {

			$order = wc_get_order( $order_id );
			$response = sevdesk_woocomerce_api_send_order( $order, false );
			$order->delete_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission' );
			$order->save_meta_data();
		}

		/**
		* transmit one refund to sevDesk via bulk
		*
		* @access public
		* @static 
		* @hook german_market_sevdesk_bulk_transmission_refund
		* @param Integer $refund_id
		* @return void
		*/
		public static function transmit_one_refund_via_bulk( $refund_id ) {

			$refund = wc_get_order( $refund_id );

			if ( is_object( $refund ) && method_exists( $refund, 'get_meta' ) ) {
				
				$response = sevdesk_woocommerce_api_send_refund( $refund, false );
				
				$refund->delete_meta_data( '_sevdesk_woocomerce_scheduled_for_transmission' );
				$refund->save_meta_data();
			}
		}
	}

	$sevdesk_bulk_transmission = new Bulk_Transmission_sevDesk();
}
