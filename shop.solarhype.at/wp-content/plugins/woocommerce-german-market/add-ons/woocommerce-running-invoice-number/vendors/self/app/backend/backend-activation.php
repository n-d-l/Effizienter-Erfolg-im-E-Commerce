<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Backend_Activation' ) ) {
	
	/**
	* When plugin is activated
	*
	* @class WP_WC_Running_Invoice_Number_Backend_Activation
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Backend_Activation {
		
		/**
		* when activated
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return void
		*/	
		public static function activation() {
			update_option( 'wp_wc_running_invoice_number_just_activated', true );
		}
		
		/**
		* get admin notices
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return array of strings
		*/	
		public static function get_notices() {
			$notices = array();	
			
			// check woocomerce
			if ( ! self::is_woocommerce_activated() ){
				$notices[] = __( 'The Plugin <strong>WooCommerce</strong> is not active, but it is necessary to use this plugin.', 'woocommerce-german-market' );
			} else { // woocommerce is activated
				// check wc version
				if ( ! version_compare( @$woocommerce->version , '2.2.8', '>=' ) ) {
					__( 'You need at least <strong>WooCommerce</strong> version 2.2.8 to use this plugin.', 'woocommerce-german-market' );
				}	
			}
			
			// check wordpress
			if ( ! version_compare( $GLOBALS[ 'wp_version' ], '4.0', '>=' ) ) {
				$notices[] = __( 'This plugin needs at least Wordpress 4.0.', 'woocommerce-german-market' );
			}
			
			// check php
			if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
				$notices[] = __( 'This plugin needs at least PHP version 5.2.0+.', 'woocommerce-german-market' );
			}
			
			return $notices;
		}
		
		/**
		* output admin notices
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook admin_notices
		* @return void
		*/	
		public static function output_notices() {
			update_option( 'wp_wc_running_invoice_number_just_activated', false );				// save that activation has been
			$notices = self::get_notices();		
			if ( count( $notices ) != 0 ) {											// if there are error notices, echo them
				foreach ( $notices as $notice ) {
					echo '<div class="error"><p>' . $notice . '</p></div>';	
				}
				// if there were error notices,
				// we decided, not to deactivate ourself
				//deactivate_plugins( plugin_basename( Woocommerce_Return_Delivery_Pdf::$plugin_filename ) );
			} else {
				// everything fine
				$notice = __( '<strong>WooCommerce Running Invoice Number</strong> has been activated successfull. You can go to the settings page here:', 'woocommerce-german-market' );
				$here = __( 'Settings page', 'woocommerce-german-market' );
				$url  = admin_url() . 'admin.php?page=wc-settings&tab=preferences-wp-wc-running-invoice-number';
				echo '<div class="updated"><p>' . $notice . ' <a href="' . $url . '">' . $here . '</a></p></div>';	
			}
		}
		
		/**
		* check if woocommerce is installed and activated
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return boolean
		*/	
		public static function is_woocommerce_activated(){
			$plugins = get_plugins();
			foreach ( $plugins as $path => $plugin ) {
				$plugin_name = strtolower( $plugin[ 'Name' ] );
				if ( $plugin_name === 'woocommerce' ) {
					if ( is_plugin_active( $path ) || is_plugin_active_for_network( $path ) ) {
						return true;
					}
				}
			}
			return false;
		}
	
		
	} // end class
} // end if
?>