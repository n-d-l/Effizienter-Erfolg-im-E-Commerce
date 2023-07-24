<?php

class IE_Exporter {

	/**
	 * @var array
	 */
	private $groups;


	/**
	 * IE_Exporter constructor.
	 */
	public function __construct() {
		$groups       = new BM_User();
		$this->groups = $groups->get_all_customer_groups();

		add_action( 'wp_ajax_trigger_export', array( $this, 'trigger_export' ) );
		add_action( 'wp_head',                array( $this, 'get_export_options' ) );
	}

	/**
	 * @return array
	 */
	public function get_export_options() {

		$export_groups = array();

		if ( isset( $this->groups ) && ! empty( $this->groups ) ) {
			foreach ( $this->groups as $group ) {
				if ( isset( $group ) && ! empty( $group ) ) {
					foreach ( $group as $key => $value ) {
						if ( get_option( 'export_' . $key ) == 'on' ) {
							array_push( $export_groups, $value );
						}
					}
				}
			}
		}

		return $export_groups;
	}

	/**
	 * @param $export_groups
	 *
	 * @return array
	 */
	public function get_export_data( $export_groups ) {

		$export_data = array();

		if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
			foreach ( $export_groups as $group ) {

				$group = get_post( $group );

				/* customer groups */
				$export_data[ $group->post_name ]['title']                        = $group->post_title;
				$export_data[ $group->post_name ]['slug']                         = $group->post_name;
				$export_data[ $group->post_name ]['bm_conditional_products']      = get_post_meta( $group->ID, 'bm_conditional_products', true );
				$export_data[ $group->post_name ]['bm_conditional_categories']    = get_post_meta( $group->ID, 'bm_conditional_categories', true );
				$export_data[ $group->post_name ]['bm_conditional_all_products']  = get_post_meta( $group->ID, 'bm_conditional_all_products', true );
				$export_data[ $group->post_name ]['bm_group_prices']              = get_post_meta( $group->ID, 'bm_group_prices', true );
				$export_data[ $group->post_name ]['bm_bulk_prices']               = get_post_meta( $group->ID, 'bm_bulk_prices', true );
				$export_data[ $group->post_name ]['bm_discount']                  = get_post_meta( $group->ID, 'bm_discount', true );
				$export_data[ $group->post_name ]['bm_discount_type']             = get_post_meta( $group->ID, 'bm_discount_type', true );
				$export_data[ $group->post_name ]['bm_discount_name']             = get_post_meta( $group->ID, 'bm_discount_name', true );
				$export_data[ $group->post_name ]['bm_discount_products']         = get_post_meta( $group->ID, 'bm_discount_products', true );
				$export_data[ $group->post_name ]['bm_discount_categories']       = get_post_meta( $group->ID, 'bm_discount_categories', true );
				$export_data[ $group->post_name ]['bm_discount_all_products']     = get_post_meta( $group->ID, 'bm_discount_all_products', true );
				$export_data[ $group->post_name ]['bm_goods_discount']            = get_post_meta( $group->ID, 'bm_goods_discount', true );
				$export_data[ $group->post_name ]['bm_goods_discount_type']       = get_post_meta( $group->ID, 'bm_goods_discount_type', true );
				$export_data[ $group->post_name ]['bm_goods_discount_categories'] = get_post_meta( $group->ID, 'bm_goods_discount_categories', true );
				$export_data[ $group->post_name ]['bm_goods_product_count']       = get_post_meta( $group->ID, 'bm_goods_product_count', true );
				$export_data[ $group->post_name ]['bm_cart_discounts']            = get_post_meta( $group->ID, 'bm_cart_discounts', true );
				$export_data[ $group->post_name ]['bm_tax_type']                  = get_post_meta( $group->ID, 'bm_tax_type', true );
				$export_data[ $group->post_name ]['bm_vat_type']                  = get_post_meta( $group->ID, 'bm_vat_type', true );
				$export_data[ $group->post_name ]['bm_show_sale_badge']           = get_post_meta( $group->ID, 'bm_show_sale_badge', true );
				$export_data[ $group->post_name ]['bm_min_order_amount']          = get_post_meta( $group->ID, 'bm_min_order_amount', true );
				$export_data[ $group->post_name ]['bm_min_order_amount_message']  = get_post_meta( $group->ID, 'bm_min_order_amount_message', true );
			}
		}

