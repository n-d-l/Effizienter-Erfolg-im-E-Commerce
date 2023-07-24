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
function gm_protected_shops_email_attachments( $attachments, $status , $order ) {

	try {

		$email_option = get_option( '_gm_protected_shops_documents_with_emai_attachments', array() );

		if ( ! empty( $email_option ) ) {

			$api = new GM_Protected_Shops_Api();
			$upload_path = $api->get_upload_path();

			foreach ( $email_option as $key => $value ) {

				$allowed_stati = $value[ 'emails' ];

				if ( in_array( $status, $allowed_stati ) ) {

					$files = $value[ 'formats' ];
					
					foreach ( $files as $file ) {

						$file_path = untrailingslashit( $upload_path ) . DIRECTORY_SEPARATOR . $file;

						if ( is_file( $file_path ) ) {
							$attachments[] = $file_path;
						}

					}

				}

			}

		}

	} catch ( Exception $e ) {

		return $attachments;

	}

	return $attachments;

}
