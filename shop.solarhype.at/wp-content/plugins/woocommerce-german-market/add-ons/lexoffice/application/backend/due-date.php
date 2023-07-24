<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* init actions and hooks needed for the due date
*
* wp-hook init
* @return void
*/
function lexoffice_woocommerce_due_date_init(){
	
	// return if WooCommerce is not active
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	if ( ! ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'wc-settings' && isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'checkout' && isset( $_REQUEST[ 'section' ] ) ) ) {
		return;
	}

	// add filter for eacht payment gateway
	foreach ( WC()->payment_gateways()->get_payment_gateway_ids() as $gateway_id ) {
		add_filter( 'woocommerce_settings_api_form_fields_' . $gateway_id, 'lexoffice_woocommerce_due_date_settings_field' );
	}

}

/**
* add "Due Date for Lexoffice" to gateway settings
*
* wp-hook woocommerce_settings_api_form_fields_ . {gateway_id}
* @param Array $settings
* @return Array
*/
function lexoffice_woocommerce_due_date_settings_field( $settings ) {

	// get defaults
	$current_filter = current_filter();
	$current_payment_gateway = str_replace( 'woocommerce_settings_api_form_fields_', '', $current_filter );

	if ( $current_payment_gateway == 'bacs' ) {
		$default = 10;
	} else if ( $current_payment_gateway == 'cheque' ) {
		$default = 14;
	} else if ( $current_payment_gateway == 'paypal' ) {
		$default = 0;
	} else if ( $current_payment_gateway == 'cash_on_delivery' ) {
		$default = 7;
	} else if ( $current_payment_gateway == 'german_market_purchase_on_account' ) {
		$default = 30;
	} else {
		$default = 0;
	}	

	$default = apply_filters( 'lexoffice_woocomerce_due_date_default', $default, $current_payment_gateway );

	$settings[ 'lexoffice_due_date_title' ] = array(
		'title' 		=> __( 'Due Date', 'woocommerce-german-market' ),
		'type' 			=> 'title',
		'default'		=> '',
	);

	$settings[ 'lexoffice_due_date' ] = array(

			'title'				=> __( 'Due Date for Lexoffice', 'woocommerce-german-market' ),
			'type'				=> 'number',
			'custom_attributes' => array(
									'min'  => 0
									),
			'default' 			=> $default,
			'description'		=> __( 'Enter a number of days that, beginning from the date of your order, determine the due date. If you leave this field free or enter 0, the due date will be the date of your order.', 'woocommerce-german-market' ),
			'desc_tip'			=> false

	);

	return $settings;
}
