<?php
/**
 * Helper Functions
 *
 * @author jj, ap
 */
class WGM_Helper {

	protected static $run_time_cache = array();

	/**
	 * Returns the decimal length of any scalar value.
	 *
	 * @param   int|float|string|bool $value
	 * @return  int
	 */
	public static function get_decimal_length( $value ) {

		if ( ! is_scalar( $value ) ) {
			return 0;
		}

		$value = (float) $value;

		$value = strrchr( $value, "." );
		$value = substr( $value, 1 );
		$value = strlen( $value );

		return $value;
	}

	/**
	 * Tests against a specific version of Woocommerce
	 *
	 * @param string $min_version
	 *
	 * @return bool
	 */
	public static function woocommerce_version_check( $min_version = '2.5.0-beta' ) {
		return ( version_compare( WC()->version, $min_version === 0 ) );
	}

	/**
	 * Replaces umlauts etc.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public static function get_page_slug( $name ){
		$name = str_replace( '&', '__and__', $name );
		$name = sanitize_title( $name );
		$name = str_replace( '__and__', '&', $name );
		$name = str_replace( '-', '_', $name );
 		$name = strtolower( $name );
		return $name;
	}

	/**
	* get the page_id from db by name of page
	*
	* @access	public
	* @static
	* @param	string $page_name
	* @return	int page_id the page id
	*/
	public static function get_page_id( $page_name ) {
		global $wpdb;

		$page_id = $wpdb->get_var( 'SELECT
										ID
									FROM
										' . $wpdb->posts . '
									WHERE
										post_name = "' . $page_name . '"
									AND
										post_status = "publish"
									AND
										post_type = "page"'
								 );

		return (int) $page_id;
	}

	/**
	* Gets the url to the check page and then to checkout form the core plugin
	*
	* @access	public
	* @uses		get_option, get_permalink, is_ssl
	* @static
	* @return	string link to checkout page
	*/
	public static function get_check_url() {

		$check_page_id = get_option( WGM_Helper::get_wgm_option( 'check' ) );

		//WPML Support
		if( function_exists( 'icl_object_id' ) ) {
			$check_page_id = icl_object_id( $check_page_id, 'page', true );
		}

		$permalink     = get_permalink( $check_page_id );


		if ( is_ssl() )
			$permalink = str_replace( 'http:', 'https:', $permalink );

		return $permalink;
	}

	/**
	* gets the checkout page_id
	*
	* @access	public
	* @uses		get_option
	* @static
	* @return 	int checkout poge id
	*/
	public static function get_checkout_redirect_page_id() {
		return get_option( WGM_Helper::get_wgm_option( 'check' ) );
	}

	/**
	* get checkout page id via filter
	*
	* @param int $checkout_redirect_page_id checkout poge id
	* @return int checkout poge id
	*/
	public static function change_checkout_redirect_page_id ( $checkout_redirect_page_id ) {
		return apply_filters( 'woocommerce_de_get_checkout_redirect_page_id', WGM_Helper::get_checkout_redirect_page_id() );
	}

	/**
	* get the default pages
	*
	* @access public
	* @static
	* @author jj, ap
	* @return array default pages
	*/
	public static function get_default_pages( $lang = null ) {

		if ( ! $lang ) {
			$lang = WGM_Helper::get_installation_language();
		}

		// get data from current user for add pages with his ID
		$user_data = wp_get_current_user();

		foreach( WGM_Defaults::get_default_page_objects( $lang ) as $page ){

			$default_pages[ $page->slug ] = array(
									'post_status'       => $page->status,
									'post_type'         => 'page',
									'post_author'       => (int) $user_data->data->ID,
									'post_name'         => $page->slug,
									'post_title'        => $page->name,
									'post_content'      => apply_filters( 'woocommerce_de_' . $page->slug . '_content', WGM_Template::get_text_template( $page->content ) ),
									'comment_status'    => 'closed'
								);
		}

		return $default_pages;
	}

