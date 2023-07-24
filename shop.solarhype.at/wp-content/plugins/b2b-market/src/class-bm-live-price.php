<?php
/**
 * Deprecated: Class to handle old live prices.
 */
class BM_Live_Price {
	/**
	 * Deprecated: Show single product price based on serverside quantity
	 *
	 * @param string $price current price.
	 * @param object $product current product object.
	 * @return string
	 */
	public static function single_product_price( $price, $product ) {
		return $price;
	}

	/**
	 * Deprecated: Get single cheapest price
	 *
	 * @param string $price current price.
	 * @param object $product current product object.
	 * @param int    $group_id current group id.
	 * @return string
	 */
	public static function get_cheapest_price( $price, $product, $group_id ) {
		$cheapest_price = BM_Price::get_price( $price, $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );
		return $cheapest_price;
	}
}
