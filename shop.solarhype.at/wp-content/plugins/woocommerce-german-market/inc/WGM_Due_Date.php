<?php

/**
 * Class WGM_Due_date
 *
 */
class WGM_Due_date {

	/**
	 * @var WGM_Due_date
	 * @since v3.2
	 */
	private static $instance = null;
	
	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Due_date
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Due_date();	
		}
		return self::$instance;
	}

	public static $complete_due_date_string = '';

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		if ( get_option( 'woocommerce_de_due_date', 'off' ) == 'on' ) {

			add_action( 'init', array( $this, 'init_form_fields' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_due_date_in_order' ), 20, 2 );
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_items_totals' ), 10, 2 );
			add_action( 'woocommerce_process_shop_order_meta',array( $this, 'save_meta_data' ), 10, 2 );
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'backend_field_in_edit_order' ), 20 );
			add_filter( 'woocommerce_de_due_date_text_cash_on_delivery', array( $this, 'wgm_cash_on_delivery_text' ) );
			add_filter( 'woocommerce_de_due_date_text_german_market_purchase_on_account', array( $this, 'wgm_german_market_purchase_on_account' ) );

		}
		
	}

	/**
	* Add Form Fields
	*
	* @wp-hook admin_init
	* @return void
	**/
	public function init_form_fields() {

		// return if WooCommerce is not active
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		if ( ! ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'wc-settings' && isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'checkout' && isset( $_REQUEST[ 'section' ] ) ) ) {
			return;
		}

		// add filter for eacht payment gateway
		foreach ( WC()->payment_gateways()->get_payment_gateway_ids() as $gateway_id ) {
			add_filter( 'woocommerce_settings_api_form_fields_' . $gateway_id, array( $this, 'settings_field' ), 11 );
		}

	}

	/**
	* Save due date as meta
	*
	* @wp-hook woocommerce_checkout_update_order_meta
	* @param Integer $order_id
	* @param WC_Checkout $checkout
	* @return void
	**/
	public function save_due_date_in_order( $order_id, $checkout = false, $payment_method_id = false ) {

		$order = wc_get_order( $order_id );

		$due_date_days_after_order_date = 0; // init
		if ( ! $payment_method_id ) {

			if ( ! WGM_Helper::method_exists( $order, 'get_payment_method' ) ) {
				return;
			}
			
			$payment_method_id = $order->get_payment_method();
		}
		
		if ( is_admin() ) {
			if ( $payment_method_id == '' ) {
				if ( isset( $_REQUEST[ '_payment_method' ] ) ) {
					$payment_method_id = $_REQUEST[ '_payment_method' ];
				}
			}
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		if ( isset( $gateways[ $payment_method_id ] ) ) {
			$gateway = $gateways[ $payment_method_id ];

			if ( isset( $gateway->settings[ 'lexoffice_due_date' ] ) ) {
				$due_date_days_after_order_date = intval( $gateway->settings[ 'lexoffice_due_date' ] );
			}
		}

		$due_date = $order->get_date_created();
		
		if ( ! $due_date ) {
			$due_date = new DateTime();
		}

		$due_date->add( new DateInterval( 'P' . $due_date_days_after_order_date .'D' ) ); // add days

		$due_date = apply_filters( 'german_market_save_due_date_in_order_due_date', $due_date, $order_id, $checkout, $payment_method_id );
		
		$due_date_string = $due_date->format( 'Y-m-d' );

		update_post_meta( $order_id, '_wgm_due_date', $due_date_string );

	}	

	/**
	* Add "Due Date" to gateway settings
	*
	* wp-hook woocommerce_settings_api_form_fields_ . {gateway_id}
	* @param Array $settings
	* @return Array
	*/
	public function settings_field( $settings ) {

		// this is already done in the lexoffice-add-on, don't need to do it twice
		if ( ! isset( $settings[ 'lexoffice_due_date' ] ) ) {

			// due date file
			$application_path = 'application' . DIRECTORY_SEPARATOR . 'api-voucher' . DIRECTORY_SEPARATOR;
			$file = dirname( Woocommerce_German_Market::$plugin_filename ) . DIRECTORY_SEPARATOR . 'add-ons' . DIRECTORY_SEPARATOR . 'lexoffice' .DIRECTORY_SEPARATOR . $application_path . 'backend' . DIRECTORY_SEPARATOR . 'due-date.php';
			if ( ! file_exists( $file ) ) {
				$application_path = 'application' . DIRECTORY_SEPARATOR;
				$file = dirname( Woocommerce_German_Market::$plugin_filename ) . DIRECTORY_SEPARATOR . 'add-ons' . DIRECTORY_SEPARATOR . 'lexoffice' .DIRECTORY_SEPARATOR . $application_path . 'backend' . DIRECTORY_SEPARATOR . 'due-date.php';
			}

			require_once( $file );
			$settings = lexoffice_woocommerce_due_date_settings_field( $settings );
		}

		$settings[ 'lexoffice_due_date_title' ][ 'description' ] = __( 'The Due Date is used to be shown to the customer in emails and in the order review when the order process is finished.', 'woocommerce-german-market' );

		if ( function_exists( 'lexoffice_woocommerce_init' ) ) {
			$settings[ 'lexoffice_due_date_title' ][ 'description' ] .= ' ' . __( 'It is also used for lexoffice vouchers.', 'woocommerce-german-market' );
		}

		
		$settings[ 'lexoffice_due_date' ][ 'title' ] = __( 'Due Date', 'woocommerce-german-market' );

		// Text
		$current_filter = current_filter();
		$current_payment_gateway = str_replace( 'woocommerce_settings_api_form_fields_', '', $current_filter );

		$default_text = apply_filters( 'woocommerce_de_due_date_text_' . $current_payment_gateway, __( 'Due Date: {{due-date}}', 'woocommerce-german-market' ) );

		$settings[ 'wgm_due_date_text' ] = array(

			'title'				=> __( 'Due Date Text', 'woocommerce-german-market' ),
			'type'				=> 'text',
			'default' 			=> $default_text,
			'desc_tip'			=> __( 'This text is shown in the customer emails, invoice pdfs and in my account.', 'woocommerce-german-market' ),
			'description'		=> __( 'You can use the following placeholders: Due Date - <code>{{due-date}}</code>, Days - <code>{{days}}</code>.', 'woocommerce-german-market' ),

		);

		return $settings;
	}

	/**
	* Add Order Date to Emails
	*
	* wp-hook woocommerce_get_order_item_totals
	* @param Array $total_rows
	* @param WC_Order $order
	* @return Array
	*/
	public function order_items_totals( $total_rows, $order ) {

		$due_date = get_post_meta( $order->get_id(), '_wgm_due_date', true );

		if ( $due_date != '' ) {
			
			$due_date_string = apply_filters( 'woocommerce_de_due_date_string', date_i18n( wc_date_format(), strtotime( $due_date ) ), $due_date, $order );

			$due_date_text 		= apply_filters( 'woocommerce_de_due_date_text_default', __( 'Due Date: {{due-date}}', 'woocommerce-german-market' ) );
			$due_date_days 		= '';
			$payment_method_id	= $order->get_payment_method();

			if ( $payment_method_id != '' ) {
				$gateways = WC()->payment_gateways()->payment_gateways();
				if ( isset( $gateways[ $payment_method_id ] ) ) {
					$gateway = $gateways[ $payment_method_id ];

					if ( isset( $gateway->settings[ 'wgm_due_date_text' ] ) ) {
						$due_date_text 	= $gateway->settings[ 'wgm_due_date_text' ];
					} else {
						$due_date_text 	= apply_filters( 'woocommerce_de_due_date_text_' . $payment_method_id, __( 'Due Date: {{due-date}}', 'woocommerce-german-market' ) );
					}

					// WPML and Polylang Support
					if ( function_exists( 'icl_register_string' ) && function_exists( 'icl_t' ) && function_exists( 'icl_st_is_registered_string' ) ) {
						
						$due_date_text = icl_t( 'German Market: Due Date Option', $due_date_text, $due_date_text );
					
					} else if ( function_exists( 'pll__' ) ) {

						$due_date_text = pll__( $due_date_text );

					}

					if ( isset( $gateway->settings[ 'lexoffice_due_date' ] ) ) {
						$due_date_days 	= intval( $gateway->settings[ 'lexoffice_due_date' ] );
					} else {

							$current_payment_gateway = $payment_method_id;

							if ( $current_payment_gateway == 'bacs' ) {
								$due_date_days = 10;
							} else if ( $current_payment_gateway == 'cheque' ) {
								$due_date_days = 14;
							} else if ( $current_payment_gateway == 'paypal' ) {
								$due_date_days = 0;
							} else if ( $current_payment_gateway == 'cash_on_delivery' ) {
								$due_date_days = 7;
							} else if ( $current_payment_gateway == 'german_market_purchase_on_account' ) {
								$due_date_days = 30;
							} else {
								$due_date_days = 0;
							}
					}
				}
			}

			$due_date_text = str_replace( array( '{{due-date}}', '{{days}}' ), array( $due_date_string, $due_date_days ), $due_date_text );

			if ( isset( $total_rows[ 'payment_method' ][ 'label' ] ) ) {
	
				$total_rows[ 'payment_method' ][ 'label' ] .= strip_tags( apply_filters( 'woocommerce_de_due_date_markup', '<br /><small>' . $due_date_text . '</small>', $due_date_text, $due_date_days, $due_date_string, $order ), '<br><small>' );
				self::$complete_due_date_string = $total_rows[ 'payment_method' ][ 'label' ];
				add_filter( 'esc_html', array( __CLASS__, 'esc_html_exception' ), 10, 2 );
				
			}

		}
		
		return $total_rows;
	}

	/**
	* Add Exception for calling esc_attr and output due date markup
	*	
	* @hook esc_html
	* @param String $save_text
	* @param String $text
	* @return String
	*/
	public static function esc_html_exception( $save_text, $text ) {
		
		if ( $text == self::$complete_due_date_string ) {
			$save_text = $text;
		}

		return $save_text;
	}

	/**
	* save meta data
	*	
	* @hook woocommerce_process_shop_order_meta
	* @param Integer $post_id
	* @param WP_Post $post
	* @return void
	*/
	public function save_meta_data( $post_id, $post ) {

		if ( isset( $_REQUEST[ '_wgm_due_date' ] ) ) {
			
			if ( $_REQUEST[ '_wgm_due_date' ] != '' ) {
				update_post_meta( $post_id, '_wgm_due_date', $_REQUEST[ '_wgm_due_date' ] );
			} else {
				$this->save_due_date_in_order( $post_id, null );
			}

		} else {
			$this->save_due_date_in_order( $post_id, null );
		}

	}

	/**
	* Backend edit due date
	*	
	* @hook woocommerce_admin_order_data_after_order_details
	* @param WC_Order $order
	* @return void
	*/
	public function backend_field_in_edit_order( $order ) {

		$due_date = get_post_meta( $order->get_id(), '_wgm_due_date', true );

		?>
		<p class="form-field form-field-wide">
			<label for="_wgm_due_date"><?php echo __( 'Due Date', 'woocommerce-german-market' ) ?>:</label>
			<input type="text" class="date-picker-field" name="_wgm_due_date" id="_wgm_due_date" value="<?php echo $due_date; ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
		</p>
		<?php

	}

	/**
	* Cash On Delivery Default Text
	*	
	* @hook woocommerce_de_due_date_text_cash_on_delivery
	* @param String $text
	* @return String
	*/
	function wgm_cash_on_delivery_text( $text ) {
		return __( 'Due on delivery', 'woocommerce-german-market' );
	}

	/**
	* Purchase On Account Default Text
	*	
	* @hook woocommerce_de_due_date_text_german_market_purchase_on_account
	* @param String $text
	* @return String
	*/
	function wgm_german_market_purchase_on_account( $text ) {
		return __( 'Due {{days}} days after receipt of the goods', 'woocommerce-german-market' );
	}

}
