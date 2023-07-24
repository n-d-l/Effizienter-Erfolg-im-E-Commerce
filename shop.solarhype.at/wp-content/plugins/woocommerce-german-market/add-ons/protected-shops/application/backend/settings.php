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
function gm_protected_shops_ui_left_menu_items( $items ) {

	$submenu = array(

		array(
			'title'		=> __( 'API Settings', 'woocommerce-german-market' ),
			'slug'		=> 'api-settings',
			'callback'	=> 'gm_protected_shops_gm_ui_render_options_api_settings',
			'options'	=> 'yes'
		),

		array(
			'title'		=> __( 'Questionary', 'woocommerce-german-market' ),
			'slug'		=> 'questionary',
			'callback'	=> 'gm_protected_shops_gm_ui_render_options_questionary',
		),

		array(
			'title'		=> __( 'Documents', 'woocommerce-german-market' ),
			'slug'		=> 'documents',
			'callback'	=> 'gm_protected_shops_gm_ui_render_options_documents',
			'options'	=> 'yes'
		),

		array(
			'title'		=> __( 'Auto Update of Page Content - Settings', 'woocommerce-german-market' ),
			'slug'		=> 'auto_update_of_page_content_settings',
			'callback'	=> 'gm_protected_shops_gm_ui_render_options_uto_update_of_page_content_settings',
			'options'	=> 'yes'
		),

	);

	$items[ 340 ] = array( 
				'title'		=> __( 'Protected Shops', 'woocommerce-german-market' ),
				'slug'		=> 'protected-shops',
				'submenu'	=> $submenu
	);

	return $items;
}

/**
* Render Options for Auto Update of Page Content - Settings
* 
* @return void
*/
function gm_protected_shops_gm_ui_render_options_uto_update_of_page_content_settings() {

	$documents_with_auto_update = get_option( '_gm_protected_shops_auto_update_documents_with_autoupdate', array() );

	if ( ! empty( $documents_with_auto_update ) ) {
		$next_update_time = get_option( '_gm_protected_shops_next_auto_updater_time', '' );
		$next_update_time_date = new DateTime( $next_update_time );
		$next_update_string = date_i18n( wc_date_format() . ' ' . wc_time_format(), $next_update_time_date->getTimestamp() );
	} else {
		$next_update_string = __( 'There will no be a check, because no document has "Auto Update of Page Content" enabled in the "Documents" tab', 'woocommerce-german-market' );
	}
	

	$options = array();

	$options[] = array(
		'name'	=> __( 'Auto Update of Page Content - Settings', 'woocommerce-german-market' ),
		'id'	=> 'gm_protected_shops_auto_update_settings_title',
		'type'	=> 'title',
		'desc' 	=> __( 'If you enabled "Auto Update of Page Content" for at least one document in the "Documents" tab, you can set up here, how often it should be checked if a new version of the document exists. If there is a new version, the WordPress Page content will be automatically updated', 'woocommerce-german-market' ) . '<br /><br /><b>' . __( 'Next Auto Update Check:', 'woocommerce-german-market' ) . ' ' . $next_update_string . '</b>'
	);

	$options[] = array(
				'name'		=> __( 'Update Check Interval', 'woocommerce-german-market' ),
				'type'		=> 'select',
				'options'	=> array(
									'daily'				=> __( 'Daily', 'woocommerce-german-market' ),
									'weekly'			=> __( 'Weekly', 'woocommerce-german-market' ),
									'monthly'			=> __( 'Monthly', 'woocommerce-german-market' ),
									'custom'			=> __( 'Custom', 'woocommerce-german-market' ),
								),
				'default'	=> 'daily',
				'id'		=> 'gm_protected_shops_auto_update_interval',
			);

	$options[] = array(
			'name'		=> __( 'Custom Interval', 'woocommerce-german-market' ),
			'type'		=> 'number',
			'desc_tip'	=> __( 'If you set the Update Check Interval to "Custom", you can enter here a period of hours that determines the Update Check Interval','woocommerce-german-market' ),
			'default'	=> '24',
			'desc'		=> __( 'hours', 'woocommerce-german-market' ),
			'id'		=> 'gm_protected_shops_auto_update_custom_interval',
			'class'		=> 'german-market-unit',
		);

	$options[] = array( 
		'type'		=> 'sectionend',
		'id' 		=> 'gm_protected_shops_auto_update_settings_title',
	);

	return $options;

}