		/* addon options */
		if ( 'on' === get_option( 'export_plugin_settings' ) ) {

			/* options */
			$export_data['options']['bm_global_group_prices']                      = get_option( 'bm_global_group_prices' );
			$export_data['options']['bm_global_bulk_prices']                       = get_option( 'bm_global_bulk_prices' );
			$export_data['options']['deactivate_whitelist_hooks']                  = get_option( 'deactivate_whitelist_hooks' );
			$export_data['options']['bm_global_discount_message']                  = get_option( 'bm_global_discount_message' );
			$export_data['options']['bm_global_discount_message_background_color'] = get_option( 'bm_global_discount_message_background_color' );
			$export_data['options']['bm_global_discount_message_font_color']       = get_option( 'bm_global_discount_message_font_color' );
			$export_data['options']['bm_activate_no_cache']                        = get_option( 'bm_activate_no_cache' );
			$export_data['options']['bm_use_rrp']                                  = get_option( 'bm_use_rrp' );
			$export_data['options']['bm_show_rrp_all_customers']                   = get_option( 'bm_show_rrp_all_customers' );
			$export_data['options']['bm_show_rrp_variable_products']               = get_option( 'bm_show_rrp_variable_products' );
			$export_data['options']['bm_rrp_label']                                = get_option( 'bm_rrp_label' );
			$export_data['options']['bm_rrp_price_format']                         = get_option( 'bm_rrp_price_format' );
			$export_data['options']['bm_bulk_price_on_shop']                       = get_option( 'bm_bulk_price_on_shop' );
			$export_data['options']['bm_bulk_price_on_product']                    = get_option( 'bm_bulk_price_on_product' );
			$export_data['options']['bm_bulk_price_below_price']                   = get_option( 'bm_bulk_price_below_price' );
			$export_data['options']['bm_cart_item_price_discount']                 = get_option( 'bm_cart_item_price_discount' );
			$export_data['options']['bm_cart_item_subtotal_discount']              = get_option( 'bm_cart_item_subtotal_discount' );
			$export_data['options']['bm_cart_item_discount_text']                  = get_option( 'bm_cart_item_discount_text' );
			$export_data['options']['bm_bulk_price_discount_message']              = get_option( 'bm_bulk_price_discount_message' );
			$export_data['options']['bm_bulk_price_table_show_totals']             = get_option( 'bm_bulk_price_table_show_totals' );
			$export_data['options']['bm_bulk_price_table_on_product']              = get_option( 'bm_bulk_price_table_on_product' );

			if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
				foreach ( $export_groups as $group ) {
					$group = get_post( $group );
					$export_data[ 'options' ][ 'bm_hide_price_' . $group->post_name ] = get_option( 'bm_hide_price_' . $group->post_name );
				}
			}

