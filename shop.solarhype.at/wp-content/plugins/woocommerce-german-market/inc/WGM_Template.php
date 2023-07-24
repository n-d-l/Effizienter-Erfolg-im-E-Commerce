<?php

/**
 * Class with Template Snippet functions, Template Helper Functions
 * Output filtering Funktions of WooCommerce hooks
 *
 * @author jj, ap
 */
class WGM_Template {

	protected static $button_html;
	protected static $run_time_cache = array();

	/**
	 * Overloading Woocommerce with German Market templates
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 * @access public
	 * @static
	 * @author ap
	 * @return string the template
	 */
	public static function add_woocommerce_de_templates( $template, $template_name, $template_path ){

		$path = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'woocommerce' . DIRECTORY_SEPARATOR;
		$orignal_template = $template;

		// Only load our templates if they are nonexistent in the theme
		if ( file_exists( $path . $template_name ) && ! locate_template( array( WC()->template_path() . $template_name ) ) ) {
			$template = $path . $template_name;
		}

		if ( $template_name == 'checkout/form-checkout.php' && get_option( 'gm_force_checkout_template', 'off' ) == 'on' ) {
			$template = $path . $template_name;
		}

		if ( $template_name == 'checkout/terms.php' ) {
			if ( get_option( 'gm_force_term_template', 'on' ) == 'on' ) {
				$template = $path . $template_name;
			} else {
				$template = $orignal_template;
			}
		}

		if ( apply_filters( 'german_market_add_woocommerce_de_templates_force_original', false, $template_name ) ) {
			return $orignal_template;
		}

		return $template;
	}

	/**
	* Add Taxes in cart/cart.php if not german market template is used
	*
	* @wp-hook woocommerce_cart_item_subtotal
	* @param String $subtotal
	* @param Array $cart_item
	* @param String $cart_item_key
	* @return String
	**/
	public static function show_taxes_in_cart_theme_template( $subtotal, $cart_item, $cart_item_key ) {

		$_product     	= apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );

		if ( ! $_product->is_taxable() ){
			return $subtotal;
		}

		$_tax = new WC_Tax();

		if( ! is_object( WC()->customer ) ){
			$order = wc_get_order( get_the_ID() );
			$addr = $order->get_address();
			$country = $addr[ 'country' ];
			$state = $addr[ 'state' ];
			$postcode = $addr[ 'postcode' ];

		} else {
			list( $country, $state, $postcode, $city ) = WC()->customer->get_taxable_address();
		}

		$t = $_tax->find_rates( array(
			'country' 	=>  $country,
			'state' 	=> $state,
			'tax_class' => $_product->get_tax_class(),
			'postcode'	=> $postcode,
		) );

		// Setup.
		$tax_display        = get_option('woocommerce_tax_display_cart');
		$tax_amount         = wc_price( $cart_item[ 'line_subtotal_tax' ] );

		if ( ! empty( $t ) ) {
			$tax                = array_shift( $t );
			$tax_label          = apply_filters( 'wgm_translate_tax_label', $tax[ 'label' ] );
			$tax_decimals       = WGM_Helper::get_decimal_length( $tax[ 'rate' ] );
			$tax_rate_formatted = number_format_i18n( (float)$tax[ 'rate' ], $tax_decimals );
		} else {
			$tax                = array();
			$tax_label          = apply_filters( 'wgm_translate_tax_label', '' );
			$tax_decimals       = false;
			$tax_rate_formatted = '';
		}

