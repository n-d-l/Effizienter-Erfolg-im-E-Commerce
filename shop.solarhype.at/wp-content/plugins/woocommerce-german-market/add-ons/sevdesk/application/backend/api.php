<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* API - send order
*
* @param WC_ORDER $order
* @return String ("SUCCESS" or "ERROR: {your error Message}")
*/
function sevdesk_woocomerce_api_send_order( $order, $show_errors = true ) {

	if ( apply_filters( 'sevdesk_woocomerce_api_send_order_dont_send', false, $order ) ) {
		return 'SUCCESS';
	}

	// get all we need, may throws errors and exit
	$api_token = sevdesk_woocommerce_api_get_api_token( $show_errors );

	if ( empty( $api_token ) ) {
		return 'ERROR';
	}
	
	$args = array(
		'api_token'		=> $api_token,
		'base_url'		=> sevdesk_woocommerce_api_get_base_url(),
		'order'			=> sevdesk_woocommerce_api_check_order( $order ),
		'invoice_pdf'	=> sevdesk_woocommerce_api_get_invoice_pdf( $order )
	);
	
	// build temp file, may throws an error and exits
	$temp_file = sevdesk_woocommerce_api_build_temp_file( $args, $show_errors );

	if ( empty( $temp_file ) ) {
		return 'ERROR';
	}

	$args[ 'temp_file' ] = $temp_file;

	// create customer or update user data
	$args[ 'customer' ] = sevdesk_woocommerce_api_contact( $order->get_user_id(), $args );

	do_action( 'sevdesk_woocommerce_api_before_send', $order );

	// send voucher to sevDesk
	$voucher_id = sevdesk_woocommerce_api_send_voucher( $args, $show_errors );

	// save sevdesk id as post meta
	$order->update_meta_data( '_sevdesk_woocomerce_has_transmission', $voucher_id );
	$order->save_meta_data();

	do_action( 'sevdesk_woocommerce_api_after_send', $order );

	return 'SUCCESS';

}

/**
* API - send refund
*
* @param WC_ORDER $order
* @return String ("SUCCESS" or "ERROR: {your error Message}")
*/
function sevdesk_woocommerce_api_send_refund( $refund, $show_errors = true ) {

	$api_token = sevdesk_woocommerce_api_get_api_token( $show_errors );

	// get all we need, may throws errors and exit
	if ( empty( $api_token ) ) {
		return 'ERROR';
	}

	$args = array(
		'api_token'		=> $api_token,
		'base_url'		=> sevdesk_woocommerce_api_get_base_url(),
		'refund'		=> sevdesk_woocommerce_api_check_order( $refund ),
		'order'			=> wc_get_order( $refund->get_parent_id() ),
		'invoice_pdf'	=> sevdesk_woocommerce_api_get_refund_pdf( $refund )
	);

	$order = wc_get_order( $refund->get_parent_id() );

	// build temp file, may throws an error and exits
	$temp_file = sevdesk_woocommerce_api_build_temp_file( $args, $show_errors );

	if ( empty( $temp_file ) ) {
		return 'ERROR';
	}

	$args[ 'temp_file' ] = $temp_file;

	// create customer or update user data
	$args[ 'customer' ] = sevdesk_woocommerce_api_contact( $order->get_user_id(), $args );

	do_action( 'sevdesk_woocommerce_api_before_send_refund', $order, $refund );

	// send voucher to sevDesk
	$voucher_id = sevdesk_woocommerce_api_send_voucher_refund( $args, $show_errors );

	// save sevdesk id as post meta
	$refund->update_meta_data( '_sevdesk_woocomerce_has_transmission', $voucher_id );
	$refund->save_meta_data();

	do_action( 'sevdesk_woocommerce_api_after_send_refund', $order, $refund );

	return 'SUCCESS';

}

/**
* send refund as voucher to sevDesk
*
* @param Array $args
* @return String
*/
function sevdesk_woocommerce_api_send_voucher_refund( $args, $show_errors = true ) {

	// init
	$refund = $args[ 'refund' ];
	$voucherPos = array();
	$accountingType = array ( 
		'id' => apply_filters( 'woocommerce_de_sevdesk_booking_account_refunds', get_option( 'woocommerce_de_sevdesk_booking_account_refunds', 27 ), $args ),
		'objectName' => 'AccountingType'
	);
	$complete_refund_amount = $refund->get_amount() * ( -1 );
	$item_sum_refunded = 0.0;
	$refund_reason = $refund->get_reason() == '' ? '' : sprintf( __( '(%s)', 'woocommerce-german-market' ), $refund->get_reason() );

	// tax free intracommunity delivery OR tax exempt export delivery
	$tax_type = 'default';
	if ( get_option( 'woocommerce_de_kleinunternehmerregelung', 'off' ) != 'on' ) {
		if ( function_exists( 'wcvat_woocommerce_order_details_status' ) ) {
			$tax_exempt_status = wcvat_woocommerce_order_details_status( $args[ 'order' ] );
			if ( $tax_exempt_status == 'tax_free_intracommunity_delivery' ) {
				$tax_type = 'eu';
			} else if ( $tax_exempt_status == 'tax_exempt_export_delivery' ) {
				$tax_type = 'noteu';
			}
		}
	}

	///////////////////////////////////
	// build voucher positions, 1st: order items
	///////////////////////////////////
	foreach ( $refund->get_items() as $item ) {
		
		if ( ! ( abs( $refund->get_line_total( $item, true, true ) ) > 0.0 ) ) {
			continue;
		} 

		$tax_gross_minus_net    = $refund->get_item_subtotal( $item, true, false ) - $refund->get_item_subtotal( $item, false, false );
		$tax_rate 				= round( $tax_gross_minus_net / $refund->get_item_subtotal( $item, false, true ) * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );

		$refund_account_type = $accountingType;

		if ( get_option( 'woocommerce_de_sevdesk_individual_product_booking_accounts', 'off' ) == 'on' ) {

			if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
				$_product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
			} else {
				$_product = apply_filters( 'woocommerce_order_item_product', $refund->get_product_from_item( $item ), $item );
			}

			if ( WGM_Helper::method_exists( $_product, 'get_meta' ) ) {

				$account_product = ( $_product->get_type() == 'variation' ) ? wc_get_product( $_product->get_parent_id() ) : $_product;

				$refund_account = intval( $account_product->get_meta( '_sevdesk_field_refund_account' ) );

				if ( $refund_account > 0 ) {
					$refund_account_type = array ( 
						'id' 			=> $refund_account,
						'objectName' 	=> 'AccountingType'
					);
				}
			}

		}

		$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_refund', 
			
			array(
				'sum'			=> abs( $refund->get_line_total( $item, false, false ) ),
				'net'			=> 'false',
				'objectName'	=> 'VoucherPos',
				'accountingType'=> $refund_account_type,
				'mapAll' 		=> 'true',
				'comment' 		=> trim( __( 'Refund', 'woocommerce-german-market' ) . ': ' . $item[ 'name' ] . ' ' . $refund_reason ),
				'taxType'		=> $tax_type,
				'taxRate'		=> $tax_rate,
			),
			$item,
			$args
		);

		$item_sum_refunded += abs( $refund->get_line_total( $item, true, true ) );

	}

	///////////////////////////////////
	// Shipping
	///////////////////////////////////
	$shipping = floatval( $refund->get_total_shipping() );
	$shipping_tax = floatval( $refund->get_shipping_tax() );
	$shipping_gross = floatval( $shipping + $shipping_tax );

	if ( abs( $shipping_gross ) > 0.0 ) {
		
		$item_sum_refunded += abs( $shipping_gross );

		$shipping_rate = round( $shipping_tax / $shipping * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );
		
		$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_shipping_refund', 
				
			array(
				'sum'			=> abs( $shipping ),
				'net'			=> 'false',
				'objectName'	=> 'VoucherPos',
				'accountingType'=> $accountingType,
				'mapAll' 		=> 'true',
				'comment' 		=> sprintf( __( 'Refund Shipping: %s', 'woocommerce-german-market' ), $refund->get_shipping_method() ),
				'taxType'		=> $tax_type,
				'taxRate'		=> $shipping_rate,
			),

			$args
		);
	}

	///////////////////////////////////
	// Fees
	///////////////////////////////////
	$fees = $refund->get_fees();

	foreach ( $fees as $fee ) {
		$fee_name 	= $fee[ 'name' ];

		$fee_total	= $fee->get_total();
		$fee_tax 	= $fee->get_total_tax();
		$fee_gross 	= $fee_total + $fee_tax;

		if ( abs( $fee_gross ) > 0.0 ) {

			$item_sum_refunded += abs( $fee_gross );
			$fee_rate = round( $fee_tax / $fee_total * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );

			$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_general_refund', 
				
				array(
					'sum'			=> abs( $fee_total ),
					'net'			=> 'false',
					'objectName'	=> 'VoucherPos',
					'accountingType'=> apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee_accounting_type', $accountingType, $fee ),
					'mapAll' 		=> 'true',
					'comment' 		=> sprintf( __( 'Refund Fee: %s', 'woocommerce-german-market' ), $fee_name ),
					'taxType'		=> $tax_type,
					'taxRate'		=> $fee_rate,
				),

				$args
			);


		}

	}

	///////////////////////////////////
	// general refund item or rounding ocrrection
	///////////////////////////////////
	if ( $item_sum_refunded < abs( $complete_refund_amount ) ) {

		$amount_of_general_refund = ( abs( $complete_refund_amount ) - $item_sum_refunded ) * ( -1 );

		if ( ! abs( round( $amount_of_general_refund, 2 ) == 0.0 ) ) {

			if ( abs( $amount_of_general_refund ) < 0.02 ) {
				$accountingType= array ( 
					'id' => 41,
					'objectName' => 'AccountingType'
				);
			}

			$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_general_refund', 
				
				array(
					'sum'			=> abs( $amount_of_general_refund ),
					'net'			=> 'false',
					'objectName'	=> 'VoucherPos',
					'accountingType'=> $accountingType,
					'mapAll' 		=> 'true',
					'comment' 		=> trim( __( 'General Refund', 'woocommerce-german-market' ) . ' ' . $refund_reason ),
					'taxType'		=> $tax_type,
					'taxRate'		=> 0,
				),

				$args
			);
		}

	}

	///////////////////////////////////
	// build voucher
	///////////////////////////////////

	$refund_voucher_paid_status = ( $args[ 'order' ]->is_paid() && apply_filters( 'woocommerce_de_sevdesk_mark_refund_as_paid', true ) ) ? 1000 : 100;

	$voucher_description = get_option( 'sevdesk_voucher_description_refund', sevdesk_woocommerce_get_default_value( 'sevdesk_voucher_description_refund' ) );
	$voucher_description = str_replace( '{{order-number}}', $args[ 'order']->get_order_number(), $voucher_description );
	$voucher_description = str_replace( '{{refund-id}}', $refund->get_id(), $voucher_description );

	$voucher = array(
		
		'voucher'=>array(
			'objectName'	=> 'Voucher',
			'mapAll'		=> 'true',
			'voucherDate'	=> apply_filters( 'sevdesk_woocommerce_api_voucher_date', $refund->get_date_created()->format( 'Y-m-d' ), $refund ),
			'description'	=> apply_filters( 'sevdesk_woocommerce_api_voucher_description', $voucher_description, $args ),
			'status'		=> 100,
			'total'			=> abs( $complete_refund_amount ),
			'comment'		=> 'null',
			'payDate'		=> 'null',
			'taxType'		=> $tax_type,
			'creditDebit'	=> 'C',
			'voucherType'	=> 'VOU',
			'currency'		=> $refund->get_currency(),
			'propertyForeignCurrencyDeadline' => $args[ 'order' ]->get_date_created()->getTimestamp(),
		),

		'filename' => $args[ 'temp_file' ],
		'voucherPosSave' => $voucherPos,
		'voucherPosDelete' => 'null'
	);

	// set customer
	if ( ! is_null( $args[ 'customer' ] ) ) {
		$voucher[ 'voucher' ][ 'supplier' ] = $args[ 'customer' ];
	} else {
		$voucher[ 'voucher' ][ 'supplier' ] = null;
		$voucher[ 'voucher' ][ 'supplierName' ] = apply_filters( 'woocommerce_de_sevdesk_supplier_name', trim( $args[ 'order' ]->get_billing_first_name() . ' ' . $args[ 'order' ]->get_billing_last_name() ), $args );
	}

	// filter
	$voucher = apply_filters( 'sevdesk_woocommerce_api_set_voucher', $voucher, $args );

	$ch = curl_init();

	$data = http_build_query( $voucher, '', '&', PHP_QUERY_RFC1738 );

	curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Voucher/Factory/saveVoucher' );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . $args[ 'api_token' ] ,'Content-Type:application/x-www-form-urlencoded' ) );
	curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	$response = curl_exec( $ch );
	$result_array = json_decode( $response, true );

	curl_close( $ch );

	// error handling
	if ( ! isset( $result_array[ 'objects' ][ 'voucher' ][ 'id' ] ) ) {
		if ( isset( $result_array[ 'error' ][ 'message' ] ) ) {
			$error_message = $result_array[ 'error' ][ 'message' ];
		} else {
			$error_message = __( 'Voucher could not be sent', 'woocommerce-german-market' );
		}

		if ( $show_errors ) {
			echo sevdesk_woocommerce_api_get_error_message( $error_message );
			exit();
		} else {
			error_log( 'German Market sevDesk Add-On: ' . $error_message );
			return '';
		}
	}

	$voucher_id = $result_array[ 'objects' ][ 'voucher' ][ 'id' ];

	// if order is paid
	if ( apply_filters( 'woocommerce_de_sevdesk_mark_refund_as_paid', true ) ) {

		$book_account = apply_filters( 'woocommerce_de_sevdesk_check_account', get_option( 'woocommerce_de_sevdesk_check_account', '' ) );

		// individual check account
		if ( get_option( 'woocommerce_de_sevdesk_individual_gateway_check_accounts', 'off' ) == 'on' ) {
			$payment_method_id = $args[ 'order' ]->get_payment_method();
			$gateways = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways[ $payment_method_id ] ) ) {
				$gateway = $gateways[ $payment_method_id ];
				if ( isset( $gateway->settings[ 'sevdesk_check_account' ] ) ) {
					if ( $gateway->settings[ 'sevdesk_check_account' ] != $tax_type ) {
						$book_account = intval( $gateway->settings[ 'sevdesk_check_account' ] );
					}
				}
			}
		}

		$type = sevdesk_woocommerce_get_type_of_check_account( $book_account );
		
		if ( 'offline' === $type ) {

			if ( $book_account != '' ) {
				$completed_date = new DateTime();

				$data = 'Voucher/' . $voucher_id . '/bookAmmount?ammount=' . $complete_refund_amount . '&date=' . $completed_date->format( 'Y-m-d' ) . '&type=null&checkAccount[id]=' . $book_account . '&checkAccount[objectName]=CheckAccount&checkAccountTransaction=null&createFeed=1';

				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . $data );
				curl_setopt( $ch, CURLOPT_PUT, 1 );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
				curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
				$response = curl_exec( $ch );
				curl_close( $ch );
				sevdesk_woocommerce_api_curl_error_validaton( $response );

			}
		}

	}

	return $result_array[ 'objects' ][ 'voucher' ][ 'id' ];

}

