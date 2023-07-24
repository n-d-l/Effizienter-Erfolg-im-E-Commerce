<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'German_Market_Email_Sepa' ) ) :

    if( ! class_exists( 'WC_Email' ) ){
        // Initialize mailer
        WC()->mailer();
    }

    /**
     * Sepa Mandat Email
     *
     * An email sent to the customer when a new order is received
     *
     * @class       WGM_Email_Sepa
     * @version     1.0
     * @extends     WC_Email
     */
    class German_Market_Email_Sepa extends WC_Email {

        /**
         * Constructor
         */
        function __construct() {

            $this->id                   = 'german_market_email_sepa';
            $this->title                = __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' );
            $this->description          = __( 'SEPA Direct Debit Mandate sent to customers', 'woocommerce-german-market' );

            $this->heading              = __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' );
            $this->subject              = __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' );

            $this->args                 = array();

            $this->settings_type        = 'html';

            // Call parent constructor
            parent::__construct();
        }

        /**
         * get_type function.
         *
         * @return string
         */
        public function get_email_type() {

            return $this->settings_type;
        }

        /**
         * trigger function.
         *
         * @access public
         * @return void
         */
        function trigger( $order_id ) {

            // Plugin Compability: Site Origin Page Builder
            $site_origin_on_or_off = apply_filters( 'siteorigin_panels_filter_content_enabled', true );
            add_filter( 'siteorigin_panels_filter_content_enabled', '__return_false' );

            if ( $order_id ) {
                $this->object       = wc_get_order( $order_id );
                $this->recipient    = $this->object->get_billing_email();
            }

            if ( ! $this->get_recipient() ) {
                return;
            }

            // Do not send any emails
            if ( apply_filters( 'german_market_sepa_email_dont_send_emails', false ) ) {
                return;
            }

            $content = $this->get_content();

            $headers = $this->get_headers();
            $headers = apply_filters( 'woocommerce_de_header_sepa_mail', $headers );
            $attachments = $this->get_attachments();

            $file_path = null;

            // load invoice pdf class if add-on is deactivated
            if ( ( $this->args[ 'email_pdf' ] != 'no' ) && ! class_exists( 'Woocommerce_Invoice_Pdf' ) ) {

                include_once( WGM_ADD_ONS_PATH . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf.php' );

                // maybe load default pdf settings
                if ( get_option( 'wp_wc_invoice_pdf_document_title', false ) === false ) {
                    WP_WC_Invoice_Pdf_Backend_Activation::load_defaults();
                }

            }

            // generate sepa pdf for customer
            if ( $this->args[ 'email_pdf' ] == 'admin_and_customer' || $this->args[ 'email_pdf' ] == 'customer' ) {
            
                add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( __CLASS__, 'pdf_content' ) );
                $args = array(
                    'order'             => $this->object,
                    'output_format'     => 'pdf',
                    'output'            => 'cache',
                    'filename'          => __( 'SEPA Mandate', 'woocommerce-german-market' ),
                    'sepa_args'         => $this->args,
                );
                $sepa_pdf              = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
                $file_path = WP_WC_INVOICE_PDF_CACHE_DIR . $sepa_pdf->cache_dir . DIRECTORY_SEPARATOR . $sepa_pdf->filename;
                remove_filter( 'wp_wc_invoice_pdf_template_invoice_content',  array( __CLASS__, 'pdf_content' ) );
            }

            if ( ! is_null( $file_path ) ) {
                $attachments[] = $file_path;
            }

            if ( apply_filters( 'german_market_sepa_email_send_customer_mail', true ) ) {
                $this->send( $this->get_recipient(), $this->get_subject(), $content, $headers, $attachments );
            }
           
            // send admin email
            if ( $this->args[ 'email_admin' ] == 'customer_and_admin' ) {
                
                // unmasked iban if set so in admin email
                if ( $this->args[ 'email_admin_iban_mask' ] == 'off' ) {
                    $this->args[ 'iban' ] = $this->args[ 'unmasked_iban' ];
                    $content = $this->get_content();
                }

                // generate sepa pdf for admin (is may different: with unmasked iban)
                if ( $this->args[ 'email_pdf' ] == 'admin_and_customer' || $this->args[ 'email_pdf' ] == 'admin' ) {
                
                    add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( __CLASS__, 'pdf_content' ) );
                    $args = array(
                        'order'             => $this->object,
                        'output_format'     => 'pdf',
                        'output'            => 'cache',
                        'filename'          => __( 'SEPA Mandate', 'woocommerce-german-market' ),
                        'sepa_args'         => $this->args,
                    );
                    $sepa_pdf = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
                    $admin_attachments = $this->get_attachments();
                    $file_path = WP_WC_INVOICE_PDF_CACHE_DIR . $sepa_pdf->cache_dir . DIRECTORY_SEPARATOR . $sepa_pdf->filename;
                    remove_filter( 'wp_wc_invoice_pdf_template_invoice_content',  array( __CLASS__, 'pdf_content' ) );

                }

                // reset this arg, may used later masked
                $this->args[ 'iban' ] = $this->args[ 'iban' ];

                $admin_email           = $this->args[ 'email_admin_address' ];
                $admin_subject_suffix  = apply_filters( 'gm_sepa_admin_email_subject_suffic', __( 'for order', 'woocommerce-german-market' ) . ' ' . $order_id );

                if ( ! is_null( $file_path ) ) {
                    $admin_attachments[] = $file_path;
                }

                $this->send( $admin_email,  $this->get_subject() . ' ' . $admin_subject_suffix, $content, $headers, $admin_attachments );
            }

            if ( $site_origin_on_or_off ) {
                 add_filter( 'siteorigin_panels_filter_content_enabled', '__return_true' );
            }

        }

        /**
        * Set Args
        **/
        function set_args( $args ) {
            $this->args = $args;

            if ( isset( $args[ 'email_subject' ] ) ) {
                $this->subject = $args[ 'email_subject' ];
            }

            if ( isset( $args[ 'email_heading' ] ) ) {
                $this->heading = $args[ 'email_heading' ];
            }

            if ( isset( $args[ 'email_type' ] ) ) {
                $this->settings_type = $args[ 'email_type' ];
            }
        }

        /**
         * get_content_html function.
         *
         * @access public
         * @return string
         */
        function get_content_html() {
            
            // Content, get saved text in backend and replace args
            $content = WGM_Sepa_Direct_Debit::generatre_mandate_preview( $this->args, $this->args[ 'mandate_id' ], $this->args[ 'date' ] );

            $template_file = WGM_Template::locate_template( 'emails/sepa-mandate.php' );

            ob_start();

             // extract needed vars
            extract( array(
                'content'       => $content,
                'email_heading' => $this->heading,
                'email'         => $this,
            ) );

            include( $template_file );

            return apply_filters( 'german_market_sepa_email_get_content_html', ob_get_clean(), $content, $email_heading, $this->args, $this->args[ 'mandate_id' ], $this->args[ 'date' ] );

        }

        /**
         * get_content_plain function.
         *
         * @access public
         * @return string
         */
        function get_content_plain() {

            // Content, get saved text in backend and replace args
            $content = WGM_Sepa_Direct_Debit::generatre_mandate_preview( $this->args, $this->args[ 'mandate_id' ], $this->args[ 'date' ] );

            $template_file = WGM_Template::locate_template( 'emails/plain/sepa-mandate.php' );

            ob_start();

             // extract needed vars
            extract( array(
                'content'       => $content,
                'email_heading' => $this->heading,
                'email'         => $this,
            ) );

            include( $template_file );

            return apply_filters( 'german_market_sepa_email_get_content_plain', ob_get_clean(), $content, $email_heading, $this->args, $this->args[ 'mandate_id' ], $this->args[ 'date' ] );
        }

        /**
        * template for sepa mandate in pdf
        *
        * @hook wp_wc_invoice_pdf_template_invoice_content
        * @param String $path
        * @return String
        */
        public static function pdf_content() {

            $theme_template_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-invoice-pdf' . DIRECTORY_SEPARATOR . 'sepa-mandate.php';
            if ( file_exists( $theme_template_file ) ) {
                $template_path = $theme_template_file;
            } else {
                $template_path = untrailingslashit( plugin_dir_path( Woocommerce_Invoice_Pdf::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'vendors' . DIRECTORY_SEPARATOR . 'self' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'sepa-mandate.php';
            }

            return $template_path;

        }

    }

endif;

return new German_Market_Email_Sepa();