			if ( get_option( 'bm_addon_shipping_and_payment' ) !== 'off' ) {

				$available_gateways = WC()->payment_gateways->payment_gateways();
				$shipping           = WC_Shipping::instance();
				$shipping->get_shipping_methods();
				$shipping_methods = $shipping->get_shipping_methods();

				if ( isset( $available_gateways ) && ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
							foreach ( $export_groups as $group ) {
								$group = get_post( $group );
								$export_data['options'][ 'bm_payment_method_enable_' . $gateway->id . '_' . $group->post_name ] = get_option( 'bm_payment_method_enable_' . $gateway->id . '_' . $group->post_name );
							}
						}
					}
				}
				if ( isset( $shipping_methods ) && ! empty( $shipping->get_shipping_methods() ) ) {
					foreach ( $shipping->get_shipping_methods() as $method ) {
						if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
							foreach ( $export_groups as $group ) {
								$group = get_post( $group );
								$export_data['options'][ 'bm_shipping_method_enable_' . $method->id . '_' . $group->post_name ] = get_option( 'bm_shipping_method_enable_' . $method->id . '_' . $group->post_name );
							}
						}
					}
				}
				if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
					foreach ( $export_groups as $group ) {
						$group = get_post( $group );
						$export_data[ 'options' ][ 'bm_shipping_rates_disabled_' . $group->post_name ] = get_option( 'bm_shipping_rates_disabled_' . $group->post_name );
					}
				}
			}

			if ( get_option( 'bm_addon_registration' ) !== 'off' ) {
				$export_data['options']['bm_double_opt_in_customer_registration'] = get_option( 'bm_double_opt_in_customer_registration' );
				$export_data['options']['b2b_market_double_opt_in_auto_delete']   = get_option( 'b2b_market_double_opt_in_auto_delete' );
				$export_data['options']['bm_gm_double_optin_active']              = get_option( 'bm_gm_double_optin_active' );
				$export_data['options']['bm_remove_label_registration']           = get_option( 'bm_remove_label_registration' );
				$export_data['options']['bm_placeholder_instead_of_label']        = get_option( 'bm_placeholder_instead_of_label' );
				if ( is_array( $export_groups ) && count( $export_groups ) > 0 ) {
					foreach ( $export_groups as $group ) {
						$group = get_post( $group );
						$export_data[ 'options' ][ 'register_' . $group->post_name ]    = get_option( 'register_' . $group->post_name );
						$export_data[ 'options' ][ 'sort_' . $group->post_name ]        = get_option( 'sort_' . $group->post_name );
						$export_data[ 'options' ][ 'custom_name_' . $group->post_name ] = get_option( 'custom_name_' . $group->post_name );
					}
				}
			}

		}

		return $export_data;
	}

	/**
	 * Triggered from ajax call.
	 */
	public function trigger_export() {

		check_ajax_referer( 'start_export', 'security' );

		$response = array(
			'status'   => 'error',
			'filename' => false,
			'raw_data' => '',
		);

		$export_all_groups = ( 'on' == $_POST[ 'export_all_groups' ] ) ? 'on' :  'off';
        $export_groups     = $_POST[ 'export_groups' ] ?? array();
        $export_settings   = ( 'on' == $_POST[ 'export_settings' ] ) ? 'on' : 'off';
        $export_to_file    = ( 'on' == $_POST[ 'export_to_file' ] ) ? 'on' : 'off';

		if ( ! empty( $export_groups ) && isset( $this->groups ) && ! empty( $this->groups ) ) {
			foreach ( $this->groups as $group ) {
				if ( isset( $group ) && ! empty( $group ) ) {
					foreach ( $group as $key => $value ) {
						if ( in_array( $value, $export_groups ) ) {
							update_option( 'export_' . $key, 'on' );
						} else {
							update_option( 'export_' . $key, 'off' );
						}
					}
				}
			}
		} else {
			foreach ( $this->groups as $group ) {
				if ( isset( $group ) && ! empty( $group ) ) {
					foreach ( $group as $key => $value ) {
						update_option( 'export_' . $key, 'off' );
					}
				}
			}
		}

		update_option( 'export_plugin_settings', $export_settings );

		$export_group_data = $this->get_export_data( $export_groups );

		// Walking through array for umlauts
		function encode_items_walker( &$item, $key ) {
			$item = htmlentities( $item );
		}
		array_walk_recursive($export_group_data, 'encode_items_walker' );

		update_option( 'export_options_raw_data', wp_json_encode( $export_group_data ) );

		if ( ! empty( $export_group_data ) ) {
			$response[ 'status' ]   = 'success';
			$response[ 'raw_data' ] = json_encode( $export_group_data );
			if ( 'on' === $export_to_file ) {
				$date_string            = current_time( 'Ymd' );
				$response[ 'filename' ] = 'b2b_market_backup_' . $date_string . '.json';
			}
		}

		echo json_encode( $response );
		die();
	}

}

new IE_Exporter();
