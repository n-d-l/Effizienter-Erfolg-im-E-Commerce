<?php
/* 
 * Add-on Name:	WooCommerce Running Invoice Number
 * Description:	This plugin adds a running invoice number to your orders
 * Author:		MarketPress GmbH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! class_exists( 'Woocommerce_Running_Invoice_Number' ) ) {
	
	/**
	* main class for plugin
	*
	* @class Woocommerce_Running_Invoice_Number
	* @version 1.0
	* @category	Class
	*/ 
	class Woocommerce_Running_Invoice_Number {
		
		/**
		* singleton
		* @var object
		*/
		static $instance = NULL;
		
		 /**
		 * @var string
		 */
		static public $plugin_filename = __FILE__;
		
		static public $compatibilities = array();
		/**
		* singleton getInstance
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook plugins_loaded
		* @return class Woocommerce_Running_Invoice_Number
		*/			
		public static function get_instance() {
			if ( self::$instance == NULL) {
				self::$instance = new Woocommerce_Running_Invoice_Number();	
			}
			return self::$instance;
		}
		
		/**
		* constructor
		*
		* @since 0.0.1
		* @access private
		* @return void
		*/	
		private function __construct() {
			// auto-load classes on demand
			if ( function_exists( "__autoload" ) ) {
				spl_autoload_register( "__autoload" );
			}
			spl_autoload_register( array( $this, 'autoload' ) );
			self::init();
		}
		
		/**
		* autoload classes on demand
		*
		* @since 0.0.1
		* @access public
		* @arguments string $class (class name)
		* @return void
		*/
		public function autoload( $class ) {
			$class			= strtolower( $class );
			$file          	= 'class-' . str_replace( '_', '-', $class ) . '.php';
			$file 			= str_replace( 'class-wp-wc-running-invoice-number-', '', $file );		
			$vendors_path	= untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors';
			
			if ( strpos( $class, 'wp_wc_running_invoice_number_backend_' ) === 0 ){
				$applications_backend_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'backend';	
				$file = $applications_backend_path . DIRECTORY_SEPARATOR . $file;
			} else if ( strpos( $class, 'wp_wc_running_invoice_number_compatibilities_' ) === 0 ) {
				$compatibilities_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'compatibilities';
				$file = str_replace( 'compatibilities-', '', $file );
				$file = $compatibilities_path . DIRECTORY_SEPARATOR . $file;
			} else {
				$applications_path = $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app';			
				$file = $applications_path . DIRECTORY_SEPARATOR . $file;
			}		

			if ( $file && is_readable( $file ) ) {
				include_once( $file );
				return;
			}
		}
		
		/**
		* cloning is private
		*
		* @since 0.0.1
		*/	
		private function __clone() {}
		
		/**
		* register actions and filters
		*
		* @since 0.0.1
		* @access private
		* @static
		* @return void
		*/		
		private static function init() {	
			
			$all_options = wp_load_alloptions();
			
			WP_WC_Running_Invoice_Number_Semaphore::init();
			
			// option page
			if ( is_admin() ) {
				add_filter( 'woocommerce_de_ui_left_menu_items',								array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'menu' ) );
				add_action( 'woocommerce_admin_field_wp_wc_running_invoice_number_textarea', 	array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'output_textarea' ) );
				add_filter( 'woocommerce_admin_settings_sanitize_option', 						array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'save' ), 10, 3 );
			}
			
			// javascript for example text field, example running invoice number
			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_js' ) );
			}	
			
			// email customer invoice & order completed 
			if ( get_option( 'wp_wc_running_invoice_email_activation', 'on' ) == 'on' ) {
				
				// heading and subject for customer invoice email
				add_filter( 'woocommerce_email_heading_customer_invoice', 				array( 'WP_WC_Running_Invoice_Number_Email', 'get_heading' ), 10, 2 );					
				add_filter( 'woocommerce_email_subject_customer_invoice',		 		array( 'WP_WC_Running_Invoice_Number_Email', 'get_subject' ), 10, 2 );			
				
				// heading and subject for customer invoice email (paid)
				add_filter( 'woocommerce_email_subject_customer_invoice_paid',			array( 'WP_WC_Running_Invoice_Number_Email', 'get_subject_paid' ), 10, 2 );
				add_filter( 'woocommerce_email_heading_customer_invoice_paid', 			array( 'WP_WC_Running_Invoice_Number_Email', 'get_heading_paid' ), 10, 2 );

				// heading and subject for completed order email (since version 1.1)
				add_filter( 'woocommerce_email_heading_customer_completed_order', 		array( 'WP_WC_Running_Invoice_Number_Email', 'get_heading_completed_order' ), 10, 2 );
				add_filter( 'woocommerce_email_subject_customer_completed_order',		array( 'WP_WC_Running_Invoice_Number_Email', 'get_subject_completed_order' ), 10, 2 );

				// heading and subject for refunded order email (since WGM 3.0)
				add_action( 'woocommerce_order_fully_refunded_notification',  			array( 'WP_WC_Running_Invoice_Number_Email', 'refunded_trigger' ), 10, 2 );
				add_action( 'woocommerce_order_partially_refunded_notification',		array( 'WP_WC_Running_Invoice_Number_Email', 'refunded_trigger' ), 10, 2 );
				add_filter( 'woocommerce_email_heading_customer_refunded_order', 		array( 'WP_WC_Running_Invoice_Number_Email', 'get_heading_refunded_order' ), 10, 2 );
				add_filter( 'woocommerce_email_subject_customer_refunded_order',		array( 'WP_WC_Running_Invoice_Number_Email', 'get_subject_refunded_order' ), 10, 2 );
				
				
				if ( is_admin() ) {
					// add notices to the default setting page email customer invoice

					// @todo seems not to work any more!
					add_filter( 'woocommerce_settings_api_form_fields_customer_invoice', 	array( 'WP_WC_Running_Invoice_Number_Backend_Notices', 'add_email_notices_to_woocommerce_setting_fields' ) );
				}				
			}
			
			// invoice pdf
			if ( self::is_wp_wc_invoice_pdf_activated() ) {
				if ( is_admin() ) {

					// add notices to invoice pdf sections
					if ( get_option( 'wp_wc_running_invoice_pdf_activation', 'on' ) == 'on' ) {
						add_filter( 'wp_wc_invoice_pdf_options_section_general_pdf_settings', 	array( 'WP_WC_Running_Invoice_Number_Backend_Notices', 'add_notices_to_wp_wc_invoice_pdf_setting_fields' ) );
						add_filter( 'wp_wc_invoice_pdf_options_section_invoice_content',		array( 'WP_WC_Running_Invoice_Number_Backend_Notices', 'add_notices_to_wp_wc_invoice_pdf_setting_fields' ) );
						add_filter( 'wp_wc_invoice_pdf_options_section_refund_content',			array( 'WP_WC_Running_Invoice_Number_Backend_Notices', 'add_notices_to_wp_wc_invoice_pdf_setting_fields' ) );
					}	
				}

				// replace file names and subject in invoice pdf
				if ( get_option( 'wp_wc_running_invoice_pdf_activation', 'on' ) == 'on' ) {
					add_filter( 'wp_wc_invoice_pdf_backend_filename',		array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_backend_filename' ), 10, 2 );
					add_filter( 'wp_wc_invoice_pdf_frontend_filename',		array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_frontend_filename' ), 10, 2 );
					add_filter( 'wp_wc_invoice_pdf_subject', 				array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_subject' ), 10, 2 );
				}
				add_filter( 'wp_wc_invoice_pdf_welcome_text',				array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'extra_texts' ), 10, 2 );
				add_filter( 'wp_wc_invoice_pdf_text_after_content', 		array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'extra_texts' ), 10, 2 );
				add_filter( 'wp_wc_invoice_pdf_girocode_placeholders_text', array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'extra_texts' ), 10, 2 );
				
				// replace file names and subject lines in refund pdf
				add_filter( 'wp_wc_invoice_pdf_refund_backend_filename',	array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_backend_filename_refund' ), 10, 2 );
				add_filter( 'wp_wc_invoice_pdf_refund_frontend_filename',	array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_frontend_filename_refund' ), 10, 2 );
				add_filter( 'wp_wc_invoice_pdf_subject_line_1',				array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_subject_refund_line_1' ), 10, 2 );
				add_filter( 'wp_wc_invoice_pdf_subject_line_2',				array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_subject_refund_line_2' ), 10, 2 );

				// add invoice date in invoice pdf
				add_filter( 'wp_wc_invoice_pdf_invoice_date',		array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'get_invoice_date' ), 10, 2 );	
				
				// actions that have to be executed if a invoice number has to be created
				add_action( 'wp_wc_invoice_pdf_before_backend_download', 		array( 'WP_WC_Running_Invoice_Number_Functions', 'static_construct' ), 10, 1 );
				add_action( 'wp_wc_invoice_pdf_before_frontend_download', 		array( 'WP_WC_Running_Invoice_Number_Functions', 'static_construct' ), 10, 1 );
				add_action( 'wp_wc_invoice_pdf_before_refund_backend_download',	array( 'WP_WC_Running_Invoice_Number_Functions', 'static_construct_by_order_id' ), 10, 1 );
				
				// create invoice number if email is send that has the invoice as an attachment
				add_action( 'wp_wc_invoice_before_adding_attachment',		array( 'WP_WC_Running_Invoice_Number_Invoice_Pdf', 'before_adding_attachment' ), 10, 2 );	
			}
			
			// backend output shop_order and refunds
			if ( is_admin() ) {
				
				add_filter( 'manage_shop_order_posts_columns', 								array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'shop_order_columns' ), 20 );
				add_filter( 'manage_edit-shop_order_sortable_columns', 						array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'shop_order_sortable_columns' ) );
				add_filter( 'manage_shop_order_posts_custom_column', 						array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'render_shop_order_columns' ), 10, 2 );
				add_action( 'pre_get_posts', 												array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'shop_order_sort' ) );
				add_action( 'wp_ajax_wp_wc_running_invoice_number_ajax_backend_shop_order', array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'shop_order_ajax' ) );
				add_action( 'wp_ajax_wp_wc_running_invoice_number_show_after_invoice_pdf_clicked', 	array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'shop_order_ajax_after_invoice_pdf_clicked' ) );
				add_filter( 'woocommerce_shop_order_search_fields',							array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'search_query' ) );

				// refunds
				add_filter( 'wgm_refunds_backend_columns', 									array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'refund_columns' ) );
				add_filter( 'wgm_refunds_array',											array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'refund_item' ), 10, 3 );
				add_action( 'wp_ajax_wp_wc_running_invoice_number_show_refund_number', 		array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'ajax_show_refund_number' ) );
				add_action( 'wp_ajax_wp_wc_running_invoice_number_update_refund_number', 	array( 'WP_WC_Running_Invoice_Number_Backend_Output_Shop_Order', 'ajax_update_refund_number' ) );
			}
			
			// backend output order.php
			if ( is_admin() ) {
				add_action( 'woocommerce_admin_order_data_after_order_details',				array( 'WP_WC_Running_Invoice_Number_Backend_Output_Post', 'order_data_after_order_details' ) );
				add_action( 'wp_ajax_wp_wc_running_invoice_number_ajax_backend_post', 		array( 'WP_WC_Running_Invoice_Number_Backend_Output_Post', 'post_ajax' ) );			
				add_action( 'wp_ajax_wp_wc_running_invoice_number_delete', 					array( 'WP_WC_Running_Invoice_Number_Backend_Output_Post', 'delete_invoice_number' ) );	
				add_action( 'woocommerce_process_shop_order_meta',							array( 'WP_WC_Running_Invoice_Number_Backend_Output_Post', 'save_meta_data' ), 10, 2 );
				add_action( 'woocommerce_after_order_refund_item_name',						array( 'WP_WC_Running_Invoice_Number_Backend_Output_Post', 'show_refund_invoice_number_in_order_refunds' ) );
			}
			
			// automatically generation when an order is created
			if ( get_option( 'wp_wc_running_invoice_number_generate_when_order_is_created', 'off' ) == 'on' ) {
				add_action( 'woocommerce_new_order', array( 'WP_WC_Running_Invoice_Number_Functions', 'static_construct_by_order_id' ) );
			}

			// return delivery pdf since GM v3.2
			add_filter( 'wcreapdf_pdf_placeholders_backend_string', 						array( 'WP_WC_Running_Invoice_Number_Return_Delivery_Pdf', 'wcreapdf_pdf_placeholders_backend_string' ) );
			add_filter( 'wcreapdf_pdf_placeholders_frontend_string',						array( 'WP_WC_Running_Invoice_Number_Return_Delivery_Pdf', 'wcreapdf_pdf_placeholders_frontend_string' ), 10, 2 );

			// send invoice number to lexoffice, sevdesk, 1&1 buchhaltung @since GM 3.5.2
			add_filter( 'lexoffice_woocommerce_de_ui_render_options',						array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'lexoffice_options' ) );
			add_filter( 'sevdesk_woocommerce_de_ui_settings_after_voucher_number',			array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'sevdesk_options' ) );
			
			add_filter( 'sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_order',  array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'sevdesk_voucher_description_order' ) );
			add_filter( 'sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_refund', array( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM', 'sevdesk_voucher_description_refund' ) );

			add_filter( 'lexoffice_woocommerce_api_order_voucher_number', 					array( 'WP_WC_Running_Invoice_Number_Online_Bookkeeping', 'lexoffice_voucher_number' ), 10, 2 );
			add_filter( 'sevdesk_woocommerce_api_voucher_description',						array( 'WP_WC_Running_Invoice_Number_Online_Bookkeeping', 'sevdesk_voucher_number' ), 10, 2 );

			add_filter( 'lexoffice_woocommerce_api_order_voucher_date', 					array( 'WP_WC_Running_Invoice_Number_Online_Bookkeeping', 'lexoffice_sevdesk_voucher_date' ), 10, 2 );
			add_filter( 'sevdesk_woocommerce_api_voucher_date',								array( 'WP_WC_Running_Invoice_Number_Online_Bookkeeping', 'lexoffice_sevdesk_voucher_date' ), 10, 2 );

			// compatibilities
			
			// B2B Market
			if ( defined( 'B2B_PLUGIN_PATH' ) ) {
				$b2b_market = WP_WC_Running_Invoice_Number_Compatibilities_Plugin_B2B_Market::get_instance();
			}
		}
		
		/**
		* enqueue javascript for example text field, example running invoice number
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook admin_enqueue_scripts
		* @return void
		*/	
		public static function load_admin_js() {
			if ( ( ( ( get_current_screen()->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) ) && isset( $_GET[ 'tab' ] ) && ( $_GET[ 'tab' ] == 'preferences-wp-wc-running-invoice-number' ) ) || ( ( get_current_screen()->id == 'edit-shop_order' ) && ( self::is_wp_wc_invoice_pdf_activated() ) ) || ( get_current_screen()->id == 'shop_order' ) ) || ( get_current_screen()->id == 'woocommerce_page_wgm-refunds' ) ) {	

					$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

					wp_register_script( 'wp-wc-running-invoice-number-admin-js', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/self/assets/js/admin.' . $min . 'js', array( 'jquery' ), Woocommerce_German_Market::$version );
					wp_enqueue_script( 'wp-wc-running-invoice-number-admin-js' );
					wp_localize_script( 'wp-wc-running-invoice-number-admin-js', 'wpwcrin_ajax', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wp_wc_running_invoice_number_nonce' ) ) );
			}
		}
				
		/**
		* plugin activation
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook register_activation_hook
		* @return void
		*/
		public static function activate(){
			$vendors_path	= untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors';
			$backend_path	= $vendors_path . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'backend';
			include_once( $backend_path . DIRECTORY_SEPARATOR . 'backend-activation.php' );
			WP_WC_Running_Invoice_Number_Backend_Activation::activation();
		}
		
		/**
		* add wc option page
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook admin_enqueue_scripts
		* @return array settings
		*/	
		public static function get_settings_page( $settings ){
			$settings[] = new WP_WC_Running_Invoice_Number_Backend_Options(); // returns class WP_WC_Running_Invoice_Number_Backend_Options extends WC_Settings_Page
			return $settings;
		}
		
		/**
		* check if WooCommerce Invoice PDF is installed and activated
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return boolean
		*/
		public static function is_wp_wc_invoice_pdf_activated() {
			return ( get_option( 'wgm_add_on_woocommerce_invoice_pdf' ) == 'on' );
		}
	
	} // end class

} // end class exists


Woocommerce_Running_Invoice_Number::get_instance();
