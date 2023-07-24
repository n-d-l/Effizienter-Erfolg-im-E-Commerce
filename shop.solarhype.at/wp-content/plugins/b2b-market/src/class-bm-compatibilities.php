<?php
/**
 * Class to handle compatibilities with different themes and plugins.
 */
class BM_Compatibilities {

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
	 * Constructor for BM_Price.
	 */
	public function __construct() {
		$theme = wp_get_theme();

		// Prices.
		$prices = BM_Price::get_instance();

		/*
		 * Salesman - Free Shipping Notice
		 */
		add_action( 'init', function() {
			if ( class_exists( 'CSP_ShippingManager' ) && class_exists( 'MarketPress_Salesman_Free_Shipping_Notice_Application' ) ) {
				add_filter( 'marketpress_salesman_free_shipping_notice_free_shipping_methods', function( $free_shipping_methods ) {
					$group_id = BM_Conditionals::get_validated_customer_group();

					if ( empty( $group_id ) ) {
						return $free_shipping_methods;
					}

					$group_object   = get_post( $group_id );
					$slug           = $group_object->post_name;
					$specific_rates = get_option( 'bm_shipping_rates_disabled_' . $slug );
					$specific_rates = explode( ',', $specific_rates );

					if ( isset( $free_shipping_methods ) && ! empty( $free_shipping_methods ) ) {
						foreach ( $free_shipping_methods as $key => $rate ) {

							$status = get_option( 'bm_shipping_method_enable_' . $rate->id . '_' . $slug );

							if ( 'on' != $status ) {
								unset( $free_shipping_methods[ $key ] );
							}

							if ( isset( $specific_rates ) && ! empty( $specific_rates ) ) {
								foreach ( $specific_rates as $specific_rate ) {
									$rate_string = $rate->id . ':' . $rate->instance_id;
									if ( $rate_string == $specific_rate ) {
										unset( $free_shipping_methods[ $key ] );
									}
								}
							}
						}
					}

					return $free_shipping_methods;
				} );
			}
		});

		/**
		 * Woocommerce TM Extra Product Options
		 */
		add_action( 'init', function() {
			if ( class_exists( 'THEMECOMPLETE_EPO_Display' ) ) {
				if ( true === apply_filters( 'bm_filter_tm_extra_options_activate_group_prices_for_child_products', true ) ) {
					add_filter( 'wc_epo_product_price_rules', function( $price, $product ) {
						$group_id       = BM_Conditionals::get_validated_customer_group();
						$cheapest_price = BM_Price::get_price( $price[ 'price' ], $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );
						if ( $cheapest_price > 0 ) {
							$price[ 'price' ] = $cheapest_price;
						}
						return $price;
					}, 10, 2 );
					add_filter( 'bm_filter_price', '__return_false' );
				}
			}
		} );

		/**
		 * Woocommerce Product Addon
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'WC_Product_Addons_Cart' ) ) {
				add_filter( 'bm_filter_disable_runtime_cache_cheapest_price', '__return_true' );
			}
		} );

		/**
		 * Atomion Quick View product price
		 */
		add_action( 'after_setup_theme', function() {
			$current_theme = get_stylesheet();
			if ( 'wordpress-theme-atomion' == $current_theme || 'wordpress-theme-atomion-child' == $current_theme ) {
				if ( false !== get_theme_mod( 'wcpc_enable_qv_setting' ) ) {
					add_filter( 'woocommerce_product_get_price', function( $price, $product ) {
						if ( doing_action( 'wp_ajax_atomion_qv_get_product' ) || doing_action( 'wp_ajax_nopriv_atomion_qv_get_product' ) ) {
							$cheapest_price = BM_Price::get_price( $price, $product, false, apply_filters( 'bm_default_qty', 1 ) );
							if ( $cheapest_price > 0 ) {
								$price = $cheapest_price;
							}
						}
						return $price;
					}, 10, 2 );
					add_filter( 'woocommerce_product_variation_get_price', function( $price, $variation ) {
						if ( doing_action( 'wp_ajax_atomion_qv_get_product' ) || doing_action( 'wp_ajax_nopriv_atomion_qv_get_product' ) ) {
							$cheapest_price = BM_Price::get_price( $price, $variation, false, apply_filters( 'bm_default_qty', 1 ) );
							if ( $cheapest_price > 0 ) {
								$price = $cheapest_price;
							}
						}
						return $price;
					}, 10, 2 );
				}
			}
		});

		/**
		 * German Market / Base Unit Prices
		 */

		// PPU on 'Thankyou' page and email.
		add_filter( 'german_market_ppu_co_woocommerce_order_formatted_line_subtotal', function( $ppu, $item, $order ) {

			$item_quantity = $item[ 'quantity' ] ?? 1;
			$product       = $item->get_product();

			$product_price = $product->get_regular_price();
			$product_id    = $product->get_id();

			if ( floatval( $product->get_sale_price() ) > 0 ) {
				$product_price = floatval( $product->get_sale_price() );
			}

			// get pricing data.
			$cheapest_price = BM_Price::get_price( $product_price, $product, false, $item_quantity );
			$cheapest_price = BM_Tax::get_tax_price( $product, $cheapest_price );

			$ppu_data = BM_Price::calculate_unit_price( $cheapest_price, $product, $item_quantity );

			$output = apply_filters(
				'wmg_price_per_unit_loop',
				sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">' . trim( WGM_Price_Per_Unit::get_prefix( $ppu_data ) . ' ' . WGM_Price_Per_Unit::get_output_format() ) . '</span>',
					wc_price( str_replace( ',', '.', apply_filters( 'wgm_price_per_unit_get_price_per_unit_string_price_per_unit', $ppu_data[ 'price_per_unit' ], $product ) ), apply_filters( 'wgm_ppu_wc_price_args', array() ) ),
					str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ),
					$ppu_data[ 'unit' ]
				),
				wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ),
				$ppu_data[ 'mult' ],
				$ppu_data[ 'unit' ]
			);

