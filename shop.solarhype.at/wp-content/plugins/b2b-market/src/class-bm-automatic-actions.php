<?php

class BM_Automatic_Actions {
	/**
	 * @var string
	 */
	public $meta_prefix;

	/**
	 * BM_Automatic_Actions constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_before_cart',                     array( $this, 'add_first_order_discount' ), 10 );
		add_action( 'woocommerce_before_cart',                     array( $this, 'add_goods_discount' ), 10 );
		add_action( 'woocommerce_before_cart',                     array( $this, 'add_cart_discount' ), 10 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'on_action_cart_item_quantity_update' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label',        array( $this, 'replace_coupon_label_with_description' ), 10, 2 );
	}

	/**
	 * Generate discount for first order.
	 *
	 * @Hook woocommerce_before_cart
	 *
	 * @acces public
	 *
	 * @return void
	 * @throws Exception
	 */
	public function add_first_order_discount() {
		global $sitepress;

		$cart = WC()->cart;

		$min_order_amount  = apply_filters( 'bm_first_order_min_amount', false, $cart );
		$customer_group_id = BM_Conditionals::get_validated_customer_group();

		if ( ! empty( $customer_group_id ) && true == self::is_first_order() && false === $min_order_amount ) {

			/* discount meta */
			$discount_name      = get_post_meta( intval( $customer_group_id ), 'bm_discount_name', true );
			$discount_value     = get_post_meta( intval( $customer_group_id ), 'bm_discount', true );
			$discount_type      = get_post_meta( intval( $customer_group_id ), 'bm_discount_type', true );
			$discount_available = false;
			$group_slug         = BM_Helper::get_group_slug( $customer_group_id );
			$user_id            = get_current_user_id();

			if ( ! empty( $discount_value ) && ! empty( $discount_type ) ) {

				/* selection rules meta */
				$discount_products     = explode( ',', get_post_meta( $customer_group_id, 'bm_discount_products', true ) );
				$discount_categories   = explode( ',', get_post_meta( $customer_group_id, 'bm_discount_categories', true ) );
				$discount_all_products = get_post_meta( $customer_group_id, 'bm_discount_all_products', true );
				$allowed_products      = array();
				$allowed_cats          = array();

				// Check for post-status for linked discounted products.
				if ( is_array( $discount_products ) && ( 0 < count( $discount_products ) ) && ( true === apply_filters( 'bm_filter_update_linked_products_in_customer_groups_on_delete_post', false ) ) ) {
					foreach ( $discount_products as $key => $product_id ) {
						if ( 'publish' != get_post_status( $product_id ) ) {
							unset( $discount_products[ $key ] );
						}
					}
				}

				// WPML support.
				if ( ! empty( $sitepress ) ) {
					if ( ! empty( $discount_products ) ) {
						$translated_discount_products = BM_Helper::get_translated_object_ids( $discount_products, 'post' );
						$discount_products            = $translated_discount_products;
					}
					if ( ! empty( $discount_categories ) ) {
						$translated_discount_categories = BM_Helper::get_translated_object_ids( $discount_categories, 'category' );
						$discount_categories            = $translated_discount_categories;
					}
				}

				if ( 'on' === $discount_all_products ) {
					$discount_available      = true;
					$allowed_products['all'] = true;
				} else {
					foreach ( $cart->get_cart() as $item => $values ) {
						$_product        = wc_get_product( $values[ 'product_id' ] );
						$product_cat_ids = $_product->get_category_ids();

						if ( in_array( $_product->get_id(), $discount_products ) ) {
							$discount_available = true;
							$allowed_products[] = $_product->get_id();
						}
						if ( isset( $product_cat_ids ) && is_array( $product_cat_ids ) ) {
							foreach ( $product_cat_ids as $cat ) {
								if ( in_array( $cat, $discount_categories ) ) {
									$discount_available = true;
									$allowed_cats[]     = $cat;
									$allowed_products[] = $_product->get_id();
								}
							}
						}
					}
				}
			}

			if ( true === $discount_available ) {
				// calculate and apply discount

				if ( true === wc_coupons_enabled() && is_cart() ) {
					$discount       = floatval( $discount_value );
					$coupon_code    = 'first_order_' . $group_slug . '_' . $user_id;
					$coupon_updated = false;

					if ( false === self::is_coupon_valid( $coupon_code ) ) {
						$coupon = $this->generate_coupon( $coupon_code, $discount_type, $discount, $discount_name, $allowed_products, $allowed_cats, true );
					} else {
						$coupon      = new WC_Coupon( $coupon_code );
						$coupon_id   = $coupon->get_id();
						$coupon_post = get_post( $coupon_id );
						// Updating coupon values if B2B settings changed.
						if ( $coupon_post->post_excerpt != $discount_name ) {
							update_post_meta( $coupon_id, 'post_excerpt', $discount_name );
						}
						if ( $discount != $coupon->get_amount() ) {
							$coupon->set_amount( $discount );
							$coupon_updated = true;
						}
						if ( $discount_type != $coupon->get_discount_type() ) {
							$type = self::get_discount_type( $discount_type );
							$coupon->set_discount_type( $type );
							$coupon_updated = true;
						}
						if ( $allowed_products != $coupon->get_product_ids() ) {
							$coupon->set_product_ids( $allowed_products );
							$coupon_updated = true;
						}
						if ( $allowed_cats != $coupon->get_product_categories() ) {
							$coupon->set_product_categories( $allowed_cats );
							$coupon_updated = true;
						}
						if ( true === $coupon_updated ) {
							$coupon->save();
						}
					}

					if ( $coupon->get_usage_count() < $coupon->get_usage_limit() || $coupon->get_usage_limit() == 0 ) {
						// Remove used coupon from cart if coupon got updated.
						if ( true === $coupon_updated && $cart->has_discount( $coupon_code ) ) {
							WC()->cart->remove_coupon( $coupon_code );
						}
						if ( ! $cart->has_discount( $coupon_code ) ) {
							WC()->cart->add_discount( wc_format_coupon_code( $coupon_code ) );
						}
					} else {
						WC()->cart->remove_coupon( $coupon_code );
					}
				}
			}
		}
	}

