<?php
/**
 * Feature Name: Styles
 * Author:		 Inpsyde GmbH for MarketPress.com
 * Author URI:   http://marketpress.com
 * Licence:      GPLv3
 */

/**
 * Enqueue styles and scripts.
 *
 * @wp-hook admin_enqueue_styles
 * @return  void
 */
function wcvat_admin_enqueue_styles() {

	$styles = wcvat_get_admin_styles();

	foreach( $styles as $key => $style ){
		wp_enqueue_style(
			$key,
			$style[ 'src' ],
			$style[ 'deps' ],
			$style[ 'version' ],
			$style[ 'media' ]
		);
	}

	$suffix = wcvat_get_script_suffix();
	
	// scripts
	wp_enqueue_script( 	
		'woocommerce_eu_vatin_check_admin_script', 
		wcvat_get_asset_directory_url( 'js' ) .  'admin' . $suffix . '.js',
		array( 'jquery' ), 
		Woocommerce_German_Market::$version 
	);

	wp_localize_script( 'woocommerce_eu_vatin_check_admin_script', 'update_billing_vat', array(
		'ajax_url' 		=> admin_url( 'admin-ajax.php' ),
		'nonce'			=> wp_create_nonce( 'wcvat_update_billing_vat' ),
		'option_value'	=> get_option( 'vat_options_billing_vat_editable', 'off' ),
		'messages'		=> array(
			'no_customer_selected' 	=> __( 'No customer selected', 'woocommerce-german-market' ),
			'no_vat_saved'			=> sprintf( __( '"%s" not yet saved in user profile', 'woocommerce-german-market' ), get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) )
		) 
	) );
}

/**
 * Returning our Styles
 *
 * @return  Array
 */
function wcvat_get_admin_styles(){

	$suffix = wcvat_get_script_suffix();

	// $handle => array( 'src' => $src, 'deps' => $deps, 'version' => $version, 'media' => $media )
	$styles = array();

	// adding the main-CSS
	$styles[ 'woocommerce-eu-vatin-check-admin' ] = array(
		'src'       => wcvat_get_asset_directory_url( 'css' ) .  'admin' . $suffix . '.css',
	    'deps'      => NULL,
	    'version'   => Woocommerce_German_Market::$version,
	    'media'     => NULL
	);

	return apply_filters( 'wcvat_get_styles', $styles );
}
