<?php
/**
 * Class which handles all price calculations
 */
class BM_Show_Discounts {

	/**
	 * @var array
	 */
	private static array $runtime_cache = array();

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of BM_Price.
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
	 * BM_Show_Discounts constructor.
	 */
	public function __construct() {

		$this->load_frontend_assets();
	}

	/**
	 * Loading frontend javascript.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function load_frontend_assets() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

		wp_enqueue_script( 'bm-frontend', B2B_PLUGIN_URL . '/assets/public/bm-frontend.' . $min . 'js', array( 'jquery' ), BM::$version, true );
		wp_localize_script( 'bm-frontend', 'bm_frontend_js', array(
			'german_market_price_variable_products' => ( class_exists( 'Woocommerce_German_Market' ) ? get_option( 'german_market_price_presentation_variable_products', 'gm_default' ) : 'gm_default' ),
		) );
	}

	/**
	 * Show the discount on single item price.
	 *
	 * @param string $price the current price.
	 * @param object $item current cart object.
	 * @param string $cart_item_key current cart item key.
	 * @return string
	 */
	public function show_discount_on_item_price( $price, $item, $cart_item_key ) {

		if ( ! is_cart() ) {
			return $price;
		}

		$discount_available = get_option( 'bm_cart_item_price_discount' );

		if ( 'on' !== $discount_available ) {
			return $price;
		}
		// Find the correct product by ID to use.
		$product = wc_get_product( $item['product_id'] );

		if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
			$product = wc_get_product( $item['variation_id'] );
		}

		// Check for bundle.
		if ( isset( $item['bundled_by'] ) && ! empty( $item['bundled_by'] ) ) {
			return $price;
		}

