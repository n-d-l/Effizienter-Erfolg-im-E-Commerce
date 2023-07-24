<?php
/**
 * Feature Name: Helpers
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com
 */

/**
 * Check if the requirements are setted. If not we need an admin
 * notice to inform the administration about the tasks to be
 * done to get this plugin work
 * 
 * @return	boolean
 */
function wcvat_system_check() {

	if ( get_option( 'wcvat_system_check_return_true_before_check', 'off' ) == 'on' ) {
		return TRUE;
	}

	// first, check if WooCommerce exists and is active
	if ( ! class_exists( 'WooCommerce' ) ) {
		if ( current_user_can( 'manage_options' ) )
			add_action( 'admin_notices', 'wcvat_show_admin_notice_missing_woocommerce' );
		return FALSE;
	}

	// check if the tax is active
	$is_tax_active = get_option( 'woocommerce_calc_taxes', 'no' );
	if ( $is_tax_active == 'no' ) {
		if ( current_user_can( 'manage_options' ) )
			add_action( 'admin_notices', 'wcvat_show_admin_notice_missing_tax' );
		return FALSE;
	}

	// check if the kleinunternehmerregelung is active
	$is_kleinunternehmerregelung_active = get_option( 'woocommerce_de_kleinunternehmerregelung', '' );
	if ( $is_kleinunternehmerregelung_active == 'on' ) {
		if ( current_user_can( 'manage_options' ) )
			add_action( 'admin_notices', 'wcvat_show_admin_notice_kleinunternehmerregelung' );
		return FALSE;
	}

	return TRUE;
}

/**
 * Shows the admin notice
 * 
 * @param	string $message
 * @return	void
 */
function wcvat_show_admin_notice( $message ) {

	switch ( $message ) {
		case 'missing-woocommerce':
			echo '<div class="error"><p>' . __( 'WooCommerce is missing! To get <strong>WooCommerce EU VAT Number Check</strong> work, you need to install <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> first.', 'woocommerce-german-market' ) . '</p></div>';
			break;
		case 'missing-tax':
			echo '<div class="error"><p>' . sprintf( __( 'Tax calculation is not active! To get <strong>WooCommerce EU VAT Number Check</strong> work, you need to enable the <a href="%s">taxes and tax calculations</a> first.', 'woocommerce-german-market' ), admin_url( 'admin.php?page=wc-settings&tab=tax' ) ) . '</p></div>';
			break;
		case 'kleinunternehmerregelung':
			echo '<div class="error"><p>' . sprintf( __( 'Small-business regulation is active! To get <strong>WooCommerce EU VAT Number Check</strong> work, you need to disnable the <a href="%s">Small-business regulation</a> first.', 'woocommerce-german-market' ), admin_url( 'admin.php?page=wc-settings&tab=preferences_de' ) ) . '</p></div>';
			break;
		default:
			break;
	}
}

/**
 * Calles the admin notice for this message
 * 
 * @wp-hook	admin_notices
 * @return	void
 */
function wcvat_show_admin_notice_missing_woocommerce() {
	wcvat_show_admin_notice( 'missing-woocommerce' );
}

/**
 * Calles the admin notice for this message
 * 
 * @wp-hook	admin_notices
 * @return	void
 */
function wcvat_show_admin_notice_missing_tax() {
	wcvat_show_admin_notice( 'missing-tax' );
}

/**
 * Calles the admin notice for this message
 * 
 * @wp-hook	admin_notices
 * @return	void
 */
function wcvat_show_admin_notice_kleinunternehmerregelung() {
	wcvat_show_admin_notice( 'kleinunternehmerregelung' );
}

/**
 * getting the Script and Style suffix for the plugin
 * Adds a conditional ".min" suffix to the file name
 * when WP_DEBUG is NOT set to TRUE.
 *
 * @return string
 */
function wcvat_get_script_suffix() {

	$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$suffix = $script_debug ? '' : '.min';

	return $suffix;
}

/**
 * Gets the specific asset directory url
 *
 * @param	string $path the relative path to the wanted subdirectory. If
 *				no path is selected, the root asset directory will be returned
 * @return	string the url of the bbp_hd asset directory
 */
function wcvat_get_asset_directory_url( $path = '' ) {

	// set base url
	$wcvat_assets_url = WCVAT_PLUGIN_URL . 'assets/';
	if ( $path != '' )
		$wcvat_assets_url .= $path . '/';
	return $wcvat_assets_url;
}

/**
 * Gets the specific asset directory path
 *
 * @param	string $path the relative path to the wanted subdirectory. If
 *				no path is selected, the root asset directory will be returned
 * @return	string the url of the bbp_hd asset directory
 */
function wcvat_get_asset_directory( $path = '' ) {

	// set base url
	$wcvat_assets = WCVAT_PLUGIN_PATH . 'assets/';
	if ( $path != '' )
		$wcvat_assets .= $path . '/';
	return $wcvat_assets;
}

/**
 * inserts an array before a given key
 *
 * @param array  $array
 * @param string $search
 * @param array  $insertment
 *
 * @return array
 */
function wcvat_array_insert( $array, $search, $insertment ) {

	$index = array_search( $search, array_keys( $array ) );
	$first = array_slice( $array, 0 , $index );
	$second = array_slice( $array, $index );

	return array_merge( $first, $insertment, $second );
}

/**
 * set customers to not vat exempted in first login and remove vat id if billing country is base country
 *
 * @wp-hook init
 * @since GM 3.4.1
 * @return void
 */
function wcvat_vat_exempt_first_login() {

	if ( ! is_admin() ) {

		if ( is_user_logged_in() ) {

			$user_id = get_current_user_id();

			$already_did = get_user_meta( $user_id, 'gm_set_vat_exempt', true );

			if ( $already_did != 'yes' ) {
				
				$billing_vat = get_user_meta( $user_id, 'billing_vat', true );

				if ( $billing_vat != '' ) {

					$country_code = substr( $billing_vat, 0, 2 );
					$base_location = wc_get_base_location();

					if ( strtolower( $country_code ) == strtolower( $base_location[ 'country' ] ) ) {

						WGM_Session::add( 'eu_vatin_check_exempt', false );
						WC()->customer->set_is_vat_exempt( FALSE );
						update_user_meta( $user_id, 'gm_set_vat_exempt', 'yes' );
					}

				}
			}

		}

	}

}
