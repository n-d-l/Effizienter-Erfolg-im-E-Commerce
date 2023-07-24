<?php

use Dompdf\Dompdf;

/**
* this abstract class uses DOMPDF class to render html to pdf files
* abstract methods: get_template_dir, get_option, get_template_part_header, get_template_part_footer, get_template_part_main
* maybe you need to overload: __construct, needs_user_unit
*
* @class 	Ebs_Pdf
* @version	1.0
* @category	Class
* @requires class DOMPDF
* @abstract
*/
abstract class Ebs_Pdf {
	
	 /**
	 * @var string 
	 * prefix for options, database, etc.
	 */
	private $prefix;
	
	 /**
	 * @var string
	 * units used in pdf, cm or in
	 */
	public $user_unit;
	
	/**
	 * @var string
	 * paper size 'A4' or 'letter'
	 */
	public $paper_size;
	
	 /**
	 * @var Class DOMPDF
	 */
	public $pdf;
	
	/**
	* constructor, creates DOMPDF object, set prefix and user_unit, 
	* perhaps, you need to overload constructor
	*
	* @since 0.0.1
	* @access public
	* @return void
	*/		
	public function __construct() {
		$this->pdf 						= new Dompdf();
		$this->prefix 					= 'ebs_pdf_';			// or '' or whatever you want
		$this->paper_size				= 'A4';					// or 'letter'
		$this->pdf->set_paper( $this->paper_size, 'portrait' );
		$this->user_unit				= 'cm';					// or 'in'
	}
	
	/**
	* set user_unit
	*
	* @since 0.0.1
	* @access public
	* @arguments string $user_unit ('cm', 'in' - 'px' or sth else is possible, but in case of creating a pdf useless)
	* @return void
	*/
	public function set_user_unit( $user_unit ) {
		$this->user_unit = $user_unit;	
	}

	/**
	* get tamplate content with placeholders
	*
	* @since 0.0.1
	* @access public
	* @arguments string $template_path, array $args
	* @static
	* @return string
	*/	
	public static function get_template( $template_path, $args = array() ){
		$html = file_get_contents ( $template_path );
		// no default css at all
		if ( ( isset( $args[ 'remove_css_style' ] ) ) && ( $args[ 'remove_css_style' ] == true ) ) {
			$html = preg_replace( '#(<style.*?>)(.*)(</style>)#si', '$1[[custom-css]]$3', $html );
		}
		return $html;
	}
	
	/**
	* get tamplate content with placeholders by template name
	*
	* @since 0.0.1
	* @access public
	* @arguments string $template_name, array $args
	* @return string
	*/	
	public function get_template_by_template_name( $template_name = 'default', $args = array() ) {
		$file_contents	= NULL;
		$current_dir	= getcwd();
		$template_dir	= $this->get_template_dir();
		$file			= $template_dir . DIRECTORY_SEPARATOR . $template_name . '.html';
		$change_dir		= chdir( $template_dir );
		if ( $change_dir ) {
			if ( file_exists( $file ) ) {
				$file_contents = self::get_template( $file, $args );
			}
			$change_dir = chdir( $current_dir );
		} 
		return $file_contents;	
	}
	
	/**
	* get tamplate dir (path)
	*
	* @since 0.0.1
	* @access public
	* @arguments string $template_name
	* @static
	* @abstract
	* @return string
	*/
	abstract public function get_template_dir();
	
