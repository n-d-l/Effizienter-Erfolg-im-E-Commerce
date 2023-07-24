<?php

class IE_Importer {

	/**
	 * @var array|mixed|object
	 */
	private $data;

	/**
	 * IE_Importer constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_trigger_import', array( $this, 'import' ) );
	}

	/**
	 * Runs the importer
	 *
	 * @return void
	 */
	public function import() {

		check_ajax_referer( 'start_export', 'security' );

		$import_data = stripslashes( $_POST[ 'import_raw_data' ] );
		$this->data  = json_decode( $import_data, true );

		function decode_items_walker( &$item, $key ) {
			$item = html_entity_decode( $item );
		}

		if ( is_array( $this->data ) ) {
			array_walk_recursive($this->data, 'decode_items_walker' );
		}

		$import_groups   = $_POST[ 'import_groups' ] ?? array();
		$import_settings = ( 'on' == $_POST[ 'import_settings' ] ? 'on' : 'off' );

		/* get data */
		$groups  = $this->get_groups( $this->data, $import_groups );
		$options = $this->get_options( $this->data, $import_settings );

		/* import data */
		$this->update_groups( $groups );
		$this->update_options( $options );

		$response = array(
			'status'  => 'success',
			'message' => __( 'The selected data were successfully imported.', 'b2b-market' ),
		);

		echo json_encode( $response );
		die();
	}

	/**
	 * Get groups from data and build array
	 *
	 * @access private
	 *
	 * @param array $data
	 * @param array $selected_groups
	 *
	 * @return array
	 */
	private function get_groups( $data, $selected_groups = array() ) {

		$groups = array();
		if ( isset( $data ) && is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( 'options' != $key && in_array( $key, $selected_groups ) ) {
					$groups[ $key ] = $value;
				}
			}
		}

		if ( get_option( 'import_b2b_example' ) == 'on' ) {
			$groups['b2b'] = $this->get_b2b_sample_group();
		}

