<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/* delete all options from wp_options */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bm_%'" );

/* delete all customer groups */
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type='customer_groups'" );

/* delete all assigned product meta */
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'bm_%'" );
