<?php

if ( BM_Helper::is_rest() !== true ) {
	add_action( 'init', 'init_bm_update_price' );
}

/**
 * Initialize all update price hooks
 *
 * @return void
 */
function init_bm_update_price() {
	$update_price = BM_Update_Price::get_instance();
	add_action( 'wp_enqueue_scripts', array( $update_price, 'load_assets' ) );
	add_action( 'woocommerce_before_add_to_cart_form', array( $update_price, 'add_hidden_id_field' ), 5 );
	add_action( 'wp_ajax_update_price', array( $update_price, 'update_price' ) );
	add_action( 'wp_ajax_nopriv_update_price', array( $update_price, 'update_price' ) );
}


if ( BM_Helper::is_rest() !== true ) {
	add_action( 'init', 'init_bm_show_discounts' );
}

/**
 * Initialize all calculation hooks
 *
 * @return void
 */
function init_bm_show_discounts() {
	$show_discounts = BM_Show_Discounts::get_instance();
	add_filter( 'woocommerce_cart_item_price', array( $show_discounts, 'show_discount_on_item_price' ), 10, 3 );
	add_filter( 'woocommerce_cart_item_subtotal', array( $show_discounts, 'show_discount_on_subtotal' ), 10, 3 );

	// Show RRP.
	add_filter( 'woocommerce_get_price_html', array( $show_discounts, 'show_rrp_and_price' ), 5, 2 );

	// Check discount position.
	$show_below_price = get_option( 'bm_bulk_price_below_price', 'off' );

	if ( 'on' === $show_below_price ) {
		add_action( 'woocommerce_after_shop_loop_item_title', array( $show_discounts, 'show_bulk_discount_after_title' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( $show_discounts, 'show_bulk_discount_after_title' ), 10 );
	} else {
		add_filter( 'woocommerce_get_price_html', array( $show_discounts, 'show_bulk_discount' ), 15, 2 );
	}

	add_filter( 'woocommerce_get_price_html', array( $show_discounts, 'maybe_manipulate_variable_price_html' ), 10, 2 );

	add_action( 'woocommerce_before_add_to_cart_button', array( $show_discounts, 'show_discount_table' ), 10 );
	add_action( 'woocommerce_before_add_to_cart_button', array( $show_discounts, 'show_discount_totals' ), 15 );
	add_filter( 'woocommerce_available_variation', array( $show_discounts, 'show_discount_table_variation' ) );
	add_filter( 'woocommerce_locate_template', array( $show_discounts, 'load_variation_template' ), 1, 3 );
}

/* init whitelist hooks */
if ( BM_Helper::is_rest() !== true ) {
	add_action( 'init', 'init_bm_whitelist' );
}

/**
 * Initialize all whitelist hooks
 *
 * @return void
 */
function init_bm_whitelist() {

	$whitelist = BM_Whitelist::get_instance();

	/* whitelist hooks */
	$whitelist_hooks = get_option( 'deactivate_whitelist_hooks' );

	if ( ! isset( $whitelist_hooks ) || empty( $whitelist_hooks ) || 'off' === $whitelist_hooks ) {
		add_action( 'woocommerce_product_query', array( $whitelist, 'set_whitelist' ) );
		add_action( 'template_redirect', array( $whitelist, 'redirect_based_on_whitelist' ) );
		add_filter( 'pre_get_posts', array( $whitelist, 'set_search_whitelist' ) );

		$use_whitelist_archive = apply_filters( 'bm_use_whitelist_on_archive', false );

		if ( $use_whitelist_archive ) {
			add_filter( 'get_terms', array( $whitelist, 'set_shop_category_view_whitelist' ), 10, 3 );
		}

		/* Upsell & Crosssell */
		add_filter( 'woocommerce_related_products', array( $whitelist, 'set_related_whitelist' ), 10, 3 );
		add_filter( 'woocommerce_product_get_upsell_ids', array( $whitelist, 'set_upsell_whitelist' ), 10, 2 );
		add_filter( 'woocommerce_product_get_cross_sell_ids', array( $whitelist, 'set_upsell_whitelist' ), 10, 2 );
		/* Product Widgets */
		add_filter( 'woocommerce_products_widget_query_args', array( $whitelist, 'set_widget_whitelist' ), 10, 1 );
		add_filter( 'woocommerce_top_rated_products_query_args', array( $whitelist, 'set_widget_whitelist' ), 10, 1 );
		add_filter( 'woocommerce_shortcode_products_query', array( $whitelist, 'set_widget_whitelist' ), 10, 1 );
		/* Category Widgets */
		add_filter( 'woocommerce_product_categories_widget_args', array( $whitelist, 'set_widget_category_whitelist' ) );
		/* cart and checkout */
		add_action( 'woocommerce_before_cart', array( $whitelist, 'is_cart_item_whitelist' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $whitelist, 'is_checkout_item_whitelist' ), 10, 2 );
		/* WooCommerce Blocks */
		add_filter( 'parse_query', array( $whitelist, 'set_woocommerce_blocks_whitelist' ), 20 );
	}
	add_action( 'woocommerce_checkout_process', 'BM_Conditionals::is_checkout_min_amount_passed' );
	add_action( 'woocommerce_before_cart', 'BM_Conditionals::is_cart_min_amount_passed' );

	do_action( 'bm_whitelist_init', $whitelist );
}

/* Initialize all tax hooks*/
if ( BM_Helper::is_rest() !== true ) {
	$taxes = BM_Tax::get_instance();

	if ( ( ! is_admin() ) || ( BM_Helper::is_frontend_ajax() ) ) {
		add_filter( 'pre_option_woocommerce_tax_display_shop', array( $taxes, 'filter_tax_display' ) );
		add_filter( 'pre_option_woocommerce_tax_display_cart', array( $taxes, 'filter_tax_display' ) );
		add_filter( 'pre_option_wcevc_general_tax_output_text_string', array( $taxes, 'filter_wcevc_general_tax_display' ) );
		add_filter( 'woocommerce_get_variation_prices_hash', array( $taxes, 'tax_display_add_hash_user_id' ) );
	}

	/**
	 * Remove tax from prices.
	 *
	 * @param  string $value excl|incl.
	 * @return string
	 */
	function bm_remove_tax( $value ) {
		$value = 'excl';
		return $value;
	}

	// Correct tax value in PDF invoice of German Market.
	add_action( 'wp_wc_invoice_pdf_start_template', function( $args ) {
		$order = $args[ 'order' ];

		if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			$group_id = BM_Admin_Orders::get_customer_group_by_id( $order->get_user_id() );
			$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

			if ( 'on' == $tax_type ) {
				add_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' );
				add_filter( 'pre_option_woocommerce_tax_display_cart', 'bm_remove_tax' );
			}
		}
	} );

	add_action( 'wp_wc_invoice_pdf_end_template', function() {
		if ( has_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' ) ) {
			remove_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' );
			remove_filter( 'pre_option_woocommerce_tax_display_cart', 'bm_remove_tax' );
		}
	} );

	// Prevent caching issues with tax filter.
	add_action( 'after_setup_theme', function() {
		add_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );
	});

	add_action( 'woocommerce_email_order_details', 'bm_set_tax_mails', 10, 4 );

	/**
	 * Filter tax in e-mails.
	 *
	 * @param object $order given order object.
	 * @param bool   $sent_to_admin bool send to admin.
	 * @param string $plain_text email text.
	 * @param int    $email email id.
	 * @return void
	 */
	function bm_set_tax_mails( $order, $sent_to_admin, $plain_text, $email ) {
		if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			$group_id = BM_Admin_Orders::get_customer_group_by_id( $order->get_user_id() );
			$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

			if ( 'on' == $tax_type ) {
				add_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' );
				add_filter( 'pre_option_woocommerce_tax_display_cart', 'bm_remove_tax' );
			}
		}
	}

	add_action( 'woocommerce_email_customer_details', function( $order, $sent_to_admin, $plain_text, $email ) {
		if ( has_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' ) ) {
			remove_filter( 'pre_option_woocommerce_tax_display_shop', 'bm_remove_tax' );
			remove_filter( 'pre_option_woocommerce_tax_display_cart', 'bm_remove_tax' );
		}
	}, 10, 4 );

	// EU Vat Addon fixed prices.
	add_filter( 'pre_option_wcevc_enabled_wgm', function( $value ) {
		$group_id = BM_Conditionals::get_validated_customer_group();
		$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

		if ( 'on' == $tax_type ) {
			return 'off';
		}

		return $value;
	} );
}

