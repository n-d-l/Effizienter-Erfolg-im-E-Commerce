<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WCREAPDF_Pdf_Delivery' ) ) {

	if ( ! defined( 'GM_EURO' ) ) {
		define( 'GM_EURO' , utf8_encode( chr( 128 ) ) );
	}

	/**
	* pdf creation
	*
	* @class WCREAPDF_Pdf
	* @version 1.0.1
	* @category	Class
	*/
	class WCREAPDF_Pdf_Delivery extends FPDF {

		/**
		* create header, overrides FPDF method
		*
		* @since 0.0.1
		* @access public
		* @override
		* @return void
		*/
		function Header() {
			// init
			$variables = array(
								'font'                  => get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_font_delivery' ), 'Times' ),
								'header_font_size'      => 7,
								'header_cell_height'    => 3.25,
								'right_margin'          => 15.0,
								'header_right_margin'   => 12.0,
								'all_pages'             => '{nb}',	// alias for the total number of pages
								'print_page_numbers'    => true,		// whether to output current page number and total number of pages or not
								'string_page'           => __( 'Page', 'woocommerce-german-market' ),
							);

			// you can change the values of the variables and add variables if you want
			$variables = apply_filters( 'wcreapdf_reset_pdf_header_vars_delivery', $variables );
			extract( $variables, EXTR_OVERWRITE );

			if ( has_action( 'wcreapdf_pdf_header_delivery' ) ) {
				do_action( 'wcreapdf_pdf_header_delivery', $this, $variables );
			} else {
				$this->SetFont( $font, '' , $header_font_size );
				// {nb} is replaced by FPDF when the document is closed. unfortunately, ther's a right margin (or padding) after the replacement
				// that's why we change the right margin to align the text without any margin
				$this->SetRightMargin( $header_right_margin );
				if ( $print_page_numbers ) {
					$this->Cell( 0, $header_cell_height, utf8_decode( $string_page ) . ' '. $this->PageNo(). ' / ' . $all_pages, 0, 0, 'R' );
				}
				// reset the margin
				$this->SetRightMargin( $right_margin );
				$this->Ln( $header_cell_height );
			}
		}

		/**
		* create footer, overrides FPDF method
		*
		* @since 0.0.1
		* @access public
		* @override
		* @return void
		*/
		function Footer() {
			// init
			$footer_cell_height = 3.25;
			$footer_text		= get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_footer_delivery' ) );

			// replace • (bullet point)
			$footer_text 		= str_replace( '•', utf8_encode( chr( '183' ) ), $footer_text );

			$footer_lines 		= self::count_lines_of_footer( $footer_text );
			$variables = array(
							'font'                   => get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_font_delivery' ), 'Times' ),
							'footer_font_size'       => 7,
							'footer_cell_height'     => $footer_cell_height,
							'footer_text'            => $footer_text,
							'footer_lines'           => $footer_lines,
							'footer_y'               => ( -1 ) * ( $footer_cell_height * $footer_lines + 5.0 ),
							'footer_text_align'      => get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_footer_alignment_delivery' ), 'C' )
						);

			// you can change the values of the variables and add variables if you want
			$variables = apply_filters( 'wcreapdf_reset_pdf_footer_vars', $variables );
			extract( $variables, EXTR_OVERWRITE );

			if ( has_action( 'wcreapdf_pdf_footer_delivery' ) ) {
				do_action( 'wcreapdf_pdf_footer_delivery', $this, $variables );
			} else {
				$this->SetY( $footer_y );
				$this->SetFont( $font, '' , $footer_font_size );
				$this->MultiCell( 0, $footer_cell_height, utf8_decode( $footer_text . '' ), 0, $footer_text_align );
			}
		}

		/**
		* returns an array of layout variables used to create the pdf
		* array will be extracted using extract( $return, EXTR_IF_EXISTS ) in the method create_pdf
		* so the key is the name of the variable
		*
		* @since 0.0.1
		* @access public
		* @return array key will be uses as variable name
		*/
		public static function get_layout_variables() {
			$cell_small			= 4.5;
			$cell_big			= 5.0;
			$footer_text		= get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_footer_delivery' ) );
			$footer_lines		= self::count_lines_of_footer( $footer_text );
			$footer_cell_height	= 3.25;
			$reasons_string		= get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_reasons_delivery' ) );
			$there_are_reasons	= ( trim( $reasons_string ) != '' );                                             // saves whether delivery reasons where set or not in backend by user
			return array(
							'font'                           => get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_font_delivery' ), 'Times' ),
							'tiny'                           => 8,                   // font size tiny
							'small'                          => 10,                  // font size small
							'big'                            => 11,                  // font size big
							'huge'                           => 14,                  // font size huge
							'cell_tiny'                      => 3.0,                 // cell height for font size tiny
							'cell_small'                     => $cell_small,         // cell height for font size small
							'cell_big'                       => $cell_big,           // cell height for font size big
							'cell_huge'                      => 5.5,                 // cell height for font size huge
							'cell_dummy'                     => 4.5,                 // cell height for a dummy cell
							'cell_table'                     => 4.5,                 // cell height in table
							'draw_grey'                      => 200,                 // color for grey lines (value between 0 and 255)
							'fill_grey'                      => 230,                 // fill color
							'black'                          => 0,                   // black
							'image_height'                   => 20,                  // height of the image
							'address_empty_space_x'          => 105,                 // space between addresses
							'column1'                        => 37,                  // row width - suk
							'column1b'                       => 25,                  // row width - amount
							'column1c'                       => 25,                  // row width - weight
							'column2'                        => 85,                  // row width - product name
							'column3'                        => 16,                  // row width - quantity
							'column4'                        => 3,                   // row width - free space betw. input fields and column 2b = space between name and amount for long names
							'column5'                        => 0,                   // row width - reason
							'left_margin'                    => 15.0,                // page margin left
							'top_margin'                     => 10.0,                // page margin top
							'right_margin'                   => 15.0,                // page margin right
							'remaining_place1'               => 10.0,                // used to check for page break, change that if you change cell heights
							'remaining_place2'               => 50.0,
							'return_delivery_row_width'      => 100.0,               // width of the first column, dilvery reasons
							'br1'                            => 15.0,                // line breaks
							'br2'                            => 2 * $cell_small,
							'br3'                            => 10.0,
							'br4'                            => 2 * $cell_small,
							'br5'                            => 2 * $cell_small,
							'br6'                            => $cell_small,
							'br7'                            => 2 * $cell_small,
							'br8'                            => $cell_small,
							'bold1'                          => '',                     // set font 'B' for bold, '' for normal
							'bold2'                          => 'B',
							'bold3'                          => '',
							'bold4'                          => 'B',
							'bold5'                          => '',
							'bold6'                          => 'B',
							'bold7'                          => '',
							'bold8'                          => '',
							'bold9'                          => '',
							'bold10'                         => '',
							'bold11'                         => '',
							'bold12'                         => 'B',
							'bold13'                         => '',
							'bold14'                         => 'B',
							'bold15'                         => '',
							'bold_short_description'		 => '',
							'delivery_reasons1'              => 43,                  // multicell width delivery reasons left
							'delivery_reasons2'              => 43,                  // multicell width delivery reasons right
							'delivery_reasons_empty_space'   => 2,                   // space between first multicell and second multicell
							'beginn_comment_lines'           => 100.0,               // x-coordinate of beginning of comment line
							'height_comment_line'            => 1.6 * $cell_big,     // height of one comment line
							'footer_cell_height'             => $footer_cell_height, // data that is used in every loop of items to estimate whether a page break is needed
							'footer_text'                    => $footer_text,
							'footer_lines'                   => $footer_lines,
							'footer_y'                       => ( -1 ) * ( $footer_cell_height * $footer_lines + 5.0 ),
							'estimated_extra'                => 10.0,                // used for estimation of page breaks
							'estimated_extra2'               => 2 * $cell_small,
							'estimated_page_height'          => 295,                 // this is not estimated page height, just page height needed for estimation
							'cMargin'                        => 0,                   // removes padding in cells
							'pdf_orientation'                => 'P',                 // pdf orientation, used in FPDF constructor.  Possible values: 'P': Portrait, 'L': Landscape
							'pdf_unit'                       => 'mm',                // pdf user unit, used in FPDF constructor. Possible values: 'pt': point, 'mm': millimeter, 'cm': centimeter, 'in': inch
							'pdf_size'                       => 'A4',                // page size, used in FDPF constructer. Possible values: 'A3', 'A4', 'A5', 'Letter', 'Legal' or array containing width & height (expr. in $pdf_unit)
							'string_recipient'               => __( 'Recipient', 'woocommerce-german-market' ),
							'string_consignor'               => __( 'Consignor', 'woocommerce-german-market' ),
							'string_return_delivery'         => __( 'Delivery Note', 'woocommerce-german-market' ),
							'string_order'                   => __( 'Order', 'woocommerce-german-market' ),
							'string_order_date_format'       => __( 'Y-m-d', 'woocommerce-german-market' ),
							'string_amount'                  => __( 'Amount', 'woocommerce-german-market' ),
							'string_suk'                     => __( 'SKU', 'woocommerce-german-market' ),
							'string_product'                 => __( 'Product', 'woocommerce-german-market' ),
							'string_quantity'                => __( 'Quantity', 'woocommerce-german-market' ),
							'string_reason'                  => __( 'Reason', 'woocommerce-german-market' ),
							'string_star'                    => '*',
							'string_remark'                  => __( 'Remark', 'woocommerce-german-market' ),
							'string_delivery_date'			 => __( 'Delivery date', 'woocommerce-german-market' ),
							'string_delivery_reasons'        => __( 'Return delivery reasons', 'woocommerce-german-market' ),
							'string_comments'                => __( 'Comments', 'woocommerce-german-market' ),
							'string_weight'					 => __( 'Weight', 'woocommerce-german-market' ),
							'string_no_sku'                  => "-",
							'string_no_weight'               => "-",
							'string_no_phone_or_email'		 => "-",
							'there_are_reasons'				 => $there_are_reasons,
							'string_customer_note'			 =>__( 'Customer Note:', 'woocommerce-german-market' ),
						);
		}

		/**
		* creates the pdf file using FPDF library
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments WC_Order $order, boolean $test (if true test pdf is generated and $order is ignored), mixed $download (if 'I' send inline to browser, if false save tmp pdf, else force file download)
		* @return string $directory_name (name of the directory where the pdf is saved)
		*/
		public static function create_pdf( $order = NULL, $test = false, $download = false, $for_zip = false, $admin = false ) {

			do_action( 'wcreapdf_pdf_before_create', 'delivery', $order, $admin );

			$directory_name = ''; // init for return
			self::clear_temp_pdf(); // clear temps

			////////////////////////////////////
			// init layout variables
			////////////////////////////////////
			$variables = apply_filters( 'wcreapdf_reset_pdf_vars_delivery', self::get_layout_variables() ); // you can change the values of the variables and add variables if you want
			extract( $variables, EXTR_OVERWRITE );

			////////////////////////////////////////////////////
			// if custom font is used see filter wcreapdf_fonts
			////////////////////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_custom_fonts' ) && ( ! in_array( $font, array( 'Helvetica', 'Courier', 'Times' ) ) ) ) {
				define( 'FPDF_FONTPATH', WCREAPDF_TEMP_DIR . DIRECTORY_SEPARATOR . 'fonts' );		// copy font files to wp-content/cache/woocommerce-return-delivery-pdf/fonts
			}

			////////////////////////////////////
			// pdf init
			////////////////////////////////////

			$pdf = new WCREAPDF_Pdf_Delivery( $pdf_orientation, $pdf_unit, $pdf_size );             		 // creates new object, FPDF constructor

			$pdf->SetCompression( false ); // Update July 2015: Some Servers seems to create corrupted pdf files if we don't turn compression off

			if ( ( ! in_array( $font, array( 'Helvetica', 'Courier', 'Times' ) ) ) ) {
				do_action( 'wcreapdf_pdf_custom_fonts', $pdf );									 // add fonts
			}

			if ( has_action( 'wcreapdf_pdf_init_delivery' ) ) {
				do_action( 'wcreapdf_pdf_init_delivery', $pdf, $variables, $order, $test, $download );    // user action for init pdf
			} else {
				$pdf->set_pdf_author_title();                                                   // set author, title and subject of pdf file
				$pdf->cMargin = $cMargin;                                                       // removes padding in cells (otherwise bottom borders start some spaces before texts)
				$pdf->SetMargins( $left_margin, $top_margin, $right_margin );                   // set page margins
				$auto_page_break = ( $footer_cell_height * $footer_lines + 5.0 );               // estimates the height of footer
				$pdf->SetAutoPageBreak( true, $auto_page_break );                               // set auto page break
				$pdf->SetDrawColor( $draw_grey );                                               // set draw color grey
				$pdf->SetFillColor( $fill_grey );                                               // set fill color grey
				$pdf->SetTextColor( $black );                                                   // set text color black
				$pdf->AliasNbPages();                                                           // set '/{nb}' as an alias for the total number of pages
				$pdf->AddPage();                                                                // add first page
			}

			////////////////////////////////////
			// image or shop name
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_iamge_bind_or_shop_name_delivery' ) ) {
				do_action( 'wcreapdf_pdf_iamge_bind_or_shop_name_delivery', $pdf, $variables, $order, $test, $download );
			} else {
				$img = get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_logo_url_delivery' ) );    // url to image
				$image_bind = $pdf->add_header_image( $img );                                       // try to insert image in pdf

				// exception handling
				if ( is_a( $image_bind, 'Exception' ) && is_admin() ) {
					$error_message = sprintf( __( 'The pdf could not be created. There was an error when trying to include the image "%s". Please check, whether this image is still available in the WordPress Media of this WordPress installation. You may have to rechoose the image from the WordPress Media (e.g. if this is a staging system) or you have to reupload the image <a href="%s">here</a>.', 'woocommerce-german-market' ), $img, get_admin_url() . 'admin.php?page=german-market&tab=preferences-wcreapdf&sub_tab=pdf_settings_delivery_note' );
					update_option( 'wcreapdf_pdf_image_bind_error', $error_message );
					wp_safe_redirect( wp_get_referer() );
				}

				// shop name
				if ( ! $image_bind ) {                                                              // if it wasn't possible to insert image, output shopname
					$pdf->SetFont( $font, $bold1 , $big );
					$pdf->Cell( 0, $cell_big, utf8_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_shop_name_delivery' ), get_bloginfo( 'name' ) ) ), 0, 0, 'L' ); // output shopname
				}
			}

			////////////////////////////////////
			// addresses
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_addresses_delivery' ) ) {
				do_action( 'wcreapdf_pdf_addresses_delivery', $pdf, $variables, $order, $test, $download );
			} else {

				do_action( 'wcreapdf_pdf_before_addresses_delivery', $pdf, $variables, $order, $test, $download );

				$extra_space = apply_filters( 'wcreapdf_pdf_addresses_delivery_extra_space_before_adress', 0 );
				if ( $extra_space > 0 ) {
					$pdf->Ln( $extra_space );
				}

				if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_shop_adress_position_delivery', 'right' ) ) == 'right' ) {

					// show consignor to the right of the recipient
					$pdf->Ln( $br1 );
					$pdf->SetFont( $font, $bold2 , $small );
					$pdf->Cell( $address_empty_space_x, $cell_small, utf8_decode( $string_recipient . ':' ), 0, 0, 'L' );   // output 'Recipient' string
					$pdf->Cell( 0, $cell_small, utf8_decode( $string_consignor . ':' ), 0, 0, 'L' );
					$pdf->Ln( $br2 );
					$pdf->SetFont( $font, $bold3, $small );
					$current_y = $pdf->GetY();  // save current y-position to get back to this position later
					$pdf->Cell( $address_empty_space_x, $cell_small, '', 0, 0, 'L' );
					$pdf->MultiCell( $address_empty_space_x, $cell_small, utf8_decode( html_entity_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_address_delivery' ) ) ) ), 0, 'L' );	// output shop address
					$pdf->SetY( $current_y );   // get back to saved y-position
					if ( ! $test ) {
						$formattedAddress  = apply_filters( 'wcreapdf_pdf_adress_delivery_shipping_address', $order->get_formatted_shipping_address(), $order );      // shipping address from order
						if ( empty( $formattedAddress ) ) {
							$formattedAddress = apply_filters( 'wcreapdf_pdf_adress_delivery_shipping_address',$order->get_formatted_billing_address(), $order );
						}
					} else {
						$formattedAddress  = $pdf->get_test_address();                      // test shipping address
					}
					$addressArray   = explode( '<br/>', $formattedAddress );
					foreach ( $addressArray as $addressRow ) {                              // output shipping address
						$pdf->Cell( 0, $cell_small, utf8_decode( html_entity_decode( trim( $addressRow ), ENT_QUOTES ) ), 0, 1, 'L' );
					}

				} else {

					// show consignor above the recipient in one line
					$pdf->Ln( apply_filters( 'wcreapdf_pdf_adress_position_factor', 1.5 ) * $br1 );
					$consignor = get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_address_delivery' ) );
					$consignor = nl2br( $consignor, false );
					$addressArray   = explode( '<br>', $consignor );
					$new_consignor = array();
					foreach ( $addressArray as $addressRow ) {                              // output shipping address
						$new_consignor[] = utf8_decode( html_entity_decode( trim( $addressRow ) ) );
					}
					$consignor = implode( apply_filters( 'wcreapdf_pdf_adress_position_above_separator', ', ' ), $new_consignor );

					$pdf->SetFont( $font, $bold1 , $tiny );
					$pdf->Cell( 0, $cell_small, $consignor, 0, 1, 'L' );
					$pdf->SetFont( $font, $bold3, $small );
					$pdf->Ln( $br6 );

					if ( ! $test ) {
						$formattedAddress  = apply_filters( 'wcreapdf_pdf_adress_delivery_shipping_address', $order->get_formatted_shipping_address(), $order );      // shipping address from order
						if ( empty( $formattedAddress ) ) {
							$formattedAddress = apply_filters( 'wcreapdf_pdf_adress_delivery_shipping_address',$order->get_formatted_billing_address(), $order );
						}
					} else {
						$formattedAddress  = $pdf->get_test_address();                      // test shipping address
					}
					$addressArray   = explode( '<br/>', $formattedAddress );
					foreach ( $addressArray as $addressRow ) {                              // output shipping address
						$pdf->Cell( 0, $cell_small, utf8_decode( html_entity_decode( trim( $addressRow ) ) ), 0, 1, 'L' );
					}

					$pdf->Ln( apply_filters( 'wcreapdf_pdf_adress_position_factor_2', 0.75 ) * $br1 );

				}

			}

			////////////////////////////////////
			// return delivery
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_return_delivery_delivery' ) ) {
				do_action( 'wcreapdf_pdf_return_delivery_delivery', $pdf, $variables, $order, $test, $download );
			} else {
				$pdf->Ln( $br3 );
				$pdf->SetFont( $font, $bold4, $huge );
				$pdf->Cell( 0, $cell_huge, utf8_decode( $string_return_delivery ) . ":", 0, 1, 'L', 0 ); // output 'Return delivery'
				// order number
				$pdf->SetFont( $font, $bold5, $small );

				// output order number and date in the following lines
				$string_order = get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_shop_small_headline_delivery' ) );
				if ( $string_order == '' ) {
					$string_order = __( 'Order: {{order-number}} ({{order-date}})', 'woocommerce-german-market' );
				}
				$search = array( '{{order-number}}', '{{order-date}}' );

				if ( ! $test ) {
					$replace = array( $order->get_order_number(), date_i18n( apply_filters( 'wcreapdf_pdf_adress_delivery_date_format', get_option( 'date_format' ) ), $order->get_date_created()->getTimestamp() ) );
				} else {
					$replace = array( utf8_decode( rand( 100, 9999 ) ), date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) );
				}

				$small_headline = str_replace( $search, $replace, $string_order );
				$small_headline = apply_filters( 'wcreapdf_pdf_placeholders_frontend_string', $small_headline, $order );

				$pdf->Cell( 0, $cell_small, utf8_decode( $small_headline ), 0, 1, 'L' );
			}

			////////////////////////////////////
			// table header
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_table_header_delivery' ) ) {
				do_action( 'wcreapdf_pdf_table_header_delivery', $pdf, $variables, $order, $test, $download );
			} else {
				$pdf->Ln( $br4 );
				$pdf->SetFont( $font, $bold6, $small );
				$pdf->Cell( $column1b,  $cell_small,    utf8_decode( $string_amount ),  0, 0, 'L', 0 );				// output amount

				if (  get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'weight_delivery' ), true ) ) {
					$pdf->Cell( $column1c,  $cell_small,    utf8_decode( $string_weight ),  0, 0, 'L', 0 );			// output weight
				} else {
					$column1c = 0;
				}

				if (  get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'sku_delivery' ), true ) ) {
					$pdf->Cell( $column1,   $cell_small, utf8_decode( $string_suk ),        0, 0, 'L', 0 );			// output suk
				} else {
					$column1 = 0;
				}

				$pdf->Cell( $column2, 	$cell_small, utf8_decode( $string_product ),	0, 1, 'L', 0 );				// output product

				$pdf->Cell( 0, $cell_dummy, '', 'B', 1, 'L' );
			}

			$weight_unit  = apply_filters( 'wcrepdf_weight_unit', get_option( 'woocommerce_weight_unit', 'kg' ) );
			$total_weight = 0.0;

			////////////////////////////////////
			// table content
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_table_content_delivery' ) ) {
				do_action( 'wcreapdf_pdf_table_content_delivery', $pdf, $variables, $order, $test, $download );
			} else {
				$pdf->SetFont( $font, $bold7, $small );
				if ( ! $test ) {
					$items = $order->get_items();		// get items of order
				} else {
					$items = $pdf->get_test_products();	// test products if we are creating a test pdf
				}
				foreach ( $items as $item_id => $item ) {
					// init output data
					if ( ! $test ) {

						if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
							$_product = $item->get_product();
						} else {
							$_product = $order_obj->get_product_from_item( $item );
						}

						$item_is_a_product = WGM_Helper::method_exists( $_product, 'needs_shipping' ); // some items aren't products (probably romoved from shop)
						if ( $item_is_a_product ) {
							$needs_shipping	= $_product->needs_shipping();
							if ( ! $needs_shipping ) {	// if this product is not shipped, don't add it to this pdf
								continue;
							}
							$sku = ( $_product->get_sku() != '' ) ? $_product->get_sku() : $string_no_sku;
							$sku = apply_filters( 'wcreapdf_pdf_sku_delivery', $sku, $_product );
						} else {
							$sku = $string_no_sku;
						}

						$item_name 					  = apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
						$name                         = apply_filters( 'wcrepdf_item_name', $item_name );
						$name 						  = strip_tags( $name );
						$name 						  = str_replace( array( '&euro;', '€' ), GM_EURO, $name );
						$name 						  = html_entity_decode( $name );
						$name 						  = str_replace ( '–', '-', $name );
						$name 						  = str_replace( '„', utf8_encode( chr( 132 ) ), $name );
						$name 						  = str_replace( '“', utf8_encode( chr( 147 ) ), $name );
						$name 						  = str_replace( '‘', utf8_encode( chr( 145 ) ), $name );
						$name 						  = str_replace( '’', utf8_encode( chr( 146 ) ), $name );
						
						if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'quantity_refund_delivery' ), 'exclude' ) == 'exclude' ) {
							$quantity = $item->get_quantity();
						} else {
							$quantity = $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id );
						}

						if ( $quantity <= 0 ) {
							continue;
						}

						$wc_display_item_meta_args = array(
							'before'    => '',
				            'after'     => '',
				            'separator' => ', ',
				            'echo'      => false,
				            'autop'     => false,
						);

						$item_meta_display_string = str_replace( array( '&euro;', '€' ), GM_EURO, strip_tags( wc_display_item_meta( $item, $wc_display_item_meta_args ) ) );
						$item_meta_display_string = apply_filters( 'wcrepdf_delivery_item_meta_display_string', html_entity_decode( $item_meta_display_string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ), $item, $order );

						if ( WGM_Helper::method_exists( $_product, 'get_weight' ) && WGM_Helper::method_exists( $item, 'get_quantity' ) ) {
							if ( $_product->get_weight() != '' ) {
								if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'quantity_refund_delivery' ), 'exclude' ) == 'exclude' ) {
									$weight = $_product->get_weight() * $item->get_quantity();
									$weight = apply_filters( 'wcrepdf_delivery_item_weight_output', $weight, $_product->get_weight(), $item->get_quantity() );
									$total_weight += $weight;
								} else {
									$weight = $_product->get_weight() * ( $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id ) );
									$weight = apply_filters( 'wcrepdf_delivery_item_weight_output', $weight, $_product->get_weight(), $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id ) );
									$total_weight += $weight;
								}
							} else {
								$weight = '';
							}
						} else {
							$weight = '';
						}

					} else {   // test products
						$sku                          = $item[ 'sku' ];
						$name                         = $item[ 'name' ];
						$quantity                     = $item[ 'quantity' ];
						$item_meta_display_string     = $item[ 'meta' ];
						$weight 					  = rand( 50, 499 );
						$total_weight += $weight;
					}

					$weight = $weight == '' ? $string_no_weight : str_replace( '.', wc_get_price_decimal_separator(), $weight ) . $weight_unit;
					// check for pagebreak: let us estimate whether we need a pagebreak in the case that $name is so lang that it'll use two or even more lines.
					$string_width              = $pdf->GetStringWidth( $name );                                                // string_width of $name
					$estimated_nr_of_lines     = ceil ( ( $string_width + $estimated_extra ) / $column2 );                     // how many lines $name takes
					$estimated_place_needed    = $estimated_nr_of_lines * $cell_small;                                         // height of the lines $name takes
					$remaining_place           = $estimated_page_height - $pdf->GetY() - $estimated_place_needed + $footer_y;  // remaining place to bottom of the page
					if ( $remaining_place < $remaining_place1 ) {
						$pdf->AddPage();		// to less place => start a new page
					}
					// end check for pagebreak
					// output item
					$pdf->Cell( 0, $cell_dummy, '', 0, 1, 'L' );
					$pdf->Cell( $column1b, $cell_small, utf8_decode( $quantity ), 0, 0, 'L' );                     	// output amount

					if (  get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'weight_delivery' ), true ) ) {
						$pdf->Cell( $column1c,  $cell_small, utf8_decode( $weight ),  0, 0, 'L', 0 );   		  	// output weight
					}

					$y_before_sku = $pdf->GetY();
					$y_after_sku = $y_before_sku;

					if (  get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'sku_delivery' ), true ) ) {		// output suk
						$current_y = $pdf->GetY();
						$pdf->MultiCell( ( $column1 - 5 ), $cell_small, utf8_decode( $sku ), 0, 'L', 0 );
						$new_current_y = $pdf->GetY();
						$pdf->SetY( $current_y );
						$pdf->Cell( ( $column1 + $column1b + $column1c ), $cell_table, '', 0, 0, 'C', 0 );
						$y_after_sku = $new_current_y;
					}

					$current_y = $pdf->GetY();
					$pdf->MultiCell( $column2, $cell_small, utf8_decode( $name ), 0, 'L', 0 );                     	// output product name
					$pdf->SetFont( $font, $bold8 , $small );
					$new_current_y = $pdf->GetY();
					$pdf->SetY( $current_y );
					$pdf->Cell( ( $column1 + $column1b + $column2 +$column1c ), $cell_table, '', 0, 0, 'C', 0 );

					$pdf->SetY( $new_current_y );

					// variation meta data
					if ( $item_meta_display_string != '' ) {                                                       // output variation meta data
						$pdf->SetFont( $font, $bold9 , $tiny );
						$pdf->Cell( ( $column1 + $column1b + $column1c ), $cell_tiny, '', 0, 0, 'L' );
						$pdf->MultiCell( $column2, $cell_tiny, utf8_decode( $item_meta_display_string ), 0, 'L', 0 );
						$pdf->SetFont( $font, $bold10, $small );
					}

					// product short_desription
					if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'show_short_description_delivery' ), false ) ) {

						$short_description = '';

						if ( $test ) {
							$short_description = __( 'This is an example product short description', 'woocommerce-german-market' );
						} else {
							if ( is_object( $_product ) && method_exists( $_product, 'get_id' ) ) {
								$short_description = WGM_Template::get_short_description_by_product_id( $_product->get_id() );
								$short_description = str_replace( '<br>', PHP_EOL, $short_description );
								$short_description = strip_tags( $short_description );
								$short_description = apply_filters( 'wcreapdf_pdf_short_description', $short_description, $_product, $order );
							}
						}

						if ( ! empty( $short_description ) ) {
							$short_description = str_replace( array( '&euro;', '€' ), GM_EURO, $short_description );
							$pdf->SetFont( $font, $bold_short_description, $tiny );
							$pdf->Cell( ( $column1 + $column1b + $column1c ), $cell_tiny, '', 0, 0, 'C', 0 );
							$pdf->MultiCell( $column2, $cell_tiny, utf8_decode( $short_description ), 0, 'L', 0 );
							$pdf->SetFont( $font, $bold10, $small );
						}

					}

					$y_after_product = $pdf->getY();

					$new_y = max( $y_before_sku, $y_after_sku, $y_after_product );
					$pdf->setY( $new_y );

				}

				$pdf->Cell( 0, $cell_dummy, '', 'B', 1, 'L' );
			}

			////////////////////////////////////
			// delivery date
			////////////////////////////////////
			$did_delivery_date = false;

			if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'show_delivery_date' ), false ) ) {
				if ( has_action( 'wcreapdf_pdf_delivery_date' ) ) {
					do_action( 'wcreapdf_pdf_delivery_date', $pdf, $variables, $order, $test, $download );
				} else {

					$is_completed = false;

					if ( $test ) {
						$is_completed  	= true;
						$delivery_date 	= new DateTime();
					} else {
						$is_completed 	= $order->get_status() == 'completed';
						$delivery_date 	= apply_filters( 'wcreapdf_pdf_delivery_date_choose_date', $order->get_date_completed(), $order );
					}

					if ( apply_filters( 'wcreapdf_pdf_delivery_date_show_if_completed', $is_completed ) ) {

						$pdf->SetFont( $font, $bold11, $small );
						$pdf->Ln( $br5 );
						$pdf->SetFont( $font, $bold12, $big );
						$pdf->Cell( 0, $cell_big, utf8_decode( $string_delivery_date ) . ":", 0, 1, 'L', 0 );          // output 'Delivery Date'
						$pdf->SetFont( $font, $bold13, $small );

						$delivery_date = date_i18n( apply_filters( 'wcreapdf_pdf_adress_delivery_date_format', get_option( 'date_format' ) ), $delivery_date->getTimestamp() );
						$pdf->MultiCell( 0, $cell_small, utf8_decode( html_entity_decode( $delivery_date ) ), 0, 'L', 0 );

						$did_delivery_date = true;
					}

				}
			}

			////////////////////////////////////
			// remark
			////////////////////////////////////
			$did_remark = false;
			if ( has_action( 'wcreapdf_pdf_remark_delivery' ) ) {
				do_action( 'wcreapdf_pdf_remark_delivery', $pdf, $variables, $order, $test, $download );
			} else {
				$remark = get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_remark_delivery' ) );
				if ( $remark != '' ) { // no remark => no remark output at all
					$pdf->SetFont( $font, $bold11, $small );

					if ( ! $did_delivery_date ) {
						$pdf->Ln( $br5 );
					} else {
						$pdf->Ln( $br6 );
					}

					if ( $test ) {
						$first_name 	= __( 'John', 'woocommerce-german-market' );
						$last_name  	= __( 'Doe', 'woocommerce-german-market' );
						$phone 			= '00123456789';
						$email 			= 'john-doe@test.com';
						$total_weight	= str_replace( '.', wc_get_price_decimal_separator(), $total_weight ) . $weight_unit;
					} else {
						$first_name 	= $order->get_shipping_first_name();
						$last_name 		= $order->get_shipping_last_name();
						$phone 			= empty( $order->get_billing_phone() ) ? $string_no_phone_or_email : $order->get_billing_phone();
						$email 			= empty( $order->get_billing_email() ) ? $string_no_phone_or_email : $order->get_billing_email();
						$total_weight	= str_replace( '.', wc_get_price_decimal_separator(), $total_weight ) . $weight_unit;
					}

					if ( ! $test ) {
						date_default_timezone_set( get_option( 'timezone_string' ) );
						$order_number = $order->get_order_number();
						$order_date = date_i18n( apply_filters( 'wcreapdf_pdf_adress_delivery_date_format', get_option( 'date_format' ) ), $order->get_date_created()->getTimestamp() );
					} else {
						$order_number = utf8_decode( rand( 100, 9999 ) );
						$order_date = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) );
					}

					$remark = str_replace(
							array( '{{first-name}}', '{{last-name}}', '{{order-date}}', '{{order-number}}', '{{phone}}', '{{email}}', '{{total-weight}}' ),
							array( $first_name, $last_name, $order_date, $order_number, $phone, $email, $total_weight ),
							$remark
					);

					$remark = apply_filters( 'wcreapdf_pdf_placeholders_frontend_string', $remark, $order );

					$pdf->SetFont( $font, $bold12, $big );
					$pdf->Cell( 0, $cell_big, utf8_decode( $string_remark ) . ":", 0, 1, 'L', 0 );                 // output 'Remark'
					$pdf->SetFont( $font, $bold13, $small );
					$pdf->MultiCell( 0, $cell_small, utf8_decode( html_entity_decode( $remark ) ), 0, 'L', 0 );    // output remark text that has been saved in backend
					$did_remark = true;
				}
			}

			////////////////////////////////////
			// customer note since GM 3.5.2
			////////////////////////////////////
			if ( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_show_customers_note' ), 'off' ) == 'on' ) {

				if ( ! $test ) {

					$customer_note = $order->get_customer_note();

					if ( ! empty( trim( $customer_note ) ) ) {

						$pdf->SetFont( $font, $bold11, $small );

						if ( ( ! $did_remark ) && ( ! $did_delivery_date ) ) {
							$pdf->Ln( $br5 );
						} else {
							$pdf->Ln( $br6 );
						}

						$pdf->SetFont( $font, $bold12, $big );
						$pdf->Cell( 0, $cell_big, utf8_decode( $string_customer_note ), 0, 1, 'L', 0 );
						$pdf->SetFont( $font, $bold13, $small );
						$pdf->MultiCell( 0, $cell_small, utf8_decode( html_entity_decode( $customer_note ) ), 0, 'L', 0 );

					}
				}

			}

			////////////////////////////////////
			// after pdf content
			////////////////////////////////////
			$after_pdf = apply_filters( 'wcreapdf_pdf_after_pdf_delivery', '', $pdf, $order, $test );
			if ( ! empty( $after_pdf ) ) {
				$pdf->Ln( $br6 );
				$pdf->SetFont( $font, $bold13, $small );
				$pdf->MultiCell( 0, $cell_small, utf8_decode( $after_pdf ), 0, 'L', 0 );
			}

			////////////////////////////////////
			// pdf reset action
			////////////////////////////////////
			if ( has_action( 'wcreapdf_pdf_reset_delivery' ) ) {
				unset( $pdf );
				$pdf = new WCREAPDF_Pdf( $pdf_orientation, $pdf_unit, $pdf_size );
				do_action( 'wcreapdf_pdf_reset_delivery', $pdf, $variables, $order, $test, $download );
			}

			do_action( 'wcreapdf_pdf_after_create', 'delivery', $order );

			do_action( 'wcreapdf_pdf_after_create_pdf', 'delivery', $pdf, $order );
			// PDF OUTPUT
			return $pdf->wc_output( $order, $download, $test, $for_zip, $admin );
		}

		/**
		* set pdf data
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/
		public function set_pdf_author_title() {
			$this->SetAuthor( utf8_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_author_delivery' ), get_bloginfo( 'name' ) ) ), false );
			$this->SetTitle( utf8_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_title_delivery' ), __( 'Delivery Note', 'woocommerce-german-market' ) . ' - ' . get_bloginfo( 'name' ) ) ) );
			$this->SetSubject( utf8_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_title_delivery' ), __( 'Delivery Note', 'woocommerce-german-market' ) . ' - ' . get_bloginfo( 'name' ) ) ) );
		}

		/**
		* get dummy customer adress
		*
		* @since 0.0.1
		* @static
		* @access public
		* @return string
		*/
		public static function get_test_address() {
			 $test_address = __( 'John Doe', 'woocommerce-german-market' ) . '<br/>' . __( '42 Example Avenue', 'woocommerce-german-market' ) . '<br/>' . __( 'Springfield, IL 61109', 'woocommerce-german-market' );
			 return apply_filters( 'wcrepdf_custom_test_address', $test_address );
		}

		/**
		* get dummy products
		*
		* @since 0.0.1
		* @access public
		* @return array
		*/
		public static function get_test_products() {
			$example_meta_array		= array(
										array( 	'label'	=> __( 'Size', 'woocommerce-german-market' ),
												'value' => __( 'XL', 'woocommerce-german-market' )
											)
										);
			$example_meta			= self::get_item_meta_display_string( $example_meta_array );
			$test_products = array(
								1	=>	array( 	'sku' 		=> rand( 1000000, 1999999 ),
												'name'		=> __( 'Example Toy Car', 'woocommerce-german-market' ),
												'quantity'	=> rand( 1, 10 ),
												'meta'		=> ''
											),
								2	=>	array( 	'sku' 		=> rand( 2000000, 2999999 ),
												'name'		=> __( 'Example T-Shirt', 'woocommerce-german-market' ),
												'quantity'	=> rand( 1, 10 ),
												'meta'		=> $example_meta
											),
								3	=>	array( 	'sku' 		=> rand( 3000000, 3999999 ),
												'name'		=> __( 'Example Piano', 'woocommerce-german-market' ),
												'quantity'	=> rand( 1, 10 ),
												'meta'		=> ''
											)
							);
			return apply_filters( 'wcrepdf_custom_test_products', $test_products );
		}

		/**
		* add the image in pdf
		*
		* @since 0.0.1
		* @access public
		* @return boolodan, true if image was added
		*/
		public function add_header_image( $img ) {
			$image_cell_small    = 4.5;
			$image_height        = 20;
			$image_x             = 15.0; // x-value of the upper-left corner
			$image_y             = 15.0; // y-value

			// APPLY FILTERS you can change the values of the variables if you want
			$variables = apply_filters( 'wcreapdf_reset_pdf_image_vars_delivery', array() );	// you can change the values of the variables if you want
			extract( $variables, EXTR_OVERWRITE );

			if ( trim( $img ) != '' ) {
				$extension  		= strtolower( pathinfo( $img, PATHINFO_EXTENSION ) );
				$allowed_extensions	= array( 'jpg', 'jpeg', 'gif', 'png' );	// the GD extension is required for gif
				if ( in_array( $extension, $allowed_extensions ) ) {
					// we need the image path
					$path_array 		= wp_upload_dir();
					$path				= untrailingslashit( ( $path_array[ 'basedir' ] ) );				// wp upload path
					$url				= untrailingslashit( ( $path_array[ 'baseurl' ] ) );				// wp upload url

					// doesn't matter if https or http, we need the path. We do this because wp_upload_dir returns http, even though https is used.
					$img = str_replace( 'https://', 'http://', $img );
					$url = str_replace( 'https://', 'http://', $url );

					$sub_dir_and_file	= str_replace( $url, '', $img );									// replace wp upload url from image url, will always start with a '/'
					$sub_dir_and_file	= str_replace( '/', DIRECTORY_SEPARATOR, $sub_dir_and_file );	// replace '/' => DIRECTORY_SEPARATOR so we have path part to image
					$image_path 		= $path . $sub_dir_and_file;										// wo upload url + path part to image => that is the image path
					if ( has_action( 'wcreapdf_pdf_add_image_delivery' ) ) {
						do_action( 'wcreapdf_pdf_add_image_delivery', $this, $image_cell_small, $image_height, $image_x, $image_y, $extension, $variables );
					} else {

						try {
							@$this->Image( $image_path, $image_x, $image_y, 0, $image_height );
						} catch ( Exception $e ) {
							return $e;
						}

						$current_y	= $this->GetY();
						$this->SetY( $image_height + $image_cell_small );
					} // end has_action
					return true;
				} // end extension
			} // end $img != ''
			return false;
		}

		/**
		* get amount of lines of a text that is seperated by '\n's
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments string $footer_text (a text seperated (or not) by text line breaks)
		* @return integer
		*/
		public static function count_lines_of_footer( $footer_text ) {
			$footer_text		= nl2br( $footer_text, false );
			$footer_text_array	= explode( '<br>', $footer_text );
			return count( $footer_text_array );
		}

		/**
		* get string that contains all meta data for output in pdf
		*
		* @since 0.0.1
		* @access public
		* @static
		* @arguments string $metaArray (you get it from the method 'get_formatted' of class WC_Order_Item_Meta
		* @return string
		*/
		public static function get_item_meta_display_string( $meta_array ) {
			$return_array = array();
			foreach ( $meta_array as $single_meta_array ) {
				$output = apply_filters( 'wcreapdf_pdf_item_meta_string_delivery', $single_meta_array[ 'label' ] . ': ' . $single_meta_array[ 'value' ], $single_meta_array );
				array_push( $return_array, $output );
			}
			$return_string = apply_filters( 'wcreapdf_pdf_all_meta_delivery', implode( ', ', $return_array ), $return_array );
			return $return_string;
		}

		/**
		* manages pdf output
		*
		* @since 0.0.1
		* @access public
		* @arguments WC_Order $order, mixed $download, boolean $test
		* possible balues for $download: false (create temp pdf), 'I' (send pdf inline to browser), everything else forces file download
		* @return mixed ($directory_name if download is false, else void )
		*/
		public function wc_output( $order, $download, $test, $for_zip = false, $admin = false ) {

			do_action( 'wcreapdf_pdf_before_output', 'delivery', $order, $admin, $this );

			if ( $download === false ) {

				if ( $for_zip ) {

					$directory = untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-return-delivery-pdf-zip' . DIRECTORY_SEPARATOR;
					wp_mkdir_p( $directory );
					$filename  = apply_filters( 'wcreapdf_zip_filename_delivery', get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_file_name_delivery' ), __( 'Delivery-Note', 'woocommerce-german-market' ) ) . '-' . $order->get_id(), $order );
					$this->Output( $directory . $filename . '.pdf', "F" );
					do_action( 'wcreapdf_pdf_after_output', 'delivery', $order );
					return $directory . $filename;

				} else {

					$directory_name	= time() . "_" . rand( 1, 99999999999 ) . '_' . md5( rand( 1, 999999) . 'wcreapdf' ) . md5( 'WCREAPDF' . rand( 0, 999999999 ) );
					wp_mkdir_p( WCREAPDF_TEMP_DIR . 'pdf' . DIRECTORY_SEPARATOR . $directory_name );
					$this->Output( WCREAPDF_TEMP_DIR . 'pdf' .  DIRECTORY_SEPARATOR . $directory_name .  DIRECTORY_SEPARATOR . get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_file_name_delivery' ), __( 'Delivery-Note', 'woocommerce-german-market' ) ) . '.pdf', "F" );
					do_action( 'wcreapdf_pdf_after_output', 'delivery', $order );
					return $directory_name;

				}

			} else {
				$download_string = ( $download === 'I' ) ? 'I' : 'D';
				$suffix = ( $test === false ) ? '-' . $order->get_order_number() : '';
				$this->Output( utf8_decode( get_option( WCREAPDF_Helper::get_wcreapdf_optionname( 'pdf_file_name_delivery' ), __( 'Delivery-Note', 'woocommerce-german-market' ) ) ) . $suffix . '.pdf', $download_string );
				do_action( 'wcreapdf_pdf_after_output', 'delivery', $order );

				// 3rd party plugins may do a redirect before our download starts
				if ( is_admin() ) {
					exit();
				}
			}

		}

		/**
		* we cannot delete our pdf immediately because the generation of pdf and sending it via mail don't happen
		* simultaneously because we are just hooked into the mail sending process
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/
		public static function clear_temp_pdf() {
			$temp_dir 		= WCREAPDF_TEMP_DIR . 'pdf' . DIRECTORY_SEPARATOR;
			if ( ! file_exists( $temp_dir ) ) {
				return;
			}
			$temp_dir_tree	= scandir( $temp_dir );
			foreach ( $temp_dir_tree as $dir ) {
				$test_dir	= explode( "-", $dir, 1 );
				$timestamp	= intval( $test_dir[ 0 ] );
				if ( $timestamp > 0 ) {
					if ( ( time() - $timestamp ) < 10 ) {
						continue;
					}
					$clear_dir	= WCREAPDF_TEMP_DIR. 'pdf' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
					$files = array_diff( scandir( $clear_dir ), array( '.', '..' ) );
					foreach ( $files as $file ) {
						unlink( $clear_dir . $file );
					}
					rmdir( $clear_dir );
				}
			}
		}

	} // end class

} // end if
