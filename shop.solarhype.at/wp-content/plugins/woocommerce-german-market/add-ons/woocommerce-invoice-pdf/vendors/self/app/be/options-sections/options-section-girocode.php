<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//////////////////////////////////////////////////
// init
//////////////////////////////////////////////////
$options = array();
$options[] = array( 'name' => __( 'Test Invoice', 'woocommerce-german-market' ), 'type' => 'wp_wc_invoice_pdf_test_download_button' );	
//////////////////////////////////////////////////
// General Infos
//////////////////////////////////////////////////

$main_info = '';

if ( ! Woocommerce_Invoice_Pdf::has_php_7_4_for_qr_codes() ) {
	$main_info .= '<div class="german-market-requirement-error">' . __( 'Unfortunately, this function is not available.', 'woocommerce-german-market' ) . PHP_EOL;
	$main_info .= __( 'To use this function, at least PHP version 7.4 is required.', 'woocommerce-german-market' ) . PHP_EOL;
	$main_info .= sprintf( __( 'Only PHP version %s is active on your server.', 'woocommerce-german-market' ), PHP_VERSION ) . PHP_EOL;
	$main_info .= __( 'Therefore, no girocode can be output with this function in the invoice PDF.', 'woocommerce-german-market' ) . PHP_EOL;
	$main_info .= __( 'Please ask your hoster / server admin if and how you can update to a current PHP version.', 'woocommerce-german-market' ) . '</div>';
}

$main_info 	.= __( 'With these settings, an "EPC QR Code" (GiroCode) can be added to the PDF invoices. This helps customers using a banking app to conveniently execute a transfer without having to enter the transfer data manually.', 'woocommerce-german-market' );
$main_info .= PHP_EOL . PHP_EOL . __( 'In order for the QR code to appear, the required data on the remittee must be entered and the payment methods for which the QR code should appear must be selected. The QR code supports only amounts in EUR and is displayed if the order has a total value greater than 0 EUR.', 'woocommerce-german-market' );
$main_info .= PHP_EOL . PHP_EOL . __( 'If a test PDF is downloaded from this submenu, the QR code will always appear.', 'woocommerce-german-market' );

$options[] = array( 'title' => __( 'GiroCode', 'woocommerce-german-market' ), 'type' => 'title','desc' => $main_info, 'id' => 'wp_wc_invoice_pdf_girocode_heading' );
$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_heading' );
		
//////////////////////////////////////////////////
// Remit Recipient
//////////////////////////////////////////////////
$remit_recipient_info = __( 'Please enter the data of the payee here. The required data is marked with an *. If not all required data is entered, the QR code cannot be output.', 'woocommerce-german-market' );

$options[] = array( 'title' => __( 'Remit Recipient', 'woocommerce-german-market' ), 'type' => 'title','desc' => $remit_recipient_info, 'id' => 'wp_wc_invoice_pdf_girocode_remit_recipient' );

