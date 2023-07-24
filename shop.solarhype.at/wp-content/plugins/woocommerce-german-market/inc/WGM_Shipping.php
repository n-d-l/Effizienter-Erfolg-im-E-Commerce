<?php

class WGM_Shipping {

	public static function add_shipping_part( $parts, $product ) {

		if ( $product->is_virtual() ) {
			if ( apply_filters( 'german_market_no_shipping_part_for_virtual_product', false,  $parts, $product ) ) {
				return $parts;
			}
		}

		$add_product_summary_part = ( 'on' == get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_show_delivery_time_overview' ) ) );

		ob_start();
		do_action( 'wgm_before_shipping_fee_single', $product );
		echo self::shipping_page_link( $product );
		
		if ( ( ! is_single() ) && ( $product->get_type() != 'variation' ) ) {
			if ( $add_product_summary_part ) {
				do_action( 'wgm_after_shipping_fee_single', $product );
			}
		} else {
			if ( get_option( 'woocommerce_de_show_delivery_time_product_page', 'on' ) == 'on' ) {
				do_action( 'wgm_after_shipping_fee_single', $product );
			}
		}
		
		$parts[ 'shipping' ] = ob_get_clean();

		return $parts;

	}

	/**
	 * Shipping page link template.
	 *
	 * @author glueckpress
	 * @access public
	 * @static
	 * @since  2.6
	 * @return string
	 */
	public static function shipping_page_link( $product = NULL ) {

		$shipping_link_or_string = self::get_shipping_page_link( $product );

		if ( empty( $shipping_link_or_string ) ) {
			return '';
		}
		// Check for free shipping advertising option
		$free_shipping = get_option( 'woocommerce_de_show_free_shipping' ) === 'on';

		$shipping_link_template = sprintf(
			'<div class="wgm-info woocommerce_de_versandkosten">%s</div>',
			$shipping_link_or_string
		);

		//TODO Deprecate 2nd parameter (used to $stopped_by_option, which has always been false since there was a return before this filter was able to run)
		return apply_filters( 'wgm_product_shipping_info', $shipping_link_template, FALSE, $free_shipping,
		                      $product );
	}

	/**
	 * Get shipping string for frontend
	 *
	 * @param WC_Product $product
	 * @return String
	 */
	public static function get_shipping_page_link( $product = NULL ) {

		// for variations
		if ( WGM_Helper::method_exists( $product, 'get_type' ) ) {
			if ( $product->get_type() == 'variation' ) {

				if ( intval( $product->get_meta( '_variable_used_setting_shipping_info' ) ) != 1 ) {
					$product = wc_get_product( $product->get_parent_id() );
				}

			}
		}

		// Check for “no shipping” option from product data
		$stopped_by_option = ( is_object( $product ) && WGM_Helper::method_exists( $product, 'get_id' ) ) ? apply_filters( 'german_market_get_post_meta_value_translatable', get_post_meta( $product->get_id(), '_suppress_shipping_notice', TRUE ), $product->get_id(), '_suppress_shipping_notice' ) : FALSE;

		if ( $stopped_by_option || ! is_object( $product ) ) {
			return '';
		}

		$alternative_text = $product->get_meta( '_alternative_shipping_information' );
		$use_alternative_text = false;
		if ( ! is_array( $alternative_text ) ) {
			if ( ! empty( trim( $alternative_text ) ) ) {
				$use_alternative_text = true;
			}
		}		

		// Check for free shipping advertising option
		$free_shipping = get_option( 'woocommerce_de_show_free_shipping' ) === 'on';

		// Build link
		if ( $free_shipping && ( ! $use_alternative_text ) ) {

			return apply_filters( 'woocommerce_de_free_shipping_string', __( 'Free Shipping', 'woocommerce-german-market' ) );

		} else {

			$page_id  = get_option( WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ) );
			$page_id  = absint( $page_id );
			$page_url = get_permalink( $page_id );
			if ( function_exists( 'icl_object_id' ) ) {
				$page_url = get_permalink( icl_object_id( $page_id ) );
			}
			
			$atts     = apply_filters( 'gm_get_shipping_page_link_atts', array(
				'class'  => 'class="versandkosten"',
				'url'    => sprintf( 'href="%s"', esc_url( $page_url ) ),
				'target' => 'target="_blank"',
			) );

			$shipping_info_text = apply_filters( 'german_market_get_shipping_page_link_text', __( 'plus <a %s>shipping</a>', 'woocommerce-german-market' ) );

			if ( $use_alternative_text ) {
				$alternative_text 	= str_replace( '%', "%%", wp_kses( $alternative_text, 'post' ) );
				$alternative_text 	= str_replace( '[link-shipping]', '<a %s>', $alternative_text );
				$alternative_text 	= str_replace( '[/link-shipping]', '</a>', $alternative_text );
				$shipping_info_text = $alternative_text;
			}

			return apply_filters( 'gm_get_shipping_page_link_return_string', sprintf(
				$shipping_info_text,
				implode( ' ', $atts )
			), $product, $atts );
		}

	}

