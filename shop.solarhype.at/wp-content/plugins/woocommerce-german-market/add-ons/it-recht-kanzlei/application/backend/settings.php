<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Backend Settings German Market 3.1
*
* wp-hook woocommerce_de_ui_options_global
* @param Array $items
* @return Array
*/
function gm_it_recht_kanzlei_ui_left_menu_items( $items ) {

	$submenu = array(

		array(
			'title'		=> __( 'Settings', 'woocommerce-german-market' ),
			'slug'		=> 'api-settings-recht-kanzlei',
			'callback'	=> 'gm_it_recht_kanzlei_gm_ui_render_options_api_settings',
			'options'	=> 'yes'
		),

		array(
			'title'		=> __( 'Documents', 'woocommerce-german-market' ),
			'slug'		=> 'itr-documents',
			'callback'	=> 'gm_it_recht_kanzlei_gm_ui_render_options_documents',
			'options'	=> 'yes'
		),

	);

	$items[ 330 ] = array( 
				'title'		=> __( 'IT-Recht Kanzlei', 'woocommerce-german-market' ),
				'slug'		=> 'it-recht-kanzlei',
				'submenu'	=> $submenu
	);

	return $items;

}

function gm_it_recht_kanzlei_gm_ui_render_options_documents() {

	$api = new GM_IT_Recht_Kanzlei_Api();
	$documents = $api->get_documents();

	$options = array();

	foreach ( $documents as $doc_key => $doc_title ) {

		$last_time_updated = intval( get_option( 'gm_it_recht_kanzlei_document_last_update_' . $doc_key, 0 ) );

		if ( $last_time_updated > 0 ) {
			$updated_string = __( 'Last Update:', 'woocommerce-german-market' ) . ' ' . date_i18n( wc_date_format() . ' ' . wc_time_format(), $last_time_updated );
		} else {
			$updated_string =  __( 'Last Update:', 'woocommerce-german-market' ) . ' ' . __( 'Never updated, yet', 'woocommerce-german-market' );
		}

		// Title for document
		$options[] = array(
			'name'		 => $doc_title,
			'id'		 => 'gm_it_recht_kanzlei_document_title_' . $doc_key,
			'type'		 => 'title',
			'desc'		 => $updated_string,
		);

		// Page Assignment
		$options[] = array(
			'name'		=> __( 'Page Assignment', 'woocommerce-german-market' ),
			'type'  	=> 'single_select_page',
			'id'		=> 'gm_it_recht_kanzlei_page_assignment_' . $doc_key,
			'class' 	=> 'wc-enhanced-select-nostd page-assignment',
			'desc_tip'	=> __( 'You can assign this text to one of your WordPress pages.' , 'woocommerce-german-market' ),
		);

		// Format
		$options[] = array(
			'name'		=> __( 'Format', 'woocommerce-german-market' ),
			'type'  	=> 'select',
			'id'		=> 'gm_it_recht_kanzlei_page_format_' . $doc_key,
			'options'	=> array(
							'text' => __( 'Text', 'woocommerce-german-market' ),
							'html' => __( 'HTML', 'woocommerce-german-market' ),
			),
			'default'	=> 'html',
			'desc_tip'	=> __( 'You can choose between the text variant or HTML varinat for your WordPress page.' , 'woocommerce-german-market' ),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		);

		// no pdf for imprints
		if ( $doc_key != 'impressum' ) {
			
			// description to find pdf file on the server:
			$pdf_file = '';
			if ( is_file( $api->local_dir_for_pdf_storage . DIRECTORY_SEPARATOR . $doc_title . '.pdf' ) ) {
				$pdf_file = $api->upload_dir . '/' . $doc_title . '.pdf';
			}

			if ( $pdf_file == '' ) {
				$description_pdf = __( 'PDF file on your server:', 'woocommerce-german-market' ) . ' ' . __( 'There was no from transmission from IT-Recht Kanzlei so far.', 'woocommerce-german-market' );
			} else {
				$description_pdf = __( 'PDF file on your server:', 'woocommerce-german-market' ) . ' <a href="' . $pdf_file . '" target="_blank">' . $pdf_file . '</a><br /><br />';
			}
			
			// Email Attachments
			$options[] = array(
				'id'		=> 'gm_it_recht_kanzlei_email_attachment_' . $doc_key,
				'name'		=> __( 'PDF Email Attachment', 'woocommerce-german-market' ),
				'type'  	=> 'wgm_ui_checkbox',
				'default'	=> 'off',
				'desc'		=> $description_pdf,
			);

			// Emails
			$options[] = array(
				'id'		=> 'gm_it_recht_kanzlei_shops_attachment_mails_' . $doc_key,
				'name'		=> __( 'Emails', 'woocommerce-german-market' ),
				'type'  	=> 'multiselect',
				'class'		=> 'wc-enhanced-select',
				'desc_tip'	=> __( 'Choose the emails that should contain the PDF attachment.', 'woocommerce-german-market' ),		
				
				'options'	=> apply_filters( 'gm_emails_in_add_ons', array( 
						'customer_order_confirmation' 	=> __( 'Customer Order Confirmation', 'woocommerce-german-market' ), 
						'new_order' 					=> __( 'New Order', 'woocommerce-german-market' ), 
						'customer_invoice' 				=> __( 'Customer Invoice', 'woocommerce-german-market' ),
						'customer_processing_order' 	=> __( 'Customer Processing Order', 'woocommerce-german-market' ), 
						'customer_completed_order' 		=> __( 'Customer Completed Order', 'woocommerce-german-market' ), 
						'customer_on_hold_order' 		=> __( 'Customer On-Hold', 'woocommerce-german-market' ), 
						'customer_refunded_order' 		=> __( 'Refunded Order', 'woocommerce-german-market' ),
					) ),
				
				'custom_attributes' => array( 
						'data-placeholder' => __( 'Choose emails', 'woocommerce-german-market' ) 
					),

			);
		}

		// End Document Settings
		$options[] = array( 
			'type'		=> 'sectionend',
			'id' 		=> 'gm_it_recht_kanzlei_document_title_' . $doc_key,
		);

	}

	return $options;

}

