<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//////////////////////////////////////////////////
// init
//////////////////////////////////////////////////

$description = __( 'Here you have got the option to bind one image to the header, one image to the footer and one image to the background of your invoice pdf', 'woocommerce-german-market' ) . '.<br /><br />' . __( 'Click the upload button to use the media uploader. Supported file formats are JPEG, PNG and GIF. Please notice that an image with a large file size will cause an invoice with a large file size and creating the pdf will take longer. So it is recommended to choose an image with a small file size. Uploading images of large size may cause a PHP memory size error. PNG files with transparency take a long time to be rendered, we recommend to use PNG files without transparency.', 'woocommerce-german-market' );
					
$image_upload_button = '<button type="button" class="button-secondary" id="wp_wc_invoice_pdf_image_upload_button_PART" style="margin: 3px 0;">' . __( 'Image upload', 'woocommerce-german-market' ) . '</button>';
$image_remove_button = '<button type="button" class="button-secondary" id="wp_wc_invoice_pdf_image_remove_button_PART" style="margin: 3px 3px;">' . __( 'Remove image', 'woocommerce-german-market' ) . '</button>';
				
$options	= array(	
				array( 'name'	=> __( 'Test Invoice', 'woocommerce-german-market' ), 'type' => 'wp_wc_invoice_pdf_test_download_button' ),
				
				array( 'title' 	=> __( 'Images', 'woocommerce-german-market' ), 'type' => 'title','desc' => $description, 'id' => 'wp_wc_invoice_pdf_images' ),

				'is_remote_enabled' => array(
					'name'     => __( 'Allow Remote Image Scources', 'woocommerce-german-market' ),
					'desc_tip' => __( 'As default, it is only allowed to include images from your own media library from your own server. Enable this option to allow other image scources. If enabled you can enter any image URL. For security reasons it is recommended to keep this option off to include only files from your own server.', 'woocommerce-german-market' ),
					'id'       => 'wp_wc_invoice_pdf_image_remote_scources',
					'type'     => 'wgm_ui_checkbox',
					'default'  => 'off',
				),

				array( 'type' 	=> 'sectionend', 'id' => 'wp_wc_invoice_pdf_images' )
			);

// if custom google fonts are used, we have to enable remote at anyway
// since dompdf 1.0.1 / GM 3.10.6.0.1-Support
if ( ! empty( trim( get_option( 'wp_wc_invoice_pdf_custom_fonts', '' ) ) ) ) {
	unset( $options[ 'is_remote_enabled' ] );
}
	
$parts		= array( 'header' => __('Header', 'woocommerce-german-market' ), 'footer' => __( 'Footer', 'woocommerce-german-market' ), 'background' => __( 'Background', 'woocommerce-german-market' ) );
$parts		= apply_filters( 'wp_wc_invoice_pdf_image_parts', $parts );

$position_desc_helper	= array(
							'header'		=> __( '(in the header)', 'woocommerce-german-market' ),
							'footer'		=> __( '(in the footer)', 'woocommerce-german-market' ),
							'background'	=> __( '(as the background image)', 'woocommerce-german-market' ),
						);
																
$height_desc_helper		= array(
							'header'		=> __( 'Height of the image in the header. If you leave this field blank or enter 0, the height will have the same height as the header, regarding the margin settings you have made on the header option page', 'woocommerce-german-market' ),
							'footer'		=> __( 'Height of the image in the footer. If you leave this field blank or enter 0, the height will have the same height as the footer, regarding the margin settings you have made on the footer option page', 'woocommerce-german-market' ),
							'background'	=> __( 'Height of the background image. If you leave this field blank or enter 0, the height will be the same as your page height, regardless of the page margins ', 'woocommerce-german-market' )
						);					

//////////////////////////////////////////////////
// options
//////////////////////////////////////////////////

