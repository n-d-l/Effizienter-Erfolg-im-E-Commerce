<?php

/**
 * Class WGM_Sepa_Direct_Debit
 *
 * German Market Gateway SEPA Direct Debit
 *
 * @author MarketPress
 */
class WGM_Sepa_Direct_Debit {

	/**
	 * @var WGM_Sepa_Direct_Debit
	 * @since v3.3
	 */
	private static $instance = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Sepa_Direct_Debit
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Sepa_Direct_Debit();
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once dirname( Woocommerce_German_Market::$plugin_filename ) . '/gateways/WGM_Gateway_Sepa_Direct_Debit.php';

		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings', array() );

		if ( isset( $sdd_settings[ 'woocommerce_german_market_sepa_direct_debit_fee' ] ) ) {
			$costs = $sdd_settings[ 'woocommerce_german_market_sepa_direct_debit_fee' ];
			if ( floatval( str_replace( ',', '.', $costs ) ) > 0.0 ) {
				WGM_Gateways::set_gateway_fee( 'german_market_sepa_direct_debit' , $sdd_settings[ 'woocommerce_german_market_sepa_direct_debit_fee' ] );
			}
		}

		add_filter( 'woocommerce_payment_gateways', array( $this, 'german_market_add_sepa_direct_debit' ) );