/**
* send order as voucher to sevDesk
*
* @param Array $args
* @return String
*/
function sevdesk_woocommerce_api_send_voucher( $args, $show_errors = true ) {
	
	// init
	$order = $args[ 'order' ];
	$voucherPos = array();
	$sum_totals_splitted = array();
	$total_without_fees_and_shipping = 0.0;
	$total_gross = 0.0;
	$item_tax_rates = array();

	// tax free intracommunity delivery OR tax exempt export delivery
	$tax_type = 'default';
	if ( get_option( 'woocommerce_de_kleinunternehmerregelung', 'off' ) != 'on' ) {
		if ( function_exists( 'wcvat_woocommerce_order_details_status' ) ) {
			$tax_exempt_status = wcvat_woocommerce_order_details_status( $order );
			if ( $tax_exempt_status == 'tax_free_intracommunity_delivery' ) {
				$tax_type = 'eu';
			} else if ( $tax_exempt_status == 'tax_exempt_export_delivery' ) {
				$tax_type = 'noteu';
			}
		}
	}

	// 26 == revenues
	// 27 == sales deduction
	// 41 == rounding differences

	///////////////////////////////////
	// build voucher positions, 1st: order items
	///////////////////////////////////
	$accountingType = array ( 
		'id' => apply_filters( 'woocommerce_de_sevdesk_booking_account_order_items', get_option( 'woocommerce_de_sevdesk_booking_account_order_items', 26 ), $args ),
		'objectName' => 'AccountingType'
	);

	foreach ( $order->get_items() as $item ) {

		$line_quantity = floatval( $item[ 'qty' ] );
		$item_tax = $order->get_item_tax( $item, false );

		if ( abs( $order->get_line_total( $item, false, false ) ) > 0 ) {
			$tax_rate = round( ( abs( $item_tax ) * abs( $line_quantity ) ) / abs( $order->get_line_total( $item, false, false ) ) * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );
		} else {
			$tax_rate = 0.0;
		}

		// when coupons are applied or an refund has been made later, tax rate is maybe set to zero, correct it in the following lines
		if ( $tax_rate == 0 || ( $tax_rate != 7 && $tax_rate != 19 && $tax_rate != 0.0 ) ) {
			$item_gross = $order->get_line_subtotal( $item, true, false );
			$item_net 	= $order->get_line_subtotal( $item, false, false );
			$item_tax 	= $item_gross - $item_net;

			if ( $item_net > 0 ) {
				$maybe_tax_rate = round( ( $item_tax ) / $item_net * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );
			} else {
				$maybe_tax_rate = 0;
			}

			if ( $maybe_tax_rate > 0 ) {
				$tax_rate = $maybe_tax_rate;
			}

		}

		if ( $tax_rate == 0 || ( $tax_rate != 7 && $tax_rate != 19 && $tax_rate != 0.0 ) ) {

			if ( method_exists( $order, 'get_line_tax' ) && abs( $order->get_line_tax( $item ) ) > 0.0 ) {

				if ( method_exists( $item, 'get_data' ) ) {

					$item_data	= $item->get_data();
					$item_tax	= array();

					$rate_id 	= false;

					if ( isset( $item_data[ 'taxes' ][ 'subtotal' ] ) ) {
						$item_tax = $item_data[ 'taxes' ][ 'subtotal' ];
					} else if ( isset( $item_data[ 'taxes' ][ 'total' ] ) ) {
						$item_tax = $item_data[ 'taxes' ][ 'total' ];
					}

					if ( ! empty( $item_tax ) ) {

						foreach ( $item_tax as $maybe_rate_id => $tax_amount ) {

							if ( empty( $tax_amount ) ) {
								continue;
							}

							$rate_id 	= $maybe_rate_id;
							break;
						}

					}
				
					if ( $rate_id ) {
						if ( floatval( WC_Tax::get_rate_percent( $rate_id ) ) === 0.0 ) {

							$tax_rate_percents = array();
							$order_taxes = $order->get_taxes();

							foreach ( $order_taxes as $key => $order_tax ) {
								$tax_rate_percents[ $order_tax->get_rate_id() ] = $order_tax->get_rate_percent() . '%';
							}

							if ( isset( $tax_rate_percents[ $rate_id ] ) ) {
								$tax_percent = floatval( $tax_rate_percents[ $rate_id ] );
							}

						} else {
							$tax_rate = floatval( WC_Tax::get_rate_percent( $rate_id ) );
						}
						
					}

				}

			}

		}

		$item_tax_rates[ $item->get_id() ] = $tax_rate;

		if ( ! isset( $sum_totals_splitted[ $tax_rate ] ) ) {
			$sum_totals_splitted[ $tax_rate ] = 0.0;
		}

		$sum_totals_splitted[ $tax_rate ] += $order->get_line_total( $item, false, false );
		
		if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
			$_product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
		} else {
			$_product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
		}

		// get sku
		$sku = '';
		if ( WGM_Helper::method_exists( $_product, 'get_sku' ) ) {
			$sku = $_product->get_sku();
			if ( $sku != '' ) {
				$sku = ' ' . $sku . ' ';
			}
		}

		$order_account_type = $accountingType;

		if ( get_option( 'woocommerce_de_sevdesk_individual_product_booking_accounts', 'off' ) == 'on' ) {

			if ( WGM_Helper::method_exists( $_product, 'get_meta' ) ) {

				$account_product = ( $_product->get_type() == 'variation' ) ? wc_get_product( $_product->get_parent_id() ) : $_product;

				$order_account = intval( $account_product->get_meta( '_sevdesk_field_order_account' ) );

				if ( $order_account > 0 ) {
					$order_account_type = array ( 
						'id' 			=> $order_account,
						'objectName' 	=> 'AccountingType'
					);
				}
			}

		}

		if ( apply_filters( 'sevdesk_woocommerce_api_voucher_use_pos_discount', true ) ) {
			$voucher_pos_sum = $order->get_line_subtotal( $item, false, false );
		} else {
			$voucher_pos_sum = $order->get_line_total( $item, false, false );
		}

		$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos', 
			
			array(
				'sum'			=> $voucher_pos_sum,
				'net'			=> 'false',
				'objectName'	=> 'VoucherPos',
				'accountingType'=> $order_account_type,
				'mapAll' 		=> 'true',
				'comment' 		=> sprintf( _x( '%sx%s%s', 'qty x(times) sku item names', 'woocommerce-german-market' ), $item[ 'qty' ], $sku, $item[ 'name' ] ),
				'taxType'		=> $tax_type,
				'taxRate'		=> $tax_rate,
			),

			$item, $args 

		);
		
		$total_without_fees_and_shipping += $order->get_line_total( $item, false, true );
		
		if ( apply_filters( 'sevdesk_woocommerce_api_voucher_use_pos_discount', true ) ) {
			$total_gross += $order->get_line_subtotal( $item, true, true );
		} else {
			$total_gross += $order->get_line_total( $item, true, true );
		}
	}

	///////////////////////////////////
	// build voucher positions, 2nd: discounts (tax splitted)
	///////////////////////////////////
	$accountingType= array ( 
		'id' => 27,
		'objectName' => 'AccountingType'
	);

	$discount_net_splitted = array();
	$discount_gross_splitted = array();

	foreach ( $order->get_items() as $item ) {

		if ( isset( $item_tax_rates[ $item->get_id() ] ) ) {
			$tax_rate = $item_tax_rates[ $item->get_id() ];
		} else {
			// init
			if ( $order->get_line_total( $item, false, true ) > 0 ) {
				$tax_rate = round( $order->get_line_tax( $item ) / $order->get_line_total( $item, false, true ) * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );
			} else {
				$tax_rate = 0;
			}

			// when coupons are applied, tax rate is maybe set to zero, correct it in the following lines
			if ( $tax_rate == 0 ) {
				$item_gross = $order->get_line_subtotal( $item, true, false );
				$item_net 	= $order->get_line_subtotal( $item, false, false );
				$item_tax 	= $item_gross - $item_net;

				if ( $item_net > 0 ) {
					$maybe_tax_rate = round( ( $item_tax ) / $item_net * 100, 1 );
				} else {
					$maybe_tax_rate = 0;
				}

				if ( $maybe_tax_rate > 0 ) {
					$tax_rate = $maybe_tax_rate;
				}

			}
		}

		if ( ! isset( $discount_net_splitted[ $tax_rate ] ) ) {
			$discount_net_splitted[ $tax_rate ] = 0.0;
		}

		if ( ! isset( $discount_gross_splitted[ $tax_rate ] ) ) {
			$discount_gross_splitted[ $tax_rate ] = 0.0;
		}

		$discount_net 	= $order->get_line_total( $item, false, false ) - $order->get_line_subtotal( $item, false, false );
		$discount_gross	= $order->get_line_total( $item, true, false ) - $order->get_line_subtotal( $item, true, false );

		// continue if there is no disocunt
		if ( ! $discount_net > 0.0 ) {
			continue;
		}

		$discount_net_splitted[ $tax_rate ] += $discount_net;
		$discount_gross_splitted[ $tax_rate ] += $discount_gross;

	}

	foreach ( $discount_net_splitted as $tax_rate => $discount_sum ) {
		
		// continue if there is no discount
		if ( ! $discount_sum > 0.0 ) {
			continue;
		}

		if ( apply_filters( 'sevdesk_woocommerce_api_voucher_use_pos_discount', true ) ) {
			
			$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_discount', 
				
				array(
					'sum'			=> $discount_sum,
					'net'			=> 'false',
					'objectName'	=> 'VoucherPos',
					'accountingType'=> $accountingType,
					'mapAll' 		=> 'true',
					'comment' 		=> __( 'Discount', 'woocommerce-german-market' ),
					'taxType'		=> $tax_type,
					'taxRate'		=> $tax_rate,
				),

				$args 
			);

			$total_gross += round( $discount_sum * ( 100 + $tax_rate ) / 100, 2 );
		}

	}

	///////////////////////////////////
	// build voucher positions, 3rd: shipping (tax splitted)
	///////////////////////////////////
	if ( floatval( $order->get_total_shipping() ) > 0.0 ) {
	
		$accountingType= array ( 
			'id' => apply_filters( 'woocommerce_de_sevdesk_booking_account_order_shipping', get_option( 'woocommerce_de_sevdesk_booking_account_order_shipping', 26 ), $args ),
			'objectName' => 'AccountingType'
		);

		$shipping_split_tax = WGM_Tax::calculate_split_rate( $order->get_total_shipping(), $order, FALSE, '', 'shipping', false, true );

		if ( get_option( 'wgm_use_split_tax', 'on' ) == 'on' ) {

			$shipping_rates = $shipping_split_tax[ 'rates' ];

			foreach ( $shipping_rates as $shipping_rate ) {

				if ( $sum_totals_splitted[ floatval( $shipping_rate[ 'rate' ] ) ] >= 0.0 ) {

					// shipping part net
					$this_shipping_part_net 	= round( $sum_totals_splitted[ floatval( $shipping_rate[ 'rate' ] ) ], 2 ) / $total_without_fees_and_shipping * $order->get_total_shipping();

					$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_shipping', 
					
						array(
							'sum'			=> round( $this_shipping_part_net, 2 ),
							'net'			=> 'false',
							'objectName'	=> 'VoucherPos',
							'accountingType'=> $accountingType,
							'mapAll' 		=> 'true',
							'comment' 		=> sprintf( __( 'Shipping: %s', 'woocommerce-german-market' ), $order->get_shipping_method() ),
							'taxType'		=> $tax_type,
							'taxRate'		=> round( $shipping_rate[ 'rate' ], 1 ),
						),

						$args 
					);

					$total_gross += round( round( $this_shipping_part_net, 2 ) * ( 100 + $shipping_rate[ 'rate' ] ) / 100.0, 2 );

				}

			}

			if ( empty( $shipping_rates ) ) {

				$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_shipping', 
					
					array(
						'sum'			=> $order->get_total_shipping(),
						'net'			=> 'false',
						'objectName'	=> 'VoucherPos',
						'accountingType'=> $accountingType,
						'mapAll' 		=> 'true',
						'comment' 		=> sprintf( __( 'Shipping: %s', 'woocommerce-german-market' ), $order->get_shipping_method() ),
						'taxType'		=> $tax_type,
						'taxRate'		=> 0,
					),

					$args 
				);

				$total_gross += round( $order->get_total_shipping(), 2 );

			}

		} else {

			$shippings = $order->get_shipping_methods();
			
			foreach ( $shippings as $shipping ) {

				$shipping_tax = floatval( $shipping->get_total_tax() );
				$shipping_net = floatval( $shipping->get_total() );

				$tax_rate = round( $shipping_tax / $shipping_net * 100, apply_filters( 'sevdesk_woocommerce_api_vat_rate_rounding', 1 ) );

				$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_shipping', 
					
					array(
						'sum'			=> $shipping_net,
						'net'			=> 'false',
						'objectName'	=> 'VoucherPos',
						'accountingType'=> $accountingType,
						'mapAll' 		=> 'true',
						'comment' 		=> sprintf( __( 'Shipping: %s', 'woocommerce-german-market' ), $shipping->get_method_title() ),
						'taxType'		=> $tax_type,
						'taxRate'		=> $tax_rate,
					),

					$args 
				);

			}

			$total_gross += round( $order->get_total_shipping(), 2 ) + $order->get_shipping_tax();

		}

	}

	///////////////////////////////////
	// build voucher positions, 4th: fees (tax splitted)
	///////////////////////////////////
	$accountingType= array ( 
		'id' => apply_filters( 'woocommerce_de_sevdesk_booking_account_order_fees', get_option( 'woocommerce_de_sevdesk_booking_account_order_fees', 26 ), $args ),
		'objectName' => 'AccountingType'
	);

	// calc total fees
	$fee_total = 0.0;
	$fees = $order->get_fees();
	$fee_names = array();
	foreach ( $fees as $fee ) {
		$fee_names[] = $fee[ 'name' ];
		$fee_total += floatval( $fee[ 'line_total' ] );
	}

	if ( $fee_total > 0.0 ) {

		$fee_label = ( count( $fee_names ) > 1 ) ? __( 'Fees', 'woocommerce-german-market' ) : __( 'Fee', 'woocommerce-german-market' );
		$fee_split_tax = WGM_Tax::calculate_split_rate( $fee_total, $order, FALSE, '', 'fee', false, true );
		$fee_rates = $fee_split_tax[ 'rates' ];

		if ( get_option( 'wgm_use_split_tax', 'on' ) == 'on' && apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee_use_split_tax', true ) ) {

			foreach ( $fee_rates as $fee_rate ) {

				if ( $sum_totals_splitted[ floatval( $fee_rate[ 'rate' ] ) ] >= 0.0 ) {

					// shipping part net
					$this_fee_part_net 	= round( $sum_totals_splitted[ floatval( $fee_rate[ 'rate' ] ) ], 2 ) / $total_without_fees_and_shipping * $fee_total;

					$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee', 
					
						array(
							'sum'			=> round( $this_fee_part_net, 2 ),
							'net'			=> 'false',
							'objectName'	=> 'VoucherPos',
							'accountingType'=> $accountingType,
							'mapAll' 		=> 'true',
							'comment' 		=> sprintf( _x( '%s: %s', 'Example: "Fee: Per Nachnahme" or "Fees: Per Nachnahme, Exportgebühr"', 'woocommerce-german-market' ), $fee_label, implode( ', ', $fee_names ) ),
							'taxType'		=> $tax_type,
							'taxRate'		=> round( $fee_rate[ 'rate' ], 1 ),
						),

						$args 
					);

					$total_gross += round( round( $this_fee_part_net, 2 ) * ( $fee_rate[ 'rate' ] + 100 ) / 100.0, 2 );

				}

			}

			if ( empty( $fee_rates ) ) {

				$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee', 
					
					array(
						'sum'			=> $fee_total,
						'net'			=> 'false',
						'objectName'	=> 'VoucherPos',
						'accountingType'=> $accountingType,
						'mapAll' 		=> 'true',
						'comment' 		=> sprintf( _x( '%s: %s', 'Example: "Fee: Per Nachnahme" or "Fees: Per Nachnahme, Exportgebühr"', 'woocommerce-german-market' ), $fee_label, implode( ', ', $fee_names ) ),
						'taxType'		=> $tax_type,
						'taxRate'		=> 0,
					),

					$args 
				);

				$total_gross += round( $fee_total, 2 );

			}

		} else {

			$fee_label = __( 'Fee', 'woocommerce-german-market' );

			foreach ( $order->get_fees() as $fee ) {

				$tax_rate = round( $fee->get_total_tax() / $fee->get_total() * 100, 2 );

				$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee', 
					
					array(
						'sum'			=> $fee->get_total(),
						'net'			=> 'false',
						'objectName'	=> 'VoucherPos',
						'accountingType'=> apply_filters( 'sevdesk_woocommerce_api_voucher_pos_fee_accounting_type', $accountingType, $fee ),
						'mapAll' 		=> 'true',
						'comment' 		=> sprintf( _x( '%s: %s', 'Example: "Fee: Per Nachnahme" or "Fees: Per Nachnahme, Exportgebühr"', 'woocommerce-german-market' ), $fee_label, $fee->get_name() ),
						'taxType'		=> $tax_type,
						'taxRate'		=> $tax_rate,
					),

					$args 
				);

				$total_gross += round( round( $fee->get_total(), 2 ) * ( $tax_rate + 100 ) / 100.0, 2 );

			}

		}

	}

	///////////////////////////////////
	// build voucher positions, 5th: rounding correction
	///////////////////////////////////

	if ( round( $order->get_total(), 2 ) != round( $total_gross, 2 ) ) {

		$accountingType= array ( 
			'id' => 41,
			'objectName' => 'AccountingType'
		);

		$voucherPos[] = apply_filters( 'sevdesk_woocommerce_api_voucher_pos_shipping', 
			
			array(
				'sum'			=> round( $order->get_total() - $total_gross, 2 ),
				'net'			=> 'false',
				'objectName'	=> 'VoucherPos',
				'accountingType'=> $accountingType,
				'mapAll' 		=> 'true',
				'taxType'		=> $tax_type,
				'taxRate'		=> 0,
				'comment'		=> apply_filters( 'sevdesk_woocommerce_api_voucher_rounding_differences_label', __( 'Rounding differences', 'woocommerce-german-market' ) ),
			),

			$args 
		);

	}

	$total = 0 ;
	foreach ($voucherPos as $pos){
		$total += $pos['sum'];
	}

	///////////////////////////////////
	// build voucher
	///////////////////////////////////
	$status_option = apply_filters( 'woocommerce_de_sevdesk_mark_voucher_as_paid_and_do_check_account', get_option( 'woocommerce_de_sevdesk_payment_status', 'completed' ) == 'completed', $order );
	$voucher_paid_status = ( $order->is_paid() && $status_option ) ? 1000 : 100;

	// Get Voucher Date
	$voucher_date = $order->get_date_created()->format( 'Y-m-d' ); // Date Created of Order
	
	// Try to get invoice date
	$invoice_date = $voucher_date;
	$maybe_invoice_date = $order->get_meta( '_wp_wc_running_invoice_number_date' );
	if ( ! empty( $maybe_invoice_date ) ) {
		$invoice_date_time = new DateTime();
		$invoice_date_time->setTimestamp( $maybe_invoice_date );
		$invoice_date = $invoice_date_time->format( 'Y-m-d' );
	}

	if ( apply_filters( 'sevdesk_woocommerce_api_use_invoice_date_as_voucher_date', false ) || ( get_option( 'woocommerce_de_sevdesk_voucher_date', 'order_date' ) == 'invoice_date' ) ) {
		$voucher_date = $invoice_date;
	}

	$voucher_description = get_option( 'sevdesk_voucher_description_order', sevdesk_woocommerce_get_default_value( 'sevdesk_voucher_description_order' ) );
	$voucher_description = str_replace( '{{order-number}}', $args[ 'order']->get_order_number(), $voucher_description );

	$voucher = array(
		
		'voucher'=>array(
			'objectName'	=> 'Voucher',
			'mapAll'		=> 'true',
			'voucherDate'	=> $voucher_date,
			'description'	=> apply_filters( 'sevdesk_woocommerce_api_voucher_description', $voucher_description, $args ),
			'status'		=> 100,
			'total'			=> $total,
			'comment'		=> 'null',
			'payDate'		=> 'null',
			'taxType'		=> $tax_type,
			'creditDebit'	=> 'D',
			'voucherType'	=> 'VOU',
			'currency'		=> $order->get_currency(),
			'propertyForeignCurrencyDeadline' => $order->get_date_created()->getTimestamp(),
		),

		'filename' => $args[ 'temp_file' ],
		'voucherPosSave' => $voucherPos,
		'voucherPosDelete' => 'null'
	);

	// due date (paymentDeadline)
	$due_date = $args[ 'order' ]->get_meta( '_wgm_due_date' );
	if ( ! empty( $due_date ) ) {
		$voucher[ 'voucher' ][ 'paymentDeadline' ] = $due_date;
	}

	// set customer
	if ( ! is_null( $args[ 'customer' ] ) ) {
		$voucher[ 'voucher' ][ 'supplier' ] = $args[ 'customer' ];
	} else {
		$voucher[ 'voucher' ][ 'supplier' ] = null;
		$voucher[ 'voucher' ][ 'supplierName' ] = apply_filters( 'woocommerce_de_sevdesk_supplier_name', trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ), $args );
	}

	// filter
	$voucher = apply_filters( 'sevdesk_woocommerce_api_set_voucher', $voucher, $args );

	$ch = curl_init();

	$data = http_build_query( $voucher, '', '&', PHP_QUERY_RFC1738 );

	curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Voucher/Factory/saveVoucher' );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . $args[ 'api_token' ] ,'Content-Type:application/x-www-form-urlencoded' ) );
	curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	$response = curl_exec( $ch );
	$result_array = json_decode( $response, true );
	curl_close( $ch );

	// error handling
	if ( ! isset( $result_array[ 'objects' ][ 'voucher' ][ 'id' ] ) ) {
		if ( isset( $result_array[ 'error' ][ 'message' ] ) ) {
			$error_message = $result_array[ 'error' ][ 'message' ];
		} else {
			$error_message = __( 'Voucher could not be sent', 'woocommerce-german-market' );
		}

		if ( $show_errors ) {
			echo sevdesk_woocommerce_api_get_error_message( $error_message );
			exit();
		} else {
			error_log( 'German Market sevDesk Add-On: ' . $error_message );
			return '';
		}
	}

	$voucher_id = $result_array[ 'objects' ][ 'voucher' ][ 'id' ];
	
	// if order is paid
	$status_option = apply_filters( 'woocommerce_de_sevdesk_mark_voucher_as_paid_and_do_check_account', get_option( 'woocommerce_de_sevdesk_payment_status', 'completed' ) == 'completed', $order );
	if ( $order->is_paid() && $status_option ) {

		$book_account = apply_filters( 'woocommerce_de_sevdesk_check_account', get_option( 'woocommerce_de_sevdesk_check_account', '' ) );

		// individual check account
		if ( get_option( 'woocommerce_de_sevdesk_individual_gateway_check_accounts', 'off' ) == 'on' ) {
			$payment_method_id = $order->get_payment_method();
			$gateways = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways[ $payment_method_id ] ) ) {
				$gateway = $gateways[ $payment_method_id ];
				if ( isset( $gateway->settings[ 'sevdesk_check_account' ] ) ) {
					if ( $gateway->settings[ 'sevdesk_check_account' ] != 'default' ) {
						$book_account = intval( $gateway->settings[ 'sevdesk_check_account' ] );
					}
				}
			}
		}

		$type = sevdesk_woocommerce_get_type_of_check_account( $book_account );
		
		if ( 'offline' === $type ) {

			if ( $book_account != '' ) {

				$paid_date = $order->get_date_paid();
				
				if ( ! $paid_date ) {
					$paid_date = $order->get_date_completed();
				}

				if ( ! $paid_date ) {
					$paid_date = $order->get_date_created();
				}

				$sum_gross = 0.0;
				foreach ( $result_array[ 'objects' ][ 'voucherPos' ] as $voucherPos_elem ) {
					$sum_gross += $voucherPos_elem[ 'sumGross' ];
				}

				$data = 'Voucher/' . $voucher_id . '/bookAmmount?ammount=' . $sum_gross . '&date=' . $paid_date->format( 'Y-m-d' ) . '&type=null&checkAccount[id]=' . $book_account . '&checkAccount[objectName]=CheckAccount&checkAccountTransaction=null&createFeed=1';

				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . $data );
				curl_setopt( $ch, CURLOPT_PUT, 1 );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
				curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
				$response = curl_exec( $ch );
				$result_array = json_decode( $response, true );
				curl_close( $ch );

				sevdesk_woocommerce_api_curl_error_validaton( $response );
				$result_array = json_decode( $response, true );

			}
		}

	}

	return $voucher_id;
	
}

