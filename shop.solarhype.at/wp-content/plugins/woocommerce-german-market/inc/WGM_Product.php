<?php

class WGM_Product {

	public static $options = array();

	public static function init(  ) {

		add_filter( 'product_type_options',                                     array( 'WGM_Product', 'register_product_type') );
		add_action( 'woocommerce_variation_options',                            array( 'WGM_Product', 'add_variant_product_type'), 10, 3 );
		add_action( 'woocommerce_update_product_variation',                     array( 'WGM_Product', 'save_variant_product_type'), 10, 1 );
		add_action( 'woocommerce_new_product_variation',                     	array( 'WGM_Product', 'save_variant_product_type'), 10, 1 );

		self::init_product_images();
		self::init_product_attributes();
		self::$options[ 'german_market_add_to_cart_in_shop_pages_product_types' ] = get_option( 'german_market_add_to_cart_in_shop_pages_product_types', array() );
		self::add_to_cart_button_shop_pages();

		// GTIN
		if ( get_option( 'gm_gtin_activation', 'off' ) == 'on' ) {
			add_action( 'woocommerce_product_options_general_product_data',  	array( 'WGM_Product', 'add_gtin_options_simple' ), 11 );
			add_action( 'woocommerce_product_after_variable_attributes',  		array( 'WGM_Product', 'add_gtin_options' ),  11, 3 );

			if ( get_option( 'gm_gtin_product_pages', 'off' ) == 'on' ) {
				add_action( 'wgm_product_summary_parts', 						array( 'WGM_Product', 'add_gtin_frontend' ), 10, 3 );
			}

			add_filter( 'woocommerce_structured_data_product',								array( 'WGM_Product', 'structured_data' ), 10, 2 );
		}
	}

	/**
	 * Add GTIN to Frontend
	 *
	 * @since 3.8.2
	 * @wp-hook wgm_product_summary_parts
	 * @param Array $output_parts
	 * @param WC_Product $product
	 * @param String $hook
	 * @return void
	 */
	public static function add_gtin_frontend( $output_parts, $product, $hook ) {

		$show = apply_filters( 'german_market_add_gtin_frontend_show_in_hook', $hook == 'single', $hook );

		if ( $show ) {

			$gtin = $product->get_meta( '_gm_gtin' );

			if ( ! empty( $gtin ) ) {

				$markup 	= apply_filters( 'german_market_add_gtin_frontend_markup', '<div class="wgm-info wgm-gtin">%s: %s</div>' );
				$label 		= apply_filters( 'german_market_add_gtin_frontend_label', __( 'GTIN', 'woocommerce-german-market' ) );

				$output_parts[ 'gtin' ] = sprintf( $markup, $label, $gtin );
			}

		}

		return $output_parts;
	}

	/**
	 * Add GTIN for simple Products
	 *
	 * @since 3.8.2
	 * @wp-hook woocommerce_product_options_general_product_data
	 * @return void
	 */
	public static function add_gtin_options_simple( ) {
		self::add_gtin_options( NULL, NULL, NULL );
	}

	/**
	 * Add GTIN for Variations (and simple products)
	 *
	 * @since 3.8.2
	 * @wp-hook woocommerce_product_after_variable_attributes
	 * @param Integer $loop
	 * @param Array $variation_data
	 * @param WC_Product_Variation $variation
	 * @return void
	 */
	public static function add_gtin_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

		/**
		 * This method can be used for both regular products as well as variations.
		 * Within a variation, styling and markup is a little bit different, so in addition to changing the post ID to the variation,
		 * also add a bit of additional markup
		 */
		$is_variation = ( ! is_null( $variation ) );
		$name_suffix  = '';

		if ( $is_variation ) {
			$name_suffix = '_variable[' . $loop . ']';
			$id          = $variation->ID;

		} else {
			?>
			<div class="options_group">
			<?php
			$id = get_the_ID();

		}

		$data = maybe_unserialize( get_post_meta( $id, '_gm_gtin', TRUE ) );

		$label_style 	= $is_variation ? 'style="width: 30%; float: left;"' : '';
		$input_style 	= $is_variation ? 'style="width: 50%; float: left;"' : '';

