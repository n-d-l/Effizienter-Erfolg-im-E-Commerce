<?php
/**
 * Class to handle price calculation in B2B Market.
 */
class BM_Price {

	/**
	 * @var bool
	 */
	private static bool $is_minicart = false;

	/**
	 * @var bool
	 */
	private static bool $is_quickedit = false;

	/**
	 * @var int
	 */
	public static int $set_price_prio = 10;

	/**
	 * @var float|int
	 */
	private static float $product_price = 0;

	/**
	 * Caching multiple bulk discount queries on filter 'bm_bulk_discount_string'.
	 *
	 * @acces private
	 * @static
	 *
	 * @var array
	 */
	private static $run_time_cache = array();

	/**
	 * Contains instance or null
	 *
	 * @acces private
	 * @static
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of BM_Price.
	 *
	 * @acces public
	 * @static
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
	 * Constructor for BM_Price.
	 *
	 * @acces public
	 *
	 * @return void
	 */
	public function __construct() {

		self::$is_quickedit = ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST[ 'woocommerce_quick_edit_nonce' ] ) );

		// Set higher prio for Quickedit Ajax Request
		if ( true === self::$is_quickedit ) {
			self::$set_price_prio = 99;
		}

		// B2B price for Cross-Sell products.
		add_action( 'woocommerce_before_template_part', array( $this, 'before_cross_sales' ) );
		add_action( 'woocommerce_after_template_part', array( $this, 'after_cross_sales' ) );

		// Simple and external products.
		add_filter( 'woocommerce_product_get_price', array( $this, 'set_price' ), self::$set_price_prio, 2 );

		// Modify regular price if sale price and B2B price available.
		add_filter( 'woocommerce_product_get_regular_price', array( $this, 'set_regular_price' ), 10, 2 );

		// Variations.
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_price' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'set_regular_price' ), 10, 2 );

		// Variable product prices.
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'set_variable_price' ), 10, 3 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'set_variable_regular_price' ), 10, 3 );

		// Handling price caching.
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'regenerate_hash' ), 10, 1 );

		// Modify cart prices.
		if ( false === self::$is_quickedit ) {
			add_action( 'woocommerce_get_cart_contents', array( $this, 'recalculate_prices' ) );
			add_action( 'woocommerce_before_main_content', array( $this, 'reenable_prices' ) );
		}

		// Price output.
		add_filter( 'woocommerce_get_price_html', array( $this, 'set_price_html' ), 4, 2 );

		// Price output for variable products in backend only.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_filter( 'woocommerce_variable_price_html', array( $this, 'set_variable_price_html' ), 10, 2 );
		}

		// Sale price output.
		add_filter( 'woocommerce_product_is_on_sale', array( $this, 'product_is_on_sale' ), 10, 2);
		add_filter( 'woocommerce_sale_flash', array( $this, 'show_sale_badge' ), 10, 3 );
		add_filter( 'woocommerce_format_sale_price', array( $this, 'show_sale_price' ), 20, 3 );
		add_filter( 'atomion_sale_percentage', array( $this, 'calculate_atomion_sale_percentage' ), 10, 2 );
		add_filter( 'atomion_sale_badge_html', array( $this, 'show_sale_price_atomion' ), 10, 6 );

		// Temporary Fix for Atomion percentage sale badge.
		add_filter( 'theme_mod_wcpc_percentage_discount_setting', array( $this, 'modify_percentage_discount' ) );

		// Grouped prices output.
		add_filter( 'woocommerce_grouped_price_html', array( $this, 'set_grouped_price_html' ), 10, 3 );
	}

	/**
	 * Set B2B price filter before Cross-Sell template part.
	 *
	 * @Hook woocommerce_before_template_part
	 *
	 * @acces public
	 *
	 * @param string $template_name template file
	 *
	 * @return void
	 */
	public function before_cross_sales( $template_name ) {

		if ( 'cart/cross-sells.php' === $template_name ) {
			add_filter( 'woocommerce_product_get_price', array( $this, 'set_price_in_cross_sales_in_cart' ), 10, 2 );
		}
	}

	/**
	 * Remove B2B price filter after Cross-Sell template part.
	 *
	 * @Hook woocommerce_after_template_part
	 *
	 * @acces public
	 *
	 * @param string $template_name template file
	 *
	 * @return void
	 */
	public function after_cross_sales( $template_name ) {

		if ( 'cart/cross-sells.php' === $template_name ) {
			remove_filter( 'woocommerce_product_get_price', array( $this, 'set_price_in_cross_sales_in_cart' ), 10, 2 );
		}
	}

	/**
	 * Looking for B2B price for Cross-Sells on cart and checkout page.
	 *
	 * @acces public
	 *
	 * @param string     $price
	 * @param WC_Product $product
	 *
	 * @return float|string
	 */
	public function set_price_in_cross_sales_in_cart( $price, $product ) {

		if ( is_cart() || is_checkout() ) {

			$group_id       = BM_Conditionals::get_validated_customer_group();
			$cheapest_price = self::get_price( $price, $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );

			if ( $cheapest_price > 0 ) {
				return $cheapest_price;
			}
		}

		return $price;
	}

	/**
	 * Deactivate percentage badge conditionally.
	 *
	 * @acces public
	 *
	 * @param  bool $option given option.
	 *
	 * @return bool
	 */
	public function modify_percentage_discount( $option ) {

		return false;
	}

	/**
	 * Get calculated price.
	 *
	 * @acces public
	 * @static
	 *
	 * @param  string $price current price.
	 * @param  object $product current product object.
	 * @param  int    $group_id current group id.
	 * @param  int    $qty current quantity of the product.
	 *
	 * @return float
	 */
	public static function get_price( $price, $product, $group_id, $qty ) {
		global $sitepress;

		if ( false === $group_id ) {
			$group_id = BM_Conditionals::get_validated_customer_group();
		}

		// Save group and product data to reduce requests.
		$group             = get_post( $group_id );
		$product_id        = $product->get_id();
		$fallback_group_id = get_option( 'bm_fallback_customer_group' );

		if ( empty( $group_id ) ) {
			return $price;
		}

		if ( property_exists( $product, 'bundled_item_price' ) && $product->bundled_item_price > 0 ) {
			return $price;
		}

		// Check if 'Sale price' of 0 is given.
		if ( '0' == $price ) {
			$price = get_post_meta( $product_id, '_regular_price', true );
		}

		// Ensure price is float.
		$price = floatval( apply_filters( 'bm_filter_get_price', $price, $product ) );

		// filter for manipulating prices.
		$force_product_price = apply_filters( 'bm_force_product_price', false, $product_id, $group_id );
		$use_regular         = apply_filters( 'bm_use_regular_for_group_price', false );

		if ( true === $use_regular ) {
			$regular = get_post_meta( $product_id, '_regular_price', true );
			$price   = $regular;
		}

		// Force and return product price if filter is set.
		if ( true === $force_product_price ) {
			return $price;
		}

		// Generate runtime cache key.
		$runtime_cache_key = sanitize_key( 'bm_cache_cheapest_price_' . $product_id . '_' . $group_id . '_' . $qty );

		// Check if runtime cache is already set
		if ( ( false === self::$is_quickedit ) && isset( self::$run_time_cache[ $runtime_cache_key ] ) && ( false === apply_filters( 'bm_filter_disable_runtime_cache_cheapest_price', false, $product_id, $group_id, $qty ) ) ) {
			return self::$run_time_cache[ $runtime_cache_key ];
		}

		// collect prices in array.
		$prices = array();

		// check if it's a variation.
		if ( 'variation' === $product->get_type() ) {
			$parent_id = $product->get_parent_id();
		} else {
			$parent_id = 0;
		}

		// get group prices from product and customer group.
		$group_prices = apply_filters(
			'bm_group_prices',
			array(
				'global'  => get_option( 'bm_global_group_prices' ),
				'group'   => get_post_meta( $group_id, 'bm_group_prices', true ),
				'product' => get_post_meta( $product_id, 'bm_' . $group->post_name . '_group_prices', true ),
			)
		);

		// get bulk prices from product, customer group.
		$bulk_prices = apply_filters(
			'bm_bulk_prices',
			array(
				'global'  => get_option( 'bm_global_bulk_prices' ),
				'group'   => get_post_meta( $group_id, 'bm_bulk_prices', true ),
				'product' => get_post_meta( $product_id, 'bm_' . $group->post_name . '_bulk_prices', true ),
			)
		);

		// collect bulk prices for sorting.
		$sortable_bulk_prices = array();

		// calculate group prices and add them to $prices.
		if ( ! empty( $group_prices ) ) {
			foreach ( $group_prices as $price_type => $price_entries ) {
				if ( is_array( $price_entries ) ) {
					foreach ( $price_entries as $price_data ) {

						// no price skip entry.
						if ( empty( $price_data['group_price'] ) ) {
							continue;
						}

						// no type set.
						if ( empty( $price_data['group_price_type'] ) ) {
							$type = 'fix';
						} else {
							$type = $price_data['group_price_type'];
						}

						// no category set.
						if ( empty( $price_data['group_price_category'] ) ) {
							$category = 0;
						} else {
							$category = BM_Helper::get_translated_object_ids( $price_data[ 'group_price_category' ], 'category' );
						}

						// check for category restriction before calculating.
						if ( $category > 0 ) {
							// if it's a variation we need to check for the parent id.
							if ( $parent_id > 0 ) {
								if ( ! has_term( $category, 'product_cat', $parent_id ) && ! self::product_in_descendant_category( $category, $parent_id ) ) {
									continue;
								}
							} else {
								if ( ! has_term( $category, 'product_cat', $product_id ) && ! self::product_in_descendant_category( $category, $product_id ) ) {
									continue;
								}
							}
						}

						// ensure price is float.
						$group_price = floatval( $price_data['group_price'] );

						// check type, calculate price and add them to prices.
						if ( $group_price > 0 ) {
							switch ( $type ) {
								case 'fix':
									$prices[] = $group_price;
									break;

								case 'discount':
									$group_price = $price - $group_price;

									if ( $group_price > 0 ) {
										$prices[] = $group_price;
									}
									break;

								case 'discount-percent':
									$group_price = $price - ( $group_price * $price / 100 );
									if ( $group_price > 0 ) {
										$prices[] = $group_price;
									}
									break;
							}
						}
					}
				}
			}
		}

		// calculate bulk prices and add them to $prices.
		if ( ! empty( $bulk_prices ) ) {
			foreach ( $bulk_prices as $price_type => $price_entries ) {

				if ( is_array( $price_entries ) ) {
					foreach ( $price_entries as $price_data ) {

						// no price skip.
						if ( empty( $price_data['bulk_price'] ) ) {
							continue;
						}
						// no from skip.
						if ( empty( $price_data['bulk_price_from'] ) ) {
							continue;
						}

						// no type set default.
						if ( empty( $price_data['bulk_price_type'] ) ) {
							$type = 'fix';
						} else {
							$type = $price_data['bulk_price_type'];
						}

						// $to equals 0.
						if ( 0 === intval( $price_data['bulk_price_to'] ) ) {
							$to = INF;
						} else {
							$to = intval( $price_data['bulk_price_to'] );
						}

						// no category set.
						if ( empty( $price_data['bulk_price_category'] ) ) {
							$category = 0;
						} else {
							$category = BM_Helper::get_translated_object_ids( $price_data[ 'bulk_price_category' ], 'category' );
						}

						// check for category restriction before calculating.
						if ( $category > 0 ) {
							// if it's a variation we need to check for the parent id.
							if ( $parent_id > 0 ) {
								if ( ! has_term( $category, 'product_cat', $parent_id ) && ! self::product_in_descendant_category( $category, $parent_id ) ) {
									continue;
								}
							} else {
								if ( ! has_term( $category, 'product_cat', $product_id ) && ! self::product_in_descendant_category( $category, $product_id ) ) {
									continue;
								}
							}
						}

						$bulk_price = floatval( $price_data['bulk_price'] );
						$from       = intval( $price_data['bulk_price_from'] );

						if ( $bulk_price > 0 ) {
							switch ( $type ) {
								case 'fix':
									$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;

									// add to prices if matched qty.
									if ( ( $qty >= $from ) && ( $qty <= $to ) ) {
										$prices[] = $bulk_price;
									}
									break;

								case 'discount':
									$bulk_price = $price - $bulk_price;

									if ( $bulk_price > 0 ) {
										$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;
									}

									// add to prices if matched qty.
									if ( ( $qty >= $from ) && ( $qty <= $to ) && $bulk_price > 0 ) {
										$prices[] = $bulk_price;
									}
									break;

								case 'discount-percent':
									$bulk_price = $price - ( $bulk_price * $price / 100 );

									if ( $bulk_price > 0 ) {
										$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;
									}

									// add to prices if matched qty.
									if ( ( $qty >= $from ) && ( $qty <= $to ) && $bulk_price > 0 ) {
										$prices[] = $bulk_price;
									}
									break;
							}
						}
					}
				}
			}
		}

		// Display cheapest bulk when available.
		if ( ! empty( $sortable_bulk_prices[0] ) ) {
			// sort bulk prices by cheapest.
			sort( $sortable_bulk_prices, SORT_NATURAL );
			$cheapest_bulk_price = $sortable_bulk_prices[0];

			// display possible discount before price.
			add_filter(
				'bm_bulk_discount_string',
				function ( $string, $product_id ) use ( $cheapest_bulk_price ) {

					$cheapest_bulk_price = explode( '|', $cheapest_bulk_price );

					// Modify price based on tax setting.
					$product = wc_get_product( $product_id );

					$cheapest_price = BM_Tax::get_tax_price( $product, $cheapest_bulk_price[0] );

					if ( $cheapest_price > 0 ) {

						$bulk_price_message = str_replace( array( '[bulk_qty]', '[bulk_price]' ), array( $cheapest_bulk_price[ 1 ], wc_price( $cheapest_price ) ), get_option( 'bm_bulk_price_discount_message', 'From [bulk_qty]x only [bulk_price] each.' ) );

						// Check if condtions match.
						$show_on_shop    = get_option( 'bm_bulk_price_on_shop', 'off' );
						$show_on_product = get_option( 'bm_bulk_price_on_product', 'off' );

						if ( 'on' === $show_on_shop && ( is_shop() || is_product_category() || is_product_tag() ) ) {
							if ( $product_id == $cheapest_bulk_price[ 2 ] ) {
								$string = '<span class="bm-cheapest-bulk" style="float:left;width:100%;margin-bottom:10px;"><b>' . $bulk_price_message . '</b></span></br>';
							}
						}
						if ( 'on' === $show_on_product && ( is_singular( 'product' ) || is_product() ) ) {
							if ( $product_id == $cheapest_bulk_price[ 2 ] ) {
								$string = '<span class="bm-cheapest-bulk" style="float:left;width:100%;margin-bottom:10px;"><b>' . $bulk_price_message . '</b></span></br>';
							}
						}
					} else {
						$string = '';
					}

					return $string;
				},
				10,
				2
			);
		}

		// add the original price.
		$prices[] = $price;

		// get the cheapest price from array.
		$price = min( $prices );

		// fill runtime cache.
		if ( $price > 0 ) {
			self::$run_time_cache[ $runtime_cache_key ] = $price;
		}

		return $price;
	}

	/**
	 * Set price.
	 *
	 * @acces public
	 *
	 * @param  float  $price current price.
	 * @param  object $product current product object.
	 *
	 * @return float
	 */
	public function set_price( $price, $product ) {

		if ( is_cart() || is_checkout() ) {
			return $price;
		}

		$group_id       = BM_Conditionals::get_validated_customer_group();
		$cheapest_price = self::get_price( $price, $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );

		if ( $cheapest_price > 0 ) {
			return $cheapest_price;
		}

		return $price;
	}

	/**
	 * Set regular price if sale price and cheapest price is available.
	 *
	 * @acces public
	 *
	 * @param float      $regular_price given regular price.
	 * @param WC_Product $product given product object.
	 *
	 * @return float
	 *
	 * @throws Exception
	 */
	public function set_regular_price( $regular_price, $product ) {

		// Check if Quickedit Ajax Request
		if ( is_admin() && defined( 'DOING_AJAX' ) && ! empty( $_POST[ 'woocommerce_quick_edit_nonce' ] ) ) {
			return $regular_price;
		}

		if ( is_cart() || is_checkout() || 'woosb' === $product->get_type() || is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return $regular_price;
		}

		$sale_price = floatval( $product->get_sale_price( apply_filters( 'bm_filter_get_sale_price_context', 'view' ) ) );

		// Check sale price end date.
		$sale_price_start_date = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$sale_price_end_date   = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

		if ( ! empty( $sale_price_end_date ) ) {
			$timezone = new DateTimeZone( wp_timezone_string() );
			$today    = wp_date("d-m-Y", strtotime( 'today' ), $timezone );
			$start    = wp_date("d-m-Y", intval( $sale_price_start_date ), $timezone );
			$expire   = wp_date("d-m-Y", intval( $sale_price_end_date ), $timezone );

			$today_date  = new DateTime( $today );
			$start_date  = new DateTime( $start );
			$expire_date = new DateTime( $expire );

			if ( ( $expire_date < $today_date ) || ( $start_date > $today_date ) ) {
				$sale_price = $regular_price;
			}
		}

		if ( $sale_price > floatval( $product->get_price( apply_filters( 'bm_filter_get_price_context', 'view' ) ) ) ) {
			return $sale_price . '.00000000001';
		}

		return (float) $regular_price;
	}

	/**
	 * Set price for variable products.
	 *
	 * @Hook woocommerce_variation_prices_price
	 *
	 * @acces public
	 *
	 * @param float  $price current price.
	 * @param object $variation current variation.
	 * @param object $product current product.
	 *
	 * @return float
	 */
	public function set_variable_price( $price, $variation, $product ) {

		$group_id       = BM_Conditionals::get_validated_customer_group();
		$cheapest_price = self::get_price( $price, $variation, $group_id, apply_filters( 'bm_default_qty', 1 ) );

		if ( $cheapest_price > 0 && $price > $cheapest_price ) {
			return $cheapest_price;
		}

		return $price;
	}

	/**
	 * Set regular price for variable products.
	 *
	 * @Hook woocommerce_variation_prices_regular_price
	 *
	 * @acces public
	 *
	 * @param float  $price current price.
	 * @param object $variation current variation.
	 * @param object $product current product.
	 *
	 * @return float
	 */
	public function set_variable_regular_price( $price, $variation, $product ) {

		$regular_price = $variation->get_regular_price();

		if ( $regular_price < $price && $regular_price > 0 ) {
			$price = $regular_price;
		}

		return $price;
	}

	/**
	 * Handles cache busting for variations.
	 *
	 * @acces public
	 *
	 * @param  string $hash current hash for caching.
	 *
	 * @return string
	 */
	public function regenerate_hash( $hash ) {

		$group_id = BM_Conditionals::get_validated_customer_group();
		$hash[]   = $group_id;

		return $hash;
	}

	/**
	 * Recalculate the item price if options from WooCommerce TM Extra Product Options attached.
	 *
	 * @acces public
	 *
	 * @param object $cart current cart object.
	 *
	 * @return object
	 */
	public function recalculate_prices( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return $cart;
		}

		self::$is_minicart = true;

		$bm_filter_price = apply_filters( 'bm_filter_price', true );

		if ( $bm_filter_price ) {
			remove_filter( 'woocommerce_product_get_price', array( $this, 'set_price' ), self::$set_price_prio, 2 );
			remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_price' ), 10, 2 );
		}

		foreach ( $cart as $hash => $item ) {
			// Find the correct product by ID to use.
			$product = wc_get_product( $item['product_id'] );

			if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
				$product = wc_get_product( $item['variation_id'] );
			}

			// YITH WooCommerce Product Bundles.
			if ( isset( $item['bundled_by'] ) && ! empty( $item['bundled_by'] ) ) {
				continue;
			}

			if ( isset( $item[ 'yith_parent' ] ) && ! empty( $item[ 'yith_parent' ] ) ) {
				return $cart;
			}

			// YITH Gift Cards.
			if ( isset( $item['ywgc_product_id'] ) && ! empty( $item['ywgc_product_id'] ) ) {
				return $cart;
			}

			$group_id = BM_Conditionals::get_validated_customer_group();

			if ( ! $group_id ) {
				return $cart;
			}

			$price = $item['data']->get_regular_price();
			$qty   = $item['quantity'];

			$price = apply_filters( 'bm_filter_get_regular_price', $price, $item );

			if ( floatval( $item['data']->get_sale_price() ) > 0 ) {
				$price = floatval( $item['data']->get_sale_price() );
			}

			// Check sale price end date.
			$sale_price_start_date = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
			$sale_price_end_date   = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

			if ( ! empty( $sale_price_end_date ) ) {
				$timezone = new DateTimeZone( wp_timezone_string() );
				$today    = wp_date("d-m-Y", strtotime( 'today' ), $timezone );
				$start    = wp_date("d-m-Y", intval( $sale_price_start_date ), $timezone );
				$expire   = wp_date("d-m-Y", intval( $sale_price_end_date ), $timezone );

				$today_date  = new DateTime( $today );
				$start_date  = new DateTime( $start );
				$expire_date = new DateTime( $expire );

				if ( ( $expire_date < $today_date ) || ( $start_date > $today_date ) ) {
					$price = $item['data']->get_regular_price();
				}
			}

			$cheapest_price = self::get_price( $price, $product, $group_id, $qty );

			/**
			 * Advanced Coupons for WooCommerce.
			 */
			if ( class_exists( 'ACFWP' ) ) {
				$cheapest_price = apply_filters( 'bm_filter_recalculate_cart_price_advanced_coupon_product_price', $cheapest_price, $item );
			}

			/**
			 * Woocommerce Custom Product Addons
			 */
			if ( isset( $item[ 'wcpa_data' ] ) && ! empty( $item[ 'wcpa_data' ] ) ) {
				$options_price = 0;
				foreach ( $item[ 'wcpa_data' ] as $key => $data ) {
					if ( is_array( $data[ 'price' ] ) ) {
						foreach ( $data[ 'price' ] as $option_price ) {
							$options_price += $option_price;
						}
					} else {
						$options_price = floatval( $data[ 'price' ] );
					}
				}
				$cheapest_price += $options_price;
			}

			/**
			 * TM Extra Product options.
			 */
			if ( isset( $item[ 'tmhasepo' ] ) && ! empty( $item[ 'tmhasepo' ] ) ) {
				$options_price  = ( ! empty( $item[ 'tm_epo_options_prices' ] ) ? floatval( $item[ 'tm_epo_options_prices' ] ) : 0 );
				$cheapest_price = $cheapest_price + $options_price;
			}

			/**
			 * Woocommerce InfititeOptions.
			 */
			if ( isset( $item['wio_price'] ) && ! empty( $item['wio_price'] ) ) {
				$options_price  = floatval( $item['wio_price'] );
				$cheapest_price = $options_price;
			}

			/**
			 * WPC Product Bundle.
			 */
			if ( isset( $item['woosb_parent_id'] ) && ! empty( $item['woosb_parent_id'] ) ) {
				$cheapest_price = 0;
			}

			/**
			 * Product Configurator for WooCommerce.
			 */
			if ( isset( $item[ 'configurator_data' ] ) && is_array( $item[ 'configurator_data' ] ) ) {
				$cheapest_price = 0;
				foreach( $item[ 'configurator_data' ] as $layer ) {
					$cheapest_price += floatval( $layer->get_choice( 'extra_price' ) );
				}
			}

			/**
			 * Free Gifts for Woocommerce
			 */
			if ( isset( $item[ 'fgf_gift_product' ] ) ) {
				$cheapest_price = apply_filters( 'fgf_gift_product_price', $item[ 'fgf_gift_product' ][ 'price' ], $hash, $item ) ;
			}

			$item['data']->set_price( $cheapest_price );
		}

		return $cart;
	}

	/**
	 * Reenable price filter after mini cart.
	 *
	 * @acces public
	 *
	 * @return void
	 */
	public function reenable_prices() {

		add_filter( 'woocommerce_product_get_price', array( $this, 'set_price' ), self::$set_price_prio, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_price' ), 10, 2 );

		self::$is_minicart = false;
	}

	/**
	 * Check if product is in sub category of given category.
	 *
	 * @acces public
	 * @static
	 *
	 * @param  int $category given category as ID.
	 * @param  int $product_id current product ID.
	 *
	 * @return bool
	 */
	public static function product_in_descendant_category( $category, $product_id ) {
		$descendants = get_term_children( $category, 'product_cat' );

		if ( $descendants && has_term( $descendants, 'product_cat', $product_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show sale price based on conditions.
	 *
	 * @acces public
	 *
	 * @param float $price current price.
	 * @param float $regular_price current regular price.
	 * @param float $sale_price current sale price.
	 *
	 * @return string
	 */
	public function show_sale_price( $price, $regular_price, $sale_price ) {

		return apply_filters( 'bm_filter_show_sale_price', $price, $regular_price, $sale_price );
	}

	/**
	 * Show or hide sale badge.
	 *
	 * @acces public
	 *
	 * @param string $span_class_onsale_esc_html_sale_woocommerce_span current html string.
	 * @param object $post post object.
	 * @param object $product product object.
	 *
	 * @return string
	 */
	public function show_sale_badge( $span_class_onsale_esc_html_sale_woocommerce_span, $post, $product ) {
		$show_sale = apply_filters( 'bm_show_sale_price', true );

		if ( $show_sale ) {
			return $span_class_onsale_esc_html_sale_woocommerce_span;
		}
	}

	/**
	 * Show / hide sale badge in Atomion.
	 *
	 * @Access PUBLIC
	 *
	 * @param string $text given discount text.
	 * @param object $post current post object.
	 * @param object $product current product object.
	 * @param string $discount_setting current discount setting.
	 * @param float  $discount given discount.
	 * @param string $sale_text sale text.
	 *
	 * @return string
	 */
	public function show_sale_price_atomion( $text, $post, $product, $discount_setting, $discount, $sale_text ) {
		$show_sale = apply_filters( 'bm_show_sale_price', true );

		if ( $show_sale && $product->is_on_sale() ) {
			return $text;
		}

		return '';
	}

	/**
	 * Set grouped price HTML.
	 *
	 * @acces public
	 *
	 * @param string $price_this_get_price_suffix price with suffix.
	 * @param object $instance given object instance.
	 * @param array  $child_prices list of prices.
	 *
	 * @return string
	 */
	public function set_grouped_price_html( $price_this_get_price_suffix, $instance, $child_prices ) {

		if ( is_admin() ) {
			$group_id = BM_Conditionals::get_validated_customer_group();
			if ( ! empty( $group_id ) ) {
				$child_prices = array();
				$children     = array_filter( array_map( 'wc_get_product', $instance->get_children() ), 'wc_products_array_filter_visible_grouped' );
				foreach ( $children as $child ) {
					$child_price = $child->get_price();
					if ( '' !== $child_price ) {
						$child_prices[] = BM_Tax::get_tax_price( $child, $child_price );
					}
				}
			}
		}

		$lowest_price  = min( $child_prices );
		$highest_price = max( $child_prices );

		if ( $lowest_price === $highest_price ) {
			return wc_price( $lowest_price );
		}

		return wc_price( $lowest_price ) . ' - ' . wc_price( $highest_price );
	}

	/**
	 * Get cheapest bulk price from given id.
	 *
	 * @acces public
	 * @static
	 *
	 * @param int $product_id given product id.
	 * @param int $group_id given group_id.
	 *
	 * @return array
	 */
	public static function get_cheapest_bulk_price( $product_id, $group_id = false ) {

		if ( false === $group_id ) {
			$group_id = BM_Conditionals::get_validated_customer_group();
		}

		// Save group and product data to reduce requests.
		$group   = get_post( $group_id );
		$product = wc_get_product( $product_id );
		$price   = $product->get_price();

		if ( empty( $group_id ) ) {
			return;
		}

		// check if it's a variation.
		if ( 'variation' === $product->get_type() ) {
			$parent_id = $product->get_parent_id();
		} else {
			$parent_id = 0;
		}

		// Generate runtime cache key.
		$runtime_cache_key = sanitize_key( 'bm_cache_cheapest_bulk_price_' . $product_id . '_' . $group_id );

		// Check if runtime cache is already set
		if ( isset( self::$run_time_cache[ $runtime_cache_key ] ) && ( false === apply_filters( 'bm_filter_disable_runtime_cache_cheapest_bulk_price', false, $product_id, $group_id ) ) ) {
			return self::$run_time_cache[ $runtime_cache_key ];
		}

		// get bulk prices from product, customer group.
		$bulk_prices = apply_filters(
			'bm_bulk_prices',
			array(
				'global'  => get_option( 'bm_global_bulk_prices' ),
				'group'   => get_post_meta( $group_id, 'bm_bulk_prices', true ),
				'product' => get_post_meta( $product_id, 'bm_' . $group->post_name . '_bulk_prices', true ),
			)
		);

		// collect bulk prices for sorting.
		$sortable_bulk_prices = array();

		// calculate bulk prices and add them to $prices.
		if ( ! empty( $bulk_prices ) ) {
			foreach ( $bulk_prices as $price_type => $price_entries ) {

				if ( is_array( $price_entries ) ) {
					foreach ( $price_entries as $price_data ) {

						// no price skip.
						if ( empty( $price_data['bulk_price'] ) ) {
							continue;
						}
						// no from skip.
						if ( empty( $price_data['bulk_price_from'] ) ) {
							continue;
						}

						// no type set default.
						if ( empty( $price_data['bulk_price_type'] ) ) {
							$type = 'fix';
						} else {
							$type = $price_data['bulk_price_type'];
						}

						// $to equals 0.
						if ( 0 === intval( $price_data['bulk_price_to'] ) ) {
							$to = INF;
						} else {
							$to = intval( $price_data['bulk_price_to'] );
						}

						// no category set.
						if ( empty( $price_data['bulk_price_category'] ) ) {
							$category = 0;
						} else {
							$category = BM_Helper::get_translated_object_ids( $price_data[ 'bulk_price_category' ], 'category' );
						}

						// check for category restriction before calculating.
						if ( $category > 0 ) {
							// if it's a variation we need to check for the parent id.
							if ( $parent_id > 0 ) {
								if ( ! has_term( $category, 'product_cat', $parent_id ) && ! self::product_in_descendant_category( $category, $parent_id ) ) {
									continue;
								}
							} else {
								if ( ! has_term( $category, 'product_cat', $product_id ) && ! self::product_in_descendant_category( $category, $product_id ) ) {
									continue;
								}
							}
						}

						$bulk_price = floatval( $price_data['bulk_price'] );
						$from       = intval( $price_data['bulk_price_from'] );

						if ( $bulk_price > 0 ) {
							switch ( $type ) {
								case 'fix':
									$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;
									break;

								case 'discount':
									$bulk_price = $price - $bulk_price;

									if ( $bulk_price > 0 ) {
										$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;
									}
									break;
								case 'discount-percent':
									$bulk_price = $price - ( $bulk_price * $price / 100 );

									if ( $bulk_price > 0 ) {
										$sortable_bulk_prices[] = $bulk_price . '|' . $from . '|' . $product_id;
									}
									break;
							}
						}
					}
				}
			}
		}

		// Display cheapest bulk when available.
		if ( ! empty( $sortable_bulk_prices[0] ) ) {
			// sort bulk prices by cheapest.
			sort( $sortable_bulk_prices, SORT_NATURAL );
			$cheapest_bulk_price = $sortable_bulk_prices[0];

			// Store into runtime cache.
			self::$run_time_cache[ $runtime_cache_key ] = $cheapest_bulk_price;

			return $cheapest_bulk_price;
		}
	}

	/**
	 * Recalculate atomion sale percentage.
	 *
	 * @acces public
	 *
	 * @param float  $discount given discount value.
	 * @param object $product given product object.
	 *
	 * @return int
	 */
	public function calculate_atomion_sale_percentage( $discount, $product ) {
		$group_id      = BM_Conditionals::get_validated_customer_group();

		$show_sale_badge = get_post_meta( $group_id, 'bm_show_sale_badge', true );

		if ( 'on' === $show_sale_badge && $product->is_type( 'variable' ) || $product->is_type( 'variation' ) && 'on' === $show_sale_badge ) {
			return $discount;
		}

		$current_price = self::get_price( $product->get_regular_price(), $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );

		if ( floatval( $product->get_sale_price() ) > 0 && floatval( $product->get_sale_price() ) <= $current_price ) {
			return $discount;
		}

		if ( $current_price <= 0 ) {
			return $discount;
		}

		$discount = ( $current_price / floatval( $product->get_price() ) ) * 100;
		$discount = ceil( 100 - $discount );

		if ( $discount <= 0 ) {
			$price = floatval( $product->get_regular_price() );

			if ( floatval( $product->get_sale_price() ) > 0 ) {
				$price = floatval( $product->get_sale_price() );
			}

			$discount = ( $current_price / $price ) * 100;
			$discount = ceil( 100 - $discount );
		}

		return $discount;
	}

	/**
	 * Returns the price html for variable product.
	 *
	 * @acces public
	 *
	 * @param string $price price html
	 * @param object $product product object
	 *
	 * @return string
	 */
	public function set_variable_price_html( $price, $product ) {

		$group_id = BM_Conditionals::get_validated_customer_group();

		// Return given price html if customer group not set or price is empty.
		if ( empty( $group_id ) ) {
			return $price;
		}

		// Generate runtime cache key.
		$runtime_cache_key = sanitize_key( 'bm_cache_variable_price_html_' . $product->get_id() . '_' . $group_id );

		// Check if runtime cache is already set
		if ( isset( self::$run_time_cache[ $runtime_cache_key ] ) && ( false === apply_filters( 'bm_filter_disable_runtime_cache_variable_price_html', false, $product->get_id(), $group_id ) ) ) {
			return self::$run_time_cache[ $runtime_cache_key ];
		}

		$prices = $product->get_variation_prices();

		if ( empty( $prices[ 'price' ] ) ) {
			$price = apply_filters( 'woocommerce_variable_empty_price_html', '', $product );
		} else {
			$min_price     = BM_Tax::get_tax_price( $product, current( $prices[ 'price' ] ) );
			$max_price     = BM_Tax::get_tax_price( $product, end( $prices[ 'price' ] ) );
			$min_reg_price = BM_Tax::get_tax_price( $product, current( $prices[ 'regular_price' ] ) );
			$max_reg_price = BM_Tax::get_tax_price( $product, end( $prices[ 'regular_price' ] ) );

			if ( $min_price !== $max_price ) {
				$price = wc_format_price_range( $min_price, $max_price );
			} elseif ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
				$price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
			} else {
				$price = wc_price( $min_price );
			}

			$price = $price . $product->get_price_suffix();
		}

		// Apply filter before storing into runtime cache.
		$price = apply_filters( 'bm_filter_woocommerce_variable_price_html', $price, $product );

		// Store into runtime cache.
		self::$run_time_cache[ $runtime_cache_key ] = $price;

		return $price;
	}

	/**
	 * Modify price html based on sale badge option and prices.
	 *
	 * @acces public
	 *
	 * @param string $price price html
	 * @param object $product product object
	 *
	 * @return string
	 */
	public function set_price_html( $price, $product ) {

		$group_id   = BM_Conditionals::get_validated_customer_group();
		$sale_badge = ( 'on' === get_post_meta( $group_id, 'bm_show_sale_badge', true ) ) ? 'on' : 'off';

		// Do not modify HTML if not customer group is set or do we have a variable product.
		if ( empty( $group_id ) || $product->is_type( 'variable' ) || $product->is_type( 'bundle' ) || $product->is_type( 'grouped' ) || '' === $product->get_price() ) {
			return $price;
		}

		// Generate runtime cache key.
		$runtime_cache_key = sanitize_key( 'bm_cache_price_html_' . $product->get_id() . '_' . $sale_badge . '_' . $group_id );

		if ( true === self::$is_minicart ) {
			$runtime_cache_key .= '_minicart';
		}

		// Check if runtime cache is already set
		if ( isset( self::$run_time_cache[ $runtime_cache_key ] ) && ( false === apply_filters( 'bm_filter_disable_runtime_cache_get_price_html', false, $product->get_id(), $group_id ) ) ) {
			return self::$run_time_cache[ $runtime_cache_key ];
		}

		$actual_price  = floatval( $product->get_price() );
		$regular_price = floatval( $product->get_regular_price() );
		$sale_price    = $product->get_sale_price();

		// Get Tax prices for customer group,
		if ( $actual_price > 0 ) {
			$actual_price = BM_Tax::get_tax_price( $product, $actual_price );
		}
		if ( $regular_price > 0 ) {
			$regular_price = BM_Tax::get_tax_price( $product, $regular_price );
		}
		if ( $sale_price > 0 ) {
			$sale_price = BM_Tax::get_tax_price( $product, $sale_price );
		}

		// Check sale price end date.
		$sale_price_start_date = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$sale_price_end_date   = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

		if ( ! empty( $sale_price_end_date ) ) {
			$timezone = new DateTimeZone( wp_timezone_string() );
			$today    = wp_date("d-m-Y", strtotime( 'today' ), $timezone );
			$start    = wp_date("d-m-Y", intval( $sale_price_start_date ), $timezone );
			$expire   = wp_date("d-m-Y", intval( $sale_price_end_date ), $timezone );

			$today_date  = new DateTime( $today );
			$start_date  = new DateTime( $start );
			$expire_date = new DateTime( $expire );

			if ( ( $expire_date < $today_date ) || ( $start_date > $today_date ) ) {
				$sale_price = '';
			}
		}

		// We need to modify html if the 'sale badge' option is deactivated only.
		if ( 'off' === $sale_badge ) {

			if ( '0' == $sale_price ) {
				if ( $actual_price < $regular_price ) {
					$price = wc_format_sale_price( wc_price( $actual_price ), wc_price( $sale_price ) );
				} else {
					$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
				}
			} else {
				if ( ( $sale_price < $regular_price ) && ( $sale_price > 0 ) ) {
					if ( ( $actual_price < $sale_price ) && ( $actual_price > 0 ) ) {
						$price = wc_format_sale_price( wc_price( $sale_price ), wc_price( $actual_price ) );
					} else {
						$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
					}
				} else if ( ( $actual_price < $regular_price ) && ( $actual_price > 0 ) ) {
					$price = wc_price( $actual_price );
				} else {
					$price = wc_price( $regular_price );
				}
			}

		} else {

			if ( '0' == $sale_price ) {
				if ( $actual_price < $regular_price ) {
					$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
				} else {
					$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
				}
			} else {
				if ( ( $sale_price < $regular_price ) && ( $sale_price > 0 ) ) {
					if ( ( $actual_price < $sale_price ) && ( $actual_price > 0 ) ) {
						$price = wc_format_sale_price( wc_price( $sale_price ), wc_price( $actual_price ) );
					} else {
						$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
					}
				} else if ( ( $actual_price < $regular_price ) && ( $actual_price > 0 ) ) {
					$price = wc_format_sale_price( wc_price( $regular_price ), wc_price( $actual_price ) );
				} else {
					$price = wc_price( $regular_price );
				}
			}

		}

		$suffix = $product->get_price_suffix();
		if ( ! empty( $suffix ) ) {
			$price .= ' <small class="woocommerce-price-suffix">' . $suffix . '</small>';
		}

		// Store into runtime cache.
		self::$run_time_cache[ $runtime_cache_key ] = $price;

		return apply_filters( 'bm_filter_woocommerce_get_price_html', $price, $product );
	}

	/**
	 * Returns if we should show the sale badge for B2B Market discounts.
	 *
	 * @Hook woocommerce_product_is_on_sale
	 *
	 * @acces public
	 *
	 * @param bool   $on_sale
	 * @param object $product
	 *
	 * @return bool
	 */
	public function product_is_on_sale( $on_sale, $product ) {

		$group_id        = BM_Conditionals::get_validated_customer_group();
		$show_sale_badge = ( 'on' === get_post_meta( $group_id, 'bm_show_sale_badge', true ) ) ? 'on' : 'off';

		// Generate runtime cache key.
		$runtime_cache_key = sanitize_key( 'bm_cache_product_on_sale_' . $product->get_id() . '_' . $group_id . '_' . $show_sale_badge );

		// Check if runtime cache is already set
		if ( isset( self::$run_time_cache[ $runtime_cache_key ] ) && ( false === apply_filters( 'bm_filter_disable_runtime_cache_product_on_sale_', false, $product->get_id(), $group_id, $show_sale_badge ) ) ) {
			return self::$run_time_cache[ $runtime_cache_key ];
		}

		// Do we have a variable or variation product?
		if ( $product->is_type( 'variable' ) ) {
			$on_sale = $this->check_variable_product_is_on_sale( $on_sale, $product, $group_id, $show_sale_badge );
		} else {
			$on_sale = $this->check_product_is_on_sale( $on_sale, $product, $group_id, $show_sale_badge );
		}

		// Store into runtime cache.
		self::$run_time_cache[ $runtime_cache_key ] = $on_sale;

		return $on_sale;
	}

	/**
	 * Check if variable product is on sale.
	 *
	 * @acces public
	 *
	 * @param bool   $on_sale
	 * @param object $product
	 * @param mixed  $group_id
	 * @param string $show_sale_badge
	 *
	 * @return bool
	 */
	public function check_variable_product_is_on_sale( $on_sale, $product, $group_id, $show_sale_badge ) {

		if ( empty( $product ) || ! is_object( $product ) || ! $product->is_type( 'variable' ) ) {
			return $on_sale;
		}

		if ( true === apply_filters( 'bm_filter_enable_check_sale_in_variable_products', true ) ) {
			$variation_prices = $product->get_variation_prices( true );
			if ( is_array( $variation_prices ) ) {
				$variation_get_prices     = ! empty( $variation_prices[ 'price' ] )         ? $variation_prices[ 'price' ]         : array();
				$variation_regular_prices = ! empty( $variation_prices[ 'regular_price' ] ) ? $variation_prices[ 'regular_price' ] : array();
				$variation_sale_prices    = ! empty( $variation_prices[ 'sale_price' ] )    ? $variation_prices[ 'sale_price' ]    : array();
				foreach ( $variation_get_prices as $variation_id => $variation_price ) {
					$variation_price         = floatval( $variation_price );
					$variation_regular_price = floatval( $variation_regular_prices[ $variation_id ] );
					$variation_sale_price    = floatval( $variation_sale_prices[ $variation_id ] );
					// Check if sale price is lower than regular.
					if ( ( $variation_sale_price < $variation_regular_price ) && ( $variation_sale_price > 0 ) ) {
						$on_sale = true;
						break;
					}
					// Check if B2B price is lower than regular.
					if ( ( $variation_price < $variation_regular_price ) && ( $variation_price > 0 ) && ( 'on' === $show_sale_badge ) ) {
						$on_sale = true;
						break;
					}
					if ( ! empty( $group_id ) && ( 'on' === $show_sale_badge ) ) {
						$variation      = wc_get_product( $variation_id );
						$cheapest_price = self::get_price( $variation_price, $variation, $group_id, apply_filters( 'bm_default_qty', 1 ) );
						$cheapest_price = BM_Tax::get_tax_price( $variation, $cheapest_price );
						if ( ( $cheapest_price < $variation_price ) && ( $cheapest_price > 0 ) ) {
							$on_sale = true;
							break;
						}
					}
				}
			}
		}

		return apply_filters( 'bm_filter_variable_product_is_on_sale', $on_sale, $product, $group_id, $show_sale_badge );
	}

	/**
	 * Check if simple product is on sale.
	 *
	 * @acces public
	 *
	 * @param bool   $on_sale woocommerce given bool value
	 * @param object $product product object
	 * @param mixed  $group_id group id if is set or empty value
	 * @param string $show_sale_badge sale badge option from group setting
	 *
	 * @return bool
	 */
	public function check_product_is_on_sale( $on_sale, $product, $group_id, $show_sale_badge ) {

		if ( empty( $product ) || ! is_object( $product ) ) {
			return $on_sale;
		}

		$actual_price  = floatval( $product->get_price() );
		$regular_price = floatval( $product->get_regular_price() );
		$sale_price    = floatval( $product->get_sale_price() );

		// Check sale price end date.
		$sale_price_start_date = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$sale_price_end_date   = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

		if ( ! empty( $sale_price_start_date ) && ! empty( $sale_price_end_date ) ) {
			$timezone = new DateTimeZone( wp_timezone_string() );
			$today    = wp_date("d-m-Y", strtotime( 'today' ), $timezone );
			$start    = wp_date("d-m-Y", intval( $sale_price_start_date ), $timezone );
			$expire   = wp_date("d-m-Y", intval( $sale_price_end_date ), $timezone );

			$today_date  = new DateTime( $today );
			$start_date  = new DateTime( $start );
			$expire_date = new DateTime( $expire );

			if ( ( $expire_date < $today_date ) || ( $start_date > $today_date ) ) {
				$sale_price = 0;
			}
		}

		if ( empty( $group_id ) || 'off' === $show_sale_badge ) {
			$on_sale = ( $sale_price < $regular_price && $sale_price > 0 );
		} else {
			if ( ( $sale_price < $regular_price && $sale_price > 0 ) || ( $actual_price < $regular_price && $actual_price > 0 ) ) {
				$on_sale = true;
			}
		}

		return apply_filters( 'bm_filter_product_is_on_sale', $on_sale, $product, $group_id, $show_sale_badge );
	}

	/**
	 * Calculating unit price with Woocommerce German Market plugin.
	 *
	 * @acces public
	 * @static
	 *
	 * @param float             $price product price
	 * @param object|WC_Product $product product object
	 * @param int               $qty product quantity
	 *
	 * @return array
	 */
	public static function calculate_unit_price( $price, $product, $qty = 1 ) {

		$response = array(
			'price_per_unit'            => 0,
			'unit'                      => '',
			'mult'                      => 0,
			'complete_product_quantity' => 0,
		);

		if ( ! is_object( $product ) ) {
			return $response;
		}

		// Woocommerce German Market PPU Compatibility.
		if ( class_exists( 'WGM_Price_Per_Unit' ) ) {

			$ppu            = array();
			$price_per_unit = 0;

			$hide_ppu_setting = 'off';
			$use_wc_weight    = get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' );
			if ( 'on' === $use_wc_weight ) {
				if ( $product->is_type( 'variation' ) ) {
					$hide_ppu_setting = get_post_meta( $product->get_parent_id(), '_price_per_unit_product_weights_completely_off', true );
				} else {
					$hide_ppu_setting = get_post_meta( $product->get_id(), '_price_per_unit_product_weights_completely_off', true );
				}
			}

			if ( 'off' == $hide_ppu_setting ) {

				self::$product_price = $price;

				add_filter( 'german_market_get_price_per_unit_data_complete_product_price', array( self::class, 'calculate_unit_price_set_wgm_price' ), 10, 2 );

				if ( $product->is_type( 'variation' ) ) {

					$variation_id = $product->get_id();
					$var_ppu_set  = get_post_meta( $variation_id, '_v_used_setting_ppu', true );

					if ( 1 == $var_ppu_set ) {
						if ( function_exists( 'wcppufv_get_price_per_unit_data' ) ) {
							$ppu = wcppufv_get_price_per_unit_data( $variation_id, $product );
						}
					} else {
						$parent_product = wc_get_product( $product->get_parent_id() );
						$ppu            = WGM_Price_Per_Unit::get_price_per_unit_data( $parent_product );
					}

				} else {
					$ppu = WGM_Price_Per_Unit::get_price_per_unit_data( $product );
				}

				remove_filter( 'german_market_get_price_per_unit_data_complete_product_price', array( self::class, 'calculate_unit_price_set_wgm_price' ), 10 );

				if ( isset( $ppu[ 'price_per_unit' ] ) ) {
					$price_per_unit = $ppu[ 'price_per_unit' ];
				}

				if ( isset( $ppu[ 'unit' ] ) ) {
					$response[ 'unit' ] = $ppu[ 'unit' ];
				}

				if ( isset( $ppu[ 'mult' ] ) ) {
					$response[ 'mult' ] = $ppu[ 'mult' ];
				}

				if ( isset( $ppu[ 'complete_product_quantity' ] ) ) {
					$response[ 'complete_product_quantity' ] = $ppu[ 'complete_product_quantity' ];
				}

				if ( $price_per_unit > 0 ) {
					$response[ 'ppu' ]            = wc_price( $price_per_unit );
					$response[ 'price_per_unit' ] = $price_per_unit;
				}
			} else {

				$response = array();

			}
		}

		return apply_filters( 'bm_filter_calculate_unit_price', $response, $price, $product );
	}

	/**
	 * Set complete product price for WGM Unit Price calculation.
	 *
	 * @param float             $price
	 * @param object|WC_Product $product
	 *
	 * @return float|int
	 */
	public static function calculate_unit_price_set_wgm_price( $price, $product ) {

		return self::$product_price;
	}

}
