<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Dompdf\Dompdf;
use Dompdf\Options;

if ( ! class_exists( 'Ebs_Pdf_Wordpress' ) ) {

	// load dompdf class

	// Compatability with WooCommerce PDF Product Vouchers
	if ( function_exists( 'wc_pdf_product_vouchers' ) ) {
		
		$dompdf_loader_file = wc_pdf_product_vouchers()->get_plugin_path() . '/vendor/autoload.php';

		if ( ! is_file( $dompdf_loader_file ) ) {
			$dompdf_loader_file = wc_pdf_product_vouchers()->get_plugin_path() . '/lib/dompdf/autoload.inc.php';
		}

		if ( is_file( $dompdf_loader_file ) ) {
			require_once( $dompdf_loader_file );
			add_filter( 'wp_wc_invoice_pdf_frame_reflower_table_fix', '__return_true' );
		} else {
			
			// file of plugin PDF Product Vouchers not found
			$dompdf_lib = apply_filters( 'wp_wc_invoice_pdf_dompd_lib_path', WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . DIRECTORY_SEPARATOR . 'autoload.inc.php' );
			require_once( $dompdf_lib );
		
		}
		
	} else {

		if ( ! class_exists( 'FontLib\Autoloader' ) ) {

			$dompdf_lib = apply_filters( 'wp_wc_invoice_pdf_dompd_lib_path', WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . DIRECTORY_SEPARATOR . 'autoload.inc.php' );
			require_once( $dompdf_lib );

		} else {

			if ( apply_filters( 'german_market_invoice_pdf_fontlib_already_use_autoloader', true ) ) {
				
				add_filter( 'wp_wc_invoice_pdf_frame_reflower_table_fix', '__return_true' );
				error_log( 'German Market: The invoice pdf add on uses antoher DOMPDF library to render a pdf file.' );
			
			} else {
				
				// HMLT5 Parser
				if ( ! class_exists( 'HTML5_Parser' ) ) {
					require_once WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . '/lib/html5lib/Parser.php';
				}
				
				// Sabberworm
				spl_autoload_register(function($class)
				{
				    if (strpos($class, 'Sabberworm') !== false) {
				        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class);
				        $file = realpath(WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . '/lib/php-css-parser/lib/' . (empty($file) ? '' : DIRECTORY_SEPARATOR) . $file . '.php');
				        if (file_exists($file)) {
				            require_once $file;
				            return true;
				        }
				    }
				    return false;
				});

				// php-font-lib
				if ( ! class_exists( 'FontLib\Autoloader' ) ) {
					require_once WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . '/lib/php-font-lib/src/FontLib/Autoloader.php';
				}
				

				//php-svg-lib
				require_once WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . '/lib/php-svg-lib/src/autoload.php';


				/*
				 * New PHP 5.3.0 namespaced autoloader
				 */
				require_once WP_WC_INVOICE_PDF_DOMPDF_LIB_PATH . '/src/Autoloader.php';

				\Dompdf\Autoloader::register();
			}

		}

	}

	require_once( 'abstract-class-ebs-pdf.php' );
	
	/**
	* this class extends Ebs_Pdf to be used in wordpress
	*
	* @class 	Ebs_Pdf_Wordpress
	* @version	1.0
	* @category	Class
	* @requires class DOMPDF (abstract class Ebs_Pdf already included)
	*/ 

	class Ebs_Pdf_Wordpress extends Ebs_Pdf {
		
		/**
		* constructor, creates DOMPDF object, set prefix and user_unit
		*
		* @since 0.0.1
		* @access public
		* @arguments string $prefix
		* @overload
		* @return void
		*/		
		public function __construct( $prefix = NULL ) {

			$options = new Options();
			$options->set( 'isPhpEnabled', TRUE );

			if ( get_option( 'wp_wc_invoice_pdf_image_remote_scources', 'off' ) == 'on' ) {
				$options->set( 'isRemoteEnabled', true );
			} else {

				if ( ! empty( trim( get_option( 'wp_wc_invoice_pdf_custom_fonts', '' ) ) ) ) {
					$options->set( 'isRemoteEnabled', true );
				}
			}

			$options->set( 'chroot', WP_CONTENT_DIR );

			do_action( 'gm_invoice_pdf_ebs_pdf_set_options', $options );

			$this->pdf 						= new Dompdf( $options );
			$this->prefix = ( $prefix === NULL ) ? 'ebs_pdf_wordpress_' : $prefix;
			$this->paper_size				= get_option( $this->prefix . 'paper_size', 'A4' );
			$this->pdf->set_paper( $this->paper_size, 'portrait' );
			$this->user_unit				= get_option( $this->prefix . 'user_unit', 'cm' );
			do_action( $this->prefix . 'init_pdf' );
		}
		
		/**
		* get option
		*
		* @since 0.0.1
		* @access public
		* @arguments string $option_name, $default
		* @abstract
		* @static
		* @return string
		*/	
		public function get_option( $option_name, $default = '' ) {
			$option_value = get_option( $this->prefix . $option_name, $default );
			$option_value = apply_filters( $this->prefix . 'get_option', $option_value, $option_name, $default );
			return ( $this->needs_user_unit( $option_name, $option_value ) ) ? $option_value . $this->user_unit : $option_value;
		}
		
		/**
		* get template parts for header
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments array $args
		* @return string
		*/
		public function get_template_part_header( $args = array() ) {
			return apply_filters( $this->prefix . 'get_part_header', '', $args );
		}
		
		/**
		* get template parts for footer
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments array $args
		* @return string
		*/
		public function get_template_part_footer( $args = array() ) {
			return apply_filters( $this->prefix . 'get_part_footer', '', $args );
		}
		
		/**
		* get template parts for main
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments array $args
		* @return string
		*/
		public function get_template_part_main( $args = array() ) {
			return apply_filters( $this->prefix . 'get_part_main', '', $args );
		}
		
		/**
		* get template parts for fonts
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments array $args
		* @return string
		*/
		public function get_template_part_fonts( $args = array() ) {
			return apply_filters( $this->prefix . 'get_part_fonts', '', $args );
		}
		
		/**
		* get template parts for background
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments array $args
		* @return string
		*/
		public function get_template_part_background( $args = array() ) {
			return apply_filters( $this->prefix . 'get_part_background', '', $args );
		}
		
		/**
		* get tamplate dir (path)
		*
		* @since 0.0.1
		* @access public
		* @arguments string $template_name
		* @static
		* @abstract
		* @arguments array $args
		* @return string
		*/
		public function get_template_dir() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'templates';	
		}
	}
}
