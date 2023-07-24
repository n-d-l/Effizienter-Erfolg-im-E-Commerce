<?php

/**
 * Class which handles the frontend pricing display
 */
class BM_Shortcode {

	/**
	 * BM_Shortcode constructor.
	 */
	public function __construct() {

		add_shortcode( 'bulk-price-table',   array( $this, 'bulk_price_table' ) );
		add_shortcode( 'b2b-group-display',  array( $this, 'conditional_customer_group_output' ) );
		add_shortcode( 'b2b-customer-group', array( $this, 'show_current_customer_group' ) );
		add_shortcode( 'b2b-group-price',    array( $this, 'show_b2b_product_price' ) );

		add_filter( 'atomion_wc_checkout_description_show_excerpt', array( $this, 'remove_shortcode_from_cart_excerpt' ) );
	}

	/**
	 * Filter excerpt and remove shortcode if exists.
	 *
	 * @param string $excerpt given excerpt.
	 *
	 * @return string
	 */
	public function remove_shortcode_from_cart_excerpt( $excerpt ) {

		return str_replace( '[bulk-price-table]', '', $excerpt );
	}

	/**
	 * Render bulk price table shortcode.
	 *
	 * @param [type] $atts list of arguments.
	 *
	 * @return void
	 */
	public function bulk_price_table( $atts ) {

		if ( ! is_product() && ! isset( $atts[ 'product-id' ] ) ) {
			return;
		}

		$id = get_the_id();

		if ( isset( $atts[ 'product-id' ] ) ) {
			$id = $atts[ 'product-id' ];
		}

		$product        = wc_get_product( $id );
		$price          = floatval( $product->get_regular_price() );
		$parent_product = null;

		if ( isset( $atts[ 'product-price' ] ) ) {
			$price = $atts[ 'product-price' ];
		}

		if ( floatval( $product->get_sale_price() ) > 0 ) {
			$price = floatval( $product->get_sale_price() );
		}

		$group_id = BM_Conditionals::get_validated_customer_group();

		if ( empty( $group_id ) ) {
			return;
		}

		$group = get_post( $group_id );

		$bulk_prices = apply_filters(
			'bm_bulk_prices',
			array(
				'global'  => get_option( 'bm_global_bulk_prices' ),
				'group'   => get_post_meta( $group_id, 'bm_bulk_prices', true ),
				'product' => get_post_meta( $product->get_id(), 'bm_' . $group->post_name . '_bulk_prices', true ),
			)
		);

		$listable_bulk_prices = array();

		// calculate bulk prices and add them to $prices.
		if ( ! empty( $bulk_prices ) ) {
			foreach ( $bulk_prices as $price_type => $price_entries ) {

				if ( is_array( $price_entries ) ) {
					foreach ( $price_entries as $price_data ) {

						// no price skip.
						if ( empty( $price_data[ 'bulk_price' ] ) ) {
							continue;
						}
						// no from skip.
						if ( empty( $price_data[ 'bulk_price_from' ] ) ) {
							continue;
						}

						// no type set default.
						if ( empty( $price_data[ 'bulk_price_type' ] ) ) {
							$type = 'fix';
						} else {
							$type = $price_data[ 'bulk_price_type' ];
						}

						// $to equals 0.
						if ( 0 === intval( $price_data[ 'bulk_price_to' ] ) ) {
							$to = INF;
						} else {
							$to = intval( $price_data[ 'bulk_price_to' ] );
						}

						// no category set.
						if ( empty( $price_data[ 'bulk_price_category' ] ) ) {
							$category = 0;
						} else {
							$category = BM_Helper::get_translated_object_ids( $price_data[ 'bulk_price_category' ], 'category' );
						}

						// check for category restriction before calculating.
						if ( $category > 0 ) {
							// if it's a variation we need to check for the parent id.
							if ( $product->get_parent_id() > 0 ) {
								if ( ! has_term( $category, 'product_cat', $product->get_parent_id() ) && ! BM_Price::product_in_descendant_category( $category, $product->get_parent_id() ) ) {
									continue;
								}
							} else {
								if ( ! has_term( $category, 'product_cat', $product->get_id() ) && ! BM_Price::product_in_descendant_category( $category, $product->get_id() ) ) {
									continue;
								}
							}
						}

						$bulk_price = floatval( $price_data[ 'bulk_price' ] );
						$from       = intval( $price_data[ 'bulk_price_from' ] );

						if ( $bulk_price > 0 ) {
							switch ( $type ) {
								case 'fix':
									$listable_bulk_prices[] = array(
										'price' => $bulk_price,
										'to'    => $to,
										'from'  => $from,
									);
									break;

								case 'discount':
									$bulk_price = $price - $bulk_price;

									if ( $bulk_price > 0 ) {
										$listable_bulk_prices[] = array(
											'price' => $bulk_price,
											'to'    => $to,
											'from'  => $from,
										);
									}
									break;

								case 'discount-percent':
									$bulk_price = $price - ( $bulk_price * $price / 100 );

									if ( $bulk_price > 0 ) {
										$listable_bulk_prices[] = array(
											'price' => $bulk_price,
											'to'    => $to,
											'from'  => $from,
										);
									}
									break;
							}
						}
					}
				}
			}
		}

		if ( empty( $listable_bulk_prices ) ) {
			return;
		}

		// Build the table.
		$columns     = array();
		$table_class = apply_filters( 'b2b_bulk_price_table_class', 'bm-bulk-table' );

		if ( isset( $atts[ 'product-title' ] ) ) {
			$columns[ 'product_title' ] = __( 'Product', 'b2b-market' );
		}
		$columns[ 'bulk_price' ]    = __( 'Bulk Price', 'b2b-market' );
		$columns[ 'quantity_from' ] = __( 'Quantity (from)', 'b2b-market' );
		$columns[ 'quantity_to' ]   = __( 'Quantity (to)', 'b2b-market' );

		if ( class_exists( 'WGM_Price_Per_Unit' ) ) {
			if ( $product->is_type( 'variation' ) ) {
				$parent_product = wc_get_product( $product->get_parent_id() );
			}
			if ( null !== $parent_product ) {
				$ppu_data = BM_Price::calculate_unit_price( $parent_product->get_price(), $parent_product, apply_filters( 'bm_default_qty', 1 ) );
			} else {
				$ppu_data = BM_Price::calculate_unit_price( $product->get_price(), $product, apply_filters( 'bm_default_qty', 1 ) );
			}
			if ( isset( $ppu_data[ 'price_per_unit' ] ) &&  ! empty( $ppu_data[ 'price_per_unit' ] ) ) {
				$columns[ 'price_per_unit' ] = __( 'Price Per Unit', 'woocommerce-german-market' );
			}
		}

		$columns     = apply_filters( 'bm_bulk_price_table_columns', $columns );
		$current_row = 0;

		$shortcode = '<table class="' . $table_class . '">';
		$shortcode .= '<thead>';
		$shortcode .= '<tr>';

		foreach ( $columns as $key => $value ) {
			$shortcode .= '<td>' . $value . '</td>';
		}
		$shortcode .= '</tr>';
		$shortcode .= '</thead>';
		$shortcode .= '<tbody>';

		foreach ( $listable_bulk_prices as $data ) {

			if ( 0 === $current_row && true === apply_filters( 'bm_filter_bulk_price_table_dynamic_generate_first_row', false ) ) {

				if ( 1 < $data[ 'from' ] ) {

					$from           = 1;
					$to             = $data[ 'from' ] - 1;
					$temp_price     = BM_Price::get_price( $price, $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );
					$tax_temp_price = BM_Tax::get_tax_price( $product, $temp_price );

					$shortcode .= '<tr>';
					$shortcode .= '  <td>' . wc_price( $tax_temp_price ) . '</td>';
					$shortcode .= '  <td>' . $from . '</td>';
					$shortcode .= '  <td>' . $to . '</td>';

					// WGM PPU compatibility.
					if ( class_exists( 'WGM_Price_Per_Unit' ) ) {

						$ppu_data = BM_Price::calculate_unit_price( $price, $product, $data[ 'from' ] );

						if ( isset( $ppu_data[ 'price_per_unit' ] ) && $ppu_data[ 'price_per_unit' ] > 0 ) {

							$output_ppu_string = trim( wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ) . ' / ' . str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ) . ' ' . $ppu_data[ 'unit' ] );
							$output_ppu        = apply_filters( 'bm_filter_bulk_price_table_ppu', $output_ppu_string, wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ), str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ), $ppu_data[ 'price_per_unit' ], $ppu_data[ 'mult' ], $ppu_data[ 'unit' ] );

							$shortcode  .= '<td>' . $output_ppu . '</td>';
						}

					}