/**
* create guest user data in sevDesk
*
* @param Array $args
* @return Integer
*/
function sevdesk_woocommerce_api_contact_guest_user( $args ) {

	$order = $args[ 'order' ];

	// is guest already created?
	$guest_customer_number = $order->get_meta( '_sevdesk_customer_number_guest' );
	$create_customer = empty( $guest_customer_number );

	if ( ( ! $create_customer ) && apply_filters( 'sevdesk_woocomerce_create_guest_user', true ) ) {

		$sevdesk_user = sevdesk_woocommerce_api_contact_get_by_customer_number( $guest_customer_number, $args );
		
		if ( ! is_array( $sevdesk_user ) ) {
			$create_customer = true;
			$order->delete_meta_data( '_sevdesk_customer_number_guest' );
			$order->delete_meta_data( '_sevdesk_customer_id_guest' );
			$order->save_meta_data();
		}

	}

	// Check if a sevdesk user with same email address already exists in sevdesk
	// If so, use this sevdesk user
	$email = $order->get_billing_email();
	$sevdesk_user_id_by_email = sevdesk_woocommerce_api_contact_get_by_email( $email, $args );

	if ( $sevdesk_user_id_by_email ) {

		$customer = sevdesk_woocommerce_api_contact_build_customer_guest_array( $order );

		if ( isset( $customer[ 'customerNumber' ] ) ) {
			unset( $customer[ 'customerNumber' ] );
		}

		$data_customer = http_build_query( $customer, '', '&', PHP_QUERY_RFC1738 );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' . $sevdesk_user_id_by_email );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_customer );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		  'Authorization:' . $args[ 'api_token' ],
		  'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );

		curl_close( $ch );
		sevdesk_woocommerce_api_curl_error_validaton( $response );

		$addresses_and_communication_ways = sevdesk_woocommerce_get_contact_addresses_and_communication_ways( $sevdesk_user_id_by_email, $args );

		sevdesk_woocommerce_api_contact_add_data_guest( 'addEmail', $order, $sevdesk_user_id_by_email, $args, null, true, array(), $addresses_and_communication_ways );
		sevdesk_woocommerce_api_contact_add_data_guest( 'addPhone', $order, $sevdesk_user_id_by_email, $args, null, true, array(), $addresses_and_communication_ways );
		sevdesk_woocommerce_api_contact_add_data_guest( 'addAddress', $order, $sevdesk_user_id_by_email, $args, 47, true, array(), $addresses_and_communication_ways ); // billing address
		sevdesk_woocommerce_api_contact_add_data_guest( 'addAddress', $order, $sevdesk_user_id_by_email, $args, 48, true, array(), $addresses_and_communication_ways); // delivery address

		$response_array 	= json_decode( $response, true );
		$sevdesk_customer 	= $response_array[ 'objects' ];

		$customer_info = sevdesk_woocommerce_api_contact_get_by_customer_number( $sevdesk_customer[ 'customerNumber' ], $args );

		return $customer_info;
	}

	if ( $create_customer && apply_filters( 'sevdesk_woocomerce_create_guest_user', true ) ) {

		// build customer array
		$customer = sevdesk_woocommerce_api_contact_build_customer_guest_array( $order );

		// do we have to create a company first?
		$add_company = apply_filters( 'sevdesk_woocomerce_api_add_company_guest', ( get_option( 'woocommerce_de_sevdesk_customer_add_company', 'on') == 'on' ), $order );

		if ( $add_company ) {
			
			$company = sevdesk_woocommerce_api_contact_build_company_array_guest( $order );
			
			// add company
			if ( is_array( $company ) ) {

				$data = http_build_query( $company, '', '&', PHP_QUERY_RFC1738 );
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt( $ch, CURLOPT_HEADER, FALSE );
				curl_setopt( $ch, CURLOPT_POST, TRUE );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				  'Authorization:' . $args[ 'api_token' ],
				  'Content-Type: application/x-www-form-urlencoded'
				));
				curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
				$response = curl_exec( $ch );
				curl_close( $ch );
				sevdesk_woocommerce_api_curl_error_validaton( $response );

				$response_array = json_decode( $response, true );
				if ( isset( $response_array[ 'objects' ] ) ) {
					$sevdesk_commpany = $response_array[ 'objects' ];

					// add company to customer array
					$customer[ 'parent' ] = array(
						'id' 			=> $sevdesk_commpany[ 'id' ],
						'objectName'	=> 'Contact'
					);
				}
			}
		}

		$data_customer = http_build_query( $customer, '', '&', PHP_QUERY_RFC1738 );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_customer );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		  'Authorization:' . $args[ 'api_token' ],
		  'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );
		curl_close( $ch );
		sevdesk_woocommerce_api_curl_error_validaton( $response );

		// save new sevDesk to order meta
		$response_array 	= json_decode( $response, true );
		$sevdesk_customer 	= $response_array[ 'objects' ];
		$return 			= $sevdesk_customer[ 'customerNumber' ];
		$sevdesk_user_id 	= $sevdesk_customer[ 'id' ];

		$order->add_meta_data( '_sevdesk_customer_number_guest', $sevdesk_customer[ 'customerNumber' ] );
		$order->add_meta_data( '_sevdesk_customer_id_guest',  $sevdesk_user_id );
		$order->save_meta_data();

		// add additional data
		sevdesk_woocommerce_api_contact_add_data_guest( 'addEmail', $order, $sevdesk_user_id, $args );
		sevdesk_woocommerce_api_contact_add_data_guest( 'addPhone', $order, $sevdesk_user_id, $args );
		sevdesk_woocommerce_api_contact_add_data_guest( 'addAddress', $order, $sevdesk_user_id, $args, 47 ); // billing address
		sevdesk_woocommerce_api_contact_add_data_guest( 'addAddress', $order, $sevdesk_user_id, $args, 48 ); // delivery address

		$return = sevdesk_woocommerce_api_contact_get_by_customer_number( $sevdesk_customer[ 'customerNumber' ], $args );

	} else {

		$return = array(
				'id' => apply_filters( 'woocommerce_de_sevdesk_user_id_for_guest_users', $order->get_meta( '_sevdesk_customer_id_guest' ), $order ),
				'objectName' => 'Contact'
			);

	}

	return $return;
}

