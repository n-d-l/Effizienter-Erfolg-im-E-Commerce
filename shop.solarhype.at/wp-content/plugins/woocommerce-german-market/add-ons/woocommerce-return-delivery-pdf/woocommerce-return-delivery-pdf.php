<?php
/* 
 * Add-on Name:	WooCommerce Return Delivery Note PDF
 * Description:	This plugin adds a Retoure PDF as an attachment to customer emails, enables backend download of the pdf and customer download on the my account page
 * Author:		MarketPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Woocommerce_Return_Delivery_Pdf' ) ) {
	
	/**
	* main class for plugin
	*
	* @class 		Woocommerce_Return_Delivery_Pdf
	* @version		1.0
	* @category	Class
	*/ 
	class Woocommerce_Return_Delivery_Pdf {
		/**
		* singleton, almost every method is static
		* @var object
		*/
		static $instance = NULL;
		
		 /**
		 * @var string
		 */
		static public $plugin_filename = __FILE__;
		
		/**
		* singleton getInstance
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook plugins_loaded
		* @return class Woocommerce_Return_Delivery_Pdf
		*/			
		public static function get_instance() {
			if ( self::$instance == NULL) {
				self::$instance = new Woocommerce_Return_Delivery_Pdf();	
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
			// define temp directory
			if ( ! defined( 'WCREAPDF_TEMP_DIR' ) ) {
				define( 'WCREAPDF_TEMP_DIR', untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-return-delivery-pdf' . DIRECTORY_SEPARATOR );
			}
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
			$class           = strtolower( $class );
			$file            = 'class-' . str_replace( '_', '-', $class ) . '.php';	
			$vendors_path    = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'vendors';
			
			if ( $class == 'fpdf' ){
				$file = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php';	
			} else if ( strpos( $class, 'wcreapdf_backend_' ) === 0 ){
				$applications_backend_path = $vendors_path . DIRECTORY_SEPARATOR . 'wcreapdf' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';	
				$file = $applications_backend_path . DIRECTORY_SEPARATOR . $file;
			} else {
				$applications_path = $vendors_path . DIRECTORY_SEPARATOR . 'wcreapdf' . DIRECTORY_SEPARATOR . 'application';			
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
			
			// add retoure pdf to customer e-mails
			add_filter( 'woocommerce_email_attachments', array( 'WCREAPDF_Email_Attachment', 'add_attachment' ), 10, 3 );	
			
			// option page
			if ( is_admin() ) {
				add_filter( 'woocommerce_de_ui_left_menu_items',						array( 'WCREAPDF_Backend_Options_WGM', 'menu' ) );
				add_action( 'woocommerce_admin_field_wcreapdf_textarea', 				array( 'WCREAPDF_Backend_Options_WGM', 'output_textarea' ) );
				add_filter( 'woocommerce_admin_settings_sanitize_option', 				array( 'WCREAPDF_Backend_Options_WGM', 'save_wcreapdf_textarea'), 10, 3 );
				add_filter( 'woocommerce_admin_settings_sanitize_option', 				array( 'WCREAPDF_Backend_Options_WGM', 'save' ), 10, 3 );

				add_action( 'admin_enqueue_scripts',									array( __CLASS__, 'media_uploader_scripts' ) );	// scripts and styles to use media uploader for image upload
				add_action( 'wp_ajax_woocommerce_wcreapdf_download_test_pdf',			array( 'WCREAPDF_Backend_Download', 'download_test_pdf' ) );
				add_action( 'wp_ajax_woocommerce_wcreapdf_download_test_pdf_delivery',	array( 'WCREAPDF_Backend_Download', 'download_test_pdf_delivery' ) );

				if ( get_option( 'wcreapdf_pdf_image_bind_error', '' ) != '' ) {
					add_filter( 'admin_notices', array( 'WCREAPDF_Backend_Download', 'show_error_message' ) );
				}
				
			}
			
			// plugin has been activated
			if ( is_admin() && get_option( 'woocomerce_wcreapdf_wgm_just_activated', false ) ){
				// create temp files
				add_action( 'admin_init', array( 'WCREAPDF_Backend_Activation', 'create_temp_directories' ) );
			}
			
			// backend download buttons
			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts',											array( __CLASS__, 'admin_styles' ) ); // style for download button
				
				if ( current_user_can( get_option( 'german_market_current_user_can_download_pdfs_in_admin', 'manage_woocommerce' ) ) ) {
					if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_backend_download' ), 'on' ) == 'on' ) {
						add_filter( 'woocommerce_admin_order_actions',									array( 'WCREAPDF_Backend_Download', 'admin_icon_download'), 10, 2 );
						add_action( 'wp_ajax_woocommerce_wcreapdf_download', 							array( 'WCREAPDF_Backend_Download', 'admin_ajax_download_pdf' ) );
						add_action( 'woocommerce_order_actions_end', 									array( 'WCREAPDF_Backend_Download', 'order_download' ) );
					}

					// Delivery Note since GM v3.2
					if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_delivery_backend_download' ), 'on' ) == 'on' ) {
						add_filter( 'woocommerce_admin_order_actions',									array( 'WCREAPDF_Backend_Download', 'admin_icon_download_delivery'), 10, 2 );
						add_action( 'wp_ajax_woocommerce_wcreapdf_download_delivery', 					array( 'WCREAPDF_Backend_Download', 'admin_ajax_download_pdf_delivery' ) );
						add_action( 'woocommerce_order_actions_end', 									array( 'WCREAPDF_Backend_Download', 'order_download_delivery' ) );
					}
				}

				// Bulk Download Actions since GM 3.5
				add_action( 'admin_init', function() {
					add_action( WGM_Hpos::get_hook_for_order_bulk_actions(), 			array( 'WCREAPDF_Backend_Download', 'add_bulk_actions' ), 10 );
					add_action(  WGM_Hpos::get_hook_for_order_handle_bulk_actions(), 	array( 'WCREAPDF_Backend_Download', 'bulk_action' ), 10, 3 );
				});
			}
			
			// frontend download button on my account page view-order 
			if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'view-order-button' ), 'off' ) == 'on' ) {
				add_action( 'woocommerce_order_details_after_order_table', 			array( 'WCREAPDF_View_Order_Download', 'make_download_button' ) );
				add_action( 'wp_ajax_woocommerce_wcreapdf_view_order_download', 	array( 'WCREAPDF_View_Order_Download', 'download_pdf' ), 10, 3 );
			}

			// dont's show prices in pdfs
			add_action( 'wcreapdf_pdf_before_create', 	array( 'WCREAPDF_Helper', 'remove_each_price' ) );
			add_action( 'wcreapdf_pdf_after_create', 	array( 'WCREAPDF_Helper', 'add_each_price' ) );

			// delivery time management
			if ( get_option( 'woocommerce_de_show_delivery_time_order_summary', 'on' ) ) {
				add_action( 'wcreapdf_pdf_before_create', 	array( 'WCREAPDF_Helper', 'shipping_time_management_start' ), 10, 3 );
				add_action( 'wcreapdf_pdf_after_create', 	array( 'WCREAPDF_Helper', 'shipping_time_management_end' ), 10, 3 );
			}

		}
		
		/**
		* enqueue scripts and styles to enable media uploader for image upload on the settings page
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook admin_enqueue_scripts
		* @return void
		*/	
		public static function media_uploader_scripts() {
			if ( get_current_screen()->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) && isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'preferences-wcreapdf' && ( ( isset( $_GET[ 'sub_tab' ] ) && $_GET[ 'sub_tab' ] == 'pdf_settings' ) || ( ! isset( $_GET[ 'sub_tab' ] ) )  || ( isset( $_GET[ 'sub_tab' ] ) && $_GET[ 'sub_tab' ] == 'pdf_settings_delivery_note' ) ) ) {
				$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
				wp_register_script( 'wcreapdf-media-uploader', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/wcreapdf/assets/js/admin.' . $min . 'js', array( 'jquery' ) );
				wp_enqueue_script( 'wcreapdf-media-uploader' );	
			}
		}
		
		/**
		* enqueue css file for download button design on shop order page
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook admin_enqueue_scripts
		* @return void
		*/				
		public static function admin_styles() {
			if ( WGM_Hpos::is_edit_shop_order_screen() ) { // add style only if we need it
				$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
				wp_enqueue_style( 'woocommerce_wcreapdf_admin_styles', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/vendors/wcreapdf/assets/css/admin.' . $min . 'css' );
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
			$backend_path	= $vendors_path . DIRECTORY_SEPARATOR . 'wcreapdf' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'backend';
			include_once( $backend_path . DIRECTORY_SEPARATOR . 'class-wcreapdf-backend-activation.php' );
			WCREAPDF_Backend_Activation::activation();
		}

		/**
		* plugin deactivation
		*
		* @since 1.0.0.9
		* @access public
		* @static
		* @hook register_deactivation_hook
		* @return void
		*/
		public static function deactivate(){
			
			// remove cache
			$cache_dir = untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-return-delivery-pdf' . DIRECTORY_SEPARATOR;
			self::delete_directories( WCREAPDF_TEMP_DIR );
		}

		/**
		* help for plugin deactivation - delete directories recursively
		*
		* @since 1.0.0.9
		* @access public
		* @static
		* @hook register_deactivation_hook
		* @return void
		*/
		private static function delete_directories( $target ) {

			if ( is_dir( $target ) ) {
		        $files = glob( $target . '*', GLOB_MARK );
		        
		        foreach ( $files as $file ) {
		            self::delete_directories( $file );
		        }
		      
		        rmdir( $target );
		    } elseif ( is_file( $target ) ) {
		        unlink( $target );
		    }

		}
					
	}

	Woocommerce_Return_Delivery_Pdf::get_instance();
}