		$tax_string = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );

		if ( apply_filters( 'gm_show_taxes_in_cart_theme_template_return_empty_string', false, $cart_item ) ) {
			return '';
		}

		return apply_filters( 'gm_cart_template_in_theme_show_taxes_markup', $subtotal . '<br class="wgm-break"/><span class="wgm-tax">' . $tax_string . '</span>', $subtotal, $tax_string, $cart_item );
	}

	/**
	 * Overloading WGM Teplates with templates form the theme if existent
	 * @param string $template_name  - tempalte name
	 * @param string $template_path  - path the templates in theme folder
	 * @param string $default_path  - path to templates in plugin folder
	 * @return mixed found template
	 * @author ap
	 * @since 2.3
	 */
	public static function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( !$template_path ) $template_path = 'woocommerce-german-market' . DIRECTORY_SEPARATOR;
		if ( !$default_path ) $default_path = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'woocommerce-german-market' . DIRECTORY_SEPARATOR;

		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);

		if ( ! $template )
			$template = $default_path . $template_name;

		return apply_filters('wgm_locate_template', $template, $template_name, $template_path);
	}

	/**
	 * @param string $template_name template name
	 * @param array $args variables for template scope
	 * @author ap
	 * @since 2.3
	 */
	public static function load_template( $template_name, array $args = array() ) {
		$tmpl = WGM_Template::locate_template( $template_name );

		extract( $args );
		include $tmpl;
	}

	public static function add_mwst_rate_to_product_item_init() {
		if ( ! has_filter( 'woocommerce_cart_item_subtotal', array( 'WGM_Template', 'add_mwst_rate_to_product_item' ) ) ) {
			add_filter( 'woocommerce_cart_item_subtotal', array( 'WGM_Template', 'add_mwst_rate_to_product_item' ), 10, 3 );
		}
	}

	/**
	 * adds german vat tax rate to every product
	 * @since 1.1.5beta
	 * @access public
	 * @hook woocommerce_checkout_item_subtotal
	 * @author ap
	 * @param float $amount
	 * @param array $item
	 * @param int $item_id
	 * @return string
	 */
	public static function add_mwst_rate_to_product_item( $amount, $item, $item_id ) {

		// Use Runtime Cache
		if ( isset( self::$run_time_cache[ 'add_mwst_rate_to_product_item_' . $item_id ] ) ) {
			return self::$run_time_cache[ 'add_mwst_rate_to_product_item_' . $item_id ];
		}

		$_product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );

		if ( ! $_product ) {
			$_product = $item[ 'data' ];
		}

		if ( apply_filters( 'gm_add_mwst_rate_to_product_item_return', false, $_product, $item ) ) {
			return $amount;
		}

		if ( ! WGM_Helper::method_exists( $_product, 'is_taxable' ) ) {
			return $amount;
		}

		if( ! $_product->is_taxable() ){
			return $amount;
		}

		$_tax = new WC_Tax();

		if( ! is_object( WC()->customer ) ){
			$order = wc_get_order( get_the_ID() );
			$addr = $order->get_address();
			$country = $addr[ 'country' ];
			$state = $addr[ 'state' ];
			$postcode = $addr[ 'postcode' ];

		} else {
			list( $country, $state, $postcode, $city ) = WC()->customer->get_taxable_address();
		}

		$t = $_tax->find_rates( array(
			'country' 	=>  $country,
			'state' 	=> $state,
			'tax_class' => $_product->get_tax_class(),
			'postcode'	=> $postcode,
		) );

		// Setup.
		$tax_display        = get_option( 'woocommerce_tax_display_cart' );

		if ( ! empty( $t ) ) {

			if ( count( $t ) > 1 ) {

				$complete_tax_string = '';
				$tax_data = $item[ 'line_tax_data' ];
				$tax_amount = '';

				foreach ( $t as $tax_rate_id => $tax ) {

					$tax                = array_shift( $t );
					$tax_label          = apply_filters( 'wgm_translate_tax_label', $tax[ 'label' ] );
					$tax_decimals       = WGM_Helper::get_decimal_length( $tax[ 'rate' ] );
					$tax_rate_formatted = number_format_i18n( (float)$tax[ 'rate' ], $tax_decimals );

					$tax_string = sprintf( '<span class="product-tax"> %s </span>',
											WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, wc_price( $tax_data[ 'subtotal' ][ $tax_rate_id ] ) )
								);

					$complete_tax_string .= $tax_string;

				}

				$item = sprintf(
					'%s <span class="product-tax"> %s </span>',
					$amount,
					$complete_tax_string
				);

			} else {

				$tax_amount 		= wc_price( $item[ 'line_subtotal_tax' ] );
				$tax                = array_shift( $t );
				$tax_label          = apply_filters( 'wgm_translate_tax_label', $tax[ 'label' ] );
				$tax_decimals       = WGM_Helper::get_decimal_length( $tax[ 'rate' ] );
				$tax_rate_formatted = number_format_i18n( (float)$tax[ 'rate' ], $tax_decimals );

				$tax_string = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );

				$item = sprintf(
					'%s <span class="product-tax"> %s </span>',
					$amount,
					$tax_string
				);

			}


		} else {

			$tax_amount 		= wc_price( $item[ 'line_subtotal_tax' ] );
			$tax                = array();
			$tax_label          = apply_filters( 'wgm_translate_tax_label', '' );
			$tax_decimals       = false;
			$tax_rate_formatted = '';

			$tax_string = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );

			$item = sprintf(
				'%s <span class="product-tax"> %s </span>',
				$amount,
				$tax_string
			);

		}

		$item = apply_filters(
			'wgm_checkout_add_tax_to_product_item',
			$item,
			$amount,
			$tax,
			$tax_display,
			$tax_label,
			$tax_amount,
			$tax_decimals,
			$tax_rate_formatted
			);

		/**
		 * Add tax line.
		 */
		self::$run_time_cache[ 'add_mwst_rate_to_product_item_' . $item_id ] = $item;
		return $item ;
	}


	/**
	 * adds german mwst tax rate to every product in line in order-details.php
	 * @since	1.1.5beta
	 * @access	public
	 * @author ap
	 * @hook 	woocommerce_checkout_item_subtotal
	 * @param float $subtotal
	 * @param array $item
	 * @param WC_Abstract_Order $order_obj
	 * @return string
	 */
	public static function add_mwst_rate_to_product_order_item( $subtotal, $item, $order_obj ) {

		$order_item = $item;

		// Compability for Product Bundles
		$item_id = false;
		if ( WGM_Helper::method_exists( $item, 'get_id' ) ) {
			$item_id = $item->get_id();
			if ( isset( self::$run_time_cache[ 'add_mwst_rate_to_product_order_item_' . $item->get_id() ] ) ) {
				if ( apply_filters( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', true ) ) {
					return self::$run_time_cache[ 'add_mwst_rate_to_product_order_item_' . $item->get_id() ];
				}
			}
		}

		// Little hack for WGM_Email (see WGM_Email::email_de_footer)
		if ( ! defined( 'WGM_MAIL' ) ) {
			define( 'WGM_MAIL', TRUE );
		}

		if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
			$_product = $item->get_product();
		} else {
			$_product = $order_obj->get_product_from_item( $item );
		}

		if ( empty( $_product ) || ! $_product->is_taxable() ) {
			return $subtotal;
		}

		// get tax
 		$tax = array(
 			'label' => '',
 			'rate' => 0.0
  		);

  		$item_data = $item->get_data();
  		$item_tax  = array();

  		if ( isset( $item_data[ 'taxes' ][ 'subtotal' ] ) ) {
  			$item_tax = $item_data[ 'taxes' ][ 'subtotal' ];
  		} else if ( isset( $item_data[ 'taxes' ][ 'total' ] ) ) {
  			$item_tax = $item_data[ 'taxes' ][ 'total' ];
  		}

  		$taxes = array();

  		if ( ! empty( $item_tax ) ) {

  			foreach ( $item_tax as $rate_id => $tax_amount ) {

  				$taxes[ $rate_id ] = array();

  				if ( empty( $tax_amount ) ) {
  					continue;
  				}

  				$taxes[ $rate_id ][ 'label' ] = WC_Tax::get_rate_label( $rate_id );
  				$taxes[ $rate_id ][ 'rate' ] = WGM_Tax::get_rate_percent_by_rate_id_and_order( $rate_id, $order_obj );
  				$taxes[ $rate_id ][ 'amount' ] = $tax_amount;

  			}

  		}

  		$tax_display = get_option( 'woocommerce_tax_display_cart' );
		$currency 			= $order_obj->get_currency();
		$wc_price_args 		= array( 'currency' => $currency );

		$complete_tax_string = '';

		foreach ( $taxes as $tax_rate => $tax ) {

			if ( empty( $tax ) ) {
				$tax_string = apply_filters( 'wgm_zero_tax_rate_message', '', 'product_tax_line' );
				if ( ! empty( $tax_string ) ) {
					$complete_tax_string .= $tax_string;
				}
				continue;
			}

			$tax_label          = apply_filters( 'wgm_translate_tax_label', $tax[ 'label' ] );
			$tax_amount         = wc_price( $tax[ 'amount' ], $wc_price_args );
			$tax_decimals       = WGM_Helper::get_decimal_length( $tax[ 'rate' ] );
			$tax_rate_formatted = number_format_i18n( (float) $tax[ 'rate' ], $tax_decimals );

			$tax_string = '';
			if ( $tax[ 'rate' ] > 0) {
				 $tax_string = WGM_Tax::get_excl_incl_tax_string( $tax_label, $tax_display, $tax_rate_formatted, $tax_amount );
			} else {
				$tax_string = apply_filters( 'wgm_zero_tax_rate_message', '', 'product_tax_line' );
			}

			$complete_tax_string .= sprintf( '<span class="product-tax"> %s </span>', $tax_string );

		}

		if ( apply_filters( 'german_market_avoid_called_twice_in_add_mwst_rate_to_product_order_item', false ) ) {
			if ( str_replace( $complete_tax_string, '', $subtotal ) != $subtotal ) {
				return $subtotal;
			}
		}

		$item = sprintf(
				'%s %s',
				$subtotal,
				$complete_tax_string
		);

		$item = apply_filters( 'wgm_template_add_mwst_rate_to_product_order_item', $item, $subtotal, $complete_tax_string, $_product, $order_item, $order_obj );

		if ( $item_id ) {
			self::$run_time_cache[ 'add_mwst_rate_to_product_order_item_' . $item_id ] = $item;
		}

		return $item;
	}

	/**
	* add filter for mwst rate to the label on cart
	*
	* @access public
	* @static
	* @return void
	* @uses add_filter
	*/
	public static function add_mwst_rate_to_cart_totals(){

		// add filter to enable rate at the mwst label
		add_filter( 'woocommerce_rate_label', array( 'WGM_Template', 'add_rate_to_label' ), 10, 2 );
	}


	/**
	* remove filter for mwst rate to the label on cart
	*
	* @access public
	* @static
	* @return void
	* @uses add_filter
	*/
	public static function remove_mwst_rate_from_cart_totals(){

		// remove filter from: this->add_mwst_rate_to_cart_totals
		remove_filter( 'woocommerce_rate_label', array( 'WGM_Template', 'add_rate_to_label' ), 10 );
	}


	/**
	 * Adds tax rate (percentage) to tax label (tax rate name) in cart and checkout.
	 *
	 * @wp-hook woocommerce_rate_label
	 *
	 * @param   string $rate_name
	 * @param   string $key
	 *
	 * @return  string $new_rate_name
	 */
	public static function add_rate_to_label( $rate_name, $key ) {
		global $wpdb;

		$new_rate_name  = $rate_name;

		if ( ! empty( $key ) ) {
			$rate_percent 	= WC_Tax::get_rate_percent( $key );
			$decimal_length = WGM_Helper::get_decimal_length( $rate_percent );
			$rate_percent   = number_format_i18n( (float)$rate_percent, $decimal_length );
			$new_rate_name  = sprintf( '%s (%s%%)', $new_rate_name, $rate_percent );
		}

		return $new_rate_name;
	}

	/**
	*  print tax hint after prices in single
	*
	* @author jj, ap
	* @hook woocommerce_single_product_summary
	* @uses remove_action, get_post_meta, get_the_ID, get_woocommerce_currency_symbol
	* @access public
	* @static
	* @return void
	*/
	public static function woocommerce_de_price_with_tax_hint_single( $call_function = '' ) {
		global $product;

		if ( $product instanceof WC_Product_Grouped ) {
			return;
		}

		if ( apply_filters( 'wgm_template_woocommerce_de_price_with_tax_hint_single_return', false, $product ) ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' );

		do_action( 'wgm_before_single_price' );

		$class_prefix = apply_filters( 'wgm_template_woocommerce_de_price_with_tax_hint_single_class_prefix', '', $call_function, $product );

		?>

		<?php if ( apply_filters( 'gm_show_itemprop', false ) ) { ?>

				<div class="legacy-itemprop-offers<?php echo $class_prefix; ?>" itemprop="offers" itemscope itemtype="http://schema.org/Offer">

		<?php } else { ?>

				<div class="legacy-itemprop-offers<?php echo $class_prefix; ?>">

		<?php } ?>

			<?php echo self::get_wgm_product_summary( $product, $call_function ); ?>

		</div>

		<?php

		if ( apply_filters( 'gm_compatibility_is_variable_wgm_template', true, $product ) ) {

			if ( is_a( $product, 'WC_Product_Variable' ) ) {
				WGM_Template::add_digital_product_prerequisits( $product );
			}

		}

	}

	/**
	* print tax hint after prices in loop
	*
	* @uses globals $product, remove_action
	* @access public
	* @hook woocommerce_after_shop_loop_item_title
	* @static
	* @author jj, ap
	* @return void
	*/
	public static function woocommerce_de_price_with_tax_hint_loop() {

		global $product;

		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );

		if ( has_action( 'custom_german_market_woocommerce_de_price_with_tax_hint_loop' ) ) {
			do_action( 'custom_german_market_woocommerce_de_price_with_tax_hint_loop', $product );
		}

		if ( is_a( $product, 'WC_Product_Grouped' ) ) {

			if ( apply_filters( 'gm_add_price_in_loop_for_grouped_products_again', true ) ) { // Used for Theme Compatibilities
				add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );
			}

			return;
		}

		if ( $product->get_price_html() ) :

				// close </a> tag in loop before german market product summary, otherwise you will get <a> tag in <a> tag
				if ( apply_filters( 'wgm_close_a_tag_before_wgm_product_summary_in_loop', true ) ) {
					?></a><?php
				}

				do_action( 'wgm_before_wgm_product_summary_in_loop', $product );

				echo self::get_wgm_product_summary( $product );

				do_action( 'wgm_after_wgm_product_summary_in_loop', $product );

				?>

		<?php endif;


	}

	public static function get_wgm_product_summary( $product = NULL, $call_function = '', $show_price = true ) {

		if ( is_null( $product ) || ! ( $product ) ) {
			$product = wc_get_product();

			// WC 2.7 beta 2
			if ( ! $product ) {
				$product = wc_get_product( get_the_ID() );
			}

		}

		if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
			return '';
		}

		$single = is_single();
		$hook   = ( $single ) ? 'single' : 'loop';

		// for related products, use 'loop'
		global $woocommerce_loop;

		if ( isset( $woocommerce_loop[ 'name' ] ) && ( $woocommerce_loop[ 'name' ] == 'related' || $woocommerce_loop[ 'name' ] == 'up-sells' ) ) {
			$hook = 'loop';
		}

		$hook = apply_filters( 'wgm_template_get_wgm_product_summary_choose_hook', $hook, $woocommerce_loop );

		// Use Runtime Cache
		if ( apply_filters( 'wgm_template_get_wgm_product_summary_use_run_time_cache', true ) ) {
			if ( isset( self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_' . $hook . $call_function . '_' . $product->get_id() ] ) ) {
				return self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_' . $hook . $call_function . '_' . $product->get_id() ];
			}
		}

		$output_parts = array();
		$output_parts = apply_filters( 'wgm_product_summary_parts', $output_parts, $product, $hook );
		$output_parts = WGM_Shipping::add_shipping_part( $output_parts, $product ); // this class inits the filter with less parameters, so call it manually
		$output_parts = apply_filters( 'wgm_product_summary_parts_after', $output_parts, $product, $hook );

		if ( isset( $output_parts[ 'price' ] ) ) {

			if ( $hook == 'loop' ) {
				$classes = ' ' . apply_filters( 'wgm_loop_price_class', '' );
			} else {
				$classes = ' ' . apply_filters( 'wgm_single_price_class', '' );
			}

			$output_parts[ 'price' ] = '<p class="' . trim( 'price ' . $classes ) . '">' . $output_parts[ 'price' ] . '</p>';

		}

		if ( ! $show_price && isset( $output_parts[ 'price' ] ) ) {
			unset( $output_parts[ 'price' ] );
		}

		$output_html  = implode( $output_parts );
		$output_html = apply_filters( 'wgm_product_summary_html', $output_html, $output_parts, $product, $hook );
		self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_' . $hook . $call_function . '_' . $product->get_id() ] = $output_html;

		return $output_html;
	}

	/**
	* Add German Market Price Data in WooCommerce Blocks
	*
	* @since 3.10.3.3
	* @wp-hook woocommerce_blocks_product_grid_item_html
	* @param String $markup
	* @param Array $data
	* @param WC_Product $product
	* @return String
	*/
	public static function german_market_woocommerce_blocks_price( $markup, $data, $product ) {
		$german_market_data = WGM_Template::get_wgm_product_summary( $product, 'woocommerce_blocks_product_grid_item_html', false );
		$markup = str_replace( "{$data->price}", "{$data->price}" . $german_market_data, $markup );
		return $markup;
	}

	public static function add_product_summary_price_part( $parts, WC_Product $product, $hook ) {
		//if ( $product instanceof WC_Product_Grouped ) {
		//	return;
		//}
		ob_start();

		do_action( 'wgm_before_' . $hook . '_price' );
		echo $product->get_price_html();
		do_action( 'wgm_after_' . $hook . '_price' );

		if ( $hook === 'single' && $product->get_type() != 'variable' ) {

			if ( apply_filters( 'gm_show_itemprop', false ) ) { ?>

				<meta itemprop="price" content="<?php echo esc_attr( $product->get_price() ); ?>" />
				<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
				<link itemprop="availability" href="http://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />

			<?php

			}
		}

		$parts[ 'price' ] = ob_get_clean();

		return $parts;
	}

	/**
	 * Adds taxes and other German Market product data to grouped products view
	 *
	 * @wp-hook woocommerce_get_stock_html (since 3.5.1)
	 * @param String $html
	 * @param WC_Prodduct $product
	 * @return String
	 */
	public static function add_grouped_product_info( $html, $product ) {

		$tax = self::get_wgm_product_summary( $product );
		return $html . $tax;
	}

	/**
	 * Adds price filter to grouped product view.
	 *
	 */
	public static function init_grouped_product_adaptions() {

		remove_filter( 'wgm_product_summary_parts', array( 'WGM_Template', 'add_product_summary_price_part' ), 0 );

		add_filter( 'woocommerce_get_stock_html', array('WGM_Template','add_grouped_product_info'), 10, 2 );

	}
	/**
	 * Output the Shippingform
	 * @access public
	 * @static
	 * @author jj, ap
	 * @return string the Shipping Form
	 */
	public static function second_checkout_form_shipping() {

		$woocommerce_checkout = WC()->checkout();

		if( WGM_Session::get('ship_to_different_address', 'first_checkout_post_array') != '1' ) return;

		if ( WGM_Template::should_be_shipping_to_shippingadress() ) :

			echo '<h3>' . __( 'Shipping Address', 'woocommerce-german-market' ) . '</h3>';

			echo'<table class="review_order_shipping">';
			$hidden_fields = array();

			foreach ( $woocommerce_checkout->checkout_fields[ 'shipping' ] as $key => $field ) :
				$out = WGM_Template::checkout_readonly_field( $key, $field );
				if ( is_array( $out ) ) {
					echo $out[0];
					$hidden_fields[] = $out[1];
				}
			endforeach;

			echo'</table>';
		endif;
	}


	/**
	 * @access public
	 * @static
	 * @author ap
	 * @since 2.3.5
	 */
	public static function shipping_address_check(){
		WGM_Session::add('ship_to_different_address', WC()->checkout()->get_value( 'ship_to_different_address' ), 'first_checkout_post_array');
	}

	/**
	 * @access public
	 * @static
	 * @author ap
	 * @return bool
	 */
	public static function should_be_shipping_to_shippingadress() {

		global $woocommerce;

		if ( $woocommerce->cart->needs_shipping() && ! WGM_Helper::ship_to_billing() || get_option( 'woocommerce_require_shipping_address' ) == 'yes' ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	* Output the billing information form
	* @access public
	* @static
	* @author jj, ap
	* @return string The billing information
	*/
	public static function second_checkout_form_billing() {

		// Get checkout object
		$checkout = WC()->checkout();

		if ( WGM_Helper::ship_to_billing() ) {
			echo '<h3>'. apply_filters( 'wgm_template_second_checkout_form_billing_h3_label', __( 'Billing & Shipping', 'woocommerce-german-market' ) ). '</h3>';
		} else {
			echo '<h3>'.  apply_filters( 'wgm_template_second_checkout_form_billing_h3_label', __( 'Billing Address', 'woocommerce-german-market' ) ) .'</h3>';
		}

		echo '<table class="review_order_billing">';
		$hidden_fields = array();

		// Billing Details
		foreach ( apply_filters( 'wgm_template_second_checkout_form_billing', $checkout->checkout_fields[ 'billing' ] )  as $key => $field ) {
			$out = WGM_Template::checkout_readonly_field( $key, $field );
			if ( is_array( $out ) ) {
				echo $out[ 0 ];
				$hidden_fields[] = $out[ 1 ];
			}
		}

		echo '</table>';

		// print the hidden fields
		echo implode( '', $hidden_fields );
	}

	/**
	* print hidden fields for given post array
	* determined by given field array
	*
	* @param array $post_array
	* @param array $fields_array
	* @static
	* @author jj
	* @return void
	*/
	public static function print_hidden_fields( $post_array, $fields_array ) {

		// Why does this function take 2 args when it is used only once and fed with
		// print_hidden_fields( $arr, array_keys( $arr ) ) ?
		// Why not take 1 array and iterate with foreach( $arr as $k => $v) ?
		foreach ( $fields_array as $field ) {
			if ( ! is_array( $post_array[ $field ] ) ) {
				echo '<input type="hidden" name="' . $field . '" value="' . $post_array[ $field ] . '" />';
			} else {
				self::array_to_input( $post_array[ $field ], $field );
			}
		}

	}

	/**
	 * create hidden input element for associative array
	 *
	 * @link https://gist.github.com/eric1234/5802030
	 *
	 * @param array  $array  associative array containing values
	 * @param string $prefix name attribute of input element
	 */

	public static function array_to_input($array, $prefix='') {
		if( (bool)count(array_filter(array_keys($array), 'is_string')) ) {
			foreach($array as $key => $value) {
				if( empty($prefix) ) {
					$name = $key;
				} else {
					$name = $prefix.'['.$key.']';
				}
				if( is_array($value) ) {
					self::array_to_input($value, $name);
				} else { ?>
					<input type="hidden" value="<?php echo $value ?>" name="<?php echo $name?>">
				<?php }
			}
		} else {
			$count=0;
			foreach($array as $item) {
				if( is_array($item) ) {
					self::array_to_input($item, $prefix.'['.$count.']');
				} else { ?>
					<input type="hidden" name="<?php echo $prefix ?>[]" value="<?php echo $item ?>">
				<?php }
				$count++;
			}
		}
	}


	/**
	* Change the button text, if on checkout page
	*
	* @param string $button_text
	* @static
	* @author jj, ap
	* @return string
	* @hook woocommerce_order_button_text
	*/
	public static function change_order_button_text( $button_text ) {

		// @todo do not touch button, when on pay for order page
		// @todo when refreshing payments,  session is expired, because cart is empty, see woocommerce-ajax.php

		// browser back button may not work, try this fix:
		$check_id = get_option( 'woocommerce_check_page_id' );
		if ( function_exists( 'icl_object_id' ) ) {
			$check_id = icl_object_id( $check_id );
		}
		$is_confirm_and_place_order_page = get_the_ID() == $check_id;

		if ( WGM_Session::is_set( 'woocommerce_de_in_first_checkout' ) || ( ! $is_confirm_and_place_order_page ) ) {

			if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'on' ) {
				$button_text = _x( 'Proceed', '1st checkout button text', 'woocommerce-german-market' );
			} else {
				$button_text_option = get_option( 'woocommerce_de_order_button_text', __( 'Place binding order', 'woocommerce-german-market' ) );
				$button_text 		= apply_filters( 'woocommerce_de_buy_button_text', $button_text_option, $button_text );
 			}

		} else {

			$button_text_option 	= get_option( 'woocommerce_de_order_button_text', __( 'Place binding order', 'woocommerce-german-market' ) );
			$button_text 			= apply_filters( 'woocommerce_de_buy_button_text', $button_text_option, $button_text );

		}

		return $button_text;
	}

	public static function add_cart_estimate_notice() {

		$show_estimate_disclaimer = ( get_option( 'woocommerce_de_estimate_cart', 'on' ) === 'on' );

		if ( $show_estimate_disclaimer ) {
			?>
			<p class="wc-cart-shipping-notice">
				<small><?php

						$option = get_option( 'woocommerce_de_estimate_cart_text', __( 'Note: Shipping and taxes are estimated and will be updated during checkout based on your billing and shipping information.',
						    'woocommerce-german-market' ) );

						echo nl2br( esc_attr( $option ) );

					?></small>
			</p>
			<?php
		}

	}

	/**
	* Adds the Shipping Time to the Product title
	*
	* @param string $item_name
	* @param array $item
	* @static
	* @author jj, ap
	* @return string
	* @hook woocommerce_order_product_title
	*/
	public static function add_delivery_time_to_product_title( $item_name, $item ) {

		if ( ! isset( $item[ 'deliverytime' ] ) ) {

			// since v.3.1.2, don't return yet, so dilvery time will be also added to manually added items
			if ( apply_filters( 'add_delivery_time_to_product_title_no_deliverytime', false, $item ) ) {
				return $item_name;
			}

		}

		// Show single price since v3.7.1
		$show_single_price = get_option( 'gm_show_single_price_of_order_items', 'on' ) == 'on';

		$shipping_time = self::get_delivery_time_string_by_term_id( $item );

		$product = null;

		if ( ! ( isset(  $item[ 'product_id' ] ) || isset( $item[ 'variation_id' ] ) ) ) {
			if ( (int) $item[ 'variation_id' ] ) {
				$product = wc_get_product( $item[ 'variation_id' ] );
			} else {
				$product = wc_get_product( $item[ 'product_id' ] );
			}
		}

		if ( empty( $shipping_time ) ) {

			// Compability for plugins that uses test $items with less keys and values
			if ( WGM_Helper::method_exists( $product, 'get_id' ) ) {
				return $item_name;
			}

			$shipping_time = self::get_deliverytime_string( $product );

		}

		// String fragments need to be brought into 1 whole string
		$start_comma = $show_single_price ? ', ' : '';
		$shipping_time_output = $start_comma . __( 'delivery time:', 'woocommerce-german-market' ) . ' ' . $shipping_time;

		// if product is out of stock, don't show delivery time
		if ( ! self::show_delivery_time_if_product_is_not_in_stock( $product ) ) {
			$shipping_time_output = apply_filters( 'german_market_delivery_time_if_product_is_not_in_stock', '', $product , $shipping_time_output, $start_comma );
		}

		$price = '';

		if ( $show_single_price ) {

			if ( WGM_Helper::method_exists( $item, 'get_quantity' ) && WGM_Helper::method_exists( $item, 'get_subtotal' ) ) {

				if ( $item->get_quantity() > 0 ) {
					if ( get_option( 'woocommerce_tax_display_cart', 'incl' ) == 'incl' ) {

						$price = ( $item->get_subtotal() + $item->get_subtotal_tax() ) / $item->get_quantity();
					} else {
						$price = ( $item->get_subtotal() ) / $item->get_quantity();
					}
				}

			} else if ( isset( $item[ 'line_subtotal' ] ) ) {

				if ( $item[ 'qty' ] > 0 ) {
					if ( get_option( 'woocommerce_tax_display_cart', 'incl' ) == 'incl' ) {
						$price = ( $item[ 'line_subtotal' ] + $item[ 'line_subtotal_tax' ] ) / $item[ 'qty' ];
					} else {
						$price = ( $item[ 'line_subtotal' ] ) / $item[ 'qty' ];
					}
				}

			} else {
				$price = $item[ 'price' ];
			}

			$price = apply_filters( 'german_market_show_single_price_filter_var_price', $price, $item );

			$wc_price_args = array();
			if ( is_object( $item ) && method_exists( $item, 'get_order_id' ) ) {
				$order = wc_get_order( $item->get_order_id() );
				if ( WGM_Helper::method_exists( $order, 'get_currency' ) ) {
					$wc_price_args[ 'currency' ] = $order->get_currency();
				}
			}

			if ( ! empty( $price ) ) {
				$each_string = __( 'each', 'woocommerce-german-market' ) . ' ' . wc_price( $price, $wc_price_args );
			} else {
				$each_string = '';
				$shipping_time_output = trim( substr( $shipping_time_output, 1 ) );
			}

			if ( ( intval( $item[ 'qty' ] == 1 ) && apply_filters( 'gm_dont_show_each_string_if_qty_is_one', true ) || apply_filters( 'gm_force_each_string_to_miss', false ) ) ) {
				$each_string = '';
				$shipping_time_output = trim( substr( $shipping_time_output, 1 ) );
			}

		} else {

			$each_string 			= '';
			$shipping_time_output 	= __( 'delivery time:', 'woocommerce-german-market' ) . ' ' . $shipping_time;

		}

		$shipping_time_output 	= apply_filters( 'wgm_shipping_time_product_string', $shipping_time_output, $shipping_time, $item );
		$each_string 			= apply_filters( 'woocommerce_de_additional_item_each_string', $each_string, $item );
		$return 				= $item_name;

		if ( ! empty( trim( $shipping_time_output . $each_string ) ) ) {
			if ( get_option( 'woocommerce_de_show_delivery_time_order_summary', 'on' ) == 'on' ) {
				$return = $item_name . apply_filters( 'woocommerce_de_additional_item_string',  ' (' . $each_string . $shipping_time_output . ') ' );
			} else {
				if ( $each_string != '' ) {
					$return = $item_name . apply_filters( 'woocommerce_de_additional_item_string', ' (' . $each_string . ') ' );
				} else {
					$return = $item_name;
				}
			}
		}

		// strip tags to avoid backend display errors, because return value is placed in title attributes and contains html markutp
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( is_object( $screen )  && isset( $screen->id ) && ( $screen->id == 'edit-shop_order' ) ) {
				$return = strip_tags( $return );
			}
		}

		return apply_filters( 'woocommerce_de_add_delivery_time_to_product_title', $return, $item_name, $item );
	}

	/**
	 * Add product info to order item
	 * @param int $order_id
	 * @param int $item_id
	 * @param WC_Product $product
	 * @param int $qty
	 * @param array $args
	 * @return void
	 * @wp-hook woocommerce_order_add_product
	 */
	public static function add_deliverytime_to_order_item( $item_id, $item, $order_id ){

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$product = $item->get_product();
			$deliverytime = apply_filters( 'add_deliverytime_to_order_item', self::get_term_id_from_product_meta( '_lieferzeit', $product ), $product );
			if ( ! empty( $deliverytime ) ) {
				wc_add_order_item_meta( $item_id, '_deliverytime', $deliverytime );
			}
		}
	}

	/**
	* show the delivery time in overview
	* @access public
	* @static
	* @author ap
	* @return void
	* @return mixed
	* @hook woocommerce_after_shop_loop_item
	*/
	public static function woocommerce_de_after_shop_loop_item() {

		// if this function is defined in template, use it (like in the original woocommerce)
		if ( ! function_exists( 'woocommerce_de_after_shop_loop_item' ) ) {
			WGM_Template::add_template_loop_shop();
		} else {
			woocommerce_de_after_shop_loop_item();
		}
	}

	/**
	* Remove Checkbox for terms in checkout page (not pay for order page)
	* @access public
	* @static
	* @hook woocommerce_checkout_show_terms
	* @param Boolean $bool
	* @return Boolean
	*/
	public static function remove_terms_from_checkout_page( $bool ) {

		if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
			$bool = false;
		}

		return $bool;
	}

	/**
	* Add redirected payment gateway host to allowed domains, cache first
	*
	* @since 3.11.0.1
	* @access public
	* @static
	* @hook woocommerce_payment_successful_result
	* @param Array $result
	* @return Array
	*/
	public static function validate_payment_result_redirect( $result ) {

		if ( isset( $result[ 'redirect' ] ) ) {

			if ( ! isset( self::$run_time_cache[ 'checkout_redirects' ] ) ) {
				self::$run_time_cache[ 'checkout_redirects' ] = array();
			}

			$url = parse_url( $result[ 'redirect' ] );
			if ( is_array( $url ) && isset( $url[ 'host' ] ) ) {
				if ( ! in_array( $url[ 'host' ], self::$run_time_cache[ 'checkout_redirects' ] ) ) {
					self::$run_time_cache[ 'checkout_redirects' ][] = $url[ 'host' ];
				}

			}

		}

		return $result;
	}

	/**
	* Add cached redirect url to allowed domains for wp_safe_redirect
	*
	* @since 3.11.0.1
	* @access public
	* @static
	* @hook allowed_redirect_hosts
	* @param Array $domains
	* @return Array
	*/
	public static function add_payment_result_to_allowed_hosts( $domains ) {

		if ( isset( self::$run_time_cache[ 'checkout_redirects' ] ) && ( ! empty( self::$run_time_cache[ 'checkout_redirects' ] ) ) ) {
			$domains = array_merge( $domains, self::$run_time_cache[ 'checkout_redirects' ] );
		}

		return $domains;
	}

	/**
	* Interupt checkout process after validation, to use the checkout site again, to fullfill
	* the german second checkout verify obligation
	* Since 3.0 this is only to redirect to second checkout page
	*
	* @access public
	* @static
	* @return void
	* @param array $posted $_POST array at hook position
	* @hook woocommerce_after_checkout_validation
	*/
	public static function do_de_checkout_after_validation ( $posted, $errors ) {

		if ( WGM_Session::is_set('woocommerce_de_in_first_checkout') ) {

			// validation for 1st checkout
			$error_count = 0;
			if ( isset ( $errors->errors ) ) {
				$error_count = count( $errors->errors );
			}

			$error_count = apply_filters( 'gm_checkout_validation_first_checkout', $error_count );

			if ( $error_count != 0 ) {
				return;
			}

			// reset woocommerce_de_in_first_checkout
			WGM_Session::remove('woocommerce_de_in_first_checkout');

			// save the $_POST variables into session, to save them during redirect
			$_POST[ 'order_comments' ] = htmlentities( stripcslashes( $_POST[ 'order_comments' ] ),
			                                           ENT_COMPAT | ENT_HTML401, 'UTF-8' );

			WGM_Session::add( 'first_checkout_post_array', $_POST );

			foreach ( $_POST as $field => $item ) {
				WGM_Session::add( $field, $item, 'first_checkout_post_array' );
			}

			if ( is_ajax() ) {
					echo json_encode( array(
								'result'   => 'success',
								'redirect' => WGM_Helper::get_check_url()
								) );
					exit;
			} else {
				wp_safe_redirect( WGM_Helper::get_check_url() );
				exit;
			}

		} else {

			// validation
			$has_virtual = false;
			$cart = WC()->cart->get_cart();
			$dcount = 0;
			foreach( $cart as $item ){

				do_action( 'german_market_before_id_to_check_is_digital', $item );

				$id_to_check_is_digital = empty( $item[ 'variation_id' ] ) ? $item['product_id'] : $item[ 'variation_id' ];

				if ( WGM_Helper::is_digital( $id_to_check_is_digital ) ) {
					$has_virtual = true;
					$dcount++;
				}

				do_action( 'german_market_after_id_to_check_is_digital', $item );

			}

			$only_digital = false;
			if( $dcount == count( $cart ) ) {
				$only_digital = true;
			}

			WGM_Session::add('only_digital', $only_digital, 'WGM_CHECKOUT');
			WGM_Session::add('has_digital', $has_virtual, 'WGM_CHECKOUT');

			$error_count = 0; // count error, not notices!
			if ( isset ( $errors->errors ) ) {
				$error_count = count( $errors->errors );
			}

			// Check Terms, Revocatio, Data Privacy
			remove_filter( 'woocommerce_checkout_show_terms', array( 'WGM_Template', 'remove_terms_from_checkout_page' ) );
			if ( ( get_option( 'german_market_checkbox_1_tac_pd_rp_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'on' ) ) {
				if ( ! isset( $_POST[ 'terms' ] ) ) {
					wc_add_notice( self::get_terms_error_text(), 'error' );
					$error_count++;
				}
			}
			add_filter( 'woocommerce_checkout_show_terms', array( 'WGM_Template', 'remove_terms_from_checkout_page' ) );

			// Validation for "Digital Content"
			if ( $has_virtual) {

				if ( ( get_option( 'german_market_checkbox_2_digital_content_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on' ) ) {

					if ( ! isset( $_POST[ 'widerruf-digital-acknowledgement' ] ) ) {

						$default_notice_text = __( 'Please confirm the waiver for your rights of revocation regarding digital content.', 'woocommerce-german-market' );
						$notice_text = get_option( 'woocommerce_de_checkbox_error_text_digital_content', $default_notice_text );
						$notice_text = apply_filters( 'wgm_checkout_validation_revocation_acknowledgement_notice', $notice_text ); // legacy
						wc_add_notice( $notice_text, 'error' );
						$error_count++;
					}

				}
			}

			// Validation for "Age Rating"
			if ( ( get_option( 'german_market_checkbox_age_rating_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'on' ) && ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) ) {

				$needs_age_rating_validation = isset( $_REQUEST[ 'age-rating-exists' ] );

				if ( $needs_age_rating_validation ) {

					if ( ! isset( $_POST[ 'age-rating' ] ) ) {

						$default_notice_text = __( 'You have to confirm that you are at least [age] years of age.', 'woocommerce-german-market' );
						$notice_text = str_replace( '[age]', $_POST[ 'age-rating-exists' ], get_option( 'german_market_checkbox_age_rating_error_text', $default_notice_text ) );
						wc_add_notice( $notice_text, 'error' );
						$error_count++;

					}

				}

			}

			// Validation for "Send Personal Data to Shipping Service Provider"
			if ( ( get_option( 'german_market_checkbox_3_shipping_service_provider_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on' ) && ( apply_filters( 'german_market_checkbox_3_shipping_service_provider_validation', true ) ) ) {

				if ( WC()->cart->needs_shipping() ) {

					if ( ! self::is_cart_local_pickup() ) {

						if ( ! isset( $_POST[ 'shipping-service-provider' ] ) ) {

							$default_notice_text = __( 'You have to agree that your personal data is send to the shipping service provider.', 'woocommerce-german-market' );
							$notice_text = get_option( 'german_market_checkbox_3_shipping_service_provider_error_text', $default_notice_text );
							wc_add_notice( $notice_text, 'error' );
							$error_count++;

						}

					}

				}

			}

			// Validation for custom checkbox
			if ( ( get_option( 'german_market_checkbox_4_custom_activation', 'off' ) == 'on' ) && ( get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'on' ) ) {

				if ( ! isset( $_POST[ 'german-market-custom-checkbox' ] ) ) {

					$notice_text = get_option( 'german_market_checkbox_4_custom_error_text', '' );
					wc_add_notice( $notice_text, 'error' );
					$error_count++;

				}

			}

			$error_count = apply_filters( 'gm_checkout_validation_fields_second_checkout', $error_count );

			if ( $error_count != 0 ) {

				// Redirect (stay) on second checkout page (confirm and place order)
				wp_safe_redirect( get_permalink( get_option( WGM_Helper::get_wgm_option( 'check' ) ) ) );
				exit();

			} else {

				if ( WGM_Session::is_set( 'first_checkout_post_array' ) ) {
					WGM_Session::remove( 'first_checkout_post_array' );
				}
			}
		}
	}

	/**
	* Checkout Validation without 2nd Checkout page
	*
	* @access public
	* @since GM v3.2
	* @static
	* @return void
	* @param array $posted $_POST array at hook position
	* @hook woocommerce_after_checkout_validation
	*/
	public static function checkout_after_validation_without_sec_checkout( $data, $errors ) {

		if ( apply_filters( 'german_market_checkout_after_validation_without_sec_checkout_return', false, $data, $errors, $_REQUEST ) ) {
			return;
		}

		$is_digital_cart_or_order = self::is_digital_cart_or_order();
		$review_order = '';

		// Terms - Change Warning Text
		$errors_array = $errors->errors;
		if ( isset( $errors->errors ) && isset( $errors->errors[ 'terms' ][ 0 ] ) ) {
			$errors->errors[ 'terms' ][ 0 ] = self::get_terms_error_text();

			// Remove Terms Warning if Opt-In is deactivated
			if ( get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'off' ) {
				$errors->remove( 'terms' );
			}

		}

		// Validation for "Digital Content"
		if ( $is_digital_cart_or_order == 'mixed' || $is_digital_cart_or_order == 'only_digital' ) {

			if ( ( get_option( 'german_market_checkbox_2_digital_content_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on' ) ) {

				if ( isset( $data[ 'widerruf-digital-acknowledgement' ] ) ) {
					$data_widerruf = $data[ 'widerruf-digital-acknowledgement' ];
				} else if ( isset( $_POST[ 'widerruf-digital-acknowledgement' ] ) ) {
					$data_widerruf = $_POST[ 'widerruf-digital-acknowledgement' ];
				} else {
					$data_widerruf = '';
				}

				if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) && empty( $data_widerruf ) ) {

					$default_notice_text = __( 'Please confirm the waiver for your rights of revocation regarding digital content.', 'woocommerce-german-market' );
					$notice_text = get_option( 'woocommerce_de_checkbox_error_text_digital_content', $default_notice_text );
					$notice_text = apply_filters( 'wgm_checkout_validation_revocation_acknowledgement_notice', $notice_text ); // legacy
					$errors->add( 'german_market_checkbox_2_digital_content', $notice_text );

				}

			}
		}

		// Validation for "Age Rating"
		if ( ( get_option( 'german_market_checkbox_age_rating_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'on' ) && ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) ) {

			$needs_age_rating_validation = isset( $data[ 'age-rating-exists' ] ) || isset( $_POST[ 'age-rating-exists' ] );

			if ( $needs_age_rating_validation ) {

				if ( isset( $data[ 'age-rating' ] ) ) {
					$data_age = $data[ 'age-rating' ];
				} else if ( isset( $_POST[ 'age-rating' ] ) ) {
					$data_age = $_POST[ 'age-rating' ];
				} else {
					$data_age = '';
				}

				if ( empty( $data_age ) ) {

					$age = isset( $_POST[ 'age-rating-exists' ] ) ? $_POST[ 'age-rating-exists' ] : '';
					if ( empty( $age ) ) {
						$age = isset( $data[ 'age-rating-exists' ] ) ? $data[ 'age-rating-exists' ] : '';
					}

					$default_notice_text = __( 'You have to confirm that you are at least [age] years of age.', 'woocommerce-german-market' );
					$notice_text = str_replace( '[age]', $age, get_option( 'german_market_checkbox_age_rating_error_text', $default_notice_text ) );
					$errors->add( 'german_market_age_rating', $notice_text );

				}

			}

		}

		// Validation for "Send Personal Data to Shipping Service Provider"
		if ( ( get_option( 'german_market_checkbox_3_shipping_service_provider_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on' ) && ( apply_filters( 'german_market_checkbox_3_shipping_service_provider_validation', true ) ) ) {

			if ( WC()->cart->needs_shipping() ) {

				if ( ! self::is_cart_local_pickup() ) {

					if ( isset( $data[ 'shipping-service-provider' ] ) ) {
						$data_shipping_service = $data[ 'shipping-service-provider' ];
					} else if ( isset( $_POST[ 'shipping-service-provider' ] ) ) {
						$data_shipping_service = $_POST[ 'shipping-service-provider' ];
					} else {
						$data_shipping_service = '';
					}

					if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) && empty( $data_shipping_service ) ) {

						$default_notice_text = __( 'You have to agree that your personal data is send to the shipping service provider.', 'woocommerce-german-market' );
						$notice_text = get_option( 'german_market_checkbox_3_shipping_service_provider_error_text', $default_notice_text );
						$errors->add( 'german_market_checkbox_3_shipping_service_provider', $notice_text );

					}

				}

			}

		}

		// Validation for custom checkbox
			if ( ( get_option( 'german_market_checkbox_4_custom_activation', 'off' ) == 'on' ) && ( get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'on' ) ) {

				if ( isset( $data[ 'german-market-custom-checkbox' ] ) ) {
					$data_custom_checkbox = $data[ 'german-market-custom-checkbox' ];
				} else if ( isset( $_POST[ 'german-market-custom-checkbox' ] ) ) {
					$data_custom_checkbox = $_POST[ 'german-market-custom-checkbox' ];
				} else {
					$data_custom_checkbox = '';
				}

				if ( empty( $data_custom_checkbox ) ) {

					$notice_text = get_option( 'german_market_checkbox_4_custom_error_text', '' );
					$errors->add( 'german_market_checkbox_4_custom', $notice_text );

				}

			}

		$more_errors = apply_filters( 'gm_checkout_validation_fields', 0, $errors, $data );

	}

	/**
	* Validation for "pay order" page
	* Checks whether user agreed to revocation policy / revocation policy for digital products
	* See WooCommerce method WC_Form_Handler::pay_action
	* See WGM method WGM_Template::do_de_checkout_after_validation
	*
	* @access public
	* @static
	* @return void
	* @hook wp
	*/
	public static function pay_order_validation_of_revocation_policy() {

		global $wp;

		if ( isset( $_POST['woocommerce_pay'] ) ) {

			$nonce_value = wc_get_var( $_REQUEST['woocommerce-pay-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
				return;
			}

			// Pay for existing order
			$order_key  = $_GET['key'];
			$order_id   = absint( $wp->query_vars['order-pay'] );
			$order      = wc_get_order( $order_id );
			$notices 	= 0;

			if ( $order->get_id() == $order_id && $order->get_order_key() == $order_key && $order->needs_payment() ) {

				// Preperation
				$has_virtual = false;
				$cart = $order->get_items();
				$dcount = 0;

				foreach ( $cart as $item ) {

					do_action( 'german_market_before_id_to_check_is_digital', $item );

					$id_to_check_is_digital = empty( $item[ 'variation_id' ] ) ? $item['product_id'] : $item[ 'variation_id' ];

					if ( WGM_Helper::is_digital( $id_to_check_is_digital ) ) {
						$has_virtual = true;
						$dcount++;
					}

					do_action( 'german_market_after_id_to_check_is_digital', $item );

				}

				$only_digital = false;
				if ( $dcount == count( $cart ) ) {
					$only_digital = true;
				}

				// Terms
				self::change_warning_text_for_terms_and_condition_checkbox();

				// Validation for "Digital Content"
				if ( $has_virtual) {

					if ( ( get_option( 'german_market_checkbox_2_digital_content_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on' ) ) {

						if ( ! isset( $_POST[ 'widerruf-digital-acknowledgement' ] ) ) {

							$default_notice_text = __( 'Please confirm the waiver for your rights of revocation regarding digital content.', 'woocommerce-german-market' );
							$notice_text = get_option( 'woocommerce_de_checkbox_error_text_digital_content', $default_notice_text );
							$notice_text = apply_filters( 'wgm_checkout_validation_revocation_acknowledgement_notice', $notice_text ); // legacy
							wc_add_notice( $notice_text, 'error' );
							$notices++;
						}

					}
				}

				// Validation for "Age Rating"
				if ( ( get_option( 'german_market_checkbox_age_rating_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'on' ) && ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) ) {

					$needs_age_rating_validation = isset( $_REQUEST[ 'age-rating-exists' ] );

					if ( $needs_age_rating_validation ) {

						if ( ! isset( $_POST[ 'age-rating' ] ) ) {

							$default_notice_text = __( 'You have to confirm that you are at least [age] years of age.', 'woocommerce-german-market' );
							$notice_text = str_replace( '[age]', $_POST[ 'age-rating-exists' ], get_option( 'german_market_checkbox_age_rating_error_text', $default_notice_text ) );
							wc_add_notice( $notice_text, 'error' );
							$notices++;

						}

					}

				}

				// Validation for "Send Personal Data to Shipping Service Provider"
				if ( ( get_option( 'german_market_checkbox_3_shipping_service_provider_activation', 'on' ) == 'on' ) && ( get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on' ) && ( apply_filters( 'german_market_checkbox_3_shipping_service_provider_validation', true ) ) ) {

					$needs_shipping = ( ! empty( $order->get_shipping_method() ) );

					if ( $needs_shipping ) {

						if ( ! self::is_order_local_pickup( $order ) ) {

							if ( ! isset( $_POST[ 'shipping-service-provider' ] ) ) {

								$default_notice_text = __( 'You have to agree that your personal data is send to the shipping service provider.', 'woocommerce-german-market' );
								$notice_text = get_option( 'german_market_checkbox_3_shipping_service_provider_error_text', $default_notice_text );
								wc_add_notice( $notice_text, 'error' );
								$notices++;

							}

						}

					}

				}

				// Validation for custom checkbox
				if ( ( get_option( 'german_market_checkbox_4_custom_activation', 'off' ) == 'on' ) && ( get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'on' ) ) {

					if ( ! isset( $_POST[ 'german-market-custom-checkbox' ] ) ) {

						$notice_text = get_option( 'german_market_checkbox_4_custom_error_text', '' );
						wc_add_notice( $notice_text, 'error' );
						$notices++;

					}

				}

			}

			$notices = apply_filters( 'gm_checkout_validation_fields_pay_for_order', $notices );

			if ( $notices > 0 ) {
				wp_safe_redirect( wp_get_referer() );
			}

			self::checkbox_logging( $order_id, array(), $order );

		}

	}

	/**
	* Rename the warning if user doesn't click the "terms and condictions" checkbox on pay-order page
	*
	* @since 3.6
	* @access public
	* @static
	* @return void
	*/
	public static function change_warning_text_for_terms_and_condition_checkbox() {

		// Terms
		$notices = wc_get_notices();

		foreach ( $notices as $type => $notices_type_notices ) {

			if ( $type === 'error' ) {

				$orignal_notice = __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' );

				foreach ( $notices_type_notices as $key => $notice ) {

					if ( is_array( $notices[ 'error' ][ $key ] ) && isset( $notices[ 'error' ][ $key ][ 'notice' ] ) ) {

						if ( $notices[ 'error' ][ $key ][ 'notice' ] == $orignal_notice ) {
							$notices[ 'error' ][ $key ][ 'notice' ] = self::get_terms_error_text();
						}
					}
				}
			}
		}

		wc_set_notices( $notices );

	}

	/**
	* This is just to rename the warning if user doesn't click the "terms and condictions" checkbox on pay-order page
	*
	* @since 3.6
	* @return void
	* @hook wp
	*/
	public static function pay_order_validation_of_terms_and_conditions() {

		global $wp;

		if ( isset( $_POST['woocommerce_pay'] ) ) {

			$nonce_value = wc_get_var( $_REQUEST['woocommerce-pay-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
				return;
			}

			self::change_warning_text_for_terms_and_condition_checkbox();

		}

	}

	/**
	* Checks if cart consists of only digital produtct, mixed products or no digital products
	*
	* @access public
	* @since 3.6
	* @static
	* @return string 'not_digital', 'only_digital', 'mixed'
	*/
	public static function is_digital_cart_or_order( $order = false ) {

		$has_virtual = FALSE;
		$dcount      = 0;

		if ( $order ) {
			$cart = $order->get_items();

		} else {

			// Is that the order pay page?
			if ( is_wc_endpoint_url( 'order-pay' ) ) {

				global $wp;
				$order_key  = $_GET['key'];
				$order_id   = absint( $wp->query_vars['order-pay'] );
				$order      = wc_get_order( $order_id );
				$cart 		= $order->get_items();

			} else {
				$cart = WC()->cart->get_cart();
			}

		}

		foreach ( $cart as $item ) {

			do_action( 'german_market_before_id_to_check_is_digital', $item );

			if ( empty( $item[ 'variation_id' ] ) ) {
				$product = wc_get_product( $item[ 'product_id' ] );
			} else {
				$product = wc_get_product( $item[ 'variation_id' ] );
			}

			if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
				do_action( 'german_market_after_id_to_check_is_digital', $item );
				continue;
			}

			if ( WGM_Helper::is_digital( $product->get_id() ) ) {
				$has_virtual = TRUE;
				$dcount ++;
			}

			do_action( 'german_market_after_id_to_check_is_digital', $item );
		}

		$only_digital = FALSE;
		if ( $dcount == count( $cart ) ) {
			$only_digital = TRUE;
		}

		$return = 'not_digital';

		if ( $only_digital ) {
			$return = 'only_digital';
		} else if ( $has_virtual ) {
			$return = 'mixed';
		}

		return $return;

	}

	/**
	* Replace Placeholders throug page links
	*
	* @access public
	* @since 3.6.2
	* @static
	* @param String $text_with_placeholders
	* @return String
	*/
	public static function replace_placeholders_terms_privacy_revocation( $text_with_placeholders ) {

		$target = apply_filters( 'german_market_replace_placeholders_terms_privacy_revocation_target_in_a_tag', '_blank' );

		// revocation
		$link_revocation = get_permalink( get_option( WGM_Helper::get_wgm_option( 'widerruf' ) ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$link_revocation = get_permalink( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'widerruf' ) ) ) );
		}

		$link_revocation 	= apply_filters( 'german_market_replace_placeholders_checkout_link_revocation', $link_revocation );
		$link_revocation_a 	= sprintf( '<a href="%s" class="wgm-widerruf" target="%s">', esc_url( $link_revocation ), $target );

		// revocation digital
		$link_revocation_digital = get_permalink( get_option( WGM_Helper::get_wgm_option( 'widerruf_fuer_digitale_medien' ) ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$link_revocation_digital = get_permalink( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'widerruf_fuer_digitale_medien' ) ) ) );
		}

		$link_revocation_digital 	= apply_filters( 'german_market_replace_placeholders_checkout_link_revocation_digital', $link_revocation_digital );
		$link_revocation_digital_a 	= sprintf( '<a href="%s" class="wgm-widerruf" target="%s">', esc_url( $link_revocation_digital ), $target );

		// terms and conditions
		if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
			$link_terms = get_permalink( wc_terms_and_conditions_page_id() );
			if ( function_exists( 'icl_object_id' ) ) {
				$link_terms = get_permalink( icl_object_id( wc_terms_and_conditions_page_id() ) );
			}
		} else {
			$link_terms = get_permalink( get_option( 'woocommerce_terms_page_id' ) );
			if ( function_exists( 'icl_object_id' ) ) {
				$link_terms = get_permalink( icl_object_id( get_option( 'woocommerce_terms_page_id' ) ) );
			}
		}

		$link_terms   = apply_filters( 'german_market_replace_placeholders_checkout_link_terms', $link_terms );
		$link_terms_a = sprintf( '<a href="%s" class="wgm-terms" target="%s">', esc_url( $link_terms ), $target );

		// privacy
		$link_privacy = get_permalink( get_option( 'woocommerce_datenschutz_page_id' ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$link_privacy = get_permalink( icl_object_id( get_option( 'woocommerce_datenschutz_page_id' ) ) );
		}

		$link_privacy 	= apply_filters( 'german_market_replace_placeholders_checkout_link_privacy', $link_privacy );
		$link_privacy_a = sprintf( '<a href="%s" class="wgm-privacy" target="%s">', esc_url( $link_privacy ), $target );

		// build return array
		$text_with_placeholders = str_replace(

				array(	'[link-terms]',
						'[link-privacy]',
						'[link-revocation]',
						'[link-revocation-digital]',
						'[/link-terms]',
						'[/link-privacy]',
						'[/link-revocation]',
						'[/link-revocation-digital]',
				),
				array(	$link_terms_a,
						$link_privacy_a,
						$link_revocation_a,
						$link_revocation_digital_a,
						'</a>',
						'</a>',
						'</a>',
						'</a>',
				),

				$text_with_placeholders
		);

		return $text_with_placeholders;

	}

	/**
	* Gets the correct text for first checkbox (terms and coditions, prvivacy police, recovation policy)
	*
	* @access public
	* @since 3.6
	* @static
	* @return String
	*/
	public static function get_terms_text() {

		$is_digital_cart_or_order = self::is_digital_cart_or_order();

		if ( $is_digital_cart_or_order == 'only_digital' ) {

			$default_text = __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_text_digital_only_digital', $default_text );

		} else if ( $is_digital_cart_or_order == 'mixed' ) {

			$default_text = __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy], the [link-revocation]revocation policy[/link-revocation] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_text_mix_digital', $default_text );

		} else {

			$default_text = __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_text_no_digital', $default_text );

		}

		$text = apply_filters( 'german_market_checkout_checkbox_text_markup', self::replace_placeholders_terms_privacy_revocation( $text ) );

		return $text;
	}

	/**
	* Gets the correct text for first checkbox error (terms and coditions, prvivacy police, recovation policy)
	*
	* @access public
	* @since 3.6
	* @static
	* @return String
	*/
	public static function get_terms_error_text( $order = false ) {

		$is_digital_cart_or_order = self::is_digital_cart_or_order( $order );

		if ( $is_digital_cart_or_order == 'only_digital' ) {

			$default_text = __( 'You must accept our Terms & Conditions, privacy policy and revocation policy for digital content.', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_error_text_digital_only_digital', $default_text );

		} else if ( $is_digital_cart_or_order == 'mixed' ) {

			$default_text = __( 'You must accept our Terms & Conditions, privacy policy, revocation policy and revocation policy for digital content.', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_error_text_mix_digital', $default_text );

		} else {

			$default_text = __( 'You must accept our Terms & Conditions, privacy policy and revocation policy.', 'woocommerce-german-market' );
			$text = get_option( 'german_market_checkbox_1_tac_pd_rp_error_text_no_digital', $default_text );

		}

		return $text;
	}

	/**
	* Return Class for <p> of checkboxes
	*
	* @access public
	* @static
	* @since 	3.7
	* @param 	String $field_name
	* @param 	Mixed $checkout_validated
	* @param 	Array $post_data
	* @return 	String
	*/
	public static function get_validation_p_class( $field_name, $checkout_validated, $post_data = array() ) {

		if ( $checkout_validated == 'maybe' ) {

			$checkout_validated = false;

			$post_data = array();
			if ( isset( $_REQUEST[ 'post_data' ] ) ) {

				parse_str( $_REQUEST[ 'post_data' ], $post_data );

				if ( isset( $post_data[ '_wp_http_referer' ] ) ) {

					if ( str_replace( 'wc-ajax=update_order_review', '', $post_data[ '_wp_http_referer' ] ) != $post_data[ '_wp_http_referer' ] ) {
						$checkout_validated = apply_filters( 'german_market_get_validation_p_class_ajax_true', true );
					}

				}

			}

		}

		$class = 'validate-required';

		if ( $checkout_validated ) {

			$required = false;

			if ( $field_name == 'german-market-custom-checkbox' ) {
				$required = get_option( 'german_market_checkbox_4_custom_activation', 'off' ) == 'on';
			} else if ( $field_name == 'shipping-service-provider' ) {
				$required = get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on';
			} else if ( $field_name == 'widerruf-digital-acknowledgement' ) {
				$required = get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on';
			} else if ( $field_name == 'terms' ) {
				$required = get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'on';
			} else if ( $field_name == 'age-rating' ) {
				$required = get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on';
			}

			$required = apply_filters( 'german_market_checkout_checkbox_is_required', $required, $field_name, $checkout_validated, $post_data );

			if ( $required ) {

				if ( ! isset( $post_data[ $field_name ] ) ) {
					$class .= ' woocommerce-invalid woocommerce-invalid-required-field';
				}

			}

		}

		return $class;
	}

	/**
	* add the german disclaimer to checkout
	* @access public
	* @static
	* @author ap
	* @return string review order
	* @hook woocommerce_review_order_before_submit
	*/
	public static function add_review_order() {

		do_action( 'woocommerce_de_add_review_order' );

		$is_digital_cart_or_order = self::is_digital_cart_or_order();
		$review_order = '';

		$checkout_validated = false;

		// Has checkout already been validated
		$post_data = array();
		if ( isset( $_REQUEST[ 'post_data' ] ) ) {

			parse_str( $_REQUEST[ 'post_data' ], $post_data );

			if ( isset( $post_data[ '_wp_http_referer' ] ) ) {

				if ( str_replace( 'wc-ajax=update_order_review', '', $post_data[ '_wp_http_referer' ] ) != $post_data[ '_wp_http_referer' ] ) {
					$checkout_validated = true;
				}

			}

		}

		// Digital Notice
		if ( $is_digital_cart_or_order == 'mixed' || $is_digital_cart_or_order == 'only_digital' ) {

			if ( get_option( 'german_market_checkbox_2_digital_content_activation', 'on' ) != 'off' ) {

				// revocation for digital produtcs: special acknowledgement re:start of delivery
				$digital_content_text_default = __( 'For digital content: You explicitly agree that we continue with the execution of our contract before expiration of the revocation period. You hereby also declare you are aware of the fact that you lose your right of revocation with this agreement.', 'woocommerce-german-market' );
				$digital_content_text = get_option( 'woocommerce_de_checkbox_text_digital_content', $digital_content_text_default );

				// If Opt-In is needed
				if ( get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on' || get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'optional' ) {

					$checked = isset( $_POST[ 'widerruf-digital-acknowledgement' ] ) ? checked( $_POST[ 'widerruf-digital-acknowledgement' ], 'on', FALSE ) : '';
					if ( $checked == '' ) {
						if ( isset( $_POST[ 'post_data' ] ) ) {
							parse_str( $_REQUEST[ 'post_data' ], $post_data );
							$checked = isset( $post_data[ 'widerruf-digital-acknowledgement' ] ) ? checked( $post_data[ 'widerruf-digital-acknowledgement' ], 'on', FALSE ) : '';

						}
					}

					$p_class  = '';
					$required = get_option( 'german_market_checkbox_2_digital_content_opt_in', 'on' ) == 'on' ? '&nbsp;<span class="required">*</span>' : '';
					if ( ! empty ( $required ) ) {
						$p_class = self::get_validation_p_class( 'widerruf-digital-acknowledgement', $checkout_validated, $post_data );
					}

					$review_order .= sprintf(
						'<p class="german-market-checkbox-p form-row ' . $p_class . '">
							<label for="widerruf-digital-acknowledgement" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
								<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" %s name="widerruf-digital-acknowledgement" id="widerruf-digital-acknowledgement" />
								<span class="widerruf-digital-acknowledgement-checkbox-text">%s</span>' . $required .'
							</label>
					</p>',
						$checked,
						apply_filters(
							'wgm_checkout_digital_revocation_text',
							$digital_content_text
						)
					);

				} else {

					// Without Opt-In -> No checkbox needed
					$review_order .= sprintf(
						'<p class="german-market-checkbox-p form-row">
						<label for="widerruf-digital-acknowledgement" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><span class="widerruf-digital-acknowledgement-checkbox-text">%s</span></label>
					</p>',
						apply_filters(
							'wgm_checkout_digital_revocation_text',
							$digital_content_text
						)
					);

				}

				if ( is_wc_endpoint_url( 'order-pay' ) ) {
					add_action( 'after_woocommerce_pay', array( 'WGM_Template', 'digital_items_notice' ) );
				} else {
					add_action( 'woocommerce_de_review_order_after_submit', array( 'WGM_Template', 'add_digital_items_notice' ), 1, 100 );
				}

			}
		}

		// Age Rating
		if ( ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) && ( get_option( 'german_market_checkbox_age_rating_activation', 'on' ) == 'on' ) ) {

			$age_of_cart_or_order = WGM_Age_Rating::get_age_rating_of_cart_or_order();

			if ( $age_of_cart_or_order > 0 ) {

				$age_rating_default_text = __( 'I confirm that I am at least [age] years of age.', 'woocommerce-german-market' );
				$age_rating_text = get_option( 'german_market_checkbox_age_rating_text', $age_rating_default_text );
				$age_rating_text = str_replace( '[age]', $age_of_cart_or_order, $age_rating_text );

				// If Opt-In is needed
				if ( get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'on' || get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'optional' ) {

					$checked = isset( $_POST[ 'age-rating' ] ) ? checked( $_POST[ 'age-rating' ], 'on', FALSE ) : '';
					if ( $checked == '' ) {
						if ( isset( $_POST[ 'post_data' ] ) ) {
							parse_str( $_REQUEST[ 'post_data' ], $post_data );
							$checked = isset( $post_data[ 'age-rating' ] ) ? checked( $post_data[ 'age-rating' ], 'on', FALSE ) : '';

						}
					}

					$p_class 	= '';
					$required 	= get_option( 'german_market_checkbox_age_rating_opt_in', 'on' ) == 'on' ? '&nbsp;<span class="required">*</span>' : '';
					if ( ! empty ( $required ) ) {
						$p_class = self::get_validation_p_class( 'age-rating', $checkout_validated, $post_data );
					}

					$review_order .= sprintf(
						'<p class="german-market-checkbox-p form-row ' . $p_class . '"' . ' id="p-age-rating">
							<label for="age-rating" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
								<input type="hidden" name="age-rating-exists" id="age-rating-exists" value="%s"/>
								<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" %s name="age-rating" id="age-rating" />
								<span class="age-rating-checkbox-text">%s</span>' . $required .'
							</label>
						</p>',
						$age_of_cart_or_order,
						$checked,
						$age_rating_text
					);

				}  else {

					// Without Opt-In -> No checkbox needed
					$review_order .= sprintf(
						'<p class="german-market-checkbox-p form-row">
						<label for="age-rating" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><span class="age-rating-checkbox-text">%s</span></label>
					</p>',
						$age_rating_text
					);

				}

			}

		}

		// Send Personal Data to Shipping Service Provider
		if ( get_option( 'german_market_checkbox_3_shipping_service_provider_activation', 'on' ) == 'on' ) {

			if ( is_wc_endpoint_url( 'order-pay' ) ) {

				global $wp;
				$order_key  = $_GET['key'];
				$order_id   = absint( $wp->query_vars['order-pay'] );
				$cart      = wc_get_order( $order_id );
				$needs_shipping = ( ! empty( $cart->get_shipping_method() ) );
				$is_local_pickup = self::is_order_local_pickup( $cart );

			} else {

				$cart = WC()->cart;
				$is_local_pickup = self::is_cart_local_pickup();
				$needs_shipping = $cart->needs_shipping();

			}

			$style = '';

			if ( ! $needs_shipping ) {
				$style = 'display: none;';
			} else {
				if ( $is_local_pickup ) {
					$style = 'display: none;';
				}
			}

			$style = apply_filters( 'german_market_add_review_order_style_for_shipping_service_provider_checkbox', $style, $needs_shipping, $is_local_pickup );

			$shipping_service_provider_default_text = __( 'I agree that my personal data is send to the shipping service provider.', 'woocommerce-german-market' );
			$shipping_service_provider_text = get_option( 'german_market_checkbox_3_shipping_service_provider_text', $shipping_service_provider_default_text );

			// If Opt-In is needed
			if ( get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on' || get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'optional' ) {

				$checked = isset( $_POST[ 'shipping-service-provider' ] ) ? checked( $_POST[ 'shipping-service-provider' ], 'on', FALSE ) : '';
				if ( $checked == '' ) {
					if ( isset( $_POST[ 'post_data' ] ) ) {
						parse_str( $_REQUEST[ 'post_data' ], $post_data );
						$checked = isset( $post_data[ 'shipping-service-provider' ] ) ? checked( $post_data[ 'shipping-service-provider' ], 'on', FALSE ) : '';

					}
				}

				$p_class 	= '';
				$required 	= get_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' ) == 'on' ? '&nbsp;<span class="required">*</span>' : '';
				if ( ! empty( $required ) ) {
					$p_class 	= self::get_validation_p_class( 'shipping-service-provider', $checkout_validated, $post_data );
				}

				$review_order .= sprintf(
					'<p class="german-market-checkbox-p form-row ' . $p_class . '" style="' . $style . '" id="p-shipping-service-provider">
						<label for="shipping-service-provider" class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox">
							<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" %s name="shipping-service-provider" id="shipping-service-provider" />
							<span class="shipping-service-provider-checkbox-text">%s</span>' . $required . '
						</label>
					</p>',
					$checked,
					$shipping_service_provider_text
				);

			} else {

				$review_order .= sprintf(
					'<p class="german-market-checkbox-p form-row">
					<label for="shipping-service-provider" class="checkbox"><span class="shipping-service-provider-checkbox-text woocommerce-form__label woocommerce-form__label-for-checkbox">%s</span></label>
				</p>',
					$shipping_service_provider_text
				);

			}

		}

		// Custom Checkbox
		if ( get_option( 'german_market_checkbox_4_custom_activation', 'off' ) == 'on' ) {

			$custom_text = get_option( 'german_market_checkbox_4_custom_text', '' );

			// If Opt-In is needed
			if ( get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'on' || get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'optional' ) {

				$checked = isset( $_POST[ 'german-market-custom-checkbox' ] ) ? checked( $_POST[ 'german-market-custom-checkbox' ], 'on', FALSE ) : '';
				if ( $checked == '' ) {
					if ( isset( $_POST[ 'post_data' ] ) ) {
						parse_str( $_REQUEST[ 'post_data' ], $post_data );
						$checked = isset( $post_data[ 'german-market-custom-checkbox' ] ) ? checked( $post_data[ 'german-market-custom-checkbox' ], 'on', FALSE ) : '';

					}
				}

				$p_class 	= '';
				$required 	= get_option( 'german_market_checkbox_4_custom_opt_in', 'on' ) == 'on' ? '&nbsp;<span class="required">*</span>' : '';
				if ( ! empty( $required ) ) {
					$p_class = self::get_validation_p_class( 'shipping-service-provider', $checkout_validated, $post_data );
				}

				$review_order .= sprintf(
					'<p class="german-market-checkbox-p form-row ' . $p_class . '">
						<label for="german-market-custom-checkbox" class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox ">
							<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" %s name="german-market-custom-checkbox" id="german-market-custom-checkbox" />
							<span class="german-market-custom-checkbox-text">%s</span>' . $required .'
						</label>
				</p>',
					$checked,
					self::replace_placeholders_terms_privacy_revocation( $custom_text )
				);

			} else {

				$review_order .= sprintf(
					'<p class="german-market-checkbox-p form-rows">
					<label for="german-market-custom-checkbox" class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox "><span class="german-market-custom-checkbox-text">%s</span></label>
				</p>',
					self::replace_placeholders_terms_privacy_revocation( $custom_text )
				);

			}

		}

		echo apply_filters( 'woocommerce_de_review_order_after_submit', $review_order );

	}

	/**
	* Checkout Checkbox Loggin
	*
	* @since 3.8.2
	* @wp-hook woocommerce_checkout_order_processed
	* @param Integer $order_id
	* @param Array $posted_data
	* @param WC_Order $order
	* @return void
	**/
	public static function checkbox_logging( $order_id, $posted_data, $order ) {

		if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
			$private_order_note_start = apply_filters( 'german_market_checkbox_logginbg_private_order_note_start', __( 'The customer has checked the following checkboxes during checkout:', 'woocommerce-german-market' ) . '<br />' );
		} else {
			$private_order_note_start = apply_filters( 'german_market_checkbox_logginbg_private_order_note_start', __( 'The customer has checked the following checkboxes on "Pay for Order" page:', 'woocommerce-german-market' ) . '<br />' );
		}

		$checkboxes_texts = array();
		$pre_symbol = apply_filters( 'german_market_checkbox_logging_pre_symbol', ' - ' );

		// terms
		if ( isset( $_REQUEST[ 'terms' ] ) ) {
			$terms_text = self::get_terms_text( $order );
			$checkboxes_texts[ 'terms' ] = $pre_symbol . strip_tags( $terms_text );
		}

		// Digital Notice
		if ( isset( $_REQUEST[ 'widerruf-digital-acknowledgement' ] ) ) {
			$digital_content_text_default = __( 'For digital content: You explicitly agree that we continue with the execution of our contract before expiration of the revocation period. You hereby also declare you are aware of the fact that you lose your right of revocation with this agreement.', 'woocommerce-german-market' );
			$digital_content_text = get_option( 'woocommerce_de_checkbox_text_digital_content', $digital_content_text_default );
			$checkboxes_texts[] = $pre_symbol . strip_tags( $digital_content_text );
		}

		// Age Rating
		if ( isset( $_REQUEST[ 'age-rating' ] ) ) {

			$age_of_cart_or_order = WGM_Age_Rating::get_age_rating_of_cart_or_order( $order );

			if ( $age_of_cart_or_order > 0 ) {
				$age_rating_default_text = __( 'I confirm that I am at least [age] years of age.', 'woocommerce-german-market' );
				$age_rating_text = get_option( 'german_market_checkbox_age_rating_text', $age_rating_default_text );
				$age_rating_text = str_replace( '[age]', $age_of_cart_or_order, $age_rating_text );
				$checkboxes_texts[ 'age-rating' ] = $pre_symbol . strip_tags( $age_rating_text );
			}
		}

		// Shipping Provider
		if ( isset( $_REQUEST[ 'shipping-service-provider' ] ) ) {
			$shipping_service_provider_default_text = __( 'I agree that my personal data is send to the shipping service provider.', 'woocommerce-german-market' );
			$shipping_service_provider_text = get_option( 'german_market_checkbox_3_shipping_service_provider_text', $shipping_service_provider_default_text );
			$checkboxes_texts[ 'shipping-service-provider' ] = $pre_symbol . strip_tags( $shipping_service_provider_text );
		}

		// Custom Checkbox
		if ( isset( $_REQUEST[ 'german-market-custom-checkbox' ] ) ) {
			$custom_text = get_option( 'german_market_checkbox_4_custom_text', '' );
			$custom_text = self::replace_placeholders_terms_privacy_revocation( $custom_text );
			$checkboxes_texts[ 'german-market-custom-checkbox' ] = $pre_symbol . strip_tags( $custom_text );
		}

		$checkboxes_texts = apply_filters( 'german_market_checkbox_logging_checbkox_texts_array', $checkboxes_texts, $pre_symbol, $posted_data, $order );

		if ( ! empty( $checkboxes_texts ) ) {

			$private_order_note = $private_order_note_start;
			$private_order_note.= apply_filters( 'german_market_checkbox_logging_checbkox_texts_string', implode( '<br />', $checkboxes_texts ), $checkboxes_texts );

			$order->add_order_note( $private_order_note, apply_filters( 'german_market_checkbox_logging_is_customer_note', 0 ), apply_filters( 'german_market_checkbox_logging_added_by_user', false ) );

			do_action( 'german_market_checkbox_logging_after_order_note_added', $order, $checkboxes_texts );

		}

	}

	/**
	* Get digitial item notice
	*
	* @return String
	**/
	public static function get_digital_item_notice() {
		$default_text = __( 'Notice: Digital content are products not being delivered on any physical medium (e.g. software downloads, e-books etc.).', 'woocommerce-german-market' );
		$text = get_option( 'woocommerce_de_checkbox_text_digital_content_notice', $default_text );

		return sprintf(
			'<span class="wgm-digital-checkout-notice">%s</span>',
			$text
		);
	}

	/**
	* Echo digitial item notice
	*
	* @return void
	**/
	public static function digital_items_notice() {

		echo self::get_digital_item_notice();

	}

	/**
	* Add digital item notice after checkboxes in checkout
	*
	* @wp-hook woocommerce_de_review_order_after_submit
	* @param String $review_order
	* @return String
	**/
	public static function add_digital_items_notice( $review_order ) {
		return $review_order .= self::get_digital_item_notice();
	}

	/**
	* add the hidden field and set the woocommerce_de_in_first_checkout
	* @access public
	* @static
	* @author ap
	* @hook woocommerce_review_order_before_submit
	*/
	public static function add_wgm_checkout_session() {
		// set update_totals, to generate the second checkout site
		if ( ! WGM_Session::is_set( 'woocommerce_de_in_first_checkout' ) ) {
			WGM_Session::add( 'woocommerce_de_in_first_checkout', true );
		}
	}

	/**
	 * Adds the selected sale label to variable products
	 *
	 * @wp-hook woocommerce_get_price_html
	 *
	 * @param String $html
	 * @param WC_Product $product
	 * @return string
	 */
	public static function add_sale_label_to_variable_products( $html, $product = null ) {

		$prices = $product->get_variation_prices( true );

		$min_reg_price = current( $prices['regular_price'] );
		$max_reg_price = end( $prices['regular_price'] );

		if ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
			$html = self::add_sale_label_to_price( $html, $product, false );
		}

		return $html;
	}

	/**
	 * Adds the selected sale label to the sale price
	 *
	 * @wp-hook woocommerce_get_price_html
	 *
	 * @param String $html
	 * @param WC_Product $product
	 * @return string
	 */
	public static function add_sale_label_to_price( $html, $product = null, $hide_for_variable_product = true ) {

		if ( ! $product ) {
			global $product;
		}

		if ( ! ( is_object( $product ) && method_exists( $product, 'is_on_sale' ) ) ) {
			return $html;
		}

		if ( ! $product->is_on_sale() ){
			return $html;
		}

		if ( 'variable' === $product->get_type() && $hide_for_variable_product ) {
			return $html;
		}

		$is_product = is_product();

		$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
		foreach ( $debug_backtrace as $elem ) {
			if ( $elem[ 'function' ] == 'woocommerce_de_price_with_tax_hint_loop' ) {
				$is_product = false;
				break;
			}
		}

		if ( $is_product ) {

			if ( get_option( 'woocommerce_de_show_sale_label_product_page' ) != 'on' ) {
				return $html;
			}


		} else {

			// in loop
			if ( get_option( 'woocommerce_de_show_sale_label_overview', 'off' ) != 'on' ) {
				return $html;
			}

		}


		$term_id = self::get_term_id_from_product_meta( '_sale_label', $product );
		$term_id = apply_filters( 'woocommerce_de_sale_label_term_id_for_frontend', $term_id, $product );

		if ( $term_id == - 2 ) {
			return $html;
		}
		if ( (int) $term_id == - 1 || empty( $term_id ) ) {
			$term_id = get_option( WGM_Helper::get_wgm_option( 'global_sale_label' ) );
		}
		$label_term = get_term( $term_id, 'product_sale_labels' );
		if ( is_wp_error( $label_term ) || ! isset( $label_term ) ) {
			$label_string = '';
		} else {
			$label_string = $label_term->name;
		}

		if ( ! empty( $label_string ) ) {
			$html = '<span class="wgm-sale-label">' . $label_string . '</span> ' . $html;
		}

		return $html;
	}

	/**
	* Show shipping time and shipping costs
	* @access public
	* @static
	* @author jj, ap
	 * @param WC_Product $product
	* @return void
	* @hook woocommerce_single_product_summary
	*/
	public static function add_template_loop_shop( $product = NULL, $delivery_time_label_overwrite = false ) {

		// Init
		if ( ! is_a( $product, 'WC_Product_Variable' ) ) {
			$label_string = self::get_deliverytime_string( $product );
		} else {
			$label_string = '';
		}

		$lieferzeit_output = '';

		$delivery_time_label = __( 'Delivery Time:', 'woocommerce-german-market' );
		$delivery_time_label = apply_filters( 'woocommerce_de_delivery_time_label_shop', $delivery_time_label );

		if ( false !== $delivery_time_label_overwrite ) {
			$delivery_time_label = $delivery_time_label_overwrite;
		}

		// If the product is a product variation, check if each variation has the same delivery time
		// => if not, do not display delivery time!
		// Add "add_filter( 'woocommerce_de_use_delivery_time_of_variable_product', '__return_true' );" to your functions.php to use the delivery time of the variable product (parent product)
		if ( is_a( $product, 'WC_Product_Variable' ) ) {

			if ( ! apply_filters( 'woocommerce_de_use_delivery_time_of_variable_product', false ) ) {

				if (  apply_filters( 'woocommerce_de_avoid_check_same_delivery_time_show_parent', false ) ) {
					$label_string = '';
				} else {
					$label_string = self::get_variable_data_quick( $product, 'delivery_time' );
				}

				if ( ! empty( $label_string ) ) {
					$lieferzeit_output = apply_filters( 'wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string );
				} else {
					$lieferzeit_output == '';
				}

			} else {

				// use delivery time of partent product (the variable product)
				$lieferzeit_output = apply_filters( 'wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string );

			}

		} else {
			$lieferzeit_output = apply_filters( 'wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string );
		}

		// if product is out of stock, don't show delivery time
		if ( ! self::show_delivery_time_if_product_is_not_in_stock( $product ) ) {
			$lieferzeit_output = apply_filters( 'gm_delivery_time_message_if_out_of_stock', '', $product );
		}

		// Output delivery time
		if ( ! empty( $lieferzeit_output ) ) {

			$lieferzeit_and_markup = '<div class="wgm-info shipping_de shipping_de_string ' . 'delivery-time-' . sanitize_title( $label_string ) . '">
				<small>
					<span>' . $lieferzeit_output . '</span>
				</small>
			</div>';

			echo apply_filters( 'gm_lieferzeit_output_lieferzeit_and_markup', $lieferzeit_and_markup, $lieferzeit_output, $product );

		}

	} // end function

	/**
	 * Retrieves the delivery time string from its term ID
	 *
	 * @param WC_Product $product
	 * @return string|void
	 */
	public static function get_deliverytime_string( $product ) {

		$term_id = self::get_term_id_from_product_meta( '_lieferzeit', $product );

		if ( intval( $term_id ) == - 1 || empty( $term_id ) || intval( $term_id ) == 0 ) {
			$term_id = intval( get_option( WGM_Helper::get_wgm_option( 'global_lieferzeit' ) ) );
		}

		$label_term = NULL;

		if ( $term_id > 0 ) {
			if ( isset( self::$run_time_cache[ 'term_id_' . $term_id ] ) ) {
				$label_term = self::$run_time_cache[ 'term_id_' . $term_id ];
			} else {
				$label_term = get_term( $term_id, 'product_delivery_times' );
				self::$run_time_cache[ 'term_id_' . $term_id ] = $label_term;
			}
		}

		$label_term = apply_filters( 'woocommerce_de_get_deliverytime_label_term', $label_term, $product );
		if (  ( ! isset( $label_term->name ) ) || is_wp_error( $label_term ) ) {
			$label_string = __( 'not specified', 'woocommerce-german-market' );
		} else {
			$label_string = $label_term->name;
		}

		return apply_filters( 'woocommerce_de_get_deliverytime_string_label_string', $label_string, $product );
	}

	/**
	 * get shipping time for order item by saved meta
	 *
	 * @param WC_Order_Item_Meta_Product $item
	 * @return Bollean
	 */
	public static function get_delivery_time_string_by_term_id( $item ) {

		$label_string = '';

		if ( WGM_Helper::method_exists( $item, 'get_meta' ) ) {

			$term_id = $item->get_meta( '_deliverytime' );

			if ( intval( $term_id ) == - 1 || empty( $term_id ) || intval( $term_id ) == 0 ) {
				$term_id = intval( get_option( WGM_Helper::get_wgm_option( 'global_lieferzeit' ) ) );
			}

			$label_term = NULL;

			if ( $term_id > 0 ) {
				if ( isset( self::$run_time_cache[ 'term_id_' . $term_id ] ) ) {
					$label_term = self::$run_time_cache[ 'term_id_' . $term_id ];
				} else {
					$label_term = get_term( $term_id, 'product_delivery_times' );
					self::$run_time_cache[ 'term_id_' . $term_id ] = $label_term;
				}
			}

			$label_term = apply_filters( 'woocommerce_de_get_deliverytime_label_term_by_term', $label_term, $item );
			if (  ( ! isset( $label_term->name ) ) || is_wp_error( $label_term ) ) {
				$label_string = __( 'not specified', 'woocommerce-german-market' );
			} else {
				$label_string = $label_term->name;
			}

		}

		return apply_filters( 'woocommerce_de_get_deliverytime_string_label_string_by_term', $label_string, $item );
	}

	/**
	 * If the product is of of stock, do we want to show delivery time?
	 *
	 * @param WC_Product $product
	 * @return Bollean
	 */
	public static function show_delivery_time_if_product_is_not_in_stock( $product ) {

		$return = true;

		if ( ! is_admin() ) {

			if ( ( $product ) && ( ! $product->is_in_stock() ) ) {
				if ( apply_filters( 'woocommerce_de_do_not_show_delivery_time_if_out_of_stock', true, $product ) ) {
					$return = false;
				}
			}

		}

		return $return;

	}

	/**
	 * Retrieves a term ID from a given product meta key.
	 * Recursively fetches the parent term ID from a variation if not set
	 *
	 * @param $product
	 * @return int
	 */
	public static function get_term_id_from_product_meta( $meta_key, $product = NULL ) {

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $meta_key;
		}

		$variation = FALSE;
		if ( ! ( $product instanceof WC_Product ) ) {
			if ( ! isset ( $GLOBALS['post'] ) ) { // if something went wrong
				return '';
			}
			$product = wc_get_product();
			if ( ! $product || is_null( $product ) ) {
				return '';
			}
			$product_id = $product->get_id();
		} else {
			$variation = ( $product instanceof WC_Product_Variation );
			if ( $variation ) {
				$product_id = $product->get_id();
			} else {
				$product_id = $product->get_id();

			}
		}
		$data = get_post_meta( $product_id, $meta_key, TRUE );

		// If data = default value or "use the default" or is not set
		if ( $variation && ( intval( $data ) == - 1 || intval( $data == 0 ) || empty( $data ) ) ) {

			/**
			 * Use 'same as parent' when nothing is set
			 */
			$parent_product = wc_get_product( $product->get_parent_id() );
			$lieferzeit = self::get_term_id_from_product_meta( $meta_key, $parent_product );

		} else {
			$lieferzeit = $data;
		}

		return $lieferzeit;
	}
	/**
	*  add shipping costs and and discalmer to cart before the buttons
	* @access public
	* @static
	* @author jj, ap
	* @return void
	* @hook woocommerce_widget_shopping_cart_before_buttons
	*/
	public static function add_shopping_cart() {

		if( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_disclaimer_cart' ) ) == 'off' )
				return;
		?>

		<p class="jde_hint">
				<?php echo WGM_Template::disclaimer_line(); ?>
			</p>
		<?php
	}

	/**
	* add shipping costs and and discalmer to cart
	* @access public
	* @static
	* @author jj, ap
	* @return void
	* @hook woocommerce_cart_contents
	*/
	public static function add_shop_table_cart() {

		if( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_disclaimer_cart' ) ) == 'off' )
			return;

		?>
		<tr class="jde_hint">
			<td colspan="<?php echo apply_filters( 'wgm_colspan_add_shop_table_cart', WGM_Tax::is_kur() ? 6 : 7 ); ?>" class="actions">
				<?php echo WGM_Template::disclaimer_line(); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Returns the Small Trading Exemption string based on the Shop location
	 *
	 * @param bool $country @depcrcated
	 * @return String
	 */
	public static function get_ste_string( $country = FALSE ) {
		$str = get_option( 'gm_small_trading_exemption_notice', self::get_default_ste_string() );
		return apply_filters( 'woocommerce_de_small_business_regulation_text', $str );
	}

	/**
	 * Returns the default Small Trading Exemption string based on the Shop location
	 *
	 * @since 3.5
	 * @return String
	 */
	public static function get_default_ste_string() {

		$country = get_option( 'woocommerce_default_country' );

		switch ( $country ) {
			case 'DE':
				$str = apply_filters( 'woocommerce_de_small_business_regulation_text',
				                      __( 'VAT exempted according to UStG §19',
				                          'woocommerce-german-market' ) );
				break;
			case 'AT':
				$str = apply_filters( 'woocommerce_de_small_business_regulation_text',
				                      __( 'VAT exempted according to UStG §6',
				                          'woocommerce-german-market' ) );
				break;
			default:
				$str = '';
		}

		return $str;

	}

	/**
	 * Returns the Small Trading Exemption string based on the Shop location
	 *
	 * @param bool $country
	 *
	 * @return mixed|void
	 */
	public static function get_ste_string_invoice( $country = FALSE, $order = false ) {
		$str = self::get_ste_string();
		return apply_filters( 'woocommerce_de_small_business_regulation_text_for_invoice', $str, $order );
	}

	/**
	* Outputs readonly checkout fields
	*
	* @author jj
	* @access public
	* @static
	* @param string $key key of field
	* @param array $args	contains a list of args for showing the field, merged with defaults (below
	* @return string
	*/
	public static function checkout_readonly_field( $key, $args = array() ) {

		WC()->checkout();

		$defaults = array(
			'type'        => 'input',
			'name'        => '',
			'label'       => '',
			'placeholder' => '',
			'required'    => FALSE,
			'class'       => array(),
			'label_class' => array(),
			'rel'         => '',
			'return'      => TRUE
		);

		$args = wp_parse_args( $args, $defaults );

		$field = '';

		if ( ! WGM_Session::is_set( $key, 'first_checkout_post_array' ) ) {
			return FALSE;
		}

		$value = WGM_Session::get( $key, 'first_checkout_post_array' );

		if ( empty( $value ) ) {
			return FALSE;
		}

		switch ( $args[ 'type' ] ) {
			case "textarea" :
				$field  = '<span class="wgm-field-label">' . $args[ 'label' ] . '</span>' . '<span class="wgm-break"></span>' . $value;
				$hidden = sprintf( '<input type="hidden" name="%s" value="%s" /> ', $key, $value );
				break;
			case 'country':
				$countries = WC()->countries->countries;
				if ( isset( $countries[ $value ] ) ) {
					$display_value = $countries[ $value ];
				} else {
					$display_value = $value;
				}
				$field  = '<tr><td><span class="wgm-field-label">' . $args[ 'label' ] . '</span></td><td>' . stripcslashes( $display_value ) . '</td></tr>';
				$hidden = sprintf( '<input type="hidden" name="%s" value="%s" /> ', $key, $value );
				break;
			case 'state':

				$display_value = $value;

				if ( isset( $args[ 'country' ] ) ) {
					$states_of_country = WC()->countries->get_states( $args[ 'country' ] );
					if ( isset( $states_of_country[ $display_value ] ) ) {
						$display_value = $states_of_country[ $display_value ];
					}
				}

				$field  = '<tr><td><span class="wgm-field-label">' . $args[ 'label' ] . '</span></td><td>' . stripcslashes( $display_value ) . '</td></tr>';
				$hidden = sprintf( '<input type="hidden" name="%s" value="%s" /> ', $key, $value );
				break;
			default :
				$field  = '<tr><td><span class="wgm-field-label">' . $args[ 'label' ] . '</span></td><td>' . stripcslashes( $value ) . '</td></tr>';
				$hidden = sprintf( '<input type="hidden" name="%s" value="%s" /> ', $key, $value );
				break;
		}

		if ( $args[ 'return' ] ) {
			return array( $field, $hidden );
		} else {
			printf( '%s,%s\n', $field, $hidden );
		}
	}

	/**
	 * Get last checkout Hints
	 *
	 * Likely not used anymore!
	 * @static
	 * @author jj
	 * @return string
	 */
	public static function get_last_checkout_hints( ){
		return WGM_Template::checkout_readonly_field( 'woocommerce_de_last_checkout_hints' );
	}

	/**
	* returns shipping costs and withdraw disclaimer as html with links
	*
	* @access public
	* @static
	* @author jj, ap
	* @uses get_option
	* @return string html
	*/
	public static function disclaimer_line(){

		// Shipping
		$shipping_url = get_permalink( get_option( WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ) ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$shipping_url = get_permalink( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ) ) ) );
		}
		$shipping   = array(
			'url'  => $shipping_url,
			'atts' => ''
		);
		$shipping[ 'atts' ] = sprintf(
			'href="%s" class="wgm-versandkosten" target="_blank"',
			esc_url( $shipping[ 'url' ] )
		);

		// Payment
		$payment_url = get_permalink( get_option( WGM_Helper::get_wgm_option( 'zahlungsarten' ) ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$payment_url = get_permalink( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'zahlungsarten' ) ) ) );
		}
		$payment   = array(
			'url'  => $payment_url,
			'atts' => ''
		);
		$payment[ 'atts' ] = sprintf(
			'href="%s" class="wgm-zahlungsarten" target="_blank"',
			esc_url( $payment[ 'url' ] )
		);

		// Revocation
		$revocation_url = get_permalink( get_option( WGM_Helper::get_wgm_option( 'widerruf' ) ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$revocation_url = get_permalink( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'widerruf' ) ) ) );
		}
		$revocation   = array(
			'url'  => $revocation_url,
			'atts' => ''
		);
		$revocation[ 'atts' ] = sprintf(
			'href="%s" class="wgm-widerruf" target="_blank"',
			esc_url( $revocation[ 'url' ] )
		);

		// Privacy
		$privacy_url = get_permalink( get_option( 'woocommerce_datenschutz_page_id' ) );
		if ( function_exists( 'icl_object_id' ) ) {
			$privacy_url = get_permalink( icl_object_id( get_option( 'woocommerce_datenschutz_page_id' ) ) );
		}
		$privacy = array(
			'url' 	=> $privacy_url,
			'atts' 	=> ''
		);
		$privacy[ 'atts' ] = sprintf(
			'href="%s" class="wgm-privacy" target="_blank"',
			esc_url( $privacy[ 'url' ] )
		);

		// Terms
		if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
			$link_terms = get_permalink( wc_terms_and_conditions_page_id() );
			if ( function_exists( 'icl_object_id' ) ) {
				$link_terms = get_permalink( icl_object_id( wc_terms_and_conditions_page_id() ) );
			}
		} else {
			$link_terms = get_permalink( get_option( 'woocommerce_terms_page_id' ) );
			if ( function_exists( 'icl_object_id' ) ) {
				$link_terms = get_permalink( icl_object_id( get_option( 'woocommerce_terms_page_id' ) ) );
			}
		}

		$terms = array(
			'url' 	=> $link_terms,
			'atts' 	=> ''
		);
		$terms[ 'atts' ] = sprintf(
			'href="%s" class="wgm-privacy" target="_blank"',
			esc_url( $terms[ 'url' ] )
		);

		// Build string
		$html  = '';
		
		$default_option = __( 'Learn more about [link-shipping]shipping costs[/link-shipping], [link-payment]payment methods[/link-payment] and our [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' );
		$option = get_option( 'woocommerce_de_learn_more_about_shipping_payment_revocation', $default_option );
		$option = str_replace( '%', '&#37;', $option );

		$text = str_replace( array( '[link-shipping]', '[link-payment]', '[link-revocation]', '[link-privacy]', '[link-terms]', '[/link-shipping]', '[/link-payment]', '[/link-revocation]', '[/link-privacy]', '[/link-terms]' ), array( '<a %1$s>', '<a %2$s>', '<a %3$s>', '<a %4$s>', '<a %5$s>', '</a>', '</a>', '</a>', '</a>', '</a>' ), $option );
		$text = apply_filters( 'woocommerce_de_learn_more_about_string_1', $text );

		$html .= sprintf(
			$text,
			$shipping[ 'atts' ],
			$payment[ 'atts' ],
			$revocation[ 'atts' ],
			$privacy[ 'atts' ],
			$terms[ 'atts' ]
		);

		/**
		 * Filter legacy
		 */
		if ( has_filter( 'wgm_disclaimer_line' ) ) {
			$versandkosten      = '<a class="wgm-versandkosten" href="' . $shipping[ 'url' ] . '" target="_blank">' . __( 'shipping costs', 'woocommerce-german-market' ) . '</a>';
			$versandkosten_text = sprintf( ' ' . __( 'Infomation regarding %1$s,', 'woocommerce-german-market' ), $versandkosten );
			$widerrufsrecht     = '<a class="wgm-widerruf" href="' . $revocation[ 'url' ] . '" target="_blank">' . __( 'revocation policy', 'woocommerce-german-market' ) . '</a>';
			$widerrufsrecht_text= sprintf( ' ' . __( 'details regarding our %1$s', 'woocommerce-german-market' ), $widerrufsrecht );
			$zahlungsarten     = '<a class="wgm-zahlungsarten" href="' . $payment[ 'url' ] . '"  target="_blank">' . __( 'Payment Methods', 'woocommerce-german-market' ) . '</a>';
			$zahlungsarten_text= sprintf( ' ' . __( 'and %s', 'woocommerce-german-market' ), $zahlungsarten );
			$disclaimer_line_prefix = __( 'Here you find', 'woocommerce-german-market' );

			// Legacy output
			$disclaimer_line = apply_filters(
				'wgm_disclaimer_line',
				$html,
				$shipping[ 'url' ],
				$versandkosten_text,
				$versandkosten,
				$widerrufsrecht,
				$revocation[ 'url' ],
				$widerrufsrecht_text,
				$zahlungsarten,
				$payment[ 'url' ],
				$zahlungsarten_text,
				$disclaimer_line_prefix
			);
		} else {

			// Regular output
			$disclaimer_line = apply_filters(
				'wgm_disclaimer_line',
				$html,
				$shipping,
				$payment,
				$revocation
			);
		}

		return $disclaimer_line;
	}


	/**
	* get string from texttemplate directory, if filename is given, else it returns the parameter
	*
	* @access	public
	* @static
	* @author	et, ap
	* @param	string $name template filename
	* @param	array $args
	* @return	void
	*/
	public static function include_template( $name, $args = array() ) {
		_deprecated_function( 'WGM_Template::include_template', "v2.3", "WGM_Template::load_template" );
		WGM_Template::load_template( $name, $args );
	}


	/**
	* get string from texttemplate directory, if filename is given, else it returns the parameter
	*
	* @access public
	* @param string $name template filename
	* @author jj, ap
	* @return string
	*/
	public static function get_text_template( $name ) {
		$path = dirname( __FILE__ ) . '/../text-templates/' . $name;
		if ( file_exists( $path )  ) {
			return file_get_contents( $path );
		} else {
			return $name;
		}
	}

	/**
	 * Adds payment information to the mails
	 * @deprecated
	 * @param WC_Order $order The Woocommerce order object
	 * @access public
	 * @author ap
	 * @since 2.0
	 * @hook woocommerce_email_after_order_table
	 */
	public static function add_paymentmethod_to_mails( $order ){
		_deprecated_function(__FUNCTION__, '2.6.5' );
		$html = '<h3>' . __( 'Payment Method', 'woocommerce-german-market' ) . ': ' . $order->get_payment_method_title() . '</h3>';
		echo apply_filters( 'wgm_add_paymentmethod_to_mails_html', $html, $order );
	}

	/**
	 * Get product short description by product id regarding variations
	 *
	 * @param Integer $product_id
	 * @return String
	 * @access public
	 * @static
	 * @since 3.5.7
	 * @last-updated 3.12.6
	 */
	public static function get_short_description_by_product_id ( $product_id ) {

		$short_description 		= '';
		$variation_description 	= '';
		$product 				= wc_get_product( $product_id );
		$is_variation			= false;

		if ( $product ) {

			if ( apply_filters( 'german_market_get_short_description_by_product_id_check_variation', $product->get_type() == 'variation', $product ) ) {

				$is_variation = true;

				if ( method_exists( $product, 'get_description' ) ) {
					$variation_description = $product->get_description();
				}

				$product = wc_get_product( $product->get_parent_id() );
			}
		}

		if ( WGM_Helper::method_exists( $product, 'get_short_description' ) ) {
			$short_description = $product->get_short_description();
		}

		if ( $is_variation ){

			$variation_option = get_option( 'german_market_short_description_of_variations', 'only_short_description' );

			if ( 'only_variation_description' === $variation_option ) {
				$short_description = $variation_description;
			} else if ( 'short_and_variation_description' === $variation_option ) {

				if ( ! empty( $variation_description ) ) {
					if ( ! empty( $short_description ) ) {
						$short_description .= apply_filters( 'german_market_get_short_description_eol', '<br>', $product ) . $variation_description;
					} else {
						$short_description = $variation_description;
					}
				}

			} // else if for 'only_short_description' is not necessary
		}

		return $short_description;

	}

	/**
	 * adds the product short description to the checkout
	 * @param string $title
	 * @param string $item
	 * @return string
	 * @author ap
	 * @access public
	 * @static
	 * @hook woocommerce_checkout_cart_item_quantity
	 */
	public static function add_product_short_desc_to_checkout_title( $title, $cart_item, $cart_item_key ){
		
		if ( get_option( 'woocommerce_de_show_show_short_desc' ) !== 'on' ){
			return $title;
		}

		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_object( $_product ) && method_exists( $_product, 'get_id' ) ) {
			$product_short_desc = self::get_short_description_by_product_id( $_product->get_id() );
			$html = '<span class="wgm-break"></span> <span class="product-desc">'  . $product_short_desc . '</span>';
			$title .= apply_filters( 'wgm_add_product_short_desc_to_checkout_title', $html, $title, $cart_item, $product_short_desc );
		}

		return $title;
	}

	/**
	 * adds the product short description to the oder listing
	 * @access public
	 * @static
	 * @author ap
	 * @param string $title
	 * @param string $item
	 * @return string
	 */
	public static function add_product_short_desc_to_order_title( $title, $item ){

		if ( get_option( 'woocommerce_de_show_show_short_desc' ) !== 'on' ){
			return $title;
		}

		$_product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
		
		if ( is_object( $_product ) && method_exists( $_product, 'get_id' ) ) {
			$product_id = $_product->get_id();
			$product_short_desc = self::get_short_description_by_product_id( $product_id );
			$html = '<span class="wgm-break"></span> <span class="product-desc">'  . $product_short_desc . '</span>';

			$title .= apply_filters( 'wgm_add_product_short_desc_to_order_title_html', $html, $title, $item, $product_short_desc );
		}

		return $title;
	}

	/**
	 * adds the product short description to the oder listing
	 * @access public
	 * @static
	 * @param int $item_id
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @param bool $plain_text
	 * @return void
	 * @hook woocommerce_order_item_meta_start'
	 */
	public static function woocommerce_order_item_meta_start_short_desc( $item_id, $item, $order, $plain_text = false ){

		$short_desc = self::add_product_short_desc_to_order_title( '', $item );
		if ( $short_desc !== '' ) {
			echo $plain_text ? "\n" : '';
			echo $short_desc;
		}

	}

	/**
	 * adds the requirements to the order listing
	 * @access public
	 * @static
	 * @param int $item_id
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @param bool $plain_text
	 * @return void
	 * @hook woocommerce_order_item_meta_start
	 */
	public static function woocommerce_order_item_meta_requirements( $item_id, $item, $order, $plain_text = false ){

		$requirements =  self::add_product_function_desc( '', $item );
		if ( $requirements !== '' ) {
			echo $plain_text ? "\n" : '';
			echo $requirements;
		}

	}

	/**
	 * adds the product short description to the oder listing
	 * @access public
	 * @static
	 * @param int $item_id
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @param $plain_text
	 * @return void
	 * @hook woocommerce_order_item_meta_start
	 */
	public static function add_product_function_desc( $title, $item ){

		$prerequisites = false;

		if ( ! empty( $item[ 'variation_id' ] ) ) {
			$id = $item[ 'variation_id' ];
			$requirements_key = '_variation_requirements';
			$is_downloadable = get_post_meta( $id, '_downloadable', true ) == 'yes';
		} else {

			$is_downloadable = false;

 			if ( isset( $item[ 'product_id' ] ) ) {

 				$id = $item[ 'product_id' ];
 				$requirements_key = 'product_function_desc_textarea';
 				$_product = wc_get_product( $item[ 'product_id'] );
 				if ( $_product ) {
 					$is_downloadable = $_product->is_downloadable();
 				}

 				$prerequisites = get_post_meta( $id, $requirements_key, true );

 			}
		}

		if ( ( WGM_Helper::is_digital( $id ) || $is_downloadable ) && $prerequisites != false ){

			$prerequisites_label = apply_filters( 'wgm_product_prerequisites_label', __( 'Requirements', 'woocommerce-german-market' ) );
			$prerequisites_label_markup = apply_filters( 'wgm_product_prerequisites_label_markup', '<span class="wgm-prerequisites-label">' . $prerequisites_label . ': </span>', $prerequisites_label );

			$html = ' <span class="wgm-break"></span><span class="wgm-product-prerequisites">' . $prerequisites_label_markup . esc_attr( $prerequisites ) . '</span>';
			$title .= apply_filters( 'wgm_add_product_prerequisites_to_order_title_html', $html, $title, $item, $prerequisites );
		}

		return $title;
	}

	/**
	 * wp-hook woocommerce_single_product_summary
	 * @return void
	 */
	public static function add_digital_product_prerequisits() {

		global $product;
		echo self::get_digital_product_prerequisits( $product );
	}

	/**
	 * @param $_product
	 *
	 * @return string|void
	 */
	public static function get_digital_product_prerequisits( $_product ) {

		if ( ! $_product ) {
			return;
		}

		// there is at least one theme where $_product is a WP_Post instead of a WC_Product
		if ( is_a( $_product, 'WP_Post' ) ) {
			$_product = wc_get_product( $_product );
		}

		$return = '';

		if ( $_product->get_type() == 'variation' ) {
			$_digital = ( get_post_meta( $_product->get_id(), '_digital', TRUE ) == 'yes' );
		} else {
			$_digital = WGM_Helper::is_digital( $_product->get_id() );
		}

		$prerequisites = get_post_meta( $_product->get_id(), 'product_function_desc_textarea', TRUE );

		if ( ( $_digital || $_product->is_downloadable() ) && $prerequisites != FALSE ) {

			ob_start();

			do_action( 'wgm_before_product_prerequists' );

			$html = '';

			if ( $_product->get_type() == 'variable' ) {

				return '';
				/*
				$notice = '<span class="wgm-digital-variation-notice">';
				$notice .= apply_filters( 'wgm_variation_prerequists_notice_label',
				                          __( 'The following product configurations are digital:',
				                              'woocommerce-german-market' ) );
				$notice .= '</span>';

				$html .= apply_filters( 'wgm_digital_variation_notice_html', $notice );

				$list = '<ul class="wgm-digital-attribute-list">';

				$child_filter = array();

				foreach ( $_product->get_visible_children() as $child ) {

					$c_product = wc_get_product( $child );

					if ( WGM_Helper::is_digital( $c_product->get_id() ) ) {
						$child_filter[] = $c_product;

						$list .= '<li>';

						$data = array_values( $c_product->variation_data );

						for ( $i = 0; $i <= count( $data ) - 1; $i ++ ) {
							if ( empty( $data[ $i ] ) ) {
								continue;
							}
							$list .= $data[ $i ];

							if ( isset( $data[ $i + 1 ] ) && ! empty( $data[ $i + 1 ] ) ) {
								$list .= apply_filters( 'wgm_digital_variation_notice_attribute_separator', ' & ' );
							}
						}
					}

					$list .= '</li>';
				}

				$list .= '</ul>';

				$html .= apply_filters( 'wgm_digital_variation_notice_attribues_list', $list, $child_filter );

				$html .= apply_filters(
					'wgm_variation_prerequists_label',
					sprintf( '<span class="wgm-product-prerequisites-label">%s</span>',
					         __( 'Digital configurations of this product have the following requirements:',
					             'woocommerce-german-market' ) )
				);
				*/
			}

			$prerequisites_html = '<div class="wgm-info wgm-product-prerequisites">' . $prerequisites . '</div>';
			$html .= apply_filters( 'wgm_add_product_prerequisites', $prerequisites_html, $prerequisites );

			echo $html;

			do_action( 'wgm_after_product_prerequists' );

			$return = ob_get_clean();

		}

		return $return;
	}

	/**
	* returns the extra cost for non eu countries to the product description
	* still used in class WGM_Embed
	*
	* @access public
	* @static
	* @return String
	*/
	public static function get_extra_costs_eu() {

		if ( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_show_extra_cost_hint_eu' ) ) !== 'on' ) {
			return '';
		}

		return apply_filters(
			'wgm_show_extra_costs_eu_html',
			sprintf(
				'<small class="wgm-info wgm-extra-costs-eu">%s</small>',
				get_option( 'woocommerce_de_show_extra_cost_hint_eu_text', __( 'Additional costs (e.g. for customs or taxes) may occur when shipping to non-EU countries.', 'woocommerce-german-market' ) )
			)
		);
	}

	/**
	* returns the extra cost for non eu countries to the product description
	*
	* @since GM 3.4.2
	* @wp-hook wgm_product_summary_parts
	* @access public
	* @static
	* @return String
	*/
	public static function add_extra_costs_non_eu( $parts, WC_Product $product, $hook ) {

		if ( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_show_extra_cost_hint_eu' ) ) == 'on' ) {

			$parts[ 'extra_costs_non_eu' ] = apply_filters( 'wgm_show_extra_costs_eu_html',

				sprintf(
					'<small class="wgm-info wgm-extra-costs-eu">%s</small>',
					get_option( 'woocommerce_de_show_extra_cost_hint_eu_text', __( 'Additional costs (e.g. for customs or taxes) may occur when shipping to non-EU countries.', 'woocommerce-german-market' ))
				)

			);


		}

		return $parts;
	}

	/**
	 * hide flat rate shipping if free shipping is available
	 * @access public
	 * @static
	 * @param Array $rates
 	 * @param Array $package
	 * @return array
	 * @hook woocommerce_package_rates
	 */

	public static function hide_flat_rate_shipping_when_free_is_available( $rates, $package ) {

		if ( get_option( 'wgm_dual_shipping_option', 'off' ) == 'on' ) {

	    	// is there frees shipping?
	    	$free_shipping_is_available = false;
	    	foreach ( $rates as $rate ) {
	    		if ( $rate->method_id == 'free_shipping' ) {
	    			$free_shipping_is_available = true;
	    			break;
	    		}
	    	}

	    	if ( $free_shipping_is_available ) {

		    	$new_rates = $rates;
		    	// unset all other rates (there can be more than 1 rate of free shipping (e.g.: free local pickup, free delivery) )
		    	foreach( $rates as $key => $rate ) {

		    		if ( $rate->method_id == 'flat_rate' ) {

		    			// filter since 3.5.5
		    			if ( apply_filters( 'wgm_dual_shipping_unset_shipping_method', true, $rate ) ) {
		    				unset( $new_rates[ $key ] );
		    			}

		    		}

		    	}

		    	$rates = $new_rates;

		    }

	    }

    	return $rates;

	}

	public static function kur_notice() {

		if ( ! WGM_Tax::is_kur() ) {
			return;
		}
		echo apply_filters(
				'wgm_kur_notice_html',
				'<tr><td colspan="2" class="wgm-kur-notice-review"><div class="wgm-kur-notice">' .	self::get_ste_string() .'</div></td></tr>'
		);
	}

	public static function kur_review_order_notice() {
		if( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) == 'on' ){
			echo apply_filters(
				'wgm_kur_review_order_notice_html',
				'<tr>
				<td></td>
				<td>
				<div class="wgm-kur-notice-review">' .self::get_ste_string() .	'</div>
				</td>
				</tr>'
			);
		}
	}

	public static function kur_review_order_item( $formatted_total, $order = false ){

		if( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) !== 'on' )
			return $formatted_total;

		$html = ' <small>' . self::get_ste_string() . '</small>';
		$html = apply_filters( 'wgm_kur_review_order_item_html', $html, $order );

		return $formatted_total . $html;
	}

	/**
	 * Adds a [Digital] notice to the product title of a digital product
	 *
	 * @wp-hook woocommerce_product_title
	 *
	 * @param $name
	 * @param $_product
	 *
	 * @return mixed|void
	 */
	public static function add_virtual_product_notice( $name, $_product ) {

		/**
		 * Unfortunately, using Markup in the product title causes all kinds of problems with escaped markup showing up.
		 * (External Payment Gateways, REST API requests and so on)
		 *
		 *Disable the use of markup for now. Maybe a solution pops up eventually
		 */
		$use_markup = FALSE;

		// you can return orignal filter parameter in ajax requests
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && apply_filters( 'gm_add_virtual_product_notice_not_in_ajax', false ) ) {
			return $name;
		}

		// before WC 3.0.0 we checked if it's a variation or not, get_id always returns the correct id
		$id = $_product->get_id();

		if ( WGM_Helper::is_digital( $id ) ) {

			$digital_keyword = apply_filters( 'wgm_product_name_virtual_notice_keyword', '[Digital]' );

			// digital keyowrd has already been added
			if ( str_replace( $digital_keyword, '', $name ) != $name ) {
				return $name;
			}

			if ( $use_markup ) {
				$string = sprintf( '%s <span class="wgm-virtual-notice">%s</span>', $name, $digital_keyword );
			} else {
				$string = sprintf( '%s %s', $name, $digital_keyword );
			}

			return apply_filters(
					'wgm_product_name_virtual_notice',
					$string,
					$name,
					$digital_keyword
			);
		}

		return $name;
	}

	/**
	 * Add the "[Digital]" to product name
	 *
	 * @author  ChriCo
	 *
	 * @wp-hook woocommerce_order_get_items
	 *
	 * @param   array $items
	 * @return  array $items
	 */
	public static function filter_order_item_name( $items ){

		$keyword= apply_filters( 'wgm_product_name_virtual_notice_keyword', '[Digital]' );
		$html   = sprintf( '<span class="wgm-virtual-notice">%s</span>', $keyword );

		foreach( $items as $key => $item ){

			if ( 'line_item' !== $item->get_type() ) {
				continue;
			}

			$search = apply_filters(
				'wgm_product_name_virtual_notice',
				$html,
				$item[ 'name' ],
				$keyword
			);

			if( strpos( $item[ 'name' ], $search ) !== FALSE ){
				$item[ 'name' ] = str_replace( $search, $keyword, $item[ 'name' ] );
			}

			/**
			 * re-assign the value
			 * @issue #421
			 */
			$items[ $key ] = $item;

		}

		return $items;
	}

	/**
	 * Caches the Order Button HTML and removes it form checkout
	 * @param $button_html
	 * @author ap
	 * @singe 2.4.13
	 * @wp-hook woocommerce_order_button_html
	 * @return string
	 */
	public static function remove_order_button_html( $button_html ){
		self::$button_html = $button_html;
		return '';
	}

	/**
	 * Prints the cahced Order Button HTML at the absolute end of the checkout page
	 *
	 * @author ap
	 * @singe 2.4.13
	 * @wp-hook woocommerce_review_order_after_submit
	 * @return void
	 */
	public static function print_order_button_html(){
		echo self::$button_html;
	}

	/**
	 * Adds excluding tax notice after cart and checkout total
	 * @param string $value
	 * @author ap, ch
	 * @hook woocommerce_cart_totals_order_total_html
	 * @since 2.5
	 * @return string
	 */
	public static function woocommerce_cart_totals_excl_tax_string( $value ) {

		// Default total without any taxes.
		$value = '<strong>' . WC()->cart->get_total() . '</strong> ';
		if ( ! wc_tax_enabled() ) {
			return $value;
		}

		$tax_total_string = WGM_Template::get_totals_tax_string( WC()->cart->get_tax_totals(),
		                                                         get_option( 'woocommerce_tax_display_cart' ) );

		// Append tax line to total.
		$value .= $tax_total_string;

		return apply_filters( 'wgm_cart_totals_order_total_html', $value, $tax_total_string );
	}

	/**
	 * Adds excluding tax notice after cart and checkout total
	 * @param string $value
	 * @author ap, ch
	 * @hook woocommerce_cart_totals_order_total_html
	 * @since 2.5
	 * @return string
	 */
	public static function woocommerce_tax_totals_excl_tax_string( $value ) {

		// Default total without any taxes.
		$value = '<strong>' . $value . '</strong> ';

		if ( ! wc_tax_enabled() || get_option( 'woocommerce_tax_total_display' ) === 'itemized' ) {
			return $value;
		}
		//TODO: It would probably be necessary to force an itemized tax display in WGM_Template::get_totals_tax_string(). This is currently not possible

		// Append tax line to total.
		$tax_total_string = WGM_Template::get_totals_tax_string( WC()->cart->get_tax_totals(),
		                                                         'incl' );
		$value .= $tax_total_string;

		return apply_filters( 'wgm_tax_totals_order_total_html', $value, $tax_total_string );
	}

	/**
	 * Get totals for display on pages and in emails.
	 *
	 * @param  array  $total_rows [description]
	 * @param  object $order      [description]
	 * @return array             [description]
	 */
	public static function get_order_item_totals( $total_rows, $order ) {

		if ( is_a( $order, 'WC_Order_Refund' ) ) {
			$parent_id = $order->get_parent_id();
			$order = wc_get_order( $parent_id );
		}

		$tax_display = get_option( 'woocommerce_tax_display_cart' );

		$total_rows[ 'order_total' ] = array(
				'label' => __( 'Total:', 'woocommerce-german-market' ),
				'value' => $order->get_formatted_order_total()
		);

		// Tax for inclusive prices
		if ( wc_tax_enabled() && 'incl' == $tax_display ) {
			if ( ( $order->get_total() > 0.0 ) || ( apply_filters( 'german_market_get_order_item_totals_show_taxes_order_total_zero', true ) ) ) {
				$tax_total_string = WGM_Template::get_totals_tax_string( $order->get_tax_totals(), $tax_display, $order );
				$total_rows[ 'order_total' ][ 'value' ] .= sprintf( ' %s', $tax_total_string );
			}
		}

		return $total_rows;
	}

	/**
	 * Hide WGMs Order Item Meta form showing up in backend order view
	 * @param array $meta Order Item Meta keys
	 * @author ap
	 * @since 2.5
	 * @wp-hook woocommerce_hidden_order_itemmeta
	 * @return array
	 */
	public static function add_hidden_order_itemmeta( array $meta ){
		$meta[] = '_deliverytime';
		$meta[] = '_gm_ppu';
		return $meta;
	}

	/**
	 * Show Copoun html without removal link
	 * @param WC_Coupon|string $coupon
	 * @author ap
	 * @since 2.5
	 * @return void
	 */
	public static function checkout_totals_coupon_html( $coupon ) {
		if ( is_string( $coupon ) ) {
			$coupon = new WC_Coupon( $coupon );
		}

		$value  = array();

		if ( $amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ) ) {
			$discount_html = '-' . wc_price( $amount );
		} else {
			$discount_html = '';
		}

		$value[] = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_html, $coupon );

		if ( $coupon->get_free_shipping() ) {
			$value[] = __( 'Free shipping coupon', 'woocommerce-german-market' );
		}

		// get rid of empty array elements
		$value = array_filter( $value );
		$value = implode( ', ', $value );

		echo apply_filters( 'wgm_checkout_totals_coupon_html', $value, $coupon );
	}



	/**
	 * Get tax string for order total.
	 *
	 * @param  array    $tax_totals  Array of tax rate objects
	 * @param  string   $tax_display incl|excl
	 * @param  WC_Order $order
	 * @param  string  $tax_total_display
	 *
	 * @return string              String indicating taxes included|excluded
	 */
	public static function get_totals_tax_string( $tax_totals, $tax_display, $order = NULL, $tax_total_display = NULL, $show_refund = true ) {

		if ( empty( $tax_total_display ) ) {
			$tax_total_display = get_option( 'woocommerce_tax_total_display' );
		}

		if ( wc_tax_enabled() && 'excl' === get_option( 'woocommerce_tax_display_cart' ) ) {
			return '';
		}

		$tax_string_array = array();
		$tax_total        = 0;

		$is_tax_free = false;

		// Collect applicable taxes.
		foreach ( $tax_totals as $code => $tax ) {

			/**
			 * If this method is called from an order (as opposed to the cart),
			 * it is possible that refunds have taken place and that we're currently rendering the
			 * refund notification email.
			 * In this case, we need to take the refunded amount of taxes into account and substract it from the
			 * actual total tax amount.
			 *
			 * Otherwise, we would still end up showing the inital amount of taxes without refunds
			 */
			if ( $show_refund && ( isset( $tax->rate_id ) && ( ! is_null( $order ) && ( $refunded = $order->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ) > 0 ) ) ) {
				$tax_total += $tax->amount - $refunded;
				$formatted_amount = '<del>' . strip_tags( $tax->formatted_amount ) . '</del> <ins>' . wc_price( $tax->amount - $refunded, array( 'currency' => $order->get_currency() ) ) . '</ins>';

			} else {

				if ( isset( $tax->amount ) ) {
					$tax_total += $tax->amount;
				}

				$formatted_amount = $tax->formatted_amount;
			}

			// Tax label
			$tax_label = apply_filters( 'wgm_get_totals_tax_string_tax_label', $tax->label, $tax, $order );
			$tax_label = apply_filters( 'wgm_translate_tax_label', $tax->label );

			// Append percentage to label when in order review or e-mail.
			if ( isset( $tax->rate_id ) ) {
				// %s: ({percentage}%)
				$tax_percent = WGM_Tax::get_rate_percent_by_rate_id_and_order( $tax->rate_id, $order );
				$tax_label .= sprintf( ' (%s)', str_replace( '.', wc_get_price_decimal_separator(), $tax_percent ) );
			}

			$tax_label = apply_filters( 'wgm_get_totals_tax_string_tax_label_before_array', $tax_label, $tax );

			// %s: €{amount} {label}
			$tax_string_array[] = apply_filters( 'wgm_get_totals_tax_string_single_tax_string_array', sprintf(
				'<span class="amount">%s</span> %s',
				$formatted_amount,
				$tax_label // here: percentage rate already included
			), $formatted_amount, $tax_label );
		}

		// Safety first.
		if ( empty( $tax_string_array ) ) {

			$free_of_taxes = false;

			if ( $order ) {

				$free_of_taxes = empty( $order->get_taxes() );

			} else {

				if ( WC()->cart ) {
					$free_of_taxes = empty( WC()->cart->get_taxes() );
				}

			}

			if ( $free_of_taxes ) {

				return apply_filters( 'wgm_zero_tax_rate_message', '', 'total_tax' );

			} else {

				$default_vat_label = get_option( WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) );

				if ( $default_vat_label == '' ) {
					$default_vat_label = __( 'VAT', 'woocommerce-german-market' );
				}

				if ( ! apply_filters( 'show_wgm_zero_tax_rate_message_instead_of_amount_of_zero', false ) ) {

					$tax_string_array[] = sprintf(
						'<span class="amount">%s</span> %s',
						wc_price( 0.0 ),
						$default_vat_label
					);

				} else {
					return apply_filters( 'wgm_zero_tax_rate_message', '', 'total_tax' );
				}

			}

		}

		$tax_line         = '';
		$tax_string       = '';
		$tax_line_class   = '';
		$tax_total_string = '';

		/**
		 * Itemized displaying of taxes.
		 */
		if ( apply_filters( 'get_totals_tax_string_tax_total_display', $tax_total_display ) == 'itemized' ) {

			foreach ( $tax_string_array as $tax_string ) {

				// Tax included.
				if ( $tax_display == 'incl' ) {

					$tax_line       = sprintf(
					/* translators: %s: tax included */
						__( 'Includes %s', 'woocommerce-german-market' ),
						$tax_string
					);
					$tax_line_class = 'wgm-tax includes_tax';

				} else { // Tax to be added.

					$tax_line       = sprintf(
					/* translators: %s: tax to be added */
						__( 'Plus %s', 'woocommerce-german-market' ),
						$tax_string
					);
					$tax_line_class = 'wgm-tax excludes_tax';
				}

				// Append tax line to total.
				$tax_total_string .= sprintf( '<br class="wgm-break" /><span class="%s">', $tax_line_class );
				$tax_total_string .= $tax_line;
				$tax_total_string .= '</span>';
			}

			/**
			 * Single displaying of taxes.
			 */
		} else {

			$wc_price_args = array();

			if ( WGM_Helper::method_exists( $order, 'get_currency' ) ) {
				$wc_price_args[ 'currency' ] = $order->get_currency();
			}

			$tax_string = sprintf(
				'<span class="amount">%s</span> %s',
				wc_price( $tax_total, $wc_price_args ),
				WGM_Helper::get_default_tax_label()
			);

			// Tax included.
			if ( $tax_display == 'incl' ) {

				$tax_line       = sprintf(
				/* translators: %s: tax included */
					__( 'Includes %s', 'woocommerce-german-market' ),
					$tax_string
				);
				$tax_line_class = 'wgm-tax includes_tax';

			} else { // Tax to be added.

				$tax_line       = sprintf(
				/* translators: %s: tax to be added */
					__( 'Plus %s', 'woocommerce-german-market' ),
					$tax_string
				);
				$tax_line_class = 'wgm-tax excludes_tax';
			}

			// Append tax line to total.
			$tax_total_string .= sprintf( '<br class="wgm-break" /><span class="%s">', $tax_line_class );
			$tax_total_string .= $tax_line;
			$tax_total_string .= '</span>';

		}

		/**
		 * Output
		 */
		return apply_filters( 'wgm_get_totals_tax_string', $tax_total_string, $tax_string_array, $tax_totals, $tax_display );
	}

	/**
	 * Avoid products that are for free in checkout
	 *
	 * @version 3.0.2
	 * @wp-hook woocommerce_after_checkout_validation
	 * @return void
	 */
	public static function avoid_free_items_in_cart( $data, $errors ) {

		if ( get_option( 'woocommerce_de_avoid_free_items_in_cart', 'off' ) == 'on' ) {
			$cart = WC()->cart->get_cart();
			$has_free_items = false;
			foreach ( $cart as $item ) {
				$line_total = $item[ 'line_subtotal' ];
				if ( ! floatval( $line_total ) > floatval( apply_filters( 'woocommerce_de_avoid_free_items_limit', 0.0 ) ) ) {
					if ( apply_filters( 'woocommerce_de_avoid_free_items_in_cart_by_item', true, $item ) ) {
						$has_free_items = true;
						break;
					}
				}

			}

			if ( $has_free_items ) {
				$errors->add( 'german_market_checkbox_5_my_account_registration', get_option( 'woocommerce_de_avoid_free_items_in_cart_message', __( 'Sorry, you can\'t proceed to checkout. Please contact our support.', 'woocommerce-german-market' ) ) );
			}
		}
	}

	/**
	 * VC Compability
	 *
	 * @since GM v3.2
	 * @param String $content
	 * @return $content
	 */
	public static function remove_vc_shortcodes( $content ) {

		if ( apply_filters( 'wgm_vc_remove_shortcodes', true ) ) {

            $regexes = array(
                "^\[(\/|)vc_(.*)\]^",
                "^\[(\/|)av_(.*)\]^",
                 "^\[(\/|)et_pb_(.*)\]^",
            );

            $regexes = apply_filters( 'wgm_vc_regexes', $regexes );

            foreach ( $regexes as $regex ) {
                $content = preg_replace( $regex, '', $content );
            }

        }

        // remove thin spin "&thinsp;"
        $content = str_replace( ' ', ' ', $content );

        return $content;
	}

	/**
	 * Get Terms And Conditions Checkbox
	 *
	 * @since GM v3.2
	 * @wp-hook woocommerce_de_add_review_order
	 * @return void
	 */
	public static function terms_and_conditions() {
		wc_get_template( 'checkout/terms.php' );
	}

	/**
	 * German Market Product Informations in Widgets
	 *
	 * @since GM v3.2
	 * @wp-hook woocommerce_after_template_part
	 * @param String $template_name
	 * @return void
	 */
	public static function widget_after_content_product( $template_name, $template_path, $located, $args ) {

		if ( $template_name == 'content-widget-product.php' ) {

			global $product;
			$hook = 'loop';
			$output_parts = array();

			$output_parts = apply_filters( 'wgm_product_summary_parts', $output_parts, $product, $hook );
			$output_parts = WGM_Shipping::add_shipping_part( $output_parts, $product ); // this class inits the filter with less parameters, so call it manually

			// unset price in widgets, because it's already there
			if ( isset( $output_parts[ 'price' ] ) ) {
				unset( $output_parts[ 'price' ] );
			}

			$output_html  = implode( $output_parts );

			$output_html = apply_filters( 'wgm_product_summary_html', $output_html, $output_parts, $product, $hook );
			$output_html = apply_filters( 'wgm_product_summary_html_in_widget', $output_html, $output_parts, $product, $hook );

			echo $output_html;

		}

	}

	/**
	 * German Market Product Informations in Widgets
	 *
	 * @since GM v3.8.2
	 * @wp-hook woocommerce_widget_product_item_end
	 * @param Array $args
	 * @return void
	 */
	public static function widget_product_item_end( $args ) {

		global $product;

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( is_null( $product ) || ! ( $product ) ) {
			$product = wc_get_product();

			// WC 2.7 beta 2
			if ( ! $product ) {
				$product = wc_get_product( get_the_ID() );
			}

		}

		$hook   = 'loop';

		if ( apply_filters( 'wgm_template_widget_product_item_end_echo_nothing', false ) ) {
			return;
		}

		// for related products, use 'loop'
		global $woocommerce_loop;

		// Use Runtime Cache
		if ( isset( self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_widget_' . $hook . '_' . $product->get_id() ] ) ) {
			echo self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_widget_' . $hook . '_' . $product->get_id() ];
			return;
		}

		$output_parts = array();
		$output_parts = apply_filters( 'wgm_product_summary_parts', $output_parts, $product, $hook );
		$output_parts = WGM_Shipping::add_shipping_part( $output_parts, $product ); // this class inits the filter with less parameters, so call it manually
		$output_parts = apply_filters( 'wgm_product_summary_parts_after', $output_parts, $product, $hook );
		$output_parts = apply_filters( 'wgm_product_summary_parts_after_widget', $output_parts, $product, $hook );

		if ( isset( $output_parts[ 'price' ] ) ) {
			unset( $output_parts[ 'price' ] );
		}

		$output_html  = implode( $output_parts );

		$output_html = apply_filters( 'wgm_product_summary_html', $output_html, $output_parts, $product, $hook );
		self::$run_time_cache[ 'get_wgm_product_summary_get_wgm_product_summary_widget_' . $hook . '_' . $product->get_id() ] = $output_html;

		echo $output_html;

	}

	/**
	 * German Market Product Informations in Mini Cart
	 *
	 * @since GM v3.2
	 * @wp-hook woocommerce_widget_cart_item_quantity
	 * @param String $html_string
	 * @param Array $cart_item
	 * @param String $cart_item_key
	 * @return void
	 */
	public static function mini_cart_price( $html_string, $cart_item, $cart_item_key ) {

		$product      = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$hook = 'loop';
		$output_parts = array();

		$output_parts = apply_filters( 'wgm_product_summary_parts', $output_parts, $product, $hook );

		// unset price in widgets, because it's already there
		if ( isset( $output_parts[ 'price' ] ) ) {
			unset( $output_parts[ 'price' ] );
		}

		// unset shipping in mini cart, because it's already there
		if ( isset( $output_parts[ 'shipping' ] ) ) {
			unset( $output_parts[ 'shipping' ] );
		}

		// unset ppu in mini cart, because it's already there
		if ( isset( $output_parts[ 'ppu' ] ) ) {
			unset( $output_parts[ 'ppu' ] );
		}

		if ( isset( $output_parts[ 'tax' ] ) ) {

			if ( get_option( 'woocommerce_tax_display_shop' ) != get_option( 'woocommerce_tax_display_cart' ) ) {

				$output_parts[ 'tax' ] = WGM_Tax::text_including_tax( $cart_item[ 'data' ], true );
			}

			$output_parts[ 'tax' ] = apply_filters( 'german_market_mini_cart_price_tax', $output_parts[ 'tax' ], $cart_item[ 'data' ] );

		}

		$output_html = implode( $output_parts );
		$output_html = apply_filters( 'wgm_product_summary_html', $output_html, $output_parts, $product, $hook );
		$output_html = apply_filters( 'wgm_product_summary_html_in_widget', $output_html, $output_parts, $product, $hook );

		return $html_string . $output_html;

	}

	/**
	* Delivery Time in Checkout: Add item meta
	*
	* @wp-hook woocommerce_add_cart_item_data
	* @since GM v3.2
	* @static
	* @access public
	* @param Array $cart_item_data
	* @param Integer $product_id
	* @param Integer $variation_id
	* @return Array
	**/
	public static function delivery_time_co_woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {

		if ( apply_filters( 'german_market_delivery_time_co_woocommerce_add_cart_item_data_return', false, $cart_item_data, $product_id, $variation_id ) ) {
			return $cart_item_data;
		}

		$id = ( $variation_id && $variation_id > 0 ) ? $variation_id : $product_id;
		$product = wc_get_product( $id  );

		$delivery_time_string = self::get_deliverytime_string( $product );

		if ( $delivery_time_string != '' ) {
			$cart_item_data[ 'gm_delivery_time' ] = $delivery_time_string;
		}

		return $cart_item_data;
	}

	/**
	* Delivery Time in Checkout: Add item meta from session
	*
	* @wp-hook woocommerce_add_cart_item_data
	* @since GM v3.2
	* @static
	* @access public
	* @param Array $cart_item_data
	* @param Array $cart_item_session_data
	* @param String $cart_item_key
	* @return Array
	**/
	public static function delivery_time_co_woocommerce_get_cart_item_from_session( $cart_item_data, $cart_item_session_data, $cart_item_key ) {

		if ( isset( $cart_item_session_data[ 'gm_delivery_time' ] ) ) {
	        $cart_item_data[ 'gm_delivery_time' ] = $cart_item_session_data[ 'gm_delivery_time' ];
	    }

		return $cart_item_data;
	}

	/**
	* Delivery Time in Checkout: Show Item Meta in Checkout
	*
	* @wp-hook woocommerce_add_cart_item_data
	* @since GM v3.2
	* @static
	* @access public
	* @param Array $data
	* @param Array $cart_item
	* @return Array
	**/
	public static function delivery_time_co_woocommerce_get_item_data( $data, $cart_item ) {

		if ( isset( $cart_item[ 'gm_delivery_time' ] ) ) {

			$label = apply_filters( 'gm_delivery_time_label_in_checkout', __( 'Delivery Time:', 'woocommerce-german-market' ) );

			// no ':' at the end of the string, this will be added by woocommerce itself
			if ( substr( $label, -1 ) == ':' ) {
				$label = substr( $label, 0, -1 );
			}

	        $data[] = array(
	            'name' => $label,
	            'value' => apply_filters( 'gm_delivery_time_value_in_checkout', $cart_item[ 'gm_delivery_time' ], $cart_item )
	        );
	    }

		return $data;
	}

	/**
	* Attributes in product names: Cart, Checkout
	*
	* @wp-hook woocommerce_cart_item_name
	* @since GM v3.4
	* @static
	* @access public
	* @param String $name
	* @param Array $cart_item
	* @param String $cart_item_key
	* @return String
	**/
	public static function attribute_in_product_name( $name, $cart_item, $cart_item_key ) {

		if ( get_option( 'german_market_attribute_in_product_name', 'off' ) == 'off' ) {

			$_product = $cart_item[ 'data' ];

			if ( WGM_Helper::method_exists( $_product, 'is_visible' ) ) {

				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

				if ( is_object( $_product ) && $_product->is_type( 'variation' ) ) {
					if ( ! $product_permalink ) {
						$name = $_product->get_title();
					} else {

						if ( is_cart() ) {
							$name =  sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() );
						} else {
							$name = $_product->get_title();
						}

					}
				}

			}

		}

		return $name;
	}

	/**
	* Attributes in product names: Order name
	*
	* @wp-hook woocommerce_order_item_name
	* @since GM v3.4
	* @static
	* @access public
	* @param String $name
	* @param WC_Order_Item_Product $item
	* @param Boolean $is_visible
	* @return String
	**/
	public static function attribute_in_product_name_order( $name, $item, $is_visible = false ) {

		if ( get_option( 'german_market_attribute_in_product_name', 'off' ) == 'off' ) {

			// Compability for plugins that uses test $items with less keys and values
			if ( ! WGM_Helper::method_exists( $item, 'get_product' ) ) {
				return $name;
			}

			$_product 			= $item->get_product();
			$order 				= $item->get_order();

			$product_permalink 	= apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $_product->get_permalink( $item ) : '', $item, $order );

			if ( is_object( $_product ) && $_product->is_type( 'variation' ) ) {
				if ( ! $product_permalink ) {
					$name = $_product->get_title();
				} else {
					$name =  sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() );
				}
			}

		}

		return $name;

	}

	/**
	* No user login in second checkout
	*
	* @wp-hook option_woocommerce_enable_checkout_login_reminder
	* @since GM v3.4.1
	* @static
	* @access public
	* @param String $value
	* @param String $option
	* @return String
	**/
	public static function remove_login_from_second_checkout( $value, $option ) {

		if ( defined( 'WGM_CHECKOUT' ) && WGM_CHECKOUT === true ) {
			$value = 'no';
		}

		return $value;
	}

	/**
	* Privacy Declaration for My Account Registration
	*
	* @wp-hook woocommerce_register_form
	* @since 3.6
	* @static
	* @access public
	* @return void
	**/
	public static function my_account_registration_fields() {

		if ( get_option( 'gm_checkbox_5_my_account_registration_activation', 'on' ) == 'on' ) {

			$default_text = __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' );
			$text = get_option( 'gm_checkbox_5_my_account_registration_text', $default_text );
			$text = apply_filters( 'german_market_checkout_checkbox_text_markup', self::replace_placeholders_terms_privacy_revocation( $text ) );

			if ( get_option( 'gm_checkbox_5_my_account_registration_opt_in', 'on' ) == 'on' ) {

				$markup = '<p class="form-row validate-required" id="german_market_privacy_declaration_field" data-priority="999"><span class="woocommerce-input-wrapper">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox inline">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="german_market_privacy_declaration" type="checkbox" id="german_market_privacy_declaration" value="1" /><span>%s</span>&nbsp;<span class="required">*</span>
					</label>
				</span></p>';

				$markup = apply_filters( 'german_market_privacy_registration_markup_opt_in', $markup );
				echo sprintf( $markup, $text );

			} else {

				$markup = '<div class="woocommerce-privacy-policy-text"><p>%s</p></div>';
				$markup = apply_filters( 'german_market_privacy_registration_markup_without_opt_in', $markup );
				echo sprintf( $markup, $text );

			}

		}

	}

	/**
	* Privacy Declaration for Product Reviews
	*
	* @wp-hook woocommerce_product_review_comment_form_args
	* @since 3.6.2
	* @static
	* @access public
	* @param Array $comment_form
	* @return Array
	**/
	public static function product_review_privacy_policy( $comment_form ) {

		if ( get_option( 'gm_checkbox_6_product_review_activation', 'on' ) == 'on' ) {

			$default_text = __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' );
			$text = get_option( 'gm_checkbox_6_product_review_text', $default_text );
			$text = self::replace_placeholders_terms_privacy_revocation( $text );

			if ( get_option( 'gm_checkbox_6_product_review_opt_in', 'on' ) == 'on' ) {
				$privacy_text = '<p class="comment-form-privacy-policy"><input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="german_market_privacy_declaration" id="german_market_privacy_declaration" value="1"><label for="german_market_privacy_declaration" class="german-market-privacy-declaration-product-review"><input type="hidden" name="gm_checkbox_6_product_review_opt_in" value="yes" /><span class="comment-form-privacy-policy-checkbox-text">' . $text . '</span>&nbsp;<span class="required">*</span></label></p>';
			} else {
				$privacy_text = '<p class="comment-form-privacy-policy">' . $text . '</p>';
			}

			$checkbox_markup = apply_filters( 'german_market_product_review_privacy_policy_comment_form', $privacy_text, $comment_form );

			if ( ! is_user_logged_in() ) {

				if ( ! isset( $comment_form[ 'fields' ] ) ) {
					$comment_form[ 'fields' ] = array();
				}

				$comment_form[ 'fields' ][ 'german_market_privacy' ] = $checkbox_markup;
			} else {
				$comment_form[ 'comment_field' ] .= $checkbox_markup;
			}
		}

		return $comment_form;
	}

	/**
	* Privacy Declaration Error Text for Product Reviews
	*
	* @wp-hook preprocess_comment
	* @since 3.6.3
	* @static
	* @param Array $commentdata
	* @access public
	* @return Array
	**/
	public static function product_review_privacy_policy_validation( $commentdata ) {

		// Do not validate during REST API calls
		if ( WGM_Helper::is_rest_api() ) {
			return $commentdata;
		}

		if ( ( get_option( 'gm_checkbox_6_product_review_activation', 'on' ) == 'on' ) && ( get_option( 'gm_checkbox_6_product_review_opt_in', 'on' ) == 'on' ) && ( isset( $_POST[ 'gm_checkbox_6_product_review_opt_in' ] ) ) && ( ! isset( $_POST[ 'german_market_privacy_declaration' ] ) ) ) {

			$error_text = get_option( 'gm_checkbox_6_product_review_error_text', __( 'You must accept the privacy policy.', 'woocommerce-german-market' ) ) . '<p><a href="javascript:history.back()">« ' . __( 'Back', 'woocommerce-german-market' ) . '</a><p>';
			wp_die( $error_text );

		}

		return $commentdata;
	}

	/**
	* Privacy Declaration Error Text for My Account Registration
	*
	* @wp-hook woocommerce_registration_errors
	* @since 3.6
	* @static
	* @param WP_Errors $errors
	* @access public
	* @return WP_Errors
	**/
	public static function my_account_registration_fields_validation_and_errors( $errors ) {

		if ( is_checkout() ) {
			return $errors;
		}

		// Do not validate during REST API calls
		if ( WGM_Helper::is_rest_api() ) {
			return $errors;
		}

		// Hook for 3rd party plugins
		if ( apply_filters( 'german_market_my_account_registration_fields_validation_and_errors_dont_validate', false ) ) {
			return $errors;
		}

		if ( ( get_option( 'gm_checkbox_5_my_account_registration_activation', 'on' ) == 'on' ) && ( get_option( 'gm_checkbox_5_my_account_registration_opt_in', 'on' ) == 'on' ) ) {

			if ( ! isset( $_POST[ 'german_market_privacy_declaration' ] ) ) {

				$error_text = get_option( 'gm_checkbox_5_my_account_registration_error_text', __( 'You must accept the privacy policy.', 'woocommerce-german-market' ) );
				$errors->add( 'german_market_checkbox_5_my_account_registration', $error_text );
			}

		}

		return $errors;

	}

	/**
	* Has Customer chosen a local pickup for delivery
	*
	* @since 3.6.2
	* @static
	* @access public
	* @return Boolean
	**/
	public static function is_cart_local_pickup() {

		$is_local_pickup = false;
		$shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( isset( $shipping_methods[ 0 ] ) ) {
			$shipping_method = $shipping_methods[ 0 ];
			$is_local_pickup = str_replace( 'local_pickup', '', $shipping_method ) != $shipping_method;
		}

		return apply_filters( 'gm_shosen_shipping_method_is_local_pickup_in_cart', $is_local_pickup );
	}

	/**
	* Has Customer chosen a local pickup for delivery in order
	*
	* @since 3.6.2
	* @static
	* @access public
	* @return Boolean
	**/
	public static function is_order_local_pickup( $order ) {

		$is_local_pickup = false;
		$shipping_methods = $order->get_shipping_methods();

		if ( ! empty( $shipping_methods ) ) {

			$shipping_method = array_shift( $shipping_methods );

			if ( $shipping_method->get_method_id() == 'local_pickup' ) {
				$is_local_pickup = true;
			}

		}

		return apply_filters( 'gm_shosen_shipping_method_is_local_pickup_in_order', $is_local_pickup, $order );
	}

	/**
	* Save Customer Note for handling returning to first checkout
	*
	* @since 3.8.2
	* @static
	* @access public
	* @wp-hook woocommerce_checkout_get_value
	* @param  String $value
	* @param  String $key
	* @return String
	**/
	public static function woocommerce_checkout_get_value_order_comments( $value, $key ) {

		if ( $key == 'order_comments' ) {
			if ( ! empty( WGM_Session::get( 'order_comments', 'first_checkout_post_array' ) ) ) {
				$value = WGM_Session::get( 'order_comments', 'first_checkout_post_array' );
			}

		}

		return $value;

	}

	/**
	* Save "ship_to_different_address" for handling returning to first checkout
	*
	* @since 3.8.2
	* @static
	* @wp-hook woocommerce_ship_to_different_address_checked
	* @access public
	* @param  Boolean $boolean
	* @return Boolean
	**/
	public static function woocommerce_ship_to_different_address_checked( $boolean ) {

		if ( ! empty( WGM_Session::get( 'ship_to_different_address', 'first_checkout_post_array' ) ) ) {
			$value = WGM_Session::get( 'ship_to_different_address', 'first_checkout_post_array' );
			if ( boolval( $value ) ) {
				$boolean = true;
			}
		}

		return $boolean;
	}

	/**
	* Get German Market Data of a variable Product (quick)
	*
	* @since 3.9.2
	* @static
	* @access public
	* @param  WC_Product_Variable $variable_product
	* @param  String $type
	* @return String
	**/
	public static function get_variable_data_quick( $variable_product, $type ) {

		$variable_product = apply_filters( 'german_market_used_product_for_price_per_unit', $variable_product );

		if ( isset( self::$run_time_cache[ 'variable_product_data' ][ $variable_product->get_id() ] ) ) {

			if ( isset( self::$run_time_cache[ 'variable_product_data' ][ $variable_product->get_id() ][ $type ] ) ) {
				return  self::$run_time_cache[ 'variable_product_data' ][ $variable_product->get_id() ][ $type ];
			} else {
				return '';
			}

		}

		$have_same_delivery_time 	= true;
		$have_same_tax_rate 		= true;
		$have_same_ppu 				= true;
		$have_same_ppu_prefix 		= true;

		$saved_delivery_time 		= null;
		$saved_tax_rate 			= null;
		$saved_ppu					= null;
		$saved_ppu_prefix			= null;

		$break_delivery_time 		= false;
		$break_tax_rate 			= false;
		$break_ppu 					= false;

		$last_child 				= null;

		$parent_tax_class 			= $variable_product->get_tax_class();

		$children = $variable_product->get_children();

		$children_prices = $variable_product->get_variation_prices( true );
		$children_prices_prices = isset( $children_prices[ 'price' ] ) ? $children_prices[ 'price' ] : array();

		$variable_ppu_weights_off = ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) && ( get_post_meta( $variable_product->get_id(), '_price_per_unit_product_weights_completely_off', true ) == 'on' );

		foreach ( $children as $child ) {

			/*
			* Check Delivery Time
			*/
			if ( ! $break_delivery_time ) {

				$delivery_time = apply_filters( 'german_market_get_delivery_time_quick_variation', get_post_meta( $child, '_lieferzeit', true ), $child, $variable_product ) ;

				if ( intval( $delivery_time ) == - 1 || empty( $delivery_time ) || intval( $delivery_time ) == 0 ) {
					$delivery_time = -1;
				}

				if ( is_null( $saved_delivery_time ) ) {
					$saved_delivery_time = $delivery_time;
				} else {

					if ( $saved_delivery_time != $delivery_time ) {
						$have_same_delivery_time = false;
						$break_delivery_time = true;
					}

				}

			}

			/*
			* Check Tax Class
			*/
			if ( ! $break_tax_rate ) {

				$tax_class = get_post_meta( $child, '_tax_class', true );

				if ( $tax_class == 'parent' ){
					$tax_class = $parent_tax_class;
				}

				if ( is_null( $saved_tax_rate ) ) {
					$saved_tax_rate = $tax_class;
				} else {

					if ( $saved_tax_rate != $tax_class ) {
						$have_same_tax_rate = false;
						$break_tax_rate = true;
					}

				}

			}


			/*
			* Price Per Unit
			*/
			if ( ! $break_ppu ) {
				if ( ( ! $variable_ppu_weights_off ) && get_option( 'woocommerce_de_show_price_per_unit', 'on' ) == 'on' && get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {

					$rtn = array();

					// use setting or parrent setting of variable product
					$parent_setting = get_post_meta( $child, '_v_used_setting_ppu', true );

					if ( intval( $parent_setting  ) != 1 ) {
						$product_id = $variable_product->get_id();
						$prefix 	= '';
					} else {
						$product_id = $child;
						$prefix 	= '_v';
					}

					$variable_weight = get_post_meta( $variable_product->get_id(), '_weight', true );

					if ( isset( $children_prices_prices[ $child ] ) ) {

						$complete_product_price		= $children_prices_prices[ $child ];

						if ( apply_filters( 'woocommerce_de_automatic_calculation_get_variation_price_fast_meta', false ) ) {
							$meta_price = get_post_meta( $child, '_price', true );
							if ( ! empty( $meta_price ) ) {
								$complete_product_price = floatval( $meta_price );
							}
						}

						$complete_product_quantity 	= get_post_meta( $product_id, $prefix . '_auto_ppu_complete_product_quantity', true );
						$rtn[ 'unit' ]				= get_post_meta( $product_id, $prefix . '_unit_regular_price_per_unit', true );
						$rtn[ 'mult' ]				= get_post_meta( $product_id, $prefix . '_unit_regular_price_per_unit_mult', true );
						$rtn[ 'price_per_unit' ] 	= WGM_Price_Per_Unit::automatic_calculation( $complete_product_price, $complete_product_quantity, $rtn[ 'mult' ] );
						$rtn[ 'complete_product_quantity' ] = $complete_product_quantity;

						if ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {

							if ( empty( $complete_product_quantity ) || empty( $rtn[ 'unit' ] ) || empty( $rtn[ 'mult' ] ) ) {

								//$variation = wc_get_product( $child );

								$variation_weight = get_post_meta( $child, '_weight', true );
								$weight = empty( $variation_weight ) ? $variable_weight : $variation_weight;

								$complete_product_quantity 	= wc_get_weight( $weight, get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) ), get_option( 'woocommerce_weight_unit', 'kg' ) );
								$rtn[ 'unit' ]				= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) );
								$rtn[ 'mult' ]				= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_mult', 1 );
								$rtn[ 'price_per_unit' ] 	= WGM_Price_Per_Unit::automatic_calculation( $complete_product_price, $complete_product_quantity, $rtn[ 'mult' ] );
								$rtn[ 'complete_product_quantity' ] = $complete_product_quantity;
							}

						}

						$rtn = apply_filters( 'german_market_ppu_get_price_per_unit_data', $rtn, $variable_product );

						if ( $rtn[ 'price_per_unit' ] ) {

							$return_string_ppu = apply_filters(
								'wmg_price_per_unit_loop',
								sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">[PPU-PREFIX]' . WGM_Price_Per_Unit::get_output_format() . '</span>',
								         wc_price( str_replace( ',', '.', $rtn[ 'price_per_unit' ] ), apply_filters( 'wgm_ppu_wc_price_args', array() ) ),
								         str_replace( '.', wc_get_price_decimal_separator(), $rtn[ 'mult' ] ),
								         $rtn[ 'unit' ]
								),
								wc_price( str_replace( ',', '.', $rtn[ 'price_per_unit' ] ) ),
								$rtn[ 'mult' ],
								apply_filters( 'german_market_measuring_unit', $rtn[ 'unit' ], $variable_product )
							);

							$return_string_ppu_prefix = WGM_Price_Per_Unit::get_prefix( $rtn );

						} else {

							$return_string_ppu = '';
							$return_string_ppu_prefix = '';

						}

						if ( is_null( $saved_ppu ) ) {
							$saved_ppu = $return_string_ppu;
						} else {

							if ( $saved_ppu != $return_string_ppu ) {
								$have_same_ppu = false;
								$break_ppu = true;
							}

						}

						if ( is_null( $saved_ppu_prefix ) ) {
							$saved_ppu_prefix = $return_string_ppu_prefix;
						} else {

							if ( $saved_ppu_prefix != $return_string_ppu_prefix ) {
								$have_same_ppu_prefix = false;
							}

						}

					}

				}

			}

			$last_child = $child;

			/*
			* Break Condition
			*/
			if ( $break_delivery_time && $break_tax_rate && $break_ppu ) {
				break;
			}

		}

		$variable_data = array(
			'delivery_time'		=> '',
			'tax_class'			=> '',
			'ppu'				=> '',
		);

		$last_variation = null;

		if ( $last_child ) {
			$last_variation = wc_get_product( $last_child );
		}

		// Delivery Time
		if ( $have_same_delivery_time ) {
			$variable_data[ 'delivery_time' ] = self::get_deliverytime_string( $last_variation );
		}

		// Tax Rate
		$variable_data[ 'tax_class' ] = array(
			'have_same_tax_class' => $have_same_tax_rate,
			'same_tax_class' => $saved_tax_rate,
		);

		// Price Per Unit
		if ( $have_same_ppu && ! empty( $saved_ppu ) ) {

			$ppu_prefix = '';
			if ( $have_same_ppu_prefix && ! empty( $saved_ppu_prefix ) ) {
				$ppu_prefix = trim( $saved_ppu_prefix ) . ' ';
			}

			$saved_ppu = str_replace( '[PPU-PREFIX]', $ppu_prefix, $saved_ppu );
			$variable_data[ 'ppu' ] = $saved_ppu;
			if ( is_array( $rtn ) ) {
				$variable_data[ 'ppu_data' ] = $rtn;
			}

		}

		if ( ! isset( self::$run_time_cache[ 'variable_product_data' ] ) ) {
			self::$run_time_cache[ 'variable_product_data' ] = array();
		}

		self::$run_time_cache[ 'variable_product_data' ][ $variable_product->get_id() ] = $variable_data;

		return isset( $variable_data[ $type ] ) ? $variable_data[ $type ] : '';

	}

}