foreach ( $parts as $part_key => $part ) {				
	
	$desc_info = '';

	if ( $part_key == 'header' ) {

		$url  = admin_url() . 'admin.php?page=german-market&tab=invoice-pdf&sub_tab=header';
		$desc_info = '<b>' . sprintf( __( 'Please note that when uploading a header image, you must have the set a corresponding heigh in the <a href="%s">header</a>.', 'woocommerce-german-market' ), $url ) . '</b>';
	
	} else if ( $part_key == 'footer' ) {

		$url  = admin_url() . 'admin.php?page=german-market&tab=invoice-pdf&sub_tab=footer';
		$desc_info = '<b>' . sprintf( __( 'Please note that when uploading a footer image, you must have the set a corresponding height in the <a href="%s">footer</a>.', 'woocommerce-german-market' ), $url ) . '</b>';

	}

	$options[]	= array( 'title' => $part, 'type' => 'title','desc' => '', 'id' => 'wp_wc_invoice_pdf_image' . $part_key, 'desc' => $desc_info );

	$remote = false;
	
	if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
		$remote = isset( $_REQUEST[ 'wp_wc_invoice_pdf_image_remote_scources' ] );
	} else {
		$remote = get_option( 'wp_wc_invoice_pdf_image_remote_scources', 'off' ) == 'on';
	}

	if ( ! empty( trim( get_option( 'wp_wc_invoice_pdf_custom_fonts', '' ) ) ) ) {
		$remote = true;
	}

	$readonly = ( ! $remote ) ? array( 'readonly' => 'readonly' ) : '';

	$options[]	= array(
					'name' 		=> __( 'Image File', 'woocommerce-german-market' ),
					'desc'		=> str_replace( 'PART', $part_key, $image_upload_button ) . str_replace( 'PART', $part_key, $image_remove_button ),
					'id'   		=> 'wp_wc_invoice_pdf_image_url_' . $part_key,
					'type'		=> 'text',
					'default'  	=> '',
					'css'      	=> 'min-width:500px;',
					'custom_attributes' => $readonly,
				);
				
	$options[]	= array(
					'name' 		=> __( 'Position', 'woocommerce-german-market' ),
					'desc_tip'	=> __( 'Choose the position where your image should be displayed', 'woocommerce-german-market' ) . ' ' . $position_desc_helper[ $part_key ],
					'tip'  		=> __( 'Choose the position where your image should be displayed', 'woocommerce-german-market' ) . ' ' . $position_desc_helper[ $part_key ],
					'id'   		=> 'wp_wc_invoice_pdf_image_position_' . $part_key,
					'type' 		=> 'select',
					'default'  	=> ( $part_key == 'background' ) ? 'middle_center' : 'top_right',
					'options' 	=> array(
									'top_left'		=> __( 'Top Left', 'woocommerce-german-market' ),
									'top_center'	=> __( 'Top Center', 'woocommerce-german-market' ),
									'top_right'		=> __( 'Top Right', 'woocommerce-german-market' ),
									'middle_left'	=> __( 'Middle Left', 'woocommerce-german-market' ),
									'middle_center'	=> __( 'Middle Center', 'woocommerce-german-market' ),
									'middle_right'	=> __( 'Middle Right', 'woocommerce-german-market' ),
									'bottom_left'	=> __( 'Bottom Left', 'woocommerce-german-market' ),
									'bottom_center'	=> __( 'Bottom Center', 'woocommerce-german-market' ),
									'bottom_right'	=> __( 'Bottom Right', 'woocommerce-german-market' )																																
									)
	
				);
					
	$options[]	= array(
						'name' 		=> __( 'Height', 'woocommerce-german-market' ),
						'desc' 		=> $user_unit,
						'desc_tip'	=> $height_desc_helper[ $part_key ],
						'tip'  		=> $height_desc_helper[ $part_key ],
						'id'   		=> 'wp_wc_invoice_pdf_image_height_' . $part_key,
						'type' 		=> 'text',
						'default'  	=> '',
						'css'      	=> 'width: 100px;',
						'class'		=> 'german-market-unit',
					);
	
	$options[]	= array(
						'name' 		=> __( 'Width', 'woocommerce-german-market' ),
						'desc' 		=> $user_unit,
						'desc_tip'	=> __( 'Width of the image, if you leave this field blank or enter 0, the width will be automatically calculated (recommended)', 'woocommerce-german-market' ),
						'tip'  		=> $height_desc_helper[ $part_key ],
						'id'   		=> 'wp_wc_invoice_pdf_image_width_' . $part_key,
						'type' 		=> 'text',
						'default'  	=> '',
						'css'      	=> 'width: 100px;',
						'class'		=> 'german-market-unit',
					);
	
	$options[]	= array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_image' . $part_key );
}
