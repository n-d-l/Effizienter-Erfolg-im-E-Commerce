<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Backend_Options_WGM' ) ) {

	/**
	* admin setting page in backend wgm 3.1
	*
	* @class WP_WC_Invoice_Pdf_Backend_Options_WGM
	* @version 1.0
	* @category	Class
	*/
	class WP_WC_Running_Invoice_Number_Backend_Options_WGM {

		/**
		* Backend Settings German Market 3.1
		*
		* wp-hook woocommerce_de_ui_options_global
		* @param Array $items
		* @return Array
		*/
		public static function menu( $items ) {

			$items[ 190 ] = array( 
				'title'		=> __( 'Invoice Number', 'woocommerce-german-market' ),
				'slug'		=> 'preferences-wp-wc-running-invoice-number',
				
				'submenu'	=> array(

					array(
						'title'		=> __( 'Collocation of Invoice Numbers', 'woocommerce-german-market' ),
						'slug'		=> 'collocation',
						'callback'	=> array( __CLASS__, 'render_menu_collocation' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Automatic Generation', 'woocommerce-german-market' ),
						'slug'		=> 'generation',
						'callback'	=> array( __CLASS__, 'render_menu_generation' ),
						'options'	=> 'yes'
					),

					array(
						'title'		=> __( 'Emails', 'woocommerce-german-market' ),
						'slug'		=> 'emails',
						'callback'	=> array( __CLASS__, 'render_menu_emails' ),
						'options'	=> 'yes'
					),

					'invoice_pdf' => array(
						'title'		=> __( 'Invoice PDF', 'woocommerce-german-market' ),
						'slug'		=> 'invoice_pdf',
						'callback'	=> array( __CLASS__, 'invoice_pdf' ),
						'options'	=> 'yes'
					),

					'refund_pdf' => array(
						'title'		=> __( 'Refund PDF', 'woocommerce-german-market' ),
						'slug'		=> 'refund_pdf',
						'callback'	=> array( __CLASS__, 'refund_pdf' ),
						'options'	=> 'yes'
					),

				)
			);

			if ( ! Woocommerce_Running_Invoice_Number::is_wp_wc_invoice_pdf_activated() ) {
				unset( $items[ 190 ][ 'submenu' ][ 'invoice_pdf' ] );
				unset( $items[ 190 ][ 'submenu' ][ 'refund_pdf' ] );
			}

			return $items;

		}

		/**
		* Render Options for invoice_pdf
		* 
		* @access public
		* @return void
		*/
		public static function refund_pdf() {

			$settings = array();

			$placeholders_string_refunded = apply_filters( 'wp_wc_running_invoice_number_placeholder_refund_pdf', __( 'You can use the following placeholders: Refund number - <code>{{refund-number}}</code>, Refund ID - <code>{{refund-id}}</code>, Refund date - <code>{{refund-date}}</code>, Order number - <code>{{order-number}}</code>, Invoice number - <code>{{invoice-number}}</code>, Order date - <code>{{order-date}}</code>, Invoice date - <code>{{invoice-date}}</code>', 'woocommerce-german-market' ) );

			$settings[]	= array( 'title' => __( 'Refund PDF', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => '<span class="desc">' . $placeholders_string_refunded . '</span>', 'id' => 'wp_wc_running_invoice_number_invoice_pdf_refund' );

			$settings[] = array(
					'name' 		=> __( 'File Name in Backend', 'woocommerce-german-market' ),
					'desc' 		=> '.pdf',
					'desc_tip' 	=> __( 'Choose the invoice file name used in backend', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_file_name_backend_refund',
					'type' 		=> 'text',
					'default' 	=> __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
					'class'		=> 'german-market-unit',
				);
				
			$settings[] = array(
					'name' 		=> __( 'File Name in Frontend', 'woocommerce-german-market' ),
					'desc' 		=> '.pdf',
					'desc_tip'	=> __( 'Choose the invoice file name used in frontend for your customer', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_file_name_frontend_refund',
					'type' 		=> 'text',
					'default' 	=> __( 'Refund-{{refund-number}}-for-order-{{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
					'class'		=> 'german-market-unit',
				);

			$settings[] = array(
					'name' 		=> __( 'Subject line 1 (big)', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_refund_start_subject_big',
					'type' 		=> 'text',
					'default'  	=> __( 'Refund {{refund-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);

			$settings[] = array(
					'name' 		=> __( 'Subject line 2 (small)', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'This line has the same font-size as the general text', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_refund_start_subject_small',
					'type' 		=> 'text',
					'default'  	=> __( 'For order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);

			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_invoice_pdf_refund' );

			$settings = apply_filters( 'wp_wc_running_invoice_number_options_invoice', $settings );
			return( $settings );
		}

		/**
		* Render Options for invoice_pdf
		* 
		* @access public
		* @return void
		*/
		public static function invoice_pdf() {

			$settings = array();

			$placeholders_string = apply_filters( 'wp_wc_running_invoice_number_placeholder_invoice_pdf', __( 'You can use the following placeholders: Order number - <code>{{order-number}}</code>, Invoice number - <code>{{invoice-number}}</code>, Order date - <code>{{order-date}}</code>, Invoice date - <code>{{invoice-date}}</code>, Payment method - <code>{{payment-method}}</code>', 'woocommerce-german-market' ) );

			$custom_placeholders = apply_filters( 'wp_wc_invoice_pdf_placeholders', array() );
			if ( ! empty( $custom_placeholders ) ) {
				$custom_placeholders_string = '';
				foreach ( $custom_placeholders as $key => $value ) {
					$custom_placeholders_string .= ', ' . $value . ' - <code>{{' . $key . '}}</code>'; 
				}
				$placeholders_string .= $custom_placeholders_string;
			}
			
			$settings[]	= array( 'title' => __( 'Invoice PDF', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => '<span class="desc">' . $placeholders_string . '</span>', 'id' => 'wp_wc_running_invoice_number_invoice_pdf' );
			
			$settings[] = array(
					'name'		=> __( 'Activation', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Override file names and the subject of the invoice with the following three settings', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Override file names and the subject of the invoice pdf with the following three settings', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_activation',
					'type' 		=> 'wgm_ui_checkbox',
					'default'  	=> 'on',
				);
			
			$settings[] = array(
					'name' 		=> __( 'File Name in Backend', 'woocommerce-german-market' ),
					'desc' 		=> '.pdf',
					'desc_tip'	=> __( 'Choose the invoice file name used in backend', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_file_name_backend',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice-{{invoice-number}}-Order-{{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
					'class'		=> 'german-market-unit',
				);
				
			$settings[] = array(
					'name' 		=> __( 'File Name in Frontend', 'woocommerce-german-market' ),
					'desc' 		=> '.pdf',
					'desc_tip'	=> __( 'Choose the invoice file name used in frontend for your customer', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_file_name_frontend',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice-{{invoice-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
					'class'		=> 'german-market-unit',
				);	
				
			$settings[] = array(
					'name' 		=> __( 'Subject', 'woocommerce-german-market' ),
					'desc_tip'	=> __( 'Choose the subject in the invoice pdf', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_subject',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Invoice Date', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'The invoice date can be outputed right to the subject in a smaller font size, text aligned right.', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_pdf_date',
					'type' 		=> 'wp_wc_running_invoice_number_textarea',
					'default' 	=> __( 'Invoice Date<br />{{invoice-date}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px; height: 110px; resize: none;',
				);		
			
			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_invoice_pdf' );

			$settings = apply_filters( 'wp_wc_running_invoice_number_options_invoice', $settings );
			return( $settings );

		}

		/**
		* Render Options for render_menu_emails
		* 
		* @access public
		* @return void
		*/
		public static function render_menu_emails() {
			
			$settings = array();

			// Email Customer Invoice
			$placeholders_string_refunded = __( 'You can use the following placeholders: Order number - <code>{{order-number}}</code>, Invoice number - <code>{{invoice-number}}</code>, Order date - <code>{{order-date}}</code>, Invoice date - <code>{{invoice-date}}</code>, Site title - <code>{{site-title}}</code>, Refund number - <code>{{refund-number}}</code>, Refund ID - <code>{{refund-id}}</code>, Refund date - <code>{{refund-date}}</code>', 'woocommerce-german-market' );
			
			$settings[]	= array( 'title' => __( 'Email Customer Invoice', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => '<span class="desc">' . $placeholders_string_refunded . '</span>', 'id' => 'wp_wc_running_invoice_number_email' );
			
			$settings[] = array(
					'name'		=> __( 'Activation', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Override the subject and the header of the emails "Customer Invoice" and "Completed order" with the following four settings', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Override the subject and the header of the emails "Customer Invoice" and "Completed order" with the following four settings', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_activation',
					'type' 		=> 'wgm_ui_checkbox',
					'default'  	=> 'on',
				);
			
			$settings[] = array(
					'name' 		=> __( 'Email subject', 'woocommerce-german-market' ) . ': ' . __( 'Customer Completed Order', 'woocommerce-german-market' ),
					'desc_tip'	=> __( 'Choose the subject of the email "Customer Completed Order"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_completed_order_email_subject',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}} from ({{order-date}})', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Email Header', 'woocommerce-german-market' ) . ': ' . __( 'Customer Completed Order', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the header of the email "Customer Completed Order"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_completed_order_email_header',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);

			$settings[] = array(
					'name' 		=> __( 'Email subject', 'woocommerce-german-market' ) . ': ' . __( 'Customer Invoice', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the subject of the email "Customer Invoice"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_subject',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}} from {{order-date}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Email Header', 'woocommerce-german-market' ) . ': ' . __( 'Customer Invoice', 'woocommerce-german-market' ),
					'desc_tip'	=> __( 'Choose the header of the email "Customer Invoice"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_header',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Email subject', 'woocommerce-german-market' ) . ': ' . __( 'Customer Invoice (paid)', 'woocommerce-german-market' ),
					'desc_tip'	=> __( 'Choose the subject of the email "Customer Invoice (paid)"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_subject_paid',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}} from {{order-date}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Email Header', 'woocommerce-german-market' ) . ': ' . __( 'Customer Invoice (paid)', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the header of the email "Customer Invoice (paid)"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_header_paid',
					'type' 		=> 'text',
					'default' 	=> __( 'Invoice {{invoice-number}} for order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);

			$settings[] = array(
					'name' 		=> __( 'Email subject', 'woocommerce-german-market' ) . ': ' . __( 'Refunded Order', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the subject of the email "Refunded Order"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_subject_refunded',
					'type' 		=> 'text',
					'default' 	=> __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Email Header', 'woocommerce-german-market' ) . ': ' . __( 'Refunded Order', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the header of the email "Refunded Order"', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_email_header_refunded',
					'type' 		=> 'text',
					'default' 	=> __( 'Refund {{refund-number}} for order {{order-number}}', 'woocommerce-german-market' ),
					'css'      	=> 'width: 400px;',
				);
			
			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_email' );

			$settings = apply_filters( 'wp_wc_running_invoice_number_options_emails', $settings );
			return( $settings );
		}

		/**
		* Render Options for automatic_generation
		* 
		* @access public
		* @return void
		*/
		public static function render_menu_generation() {

			$settings = array();

			// Automatic Generation
			$automatic_generation_desc = __( 'An invoice number is generated for an order exactly when it is to be outputed for the first time. The output can be in e-mails or the invoice PDF, depending on the settings in the tabs "Emails" and "Invoice PDF" of this menu. If the setting below is activated, an invoice number is automatically generated when an order is received in the store and created by WooCommerce.', 'woocommerce-german-market' );

			$settings[]	= array( 'title' => __( 'Automatic Generation', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => $automatic_generation_desc, 'id' => 'wp_wc_running_invoice_number_automatic_generation' );
			
			$settings[] = array(
					'name' 		=> __( 'New Order', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Activate this option to generate the invoice number and date already when the order is created', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Activate this option to generate the invoice number and date already when the order is created', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_number_generate_when_order_is_created',
					'type' 		=> 'wgm_ui_checkbox',
					'default' 	=> 'off',
				);
		
			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_automatic_generation' );

			$settings = apply_filters( 'wp_wc_running_invoice_number_options_automatic_generation', $settings );
			return( $settings );

		}

		/**
		* Render Options for e_mail
		* 
		* @access public
		* @return void
		*/
		public static function render_menu_collocation() {

			// maybe reset invoice number
			WP_WC_Running_Invoice_Number_Functions::reset_number();

			$settings = array();

			if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
				if ( is_multisite() ) {
					if ( isset( $_REQUEST[ 'wp_wc_running_invoice_number_multisite_global' ] ) ) {
						update_site_option( 'wp_wc_running_invoice_number_multisite_global', 'yes' );
					} else {
						update_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' );
					}
				}
			}

			$next_running_invoice_number = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next', 1 ) : get_option( 'wp_wc_running_invoice_number_next', 1 );
			update_option( 'wp_wc_running_invoice_number_next', $next_running_invoice_number ); 

			$next_running_refund_number = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next_refund', 1 ) : get_option( 'wp_wc_running_invoice_number_next_refund', 1 );
			update_option( 'wp_wc_running_invoice_number_next_refund', $next_running_refund_number ); 

			if ( is_multisite() ) {

				update_option( 'wp_wc_running_invoice_number_multisite_global', get_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) );

				$settings[]	= array( 'title' => __( 'Multisite', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => '', 'id' => 'wp_wc_running_invoice_number_multisite' );

				$settings[] = array(
					'name' 		=> __( 'Global Running Number', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Enable this checkbox if you want to use a unique running number for of all your sites of your multisite installation', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Enable this checkbox if you want to use a unique running number for of all your sites of your multisite installation', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_number_multisite_global',
					'type' 		=> 'wgm_ui_checkbox',
					'default' 	=> 'no',
				);

				$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_multisite' );
			}

			// Collocation of Invoice Numbers
			$settings[]	= array( 'title' => __( 'Collocation of Invoice Numbers', 'woocommerce-german-market' ), 'type' => 'title', 'desc' => '', 'id' => 'wp_wc_running_invoice_number_collocation' );
			
			$settings[] = array(
					'name' 		=> __( 'Prefix', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose a fix prefix that is used before the running number', 'woocommerce-german-market' ),
					'desc'  	=> apply_filters( 'wp_wc_running_invoice_number_placeholder_desc', __( 'You can use the following placeholders: <code>{{year}}</code> (four-digit), <code>{{year-2}}</code> (double-digit), <code>{{month}}</code>, <code>{{day}}</code>', 'woocommerce-german-market' ) ),
					'id'   		=> 'wp_wc_running_invoice_number_prefix',
					'type' 		=> 'text',
					'default' 	=> '',
					'css'      	=> 'width: 300px;',
				);
				
			$settings[] = array(
					'name' 		=> __( 'Number of Digits', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose of how many digits the running number at least consists of, enter an integer, missing digits will be filled with zero', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_number_digits',
					'type' 		=> 'text',
					'default' 	=> '0',
					'css'      	=> 'width: 100px;',
				);	
				
			$settings[] = array(
					'name' 		=> __( 'Suffix', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose a fix suffix that is used after the running number', 'woocommerce-german-market' ),
					'desc'  	=> apply_filters( 'wp_wc_running_invoice_number_placeholder_desc', __( 'You can use the following placeholders: <code>{{year}}</code> (four-digit), <code>{{year-2}}</code> (double-digit), <code>{{month}}</code>, <code>{{day}}</code>', 'woocommerce-german-market' ) ),
					'id'   		=> 'wp_wc_running_invoice_number_suffix',
					'type' 		=> 'text',
					'default' 	=> '',
					'css'      	=> 'width: 300px;',
				);	

			$settings[] = array(
					'name' 		=> __( 'Next Number', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the running number that shall be used next time a running invoice number is generated, enter an integer', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_number_next',
					'type' 		=> 'text',
					'default' 	=> absint( $next_running_invoice_number ),
					'css'      	=> 'width: 100px;',
				);

			$example = get_option( 'wp_wc_running_invoice_number_prefix' ) . str_pad( $next_running_invoice_number, intval( get_option( 'wp_wc_running_invoice_number_digits' ) ), '0', STR_PAD_LEFT ) . get_option( 'wp_wc_running_invoice_number_suffix' );

			// Change Placeholdes
			$placeholder_date_time = new DateTime();
			$search 		= array( '{{year}}', '{{year-2}}', '{{month}}', '{{day}}' );
			$replace 		= array( $placeholder_date_time->format( 'Y' ), $placeholder_date_time->format( 'y' ), $placeholder_date_time->format( 'm' ), $placeholder_date_time->format( 'd' ) );
			$example 		= str_replace( $search, $replace, $example );
			$pre_example 	= str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_prefix', '' ) );
			$suf_example 	= str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_suffix', '' ) );

			update_option( 'wp_wc_running_invoice_number_example', $example );

			$settings[] = array(
					'name' 		=> __( 'Example', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Here you see how the next running invoice number looks like', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_number_example',
					'type' 		=> 'text',
					'default' 	=> $pre_example . str_pad( absint( $next_running_invoice_number ), absint( get_option( 'wp_wc_running_invoice_number_digits', 0 ) ), '0', STR_PAD_LEFT ) . $suf_example,
					'css'      	=> 'width: 300px;',
					'custom_attributes' => array( 'readonly' => 'readonly' )
				);
			
			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_collocation' );
			
			// Refund number
			$settings[]	= array( 'title' => __( 'Refund Numbers', 'woocommerce-german-market' ), 'type' => 'title','id' => 'wp_wc_running_invoice_number_refund_number' );

			$settings[] = array(
					'name' 		=> __( 'Seperate Refund Numbers from Invoice Numbers', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Select "Yes" if you want to use seperate numbers for your refunds. In that case you can set up seperate options for the refund numbers. If you chose "No" your refund numbers will be integrated in the invoice numbers. If you change to "Yes", save your settings to let the options for the collocation of the refund numbers appear.', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Seperate Refund Numbers from Invoice Numbers', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Select "Yes" if you want to use seperate numbers for your refunds. In that case you can set up seperate options for the refund numbers. If you chose "No" your refund numbers will be integrated in the invoice numbers. If you change to "Yes", save your settings to let the options for the collocation of the refund numbers appear.', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_running_invoice_refund_separation',
					'type' 		=> 'wgm_ui_checkbox',
					'default' 	=> 'off',
					'css'      	=> 'width: 100px;',
				);

			$show_refund_options = get_option( 'wp_wc_running_invoice_refund_separation' ) == 'on';
			if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
				$show_refund_options = isset( $_REQUEST[ 'wp_wc_running_invoice_refund_separation' ] );
			}

			if ( $show_refund_options ) {

				$settings[] = array(
						'name' 		=> __( 'Prefix', 'woocommerce-german-market' ),
						'desc_tip' 	=> __( 'Choose a fix prefix that is used before the running number', 'woocommerce-german-market' ),
						'desc'  	=> apply_filters( 'wp_wc_running_invoice_number_placeholder_desc', __( 'You can use the following placeholders: <code>{{year}}</code> (four-digit), <code>{{year-2}}</code> (double-digit), <code>{{month}}</code>, <code>{{day}}</code>', 'woocommerce-german-market' ) ),
						'id'   		=> 'wp_wc_running_invoice_number_prefix_refund',
						'type' 		=> 'text',
						'default' 	=> '',
						'css'      	=> 'width: 300px;',
					);
					
				$settings[] = array(
						'name' 		=> __( 'Number of Digits', 'woocommerce-german-market' ),
						'desc_tip' 	=> __( 'Choose of how many digits the running number at least consists of, enter an integer, missing digits will be filled with zero', 'woocommerce-german-market' ),
						'tip'  		=> __( 'Choose of how many digits the running number at least consists of, enter an integer, missing digits will be filled with zero', 'woocommerce-german-market' ),
						'id'   		=> 'wp_wc_running_invoice_number_digits_refund',
						'type' 		=> 'text',
						'default' 	=> '0',
						'css'      	=> 'width: 100px;',
					);	
					
				$settings[] = array(
						'name' 		=> __( 'Suffix', 'woocommerce-german-market' ),
						'desc_tip' 	=> __( 'Choose a fix suffix that is used after the running number', 'woocommerce-german-market' ),
						'desc'  	=> apply_filters( 'wp_wc_running_invoice_number_placeholder_desc', __( 'You can use the following placeholders: <code>{{year}}</code> (four-digit), <code>{{year-2}}</code> (double-digit), <code>{{month}}</code>, <code>{{day}}</code>', 'woocommerce-german-market' ) ),
						'id'   		=> 'wp_wc_running_invoice_number_suffix_refund',
						'type' 		=> 'text',
						'default' 	=> '',
						'css'      	=> 'width: 300px;',
					);	

				$settings[] = array(
						'name' 		=> __( 'Next Number', 'woocommerce-german-market' ),
						'desc_tip' 	=> __( 'Choose the running number that shall be used next time a running refund number is generated, enter an integer', 'woocommerce-german-market' ),
						'tip'  		=> __( 'Choose the running number that shall be used next time a running refund number is generated, enter an integer', 'woocommerce-german-market' ),
						'id'   		=> 'wp_wc_running_invoice_number_next_refund',
						'type' 		=> 'text',
						'default' 	=> absint( $next_running_refund_number ),
						'css'      	=> 'width: 100px;',
					);

				$example_refund = get_option( 'wp_wc_running_invoice_number_prefix_refund' ) . str_pad( $next_running_refund_number, absint( get_option( 'wp_wc_running_invoice_number_digits_refund', 0 ) ), '0', STR_PAD_LEFT ) . get_option( 'wp_wc_running_invoice_number_suffix_refund' );
				
				$placeholder_date_time = new DateTime();
				$search 		= array( '{{year}}', '{{year-2}}', '{{month}}', '{{day}}' );
				$replace 		= array( $placeholder_date_time->format( 'Y' ), $placeholder_date_time->format( 'y' ), $placeholder_date_time->format( 'm' ), $placeholder_date_time->format( 'd' ) );
				$example_refund = str_replace( $search, $replace, $example_refund );
				$pre_example 	= str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_prefix_refund', '' ) );
				$suf_example 	= str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_suffix_refund', '' ) );

				update_option( 'wp_wc_running_invoice_number_example_refund', $example_refund );

				$settings[] = array(
						'name' 		=> __( 'Example', 'woocommerce-german-market' ),
						'desc_tip' 	=> __( 'Here you see how the next running invoice number looks like', 'woocommerce-german-market' ),
						'tip'  		=> __( 'Here you see how the next running refund number looks like', 'woocommerce-german-market' ),
						'id'   		=> 'wp_wc_running_invoice_number_example_refund',
						'type' 		=> 'text',
						'default' 	=> $pre_example . str_pad( absint( $next_running_refund_number ), absint( get_option( 'wp_wc_running_invoice_number_digits_refund', 0 ) ), '0', STR_PAD_LEFT ) . $suf_example ,
						'css'      	=> 'width: 300px;',
						'custom_attributes' => array( 'readonly' => 'readonly' )
					);

			}

			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_refund_number' );

			// Reset next number month or year
			$settings[]	= array( 
				'title' => __( 'Reset Number', 'woocommerce-german-market' ), 
				'type' 	=> 'title',
				'id' 	=> 'wp_wc_running_invoice_number_reset',
				'desc'	=> WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/videogm/zuruecksetzende+Nummer.mp4' ) 
			);

			$settings[] = array(
				'name' 		=> __( 'Reset Interval', 'woocommerce-german-market' ),
				'desc_tip' 	=> __( 'The next used invoice number can be set to "1" if a new month or a new year begins. Take care: You have to use the placeholders {{year}} and {{month}} to avoid dublicate invoice numbers.', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_running_invoice_number_reset_interval',
				'type' 		=> 'select',
				'default'	=> 'off',
				'options'	=> array(
					'off'		=> __( 'No Reset', 'woocommerce-german-market' ),
					'daily'		=> __( 'Daily Reset', 'woocommerce-german-market' ),
					'monthly'	=> __( 'Monthly Reset', 'woocommerce-german-market' ),
					'annually'	=> __( 'Annually Reset', 'woocommerce-german-market')
				)
			);

			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_reset' );

			$logging = apply_filters( 'german_market_invoice_number_logging', false );
			if ( $logging ) {

				if ( isset( $_REQUEST[ 'wp_wc_running_invoice_number_next' ] ) ) {

					$current_option = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next', 1 ) : get_option( 'wp_wc_running_invoice_number_next', 1 );

					if ( $current_option != $_REQUEST[ 'wp_wc_running_invoice_number_next' ] ) {
						$new_log = sprintf( 'Manual Saved Option Next Invoice Number from %s to %s.', $current_option, $_REQUEST[ 'wp_wc_running_invoice_number_next' ] );
						$logger = wc_get_logger();
						$context = array( 'source' => 'german-market-invoice-number' );
						$logger->info( $new_log, $context );
					}
				}

				if ( isset( $_REQUEST[ 'wp_wc_running_invoice_number_next_refund' ] ) ) {

					$current_option = ( is_multisite() && get_site_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) ? get_site_option( 'wp_wc_running_invoice_number_next_refund', 1 ) : get_option( 'wp_wc_running_invoice_number_next_refund', 1 );

					if ( $current_option != $_REQUEST[ 'wp_wc_running_invoice_number_next_refund' ] ) {
						$new_log = sprintf( 'Manual Saved Option Next Refund Number from %s to %s.', $current_option, $_REQUEST[ 'wp_wc_running_invoice_number_next_refund' ] );
						$logger = wc_get_logger();
						$context = array( 'source' => 'german-market-invoice-number' );
						$logger->info( $new_log, $context );
					}
				}

				// semaphore-technique
				$semaphore = '<br><br><strong>Semaphore:</strong> ';
				if ( WP_WC_Running_Invoice_Number_Semaphore::$use_sem ) {
					$semaphore .= 'PHP sem_*-Functions';
				} elseif ( WP_WC_Running_Invoice_Number_Semaphore::$use_flock ) {
					$semaphore .= 'Lock File ' . WP_WC_Running_Invoice_Number_Semaphore::$lock_file;
				} else {
					$semaphore .= 'WP Option';
				}

				$semaphore .= '<br>' . sprintf( 
					__( 'The log files for the invoice numbers can be found in the WooCommerce log (menu: <em><a href="%s">WooCommerce -> Status -> Logs</a></em>). The file start with the name <strong>german-market-invoice-number</strong>.', 'woocommerce-german-market' ),
					admin_url( 'admin.php?page=wc-status&tab=logs' )
				);

				$settings[]	= array( 
					'title' => 'Semaphore Info (Log-Info)', 
					'type' 	=> 'title',
					'id' 	=> 'wp_wc_running_invoice_number_log',
					'desc'	=> $semaphore,
				);

				$settings[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_running_invoice_number_log' );

			}

			$settings = apply_filters( 'wp_wc_running_invoice_number_options_collocation', $settings );
			return( $settings );

		}

		/**
		* Output textarea
		*
		* @since 0.0.1
		* @static
		* @access public
		* @hook woocommerce_admin_field_wp_wc_running_invoice_number_textarea
		* @return void
		*/
		public static function output_textarea( $value ) {

			// Description handling
			$field_description = WC_Admin_Settings::get_field_description( $value );
			extract( $field_description );

			$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default'] );
			?><tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?></label><?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ) ?>">
					<textarea
						name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						><?php echo esc_textarea( $option_value );  ?></textarea>
						<br /><span class="description"><?php echo $value[ 'desc' ]; ?></span>
				</td>
			</tr><?php
		}

		/**
		* Validation for saving
		*
		* @since 0.0.1
		* @access public
		* @return void
		*/
		public static function save( $value, $option, $raw_value ) {
			
			// validation: number of digits
			if ( $option[ 'id' ] == 'wp_wc_running_invoice_number_digits' ) {
				return $value;
			}

			if ( $option[ 'id' ] == 'wp_wc_running_invoice_pdf_date' ) {
				return strip_tags( $raw_value, '<br/><br><br /><span>' );
			}
			
			// gobal multisite option
			if ( $option[ 'id' ] == 'wp_wc_running_invoice_number_multisite_global' ) {
				return is_null( $raw_value ) ? 'no' : 'yes';
			} 

			// validation: next number
			if ( $option[ 'id' ] == 'wp_wc_running_invoice_number_next' ) {
				$number_next = absint( $value );	
				if ( $number_next == 0 ) {
					$number_next = 1;
				}
				
				if ( is_multisite() && get_option( 'wp_wc_running_invoice_number_multisite_global', 'no' ) == 'yes' ) {
					update_site_option( 'wp_wc_running_invoice_number_next', $number_next );	
				}

				return $number_next;
				
			}

			return $value;
			
		}

		/**
		* lexoffice Support: Send invoice number as voucher number
		*
		* @since 3.5.2
		* @access public
		* @return void
		*/
		public static function lexoffice_options( $options ) {

			$options[] = array(
				'name'		 => __( 'Voucher Number', 'woocommerce-german-market' ) . ' ' . __( 'and Voucher Date', 'woocommerce-german-market' ),
				'type'		 => 'title',
				'id'  		 => 'lexoffice_voucher_number',
			);

			$options[] = array(
				'name'		=> __( 'Voucher Number', 'woocommerce-german-market' ),
				'id'		=> 'woocommerce_de_lexoffice_voucher_number',
				'desc_tip'	=> sprintf( __( 'Either the Order Number or the Invoice Number can be send to %s as the Voucher Number.', 'woocommerce-german-market' ), 'lexoffice' ),
				'type'     	=> 'select',
				'default'  	=> 'order_number',
				'options'	=> array(
						'order_number'  	=> __( 'Order Number', 'woocommerce-german-market' ),
						'invoice_number' 	=> __( 'Invoice Number', 'woocommerce-german-market' )
				)
			);

			$options[] = array(
				'name'		=> __( 'Voucher Date', 'woocommerce-german-market' ),
				'id'		=> 'woocommerce_de_lexoffice_voucher_date',
				'desc_tip'	=> sprintf( __( 'Either the Order Date or the Invoice Date can be send to %s as the Voucher Date.', 'woocommerce-german-market' ), 'lexoffice' ),
				'type'     	=> 'select',
				'default'  	=> 'order_date',
				'options'	=> array(
						'order_date'  	=> __( 'Order Date', 'woocommerce-german-market' ),
						'invoice_date' 	=> __( 'Invoice Date', 'woocommerce-german-market' )
				)
			);

			$options[] = array( 
				'type'		=> 'sectionend',
				'id' 		=> 'lexoffice_voucher_number' 
			);

			return $options;
		}

		/**
		* sevDesk Support: Send invoice number as voucher number
		*
		* @since 3.5.2
		* @access public
		* @return void
		*/
		public static function sevdesk_options( $options ) {

			$options[] = array(
				'name'		=> __( 'Voucher Date', 'woocommerce-german-market' ),
				'id'		=> 'woocommerce_de_sevdesk_voucher_date',
				'desc_tip'	=> sprintf( __( 'Either the Order Date or the Invoice Date can be send to %s as the Voucher Date.', 'woocommerce-german-market' ), 'sevDesk' ),
				'type'     	=> 'select',
				'default'  	=> 'order_date',
				'options'	=> array(
						'order_date'  	=> __( 'Order Date', 'woocommerce-german-market' ),
						'invoice_date' 	=> __( 'Invoice Date', 'woocommerce-german-market' )
				)
			);

			return $options;
		}

		/**
		* sevDesk Support: placeholder to use invoice number in voucher description
		*
		* @since 3.9.2
		* @access public
		* @wp-hook sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_order
		* @return void
		*/
		public static function sevdesk_voucher_description_order( $option = array() ) {
			$option[ 'desc' ] = __( 'You can use the following placeholder', 'woocommerce-german-market' ) . ': ' . __( 'Invoice Number - <code>{{invoice-number}}</code>, Order Number - <code>{{order-number}}</code>', 'woocommerce-german-market' );
			return $option;
		}

		/**
		* sevDesk Support: placeholders to use invoice number and refund number in voucher description
		*
		* @since 3.9.2
		* @access public
		* @wp-hook sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_refunds
		* @return void
		*/
		public static function sevdesk_voucher_description_refund( $option = array() ) {
			$option[ 'desc' ] = __( 'You can use the following placeholder', 'woocommerce-german-market' ) . ': ' . __( 'Refund Number - <code>{{refund-number}}</code>, Refund ID - <code>{{refund-id}}</code>, Invoice Number - <code>{{invoice-number}}</code>, Order Number - <code>{{order-number}}</code>', 'woocommerce-german-market' );
			return $option;
		}

	}

}
