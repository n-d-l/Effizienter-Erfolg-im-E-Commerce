<?php
/**
 * Template for page numbers
 *
 * @version     0.0.1
 *
 * everything inside the <script type="text/php"> tag will be executed as a PHP script during the rendering process by DOMPDF
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

?>
<script type="text/php">
        if ( isset( $pdf ) ) {
		
			   $pdf->page_script('
			        
			        // page number output not desired
			        if ( isset( $GLOBALS[ "hide_page_number_pages" ] ) ) {
			        	if ( in_array( $PAGE_NUM, $GLOBALS[ "hide_page_number_pages" ] ) ) {
			        		return;
			        	}
			        }

			       //position
				   $position = trim( get_option( "wp_wc_invoice_pdf_page_numbers_output", "footer_bottom_right" ) );
				   
				   if ( $position != "none" ) {
						
						// paper size and user unit
						$paper_size		= get_option( "wp_wc_invoice_pdf_paper_size" , "A4" );
					    $user_unit 		= get_option( "wp_wc_invoice_pdf_user_unit", "cm" );
						
						// text
						$text			= $GLOBALS[ "page_numbers_text" ];
						$pseudo_text	= str_replace( array( "{{current_page_number}}", "{{total_page_number}}" ), array( "9", "9" ), $text );
						$text			= str_replace( array( "{{current_page_number}}", "{{total_page_number}}" ), array( $PAGE_NUM, $PAGE_COUNT ), $text );
						
						// font
						$font_family	= $fontMetrics->get_font( get_option( "wp_wc_invoice_pdf_page_numbers_font", "helvetica" ) , "normal" );
						$font_size		= get_option( "wp_wc_invoice_pdf_page_numbers_font_size", 6 );
						$font_color		= get_option( "wp_wc_invoice_pdf_page_numbers_color", "#000000" );
						
						// text width and height
						$text_width		= $pdf->get_text_width( $pseudo_text, $font_family, $font_size );
						$text_height	= $pdf->get_font_height( $font_family, $font_size ) / apply_filters( "wp_wc_invoice_pdf_page_number_text_height_factor", 0.87 ) ;
						
						if ( $user_unit == "cm" ) {
							$text_width 		= $text_width / 	28.3527;
							$text_height	= $text_height / 28.3527;
						} else {
							$text_width 		= $text_width / 	72.0159;
							$text_height	= $text_height / 72.0159;
						}
						
						// convert color: hex to rgb
					   $hex = trim( str_replace( "#", "", $font_color ) );
					   if ( strlen ( $hex ) == 3 ) {
						  $r = hexdec( substr( $hex ,0 ,1 ) . substr( $hex ,0 ,1 ) );
						  $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1 ,1 ) );
						  $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
					   } else {
						  $r = hexdec( substr( $hex, 0, 2 ) );
						  $g = hexdec( substr( $hex, 2, 2 ) );
						  $b = hexdec( substr( $hex, 4, 2 ) );
					   }
					   $font_color_rgb = array( ( $r / 256.0), ( $g / 256.0 ) , ( $b / 256.0 ) );
					   
					   // now we calculate the position
						if ( $position != "custom" ) {

									$orientation = get_option( "wp_wc_invoice_pdf_paper_orientation", "portrait" );
									
									if ( $orientation == "portrait" ) {
										  
										  if ( $paper_size == "A4" && $user_unit == "cm" ) {
												 $page_width	= 21.0;
												 $page_height	= 29.7;  
										   } else if ( $paper_size == "A4" && $user_unit == "in" ) {
												 $page_width	= 8.2677;
												 $page_height	= 11.692;  
										   } else if ( $paper_size == "letter" && $user_unit == "in" ) {
												$page_width		= 8.5;
												$page_height	= 11.0;
										   } else if ( $paper_size == "letter" && $user_unit == "cm" ) {
												$page_width		= 21.59;
												$page_height	= 27.94;
										   }

									} else {

										if ( $paper_size == "A4" && $user_unit == "cm" ) {
												 $page_height	= 21.0;
												 $page_width	= 29.7;  
										   } else if ( $paper_size == "A4" && $user_unit == "in" ) {
												 $page_height	= 8.2677;
												 $page_width	= 11.692;  
										   } else if ( $paper_size == "letter" && $user_unit == "in" ) {
												$page_height		= 8.5;
												$page_width	= 11.0;
										   } else if ( $paper_size == "letter" && $user_unit == "cm" ) {
												$page_height		= 21.59;
												$page_width	= 27.94;
										   }

									}

								   $positionArray			= explode( "_", $position );
								   $part					= $positionArray[ 0 ];
								   $vertical_alignment		= $positionArray[ 1 ];
								   $horizontal_alignment	= $positionArray[ 2 ];
								   
								   if ( $part == "header" ) {
									   
									   $header_height			= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_header_height", 0 ) ) );
									   $header_padding_top 		= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_header_padding_top", 0 ) ) );
									   $header_padding_bottom 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_header_padding_bottom", 0 ) ) );
									   $header_padding_left 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_header_padding_left", 0 ) ) );
									   $header_padding_right 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_header_padding_right", 0 ) ) );
									   
									   if ( $vertical_alignment	== "top" ) {
										   $y = $header_padding_top;
									   } else { // $vertival_alignment == "bottom"
										   $y = $header_height - $header_padding_bottom - $text_height;
									   }
									   
									   if ( $horizontal_alignment == "left" ) {
										   $x = $header_padding_left;
									   } else if ( $horizontal_alignment == "right" ) {
										   $x = $page_width - $header_padding_right - $text_width;
									   } else { // $horizontal_alignment == "center"
										   $x = ( ( ( $page_width - $header_padding_right ) - $header_padding_left ) / 2.0 ) + $header_padding_left - ( $text_width / 2.0 );
									   }
								   
								   } else { // $part == "footer"
									   
									   $footer_height			= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_footer_height", 0 ) ) );
									   $footer_padding_top 		= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_footer_padding_top", 0 ) ) );
									   $footer_padding_bottom 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_footer_padding_bottom", 0 ) ) );
									   $footer_padding_left 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_footer_padding_left", 0 ) ) );
									   $footer_padding_right 	= floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_footer_padding_right", 0 ) ) );
									   
									   if ( $vertical_alignment	== "top" ) {
										   $y = $page_height - $footer_height + $footer_padding_top;
									   } else { // $vertival_alignment == "bottom"
										   $y = $page_height - $footer_padding_bottom - $text_height;
									   }
									   
									   if ( $horizontal_alignment == "left" ) {
										   $x = $footer_padding_left;
									   } else if ( $horizontal_alignment == "right" ) {
										   $x = $page_width - $footer_padding_right - $text_width;
									   } else { // $horizontal_alignment == "center"
										   $x = ( ( ( $page_width - $footer_padding_right ) - $footer_padding_left ) / 2.0 ) + $footer_padding_left - ( $text_width / 2.0 );
									   }
									   
								   }
								   
								   
						} else { // $position == "custom"
							   $x = floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_page_numbers_custom_x", 0 ) ) );
							   $y = floatval( str_replace( ",", ".", get_option( "wp_wc_invoice_pdf_page_numbers_custom_y", 0 ) ) );  
						}
							   
						// $x and $y are in user_unit (cm or in), we need pt
						if ( $user_unit == "cm" ) {
							$x = 28.3527 * $x;
							$y = 28.3527 * $y;  
						} else { // $user_unit == "in"
							$x = 72.0159 * $x;
							$y = 72.0159 * $y;
						}

						$pdf->text( $x, $y, $text, $font_family, $font_size, $font_color_rgb ); 
						
				   }

			');
	}
</script>
