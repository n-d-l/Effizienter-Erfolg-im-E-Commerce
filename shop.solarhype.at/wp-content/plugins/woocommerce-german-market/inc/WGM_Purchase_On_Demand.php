<?php

/**
 * Class WGM_Purchase_On_Demand
 *
 * German Market Gateway Puchase on Demand
 *
 * @author MarketPress
 */
class WGM_Purchase_On_Demand {

	/**
	 * @var WGM_Purchase_On_Demand
	 * @since v3.2
	 */
	private static $instance = null;
	
	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Purchase_On_Demand
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Purchase_On_Demand();	
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
		
		require_once dirname( Woocommerce_German_Market::$plugin_filename ) . '/gateways/WGM_Gateway_Purchase_On_Account.php';
		
		$poa_settings = get_option( 'woocommerce_german_market_purchase_on_account_settings', array() );
		if ( isset( $poa_settings[ 'woocommerce_german_market_purchase_on_account_fee' ] ) ) {
			$costs = $poa_settings[ 'woocommerce_german_market_purchase_on_account_fee' ];
			if ( floatval( str_replace( ',', '.', $costs ) ) > 0.0 ) {
				WGM_Gateways::set_gateway_fee( 'german_market_purchase_on_account' , $poa_settings[ 'woocommerce_german_market_purchase_on_account_fee' ] );
			}
		}

		add_filter( 'woocommerce_payment_gateways', array( $this, 'german_market_add_purchase_on_account' ) );

		// no pay date
		add_filter( 'woocommerce_order_get_date_paid', array( $this, 'dont_show_paid_for_admin' ), 10, 2 );

		// if "Ship to different address" is disabled, show a message to cusomer when changing payment method
		add_action( 'wp_enqueue_scripts', 	array( $this, 'localize_frontend_scripts' ),20 );
		
		do_action( 'wgm_after_purchase_on_demand_init', $this );

	}

	/**
	* if "Ship to different address" is disabled, show a message to cusomer when changing payment method
	*
	* @since GM 3.11
	* @wp-hook wp_enqueue_scripts
	* @return void
	**/
	public function localize_frontend_scripts() {

		$text = __( '"Ship to different address" is not available for the selected payment method "Purchase on Account" and has been disabled!', 'woocommerce-german-market' );
		$text = apply_filters( 'german_market_purchase_on_account_ship_to_different_address_message', $text );

		$message_with_markup = sprintf( '<p class="woocommerce-notice woocommerce-notice--info woocommerce-info" id="german-market-puchase-on-account-message">%s</p>', $text );
		$message_with_markup = apply_filters( 'german_market_purchase_on_account_ship_to_different_address_message_with_markup', $message_with_markup, $text );

		wp_localize_script( 'woocommerce_de_frontend', 'ship_different_address', 
			array( 
				'message' => $message_with_markup, 
				'before_element' => apply_filters( 'german_market_purchase_on_account_ship_to_different_address_before_element', '.woocommerce-checkout-payment' ) 
			) 
		);
	}

	/**
	* No Pay Date for this gateway
	*
	* @since GM 3.8.1
	* @wp-hook woocommerce_order_get_date_paid
	* @param WC_DateTime $value
	* @param WC_Order $order
	* @return Boolean
	**/
	public function dont_show_paid_for_admin( $value, $order ) {
		
		if ( $order->get_payment_method() == 'german_market_purchase_on_account' ) {
			$value = null;
		}
		
		return $value;
	}

	/**
	* Add Gateway
	*
	* @since GM 3.2
	* @wp-hook woocommerce_payment_gateways
	* @param Array $gateway
	* @return Array
	**/
	public function german_market_add_purchase_on_account( $gateways ) {
		$gateways[] = 'WGM_Gateway_Purchase_On_Account';
		return $gateways;
	}


}
