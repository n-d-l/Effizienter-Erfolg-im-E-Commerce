<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Helper' ) ) {
	
	/**
	* library of helper functions
	*
	* @WP_WC_Invoice_Pdf_Helper
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Helper {
		
		/**
		* get all core fonts, additional fonts and custom fonts (custom fonts are only avaiable if allow_url_fopen is enabled)
		*
		* @since 0.0.1
		* @access public
		* @static
		* @return array keys are font names, values are empty for core & additional fonts and link code for google fonts
		*/
		public static function get_fonts() {
			
			// core fonts
			$fonts 				= array( 'Times' => '', 'Helvetica' => '', 'Courier' => '', 'DejaVu Sans' => '', 'DejaVu Serif' => '', 'DejaVu Sans Mono' => '' );
			
			// add additional fonts
			$additional_fonts	= apply_filters( 'wp_wc_invoice_pdf_additional_fonts', array() );
			if ( count( $additional_fonts ) > 0 ) {
				$empty_string		= array_fill( 0, count( $additional_fonts ), '' );
				$additional_fonts	= array_combine( $additional_fonts, $empty_string );			
				$fonts = array_merge( $fonts, $additional_fonts );
			}
			
			// custom fonts
			$allow_url_fopen	= ini_get( 'allow_url_fopen' );
			if ( $allow_url_fopen ) {
				$custom_fonts		= get_option( 'wp_wc_invoice_pdf_custom_fonts' );
				$custom_fonts		= nl2br( $custom_fonts, false );
				$custom_fonts_array	= explode( '<br>', $custom_fonts );
				foreach( $custom_fonts_array as $custom_font ) {
					if ( trim( $custom_font ) == '' ) {
						continue;	
					}
					$custom_font = str_replace( array( 'http://', 'https://' ), '//', $custom_font );
					$custom_font = str_replace( "\"", "'", $custom_font );
					// search name
					$searchArray	= explode( 'family=', $custom_font, 2 );
					
					if ( isset( $searchArray[ 1 ] ) ) {
						$start_string	= $searchArray[ 1 ];
						$start_string	= str_replace( array( ":", "&" ), "'", $start_string );
						$searchArray	= explode( "'", $start_string, 2 );
						$font_name		= $searchArray[ 0 ];
						$font_name		= str_replace( array( '+' ), ' ', $font_name );
						// add to custom font to fonts
						$fonts[ $font_name ] = $custom_font;
					}
				}
			}
			ksort( $fonts );		
			return $fonts;
		}
		
	} // end class
	
} // end if

?>