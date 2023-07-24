<?php
/**
 * Feature Name: Tax
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com
 */

/**
 * Recalculate totals and overwrite cart totals data.
 *
 * @wp-hook woocommerce_calculate_totals
 * @return  void
 */
function wcvat_recalculate_cart() {

	$current_user = wp_get_current_user();
	
	// check admin area
	if ( is_admin() )
		return;

	// when doing cron jobs
	if ( is_null( WC()->customer ) ) {
		return;
	}

	if ( apply_filters( 'wcvat_recalculate_cart_return_before', false ) ) {
		return;
	}

	if ( WGM_Session::is_set( 'eu_vatin_check_billing_vat' ) ) {

		$billing_vat = WGM_Session::get( 'eu_vatin_check_billing_vat' );

	} else {

		if ( WGM_Session::is_set( 'eu_vatin_check_exempt' ) && ! WGM_Session::get( 'eu_vatin_check_exempt' ) ) {

			WC()->customer->set_is_vat_exempt( FALSE );
			return;

		} else {

			$checkout = WC()->checkout();
			$billing_vat = $checkout->get_value( 'billing_vat' );
		
		}

	}
	
	if ( empty( $billing_vat ) ) {
		WC()->customer->set_is_vat_exempt( FALSE );
		return;
	}

	// validate the billing_vat
	if ( ! class_exists( 'WC_VAT_Validator' ) )
		require_once 'class-wc-vat-validator.php';
	$validator = new WC_VAT_Validator( $billing_vat );
	if ( ! $validator->is_valid() ) {
		WC()->customer->set_is_vat_exempt( FALSE );
		return;
	}

	// remove taxes
	WC()->customer->set_is_vat_exempt( TRUE );
}