	/**
	 * Recalculate the split tax for the shipping-methods
	 *
	 * @author  ChriCo
	 *
	 * @wp-hook woocommerce_package_rates
	 *
	 * @param   array $rates
	 *
	 * @return  array $rates
	 */
	public static function add_taxes_to_package_rates( $rates ) {

		if ( WGM_Tax::is_kur() ) {
			return $rates;
		}

		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

		if ( $use_split_tax == 'off' ) {
		
			// splittax are off, but gross calculation is used
			if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'on' ) {

				foreach ( $rates as $key => $rate ) {

					$net_costs = $rate->cost;
					
					$calculated = WGM_Tax::calculate_gross_rate_without_splittax( $net_costs );

					if ( isset( $calculated[ 'net_sum' ] ) ) {

						$rates[ $key ]->cost = $calculated[ 'net_sum' ];
						$rates[ $key ]->taxes = $calculated[ 'taxes' ];
					}

				}

			} else {

				// splittax are off, gross calculation is off
				foreach ( $rates as $key => $rate ) {

					$rates[ $key ]->taxes = WGM_Tax::get_calculate_net_rate_without_splittax( $rate->cost );

				}

			}

			return $rates;
		}

		$return_rates = $rates;

		// looping through all packages to calculate the new taxes
		foreach ( $return_rates as $key => $rate ) {

			$rate = apply_filters( 'woocommerce_de_add_shipping_tax_notice_method', $rate );

			// no costs defined and taxes are empty?
			if ( $rate->cost == 0 && empty( $rate->taxes ) ) {
				continue;
			}

			// getting the correct calculated taxes for the package
			$new_rates = WGM_Tax::calculate_split_rate( $rate->cost, NULL, FALSE, '', 'shipping', true, true, $rate );

			// reset the taxes for new assignment
			$rate->taxes = array();
			$new_calculates_rates = array();
			foreach ( $new_rates[ 'rates' ] as $rate_id => $item ) {
				$new_calculates_rates[ $rate_id ] = apply_filters( 'woocommerce_de_add_shipping_tax_rate_sum', $item[ 'sum' ], $item );
			}

			$rate->taxes = $new_calculates_rates;

			if ( isset( $new_rates[ 'use_as_gross' ] ) ) {
				$rate->cost = $new_rates[ 'use_as_gross' ];
			}
			// re-assign the rate to the package
			$return_rates[ $key ] = $rate;

		}

