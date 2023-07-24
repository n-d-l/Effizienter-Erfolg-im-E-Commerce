<?php
/**
 * General Defaults
 *
 *  @author jj, ap
 */
Class WGM_Defaults {

	/**
	 * holds key for $lieferzeit_string
	 * default shipping of 48 if not defined
	 *
	 * @access	public
	 * @static
	 * @var		int
	 */
	public static $default_lieferzeit_id = 3;

	/**
	 * Currency
	 *
	 * @access	public
	 * @static
	 * @var string
	 */
	public static $woocommerce_de_currency	= 'EUR';

	/**
	 * Default Country
	 *
	 * @access	public
	 * @static
	 * @var string
	 */
	public static $woocommerce_de_default_country = 'DE';

	/**
	 * Default Pages
	 *
	 * @access	private
	 * @static
	 * @var		std object
	 */
	private static $default_pages = array();

	/**
	 * Default Options
	 *
	 * @access	private
	 * @static
	 * @var array
	 */
	private static $options = array();

	/**
	 * Constructor
	 */
	public function __construct () {

		// to be sure, that the slugs ar in option array when calling options
		WGM_Defaults::generate_default_page_objects( WGM_Helper::get_installation_language() );
	}

	/**
	 * General options
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_options
	 * @since	1.1.5
	 * @return	array
	 */
	public static function get_options() {
		if( ! empty( WGM_Defaults::$options ) ) {
			return WGM_Defaults::$options;
		} else {
			WGM_Defaults::generate_options();
			return WGM_Defaults::$options;
		}
	}

	/**
	 * General options
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_options
	 * @since	1.1.5
	 * @return	array
	 */
	public static function generate_options() {

		$options = array(
			'agb'                                                        => 'woocommerce_terms_page_id',
			'check'                                                      => 'woocommerce_check_page_id',
			'widerruf'                                                   => 'woocommerce_widerruf_page_id',
			'widerruf_fuer_digitale_medien'                              => 'woocommerce_widerruf_fuer_digitale_medien_page_id',
			'impressum'                                                  => 'woocommerce_impressum_page_id',
			'datenschutz'                                                => 'woocommerce_datenschutz_page_id',
			'zahlungsarten'                                              => 'woocommerce_zahlungsarten_page_id',
			'versandkosten'                                              => 'woocommerce_versandkosten_page_id',
			'versandkosten__lieferung'                                   => 'woocommerce_versandkosten__lieferung_page_id',
			'widerrufsfrist'                                             => 'woocommerce_widerrufsfrist',
			'global_lieferzeit'                                          => 'woocommerce_global_lieferzeit',
			'global_sale_label'                                          => 'woocommerce_global_sale_label',
			'woocommerce_de_show_sale_label_overview'                    => 'woocommerce_de_show_sale_label_overview',
			'widerrufsadressdaten'                                       => 'woocommerce_widerrufsadressdaten',
			'woocommerce_options_installed'                              => 'woocommerce_options_installed',
			'load_woocommerce-de_standard_css'                           => 'load_woocommerce-de_standard_css',
			'woocommerce_de_append_terms_to_mail'                        => 'woocommerce_de_append_terms_to_mail',
			'woocommerce_de_append_imprint_to_mail'                      => 'woocommerce_de_append_imprint_to_mail',
			'woocommerce_de_append_withdraw_to_mail'                     => 'woocommerce_de_append_withdraw_to_mail',
			'woocommerce_de_show_widerrufsbelehrung'                     => 'woocommerce_de_show_widerrufsbelehrung',
			'woocommerce_de_show_delivery_time_overview'                 => 'woocommerce_de_show_delivery_time_overview',
			'woocommerce_de_show_shipping_fee_overview'                  => 'woocommerce_de_show_shipping_fee_overview',
			'woocommerce_de_show_shipping_fee_overview_single'           => 'woocommerce_de_show_shipping_fee_overview_single',
			'woocommerce_de_show_free_shipping'                          => 'woocommerce_de_show_free_shipping',
			'woocommerce_de_show_show_short_desc'                        => 'woocommerce_de_show_show_short_desc',
			'woocommerce_de_use_backend_footer_text_for_imprint_enabled' => 'woocommerce_de_use_backend_footer_text_for_imprint_enabled',
			'woocommerce_de_show_Widerrufsbelehrung'                     => 'woocommerce_de_show_Widerrufsbelehrung',
			'woocommerce_de_disclaimer_cart'                             => 'woocommerce_de_disclaimer_cart',
			'woocommerce_de_estimate_cart'                               => 'woocommerce_de_estimate_cart',
			'woocommerce_de_previous_installed'                          => 'woocommerce_de_previous_installed',
			'woocommerce_de_show_extra_cost_hint_eu'                     => 'woocommerce_de_show_extra_cost_hint_eu',
			'woocommerce_de_kleinunternehmerregelung'                    => 'woocommerce_de_kleinunternehmerregelung',
			'woocommerce_de_show_price_per_unit'                         => 'woocommerce_de_show_price_per_unit',
			'wgm_dual_shipping_option'                                   => 'wgm_dual_shipping_option',
			'wgm_default_tax_label'                                      => 'wgm_default_tax_label',
			'wgm_display_digital_revocation'                             => 'wgm_display_digital_revocation',
			'wgm_use_split_tax'                                          => 'wgm_use_split_tax',
			'wgm_send_order_confirmation_mail'                           => 'wgm_send_order_confirmation_mail',
			'wgm_plain_text_order_confirmation_mail'                     => 'wgm_plain_text_order_confirmation_mail'
		);

		WGM_Defaults::$options = apply_filters( 'woocommerce_de_options', $options );
	}

	/**
	 * Default pages
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_default_pages
	 * @since	1.1.5
	 * @return	array
	 */
	public static function get_default_pages_with_option() {

		// Mandatory pages in Germany and Austria
		$pages = array(
				/* translators: Mandatory page containing general legal business info, e.g. Impressum in German. */
				'woocommerce_impressum_page_id'                 => __( 'Legal Information', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing info regarding shipping methods + costs and periods of delivery. */
				'woocommerce_versandkosten__lieferung_page_id'  => __( 'Shipping & Delivery', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing info regarding rights of revocation. Correct legal terms may vary depending on country. e.g. German: Widerruf, Austria: Rücktritt */
				'woocommerce_widerruf_page_id'                  => __( 'Revocation', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing refering to specific German legislation regarding digital goods, e.g. file downloads */
				'woocommerce_widerruf_fuer_digitale_medien_page_id' => __( 'Revocation Policy for Digital Content', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing disclaimers regarding data security and privacy. */
				'woocommerce_datenschutz_page_id'               => __( 'Privacy', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing a listing of payment methods offered by the online store. */
				'woocommerce_zahlungsarten_page_id'             => __( 'Payment Methods', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing all order data + final checkout button. */
				'woocommerce_check_page_id'                     => __( 'Confirm & Place Order', 'woocommerce-german-market' ),

				/* translators: Mandatory page containing general terms and conditions of the online store. */
				'woocommerce_terms_page_id'                     => __( 'Terms and Conditions', 'woocommerce-german-market' ),

		);
		return $pages;
	}

    public static function get_default_pages(){
        $_pages = WGM_Defaults::get_default_pages_with_option();
        return apply_filters( 'woocommerce_de_default_pages', array_values( $_pages ) );
    }

	/**
	 * Get the default page objects
	 *
	 * @static
	 * @access	public
	 * @return	Object  default pages objects
	 */
	public static function get_default_page_objects( $lang = 'de' ) {
		WGM_Defaults::generate_default_page_objects( $lang );
		return WGM_Defaults::$default_pages[ $lang ];
	}

	/**
	 * Manual translate option names.
	 * @param $option
	 * @return mixed
	 */
	public static function get_german_option_name( $option ) {

		$options = array(
			'imprint'			    => 'impressum',
			'shipping_&_delivery'	=> 'versandkosten',
			'revocation'		    => 'widerruf',
			'privacy'			    => 'datenschutz',
			'confirm_&_place_order'	=> 'zahlungsarten',
			'check_order_pay'	    => 'check'
		);

		if( isset( $options[ $option ] ) )
			return $options[ $option ];
		else
			return $option;
	}

	/**
	 * generate the default page objects
	 *
	 * @static
	 * @access	public
	 * @return	void
	 */
	private static function generate_default_page_objects( $lang = 'de' ) {

		// get default pages
		$pages = WGM_Defaults::get_default_pages_with_option();

		$default_pages = new stdClass();

		$drafts = array();

		if ( $lang == 'de' ) {
			
			$htmls = array(
				'legal_information'						=> 'impressum',
				'shipping_&_delivery'					=> 'versand_&_lieferung',
				'revocation'							=> 'widerruf',
				'revocation_policy_for_digital_content'	=> 'widerruf_fuer_digitale_inhalte',
				'privacy'								=> 'datenschutz',
				'payment_methods'						=> 'zahlungsweisen',
				'confirm_&_place_order'					=> 'bestellung_bestaetigen_&_absenden',
				'terms_and_conditions'					=> 'allgemeine_geschaeftsbedingungen',
			);

		} else {

			$htmls = array(
				'legal_information'						=> 'en/legal_information',
				'shipping_&_delivery'					=> 'en/shipping_and_delivery',
				'revocation'							=> 'en/revocation_policy',
				'revocation_policy_for_digital_content'	=> 'en/revocation_policy_for_digital_content',
				'privacy'								=> 'en/privacy',
				'payment_methods'						=> 'en/payment_methods',
				'confirm_&_place_order'					=> 'bestellung_bestaetigen_&_absenden',
				'terms_and_conditions'					=> 'en/terms_and_conditions',
			);

		}

		foreach ( $pages as $option => $page ){

			$slug = WGM_Helper::get_page_slug( $page );
			$file = isset( $htmls[ $slug ] ) ? $htmls[ $slug ] : $slug;

			if ( $slug ) {
				$default_pages->$slug = new stdClass();
				$default_pages->$slug->name      = $page;
				$default_pages->$slug->slug      = $slug;
				$default_pages->$slug->content   = $file . '.html';
				$default_pages->$slug->status    = in_array($page, $drafts) ? 'draft' : 'publish';
			}

			// set the slug to the options
			WGM_Defaults::$options[ $slug ] = $option;
		}
		WGM_Defaults::$default_pages[ $lang ] = $default_pages;
	}

	/**
	 * Registers default delivery time strings
	 *
	 * @access	public
	 * @static
	 */
	public static function register_default_lieferzeiten_strings() {

		$option = 'wgm_default_lieferzeiten_registered';

		if ( !get_option( $option, false ) ) {

			WGM_Helper::load_installation_language_textdomain();

			add_option( $option, true );

			$defaults = array(
				__( 'available for immediate delivery', 'woocommerce-german-market' ),
				__( 'ca. 24 hours', 'woocommerce-german-market' ),
				__( 'ca. 48 hours', 'woocommerce-german-market' ),
				__( 'ca. 2-3 workdays', 'woocommerce-german-market' ),
				__( 'ca. 3-4 workdays', 'woocommerce-german-market' ),
				__( 'ca. 10 workdays', 'woocommerce-german-market' ),
				__( 'ca. 14 workdays', 'woocommerce-german-market' ),
				__( 'ca. 30 workdays', 'woocommerce-german-market' ),
				__( 'currently not available', 'woocommerce-german-market' ),
				__( 'no delivery time (e.g. download)', 'woocommerce-german-market' )
			);

			$defaults = apply_filters( 'woocommerce_de_default_delivery_times', $defaults );

			// Add terms
			foreach ($defaults as $key => $value) {
				wp_insert_term( $value, 'product_delivery_times' );
			}

			WGM_Helper::reset_textdomain();
		}
	}

	/**
	 * Registers default delivery time strings
	 *
	 * @access	public
	 * @static
	 */
	public static function register_default_sale_strings() {

		$option = 'wgm_default_sale_labels_registered';

		if ( ! get_option( $option, FALSE ) ) {
			
			WGM_Helper::load_installation_language_textdomain();

			add_option( $option, TRUE );

			$defaults = array(
				__( 'MSRP: ', 'woocommerce-german-market' ),
				__( 'Former MSRP ', 'woocommerce-german-market' ),
				__( 'Previously ', 'woocommerce-german-market' ),
			);

			$defaults = apply_filters( 'woocommerce_de_default_sale_labels', $defaults );

			// Add terms
			foreach ( $defaults as $key => $value ) {
				$result = wp_insert_term( $value, 'product_sale_labels' );
			}

			$tt_id = $result;

			/**
			 * This check is probably unneeded, but it helped testing/debugging things and does not hurt
			 */
			if ( is_wp_error( $result ) ) {
				$tt_id = $result->error_data[ 'term_exists' ];
			}
			update_option( WGM_Helper::get_wgm_option( 'global_sale_label' ), $tt_id );

			WGM_Helper::reset_textdomain();
		}
	}

	/**
	 * Lieferzeit strings
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_lieferzeit_strings
	 * @since	1.1.5
	 * @return	array
	 */
	public static function get_lieferzeit_strings() {

		// Get the terms
		$terms = array_map(
			array( 'WGM_Defaults', 'wgm_return_translated_terms' ),
			get_terms( 'product_delivery_times', array(
				'orderby' => 'id',
				'hide_empty' => 0
			)
		));

		if( ! in_array( __( 'Use the default', 'woocommerce-german-market' ), $terms ) )
			$term = array_unshift( $terms ,  __( 'Use the default', 'woocommerce-german-market' ) );

		return apply_filters( 'woocommerce_de_lieferzeit_strings', $terms );
	}

	/**
	 * @param $taxonomy
	 *
	 * @return array
	 */
	public static function get_term_strings( $taxonomy ) {

		$terms = get_terms( $taxonomy, array( 'orderby' => 'id', 'hide_empty' => 0 ) );
		$out   = array();

		foreach ( $terms as $term ) {
			$out[ $term->term_id ] = __( $term->name, 'woocommerce-german-market' );
		}

		return $out;
	}

	/**
	 * Function for array_map
	 *
	 * @access	public
	 * @static
	 * @param 	string $term
	 * @since	2.1.2
	 * @return	string
	 */
	public static function wgm_return_translated_terms( $term ) {

		return __( $term->name, 'woocommerce-german-market' );
	}


	/**
	 * Get the default tax rates
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_default_tax_rates
	 * @since	1.1.5
	 * @return	array
	 */
	public static function get_default_tax_rates() {

		$base_country = get_option( 'woocommerce_default_country', 'DE' );

		if ( $base_country == 'AT' ) {

			$default_de_tax_rates = array(
				array( 'countries' => array( 'AT' => array( '*' ) ),
					 'rate'	 => number_format( 20.0, 4 ),
					 'shipping'  => 'yes',
					 'class'	=> '',
					 /* translators: abbrevation for Value Added Tax, e.g. “MwSt.” (DE) or “USt.” (AT) */
					 'label'	 => __( 'VAT', 'woocommerce-german-market' ),
					 'compound'  => 'no'
					 ),
				array( 'countries' => array( 'AT' => array( '*' ) ),
					 'rate'	 => number_format( 10.0, 4 ),
					 'shipping'  => 'yes',
					 'class'	=> 'reduced-rate',
					 'label'	 => __( 'VAT', 'woocommerce-german-market' ),
					 'compound'  => 'no'
					 )
			);

		} else {
			
			$default_de_tax_rates = array(
				array( 'countries' => array( 'DE' => array( '*' ) ),
					 'rate'	 => number_format( 19.0, 4 ),
					 'shipping'  => 'yes',
					 'class'	=> '',
					 /* translators: abbrevation for Value Added Tax, e.g. “MwSt.” (DE) or “USt.” (AT) */
					 'label'	 => __( 'VAT', 'woocommerce-german-market' ),
					 'compound'  => 'no'
					 ),
				array( 'countries' => array( 'DE' => array( '*' ) ),
					 'rate'	 => number_format( 7.0, 4 ),
					 'shipping'  => 'yes',
					 'class'	=> 'reduced-rate',
					 'label'	 => __( 'VAT', 'woocommerce-german-market' ),
					 'compound'  => 'no'
					 )
			);

		}

		return apply_filters( 'woocommerce_de_default_tax_rates',  $default_de_tax_rates );
	}

	/**
	 * Get default product attributes
	 *
	 * @access	public
	 * @static
	 * @hook	woocommerce_de_default_procuct_attributes
	 * @since	1.1.5
	 * @return	array
	 */
	public static function get_default_product_attributes() {

		$scale_units = array(
		  array( 'tag-name' => 'kg', 'tag-slug' => 'kg','description' => __( 'Kilograms', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'g',  'tag-slug' => 'g', 'description' => __( 'Grams', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'mg', 'tag-slug' => 'mg','description' => __( 'Milligrams', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'L',  'tag-slug' => 'L', 'description' => __( 'Liters', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'ml', 'tag-slug' => 'ml','description' => __( 'Milliliters', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'm',  'tag-slug' => 'm', 'description' => __( 'Meters', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'cm', 'tag-slug' => 'cm','description' => __( 'Centimeters', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => _x( 'Piece', 'Measturing Unit', 'woocommerce-german-market' ), 'tag-slug' => 'piece','description' => _x( 'Piece', 'Measturing Unit', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'cm²', 'tag-slug' => 'square-cm','description' => __( 'Square centimeters', 'woocommerce-german-market' ) ),
		  array( 'tag-name' => 'm²', 'tag-slug' => 'square-cm','description' => __( 'Square meters', 'woocommerce-german-market' ) ),
		);

		$default_product_attributes	= array(
										array(  'attribute_name'	=> sanitize_title( 'Measuring Unit' ),
												'attribute_label'	=> __( 'Measuring Unit', 'woocommerce-german-market' ),
												'attribute_type'	=> 'select',
												'elements'			=> $scale_units,
										)
									);

		return apply_filters( 'woocommerce_de_default_product_attributes',  $default_product_attributes );
	}
}
