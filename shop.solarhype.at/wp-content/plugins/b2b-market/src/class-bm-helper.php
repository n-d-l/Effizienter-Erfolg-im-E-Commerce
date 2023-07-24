<?php

/**
 * Class which handles all the helper functions
 */
class BM_Helper {

	/**
	 * BM_Helper constructor.
	 */
	public function __construct() {
		add_action( 'wp_trash_post', array( $this, 'skip_trash' ) );
		add_action( 'before_delete_post', array( $this, 'clear_options' ) );
	}

	/**
	 * Get available products
	 *
	 * @return void|array
	 */
	public static function get_available_products() {
		global $sitepress;

		$args     = array(
			'posts_per_page' => - 1,
			'post_type'      => 'product',
			'post_status'    => 'publish',
		);
		$products = array();
		$posts    = get_posts( $args );

		if ( ! empty( $sitepress ) ) {
			$current_language = apply_filters( 'wpml_current_language', null );
			foreach ( $posts as $product ) {
				$_product          = wc_get_product( $product->ID );
				$_product_language = apply_filters( 'wpml_post_language_details', null, $product->ID );
				if ( ! $_product->is_type( 'grouped' ) && $_product_language[ 'language_code' ] == $current_language ) {
					array_push( $products, array( $product->post_title, $product->ID ) );
				}
			}
		} else {
			foreach ( $posts as $product ) {
				$_product = wc_get_product( $product->ID );
				if ( ! $_product->is_type( 'grouped' ) ) {
					array_push( $products, array( $product->post_title, $product->ID ) );
				}
			}
		}

		return $products;
	}

	/**
	 * Get avaialable product categories
	 *
	 * @return array
	 */
	public static function get_available_categories() {
		$cats = get_terms( 'product_cat', array(
			'hide_empty' => false,
		) );

		$available_categories = array();

		foreach ( $cats as $cat ) {
			array_push( $available_categories, array( $cat->name, $cat->term_id ) );
		}

		return $available_categories;
	}

	/**
	 * Get current posttype from admin page
	 *
	 * @return void
	 */
	public static function get_current_post_type() {
		global $post, $typenow, $current_screen;

		if ( $post && $post->post_type ) {
			return $post->post_type;
		} elseif ( $typenow ) {
			return $typenow;
		} elseif ( $current_screen && $current_screen->post_type ) {
			return $current_screen->post_type;
		} elseif ( isset( $_REQUEST['post_type'] ) ) {
			return sanitize_key( $_REQUEST['post_type'] );
		} elseif ( isset( $_REQUEST['post'] ) ) {
			return get_post_type( $_REQUEST['post'] );
		}

		return null;
	}

	/**
	 * force delete customer_groups
	 *
	 * @param $post_id
	 */
	public function skip_trash( $post_id ) {
		if ( $this->get_current_post_type() == 'customer_groups' ) {
			// Force delete
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Delete all options for customer group
	 *
	 * @param int $postid
	 * @return void
	 */
	public function clear_options( $postid ) {

		if ( $this->get_current_post_type() == 'customer_groups' ) {
			global $wpdb;

			$group_object = get_post( $postid );
			$group        = $group_object->post_name;

			if ( ! empty( $group ) ) {

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * from $wpdb->options WHERE option_name LIKE %s", $group ) );

				foreach ( $results as $result ) {
					delete_option( $result->option_name );
				}
			}
		}
	}
	/**
	 * Checks if array is empty
	 *
	 * @param array $array
	 * @return boolean
	 */
	public static function is_array_empty( $array ) {

		foreach ( $array as $key => $val ) {
			if ( '' == $val ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the current visit is a rest api call
	 *
	 * @return boolean
	 */
	public static function is_rest() {

		$prefix = rest_get_url_prefix();

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST || isset( $_GET['rest_route'] ) && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix, 0 ) === 0 ) {
			return true;
		}

		$rest_url    = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		if ( is_array( $current_url ) && is_array( $rest_url ) ) {
			if ( isset( $current_url['path'] ) && isset( $rest_url['path'] ) ) {
				return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
			}
		}
	}


	/**
	* Returns true if ajax is executed from frontend.
	*
	* @access public
	* @return Boolean
	*/
	public static function is_frontend_ajax() {

		$script_filename = isset( $_SERVER[ 'SCRIPT_FILENAME' ] ) ? $_SERVER[ 'SCRIPT_FILENAME' ] : '';

		//Try to figure out if frontend AJAX request... If we are DOING_AJAX; let's look closer
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			$ref = '';

			if ( ! empty( $_REQUEST[ '_wp_http_referer' ] ) ) {
				$ref = wp_unslash( $_REQUEST[ '_wp_http_referer' ] );
			} elseif ( ! empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
				$ref = wp_unslash( $_SERVER[ 'HTTP_REFERER' ] );
			}

			// If referer does not contain admin URL and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request
			if ( ( ( strpos( $ref, admin_url() ) === false ) && ( basename( $script_filename ) === 'admin-ajax.php' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return translated object id(s) if WPML is supported.
	 *
	 * @acces public
	 * @static
	 *
	 * @param int|array $object_id object id or ids
	 * @param string $type type of object like 'post' or 'category'
	 *
	 * @return int|array
	 */
	public static function get_translated_object_ids( $object_id, $type ) {
		global $sitepress;

		// Check if WPML object is available.
		if ( empty( $sitepress ) ) {
			if ( is_array( $object_id ) ) {
				// Check of empty values in given array.
				$output_array = array();
				foreach( $object_id as $id ) {
					if ( '' != $id ) {
						$output_array[] = $id;
					}
				}
				return $output_array;
			}
			return $object_id;
		}

		if ( '' == $type ) {
			return $object_id;
		}

		if ( is_array( $object_id ) ) {
			$translated_object_ids = array();
			foreach( $object_id as $id ) {
				if ( '' != $id ) {
					$translated_object_ids[] = apply_filters( 'wpml_object_id', $id, $type, true );
				}
			}
			return $translated_object_ids;
		} else {
			return apply_filters( 'wpml_object_id', $object_id, $type, true );
		}
	}

	/**
	 * Returns the group slug.
	 *
	 * @acces public
	 * @static
	 *
	 * @param int|null $group_id group id
	 *
	 * @return string|void
	 */
	public static function get_group_slug( $group_id ) {

		if ( ! empty( $group_id ) ) {
			return get_post_field( 'post_name', $group_id );
		}
	}

	/**
	 * Force regenerating product price hashes.
	 *
	 * @acces public
	 * @static
	 *
	 * @return void
	 */
	public static function force_regenerate_woocommerce_price_hashes() {

		// Refresh cached product prices transient.
		WC_Cache_Helper::get_transient_version( 'product', true );

		$query = new WC_Product_Query( array(
			'type'   => 'variable',
			'return' => 'ids',
			'limit'  => -1,
		) );
		$products = $query->get_products();

		if ( ! empty( $products ) ) {
			foreach ( $products as $product_id ) {
				delete_transient( 'wc_product_children_' . $product_id );
			}
		}
	}

}
