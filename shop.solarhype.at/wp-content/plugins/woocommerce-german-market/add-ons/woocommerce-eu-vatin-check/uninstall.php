<?php
/**
 * woocommerce-eu-vatin-check
 *
 * Uninstalling Options
 */

// Prevent direct access.
if ( ! ( defined( 'WGM_UNINSTALL_ADD_ONS' ) || defined( 'WP_UNINSTALL_PLUGIN' ) ) ) {
	exit;
}

delete_option( 'vat_options_label' );
