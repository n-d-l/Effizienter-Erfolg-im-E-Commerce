<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GM_IT_Recht_Kanzlei_Api' ) ) {

	class GM_IT_Recht_Kanzlei_Api {

		// IT-Recht Kanzlei Settings
		private $local_api_version 						= '1.0';
		private $local_api_username 					= 'ge_ma_user';
		private $local_api_password 					= 'X3a9Pq_7fA';
		private $local_supported_rechtstext_types 		= array( 'agb', 'datenschutz', 'widerruf', 'impressum' );
		private $local_supported_rechtstext_languages 	= array( 'de' );
		private $local_supported_rechtstext_countries 	= array( 'DE', 'AT' );
		private $local_supported_actions 				= array( 'push' );
		private $local_rechtstext_pdf_required 			= array( 'agb' => true, 'datenschutz' => true, 'widerruf' => true, 'impressum' => false );
		private $local_limit_download_from_host 		= '';
		private $local_flag_multishop_system 			= false;
		private $test_with_local_xml_file 				= false;
		private $locale_api_user_auth_token				= '';
		public $local_dir_for_pdf_storage 				= '';
		public $upload_dir 								= '';

		public $debugging 								= false;

		/**
		* Constructor
		*/
		function __construct() {

			$this->locale_api_user_auth_token = get_option( 'gm_it_recht_kanzlei_api_token', '' );

			if ( $this->locale_api_user_auth_token == '' ) {
				$this->generate_api_token();
			}

			$wp_upload_dir 						= wp_upload_dir();
			$this->local_dir_for_pdf_storage	= untrailingslashit( $wp_upload_dir[ 'basedir' ] ) . DIRECTORY_SEPARATOR . 'german-market-it-recht-kanzlei';
			$this->upload_dir 					= untrailingslashit( $wp_upload_dir[ 'baseurl' ] ) . '/german-market-it-recht-kanzlei';

			if ( ! is_dir( $this->local_dir_for_pdf_storage ) ) {
				wp_mkdir_p( $this->local_dir_for_pdf_storage );
			}

			do_action( 'gm_it_recht_kanzlei_api_after_construct', $this );
		}

		/**
		* Generate API token
		*
		* @access private
		* @return void
		*/
		private function generate_api_token() {

			$allowed_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-=';
    		$random_string = '';
		    for ( $i = 0; $i < apply_filters( 'gm_it_recht_kanzlei_api_token_length', 50 ); $i++ ) {
		        $random_string .= $allowed_chars[ rand( 0, strlen( $allowed_chars ) - 1 ) ];
		    }
    		
    		$this->locale_api_user_auth_token = $random_string;

    		update_option( 'gm_it_recht_kanzlei_api_token', $this->locale_api_user_auth_token );

		}

		/**
		* Generate API token
		*
		* @access public
		* @return String
		*/
		public function get_api_token() {
			return $this->locale_api_user_auth_token;
		}

		/**
		* Gets supported pages
		*
		* @access public
		* @return Array
		*/
		public function get_documents() {

			return array(

				'agb' 			=> __( 'Terms of Conditions', 'woocommerce-german-market' ),
				'datenschutz'	=> __( 'Privacy', 'woocommerce-german-market' ),
				'widerruf'		=> __( 'Revocation', 'woocommerce-german-market' ),
				'impressum'		=> __( 'Imprint', 'woocommerce-german-market' ),

			);

		}

		/**
		* Handling of API Request from IT-Recht Kanzlei
		*
		* @access public
		* @static
		* @wp-hook wp_loaded
		* @return void
		*/
		public static function check_api_request() {

			if ( is_admin() || ( defined( 'DOING_AJAX') && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
				return;
			}

			if ( ! strstr( $_SERVER[ 'REQUEST_URI' ], '/api/gm-it-recht-kanzlei' ) ) {
				return;
			}

			$xml = '';

			if ( ! empty( $_POST[ 'xml' ] ) ) {
				
				$api = new self();
				$api->answer_api_request( $_POST[ 'xml' ] );

			}

		}

		/**
		* Answer of API Request from IT-Recht Kanzlei
		*
		* @access public
		* @return void
		*/
		public function answer_api_request( $post_xml ) {

			// if your system is a multishop system, action 'getaccountlist' should be supported
			if ( $this->local_flag_multishop_system === true ){ 
				array_push( $this->local_supported_actions, 'getaccountlist' ); 
			}
			
			// no host limit for downloading pdf when testing
			if ( $this->test_with_local_xml_file === true ) { 
				$this->local_limit_download_from_host = '';
			}

			// remove form slashes
			$post_xml = wp_unslash( $post_xml );

			// Catch errors - no data sent
			if ( $this->test_with_local_xml_file !== true ) { 
				if ( trim( $post_xml ) == '' ) { 
					$this->return_error( '12' );
				}
			 }

			 // create xml object
			if ( $this->test_with_local_xml_file !== true ) {
				$xml = @simplexml_load_string( $post_xml );
			} else {
				$xml = @simplexml_load_file( apply_filters( 'gm_it_recht_kanzlei_example_test_xml', 'beispiel.xml' ) );
			}

			// Catch errors - error creating xml object
			if ( $xml === false ){ 
				$this->return_error( '12' );
			}

			// Catch errors - action not supported
			if ( ($xml->action == '' ) || ( in_array( $xml->action, $this->local_supported_actions ) == false) ) {
				$this->return_error( '10' );
			}

			// Check api-version
			if ( $xml->api_version != $this->local_api_version ) {
				$this->return_error( '1' );
			}

			// Check api authentication
			if ( ( $xml->api_username != $this->local_api_username) || ( $xml->api_password != $this->local_api_password ) ) { 
				$this->return_error( '2' ); 
			}

			// Authentification
			if ( trim( $this->locale_api_user_auth_token ) != trim( $xml->user_auth_token ) ) { 
				$this->return_error( '3' ); 
			}

			// Begin action push
			if ( $xml->action == 'push' ) {
	
				// Catch errors - rechtstext_type 
				if ( ( $xml->rechtstext_type == '' ) || ( in_array( $xml->rechtstext_type, $this->local_supported_rechtstext_types ) == false ) ) { 
					$this->return_error( '4' ); 
				}
				
				// Catch errors - rechtstext_text
				if ( strlen( $xml->rechtstext_text ) < 50 ) { 
					$this->return_error( '5' );
				}
				
				// Catch errors - rechtstext_html
				if ( strlen( $xml->rechtstext_html ) < 50 ) { 
					$this->return_error( '6' ); 
				}

				// Catch errors - rechtstext_language
				if ( ( $xml->rechtstext_language == '' ) || ( in_array( $xml->rechtstext_language, $this->local_supported_rechtstext_languages ) == false ) ) { 
					$this->return_error( '9' ); 
				}

				// Catch errors - rechtstext_country
				if ( ( $xml->rechtstext_country == '' ) || ( in_array( $xml->rechtstext_country, $this->local_supported_rechtstext_countries ) == false ) ) { 
					$this->return_error( '17' ); 
				}

				// Save PDF File
				if ( $this->local_rechtstext_pdf_required[ (string) $xml->rechtstext_type ] === true ) {
		
					// Catch errors - element 'rechtstext_pdf_url' empty or URL invalid
					if ( ( $xml->rechtstext_pdf_url == '' ) || ( $this->url_valid( $xml->rechtstext_pdf_url, $this->local_limit_download_from_host ) !== true ) ) { 
						$this->return_error( '7' );
					}
					
					// Download pdf file
					$documents = $this->get_documents();
					$document_name = $documents[ (string) $xml->rechtstext_type ];

					$file_pdf_targetfilename = $document_name . '.pdf';
					$file_pdf_target = $this->local_dir_for_pdf_storage . DIRECTORY_SEPARATOR . $file_pdf_targetfilename;
					$file_pdf = fopen( $file_pdf_target, "w+" );
					
					// catch errors
					if ( $file_pdf === false ) {
						$this->return_error( '7', 'Cann not write pdf file to upload directory.' );
					}
					
					if ( ini_get( 'allow_url_fopen' ) == true ) { 
						
						// allow_url_fopen is enabled	
						$retval = @fwrite( $file_pdf, @file_get_contents( $xml->rechtstext_pdf_url ) );
					
					}  else if ( function_exists( 'curl_init' ) ) {

						// allow_url_fopen is disabled: use curl
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_HEADER, 0 );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1) ;
						curl_setopt( $ch, CURLOPT_URL, $xml->rechtstext_pdf_url  );
						$data = curl_exec( $ch );
						curl_close( $ch );

						$retval = @file_put_contents( $file_pdf_target, $data );
						
					} else {

						$this->return_error( '7', 'Neither allow_url_fopen nor curl_init is available on the server.' );

					}
					
					if ( $retval === false ){ 
						$this->return_error( '7', '$retval is false.' );
					}

					$retval = @fclose( $file_pdf );
					if ( $retval === false ) { 
						$this->return_error( '7', 'Error while closing pdf file' );
					}
					
					// Catch errors - downloaded file was not properly saved
					if ( file_exists( $file_pdf_target ) !== true ) { 
						$this->return_error( '7', 'Pdf file has not be saved.' );
					}
					
					// verify that file is a pdf
					if ( $this->check_if_pdf_file( $file_pdf_target ) !== true ) {
						unlink( $file_pdf_target );
						$this->return_error( '7', 'Pdf file check failed.' );
					}
					
					// verify md5-hash, delete file if hash is not equal
					if ( md5_file( $file_pdf_target ) != $xml->rechtstext_pdf_md5hash ) {
						unlink( $file_pdf_target );
						$this->return_error( '8', 'MD5 hash fail.' );;
					}
					
				}

				// Save WordPress page content
				$assigned_wordpress_page = intval( get_option( 'gm_it_recht_kanzlei_page_assignment_' . $xml->rechtstext_type ) );

				if ( ! ( $assigned_wordpress_page > 0 ) ) {
					$this->return_error( '80', 'No WordPress page has been assigned.' );
				}

				// Get format from Settings
				$html_or_text = get_option( 'gm_it_recht_kanzlei_page_format_' . $xml->rechtstext_type, 'text' );

				if ( $html_or_text == 'html' ) {
					$page_content = $xml->rechtstext_html;
				} else if ( $html_or_text == 'text' ) {
					$page_content = $xml->rechtstext_text;
				} else {
					$this->return_error( '99', 'Neither html nor text has been set as document format.' );
				}

				// Update the post
				kses_remove_filters();

				if ( is_a( $page_content, 'SimpleXMLElement' ) ) {
					$page_content = $page_content->__toString();
				}
				
				$the_post = array(
					'ID'           => $assigned_wordpress_page,
					'post_content' => $page_content,
				);

				$page_id = wp_update_post( $the_post );

				kses_init_filters();

				if ( is_wp_error( $page_id ) ) {
					$this->return_error( '81', 'WordPress page has not been updated.' );
				}

				// Update save time
				update_option( 'gm_it_recht_kanzlei_document_last_update_' . $xml->rechtstext_type, current_time( 'timestamp' ) );

				// Return success
				$this->return_success();
				
			}

		}

		/**
		* Validate URL
		* @access private
		* @param String $rul
		* @return Boolean
		**/
		private function url_valid( $url, $limit_to_host = '' ) {
			// $limit_to_host is obsolete and remains as a parameter for compatibility reasons
			
			// check for allowed URLs
			if ( ( md5( md5( strtolower( substr( $url, 0, 32 ) ) ) ) == 'e8d1c6ea05d248e381301ffff004c0d8' ) || ( md5( md5( strtolower( substr( $url, 0, 33 ) ) ) ) == '43f82fb310c6c9f9d4f59a64e194252f' ) || ( strtolower( substr( $url, 0, 31 ) ) == 'http://www.it-recht-kanzlei.de/' ) || ( strtolower( substr( $url, 0, 32 ) ) == 'https://www.it-recht-kanzlei.de/' ) ) { 
				return true; 
			} else { 
				return false; 
			}

		}

		/**
		* Check if a file is a pdf
		* @access private
		* @param String $filename
		* @return Boolean
		**/
		private function check_if_pdf_file( $filename ) {
			$handle 	= @fopen( $filename, "r" );
			$contents 	= @fread( $handle, 4 );
			@fclose($handle);
			if ( $contents == '%PDF' ) { 
				return true; 
			} else { 
				return false; 
			}
		}

		/**
		* Return error and end script
		* @access private
		* @param String $errorcode
		* @return void
		**/
		function return_error( $errorcode, $german_market_debugging = 0 ){ 
			
			// output error
			header( 'Content-type: application/xml; charset=utf-8' );
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
			echo "<response>\n";
			echo "	<status>error</status>\n";
			echo "	<error>" . $errorcode . "</error>\n";
			$this->echo_moudul_and_shop_version();
			echo "</response>";

			if ( $this->debugging ) {
				error_log( 'German Market IT-Recht Kanzlei Debug: ' . $error_code . ' - ' . $german_market_debugging );
			}

			exit();
			
		}
		
		/**
		* Return success and end script
		* @access private
		* @param String $errorcode
		* @return void
		**/
		function return_success(){
			
			// output success
			header( 'Content-type: application/xml; charset=utf-8' );
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
			echo "<response>\n";
			echo "	<status>success</status>\n";
			$this->echo_moudul_and_shop_version();
			echo "</response>";
			exit();
			
		}

		/**
		* Echo modul and shop version for error and success message
		* @access private
		* @return void
		**/
		private function echo_moudul_and_shop_version() {
			
			global $woocommerce;

			echo "    <meta_shopversion>" . $woocommerce->version . "</meta_shopversion>\n";
			echo "    <meta_modulversion>" . Woocommerce_German_Market::$version . "</meta_modulversion>\n";
		}

	}

}