/**
* Render Options for Documents
* 
* @return void
*/
function gm_protected_shops_gm_ui_render_options_documents() {

	$api = new GM_Protected_Shops_Api();

	try {

		$can_use_api = $api->can_use_api();
		$documents = $api->get_documents();

		$options = array();

		foreach ( $documents as $document ) {

			$gm_document_id = sanitize_title( $document[ 'type' ] );
			$updated = $document[ 'updated_at' ];

			// Title for document
			$options[] = array(
				'name'		 => $document[ 'name' ],
				'id'		 => 'gm_protected_shops_document_title_' . $gm_document_id,
				'type'		 => 'title',
				'desc'		 => __( 'Updated at:' ) . ' ' . date_i18n( wc_date_format() . ' ' . wc_time_format(), strtotime( $updated ) ),
			);

			// Format of Document
			$options[] = array(
				'name'		=> __( 'Format', 'woocommerce-german-market' ),
				'type'		=> 'select',
				'options'	=> array(
									'text'				=> __( 'Text', 'woocommerce-german-market' ),
									'html-lite'			=> esc_attr( __( 'Lightweight HTML', 'woocommerce-german-market' ) ),
									'html'				=> esc_attr( __( 'Complete HTML', 'woocommerce-german-market' ) ),
									'html-responsive'	=> esc_attr( __( 'HTML Responsive', 'woocommerce-german-market' ) )
								),
				'desc_tip'	=> __( 'Text: text only version of the document, only text and new-line characters<br /><br />Lightweight HTML: using only u, b, i and br tags<br /><br />Complete HTML: Complete HTML structure. Consists of html tags with assigned css classes and IDs so the end user can style it.<br /><br />HTML Responsive: Complete HTML structure, plus styles. Note that the content is split into css and html','woocommerce-german-market' ),
				'default'	=> 'text',
				'id'		=> 'gm_protected_shops_document_format_' . $gm_document_id,
			);

			$preview_window = '<div class="gm-protected-shops-preview-text text-or-html-preview">%s</div>';
			
			$preview_text = $api->get_document( $document[ 'type' ] );

			$url_pdf 	= wp_nonce_url( admin_url( 'admin-ajax.php?action=gm_ps_download_documents&format=pdf&type=' . $document[ 'type' ] ), 'gm_protected_shops_documents' );

			$pdf_button				 	= '<a href="' . $url_pdf . '"><button type="button" class="button-secondary gm-ps-download">' . __( 'Download PDF', 'woocommerce-german-market' ) . '</button></a> '; 
			$text_has_been_copied		= '<span class="copied-success">' . __( 'The text has been copied to clipboard', 'woocommerce-german-market' ) . ' ✓' . '</span>';

			$has_css 					= false;
			
			if ( is_array( $preview_text ) ) { 

				// css and html
				$has_css 						= true;
				$preview_window_css 			= '<div class="gm-protected-shops-preview-text css-preview">%s</div>';
				$copy_to_cliboard_button_css 	= '<button type="button" class="button-secondary copy-button copy-css-button">' . __( 'Copy CSS to Clipboard', 'woocommerce-german-market' ) . '</button> ';
				$copy_to_cliboard_button_html 	= '<button type="button" class="button-secondary copy-button copy-html-button">' . __( 'Copy HTML to Clipboard', 'woocommerce-german-market' ) . '</button> ';
				$css_content 					= $preview_text[ 'css' ];
				$html_content 					= $preview_text[ 'html' ];
				$css_header 					= 'CSS: <br />';
				$html_header 					= __( 'HTML (CSS style is not applied in this preview):', 'woocommerce-german-market' ) . '<br />';

				$options[] = array(
					'name'			=> __( 'Preview', 'woocommerce-german-market' ),
					'type'			=> 'textarea',
					'css'			=> 'display: none',
					'desc'			=> $css_header . sprintf( $preview_window_css, $css_content ) . $html_header . sprintf( $preview_window, $html_content ) . $copy_to_cliboard_button_css . $copy_to_cliboard_button_html . $pdf_button . $text_has_been_copied,
				);

			} else {

				// only text or simple html
				$copy_to_cliboard_button 	= '<button type="button" class="button-secondary copy-button">' . __( 'Copy to Clipboard', 'woocommerce-german-market' ) . '</button> ';

				$options[] = array(
					'name'			=> __( 'Preview', 'woocommerce-german-market' ),
					'type'			=> 'textarea',
					'css'			=> 'display: none',
					'desc'			=> sprintf( $preview_window, $preview_text ) . $copy_to_cliboard_button . $pdf_button . $text_has_been_copied,
				);
			}

			// Page Assignment
			$options[] = array(
				'name'		=> __( 'Page Assignment', 'woocommerce-german-market' ),
				'type'  	=> 'single_select_page',
				'id'		=> 'gm_protected_shops_page_assignment_' . $gm_document_id,
				'class' 	=> 'wc-enhanced-select-nostd page-assignment',
				'desc_tip'	=> __( 'You can assign this text to one of your WordPress pages.' , 'woocommerce-german-market' ),
			);

			// Manual Update
			$extra_class = $has_css ? 'has-css' : 'just-text';
			$page_save_button			= '<button type="button" class="button-secondary gm-ps-save-to-page ' . $extra_class . '">' . __( 'Save Text into WordPress Page and Update PDF file', 'woocommerce-german-market' ) . '</button>';
			$text_has_been_copied_page	= '<span class="copied-page-success copy-page-success">' . __( 'The text has been copied to the page and the page assignment has been saved', 'woocommerce-german-market' ) . ' ✓' . '</span>';
			$text_pdf_docx_update		= '<span class="copied-page-success copy-pdf-docx-success">' . __( 'PDF file has been updated', 'woocommerce-german-market' ) . ' ✓' . '</span>';
			$loader = '<div class="gm-ps-background-icon small"></div>';
			$text_error	= '<span class="copied-page-error">' . __( 'Error: No page has been selected.', 'woocommerce-german-market' ) . '</span>';
			$hidden_field = '<span class="document-type hidden">' . $document[ 'type' ] . '</span>';

			$api->check_files_and_maybe_create( $document[ 'name' ], $document[ 'type' ] );

			$pdf_file = $api->get_file_url( $document[ 'name' ], 'pdf' );

			$files_on_your_server = __( 'PDF file on your server:', 'woocommerce-german-market' ) . ' <a href="' . $pdf_file . '" target="_blank">' . $pdf_file . '</a><br /><br />';

			delete_option( 'gm_protected_shops_documents_id_to_name' );

			$options[] = array(
				'name'		=> __( 'Manual Update', 'woocommerce-german-market' ),
				'type'  	=> 'text',
				'css'		=> 'display: none;',
				'default'	=> $document[ 'name' ],
				'id'		=> 'gm_protected_shops_documents_id_to_name_' . $gm_document_id,
				'desc'		=> $hidden_field . $files_on_your_server . $page_save_button . $text_has_been_copied_page . $text_pdf_docx_update . $loader . $text_error,
			);

			// Auto Update
			$page_id 			= intval( get_option( 'gm_protected_shops_page_assignment_' . $gm_document_id ) );
			if ( isset ( $_REQUEST[ 'gm_protected_shops_page_assignment_' . $gm_document_id ] ) ) {
				$page_id = intval( $_REQUEST[ 'gm_protected_shops_page_assignment_' . $gm_document_id ] );
			}
			
			$last_update_string	= '';

			if ( $page_id > 0 ) {

				$last_update 		= get_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_time', '' );
				$last_update_kind 	= get_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_kind', '' );
				
			} else {

				$last_update 		= get_option( '_gm_protected_shops_auto_update_' . 'without_page' . '_' . $gm_document_id . '_last_update_time', '' );
				$last_update_kind 	= get_option( '_gm_protected_shops_auto_update_' . 'without_page' . '_' . $gm_document_id . '_last_update_kind', '' );

			}

			$last_update_string = __( 'Last Update:', 'woocommerce-german-market' );
			if ( $last_update == '' ) {
				$last_update_string .= ' ' . __( 'Never updated, yet', 'woocommerce-german-market' );
			} else {
				$last_update_string .= ' ' . date_i18n( wc_date_format() . ' ' . wc_time_format(), intval( $last_update ) ) . ' (' . $last_update_kind . ')';
			}

			$options[] = array(
				'name'		=> __( 'Auto Update of Page and PDF Content', 'woocommerce-german-market' ),
				'type'  	=> 'wgm_ui_checkbox',
				'id'		=> 'gm_protected_shops_auto_update_' . $gm_document_id,
				'default'	=> 'off',
				'desc_tip'	=> __( 'If you have assigned a WordPress page to the document in the settings above, you can auto update the page content. If there is a new version of the document, the page content as well as the PDF that you may use as email attachment, will automatically be updated according to the settings in the "Auto Update of Page Content - Settings" tab.', 'woocommerce-german-market' ),
				'desc'		=> '<span class="last-update-string gm_protected_shops_page_assignment_' . $gm_document_id . '">' . $last_update_string . '</span>',
			);

			// Email Attachments
			$options[] = array(
				'id'		=> 'gm_protected_shops_email_attachment_' . $gm_document_id,
				'name'		=> __( 'PDF Email Attachment', 'woocommerce-german-market' ),
				'type'  	=> 'wgm_ui_checkbox',
				'default'	=> 'off',
			);

			// Emails
			$options[] = array(
				'id'		=> 'gm_protected_shops_attachment_mails_' . $gm_document_id,
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

			// End Document Settings
			$options[] = array( 
				'type'		=> 'sectionend',
				'id' 		=> 'gm_protected_shops_document_title_' . $gm_document_id,
			);

		}

		return $options;
	
	} catch ( Exception $e ) {

		echo __( 'The documents could not be loaded.', 'woocommerce-german-market' );
		echo '<br />' . $e->getMessage();
		
	}

}

/**
* Render Options for API Settings
* 
* @return void
*/
function gm_protected_shops_gm_ui_render_options_api_settings() {

	if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
		delete_option( 'gm_protected_shops_api_bearer' );
		delete_option( 'gm_protected_shops_api_bearer_expires' );
	}

	$description = sprintf( __( "Please visit <a href=\"%s\" target=\"_blank\">this page</a> and log in with your Protected Shop account. Navigate to the menu <i>Interface</i> and copy your wanted Shop-ID to the field below and save the settings.", 'woocommerce-german-market' ), 
		'https://www.protectedshops.de/account/login' );

	$description .= '<br />' . __( 'Furthermore, you have to enter your Client-ID and your Client-Secret of the chosen Shop-ID. You can show or generate this data at the bottom of the page mentioned above in the section <i>AGB Configurator</i>.', 'woocommerce-german-market' );

	$description .= '<br /><br /><strong>' . sprintf ( __( "Benefit from our <a href=\"%s\" target=\"_blank\">exclusive partner offer</a>. With the coupon code PS-GM-3X, new customers of Protected Shops receive 3 months for free - use 15 months and pay only 12 months.", 'woocommerce-german-market' ), 'https://www.protectedshops.de/unsere-schutzpakete' ) . '</strong>';

	$options = array(

		array(
			'name'		 => __( 'API Settings', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'gm_protectd_shops_api_title',
			'desc'		 => $description
		),

		array(
			'name'		=> __( 'Shop-ID', 'woocommerce-german-market' ),
			'id'		=> 'gm_protected_shops_api_shop_id',
			'type'		=> 'text',
			'css'		=> 'width: 400px;'
		),

		array(
			'name'		=> __( 'Client-ID', 'woocommerce-german-market' ),
			'id'		=> 'gm_protected_shops_api_client_id',
			'type'		=> 'text',
			'css'		=> 'width: 400px;'
		),

		array(
			'name'		=> __( 'Client-Secret', 'woocommerce-german-market' ),
			'id'		=> 'gm_protected_shops_api_client_secret',
			'type'		=> 'text',
			'css'		=> 'width: 400px;'
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'gm_protectd_shops_api_title' 
		)

	);

	$options = apply_filters( 'gm_protected_shops_gm_ui_render_options', $options );
	return( $options );

}

/**
* Load JS and CSS of questionary library only on sub_tab questionary
*
* @wp-hook current_screen
* @return void
**/
function gm_protected_shops_questionary_library() {

	$current_screen = get_current_screen();

	if ( $current_screen->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) && isset( $_REQUEST[ 'sub_tab' ] ) && $_REQUEST[ 'sub_tab' ] == 'questionary' ) {

		add_action( 'admin_enqueue_scripts', 'gm_protected_shops_questionary_library_js_and_css' );

	} else if ( $current_screen->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) && isset( $_REQUEST[ 'sub_tab' ] ) && $_REQUEST[ 'sub_tab' ] == 'documents' ) {

		add_action( 'admin_enqueue_scripts', 'gm_protected_shops_documents_js' );

	}
}