	/**
	 * Generate discount coupon in cart if possible.
	 *
	 * @Hook woocommerce_before_cart
	 *
	 * @acces public
	 *
	 * @return void
	 */
	public function add_goods_discount() {

		$cart = WC()->cart;

		if ( ! empty( BM_Conditionals::get_validated_customer_group() ) ) {

			$goods_categories    = get_post_meta( BM_Conditionals::get_validated_customer_group(), 'bm_goods_discount_categories', true );
			$goods_product_count = intval( get_post_meta( BM_Conditionals::get_validated_customer_group(), 'bm_goods_product_count', true ) );
			$goods_discount      = get_post_meta( BM_Conditionals::get_validated_customer_group(), 'bm_goods_discount', true );
			$goods_discount_type = get_post_meta( BM_Conditionals::get_validated_customer_group(), 'bm_goods_discount_type', true );

			$goods_categories_array_temp = explode( ',', $goods_categories );
			$translated_goods_categories = BM_Helper::get_translated_object_ids( $goods_categories_array_temp, 'category' );
			$goods_categories_array      = $translated_goods_categories;

			$valid_products      = array();
			$valid_categories    = array();
			$valid_cart_quantity = 0;

			$group_id         = BM_Conditionals::get_validated_customer_group();
			$group_slug       = BM_Helper::get_group_slug( $group_id );
			$user_id          = get_current_user_id();
			$allowed_products = array();
			$allowed_cats     = array();

			/* check if products in discount category */
			foreach ( $cart->get_cart() as $item => $values ) {

				$_product = wc_get_product( $values[ 'product_id' ] );

				if ( false === $_product ) {
					return;
				}

				$product_cat_ids = $_product->get_category_ids();

				foreach ( $product_cat_ids as $category_id ) {
					$parent_cats = get_ancestors($category_id, 'product_cat');
					if ( is_array( $parent_cats ) && ! empty( $parent_cats ) ) {
						foreach( $parent_cats as $parent_cat ) {
							$product_cat_ids[] = $parent_cat;
						}
					}
				}

				foreach ( $goods_categories_array as $cat ) {

					if ( in_array( $cat, $product_cat_ids ) ) {
						array_push( $valid_products, array( $_product->get_id() => $values[ 'quantity' ] ) );
						$allowed_products[] = $_product->get_id();
						$valid_categories[] = $cat;
					}
				}
			}
			/* check if quantity match discount quantity */
			if ( isset( $valid_products ) ) {
				foreach ( $valid_products as $product ) {
					foreach ( $product as $key => $value ) {
						$valid_cart_quantity = $valid_cart_quantity + $value;
					}
				}
			}

			if ( ! empty( $goods_discount ) && ! empty( $goods_discount_type ) ) {

				$discounted_cats = array();

				foreach ( $valid_categories as $id ) {
					$term              = get_term_by( 'id', $id, 'product_cat' );
					$discounted_cats[] = $term->name;
				}

				/* dynamic discount name */
				$discount_name = '';

				if ( count( array_unique( $discounted_cats ) ) == 1 ) {
					$discount_name = apply_filters( 'bm_cat_qty_discount_name', $goods_product_count . ' ' . __( 'Products', 'b2b-market' ) . ' ' . __( 'from Product Category', 'b2b-market' ) . ': ' . implode( ',', array_unique( $discounted_cats ) ) );
				} elseif ( count( array_unique( $discounted_cats ) ) > 1 ) {
					$discount_name = apply_filters( 'bm_cat_qty_discount_name', $goods_product_count . ' ' . __( 'Products', 'b2b-market' ) . ' ' . __( 'from Product Categories', 'b2b-market' ) . ': ' . implode( ',', array_unique( $discounted_cats ) ) );
				}

				$allowed_products = implode( ',', $allowed_products );

				if ( true === wc_coupons_enabled() && is_cart() ) {

					$discount       = floatval( $goods_discount );
					$coupon_code    = 'category_discount' . $group_slug . '_' . $user_id;
					$coupon_updated = false;

					if ( false === self::is_coupon_valid( $coupon_code ) ) {
						$coupon = $this->generate_coupon( $coupon_code, $goods_discount_type, $discount, $discount_name, '', $goods_categories_array, false );
					} else {
						$coupon      = new WC_Coupon( $coupon_code );
						$coupon_id   = $coupon->get_id();
						$coupon_post = get_post( $coupon_id );
						// Updating coupon values if B2B settings changed.
						if ( $coupon_post->post_excerpt != $discount_name ) {
							update_post_meta( $coupon_id, 'post_excerpt', $discount_name );
						}
						if ( $discount != $coupon->get_amount() ) {
							$coupon->set_amount( $discount );
							$coupon_updated = true;
						}
						if ( $goods_discount_type != $coupon->get_discount_type() ) {
							$type = self::get_discount_type( $goods_discount_type );
							$coupon->set_discount_type( $type );
							$coupon_updated = true;
						}
						if ( true === $coupon_updated ) {
							$coupon->save();
						}
					}

					if ( $coupon->get_usage_count() < $coupon->get_usage_limit() || $coupon->get_usage_limit() == 0 ) {
						if ( count( $valid_products ) >= $goods_product_count || $valid_cart_quantity >= $goods_product_count ) {
							// Remove used coupon from cart if coupon got updated.
							if ( true === $coupon_updated && $cart->has_discount( $coupon_code ) ) {
								WC()->cart->remove_coupon( $coupon_code );
							}
							if ( ! $cart->has_discount( $coupon_code ) ) {
								WC()->cart->add_discount( wc_format_coupon_code( $coupon_code ) );
							}
						} else {
							WC()->cart->remove_coupon( $coupon_code );
						}
					}
				}
			}
		}
	}

