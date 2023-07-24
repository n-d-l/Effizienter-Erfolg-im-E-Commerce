<?php

/**
 * Class WGM_Fee
 *
 * This class will fix the taxes for fee "Nachnahme" in "review-order"
 * and after creating the order
 *
 * @author  ChriCo
 */
class WGM_Fee {

	/**
	 * Adding Fee to gateway-Page second-checkout to display the taxes
	 *
	 * @wp-hook woocommerce_cart_calculate_fees
	 *
	 * @param   WC_Cart $cart
	 * @return  void
	 */
	public static function add_fee_to_gateway_page( WC_Cart $cart ) {
		
		if ( WC()->payment_gateways ) {
			$avail        = WC()->payment_gateways->get_available_payment_gateways();
			$chosen       = WC()->session->chosen_payment_method;
			$wgm_gateways = WGM_Gateways::get_gateway_fees();

			if ( isset( $wgm_gateways[ $chosen ], $avail[ $chosen ] ) ) {
				$g              = $avail[ $chosen ];
				$title          = __( $g->title, 'woocommerce-german-market' );
				$fee            = str_replace( ',', '.', $wgm_gateways[ $chosen ] );
				$tax_class_hack = 'wgm_' . $fee;

				$taxable        = ( get_option( 'woocommerce_tax_display_cart' ) === 'incl' );
				$cart->add_fee( $title, $fee, $taxable, $tax_class_hack );

			}
		}
	}

	/**
	 * Adds the Fee with tax-string to review-order- and cart-totals-Template
	 *
	 * @wp-hook woocommerce_cart_totals_fee_html
	 *
	 * @param   string $fee_html
	 * @param   stdClass $fee
	 *
	 * @return  string $fee_html
	 */
	public static function show_gateway_fees_tax( $fee_html, $fee ) {

		if ( WGM_Tax::is_kur() ) {
			return $fee_html;
		}
		
		if ( ! apply_filters( 'woocommerce_de_show_gateway_fees_tax', true, $fee ) ) {
			return $fee_html;
		}

		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

		if ( $use_split_tax == 'off' ) {

			// splittax are off, but gross calculation is used
			if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'on' ) {

				$calculated = WGM_Tax::calculate_gross_rate_without_splittax( $fee->amount );

				if ( isset( $calculated[ 'net_sum' ] ) ) {
					
					$tax_amount 		= array_shift( $calculated[ 'taxes' ] );
					$tax_amount 		= wc_price( $tax_amount );

					$tax_decimals       = WGM_Helper::get_decimal_length( $calculated[ 'rates' ][ 'rate' ] );
					$tax_rate_formatted = number_format_i18n( (float) $calculated[ 'rates' ][ 'rate' ], $tax_decimals );
					$tax_display   		= get_option( 'woocommerce_tax_display_cart' );
					$tax_label  		= apply_filters( 'wgm_translate_tax_label', $calculated[ 'rates' ][ 'label' ] );

					$tax_string = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );

					return $fee_html . sprintf(
						'<br class="wgm-break" /><span class="wgm-tax product-tax"> %s </span>',
						$tax_string
					);
					
				}

			}

			// Setup.
			$tax             	= WGM_Tax::get_calculate_net_rate_without_splittax( $fee->amount );
			$tax_label 			= '';
			$tax_id 			= '';
			$tax_rate 			= '';
			$tax_rate_formatted = '';
			$tax_amount 		= '';

			if ( ! empty( $tax ) ) {

				reset( $tax );
				$tax_id 			= key( $tax );
				$tax_rate 			= WC_Tax::_get_tax_rate( $tax_id );
				$tax_rate_rate 		= $tax_rate[ 'tax_rate' ];
				$tax_amount 		= $tax[ $tax_id ];
				$tax_label 			= apply_filters( 'wgm_translate_tax_label', $tax_rate[ 'tax_rate_name' ] );
				$tax_amount 		= wc_price( $tax_amount );
				$tax_decimals 		= WGM_Helper::get_decimal_length( $tax_rate );
				$tax_rate_formatted = number_format_i18n( (float) $tax_rate_rate, $tax_decimals );

			}
			
