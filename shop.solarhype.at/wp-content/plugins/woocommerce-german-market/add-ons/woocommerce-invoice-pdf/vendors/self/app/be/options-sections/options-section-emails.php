<?php
$options = array(

	array(
		'title'    => __( 'Format', 'woocommerce-german-market' ),
	    'type'     => 'title',
	    'desc'     => __( 'Choose if you want send the invoice as an attachment or if the customer can download it via link.', 'woocommerce-german-market' ),
	    'id'       => 'wp_wc_invoice_pdf_emails_format',
	),

	array(
		'name'     => __( 'Format', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_attachment_format',
		'type'     => 'select',
		'options'  => array(
			'attachment' => __( 'Email Attachment', 'woocommerce-german-market' ),
			'link'       => __( 'Download Link', 'woocommerce-german-market' ),
		),
		'default'  => 'attachment',
		'class'    => 'wc-enhanced-select',
	),

	array(
		'name'     => __( 'Position in Email', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Where do you want display the text and link in the email.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_link_position',
		'type'     => 'select',
		'options'  => array(
			'before_details' => __( 'Before order details', 'woocommerce-german-market' ),
			'after_details'  => __( 'After order details', 'woocommerce-german-market' ),
		),
		'default'  => 'after_details',
		'class'    => 'wc-enhanced-select',
	),

	array(
		'name'     => __( 'Invoices - Download Link Label Text', 'woocommerce-german-market' ),
		'desc_tip' => __( 'This is the label text for the download link. If left blank, the download url will be used instead.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_link_label_text',
		'type'     => 'text',
		'default'  => __( 'Download Invoice Pdf', 'woocommerce-german-market' ),
	),

	array(
		'name'     => __( 'Invoices - Text for Your Email Template', 'woocommerce-german-market' ),
		'desc'     => __( 'In this option you can define your own text in the email template for invoices. Use <code>{invoice_download_link}</code> placeholder to place the download link.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_link_text',
		'type'     => 'german_market_textarea',
		'default'  => __( 'Please use the following link to download your invoice PDF: {invoice_download_link}', 'woocommerce-german-market' ),
		'css'      => 'width: 400px;',
	),

	array(
		'name'     => __( 'Refunds - Download Link Label Text', 'woocommerce-german-market' ),
		'desc_tip' => __( 'This is the label text for the refunds download link. If left blank, the download url will be used instead.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_refunds_link_label_text',
		'type'     => 'text',
		'default'  => __( 'Download refund pdf', 'woocommerce-german-market' ),
	),

	array(
		'name'     => __( 'Refunds - Text for Your Email Template', 'woocommerce-german-market' ),
		'desc'     => __( 'In this option you can define your own text in the email template for refunds. Use <code>{invoice_download_link}</code> shortcode to place the download link.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_refunds_link_text',
		'type'     => 'german_market_textarea',
		'default'  => __( 'Please use the following link to download your refund PDF: {invoice_download_link}', 'woocommerce-german-market' ),
		'css'      => 'width: 400px;',
	),

	array(
		'name' 		=> __( 'Download Behaviour', 'woocommerce-german-market' ),
		'desc_tip' 	=> __( 'If "Download" is selected the browser forces a file download. If "Inline" is selected the file will be send inline to the browser, i.e. the browser will try to open the file in a tab using a browser plugin to display pdf files if available', 'woocommerce-german-market' ),
		'id'   		=> 'wp_wc_invoice_pdf_emails_link_download_behaviour',
		'type' 		=> 'select',
		'css'  		=> 'min-width:250px;',
		'default'  	=> 'inline',
		'options' 	=> array(
			'inline'	=> __( 'Inline', 'woocommerce-german-market' ),
			'download'	=> __( 'Download', 'woocommerce-german-market' ),
				)
		),

	array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_emails_format' ),

	array(
		'title'    => __( 'Emails', 'woocommerce-german-market' ),
	    'type'     => 'title',
	    'desc' 	   => __( 'Select the emails that should contain the invoice.', 'woocommerce-german-market' ),
	    'id'       => 'wp_wc_invoice_pdf_emails',
	),

	array(
		'name'     => __( 'Customer Order Confirmation', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "Customer Order Confirmation" email', 'woocommerce-german-market' ) . '<br />' . __( 'Customer Order Confirmation emails are sent after a successful customer order.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "Customer Order Confirmation" email', 'woocommerce-german-market' ) . '<br />' . __( 'Customer Order Confirmation emails are sent after a successful customer order', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_order_confirmation',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'New Order', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "New Order" email', 'woocommerce-german-market' ) . '<br />' . __( 'New order emails are sent to chosen recipient(s) when an order is received.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "New Order" email', 'woocommerce-german-market' ) . '<br />' . __( 'New order emails are sent to chosen recipient(s) when an order is received.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_new_order',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Customer Invoice', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "Customer invoice" email', 'woocommerce-german-market' ) . '<br />' . __( 'Customer invoice emails can be sent to the user containing order info and payment links.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "Customer invoice" email', 'woocommerce-german-market' ) . '<br /> ' . __( 'Customer invoice emails can be sent to the user containing order info and payment links.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_invoice',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Customer On-Hold', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "Customer on-hold" email', 'woocommerce-german-market' ) . '<br />' . __( 'Customer on-hold emails can be sent to customers containing order details after an order is placed on-hold.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "Customer on hold" email', 'woocommerce-german-market' ) . '<br /> ' . __( 'Customer on-hold emails can be sent to customers containing order details after an order is placed on-hold.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_on_hold_order',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Customer Processing Order', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "Customer processing order" email', 'woocommerce-german-market' ) . '<br />' . __( 'This is an order notification sent to the customer after payment containing order details.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "Customer processing order" email', 'woocommerce-german-market' ) . '<br />' . __( 'This is an order notification sent to the customer after payment containing order details.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_processing_order',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Customer Completed Order', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add invoice as an attachment to "Customer completed order" email', 'woocommerce-german-market' ) . '<br />' . __( 'Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.', 'woocommerce-german-market' ),
		'tip'      => __( 'Add invoice as an attachment to "Customer completed order" email', 'woocommerce-german-market' ) . '<br /> ' . __( 'Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_completed_order',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Refunded Order', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Add refund pdf as an attachment to "Customer refunded order" email', 'woocommerce-german-market' ) . '<br />' . __( 'Order refunded emails are sent to the customer when the order is marked refunded', 'woocommerce-german-market' ),
		'tip'      => __( 'Add refund pdf as an attachment to "Customer refunded order" email', 'woocommerce-german-market' ) . '<br />' . __( 'Order refunded emails are sent to the customer when the order is marked refunded', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_refunded_order',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),

	array(
		'name'     => __( 'Customer Note', 'woocommerce-german-market' ),
		'desc_tip' => __( 'Customer note emails are sent when you add a note to an order.', 'woocommerce-german-market' ),
		'tip'      => __( 'Customer note emails are sent when you add a note to an order.', 'woocommerce-german-market' ),
		'id'       => 'wp_wc_invoice_pdf_emails_customer_note',
		'type'     => 'wgm_ui_checkbox',
		'default'  => 'off',
	),


	array( 'type' => 'sectionend', 'id' => 'wp_wc_invoice_pdf_emails' ),

);

$options = apply_filters( 'gm_invoice_pdf_email_settings', $options );