	/**
	* get placeholders
	*
	* @since 0.0.1
	* @access public
	* @static
	* @return array
	*/
	public function get_template_placeholders_and_defaults() {
		return 	array( 
				// document title
				'[[document-title]]'						=> 'PDF',
				
				// fonts
				'[[fonts]]'									=> '',
				
				// body
				'[[body-margin-top]]'						=> '0',
				'[[body-margin-right]]'						=> '0',
				'[[body-margin-bottom]]'					=> '0',
				'[[body-margin-left]]'						=> '0',
				'[[body-width]]'							=> ( $this->paper_size == 'A4' ) ? '21.0cm' : '8.5in',
				'[[body-color]]'							=> '#000',
				'[[body-background]]'						=> 'none',
				'[[body-font-family]]'						=> 'Helvetica',
				'[[body-font-size]]'						=> 'medium',
				'[[body-font-weight]]'						=> 'normal',
				'[[body-font-style]]'						=> 'normal',
				'[[body-text-decoration]]'					=> 'none',
				'[[body-custom-css]]'						=> '',
				
				// header
				'[[header-width]]'							=> ( $this->paper_size == 'A4' ) ? '21.0cm' : '8.5in',
				'[[header-padding-top]]'					=> '0',
				'[[header-padding-right]]'					=> '0',
				'[[header-padding-bottom]]'					=> '0',
				'[[header-padding-left]]'					=> '0',
				'[[header-height]]'							=> '0',
				'[[header-background]]'						=> 'none',
				'[[header-color]]'							=> 'inherit',
				'[[header-font-family]]'					=> 'inherit',
				'[[header-font-size]]'						=> 'inherit',
				'[[header-font-weight]]'					=> 'inherit',
				'[[header-font-style]]'						=> 'inherit',
				'[[header-text-decoration]]'				=> 'inherit',
				'[[header-text-align]]'						=> 'inherit',			
				'[[header-custom-css]]'						=> '',
				
				// footer
				'[[footer-width]]'							=> ( $this->paper_size == 'A4' ) ? '21.0cm' : '8.5in',
				'[[footer-padding-top]]'					=> '0',
				'[[footer-padding-right]]'					=> '0',
				'[[footer-padding-bottom]]'					=> '0',
				'[[footer-padding-left]]'					=> '0',
				'[[footer-height]]'							=> '0',
				'[[footer-background]]'						=> 'none',
				'[[footer-color]]'							=> 'inherit',
				'[[footer-font-family]]'					=> 'inherit',
				'[[footer-font-size]]'						=> 'inherit',
				'[[footer-font-weight]]'					=> 'inherit',
				'[[footer-font-style]]'						=> 'inherit',
				'[[footer-text-decoration]]'				=> 'inherit',
				'[[footer-text-align]]'						=> 'inherit',
				'[[footer-custom-css]]'						=> '',
				
				// background-color
				'[[background-color-background]]'			=> 'none',
				'[[background-color-width]]'				=> ( $this->paper_size == 'A4' ) ? '21.0cm' : '8.5in',
				'[[background-color-height]]'				=> ( $this->paper_size == 'A4' ) ? '29.7cm' : '11in',
				'[[background-color-custom-css]]'			=> '',
				
				// h1
				'[[h1-color]]'								=> 'inherit',
                '[[h1-family]]'								=> 'inherit',
                '[[h1-font-size]]'							=> 'xx-large',
                '[[h1-font-weight]]'						=> 'bold',
				'[[h1-font-style]]'							=> 'inherit',
				'[[h1-text-decoration]]'					=> 'inherit',
				'[[h1-custom-css]]'							=> '',
				
				// h2
				'[[h2-color]]'								=> 'inherit',
                '[[h2-family]]'								=> 'inherit',
                '[[h2-font-size]]'							=> 'x-large',
                '[[h2-font-weight]]'						=> 'bold',
				'[[h2-font-style]]'							=> 'inherit',
				'[[h2-text-decoration]]'					=> 'inherit',
				'[[h2-custom-css]]'							=> '',
				
				// h3
				'[[h3-color]]'								=> 'inherit',
                '[[h3-family]]'								=> 'inherit',
                '[[h3-font-size]]'							=> 'large',
                '[[h3-font-weight]]'						=> 'bold',
				'[[h3-font-style]]'							=> 'inherit',
				'[[h3-text-decoration]]'					=> 'inherit',
				'[[h3-custom-css]]'							=> '',
				
				// h4
				'[[h4-color]]'								=> 'inherit',
                '[[h4-family]]'								=> 'inherit',
                '[[h4-font-size]]'							=> 'medium',
                '[[h4-font-weight]]'						=> 'bold',
				'[[h4-font-style]]'							=> 'inherit',
				'[[h4-text-decoration]]'					=> 'inherit',
				'[[h4-custom-css]]'							=> '',
				
				// h5
				'[[h5-color]]'								=> 'inherit',
                '[[h5-family]]'								=> 'inherit',
                '[[h5-font-size]]'							=> 'small',
                '[[h5-font-weight]]'						=> 'bold',
				'[[h5-font-style]]'							=> 'inherit',
				'[[h5-text-decoration]]'					=> 'inherit',
				'[[h5-custom-css]]'							=> '',
				
				// h6
				'[[h6-color]]'								=> 'inherit',
                '[[h6-family]]'								=> 'inherit',
                '[[h6-font-size]]'							=> 'x-small',
                '[[h6-font-weight]]'						=> 'bold',
				'[[h6-font-style]]'							=> 'inherit',
				'[[h6-text-decoration]]'					=> 'inherit',
				'[[h6-custom-css]]'							=> '',								
				
				// p
				'[[p-color]]'								=> 'inherit',
                '[[p-family]]'								=> 'inherit',
                '[[p-font-size]]'							=> 'inherit',
                '[[p-font-weight]]'							=> 'inherit',
				'[[p-font-style]]'							=> 'inherit',
				'[[p-text-decoration]]'						=> 'inherit',
				'[[p-custom-css]]'							=> '',
				
				// span
				'[[span-color]]'							=> 'inherit',
                '[[span-family]]'							=> 'inherit',
                '[[span-font-size]]'						=> 'inherit',
                '[[span-font-weight]]'						=> 'inherit',
				'[[span-font-style]]'						=> 'inherit',
				'[[span-text-decoration]]'					=> 'inherit',
				'[[span-custom-css]]'						=> '',
				
				// custom css
				'[[custom-css]]'							=> '',
				
				// template parts
				'[[header]]'									=> '',
				'[[footer]]'									=> '',
				'[[main]]'									=> '',
				'[[background]]'							=> ''
			);
	}
	