/**
* Load JS on documents tab
*
* @wp-hook admin_enqueue_scripts
* @return void
**/
function gm_protected_shops_documents_js() {

	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
	wp_register_script( 'gm_protected_shops_documents', GM_PROTECTED_SHOPS_ASSETS_URL . '/js/documents.' . $min . 'js', array( 'jquery' ), '1.0.0' );
	wp_enqueue_script( 'gm_protected_shops_documents' );

	wp_localize_script( 'gm_protected_shops_documents', 'gm_ps_ajax', 
		array(
			'url' 	=> admin_url( 'admin-ajax.php' ),
			'nonce'	=> wp_create_nonce( 'gm_protected_shops_documents' )
		)
	);

}

/**
* Load JS and CSS of questionary library
*
* @wp-hook admin_enqueue_scripts
* @return void
**/
function gm_protected_shops_questionary_library_js_and_css() {

	$api = new GM_Protected_Shops_Api();
	
	try {

		$can_use_api = $api->can_use_api();
		$api->get_questionary( false );
		update_option( 'gm_protected_shops_api_works', 'yes' );

		// Scripts
		
		// dust
		wp_register_script( 'gm_protected_shops_dust_jquery', GM_PROTECTED_SHOPS_LIBRARY_URL . '/jquery-ui-1.12.1.custom/jquery-ui.min.js', array( 'jquery' ), '1.0.0' );
		
		$gm_protected_shops_dust_full = apply_filters( 'gm_protected_shops_dust_full_url', 'https://cdnjs.cloudflare.com/ajax/libs/dustjs-linkedin/2.6.1/dust-full.min.js' );
		$gm_protected_shops_dust_helper = apply_filters( 'gm_protected_shops_dust_helper', 'https://cdnjs.cloudflare.com/ajax/libs/dustjs-helpers/1.6.1/dust-helpers.min.js' );

		wp_register_script( 'gm_protected_shops_dust_full', $gm_protected_shops_dust_full, array( 'jquery' ), '1.0.0' );
		wp_register_script( 'gm_protected_shops_dust_helper', $gm_protected_shops_dust_helper, array( 'jquery' ), '1.0.0' );
		
		// protected shops
		wp_register_script( 'gm_protected_shops_questionary_script', GM_PROTECTED_SHOPS_LIBRARY_URL . '/js/questionary.js', array( 'jquery' ), '1.0.0' );

		// german market
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
		wp_register_script( 'gm_protected_shops_questionary_gm_script', GM_PROTECTED_SHOPS_ASSETS_URL . '/js/questionary.' . $min . 'js', array( 'jquery' ), '1.0.0' );

		wp_enqueue_script( 'gm_protected_shops_dust_jquery' );
		wp_enqueue_script( 'gm_protected_shops_dust_full' );
		wp_enqueue_script( 'gm_protected_shops_dust_helper' );
		
		wp_enqueue_script( 'gm_protected_shops_questionary_script' );
		wp_enqueue_script( 'gm_protected_shops_questionary_gm_script' );
		

		// Styles
		wp_register_style( 'gm_protected_shops_questionary_style', GM_PROTECTED_SHOPS_LIBRARY_URL . '/css/default.css', false, '1.0.0' );
		wp_register_style( 'gm_protected_shops_questionary_style_jquery_min', GM_PROTECTED_SHOPS_LIBRARY_URL . '/jquery-ui-1.12.1.custom/jquery-ui.min.css', false, '1.0.0' );
		wp_register_style( 'gm_protected_shops_questionary_style_jquery_structure', GM_PROTECTED_SHOPS_LIBRARY_URL . '/jquery-ui-1.12.1.custom/jquery-ui.structure.min.css', false, '1.0.0' );
		wp_register_style( 'gm_protected_shops_questionary_style_theme', GM_PROTECTED_SHOPS_LIBRARY_URL . '/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css', false, '1.0.0' );

		wp_enqueue_style( 'gm_protected_shops_questionary_style' );
		wp_enqueue_style( 'gm_protected_shops_questionary_style_jquery_min' );
		wp_enqueue_style( 'gm_protected_shops_questionary_style_jquery_structure' );
		wp_enqueue_style( 'gm_protected_shops_questionary_style_jquery_theme' );

	} catch ( Exception $e ) {

		update_option( 'gm_protected_shops_api_works', $e->getMessage() );
	}

}