	/**
	 * @Hook on_action_cart_item_quantity_update
	 *
	 * @access public
	 *
	 * @param string $cart_item_key
	 * @param int    $quantity
	 * @param int    $old_quantity
	 *
	 * @return void
	 */
	public function on_action_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity ) {

		if ( ! is_cart() ) {
			return; // Only on cart page
		}

		$this->add_cart_discount();
	}

	/**
	 * Generate discount coupon in cart if possible.
	 *
	 * @Hook woocommerce_before_cart
	 *
	 * @acces public
	 *
	 * @return void
	 */
	public function add_cart_discount() {

		if ( ! empty( BM_Conditionals::get_validated_customer_group() ) ) {

			$group_id          = BM_Conditionals::get_validated_customer_group();
			$cart_discounts    = get_post_meta( $group_id, 'bm_cart_discounts', true );
			$group_tax_setting = get_post_meta( $group_id, 'bm_tax_type', true );
			$group_object      = get_post( $group_id );
			$group_slug        = $group_object->post_name;
			$user_id           = get_current_user_id();
			$coupon_code       = 'cart_discount' . $group_slug . '_' . $user_id;

			// Get cart data.
			$cart = WC()->cart;

			// Loop through used coupons.
			$coupons = $cart->get_applied_coupons();
			foreach ( $coupons as $applied_coupon_code ) {
				if ( false !== strpos( $applied_coupon_code, 'cart_discount' ) ) {
					if ( $applied_coupon_code !== $coupon_code ) {
						$cart->remove_coupon( $applied_coupon_code );
					}
				}
			}

			// Get cart subtotal depending on group tax setting.
			if ( 'on' === $group_tax_setting ) {
				$sub_total = WC()->cart->get_subtotal();
			} else {
				$sub_total = WC()->cart->subtotal;
			}

			if ( $sub_total <= 0 ) {
				return;
			}

			if ( is_array( $cart_discounts ) ) {
				$discount_value = false;
				$discount_type  = false;
				$discount_name  = apply_filters( 'bm_cart_discount_name', __( 'Cart Discount', 'b2b-market' ) );
				// Sort discount entries.
				array_multisort(array_map(function( $discount ) {
					return $discount[ 'cart_discount_from' ];
				}, $cart_discounts), SORT_ASC, $cart_discounts );
				// Walking through discounts.
				foreach( $cart_discounts as $discount ) {
					if ( ( intval( $discount[ 'cart_discount_from' ] ) >= 0 ) && ( $sub_total >= $discount[ 'cart_discount_from' ] ) ) {
						$discount_value = $discount[ 'cart_discount' ];
						$discount_type  = $discount[ 'cart_discount_type' ];
					}
				}
				if ( true === wc_coupons_enabled() && is_cart() ) {
					$coupon_exists = get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' );
					if ( ( false !== $discount_value ) && ( false !== $discount_type ) ) {
						if ( is_null( $coupon_exists ) || 'publish' !== $coupon_exists->post_status ) {
							$coupon = $this->generate_coupon( $coupon_code, $discount_type, $discount_value, $discount_name, '', array(), false );
						} else {
							// Update coupon if excerpt has changed.
							$coupon = new WC_Coupon( $coupon_code );
							if ( $cart->get_coupon_discount_amount( $coupon_code ) != $discount_value ) {
								$coupon->set_amount($discount_value );
								$coupon->save_meta_data();
								$coupon->save();
							}
							if ( $coupon->get_discount_type() != $discount_type ) {
								$type = self::get_discount_type( $discount_type );
								$coupon->set_discount_type($type );
								$coupon->save_meta_data();
								$coupon->save();
							}
							if ( $coupon_exists->post_excerpt != $discount_name ) {
								$coupon_id = $coupon_exists->ID;
								$coupon    = get_post( $coupon_id );
								$coupon->post_excerpt = $discount_name;
								wp_update_post( $coupon );
							}
						}

						$coupon_apply = new WC_Coupon( $coupon_code );

						if ( ( $coupon_apply->get_usage_count() < $coupon_apply->get_usage_limit() ) || ( 0 === $coupon_apply->get_usage_limit() ) ) {
							if ( ! $cart->has_discount( $coupon_code ) ) {
								$cart->add_discount( wc_format_coupon_code( $coupon_code ) );
							}
						} else {
							// Remove a voucher.
							$cart->remove_coupon( $coupon_code );
						}
					} else {
						// Remove a voucher.
						$cart->remove_coupon( $coupon_code );
					}

				}

				// Recalculate cart totals.
				$cart->calculate_totals();
			}

		}
	}

	/**
	 * Checks if a coupon still exists.
	 *
	 * @acces public
	 * @static
	 *
	 * @param string $coupon_code
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function is_coupon_valid( $coupon_code ) {

		$coupon    = new WC_Coupon( $coupon_code );
		$discounts = new WC_Discounts( WC()->cart );
		$response  = $discounts->is_coupon_valid( $coupon );

		return ! is_wp_error( $response );
	}

	/**
	 * Check if its first customer order.
	 *
	 * @acces public
	 * @static
	 *
	 * @return bool
	 */
	public static function is_first_order() {

		$customer_orders = get_posts( array(
			'numberposts' => - 1,
			'meta_key'    => '_customer_user',
			'meta_value'  => get_current_user_id(),
			'post_type'   => wc_get_order_types(),
			'post_status' => array_keys( wc_get_order_statuses() ),
			'fields'      => 'ids',
		) );

		if ( count( $customer_orders ) > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate discount coupon.
	 *
	 * @acces protected
	 *
	 * @param string       $coupon_code
	 * @param string       $discount_type
	 * @param float        $discount_amount
	 * @param string       $discount_name
	 * @param string|array $allowed_products
	 * @param string|array $allowed_cats
	 * @param bool         $first_order
	 *
	 * @return WC_Coupon coupon object
	 */
	protected function generate_coupon( $coupon_code, $discount_type, $discount_amount, $discount_name, $allowed_products, $allowed_cats, $first_order = false ) {

		$type                        = self::get_discount_type( $discount_type );
		$coupon_usage_limit          = ( true === $first_order ) ? 1 : intval( apply_filters( 'bm_filter_coupons_usage_limit', 0 ) );
		$coupon_usage_limit_per_user = ( true === $first_order ) ? 1 : intval( apply_filters( 'bm_filter_coupons_limit_per_user', 0 ) );

		$customer = new WC_Customer( get_current_user_id() );
		$coupon   = new WC_Coupon();

		// Set coupon code.
		$coupon->set_code( $coupon_code );
		// Set discount type.
		$coupon->set_discount_type( $type );
		// Set description.
		$coupon->set_description( $discount_name );
		// Set coupon amount.
		$coupon->set_amount( $discount_amount );
		// Set usage limit (unlimited by default).
		$coupon->set_usage_limit( $coupon_usage_limit );
		// Set usage limit per user (unlimited by default).
		$coupon->set_usage_limit_per_user( $coupon_usage_limit_per_user );
		// Set expiring date (default is null).
		$coupon->set_date_expires( apply_filters( 'bm_filter_coupons_date_expires', null ) );
		// Set free shipping.
		$coupon->set_free_shipping( apply_filters( 'bm_filter_coupons_free_shipping', false ) );
		// Restrict coupon to users email address.
		$coupon->set_email_restrictions( $customer->get_email() );

		// Set allowed product ids and/or categories.
		if ( ! isset( $allowed_products[ 'all' ] ) ) {
			$coupon->set_product_ids( is_array( $allowed_products ) ? $allowed_products : array( $allowed_products ) );
			$coupon->set_product_categories( is_array( $allowed_cats ) ? $allowed_cats : array( $allowed_cats ) );
		}

		$coupon->save();

		$coupon_id = $coupon->get_id();
		update_post_meta( $coupon_id, 'post_excerpt', $discount_name );

		return $coupon;
	}

	/**
	 * Returns the discount type.
	 *
	 * @acces public
	 * @static
	 *
	 * @param string $discount_type
	 *
	 * @return string
	 */
	public static function get_discount_type( $discount_type ) {

		$type = '';
		if ( 'order-discount-fix' == $discount_type || 'discount' == $discount_type ) {
			$type = 'fixed_cart';
		} else
		if ( 'order-discount-percent' == $discount_type || 'discount-percent' == $discount_type ) {
			$type = 'percent';
		}

		return $type;
	}

	public function replace_coupon_label_with_description( $label, $coupon ) {

		if ( false !== strpos( $coupon->get_code(), 'cart_discount' ) || false !== strpos( $coupon->get_code(), 'first_order' ) || false !== strpos( $coupon->get_code(), 'category_discount' ) ) {

			if ( is_callable( array( $coupon, 'get_description' ) ) ) {
				$description = $coupon->get_description();
			} else {
				$coupon_post = get_post( $coupon->id );
				$description = ! empty( $coupon_post->post_excerpt ) ? $coupon_post->post_excerpt : null;
			}
			return $description ? sprintf( esc_html__( 'Coupon: %s', 'woocommerce' ), $description ) : esc_html__( 'Coupon', 'woocommerce' );
		} else {
			return $label;
		}
	}

}

new BM_Automatic_Actions();