/**
* create or update user data in sevDesk
*
* @param Integer $wordpress_user_id
* @return Integer
*/
function sevdesk_woocommerce_api_contact( $wordpress_user_id, $args ) {

	$return = null;

	// only if option is activated
	if ( get_option( 'woocommerce_de_sevdesk_send_customer_data', 'off' ) == 'on' ) {	
		
		// check if guest
		if ( $wordpress_user_id == 0 ) {
			
			if ( get_option( 'woocommerce_de_sevdesk_guest_users', 'no' ) == 'yes' ) {
				return sevdesk_woocommerce_api_contact_guest_user( $args );
			} else {
				return apply_filters( 'woocommerce_de_sevdesk_send_customer_guest', null, $args );
			}
		}

		// get sevdesk user
		$sevdesk_user = array();
		$sevdesk_user_customer_number = get_user_meta( $wordpress_user_id, '_sevdesk_customer_number', true );

		// 1st try if user still exists
		if ( $sevdesk_user_customer_number != '' ) {
			$sevdesk_user = sevdesk_woocommerce_api_contact_get_by_customer_number( $sevdesk_user_customer_number, $args );
			if ( ! is_array( $sevdesk_user ) ) {
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer_number' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_user_id' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer_company_number' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_company_id' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer__Email' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer__Phone' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer_billing_Address' );
				delete_user_meta( $wordpress_user_id, '_sevdesk_customer_shipping_Address' );
				$sevdesk_user_customer_number = '';
			} else {

				// re-save id (it may have change when switchen through sevDesk accounts)
				if ( isset( $sevdesk_user[ 'id' ] ) ) {
					$old_user_id = get_user_meta( $wordpress_user_id, '_sevdesk_user_id', true );
					if ( $old_user_id != $sevdesk_user[ 'id' ] ) {
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer_number' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_user_id' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer_company_number' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_company_id' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer__Email' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer__Phone' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer_billing_Address' );
						delete_user_meta( $wordpress_user_id, '_sevdesk_customer_shipping_Address' );
						update_user_meta( $wordpress_user_id, '_sevdesk_user_id', $sevdesk_user[ 'id' ] );	
					}
					
				}
			}

		}

		if ( $sevdesk_user_customer_number == '' ) {

			// Check if a sevdesk user with same email address already exists in sevdesk
			// If so, use this sevdesk user
			if ( isset( $args[ 'order' ] ) ) {
				if ( is_object( $args[ 'order' ] ) && method_exists( $args[ 'order' ], 'get_billing_email' ) ) {
					$email = $args[ 'order' ]->get_billing_email();
					$sevdesk_user_id_by_email = sevdesk_woocommerce_api_contact_get_by_email( $email, $args );
					if ( $sevdesk_user_id_by_email ) {
						$sevdesk_user_customer_number = 'not-wc-user-in-sevdesk'; // just to get in the following -else statement
						update_user_meta( $wordpress_user_id, '_sevdesk_user_id', $sevdesk_user_id_by_email );
					}
				}
			}
		}

		// create a new user
		if ( $sevdesk_user_customer_number == '' ) {

			// build customer array
			$customer = sevdesk_woocommerce_api_contact_build_customer_array( $wordpress_user_id, $args[ 'order' ] );

			// do we have to create a company first?
			$add_company = apply_filters( 'sevdesk_woocomerce_api_add_company', ( get_option( 'woocommerce_de_sevdesk_customer_add_company', 'on') == 'on' ), $wordpress_user_id );

			if ( $add_company ) {
				$company = sevdesk_woocommerce_api_contact_build_company_array( $wordpress_user_id, $args[ 'order' ] );
				
				// add company
				if ( is_array( $company ) ) {

					$data = http_build_query( $company, '', '&', PHP_QUERY_RFC1738 );
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
					curl_setopt( $ch, CURLOPT_HEADER, FALSE );
					curl_setopt( $ch, CURLOPT_POST, TRUE );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
					curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					  'Authorization:' . $args[ 'api_token' ],
					  'Content-Type: application/x-www-form-urlencoded'
					));
					curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
					$response = curl_exec( $ch );
					curl_close( $ch );
					sevdesk_woocommerce_api_curl_error_validaton( $response );

					$response_array = json_decode( $response, true );
					$sevdesk_commpany = $response_array[ 'objects' ];

					// save new sevDesk company data
					update_user_meta( $wordpress_user_id, '_sevdesk_customer_company_number', $sevdesk_commpany[ 'customerNumber' ] );
					update_user_meta( $wordpress_user_id, '_sevdesk_company_id', $sevdesk_commpany[ 'id' ] );

					if ( apply_filters( 'sevdesk_woocommerce_api_add_company_address', false ) ) {
						sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_commpany[ 'id' ], $args, 47 );
						sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_commpany[ 'id' ], $args, 48 );
					}

					do_action( 'sevdesk_woocommerce_api_after_company_build', $sevdesk_commpany, $wordpress_user_id, $args );

					// add company to customer array
					$customer[ 'parent' ] = array(
						'id' 			=> $sevdesk_commpany[ 'id' ],
						'objectName'	=> 'Contact'
					);

				}

			}

			$data_customer = http_build_query( $customer, '', '&', PHP_QUERY_RFC1738 );

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $ch, CURLOPT_HEADER, FALSE );
			curl_setopt( $ch, CURLOPT_POST, TRUE );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_customer );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			  'Authorization:' . $args[ 'api_token' ],
			  'Content-Type: application/x-www-form-urlencoded'
			));
			curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
			$response = curl_exec( $ch );
			curl_close( $ch );
			sevdesk_woocommerce_api_curl_error_validaton( $response );

			// save new sevDesk user data
			$response_array = json_decode( $response, true );
			$sevdesk_customer = $response_array[ 'objects' ];
			update_user_meta( $wordpress_user_id, '_sevdesk_customer_number', $sevdesk_customer[ 'customerNumber' ] );
			update_user_meta( $wordpress_user_id, '_sevdesk_user_id', $sevdesk_customer[ 'id' ] );
			
			$return = $sevdesk_customer[ 'customerNumber' ];
			$sevdesk_user_id = $sevdesk_customer[ 'id' ];

			// add additional data
			sevdesk_woocommerce_api_contact_add_data( 'addEmail', $wordpress_user_id, $sevdesk_user_id, $args );
			sevdesk_woocommerce_api_contact_add_data( 'addPhone', $wordpress_user_id, $sevdesk_user_id, $args );
			sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_user_id, $args, 47 ); // billing address
			sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_user_id, $args, 48 ); // delivery address

			do_action( 'sevdesk_woocommerce_api_after_customer_build', $sevdesk_customer, $wordpress_user_id, $args );

			$return = sevdesk_woocommerce_api_contact_get_by_customer_number( $sevdesk_customer[ 'customerNumber' ], $args );

		} else {
			
			// user exists update all data
			$customer = sevdesk_woocommerce_api_contact_build_customer_array( $wordpress_user_id );

			// never update customer number, sevder user may exists in sevdesk - identified by email
			if ( isset( $customer[ 'customerNumber' ] ) ) {
				unset( $customer[ 'customerNumber' ] );
			}

			$data = http_build_query( $customer, '', '&', PHP_QUERY_RFC1738 );

			$ch = curl_init();
			$sevdesk_user_id = get_user_meta( $wordpress_user_id, '_sevdesk_user_id', true );
			curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' . $sevdesk_user_id );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $ch, CURLOPT_HEADER, FALSE );
			curl_setopt( $ch, CURLOPT_POST, TRUE );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			  'Authorization:' . $args[ 'api_token' ],
			  'Content-Type: application/x-www-form-urlencoded'
			));
			curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
			$response = curl_exec( $ch );
			curl_close( $ch );
			sevdesk_woocommerce_api_curl_error_validaton( $response );

			$addresses_and_communication_ways = sevdesk_woocommerce_get_contact_addresses_and_communication_ways( $sevdesk_user_id, $args );
			
			sevdesk_woocommerce_api_contact_add_data( 'addEmail', $wordpress_user_id, $sevdesk_user_id, $args, null, true, $addresses_and_communication_ways );
			sevdesk_woocommerce_api_contact_add_data( 'addPhone', $wordpress_user_id, $sevdesk_user_id, $args, null, true, $addresses_and_communication_ways );
			sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_user_id, $args, 47, true, $addresses_and_communication_ways ); // billing address
			sevdesk_woocommerce_api_contact_add_data( 'addAddress', $wordpress_user_id, $sevdesk_user_id, $args, 48, true, $addresses_and_communication_ways ); // delivery address

			$return = array(
				'id' => $sevdesk_user_id,
				'objectName' => 'Contact'
			);

		}

	}

	return $return;

}

