<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Create_Pdf' ) ) {

	/**
	* pdf creation
	*
	* @WP_WC_Invoice_Pdf_Create_Pdf
	* @version 1.0.1
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Create_Pdf {

		 /**
		 * @var string
		 */
		public $cache_dir = '';

		 /**
		 * @var string
		 */
		public $filename = '';

		/**
		* constructor
		*
		* @since 0.0.1
		* @access public
		* @arguments array $args - array of settings
		* @return void
		*/
		public function __construct ( $args = array() ) {

			self::clear_cache();
			self::init();

			// debug
			if ( get_option( 'wp_wc_invoice_pdf_debug', 'off' ) == 'on' ) {
				add_filter( 'wp_wc_invoice_pdf_no_debug_in_pdf', '__return_false' );
			}

			// delivery time, stock management
			add_filter( 'woocommerce_de_do_not_show_delivery_time_if_out_of_stock', array( __CLASS__, 'delivery_time_out_of_stock', 10, 2 ) );

			$invoice = new Ebs_Pdf_Wordpress( 'wp_wc_invoice_pdf_' );
			do_action( 'wp_wc_invoice_pdf_after_new_dompdf', $invoice, $args );

			$return = apply_filters( 'wp_wc_invoice_pdf_return_before_cration', false, $args );
			if ( $return ) {
				return;
			}

			$is_test = is_string( $args[ 'order' ] ) && $args[ 'order' ] == 'test';

			// filename
			if ( ! $is_test ) {
				$filename	= ( isset( $args[ 'filename' ] ) ? $args[ 'filename' ] : __( 'invoice', 'woocommerce-german-market' ) );
				$file_name_placeholders = apply_filters( 'wp_wc_invoice_pdf_placeholders', array( 'order-number' => __( 'Order number', 'woocommerce-german-market' ) ) );
				foreach( $file_name_placeholders as $key => $value ) {
					$search[] 	= '{{' . $key . '}}';
					if ( $key == 'order-number' ) {
						$replace[] = $args[ 'order' ]->get_order_number();
					} else {
						// how to replace the custom placeholder
						$replace[] = apply_filters( 'wp_wc_invoice_pdf_placeholder_' . $key, $value, $key, $args[ 'order' ] );
					}
				}
				$filename	= str_replace( $search, $replace, $filename ) . '.pdf';
			} else {
				$filename	= isset( $args[ 'filename' ] ) ? $args[ 'filename' ] . '.pdf' : 'invoice-test.pdf';
			}
			$this->filename = $filename;

			// set paper size
			$orientation = get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );
			if ( $orientation == 'portrait' ) {
				$invoice->pdf->set_paper( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ), 'portrait' );
			} else {
				$invoice->pdf->set_paper( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ), 'landscape' );
			}

			// default css
			if ( ! isset( $args[ 'remove_css_style' ] ) ) {
				$args[ 'remove_css_style' ] = get_option( 'wp_wc_invoice_pdf_remove_css_style', false );
			}

			// inline style ( is used to position footer, header and images, it's not recommended to turn it off )
			if ( ! isset( $args[ 'inline_style' ] ) ) {
				$args[ 'inline_style' ] = get_option( 'wp_wc_invoice_pdf_inline_style', true );
			}

			// before output is generated, you could do something
			do_action( 'wp_wc_invoice_pdf_before_get_pdf_content', $args[ 'order' ], $args );

			// get html

			// digital content repetition
			if ( ( get_option( 'woocommerce_de_repeat_digital_content', 'on' ) == 'on' ) && ( get_option( 'wp_wc_invoice_pdf_show_for_digital_content', 'on' ) == 'off' ) ) {
				add_filter( 'woocommerce_de_repeat_digital_content_do_return_if_on', '__return_true' );
			} else if ( ( get_option( 'woocommerce_de_repeat_digital_content', 'on' ) == 'off' ) && ( get_option( 'wp_wc_invoice_pdf_show_for_digital_content', 'on' ) == 'on' ) ) {
				add_filter( 'woocommerce_de_repeat_digital_content_do_return_if_off', '__return_false' );
			}

			// is html saved for an completed order?
			$create_new_but_dont_save = apply_filters( 'wp_wc_invoice_pdf_create_new_but_dont_save', false, $args[ 'order' ], $args );

			if ( ! isset( $args[ 'refund' ] ) ) {

				// for invoices
				$saved_html = false;

				if ( ! $is_test && ! has_filter( 'wp_wc_invoice_pdf_template_invoice_content' ) ) {

					$always_create_new_pdf_status = apply_filters( 'wp_wc_invoice_pdf_always_create_new_pdf_status', array( 'pending', 'processing', 'on-hold' ) );

					if ( in_array( $args[ 'order' ]->get_status(), $always_create_new_pdf_status ) ) {
						delete_post_meta( $args[ 'order' ]->get_id(), '_wp_wc_invoice_pdf_saved_html' );
					} else {
						$maybe_saved_html = get_post_meta( $args[ 'order' ]->get_id(), '_wp_wc_invoice_pdf_saved_html', true );

						if ( trim( $maybe_saved_html ) != '' ) {

							// Dompdf Legacy: Avoid Fatal Error
							if ( ! ( strpos( 'Font_Metrics::get_font', $maybe_saved_html ) === false ) ) {
								$maybe_saved_html = '';
							}  else {
								$saved_html = true;
							}

						}
					}

				}

				if ( $saved_html && ! $create_new_but_dont_save ) {

					do_action( 'wp_wc_invoice_pdf_before_get_template_page_numbers', $args );

					$page_numbers_text = get_option( "wp_wc_invoice_pdf_page_numbers_text", __( "Page {{current_page_number}} of {{total_page_number}}", "woocommerce-german-market" ) );
					$GLOBALS[ 'page_numbers_text' ] = $page_numbers_text;

					do_action( 'wp_wc_invoice_pdf_after_get_template_page_numbers', $args );

					$html = $maybe_saved_html;
				} else {

					if ( apply_filters( 'wp_wc_invoice_pdf_no_debug_in_pdf', true ) ) {
						$html = @$invoice->get_pdf_content( 'default', $args );
					} else {
						$html = $invoice->get_pdf_content( 'default', $args );
					}

					if ( ! $is_test ) {
						if ( ! has_filter( 'wp_wc_invoice_pdf_template_invoice_content' ) ) {
							if ( ! $create_new_but_dont_save ) {

 								if ( ! in_array( $args[ 'order' ]->get_status(), $always_create_new_pdf_status ) ) {
 									update_post_meta( $args[ 'order' ]->get_id(), '_wp_wc_invoice_pdf_saved_html', $html );
 								}

 							}
						}
					}

				}

			} else {

				// for refunds
				$maybe_saved_html = get_post_meta( $args[ 'refund' ]->get_id(), '_wp_wc_invoice_pdf_saved_html', true );

				// Dompdf Legacy: Avoid Fatal Error
				if ( ( trim( $maybe_saved_html ) != '' ) && ( ! ( strpos( 'Font_Metrics::get_font', $maybe_saved_html ) === false ) ) ) {
					$maybe_saved_html = '';
				}

				if ( $maybe_saved_html != '' && ( ! $create_new_but_dont_save ) ) {

					do_action( 'wp_wc_invoice_pdf_before_get_template_page_numbers', $args );

					$page_numbers_text = get_option( "wp_wc_invoice_pdf_page_numbers_text", __( "Page {{current_page_number}} of {{total_page_number}}", "woocommerce-german-market" ) );
					$GLOBALS[ 'page_numbers_text' ] = $page_numbers_text;

					do_action( 'wp_wc_invoice_pdf_after_get_template_page_numbers', $args );

					$html = $maybe_saved_html;
				} else {

					if ( apply_filters( 'wp_wc_invoice_pdf_no_debug_in_pdf', true ) ) {
						$html = @$invoice->get_pdf_content( 'default', $args );
					} else {
						$html = $invoice->get_pdf_content( 'default', $args );
					}

					if ( ! $create_new_but_dont_save ) {
 						update_post_meta( $args[ 'refund' ]->get_id(), '_wp_wc_invoice_pdf_saved_html', $html );
 					}

				}

			}

			// set this to 'html' to avoid rendering and to echo html ( useful for testing )
			$args[ 'output_format' ] = apply_filters( 'wp_wc_invoice_pdf_output_format', '' );

			// modify all possible args
			$args = apply_filters( 'wp_wc_invoice_pdf_args_before_pdf_rendering', $args );

			// repair html (since 3.5.5.)
			$html = str_replace( array( '&#x200e;', '#x200f;' ), '', $html ); // added in WC 3.3., not supported by dompdf

			// filter for html
			$html = apply_filters( 'wp_wc_invoice_pdf_html_before_rendering', $html, $args );

			// digital content repetition
			if ( ( get_option( 'woocommerce_de_repeat_digital_content', 'on' ) == 'on' ) && ( get_option( 'wp_wc_invoice_pdf_show_for_digital_content', 'on' ) == 'off' ) ) {
				remove_filter( 'woocommerce_de_repeat_digital_content_do_return_if_on', '__return_true' );
			} else if ( ( get_option( 'woocommerce_de_repeat_digital_content', 'on' ) == 'off' ) && ( get_option( 'wp_wc_invoice_pdf_show_for_digital_content', 'on' ) == 'on' ) ) {
				remove_filter( 'woocommerce_de_repeat_digital_content_do_return_if_off', '__return_false' );
			}

			// delivery time, stock management
			remove_filter( 'woocommerce_de_do_not_show_delivery_time_if_out_of_stock', array( __CLASS__, 'delivery_time_out_of_stock', 10, 2 ) );

			// Commpatibiltiy for plugins that load dompdf lib
			// see https://github.com/barryvdh/laravel-dompdf/issues/389
			$html = preg_replace( '/>\s+</', '><', $html );

			// echo html
			if ( ( ( isset( $args[ 'output_format' ] ) ) && ( $args[ 'output_format' ] == 'html' ) ) || ( WP_WC_Invoice_Pdf_Create_Pdf::use_html_output( $args ) == 'yes'  ) ) {

				// make html pretty with php's tidy class
				if ( class_exists( 'tidy' ) ) {
					$tidy = new tidy();
					$options = array(
								'indent'			=> true,
								'indent-attributes' => false,
								'indent-spaces' 	=> 6,
								'wrap' 				=> 0,
								'break-before-br' 	=> true
								);
					$tidy->parseString( $html, $options );
					$tidy->cleanRepair();
					$html = $tidy;
				}
				echo $html;

			// render to pdf
			} else {

				// load html
				if ( apply_filters( 'wp_wc_invoice_pdf_no_debug_in_pdf', true ) ) {
					@$invoice->pdf->load_html( $html );
					@$invoice->pdf->render();
				} else {
					$invoice->pdf->load_html( $html );
					$invoice->pdf->render();
				}

				$invoice = apply_filters( 'wp_wc_invoice_pdf_before_pdf_generation_invoice_var', $invoice, $html, $args );
				do_action( 'wp_wc_invoice_pdf_before_pdf_generation', $invoice, $html, $args );

				if ( ( isset( $args[ 'output' ] ) ) && ( $args[ 'output' ] == 'inline' ) ) {

					// show pdf inline in browser
					$invoice->pdf->stream( $filename, array( 'Attachment' => 0 ) );
					exit();

				} else if ( ( isset( $args[ 'output' ] ) ) && ( $args[ 'output' ] == 'cache' ) ) {

					// save pdf in cache directory and return directory and filename
					$directory_name	= time() . "_" . rand( 1, 99999 ) . '_' . md5( rand( 1, 99999 ) . 'wp_wc_invoice_pdf' ) . md5( 'woocommerce-invoice-pdf' . rand( 0, 99999 ) );

					// fallback for local test systems
					if ( $directory_name == '' ) {
						$directory_name = 'local_temp_dir';
					}

					wp_mkdir_p( WP_WC_INVOICE_PDF_CACHE_DIR . $directory_name );

					if ( apply_filters( 'wp_wc_invoice_pdf_no_debug_in_pdf', true ) ) {
						$file = @$invoice->pdf->output();
					} else {
						$file = $invoice->pdf->output();
					}

					file_put_contents( WP_WC_INVOICE_PDF_CACHE_DIR . $directory_name . DIRECTORY_SEPARATOR . $filename, $file );

					$this->cache_dir	= $directory_name;
				} else if ( ( isset( $args[ 'output' ] ) ) && ( $args[ 'output' ] == 'cache-zip' ) ) {

					$directory_name = untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf-zip';
					wp_mkdir_p( $directory_name );
					$file = $invoice->pdf->output();
					file_put_contents( $directory_name . DIRECTORY_SEPARATOR . $filename, $file );
					$this->cache_dir	= $directory_name;

				} else if ( isset( $args[ 'output' ] ) && has_action( 'wp_wc_invoice_pdf_' . $args[ 'output' ] ) ) {

					do_action( 'wp_wc_invoice_pdf_' . $args[ 'output' ], $invoice, $filename );

				} else {

					// browser download
					if ( apply_filters( 'wp_wc_invoice_pdf_no_debug_in_pdf', true ) ) {
						@$invoice->pdf->stream( $filename );
					} else {
						$invoice->pdf->stream( $filename );
					}

					exit();

				}
			}
		}

		/**
		* get html as string for test html ( uesed in custom-css-styles to show it to user )
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return string html
		*/
		public static function get_test_html( $remove_css_style = false, $inline_style = true) {

			self::init();

			$invoice	= new Ebs_Pdf_Wordpress( 'wp_wc_invoice_pdf_' );
			$invoice->pdf->set_paper( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) );

			$args 		= array(
								'order'				=> 'test',
								'output_format' 	=> 'return_html',
								'remove_css_style'	=> $remove_css_style,
								'inline_style'		=> $inline_style
							);

			// modify all possible args
			$args = apply_filters( 'wp_wc_invoice_pdf_args_before_pdf_rendering', $args );

			ob_start();
			echo @$invoice->get_pdf_content( 'default', $args );
			$html = preg_replace( '#(<script.*?>)(.*)(</script>)#si', '', ob_get_clean() );

			// make html pretty with php's tidy class
			if ( class_exists( 'tidy' ) ) {
				$tidy = new tidy();
				$options = array(
							'indent'			=> true,
							'indent-attributes' => false,
							'indent-spaces' 	=> 6,
							'wrap' 				=> 0,
							'break-before-br' 	=> true
							);
				$tidy->parseString( $html, $options );
				$tidy->cleanRepair();
				$html = $tidy;
			}
			return $html;
		}

		/**
		* add filters and hooks
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_option
		* @return void
		*/
		public static function init() {
			add_filter( 'wp_wc_invoice_pdf_get_option',				array( __CLASS__, 'modify_get_option' ), 10, 3 );
			add_filter( 'wp_wc_invoice_pdf_get_part_footer', 		array( __CLASS__, 'get_footer' ), 1, 2 );
			add_filter( 'wp_wc_invoice_pdf_get_part_header', 		array( __CLASS__, 'get_header' ), 1, 2 );
			add_filter( 'wp_wc_invoice_pdf_get_part_background', 	array( __CLASS__, 'get_background' ), 1, 2 );
			add_filter( 'wp_wc_invoice_pdf_get_part_fonts',			array( __CLASS__, 'get_fonts' ), 1, 2 );
			add_filter( 'wp_wc_invoice_pdf_get_part_main',			array( __CLASS__, 'get_main' ), 1, 2 );
			$gateways = WC_Payment_Gateways::instance();; // add actions 'woocommerce_email_before_order_table' UPDATE 1.0.2 -> actions are loaded!
		}

		/**
		* returns header content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_part_header
		* @arguments string $content, $args - we do not use it
		* @return string
		*/
		public static function get_header( $content, $args = array() ) {
			return self::get_footer_or_header( 'header', $args );
		}

		/**
		* returns footer content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_part_footer
		* @arguments string $content, $args - we do not use it
		* @return string
		*/
		public static function get_footer( $content, $args = array() ) {
			return self::get_footer_or_header( 'footer', $args );
		}

		/**
		* returns footer or header content, we use the same function to create the content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments string $part ('header' or 'footer'), $args
		* @return string
		*/
		public static function get_footer_or_header( $part, $args = array() ) {
			// pre-filter
			if ( has_filter( 'wp_wc_invoice_get_footer_or_header' ) ) {
				return apply_filters( 'wp_wc_invoice_get_footer_or_header', '', $part, $args );
			}

			// check if $part is set
			if ( ! in_array( $part, array( 'header', 'footer' ) ) ){
				return '';
			}

			// init return value
			$content = '';

			// load options only once
			$user_unit 			= get_option( 'wp_wc_invoice_pdf_user_unit', 'cm' );
			$number_of_columns	= get_option( 'wp_wc_invoice_pdf_' . $part . '_number_of_columns', 1 );
			$last_column_width	= get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $number_of_columns .'_width' );
			$inline_style		= ( isset( $args[ 'inline_style' ] ) ) ? $args[ 'inline_style' ] : true;
			$paper_size			= get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' );
			$orientation 		= get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );

			// table width = width of header / footer content ( without paddings )
			// now we calculate max-width of the table (if box-sizing: border-box; would be rendered, we could go without that)
			if ( $orientation == 'portrait' ) {

				if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
					$table_width_css = 21.0;
				} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {				// case A4 in inches
					$table_width_css = 8.2677;
				} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {			// case letter size in in
					$table_width_css = 8.5;
				} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {			// case letter size in cm
					$table_width_css = 21.59;
				}

			} else {

				if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
					$table_width_css = 29.7;
				} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {				// case A4 in inches
					$table_width_css = 11.6929;
				} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {			// case letter size in in
					$table_width_css = 11;
				} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {			// case letter size in cm
					$table_width_css = 27.94;
				}

			}

			// we need this value for including the image, too
			$table_width_css_helper = $table_width_css - self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_left', 0 ) ) - self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_right', 0 ) );
			$table_width_css_helper.= $user_unit;

			if ( $last_column_width != 0 && $last_column_width != '' ) {				// we don't need table width in that case
				$table_width_css = '';
			} else {
				$table_width_css = ' width:' . $table_width_css_helper . ';';
			}

			// table height, use it in table, tr and td, otherwise we might get other rendering results as expected
			$table_height_css = self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_height', 0 ) );
			$table_height_css-= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_top', 0 ) ) + self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_bottom', 0 ) );
			$table_height_css = max( array( 0, $table_height_css ) ); // kind of error handling
			$table_height_css.= $user_unit;

			// images in header and footer
			$image = self::include_background_image( $part, $table_height_css, $table_width_css_helper, $args );
			if ( $image !== false ) {
				$content.= $image;
			}

			// start table
			if ( $inline_style ) {
				// inline styles that are necessary to render html footer to pdf as best as possible
				$content.= '<table class="' . $part . '-table" cellpadding="0" cellspacing="0" border="0" style="height: ' . $table_height_css . '; overflow: hidden;' . $table_width_css . '">';
				$content.= '<tr style="' . $table_width_css . ' height: ' . $table_height_css . '; overflow: hidden;">';
			} else {
				// keep it simple if option inline_style is deactivated
				$content.= '<table class="' . $part . '-table">';
				$content.= '<tr>';
			}

			for ( $i = 1; $i <= get_option( 'wp_wc_invoice_pdf_' . $part . '_number_of_columns', 1 ); $i++ ) {

				$style = '';

				if ( $inline_style ) { // add default styles and styles saved in backend
					// build style
					$style = ' style="height: ' . $table_height_css . '; overflow: hidden;' ;

					// font, font-size, text-align, vertical-align
					$style .= ' font-family: \'' . get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_font', 'Helvetica' ) . '\';';
					$style .= ' color: ' . get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_color', '#000' ) . ';';
					$style .= ' font-size: ' . get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_font_size', '10' ) . 'pt;';
					$style .= ' text-align: ' . get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_horizontal_text_alignment', 'left' ) . ';';
					$style .= ' vertical-align: ' . get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_vertical_text_alignment', 'text-top' ) . ';';

					// font styles: bold, italic, underline
					$font_style = get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_font_style', 'text-top' );

					// bold
					if ( str_replace( 'bold', '', $font_style ) != $font_style ) {
						$style .= ' font-weight: bold;';
					}

					// italic
					if ( str_replace( 'italic', '', $font_style ) != $font_style ) {
						$style .= ' font-style: italic;';
					}

					// underline
					if ( str_replace( 'underline', '', $font_style ) != $font_style ) {
						$style .= ' text-decoration: underline;';
					}

					// width
					$backend_saved_width = get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_width' );
					if ( $backend_saved_width != 0 && $backend_saved_width != '' ) {
						$style .= ' width: ' . self::convert_to_css_numeric( $backend_saved_width ) . $user_unit . ';';
					}

					// end style
					$style		.= '"';
				}

				// outout one column
				$content.= '<td' . $style . '>';
				$content.= nl2br( apply_filters( 'wp_wc_invoice_pdf_header_footer_content', get_option( 'wp_wc_invoice_pdf_' . $part . '_column_' . $i . '_text', '' ), $args ) );
				$content.= '</td>';
			}

			// close table
			$content.= '</tr>';
			$content.= '</table>';

			return $content;
		}

		/**
		* creates the string that contains the background image that is positioned as entered in the settings
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments $part (header or footer), string $height, string $width, array $args
		* @return string
		*/
		public static function include_background_image( $part, $height = NULL, $width = NULL, $args = array() ) {

			// pre-filter
			if ( has_filter( 'wp_wc_invoice_include_background_image' ) ) {
				return apply_filters( 'wp_wc_invoice_include_background_image', '', $part, $height, $width, $args );
			}

			// check if we have to inlcude an image
			$file			= get_option( 'wp_wc_invoice_pdf_image_url_' . $part );

			if ( $file == '' ) {
				return false;
			} else {
				$inline_style	= ( isset( $args[ 'inline_style' ] ) ) ? $args[ 'inline_style' ] : true;

				// if 'allow_url_fopen' is disabeld, we need to get the rel. url to the image
				$html_output = WP_WC_Invoice_Pdf_Create_Pdf::use_html_output( $args );

				// build absolute server path to image (rebuild in WGM 3.0.1)
				if ( $html_output == 'no' ) { // for html debugging => outout url

					$is_remote = false;

					if ( get_option( 'wp_wc_invoice_pdf_image_remote_scources', 'off' ) == 'on' ) {
						$is_remote = true;
					} else {

						if ( ! empty( trim( get_option( 'wp_wc_invoice_pdf_custom_fonts', '' ) ) ) ) {
							$is_remote = true;
						}
					}

					if ( ! $is_remote ) {

						$path_array 		= wp_upload_dir();
						$path				= untrailingslashit( ( $path_array[ 'basedir' ] ) );						// wp upload path
						$url				= untrailingslashit( ( $path_array[ 'baseurl' ] ) );						// wp upload url
						$current_dir		= getcwd();

						$file = str_replace( array( 'http://', 'https://' ), '', $file );
						$url  = str_replace( array( 'http://', 'https://' ), '', $url );

						$sub_dir_and_file	= str_replace( $url, '', $file );										// replace wp upload url from image url, will always start with a '/'
						$file_path =  $path . $sub_dir_and_file;

						// little fallback
						if ( is_file( $file_path ) ) {
							$file = $file_path;
						} else {
							$file_parts = explode( '/uploads', $file );
							if ( is_array( $file_parts ) && isset( $file_parts[ 1 ]  ) ) {
								$file_path = $path . $file_parts[ 1 ];

								if ( is_file( $file_path ) ) {
									$file = $file_path;
									
								}
							}

						}
					}

				}

				$user_unit 	= get_option( 'wp_wc_invoice_pdf_user_unit', 'cm' );

				// first build div
				if ( $inline_style ) {
					$div_style = 'position: fixed; z-index: -1; overflow: hidden; height: ' . $height . '; width:' . $width . '; content: "";';

					if( $part == 'background' ) {
						$div_style	.= ' top: 0;';
						$div_style	.= ' left: 0;';
					} else if ( $part == 'header' ) {
						$div_style	.= ' top: ' . self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_top', 0 ) ) . $user_unit . ';';
						$div_style	.= ' left: ' . self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_left', 0 ) ) . $user_unit . ';';
					} else {	 // footer
						$div_style	.= ' bottom: ' . self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_bottom', 0 ) ) . $user_unit . ';';
						$div_style	.= ' left: ' . self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_left', 0 ) ) . $user_unit . ';';
					}
					$div = '<div class="' . $part . '-div-image" style="' . $div_style . '">[table]</div>';
				} else {
					$div = '<div class="' . $part . '-div-image">[table]</div>';
				}

				// now we insert a table to allow vertical-alignment
				$position		= get_option( 'wp_wc_invoice_pdf_image_position_' . $part );
				$positions		= explode( '_', $position );
				$table_style 	= 'height: ' . $height . '; width: ' . $width . '; text-align: ' . $positions[1] . '; vertical-align: ' . $positions[0] . ';';
				if ( $inline_style ) {
					$table	= '<table border="0" cellpadding="0" cellspacing="0" style="' . $table_style . '"><tr style="' . $table_style . '"><td style="' . $table_style . '">[image]</td></tr></table>';
				} else {
					$table	= '<table><tr><td>[image]</td></tr></table>';
				}
				$div			= str_replace( '[table]', $table, $div );

				// at last we build the image tag
				$url		= 'url("' . $file . '")';
				if ( $inline_style ) {
					$original_height = $height;
					$height		= get_option( 'wp_wc_invoice_pdf_image_height_' . $part );
					$width		= get_option( 'wp_wc_invoice_pdf_image_width_' . $part );
					if ( in_array( $height, array( 0, '' ) ) && in_array( $width, array( 0, '' ) ) ) {
						$width	= 'auto';
						$height = '100%';

						if ( 'background' === $part ) {

							$paper_size			= get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' );
							$orientation 		= get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );
							
							if ( $orientation == 'portrait' ) {
								if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
									$height = 29.7;
								} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {				// case A4 in inches
									$height = 11.6929;
								} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {			// case letter size in in
									$height = 11;
								} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {			// case letter size in cm
									$height = 27.94;
								}

							} else {
								if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
									$height= 21.0;
								} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {				// case A4 in inches
									$height = 8.2677;
								} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {			// case letter size in in
									$height = 8.5;
								} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {			// case letter size in cm
									$height = 21.59;
								}
							}

							$height .= $user_unit;
						} else {
							$height = self::convert_to_css_numeric( $original_height ) . $user_unit;
						}

					} else if ( ( ! in_array( $height, array( 0, '' ) ) ) && in_array( $width, array( 0, '' ) ) ) {
						$height = self::convert_to_css_numeric( $height ) . $user_unit;
						$width  = 'auto';
					} else if ( in_array( $height, array( 0, '' ) ) && ( ! in_array( $width, array( 0, '' ) ) ) ) {
						$width	= self::convert_to_css_numeric( $width ) . $user_unit;
						$height	= 'auto';
					} else {
						$width	= self::convert_to_css_numeric( $width ) . $user_unit;
						$height = self::convert_to_css_numeric( $height ) . $user_unit;
					}
					$img = '<img src="' . $file . '" style="height: ' . $height . '; width: ' . $width . ';"/>';
				} else {
					$img = '<img src="' . $file . '"/>';
				}
				// return div containing a table containing the image
				return apply_filters( 'wp_wc_invoice_include_background_image_return_value', str_replace( '[image]', $img, $div ), $img, $div );
			}
		}

		/**
		* returns background content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_part_background
		* @arguments string $content - we do not use it, array $args
		* @return string
		*/
		public static function get_background( $content, $args = array() ) {

			$orientation = get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );

			if ( $orientation == 'portrait' ) {
				$height	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '29.7cm' : '11in';
				$width	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '21cm' : '8.5in';
			} else {
				$height	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '21cm' : '8.5in';
				$width	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '29.7cm' : '11in';
			}

			return self::include_background_image( 'background', $height, $width, $args );
		}

		/**
		* returns font content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_part_fonts
		* @arguments string $content- we do not use it, array $args
		* @return string
		*/
		public static function get_fonts( $content, $args = array() ) {
			// pre-filter
			if ( has_filter( 'wp_wc_invoice_get_fonts' ) ) {
				return apply_filters( 'wp_wc_invoice_get_fonts', '', $args );
			}
			return get_option( 'wp_wc_invoice_pdf_custom_fonts' );
		}

		/**
		* returns invoice content
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_part_main
		* @arguments string $content- we do not use it, array $args
		* @return string
		*/
		public static function get_main( $content, $args = array() ) {
			if ( ! isset( $args[ 'order' ] ) ) {
				return '';
			}

			ob_start();

			do_action( 'wp_wc_invoice_pdf_before_get_template_page_numbers', $args );

			// page numbers
			$page_numbers_text = get_option( "wp_wc_invoice_pdf_page_numbers_text", __( "Page {{current_page_number}} of {{total_page_number}}", "woocommerce-german-market" ) );
			$GLOBALS[ 'page_numbers_text' ] = $page_numbers_text;

			$page_numbers_core_template = untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'invoice-content-page-numbers.php';
			include( apply_filters( 'wp_wc_invoice_pdf_template_page_numbers', $page_numbers_core_template ) );

			//do_action( 'wp_wc_invoice_pdf_after_get_template_page_numbers', $args );

			// whole content of invoice, first check if filter or theme overrides that template
			$theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'invoice-content.php';
			if ( has_filter( 'wp_wc_invoice_pdf_template_invoice_content' ) ) {
				include apply_filters( 'wp_wc_invoice_pdf_template_invoice_content', $theme_template_file );
			} else if ( file_exists( $theme_template_file ) ) {
				include( $theme_template_file );
			} else {
				include( untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'invoice-content.php' );
			}
			return ob_get_clean();
		}

		/**
		* returns default css styles
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return string
		*/
		public static function get_default_styles() {
			ob_start();
			$theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'invoice-default-styles.php';
			if ( has_filter( 'wp_wc_invoice_pdf_template_default_styles' ) ) {
				include apply_filters( 'wp_wc_invoice_pdf_template_default_styles', $theme_template_file );
			} elseif ( file_exists( $theme_template_file ) ) {
				include( $theme_template_file );
			} else {
				include( untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'invoice-default-styles.php' );
			}
			$default_styles = ob_get_clean();
			$default_styles = str_replace( array( '<style>', '</style>' ), '', $default_styles );
			$default_styles = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $default_styles );
			return $default_styles;
		}

		/**
		* modify css property
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments $value ( checked to be numeric, maybe entered with , instead of . )
		* @return float
		*/
		public static function convert_to_css_numeric( $value ) {
			return floatval( str_replace( ',', '.', $value ) );
		}

		/**
		* get relative path from two absolute paths
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments string $from, $to (the absolute paths)
		* @return string
		*/
		public static function get_relative_path( $from, $to ){
			// some compatibility fixes for Windows paths
			$from = is_dir( $from ) ? rtrim( $from, '\/' ) . '/' : $from;
			$to   = is_dir( $to )   ? rtrim( $to, '\/' ) . '/'   : $to;
			$from = str_replace( '\\', '/', $from );
			$to   = str_replace( '\\', '/', $to );

			$from     = explode( '/', $from );
			$to       = explode( '/', $to );
			$relPath  = $to;

			foreach ( $from as $depth => $dir ) {
				// find first non-matching dir
				if ( $dir === $to[ $depth ] ) {
					// ignore this directory
					array_shift( $relPath );
				} else {
					// get number of remaining dirs to $from
					$remaining = count( $from ) - $depth;
					if ( $remaining > 1 ) {
						// add traversals up to first matching dir
						$padLength = ( count( $relPath ) + $remaining - 1 ) * -1;
						$relPath = array_pad( $relPath, $padLength, '..' );
						break;
					} else {
						$relPath[ 0 ] = './' . $relPath[ 0 ];
					}
				}
			}
			return implode( DIRECTORY_SEPARATOR, $relPath);
		}

		/**
		* modify option before returned to caller function
		*
		* @since 0.0.1
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_get_option
		* @arguments $option_value, $option_name, $default
		* @return string
		*/
		public static function modify_get_option( $option_value, $option_name, $default ) {

			// font size
			if ( str_replace( 'font_size', '', $option_name ) != $option_name ) {
				$option_value = str_replace( ',', '.', $option_value );
				if ( is_numeric ( $option_value ) ) {
					return $option_value . 'pt';
				}
			}

			// background
			if ( in_array( $option_name, array( 'body_background', 'footer_background', 'header_background', 'background_color_background' ) ) ) {
				if ( $option_name == 'background_color_background' ) { // otherwise we have problem with str_replace
					$background_color = get_option( 'wp_wc_invoice_pdf_background_color_background', '' );
				} else {
					$part = str_replace( '_background', '', $option_name );
					$background_color = get_option( 'wp_wc_invoice_pdf_' . $part . '_background_color', '' );
				}
				return ( $background_color == '' || $background_color == '#fff' || $background_color == '#ffffff' || $background_color == '#FFF' || $background_color == '#FFFFFF') ? 'none' : $background_color;

			// footer height and header height
			} else if ( in_array( $option_name, array( 'footer_height', 'header_height' ) ) ) {

				// in backend we say the spaces are margins, but actually they are css paddings
				$part = str_replace( '_height', '', $option_name );
				$margin_top		= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_top', 0 ) );
				$margin_bottom	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_padding_bottom', 0 ) );
				$option_value	= self::convert_to_css_numeric( $option_value );
				return ( $option_value - $margin_top - $margin_bottom );

			} else if ( in_array( $option_name, array( 'footer_width', 'header_width', 'body_width' ) ) ) {

				$part = str_replace( '_width', '', $option_name );
				$paper_size			= get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' );
				$user_unit 			= get_option( 'wp_wc_invoice_pdf_user_unit', 'cm' );
				$orientation 		= get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' );

				if ( $orientation == 'portrait' ) {

					if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
						$option_value = 21.0;
					} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {			// curious case A4 in inches
						$option_value = 8.2677;
					} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {		// default case letter size in in
						$option_value = 8.5;
					} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {		// curious case letter size in cm
						$option_value = 21.59;
					}

				} else { // landscape

					if ( $paper_size == 'A4' && $user_unit == 'cm' ) {					// default case A4 in cm
						$option_value = 29.7;
					} else if ( $paper_size == 'A4' && $user_unit == 'in' ) {			// case A4 in inches
						$option_value = 11.6929;
					} else if ( $paper_size == 'letter' && $user_unit == 'in' ) {		// case letter size in in
						$option_value = 11;
					} else if ( $paper_size == 'letter' && $user_unit == 'cm' ) {		// case letter size in cm
						$option_value = 27.94;
					}

				}

				$padding_or_margin = ( $option_name == 'body_width' ) ? 'margin' : 'padding';
				$margin_left	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_' . $padding_or_margin . '_left', 0 ) );
				$margin_right	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_' . $part . '_' . $padding_or_margin . '_right', 0 ) );

				return ( $option_value - $margin_left - $margin_right );

			// add height of header to body padding top
			} else if ( $option_name == 'body_margin_top' ) {
				$header_height 	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_header_height' ) );
				$option_value	= self::convert_to_css_numeric( $option_value );
				return $header_height + $option_value;

			// add height of footer to body padding top
			} else if ( $option_name == 'body_margin_bottom' ) {
				$footer_height 	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_footer_height' ) );
				$option_value	= self::convert_to_css_numeric( $option_value );
				return $footer_height + $option_value;

			// validate css numeric values
			} else if ( str_replace( array( 'padding', 'margin' ), '', $option_name ) != $option_name ) {
				return self::convert_to_css_numeric( $option_value );

			// custom css
			} else if ( $option_name == 'custom_css' ) {
				if ( ! get_option( 'wp_wc_invoice_pdf_remove_css_style', false ) ) {
					return self::get_default_styles() . $option_value;
				}

			// default font weight for bold
			} else if ( in_array( $option_name, array( 'h1_font_weight', 'h2_font_weight', 'h3_font_weight', 'h4_font_weight', 'h5_font_weight', 'h6_font_weight' ) ) ) {
				return get_option( 'wp_wc_invoice_pdf_default_font_weight_bold', 'bold' );
			}

			return $option_value;
		}

		/**
		* we cannot delete our pdf immediately because the generation of pdf and sending it via mail don't happen
		* simultaneously because we are just hooked into the mail sending process
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/
		public static function clear_cache() {
			$cache_dir 		= WP_WC_INVOICE_PDF_CACHE_DIR;
			if ( ! is_dir( $cache_dir ) ) {
				return;
			}
			$cache_dir_tree	= scandir( $cache_dir );
			foreach ( $cache_dir_tree as $dir ) {
				$cache_dir	= explode( "-", $dir, 1 );
				$timestamp	= intval( $cache_dir[ 0 ] );
				if ( $timestamp > 0 ) {
					if ( ( time() - $timestamp ) < apply_filters( 'wp_wc_invoice_pdf_clear_cache_time', 10 ) ) {
						continue;
					}
					$clear_dir	= WP_WC_INVOICE_PDF_CACHE_DIR . $dir . DIRECTORY_SEPARATOR;
					$files = array_diff( scandir( $clear_dir ), array( '.', '..' ) );
					foreach ( $files as $file ) {
						unlink( $clear_dir . $file );
					}
					rmdir( $clear_dir );
				}
			}
		}

		/**
		* Show delivery time in any case
		*
		* @since 3.8.2
		* @access public
		* @param Boolean $boolean
		* @param WC_Product $product
		* @return Boolean
		*/
		public static function delivery_time_out_of_stock( $boolean, $product ) {
			return false;
		}

		/**
		* Don't show delivery time in invoice pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook wp_wc_invoice_pdf_start_template
		* @return void
		*/
		public static function shipping_time_management_start() {
			add_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
		}

		/**
		* Don't show delivery time in invoice pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook shipping_time_management_end
		* @return void
		*/
		public static function shipping_time_management_end() {
			remove_filter( 'wgm_shipping_time_product_string', array( __CLASS__, 'remove_delivery_time_in_pdf' ), 10, 3 );
		}

		/**
		* Don't show delivery time in invoice pdfs
		*
		* @since 3.10.2
		* @access public
		* @static
		* @wp-hook wgm_shipping_time_product_string
		* @param String $shipping_time_output
		* @param String shipping_time
		* @param WC_Order_Item $item
		* @return String
		*/
		public static function remove_delivery_time_in_pdf( $shipping_time_output, $shipping_time, $item ) {
			return '';
		}

		/**
		* Check if we can output html or pdf
		*
		* @since 3.12.6
		* @access public
		* @static
		* @param Array $args
		* @return String
		*/
		public static function use_html_output( $args = array() ) {

			if ( is_array( $args ) && isset( $args[ 'output'] ) && 'cache' === $args[ 'output' ] ) {
				$use_html_output = 'no';
			} else if ( is_array( $args ) && isset( $args[ 'output'] ) && 'cache-zip' === $args[ 'output' ] ) {
				$use_html_output = 'no';
			} else if ( is_array( $args ) && isset( $args[ 'frontend'] ) && 'yes' === $args[ 'frontend' ] ) {
				$use_html_output = 'no';
			} else {
					$use_html_output = get_option( 'wp_wc_invoice_pdf_force_html_output', 'no' );
			}

			return $use_html_output;
		}

	} // end class

} // end if
