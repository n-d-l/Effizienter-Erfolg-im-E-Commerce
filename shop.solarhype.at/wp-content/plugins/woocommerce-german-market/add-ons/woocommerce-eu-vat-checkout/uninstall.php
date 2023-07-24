<?php
/**
 * Uninstall routines. This file is called automatically when the plugin
 * is deleted per user interface.
 *
 * See http://codex.wordpress.org/Function_Reference/register_uninstall_hook
 */

// Prevent direct access.
if ( ! ( defined( 'WGM_UNINSTALL_ADD_ONS' ) || defined( 'WP_UNINSTALL_PLUGIN' ) ) ) {
	exit;
}


// ------ Plugin options ------

delete_option( 'wcevc_dismiss_tax_based_on_notice_wgm' );
delete_option( 'wcevc_enabled_wgm' );