/**
* build company array from wordpress user_id
*
* @param Integer $wordpress_user_id
* @return Mixed: false (no company) / Array
*/
function sevdesk_woocommerce_api_contact_build_company_array( $wordpress_user_id, $order = null ) {

	// init
	$company = false;
	$company_name = get_user_meta( $wordpress_user_id, 'billing_company', true );

	// if there is a company
	if ( trim( $company_name ) != '' ) {

		$company = array(
			'name'				=> $company_name,
			'customerNumber'	=> get_option( 'woocommerce_de_sevdesk_customer_company_number_prefix', '' ) . $wordpress_user_id,
			'category'			=> array( 
									'id' 			=> 3, // customer
									'objectName'	=> 'Category'
								),
			'name2'				=> '',
			'description'		=> '',
			'vatNumber'			=> apply_filters( 'sevdesk_woocomerce_api_customer_vat_number', sevdesk_get_vat_number_of_order_and_wordpress_user_id( $order, $wordpress_user_id ) ),
			'bankAccount'		=> '',
			'bankNumber'		=> ''
		);

		$company = apply_filters( 'sevdesk_woocomerce_api_customer_company_array', $company, $wordpress_user_id );

	}

	return $company;

}

/**
* build company array from order for guest users
*
* @param Integer $wordpress_user_id
* @return Mixed: false (no company) / Array
*/
function sevdesk_woocommerce_api_contact_build_company_array_guest( $order ) {

	// init
	$company = false;
	$company_name = $order->get_billing_company();

	// if there is a company
	if ( trim( $company_name ) != '' ) {

		$company = array(
			'name'				=> $company_name,
			'customerNumber'	=> get_option( 'woocommerce_de_sevdesk_customer_company_number_prefix', '' ) . get_option( 'woocommerce_de_sevdesk_customer_guest_prefix', __( 'Guest-', 'woocommerce-german-market' ) ) . $order->get_order_number(),
			'category'			=> array( 
									'id' 			=> 3, // customer
									'objectName'	=> 'Category'
								),
			'name2'				=> '',
			'description'		=> '',
			'vatNumber'			=> apply_filters( 'sevdesk_woocomerce_api_customer_vat_number', sevdesk_get_vat_number_of_order_and_wordpress_user_id( $order ) ),
			'bankAccount'		=> '',
			'bankNumber'		=> ''
		);

		$company = apply_filters( 'sevdesk_woocomerce_api_customer_company_array_guest', $company, $order );

	}

	return $company;

}

