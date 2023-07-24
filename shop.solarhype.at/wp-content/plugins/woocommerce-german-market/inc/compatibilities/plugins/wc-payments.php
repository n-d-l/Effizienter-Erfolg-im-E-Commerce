<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Plugin_Compatibility_WC_Payments
 * Compatibility functions for WooCommerce Payments plugin.
 *
 * @author MarketPress
 */
class WGM_Plugin_Compatibility_WC_Payments {

	static $instance = NULL;

	/**
	 * singleton getInstance
	 *
	 * @access public
	 * @static
	 *
	 * @return WGM_Plugin_Compatibility_WC_Payments
	 */
	public static function get_instance() {

		if ( self::$instance == NULL) {
			self::$instance = new WGM_Plugin_Compatibility_WC_Payments();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function __construct() {

		add_filter( 'german_market_checkout_after_validation_without_sec_checkout_return', array( $this, 'dont_check_checkboxes' ), 1, 4 );
	}

	/**
	 * Dont check chechboxes on product pages / cart / using "Express checkouts"
	 *
	 * @access public
	 *
	 * @param Boolean $boolean
	 * @param Array $data
	 * @param Array $errors
	 * @param Array $request_array
	 * 
	 * @return Boolean
	 */
	public function dont_check_checkboxes( $boolean, $data, $errors, $request_array ) {

		if ( isset( $request_array[ 'wc-ajax' ] ) && $request_array[ 'wc-ajax' ] == 'wcpay_create_order' ) {
			$boolean = true;
		}

		return $boolean;
	}
}