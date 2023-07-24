<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$options	= array(	
				array(	'name'	=> __( 'Test Invoice', 'woocommerce-german-market' ), 'type' => 'wp_wc_invoice_pdf_test_download_button' ),	
				
				array( 'title'	=> __( 'General Header Settings', 'woocommerce-german-market' ), 'type' => 'title','desc' => '', 'id' => 'wp_wc_invoice_pdf_general_header_settings' ),

				array(
					'name' 		=> __( 'Height', 'woocommerce-german-market' ),
					'desc' 		=> $user_unit,
					'desc_tip'	=> __( 'Height of the header, measured from the top of the page, regardless of the following margin settings, i.e. the height has to be greater than margin top plus margin bottom', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Height of the header, measured from the top of the page, regardless of the following margin settings, i.e. the height has to be greater than margin top plus margin bottom', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_height',
					'type' 		=> 'text',
					'default'  	=> 0,
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				),
				
				array(
					'name' 		=> __( 'Margin Top', 'woocommerce-german-market' ),
					'desc' 		=> $user_unit,
					'desc_tip'	=> __( 'Margin between the top of the page and beginning of the header content', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Margin between the top of the page and beginning of the header content', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_padding_top',
					'type' 		=> 'text',
					'default'  	=> '0',
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				),
				
				array(
					'name' 		=> __( 'Margin Right', 'woocommerce-german-market' ),
					'desc' 		=> $user_unit,
					'desc_tip'	=> __( 'Space between the right page margin and the header content', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Space between the right page margin and the header content', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_padding_right',
					'type' 		=> 'text',
					'default'  	=> '0',
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				),
				
				array(
					'name' 		=> __( 'Margin Bottom', 'woocommerce-german-market' ),
					'desc' 		=> $user_unit,
					'desc_tip'	=> __( 'Margin between the header content and the the bottom of the header', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Margin between the header content and the the bottom of the header', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_padding_bottom',
					'type' 		=> 'text',
					'default'  	=> '0',
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				),
				
				array(
					'name' 		=> __( 'Margin Left', 'woocommerce-german-market' ),
					'desc' 		=> $user_unit,
					'desc_tip'	=> __( 'Space between the left page margin and the header content', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Space between the left page margin and the header content', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_padding_left',
					'type' 		=> 'text',
					'default'  	=> '0',
					'css'      	=> 'width: 100px;',
					'class'		=> 'german-market-unit',
				),
				
				array(
					'name' 		=> __( 'Background Color', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Choose the background color of the header, leave empty to use no background color', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Choose the background color of the header, leave empty to use no background color', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_background_color',
					'type' 		=> 'color',
					'default'  	=> '',
					'css'      	=> 'width: 100px;',
				),
				
				array(
					'name' 		=> __( 'Number of Columns', 'woocommerce-german-market' ),
					'desc_tip' 	=> __( 'Number of columns in the header, click the save button to update this page after you changed this option', 'woocommerce-german-market' ),
					'tip'  		=> __( 'Number of columns in the header, click the save button to update this page after you changed this option', 'woocommerce-german-market' ),
					'id'   		=> 'wp_wc_invoice_pdf_header_number_of_columns',
					'type' 		=> 'select',
					'default'  	=> 1,
					'css'      	=> 'width: 100px;',
					'options' 	=> array(
									1 	=> 1,
									2	=> 2,
									3	=> 3,
									4	=> 4,
									5	=> 5,
									6	=> 6,
									7	=> 7,
									8	=> 8,
									9	=> 9,
									10	=> 10
								)
				),
											
				array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_general_header_settings' )

			);

// columns
$number_of_columns = isset( $_REQUEST[ 'wp_wc_invoice_pdf_header_number_of_columns' ] ) ? $_REQUEST[ 'wp_wc_invoice_pdf_header_number_of_columns' ] : get_option( 'wp_wc_invoice_pdf_header_number_of_columns', 1 );
for ( $i = 1; $i <= $number_of_columns; $i++ ) {
	$part = 'header';
	include( 'helper-options-section-output-one-column.php' );
	$options = array_merge( $options, $column );
}
					