	/**
	 * Determines wether to show shipping address or not
	 * @author jj, ap
	 * @static
	 * @return bool should show seperate shipping address or not
	 */
	public static function ship_to_billing() {
		if( ! WGM_Session::is_set( 'ship_to_different_address', 'first_checkout_post_array' ) || wc_ship_to_billing_address_only() ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get an Option name for WooCommerce German Market
	 *
	 * @since	1.1.5
	 * @static
	 * @access	public
	 * @param	string $option_index
	 * @return	mixed string of option, when not exist FALSE
	 */
	public static function get_wgm_option( $option_index ) {

		// geht the default option array
		$options = WGM_Defaults::get_options();

		if( isset( $options[ $option_index ] ) )
			return  $options[ $option_index ];
		else
			return FALSE;
	}

	/**
	* update option if not exists
	*
	* @access	public
	* @static
	* @uses		update_option, get_option
	* @param 	string $option
	* @param 	string $value
	* @return	void
	*/
	public static function update_option_if_not_exist( $option, $value ) {
		if( ! get_option( $option ) )
			update_option( $option, $value );
	}

	/**
	 * Checks if the shop is configured to only ship to its base location
	 *
	 * ATTENTION: This is backported from WGM3 and is not currently in use.
	 * It's here for safekeeping as me might need this check here as well
	 *
	 * @return bool
	 */
	//public static function is_domestic_shop() {
	//
	//	if ( get_option( 'woocommerce_allowed_countries' ) !== 'specific' ) {
	//		return FALSE;
	//	}
	//	$base_location = get_option( 'woocommerce_default_country' );
	//
	//	$wc_countries = WC()->countries;
	//	if ( is_null( $wc_countries ) ) {
	//		$wc_countries = new WC_Countries();
	//	}
	//
	//	$allowed = $wc_countries->get_allowed_countries();
	//	if ( count( $allowed ) === 1 && isset( $allowed[ $base_location ] ) ) {
	//		return TRUE;
	//	}
	//
	//	return FALSE;
	//
	//}

	/**
	* inserts a given element before key into given array
	*
	* @access public
	* @author jj, ap
	* @param array $array
	* @param string $key
	* @param string $element
	* @return array items
	*/
	public function insert_array_before_key( $array, $key, $element ) {

		if( in_array( key( $element ), array_keys( $array ) ) )
			return $array;

		$position = array_search( $key ,array_keys( $array ) );
		$before   = array_slice( $array, 0, $position );
		$after    = array_slice( $array, $position );

		return array_merge( $before, $element, $after );
	}

	/**
	 * Adds bodyclass to second checkout
	 * @param array $classes
	 * @return array
	 * @author ap
	 */
	public static function add_checkout_body_classes( $classes) {

		global $woocommerce;

		$classes = ( array ) $classes;

		// id of the second checkout page
		$check_page_id = absint( get_option( WGM_Helper::get_wgm_option( 'check' ) ) );

		if ( $check_page_id > 0 ) {
			
			if ( function_exists( 'icl_object_id' ) ) {
				$check_page_id = icl_object_id( $check_page_id );
			}

			// current page id
			$current_id =  @get_the_ID();

			if( ! empty( $woocommerce ) && is_object( $woocommerce ) && $current_id == $check_page_id ) {
				$classes[] = 'woocommerce';
				$classes[] = 'woocommerce-checkout';
				$classes[] = 'woocommerce-page';
				$classes[] = 'wgm-second-checkout';
			}
		}

		return $classes;
	}

	/**
	 * Enforced certain settings for the small business regulation setting.
	 * @author ap
	 * @return void
	 */
	public static function check_kleinunternehmerregelung(){

		if( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) == 'on' ){
			// Enforce that all prices do not include tax
			update_option( 'woocommerce_prices_include_tax', 'no' );
			// Don't calc the taxes
			update_option( 'woocommerce_calc_taxes', 'no' );
			// Display prices excluding taxes
			update_option( 'woocommerce_tax_display_shop', 'excl' );
			update_option( 'woocommerce_tax_display_cart', 'excl' );
		}
	}


	/**
	 * Filters and replaces deliveryimes
	 *
	 * not in used since 3.8.1
	 *
	 * @param string $string
	 * @param string $deliverytime
	 * @author ap
	 * @return string
	 */
	public static function filter_deliverytimes( $string, $deliverytime ){
		if( $deliverytime == __( 'available for immediate delivery', 'woocommerce-german-market' ) ) {
			/* translators: This is placed in the middle of a longer string, therefore lowercase in English. Should be merged into 1 longer string in a future version. */
			$show_single_price = get_option( 'gm_show_single_price_of_order_items', 'on' ) == 'on';
			$start_comma = $show_single_price ? ', ' : '';
			$string = $start_comma . __( 'delivery time:', 'woocommerce-german-market' ) . ' ' . $deliverytime;
		}
		return $string;
	}

	/**
	 * Removes postcount on deliverytimes backend page
	 * @author ap
	 * @param array $cols
	 * @return array
	 */
	public static function remove_deliverytime_postcount_columns( $cols ){
		unset( $cols['posts'] );
		return $cols;
	}


	/**
	 * @since 3.0.2: filter does nothing if woocommerce-subscriptions is activated and we are in checkout or in cart 
	 *
	 *
	 * @author unknown
	 * @wp-hook woocommerce_countries_inc_tax_or_vat
	 * @wp-hook woocommerce_countries_ex_tax_or_vat
	 * @param String $return
	 * @return return
	 */
	public static function remove_woo_vat_notice( $return ){
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( apply_filters( 'gm_remove_woo_vat_notice_return_original_param', false ) ) {
			return $return;
		}
		
		if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) || is_plugin_active_for_network( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
			if ( ! ( is_cart() || is_checkout() ) ) {
				$return = '';
			}
		} else {
			$return = '';
		}

		return $return;
	}

