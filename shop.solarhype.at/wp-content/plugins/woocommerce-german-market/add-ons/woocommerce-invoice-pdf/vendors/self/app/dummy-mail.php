<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Dummy_Mail' ) ) {

    if( ! class_exists( 'WC_Email' ) ){
        // Initialize mailer
        WC()->mailer();
    }

    /**
     * dummy email for invoice pdf to use actions:
     * woocommerce_email_before_order_table
     * woocommerce_email_after_order_table
     * woocommerce_email_order_meta
     *
     * @class 		WP_WC_Invoice_Pdf_Dummy_Mail
     * @version		1.0
     * @extends 	WC_Email
     */
    class WP_WC_Invoice_Pdf_Dummy_Mail extends WC_Email {

        /**
         * Constructor
         */
        function __construct() {
            $this->id 				    = 'wp_wc_invoice_odf_dummy_mail';
            // Call parent constructor
            parent::__construct();
        }
    }
}
