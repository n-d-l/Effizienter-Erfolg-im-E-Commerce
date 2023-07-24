<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* runs the cronjob, updates page content if neccessary
*
* @return void
*/
function gm_protected_shops_cronjob() {

	$api = new GM_Protected_Shops_Api();

	try {

		// init
		$can_use_api 				= $api->can_use_api();
		$ps_documents 				= $api->get_documents();
		$documents_with_auto_update = get_option( '_gm_protected_shops_auto_update_documents_with_autoupdate', array() );

		// foreach document from ps api
		foreach ( $ps_documents as $ps_document ) {

			// check if auto update page content is enabled
			$gm_document_id = sanitize_title( $ps_document[ 'type' ] );

			if ( isset( $documents_with_auto_update[ $gm_document_id ] ) ) {

				// get page id
				$page_id = intval( get_option( 'gm_protected_shops_page_assignment_' . $gm_document_id ) );

				if ( $page_id > 0 ) {

					// get update time in WordPress
					$last_update_time_of_document = get_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_time', current_time( 'timestamp' ) );

				} else {

					// if no page is set
					$last_update_time_of_document = get_option( '_gm_protected_shops_auto_update_' . 'without_page' . '_' . $gm_document_id . '_last_update_time', current_time( 'timestamp' ) );
				}

				// get update time in ps
				$last_update_time_in_ps = strtotime( $ps_document[ 'updated_at' ] );

				if ( $last_update_time_in_ps > $last_update_time_of_document  ) {

					// do the update of wordpress page
					if ( $page_id > 0 ) {
					
						$new_document_content = $api->get_document( $ps_document[ 'type' ] );

						if ( is_array( $new_document_content ) ) {

							$new_document_content = '<style>' . $new_document_content[ 'css' ] . '</style>' . $new_document_content[ 'html' ];

						}

						$the_post = array(
							'ID'           => $page_id,
							'post_content' => $new_document_content,
						);

						// Update the post into the database
						kses_remove_filters();
						wp_update_post( $the_post );
						kses_init_filters();
					}

					// save pdf
					$api = new GM_Protected_Shops_Api();
					$api->save_document_on_server( $ps_document[ 'type' ], 'pdf' );

					// Update last time String
					if ( ! ( $page_id > 0 ) ) {
						$page_id = 'without_page';
					}

					update_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_time', current_time( 'timestamp' ) );
					update_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_kind', __( 'Automatically updated', 'woocommerce-german-market' ) );

				}

			}

		}


	} catch ( Exception $e ) {

		do_action( 'gm_protected_shops_exception_during_cron_job', $e );

	}

}
