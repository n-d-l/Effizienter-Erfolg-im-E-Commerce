<?php

/**
 * Class WGM_Add_Ons
 *
 * This class loads the Add Ons
 *
 * @author  ChriCo
 */
class WGM_Add_Ons {

	/**
	* Include all activated modules
	*
	* @static
	* @return Array
	*/
	public static function init() {
		
		// get activated add ons
		$activated_add_ons = self::get_activated_add_ons();

		foreach ( $activated_add_ons as $base_name => $activated_add_on_file ) {

			$include = true;

			if ( $base_name == 'woocommerce-eu-vat-checkout' ) {
				
				$plugin = 'woocommerce-eu-vat-checkout/woocommerce-eu-vat-checkout.php';
				
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				if ( is_plugin_active_for_network( $plugin ) || is_plugin_active( $plugin ) ) {
					update_option( 'wgm_add_on_woocommerce_eu_vat_checkout_turn_off', '1' );
					$include = false;
				}

			}

			if ( $base_name == 'woocommerce-return-delivery-pdf' ) {

				$plugin = 'woocommerce-return-delivery-pdf/woocommerce-return-delivery-pdf.php';
				
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				if ( is_plugin_active_for_network( $plugin ) || is_plugin_active( $plugin ) ) {
					update_option( 'wgm_add_on_woocommerce_return_delivery_pdf_turn_off', '1' );
					$include = false;
				}
			}

			if ( $include ) {
				require_once( $activated_add_on_file );
			}
			
		}

	}

	/**
	* Get array of all modules
	*
	* @static
	* @return Array
	*/
	public static function get_all_add_ons( $get_only_activated_add_ons = false ) {
		
		$add_ons = array();

		// set the add on files
		$add_on_files = array();

		// get the module path
		$add_on_dir = @opendir( WGM_ADD_ONS_PATH );

		if ( $add_on_dir ) {
			while ( ( $file = readdir( $add_on_dir ) ) !== FALSE ) {

				// Skip the folders
				if ( substr( $file, 0, 1 ) == '.' )
					continue;
					
				// We only acceppt folder structures
				$add_on_files[ $file ] = WGM_ADD_ONS_PATH . '/' . $file . '/' . $file . '.php';

			}
			closedir( $add_on_dir );
		}

		// we don't have modules
		if ( empty( $add_on_files ) )
			return;

		// walk the modules
		foreach ( $add_on_files as $add_on_id => $add_on ) {

			// get option key and option value
			$option = 'wgm_add_on_'  . str_replace( '-', '_', $add_on_id );
			$option_value = get_option( $option );

			$if_condition = ( $get_only_activated_add_ons ) ? $option_value == 'on' : true;
			// if module is activated
			if ( $if_condition ) {

				// create file name
				$add_on_file = WGM_ADD_ONS_PATH . DIRECTORY_SEPARATOR . $add_on_id . DIRECTORY_SEPARATOR . $add_on_id .'.php';
				
				if ( file_exists( $add_on_file ) ) {
					
					// include add on file
					$add_ons[ $add_on_id ] = $add_on_file;
				}
				
			}

		}

		return $add_ons;
	}

	/**
	* Get array of all activated modules
	*
	* @static
	* @return Array
	*/
	public static function get_activated_add_ons() {
		return self::get_all_add_ons( true );
	}

	/**
	* Reloads if a add on option changed
	*
	* @static
	* @return void
	*/
	public static function reload_after_saving_options() {

		self::init();

		// may we have to turn off some add-ons?
		if ( get_option( 'wgm_add_on_woocommerce_eu_vat_checkout_turn_off' ) == '1' ) {
			update_option( 'wgm_add_on_woocommerce_eu_vat_checkout', 'off' );
		}

		if ( get_option( 'wgm_add_on_woocommerce_return_delivery_pdf_turn_off' ) == '1' ) {
			update_option( 'wgm_add_on_woocommerce_return_delivery_pdf', 'off' );
		}

		// get old status of activated add ons
		$old_activated_add_ons = get_option( 'wgm_activated_add_ons', array() );
		
		// get activated add ons after saving just now
		$activated_add_ons = self::get_activated_add_ons();

		if ( $old_activated_add_ons != $activated_add_ons ) {
			
			// call activation method / function of the (new) activated add-ons
			$array_diff = array_diff( $activated_add_ons, $old_activated_add_ons );
			foreach ( $array_diff as $add_on_key => $add_on_file ) {
				
				self::init();
				// exceptions
				if ( $add_on_key == 'woocommerce-eu-vat-checkout' ) {
					wcevc_activate();
				} else {

					// check for default method 'activate'
					$class_name = self::get_class_name( $add_on_key );
					if ( WGM_Helper::method_exists( $class_name, 'activate' ) ) {
						call_user_func( array( $class_name, 'activate' ) );
					}

				}
			}

			// sth changed
			update_option( 'wgm_activated_add_ons', $activated_add_ons );
			update_option( 'wgm_activated_add_ons_changed', 'yes' );

			wp_safe_redirect( get_admin_url() . 'admin.php?page=wc-settings&tab=preferences_de' );
			exit;
		}
		
	}

