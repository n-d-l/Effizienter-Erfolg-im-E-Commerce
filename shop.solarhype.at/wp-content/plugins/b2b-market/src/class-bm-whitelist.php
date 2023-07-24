<?php

/**
 * Class to handle Whitelist / Blacklist
 */
class BM_Whitelist {

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
	 * BM_Conditionals constructor.
	 */
	public function __construct() {
		$this->current_customer_group = BM_Conditionals::get_validated_customer_group();

		$products   = $this->get_products_whitelist();
		$categories = $this->get_categories_whitelist();

		if ( is_array( $products ) && is_array( $categories ) ) {
			$this->blacklist = array_merge( $products, $categories );
		} elseif ( is_array( $products ) ) {
			$this->blacklist = $products;
		} elseif ( is_array( $categories ) ) {
			$this->blacklist = $products;
		}

		if ( isset( $this->current_customer_group ) && ! is_null( $this->current_customer_group ) ) {
			$this->active_whitelist = get_post_meta( $this->current_customer_group, 'bm_conditional_all_products', true );
		}
	}

	/**
	 * Set whitelist for WooCommerce Blocks.
	 *
	 * @Hook parse_query
	 * @access public
	 * @param  object query object
	 * @return void
	 */
	public function set_woocommerce_blocks_whitelist( $wp_query ) {
		/*
		 * Do we have a block query?
		 * Attention: This is not working for block 'All Products' at the moment.
		 */
		if ( is_a( $wp_query, 'Automattic\WooCommerce\Blocks\Utils\BlocksWpQuery' ) ) {

			if ( isset( $this->current_customer_group ) && ! is_null( $this->current_customer_group ) ) {

				// Whitelisting.
				if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
					if ( ! empty( $this->blacklist ) ) {
						$wp_query->query_vars[ 'post__in' ] = $this->blacklist;
					}
					// Check for vars in 'query'
					if ( ! empty( $wp_query->query[ 'post__not_in' ] ) ) {
						foreach ( $wp_query->query[ 'post__not_in' ] as $key => $post_id ) {
							if ( in_array( $post_id, $this->blacklist ) && isset( $wp_query->query[ 'post__not_in' ][ $key ] ) ) {
								unset( $wp_query->query[ 'post__not_in' ][ $key ] );
							}
						}
					}
					// Check for vars in 'query_vars'
					if ( ! empty( $wp_query->query_vars[ 'post__not_in' ] ) ) {
						foreach ( $wp_query->query_vars[ 'post__not_in' ] as $key => $post_id ) {
							if ( in_array( $post_id, $this->blacklist ) && isset( $wp_query->query_vars[ 'post__not_in' ][ $key ] ) ) {
								unset( $wp_query->query_vars[ 'post__not_in' ][ $key ] );
							}
						}
					}
				} else
				// Blacklisting.
				if ( empty( $this->active_whitelist ) || 'off' == $this->active_whitelist ) {
					if ( ! empty( $this->blacklist ) ) {
						$wp_query->query_vars[ 'post__not_in' ] = $this->blacklist;
					}
					// Check for vars in 'query'
					if ( ! empty( $wp_query->query[ 'post__in' ] ) ) {
						foreach ( $wp_query->query[ 'post__in' ] as $key => $post_id ) {
							if ( in_array( $post_id, $this->blacklist ) && isset( $wp_query->query[ 'post__in' ][ $key ] ) ) {
								unset( $wp_query->query[ 'post__in' ][ $key ] );
							}
						}
					}
					// Check for vars in 'query_vars'
					if ( ! empty( $wp_query->query_vars[ 'post__in' ] ) ) {
						foreach ( $wp_query->query_vars[ 'post__in' ] as $key => $post_id ) {
							if ( in_array( $post_id, $this->blacklist ) && isset( $wp_query->query_vars[ 'post__in' ][ $key ] ) ) {
								unset( $wp_query->query_vars[ 'post__in' ][ $key ] );
							}
						}
					}
				}
			}
		}

	}

	/**
	 * Get the products on whitelist
	 *
	 * @return array
	 */
	public function get_products_whitelist() {
		global $sitepress;

		$whitelist = array();

		if ( isset( $this->current_customer_group ) && ! is_null( $this->current_customer_group ) ) {
			$products_customer_group_meta = get_post_meta( $this->current_customer_group, 'bm_conditional_products', true );
		}

		if ( isset( $products_customer_group_meta ) && ! empty( $products_customer_group_meta ) ) {
			$products_customer_group = explode( ',', $products_customer_group_meta );

			if ( isset( $products_customer_group ) ) {
				$whitelist = BM_Helper::get_translated_object_ids( $products_customer_group, 'post' );
			}
		}

		return $whitelist;
	}

	/**
	 * Get categories for whitelist / blacklist
	 *
	 * @return array
	 */
	public function get_categories_whitelist() {
		global $sitepress;

		$whitelist = array();

		if ( isset( $this->current_customer_group ) && ! is_null( $this->current_customer_group ) ) {
			$cat_customer_group_meta = get_post_meta( $this->current_customer_group, 'bm_conditional_categories', true );
		}

		if ( isset( $cat_customer_group_meta ) && ! empty( $cat_customer_group_meta ) ) {
			$categories_customer_group = explode( ',', $cat_customer_group_meta );

			if ( isset( $categories_customer_group ) ) {
				foreach ( $categories_customer_group as $category_id ) {

					if ( '' != $category_id ) {

						$category_id = BM_Helper::get_translated_object_ids( $category_id, 'category' );
						$term        = get_term( $category_id, 'product_cat' );

						if ( ! empty( $term ) ) {

							$args     = array(
								'posts_per_page'   => - 1,
								'post_type'        => 'product',
								'fields'           => 'ids',
								'post_status'      => 'publish',
								'suppress_filters' => true,
								'tax_query'        => array(
									array(
										'taxonomy' => 'product_cat',
										'field'    => 'slug',
										'terms'    => $term->slug,
									),
								),
							);
							$products = get_posts( $args );

							foreach ( $products as $product_id ) {
								array_push( $whitelist, $product_id );
							}
						}
					}
				}
			}
		}
		return $whitelist;
	}

	/**
	 * Set whitelist
	 *
	 * @param object $query
	 * @return void
	 */
	public function set_whitelist( $query ) {
		$q = 'post__not_in';

		if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
			$q = 'post__in';
			if ( count( $this->blacklist ) === 0 ) {
				set_query_var( $q, array( 0 ) );
			} else {
				set_query_var( $q, array_unique( $this->blacklist ) );
			}
		} else {
			set_query_var( $q, array_unique( $this->blacklist ) );
		}
	}

	/**
	 * Set whitelist for category views on shop page.
	 *
	 * @param  array $terms array of terms.
	 * @param  array $taxonomies array of taxonomies.
	 * @param  array $args array of arguments.
	 * @return array
	 */
	public function set_shop_category_view_whitelist( $terms, $taxonomies, $args ) {
		global $sitepress;

		// if not in shop, archive or category - skip.
		if ( ! is_shop() && ! is_archive() && ! is_category() ) {
			return $terms;
		}

		$cats_blacklists = get_post_meta( $this->current_customer_group, 'bm_conditional_categories', true );

		// if not category set - skip.
		if ( ! isset( $cats_blacklists ) || empty( $cats_blacklists ) ) {
			return $terms;
		}

		$cats_blacklists = explode( ',', $cats_blacklists );
		$new_terms       = array();

		// WPML support.
		if ( ! empty( $sitepress ) && ! empty( $cats_blacklists ) ) {
			foreach( $cats_blacklists as $category_key => $category_id ) {
				if ( '' != $category_id ) {
					$cats_blacklists[ $category_key ] = BM_Helper::get_translated_object_ids( $category_id, 'category' );
				}
			}
		}

		// Check if blacklist or whitelist.
		$whitelist = $this->active_whitelist;

		if ( in_array( 'product_cat', $taxonomies ) && ! is_admin() ) {
			foreach ( $terms as $key => $term ) {
				if( 'on' === $whitelist ) {
					if ( in_array( $term->term_id, $cats_blacklists ) ) {
						$new_terms[] = $term;
					}
				} else {
					if ( ! in_array( $term->term_id, $cats_blacklists ) ) {
						$new_terms[] = $term;
					}
				}
			}
			$terms = $new_terms;
		}

		return $terms;
	}

	/**
	 * Set whitelist / blacklist for related products
	 *
	 * @param array $related_posts
	 * @param int $product_id
	 * @param array $args
	 * @return void
	 */
	public function set_related_whitelist( $related_posts, $product_id, $args ) {

		$blacklist_items = count( $this->blacklist );

		if ( is_product() ) {

			if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
				if ( $blacklist_items === 0 ) {
					return $this->blacklist;
				}
			} else {
				$exclude_ids = $this->blacklist;
				return array_diff( $related_posts, $exclude_ids );
			}
		}
		return $related_posts;
	}

	/**
	 * Set whitelist / blacklist for upsells
	 *
	 * @param [type] $relatedIds
	 * @param [type] $product
	 * @return void
	 */
	public function set_upsell_whitelist( $relatedIds, $product ) {

		if ( empty( $relatedIds ) ) {
			return $relatedIds;
		}

		$blacklist_items = count( $this->blacklist );

		if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
			if ( $blacklist_items === 0 ) {
				return $this->blacklist;
			}
		} else {
			$exclude_ids = $this->blacklist;
			return array_diff( $relatedIds, $exclude_ids );
		}

		return $relatedIds;
	}

	/**
	 * Set whitelist / blacklist for widgets
	 *
	 * @param array $query_args
	 * @return void
	 */
	public function set_widget_whitelist( $query_args ) {
		$q         = 'post__not_in';

		if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
			$q = 'post__in';
			if ( count( $this->blacklist ) === 0 ) {
				$query_args[ $q ] = array( 0 );
			} else {
				$query_args[ $q ] = array_unique( $this->blacklist );
			}
		} else {
			$query_args[ $q ] = array_unique( $this->blacklist );
		}
		return $query_args;
	}

	/**
	 * Set whitelist / blacklist for category widgets
	 *
	 * @param array $query_args
	 * @return void
	 */
	public function set_widget_category_whitelist( $query_args ) {
		global $sitepress;

		if ( isset( $this->current_customer_group ) && ! is_null( $this->current_customer_group ) ) {
			$cat_customer_group_meta      = get_post_meta( $this->current_customer_group, 'bm_conditional_categories', true );
			$products_customer_group_meta = get_post_meta( $this->current_customer_group, 'bm_conditional_products', true );
			$categories_customer_group    = explode( ',', $cat_customer_group_meta );
			$products_customer_group      = explode( ',', $products_customer_group_meta );

			// WMPL support.
			if ( ! empty( $sitepress ) ) {
				if ( is_array( $categories_customer_group ) && ! empty( $categories_customer_group ) ) {
					foreach ( $categories_customer_group as $category_key => $category_id ) {
						if ( '' != $category_id ) {
							$categories_customer_group[ $category_key ] = BM_Helper::get_translated_object_ids( $category_id, 'category' );
						}
					}
				}
				if ( is_array( $products_customer_group ) && ! empty( $products_customer_group ) ) {
					foreach ( $products_customer_group as $product_key => $product_id ) {
						if ( '' != $product_id ) {
							$products_customer_group[ $product_key ] = BM_Helper::get_translated_object_ids( $product_id, 'post' );
						}
					}
				}
			}

			if ( isset( $categories_customer_group ) && ! empty( $categories_customer_group ) ) {
				if ( '' === $categories_customer_group[0] ) {
					unset( $categories_customer_group[0] );
				}

				if ( '' === $products_customer_group[0] ) {
					unset( $products_customer_group[0] );
				}

				foreach ( $products_customer_group as $product_id ) {
					$product_cats = wp_get_post_terms( $product_id, 'product_cat' , array( 'fields' => 'ids' ) );

					foreach ( $product_cats as $cat_id ) {
						$cat = get_term( $cat_id, 'product_cat' );

						// get children.
						$children = get_term_children( $cat_id, 'product_cat' );

						if ( isset( $children ) && ! empty( $children ) ) {
							foreach ( $children as $child ) {
								$categories_customer_group[] = $child;
							}
						}

						if ( ! empty( $categories_customer_group ) || 'on' == $this->active_whitelist ) {
							$categories_customer_group[] = $cat_id;
							$categories_customer_group[] = $cat->parent;
						}
					}
				}

				if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
					$query_args['include'] = $categories_customer_group;
				} else {
					if ( ! empty( $categories_customer_group ) ) {
						$query_args['exclude'] = $categories_customer_group;
					}
				}
			}
		}
		return $query_args;
	}
	/**
	 * Set whitelist / blacklist for search
	 *
	 * @param array $query
	 * @return void
	 */
	public function set_search_whitelist( $query ) {

		if ( ! $query->is_admin && $query->is_search ) {

			$q        = 'post__not_in';
			$q_parent = 'post_parent__not_in';

			if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
				$q        = 'post__in';
				$q_parent = 'post_parent__in';
				if ( count( $this->blacklist ) === 0 ) {
					$query->set( $q, array( 0 ) );
					$query->set( $q_parent, array( 0 ) );
					return $query;
				} else {
					$query->set( $q, array_unique( $this->blacklist ) );
					$query->set( $q_parent, array_unique( $this->blacklist ) );
				}
			} else {
				$query->set( $q, array_unique( $this->blacklist ) );
				$query->set( $q_parent, array_unique( $this->blacklist ) );
			}
		}
	}

	/**
	 * Set redirects based on whitelist / blacklist
	 *
	 * @return void
	 */
	public function redirect_based_on_whitelist() {

		if ( is_product() ) {

			if ( ! empty( $this->active_whitelist) && 'on' == $this->active_whitelist ) {
				if ( ! in_array( get_the_id(), $this->blacklist ) ) {
					wp_redirect( get_site_url() . apply_filters( 'bm_filter_whitelist_redirect_to_page', '/404' ) );
					exit;
				}
			} elseif ( isset( $this->blacklist ) && ! empty( $this->blacklist ) ) {
				if ( in_array( get_the_id(), $this->blacklist ) ) {
					wp_redirect( get_site_url() . apply_filters( 'bm_filter_blacklist_redirect_to_page', '/404' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Checks if product in cart is on blacklist
	 *
	 * @return void
	 */
	public function is_cart_item_whitelist() {

		if ( ! isset( $this->blacklist ) || empty( $this->blacklist ) ) {
			return;
		}

		$blacklist = array_unique( $this->blacklist );

		if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( ! in_array( $item['product_id'], $blacklist ) ) {
					wc_print_notice( __( 'You are not allowed to purchase the following product, please remove it from your cart', 'b2b-market' ) . ': <b>' . $item['data']->get_name() . '</b>', 'error' );
				}
			}
		} else {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $blacklist ) ) {
					wc_print_notice( __( 'You are not allowed to purchase the following product, please remove it from your cart', 'b2b-market' ) . ': <b>' . $item['data']->get_name() . '</b>', 'error' );
				}
			}
		}

	}
	/**
	 * Checks if product in checkout is on blacklist
	 *
	 * @param  array  $data cart data.
	 * @param  object $errors cart errors.
	 * @return boolean
	 */
	public function is_checkout_item_whitelist( $data, $errors ) {
		if ( ! isset( $this->blacklist ) || empty( $this->blacklist ) ) {
			return;
		}

		$blacklist = array_unique( $this->blacklist );

		if ( ! empty( $this->active_whitelist ) && 'on' == $this->active_whitelist ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( ! in_array( $item['product_id'], $blacklist ) ) {
					$errors->add( 'validation', __( 'You are not allowed to complete your order, please remove the following product from your cart', 'b2b-market' ) . ': <b>' . $item['data']->get_name() . '</b>' );
				}
			}
		} else {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( in_array( $item['product_id'], $blacklist ) ) {
					$errors->add( 'validation', __( 'You are not allowed to complete your order, please remove the following product from your cart', 'b2b-market' ) . ': <b>' . $item['data']->get_name() . '</b>' );
				}
			}
		}
	}
}