		if ( $is_variation ) { ?>

			<div class="german-market-gtin" style="border: 1px solid #eee; padding: 10px; box-sizing: border-box; float: left; width: 100%;">

		<?php } ?>

			<?php $class = apply_filters( 'german_market_wgm_product_add_gtin_options_admin_p_class', 'form-field show_if_simple show_if_external' ); ?>
			<p class="<?php echo $class; ?>" style="display:block">

				<label for="_gm_gtin<?php echo $name_suffix; ?>" <?php echo $label_style; ?>>
					<?php _e( 'GTIN', 'woocommerce-german-market' ); ?>:
				</label>

				<input type="text" name="_gm_gtin<?php echo $name_suffix; ?>" id="_gm_gtin<?php echo $name_suffix; ?>" value="<?php echo $data; ?>" <?php echo $input_style; ?>/>

			</p>

		<?php if ( $is_variation ) {
			?><br style="clear: both;"></div><?php
		}
		?>

		<?php
		if ( ! $is_variation ) {
			?>
			</div>
			<?php
		}

	}

	/**
	 * Add GTIN to structured data
	 *
	 * @since 3.12.4
	 * @wp-hook woocommerce_structured_data_product
	 * @param Array $markup
	 * @param WC_Product $product
	 * @return Array
	 */
	public static function structured_data( $markup, $product ) {

		$gtin		= '';

		if ( is_object( $product ) && method_exists( $product, 'get_meta' ) ) {
			$gtin 	= $product->get_meta( '_gm_gtin' );
		}

	 	$strlen = strlen( $gtin );
		$key 	= '';

		if ( 8 === $strlen ) {
			$key = 'gtin8';
		} else if ( 12 === $strlen ) {
			$key = 'gtin12';
		} else if ( 13 === $strlen ) {
			$key = 'gtin13';
		} else if ( 14 === $strlen ) {
			$key = 'gtin14';
		}

		if ( ! empty( $key ) ) {
			if ( ! isset( $markup[ $key ] ) ) {
				$markup[ $key ] = apply_filters( 'german_market_wgm_product_gtin_structured_data', (string)$gtin, $markup, $product );
			}
		}

		return $markup;
	}

	/**
	 * Change "Add To Cart"-Button on Shop Pages
	 *
	 * @since 3.8.2
	 * @return void
	 */
	public static function add_to_cart_button_shop_pages() {
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_button_shop_pages_text' ), 10 ,2 );
		add_filter( 'woocommerce_product_add_to_cart_url', 	array( __CLASS__, 'add_to_cart_button_shop_pages_link' ), 10 ,2 );
		add_filter( 'woocommerce_product_supports', 		array( __CLASS__, 'add_to_cart_button_shop_pages_avoid_ajax' ), 10, 3 );
	}

	

	/**
	 * Change "Add To Cart"-Button: Remove Ajax Functionality
	 *
	 * @since 3.8.2
	 * @wp-hook woocommerce_product_supports
	 * @param Boolean $boolean
	 * @param String $feature
	 * @param WC_Product $product
	 * @return Boolean
	 */
	public static function add_to_cart_button_shop_pages_avoid_ajax( $boolean, $feature, $product ) {

		if ( $feature == 'ajax_add_to_cart' ) {
			if ( WGM_Helper::method_exists( $product, 'get_type' ) ) {
				if ( in_array( $product->get_type(), self::$options[ 'german_market_add_to_cart_in_shop_pages_product_types' ] ) ) {
					$boolean = false;
				}
			}
		}

		return $boolean;
	}

	/**
	 * Change "Add To Cart"-Button Text on Shop Pages
	 *
	 * @since 3.8.2
	 * @wp-hook woocommerce_product_add_to_cart_text
	 * @param String $text
	 * @param WC_Product $product
	 * @return String
	 */
	public static function add_to_cart_button_shop_pages_text( $text, $product ) {

		if ( WGM_Helper::method_exists( $product, 'get_type' ) ) {
			if ( in_array( $product->get_type(), self::$options[ 'german_market_add_to_cart_in_shop_pages_product_types' ] ) ) {
				$text = get_option( 'german_market_add_to_cart_in_shop_pages_text', __( 'Show Product', 'woocommerce-german-market' ) );
			}
		}

		return $text;
	}

	/**
	 * Change "Add To Cart"-Button URL on Shop Pages
	 *
	 * @since 3.8.2
	 * @wp-hook woocommerce_product_add_to_cart_url
	 * @param String $url
	 * @param WC_Product $product
	 * @return String
	 */
	public static function add_to_cart_button_shop_pages_link( $url, $product ) {

		if ( WGM_Helper::method_exists( $product, 'get_type' ) ) {
			if ( in_array( $product->get_type(), self::$options[ 'german_market_add_to_cart_in_shop_pages_product_types' ] ) ) {
				$url = $product->get_permalink();
			}
		}

		return $url;
	}

	/**
	 * Show Product Attributes that are not used for variations in cart, checkout and orders
	 *
	 * @since 3.7
	 * @return void
	 */
	public static function init_product_attributes() {

		if ( get_option( 'gm_show_product_attributes', 'off' ) == 'on' ) {
			add_filter( 'woocommerce_add_cart_item_data', 			array( __CLASS__, 'add_cart_item_data' ),	 		10, 4 );
			add_filter( 'woocommerce_get_item_data', 				array( __CLASS__, 'get_item_data' ),		 		10, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', 	array( __CLASS__, 'get_cart_item_from_session' ),	10, 3 );
			add_action( 'woocommerce_new_order_item', 				array( __CLASS__, 'new_order_item' ),				10, 3 );
		}

	}

	/**
	 * Add Product Attributes to order Item
	 *
	 * @since 3.7
	 * @wp-hook woocommerce_new_order_item
	 * @param Integer $item_id
	 * @param WC_Order_Item_Product $item
	 * @param Integer $order_id
	 * @return void
	 */
	public static function new_order_item( $item_id, $item, $order_id ) {

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {

			$product = $item->get_product();

			// Compatibility for plugins that creates a WC_Order_Item_Product without an existing Product
			if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
				return;
			}

			$data = array();
			$attribute_data = WGM_Product::add_cart_item_data( $data, $product->get_id(), false, false );

			if ( isset( $attribute_data[ 'gm_product_properties' ] ) ) {

				foreach ( $attribute_data[ 'gm_product_properties' ] as $property ) {
					wc_add_order_item_meta( $item_id, $property[ 'name' ], $property[ 'value' ] );
				}

			}

		}

	}

	/**
	 * Add Product Attributes from session to cart item
	 *
	 * @wp-hook woocommerce_get_cart_item_from_session
	 * @since 3.7
	 * @param Array $cart_item_data
	 * @param Array $cart_item_session_data
	 * @param Integer $cart_item_key
	 * @return Array
	 */
	public static function get_cart_item_from_session( $cart_item_data, $cart_item_session_data, $cart_item_key ) {

		if ( isset( $cart_item_session_data[ 'gm_product_properties' ] ) ) {
	        $cart_item_data[ 'gm_product_properties' ] = $cart_item_session_data[ 'gm_product_properties' ];
	    }

		return $cart_item_data;

	}

	/**
	 * Add Product Attributes to cart item data
	 *
	 * @wp-hook woocommerce_add_cart_item_data
	 * @since 3.7
	 * @param Array $cart_item_data
	 * @param Integer $product_id
	 * @param Integer $variation_id
	 * @param Integer $quantity
	 * @return Array
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity = 1 ) {

		$product = wc_get_product( $product_id );

		if ( ! WGM_Helper::method_exists( $product, 'get_type' ) ) {
			$product = wc_get_product( $variation_id );
		}

		if ( $product->get_type() == 'variation' ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		if ( ( ! is_null( $product ) ) && WGM_Helper::method_exists( $product, 'get_attributes' ) ) {

			$attributes = $product->get_attributes();

			foreach ( $attributes as $attribute ) {

				if ( ! WGM_Helper::method_exists( $attribute, 'get_visible' ) ) {
					continue;
				}

				$name = '';

				if ( WGM_Helper::method_exists( $attribute, 'get_name' ) ) {
					$name = trim( wc_attribute_label( $attribute->get_name() ) );
				}

				$is_variation = $attribute->get_variation();

				if ( str_replace( 'variable', '', $product->get_type() ) === $product->get_type() ) {
					$is_variation = false;
				}

				if ( $attribute->get_visible() && ( ! $is_variation ) ) {

					$option_names	  = array();
					
					if ( $attribute->is_taxonomy() ) {
						$taxonomy 		= $attribute->get_taxonomy_object();
						$name 			= empty( $name ) ? $taxonomy->attribute_label : $name;
						$option_terms 	= $attribute->get_terms();

						foreach ( $option_terms as $term ) {
							$option_names[] = $term->name;
						}

					} else {
						$name = apply_filters( 'german_market_attribute_name_add_to_cart', $attribute->get_name(), $product );
						$option_names 	= $attribute->get_options();
					}

					if ( is_array( $option_names ) && ( count( $option_names ) > 1 ) && apply_filters( 'german_market_attribute_name_add_to_cart_do_asort', '__return_true' ) ) {
						asort( $option_names );
					}

					$value = implode( ', ', $option_names );

					if ( ! isset( $cart_item_data[ 'gm_product_properties' ] ) ) {
						$cart_item_data[ 'gm_product_properties' ] = array();
					}

					$cart_item_data[ 'gm_product_properties' ][] = array(
						'name'	=> $name,
						'value'	=> $value,
					);

				}

			}
		}

		return $cart_item_data;
	}

	/**
	 * Get Product Attributes
	 *
	 * @wp-hook woocommerce_get_item_data
	 * @since 3.7
	 * @param Array $item_data
	 * @param Array $cart_item
	 * @return Array
	 */
	public static function get_item_data ( $item_data, $cart_item ) {

		if ( isset( $cart_item[ 'gm_product_properties' ] ) ) {

			foreach ( $cart_item[ 'gm_product_properties' ] as $property ) {

				$item_data[] = array(
					'name'	=> $property[ 'name' ],
					'value' => $property[ 'value' ],
				);

			}

		}

		return $item_data;

	}

	/**
	 * Handle Product Images in Cart, Checkout, Orders, Emails
	 *
	 * @since 3.6.4
	 * @return void
	 */
	public static function init_product_images() {

		// Get all options
		$show_images_in = array(
			'cart'		=> get_option( 'german_market_product_images_in_cart', 'on' ),
			'checkout'	=> get_option( 'german_market_product_images_in_checkout', 'off' ),
			'order'		=> get_option( 'german_market_product_images_in_order', 'off' ),
			'email'		=> get_option( 'german_market_product_images_in_emails', 'off' ),
		);

		// Images in Cart
		if ( $show_images_in[ 'cart' ] == 'off' ) {

			add_filter( 'woocommerce_cart_item_thumbnail', '__return_false', 100 );

			// Image is not shown, but table column is still there, let's add some css to hide it
			add_action( 'wp_head', array( __CLASS__, 'hide_thumbnail_column_in_cart_with_css' ) );

		}

		// Images in Checkout
		if ( $show_images_in[ 'checkout' ] == 'on' ) {
			add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'add_thumbnail_to_checkout' ), 100, 3 );
		}

		// Images In Order
		if ( $show_images_in[ 'order' ] == 'on' ) {

			add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'add_thumbnail_to_order' ), 100, 3 );

			// Add and remove filter in invoice pdfs
			add_action( 'wp_wc_invoice_pdf_start_template', array( __CLASS__, 'remove_add_thumbnail_to_order_invoice_pdfs' ) );
			add_action( 'wp_wc_invoice_pdf_end_template', array( __CLASS__, 'undo_remove_add_thumbnail_to_order_invoice_pdfs' ) );
		}

		// Images in E-Mails
		add_action( 'woocommerce_email_header', array( __CLASS__, 'avoid_double_images_in_emails_header' ), 10, 2 );
		add_action( 'woocommerce_email_footer', array( __CLASS__, 'avoid_double_images_in_emails_footer' ), 10, 1 );
		if ( $show_images_in[ 'email' ] == 'on' ) {
			add_filter( 'woocommerce_email_order_items_args', array( __CLASS__, 'add_thumbnail_to_emails' ), 100, 3 );
		}

	}

	/**
	 * Avoid double images in invoice pdfs: remove filter
	 *
	 * @since 3.7.1
	 * @wp-hook wp_wc_invoice_pdf_start_template
	 * @return void
	 */
	public static function remove_add_thumbnail_to_order_invoice_pdfs() {
		remove_filter( 'woocommerce_order_item_name', array( __CLASS__, 'add_thumbnail_to_order' ), 100, 3 );
	}

	/**
	 * Avoid double images in invoice pdfs: add filter again
	 *
	 * @since 3.7.1
	 * @wp-hook wp_wc_invoice_pdf_end_template
	 * @return void
	 */
	public static function undo_remove_add_thumbnail_to_order_invoice_pdfs() {
		add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'add_thumbnail_to_order' ), 100, 3 );
	}

	/**
	 * Handle Product Images Emails, avoid item image in order item name
	 *
	 * @since 3.6.4
	 * @wp-hook woocommerce_email_header
	 * @param String $email_heading
	 * @param WC_Email
	 * @return void
	 */
	public static function avoid_double_images_in_emails_header( $email_heading, $email = false ) {

		if ( get_option( 'german_market_product_images_in_order', 'off' ) == 'on' ) {
			remove_filter( 'woocommerce_order_item_name', array( __CLASS__, 'add_thumbnail_to_order' ), 100, 3 );
		}

	}

	/**
	 * Handle Product Images Emails, avoid item image in order item name
	 *
	 * @since 3.6.4
	 * @wp-hook woocommerce_email_footer
	 * @param WC_Email
	 * @return void
	 */
	public static function avoid_double_images_in_emails_footer(  $email ) {

		if ( get_option( 'german_market_product_images_in_order', 'off' ) == 'on' ) {
			add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'add_thumbnail_to_order' ), 100, 3 );
		}

	}

	/**
	 * Handle Product Images Emails
	 *
	 * @since 3.6.4
	 * @wp-hook woocommerce_email_order_items_args
	 * @param Array $args
	 * @return Array
	 */
	public static function add_thumbnail_to_emails( $args ) {

		$args[ 'show_image' ] = true;
		return $args;
	}

	/**
	 * Handle Product Images Checkout
	 *
	 * @since 3.6.4
	 * @wp-hook woocommerce_order_item_name
	 * @param String $product_name
	 * @param WC_Order_Item_Product $item
	 * @param Boolean is_visible
	 * @return String
	 */
	public static function add_thumbnail_to_order( $product_name, $item, $is_visible = true ) {

		if ( ( ! is_checkout() ) && ( ! is_account_page() ) ) {
			return $product_name;
		}

		$image = '';
		
		if ( get_option( 'german_market_product_images_in_cart', 'on' ) == 'off' ) {
			remove_filter( 'woocommerce_cart_item_thumbnail', '__return_false', 100 );
		}

		$_product = $item->get_product();

		if ( $_product && $_product->exists() && WGM_Helper::method_exists( $_product, 'get_image' ) ) {

			$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $_product->is_visible() ? $_product->get_permalink( $item ) : '', $item, $item->get_order() );

			$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $item, false );

			ob_start();

				echo '<div class="german-market-product-image order">';

				if ( ! $product_permalink ) {
					echo wp_kses_post( $thumbnail );
				} else {
					printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
				}

				echo '</div>';

			$image = ob_get_clean();

		}

		if ( get_option( 'german_market_product_images_in_cart', 'on' ) == 'off' ) {
			add_filter( 'woocommerce_cart_item_thumbnail', '__return_false', 100 );
		}

		return apply_filters( 'add_thumbnail_to_order', $image . $product_name, $image, $product_name, $item );

	}

	/**
	 * Handle Product Images Checkout
	 *
	 * @since 3.6.4
	 * @wp-hook woocommerce_cart_item_name
	 * @param String $product_name
	 * @param Array $cart_item
	 * @param String cart_item_key
	 * @return String
	 */
	public static function add_thumbnail_to_checkout( $product_name, $cart_item, $cart_item_key ) {

		if ( ! is_checkout() ) {
			return $product_name;
		}

		if ( get_option( 'german_market_product_images_in_cart', 'on' ) == 'off' ) {
			remove_filter( 'woocommerce_cart_item_thumbnail', '__return_false', 100 );
		}

		// has to be order review
		$is_order_review = false;
		$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
		foreach ( $debug_backtrace as $elem ) {

			if ( $elem[ 'function' ] == 'woocommerce_order_review' ) {
				$is_order_review = true;
				break;
			}


		}

		// 2nd CO
		$second_checkout_page_id = get_option( WGM_Helper::get_wgm_option( 'check' ) );
		//WPML Support
		if( function_exists( 'icl_object_id' ) ) {
			$second_checkout_page_id = icl_object_id( $second_checkout_page_id, 'page', true );
		}

		if ( is_page( $second_checkout_page_id ) ) {
			$is_order_review = true;
		}

		if ( ! $is_order_review ) {
			return $product_name;
		}

		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( $_product && $_product->exists() && $cart_item['quantity'] ) {

			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

			ob_start();

				echo '<div class="german-market-product-image checkout">';

				if ( ! $product_permalink ) {
					echo wp_kses_post( $thumbnail );
				} else {
					printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
				}

				echo '</div>';

			$image = ob_get_clean();

		}

		if ( get_option( 'german_market_product_images_in_cart', 'on' ) == 'off' ) {
			add_filter( 'woocommerce_cart_item_thumbnail', '__return_false', 100 );
		}

		return apply_filters( 'wgm_product_add_thumbnail_to_checkout', $image . $product_name, $image, $product_name, $cart_item, $cart_item_key );

	}

	/**
	 * Add some CSS to hide thumbnail column in cart
	 *
	 * @since 3.6.4
	 * @wp-hook wp_head
	 * @return void
	 */
	public static function hide_thumbnail_column_in_cart_with_css() {

		if ( ! is_cart() ) {
			return;
		}

		if ( apply_filters( 'hide_thumbnail_column_in_cart_with_css', true ) ) {

			?>
			<style>
				table.shop_table.cart th.product-thumbnail, table.shop_table.cart td.product-thumbnail { border: none; width: 0; }
			</style>
			<?php

		}

	}

	/**
	 * @param array $types
	 * @wp-hook product_type_options
	 * @return array $tye
	 */
	public static function register_product_type(array $types){

		$types[ 'digital' ] = array(
				'id'            => '_digital',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Digital', 'woocommerce-german-market' ),
				'description'   => __( 'Only products with this marker will be treated as “digital” in the context of the EU Consumer Rights Directive from June 13, 2014.', 'woocommerce-german-market' ),
				'default'       => 'no'
			);

		return $types;
	}

	/**
	 * Adds Digital checkbox to variation meta box
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 * @wp-hook woocommerce_variation_options
	 */
	public static function add_variant_product_type( $loop, $variation_data, $variation ) {
		$_digital = get_post_meta( $variation->ID, '_digital', true );

		?>
		<label>
			<input type="checkbox" id="_digital" class="checkbox variable_is_digital" name="variable_is_digital[<?php echo $loop; ?>]" <?php checked( isset( $_digital ) ? $_digital : '', 'yes' ); ?> />
				<?php _e( 'Digital', 'woocommerce-german-market' ); ?>
				<a class="tips" data-tip="<?php esc_attr_e( 'Only products with this marker will be treated as “digital” in the context of the EU Consumer Rights Directive from June 13, 2014.', 'woocommerce-german-market' ); ?>" href="#">[?]</a>
			</label>

	<?php
	}

	/**
	 *  Save the digital setting for variations
	 * @param $var_id
	 * @wp-hook woocommerce_update_product_variation
	 * @wp-hook woocommerce_create_product_variation
	 *
	 */
	public static function save_variant_product_type( $var_id ){

		if( ! isset($_POST['variable_post_id'] ) ){
			return;
		}

		$variable_post_id   = $_POST['variable_post_id'];
		$max_loop           = max( array_keys( $_POST['variable_post_id'] ) );

		for ( $i = 0; $i <= $max_loop; $i ++ ) {

			if ( ! isset( $variable_post_id[ $i ] ) ) {
				continue;
			}

			$variable_is_virtual = isset( $_POST['variable_is_digital'] ) ? $_POST['variable_is_digital'] : array();

			$variation_id = absint( $variable_post_id[ $i ] );

			if( $variation_id == $var_id ){
				$is_digital = isset( $variable_is_virtual[ $i ] ) ? 'yes' : 'no';
				update_post_meta( $var_id, '_digital', $is_digital );
			}
		}
	}
}