/**
* Render Options for API Settings
* 
* @return void
*/
function gm_it_recht_kanzlei_gm_ui_render_options_api_settings() {

	$description 				 = __( 'If you are logged into your account of "IT-Recht Kanzlei", you can transfer the legal texts into this online shop from the <a href="https://www.it-recht-kanzlei.de/Portal/login.php" target="_blank">mandate portal</a>. To do this, click on the link "Data interface", select the shop system "WordPress (German Market)" and enter the requested authentication data. These are the ones you find below. The success of the transmission (or a corresponding error message) will be displayed.', 'woocommerce-german-market' );
	$description 				.= '<br /><br />' . __( 'In addition, an automatic transmission system can be activated, by which the legal texts are automatically updated in the WooCommerce shop with every change. This system also checks several times a day whether a legal text is displayed correctly in the shop.', 'woocommerce-german-market' );
	
	$description 				.= '<br /><br /><strong>' . sprintf ( __( "If you don't have a IT-Recht Kanzlei account yet, you can order a starter package <a href=\"%s\" target=\"_blank\">here</a> for 8,90 Euro per month.", 'woocommerce-german-market' ), 'http://www.it-recht-kanzlei.de/Service/german-market-agb.php?partner_id=294' ) . '</strong>';

	$text_has_been_copied		= '<span class="copied-success copy-success">' . __( 'The text has been copied to clipboard', 'woocommerce-german-market' ) . ' ✓' . '</span>';
	$text_refreshed				= '<span class="copied-success refreshed-success">' . __( 'A new API token has been created', 'woocommerce-german-market' ) . ' ✓ <br />' . __( 'Don\'t forget to save your settings.', 'woocommerce-german-market' ) . '</span>';
	$api 						= new GM_it_recht_kanzlei_Api();

	$options = array(

		array(
			'name'		 => __( 'Settings', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'gm_it_recht_kanzlei_api_title',
			'desc'		 => $description
		),

		array(
			'name'		=> __( 'API Token', 'woocommerce-german-market' ),
			'id'		=> 'gm_it_recht_kanzlei_api_token',
			'type'		=> 'text',
			'css'		=> 'width: 600px; ; margin-bottom: 5px;',
			'custom_attributes' => array( 'readonly' => 'readonly' ),
			'default'	=> $api->get_api_token(),
			'desc' 		=> '<br /><button type="button" class="button-secondary copy-to-clipboard">' . __( 'Copy to Clipboard', 'woocommerce-german-market' ) . '</button> <button type="button" class="button-secondary refresh">' . __( 'Create a new token', 'woocommerce-german-market' ) . '</button> ' . $text_has_been_copied . $text_refreshed,
		),

		array(
			'name'		=> __( 'Shop URL', 'woocommerce-german-market' ),
			'id'		=> 'gm_it_recht_kanzlei_api_shop_url',
			'type'		=> 'text',
			'css'		=> 'width: 600px; margin-bottom: 5px;',
			'custom_attributes' => array( 'readonly' => 'readonly' ),
			'default'	=> untrailingslashit( get_site_url() ),
			'desc' 		=> __( 'In the case that your shop is a test enviornment and you are using a htacess protection with username and password, you have to adapt the Shop URL when entering it into your IT-Recht Kanzlei account as follows:<br />https://user:password@domain.de<br />Where username and password has to bee replaced with your htaccess data and domain with your general domain.', 'woocommerce-german-market' ) . '<br /><br /><button type="button" class="button-secondary copy-to-clipboard">' . __( 'Copy to Clipboard', 'woocommerce-german-market' ) . '</button>' . $text_has_been_copied,
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'gm_it_recht_kanzlei_api_title' 
		)

	);

	$options = apply_filters( 'gm_it_recht_kanzlei_gm_ui_render_options', $options );
	return $options;

}

/**
* Load JS only in IT-Recht Kanzlei Tab
*
* @wp-hook current_screen
* @return void
**/
function gm_it_recht_kanzlei_backend_scripts() {

	$current_screen = get_current_screen();

	if ( $current_screen->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) && isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'it-recht-kanzlei' ) {

		add_action( 'admin_enqueue_scripts', 'gm_it_recht_kanzlei_backend_scripts_load_scripts' );

	}

}

/**
* Load JS on documents tab
*
* @wp-hook admin_enqueue_scripts
* @return void
**/
function gm_it_recht_kanzlei_backend_scripts_load_scripts() {

	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
	wp_register_script( 'gm_it_recht_kanzlei_js', GM_it_recht_kanzlei_ASSETS_URL . '/js/backend-script.' . $min . 'js', array( 'jquery' ), '1.0.0' );
	wp_enqueue_script( 'gm_it_recht_kanzlei_js' );

}
