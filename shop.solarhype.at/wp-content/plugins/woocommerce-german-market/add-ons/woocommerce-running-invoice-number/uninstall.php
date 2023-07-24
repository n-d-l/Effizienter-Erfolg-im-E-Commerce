<?php
/**
 * WooCommerce Invoice PDF - Uninstall
 *
 * Uninstalling Options
 */

if ( ! ( defined( 'WGM_UNINSTALL_ADD_ONS' ) || defined( 'WP_UNINSTALL_PLUGIN' ) ) ) {
	exit;
}

$all_wordpress_options = wp_load_alloptions();

foreach ( $all_wordpress_options as $option_key => $option_value ) {
	
	if ( substr( $option_key, 0, 22 ) == 'wp_wc_running_invoice_' ) {
		delete_option( $option_key );
	}

}

delete_option( 'wp_wc_running_completed_order_email_header' );
delete_option( 'wp_wc_running_completed_order_email_subject' );
delete_site_option( 'wp_wc_running_invoice_number_multisite_global' );
delete_site_option( 'wp_wc_running_invoice_number_next_refund' );
delete_site_option( 'wp_wc_running_invoice_number_next' );
