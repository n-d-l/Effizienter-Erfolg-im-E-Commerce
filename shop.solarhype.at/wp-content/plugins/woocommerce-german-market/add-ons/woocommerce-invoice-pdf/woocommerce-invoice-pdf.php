<?php
/* 
 * Add-on Name:	WooCommerce Invoice PDF
 * Description:	This plugin adds an Invoice PDF as an attachment to customer emails, enables backend download of the pdf and customer download on the my account page
 * Author:		MarketPress GmbH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Dompdf\Dompdf;

if ( ! class_exists( 'Woocommerce_Invoice_Pdf' ) ) {

	/**
	 * main class for plugin
	 *
	 * @class Woocommerce_Invoice_Pdf
	 * @version 1.0
	 * @category Class
	 */
	class Woocommerce_Invoice_Pdf {

		/**
		 * singleton
		 * @var object
		 */
		static $instance = null;

		/**
		 * _e(* @var string
		 */
		static public $plugin_filename = __FILE__;

		/**
		 * singleton getInstance
		 *
		 * @hook plugins_loaded
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return class Woocommerce_Invoice_Pdf
		 */
		public static function get_instance() {

			if ( null == self::$instance ) {
				self::$instance = new Woocommerce_Invoice_Pdf();
			}

			return self::$instance;
		}

		/**
		 * constructor
		 *
		 * @since 0.0.1
		 *
		 * @access private
		 *
		 * @return void
		 */
		private function __construct() {

			// if mb_string is missing
			require_once( untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'mb_string_missing.php' );

			// load dompdf autoloader
			define( 'WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH', untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'dompdf' );

			// auto-load classes on demand
			if ( function_exists( "__autoload" ) ) {
				spl_autoload_register( "__autoload" );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
			// define cache directory
			if ( ! defined( 'WP_WC_INVOICE_PDF_CACHE_DIR' ) ) {
				define( 'WP_WC_INVOICE_PDF_CACHE_DIR', untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR );
			}
			self::init();

		}

		/**
		 * autoload classes on demand
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 *
		 * @arguments string $class (class name)
		 *
		 * @return void
		 */
		public function autoload( $class ) {

			$filename     = false;
			$class        = strtolower( $class );
			$file         = 'class-' . str_replace( '_', '-', $class ) . '.php';
			$file         = str_replace( 'class-wp-wc-invoice-pdf-', '', $file );
			$vendors_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors';

			if ( $class == 'ebs_pdf_wordpress' ) {
				$file = $vendors_path . DIRECTORY_SEPARATOR . 'ebs-pdf' . DIRECTORY_SEPARATOR . $file;
			} else if ( strpos( $class, 'wp_wc_invoice_pdf_backend_' ) === 0 ) {
				$applications_backend_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'be';
				$file                      = $applications_backend_path . DIRECTORY_SEPARATOR . $file;
			} else {
				$applications_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app';
				$file              = $applications_path . DIRECTORY_SEPARATOR . $file;
			}

			if ( $file && is_readable( $file ) ) {
				include_once( $file );
			}

			// autoloader for QR-Code libs
			if ( self::has_php_7_4_for_qr_codes() ) {
				if ( file_exists( plugin_dir_path(Woocommerce_German_Market::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sepa-qr-data' . DIRECTORY_SEPARATOR . 'autoload.php' ) {

					require_once( untrailingslashit( plugin_dir_path(Woocommerce_German_Market::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sepa-qr-data' . DIRECTORY_SEPARATOR . 'autoload.php' );
					require_once( untrailingslashit( plugin_dir_path(Woocommerce_German_Market::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'php-qrcode' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
					require_once( untrailingslashit( plugin_dir_path(Woocommerce_German_Market::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'php-swiss-qr-bill' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
				}
				
			}			
		}

		/**
		 * cloning is private
		 *
		 * @since 0.0.1
		 */
		private function __clone() {
		}

		/**
		 * register actions and filters
		 *
		 * @since 0.0.1
		 *
		 * @access private
		 * @static
		 *
		 * @return void
		 */
		private static function init() {

			// download pdf actions
			if ( isset( $_GET[ 'gm_invoice_pdf_order_id' ], $_GET[ 'order_key' ], $_GET[ 'order_hash' ] ) ) {
				add_action( 'init', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'download_email_invoice_pdf' ) );
			} elseif ( isset( $_GET[ 'gm_invoice_pdf_refund_id' ], $_GET[ 'order_key' ], $_GET[ 'order_hash' ] ) ) {
				add_action( 'init', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'download_email_refund_invoice_pdf' ) );
			}

			// email attachment for invoice pdfs
			$invoice_attachment_format = get_option( 'wp_wc_invoice_pdf_emails_attachment_format', 'attachment' );
			if ( 'attachment' === $invoice_attachment_format ) {
				add_filter( 'woocommerce_email_attachments', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_attachment' ), 10, 3 );
			} else
			if ( 'link' === $invoice_attachment_format ) {
				// pdf download link in email
				$invoice_download_link_position = get_option( 'wp_wc_invoice_pdf_emails_link_position', 'before_details' );
				if ( 'before_details' === $invoice_download_link_position ) {
					add_action( 'woocommerce_email_order_details', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_download_link' ), 10, 4 );
				} else
				if ( 'after_details' === $invoice_download_link_position ) {
					add_action( 'woocommerce_email_order_meta', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'add_download_link' ), 99, 4 );
				}
			}

			add_action( 'woocommerce_order_fully_refunded_notification',     array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'refunded_trigger' ), 10, 2 );
			add_action( 'woocommerce_order_partially_refunded_notification', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'refunded_trigger' ), 10, 2 );

			// additional pdfs as email attachments
			add_filter( 'woocommerce_email_attachments', array( 'WP_WC_Invoice_Pdf_Email_Attachment', 'additional_email_attachments' ), 10, 3 );

			// plugin has been activated
			if ( is_admin() && get_option( 'wp_wc_invoice_pdf_just_activated', false ) ) {

				// admin_notices
				//add_filter( 'admin_notices', array( 'WP_WC_Invoice_Pdf_Backend_Activation', 'output_notices' ) );
				add_filter( 'admin_init', array( 'WP_WC_Invoice_Pdf_Backend_Activation', 'load_defaults' ) );
				add_filter( 'admin_init', array( 'WP_WC_Invoice_Pdf_Backend_Activation', 'setup_cache_dir' ) );
			}

			// option page
			if ( is_admin() ) {

				add_filter( 'woocommerce_de_ui_left_menu_items',                              array( 'WP_WC_Invoice_Pdf_Backend_Options_WGM', 'menu' ) );
				add_action( 'woocommerce_admin_field_wp_wc_invoice_pdf_textarea',             array( 'WP_WC_Invoice_Pdf_Backend_Options_WGM', 'output_textarea' ) );
				add_action( 'woocommerce_admin_field_wp_wc_invoice_pdf_test_download_button', array( 'WP_WC_Invoice_Pdf_Backend_Options_WGM', 'output_test_pdf_button' ) );
				add_filter( 'woocommerce_admin_settings_sanitize_option',                     array( 'WP_WC_Invoice_Pdf_Backend_Options_WGM', 'save_wp_wc_invoice_pdf_textarea_textarea' ), 10, 3 );
				add_filter( 'woocommerce_admin_settings_sanitize_option',                     array( 'WP_WC_Invoice_Pdf_Backend_Options_WGM', 'save' ), 10, 3 );
				add_action( 'admin_enqueue_scripts',                                          array( __CLASS__, 'media_uploader_scripts' ) );    // scripts and styles to use media uploader for image upload

				// backend download buttons
				if ( current_user_can( get_option( 'german_market_current_user_can_download_pdfs_in_admin', 'manage_woocommerce' ) ) ) {
					add_action( 'admin_enqueue_scripts',                                             array( __CLASS__, 'admin_styles' ) ); // style for download button
					add_action( 'woocommerce_order_actions_end',                                     array( 'WP_WC_Invoice_Pdf_Backend_Download', 'order_download' ) );
					add_filter( 'woocommerce_admin_order_actions',                                   array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_icon_download' ), 10, 2 );
					add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_invoice_download',            array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_ajax_download_pdf' ) );
					add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_invoice_delete_content',      array( 'WP_WC_Invoice_Pdf_Backend_Download', 'invoice_pdf_delete_saved_content' ) );
					add_action( 'admin_notices',                                                     array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_notices' ) );
					add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_test_invoice',                array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_ajax_test_invoice' ) );
					add_filter( 'wgm_refunds_actions',                                               array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_refund_icon_download' ), 10, 2 );
					add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_refund_download',             array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_ajax_download_refund_pdf' ) );
					add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_refund_delete_saved_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'admin_ajax_refund_delete_saved_content' ) );
				}

				if ( get_option( 'wp_wc_invoice_pdf_new_post_message', false ) == true ) {
					add_filter( 'admin_notices', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'output_notices' ) );
				}

				// bulk zip download since GM 3.1
				add_action( 'admin_footer',                      array( 'WP_WC_Invoice_Pdf_Backend_Download', 'bulk_admin_footer' ), 10 );
				add_action( 'load-edit.php',                     array( 'WP_WC_Invoice_Pdf_Backend_Download', 'bulk_action' ) );
				add_action( 'wgm_refunds_render_refund_id',      array( 'WP_WC_Invoice_Pdf_Backend_Download', 'refund_checkboxes' ), 10, 2 );
				add_action( 'wgm_refunds_render_refund_id_head', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'refund_checkboxes_select_all' ) );
				add_action( 'woocommerc_de_refund_before_list',  array( 'WP_WC_Invoice_Pdf_Backend_Download', 'submit_button' ) );
				add_action( 'woocommerc_de_refund_after_list',   array( 'WP_WC_Invoice_Pdf_Backend_Download', 'submit_button' ) );

				add_action( 'admin_init', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'bulk_action_refunds' ) );

			}

			// frontend download button on my account page view-order
			if ( ! is_admin() ) {

				add_action( 'woocommerce_order_details_after_order_table',           array( 'WP_WC_Invoice_Pdf_View_Order_Download', 'make_download_button' ) );

			}

			add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_view_order_invoice_download', array( 'WP_WC_Invoice_Pdf_View_Order_Download', 'download_pdf' ) );
			add_action( 'wp_ajax_woocommerce_wp_wc_invoice_pdf_view_order_refund_download', array( 'WP_WC_Invoice_Pdf_View_Order_Download', 'download_refund_pdf' ) );

			// emails with invoice pdfs may not be sent because of validation problems
			add_filter( 'wp_mail', array( __CLASS__, 'phpmailer_validation' ) );

			// delivery time management
			if ( get_option( 'woocommerce_de_show_delivery_time_order_summary', 'on' ) == 'on' && get_option( 'woocommerce_de_show_delivery_time_invoice_pdf', 'on' ) == 'off' ) {
				add_action( 'wp_wc_invoice_pdf_start_template', array( 'WP_WC_Invoice_Pdf_Create_Pdf', 'shipping_time_management_start' ), 10, 3 );
				add_action( 'wp_wc_invoice_pdf_end_template',   array( 'WP_WC_Invoice_Pdf_Create_Pdf', 'shipping_time_management_end' ), 10, 3 );
			}

			
			if ( self::has_php_7_4_for_qr_codes() ) {
				// griocode
				$girocode = WP_WC_Invoice_Pdf_Girocode_Hooks::get_instance();

				// swiss qr invoice
				$swiss_qr_invoice = WP_WC_Invoice_Pdf_Swiss_Qr_Invoice_Hooks::get_instance();
			}
		}

		/**
		 * enqueue css file for download button design on shop order page
		 *
		 * @hook admin_enqueue_scripts
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return void
		 */
		public static function admin_styles() {
			if ( get_current_screen()->id == 'edit-shop_order' || get_current_screen()->id == 'woocommerce_page_wgm-refunds' ) { // add style only if we need it
				$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
				wp_enqueue_style( 'woocommerce_wp_wc_invoice_pdf_admin_styles', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/self/assets/css/admin.' . $min . 'css' );
			}
		}

		/**
		 * plugin activation
		 *
		 * @hook register_activation_hook
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return void
		 */
		public static function activate() {
			$vendors_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors';
			$backend_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'be';
			include_once( $backend_path . DIRECTORY_SEPARATOR . 'backend-activation.php' );
			WP_WC_Invoice_Pdf_Backend_Activation::activation();
		}

		/**
		 * plugin deactivation
		 *
		 * @hook register_deactivation_hook
		 *
		 * @since 1.0.4.1
		 *
		 * @access public
		 * @static
		 *
		 * @return void
		 */
		public static function deactivate() {
			// remove cache
			WP_WC_Invoice_Pdf_Create_Pdf::clear_cache();
			if ( is_dir( WP_WC_INVOICE_PDF_CACHE_DIR ) ) {
				rmdir( WP_WC_INVOICE_PDF_CACHE_DIR );
			}
			//WP_WC_Invoice_Pdf_Backend_Download::clear_zip_cache( false, true );
		}

		/**
		 * enqueue scripts and styles to enable media uploader for image upload on the settings page
		 *
		 * @hook admin_enqueue_scripts
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return void
		 */
		public static function media_uploader_scripts() {

			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

			if ( ( ( get_current_screen()->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) ) && isset( $_GET[ 'tab' ] ) && ( $_GET[ 'tab' ] == 'invoice-pdf' ) && isset( $_GET[ 'sub_tab' ] ) && ( $_GET[ 'sub_tab' ] == 'images' || $_GET[ 'sub_tab' ] == 'emails' || $_GET[ 'sub_tab' ] == 'my_account_page' || $_GET[ 'sub_tab' ] == 'girocode' || $_GET[ 'sub_tab' ] == 'swiss_qr_invoice' ) ) || get_current_screen()->id == 'woocommerce_page_wgm-refunds' ) {

				wp_register_script( 'wp-wc-invoice-pdf-media-uploader', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/self/assets/js/admin.' . $min . 'js', array( 'jquery' ) );
				wp_enqueue_script( 'wp-wc-invoice-pdf-media-uploader' );

			} else if ( get_current_screen()->id == 'edit-shop_order' ) {
				wp_register_script( 'wp-wc-invoice-pdf-media-uploader', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/self/assets/js/admin.' . $min . 'js', array( 'jquery' ) );
				wp_enqueue_script( 'wp-wc-invoice-pdf-media-uploader' );
			}

		}

		/**
		 * In several PHP Versions > 7.3 emails are not sent when invoice pdfs are atteched
		 * The following error can be logged: "invalid adress: setFrom()""
		 * This Filter changes the validator of phpmailer to avoid this problem
		 *
		 * @hook wp_mail
		 *
		 * @since 3.10.1
		 *
		 * @access public
		 * @static
		 *
		 * @param Array $mail_array
		 *
		 * @return Array
		 */
		public static function phpmailer_validation( $mail_array ) {
			if ( version_compare( PHP_VERSION, '7.3', '>=' ) && version_compare( get_bloginfo( 'version' ), '5.5-dev', '<' ) ) {
				global $phpmailer;
				if ( ! ( $phpmailer instanceof PHPMailer ) ) {
					require_once ABSPATH . WPINC . '/class-phpmailer.php';
					require_once ABSPATH . WPINC . '/class-smtp.php';
					$phpmailer = new PHPMailer( true );
				}
				$phpmailer::$validator = 'php';
			}

			return $mail_array;
		}

		/**
		 * Checks if PHP versions > 7.4
		 * Required for QR code libraries
		 *
		 * @since 3.14.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return Boolean
		 */
		public static function has_php_7_4_for_qr_codes() {
			return apply_filters( 'wp_wc_invoice_pdf_has_php_7_4_for_qr_codes', version_compare( PHP_VERSION, '7.4.0', '>' ) );
		}

	} // end class

} // end class exists

Woocommerce_Invoice_Pdf::get_instance();
