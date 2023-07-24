<?php
/**
 * Feature Name: Helper
 * Descriptions: Here are some helper functions we need
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   https://marketpress.com
 * Licence:      GPLv3
 */

/**
 * getting the Script and Style suffix for Kiel-Theme
 * Adds a conditional ".min" suffix to the file name when WP_DEBUG is NOT set to TRUE.
 *
 * @return	string
 */
function wcppufv_get_script_suffix() {

	$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$suffix = $script_debug ? '' : '.min';

	return $suffix;
}

/**
 * Gets the specific asset directory url
 *
 * @param	string $path the relative path to the wanted subdirectory. If
 *				no path is selected, the root asset directory will be returned
 * @return	string the url of the wcppufv asset directory
 */
function wcppufv_get_asset_directory_url( $path = '' ) {

	// set base url
	$wcppufv_assets_url = WCPPUFV_PLUGIN_URL . 'assets/';
	if ( $path != '' )
		$wcppufv_assets_url .= $path . '/';
	return $wcppufv_assets_url;
}

/**
 * Gets the specific asset directory path
 *
 * @param	string $path the relative path to the wanted subdirectory. If
 *				no path is selected, the root asset directory will be returned
 * @return	string the url of the wcppufv asset directory
 */
function wcppufv_get_asset_directory( $path = '' ) {

	// set base url
	$wcppufv_assets = WCPPUFV_PLUGIN_PATH . 'assets/';
	if ( $path != '' )
		$wcppufv_assets .= $path . '/';
	return $wcppufv_assets;
}

/**
 * Make a select field for scale_units
 * 
 * @param	array $field
 * @return	string html
 */
function wcppufv_select_scale_units( $post_id, $field ) {
	global $woocommerce;

	if ( ! isset( $field[ 'class' ] ) )
		$field[ 'class' ] = 'select short';

	if ( ! isset( $field[ 'value' ] ) )
		$field[ 'value' ] = get_post_meta( $post_id, $field[ 'name' ], TRUE );

	$default_product_attributes = WGM_Defaults::get_default_product_attributes();

	$attribute_taxonomy_name = wc_attribute_taxonomy_name( $default_product_attributes[ 0 ][ 'attribute_name' ] );

	$string = '<select name="' . $field[ 'id' ] . '" class="german-market-variation-input-not-translatable">';

	$terms = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		$attribute_taxonomy_name    = 'pa_masseinheit';
		$terms                      = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );
	}

	$terms = apply_filters( 'german_market_pa_measuring_terms_in_backend', $terms, $post_id, $field[ 'value' ] );
	
	foreach ( $terms as  $value ) {
		
		if ( is_object( $value ) && isset( $value->name ) && isset( $value->description ) ) {

			$string .= '<option value="'. $value->name .'" ';
			$string .= selected( $field[ 'value' ], $value->name, FALSE );
			$string .=  '>'. $value->description . '</option>';

		}
		
	}

	$string .= '</select>';
	return $string;
}

/**
 * Retrives price per unit data
 * 
 * @param	int $variation_id
 * @param	object $product
 * @return	array
 */
function wcppufv_get_price_per_unit_data( $variation_id, $product ) {

	$rtn = array();

	if ( ! is_object( $product ) ) {
		return $rtn;
	}

	if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'off' ) {
		
		if ( $product->is_on_sale() ) {
			$rtn[ 'price_per_unit' ] = str_replace( ',', '.', $product->get_meta( '_v_sale_price_per_unit' ) );
			$rtn[ 'unit' ] = apply_filters( 'german_market_measuring_unit', $product->get_meta( '_v_unit_sale_price_per_unit' ), $product );
			$rtn[ 'mult' ] = $product->get_meta( '_v_unit_sale_price_per_unit_mult' );
		} else {
			$rtn[ 'price_per_unit' ] = str_replace( ',', '.', $product->get_meta( '_v_regular_price_per_unit' ) );
			$rtn[ 'unit' ] = apply_filters( 'german_market_measuring_unit', $product->get_meta( '_v_unit_regular_price_per_unit' ), $product );
			$rtn[ 'mult' ] = $product->get_meta( '_v_unit_regular_price_per_unit_mult' );
		}
	
	} else {

		$complete_product_price = apply_filters( 'german_market_get_price_per_unit_data_complete_product_price', wc_get_price_to_display( $product ), $product );
		$price_per_unit_product_weights_completely_off = null;

		if ( intval( $product->get_meta( '_v_used_setting_ppu' ) ) != 1 ) {
			
			$product 	= wc_get_product( $product->get_parent_id() );
			$prefix 	= '';
			if ( is_object( $product ) && method_exists( $product, 'get_meta' ) ) { // variation without parent product (?) - happens with divi page builder
				$price_per_unit_product_weights_completely_off = $product->get_meta( '_price_per_unit_product_weights_completely_off' );
			}

		} else {
			
			$prefix = '_v';

		}

		if ( WGM_Helper::method_exists( $product, 'get_meta' ) ) {
			$complete_product_quantity 	= $product->get_meta( $prefix . '_auto_ppu_complete_product_quantity' );
			$rtn[ 'unit' ]				= apply_filters( 'german_market_measuring_unit', $product->get_meta( $prefix . '_unit_regular_price_per_unit' ), $product );
			$rtn[ 'mult' ]				= $product->get_meta( $prefix . '_unit_regular_price_per_unit_mult' );
			$rtn[ 'price_per_unit' ] 	= WGM_Price_Per_Unit::automatic_calculation( $complete_product_price, $complete_product_quantity, $rtn[ 'mult' ] );
			$rtn[ 'complete_product_quantity' ] = $complete_product_quantity;

			if ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {

				if ( is_null( $price_per_unit_product_weights_completely_off ) ) {
					$parent_product	= wc_get_product( $product->get_parent_id() );
					if ( is_object( $parent_product ) && method_exists( $parent_product, 'get_meta' ) ) {
						$price_per_unit_product_weights_completely_off = $parent_product->get_meta( '_price_per_unit_product_weights_completely_off' );
					}
				}

				if ( 'on' === $price_per_unit_product_weights_completely_off ) {
					$rtn = array();
				} else if ( empty( $complete_product_quantity ) || empty( $rtn[ 'unit' ] ) || empty( $rtn[ 'mult' ] ) ) {
					
					$variation = wc_get_product( $variation_id );

					$complete_product_quantity 	= wc_get_weight( $variation->get_weight(), get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) ), get_option( 'woocommerce_weight_unit', 'kg' ) );
					$rtn[ 'unit' ]				= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) );
					$rtn[ 'mult' ]				= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_mult', 1 );
					$rtn[ 'price_per_unit' ] 	= WGM_Price_Per_Unit::automatic_calculation( $complete_product_price, $complete_product_quantity, $rtn[ 'mult' ] );
					$rtn[ 'complete_product_quantity' ] = $complete_product_quantity;
				}

			}
		}

	}

	return apply_filters( 'german_market_ppu_get_price_per_unit_data', $rtn, $product, $variation_id );
}