	/**
	* true if option (a css property) needs user_unit
	* perhaps, you want to overload this method
	* not used in this abstract class, you should use it in your implementation of 'get_option'
	*
	* @since 0.0.1
	* @access public
	* @arguments string $search_array
	* @return boolean (true if css property needs user_unit)
	*/	
	public function needs_user_unit( $option_name, $option_value ) {
		
		if ( ! is_numeric( $option_value ) || in_array( $option_name, array( 'background_color_width', 'background_color_height' ) ) ) {
			return false;	
		}
		
		// if the value already got an unit don't add another one
		if ( ( (string) str_replace( array( 'cm', 'in' ), '', $option_value ) ) != ( (string) $option_value ) ) {
			return false;	
		}
		
		$option_name = str_replace( array ('span_', 'p_', 'h1_', 'h2_', 'h3_', 'h4_', 'h5_', 'h6_', 'background_color_',  'footer_', 'header_', 'body_' ), '', $option_name );
		
		$needs_user_unit = array( 'height', 'width', 'padding_top', 'padding_right', 'padding_bottom', 'padding_left', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left');
		
		return in_array( $option_name, $needs_user_unit );			
	}
	
	/**
	* get placeholders
	*
	* @since 0.0.1
	* @access public
	* @arguments string $search_array
	* @static
	* @return array
	*/	
	public function get_template_placeholders() {
		return array_keys( $this->get_template_placeholders_and_defaults() );
	}
	
	/**
	* get the sanitized option_name
	*
	* @since 0.0.1
	* @access public
	* @arguments string $option_name
	* @static
	* @return string
	*/	
	public static function sanitize_option_name( $option_name ) {
		$option_name = strtolower( str_replace( '-', '_', $option_name ) );
		if ( function_exists( 'sanitize_title' ) ) {	// wordpress is running
			return sanitize_title( $option_name );		
		} else {
			return filter_var( $option_name, FILTER_SANITIZE_SPECIAL_CHARS );
		}
	}
	
	/**
	* load an option by its key ($option_name)
	*
	* @since 0.0.1
	* @access public
	* @arguments string $option_name, string $default, array $args
	* @return void
	*/	
	public function load_option( $option_name, $default = '', $args = array() ) {
		// get option name
	
		if( in_array( $option_name, array( 'header', 'footer', 'main', 'fonts', 'background' ) ) ) {
				return call_user_func( array( $this, 'get_template_part_' . $option_name ), $args );
		}
		
		// user_unit
		if( $option_name == 'user_unit' ) {
			return $this->user_unit;	
		}
		
		// sanitize option name		
		$option_name = self::sanitize_option_name( $option_name );
		
		// get option
		return $this->get_option( $option_name, $default );
	}
	
	/**
	* get options that are replaces by placeholders
	*
	* @since 0.0.1
	* @access public
	* @arguments array $args
	* @static
	* @return array
	*/	
	public function get_template_replace_array( $args = array() ) {
		$placeholders_and_defaults 	= $this->get_template_placeholders_and_defaults();
		$options					= array();
		foreach( $placeholders_and_defaults as $placeholder => $default ) {
			$option_name	= str_replace( array( '[[', ']]' ), '', $placeholder );
			$options[]		= $this->load_option( $option_name, $default, $args );	
		}
		return $options;
	}	
	
	/**
	* get pdf_content (template placeholders are replaced by saved options or defaults)
	*
	* @since 0.0.1
	* @access public
	* @arguments string $template_name, array $args
	* @static
	* @return string
	*/
	public function get_pdf_content( $template_name = 'default', $args = array() ) {
		$html_with_placeholders = $this->get_template_by_template_name( $template_name, $args );
		$html = str_replace( $this->get_template_placeholders(), $this->get_template_replace_array( $args ), $html_with_placeholders );
		$html = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $html );
		return $html;
	}
	
	/**
	* get option
	* you should yous $this->user_unit for some options when implementing this method
	*
	* @since 0.0.1
	* @access public
	* @arguments string $option_name, $default
	* @abstract
	* @return string
	*/	
	abstract public function get_option( $option_name, $default = '' );

	/**
	* get template parts for header
	*
	* @since 0.0.1
	* @access public
	* @abstract
	* @arguments array $args
	* @return string
	*/
	abstract public function get_template_part_header( $args = array() );
	
	/**
	* get template parts for footer
	*
	* @since 0.0.1
	* @access public
	* @abstract
	* @arguments array $args
	* @return string
	*/
	abstract public function get_template_part_footer( $args = array() );
	
	/**
	* get template parts for main part
	*
	* @since 0.0.1
	* @access public
	* @abstract
	* @arguments array $args
	* @return string
	*/
	abstract public function get_template_part_main( $args = array() );
	
	/**
	* get template parts for fonts
	*
	* @since 0.0.1
	* @access public
	* @abstract
	* @arguments array $args
	* @return string
	*/
	abstract public function get_template_part_fonts( $args = array() );
	
	/**
	* get template parts for background
	*
	* @since 0.0.1
	* @access public
	* @abstract
	* @arguments array $args
	* @return string
	*/
	abstract public function get_template_part_background( $args = array() );
}
?>