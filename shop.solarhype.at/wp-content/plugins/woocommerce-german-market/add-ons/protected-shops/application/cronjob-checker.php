<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Check whether the cronjob has to run
*
* wp-hook wp_loaded
* @return void
*/
function gm_protected_shops_cronjob_checker() {

	// init
	$documents_with_auto_update = get_option( '_gm_protected_shops_auto_update_documents_with_autoupdate', array() );
	$setting 					= get_option( 'gm_protected_shops_auto_update_interval', 'daily' );
	$hours						= intval( get_option( 'gm_protected_shops_auto_update_custom_interval', 24 ) );

	if ( isset( $_REQUEST[ 'sub_tab' ] ) && $_REQUEST[ 'sub_tab' ] == 'auto_update_of_page_content_settings' ) {
		if ( isset( $_POST[ 'submit_save_wgm_options' ] ) ) {
			if ( wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {
				
				delete_option( '_gm_protected_shops_next_auto_updater_time' );
				$setting = $_REQUEST [ 'gm_protected_shops_auto_update_interval' ];
				$hours = intval( $_REQUEST[ 'gm_protected_shops_auto_update_custom_interval' ] );
			}

		}

	}

	$next_update_time = get_option( '_gm_protected_shops_next_auto_updater_time', '' );

	if ( ! empty( $documents_with_auto_update ) ) {

		if ( empty( $next_update_time ) ) {

			// init next update time
			$next_update_date 	= new DateTime( current_time( 'Y-m-d H:i' ) );

			if ( $setting == 'daily' ) {
				$next_update_date->add( new DateInterval( 'P1D' ) );
			} else if ( $setting == 'weekly' ) {
				$next_update_date->add( new DateInterval( 'P7D' ) );
			} else if ( $setting == 'monthly') {
				$next_update_date->add( new DateInterval( 'P1M' ) );
			} else {
				$next_update_date->add( new DateInterval( 'PT' . $hours . 'H' ) );
			}

			update_option( '_gm_protected_shops_next_auto_updater_time', $next_update_date->format( 'Y-m-d H:i:s' ) );
			$next_update_time = get_option( '_gm_protected_shops_next_auto_updater_time', '' );
		}

		$now = new DateTime( current_time( 'Y-m-d H:i:s' ) );

		$next_update_time_date = new DateTime( $next_update_time );

		if ( $next_update_time_date < $now ) {

			// load and do the cronjob
			require_once( 'cronjob.php' );
			gm_protected_shops_cronjob();

			// save new next_update_time
			$is_smaller = false;

			while ( ! $is_smaller ) {

				if ( $setting == 'daily' ) {
					$next_update_time_date->add( new DateInterval( 'P1D' ) );
				} else if ( $setting == 'weekly' ) {
					$next_update_time_date->add( new DateInterval( 'P7D' ) );
				} else if ( $setting == 'monthly') {
					$next_update_time_date->add( new DateInterval( 'P1M' ) );
				} else {
					$next_update_time_date->add( new DateInterval( 'PT' . $hours . 'H' ) );
				}

				if ( $next_update_time_date > $now ) {
					$is_smaller = true;
				}

			}

			// update next update time
			update_option( '_gm_protected_shops_next_auto_updater_time', $next_update_time_date->format( 'Y-m-d H:i:s' ) ); 
		
		}

	}

}