		if ( $product->get_price() > 0 ) {
			// setup prices to compare.
			$current_price = BM_Price::get_price( $product->get_price(), $product, false, $item['quantity'] );
			$regular_price = floatval( $product->get_regular_price() );
			$sale_price    = floatval( $product->get_sale_price() );

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
					$sale_price = $product->get_regular_price();
				}
			}

			// if sale compare otherwise compare with regular.
			if ( $sale_price > 0 ) {
				$discount          = round( ( $current_price / $sale_price ) * 100 );
				$absolute_discount = $sale_price - $current_price;
				$old_price         = $sale_price;
			} else {
				$discount          = round( ( $current_price / $regular_price ) * 100 );
				$absolute_discount = $regular_price - $current_price;
				$old_price         = $regular_price;
			}

			$discount          = round( 100 - $discount );
			$old_price         = BM_Tax::get_tax_price( $product, $old_price );
			$absolute_discount = BM_Tax::get_tax_price( $product, $absolute_discount );

			if ( $discount > 0 ) {
				$discount_text = str_replace( array( '[percent]', '[old-price]', '[absolute-discount]' ), array( $discount, wc_price( $old_price ), wc_price( $absolute_discount ) ), get_option( 'bm_cart_item_discount_text' ) );
				$price         = $price . apply_filters( 'bm_percentual_discount_string', '<br><small class="bm-percentual-discount">' . $discount_text . '</small>' );
			}
		}
		return $price;
	}
	/**
	 * Show discount on subtotal.
	 *
	 * @param string $subtotal subtotal as string.
	 * @param array  $item cart item array with data.
	 * @param string $cart_item_key unique hash string for item.
	 * @return string
	 */
	public function show_discount_on_subtotal( $subtotal, $item, $cart_item_key ) {

		if ( ! is_cart() ) {
			return $subtotal;
		}

		$discount_available = get_option( 'bm_cart_item_subtotal_discount' );

		if ( 'on' !== $discount_available ) {
			return $subtotal;
		}
		// Find the correct product by ID to use.
		if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
			$product = wc_get_product( $item['variation_id'] );
		} else {
			$product = wc_get_product( $item['product_id'] );
		}

		// Check for bundle.
		if ( isset( $item['bundled_by'] ) && ! empty( $item['bundled_by'] ) ) {
			return $subtotal;
		}

		if ( $product->get_price() > 0 ) {
			// setup prices to compare.
			$current_price = BM_Price::get_price( $product->get_price(), $product, false, $item['quantity'] ) * $item['quantity'];
			$regular_price = floatval( $product->get_regular_price() ) * $item['quantity'];
			$sale_price    = floatval( $product->get_sale_price() ) * $item['quantity'];

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
					$sale_price = $product->get_regular_price() * $item['quantity'];
				}
			}

			// if sale compare otherwise compare with regular.
			if ( $sale_price > 0 ) {
				$discount          = round( ( $current_price / $sale_price ) * 100 );
				$absolute_discount = $sale_price - $current_price;
				$old_price         = $sale_price;
			} else {
				$discount          = round( ( $current_price / $regular_price ) * 100 );
				$absolute_discount = $regular_price - $current_price;
				$old_price         = $regular_price;
			}

			$discount          = round( 100 - $discount );
			$old_price         = BM_Tax::get_tax_price( $product, $old_price );
			$absolute_discount = BM_Tax::get_tax_price( $product, $absolute_discount );

			if ( $discount > 0 ) {
				$discount_text = str_replace( array( '[percent]', '[old-price]', '[absolute-discount]' ), array( $discount, wc_price( $old_price ), wc_price( $absolute_discount ) ), get_option( 'bm_cart_item_discount_text' ) );
				$subtotal      = $subtotal . apply_filters( 'bm_percentual_discount_string', '<br><small class="bm-percentual-discount">' . $discount_text . '</small>' );
			}
		}
		return $subtotal;
	}

	/**
	 * Handles the RRP output.
	 *
	 * @Hook woocommerce_get_price_html
	 *
	 * @param string     $price current price string.
	 * @param WC_Product $product current product object.
	 *
	 * @return string
	 */
	public function show_rrp_and_price( $price, $product ) {

		return $this->show_rrp_html( $price, $product, true );
	}

	/**
	 * Handles the RRP output.
	 *
	 * @Hook woocommerce_single_product_summary
	 *
	 * @return void
	 */
	public function show_rrp() {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( ! is_object( $product ) ) {
			return;
		}

		echo $this->show_rrp_html( '', $product, false );
	}

	/**
	 * Handles the RRP output.
	 *
	 * @param string $price current price string.
	 * @param object $product current product object.
	 * @param bool   $combine show rrp together with price html
	 *
	 * @return string
	 */
	public function show_rrp_html( $price, $product, $combine = true ) {

		$group_id   = BM_Conditionals::get_validated_customer_group();
		$use_rrp    = get_option( 'bm_use_rrp', 'off' );
		$rrp_label  = trim( get_option( 'bm_rrp_label', __( 'RRP', 'b2b-market' ) ) );
		$rrp_markup = apply_filters( 'bm_rrp_markup', '<small class="b2b-rrp">' . ( '' !== $rrp_label ? $rrp_label . ': ' : '' ) . '[rrp]</small><br>' );

		// check if RRP activated.
		if ( 'on' !== $use_rrp ) {
			return $price;
		}

		// Check for bundle.
		if ( property_exists( $product, 'bundled_item_price' ) && $product->bundled_item_price > 0 ) {
			return $price;
		}

		// check if product has custom RRP.
		$rrp_price = floatval( get_post_meta( $product->get_id(), 'bm_rrp', true ) );

		if ( $rrp_price <= 0 ) {
			return $price;
		}

		// Check RRP prices in variable products.
		if ( 'variable' === $product->get_type() ) {
				if ( 'on' === apply_filters( 'bm_filter_show_rrp_html_variable_product', get_option( 'bm_show_rrp_variable_products', 'off' ), $product, $group_id ) ) {
					$variations    = $product->get_available_variations();
					$variation_ids = wp_list_pluck( $variations, 'variation_id' );
					if ( is_array( $variation_ids ) ) {
						$rrp_price = null;
						foreach ( $variation_ids as $variation_id ) {
							if ( ! array_key_exists( $variation_id, self::$runtime_cache ) ) {
								$rrp_variation = floatval( get_post_meta( $variation_id, 'bm_rrp', true ) );
								self::$runtime_cache[ $variation_id ] = $rrp_variation;
							} else {
								$rrp_variation = self::$runtime_cache[ $variation_id ];
							}
							if ( ( null !== $rrp_price ) && ( $rrp_price !== $rrp_variation ) ) {
								// RRP is different, abort.
								return $price;
							}
							if ( null === $rrp_price ) {
								$rrp_price = $rrp_variation;
							}
						}
					} else {
						return $price;
					}
				} else {
					return $price;
				}
			}

		if ( 'grouped' === $product->get_type() ) {
			return $price;
		}

		// check RRP option for net or gross price.
		$rrp_price_format = get_option( 'bm_rrp_price_format', 'gross' );
		$tax_input        = get_option( 'woocommerce_prices_include_tax' );

		if ( 'group-based' === $rrp_price_format ) {
			$rrp_price = BM_Tax::get_tax_price( $product, $rrp_price );
		}

		if ( 'no' === $tax_input && 'gross' === $rrp_price_format ) {
			$args      = array( 'price' => $rrp_price );
			$rrp_price = wc_get_price_including_tax( $product, $args );
		}

		// exclude specific groups from RRP output.
		$use_all_customers  = get_option( 'bm_show_rrp_all_customers', 'off' );
		$rrp_exclude_groups = apply_filters( 'bm_rrp_exluded_groups', array( get_option( 'bm_customer_group' ), get_option( 'bm_guest_group' ) ) );

		if ( 'on' === $use_all_customers ) {
			$rrp_exclude_groups = apply_filters( 'bm_rrp_exluded_groups', array() );
		}

		if ( in_array( $group_id, $rrp_exclude_groups, true ) ) {
			return $price;
		}

		// replace [rrp] with real price.
		$rrp_markup = str_replace( '[rrp]', wc_price( $rrp_price ), $rrp_markup );

		// return the result string.
		return $rrp_markup . ( true === $combine ? $price : '' );
	}

	/**
	 * Show if we have same prices on a variation.
	 *
	 * @param string     $price_html
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function maybe_manipulate_variable_price_html( $price_html, $product ) {

		if ( $product->is_type( 'variation' ) || $product->is_type( 'variable' ) ) {
			if ( strpos( $price_html, '<del' ) !== false ) {
				// Find prices in string.
				preg_match_all( '/<bdi>(\d+,\d+)\&/i', $price_html, $prices );
				if ( is_array( $prices ) && count( $prices[ 1 ] ) == 2 ) {
					// We found 2 prices. Let's see if they are not different.
					if ( $prices[ 1 ][ 0 ] == $prices[ 1 ][ 1 ] ) {
						$price_html = apply_filters( 'bm_bulk_discount_variation_price_html', preg_replace( array(
							'/(<del.*<\/del>\s)/i',
							'/<ins>(.+)<\/ins>/i'
						), array(
							'',
							'$1',
						), $price_html ), $price_html, $product );
					}
				}
			}
		}

		return $price_html;
	}

	/**
	 * Handles the bluk discount message output.
	 *
	 * @param string $price_html current price string.
	 * @param object $product current product object.
	 * @return string
	 */
	public function show_bulk_discount( $price_html, $product ) {
		if ( is_admin() || $product->is_type( 'variable' ) || $product->is_type( 'variation' ) || $product->is_type( 'bundle' ) || property_exists( $product, 'bundled_item_price' ) && $product->bundled_item_price > 0 ) {
			return $price_html;
		}
		return '<small>' . apply_filters( 'bm_bulk_discount_string', '', $product->get_id() ) . '</small>' . $price_html;
	}

	/**
	 * Handles bulk output message after title.
	 *
	 * @return string
	 */
	public function show_bulk_discount_after_title() {
		global $product;

		if ( is_admin() || $product->is_type( 'variable' ) || $product->is_type( 'variation' ) || $product->is_type( 'bundle' ) || property_exists( $product, 'bundled_item_price' ) && $product->bundled_item_price > 0 ) {
			return '';
		}
		echo '<small>' . apply_filters( 'bm_bulk_discount_string', '', $product->get_id() ) . '</small>';
	}

	/**
	 * Show discount table for bulk prices.
	 *
	 * @return void
	 */
	public function show_discount_table() {
		if ( ! is_product() ) {
			return;
		}

		$show_table = get_option( 'bm_bulk_price_table_on_product' );
		$product    = wc_get_product( get_the_id() );

		if ( 'on' === $show_table && 'variable' !== $product->get_type() && ! $product->is_type( 'bundle' ) ) {
			echo do_shortcode( '[bulk-price-table]' );
		}
	}

	/**
	 * Show discount table per variation.
	 *
	 * @param  array $variations given variations.
	 * @return array
	 */
	public function show_discount_table_variation( $variations ) {
		$show_table = get_option( 'bm_bulk_price_table_on_product' );
		$product    = wc_get_product( $variations['variation_id'] );
		$price      = $product->get_regular_price();

		if ( floatval( $product->get_sale_price() ) > 0 ) {
			$price = floatval( $product->get_sale_price() );
		}

		if ( 'on' === $show_table ) {
			$bulk_table                = do_shortcode( '[bulk-price-table product-id="' . $variations['variation_id'] . '" product-price="' . $price . '"]' );
			$variations['bulk_prices'] = $bulk_table;

			if ( ! empty( $bulk_table ) ) {
				$cheapest_bulk_price                = BM_price::get_cheapest_bulk_price( $variations['variation_id'], false );
				$variations['bulk_discount_string'] = '<small>' . apply_filters( 'bm_bulk_discount_string', '', $variations['variation_id'] ) . '</small>';
			}
		}
		return $variations;
	}

	/**
	 * Show totals on product page.
	 *
	 * @return void
	 */
	public function show_discount_totals() {
		if ( ! is_product() ) {
			return;
		}

		$product     = wc_get_product( get_the_ID() );
		$qty         = 1;
		$group_id    = BM_Conditionals::get_validated_customer_group();

		if ( $product->is_type( 'bundle' ) || $product->is_type( 'composite' ) || $product->is_type( 'grouped' ) ) {
			return;
		}

		// if customer_group missing return false.
		if ( empty( $group_id ) ) {
			return;
		}

		$product_price = $product->get_regular_price();

		if ( floatval( $product->get_sale_price() ) > 0 ) {
			$product_price = floatval( $product->get_sale_price() );
		}

		// get pricing data.
		$cheapest_price = BM_Price::get_price( $product_price, $product, $group_id, $qty );
		$cheapest_price = BM_Tax::get_tax_price( $product, $cheapest_price );

		if ( 'on' === get_option( 'bm_bulk_price_table_show_totals', 'off' ) && false === apply_filters( 'bm_filter_disable_bulk_price_table_show_totals', false ) ) {
			echo '<div class="bm-price-totals" style="margin-bottom: 1.5em; font-weight: bold; ' . ( $product->is_type( 'variable' ) ? 'display: none' : '' ) . '">';
			echo '	<span class="totals-label">' . apply_filters( 'bm_filter_price_totals_label', __( 'Totals', 'b2b-market' ) ) . ':</span> ';
			echo '  <span class="totals-price">' . wc_price( $cheapest_price ) . '</span>';
			echo '</div>';
		}
	}

	/**
	 * Overwrites WooCommerce variation.php template for bulk price support.
	 *
	 * @param string $template given template.
	 * @param string $template_name template name.
	 * @param string $template_path given path.
	 * @return string
	 */
	public function load_variation_template( $template, $template_name, $template_path ) {
		global $woocommerce;

		$show_table = get_option( 'bm_bulk_price_table_on_product' );
		$_template  = $template;

		if ( 'on' === $show_table ) {
			if ( ! $template_path ) {
				$template_path = $woocommerce->template_url;
			}

			$template = locate_template(
				array(
					$template_path . $template_name,
					$template_name
				)
			);

			if ( ! $template && file_exists( B2B_TEMPLATE_PATH . $template_name ) ) {
				$template = B2B_TEMPLATE_PATH . $template_name;
			}

			if ( ! $template ) {
				$template = $_template;
			}
		}

		return $template;
	}
}