/* init price quantity hooks */
if ( BM_Helper::is_rest() !== true ) {
	add_action( 'init', 'init_bm_min_max_quantities' );
}

/**
 * Initialize min max quantity hooks
 *
 * @return void
 */
function init_bm_min_max_quantities() {
	if ( class_exists( 'BM_Quantities' ) ) {

		$quantities = BM_Quantities::get_instance();

		add_filter( 'woocommerce_quantity_input_args', array( $quantities, 'bm_product_quantity' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $quantities, 'bm_add_to_cart_quantity' ), 1, 5 );
		add_filter( 'woocommerce_update_cart_validation', array( $quantities, 'bm_cart_update_quantity' ), 1, 4 );
		add_filter( 'woocommerce_available_variation', array( $quantities, 'bm_variation_quantity' ) );
		add_filter( 'bm_default_qty', array( $quantities, 'set_default_qty' ) );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( $quantities, 'set_default_loop_qty' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $quantities, 'check_cart_qty' ), 10, 1 );
	}
}



/* init price display hooks */
if ( BM_Helper::is_rest() !== true ) {
	add_action( 'init', 'init_bm_hide_prices' );
}

/**
 * Initialize all price_display hooks
 *
 * @return void
 */
function init_bm_hide_prices() {
	$groups = BM_User::get_instance();

	foreach ( $groups->get_all_customer_groups() as $group ) {
		foreach ( $group as $key => $value ) {

			$hide_price = get_option( 'bm_hide_price_' . $key );

			if ( 'on' === $hide_price ) {
				$group = get_post( BM_Conditionals::get_validated_customer_group() );

				if ( is_null( $group ) ) {
					return;
				}

				if ( $key === $group->post_name ) {
					/* remove price display */
					if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
						add_filter( 'woocommerce_get_price_html', function( $price, $product ) {
							$string = '<span class="bm-hidden-product">' . apply_filters( 'bm_conditional_hide_message', get_option( 'bm_hide_price_message' ) ) . '</span>';
							return $string;
						}, 20, 2 );
						// Remove WooCommerce Structured Data for products.
						add_filter( 'woocommerce_structured_data_product_offer', '__return_empty_array' );
					}

					// Remove discount string if hide price.
					add_filter( 'bm_bulk_discount_string', function ( $string, $product_id ) {
						return '';
					}, 20, 2 );

					/* filter purchasable status */
					add_filter( 'woocommerce_is_purchasable', function() {
						return false;
					} );
					/* german market markup */
					add_filter( 'wgm_product_summary_parts_after', function( $output_parts, $product, $hook ) {
						unset( $output_parts['shipping'] );
						unset( $output_parts['tax'] );
						unset( $output_parts['ppu'] );
						return $output_parts;
					}, 50, 3 );

					add_filter( 'woocommerce_sale_flash', '__return_false' );

					// Flatsome Compatibility.
					remove_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_template_single_price', 10 );
					remove_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_template_single_excerpt', 20 );
					remove_action( 'woocommerce_single_product_lightbox_summary', 'woocommerce_template_single_add_to_cart', 30 );

					add_action( 'woocommerce_single_product_lightbox_summary', function() {
						echo '<span class="bm-hidden-product">' . apply_filters( 'bm_conditional_hide_message', get_option( 'bm_hide_price_message' ) ) . '</span>';
					}, 10 );

					// Germanized Price Output.
					add_action( 'wp_footer', function() {
						?>
						<style>
                            .legal-price-info, .wc-gzd-additional-info.delivery-time-info {
                                display: none;
                            }
						</style>
						<?php
					});
				}
			}
		}
	}
}
