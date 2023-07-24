<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WGM_Email_Confirm_Order' ) ) :

    if( ! class_exists( 'WC_Email' ) ){
        // Initialize mailer
        WC()->mailer();
    }

    /**
     * Customer Processing Order Email
     *
     * An email sent to the customer when a new order is received/paid for.
     *
     * @class 		WGM_Email_Confirm_Order
     * @version		2.0.0
     * @package		WooCommerce/Classes/Emails
     * @author 		WooThemes
     * @extends 	WC_Email
     */
    class WGM_Email_Confirm_Order extends WC_Email {

        private $wgm_template_path;

        /**
         * Constructor
         */
        function __construct() {

            $this->id 				    = 'customer_order_confirmation';
            $this->title 		    	= __( 'Order Confirmation', 'woocommerce-german-market' );
            $this->description		    = __( 'Order confirmation e-mail sent to customers.', 'woocommerce-german-market' );

            $this->heading 			    = get_option( 'gm_order_confirmation_mail_heading', __( 'Order Confirmation', 'woocommerce-german-market' ) );
            $this->subject      	    = get_option( 'gm_order_confirmation_mail_subject', __( 'Your {site_title} order confirmation from {order_date}', 'woocommerce-german-market' ) );

            $this->template_html 	    = 'customer-confirm-order.php';
            $this->template_plain 	    = 'plain/customer-confirm-order.php';
            $this->wgm_template_path    = Woocommerce_German_Market::$plugin_path . 'templates/woocommerce-german-market/emails/';

            // Triggers for this email
            add_action( 'wgm_order_confirmation_mail_trigger', array( $this, 'trigger' ) );

            // Call parent constructor
            parent::__construct();

            do_action( 'wgm_email_confirm_order_after_construct', $this );
        }

	    /**
	     * get_type function.
	     *
	     * @return string
	     */
	    public function get_email_type() {

		    return ( get_option( 'wgm_plain_text_order_confirmation_mail', 'off' ) === 'off' ) ? 'html' : 'plain';
	    }

        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger( $order_id ) {

            if ( $order_id ) {
                $this->object 		= wc_get_order( $order_id );
                $this->recipient	= $this->object->get_billing_email();

                $this->find['order-date']      = '{order_date}';
                $this->find['order-number']    = '{order_number}';

                $this->replace['order-date']   = wc_format_datetime( $this->object->get_date_created() );
                $this->replace['order-number'] = $this->object->get_order_number();
            }

            if ( ! $this->get_recipient() ) {
                return;
            }

            // Plugin Compability: Site Origin Page Builder
            $site_origin_on_or_off = apply_filters( 'siteorigin_panels_filter_content_enabled', true );
            add_filter( 'siteorigin_panels_filter_content_enabled', '__return_false' );

            $content = $this->get_content();

            $headers = $this->get_headers();
            $headers = apply_filters( 'woocommerce_de_header_order_confirmation_mail', $headers );

            if ( apply_filters( 'gm_email_confirm_order_send_it', true, $this->object ) ) {
                $this->send( $this->get_recipient(), apply_filters( 'gm_email_confirm_order_subject', $this->get_subject(), $this->object) , $content, $headers, $this->get_attachments() );
                do_action( 'gm_email_confirm_order_after_send', $this, $content, $headers );
            }

            if ( $site_origin_on_or_off ) {
                add_filter( 'siteorigin_panels_filter_content_enabled', '__return_true' );
            }
          
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
                'order'         => $this->object,
                'email_heading' => apply_filters( 'gm_email_confirm_order_heading', $this->get_heading(), $this->object ),
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
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
                'order'         => $this->object,
                'email_heading' => apply_filters( 'gm_email_confirm_order_heading', $this->get_heading() ),
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            ) );

            include( $template_file );
            return ob_get_clean();
        }
    }

endif;

return new WGM_Email_Confirm_Order();
