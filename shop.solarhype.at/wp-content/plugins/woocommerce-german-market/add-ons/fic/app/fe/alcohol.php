<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Add Alcohol Content to Product Info
*
* @wp-hook wgm_product_summary_parts
* @param Array $output_parts
* @param WC_Product $product
* @param String $hook
* @return String
**/
function gm_fic_add_alcohol_content_to_product_info( $output_parts, $product, $hook, $option_check = true ) {
	
	if ( $option_check ) {
		// return on product page if option is off
		if ( $hook == 'single' && get_option( 'gm_fic_ui_alocohol_product_page', 'on' ) != 'on' ) {
			return $output_parts;
		}

		// return on shop page if option is off
		if ( $hook == 'loop' && get_option( 'gm_fic_ui_alocohol_loop', 'off' ) != 'on' ) {
			return $output_parts;
		}
	}

	$alcohol_value	= $product->get_meta( '_alcohol_value' );
	$alcohol_unit 	= $product->get_meta( '_alcohol_unit' );

	// variation of a variable product
	if ( is_a( $product, 'WC_Product_Variation' ) ) {
		
		$alcohol_value_variation = $product->get_meta( '_alcohol_value' );

		if ( $alcohol_value_variation == '' ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			$alcohol_value	= $parent_product->get_meta( '_alcohol_value' );
			$alcohol_unit 	= $parent_product->get_meta( '_alcohol_unit' );
		}

	}

	// variable product
	if ( is_a( $product, 'WC_Product_Variable' ) ) {
		
		$all_values = array();
		$children = $product->get_children();

		$min_string = $alcohol_value;
		$max_string = $alcohol_value;

		$max = 0.0;
		$min = 10000.0;

		$has_alcohol = false;

		foreach ( $children as $child ) {

			$alcohol_value_child = get_post_meta( $child, '_alcohol_value', true );
			
			if ( $alcohol_value_child == '' ) {
				$alcohol_value_child = $alcohol_value;
			}

			if ( $alcohol_value_child == '' ) {
				$alcohol_value_child = 0;
			} else {
				$has_alcohol = true;
			}

			$alcohol_value_float = str_replace( ',', '.', $alcohol_value_child );

			if ( $alcohol_value_float <= $min ) {
				$min = $alcohol_value_float;
				$min_string = $alcohol_value_child;
			}

			if ( $alcohol_value_float >= $max ) {
				$max = $alcohol_value_float;
				$max_string = $alcohol_value_child;
			}
			
		}

		if ( $min_string != $max_string ) {
			$alcohol_value = $min_string . apply_filters( 'gm_fic_alcohol_fromt_to_symbol', ' - ' ) . $max_string;
		} else {
			$alcohol_value = $min_string;
		}

		if ( ! $has_alcohol ) {
			$alcohol_value = '';
		}

	}

	// return if there is no alcohol value
	if ( $alcohol_value == '' ) {
		return $output_parts;
	}

	// build complete markup
	$alcohol = gm_fic_alcohol_get_markup( $alcohol_value, $alcohol_unit );

	// add alochol content to product summary parts
	$after = 'ppu';
	if ( ! isset ( $output_parts[ 'ppu' ] ) ) {
		$after = 'tax';
		if ( ! isset ( $output_parts[ 'tax' ] ) ) {
			$after = 'price';
			if ( ! isset( $output_parts[ 'price' ] ) ) {
				$after = ''; // add to the end
			}
		}
	}

	if ( $after == '' ) {
		$output_parts[ 'alc' ] = $alcohol;
	} else {

		$new_output_parts = array();

		foreach ( $output_parts as $key => $output_part ) {
			
			$new_output_parts[ $key ] = $output_part;
			if ( $key == $after ) {
				$new_output_parts[ 'alc' ] = $alcohol;
			}
		}

		$output_parts = $new_output_parts;

	}

	return $output_parts;

}
/**
* Make Markup
*
* @param String $value
* @param String $unit
* @return String
**/
function gm_fic_alcohol_get_markup( $value, $unit ) {

	$prefix = get_option( 'gm_fic_ui_alocohol_prefix', __( 'alc.', 'woocommerce-german-market' ) );

	$alcohol = trim( $prefix . ' ' . $value . ' ' . $unit );

	$markup = apply_filters( 'gm_fic_alcohol_markup', '<span class="wgm-info fic-alcohol">%s</span>' );
	return sprintf( $markup, $alcohol );

}

