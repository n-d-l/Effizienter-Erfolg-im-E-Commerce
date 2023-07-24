<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Backend_Notices' ) ) {

	/**
	* adds admin notices to setting fields that are overridden by this plugin
	*
	* @class WP_WC_Running_Invoice_Number_Backend_Notices
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Backend_Notices {
		
		/**
		* add notices to setting fields for customer invoice email
		*
		* @since 0.0.1
		* @arguments array $setting_fields
		* @access public
		* @static
		* @hook woocommerce_settings_api_form_fields_customer_invoice
		* @return array
		*/
		public static function add_email_notices_to_woocommerce_setting_fields( $setting_fields ) {
			
			// notice		
			$url  = admin_url() . 'admin.php?page=wc-settings&tab=preferences-wp-wc-running-invoice-number';
			$here = __( 'Settings page', 'woocommerce-german-market' );		
			$notice = '<br /><b>' . __( 'This setting has been overridden by the add-on "WooCommerce Running Invoice Number" and can be found here:', 'woocommerce-german-market' ) . ' <a href="' . $url . '">' . $here . '</a></b>'; 
			
			// add the notice to these setting_fields
			$keys 	= array( 'subject', 'heading', 'subject_paid', 'heading_paid' ); 
			
			// add the notice			
			foreach( $setting_fields as $key => $value ) {
				if ( in_array( $key, $keys ) ) {
					$setting_fields[ $key ][ 'description' ] .= $notice;
				}
			}
				
			return $setting_fields;	
		}
		
		/**
		* add notices to setting fields for wp_wc_invoice_pdf
		*
		* @since 0.0.1
		* @arguments array $setting_fields
		* @access public
		* @static
		* @hook wp_wc_invoice_pdf_options_section_general_pdf_settings
		* @hook wp_wc_invoice_pdf_options_section_invoice_content
		* @hook wp_wc_invoice_pdf_options_section_refund_content
		* @return array
		*/
		public static function add_notices_to_wp_wc_invoice_pdf_setting_fields( $setting_fields ) {
			
			// notice		
			$url  = admin_url() . 'admin.php?page=german-market&tab=preferences-wp-wc-running-invoice-number&sub_tab=invoice_pdf';
			$refund_url = admin_url() . 'admin.php?page=german-market&tab=preferences-wp-wc-running-invoice-number&sub_tab=refund_pdf';

			$here = __( 'Settings page', 'woocommerce-german-market' );

			$notice = '<br /><b>' . __( 'This setting has been overridden by the add-on "WooCommerce Running Invoice Number" and can be found here:', 'woocommerce-german-market' ) . ' <a href="' . $url . '">' . $here . '</a></b>';
			$refund_notice = '<br /><b>' . __( 'This setting has been overridden by the add-on "WooCommerce Running Invoice Number" and can be found here:', 'woocommerce-german-market' ) . ' <a href="' . $refund_url . '">' . $here . '</a></b>';
			
			// add the notice to these setting_fields
			$ids 	= array( 'wp_wc_invoice_pdf_file_name_backend', 'wp_wc_invoice_pdf_file_name_frontend', 'wp_wc_invoice_pdf_invoice_start_subject', 'wp_wc_invoice_pdf_refund_file_name_backend', 'wp_wc_invoice_pdf_refund_file_name_frontend', 'wp_wc_invoice_pdf_refund_start_subject_big', 'wp_wc_invoice_pdf_refund_start_subject_small' ); 
			
			// add the notice			
			foreach( $setting_fields as $key => $value ) {

				if ( isset( $value[ 'id' ] ) ) {
					if ( in_array( $value[ 'id' ], $ids ) ) {
						
						if ( ! isset( $setting_fields[ $key ][ 'desc' ] ) ) {
							$setting_fields[ $key ][ 'desc' ] = '';
						}

						if ( str_replace( 'refund', '', $value[ 'id' ] ) != $value[ 'id' ] ) {
							$setting_fields[ $key ][ 'desc' ] .= $refund_notice;
						} else {
							$setting_fields[ $key ][ 'desc' ] .= $notice;
						}
						
					}
				}
			}
				
			return $setting_fields;	
		}
		
	} // end class
	
} // end if class exists ?>