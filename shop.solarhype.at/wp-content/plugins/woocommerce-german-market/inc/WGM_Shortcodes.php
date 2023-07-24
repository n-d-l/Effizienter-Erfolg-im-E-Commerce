<?php

/**
 * Shortcodes
 *
 * @author jj,ap
 */
Class WGM_Shortcodes {

	public static function register() {

		// Disclaimer
		add_shortcode( 'woocommerce_de_disclaimer_deadline', 		array( __CLASS__, 'add_shortcode_disclaimer_deadline' ) );
		add_shortcode( 'woocommerce_de_disclaimer_address_data', 	array( __CLASS__, 'add_shortcode_disclaimer_address_data' ) );

		// Second Checkout
		if ( Woocommerce_German_Market::is_frontend() ) {
			add_shortcode( 'woocommerce_de_check', 					array( __CLASS__, 'add_shortcode_check' ) );
		}

		// General Shortcodes for German Market Product Data
		add_shortcode( 'gm_product_tax_info', 						array( __CLASS__, 'tax_callback' ) );
		add_shortcode( 'gm_product_shipping_info', 					array( __CLASS__, 'shipping_info_callback' ) );
		add_shortcode( 'gm_product_delivery_time', 					array( __CLASS__, 'delivery_time_callback' ) );
		add_shortcode( 'gm_product_ppu', 							array( __CLASS__, 'ppu_callback' ) );

		add_shortcode( 'gmgtin', 									array( __CLASS__, 'gtin_callback' ) );			// LEGACY
		add_shortcode( 'gm_product_gtin', 							array( __CLASS__, 'gtin_callback' ) );

		// Shortcode for age rating is only active if age rating is active in German Market
		if ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) {
			add_shortcode( 'gm_product_age_rating', 				array( __CLASS__, 'age_rating_callback' ) );
		}

		add_shortcode( 'gm_extra_costs_non_eu', 					array( __CLASS__, 'extra_costs_non_eu_callback' ) );

		add_shortcode( 'gm_product_sale_label', 					array( __CLASS__, 'sale_label_callback' ) );

		add_shortcode( 'gm_product_digital_prerequisits', 			array( __CLASS__, 'digital_prerequisits_callback' ) );

		// DEPRECATED
		//remove_shortcode( 'woocommerce_pay' );
		add_shortcode( 'gm_product_review_info', 					array( __CLASS__, 'add_shortcode_product_review_info' ) );
	}

	/**
	* callback for shortcode [gm_product_review_info]
	*
	* @param Array $atts
	* @return String
	*/
	public static function add_shortcode_product_review_info( $atts = array() ) {
		$gm_legal_information_product_reviews = WGM_Legal_Information_Product_Reviews::get_instance();
		$review_info = sprintf( $gm_legal_information_product_reviews->get_markup_before_review(), $gm_legal_information_product_reviews->get_info_text() );
		$review_info = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $review_info, $atts );
		return apply_filters( 'gm_product_review_info', $review_info );
	}

	/**
	* callback for shortcode [gm_product_extra_costs_non_eu]
	*
	* @param Array $atts
	* @return String
	*/
	public static function extra_costs_non_eu_callback( $atts = array() ) {

		global $product;
		$used_product = self::get_product_by_shortcode_attribute( $atts, $product ); // even if the string does not depend on the product

		$extra_costs_non_eu = apply_filters( 'wgm_show_extra_costs_eu_html',
				sprintf(
					'<small class="wgm-info wgm-extra-costs-eu">%s</small>',
					get_option( 'woocommerce_de_show_extra_cost_hint_eu_text', __( 'Additional costs (e.g. for customs or taxes) may occur when shipping to non-EU countries.', 'woocommerce-german-market' ))
				)
			);

		$extra_costs_non_eu = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $extra_costs_non_eu, $atts );

		return apply_filters( 'german_market_shortcode_extra_costs_non_eu_callback', $extra_costs_non_eu, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_age_rating]
	*
	* @param Array $atts
	* @return String
	*/
	public static function age_rating_callback( $atts = array() ) {

		global $product;
		$age_rating	= '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		$product_age_rating_without_intval = '';

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			$product_age_rating = WGM_Age_Rating::get_age_rating_or_product( $product );
			$product_age_rating_without_intval = WGM_Age_Rating::get_age_rating_or_product( $product, false );
		}

		if ( '' === $product_age_rating_without_intval ) {

			if ( '' === get_option( 'german_market_age_rating_default_age_rating', '' ) ) {
				return '';
			}

			$product_age_rating = intval( get_option( 'german_market_age_rating_default_age_rating', '' ) );
		}

		$prefix = isset( $atts[ 'prefix' ] ) ? $atts[ 'prefix' ] : '';
		$suffix = isset( $atts[ 'suffix' ] ) ? $atts[ 'suffix' ] : '';

		$product_age_rating_string = $prefix . $product_age_rating . $suffix;

		if ( isset( $atts[ 'hide_if_zero' ] ) && 'yes' === $atts[ 'hide_if_zero' ] ) {
			if ( intval( $product_age_rating ) === 0 ) {
				$product_age_rating_string = '';
			}
		}

		return apply_filters( 'german_market_shortcode_age_rating_callback', $product_age_rating_string, $used_product, $atts );
	}

	/**
	 * Add GTIN Shortcode
	 *
	 * @since 3.8.2
	 * @param Array $atts
	 * @return String
	 */
	public static function gtin_callback( $atts = array() ) {

		global $product;
		$gtin = '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			$gtin = $used_product->get_meta( '_gm_gtin' );
		}

		return apply_filters( 'german_market_shortcode_gtin_callback', $gtin, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_tax_info]
	*
	* @param Array $atts
	* @return String
	*/
	public static function tax_callback( $atts = array() ) {

		global $product;
		$tax_info = '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			$tax_info = WGM_Tax::text_including_tax( $used_product );
		}

		$tax_info = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $tax_info, $atts );

		return apply_filters( 'german_market_shortcode_tax_callback', $tax_info, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_shipping_info]
	*
	* @param Array $atts
	* @return String
	*/
	public static function shipping_info_callback( $atts = array() ) {

		global $product;
		$shipping_info = '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			$shipping_info = WGM_Shipping::get_shipping_page_link( $used_product );
		}

		// Check for free shipping advertising option
		$free_shipping = get_option( 'woocommerce_de_show_free_shipping' ) === 'on';

		$shipping_link_template = sprintf(
			'<div class="wgm-info woocommerce_de_versandkosten">%s</div>',
			$shipping_info
		);

		//TODO Deprecate 2nd parameter (used to $stopped_by_option, which has always been false since there was a return before this filter was able to run)
		$shipping_link_template = apply_filters( 'wgm_product_shipping_info', $shipping_link_template, FALSE, $free_shipping, $used_product );

		$shipping_link_template = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $shipping_link_template, $atts );

		return apply_filters( 'german_market_shortcode_shipping_info_callback', $shipping_link_template, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_proudct_delivery_time]
	*
	* @param Array $atts
	* @return String
	*/
	public static function delivery_time_callback( $atts = array() ) {

		global $product;
		$delivery_time = '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {

			$delivery_time_label_overwrite = isset( $atts[ 'label' ] ) ? $atts[ 'label' ] : false;

			ob_start();
			WGM_Template::add_template_loop_shop( $used_product, $delivery_time_label_overwrite );
			$delivery_time = ob_get_clean();
		}

		$delivery_time = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $delivery_time, $atts );

		return apply_filters( 'german_market_shortcode_delivery_time_callback', $delivery_time, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_ppu]
	*
	* @param Array $atts
	* @return String
	*/
	public static function ppu_callback( $atts = array() ) {

		global $product;
		$ppu_string = '';

		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		if ( WGM_Helper::method_exists( $used_product, 'get_id' ) ) {

			$output_parts = array();

			if ( is_a( $used_product, 'WC_Product_Variation' ) || is_a( $product, 'WC_Product_Variable' ) ) {
				$output_parts = wcppufv_add_price_per_unit( $output_parts, $used_product, false );
			} else {
				$output_parts[ 'ppu' ] = WGM_Price_Per_Unit::get_price_per_unit_string( $used_product );
			}

			if ( isset( $output_parts[ 'ppu' ] ) ) {
				$ppu_string = $output_parts[ 'ppu' ];
			}
		}

		$ppu_string = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $ppu_string, $atts );

		return apply_filters( 'german_market_shortcode_ppu_callback', $ppu_string, $used_product, $atts );
	}

	/**
	* get product bei attribute
	*
	* @param Array $atts
	* @param WC_Product $product
	* @return WC_Product || false
	*/
	public static function get_product_by_shortcode_attribute( $atts = array(), $product = null ) {

		if ( isset( $atts[ 'product_id' ] ) ) {
			$id = $atts[ 'product_id' ];
			$product = wc_get_product( $id );
		}

		return $product;
	}

	/**
	* maybe remove html markup
	*
	* @param String $return_string
	* @param Array $atts
	* @return String
	*/
	public static function maybe_remove_markup_of_shortcode_return_value_by_attribute( $return_string, $atts ) {

		if ( isset( $atts[ 'markup' ] ) && 'no' === $atts[ 'markup' ] ) {
			$return_string = strip_tags( $return_string );
		} else if ( isset( $atts[ 'markup' ] ) && 'only_a_tags' === $atts[ 'markup' ] ) {
			$return_string = strip_tags( $return_string, '<a>' );
		}

		return $return_string;
	}

	/**
	 * Shortcode for the amount of days to withdraw to include in Disclaimer page
	 *
	 * @access      public
	 * @static
	 * @uses        get_option
	 * @return    string days and singular/plural of day
	 */
	public static function add_shortcode_disclaimer_deadline() {

		$option = get_option( WGM_Helper::get_wgm_option( 'widerrufsfrist' ), 14 );
		$days   = absint( $option );
		$string = sprintf(
			_n( '%s day', '%s days', $days, 'woocommerce-german-market' ),
			$days
		);

		return $string;
	}

	/**
	 * withdraw address shortcode for the disclaimer page
	 *
	 * @access      public
	 * @uses        get_option
	 * @static
	 * @return    string withdraw address
	 */
	public static function add_shortcode_disclaimer_address_data() {

		return nl2br( get_option( WGM_Helper::get_wgm_option( 'widerrufsadressdaten' ) ) );
	}

	/**
	 * Shortcode for the second checkout page in the german Version
	 *
	 * @access    public
	 * @static
	 * @return    string template conents
	 */
	public static function add_shortcode_check() {

		ob_start();

		WGM_Template::load_template( 'second-checkout2.php' );

		$tpl = ob_get_contents();
		ob_end_clean();

		return $tpl;
	}

	/**
	* callback for shortcode [gm_product_sale_label]
	*
	* @param Array $atts
	* @return String
	*/
	public static function sale_label_callback( $atts = array() ) {

		global $product;
		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		$sale_label = WGM_Template::add_sale_label_to_price( '', $used_product, false );

		return apply_filters( 'german_market_shortcode_sale_label_callback', $sale_label, $used_product, $atts );
	}

	/**
	* callback for shortcode [digital_prerequisits]
	*
	* @param Array $atts
	* @return String
	*/
	public static function digital_prerequisits_callback( $atts = array() ) {
		
		global $product;
		$used_product = self::get_product_by_shortcode_attribute( $atts, $product );

		$digital_prerequisits = WGM_Template::get_digital_product_prerequisits( $used_product );
		$digital_prerequisits = self::maybe_remove_markup_of_shortcode_return_value_by_attribute( $digital_prerequisits, $atts );
		
		return apply_filters( 'german_market_shortcode_digital_prerequisits_callback', $digital_prerequisits, $used_product, $atts );
	}
}