/**
* Questionary Tab
* 
* @return void
*/
function gm_protected_shops_gm_ui_render_options_questionary() {

	$api = new GM_Protected_Shops_Api();

	try {

		$can_use_api = $api->can_use_api();

		if ( get_option( 'gm_protected_shops_api_works', '' ) == 'yes' ) {

			// markup
			?> 
			<input type="hidden" id="gm-ps-build-url" value="<?php echo $api->get_build_url(); ?>" />
			<input type="hidden" id="gm-ps-save-url" value="<?php echo $api->get_save_url(); ?>" />
			<input type="hidden" id="gm-ps-template-path" value="<?php echo $api->get_template_path(); ?>" />
			<input type="hidden" id="gm-ps-translation-path" value="<?php echo $api->get_translation_path(); ?>" />

			<div id="main-questionary"></div>
			<div id="gm-ps-background-icon"></div>
			<div id="gm-ps-background-loader"></div>

			<?php

		} else {

			echo __( 'The Questionary could not be loaded.', 'woocommerce-german-market' );
			echo '<br />' . get_option( 'gm_protected_shops_api_works' );
		}

	} catch ( Exception $e ) {

		echo __( 'The Questionary could not be loaded.', 'woocommerce-german-market' );
		echo '<br />' . $e->getMessage();
		
	}

}