/**
* build customer array from order for guest user
*
* @param WC_Order
* @return Array
*/
function sevdesk_woocommerce_api_contact_build_customer_guest_array( $order ) {
	
	// because some admins did not saved first and last name
	$last_name = $order->get_billing_last_name();
	$first_name = $order->get_billing_first_name();

	$customer =  array(
		'familyname'		=> $last_name,
		'surename'			=> $first_name,
		'customerNumber'	=> get_option( 'woocommerce_de_sevdesk_customer_number_prefix', '' ) . get_option( 'woocommerce_de_sevdesk_customer_guest_prefix', __( 'Guest-', 'woocommerce-german-market' ) ) . $order->get_order_number(),
		'category'			=> array( 
									'id' 			=> 3, // customer
									'objectName'	=> 'Category'
								), 
		'birthday'			=> null,
		'title'				=> null,
		'academicTitle' 	=> null,
		'gender'			=> null,
		'name2'				=> null,
		'description'		=> null,
		'vatNumber'			=> apply_filters( 'sevdesk_woocomerce_api_customer_vat_number', sevdesk_get_vat_number_of_order_and_wordpress_user_id( $order ) ),
		'bankAccount'		=> null,
		'bankNumber'		=> null,
	);

	return apply_filters( 'sevdesk_woocomerce_api_customer_guest_array', $customer, $order );

}

/**
* build customer array from wordpress user_id
*
* @param Integer $wordpress_user_id
* @return Array
*/
function sevdesk_woocommerce_api_contact_build_customer_array( $wordpress_user_id, $order = null ) {

	$user_data = get_userdata( $wordpress_user_id );
	
	// because some admins did not saved first and last name
	$last_name = $user_data->last_name != '' ? $user_data->last_name : get_user_meta( $wordpress_user_id, 'billing_last_name', true );
	$first_name = $user_data->first_name != '' ? $user_data->first_name : get_user_meta( $wordpress_user_id, 'billing_first_name', true );

	$customer =  array(
		'familyname'		=> $last_name,
		'surename'			=> $first_name,
		'customerNumber'	=> get_option( 'woocommerce_de_sevdesk_customer_number_prefix', '' ) . $wordpress_user_id,
		'category'			=> array( 
									'id' 			=> 3, // customer
									'objectName'	=> 'Category'
								), 
		'birthday'			=> null,
		'title'				=> null,
		'academicTitle' 	=> null,
		'gender'			=> null,
		'name2'				=> null,
		'description'		=> null,
		'vatNumber'			=> apply_filters( 'sevdesk_woocomerce_api_customer_vat_number', sevdesk_get_vat_number_of_order_and_wordpress_user_id( $order, $wordpress_user_id ) ),
		'bankAccount'		=> null,
		'bankNumber'		=> null,
	);

	return apply_filters( 'sevdesk_woocomerce_api_customer_array', $customer, $wordpress_user_id );

}

/**
* get customer vat number by order or wordpress_user_id
*
* @param Integer $wordpress_user_id
* @return Array
*/
function sevdesk_get_vat_number_of_order_and_wordpress_user_id( $order = null, $wordpress_user_id = null ) {

	$vat_number = null;
	$maybe_vat_number = '';

	if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {
		$maybe_vat_number = $order->get_meta( 'billing_vat' );
	}

	if ( empty( $maybe_vat_number ) && ( ! is_null( $wordpress_user_id ) ) ) {
		$maybe_vat_number = get_user_meta( $wordpress_user_id, 'billing_vat', true );
	}

	if ( ! empty( $maybe_vat_number ) ) {
		$vat_number = $maybe_vat_number;
	}

	return $vat_number;
}

/**
* add additional customer data
*
* @param String $endpoint
* @param Integer $wordpress_user_id
* @param Integer $sevdesk_user_id
* @param Array $args
* @param Integer $address_category
* @return Array
*/
function sevdesk_woocommerce_api_contact_add_data( $endpoint, $wordpress_user_id, $sevdesk_user_id, $args, $address_category = 47, $update = false, $addresses_and_communication_ways = array() ) {

	$user_data = get_userdata( $wordpress_user_id );
	$post_meta_prefix = '';

	if ( $endpoint == 'addEmail' ) {

		$data = array(
			'key'	=> 2, // work
			'value'	=> $user_data->user_email,
			'type'	=> 2
		);

	} else if ( $endpoint == 'addPhone' ) {

		$data = array(
			'key'	=> 2, // work
			'value'	=> get_user_meta( $wordpress_user_id, 'billing_phone', true ),
			'type'	=> 2
		);

	} else if ( $endpoint == 'addAddress' ) {

		$post_meta_prefix = $address_category == 48 ? 'shipping' : 'billing';

		// get country
		$user_country = strtolower( get_user_meta( $wordpress_user_id, $post_meta_prefix . '_country', true ) );

		// get all country codes to get the id of the country
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'StaticCountry/?limit=999' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization:' . $args[ 'api_token' ],
			'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );
		curl_close( $ch );
		sevdesk_woocommerce_api_curl_error_validaton( $response );
		$response_array = json_decode( $response, true );
		$countries = $response_array[ 'objects' ];

		$data = array(
			'street'	=> trim( get_user_meta( $wordpress_user_id, $post_meta_prefix . '_address_1', true ) . ' ' . get_user_meta( $wordpress_user_id, $post_meta_prefix . '_address_2', true ) ),
			'zip'		=> get_user_meta( $wordpress_user_id, $post_meta_prefix . '_postcode', true ),
			'city'		=> get_user_meta( $wordpress_user_id, $post_meta_prefix . '_city', true ),
			'category'	=> $address_category,
			'type'		=> $address_category,
		);

		$data[ 'contact' ] = array(
			'id' => $sevdesk_user_id,
			'objectName' => 'Contact'
		);

		// get country
		$not_add_country = empty( trim( $data[ 'street' ] ) ) && empty( trim( $data[ 'zip' ] ) ) && empty( trim( $data[ 'city' ] ) );
		
		// don't add adress if no adress is set at all
		if ( $not_add_country ) {
			return;
		}

		// pretend to be from Germany if we will not find the correct country
		$data[ 'country' ] = 1;

		foreach ( $countries as $country ) {
			// attention: a WooCommerce country code always consists of 2 letters (even if it should be 3)
			if ( strtolower( substr( $country[ 'code' ], 0, 2 ) ) == strtolower( $user_country ) ) {
				$data[ 'country' ] = $country[ 'id' ];
				break;
			}
		}

	}

	$post_meta_key = str_replace( 'add', '_sevdesk_customer_' . $post_meta_prefix . '_', $endpoint );

	$data = apply_filters( 'sevdesk_woocomerce_api_customer_data_before_send', $data, $endpoint, $wordpress_user_id, $sevdesk_user_id, $args, $address_category, $update );

	if ( $update ) {
		$new_data = sevdesk_woocommerce_update_sevdesk_user_data( $sevdesk_user_id, $data, $endpoint, $args, $address_category, $addresses_and_communication_ways );
		if ( $new_data ) {
			$update = false;
		}
	}

	if ( ! $update ) {

		// add data
		$data = http_build_query( $data, '', '&', PHP_QUERY_RFC1738 );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' . $sevdesk_user_id . '/' . $endpoint );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization:' . $args[ 'api_token' ],
			'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );
		curl_close( $ch );

		sevdesk_woocommerce_api_curl_error_validaton( $response );

		// Save id of CommunicationWay to update this data later
		$response_array = json_decode( $response, true );
		$id = $response_array[ 'objects' ][ 'id' ];
		update_user_meta( $wordpress_user_id, $post_meta_key, $id );

	}
}