		/*
		/* validation of additional checkout fields
		*/
		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'on' ) {
			add_filter( 'gm_checkout_validation_first_checkout', array( 'WGM_Gateway_Sepa_Direct_Debit', 'validate_required_fields' ) );
		}

		if ( ! is_admin() ) {
			
			add_action( 'wp', function() {

				if ( self::need_to_vaildate_sepa_checkbox() ) {

					add_filter( 'woocommerce_de_review_order_after_submit', array( 'WGM_Gateway_Sepa_Direct_Debit', 'checkout_field_checkbox' ) );

					if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) === 'off' ) {
				   		add_filter( 'woocommerce_after_checkout_validation', array( 'WGM_Gateway_Sepa_Direct_Debit', 'checkout_field_checkbox_validation' ) );
				   	}
				}
			});

			if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) === 'on' ) {
				add_filter( 'gm_checkout_validation_fields_second_checkout', array( 'WGM_Gateway_Sepa_Direct_Debit', 'checkout_field_checkbox_validation_second_checkout' ) );
			}
		}

		// ajax
		if ( isset( $sdd_settings[ 'enabled' ] ) ) {
			if ( $sdd_settings[ 'enabled' ] == 'yes' ) {
				add_action( 'wp_ajax_gm_sepa_direct_debit_mandate_preview', 		array( $this, 'ajax_mandate_preview' ) );
				add_action( 'wp_ajax_nopriv_gm_sepa_direct_debit_mandate_preview', 	array( $this, 'ajax_mandate_preview' ) );
			}
		}

		if ( is_admin() ) {
			add_action( 'current_screen', array( $this, 'load_gateway_on_shop_order' ) );
		}

		/**
		 * SEPA
		 */
		add_action( 'wp_ajax_german_market_sepa_delete_user_information',       array( 'WGM_Gateway_Sepa_Direct_Debit', 'my_account_delete_user_payment_information' ) );
		add_action( 'wp_ajax_german_market_sepa_check_stored_user_information', array( 'WGM_Gateway_Sepa_Direct_Debit', 'check_for_stored_user_payment_information' ) );

		/**
		 * Add SEPA fields in user admin section
		 */
		if ( isset( $sdd_settings[ 'checkout_customer_can_save_payment_information' ] ) && ( 'on' == $sdd_settings[ 'checkout_customer_can_save_payment_information' ] ) ) {
			add_action( 'show_user_profile',            array( 'WGM_Gateway_Sepa_Direct_Debit', 'add_sepa_fields_to_user_section' ), 30 );
			add_action( 'personal_options_update',      array( 'WGM_Gateway_Sepa_Direct_Debit', 'update_profile_sepa_fields' ), 10, 1 );
			add_action( 'edit_user_profile',            array( 'WGM_Gateway_Sepa_Direct_Debit', 'add_sepa_fields_to_user_section' ), 30 );
			add_action( 'edit_user_profile_update',     array( 'WGM_Gateway_Sepa_Direct_Debit', 'update_profile_sepa_fields' ), 10, 1 );
		}

		/**
		 * SEPA Bulk action for deleting stored information
		 */
		add_action( 'german_market_sepa_bulk_delete_stored_payment_information', array( 'WGM_Gateway_Sepa_Direct_Debit', 'bulk_schedule_delete_stored_payment_information' ) );
		
		// backend download
		add_action( 'wp_ajax_german_market_download_sepa_mandate', array( $this, 'admin_ajax_download_pdf' ) );

		// REST API
		add_filter( 'woocommerce_api_order_response', array( $this, 'add_api_data_legacy' ), 90, 2 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'add_api_data' ), 90, 3 );

	}

	/**
	* Check if sepa checkox has to be validated
	*
	* @return Boolean
	*/
	public static function need_to_vaildate_sepa_checkbox() {

		$check = false;

		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings', array() );
				
		if ( isset( $sdd_settings[ 'enabled' ] ) ) {
			if ( $sdd_settings[ 'enabled' ] == 'yes' && $sdd_settings[ 'checkbox_confirmation' ] == 'activated' ) {

				$check = true;

				if ( WC()->payment_gateways ) {
					$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
					$check 						= isset( $available_payment_gateways[ 'german_market_sepa_direct_debit' ] );
				}
			}
		}

		return $check;
	}

	/**
	* Add SEPA Data to REST API
	*
	* @since 3.6.2
	* @wp-hook woocommerce_api_order_response
	* @param Array $order_data
	* @param WC_Order $order
	* @return Array
	**/
	function add_api_data( $response, $order, $request ) {

		if ( ! WGM_Helper::method_exists( $order, 'get_payment_method' ) ) {
			return $response;
		}

		if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {
			$gateway = new WGM_Gateway_Sepa_Direct_Debit();
			if ( $gateway->rest_api_activaction == 'on' ) {
				$response->data[ 'sepa' ] = $gateway->get_api_data( $order );
			}
		}

		return $response;
	}

	/**
	* Add SEPA Data to REST API
	*
	* @since 3.6.2
	* @wp-hook woocommerce_api_order_response
	* @param Array $order_data
	* @param WC_Order $order
	* @return Array
	**/
	function add_api_data_legacy( $order_data, $order ) {

		if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {
			$gateway = new WGM_Gateway_Sepa_Direct_Debit();
			if ( $gateway->rest_api_activaction == 'on' ) {
				$order_data[ 'sepa' ] = $gateway->get_api_data( $order );
			}

		}

		return $order_data;

	}

	/**
	* Load Gateway on shop order screen
	*
	* @wp-hook current_screen
	* @return void
	**/
	public function load_gateway_on_shop_order() {

		$screen = get_current_screen();

		if ( $screen->id == 'edit-shop_order' || $screen->id == 'shop_order' ) {
			if ( WGM_Gateway_Sepa_Direct_Debit::$instances == 0 ) {
				$new_gateway = new WGM_Gateway_Sepa_Direct_Debit();
			}
		}
	}

	/**
	* Ajax
	*
	* @since GM 3.3
	* @wp-hook wp_ajax_gm_sepa_direct_debit_mandate_preview
	* @return void
	**/
	public function ajax_mandate_preview() {

		if ( ! ( isset( $_REQUEST[ 'nonce' ] ) && wp_verify_nonce( $_REQUEST[ 'nonce' ], 'gm-sepa-direct-debit' ) ) ) {

			echo __( 'There was an error generating the mandate preview. Please reload the page and try again', 'woocommerce-german-market' );

		} else {

			unset( $_REQUEST[ 'nonce' ] );

			// close button
			?><div class="close"><?php echo apply_filters( 'german_market_sepa_close_mandate_preview', __( 'Close', 'woocommerce-german-market' ) ); ?></div><?php

			// mandate text
			echo '<div class="gm-sepa-mandate-preview-inner">' . self::generatre_mandate_preview( $_REQUEST ) . '</div>';

		}

		exit();
	}

	/**
	* Get Ajax preview
	*
	* @since GM 3.3
	* @param Array $args
	* @return String
	**/
	public static function generatre_mandate_preview( $args, $mandate_id = false, $date = false ) {

		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings' );

		$mandate_text = $sdd_settings[ 'direct_debit_mandate' ];

		$search = array(
			'[creditor_information]',
			'[creditor_identifier]',
			'[creditor_account_holder]',
			'[creditor_iban]',
			'[creditor_bic]',
			'[mandate_id]',
			'[street]',
			'[city]',
			'[postcode]',
			'[country]',
			'[date]',
			'[account_holder]',
			'[account_iban]',
			'[account_bic]',
			'[amount]'
		);

		if ( ! isset( $args[ 'amount' ] ) ) {
			$amount = WC()->cart->get_total();
		} else {
			$amount = $args[ 'amount' ];
		}

		if ( isset( $args[ 'saved_data' ] ) ) {
			if ( is_user_logged_in() ) {
				$args = apply_filters( 'wgm_sepa_direct_debit_logged_in_user', $args );
			}
		}

		$replace = array(
			$sdd_settings[ 'creditor_information' ],
			$sdd_settings[ 'creditor_identifier' ],
			$sdd_settings[ 'creditor_account_holder' ],
			$sdd_settings[ 'iban' ],
			$sdd_settings[ 'bic' ],
			$mandate_id ? $mandate_id : __( 'Will be communicated separately', 'woocommerce-german-market' ),
			$args[ 'street' ],
			$args[ 'city' ],
			$args[ 'zip' ],
			$args[ 'country' ],
			$date ? $date :  date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
			$args[ 'holder' ],
			$args[ 'iban' ],
			$args[ 'bic' ],
			$amount,
		);

		do_action( 'wgm_sepa_direct_debit_before_apply_filters_for_content' );

		if ( apply_filters( 'german_market_email_footer_the_content_filter', true, null ) ) {
            $mandate_preview = apply_filters( 'the_content', str_replace( $search, $replace, $mandate_text ) );
        } else {
            $mandate_preview = wpautop( WGM_Template::remove_vc_shortcodes( str_replace( $search, $replace, $mandate_text ) ) );
        }

		do_action( 'wgm_sepa_direct_debit_after_apply_filters_for_content' );

		return $mandate_preview;

	}

	/**
	* Add Gateway
	*
	* @since GM 3.3
	* @wp-hook woocommerce_payment_gateways
	* @param Array $gateway
	* @return Array
	**/
	public function german_market_add_sepa_direct_debit( $gateways ) {
		$gateways[] = 'WGM_Gateway_Sepa_Direct_Debit';
		return $gateways;
	}

	/**
	* ajax, manages what happen when the downloadbutton on admin order page is clicked
	*
	* @since WGM 3.0
	* @access public
	* @static
	* @hook wp_ajax_german_market_download_sepa_mandate
	* @arguments $_REQUEST[ 'order_id' ]
	* @return void, exit()
	*/
	public static function admin_ajax_download_pdf() {

		if ( ! check_ajax_referer( 'german-market-sepa-mandate', 'security', false ) ) {
			wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
		}

		 // load invoice pdf class if add-on is deactivated
		if ( ! class_exists( 'Woocommerce_Invoice_Pdf' ) ) {

            include_once( WGM_ADD_ONS_PATH . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf.php' );

            // maybe load default pdf settings
            if ( get_option( 'wp_wc_invoice_pdf_document_title', false ) === false ) {
                WP_WC_Invoice_Pdf_Backend_Activation::load_defaults();
            }

        }

		$order_id	= $_REQUEST[ 'order_id' ];
		$order 		= new WC_Order( $order_id );
		$order_nr 	= $order->get_order_number();
		//self::order_action( $order );

		$args = array(
            'street'    			=> $_REQUEST[ 'street' ],
            'city'      			=> $_REQUEST[ 'city' ],
            'zip'       			=> $_REQUEST[ 'zip' ],
            'country'   			=> $_REQUEST[ 'country' ],
            'holder'    			=> $_REQUEST[ 'holder' ],
            'iban'      			=> $_REQUEST[ 'iban' ],
            'bic'      				=> $_REQUEST[ 'bic' ],
            'mandate_id'			=> $_REQUEST[ 'mandate_id' ],
            'date'					=> $_REQUEST[ 'date' ],
            'email_admin_iban_mask'	=> $_REQUEST[ 'email_admin_iban_mask' ],
            'iban'					=> $_REQUEST[ 'iban' ],
            'amount'				=> wc_price( $order->get_total() ),
        );

		if ( $args[ 'email_admin_iban_mask' ] == 'off' ) {
			$args[ 'iban' ] = $_REQUEST[ 'unmasked_iban' ];
		}

		do_action( 'wp_wc_invoice_pdf_before_backend_download_switch', array( 'order' => $order, 'admin' => true ) );

		add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WGM_Sepa_Direct_Debit', 'pdf_content' ) );
        $args = array(
        	'admin'				=> true,
            'order'             => $order,
            'output_format'     => 'pdf',
            'output'            => '',
            'filename'          => __( 'SEPA Mandate', 'woocommerce-german-market' ) . ' ' . __( 'for order', 'woocommerce-german-market' ) . ' ' . $order_nr,
            'sepa_args'         => $args,
        );
        $sepa_pdf              = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
        remove_filter( 'wp_wc_invoice_pdf_template_invoice_content',  array( 'WGM_Sepa_Direct_Debit', 'pdf_content' ) );

		exit();
	}

	/**
    * template for sepa mandate in pdf
    *
    * @hook wp_wc_invoice_pdf_template_invoice_content
    * @param String $path
    * @return String
    */
    public static function pdf_content() {

        $theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'sepa-mandate.php';
        if ( file_exists( $theme_template_file ) ) {
            $template_path = $theme_template_file;
        } else {
            $template_path = untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'sepa-mandate.php';
        }

        return $template_path;

    }

}