	/**
	* After reload we need to output the update message again
	*
	* @static
	* @return void
	*/
	public static function update_message() {
		
		if ( get_option( 'wgm_activated_add_ons_changed' ) == 'yes' ) {
			delete_option( 'wgm_activated_add_ons_changed' );
			$message = ( __( 'Your settings have been saved.', 'woocommerce-german-market' ) );
			echo '<div id="message" class="updated"><p><strong>' . esc_html( $message ) . '</strong></p></div>';

		}

		// eu vat => no activation because plugin is active
		if ( get_option( 'wgm_add_on_woocommerce_eu_vat_checkout_turn_off' ) == '1' ) {
			delete_option( 'wgm_add_on_woocommerce_eu_vat_checkout_turn_off' );
			$message = __ ( 'The Add-On "WooCommerce EU VAT Checkout" can\'t be activated because the stand alone plugin is still acitve. Please, deactivate your plugin. If it\'s the first time you want to activate the add-on, don\'t delete the plugin deactivation to import the settings fromt the plugin into the add-on.', 'woocommerce-german-market' );
			echo '<div id="message" class="error"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
		}

		// retoure pdf => no activation because plugin is active
		if ( get_option( 'wgm_add_on_woocommerce_return_delivery_pdf_turn_off' ) == '1' ) {
			delete_option( 'wgm_add_on_woocommerce_return_delivery_pdf_turn_off' );
			$message = __ ( 'The Add-On "Return Delivery Note PDF" can\'t be activated because the stand alone plugin is still acitve. Please, deactivate your plugin. If it\'s the first time you want to activate the add-on, don\'t delete the plugin after deactivation to import the settings fromt the plugin into the add-on.', 'woocommerce-german-market' );
			echo '<div id="message" class="error"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
		}

		// eu vat => does the add-on has settings?
		$activated_add_ons = self::get_activated_add_ons();
		if ( isset( $activated_add_ons[ 'woocommerce-eu-vat-checkout' ] ) ) {
			if ( get_option( 'wcevc_wgm_dismiss_tax_based_on_notice', '' ) == '' ) {

				// does the old eu vat plugin has settings?
				if ( get_option( 'wcevc_dismiss_tax_based_on_notice', '' ) != '' ) {
					
					// if so, import plugin settings into add-on settings
					update_option( 'wcevc_wgm_dismiss_tax_based_on_notice', get_option( 'wcevc_dismiss_tax_based_on_notice' ) );
					update_option( 'wcevc_wgm_enabled', get_option( 'wcevc_enabled' ) );
					$message = __( 'No settings were found for the Add-On "WooCommerce EU VAT Checkout" but for the plugin, so your plugin settings have been imported right now. This happens just once.', 'woocommerce-german-market' );
					echo '<div id="message" class="notice notice-info"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
				}
			}
		}

		// retoure pdf => does the add-on has settings?
		if ( isset( $activated_add_ons[ 'woocommerce-return-delivery-pdf' ] ) ) {
			if ( get_option( 'woocomerce_wcreapdf_wgm_pdf_font', '' ) == '' ) {

				// does the old retoure pdf plugin has settings?
				if ( get_option( 'woocomerce_wcreapdf_pdf_font', '' ) != '' ) {

					// if so, import plugin settings into add-on settings
					$all_wordpress_options = wp_load_alloptions();
					foreach ( $all_wordpress_options as $option_key => $option_value ) {
						
						if ( substr( $option_key, 0, 20 ) == 'woocomerce_wcreapdf_' ) {

							$new_option_key = str_replace( 'woocomerce_wcreapdf_', 'woocomerce_wcreapdf_wgm_', $option_key );
							update_option( $new_option_key, $option_value );
						}

					}

					update_option( 'woocomerce_wcreapdf_wgm_pdf', get_option( 'wcreapdf_pdf' ) );
					update_option( 'woocomerce_wcreapdf_wgm_test_pdf', get_option( 'wcreapdf_test_pdf' ) );
					$message = __( 'No settings were found for the Add-On "Return Delivery Note PDF" but for the plugin, so your plugin settings have been imported right now. This happens just once.', 'woocommerce-german-market' );
					echo '<div id="message" class="notice notice-info"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
				}

			}
		}

	}

	/**
	* Run uninstall.php of all add ons
	*
	* @static
	* @return void
	*/
	public static function uninstall() {

		$all_add_ons = self::get_all_add_ons();
		
		foreach ( $all_add_ons as $add_on_id => $add_on ) {
			
			// get uninstall.php
			$uninstall_file = WGM_ADD_ONS_PATH . DIRECTORY_SEPARATOR . $add_on_id . DIRECTORY_SEPARATOR . 'uninstall.php';
			
			// only if file exists
			if ( file_exists( $uninstall_file ) ) {
				include_once( $uninstall_file );
			}
		}

	}

	/**
	* Build class name of add-on from add-on key ( id )
	*
	* @static
	* @param String $key
	* @return String
	*/
	public static function get_class_name( $key ) {

		// init
		$name = $key;

		if ( trim( $key ) != '' ) {

			$name = '';
			$key_array = explode( '-', $key );
			$name_array = array();
			foreach ( $key_array as $key_element ) {
				$name_array[] = ucfirst( $key_element );
			}

			$name = implode( '_', $name_array );

		}

		return $name;

	}
}
