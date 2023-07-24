<?php

if ( ! class_exists( 'MarketPress_Improve_Plugin' ) ) {
	require_once( 'abstracts' . DIRECTORY_SEPARATOR . 'abstract-class-marketpress-improve-plugin.php' );
}

/**
 * MarketPress_Improve_GermanMarket
 * @version 1.0.1
 */
class MarketPress_Improve_GermanMarket extends MarketPress_Improve_Plugin {

	/**
	 * Get Plugin Name
	 *
	 * @since 	1.0
	 * @return 	String
	 */
	final protected function get_plugin_name() {
		return 'German Market';
	}

	/**
	 * Get Plugin Slug
	 *
	 * @since 	1.0
	 * @return 	String
	 */
	final protected function get_plugin_slug() {
		return 'german-market';
	}

	/**
	 * Get Plugin Version
	 *
	 * @since 	1.0
	 * @return 	String
	 */
	final protected function get_plugin_version() {
		return Woocommerce_German_Market::$version;
	}

	/**
	 * Get Plugin Data
	 *
	 * @since 	1.0
	 * @return 	Array
	 */
	final protected function get_plugin_data() {

		$data = array();

		// Add-Ons
		$add_on_files = WGM_Add_Ons::get_all_add_ons();
		foreach ( $add_on_files as $add_on_id => $add_on ) {
			$data[ 'Add-On ' . $add_on_id ] = get_option( 'wgm_add_on_'  . str_replace( '-', '_', $add_on_id ), 'off' );

		}

		// Options
		$data[ 'Option secondcheckout' ] 						= get_option( 'woocommerce_de_secondcheckout', 'off' );
		$data[ 'Option manual_order_confirmation' ] 			= get_option( 'woocommerce_de_manual_order_confirmation', 'off' );
		$data[ 'Option split_tax' ] 							= get_option( 'wgm_use_split_tax', 'on' );
		$data[ 'Option double_opt_in_customer_registration' ] 	= get_option( 'wgm_double_opt_in_customer_registration', 'off' );
		$data[ 'Option age rating' ] 							= get_option( 'german_market_age_rating', 'off' );
		$data[ 'Checkout Checkbox Logging' ]					= get_option( 'gm_order_review_checkboxes_logging', 'off' );
		$data[ 'Download Link for Invoice PDFs' ]				= 'link' === get_option( 'wp_wc_invoice_pdf_emails_attachment_format', 'attachment' ) ? 'on' : 'off';
		$data[ 'Save VAT ID in user profile' ]					= get_option( 'vat_options_billing_vat_editable', 'off' );
		$data[ 'Option confirmation_mail_on_thank_you_page' ]	= get_option( 'gm_order_confirmation_mail_send_on_thank_you_page', 'off' );
		$data[ 'GiroCode - Purchase On Account' ]				= get_option( 'wp_wc_invoice_pdf_girocode_gateway_german_market_purchase_on_account', 'off' );
		$data[ 'GiroCode - BACS' ]								= get_option( 'wp_wc_invoice_pdf_girocode_gateway_bacs', 'off' );
		$data[ 'SWISS QR - Purchase On Account' ]				= get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_gateway_german_market_purchase_on_account', 'off' );
		$data[ 'SWISS QR - BACS' ]								= get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_gateway_bacs', 'off' );

		// Payment Gateways
		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings' );
		$sepa = 'off';
		if ( isset( $sdd_settings[ 'enabled' ] ) && $sdd_settings[ 'enabled' ] == 'yes' ) {
			$sepa = 'on';
		}

		$poa_settings = get_option( 'woocommerce_german_market_purchase_on_account_settings' );
		$poa = 'off';
		if ( isset( $poa_settings[ 'enabled' ] ) && $poa_settings[ 'enabled' ] == 'yes' ) {
			$poa = 'on';
		}

		$data[ 'Gateway SEPA' ]									= $sepa;
		$data[ 'Gateway Purchase On Demand' ]					= $poa;

		return $data;
	}

}
