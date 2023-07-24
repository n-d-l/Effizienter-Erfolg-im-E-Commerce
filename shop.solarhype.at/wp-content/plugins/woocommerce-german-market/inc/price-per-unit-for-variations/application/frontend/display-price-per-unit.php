<?php
/**
 * Feature Name: Display Price Per Unit
 * Descriptions: This function adds the price per units to the price on single product pages
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   https://marketpress.com
 * Licence:      GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'wgm_product_summary_parts', 'wcppufv_add_price_per_unit', 10, 2 );

/**
 * Override PPU Part of Variations for Variable Products
 *
 * @wp-hook wgm_product_summary_parts
 * @param Array $parts
 * @param WC_Product $product
 * @param Boolean $option_check
 * @return Array
 */
function wcppufv_add_price_per_unit( $parts = array(), $product = null, $option_check = true ) {

	if ( $option_check ) {
		if ( get_option( 'woocommerce_de_show_price_per_unit', 'on' ) == 'off' ) {
			return $parts;
		}
	}
	
	if ( is_a( $product, 'WC_Product_Variation' ) ) {
		
		// Don't show PPU at all if option _price_per_unit_product_weights_completely_off is set to "on"				
		if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' && get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {
			
			$parent_product = wc_get_product( $product->get_parent_id() );

			if ( ! WGM_Helper::method_exists( $parent_product, 'get_meta' ) ) {
				return $parts;
			}
			
			$price_per_unit_product_weights_completely_off = $parent_product->get_meta( '_price_per_unit_product_weights_completely_off' );

			if ( $price_per_unit_product_weights_completely_off == 'on' ) {
				if ( isset( $parts[ 'ppu' ] ) ) {
					unset( $parts[ 'ppu' ] );
				}

				return $parts;
			}
		}

		$ppu_for_variation = wcppufv_get_price_per_unit_string_by_product( $product );

		if ( ! empty( $ppu_for_variation ) ) {
			
			$parts[ 'ppu' ] = $ppu_for_variation;
		
		} else {

			if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {
				return $parts;
			}

			$parent_product = wc_get_product( $product->get_parent_id() );
			$ppu_for_variation_by_parent = WGM_Price_Per_Unit::get_price_per_unit_string( $parent_product );
			if ( ! empty( $ppu_for_variation_by_parent ) ) {
				$parts[ 'ppu' ] = $ppu_for_variation_by_parent;
			}
		}
		
	} else if ( is_a( $product, 'WC_Product_Variable' ) ) {

		if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {
			
			if ( isset( $parts[ 'ppu' ] ) ) {
				unset( $parts[ 'ppu' ] );
			}

			if ( apply_filters( 'german_market_check_variable_product_for_same_variation_ppu', true ) ) {

				$variable_ppu = WGM_Template::get_variable_data_quick( $product, 'ppu' );

				if ( ! empty( $variable_ppu ) ) {
					$parts[ 'ppu' ] = $variable_ppu; 
				}

			}

			return $parts;
			
		}

		if ( apply_filters( ' wcppufv_get_price_display_variable_price', true ) ) {
			
			$ppu_for_variable_product = wcppufv_get_price_per_unit_string_by_variable_product( $product );

			if ( ! empty( $ppu_for_variable_product ) ) {
				$parts[ 'ppu' ] = $ppu_for_variable_product;
			}
			
		}

	}

	$parts[ 'ppu' ] = apply_filters( 'wcppufv_add_price_per_unit_return_string', $parts[ 'ppu' ], $product, $parts );
	return $parts;
}

/**
 * Get PPU String by variable product
 *
 * @param WC_Product $product
 * @return String
 */
function wcppufv_get_price_per_unit_string_by_variable_product( $product ) {

	$return_string = '';

	if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'off' ) {

		if ( $product->is_on_sale() ) {
			$price_per_unit 		= $product->get_meta( '_sale_price_per_unit' );
			$price_per_unit_mult	= $product->get_meta( '_unit_sale_price_per_unit_mult' );
			$price_per_unit_unit	= $product->get_meta( '_unit_sale_price_per_unit' );

		} else {
			$price_per_unit 		= $product->get_meta( '_regular_price_per_unit' );
			$price_per_unit_mult	= $product->get_meta( '_unit_regular_price_per_unit_mult' );
			$price_per_unit_unit	= $product->get_meta( '_unit_regular_price_per_unit' );
		}

		$price_per_unit = floatval( str_replace( ',', '.', $price_per_unit ) );

	} else {

		$complete_product_price 	= wc_get_price_to_display( $product );
		$complete_product_quantity 	= $product->get_meta( '_auto_ppu_complete_product_quantity' );
		$unit 						= $product->get_meta( '_unit_regular_price_per_unit' );
		$mult 						= $product->get_meta( '_unit_regular_price_per_unit_mult' );
		$price_per_unit 			= WGM_Price_Per_Unit::automatic_calculation( $complete_product_price, $complete_product_quantity, $mult );

	}

	if ( empty( $price_per_unit ) && empty( $price_per_unit_mult ) ) {
		return '';
	}

	$return_string .= apply_filters(
		'wmg_price_per_unit_loop_variable',
		sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">' . WGM_Price_Per_Unit::get_output_format() . '</span>',
		         apply_filters( 'wcppufv_get_price_per_unit_string_by_variable_product_price', wc_price( $price_per_unit, apply_filters( 'wgm_ppu_wc_price_args', array() ) ), $price_per_unit, $product ),
		         str_replace( '.', wc_get_price_decimal_separator(), $price_per_unit_mult ),
		         $price_per_unit_unit
		),
		$price_per_unit,
		$price_per_unit_mult,
		apply_filters( 'german_market_measuring_unit', $price_per_unit_unit, $product )
	);

	return $return_string;

}

/**
* Cache Variaiton PPU
**/
global $wcppufv_cache;
$wcppufv_cache = array();

/**
 * Get PPU String by product
 *
 * @param WC_Product $product
 * @return String
 */
function wcppufv_get_price_per_unit_string_by_product( $product ) {

	global $wcppufv_cache;

	$return_string = '';
	$product = apply_filters( 'german_market_used_product_for_price_per_unit', $product );
	$variation_id = $product->get_id();

	if ( isset( $wcppufv_cache[ $variation_id ] ) ) {
		return $wcppufv_cache[ $variation_id ];
	}

	$price_per_unit_data = wcppufv_get_price_per_unit_data( $variation_id, $product );

	if ( isset( $price_per_unit_data[ 'price_per_unit' ] ) && $price_per_unit_data[ 'price_per_unit' ] ) {

		$return_string .= apply_filters(
			'wmg_price_per_unit_loop',
			sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">' . trim( WGM_Price_Per_Unit::get_prefix( $price_per_unit_data ) . ' ' . WGM_Price_Per_Unit::get_output_format() ) . '</span>',
			         wc_price( str_replace( ',', '.', $price_per_unit_data[ 'price_per_unit' ] ), apply_filters( 'wgm_ppu_wc_price_args', array() ) ),
			         str_replace( '.', wc_get_price_decimal_separator(), $price_per_unit_data[ 'mult' ] ),
			         $price_per_unit_data[ 'unit' ]
			),
			wc_price( str_replace( ',', '.', $price_per_unit_data[ 'price_per_unit' ] ) ),
			$price_per_unit_data[ 'mult' ],
			$price_per_unit_data[ 'unit' ]
		);

	}

	$wcppufv_cache[ $variation_id ] = $return_string;
	return $return_string;

}
