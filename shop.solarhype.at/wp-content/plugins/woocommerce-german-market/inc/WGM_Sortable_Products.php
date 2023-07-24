<?php

/**
 * Class WGM_Sortable_Products
 *
 * This class contains helper functions to order products in cart and checkout process.
 *
 * @author  Maik
 */
class WGM_Sortable_Products {

	/**
	 * This function is responsible for sorting order items
	 *
	 * @hook woocommerce_order_get_items
	 *
	 * @access public
	 * @static
	 *
	 * @param array    $items Array of Items.
	 * @param WC_Order $order Order Object.
	 * @param array    $types Type of Item.
	 *
	 * @return mixed
	 */
	public static function gm_sort_woocommerce_order_get_items( $items, $order, $types ) {

		$sort_products_by    = get_option( 'gm_checkout_sort_products_by', 'standard' );
		$sort_products_order = get_option( 'gm_checkout_sort_products_ascdesc', 'asc' );

		if ( count( $items ) <= 0 ) {
			return $items;
		}

		foreach ( $types as $type ) {
			if ( 'line_item' != $type ) {
				return $items; 
			}
		}

		$products_processing = array();
		$products_unsortable = array();
		$products_result     = array();
		$sort_order          = 'asc' === $sort_products_order ? SORT_ASC : SORT_DESC;
		$sort_flag           = SORT_REGULAR;
		$is_numeric          = true; // We assume SKU is numeric by default

		foreach ( $items as $key => $item ) {

			if ( is_object( $item ) && method_exists( $item, 'get_product' ) ) {

				$_product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );

				$sku      = self::get_the_sku( $_product );
				$name     = self::get_the_title( $_product );
				$bundled  = array();

				// Product is not sortable
				if ( ( 'sku' === $sort_products_by && empty( $sku ) ) || ( 'name' === $sort_products_by && empty( $name ) ) ) {
					$products_unsortable[] = array(
						'key'     => $key,
						'sku'     => $sku,
						'name'    => $name,
						'bundled' => $bundled,
					);
					continue;
				}

				// Product is sortable.
				$products_processing[] = array(
					'key'     => $key,
					'sku'     => $sku,
					'name'    => $name,
					'bundled' => $bundled,
				);

				if ( 'sku' === $sort_products_by && true === $is_numeric ) {
					if ( ! is_numeric( $sku ) ) {
						$is_numeric = false;
					}
				}
			}

		}

		// Sort 'numeric' only if we didnt find alphanumeric SKU's
		if ( 'sku' === $sort_products_by && true === $is_numeric ) {
			$sort_flag = SORT_NUMERIC;
		}

		$sort_flag = apply_filters( 'german_market_sort_flag', $sort_flag, 'order_get_items' );

		// Sort by column.
		$column = array_column( $products_processing, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_processing );

		// Sort by column.
		$column = array_column( $products_unsortable, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_unsortable );

		// Merging sorted and unsortable array
		$products_processing = array_merge( $products_processing, $products_unsortable );

		// Assigning sorted items.
		foreach ( $products_processing as $order_item ) {
			$order_key = $order_item[ 'key' ];
			$products_result[ $order_key ] = $items[ $order_key ];
		}

		return apply_filters( 'german_market_sort_woocommerce_order_get_items', $products_result, $items, $order, $types );
	}

	/**
	 * This function is responsible for sorting cart items
	 *
	 * @hook woocommerce_cart_loaded_from_session
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function gm_sort_woocommerce_cart_items() {

		$sort_products_by    = get_option( 'gm_cart_sort_products_by', 'standard' );
		$sort_products_order = get_option( 'gm_cart_sort_products_ascdesc', 'asc' );

		$products_processing = array();
		$products_unsortable = array();
		$products_result     = array();
		$sort_order          = 'asc' === $sort_products_order ? SORT_ASC : SORT_DESC;
		$sort_flag           = SORT_REGULAR;
		$is_numeric          = true; // We assume SKU is numeric by default

		foreach ( WC()->cart->get_cart_contents() as $cart_key => $item ) {

			$sku     = self::get_the_sku( $item[ 'data' ] );
			$name    = self::get_the_title( $item[ 'data' ] );
			$bundled = array();

			// Product is not sortable
			if ( ( 'sku' === $sort_products_by && empty( $sku ) ) || ( 'name' === $sort_products_by && empty( $name ) ) ) {
				$products_unsortable[] = array(
					'key'     => $cart_key,
					'sku'     => $sku,
					'name'    => $name,
					'bundled' => $bundled,
				);
				continue;
			}

			// Product is sortable.
			$products_processing[] = array(
				'key'     => $cart_key,
				'sku'     => $sku,
				'name'    => $name,
				'bundled' => $bundled,
			);

			if ( 'sku' === $sort_products_by && true === $is_numeric ) {
				if ( ! is_numeric( $sku ) ) {
					$is_numeric = false;
				}
			}

		}

		// Sort 'numeric' only if we didnt find alphanumeric SKU's
		if ( 'sku' === $sort_products_by && true === $is_numeric ) {
			$sort_flag = SORT_NUMERIC;
		}

		$sort_flag = apply_filters( 'german_market_sort_flag', $sort_flag, 'cart_items' );
		
		// Sort by column.
		$column = array_column( $products_processing, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_processing );

		// Sort by column.
		$column = array_column( $products_unsortable, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_unsortable );

		// Merging sorted and unsortable array
		$products_processing = array_merge( $products_processing, $products_unsortable );

		// Assigning sorted items.
		foreach ( $products_processing as $cart_item ) {
			$cart_key = $cart_item[ 'key' ];
			$products_result[ $cart_key ] = WC()->cart->cart_contents[ $cart_key ];
		}

		WC()->cart->cart_contents = apply_filters( 'german_market_sort_woocommerce_cart_items', $products_result, WC()->cart->cart_contents );
	}

	/**
	 * This helper function returns the item title.
	 *
	 * @access public
	 * @static
	 *
	 * @param object $item_data
	 *
	 * @return string
	 */
	public static function get_the_title( $item_data ) {

		$result = null;
		if ( is_object( $item_data ) && method_exists( $item_data, 'get_title' ) ) {
			$result = strtolower( $item_data->get_title() );
		}

		return apply_filters( 'german_market_sort_items_get_the_title', $result, $item_data );
	}

	/**
	 * This helper function returns the item sku.
	 *
	 * @access public
	 * @static
	 *
	 * @param object $item_data
	 *
	 * @return string
	 */
	public static function get_the_sku( $item_data ) {

		$result = null;
		if ( is_object( $item_data ) && method_exists( $item_data, 'get_sku' ) ) {
			$result = $item_data->get_sku();
		}

		return apply_filters( 'german_market_sort_items_get_the_sku', $result, $item_data );
	}

}