/**
* Add item meta
*
* @wp-hook woocommerce_add_cart_item_data
* @param Array $cart_item_data
* @param Integer $product_id
* @param Integer $variation_id
* @return Array
**/
function gm_fic_woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {

	$alcohol_value	= get_post_meta( $product_id, '_alcohol_value', true );
	$alcohol_unit 	= get_post_meta( $product_id, '_alcohol_unit', true );

	if ( $variation_id && $variation_id > 0 ) {
		
		$alcohol_value_variation = get_post_meta( $variation_id, '_alcohol_value', true );
		if ( $alcohol_value_variation != '' ) {
			$alcohol_value = $alcohol_value_variation;
			$alcohol_unit  = get_post_meta( $variation_id, '_alcohol_unit', true );
		}

	}

	if ( $alcohol_value != '' ) {
		$cart_item_data[ 'gm_fic_alc' ] = trim( $alcohol_value . ' ' . $alcohol_unit );
	}

	return $cart_item_data;
}

/**
* Add item meta from session
*
* @wp-hook woocommerce_add_cart_item_data
* @param Array $cart_item_data
* @param Array $cart_item_session_data
* @param String $cart_item_key
* @return Array
**/
function gm_fic_woocommerce_get_cart_item_from_session( $cart_item_data, $cart_item_session_data, $cart_item_key ) {

	if ( isset( $cart_item_session_data[ 'gm_fic_alc' ] ) ) {
        $cart_item_data[ 'gm_fic_alc' ] = $cart_item_session_data[ 'gm_fic_alc' ];
    }

	return $cart_item_data;
}

/**
* Show Item Meta in Checkout
*
* @wp-hook woocommerce_add_cart_item_data
* @param Array $data
* @param Array $cart_item
* @return Array
**/
function gm_fic_woocommerce_get_item_data( $data, $cart_item ) {
	
	if ( isset( $cart_item[ 'gm_fic_alc' ] ) ) {
        $data[] = array(
            'name' => get_option( 'gm_fic_ui_alocohol_prefix', __( 'alc.', 'woocommerce-german-market' ) ),
            'value' => $cart_item[ 'gm_fic_alc' ]
        );
    }

	return $data;
}

/**
* Store into order
*
* @wp-hook woocommerce_new_order_item
* @param Integer $item_id
* @param Object $item
* @param Integer $order_id
* @return void
**/
function gm_fic_woocommerce_add_order_item_meta_wc_3( $item_id, $item, $order_id ) {

	if ( is_a( $item, 'WC_Order_Item_Product' ) ) {

		$product = $item->get_product();

		if ( ! WGM_Helper::method_exists( $product, 'get_meta' ) ) {
			return;
		}
		
		$alcohol_value	= $product->get_meta( '_alcohol_value' );
		$alcohol_unit 	= $product->get_meta( '_alcohol_unit' );

		if ( $product->get_type() == 'variation' ) {

			if ( $alcohol_value == '' ) {

				$parent_product = wc_get_product( $product->get_parent_id() );
				$alcohol_value	= $parent_product->get_meta( '_alcohol_value' );
				$alcohol_unit 	= $parent_product->get_meta( '_alcohol_unit' );

			}
		}

		$output = trim( $alcohol_value . ' ' . $alcohol_unit );

		if ( trim( $alcohol_value ) != '' ) {
	        wc_add_order_item_meta( $item_id, get_option( 'gm_fic_ui_alocohol_prefix', __( 'alc.', 'woocommerce-german-market' ) ), $output );
	    }

	}

}
