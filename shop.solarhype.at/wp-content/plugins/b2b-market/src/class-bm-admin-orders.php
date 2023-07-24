<?php

/**
 * Class to handle admin orders with B2B Market
 */
class BM_Admin_Orders {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of BM_Admin_Orders.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for BM_Admin_Orders.
	 */
	public function __construct() {
		add_action( 'wp_ajax_reset_order_customer_id',      array( $this, 'reset_order_customer_id' ) );
		add_action( 'wp_ajax_update_order_customer_id',     array( $this, 'update_order_customer_id' ) );
		add_action( 'woocommerce_ajax_order_items_added',   array( $this, 'ajax_order_items_added' ), 10, 2 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'apply_cheapest_price_to_items' ), 10, 2 );
		add_action( 'woocommerce_thankyou',                 array( $this, 'prevent_price_update' ), 10, 1 );
	}

	/**
	 * Reset order customer in auto-draft order if select field got resetted.
	 *
	 * @Hook wp_ajax_reset_order_customer_id
	 *
	 * @return void
	 */
	public function reset_order_customer_id() {

		$response = array( 'success' => false );
		$order_id = intval( $_POST[ 'order_id' ] );
		$user_id  = intval( $_POST[ 'user_id' ] );
		$nonce    = sanitize_text_field( $_POST[ 'nonce' ] );

		if ( ! wp_verify_nonce( $nonce, 'bm-admin-nonce' ) ) {
			die();
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die( json_encode( $response ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! is_object( $order ) ) {
			die( json_encode( $response ) );
		}

		// Reset customer in order if we have an auto-draft only.
		if ( 'auto-draft' == $order->get_status() ) {
			update_post_meta( $order_id, '_customer_user', 0 );
			$response[ 'success' ] = true;
		}

		die( json_encode( $response ) );
	}

	/**
	 * @Hook wp_ajax_update_order_customer_id
	 *
	 * @return void
	 */
	public function update_order_customer_id() {

		$response = array( 'success' => false );
		$order_id = intval( $_POST[ 'order_id' ] );
		$user_id  = intval( $_POST[ 'user_id' ] );
		$nonce    = sanitize_text_field( $_POST[ 'nonce' ] );

		if ( ! wp_verify_nonce( $nonce, 'bm-admin-nonce' ) ) {
			die();
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die( json_encode( $response ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! is_object( $order ) ) {
			die( json_encode( $response ) );
		}

		// Store customer into order if we have an auto-draft only.
		if ( 'auto-draft' == $order->get_status() ) {
			update_post_meta( $order_id, '_customer_user', $user_id );
			$response[ 'success' ] = true;
		}

		die( json_encode( $response ) );
	}

	/**
	 * This function is fired when adding products to a manual order (after modal panel).
	 *
	 * @Hook woocommerce_ajax_order_items_added
	 *
	 * @param array    $added_items
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function ajax_order_items_added( $added_items, $order ) {

		self::apply_cheapest_price_to_items( $order, null );
	}

	/**
	 * Applies the cheapest price to items from order.
	 *
	 * @Hook woocommerce_before_order_object_save
	 *
	 * @param object $order current post  object.
	 * @param array  $data_store object with the current store abstraction.
	 *
	 * @return void
	 */
	public function apply_cheapest_price_to_items( $order, $data_store ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		$order_status = $order->get_status();
		$group_id     = self::get_customer_group_by_id( $order->get_user_id() );

		if ( ! is_null( $group_id ) ) {
			$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

			foreach ( $order->get_items() as $item_id => $item ) {

				$item_price_updated = get_post_meta( $item_id, 'line_item_updated', true );

				if ( ( 'auto-draft' == $order_status && $item_price_updated != $group_id ) || ! $item_price_updated ) {
					// Find the correct product by ID to use.
					$product = wc_get_product( $item['product_id'] );

					if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
						$product = wc_get_product( $item['variation_id'] );
					}

					// Get original price.
					$price = $product->get_regular_price();

					if ( floatval( $product->get_sale_price() ) > 0 ) {
						$price = floatval( $product->get_sale_price() );
					}

					// Check sale price end date.
					$sale_price_end_date = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

					if ( ! empty( $sale_price_end_date ) ) {
						$today  = gmdate( 'd-m-Y', strtotime( 'today' ) );
						$expire = gmdate( 'd-m-Y', intval( $sale_price_end_date ) );

						$today_date  = new DateTime( $today );
						$expire_date = new DateTime( $expire );

						if ( $expire_date < $today_date ) {
							$price = $product->get_regular_price();
						}
					}

					$cheapest_price = BM_Price::get_price( $price, $product, $group_id, $item->get_quantity() );
					$cheapest_price = wc_get_price_excluding_tax( $product, array( 'price' => $cheapest_price ) );

					$update_item_price = apply_filters( 'bm_update_admin_order_item_price', true );

					if ( $item->get_total() > 0 && $update_item_price ) {
						$item->set_subtotal( $cheapest_price * $item->get_quantity() );
						$item->set_total( $cheapest_price * $item->get_quantity() );
						update_post_meta( $item_id, 'line_item_updated', $group_id );
					}

					$item->calculate_taxes();
					$item->save();
				}
			}
		}
	}


	/**
	 * Get customer group by user id.
	 *
	 * @param  int $user_id given user id.
	 * @return int
	 */
	public static function get_customer_group_by_id( $user_id ) {
		$current_user = get_user_by( 'id', $user_id );

		if ( ! empty( $current_user->roles ) ) {
			foreach ( $current_user->roles as $slug ) {
				$group = get_page_by_path( $slug, OBJECT, 'customer_groups' );

				if ( ! is_null( $group ) ) {
					return $group->ID;
				}
			}
		}
	}

	/**
	 * Prevent order updates from frontend.
	 *
	 * @param  int $order_id given order id.
	 * @return void
	 */
	public function prevent_price_update( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			update_post_meta( $item_id, 'line_item_updated', true );
		}

		$order->save();
	}
}
