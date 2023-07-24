<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Email Attachments
* @hook woocommerce_email_attachments
* @param Array $attachments
* @param String $status
* @param WC_Order $order
* @return Array
*/
function gm_it_recht_kanzlei_email_attachments( $attachments, $status , $order ) {

	// init api
	$api = new GM_IT_Recht_Kanzlei_Api();
	
	// get documents
	$documents = $api->get_documents();

	foreach ( $documents as $doc_key => $doc_name ) {

		// general activation
		$activation_option = get_option( 'gm_it_recht_kanzlei_email_attachment_' . $doc_key, 'off' );
		if ( $activation_option == 'off' ) {
			continue;
		}

		// get emails that shall contain the attachment
		$allowed_statis = get_option( 'gm_it_recht_kanzlei_shops_attachment_mails_' . $doc_key, array() );

		// check whether pdf hast to be attached
		if ( in_array( $status, $allowed_statis ) ) {

			// get pdf file path
			$upload_path 	= $api->local_dir_for_pdf_storage;
			$file 			= $doc_name . '.pdf';
			$file_path 		= untrailingslashit( $upload_path ) . DIRECTORY_SEPARATOR . $file;

			if ( is_file( $file_path ) ) {
				
				$attachments[] = $file_path;
			
			} else {
				
				if ( $api->debugging ) {
					error_log( 'German Market IT-Recht Kanzlei Debug: File ' . $file_path . ' does not exist when trying to attache to email.' );
				}

			}

		}		

	}

	return $attachments;

}