/**
* add additional customer data for guest users
*
* @param String $endpoint
* @param Integer $wordpress_user_id
* @param Integer $sevdesk_user_id
* @param Array $args
* @param Integer $address_category
* @return Array
*/
function sevdesk_woocommerce_api_contact_add_data_guest( $endpoint, $order, $sevdesk_user_id, $args, $address_category = 47, $update = false, $user_data = array(), $addresses_and_communication_ways = array() ) {

	$post_meta_prefix = '';

	if ( $endpoint == 'addEmail' ) {

		$data = array(
			'key'	=> 2, // work
			'value'	=> $order->get_billing_email(),
			'type'	=> 2
		);

	} else if ( $endpoint == 'addPhone' ) {

		$data = array(
			'key'	=> 2, // work
			'value'	=> $order->get_billing_phone(),
			'type'	=> 2
		);

	} else if ( $endpoint == 'addAddress' ) {

		$post_meta_prefix = $address_category == 48 ? 'shipping' : 'billing';

		// get country
		if ( $post_meta_prefix == 'shipping' ) {
			$user_country = $order->get_shipping_country();
		} else {
			$user_country = $order->get_billing_country();
		}

		// get all country codes to get the id of the country
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'StaticCountry/?limit=999' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization:' . $args[ 'api_token' ],
			'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );
		curl_close( $ch );
		sevdesk_woocommerce_api_curl_error_validaton( $response );
		$response_array = json_decode( $response, true );
		$countries = $response_array[ 'objects' ];

		if ( $post_meta_prefix == 'shipping' ) {
			
			$data = array(
				'street'	=> trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() ),
				'zip'		=> $order->get_shipping_postcode(),
				'city'		=> $order->get_shipping_city(),
				'category'	=> $address_category,
				'type'		=> $address_category,
			);

		} else {

			$data = array(
				'street'	=> trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() ),
				'zip'		=> $order->get_billing_postcode(),
				'city'		=> $order->get_billing_city(),
				'category'	=> $address_category,
				'type'		=> $address_category,
			);

		}

		$data[ 'contact' ] = array(
			'id' => $sevdesk_user_id,
			'objectName' => 'Contact'
		);

		// get country
		$not_add_country = empty( trim( $data[ 'street' ] ) ) && empty( trim( $data[ 'zip' ] ) ) && empty( trim( $data[ 'city' ] ) );
		
		// don't add adress if no adress is set at all
		if ( $not_add_country ) {
			return;
		}

		// pretend to be from Germany if we will not find the correct country
		$data[ 'country' ] = 1;

		foreach ( $countries as $country ) {
			// attention: a WooCommerce country code always consists of 2 letters (even if it should be 3)
			if ( strtolower( substr( $country[ 'code' ], 0, 2 ) ) == strtolower( $user_country ) ) {
				$data[ 'country' ] = $country[ 'id' ];
				break;
			}
		}

	}

	$post_meta_key = str_replace( 'add', '_sevdesk_customer_' . $post_meta_prefix . '_', $endpoint );
	
	$data = apply_filters( 'sevdesk_woocomerce_api_customer_data_before_send_guest', $data, $endpoint, $order, $sevdesk_user_id, $args, $address_category, $update );

	if ( $update ) {
		$new_data = sevdesk_woocommerce_update_sevdesk_user_data( $sevdesk_user_id, $data, $endpoint, $args, $address_category, $addresses_and_communication_ways );
		if ( $new_data ) {
			$update = false;
		}
	}

	if ( ! $update ) {

		// add data
		$data = http_build_query( $data, '', '&', PHP_QUERY_RFC1738 );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/' . $sevdesk_user_id . '/' . $endpoint );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization:' . $args[ 'api_token' ],
			'Content-Type: application/x-www-form-urlencoded'
		));
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		$response = curl_exec( $ch );
		curl_close( $ch );

		sevdesk_woocommerce_api_curl_error_validaton( $response );

		// Save id of CommunicationWay to update this data later
		$response_array = json_decode( $response, true );
		$id = $response_array[ 'objects' ][ 'id' ];
	}
}

/**
* this functions checks if the value of an endpoint (email, phone, address)
* already exists for a sevdesk user. It returns true if this data is new.
*
* @param String $sevdes_user_id
* @param Array $data
* @param String $endpoint
* @param Array $args
* @param Integer $address_category
* @return Boolean
*/
function sevdesk_woocommerce_update_sevdesk_user_data( $sevdesk_user_id, $data, $endpoint, $args, $address_category, $user_data ) {

	$new_data = false;

	if ( 'addEmail' === $endpoint ) {

		if ( isset( $user_data[ 'email' ] ) ) {
			$email = $data[ 'value' ];
			if ( ! in_array( $email, $user_data[ 'email' ] ) ) {
				$new_data = true;
			}
		}

	} else if ( 'addPhone' === $endpoint ) {

		if ( isset( $user_data[ 'phone' ] ) ) {
			$phone = $data[ 'value' ];
			if ( ! in_array( $phone, $user_data[ 'phone' ] ) ) {
				$new_data = true;
			}
		}

	} else if ( 'addAddress' === $endpoint ) {
		
		$key = false;

		if ( 47 === $address_category ) {
			$key = 'billing_address';
		} else if ( 48 === $address_category ) {
			$key = 'shipping_address';
		}

		if ( isset( $user_data[ $key ] ) ) {
			
			if ( isset( $data[ 'street' ] ) && isset( $data[ 'zip' ] ) && isset( $data[ 'city' ] ) ) {

				$found_address = false;

				foreach ( $user_data[ $key ] as $address ) {
					
					if (	
							isset( $address[ 'street' ] ) && isset( $address[ 'zip' ] ) && isset( $address[ 'city' ] ) &&
							( 
								trim( $address[ 'street' ] ) == trim( $data[ 'street' ] ) &&
								trim( $address[ 'zip' ] ) == trim( $data[ 'zip' ] ) &&
								trim( $address[ 'city' ] ) == trim( $data[ 'city' ] )
							)
					) {

						$found_address = true;
						break;
						
					}
				}

				if ( ! $found_address ) {
					$new_data = true;
				}
			}
		}

	}

	return $new_data;
}

/**
* get sevdesk_user bei sevdesk_user_id
*
* @param Integer $sevdesk_user_id
* @return -1 OR Array
*/
function sevdesk_woocommerce_api_contact_get_by_customer_number( $sevdesk_customer_number, $args ) {

	$return = -1;

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'Contact/?customerNumber=' . $sevdesk_customer_number . '&depth=true' );
	curl_setopt( $ch, CURLOPT_POST, 0 );
	curl_setopt( $ch,CURLOPT_HTTPHEADER,array( 'Authorization:' . $args[ 'api_token' ] ) );
	curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$response = curl_exec( $ch );
	curl_close( $ch );
	$result_array = json_decode( $response, true );

	if ( isset( $result_array[ 'objects' ][ 0 ][ 'id' ] ) ) {
		$return = $result_array[ 'objects' ][ 0 ];
	}

	return $return;
}

/**
* get all address data and communication ways (phone & email) of a sevdesk user
*
* @param String $sevdesk_user_id
* @param Array $args
* @return Array
*/
function sevdesk_woocommerce_get_contact_addresses_and_communication_ways( $sevdesk_user_id, $args ) {

	$data = array(
		'phone' => array(),
		'email' => array(),
		'billing_address' => array(),
		'shipping_address' => array(),
	);

	// communication ways
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'CommunicationWay/?contact[objectName]=Contact&contact[id]=' . $sevdesk_user_id );
	curl_setopt( $ch, CURLOPT_POST, 0 );
	curl_setopt( $ch,CURLOPT_HTTPHEADER,array( 'Authorization:' . $args[ 'api_token' ] ) );
	curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$response = curl_exec( $ch );
	curl_close( $ch );
	
	$result_array = json_decode( $response, true );
	if ( isset( $result_array[ 'objects' ] ) ) {
		foreach ( $result_array[ 'objects' ] as $communication_way ) {
			if ( isset( $communication_way[ 'type' ] ) && 'PHONE' === $communication_way[ 'type' ] ) {
				$data[ 'phone' ][] = $communication_way[ 'value' ];
			} else if ( isset( $communication_way[ 'type' ] ) && 'EMAIL' === $communication_way[ 'type' ] ) {
				$data[ 'email' ][] = $communication_way[ 'value' ];
			}
		}
	}

	// contact addresses
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'ContactAddress/?contact[objectName]=Contact&contact[id]=' . $sevdesk_user_id );
	curl_setopt( $ch, CURLOPT_POST, 0 );
	curl_setopt( $ch,CURLOPT_HTTPHEADER,array( 'Authorization:' . $args[ 'api_token' ] ) );
	curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$response = curl_exec( $ch );
	$result_array = json_decode( $response, true );
	curl_close( $ch );

	if ( isset( $result_array[ 'objects' ] ) ) {
		foreach ( $result_array[ 'objects' ] as $address ) {
			if ( isset( $address[ 'category' ][ 'id' ] ) ) {
				
				$key = false;

				if ( 47 === intval( $address[ 'category' ][ 'id' ] ) ) {
					$key = 'billing_address';
				} else if ( 48 === intval( $address[ 'category' ][ 'id' ] ) ) {
					$key = 'shipping_address';
				}

				if ( $key ) {

					$data[ $key ][] = array(
						'street'	=> $address[ 'street' ],
						'zip'		=> $address[ 'zip' ],
						'city'		=> $address[ 'city' ],
						'category'	=> $address[ 'category' ][ 'id' ],
					);

				}

					
			}
		}
	}

	return $data;
}

/**
* check if a sevdesk user with the same email exists
* and return the sevdesk user id
*
* @param String $email
* @param Array $args
* @return String
*/
function sevdesk_woocommerce_api_contact_get_by_email( $email, $args ) {

	$sevdesk_user_id = false;

	if ( apply_filters( 'sevdesk_woocomerce_get_sevdesk_contact_by_email_before_creating_new_contact', true ) ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $args[ 'base_url' ] . 'CommunicationWay/?value=' . $email . '&depth=true' );
		curl_setopt( $ch, CURLOPT_POST, 0 );
		curl_setopt( $ch,CURLOPT_HTTPHEADER,array( 'Authorization:' . $args[ 'api_token' ] ) );
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$response = curl_exec( $ch );
		curl_close( $ch );
		$result_array = json_decode( $response, true );

		if ( isset( $result_array[ 'objects' ] ) && is_array( $result_array[ 'objects' ] ) ) {
			foreach ( $result_array[ 'objects' ] as $result_element ) {
				if ( isset( $result_element[ 'contact' ][ 'id' ] ) ) {
					$sevdesk_user_id = $result_element[ 'contact' ][ 'id' ];
					break;
				}
			}
		}			
	}

	return $sevdesk_user_id;
}

