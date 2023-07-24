<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WGM_Email_Double_Opt_In_Customer_Registration' ) ) :

    if( ! class_exists( 'WC_Email' ) ){
        // Initialize mailer
        WC()->mailer();
    }

    /**
     * Double Opt-in Customer Registration Email
     *
     * @class 		WGM_Email_Double_Opt_In_Customer_Registration
     * @version		2.1.0
     * @package		WooCommerce/Classes/Emails
     * @author 		MarketPress
     * @extends 	WC_Email
     */
    class WGM_Email_Double_Opt_In_Customer_Registration extends WC_Email {

        private $wgm_template_path;
        private $activation_link;
        private $user_login;
        /**
         * Constructor
         */
        function __construct() {

            $this->id 				    = 'double_opt_in_customer_registration';
            $this->title 		    	= __( 'Double Opt-in Customer Registration', 'woocommerce-german-market' );
            $this->description		    = __( 'This email is sent to the customer after the account creation. It contains an activation link, which the customer must open to complete the account creation.', 'woocommerce-german-market' );

            $this->heading 			    = apply_filters( 'wgm_double_opt_in_activation_email_heading', __( 'Activate your account - {site_title}', 'woocommerce-german-market' ) );
            $this->subject      	    = apply_filters( 'wgm_double_opt_in_activation_email_subject', __( 'Activate your account - {site_title}', 'woocommerce-german-market' ) );

            $this->template_html 	    = 'double-opt-in-customer-registration.php';
            $this->template_plain 	    = 'plain/double-opt-in-customer-registration.php';
            $this->wgm_template_path    = Woocommerce_German_Market::$plugin_path . 'templates/woocommerce-german-market/emails/';

            $this->customer_id          = null;
            $this->resend               = false;

            // Triggers for this email
            add_action( 'wgm_order_double_opt_in_customer_registration', array( $this, 'trigger' ) );

            // Call parent constructor
            parent::__construct();
        }

	    /**
	     * get_type function.
	     *
	     * @return string
	     */
	    public function get_email_type() {

		    return ( get_option( 'wgm_plain_text_double_opt_in_customer_registration', 'off' ) === 'off' ) ? 'html' : 'plain';
	    }

        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger( $customer_id, $activation_link, $user_email, $user_login, $user_pass = '', $resend = false ) {
            
            $this->customer_id = $customer_id;
            
            // set recipient
            if ( trim( $user_email != '' ) ) {
                 $this->recipient = $user_email;
            }

            if ( ! $this->get_recipient() ) {
                return;
            }

            // set activation link
            $this->activation_link = $activation_link;
           
            // set user login
            $this->user_login = $user_login;

            // set user_pass
            $this->user_pass = $user_pass;

            // set resend
            $this->resend = $resend;

            $content = $this->get_content();
           
            $sending = $this->send( $this->get_recipient(), $this->get_subject(), $content, $this->get_headers(), $this->get_attachments() );

        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {

            // get the template file
            $template_file = WGM_Template::locate_template( 'emails/' . $this->template_html );

            ob_start();

            // extract needed vars
            extract( array(
                'email_heading'     => $this->get_heading(),
                'user_login'        => $this->user_login,
                'user_pass'         => $this->user_pass,
                'resend'            => $this->resend,
                'activation_link'   => $this->activation_link,
                'sent_to_admin'     => false,
                'plain_text'        => false,
                'email'             => $this,
            ) );

            include( $template_file );
            return ob_get_clean();
        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {

            // get the template file
            $template_file = WGM_Template::locate_template( 'emails/' . $this->template_plain );

            ob_start();

            // extract needed vars
            extract( array(
                'email_heading'     => $this->get_heading(),
                'user_login'        => $this->user_login,
                'user_pass'         => $this->user_pass,
                'resend'            => $this->resend,
                'activation_link'   => $this->activation_link,
                'sent_to_admin'     => false,
                'plain_text'        => true,
                'email'             => $this,
            ) );

            include( $template_file );
            return ob_get_clean();
        }
    }

endif;

return new WGM_Email_Double_Opt_In_Customer_Registration();