/**
* Return json questionary for ps js library
* 
* @wp-hook wp_ajax_gm_ps_get_questionary
* @return void
*/
function gm_protected_shops_ajax_get_questionary() {

	if ( ! check_ajax_referer( 'gm-ps-get-questionary', 'security', false ) ) {
		wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
	}

	$api = new GM_Protected_Shops_Api();
	$api->get_questionary();
	exit();
}

/** 
* Save questionary for ps js library
* 
* @wp-hook wp_ajax_gm_ps_save_questionary
* @return void
*/
function gm_protected_shops_ajax_save_questionary() {

	if ( ! check_ajax_referer( 'gm-ps-save-questionary', 'security', false ) ) {
		wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
	}

	$api = new GM_Protected_Shops_Api();
	$api->save_questionary();
	echo '1';
	exit();
}

/** 
* Ajax for getting pdf or docx document
* 
* @wp-hook wp_ajax_gm_ps_download_documents
* @return void
*/
function gm_protected_shops_ajax_download_documents() {

	if ( ! check_ajax_referer( 'gm_protected_shops_documents', 'security', false ) ) {
  		wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
	}

	$type 	= $_REQUEST[ 'type' ];
	$format = $_REQUEST[ 'format' ];

	$api = new GM_Protected_Shops_Api();
	$response = $api->download_document( $type, $format );

	$content = $response[ 'content' ];

	if ( isset( $response[ 'content' ] ) ) {

		$title = isset( $response[ 'title' ] ) ? $response[ 'title' ] : $type;
		$file_extension = ( $format == 'pdf' ) ? '.pdf' : '.docx';#

		header( 'Content-disposition: attachment; filename="' . $title . $file_extension . '"' );
		header( 'Content-Type: ' . $response[ 'contentType' ] );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: public, must-revalidate, max-age=0' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );

		echo base64_decode( $content );

	} else {

		ob_start();
		var_dump( $content );
		$error = ob_get_clean();

		wp_die( sprintf( __( 'Please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), $error ), '', array( 'response' => 403 ) );

	}
	
	exit();

}

/** 
* Ajax to save content into page
* 
* @wp-hook wp_ajax_gm_ps_save_page
* @return void
*/
function gm_protected_shops_ajax_save_page() {

	if ( ! check_ajax_referer( 'gm_protected_shops_documents', 'nonce', false ) ) {
  		
  		echo "ERROR:" . __( 'Ajax nonce check failed.', 'woocommerce-german-market' );
	
	} else {

		$rtn = "success";

		$page_id = intval( $_REQUEST[ 'page_id' ] );

		// save_page
		if ( $page_id > 0 ) {

			$html_content = $_REQUEST[ 'html_content' ];
			$css_content  = $_REQUEST[ 'css_content' ];

			if ( $css_content != '' ) {
				$html_content = '<style>' . $css_content . '</style>' . $html_content;
			}

			$the_post = array(
				'ID'           => $page_id,
				'post_content' => $html_content,
			);

			// Update the post into the database
			kses_remove_filters();
			wp_update_post( $the_post );
			kses_init_filters();


		} else {

			$page_id = 'without_page';
		}

		// save pdf and docx
		$type = $_REQUEST[ 'type' ];

		$api = new GM_Protected_Shops_Api();
		$api->save_document_on_server( $type, 'pdf' );

		// save option
		update_option( $_REQUEST[ 'option_name' ], $page_id );

		// save last time updated
		$gm_document_id 	= str_replace( 'gm_protected_shops_page_assignment_', '', $_REQUEST[ 'option_name' ] );
		update_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_time', current_time( 'timestamp' ) );
		update_option( '_gm_protected_shops_auto_update_' . $page_id . '_' . $gm_document_id . '_last_update_kind', __( 'Manually updated', 'woocommerce-german-market' ) );

		$last_update_string = __( 'Last Update:', 'woocommerce-german-market' );
		$last_update_string .= ' ' . date_i18n( wc_date_format() . ' ' . wc_time_format(), current_time( 'timestamp' ) ) . ' (' . __( 'Manually updated', 'woocommerce-german-market' ) . ')';

		echo $rtn .'_' . $last_update_string;


	}

	exit();

}

/**
* Update cronjob settings when saving options and all documents that needs email attachments
* 
* @wp-hook woocommerce_de_ui_update_options
* @param Array $options
* @return void
*/
function gm_protected_shops_cronjob_helper_save_settings( $options ) {

	if ( isset( $_POST[ 'submit_save_wgm_options' ] ) && isset( $_REQUEST[ 'sub_tab' ] ) && $_REQUEST[ 'sub_tab' ] == 'documents' ) { 
		
		if ( wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {

			$have_to_run = false;
			
			$docments_with_auto_update = array();
			$documents_with_email_attachments = array();

			foreach ( $_REQUEST as $key => $value ) {

				// cronjob
				if ( str_replace( 'gm_protected_shops_auto_update_', '', $key ) != $key ) {

					$gm_document_id = str_replace( 'gm_protected_shops_auto_update_', '', $key );
					
					if ( isset(  $_REQUEST[ 'gm_protected_shops_page_assignment_' . $gm_document_id ] ) ) {

						$page_id = intval( $_REQUEST[ 'gm_protected_shops_page_assignment_' . $gm_document_id ] );
						
						if ( $page_id  > 0 ) {
							$docments_with_auto_update[ $gm_document_id ] = $page_id;
						} else {
							$docments_with_auto_update[ $gm_document_id ] = 'without_page';
						}

					}
					
				}

				// email
				if ( str_replace( 'gm_protected_shops_email_attachment_', '', $key ) != $key ) {

					$gm_document_id = str_replace( 'gm_protected_shops_email_attachment_', '', $key );

					// check if mails are assigned
					if ( isset( $_REQUEST[ 'gm_protected_shops_attachment_mails_' . $gm_document_id ] ) ) {

						$formats = array();
						$formats[] = $_REQUEST[ 'gm_protected_shops_documents_id_to_name_' . $gm_document_id ] . '.pdf';

						$documents_with_email_attachments[ $gm_document_id ] = array(
							'formats'	=> $formats,
							'emails'	=> $_REQUEST[ 'gm_protected_shops_attachment_mails_' . $gm_document_id ]
						);

					}

				}

			}

			update_option( '_gm_protected_shops_auto_update_documents_with_autoupdate', $docments_with_auto_update );
			update_option( '_gm_protected_shops_documents_with_emai_attachments', $documents_with_email_attachments );

		}

	}

}
