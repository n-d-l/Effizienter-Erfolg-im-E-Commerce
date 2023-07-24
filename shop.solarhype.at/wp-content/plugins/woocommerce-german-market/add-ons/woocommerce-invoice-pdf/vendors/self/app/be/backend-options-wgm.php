<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Backend_Options_WGM' ) ) {

	/**
	* admin setting page in backend wgm 3.1
	*
	* @class WP_WC_Invoice_Pdf_Backend_Options_WGM
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Backend_Options_WGM {

		/**
		 * @var string
		 * @static
		 */
		public static $font_sizes = array( 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 24, 26, 28, 32, 36 );

		/**
		* Backend Settings German Market 3.1
		*
		* wp-hook woocommerce_de_ui_options_global
		* @param Array $items
		* @return Array
		*/
		public static function menu( $items ) {

			$items[ 200 ] = array( 
				'title'		=> __( 'Invoice PDF', 'woocommerce-german-market' ),
				'slug'		=> 'invoice-pdf',
				
				'submenu'	=> array(

					array(
						'title'		=> __( 'General Pdf Settings', 'woocommerce-german-market' ),
						'slug'		=> 'general_pdf_settings',
						'callback'	=> array( __CLASS__, 'render_menu_general_pdf_settings' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Invoice Content', 'woocommerce-german-market' ),
						'slug'		=> 'invoice_content',
						'callback'	=> array( __CLASS__, 'render_menu_invoice_content' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Refund PDF', 'woocommerce-german-market' ),
						'slug'		=> 'refund_content',
						'callback'	=> array( __CLASS__, 'render_menu_refund_content' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Header', 'woocommerce-german-market' ),
						'slug'		=> 'header',
						'callback'	=> array( __CLASS__, 'render_menu_header' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Footer', 'woocommerce-german-market' ),
						'slug'		=> 'footer',
						'callback'	=> array( __CLASS__, 'render_menu_footer' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Images', 'woocommerce-german-market' ),
						'slug'		=> 'images',
						'callback'	=> array( __CLASS__, 'render_menu_images' ),
						'options'	=> 'yes'
					),

					'girocode' => array(
						'title'		=> __( 'GiroCode', 'woocommerce-german-market' ),
						'slug'		=> 'girocode',
						'callback'	=> array( __CLASS__, 'render_menu_girocode' ),
						'options'	=> 'yes'
					),

					'swiss_qr_invoice' => array(
						'title'		=> __( 'Swiss QR Invoice', 'woocommerce-german-market' ),
						'slug'		=> 'swiss_qr_invoice',
						'callback'	=> array( __CLASS__, 'render_menu_swiss_qr_invoice' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Custom CSS Styles', 'woocommerce-german-market' ),
						'slug'		=> 'custom_css_styles',
						'callback'	=> array( __CLASS__, 'render_menu_custom_css_styles' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Custom Fonts', 'woocommerce-german-market' ),
						'slug'		=> 'custom_fonts',
						'callback'	=> array( __CLASS__, 'render_menu_custom_fonts' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Emails Invoice PDF', 'woocommerce-german-market' ),
						'slug'		=> 'emails',
						'callback'	=> array( __CLASS__, 'render_menu_emails' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'My Account Page', 'woocommerce-german-market' ),
						'slug'		=> 'my_account_page',
						'callback'	=> array( __CLASS__, 'render_menu_my_account_page' ),
						'options'	=> 'yes'
					),
					array(
						'title'		=> __( 'Legal Texts PDFs', 'woocommerce-german-market' ),
						'slug'		=> 'additional_pdfs',
						'callback'	=> array( __CLASS__, 'render_menu_additional_pdfs' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Emails Legal Texts PDFs', 'woocommerce-german-market' ),
						'slug'		=> 'emails_additional_pdfs',
						'callback'	=> array( __CLASS__, 'render_menu_emails_additional_pdfs' ),
						'options'	=> 'yes'
					),

				)
			);

			return $items;
		}

		/**
		* The following static functions call 'get_options_preferences'
		* To get the options as an array and render them
		*/
		public static function render_menu_general_pdf_settings() {
			$settings = self::get_options_preferences( 'general_pdf_settings' );
			return( $settings );
		}

		public static function render_menu_invoice_content() {
			$settings = self::get_options_preferences( 'invoice_content' );
			return( $settings );
		}

		public static function render_menu_refund_content() {
			$settings = self::get_options_preferences( 'refund_content' );
			return( $settings );
		}

		public static function render_menu_header() {
			$settings = self::get_options_preferences( 'header' );
			return( $settings );
		}

		public static function render_menu_footer() {
			$settings = self::get_options_preferences( 'footer' );
			return( $settings );
		}

		public static function render_menu_images() {
			$settings = self::get_options_preferences( 'images' );
			return( $settings );
		}

		public static function render_menu_girocode() {
			$settings = self::get_options_preferences( 'girocode' );
			return( $settings );
		}

		public static function render_menu_swiss_qr_invoice() {
			$settings = self::get_options_preferences( 'swiss_qr_invoice' );
			return( $settings );
		}

		public static function render_menu_custom_fonts() {
			$settings = self::get_options_preferences( 'custom_fonts' );
			return( $settings );
		}

		public static function render_menu_custom_css_styles() {
			$settings = self::get_options_preferences( 'custom_css_styles' );
			return( $settings );
		}

		public static function render_menu_emails() {
			$settings = self::get_options_preferences( 'emails' );
			return( $settings );
		}

		public static function render_menu_my_account_page() {
			$settings = self::get_options_preferences( 'my_account_page' );
			return( $settings );
		}

		public static function render_menu_additional_pdfs() {
			$settings = self::get_options_preferences( 'additional_pdfs' );
			return( $settings );
		}

		public static function render_menu_emails_additional_pdfs() {
			$settings = self::get_options_preferences( 'emails_additional_pdfs' );
			return( $settings );
		}

		/**
		* create admin fields in woocomerce
		*
		* @since 0.0.1
		* @access private
		* @return array $options
		*/
		static function get_options_preferences( $section = NULL ) {
			$user_unit = get_option( 'wp_wc_invoice_pdf_user_unit', 'cm' );
			$options = array();
			if ( $section == '' ) {
				$section = 'general_pdf_settings';	
			}
			$option_section = untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'options-sections' . DIRECTORY_SEPARATOR . 'options-section-' . str_replace( '_', '-', $section ) . '.php';
			if ( file_exists( $option_section ) ) {
				include ( $option_section );
			} 
			$options = apply_filters( 'wp_wc_invoice_pdf_options_section_' . $section, $options );
			return $options;	
		}

		/**
		* Output type wcreapdf_textarea
		*
		* @since 0.0.1
		* @static
		* @access public
		* @hook woocommerce_admin_field_wp_wc_invoice_pdf_textarea
		* @return void
		*/
		public static function output_textarea( $value ) {
			
			// Description handling
			$field_description = WC_Admin_Settings::get_field_description( $value );
			extract( $field_description );

			$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default'] );
			
			// readonly and example html
			$readonly = '';
			if ( isset( $value[ 'custom_attributes' ] ) ) {
				$custom_attributes	= $value[ 'custom_attributes' ];
				if ( isset ( $custom_attributes[ 'readonly' ] ) ) {
					$readonly = ' readonly ';
				}
				if ( isset ( $custom_attributes[ 'return_html' ] ) ) {
					$option_value = $custom_attributes[ 'return_html' ];
				}
			}
			?><tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?></label><?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ) ?>">
					<textarea
						name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						<?php echo $readonly; ?>style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						><?php echo esc_textarea( $option_value );  ?></textarea>
						<br /><p class="description"><?php echo $value[ 'desc' ]; ?></p>
				</td>
			</tr><?php
		}

		/**
		* Output test button
		*
		* @since 0.0.1
		* @static
		* @access public
		* @hook woocommerce_admin_field_wp_wc_invoice_pdf_test_download_button
		* @return void
		*/
		public static function output_test_pdf_button( $value ) {
			
			$subtab = isset( $_REQUEST[ 'sub_tab' ] ) ? esc_attr( $_REQUEST[ 'sub_tab' ] ) : 'x';

			?>
            <table class="test-download">
            	<tr valign="top">
                    <th scope="row" class="titledesc">
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_wp_wc_invoice_pdf_test_invoice&subtab=' . $subtab ), 'wp-wc-invoice-pdf-test-invoice' ); ?>"><input type="button" class="button-primary" value="<?php echo __( 'Download Test Invoice', 'woocommerce-german-market' );?>" /></a>
                    </th>
                    <td></td>
                </tr>
			</table>
			<span style="clear: both; display: block;"></span>
			<?php
		}

		/**
		* Save type wcreapdf_textarea
		*
		* @since 1.0.3
		* @static
		* @access public
		* @hook woocommerce_admin_settings_sanitize_option
		* @return void
		*/
		public static function save_wp_wc_invoice_pdf_textarea_textarea( $value, $option, $raw_value ) {
			
			if ( isset( $option[ 'type'] ) && $option[ 'type' ] == 'wp_wc_invoice_pdf_textarea' ) {
				
				if ( $option[ 'id' ] == 'wp_wc_invoice_pdf_custom_fonts' ) {
					return html_entity_decode( trim( $raw_value ) );
				}

				return html_entity_decode( wp_kses_post( trim( $raw_value ) ) );
			}

			return $value;
		}

		/**
		* Validation for saving file names
		*
		* @since 1.0.3
		* @static
		* @access public
		* @hook woocommerce_admin_settings_sanitize_option
		* @return void
		*/
		public static function save( $value, $option, $raw_value ) {

			if ( $option[ 'id' ] == 'wp_wc_invoice_pdf_file_name_backend' || $option[ 'id' ] == 'wp_wc_invoice_pdf_file_name_frontend' ) {
				
				$file_name_placeholders = apply_filters( 'wp_wc_invoice_pdf_placeholders', array( 'order-number' => __( 'Order number', 'woocommerce-german-market' ) ) );
				$search		= array();
				$replace	= array();
				
				foreach( $file_name_placeholders as $key => $value_placeholder ) {
					$search[] 	= '{{' . $key . '}}';
					$replace[]	= 'START__SEPARATOR__START' . $key . 'END__SPARATOR__END';
				}
				
				$file_name = str_replace( $search, $replace, $value );
				$file_name = sanitize_file_name( preg_replace("([^\w\s\d\-_~,;:\[\]\(\)])", '', $file_name ) );
				$file_name = str_replace( $replace, $search, $file_name );
				$value = $file_name;

			}

			return $value;

		}

	}

}