$options[] = array(
				'name' 		=> __( 'Name', 'woocommerce-german-market' ) . '*',
				'id'   		=> 'wp_wc_invoice_pdf_girocode_remit_recipient_name',
				'type' 		=> 'text',
				'default'  	=> wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'css'      	=> 'width: 500px;',
				'desc_tip'	=> __( 'Name of the payment recipient', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'IBAN', 'woocommerce-german-market' ) . '*',
				'id'   		=> 'wp_wc_invoice_pdf_girocode_remit_recipient_iban',
				'type' 		=> 'text',
				'default'  	=> '',
				'css'      	=> 'width: 500px;',
				'desc_tip'	=> __( 'IBAN of the payment recipient', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'BIC', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_remit_recipient_bic',
				'type' 		=> 'text',
				'default'  	=> '',
				'css'      	=> 'width: 500px;',
				'desc_tip'	=> __( 'BIC of the payment recipient', 'woocommerce-german-market' )
			);

$placeholders = __( 'Customer\'s first name - <code>{{first-name}}</code>, customer\'s last name - <code>{{last-name}}</code>, Order Total - <code>{{order-total}}</code>, Order Number - <code>{{order-number}}</code>', 'woocommerce-german-market' );
if ( class_exists( 'Woocommerce_Running_Invoice_Number' ) ) {
	$placeholders .= ', ' . __( 'Invoice Number - <code>{{invoice-number}}</code>', 'woocommerce-german-market' );
}

$options[] = array(
				'name' 		=> __( 'Remittance Text', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_remit_remittance_text',
				'type' 		=> 'text',
				'default'  	=> __( 'Order {{order-number}}', 'woocommerce-german-market' ),
				'css'      	=> 'width: 500px;',
				'desc_tip'	=> __( 'Purpose of use that appears in the bank transfer.', 'woocommerce-german-market' ),
				'desc'		=> '<strong>' . __( 'The remittance text must not be longer than 140 characters.', 'woocommerce-german-market' ) . '</strong><br>' . __( 'You can use the following placeholders:', 'woocommerce-german-market' ) . ' ' . $placeholders,
			);

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_remit_recipient' );

//////////////////////////////////////////////////
// Payment methods output
//////////////////////////////////////////////////
$allowed_payment_methods = apply_filters( 'wp_wc_invoice_pdf_girocode_supported_gateways', array(
	'german_market_purchase_on_account' => __( 'Purchase On Acccount', 'woocommerce-german-market' ),
	'bacs'								=> __( 'Direct bank transfer', 'woocommerce-german-market' ),
));

$payment_method_info = __( 'Here you can select the payment methods for which the QR code should appear on the invoice pdf.', 'woocommerce-german-market' );

$options[] = array( 'title' => __( 'Payment Methods', 'woocommerce-german-market' ), 'type' => 'title','desc' => $payment_method_info, 'id' => 'wp_wc_invoice_pdf_girocode_payment_methods' );

foreach ( $allowed_payment_methods as $gateway_id => $gateway_name ) {

	$options[] = array(
					'name'		=> $gateway_name,
					'id'   		=> 'wp_wc_invoice_pdf_girocode_gateway_' . $gateway_id,
					'type' 		=> 'wgm_ui_checkbox',
					'default'  	=> 'off',
				);
}

if ( 'off' === get_option( 'wp_wc_invoice_pdf_avoid_payment_instructions', 'off' ) ) {
	$options[] = array(
					'name'		=> __( 'Hide Default Payment Instructions', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_hide_default_payment_instructions',
					'type' 		=> 'wgm_ui_checkbox',
					'default'  	=> 'off',
					'desc_tip'	=> __( 'By default, WooCommerce outputs payment instructions above the invoice table, depending on the order status. If this setting is enabled, the output of these payment instructions will be prevented for the activated payment methods. This is useful if you write the instructions in the "Text next to the QR code" in the following settings.', 'woocommerce-german-market' ),
				);
}

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_payment_methods' );

//////////////////////////////////////////////////
// Billing Countries
//////////////////////////////////////////////////

$billing_countries_info = __( 'Here you can select for which billing countries the QR code should appear on the invoice pdf.', 'woocommerce-german-market' );

$options[] = array( 'title' => __( 'Billing Countries', 'woocommerce-german-market' ), 'type' => 'title','desc' => $billing_countries_info, 'id' => 'wp_wc_invoice_pdf_girocode_billing_countries' );

$options[] = array(
				'name' 		=> __( 'Enable for Billing Countries', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_billing_countries_option',
				'type' 		=> 'select',
				'css'      	=> 'width: 500px;',
				'options'	=> array(
									'all' 			=> __( 'Enable for all billing countries', 'woocommerce-german-market' ),
									'all_except'	=> __( 'Enable for all billing countries, except for ...', 'woocommerce-german-market' ),
									'specific'		=> __( 'Enable for specific billing countries', 'woocommerce-german-market' ),
				),
				'class'		=> 'wc-enhanced-select',
				'default'	=> array(),
			);

$options[] = array(
				'name' 		=> __( 'Countries', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_billing_countries',
				'type' 		=> 'multiselect',
				'css'      	=> 'width: 500px;',
				'options'	=> WC()->countries->get_countries(),
				'class'		=> 'wc-enhanced-select',
				'default'	=> array(),
			);

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_billing_countries' );

//////////////////////////////////////////////////
// Output
//////////////////////////////////////////////////
$options[] = array( 'title' => __( 'Output', 'woocommerce-german-market' ), 'type' => 'title','desc' => '', 'id' => 'wp_wc_invoice_pdf_girocode_output' );

$options[] = array(
				'name' 		=> __( 'Position in Invoice PDF', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_position',
				'type' 		=> 'select',
				'default'  	=> 'after',
				'css'      	=> 'width: 250px;',
				'class'		=> 'wc-enhanced-select',
				'options' 	=> array(
									'before'	=> __( 'Before Invoice Table ', 'woocommerce-german-market' ),
									'after'		=> __( 'After Invoice Table', 'woocommerce-german-market' )
								)
			);

$options[] = array(
				'name' 		=> __( 'Priority', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_position_prio',
				'type' 		=> 'number',
				'default'  	=> '12',
				'custom_attributes' => array(
						'step'	=> 1,
						'min'	=> 0,
					),
				'css'      	=> 'width: 100px;',
				'desc_tip'	=> __( 'Other plugins may also output data at the desired output position. If this is the case, you can influence the position of the QR code with this setting. A smaller number will output the QR code further up, a larger number further down. By default, the output is with a priority of 10.', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'Alignment', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_alignment',
				'type' 		=> 'select',
				'default'  	=> 'after',
				'css'      	=> 'width: 250px;',
				'class'		=> 'wc-enhanced-select',
				'options' 	=> array(
									'left'	=> __( 'QR-Code left, Text right', 'woocommerce-german-market' ),
									'right'	=> __( 'QR-Code right, Text left', 'woocommerce-german-market' ),
								),
				'desc_tip'	=> __( 'Next to the QR code you can output a text, here you can set how text and QR code should be arranged.', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'Width and Height', 'woocommerce-german-market' ),
				'desc' 		=> $user_unit,
				'id'   		=> 'wp_wc_invoice_pdf_girocode_width',
				'type' 		=> 'number',
				'default'  	=> 1.5,
				'custom_attributes' => array(
						'step'	=> 0.1,
						'min'	=> 1,
					),
				'css'      	=> 'width: 100px;',
				'class'		=> 'german-market-unit',
				'desc_tip'	=> __( 'Square dimension of the QR code', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'Dark Color', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_dark_color',
				'type' 		=> 'color',
				'default'  	=> '#000000',
				'css'      	=> 'width: 100px;',
				'desc_tip'	=> __( 'By default, the QR code is black and white. You can select the color for the dark shade here.', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'Bright Color', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_bright_color',
				'type' 		=> 'color',
				'default'  	=> '#ffffff',
				'css'      	=> 'width: 100px;',
				'desc_tip'	=> __( 'By default, the QR code is black and white. You can select the color for the bright shade here.', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> _x( 'Margin', 'margin between cq code in invoice pdf and border', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_margin',
				'desc' 		=> $user_unit,
				'type' 		=> 'number',
				'default'  	=> '0.15',
				'custom_attributes' => array(
						'step'	=> 0.01,
						'min'	=> 0,
						'max'	=> 5,
					),
				'css'      	=> 'width: 100px;',
				'class'		=> 'german-market-unit',
				'desc_tip'	=> __( 'Distance between the QR code and the border defined in the following settings.', 'woocommerce-german-market' ),
			);

$options[] = array(
					'name' 		=> __( 'Border Color', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_border_color',
					'type' 		=> 'color',
					'default'  	=> '#000',
					'css'      	=> 'width: 100px;',
				);
			
$options[] =			array(
					'name' 		=> __( 'Border Width', 'woocommerce-german-market' ),
					'desc' 		=> 'px',
					'id'   		=> 'wp_wc_invoice_pdf_girocode_border_width',
					'type' 		=> 'number',
					'default'  	=> 1,
					'custom_attributes' => array(
						'step'	=> 1,
						'min'	=> 0,
						'max'	=> 10,
					),
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				);

$options[] = array(
				'name' 		=> __( 'ECC-Level', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_ecc_level',
				'type' 		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'  	=> 'L',
				'options' 	=> array(
								'L'	=> __( 'Level L - 7%', 'woocommerce-german-market' ),
								'M'	=> __( 'Level M - 15%', 'woocommerce-german-market' ),
								'Q'	=> __( 'Level Q - 25%', 'woocommerce-german-market' ),
								'H'	=> __( 'Level H - 30%', 'woocommerce-german-market' ),
							),
				'desc_tip'	=> __( 'This setting specifies the error correction level used. The percentage indicates what proportion of corrupted data in the QR code can be reconstructed. It is recommended to select the "L" setting here. Higher error correction levels can cause that the creation of the QR code and thus the invoice PDF to take longer, especially if "HTML" is selected as the output format.', 'woocommerce-german-market' ),
			);

$options[] = array(
				'name' 		=> __( 'Format', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_format',
				'type' 		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'  	=> 'svg',
				'options' 	=> array(
								'svg'	=> __( 'SVG', 'woocommerce-german-market' ),
								'jpg'	=> __( 'JPG', 'woocommerce-german-market' ),
								'html'	=> __( 'HTML', 'woocommerce-german-market' ),
							),
				'desc_tip'	=> __( 'On the one hand, the output format has a slight influence on the quality of the QR code, but above all on the time it takes to render the QR code in the invoice PDF. The "JPG" format can be rendered very quickly in the PDF, the "HTML" format takes a long time, especially if a high error correction level has been selected. The "HTML" output format produces slightly better quality results. In "JPG" format, the QR code can only be printed in black and white. We recommend the "SVG" setting.', 'woocommerce-german-market' ),
			);

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_output' );


//////////////////////////////////////////////////
// Texts
//////////////////////////////////////////////////

$fonts		= WP_WC_Invoice_Pdf_Helper::get_fonts();
$fonts		= array_keys( $fonts );
$fonts		= array_combine( $fonts, $fonts );

$options[] = array( 'title' => __( 'Text next to the QR code', 'woocommerce-german-market' ), 'type' => 'title','desc' => '', 'id' => 'wp_wc_invoice_pdf_girocode_text_next' );

$options[] = array(
				'name' 		=> __( 'Text next to the QR code', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_text',
				'type' 		=> 'wp_wc_invoice_pdf_textarea',
				'css'  		=> 'min-width: 500px; height: 100px;',
				'default'  	=> WGM_Helper::get_default_text_next_to_qr_code(),
				'desc'		=> __( 'You can use the following placeholders:', 'woocommerce-german-market' ) . ' ' . $placeholders . '<br><br>' . __( 'You can use HTML, following tags are allowed: <code>&lt;br/&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;h1&gt;</code>, <code>&lt;h2&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;u&gt;</code>, <code>&lt;ol&gt;</code>, <code>&lt;span&gt;</code>', 'woocommerce-german-market' ),
			);

$options[] = array(
					'name' 		=> __( 'Font', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_next_to_qr_code_font',
					'type' 		=> 'select',
					'class'		=> 'wc-enhanced-select',
					'default'  	=> 'Helvetica',
					'css'      	=> 'width: 250px;',
					'options' 	=> $fonts
				);
				
$options[] = array(
					'name' 		=> __( 'Font Size', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_next_to_qr_code_font_size',
					'type' 		=> 'select',
					'default'  	=> 10,
					'css'      	=> 'width: 100px;',
					'options' 	=> array_combine( self::$font_sizes, self::$font_sizes )
				);

$options[] = array(
					'name' 		=> __( 'Text Alignment', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_next_to_qr_code_text_align',
					'type' 		=> 'select',
					'default'  	=> 'left',
					'css'      	=> 'width: 250px;',
					'class'		=> 'wc-enhanced-select',
					'options' 	=> array(
										'left'	 => __( 'Left', 'woocommerce-german-market' ),
										'center' => __( 'Center', 'woocommerce-german-market' ),
										'right'  => __( 'Right', 'woocommerce-german-market' )
									)
				);

$options[] = array(
					'name' 		=> __( 'Vertical Text Alignment', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_next_to_qr_code_text_vertical_align',
					'type' 		=> 'select',
					'default'  	=> 'top',
					'css'      	=> 'width: 250px;',
					'class'		=> 'wc-enhanced-select',
					'options' 	=> array(
										'top'	 => __( 'Top', 'woocommerce-german-market' ),
										'middle' => __( 'Middle', 'woocommerce-german-market' ),
										'bottom' => __( 'Bottom', 'woocommerce-german-market' )
									)
				);
			
			
			// font color
$options[] = array(
					'name' 		=> __( 'Text Color', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_next_to_qr_code_color',
					'type' 		=> 'color',
					'default'  	=> '#000',
					'css'      	=> 'width: 100px;',
				);

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_text_next' );

$options[] = array( 'title' => __( 'Text under to the QR code', 'woocommerce-german-market' ), 'type' => 'title','desc' => '', 'id' => 'wp_wc_invoice_pdf_girocode_text_next' );

$options[] = array(
				'name' 		=> __( 'Text under to the QR code', 'woocommerce-german-market' ),
				'id'   		=> 'wp_wc_invoice_pdf_girocode_text_under',
				'type' 		=> 'text',
				'css'      	=> 'width: 500px;',
				'default'  	=> __( 'Girocode', 'woocommerce-german-market' ),
			);

$options[] = array(
					'name' 		=> __( 'Font', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_under_qr_code_font',
					'type' 		=> 'select',
					'class'		=> 'wc-enhanced-select',
					'default'  	=> 'Helvetica',
					'css'      	=> 'width: 250px;',
					'options' 	=> $fonts
				);
				
$options[] = array(
					'name' 		=> __( 'Font Size', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_under_qr_code_font_size',
					'type' 		=> 'select',
					'default'  	=> 8,
					'css'      	=> 'width: 100px;',
					'options' 	=> array_combine( self::$font_sizes, self::$font_sizes )
				);

$options[] = array(
					'name' 		=> __( 'Text Alignment', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_under_qr_code_text_align',
					'type' 		=> 'select',
					'default'  	=> 'center',
					'css'      	=> 'width: 250px;',
					'class'		=> 'wc-enhanced-select',
					'options' 	=> array(
										'left'	 => __( 'Left', 'woocommerce-german-market' ),
										'center' => __( 'Center', 'woocommerce-german-market' ),
										'right'  => __( 'Right', 'woocommerce-german-market' )
									)
				);
			
			
			// font color
$options[] = array(
					'name' 		=> __( 'Text Color', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_girocode_text_under_qr_code_color',
					'type' 		=> 'color',
					'default'  	=> '#000',
					'css'      	=> 'width: 100px;',
				);

$options[] = array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_girocode_text_next' );