			$tax_display        = get_option( 'woocommerce_tax_display_cart' );
			$tax_string         = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );

			return $fee_html . sprintf(
				'<br class="wgm-break" /><span class="wgm-tax product-tax"> %s </span>',
				$tax_string
			);
		}

		if ( ! empty( $fee->tax_class ) && substr( $fee->tax_class, 0, 4 ) == 'wgm_' ) {
			$amount = substr( $fee->tax_class, 4 );
		} else {
			$amount = $fee->amount;
		}

		$bypass_digital = FALSE;
		//if ( $fee->id == WGM_Fee::get_cod_fee_id() ) {
		//	$bypass_digital = TRUE;
		//}

		$rates = WGM_Tax::calculate_split_rate( $amount, WC()->cart, $bypass_digital, $fee->id, 'fee' );

		$fee_html .= WGM_Tax::get_split_tax_html( $rates, get_option( 'woocommerce_tax_display_cart' ) );

		return apply_filters( 'wgm_show_gateway_fees_tax', $fee_html, $fee );
	}
	/**
	 * Returns the highest tax rate of all cart items
	 *
	 * @return array
	 */
	public static function get_highest_tax_rate() {

		$cart         = WC()->cart->get_cart();
		$highest      = 0;
		$highest_rate = array();
		foreach ( $cart as $key => $item ) {

			// get the product
			$_product = apply_filters( 'woocommerce_cart_item_product', $item[ 'data' ], $item, $key );

			// get the product tax classes to set the array
			$class = $_product->get_tax_class();
			$rates = WC_Tax::get_rates( $class );

			foreach ( $rates as $rate_key => $rate ) {
				if ( $rate[ 'rate' ] > $highest ) {
					$highest = $rate[ 'rate' ];
					unset( $highest_rate );
					$highest_rate = array( $rate_key => $rate );
				}
			}

		}

		return $highest_rate;
	}

	/**
	 * Adding the correct split taxes to the fee-object.
	 *
	 * since 3.5
	 * @wp-hook woocommerce_cart_totals_get_fees_from_cart_taxes
	 * @param   Array $fee_taxes
	 * @param 	Array $fee
	 * @param   WC_Cart_totals $wc_cart_totals
	 * @return  Array 
	 */
	public static function cart_totals_get_fees_from_cart_taxes( $fee_taxes, $fee, $wc_cart_totals ) {

		if ( WGM_Tax::is_kur() ) {
			return array();
		}

		if ( ! apply_filters( 'woocommerce_de_calculate_gateway_fees_tax', true, $fee ) ) {
			return array();
		}

		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

		if ( $use_split_tax == 'off' ) {
			
			// splittax are off, but gross calculation is used
			if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'on' ) {

				$calculated = WGM_Tax::calculate_gross_rate_without_splittax( $fee->total );

				if ( isset( $calculated[ 'net_sum' ] ) ) {
					
					$new_taxes = array();
					foreach ( $calculated[ 'taxes' ] as $tax_id => $tax ) {
						$new_taxes[ $tax_id ]    = round( $tax );
					}
					
					$fee->total = $calculated[ 'net_sum' ];

					return $new_taxes;
				}

			} else {

				// splittax are off, gross calculation is off
				return WGM_Tax::get_calculate_net_rate_without_splittax( $fee->total );

			}

			return $fee_taxes;
		}

		$cart = WC()->cart;

		$precision = apply_filters( 'gm_split_tax_rounding_precision', 10 );
				
		// get fee total not in cents
		$fee_total = $fee->total / 100;

		$taxes = WGM_Tax::calculate_split_rate( $fee_total, $cart, false, $fee->object->id, 'fee' );

		$new_taxes = array();

		// calculating the tax-sum and adding the tax-positions to the fee
		foreach ( $taxes[ 'rates' ] as $tax_id => $tax ) {
				$new_taxes[ $tax_id ]    = round( $tax[ 'sum' ] * 100, $precision ); // calculate this in cents
		}

		// reset total here if "use as gross"
		if ( isset( $taxes[ 'use_as_gross' ] ) ) {
			$fee->total = round( $taxes[ 'use_as_gross' ] * 100, $precision );
		}

		return $new_taxes;

	}

	/**
	 * Adding the split taxes to fee order_item which is called
	 * in get_order_item_totals() for thankyou-page, email-template, ..
	 *
	 * @author  Chrico
	 *
	 * @wp-hook woocommerce_get_order_item_totals
	 *
	 * @param   array $items    contains all order items for display
	 * @param   WC_Order $order contains the complete order-Object
	 *
	 * @return  array $items
	 */
	public static function add_tax_string_to_fee_order_item( $items, $order ) {

		if( WGM_Tax::is_kur() ){
			return $items;
		}

		if ( is_a( $order, 'WC_Order_Refund' ) ) {
			$parent_id = $order->get_parent_id();
			$order = wc_get_order( $parent_id );
		}

        $rates = array(
        	'rates' => array(),
        );

		// looping through all fees to fix the text-string which is in "value"
		foreach( $order->get_fees() as $key => $fee ) {

			if ( ! apply_filters( 'woocommerce_de_show_gateway_fees_tax', true, $fee ) ) {
				continue;
			}

			// in $items the fee is saved with {fee_$key)
			$search_key = 'fee_' . $key;

			if ( ! array_key_exists( $search_key, $items ) ) {
				continue;
			}

			$fee_id = sanitize_title_with_dashes( $fee[ 'name' ] );
			
			$taxes = $fee->get_taxes();
			if ( isset( $taxes[ 'total' ] ) ) {

				foreach ( $taxes[ 'total' ] as $tax_rate_key => $tax_infos ) {

					$rate[ 'label' ] 	= apply_filters( 'wgm_translate_tax_label', WC_Tax::get_rate_label( $tax_rate_key ) );
					$rate[ 'sum' ]   	= $tax_infos;
					$rate[ 'rate' ]  	= WGM_Tax::get_rate_percent_by_rate_id_and_order( $tax_rate_key, $order );
					$rate[ 'rate_id' ]	= $tax_rate_key;
					$rates[ 'rates' ][] = $rate;
				}
			
			}

			// append the tax-messages to the value
			$items[ $search_key ][ 'value' ] .= WGM_Tax::get_split_tax_html( $rates, get_option( 'woocommerce_tax_display_cart' ) );

		}

		return $items;
	}

	/**
	 * Adds the fee taxes to the tax_totals-array.
	 * The key of $tax_totals is the unique WC_Tax::get_rate_code( $rate_id );
	 *
	 * @author  ChriCo
	 *
	 * @wp-hook woocommerce_order_tax_totals
	 *
	 * @param   array $tax_totals
	 * @param   WC_Order $order
	 *
	 * @return  array $tax_totals
	 */
	public static function add_fee_to_order_tax_totals( $tax_totals, $order ){

		return $tax_totals;

		if( WGM_Tax::is_kur() ){
			return $tax_totals;
		}

		if ( is_a( $order, 'WC_Order_Refund' ) ) {
			$parent_id = $order->get_parent_id();
			$order = wc_get_order( $parent_id );
		}

        $use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

        // if splittax is off, add the taxes to total taxes
        if ( $use_split_tax == 'off' ){
           	
			foreach ( $order->get_fees() as $fee_id => $fee ) {

				$taxes = $fee->get_taxes();
				
				if ( isset( $taxes[ 'total' ] ) ) { 
					
					foreach( $taxes[ 'total' ] as $rate_id => $rate_amount_as_string ) {

						$rate_key = WC_Tax::get_rate_code( $rate_id );

						if ( ! array_key_exists( $rate_key, $tax_totals ) ) {
							continue;
						}

						$tax_totals[ $rate_key ]->amount += floatval( $rate_amount_as_string );
						$tax_totals[ $rate_key ]->formatted_amount = wc_price( wc_round_tax_total( $tax_totals[ $rate_key ]->amount ), array('currency' => $order->get_currency() ) );

					}

				}

	        }

            return $tax_totals;
        }

		// looping through all existing fees
		foreach( $order->get_fees() as $key => $fee ) {

			if ( ! apply_filters( 'woocommerce_de_show_gateway_fees_tax', true, $fee ) ) {
				continue;
			}

			$fee_id = sanitize_title_with_dashes( $fee[ 'name' ] );
			$bypass_digital = FALSE;
			//if ( $fee_id == WGM_Fee::get_cod_fee_id() )
			//	$bypass_digital = TRUE;

			//$order->calculate_totals();
			$taxes = WGM_Tax::calculate_split_rate( $fee['line_total'], $order, $bypass_digital, $fee_id, 'fee', false );

			// looping through all found taxes
			foreach( $taxes[ 'rates' ] as $rate_id => $item ) {

				// getting the unique rate_code
				$rate_code = WC_Tax::get_rate_code( $rate_id );

				if ( !array_key_exists( $rate_code, $tax_totals ) ) {
					continue;
				}

				// add the new amount to the current amount
				$new_amount                         = $tax_totals[ $rate_code ]->amount + $item[ 'sum' ];
				$tax_totals[ $rate_code ]->amount   = $new_amount;

				// create the new formatted amount
				$tax_totals[ $rate_code ]->formatted_amount = wc_price(
					wc_round_tax_total( $new_amount ),
					array('currency' => $order->get_currency() )
				);

			}
		}

		return $tax_totals;
	}

	public static function get_cod_fee_id() {

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( isset( $available_gateways[ 'cash_on_delivery' ] ) )
			$cod_gateway        = $available_gateways[ 'cash_on_delivery' ];
		else
			return FALSE;

		return sanitize_title( $cod_gateway->title );
	}
}