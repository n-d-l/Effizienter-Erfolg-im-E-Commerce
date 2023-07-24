<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GM_Protected_Shops_Api' ) ) {

	class GM_Protected_Shops_Api {

		private static $api_url_base	= 'https://api.protectedshops.de';
		private static $api_url_version = 'v2.0';
		static $api_url_locale 			= 'de';
		static $api_url_format 			= 'json';
		
		private static $partner_id 		= 'protectedshops';

		private static $client_id 		= '';
		private static $client_secret 	= '';
		private static $client_shop_id 	= '';
		
		private static $bearer 			= '';
		private static $bearer_expires  = '';

		private static $upload_path		= '';
		private static $upload_dir 		= '';

		/**
		* Constructor
		*/
		function __construct() {

			self::$client_id 		= get_option( 'gm_protected_shops_api_client_id', '' );
			self::$client_secret 	= get_option( 'gm_protected_shops_api_client_secret', '' );
			self::$client_shop_id 	= get_option( 'gm_protected_shops_api_shop_id', '' );

			self::$bearer 			= get_option( 'gm_protected_shops_api_bearer', '' );
			self::$bearer_expires	= get_option( 'gm_protected_shops_api_bearer_expires', time() );

			$wp_upload_dir 			= wp_upload_dir();
			self::$upload_path 		= untrailingslashit( $wp_upload_dir[ 'basedir' ] ) . DIRECTORY_SEPARATOR . 'german-market-protected-shops';
			self::$upload_dir 		= untrailingslashit( $wp_upload_dir[ 'baseurl' ] ) . '/german-market-protected-shops';

			if ( ! is_dir( self::$upload_path ) ) {
				wp_mkdir_p( self::$upload_path );
			}

			do_action( 'gm_protected_shops_api_after_construct', $this );

		}

		/**
		* Checks client_id, client_secred, client_shohp to be entered by user
		* Checks bearer and try to create a new one
		*
		* @throws Exception
		* @return bool
		*/
		public function can_use_api() {

			if ( self::$client_id == '' || self::$client_secret == '' || self::$client_shop_id == '' ) {

				 throw new Exception( __( 'There was an error connecting to the Protected Shops API.', 'woocommerce-german-market' ) . '<br />' . __( 'Please check whether you have entered your Client-ID, Client-Secret and Shop-ID in the settings completeley and correctly.', 'woocommerce-german-market' ) );

			} else if ( ! function_exists( 'curl_init' ) ) {

				throw new Exception( __( 'There was an error connecting to the Protected Shops API.', 'woocommerce-german-market' ) . '<br />' . __( 'The PHP cURL library seems not to be present on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' ) );

			} else {

				try {

					$this->refresh_bearer();
				
				} catch ( Exception $e ) {

					throw $e;

				}

			}

			return true;

		}

		/**
		* Refresh Bearer
		*
		* @throws Exception
		* @return void
		*/
		private function refresh_bearer() {

			if ( self::$bearer == '' || intval( self::$bearer_expires <= time() ) ) {

				// api post request
				$post_array = array(

					'client_id'		=> self::$client_id,
					'client_secret'	=> self::$client_secret,
					'grant_type'	=> 'client_credentials',
				);

				$api_response = $this->curl_post( $post_array, self::$api_url_base . "/oauth/v2/token", true );

				if ( isset( $api_response[ 'error' ] ) ) {

					// error handling
					$error_message = __( 'There was an error connecting to the Protected Shops API.', 'woocommerce-german-market' ) . '<br />' . __( 'Please check whether you have entered your Client-ID, Client-Secret and Shop-ID in the settings completeley and correctly.', 'woocommerce-german-market' );
					
					if ( isset( $api_response[ 'error_description' ] ) ) {
						$error_message .= '<br />' . sprintf( __( 'If you still get errors after this, please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), '"' . $api_response[ 'error_description' ] . '"' );
					}

					throw new Exception(  $error_message );
				
				} else {

					// if erverything was fine: save new bearer and expires in time
					if ( isset( $api_response[ 'access_token' ] ) ) {
						update_option( 'gm_protected_shops_api_bearer', $api_response[ 'access_token' ] );
						self::$bearer = $api_response[ 'access_token' ];
					}

					if ( isset( $api_response[ 'expires_in' ] ) ) {
						$expires_in = time() + intval( $api_response[ 'expires_in' ] );
						update_option( 'gm_protected_shops_api_bearer_expires', $expires_in );
						self::$bearer_expires = $expires_in;
					}

				}

			} else {
				
				// do not need to refresh bearer
				do_action( 'gm_protected_shops_api_bearer_no_refresh', $this );

			}

		}

		/**
		* Send API Post Request
		*
		* @param Array $Post_array
		* @param String $url
		* @param Boolean $auth
		* @return Array
		*/
		private function curl_post( $post_array, $url, $auth = false ) {

			// init
			$post_array_json = json_encode( $post_array, JSON_PRETTY_PRINT );

			$http_header = array(
			    "accept: application/json",
			    "cache-control: no-cache",
			    "content-type: application/json",
			);

			if ( ! $auth ) {

				$http_header[] = "authorization: Bearer " . self::$bearer;
			}

			$curl = curl_init();

			curl_setopt_array( $curl, 

				array(
				  	CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $post_array_json,
					CURLOPT_HTTPHEADER => $http_header,
				)

			);

			$response_json = curl_exec( $curl );

			curl_close( $curl );

			return json_decode( $response_json, true );

		}

		/**
		* Send API Get Request
		*
		* @param String $url
		* @return Array
		*/
		private function curl_get( $url, $authorization = true ) {

			// init
			$http_header = array(
			    "accept: application/json",
			    "cache-control: no-cache",
			    "content-type: application/json",
			);

			if ( $authorization ) {
				$http_header[] = "authorization: Bearer " . self::$bearer;
			}

			$curl = curl_init();

			curl_setopt_array( $curl, 

				array(
				  	CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => $http_header,
				)

			);

			$response_json = curl_exec( $curl );

			curl_close( $curl );

			return json_decode( $response_json, true );

		}

		/**
		* Get Build URL for js in ps library
		*
		* @return String
		*/
		public function get_build_url() {
			return wp_nonce_url( admin_url( 'admin-ajax.php?action=gm_ps_get_questionary' ), 'gm-ps-get-questionary' );
		}

		/**
		* Get Save URL for js in ps library
		*
		* @return String
		*/
		public function get_save_url() {
			return wp_nonce_url( admin_url( 'admin-ajax.php?action=gm_ps_save_questionary' ), 'gm-ps-save-questionary' );
		}

		/**
		* Get template path (url) for js in ps library
		*
		* @return String
		*/
		public function get_template_path() {
			return GM_PROTECTED_SHOPS_LIBRARY_URL . '/templates/';
		}

		/**
		* Get translation  path (url) for js in ps library
		*
		* @return String
		*/
		public function get_translation_path() {
			return GM_PROTECTED_SHOPS_LIBRARY_URL . '/translations/';
		}

		/**
		* Get questionary as echo output for ajax, used in ps js library
		*
		* @access public
		* @throws Exception
		* @return void
		**/
		public function get_questionary( $output = true ) {

			$rtn = '';

			$this->refresh_bearer();
			$url = self::$api_url_base . '/' . self::$api_url_version . '/' . self::$api_url_locale . '/partners/' . self::$partner_id . '/shops/' . self::$client_shop_id . '/questionary/format/json';

			$curl = curl_init();

			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . self::$bearer ) );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true);

			$response_json = curl_exec( $curl );

			curl_close( $curl );

			$check_error = json_decode( $response_json, true );

			if ( isset( $check_error[ 'error' ] ) ) {
				
				// error handling
				$error_message = __( 'There was an error connecting to the Protected Shops API.', 'woocommerce-german-market' ) . '<br />' . __( 'Please check whether you have entered your Client-ID, Client-Secret and Shop-ID in the settings completeley and correctly.', 'woocommerce-german-market' );
					
				if ( isset( $check_error[ 'error_description' ] ) ) {
					$error_message .= '<br />' . sprintf( __( 'If you still get errors after this, please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), '"' . $check_error[ 'error_description' ] . '"' );
				}

				throw new Exception( $error_message );

			} else {

				$rtn = $response_json;

			}

			if ( $output ) {
				echo $rtn;
			}
			

		}

		/**
		* Save questionary, used in ps js library
		*
		* @access public
		* @return void
		**/
		public function save_questionary() {

			$this->refresh_bearer();
			$url = self::$api_url_base . '/' . self::$api_url_version . '/' . self::$api_url_locale . '/partners/' . self::$partner_id . '/shops/' . self::$client_shop_id . '/answers/format/json';
			$answers = $_REQUEST[ 'answers' ];

			$curl = curl_init();

			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . self::$bearer ) );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true) ;
			curl_setopt( $curl, CURLOPT_POST, 1);
			curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( array( 'answers' => $answers ) ) ) ;

			$response_json = curl_exec( $curl );

			curl_close( $curl );

		}

		/**
		* Get all available documents
		*
		* @access public
		* @throws Exception
		* @return Array
		**/
		public function get_documents() {

			$url = self::$api_url_base . '/' . self::$api_url_version . '/' . self::$api_url_locale . '/partners/' . self::$partner_id . '/shops/' . self::$client_shop_id . '/documents/format/json';
			$api_response = $this->curl_get( $url );

			if ( ! isset( $api_response[ 'content' ][ 'documents' ] ) ) {
				throw new Exception( sprintf( __( 'Please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), 'Wrong API answer when trying to get documents.' ) );
			}

			return $api_response[ 'content' ][ 'documents' ];

		}

		/**
		* Get content of a specific document
		*
		* @access public
		* @param String $type
		* @return String
		**/
		public function get_document( $type ) {

			$rtn = sprintf( __( 'Preview could not be loaded. Please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), 'Wrong API answer when trying to get documents: ' . $type );
			
			if ( isset( $_REQUEST[ 'gm_protected_shops_document_format_' . sanitize_title( $type ) ] ) ) {
				$content_format = $_REQUEST[ 'gm_protected_shops_document_format_' . sanitize_title( $type ) ];
			} else {
				$content_format = get_option( 'gm_protected_shops_document_format_' . sanitize_title( $type ), 'text' );
			}

			$url = self::$api_url_base . '/' . self::$api_url_version . '/' . self::$api_url_locale . '/partners/' . self::$partner_id . '/shops/' . self::$client_shop_id . '/documents/' . $type . '/contentformat/' . $content_format .'/format/json';
			$api_response = $this->curl_get( $url, false );

			if ( isset( $api_response[ 'content' ] ) ) {
				$rtn = $api_response[ 'content' ];
			}

			return $rtn;

		}

		/**
		* Save documents on server, do override
		*
		* @access public
		* @param String $type
		* @param String content_format
		* @return void
		**/
		public function save_document_on_server( $type, $content_format ) {

			$api_response = $this->download_document( $type, $content_format );

			$content = $api_response[ 'content' ];

			if ( isset( $api_response[ 'content' ] ) ) {

				$name = $api_response[ 'title' ];
				$file_content = base64_decode( $api_response[ 'content' ] );
				$file_extension = ( $content_format == 'pdf' ) ? '.pdf' : '.docx';
				$file = self::$upload_path . DIRECTORY_SEPARATOR . $name . $file_extension;

				file_put_contents( $file, $file_content );

			}

		}

		/**
		* Checks if docx and pdf exists on server
		*
		* @access public
		* @param String $name
		* @param String type
		* @return Boolean
		**/
		public function check_document_on_server( $name, $content_format ) {

			$file_extension = ( $content_format == 'pdf' ) ? '.pdf' : '.docx';
			$file = self::$upload_path . DIRECTORY_SEPARATOR . $name . $file_extension;

			return is_file( $file );

		}

		/**
		* Checks if docx and pdf exists on server, if not: create them
		*
		* @access public
		* @param String $name
		* @param String type
		* @return void
		**/
		public function check_files_and_maybe_create( $name, $type ) {

			$content_format = 'pdf';

			if ( ! $this->check_document_on_server( $name, $content_format ) ) {
				$this->save_document_on_server( $type, $content_format );
			}

		}

		/**
		* Get file url
		*
		* @access public
		* @param String $name
		* @param String content_format
		* @return String
		**/
		public function get_file_url( $name, $content_format ) {
			$file_extension = ( $content_format == 'pdf' ) ? '.pdf' : '.docx';
			return self::$upload_dir . '/' . $name . $file_extension;
		}

		/**
		* Get upload path
		*
		* @access public
		* @return String
		**/
		public function get_upload_path() {
			return self::$upload_path;
		}

		/**
		* Get content of a specific document to be downloaded (pdf and doxc documents)
		*
		* @access public
		* @param String $type
		* @param String content_format
		* @return Array
		**/
		public function download_document( $type, $content_format ) {

			$url = self::$api_url_base . '/' . self::$api_url_version . '/' . self::$api_url_locale . '/partners/' . self::$partner_id . '/shops/' . self::$client_shop_id . '/documents/' . $type . '/contentformat/' . $content_format .'/format/json';
			$api_response = $this->curl_get( $url, false );

			return $api_response;

		}

	}

}