		return $groups;
	}

	/**
	 * Get current options
	 *
	 * @access private
	 *
	 * @param array  $data
	 * @param string $import_settings
	 *
	 * @return array
	 */
	private function get_options( $data, $import_settings = 'off' ) {

		$options = array();

		if ( isset( $data ) && is_array( $data ) && 'on' === $import_settings ) {
			foreach ( $data as $key => $value ) {
				if ( 'options' == $key ) {
					$options = $value;
				}
			}

			return $options;
		}
	}

	/**
	 * Update groups meta
	 *
	 * @access private
	 *
	 * @param array $groups
	 *
	 * @return void
	 */
	private function update_groups( $groups ) {

		if ( isset( $groups ) && is_array( $groups ) ) {

			foreach ( $groups as $group ) {

				$args = array(
					'post_type'      => 'customer_groups',
					'posts_per_page' => 1,
					'post_name__in'  => array( $group['slug'] ),
				);

				$existing_group = get_posts( $args );

				if ( empty( $existing_group[0] ) ) {
					$args = array(
						'post_title'   => $group['title'],
						'post_name'    => $group['slug'],
						'post_type'    => 'customer_groups',
						'post_content' => '',
						'post_status'  => 'publish',
					);

					$group_id = wp_insert_post( $args );

					if ( isset( $group['bm_tax_type'] ) ) {
						update_post_meta( $group_id, 'bm_tax_type', $group['bm_tax_type'] );
					}
					if ( isset( $group['bm_vat_type'] ) ) {
						update_post_meta( $group_id, 'bm_vat_type', $group['bm_vat_type'] );
					}
					if ( isset( $group['bm_show_sale_badge'] ) ) {
						update_post_meta( $group_id, 'bm_show_sale_badge', $group['bm_show_sale_badge'] );
					}
					if ( isset( $group['bm_conditional_products'] ) ) {
						update_post_meta( $group_id, 'bm_conditional_products', $group['bm_conditional_products'] );
					}
					if ( isset( $group['bm_conditional_categories'] ) ) {
						update_post_meta( $group_id, 'bm_conditional_categories', $group['bm_conditional_categories'] );
					}
					if ( isset( $group['bm_conditional_all_products'] ) ) {
						update_post_meta( $group_id, 'bm_conditional_all_products', $group['bm_conditional_all_products'] );
					}
					if ( isset( $group['bm_group_prices'] ) ) {
						update_post_meta( $group_id, 'bm_group_prices', $group['bm_group_prices'] );
					}
					if ( isset( $group['bm_bulk_prices'] ) ) {
						update_post_meta( $group_id, 'bm_bulk_prices', $group['bm_bulk_prices'] );
					}
					if ( isset( $group['bm_bulk_price_table_show_totals'] ) ) {
						update_post_meta( $group_id, 'bm_bulk_price_table_show_totals', $group['bm_bulk_price_table_show_totals'] );
					}
					if ( isset( $group['bm_bulk_price_table_on_product'] ) ) {
						update_post_meta( $group_id, 'bm_bulk_price_table_on_product', $group['bm_bulk_price_table_on_product'] );
					}
					if ( isset( $group['bm_discount'] ) ) {
						update_post_meta( $group_id, 'bm_discount', $group['bm_discount'] );
					}
					if ( isset( $group['bm_discount_type'] ) ) {
						update_post_meta( $group_id, 'bm_discount_type', $group['bm_discount_type'] );
					}
					if ( isset( $group['bm_discount_name'] ) ) {
						update_post_meta( $group_id, 'bm_discount_name', $group['bm_discount_name'] );
					}
					if ( isset( $group['bm_discount_products'] ) ) {
						update_post_meta( $group_id, 'bm_discount_products', $group['bm_discount_products'] );
					}
					if ( isset( $group['bm_discount_categories'] ) ) {
						update_post_meta( $group_id, 'bm_discount_categories', $group['bm_discount_categories'] );
					}
					if ( isset( $group['bm_discount_all_products'] ) ) {
						update_post_meta( $group_id, 'bm_discount_all_products', $group['bm_discount_all_products'] );
					}
					if ( isset( $group['bm_goods_discount'] ) ) {
						update_post_meta( $group_id, 'bm_goods_discount', $group['bm_goods_discount'] );
					}
					if ( isset( $group['bm_goods_discount_type'] ) ) {
						update_post_meta( $group_id, 'bm_goods_discount_type', $group['bm_goods_discount_type'] );
					}
					if ( isset( $group['bm_goods_discount_categories'] ) ) {
						update_post_meta( $group_id, 'bm_goods_discount_categories', $group['bm_goods_discount_categories'] );
					}
					if ( isset( $group['bm_goods_discount_type'] ) ) {
						update_post_meta( $group_id, 'bm_goods_discount_type', $group['bm_goods_discount_type'] );
					}
					if ( isset( $group['bm_goods_discount_categories'] ) ) {
						update_post_meta( $group_id, 'bm_goods_discount_categories', $group['bm_goods_discount_categories'] );
					}
					if ( isset( $group['bm_goods_product_count'] ) ) {
						update_post_meta( $group_id, 'bm_goods_product_count', $group['bm_goods_product_count'] );
					}
					if ( isset( $group['bm_cart_discounts'] ) ) {
						update_post_meta( $group_id, 'bm_cart_discounts', $group['bm_cart_discounts'] );
					}
					if ( isset( $group['bm_min_order_amount'] ) ) {
						update_post_meta( $group_id, 'bm_min_order_amount', $group['bm_min_order_amount'] );
					}
					if ( isset( $group['bm_min_order_amount_message'] ) ) {
						update_post_meta( $group_id, 'bm_min_order_amount_message', $group['bm_min_order_amount_message'] );
					}
					if ( isset( $group['bm_shipping_rated_disabled_' . $group[ 'slug' ] ] ) ) {
						update_post_meta( $group_id, 'bm_shipping_rated_disabled_' . $group[ 'slug'], $group[ 'bm_shipping_rated_disabled_' . $group[ 'slug' ] ] );
					}
				} else {
					if ( isset( $group['bm_tax_type'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_tax_type', $group['bm_tax_type'] );
					}
					if ( isset( $group['bm_vat_type'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_vat_type', $group['bm_vat_type'] );
					}
					if ( isset( $group['bm_show_sale_badge' ] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_show_sale_badge', $group['bm_show_sale_badge'] );
					}
					if ( isset( $group['bm_conditional_products'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_conditional_products', $group['bm_conditional_products'] );
					}
					if ( isset( $group['bm_conditional_categories'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_conditional_categories', $group['bm_conditional_categories'] );
					}
					if ( isset( $group['bm_conditional_all_products'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_conditional_all_products', $group['bm_conditional_all_products'] );
					}
					if ( isset( $group['bm_group_prices'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_group_prices', $group['bm_group_prices'] );
					}
					if ( isset( $group['bm_bulk_prices'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_bulk_prices', $group['bm_bulk_prices'] );
					}
					if ( isset( $group['bm_bulk_price_table_show_totals'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_bulk_price_table_show_totals', $group['bm_bulk_price_table_show_totals'] );
					}
					if ( isset( $group['bm_bulk_price_table_on_product'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_bulk_price_table_on_product', $group['bm_bulk_price_table_on_product'] );
					}
					if ( isset( $group['bm_discount'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount', $group['bm_discount'] );
					}
					if ( isset( $group['bm_discount_type'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount_type', $group['bm_discount_type'] );
					}
					if ( isset( $group['bm_discount_name'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount_name', $group['bm_discount_name'] );
					}
					if ( isset( $group['bm_discount_products'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount_products', $group['bm_discount_products'] );
					}
					if ( isset( $group['bm_discount_categories'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount_categories', $group['bm_discount_categories'] );
					}
					if ( isset( $group['bm_discount_all_products'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_discount_all_products', $group['bm_discount_all_products'] );
					}
					if ( isset( $group['bm_goods_discount'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_discount', $group['bm_goods_discount'] );
					}
					if ( isset( $group['bm_goods_discount_type'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_discount_type', $group['bm_goods_discount_type'] );
					}
					if ( isset( $group['bm_goods_discount_categories'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_discount_categories', $group['bm_goods_discount_categories'] );
					}
					if ( isset( $group['bm_goods_discount_type'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_discount_type', $group['bm_goods_discount_type'] );
					}
					if ( isset( $group['bm_goods_discount_categories'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_discount_categories', $group['bm_goods_discount_categories'] );
					}
					if ( isset( $group['bm_goods_product_count'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_goods_product_count', $group['bm_goods_product_count'] );
					}
					if ( isset( $group['bm_cart_discounts'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_cart_discounts', $group['bm_cart_discounts'] );
					}
					if ( isset( $group['bm_min_order_amount'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_min_order_amount', $group['bm_min_order_amount'] );
					}
					if ( isset( $group['bm_min_order_amount_message'] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_min_order_amount_message', $group['bm_min_order_amount_message'] );
					}
					if ( isset( $group['bm_shipping_rated_disabled_' . $group[ 'slug' ] ] ) ) {
						update_post_meta( $existing_group[0]->ID, 'bm_shipping_rated_disabled_' . $group[ 'slug'], $group[ 'bm_shipping_rated_disabled_' . $group[ 'slug' ] ] );
					}
				}
				/* create user role if not exists */
				$role = new BM_User();

				if ( ! empty( $group_id ) ) {
					$role->add_customer_group( $group_id );
				}
			}
		}
	}

	/**
	 * Update options
	 *
	 * @access private
	 *
	 * @param array $options
	 *
	 * @return void
	 */
	private function update_options( $options ) {

		if ( isset( $options ) && is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				if ( false !== $value ) {
					update_option( $key, $value );
				}
			}
		}
	}
	/**
	 * Get b2b group
	 *
	 * @return void
	 */
	private function get_b2b_sample_group() {

		$b2b = array(
			'title'                       => 'B2B',
			'slug'                        => 'b2b',
			'bm_price'                    => '10.50',
			'bm_price_type'               => 'Fixed Price',
			'bm_tax_type'                 => 'on',
			'bm_vat_type'                 => 'on',
			'bm_all_products'             => 'on',
			'bm_conditional_all_products' => 'off',
			'bm_discount_all_products'    => 'off',
			'bm_bulk_prices'              => array(
				array(
					'bulk_price' => '5',
					'bulk_price_from' => '3',
					'bulk_price_to' => '5',
					'bulk_price_type' => 'fix',
				),
				array(
					'bulk_price' => '2.5',
					'bulk_price_from' => '10',
					'bulk_price_to' => '20',
					'bulk_price_type' => 'fix',
				),
			),
		);

		return $b2b;
	}
}

new IE_Importer();