		return $return_rates;
	}

	/**
	 * Remove the taxes when "kur" (*K*lein*u*nternehmer*r*egelung) is enabled
	 *
	 * @author  ChriCo
	 *
	 * @wp-hook woocommerce_get_shipping_tax
	 *
	 * @param   int $taxes
	 *
	 * @return  int $taxes
	 */
	public static function remove_kur_shipping_tax( $taxes ) {

		if ( WGM_Tax::is_kur() ) {

			$taxes = 0;
		}

		return $taxes;
	}

	/**
	 * Adding the taxes to shipping method
	 *
	 * @author  ChriCo
	 *
	 * @wp-hook woocommerce_cart_shipping_method_full_label
	 *
	 * @param   string   $label
	 * @param   stdClass $method
	 *
	 * @return  string $label
	 */
	public static function add_shipping_tax_notice( $label, $method ) {

		if ( WGM_Tax::is_kur() ) {
			return $label;
		}

		// shipping->cost is already rounded with rounding precision 2, we need all decimal places
		$slug = str_replace( ':', '_', $method->id );
		$option_name = 'woocommerce_' . $slug . '_settings';
		$option = get_option( $option_name );
		$method_cost_check = round( $method->cost, 2 );
		if ( is_array( $option ) ) {
			foreach ( $option as $maybe_cost ) {
				$maybe_cost_float = round( floatval( str_replace( ',', '.', $maybe_cost ) ), 2 );
				if ( $maybe_cost_float == $method_cost_check ) {
					$method->cost = floatval( str_replace( ',', '.', $maybe_cost ) );
					break;
				}
				
			}
		}
		
		$method = apply_filters( 'woocommerce_de_add_shipping_tax_notice_method', $method );

		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );
		
		if ( $use_split_tax == 'off' ) {

			$label = $method->label;
			
			if ( $method->cost > 0 ) {

				// get the tax rate
				$rate = array();

				// get the rate id
				$taxes           = $method->taxes;
				$tax_rate_key    = array_keys( $taxes );
				$tax_rate_key    = reset( $tax_rate_key );

				$rate[ 'label' ] = WC_Tax::get_rate_label( $tax_rate_key );
				$rate[ 'sum' ]   = reset( $taxes );
				$rate[ 'rate' ]  = WC_Tax::get_rate_percent( $tax_rate_key );

				if ( get_option( 'woocommerce_tax_display_cart' ) == 'excl' ) {
					$label .= ': ' . wc_price( $method->cost );
				} else {
					$label .= ': ' . wc_price( $method->cost + $rate[ 'sum' ] );
				}

				// set rates
				$rates              = array();
				$rates[ 'rates' ][] = $rate;

				$rates[ 'rates' ] = apply_filters( 'woocommerce_find_rates', $rates[ 'rates' ] );

				// append the split taxes to shipping-string
				$label .= WGM_Tax::get_split_tax_html( $rates, get_option( 'woocommerce_tax_display_cart' ) );
			}

			return $label;
		}

		$label = $method->label;

		$the_rates 				= array();
		$the_rates[ 'rates' ] 	= array();
		$the_rates[ 'sum' ] 	= 0;

		$the_rates[ 'sum' ] = array_sum( $method->taxes );

		foreach ( $method->taxes as $tax_rate_key => $rate ) {

			if ( $rate == 0.0 ) {
				continue;
			}

			$the_rates[ 'rates' ][ $tax_rate_key ] = array();
			$the_rates[ 'rates' ][ $tax_rate_key ][ 'sum' ] 		= $rate;
			$the_rates[ 'rates' ][ $tax_rate_key ][ 'rate_id' ] 	= $tax_rate_key;
			$the_rates[ 'rates' ][ $tax_rate_key ][ 'label' ] 		= WC_Tax::get_rate_label( $tax_rate_key );
			$the_rates[ 'rates' ][ $tax_rate_key ][ 'rate' ]  		= WC_Tax::get_rate_percent( $tax_rate_key );
		}

		$the_rates[ 'rates' ] = apply_filters( 'woocommerce_find_rates', $the_rates[ 'rates' ] );

		if ( $method->cost > 0 ) {
			if ( get_option( 'woocommerce_tax_display_cart' ) == 'excl' ) {
				
				$label .= ': ' . wc_price( $method->cost );

			} else {

				$label .= ': ' . wc_price( $method->cost + $the_rates[ 'sum' ] );
				
			}

			// append the split taxes to shipping-string
			$label .= WGM_Tax::get_split_tax_html( $the_rates, get_option( 'woocommerce_tax_display_cart' ) );

		} else if ( $method->method_id !== 'free_shipping' ) {
			$label .= ' (' . __( 'Free', 'woocommerce-german-market' ) . ')';
		}

		return apply_filters( 'wgm_cart_shipping_method_full_label', $label, $method, $the_rates );

	}

	/**
	 * Adding taxes to shipping to output
	 *
	 * @wp-hook woocommerce_order_shipping_to_display
	 *
	 * @param   string   $shipping
	 * @param   WC_Order $order
	 *
	 * @return  string $shipping
	 */
	public static function shipping_tax_for_thankyou( $shipping, $order ) {

		if ( WGM_Tax::is_kur() ) {
			return $shipping;
		}

		if ( is_a( $order, 'WC_Order_Refund' ) ) {
			$parent_id = $order->get_parent_id();
			$order = wc_get_order( $parent_id );
		}

		$use_split_tax = get_option( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

		if ( $use_split_tax == 'off' ) {
			
			// things are getting complicatet right now: 1st get shipping_method
			$shipping = $order->get_shipping_method() . ': ';

			$wc_price_args = array(
				'currency' => $order->get_currency()
			);

			$shipping_methods = $order->get_shipping_methods();
			$tax_html 		  = '';
			$rates_by_key 	  = array();
			// there's only one shipping method, we made it that way, so break after foreach
			foreach ( $shipping_methods as $shipping_method ) {

				$rate = array();

				// get the the taxes data, be careful, there's another array including the taxes
				$shipping_taxes = $shipping_method->get_data( 'taxes' ) ;

				$rate[ 'label' ] = '';
				$rate[ 'sum' ]   = '';
				$rate[ 'rate' ]  = '';

				if ( isset( $shipping_taxes[ 'taxes' ][ 'total' ] ) ) {

					foreach ( $shipping_taxes[ 'taxes' ][ 'total' ] as $tax_rate_key => $tax_infos ) {

						if ( empty( $tax_infos ) ) {
							continue;
						}
						
						if ( ! isset( $rates_by_key[ $tax_rate_key ] ) ) {
							
							$rates_by_key[ $tax_rate_key ] = array(
								'label' 	=> apply_filters( 'wgm_translate_tax_label', WC_Tax::get_rate_label( $tax_rate_key ) ),
								'sum'		=> floatval( $tax_infos ),
								'rate'  	=> WC_Tax::get_rate_percent( $tax_rate_key ),
								'rate_id' 	=> $tax_rate_key
							);
						} else {
							$rates_by_key[ $tax_rate_key ][ 'sum' ] += floatval( $tax_infos );
						}

					}
				
				}

			}

			// set rates
			$rates = array();
			foreach ( $rates_by_key as $key => $value ) {
				$rates[ 'rates' ][] = $value;
			}

			if ( empty( $rates ) ){
				$rates[ 'rates' ] = array();
			} 

			$label = WGM_Tax::get_split_tax_html( $rates, get_option( 'woocommerce_tax_display_cart' ), $order );

			if ( get_option( 'woocommerce_tax_display_cart' ) === 'excl' ) {
				// Show shipping excluding tax
				$shipping .= wc_price( $order->get_shipping_total(), $wc_price_args ) . $label;
			} else {
				// Show shipping including tax
				$shipping .= wc_price( $order->get_shipping_total() + floatval( $order->get_shipping_tax() ), $wc_price_args ) . $label;
			}

			

			return $shipping;
		}

		if ( $order->get_shipping_total() > 0 ) {

			$shipping_cost = $order->get_shipping_total();

			$the_rates 				= array();
			$the_rates[ 'rates' ] 	= array();
			$the_rates[ 'sum' ] 	= 0;

			foreach ( $order->get_shipping_methods() as $item_id => $item ) {
	            
	           $shipping_taxes = $item->get_taxes();

	           if ( isset( $shipping_taxes[ 'total' ] ) ) {
	           		$the_rates[ 'sum' ] += array_sum( $shipping_taxes[ 'total' ] );

	           		foreach ( $shipping_taxes[ 'total' ] as $tax_rate_key => $rate ) {

	           			$rate = floatval( $rate );

	           			if ( $rate == 0.0 ) {
							continue;
						}
						
						if ( ! isset( $the_rates[ 'rates' ][ $tax_rate_key ] ) ) {
							$the_rates[ 'rates' ][ $tax_rate_key ] = array();
							$the_rates[ 'rates' ][ $tax_rate_key ][ 'sum' ] = 0.0;
						}

						$the_rates[ 'rates' ][ $tax_rate_key ][ 'sum' ] 		+= $rate;
						$the_rates[ 'rates' ][ $tax_rate_key ][ 'rate_id' ] 	= $tax_rate_key;
						$the_rates[ 'rates' ][ $tax_rate_key ][ 'label' ] 		= apply_filters( 'wgm_translate_tax_label', WC_Tax::get_rate_label( $tax_rate_key ) );
						$the_rates[ 'rates' ][ $tax_rate_key ][ 'rate' ]  		= WC_Tax::get_rate_percent( $tax_rate_key );
	           		}
	           		
		
	           }

	        }

			$wc_price_args = array(
				'currency' => $order->get_currency()
			);

			$shipping = $order->get_shipping_method() . ': ';

			if ( get_option( 'woocommerce_tax_display_cart' ) === 'excl' ) {
				// Show shipping excluding tax
				$shipping .= wc_price( $order->get_shipping_total(), $wc_price_args );
			} else {
				// Show shipping including tax
				$shipping .= wc_price( $order->get_shipping_total() + $the_rates[ 'sum' ], $wc_price_args );
			}

			$shipping .= WGM_Tax::get_split_tax_html( $the_rates, get_option( 'woocommerce_tax_display_cart' ), $order );

		} else if ( $order->get_shipping_method() ) {
			$shipping = $order->get_shipping_method();
		} else {
			$shipping = __( 'Free!', 'woocommerce-german-market' );
		}

		return apply_filters( 'wgm_order_shipping_to_display', $shipping, $order );
	}
}
