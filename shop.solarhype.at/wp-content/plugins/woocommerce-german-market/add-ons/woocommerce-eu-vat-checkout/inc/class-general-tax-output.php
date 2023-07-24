<?php

class WGM_General_Tax_Output {

	/**
	 * @var WGM_Age_Rating
	 * @since v3.7.2
	 */
	private static $instance = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Compatibilities
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_General_Tax_Output();	
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		$general_tax_output_activation = get_option( 'wcevc_general_tax_output_activation', 'off' );

		if ( 'on' === $general_tax_output_activation ) {

			$this->activate_general_tax_output_product_level();
			$this->activate_general_tax_output_totals();
			$this->exception_for_invoice_pdf();
			$this->exception_for_emails();
			$this->avoid_same_tax_line_in_split_tax_html();

		} else if ( 'product_level_and_cart' === $general_tax_output_activation ) {

			$this->activate_general_tax_output_product_level();
			add_action( 'wp', array( $this, 'activate_general_taxoutput_on_cart' ) );
 			add_action( 'wp', array( $this, 'avoid_same_tax_line_in_split_tax_html_in_cart' ) );

		} else if ( 'product_level' === $general_tax_output_activation ) {
			$this->activate_general_tax_output_product_level();
		}
	}

	/**
	 * Avoid multiple lines "inc. vat" in split tax html
	 * @return void
	 */
	public function avoid_same_tax_line_in_split_tax_html() {
		add_filter( 'german_market_get_split_tax_html_add_tax_line', array( __CLASS__, 'german_market_get_split_tax_html_add_tax_line' ), 10, 3 );
	}

	/**
	 * Callback for "Avoid multiple lines "inc. vat" in split tax html"
	 * 
	 * @wp-hook german_market_get_split_tax_html_add_tax_line
	 * @static
	 * @param Boolean $boolean
	 * @param String $new_html
	 * @param String $html
	 * @return Boolean
	 */
	public static function german_market_get_split_tax_html_add_tax_line( $boolean, $new_html, $html ) {

		if ( str_replace( $new_html, '', $html ) != $html ) {
			$boolean = false;
		}

		return $boolean;
	}

	/**
	 *  "Avoid multiple lines "inc. vat" in split tax html" only in cart
	 * 
	 * @wp-hook wp
	 * @return void
	 */
	public function avoid_same_tax_line_in_split_tax_html_in_cart() {
		if ( function_exists( 'is_cart' ) ) {
			if ( is_cart() ) {
				$this->avoid_same_tax_line_in_split_tax_html();
			}
		}
	}

	/**
	 * add hooks to activate general tax output on product level
	 * 
	 * @return void
	 */
	public function activate_general_tax_output_product_level() {
		
		if ( ! has_filter( 'wgm_product_summary_parts_after', array( __CLASS__, 'product_summary_parts_after' ) ) ) {
			add_filter( 'wgm_product_summary_parts_after',	array( __CLASS__, 'product_summary_parts_after' ), 20, 3 );
		}
		
		if ( ! has_filter( 'wgm_get_tax_line', array( __CLASS__, 'get_tax_line' ) ) ) {
			add_filter( 'wgm_get_tax_line',	array( __CLASS__, 'get_tax_line' ), 20, 2 );
		}
		
		if ( ! has_filter( 'german_market_mini_cart_price_tax', array( __CLASS__, 'mini_cart_price_tax' ) ) ) {
			add_filter( 'german_market_mini_cart_price_tax', array( __CLASS__, 'mini_cart_price_tax' ), 20 );
		}
	}

	/**
	 * removes hooks to deactivate general tax output on product level
	 * 
	 * @return void
	 */
	public function deactivate_general_tax_output_product_level() {

		if ( has_filter( 'wgm_product_summary_parts_after', array( __CLASS__, 'product_summary_parts_after' ) ) ) {
			remove_filter( 'wgm_product_summary_parts_after',	array( __CLASS__, 'product_summary_parts_after' ), 20, 3 );
		}
		
		if ( has_filter( 'wgm_get_tax_line', array( __CLASS__, 'get_tax_line' ) ) ) {
			remove_filter( 'wgm_get_tax_line',	array( __CLASS__, 'get_tax_line' ), 20, 2 );
		}
		
		if ( has_filter( 'german_market_mini_cart_price_tax', array( __CLASS__, 'mini_cart_price_tax' ) ) ) {
			remove_filter( 'german_market_mini_cart_price_tax', array( __CLASS__, 'mini_cart_price_tax' ), 20 );
		}
	}

	/**
	 * add hooks to activate general tax output for total tax
	 * 
	 * @return void
	 */
	public function activate_general_tax_output_totals() {

		if ( ! has_filter( 'wgm_get_totals_tax_string', array( __CLASS__, 'get_totals_tax_string' ) ) ) {
			add_filter( 'wgm_get_totals_tax_string', array( __CLASS__, 'get_totals_tax_string' ), 20, 4 );
		}
		
		if ( ! has_filter( 'wgm_get_excl_incl_tax_string', array( __CLASS__, 'get_excl_incl_tax_string' ) ) ) {
			add_filter( 'wgm_get_excl_incl_tax_string',	array( __CLASS__, 'get_excl_incl_tax_string' ), 20, 4 );
		}
	}

	/**
	 * removes hooks to deactivate general tax output for total tax
	 * 
	 * @return void
	 */
	public function deactivate_general_tax_output_totals() {

		if ( has_filter( 'wgm_get_totals_tax_string', array( __CLASS__, 'get_totals_tax_string' ) ) ) {
			remove_filter( 'wgm_get_totals_tax_string', array( __CLASS__, 'get_totals_tax_string' ), 20, 4 );
		}
		
		if ( has_filter( 'wgm_get_excl_incl_tax_string', array( __CLASS__, 'get_excl_incl_tax_string' ) ) ) {
			remove_filter( 'wgm_get_excl_incl_tax_string',	array( __CLASS__, 'get_excl_incl_tax_string' ), 20, 4 );
		}
	}

	/**
	 * if general output is activated everywhere: do not apply the hooks in invoice pdf
	 * 
	 * @return void
	 */
	public function exception_for_invoice_pdf() {
		add_action( 'wp_wc_invoice_pdf_start_template', array( $this, 'invoice_pdf_start_template' ) );
		add_action( 'wp_wc_invoice_pdf_end_template', array( $this, 'invoice_pdf_end_template' ) );
	}

	/**
	 * removes hook before invoice pdf contents starts and deactivate caching for tax string
	 * 
	 * @wp-hook wp_wc_invoice_pdf_start_template
	 * @return void
	 */
	public function invoice_pdf_start_template() {
		$this->deactivate_general_tax_output_totals();
		add_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );
	}

	/**
	 * undo "invoice_pdf_start_template" add the end of invoice pdf template
	 * 
	 * @wp-hook wp_wc_invoice_pdf_end_template
	 * @return void
	 */
	public function invoice_pdf_end_template() {
		$this->activate_general_tax_output_totals();
		remove_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );
	}

	/**
	 * if general output is activated everywhere: do not apply the hooks in selected emails (exceptions)
	 * 
	 * @return void
	 */
	public function exception_for_emails() {
		add_filter( 'woocommerce_email_header', array( $this, 'email_header' ), 10, 2 );
		add_action( 'woocommerce_email_footer', array( $this, 'email_footer' ), 10, 1 );
	}

	/**
	 * removes hook before emails contents starts and deactivate caching for tax string
	 * 
	 * @wp-hook woocommerce_email_header
	 * @return void
	 */
	public function email_header( $email_heading, $email = false ) {

		add_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );
		
		if ( $email && isset( $email->id ) ) {

			$email_exceptions = get_option( 'wcevc_general_tax_output_exceptions_for_emails', array() );
			if ( is_array( $email_exceptions ) ) {
				
				$email_id = 'customer_partially_refunded_order' === $email->id ? 'customer_refunded_order' : $email->id;

				if ( in_array( $email_id, $email_exceptions ) ) {
					$this->deactivate_general_tax_output_totals();
				}
			}
		}
	}

	/**
	 * undo "email_header" add the end of email template
	 * 
	 * @wp-hook wp_wc_invoice_pdf_end_template
	 * @return void
	 */
	public function email_footer( $email = false ) {

		remove_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );

		if ( $email && isset( $email->id ) ) {

			$email_exceptions = get_option( 'wcevc_general_tax_output_exceptions_for_emails', array() );
			if ( is_array( $email_exceptions ) ) {
				
				$email_id = 'customer_partially_refunded_order' === $email->id ? 'customer_refunded_order' : $email->id;

				if ( in_array( $email_id, $email_exceptions ) ) {
					$this->activate_general_tax_output_totals();
				}
			}
		}
	}

	/**
	 * activate general tax output on cart
	 * used if general tax output is enabled on product level and cart
	 * @wp-hook wp
	 * 
	 * @return void
	 */
	public function activate_general_taxoutput_on_cart() {

		if ( function_exists( 'is_cart' ) ) {
			if ( is_cart() ) {
				$this->activate_general_tax_output_totals();
			}
		}
 	}

	/**
	* General Tax output
	* 
	* @wp-hook wgm_get_totals_tax_string
	* @param String $tax_total_string
	* @param Array $tax_string_array
	* @param String $tax_totals
	* @param Mixed $tax_display
	* @return String
	*/
	public static function get_totals_tax_string( $tax_total_string, $tax_string_array, $tax_totals, $tax_display ) {
		
		if ( ! empty( $tax_total_string ) ) {
			$tax_total_string = '<span class="wgm-tax includes_tax"><br />' . get_option( 'wcevc_general_tax_output_text_string', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</span>';
		}

		return $tax_total_string;
	}

	/**
	* General Tax output
	* 
	* @wp-hook wgm_product_summary_parts_after
	* @param Array $output_parts
	* @param WC_Product $product
	* @param String $hook
	* @return Array
	*/
	public static function product_summary_parts_after( $output_parts, $product, $hook ) {

		if ( isset( $output_parts[ 'tax' ] ) && ! empty( $output_parts[ 'tax' ] ) ) {
			if ( ! empty( trim( strip_tags( $output_parts[ 'tax' ] ) ) ) ) {
				$output_parts[ 'tax' ] = '<div class="wgm-info woocommerce-de_price_taxrate ">' . get_option( 'wcevc_general_tax_output_text_string', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</div>';
			}
		}
		
		return $output_parts;

	}

	/**
	* General Tax output
	* 
	* @wp-hook wgm_get_tax_line
	* @param String $tax_line
	* @param WC_Product $product
	* @return String
	*/
	public static function get_tax_line( $tax_line, $product ) {

		if ( ! empty( $tax_line ) ) {
			$tax_line = get_option( 'wcevc_general_tax_output_text_string', __( 'Incl. tax', 'woocommerce-german-market' ) );
		}

		return $tax_line;
	}

	/**
	* General Tax output
	* 
	* @wp-hook wgm_get_excl_incl_tax_string
	* @param String $msg
	* @param String $type
	* @param String $rate
	* @param String $amount
	* @return String
	*/
	public static function get_excl_incl_tax_string( $msg, $type, $rate, $amount ) {
		
		if ( ! empty( $msg ) ) {
			$msg = get_option( 'wcevc_general_tax_output_text_string', __( 'Incl. tax', 'woocommerce-german-market' ) );
		}

		return $msg;
	}

	/**
	* General Tax output, Mini cart
	* 
	* @wp-hook german_market_mini_cart_price_tax
	* @param String $string
	* @return String
	*/
	public static function mini_cart_price_tax( $string ) {

		if ( ! empty( $string ) ) {
			$string = '<div class="wgm-info woocommerce-de_price_taxrate">' . get_option( 'wcevc_general_tax_output_text_string', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</div>';
		}

		return $string;
	}

}
