<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Plugin_Compatibility_PayPal_Payments
 * Compatibility functions for WooCommerce Paypal Payments plugin.
 *
 * @author MarketPress
 */
class WGM_Plugin_Compatibility_PayPal_Payments {

	static $instance = NULL;

	/**
	 * singleton getInstance
	 *
	 * @access public
	 * @static
	 *
	 * @return WGM_Plugin_Compatibility_PayPal_Payments
	 */
	public static function get_instance() {

		if ( self::$instance == NULL) {
			self::$instance = new WGM_Plugin_Compatibility_PayPal_Payments();
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

		add_action( 'init', array( $this, 'deactivate_confirm_order_page' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'wp_ajax_german_market_dismiss_wc_paypal_notice', 	array( $this, 'dismiss_notice' ) );
		add_action( 'admin_enqueue_scripts', function() {

			wp_localize_script( 'woocommerce_de_admin', 'ppp_dismiss_notices', array(
					'ajax_url' 	=> admin_url( 'admin-ajax.php' ),
		        	'nonce'		=> wp_create_nonce( 'german_market_wc_ppp' ),
			) );

		}, 20 );
		

		add_action( 'woocommerce_paypal_payments_checkout_button_renderer_hook', function( $hook_name ) {

			// second checkout page is deactivated
			if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'off' ) {

				// deactivate german market hooks is off				
				if ( get_option( 'gm_deactivate_checkout_hooks', 'off' ) == 'off' ) {

					$hook_name = 'woocommerce_checkout_order_review';

					// checkboxes before order review is on
					if ( get_option( 'gm_order_review_checkboxes_before_order_review', 'off' ) == 'off' ) {
						remove_action( 'woocommerce_checkout_order_review', array( 'WGM_Template', 'add_review_order' ), 15 );
						add_action( 'woocommerce_checkout_order_review', array( 'WGM_Template', 'add_review_order' ), 10 );
					}
				}
			}

			return $hook_name;
		});
	}

	/**
	 * Deactivate 2nd CO page if plugin is active
	 *
	 * @since 3.21
	 * @wp-hook init
	 * @return void
	 */
	public function deactivate_confirm_order_page() {

		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) === 'on' ) {
			update_option( 'woocommerce_de_secondcheckout', 'off' );
			update_option( 'german_market_wc_paypal_payments_turned_off', 'recently' );
		}
	}

	/**
	 * Show admin notice that 2nd CO page has been turned off
	 *
	 * 
	 * @since 3.21
	 * @wp-hook admin_notices
	 * @return void
	 */
	public function admin_notice() {

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen->id === 'woocommerce_page_german-market' ) {
				if ( get_option( 'german_market_wc_paypal_payments_turned_off', 'no' ) === 'recently' ) {
					$class = 'notice notice-warning is-dismissible german-market-wc-ppp-notice';
					$message = __( 'You are using the plugin "WooCommerce PayPal Payments". This is not compatible with the "Confirm & Place Order Page" page of German Market, which you have activated. To enable the smooth ordering process, the use of the page has been disabled.', 'woocommerce-german-market' ) . '<br><br>' . __( 'German Market works with "PayPal Payments". The payment method remains activated. Only the function of the "Confirm & Place Order Page" of German Market has beeen disabled.', 'woocommerce-german-market' );
					printf( '<div class="%1$s" data-option="german_market_wc_paypal_payments_turned_off"><p>%2$s</p></div>', $class, $message );
				}
			}
		}
	}

	/**
	 * Make admin notice dismissible 
	 *
	 * 
	 * @since 3.21
	 * @wp-hook wp_ajax_german_market_dismiss_wc_paypal_notice
	 * @return void
	 */
	public function dismiss_notice() {

		if ( isset( $_REQUEST[ 'nonce' ] ) && wp_verify_nonce( $_REQUEST[ 'nonce' ], 'german_market_wc_ppp' ) ) {
			echo 'success';
			update_option( 'german_market_wc_paypal_payments_turned_off', 'yes' );
		} else {
			echo 'error';
		}
		
		exit();
	}
}