	/**
	 * Adds additional info to the variation data used by the add-to-cart form
	 *
	 * @wp_hook woocommerce_available_variation
	 *
	 * @param $data
	 * @param $product
	 * @param $variation
	 *
	 * @return array
	 */
	public static function prepare_variation_data( $data, $product, $variation ) {

		if ( WGM_Helper::method_exists( $product, 'get_id' ) && isset( self::$run_time_cache[ 'prepare_variation_data_' . $variation->get_id() ] ) ) {
			$data[ 'price_html' ] .= self::$run_time_cache[ 'prepare_variation_data_' . $variation->get_id() ];
			return $data;
		}

		remove_filter( 'wgm_product_summary_parts', array( 'WGM_Template', 'add_product_summary_price_part' ), 0 );
		//WGM_Template::add_template_loop_shop( $variation );
		$price_html = WGM_Template::get_wgm_product_summary( $variation );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Template', 'add_product_summary_price_part' ), 0, 3 );

		$data[ 'price_html' ] .= $price_html;

		// meta data
		ob_start();

		if ( apply_filters( 'gm_show_itemprop', false ) ) { ?>
			
			<div class="legacy-itemprop-offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
				<meta itemprop="price" content="<?php echo esc_attr( $variation->get_price() ); ?>" />
				<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
				<link itemprop="availability" href="http://schema.org/<?php echo $variation->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />
			</div> 

			<?php

		} 

		$gm_data = ob_get_clean();
		$data[ 'price_html' ] .= $gm_data;
		
		if ( WGM_Helper::method_exists( $product, 'get_id' ) ) {
			if ( ! empty( $gm_data ) ) {
				self::$run_time_cache[ 'prepare_variation_data_' . $variation->get_id() ] = $gm_data;
			}
		}

		return $data;
	}


	public static function is_digital( $product_id = 0 ){
		
		//When the product_id is an array get the first entry as the product id
		if( is_array($product_id) )
			$product_id = current($product_id);

		if( $product_id == 0 ){
			$product = wc_get_product();
		} else {
			$product = wc_get_product( $product_id );
		}

		$is_digital = null;

		if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
			return $is_digital;
		}

		$is_digital = 'yes' === $product->get_meta( '_digital' );

		if ( 'variable' === $product->get_type() ) {
			
			$is_digital = true;
			$children = $product->get_children();

			if ( isset( self::$run_time_cache[ 'variable_product_is_digital_' . $product_id ] ) ) {
				$is_digital = self::$run_time_cache[ 'variable_product_is_digital_' . $product_id ];
			} else {

				foreach ( $children as $child ) {
					
					if ( 'yes' != get_post_meta( $child, '_digital', true ) ) {
						$is_digital = false;
						break;
					}
				}

			}
		}

		self::$run_time_cache[ 'variable_product_is_digital_' . $product_id ] = $is_digital;