			return $output;
		}, 10, 3 );

		// PPU in cart.
		add_filter( 'german_market_ppu_co_woocommerce_cart_item_price_ppu_string', function( $ppu_string, $cart_item, $cart_item_key ) {

			// Find the correct product by ID to use.
			$product = wc_get_product( $cart_item[ 'product_id' ] );

			if ( isset( $cart_item[ 'variation_id' ] ) && ! empty( $cart_item[ 'variation_id' ] ) ) {
				$product = wc_get_product( $cart_item[ 'variation_id' ] );
			}

			$product_price = $product->get_regular_price();

			if ( floatval( $product->get_sale_price() ) > 0 ) {
				$product_price = floatval( $product->get_sale_price() );
			}

			// get pricing data.
			$cheapest_price = BM_Price::get_price( $product_price, $product, false, $cart_item[ 'quantity' ] );
			$cheapest_price = BM_Tax::get_tax_price( $product, $cheapest_price );

			$cheapest_price = apply_filters( 'bm_filter_bundle_cheapest_price', $cheapest_price, $product, $cart_item );

			$ppu_data = BM_Price::calculate_unit_price( $cheapest_price, $product, $cart_item[ 'quantity' ] );

			if ( isset( $ppu_data[ 'price_per_unit' ] ) && $ppu_data[ 'price_per_unit' ] > 0 ) {
				$result = apply_filters( 'wmg_price_per_unit_loop',
					sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">' . trim( WGM_Price_Per_Unit::get_prefix( $ppu_data ) . ' ' . WGM_Price_Per_Unit::get_output_format() ) . '</span>',
						wc_price( $ppu_data[ 'price_per_unit' ] ),
						str_replace( '.', wc_get_price_decimal_separator(), $ppu_data[ 'mult' ] ),
						$ppu_data[ 'unit' ]
					),
					wc_price( str_replace( ',', '.', $ppu_data[ 'price_per_unit' ] ) ),
					$ppu_data[ 'mult' ],
					$ppu_data[ 'unit' ]
				);

				return $result;
			}

			return $ppu_string;
		}, 10, 3 );

		/**
		 * German Market und B2B Shipping Manager Addon
		 */
		add_action( 'init', function() {
			if ( class_exists( 'Woocommerce_German_Market' ) && class_exists( 'CSP_ShippingManager' ) ) {
				$wgm_dual_shipping_option = get_option( 'wgm_dual_shipping_option', 'off' );
				if ( 'on' === $wgm_dual_shipping_option ) {
					add_filter( 'wgm_dual_shipping_unset_shipping_method', '__return_true' );
					add_filter( 'bm_filter_csp_shipping_manager_hide_shipping_if_free_available', function( $free, $rates ) {
						return $rates;
					}, 10, 2 );
				}
			}
		});

		/**
		 * Germanized
		 */
		if ( class_exists( 'WooCommerce_Germanized' ) ) {
			// RRP.
			add_action( 'init', function() {
				$show_discounts = BM_Show_Discounts::get_instance();
				// RRP badge.
				remove_filter( 'woocommerce_get_price_html', array( $show_discounts, 'show_rrp_and_price' ), 5 );
				add_action( 'woocommerce_single_product_summary', array( $show_discounts, 'show_rrp' ), 9 );
				add_action( 'woocommerce_after_shop_loop_item_title', array( $show_discounts, 'show_rrp' ), 5 );
				// Change prio for rating.
				remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
				add_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 5 );
			});
			// Base Unit Prices
			$group_id = BM_Conditionals::get_validated_customer_group();
			if ( ! empty( $group_id ) ) {
				$group      = get_post( $group_id );
				$group_slug = strtolower( $group->post_name );
				$hide_price = get_option( 'bm_hide_price_' . $group_slug, 'off' );
				if ( 'on' === $hide_price ) {
					add_filter( 'woocommerce_gzd_formatted_unit_price', function( $html, $price, $unit_base, $unit ) {
						return '';
					}, 10, 4 );
				}
				if ( class_exists( 'WooCommerce_Germanized_Pro' ) ) {

					add_filter( 'woocommerce_get_price_html', function( $html, $product ) {

						add_action( 'woocommerce_gzd_before_get_unit_price', function( $gzd_product ) {
							$gzd_product->recalculate_unit_price();
						}, 10, 1 );
						// Adjust variable from-to unit prices
						add_action( 'woocommerce_gzd_before_get_variable_variation_unit_price', function( $gzd_product ) {
							$gzd_product->recalculate_unit_price();
						}, 10, 1 );

						return $html;
					}, 200, 2 );

				}
			}
		}

		/**
		 * WP Bakery Pagebuilder.
		 */
		add_action( 'woocommerce_shortcode_before_featured_products_loop', array( $prices, 'reenable_prices' ) );
		add_action( 'woocommerce_shortcode_before_product_category_loop', array( $prices, 'reenable_prices' ) );

		// Whitelist / Blacklist compatibility
		if ( class_exists( 'Vc_Manager' ) ) {
			add_filter( 'woocommerce_product_is_visible', function( $is_visible, $product_id ) {
				$bm_whitelist = BM_Whitelist::get_instance();

				if ( empty( $bm_whitelist->blacklist ) ) {
					return $is_visible;
				}

				$blacklist = array_unique( $bm_whitelist->blacklist );

				if ( ! empty( $bm_whitelist->active_whitelist ) && 'on' == $bm_whitelist->active_whitelist ) {
					if ( ! in_array( $product_id, $blacklist ) ) {
						$is_visible = false;
					}
				} else {
					if ( in_array( $product_id, $blacklist ) ) {
						$is_visible = false;
					}
				}

				return $is_visible;
			}, 10, 2 );
		}

		/**
		 * Pro theme & Cornerstone builder.
		 */
		if ( 'Pro' === $theme->name || 'Pro' == $theme->parent_theme ) {
			add_action( 'x_layout_row', array( $prices, 'reenable_prices' ) );
			add_filter( 'bm_filter_price', '__return_false' );
		}

		/**
		 * Impreza theme.
		 */
		if ( 'Impreza' === $theme->name || 'Impreza' == $theme->parent_theme ) {
			add_action( 'us_before_template:templates/content', array( $prices, 'reenable_prices' ) );
		}

		/**
		 * Avada.
		 */
		if ( 'Avada' === $theme->name || 'Avada' == $theme->parent_theme ) {
			add_action( 'avada_after_header_wrapper', function() {
				add_filter( 'bm_filter_price', '__return_false' );
			});

			add_action( 'avada_before_main', array( $prices, 'reenable_prices' ) );
		}

		/**
		 * Divi Theme.
		 */
		if ( 'Divi' === $theme->name || 'Divi' == $theme->parent_theme ) {
			add_action( 'et_before_main_content', array( $prices, 'reenable_prices' ) );
		}

		/**
		 * Open Shop Theme.
		 */
		if ( 'Open Shop' === $theme->name || 'Open Shop' == $theme->parent_theme ) {
			add_filter( 'bm_filter_price', '__return_false' );
		}

		/**
		 * Envo eCommerce Theme.
		 */
		if ( 'Envo eCommerce' === $theme->name || 'Envo eCommerce' == $theme->parent_theme ) {
			add_filter( 'bm_filter_price', '__return_false' );
		}

		/**
		 * Thrive Theme Builder
		 */
		if ( 'Thrive Theme Builder' === $theme->name || 'Thrive Theme Builder' == $theme->parent_theme ) {
			add_filter( 'bm_filter_price', '__return_false' );
		}

		/**
		 * Oxygen Elements for WooCommerce Plugin
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'OxyWooCommerce' ) ) {
				add_filter( 'bm_filter_price', '__return_false' );
			}
		});

		/**
		 * WooCommerce Chained Products Plugin
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'WC_Chained_Products' ) ) {
				$prices = BM_Price::get_instance();
				add_filter( 'bm_filter_price', '__return_false' );
			}
		});

		/**
		 * Elementor Plugin
		 */
		add_action( 'elementor/element/before_section_start', array( $prices, 'reenable_prices' ) );

		/**
		 * Elementor mit Shortcode
		 */
		add_action( 'after_setup_theme', function() {
			if ( defined( 'ELEMENTOR_VERSION' ) && class_exists( 'Elementor\Plugin' ) ) {
				add_action( 'bm_action_before_conditional_customer_group_output', function() {
					$elementor = Elementor\Plugin::instance();
					$elementor->frontend->remove_content_filter();
				});
				add_action( 'bm_action_after_conditional_customer_group_output', function() {
					$elementor = Elementor\Plugin::instance();
					$elementor->frontend->add_content_filter();
				});
			}
		});

		/**
		 * WooCommerce Product Table.
		 */
		if ( is_plugin_active( 'woocommerce-product-table/woocommerce-product-table.php' ) ) {
			add_filter( 'wc_product_table_data_price', function( $price, $product ) {
				$group_id       = BM_Conditionals::get_validated_customer_group();
				$cheapest_price = BM_Price::get_price( $product->get_price(), $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );

				// RRP.
				$use_rrp   = get_option( 'bm_use_rrp', 'off' );
				$rrp_label = get_option( 'bm_rrp_label', __( 'RRP', 'b2b-market' ) );
				$rrp_price = floatval( get_post_meta( $product->get_id(), 'bm_rrp', true ) );

				if ( ! $product->is_type( 'variable' ) ) {
					if ( 'on' === $use_rrp ) {
						return '<small class="b2b-rrp">' . $rrp_label . ': ' . wc_price( $rrp_price ) . '</small><br>' . wc_price( $cheapest_price );
					} else {
						return wc_price( $cheapest_price );
					}
				}
				return $price;
			}, 10, 2 );

			// Temporary fix for variation prices.
			add_action( 'wp_footer', function() {
				?>
				<style>
                    .wpt_variations_form .woocommerce-variation-price .price {
                        display: none !important;
                    }
				</style>
				<?php
			});
		}

		/**
		 * Flatsome
		 */
		if ( 'Flatsome' === $theme->name || 'Flatsome' == $theme->parent_theme ) {
			add_action( 'flatsome_after_header_bottom', array( $prices, 'reenable_prices' ) );
		}

		/**
		 * WooCommerce Product Bundles
		 */
		add_action( 'init', function() {
			if ( class_exists( 'WC_Bundles' ) ) {
				add_filter( 'bm_filter_get_sale_price_context', function( $context ) {
					$context = 'edit';
					return $context;
				} );
				add_filter( 'woocommerce_bundled_item_price', function( $price, $product, $discount, $bundled_item ) {

					if ( is_product() ) {
						add_filter( 'bm_filter_disable_bulk_price_table_show_totals', '__return_true' );
					}

					$product          = wc_get_product( $product->get_id() );
					$offset_price     = ! empty( $product->bundled_price_offset ) ? $product->bundled_price_offset : false;
					$offset_price_pct = ! empty( $product->bundled_price_offset_pct ) && is_array( $product->bundled_price_offset_pct ) ? $product->bundled_price_offset_pct : false;

					$group_id         = BM_Conditionals::get_validated_customer_group();
					$cheapest_price   = BM_Price::get_price( $product->get_price( 'edit' ), $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );

					$cheapest_price   = WC_PB_Product_Prices::get_discounted_price($cheapest_price, $discount );

					// Add-on % prices.
					if ( $offset_price_pct ) {

						if ( ! $offset_price ) {
							$offset_price = 0.0;
						}

						foreach ( $offset_price_pct as $price_pct ) {
							$offset_price += $cheapest_price * $price_pct / 100;
						}
					}

					// Add-on prices.
					if ( $offset_price ) {
						$cheapest_price += $offset_price;
					}

					$product->bundled_item_price = $cheapest_price;

					return $cheapest_price;
				}, 10, 4 );
				add_filter( 'bm_filter_bundle_cheapest_price', function( $cheapest_price, $product, $cart_item ) {

					$offset_price     = ! empty( $product->bundled_price_offset ) ? $product->bundled_price_offset : false;
					$offset_price_pct = ! empty( $product->bundled_price_offset_pct ) && is_array( $product->bundled_price_offset_pct ) ? $product->bundled_price_offset_pct : false;

					if ( array_key_exists( 'bundle_sell_discount', $cart_item ) ) {
						$discount = intval( $cart_item[ 'bundle_sell_discount' ] );
					} else {
						$discount = 0;
					}

					$cheapest_price   = WC_PB_Product_Prices::get_discounted_price($cheapest_price, $discount );

					// Add-on % prices.
					if ( $offset_price_pct ) {

						if ( ! $offset_price ) {
							$offset_price = 0.0;
						}

						foreach ( $offset_price_pct as $price_pct ) {
							$offset_price += $cheapest_price * $price_pct / 100;
						}
					}

					// Add-on prices.
					if ( $offset_price ) {
						$cheapest_price += $offset_price;
					}

					$product->bundled_item_price = $cheapest_price;

					return $cheapest_price;
				}, 10, 3 );
			}
		} );

		/** WooCommerce Subscriptions */
		add_action( 'init', function() {
			if ( class_exists( 'WC_Subscriptions' ) ) {
				// Sale Price.
				add_filter( 'bm_filter_get_sale_price_context', function( $context ) {
					return 'edit';
				}, 10, 1 );

				// Regular Price.
				add_filter( 'bm_filter_get_price_context', function( $context ) {
					return 'edit';
				}, 10, 1 );
			}
		} );

		/**
		 * WPC Composite Products Plugin
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'WC_Product_Composite' ) ) {
				add_filter( 'wooco_product_original_price', function( $price, $product ) {
					$group_id       = BM_Conditionals::get_validated_customer_group();
					$cheapest_price = BM_Price::get_price( $product->get_price(), $product, $group_id, apply_filters( 'bm_default_qty', 1 ) );
					return $cheapest_price;
				}, 10, 2 );
				add_filter( 'bm_filter_get_price_context', function( $context ) {
					$context = 'edit';
					return $context;
				} );
			}
		} );

		/**
		 * WPClever Product Bundles
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'WPCleverWoosb' ) ) {

				add_filter( 'woocommerce_cart_item_price', function( $product_price, $cart_item, $cart_item_key ) {
					$cheapest_price = $cart_item[ 'data' ]->get_price();
					return wc_price( $cheapest_price );
				}, 99, 3 );

				add_filter( 'woocommerce_cart_item_subtotal', function( $subtotal, $cart_item, $cart_item_key ) {
					$cheapest_price = $cart_item[ 'data' ]->get_price();
					$new_subtotal   = false;

					if ( isset( $cart_item[ 'woosb_ids' ], $cart_item[ 'woosb_price' ], $cart_item[ 'woosb_fixed_price' ] ) && ! $cart_item[ 'woosb_fixed_price' ] ) {
						$new_subtotal = true;
						$subtotal     = wc_price( $cheapest_price * $cart_item[ 'quantity' ] );
					}

					if ( isset( $cart_item[ 'woosb_parent_id' ], $cart_item[ 'woosb_price' ], $cart_item[ 'woosb_fixed_price' ] ) && $cart_item[ 'woosb_fixed_price' ] ) {
						$new_subtotal = true;
						$subtotal     = wc_price( $cheapest_price * $cart_item[ 'quantity' ] );
					}

					if ( $new_subtotal && ( $cart_product = $cart_item['data'] ) ) {
						if ( $cart_product->is_taxable() ) {
							if ( WC()->cart->display_prices_including_tax() ) {
								if ( ! wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
									$subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
								}
							} else {
								if ( wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
									$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
								}
							}
						}
					}

					return $subtotal;
				}, 99, 3 );
			}
		});

		/**
		 * MarketPress Salesman - Addon 'Free Shipping Notice'
		 */
		add_action( 'after_setup_theme', function() {
			if ( class_exists( 'CSP_ShippingManager' ) && class_exists( 'MarketPress_Salesman' ) && class_exists( 'MarketPress_Salesman_Free_Shipping_Notice' ) ) {
				$group_id = BM_Conditionals::get_validated_customer_group();
				if ( ! empty( $group_id ) ) {
					$group      = get_post( $group_id );
					$group_slug = $group->post_name;
					$status     = get_option( 'bm_shipping_method_enable_free_shipping_' . $group_slug );
					if ( 'off' === $status ) {
						$fsn      = MarketPress_Salesman_Free_Shipping_Notice::get_instance();
						$instance = $fsn->application;
						remove_action( 'woocommerce_before_cart_table', array( $instance, 'free_shipping_cart_notice' ) );
						add_filter( 'marketpress_salesman_free_shipping_notice_shortcode', function( $content ) {
							$content = '';
							return $content;
						} );
					}
				}
			}
		} );

		/**
		 * JupiterX theme compatibility.
		 */
		if ( 'JupiterX' == $theme ) {
			remove_action( 'woocommerce_before_main_content', array( $prices, 'reenable_prices' ) );
			add_action( 'jupiterx_main_content_before_markup', array( $prices, 'reenable_prices' ) );
		}

		/**
		 * MarketPress Salesman - Addon 'Printy Coupons'
		 */
		add_action( 'after_setup_theme', function() {
			add_filter( 'bm_filter_get_regular_price', function( $price, $item ) {
				if ( class_exists( 'MarketPress_Salesman_Printy_Coupons' ) && 'voucher' == $item[ 'data' ]->get_type() ) {
					if ( is_cart() && ! empty( $item[ 'voucher_value' ] ) ) {
						$price = $item[ 'voucher_value' ];
					} else {
						$price = $item[ 'data' ]->get_price();
					}
				}
				return $price;
			}, 10, 2 );
			if ( class_exists( 'MarketPress_Salesman_Printy_Coupons' ) ) {
				add_filter( 'marketpress_salesman_printy_coupons_voucher_min_value', function ( $price, $product ) {
					$cheapest_price = BM_Price::get_price( $price, $product, false, 1 );
					if ( $cheapest_price < $price ) {
						$price = $cheapest_price;
					}
					return $price;
				}, 10, 2 );
				add_filter( 'marketpress_salesman_printy_coupons_voucher_max_value', function ( $price, $product ) {
					$cheapest_price = BM_Price::get_price( $price, $product, false, 1 );
					if ( $cheapest_price < $price ) {
						$price = $cheapest_price;
					}
					return $price;
				}, 10, 2 );
			}
		} );

		/**
		 * Woocommerce Side Cart Premium.
		 */
		if ( class_exists( 'Xoo_Wsc_Cart' ) ) {
			add_action( 'after_setup_theme', function() {
				add_filter( 'xoo_wsc_shipping_bar_args', function( $args ) {

					$freeValue       = 0;
					$hasFreeShipping = false;
					$group_id        = BM_Conditionals::get_validated_customer_group();
					$group_object    = get_post( $group_id );
					$group_slug      = $group_object->post_name;
					$specific_rates  = get_option( 'bm_shipping_rates_disabled_' . $group_slug, array() );
					$specific_rates  = explode( ',', $specific_rates );
					$tax_type        = get_post_meta( $group_id, 'bm_tax_type', true );
					$packages        = WC()->shipping()->get_packages();

					if ( 'on' === $tax_type ) {
						$subtotal = WC()->cart->get_subtotal();
					} else {
						$subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();
					}

					//Support for 1 package only
					$package = $packages[ 0 ];

					$available_methods = $package[ 'rates' ];

					foreach ( $available_methods as $id => $method ) {
						if( $method instanceof WC_Shipping_Free_Shipping ){
							$hasFreeShipping = true;
							break;
						}
					}

					if ( ! $hasFreeShipping ) {

						$shipping_zone 		= WC_Shipping_Zones::get_zone_matching_package( $package );
						$shipping_methods 	= $shipping_zone->get_shipping_methods(true );

						foreach ( $shipping_methods as $id => $shipping_method ) {

							$status = get_option( 'bm_shipping_method_enable_' . $shipping_method->id . '_' . $group_slug );

							if ( 'on' != $status ) {
								continue;
							}

							if ( isset( $specific_rates ) && ! empty( $specific_rates ) ) {
								$shipping_method_id = $shipping_method->id . ':' . $shipping_method->instance_id;
								if ( in_array( $shipping_method_id, $specific_rates ) ) {
									continue;
								}
							}

							if ( $shipping_method instanceof WC_Shipping_Free_Shipping && ( $shipping_method->requires === 'min_amount' || $shipping_method->requires === 'either' ) ) {

								if( 'no' === $shipping_method->ignore_discounts && ! empty( WC()->cart->get_coupon_discount_totals() ) ){
									foreach ( WC()->cart->get_coupon_discount_totals() as $coupon_code => $coupon_value ) {
										$subtotal -= $coupon_value;
									}
								}

								$freeValue = $shipping_method->min_amount;

								if ( $subtotal >= $freeValue ){
									$hasFreeShipping = true;
								} else {
									$amountLeft 	= $freeValue - $subtotal;
									$fillPercentage = ceil( ($subtotal / $freeValue ) * 100 );
								}

							}
						}
					}

					$amountLeft = $freeValue - $subtotal; // amount remaining for free shipping

					$data = array(
						'free'            => $hasFreeShipping,
						'amount_left'     => $hasFreeShipping ? 0 : $amountLeft,
						'fill_percentage' => $hasFreeShipping ? 100 : ceil( ($subtotal / $freeValue ) * 100 ),

					);

					$text = ( $amountLeft > 0 && ! $hasFreeShipping ) ? str_replace( '%s', wc_price( $amountLeft ), xoo_wsc_helper()->get_general_option('sct-sb-remaining') ) : xoo_wsc_helper()->get_general_option( 'sct-sb-free' );

					$args[ 'data' ] = $data;
					$args[ 'text' ] = $text;

					return $args;
				} );
			});
		}

	}
}
