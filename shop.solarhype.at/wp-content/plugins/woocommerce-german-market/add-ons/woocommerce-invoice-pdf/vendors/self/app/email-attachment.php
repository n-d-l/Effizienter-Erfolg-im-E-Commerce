<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Email_Attachment' ) ) {

	/**
	* adds the pdf as an attachment to e-mails
	*
	* @class WP_WC_Invoice_Pdf_Email_Attachment
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Email_Attachment {

		/**
		 * @var string
		 */
		private static $security_salt = 'woocommerce-german-market';

		/**
		 * @var int
		 */
		private static $security_length = 12;

		/**
		 * Returns the status page if links arent working.
		 *
		 * @access private
		 * @static
		 *
		 * @returns void
		 */
		private static function download_email_error_handler() {

			if ( has_action( 'wp_wc_invoice_pdf_download_email_error_handler' ) ) {
				do_action( 'wp_wc_invoice_pdf_download_email_error_handler' );
			} else {

				header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
				header_remove( 'Content-Description;' );
				header_remove( 'Content-Disposition' );
				header_remove( 'Content-Transfer-Encoding' );

				$error_message     = apply_filters( 'wc_gm_download_email_error_handler_message', __( 'Sorry, this download link does not work.', 'woocommerce-german-market' ) );
				$error_page_title  = apply_filters( 'wc_gm_download_email_error_handler_page_title', __( 'Sorry, this download link does not work.', 'woocommerce-german-market' ) );
				$error_status_code = apply_filters( 'wc_gm_download_email_error_handler_status_code', '404' );

				if ( ! strstr( $error_message, '<a ' ) ) {
					$error_message .= ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-forward">' . esc_html__( 'Go to shop', 'woocommerce-german-market' ) . '</a>';
				}

				wp_die( $error_message, $error_page_title, array( 'response' => $error_status_code ) );
			}
		}

		/**
		 * Download the refunded invoice PDF via url.
		 *
		 * @Hook wp_ajax_nopriv_gm_email_refund_invoice_download
		 *
		 * @access public
		 * @static
		 *
		 * @return mixed, die()
		 */
		public static function download_email_refund_invoice_pdf() {

			$refund_id  = $_GET[ 'gm_invoice_pdf_refund_id' ] ?? 0;
			$order_id   = $_GET[ 'order_id' ] ?? 0;
			$order_key  = $_GET[ 'order_key' ] ?? 0;
			$order_hash = $_GET[ 'order_hash' ] ?? '';
			$email_hash = $_GET[ 'email' ] ?? '';

			// check security hash first
			if ( $order_hash !== substr( md5( $refund_id . '-' . $order_key . '-' . self::$security_salt ), 0, self::$security_length ) ) {
				self::download_email_error_handler();
			}

			$order = wc_get_order( $order_id );

			if ( is_object( $order ) ) {
				$email_address = $order->get_billing_email();
				$email_hash_proof = function_exists( 'hash' ) ? hash( 'sha256', $email_address ) : sha1( $email_address );
				if ( $email_hash !== $email_hash_proof ) {
					self::download_email_error_handler();
				}
			}

			if ( is_object( $order ) && ( $order_key == $order->get_order_key() ) ) {

				$refund = wc_get_order( $refund_id );

				// get filename
				$filename = get_option( 'wp_wc_invoice_pdf_refund_file_name_frontend', 'Refund-{{refund-id}} for order {{order-number}}' );
				// replace {{refund-id}}, the other placeholders will be managed by the class WP_WC_Invoice_Pdf_Create_Pdf
				$filename = str_replace( '{{refund-id}}', $order_id, $filename );
				$filename = self::repair_filename( apply_filters( 'wp_wc_invoice_pdf_refund_frontend_filename', $filename, $order ) );

				$download_behaviour = 'inline' === get_option( 'wp_wc_invoice_pdf_emails_link_download_behaviour', 'inline' ) ? 'inline' : '';

				// change template
				add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

				$args = array(
					'refund'			=> $refund,
					'order'				=> $order,
					'output_format'		=> 'pdf',
					'output'			=> $download_behaviour,
					'filename'			=> $filename,
					'frontend'		=> 'yes',
				);

				$refund = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
				remove_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

				exit();

			} else {
				self::download_email_error_handler();
			}

			die();
		}

		/**
		 * Download the invoice PDF via url.
		 *
		 * @Hook wp_ajax_nopriv_gm_email_invoice_download
		 *
		 * @access public
		 * @static
		 *
		 * @return mixed, die()
		 */
		public static function download_email_invoice_pdf() {

			$order_id   = $_GET[ 'gm_invoice_pdf_order_id' ] ?? 0;
			$order_key  = $_GET[ 'order_key' ] ?? 0;
			$order_hash = $_GET[ 'order_hash' ] ?? '';
			$email_hash = $_GET[ 'email' ] ?? '';

			// check security hash first
			if ( $order_hash !== substr( md5( $order_id . '-' . $order_key . '-' . self::$security_salt ), 0, self::$security_length ) ) {
				self::download_email_error_handler();
			}

			$order = wc_get_order( $order_id );

			if ( is_object( $order ) ) {
				$email_address = $order->get_billing_email();
				$email_hash_proof = function_exists( 'hash' ) ? hash( 'sha256', $email_address ) : sha1( $email_address );
				if ( $email_hash !== $email_hash_proof ) {
					self::download_email_error_handler();
				}
			}

			if ( is_object( $order ) && ( $order_key == $order->get_order_key() ) ) {

				$download_behaviour = 'inline' === get_option( 'wp_wc_invoice_pdf_emails_link_download_behaviour', 'inline' ) ? 'inline' : '';

				$args = array(
					'order'			=> $order,
					'output_format'	=> 'pdf',
					'output'		=> $download_behaviour,
					'filename'		=> self::repair_filename( apply_filters( 'wp_wc_invoice_pdf_frontend_filename', get_option( 'wp_wc_invoice_pdf_file_name_frontend', get_bloginfo( 'name' ) . '-' . __( 'Invoice-{{order-number}}', 'woocommerce-german-market' ) ), $order ) ),
					'frontend'		=> 'yes',
				);

				$invoice = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
				exit();

			} else {
				self::download_email_error_handler();
			}

			die();
		}

		/**
		* Add PDF download link to Costumer Email for higher safety.
		*
		* @Hook woocommerce_email_order_details
		* @Hook woocommerce_email_order_meta
		*
		* @access public
		* @static
		*
		* @param WC_Order $order
		* @param mixed    $sent_to_admin
		* @param string   $plain_text
		* @param string   $email
		*
		* @return void
		*/
		public static function add_download_link( $order, $sent_to_admin, $plain_text = true, $email = null ) {

			if ( ! isset( $email->id ) ) {
				return;
			}

			// get the email id.
			$status = $email->id;

			// init.
			$allowed_stati = array(
				'customer_order_confirmation',
				'new_order',
				'customer_invoice',
				'customer_processing_order',
				'customer_completed_order',
				'customer_on_hold_order',
				'customer_note'
			);
			$allowed_stati = apply_filters( 'wp_wc_inovice_pdf_allowed_stati', $allowed_stati );

			$option_on_or_off = 'off';

			// check if download link has to be included
			if ( isset( $status ) && in_array( $status, $allowed_stati ) && apply_filters( 'wp_wc_invoice_pdf_allowed_order', true, $order ) ) {
				$option_on_or_off = get_option( 'wp_wc_invoice_pdf_emails_' . $status, 'off' );
				if ( 'on' == $option_on_or_off ) {

					$email_address      = $order->get_billing_email();
					$email_hash         = function_exists( 'hash' ) ? hash( 'sha256', $email_address ) : sha1( $email_address );
					$shop_url           = esc_url( wc_get_page_permalink( 'shop' ) );
					$invoice_order_id   = $order->get_id();
					$invoice_order_key  = $order->get_order_key();
					$invoice_label_text = apply_filters( 'wp_wc_invoice_pdf_emails_link_label_text', get_option( 'wp_wc_invoice_pdf_emails_link_label_text', __( 'Download Invoice Pdf', 'woocommerce-german-market' ) ) );
					$invoice_link_text  = apply_filters( 'wp_wc_invoice_pdf_emails_link_text', get_option( 'wp_wc_invoice_pdf_emails_link_text', __( 'Please use the following link to download your invoice PDF: {invoice_download_link}', 'woocommerce-german-market' ) ) );
					$invoice_link_url   = $shop_url . '?gm_invoice_pdf_order_id=' . $invoice_order_id . '&order_key=' . $invoice_order_key . '&order_hash=' . substr( md5( $invoice_order_id . '-' . $invoice_order_key . '-' . self::$security_salt ), 0, self::$security_length ) . '&email=' . $email_hash;
					$invoice_link       = apply_filters( 'wp_wc_invoice_pdf_emails_link_markup',
										      '<a href="' . $invoice_link_url . '" class="gm_email_invoice_download_link" target="_blank">' . ( '' != $invoice_label_text ? $invoice_label_text : $invoice_link_url ) . '</a>',
										      $invoice_link_url,
										      $invoice_label_text
										  );

					if ( empty( $invoice_link_text ) ) {
						$invoice_link_text = '{invoice_download_link}';
					}

					if ( ! $plain_text ) {
						$invoice_text_with_link = str_replace( '{invoice_download_link}', $invoice_link, $invoice_link_text );
						echo wpautop( $invoice_text_with_link );
					} else {
						$invoice_text_with_link = str_replace( '{invoice_download_link}', $invoice_link_url, $invoice_link_text );
						echo "\n\n" . $invoice_text_with_link . "\n\n";
					}

				}
			}
		}

		/**
		 * Add Refund PDF download link to Costumer Email for higher safety.
		 *
		 * @Hook woocommerce_email_order_details
		 * @Hook woocommerce_email_order_meta
		 *
		 * @access public
		 * @static
		 *
		 * @param WC_Order $order
		 * @param mixed    $sent_to_admin
		 * @param string   $plain_text
		 * @param string   $email
		 *
		 * @return void
		 */
		public static function add_refund_download_link( $order, $sent_to_admin, $plain_text = true, $email = null ) {

			if ( ! isset( $email->id ) ) {
				return;
			}

			// get refund id
			$refund_id = get_post_meta( $order->get_id(), '_wp_wc_invoice_pdf_refund_id_for_email', true );
			$refund    = wc_get_order( $refund_id );

			// get filename
			$filename = get_option( 'wp_wc_invoice_pdf_refund_file_name_frontend', 'Refund-{{refund-id}} for order {{order-number}}' );
			// replace {{refund-id}}, the other placeholders will be managed by the class WP_WC_Invoice_Pdf_Create_Pdf
			$filename = str_replace( '{{refund-id}}', $refund_id, $filename );
			$filename = self::repair_filename( apply_filters( 'wp_wc_invoice_pdf_refund_frontend_filename', $filename, $refund ) );

			$email_address      = $order->get_billing_email();
			$email_hash         = function_exists( 'hash' ) ? hash( 'sha256', $email_address ) : sha1( $email_address );
			$shop_url           = esc_url( wc_get_page_permalink( 'shop' ) );
			$invoice_order_key  = $order->get_order_key();
			$invoice_label_text = apply_filters( 'wp_wc_invoice_pdf_emails_refunds_link_label_text', get_option( 'wp_wc_invoice_pdf_emails_refunds_link_label_text', __( 'Download refund pdf', 'woocommerce-german-market' ) ) );
			$invoice_link_text  = apply_filters( 'wp_wc_invoice_pdf_emails_refunds_link_text', get_option( 'wp_wc_invoice_pdf_emails_refunds_link_text', __( 'Please use the following link to download your refund PDF: {invoice_download_link}', 'woocommerce-german-market' ) ) );
			$invoice_link_url   = $shop_url . '?gm_invoice_pdf_refund_id=' . $refund_id . '&order_id=' . $order->get_id() . '&order_key=' . $invoice_order_key . '&order_hash=' . substr( md5( $refund_id . '-' . $invoice_order_key . '-' . self::$security_salt ), 0, self::$security_length ) . '&email=' . $email_hash;
			$invoice_link       = apply_filters( 'wp_wc_invoice_pdf_emails_refunds_link_markup',
									  '<a href="' . $invoice_link_url . '" class="gm_email_invoice_download_link" target="_blank">' . ( '' != $invoice_label_text ? $invoice_label_text : $invoice_link_url ) . '</a>',
									  $invoice_link_url,
									  $invoice_label_text
								  );

			if ( empty( $invoice_link_text ) ) {
				$invoice_link_text = '{invoice_download_link}';
			}

			if ( ! $plain_text ) {
				$invoice_text_with_link = str_replace( '{invoice_download_link}', $invoice_link, $invoice_link_text );
				echo wpautop( $invoice_text_with_link );
			} else {
				$invoice_text_with_link = str_replace( '{invoice_download_link}', $invoice_link_url, $invoice_link_text );
				echo "\n\n" . $invoice_text_with_link . "\n\n";
			}

			// clear
			delete_post_meta( $order->get_id(), '_wp_wc_invoice_pdf_refund_id_for_email' );

		}

		/**
		* Adds the pdf as an attachement to chosen customer e-mails
		*
		* @since 0.0.1
		*
		* @access public
		* @static
		*
		* @hook woocommerce_email_attachments
		*
		* @param array    $attachments
		* @param string   $status
		* @param WC_Order $order
		*
		* @return array
		*/
		public static function add_attachment( $attachments, $status, $order ) {

			  // init
			  $allowed_stati = array(
			      'customer_order_confirmation',
				  'new_order',
				  'customer_invoice',
				  'customer_processing_order',
				  'customer_completed_order',
				  'customer_on_hold_order',
				  'customer_note'
			  );
			  $allowed_stati = apply_filters( 'wp_wc_inovice_pdf_allowed_stati', $allowed_stati );

			  $option_on_or_off	= 'off';

			  // check if file has to be attached
			  if ( isset( $status ) && in_array( $status, $allowed_stati ) && apply_filters( 'wp_wc_invoice_pdf_allowed_order', true, $order ) ) {
				  $option_on_or_off = get_option( 'wp_wc_invoice_pdf_emails_' . $status, 'off' );
				  if ( 'on' == $option_on_or_off ) {
						$args = array(
							'order'			=> $order,
							'output_format'	=> 'pdf',
							'output'		=> 'cache',
							'filename'		=> self::repair_filename( apply_filters( 'wp_wc_invoice_pdf_frontend_filename', get_option( 'wp_wc_invoice_pdf_file_name_frontend', get_bloginfo( 'name' ) . '-' . __( 'Invoice-{{order-number}}', 'woocommerce-german-market' ) ), $order ) ),
						);

						do_action( 'wp_wc_invoice_before_adding_attachment', $status, $order );

						//remove_all_filters( 'wp_wc_invoice_pdf_template_invoice_content' );

						$invoice = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
					  	$attachments[] = WP_WC_INVOICE_PDF_CACHE_DIR . $invoice->cache_dir . DIRECTORY_SEPARATOR . $invoice->filename;
				  }
			  }
			return $attachments;
		}

		/**
		* triggers when order is refunded
		*
		* @since WGM 3.0
		* @access public
		* @static
		* @hook woocommerce_order_fully_refunded_notification
		* @hook woocommerce_order_partially_refunded_notification
		* @param int $order_id
	 	* @param int $refund_id
		* @return void
		*/
		public static function refunded_trigger( $order_id, $refund_id ) {

			if ( get_option( 'wp_wc_invoice_pdf_emails_customer_refunded_order' ) == 'on' ) {

				update_post_meta( $order_id, '_wp_wc_invoice_pdf_refund_id_for_email', $refund_id );
				do_action( 'wp_wc_invoice_before_adding_refund_attachment', $refund_id, $order_id );

				$invoice_attachment_format = get_option( 'wp_wc_invoice_pdf_emails_attachment_format', 'attachment' );
				if ( 'attachment' === $invoice_attachment_format ) {
					add_filter( 'woocommerce_email_attachments', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_refund_attachment' ), 10, 3 );
				} else
				if ( 'link' === $invoice_attachment_format ) {
					$invoice_download_link_position = get_option( 'wp_wc_invoice_pdf_emails_link_position', 'before_details' );
					if ( 'before_details' === $invoice_download_link_position ) {
						add_action( 'woocommerce_email_order_details', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_refund_download_link' ), 99, 4 );
					} else
					if ( 'after_details' === $invoice_download_link_position ) {
						add_action( 'woocommerce_email_order_meta', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_refund_download_link' ), 99, 4 );
					}
				}
			}

			if ( get_option( 'wp_wc_invoice_pdf_emails_customer_refunded_order_add_pdfs' ) == 'on' ) {
				add_filter( 'woocommerce_email_attachments', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'trigger_refund_for_additional_pdfs' ), 10, 3 );
			}
		}

		/**
		* Do that trick for adding additional pdfs to customer refunded order
		*
		* @since WGM 3.0.2
		* @access public
		* @static
		* @hook woocommerce_email_attachments
		* @param Array $attachments
		* @param String $status
		* @param WC_Order $order
		* @return Array
		*/
		public static function trigger_refund_for_additional_pdfs( $attachments, $status , $order ) {
			return self::additional_email_attachments( $attachments, 'customer_refunded_order', $order );
		}

		/**
		* adds the refund pdf as an attachement
		*
		* @since WGM 3.0
		* @access public
		* @static
		* @hook woocommerce_email_attachments
		* @param Array $attachments
		* @param String $status
		* @param WC_Order $order
		* @return Array
		*/
		public static function add_refund_attachment( $attachments, $status , $order ) {

			// get refund id
			$refund_id = get_post_meta( $order->get_id(), '_wp_wc_invoice_pdf_refund_id_for_email', true );
			$refund    = wc_get_order( $refund_id );

			// get filename
			$filename = get_option( 'wp_wc_invoice_pdf_refund_file_name_frontend', 'Refund-{{refund-id}} for order {{order-number}}' );
			// replace {{refund-id}}, the other placeholders will be managed by the class WP_WC_Invoice_Pdf_Create_Pdf
			$filename = str_replace( '{{refund-id}}', $refund_id, $filename );
			$filename = self::repair_filename( apply_filters( 'wp_wc_invoice_pdf_refund_frontend_filename', $filename, $refund ) );

			// change template
			add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

			$args = array(
				'refund'			=> $refund,
				'order'				=> $order,
				'output_format'		=> 'pdf',
				'output'			=> 'cache',
				'filename'			=> $filename,
			);

			$refund 						= new WP_WC_Invoice_Pdf_Create_Pdf( $args );
			$attachments[] = WP_WC_INVOICE_PDF_CACHE_DIR . $refund->cache_dir . DIRECTORY_SEPARATOR . $refund->filename;

			// clear
			delete_post_meta( $order->get_id(), '_wp_wc_invoice_pdf_refund_id_for_email' );
			remove_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

			return $attachments;

		}

		/**
		* adds additonal pdfs as an attachement to chosen customer e-mails
		*
		* @hook woocommerce_email_attachments
		* @param Array $attachments
		* @param String $status
		* @param WC_Order $order
		* @return Array
		* @return array $attachments
		*/
		public static function additional_email_attachments( $attachments, $status , $order ) {

			 // init
			$allowed_stati 	= array( 'customer_order_confirmation', 'new_order', 'customer_invoice', 'customer_processing_order', 'customer_completed_order', 'customer_on_hold_order', 'customer_refunded_order', 'customer_note' );
			$allowed_stati	= apply_filters( 'wp_wc_inovice_pdf_allowed_stati_additional_mals', $allowed_stati );
			$option_on_or_off	= 'off';

		  	// check if file has to be attached
			$option_on_or_off = get_option( 'wp_wc_invoice_pdf_emails_' . $status . '_add_pdfs', 'off' );
			if ( $option_on_or_off == 'on' ) {

				do_action( 'wp_wc_invoice_pdf_email_additional_attachment_before', array( 'order' => $order ) );

				if ( isset( $status ) && in_array ( $status, $allowed_stati ) ) {

					///////////////////////////
					// terms and conditions
					///////////////////////////
					
					// do whe have to inlcude this pdf?
					$options = array(
						'wp_wc_invoice_pdf_additional_pdf_legal_information_page',
						'wp_wc_invoice_pdf_additional_pdf_terms_page',
						'wp_wc_invoice_pdf_additional_pdf_privacy_page',
						'wp_wc_invoice_pdf_additional_pdf_shipping_and_delivery_page',
						'wp_wc_invoice_pdf_additional_pdf_payment_methods_page'
					);

					$include_terms_and_conditions_pdf = false;
					foreach ( $options as $option ) {
						if ( get_option( $option ) == 'yes' ) {
							$include_terms_and_conditions_pdf = true;
							break;
						}
					}

					$include_terms_and_conditions_pdf = apply_filters( 'wp_wc_invoice_pdf_include_terms_and_conditions_pdf', $include_terms_and_conditions_pdf, $order );

					if ( $include_terms_and_conditions_pdf ) {

						add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( __CLASS__, 'terms_and_conditions_content' ) );

						$args = array(
							'order'				=> $order,
							'output_format'		=> 'pdf',
							'output'			=> 'cache',
							'filename'			=> apply_filters( 'wp_wc_invoice_pdf_template_filename_termans_and_conditions', get_option( 'wp_wc_invoice_pdf_additional_pdfs_file_name_terms', __( 'Terms and conditions', 'woocommerce-german-market' ) ) ),
						);

						$invoice = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
						$attachments[] = WP_WC_INVOICE_PDF_CACHE_DIR . $invoice->cache_dir . DIRECTORY_SEPARATOR . $invoice->filename;

						remove_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( __CLASS__, 'terms_and_conditions_content' ) );
					}


					///////////////////////////
					// Recovation policy
					///////////////////////////
					$options = array(
						'wp_wc_invoice_pdf_additional_pdf_recovation_policy_page',
						'wp_wc_invoice_pdf_additional_pdf_recovation_policy_digital_page',
					);

					$include_revocation_pdf = false;
					foreach ( $options as $option ) {
						if ( get_option( $option ) == 'yes' ) {
							$include_revocation_pdf = true;
							break;
						}
					}

					$include_revocation_pdf = apply_filters( 'wp_wc_invoice_pdf_include_revocation_pdf', $include_revocation_pdf, $order );

					if ( $include_revocation_pdf ) {

						add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( __CLASS__, 'revocation_policy_content' ) );

						$args = array(
							'order'				=> $order,
							'output_format'		=> 'pdf',
							'output'			=> 'cache',
							'filename'			=> apply_filters( 'wp_wc_invoice_pdf_template_filename_revocation_policy', get_option( 'wp_wc_invoice_pdf_additional_pdfs_file_name_revocation', __( 'Revocation Policy', 'woocommerce-german-market' ) ) ),
						);

						$invoice = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
						$attachments[] = WP_WC_INVOICE_PDF_CACHE_DIR . $invoice->cache_dir . DIRECTORY_SEPARATOR . $invoice->filename;

						remove_filter( 'wp_wc_invoice_pdf_template_invoice_content',  array( __CLASS__, 'revocation_policy_content' ) );
					}
					

				}
			}

			return $attachments;

		}

		/**
		* template for terms and conditions in pdf
		*
		* @hook wp_wc_invoice_pdf_template_invoice_content
		* @param String $path
		* @return String
		*/
		public static function terms_and_conditions_content( $path ) {

			$theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'terms-and-conditions.php';
			if ( file_exists( $theme_template_file ) ) {
				$template_path = $theme_template_file;
			} else {
				$template_path = untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'terms-and-conditions.php';
			}

			$template_path = apply_filters( 'wp_wc_invoice_attachment_terms_and_conditions_content', $template_path, $path );

			return $template_path;

		}

		/**
		* recovation policy in pdf
		*
		* @hook wp_wc_invoice_pdf_template_invoice_content
		* @param String $path
		* @return String
		*/
		public static function revocation_policy_content( $path ) {

			$theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'revocation-policy.php';
			if ( file_exists( $theme_template_file ) ) {
				$template_path = $theme_template_file;
			} else {
				$template_path = untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'revocation-policy.php';
			}

			$template_path = apply_filters( 'wp_wc_invoice_attachment_revocation_policy_content', $template_path, $path );

			return $template_path;
		}

		/**
		* Filename may not include '/'
		*
		* @since GM 3.5.4.
		* @param String $filename
		* @return String
		*/
		public static function repair_filename( $filename ) {
			return str_replace( '/', '-', $filename );
		}

	} // end class

} // end if