		return apply_filters( 'wgm_digital_product', $is_digital, $product );
	}

	public static function paypal_fix( $args ){
		$args[ 'return' ] = urldecode( $args[ 'return' ] );
		$args[ 'cancel_return' ] = urldecode( $args[ 'cancel_return' ] );

		return $args;
	}

	/**
	 * Change the checkout button of gateways to "next"
	 *
	 * @param $template_name
	 * @param $template_path
	 * @param $located
	 * @param $args
	 */
	public static function change_payment_gateway_order_button_text( $template_name, $template_path, $located, $args ) {

		if ( $template_name == 'checkout/payment-method.php' && is_object( $args[ 'gateway' ] ) && ! empty( $args[ 'gateway' ]->order_button_text ) ) {
			/* translators: Button during checkout, will lead either to the final order confirmation page, or to an external payment provider. */
			$button_text = WGM_Template::change_order_button_text( $args[ 'gateway' ]->order_button_text );
			$button_text = apply_filters( 'woocommerce_de_buy_button_text_gateway_' . $args[ 'gateway' ]->id, $button_text, $args[ 'gateway' ]->order_button_text );
			$args[ 'gateway' ] ->order_button_text = $button_text;
		}
	}

	/**
	 * Disable shipping for virtual products
	 * @deprecated since 2.6.9
	 * @param bool $need_shipping
	 * @access public
	 * @since 2.4.10
	 * @author ap
	 * @wp-hook woocommerce_cart_needs_shipping
	 * @return bool $need_shipping
	 */
	public static function virtual_needs_shipping( $need_shipping ){
		_deprecated_function(__FUNCTION__, '2.6.9' );
		return $need_shipping;
	}

	public static function get_default_tax_label(){
		$tax_label = get_option(WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) );
		if( $tax_label && trim( $tax_label ) != ''){
			return $tax_label;
		} else {
			/* translators: fallback for default tax label */
			return __( 'VAT', 'woocommerce-german-market' );
		}
	}

	public static function only_digital( $order ) {

		$only_digital = false;

		if ( is_object( $order ) && method_exists( $order, 'get_items' ) ) {
			
			$cart = $order->get_items();
			$dcount = 0;

			foreach( $cart as $item ) {

				if ( empty( $item[ 'variation_id' ] ) ) {
					$product = wc_get_product( $item['product_id'] );
				} else {
					$product = wc_get_product( $item[ 'variation_id' ] );
				}

				// check if product is set
				if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
					continue;
				}

				if ( WGM_Helper::is_digital( $product->get_id() ) ){
					$dcount++;
				}
			}

			if ( $dcount == count( $cart ) ) {
				$only_digital = true;
			}
		}

		return $only_digital;
	}

	public static function order_has_digital_product( WC_Order $order ) {

		$has_digital = false;
		
		foreach ( $order->get_items() as $item ) {
			
			$product = false;

			if ( empty( $item[ 'variation_id' ] ) ) {
				
				if ( isset( $item[ 'product_id' ] ) ) {
					$product = wc_get_product( $item[ 'product_id' ] );
				}
				

			} else {
				$product = wc_get_product( $item[ 'variation_id' ] );
			}

			
			if ( $product && WGM_Helper::method_exists( $product, 'get_id' ) ) {
				
				if ( WGM_Helper::is_digital( $product->get_id() ) ){
					$has_digital = true;
					break;
				}
			}

		}

		return $has_digital;
	}

	/**
	 * Check if the current visit is a rest api call
	 *
	 * @since 3.8.2
	 * @return boolean
	 */
	public static function is_rest_api() {

		$prefix = rest_get_url_prefix();

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST || isset( $_GET['rest_route'] ) && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix, 0 ) === 0 ) {
			return true;
		}

		$rest_url    = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}

	/**
	 * Get Translatable Options
	 *
	 * @since 3.9.2
	 * @return Array
	 */
	public static function get_translatable_options() {

		// Checkout Strings
		$options = array(
			'woocommerce_de_last_checkout_hints'											=> '',
			'woocommerce_de_estimate_cart_text' 											=> __( 'Note: Shipping and taxes are estimated and will be updated during checkout based on your billing and shipping information.', 'woocommerce-german-market' ),
			'woocommerce_de_avoid_free_items_in_cart_message'								=> __( 'Sorry, you can\'t proceed to checkout. Please contact our support.', 'woocommerce-german-market' ),
			'woocommerce_de_order_button_text'												=> __( 'Place binding order', 'woocommerce-german-market' ),
			'woocommerce_de_checkbox_text_digital_content'									=> __( 'For digital content: You explicitly agree that we continue with the execution of our contract before expiration of the revocation period. You hereby also declare you are aware of the fact that you lose your right of revocation with this agreement.', 'woocommerce-german-market' ),
			'woocommerce_de_checkbox_text_digital_content_notice'							=> __( 'Notice: Digital content are products not being delivered on any physical medium (e.g. software downloads, e-books etc.).', 'woocommerce-german-market' ),
			'woocommerce_de_learn_more_about_shipping_payment_revocation'					=> __( 'Learn more about [link-shipping]shipping costs[/link-shipping], [link-payment]payment methods[/link-payment] and our [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' ),
			'vat_options_notice'															=> __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ),
			'vat_options_non_eu_notice'														=> __( 'Tax-exempt export delivery', 'woocommerce-german-market' ),
			'vat_options_label'																=> __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ),
			'gm_small_trading_exemption_notice'												=> WGM_Template::get_default_ste_string(),
			'gm_small_trading_exemption_notice_extern_products'								=> WGM_Template::get_default_ste_string(),
			'wgm_default_tax_label'															=> __( 'VAT', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_text_no_digital'							=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_text_digital_only_digital'					=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_text_mix_digital'							=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy], the [link-revocation]revocation policy[/link-revocation] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_error_text_no_digital'						=> __( 'You must accept our Terms & Conditions, privacy policy and revocation policy.', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_error_text_digital_only_digital'			=> __( 'You must accept our Terms & Conditions, privacy policy and revocation policy for digital content.', 'woocommerce-german-market' ),
			'german_market_checkbox_1_tac_pd_rp_error_text_mix_digital'						=> __( 'You must accept our Terms & Conditions, privacy policy, revocation policy and revocation policy for digital content.', 'woocommerce-german-market' ),
			'woocommerce_de_checkbox_error_text_digital_content'							=> __( 'Please confirm the waiver for your rights of revocation regarding digital content.', 'woocommerce-german-market' ),
			'german_market_checkbox_3_shipping_service_provider_text'						=> __( 'I agree that my personal data is send to the shipping service provider.', 'woocommerce-german-market' ),
			'german_market_checkbox_3_shipping_service_provider_error_text'					=> __( 'You have to agree that your personal data is send to the shipping service provider.', 'woocommerce-german-market' ),
			'german_market_checkbox_4_custom_text'											=> '',
			'german_market_checkbox_4_custom_error_text'									=> '',
			'gm_checkbox_product_depending_text_1'											=> '',
			'gm_checkbox_product_depending_error_text_1'									=> '',
			'gm_checkbox_product_depending_text_2'											=> '',
			'gm_checkbox_product_depending_error_text_2'									=> '',
			'gm_checkbox_product_depending_text_3'											=> '',
			'gm_checkbox_product_depending_error_text_3'									=> '',
			'gm_checkbox_product_depending_text_4'											=> '',
			'gm_checkbox_product_depending_error_text_4'									=> '',
			'gm_checkbox_5_my_account_registration_text'									=> __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' ),
			'gm_checkbox_5_my_account_registration_error_text'								=> __( 'You must accept the privacy policy.', 'woocommerce-german-market' ),
			'gm_checkbox_6_product_review_text'												=> __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' ),
			'gm_checkbox_6_product_review_error_text'										=> __( 'You must accept the privacy policy.', 'woocommerce-german-market' ),
			'german_market_checkbox_age_rating_text'										=> __( 'I confirm that I am at least [age] years of age.', 'woocommerce-german-market' ),
			'german_market_checkbox_age_rating_error_text'									=> __( 'You have to confirm that you are at least [age] years of age.', 'woocommerce-german-market' ),
			'gm_order_confirmation_mail_subject'											=> __( 'Your {site_title} order confirmation from {order_date}', 'woocommerce-german-market' ),
			'gm_order_confirmation_mail_heading'											=> __( 'Order Confirmation', 'woocommerce-german-market' ),
			'gm_order_confirmation_mail_text'												=> __( 'With this e-mail we confirm that we have received your order. However, this is not a legally binding offer until payment is received.', 'woocommerce-german-market' ),
			'woocommerce_de_show_extra_cost_hint_eu_text'									=> __( 'Additional costs (e.g. for customs or taxes) may occur when shipping to non-EU countries.', 'woocommerce-german-market' ),
			'german_market_add_to_cart_in_shop_pages_text'									=> __( 'Show Product', 'woocommerce-german-market' ),
			'wgm_double_opt_in_customer_registration_autodelete_extratext'					=> __( 'If you don\'t activate your account, it will be automatically deleted after [days] days.', 'woocommerce-german-market' ),
			'gm_default_template_requirements_digital'										=> '',
			'woocommerce_de_ppu_outpout_format'												=> '([price] / [mult] [unit])',
			'woocommerce_de_ppu_outpout_format_prefix'										=> '',
			'wcevc_general_tax_output_text_string'											=> __( 'Incl. tax', 'woocommerce-german-market' ),
			'gm_legal_information_product_reviews_custom_text'								=> '',
			'gm_legal_information_product_reviews_short_information_text'					=> __( 'Information on verifying the authenticity of reviews', 'woocommerce-german-market' ),
		);

		for ( $i = 1; $i<= 10; $i++ ) {
			if ( get_option( 'de_shop_emails_file_attachment_' . $i ) != '' ) {
				$options[ 'de_shop_emails_file_attachment_' . $i ] = '';
			}
		}

		$add_ons = WGM_Add_Ons::get_activated_add_ons();

		// Invoice PDF
		if ( isset( $add_ons[ 'woocommerce-invoice-pdf' ] ) ) {
			
			$options[ 'wp_wc_invoice_pdf_file_name_frontend' ] 					= get_bloginfo( 'name' ) . '-' . __( 'Invoice-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_file_name_backend' ] 					= __( 'Invoice-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_billing_address_additional_notation' ] = get_bloginfo( 'name' );
			$options[ 'wp_wc_invoice_pdf_invoice_start_subject' ] 				= __( 'Invoice for order {{order-number}} ({{order-date}})', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_invoice_start_welcome_text' ] 			= '';
			$options[ 'wp_wc_invoice_pdf_text_after_content' ] 					= '';
			$options[ 'wp_wc_invoice_pdf_page_numbers_text' ] 					= __( 'Page {{current_page_number}} of {{total_page_number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_fine_print_custom_content' ] 			= '';
			$options[ 'wp_wc_invoice_pdf_refund_file_name_frontend' ] 			= get_bloginfo( 'name' ) . '-' . __( 'Refund-{{refund-id}}-for-order-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_refund_file_name_backend' ] 			= __( 'Refund-{{refund-id}}-for-order-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_refund_start_subject_big' ] 			= __( 'Refund {{refund-id}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_refund_start_subject_small' ] 			= __( 'For order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_refund_start_refund_date' ] 			= __( 'Refund date<br />{{refund-date}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_refund_start_welcome_text' ] 			= '';
			$options[ 'wp_wc_invoice_pdf_refund_text_after_content' ] 			= '';
			$options[ 'wp_wc_invoice_pdf_view_order_button_text' ] 				= __( 'Download Invoice Pdf', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_additional_pdfs_file_name_terms' ] 	= __( 'Terms and conditions', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_additional_pdfs_file_name_revocation' ]= __( 'Revocation Policy', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_emails_link_label_text' ] 				= __( 'Download Invoice Pdf', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_emails_link_text' ] 					= __( 'Please use the following link to download your invoice PDF: {invoice_download_link}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_emails_refunds_link_label_text' ] 		= __( 'Download refund pdf', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_emails_refunds_link_text' ]			= __( 'Please use the following link to download your refund PDF: {invoice_download_link}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_girocode_remit_recipient_name' ]		= wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$options[ 'wp_wc_invoice_pdf_girocode_remit_recipient_iban' ]		= '';
			$options[ 'wp_wc_invoice_pdf_girocode_remit_recipient_bic' ]		= '';
			$options[ 'wp_wc_invoice_pdf_girocode_remit_remittance_text' ]		= __( 'Order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_girocode_text' ]						= WGM_Helper::get_default_text_next_to_qr_code();
			$options[ 'wp_wc_invoice_pdf_girocode_text_under' ]					= __( 'Girocode', 'woocommerce-german-market' );
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_name' ] 			= wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_address' ] 		= get_option( 'woocommerce_store_address', '' );
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_postcode' ] 		= get_option( 'woocommerce_store_postcode', '' );
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_city' ] 			= get_option( 'woocommerce_store_city', '' );
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_country' ] 		= '';
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_qr_iban' ] 					= '';
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_customer_id' ] 				= '';
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_v3_iban' ] 					= '';
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_creditor_reference' ] 			= '';
			$options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_additional_information_text' ] 	= __( 'Order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'woocommerce_de_manual_order_confirmation_payment_methods_hint_text' ] = __( 'There are no payment methods available on the checkout page. Your order will be checked manually. If the order is accepted, you will receive an email with a link to pay for the order. There you can choose a payment method.', 'woocommerce-german-market' );

			$header_columns = get_option( 'wp_wc_invoice_pdf_header_number_of_columns', 1 );
			for ( $i = 1; $i <= $header_columns; $i++ ) {
				$options[ 'wp_wc_invoice_pdf_header_column_' . $i . '_text' ] = '';
			}

			$footer_columns = get_option( 'wp_wc_invoice_pdf_footer_number_of_columns', 1 );
			for ( $i = 1; $i <= $footer_columns; $i++ ) {
				$options[ 'wp_wc_invoice_pdf_footer_column_' . $i . '_text' ] = '';
			}

		}

		// Invoice Numbers
		if ( isset( $add_ons[ 'woocommerce-running-invoice-number' ] ) ) {
			
			$options[ 'wp_wc_running_invoice_completed_order_email_subject' ] 				= __( 'Invoice {{invoice-number}} for order {{order-number}} from ({{order-date}})', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_completed_order_email_header' ] 				= __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_subject' ] 								= __( 'Invoice {{invoice-number}} for order {{order-number}} from {{order-date}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_header' ] 								= __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_subject_paid' ] 							= __( 'Invoice {{invoice-number}} for order {{order-number}} from {{order-date}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_header_paid' ] 							= __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_subject_refunded' ] 						= __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_email_header_refunded' ] 						= __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' );

			$options[ 'wp_wc_running_invoice_pdf_file_name_backend' ]						= __( 'Invoice-{{invoice-number}}-Order-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_file_name_frontend' ]						= __( 'Invoice-{{invoice-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_subject' ] 								= __( 'Invoice {{invoice-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_date' ] 									= __( 'Invoice Date<br />{{invoice-date}}', 'woocommerce-german-market' );

			$options[ 'wp_wc_running_invoice_pdf_file_name_backend_refund' ] 				= __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_file_name_frontend_refund' ] 				= __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_refund_start_subject_big' ] 				= __( 'Refund {{refund-number}}', 'woocommerce-german-market' );
			$options[ 'wp_wc_running_invoice_pdf_refund_start_subject_small' ] 				= __( 'For order {{order-number}}', 'woocommerce-german-market' );

		}

		// Return Delivery
		if ( isset( $add_ons[ 'woocommerce-return-delivery-pdf' ] ) ) {
			
			$options[ 'woocomerce_wcreapdf_wgm_pdf_file_name' ] 							= __( 'Retoure', 'woocommerce-german-market' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_author' ] 								= get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_title' ] 								= __( 'Retoure', 'woocommerce-german-market' ) . ' - ' . get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_shop_name' ] 							= get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_address' ] 								= '';
			$options[ 'woocomerce_wcreapdf_wgm_pdf_shop_small_headline' ] 					= __( 'Order: {{order-number}} ({{order-date}})', 'woocommerce-german-market' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_remark' ] 								= '';
			$options[ 'woocomerce_wcreapdf_wgm_pdf_reasons' ] 								= '';
			$options[ 'woocomerce_wcreapdf_wgm_pdf_footer' ] 								= '';

			$options[ 'woocomerce_wcreapdf_wgm_pdf_file_name_delivery' ] 					= __( 'Delivery-Note', 'woocommerce-german-market' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_author_delivery' ] 						= get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_title_delivery' ] 						= __( 'Delivery Note', 'woocommerce-german-market' ) . ' - ' . get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_shop_name_delivery' ] 					= get_bloginfo( 'name' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_address_delivery' ] 						= '';
			$options[ 'woocomerce_wcreapdf_wgm_pdf_shop_small_headline_delivery' ] 			= __( 'Order: {{order-number}} ({{order-date}})', 'woocommerce-german-market' );
			$options[ 'woocomerce_wcreapdf_wgm_pdf_remark_delivery' ] 						= '';
			$options[ 'woocomerce_wcreapdf_wgm_pdf_footer_delivery' ] 						= '';

			$options[ 'woocomerce_wcreapdf_wgm_view-order-button-text' ] 					= __( 'Download Return Delivery Pdf', 'woocommerce-german-market' );
		}

		// Return Delivery
		if ( isset( $add_ons[ 'fic' ] ) ) {
			$options[ 'gm_fic_ui_frontend_labels_ingredients' ] 					= __( 'Ingredients', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_frontend_labels_nutritional_values' ] 				= __( 'Nutritional Values', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_frontend_labels_allergens' ] 						= __( 'Allergens', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_frontend_remark_nutritional_values' ] 				= __( 'Nutritional values per 100g', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_frontend_prefix_nutritional_values' ] 				= __( '- of which', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_alocohol_default_unit' ] 							= __( '% vol', 'woocommerce-german-market' );
			$options[ 'gm_fic_ui_alocohol_prefix' ] 								= __( 'alc.', 'woocommerce-german-market' );
		}

		return $options;

	}

	/**
	 * tests if $object is an object and the method exisits in this object
	 *
	 * @sice 3.11
	 * @param mixed $object
	 * @param String $method_name
	 * @return bool
	 */
	public static function method_exists( $object, $method_name ) {
		return is_object( $object ) && method_exists( $object, $method_name );
	}

	/**
	 * Do not save customer ip in order
	 *
	 * @since 3.12
	 * @wp-hook woocommerce_checkout_create_order    
	 * @param WC_Order $order
	 * @param Array $data
	 * @return void
	 */
	public static function do_not_save_customer_ip_in_order( $order, $data ) {
		if ( 'on' === get_option( 'woocommerce_de_do_not_save_customer_ip_in_order', 'off' ) ) {
			if ( method_exists( $order, 'set_customer_ip_address' ) ) {
				$order->set_customer_ip_address( apply_filters( 'german_market_do_not_save_customer_ip_in_order_ip', '', $order, $data ) );
			}
		}
	}

	/**
	 * Get "tax exempt" status of an order as String
	 *
	 * @param	WC_Order $order
	 * @return	String
	 */
	public static function wcvat_woocommerce_order_details_status( $order ) {

		$status       = '';
		$eu_countries = WC()->countries->get_european_union_countries();
		$country      = $order->get_billing_country();
		$postcode 	  = $order->get_billing_postcode();
		$uk_exception = false;

		if ( 'shipping' == apply_filters( 'wcvat_tax_based_on', get_option( 'woocommerce_tax_based_on', 'billing' ) ) && '' != $order->get_shipping_country() ) {
			$country = $order->get_shipping_country();
			$postcode = $order->get_shipping_postcode();
		}

		if ( get_post_meta( $order->get_id(), 'billing_vat', true ) != '' ) {

			if ( $country != WC()->countries->get_base_country() ) {
				
				if ( in_array( $country, $eu_countries ) ) {
					$status = 'tax_free_intracommunity_delivery';
				} else if ( 'GB' === $country ) {
				
					if ( is_object( $order ) && method_exists( $order, 'get_date_created' ) ) {
						$date_created = $order->get_date_created();
						$first_of_january = new DateTime( '2021-01-01 00:00:00' );
						if ( $date_created < $first_of_january ) {
							$status = 'tax_free_intracommunity_delivery';
							$uk_exception = true;
						}
					}
				}
			}
		}

		if ( ( ! in_array( $country, $eu_countries ) ) && ( ! $uk_exception ) ) {
			$status = 'tax_exempt_export_delivery';
		} else if ( self::is_vat_postcode_exemptions( $country, $postcode ) ) {
			$status = 'tax_exempt_export_delivery';
		}

		// only print notice if order has no taxes!
		// (maybe someone entered an invalid vat id)
		if ( $order->get_total_tax() > 0.0 ) {
			$status = '';
		}

		return $status;
	}

	/**
	* Returns whether the postocde of an given eu country is an exemption for eu vat
	*
	* @param String $country
	* @param String $postcode
	* @return Boolean
	*/ 
	public static function is_vat_postcode_exemptions( $country, $postcode ) {

		$is_exemption = false;

		$exemptions = self::get_vat_postcode_exemptions();

		if ( isset( $exemptions[ $country ] ) ) {
			foreach ( $exemptions[ $country ] as $post_code_exemption ) {

				$post_code_exemption_length = strlen( $post_code_exemption );
				if ( ( ! empty( $postcode ) && strlen( $postcode ) >= $post_code_exemption_length ) ) {
					if ( $post_code_exemption === substr( $postcode, 0, $post_code_exemption_length ) ) {
						$is_exemption = true;
					}
				}
			}
		}

		return apply_filters( 'german_marekt_is_eu_vat_postcode_exemption', $is_exemption, $country, $postcode );
	}

	/**
	 * get vat postcode exemptions for eu countries 
	 * 
	 * @return Array
	 */
	public static function get_vat_postcode_exemptions() {

		return array(

			'DE' 	=> array( '27498', '78266' ),
			'ES'	=> array( '35', '38', '51', '52' ),
			'GR'	=> array( '63086', '63087' ),
			'FR'	=> array( '971', '972', '973', '974', '976' ),
			'IT'	=> array( '22060', '23030' ),
			'FI'	=> array( '22' )

		);
	}

	/**
	* get default text for option 'wp_wc_invoice_pdf_girocode_text'
	* 
	* @static
	* @return String
	*/
	public static function get_default_text_next_to_qr_code() {
		$text = __( "Please transfer the total amount of {{order-total}}.", 'woocommerce-german-market' );
		$text .= PHP_EOL . __( "Use your banking app and scan the QR code\nto make the transfer conveniently.", 'woocommerce-german-market' );
		return $text;
	}

	/**
	* get install language
	* 
	* @static
	* @return String
	*/
	public static function get_installation_language() {
		
		$lang = get_option( 'WPLANG' );

		if ( empty( $lang ) ) {
			$lang = get_locale();
		}

		$lang = apply_filters( 'german_market_get_installation_language', $lang );

		if ( substr( $lang, 0, 2 ) == 'de' ) {
			$lang = 'de';
		} else {
			$lang = 'en';
		}

		return apply_filters( 'german_market_get_installation_language_return_value', $lang );
	}

	/**
	* load German Market textdomain of installing language
	* 
	* @static
	* @return void
	*/
	public static function load_installation_language_textdomain() {

		unload_textdomain( Woocommerce_German_Market::$textdomain );
		add_filter( 'plugin_locale', array( __CLASS__, 'plugin_locale' ), 10, 2 );
		load_plugin_textdomain( Woocommerce_German_Market::$textdomain, FALSE, dirname( Woocommerce_German_Market::$plugin_base_name ) . '/languages' );

		do_action( 'german_market_after_load_installation_language_textdomain' );

	}

	/**
	* re-load German Market textdomain after installation
	* 
	* @static
	* @return void
	*/
	public static function reset_textdomain() {

		unload_textdomain( Woocommerce_German_Market::$textdomain );
		remove_filter( 'plugin_locale', array( __CLASS__, 'plugin_locale' ), 10, 2 );
		load_plugin_textdomain( Woocommerce_German_Market::$textdomain, FALSE, dirname( Woocommerce_German_Market::$plugin_base_name ) . '/languages' );

		do_action( 'german_market_after_reset_textdomain' );

	}

	/**
	* filter to change German Market locale 
	* 
	* @static
	* @wp-hook 
	* @return void
	*/
	public static function plugin_locale( $locale, $domain = '' ) {

		if ( 'de' === self::get_installation_language() ) {
			
			if ( ( 'de_DE_formal' === get_option( 'WPLANG' ) || 'de_CH' === get_option( 'WPLANG' ) ) && apply_filters( 'german_market_install_plugin_locale_formal', true ) ) {
				$locale = 'de_DE_formal';
			} else {
				$locale = 'de_DE';
			}
			
		} else {
			$locale = 'en_US';
		}

		return $locale;
	}

}

if ( 'off' === get_option( 'wgm_add_on_woocommerce_eu_vatin_check', 'off' ) && ( ! function_exists( 'wcvat_vat_exempt_first_login' ) ) ) {

	/**
	 * Get "tax exempt" status of an order as String
	 *
	 * @param	WC_Order $order
	 * @return	String
	 */
	function wcvat_woocommerce_order_details_status( $order ) {
		return WGM_Helper::wcvat_woocommerce_order_details_status( $order );
	}

}