					$shortcode .= '</tr>';
				}

				$current_row ++;
			}

			$price = BM_Tax::get_tax_price( $product, $data[ 'price' ] );
			$to    = $data[ 'to' ];

			$from_totals = '';
			$to_totals   = '';

			if ( INF === $data[ 'to' ] ) {
				$to = '∞';
			}

			$shortcode .= '<tr>';
			$shortcode .= '	<td>' . wc_price( $price ) . '</td>';
			$shortcode .= '	<td>' . $data[ 'from' ] . $from_totals . '</td>';
			$shortcode .= '	<td>' . $to . $to_totals . '</td>';

			// WGM PPU compatibility.
			if ( class_exists( 'WGM_Price_Per_Unit' ) ) {

				$ppu_data = BM_Price::calculate_unit_price( $price, $product, $data[ 'from' ] );

				if ( isset( $ppu_data[ 'price_per_unit' ] ) && $ppu_data[ 'price_per_unit' ] > 0 ) {

					$output_ppu_string = trim( wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ) . ' / ' . str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ) . ' ' . $ppu_data[ 'unit' ] );
					$output_ppu        = apply_filters( 'bm_filter_bulk_price_table_ppu', $output_ppu_string, wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ), str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ), $ppu_data[ 'price_per_unit' ], $ppu_data[ 'mult' ], $ppu_data[ 'unit' ] );

					$shortcode  .= '<td>' . $output_ppu . '</td>';
				}

			}

			$shortcode .= '</tr>';
		}

		$shortcode .= '</tbody>';
		$shortcode .= '</table>';

		return apply_filters( 'bm_filter_shortcode_bulk_table_wrap', $shortcode, $product, $parent_product );
	}

	/**
	 * Shortcode for group based content display
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return void
	 */
	public function conditional_customer_group_output( $atts, $content = null ) {
		$current_group = get_post( BM_Conditionals::get_validated_customer_group() );
		$group         = $atts[ 'group' ];
		$shortcode     = '';

		do_action( 'bm_action_before_conditional_customer_group_output' );

		if ( strpos( $group, ',' ) ) {
			$groups = explode( ',', $group );

			if ( in_array( $current_group->post_name, $groups ) ) {
				$shortcode = apply_filters( 'the_content', $content );
			}
		} else {
			if ( isset( $group ) ) {
				if ( $group === $current_group->post_name ) {
					$shortcode = apply_filters( 'the_content', $content );
				}
			}
		}

		do_action( 'bm_action_after_conditional_customer_group_output' );

		return $shortcode;
	}

	/**
	 * Show B2B group price for a given product id.
	 *
	 * @param array       $atts shortcode params
	 * @param string|null $content html output
	 * @param string      $tag shortcode tag
	 *
	 * @return string|void
	 */
	public function show_b2b_product_price( $atts = array(), $content = null, $tag = '' ) {

		$group_id        = BM_Conditionals::get_validated_customer_group();
		$show_sale_badge = 'on' === get_post_meta( $group_id, 'bm_show_sale_badge', true ) ? 'on' : 'off';

		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$b2b_atts = shortcode_atts(
			array(
				'product_id' => 0,
				'show_sale'  => 'yes',
			), $atts, $tag
		);

		if ( $b2b_atts[ 'product_id' ] == 0 ) {
			return;
		}

		$product = wc_get_product( $b2b_atts[ 'product_id' ] );

		if ( ! is_object( $product ) || ( ! $product->is_type( 'simple' ) && ! $product->is_type( 'variable' ) && ! $product->is_type( 'variation' ) ) ) {
			return;
		}

		$shortcode = '<div class="b2b-product-group-price">';

		if ( $product->is_type( 'simple' ) || $product->is_type( 'variation' ) ) {

			$actual_price  = floatval( $product->get_price() );
			$regular_price = floatval( $product->get_regular_price() );
			$sale_price    = floatval( $product->get_sale_price() );

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

			if ( 'yes' === $b2b_atts[ 'show_sale' ] ) {
				// We need to modify html if the 'sale badge' option is deactivated only.
				if ( 'off' === $show_sale_badge ) {

					if ( ( $sale_price < $regular_price ) && ( $sale_price > 0 ) ) {
						if ( ( $actual_price < $sale_price ) && ( $actual_price > 0 ) ) {
							$shortcode .= wc_format_sale_price( wc_price( $sale_price ), wc_price( $actual_price ) );
						} else {
							$shortcode .= wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
						}
					} else if ( ( $actual_price < $regular_price ) && ( $actual_price > 0 ) ) {
						$shortcode .= wc_price( $actual_price );
					} else {
						$shortcode .= wc_price( $regular_price );
					}

				} else {

					if ( ( $sale_price < $regular_price ) && ( $sale_price > 0 ) ) {
						if ( ( $actual_price < $sale_price ) && ( $actual_price > 0 ) ) {
							$shortcode .= wc_format_sale_price( wc_price( $sale_price ), wc_price( $actual_price ) );
						} else {
							$shortcode .= wc_format_sale_price( wc_price( $regular_price ), wc_price( $sale_price ) );
						}
					} else if ( ( $actual_price < $regular_price ) && ( $actual_price > 0 ) ) {
						$shortcode .= wc_format_sale_price( wc_price( $regular_price ), wc_price( $actual_price ) );
					} else {
						$shortcode .= wc_price( $regular_price );
					}

				}

			} else {
				$shortcode .= wc_price( $actual_price );
			}

		} else
			if ( $product->is_type( 'variable' ) ) {

				$prices = $product->get_variation_prices();

				if ( empty( $prices[ 'price' ] ) ) {
					$shortcode .= apply_filters( 'woocommerce_variable_empty_price_html', '', $product );
				} else {
					$min_price     = BM_Tax::get_tax_price( $product, current( $prices[ 'price' ] ) );
					$max_price     = BM_Tax::get_tax_price( $product, end( $prices[ 'price' ] ) );
					$min_reg_price = BM_Tax::get_tax_price( $product, current( $prices[ 'regular_price' ] ) );
					$max_reg_price = BM_Tax::get_tax_price( $product, end( $prices[ 'regular_price' ] ) );

					if ( 'yes' === $b2b_atts[ 'show_sale' ] ) {
						if ( $min_price !== $max_price ) {
							$shortcode .= wc_format_price_range( $min_price, $max_price );
						} else if ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
							$shortcode .= wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
						} else {
							$shortcode .= wc_price( $min_price );
						}
					} else {
						if ( $min_price !== $max_price ) {
							$shortcode .= wc_format_price_range( $min_price, $max_price );
						} else {
							$shortcode .= wc_price( $min_price );
						}
					}
				}

			}

		$shortcode .= '</div>';

		return $shortcode;
	}

	/**
	 * Show current customer group
	 *
	 * @param array $atts list of arguments.
	 *
	 * @return string
	 */
	public function show_current_customer_group( $atts ) {
		$group_id = BM_Conditionals::get_validated_customer_group();

		if ( is_null( $group_id ) || empty( $group_id ) ) {
			return '';
		}

		$customer_group = get_post( $group_id );

		ob_start();
		?>
		<span class="bm-current-group"><?php echo esc_html( $customer_group->post_title ); ?></span>
		<?php
		return ob_get_clean();
	}
}

new BM_Shortcode();