/**
* build temp file of invoice pdf
*
* @param Array $args
* @return String
*/
function sevdesk_woocommerce_api_build_temp_file( $args, $show_errors = true ) {

	$attachment = $args[ 'invoice_pdf' ];

	$cfile = new CURLFile( $attachment  );

	$post = array (
	    'file' => $cfile,
	);

	$curl = curl_init();

	curl_setopt_array( $curl, array(
	  CURLOPT_URL => $args[ 'base_url' ] . 'Voucher/Factory/uploadTempFile',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $post,
	  CURLOPT_HTTPHEADER => array(
	    'accept: application/json',
	    'authorization: ' . $args[ 'api_token' ],
	    'cache-control: no-cache',
	    'content-type: multipart/form-data;',
	  ),
	  CURLOPT_USERAGENT => sevdesk_woocommerce_get_user_agent(),
	) );
                                                                                                                                                                                                             
	$response = curl_exec( $curl );
	$error = curl_error( $curl );
	curl_close ( $curl );

	$response_array = json_decode( $response, true );

	// error handling
	if ( ! isset( $response_array[ 'objects' ][ 'filename' ] ) ) {

		if ( $error != '' ) {
			echo sevdesk_woocommerce_api_get_error_message( $error );
		} else {

			if ( isset( $response_array[ 'message' ] ) && ( 'Authentication required' === $response_array[ 'message' ] ) ) {
				$error_message = __( 'Authentication required. Please check the validity of the API token in the settings of the sevDesk add-on and check the validity of your sevDesk account.', 'woocommerce-german-market' );
			} else {
				$error_message = __( 'Failed to upload invoice pdf.', 'woocommerce-german-market' );
			}

			if ( $show_errors ) {
				echo sevdesk_woocommerce_api_get_error_message( $error_message );
				exit();
			} else {
				error_log( 'German Market sevDesk Add-On: ' . $error_message );
				return '';
			}
		}
	}

	return $response_array[ 'objects' ][ 'filename' ];
	
} 

/**
* get voucher status (exists or not)
*
* @param Integer $args
* @return Boolean
*/
function sevdesk_woocommerce_api_get_vouchers_status( $voucher_id, $show_errors = true ) {

	$curl = curl_init();

	curl_setopt_array( $curl, array(
	  CURLOPT_URL => sevdesk_woocommerce_api_get_base_url() . 'Voucher/' . $voucher_id,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
	    'accept: application/json',
	    'authorization: ' . sevdesk_woocommerce_api_get_api_token( $show_errors ),
	    'cache-control: no-cache',
	  ),
	  CURLOPT_USERAGENT => sevdesk_woocommerce_get_user_agent(),
	) );

	$response = curl_exec( $curl );
	$response_array = json_decode( $response, true );

	if ( isset( $response_array[ 'error' ][ 'code' ] ) && $response_array[ 'error' ][ 'code' ] == 151 ) {
		return false;
	}

	return true;

}

/**
* Get api token
* @return String
*/
function sevdesk_woocommerce_api_get_api_token( $show_errors = true ) {

	$api_token = apply_filters( 'sevdesk_woocomerce_api_get_api_token', get_option( 'woocommerce_de_sevdesk_api_token', '' ) );
	
	if ( $api_token == '' && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		
		$error_message = __( 'There is no API token. Please go to the WooCommerce German Market settings and enter a valid API token.', 'woocommerce-german-market' );

		if ( $show_errors ) {
			echo sevdesk_woocommerce_api_get_error_message( $error_message );
			exit();
		} else {
			error_log( 'German Market sevDesk Add-On: ' . $error_message );
		}
	}

	return $api_token;
}

/**
* Get invoice pdf, path to file
* @param WC_Order $order
* @return String
*/
function sevdesk_woocommerce_api_get_invoice_pdf( $order ) {

	if ( ! class_exists( 'WP_WC_Invoice_Pdf_Create_Pdf' ) ) {
		echo sevdesk_woocommerce_api_get_error_message( __( 'Modul Invoice PDF of WooCommerce German Market is not enabled.', 'woocommerce-german-market' ) );
		exit();
	}

	WGM_Compatibilities::wpml_invoice_pdf_switch_lang_for_online_booking( array( 'order' => $order, 'admin' => 'true' ) );

	$args = array( 
			'order'				=> $order,
			'output_format'		=> 'pdf',
			'output'			=> 'cache',
			'filename'			=> str_replace( '/', '-', apply_filters( 'wp_wc_invoice_pdf_frontend_filename', get_option( 'wp_wc_invoice_pdf_file_name_frontend', get_bloginfo( 'name' ) . '-' . __( 'Invoice-{{order-number}}', 'woocommerce-invoice-pdf' ) ), $order ) ),
			'admin'				=> 'true',
		);
		
	$invoice 	= new WP_WC_Invoice_Pdf_Create_Pdf( $args );
  	$attachment = WP_WC_INVOICE_PDF_CACHE_DIR . $invoice->cache_dir . DIRECTORY_SEPARATOR . $invoice->filename;

  	WGM_Compatibilities::wpml_invoice_pdf_reswitch_lang_for_online_booking();

  	return $attachment;
} 

/**
* Get refund pdf, path to file
* @param WC_Order $refund
* @return String
*/
function sevdesk_woocommerce_api_get_refund_pdf( $refund ) {

	if ( ! class_exists( 'WP_WC_Invoice_Pdf_Create_Pdf' ) ) {
		echo sevdesk_woocommerce_api_get_error_message( __( 'Modul Invoice PDF of WooCommerce German Market is not enabled.', 'woocommerce-german-market' ) );
		exit();
	}

	// init
	$refund_id 	= $refund->get_id();
	$order_id 	= $refund->get_parent_id();
	$order 		= wc_get_order( $order_id );

	WGM_Compatibilities::wpml_invoice_pdf_switch_lang_for_online_booking( array( 'order' => $order, 'admin' => 'true' ) );

	do_action( 'wp_wc_invoice_pdf_before_refund_backend_download', $refund_id );

	add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

	// get filename
	$filename = get_option( 'wp_wc_invoice_pdf_refund_file_name_backend', 'Refund-{{refund-id}} for order {{order-number}}' );
	// replace {{refund-id}}, the other placeholders will be managed by the class WP_WC_Invoice_Pdf_Create_Pdf
	$filename = str_replace( '{{refund-id}}', $refund_id, $filename );
	$filename = apply_filters( 'wp_wc_invoice_pdf_refund_backend_filename', $filename, $refund );

	$args = array( 
				'order'				=> $order,
				'refund'			=> $refund,
				'output_format'		=> 'pdf',
				'output'			=> 'cache',
				'filename'			=> str_replace( '/', '-', $filename ),
				'admin'				=> 'true',
			);
	
	$refund = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
	$attachment = WP_WC_INVOICE_PDF_CACHE_DIR . $refund->cache_dir . DIRECTORY_SEPARATOR . $refund->filename;

	remove_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

  	WGM_Compatibilities::wpml_invoice_pdf_reswitch_lang_for_online_booking();

	return $attachment;
} 

/**
* check if we can use the order
* @param WC_Order $order
* @return WC_Order
*/
function sevdesk_woocommerce_api_check_order( $order ) {

	$error = '';

	$error = apply_filters( 'sevdesk_woocommerce_api_check_order', $error, $order );

	if ( $error != '' ) {
		echo sevdesk_woocommerce_api_get_error_message( $error );
		exit();
	}

	return $order;

}

/**
* Markup for error message
* @param String $message
* @return String
*/
function sevdesk_woocommerce_api_get_error_message( $message = '' ) {
	
	if ( $message == '' ) {
		$message = __( 'Unknown error.', 'woocommerce-german-market' );
	}
	
	return trim( __( '<b>ERROR:</b>', 'woocommerce-german-market' ) . ' ' . $message );
}

/**
* Check if curl response is an error
* @param String $response
* @return void (exit if error)
*/
function sevdesk_woocommerce_api_curl_error_validaton( $response ) {

	$response_array = json_decode( $response, true );
	if ( isset( $response_array[ 'error' ] ) ) {
		
		if ( $response_array[ 'error' ][ 'message' ] == 'No CheckaccountTransaction for online checkaccount given' ) {
			return;	
		}

		echo sevdesk_woocommerce_api_get_error_message( $response_array[ 'error' ][ 'message' ] );

		exit();
	}

}

/**
* get base_url
* @return String
*/
function sevdesk_woocommerce_api_get_base_url() {
	return apply_filters( 'sevdesk_woocommerce_api_get_base_url', 'https://my.sevdesk.de/api/v1/' );
}

/**
* get default value for strings of options 'sevdesk_voucher_description_order' or 'sevdesk_voucher_description_reund'
* depending on the former setting 'woocommerce_de_sevdesk_voucher_number'
*
* @since 3.9.2
* @param String $option_key
* @return String
*/
function sevdesk_woocommerce_get_default_value( $option_key ) {

	$default_value = '';

	if ( $option_key == 'sevdesk_voucher_description_order' ) {

		$default_value = __( 'Order #{{order-number}}', 'woocommerce-german-market' );
		if ( class_exists( 'Woocommerce_Running_Invoice_Number' ) && ( get_option( 'woocommerce_de_sevdesk_voucher_number', 'order_number' ) == 'invoice_number' ) ) {
			$default_value = __( 'Invoice {{invoice-number}}', 'woocommerce-german-market' );
		}

	} else if ( $option_key == 'sevdesk_voucher_description_refund' ) {

		$default_value = __( 'Refund #{{refund-id}} for Order #{{order-number}}', 'woocommerce-german-market' );
		if ( class_exists( 'Woocommerce_Running_Invoice_Number' ) && ( get_option( 'woocommerce_de_sevdesk_voucher_number', 'order_number' ) == 'invoice_number' ) ) {
			$default_value = __( 'Refund {{refund-number}} for Invoice {{invoice-number}}', 'woocommerce-german-market' );
		}

	}

	return $default_value;

}

/**
* Get type of check account
*
* @since 3.11.1.4
* @param Integer $checkaccount_id
* @return String
**/ 
function sevdesk_woocommerce_get_type_of_check_account( $checkaccount_id ) {

	$type = 'offline';

	$check_accounts = get_transient( 'german_market_sevdesk_checkaccounts' );
	
	if ( ! is_array( $check_accounts ) ) {

		$check_accounts = array();

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, sevdesk_woocommerce_api_get_base_url() . 'CheckAccount/?register=0' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
		curl_setopt( $ch, CURLOPT_USERAGENT, sevdesk_woocommerce_get_user_agent() );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$response = curl_exec( $ch );
		$result_array = json_decode( $response, true );
		curl_close( $ch );

		if ( isset ( $result_array[ 'objects' ] ) ) {
			$check_accounts = $result_array[ 'objects' ];
			set_transient( 'german_market_sevdesk_checkaccounts', $check_accounts, MINUTE_IN_SECONDS );
		}

	}

	foreach ( $check_accounts as $check_account ) {
		if ( isset( $check_account[ 'id' ] ) ) {
			if ( intval( $checkaccount_id ) === intval( $check_account[ 'id' ] ) ) {
				if ( isset( $check_account[ 'type' ] ) ) {
					$type = $check_account[ 'type' ];
					break;
				}
			}
		}

	}

	return $type;
}

/**
* Get User Agent for CUrl
*
* @since 3.16
* @return String
**/
function sevdesk_woocommerce_get_user_agent() {
	return 'MarketPress German Market';
}

