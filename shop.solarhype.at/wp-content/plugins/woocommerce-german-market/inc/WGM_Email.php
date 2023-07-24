<?php
/**
 * Email Functions
 */
Class WGM_Email {

    /**
    * init order confirmation mail
    *
    * @access public
    * @static
    * @wp-hook woocommerce_checkout_order_processed
    * @return void
    */
    public static function init_order_confirmation_mail() {
      $send_mail = get_option( 'wgm_send_order_confirmation_mail', 'on' );

      if ( 'on' === $send_mail ) {
        if ( 'off' === get_option( 'gm_order_confirmation_mail_send_on_thank_you_page', 'off' ) ) {
          add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'send_order_confirmation_mail' ) );
        } else {
          add_action( 'woocommerce_thankyou', array( __CLASS__, 'send_order_confirmation_mail_on_thank_you_page' ) );
        }
      }
    }

    /**
    * send order confirmation mail on thank you page
    *
    * @access public
    * @static
    * @wp-hook woocommerce_thank_you
    * @param Integer $order_id
    * @return void
    */
    public static function send_order_confirmation_mail_on_thank_you_page( $order_id ) {

        $order = wc_get_order( $order_id );
        if ( empty( $order->get_meta( '_gm_email_confirm_order_send' ) ) ) {
          self::send_order_confirmation_mail( $order_id );
          $order->update_meta_data( '_gm_email_confirm_order_send', 'yes' );
          $order->save_meta_data();
        }
    }

    /**
    * send order confirmation mail
    *
    * @access public
    * @static
    * @wp-hook woocommerce_thank_you
    * @param Integer $order_id
    * @return void
    */
    public static function send_order_confirmation_mail( $order_id ) {

        // Trigger confirmation mail
        if ( apply_filters( 'wgm_email_order_confirmation_before_trigger_before_return', false, $order_id ) ) {
            return;
        }

        $mail = include( 'WGM_Email_Confirm_Order.php' );

        do_action( 'wgm_email_order_confirmation_before_trigger', $order_id );
        $mail->trigger( $order_id );
    }

    /**
     * Returns email_de_footer().
     *
     * Takes an optional $output param to be usable as a filter.
     *
     * @param string $output
     *
     * @return string
     */
    public static function get_email_de_footer( $output = '' ) {

    	if ( get_option( 'wgm_email_footer_general', 'on' ) == 'on' ) {

            do_action( 'wgm_email_before_get_email_de_footer' );

	        ob_start();
	        self::email_de_footer();
	        $gm_footer = ob_get_clean();

	        // Visual Composer Compability
	        $gm_footer = WGM_Template::remove_vc_shortcodes( $gm_footer );

	        $gm_footer = apply_filters( 'wgm_email_footer_content', $gm_footer );

	        $output = $gm_footer . $output;

            do_action( 'wgm_email_after_get_email_de_footer' );

	    }

        return $output;

    }


/**
	* Add legal Text to emails
	*
	* @author jj, et
	* @access public
	* @static
	* @uses get_option, get_post, do_shortcode
	* @return void
	*
	*/
	public static function email_de_footer() {

        $_order = wc_get_order( WGM_Session::get( 'order_number', 'WGM' ) );

        if ( is_admin() ) {
            if ( ! is_a( $_order, 'WC_Order' ) ) {

                global $post_id;
                if ( $post_id ) {
                    $_order = wc_get_order( $post_id );
                }
            }

            if ( ! is_a( $_order, 'WC_Order' ) ) {
                if ( isset( $_REQUEST[ 'order_id' ] ) ) {
                    $_order = wc_get_order( $_REQUEST[ 'order_id' ] );
                }
            }

            if ( ! is_a( $_order, 'WC_Order' ) ) {
                $order_id = intval( get_transient( 'wgm_order_number' ) );


                if ( $order_id && $order_id > 0 ) {
                    $_order = wc_get_order( $order_id );
                }
            }
        }

		WGM_Email::start_email_footer();

		// Mandatory legal information, aka “Impressum”
		if ( apply_filters( 'wgm_email_display_imprint', TRUE, $_order ) == TRUE ) {
			if( 'yes' == get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_use_backend_footer_text_for_imprint_enabled' ) ) ) {
				$imprint_text = get_option( 'woocommerce_email_footer_text' );
                $imprint_page = null;
			} else {
				$imprint_page_id = get_option( WGM_Helper::get_wgm_option( 'impressum' ) );
				$imprint_page = get_post( $imprint_page_id );
                if ( function_exists( 'icl_object_id' ) ) {
                   $imprint_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'impressum' ) ) ) );
                }

				$imprint_text = $imprint_page->post_content;
			}

			WGM_Email::the_mail_footer_section(
				__( 'Legal Information', 'woocommerce-german-market' ),
				$imprint_text,
                $imprint_page
			);

		}

        do_action( 'wgm_email_email_de_footer_after_imprint', $_order );

        // Terms and Conditions
        if ( apply_filters( 'wgm_email_display_terms', TRUE, $_order ) == TRUE ) {
            $terms_page_id = get_option( WGM_Helper::get_wgm_option( 'agb' ) );
            $terms_page	= get_post( $terms_page_id );

            if ( ( intval( $terms_page_id ) > 0 ) && is_a( $terms_page, 'WP_Post' ) && isset( $terms_page->post_content ) ) {

                // WPML Support
                if ( function_exists( 'icl_object_id' ) ) {
                    $terms_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'agb' ) ) ) );
                }

                WGM_Email::the_mail_footer_section(
                    __( 'Terms and Conditions', 'woocommerce-german-market' ),
                    $terms_page->post_content,
                    $terms_page
                );
            }
        }

        do_action( 'wgm_email_email_de_footer_after_terms', $_order );

        if( is_a($_order, 'WC_Order')){

            if( ! WGM_Helper::only_digital( $_order ) ) {
                // Revocation
                if ( apply_filters( 'wgm_email_display_cancellation_policy', TRUE, $_order ) == TRUE ) {

                    $withdrawal_page_id = get_option( WGM_Helper::get_wgm_option( 'widerruf' ) );
                    $withdrawal_page	= get_post( $withdrawal_page_id );

                    if ( ( intval( $withdrawal_page_id ) > 0 ) && is_a( $withdrawal_page, 'WP_Post' ) && isset( $withdrawal_page->post_content ) ) {
                        if ( function_exists( 'icl_object_id' ) ) {
                            $withdrawal_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'widerruf' ) ) ) );
                        }

                        WGM_Email::the_mail_footer_section(
                            __( 'Revocation', 'woocommerce-german-market' ),
                            $withdrawal_page->post_content,
                            $withdrawal_page
                        );
                    }
                }
            };

            if( WGM_Helper::order_has_digital_product( $_order ) ) {
                // Revocation for Digital Content (as defined by German law)
                if ( apply_filters( 'wgm_email_display_cancellation_policy_for_digital_goods', TRUE, $_order ) == TRUE ) {
                    $withdrawal_page_id = get_option( WGM_Helper::get_wgm_option( 'widerruf_fuer_digitale_medien' ) );
                    $withdrawal_page	= get_post( $withdrawal_page_id );

                    if ( ( intval( $withdrawal_page_id ) > 0 ) && is_a( $withdrawal_page, 'WP_Post' ) && isset( $withdrawal_page->post_content ) ) {
                        if ( function_exists( 'icl_object_id' ) ) {
                            $withdrawal_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'widerruf_fuer_digitale_medien' ) ) ) );
                        }

                        WGM_Email::the_mail_footer_section(
                            __( 'Revocation Policy for Digital Content', 'woocommerce-german-market' ),
                            $withdrawal_page->post_content,
                            $withdrawal_page
                        );
                    }
                }

                // Waiver of Rights of Revocation for Digital Content
                if ( ( get_option( 'german_market_checkbox_2_digital_content_activation', 'on' ) == 'on' ) ) {
                    if ( apply_filters( 'wgm_email_display_cancellation_policy_for_digital_goods_acknowlagement', TRUE, $_order ) == TRUE ) {
                        WGM_Email::the_mail_footer_section(
                            __( 'Waiver of Rights of Revocation for Digital Content', 'woocommerce-german-market' ),
                            __( 'You explicitly agreed that we continue with the execution of our contract before expiration of the revocation period. You thereby also declared you are aware of the fact that you lose your right of revocation at the beginning of our execution of the contract.', 'woocommerce-german-market' )
                        );
                    }
                }

            }
        }

        do_action( 'wgm_email_email_de_footer_after_cancellation_policy', $_order );

        if ( apply_filters( 'wgm_email_display_gerneral_customer_information_headline', true, $_order ) ) {
    		// General Customer Information
            echo '<div style="' . apply_filters( 'wgm_email_footer_style', 'float:left; width: 100%;' ) .' ">
                    <h3 style="' . apply_filters( 'wgm_email_footer_style_h3', '' ) . '">'. apply_filters( 'wgm_email_customer_infomation_text',
                        __( 'General Customer Information', 'woocommerce-german-market' ) ) .'</h3>
                </div>';
        }

        // Shipping Costs and Delivery
        if ( apply_filters( 'wgm_email_display_delivery', TRUE, $_order ) == TRUE ) {
            $terms_page_id = get_option( WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ) );

            if ( intval( $terms_page_id ) > 0 ) {

                $terms_page	= get_post( $terms_page_id );
                if ( function_exists( 'icl_object_id' ) ) {
                    $terms_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ) ) ) );
                }

                WGM_Email::the_mail_footer_sub_section(
                    sprintf( __( 'Shipping %s Delivery', 'woocommerce-german-market' ), '&amp;'),
                    $terms_page->post_content,
                    $terms_page
                );

            }

        }

        do_action( 'wgm_email_email_de_footer_after_delivery', $_order );

        // Payment Method
        if ( apply_filters( 'wgm_email_display_payment_methods', TRUE, $_order ) == TRUE ) {
            $payment_methods_page_id = get_option( WGM_Helper::get_wgm_option( 'zahlungsarten' ) );

            if ( intval( $payment_methods_page_id ) > 0 ) {

                $payment_methods_page   = get_post( $payment_methods_page_id );

                if ( function_exists( 'icl_object_id' ) ) {
                    $payment_methods_page = get_post( icl_object_id( get_option( WGM_Helper::get_wgm_option( 'zahlungsarten' ) ) ) );
                }

                WGM_Email::the_mail_footer_sub_section(
                    /* translators: heading in order confirmation, must be singular! */
                    __( 'Payment Method', 'woocommerce-german-market' ),
                    $payment_methods_page->post_content,
                    $payment_methods_page
                );

            }

        }

        do_action( 'wgm_email_email_de_footer_after_payment_methods', $_order );

		WGM_Email::end_email_footer();
	}

	/**
	 * Print Mail Footer Section HTML
	 * @param  string $title
	 * @param  string $content
	 * @return void
	 */
	public static function the_mail_footer_section( $title, $content, $post = null ) {

        $compatibility_content = apply_filters( 'german_market_email_compatibility_content', '', $content, $post );

        if ( empty( $compatibility_content ) ) {

            if ( function_exists( 'has_blocks' ) ) {

                if ( has_blocks( $content ) ) {
                    $content = do_blocks( $content );
                }

                if ( apply_filters( 'german_market_email_footer_the_content_filter', true, $post ) ) {
                    $content = apply_filters( 'the_content', $content );
                } else {
                     $content = WGM_Template::remove_vc_shortcodes( $content );
                }

            }
        } else {
            $content = $compatibility_content;
        }

        ?>
		<div style="<?php echo apply_filters( 'wgm_email_footer_style', 'float:left; width: 100%;' ); ?>">

            <?php if ( apply_filters( 'wgm_email_show_page_headlines', true ) ) { ?>
                <h3 style="<?php echo apply_filters( 'wgm_email_footer_style_h3', '' ); ?>"><?php echo $title; ?></h3>
			<?php } ?>

            <p style="<?php echo apply_filters( 'wgm_email_footer_style_p', '' ); ?>"><?php echo $content; ?></p>
		</div>
		<?php
	}

    public static function the_mail_footer_sub_section( $title, $content, $post ) {

        $compatibility_content = apply_filters( 'german_market_email_compatibility_content', '', $content, $post );

        if ( empty( $compatibility_content ) ) {

            if ( function_exists( 'has_blocks' ) ) {
                if ( has_blocks( $content ) ) {
                    $content = do_blocks( $content );
                }

                if ( apply_filters( 'german_market_email_footer_the_content_filter', true , $post ) ) {
                    $content = apply_filters( 'the_content', $content );
                } else {
                    $content = WGM_Template::remove_vc_shortcodes( $content );
                }

            }

        } else {
            $content = $compatibility_content;
        }

        ?>
        <div style="<?php echo apply_filters( 'wgm_email_footer_style', 'float:left; width: 100%;' ); ?>">

            <?php if ( apply_filters( 'wgm_email_show_page_headlines', true ) ) { ?>
                <h4 style="<?php echo apply_filters( 'wgm_email_footer_style_h4', '' ); ?>"><?php echo $title; ?></h4>
            <?php } ?>

            <p style="<?php echo apply_filters( 'wgm_email_footer_style_p', '' ); ?>"><?php echo $content; ?></p>
        </div>
    <?php
    }

	private static function start_email_footer(){
		echo apply_filters( 'wgm_start_email_footer_html', '<div class="wgm-wrap-email-appendixes" style="text-align: left;    color: #737373; font-size: 14px;  line-height: 150%;">' );
	}

	private static function end_email_footer(){
		echo apply_filters( 'wgm_end_email_footer_html', '</div>' );
	}



    public static function cache_order( $order ){
        if ( $order ) {
            WGM_Session::add( 'order_number', $order->get_id(), 'WGM' );
            set_transient( 'wgm_order_number', $order->get_id() );
        }
    }

    /**
     * Disable email footer text for admins if option is set
     * Removes the Email Footer Text of the E-Mails "New order", "Cancelled order" and "Failed order" that are sent to the shop admin
     *
     * @access public
     * @static
     * @wp-hook woocommerce_email
     * @param $object
     * @return void
     */
    public static function disable_footer_text_for_admin_emails( $email ) {

        $condition = is_a( $email, 'WC_Email_New_Order' ) || is_a( $email, 'WC_Email_Cancelled_Order' ) || is_a( $email, 'WC_Email_Failed_Order' );
        $condition = apply_filters( 'wgm_disable_footer_text_for_admin_emails', $condition, $email );

        if ( $condition ) {

            if ( get_option( 'wgm_email_footer_in_admin_emails', 'on' ) != 'on' ) {

                // Remove the filter
                remove_filter( 'woocommerce_email_footer_text', array( 'WGM_Email', 'get_email_de_footer' ), 5 );
            }

        } else {

            // If it's a non-admin email, check whether an admin email has been send before and add filter again
            if ( ! has_filter( 'woocommerce_email_footer_text', array( 'WGM_Email', 'get_email_de_footer' ), 5 ) ) {
                add_filter( 'woocommerce_email_footer_text', array( 'WGM_Email', 'get_email_de_footer' ), 5 );
            }
        }

    }

    /**
     * Add BCC / CC to Customer Emails
     *
     * @access public
     * @static
     * @wp-hook woocommerce_email_headers
     * @param String $headers
     * @param String $id
     * @param Object | bool $email
     * @return String
     */
    public static function woocommerce_email_headers_bcc_cc( $headers, $id, $wc_object = null, $email = null ) {

        $gm_option = 'wgm_email_cc_bcc_' . $id;

        if ( $id == 'customer_partially_refunded_order' ) {
            $gm_option = 'wgm_email_cc_bcc_customer_refunded_order';
        }

        if ( get_option( $gm_option, 'off' ) == 'on' ) {

            $bcc_or_cc = get_option( 'wgm_email_cc_bcc_type', 'bcc' );
            $bcc_or_cc = strtoupper( $bcc_or_cc );

            $recipients = get_option( 'wgm_email_cc_bcc_receivers', '' );
            $recipients = apply_filters( 'wgm_email_cc_bcc_receivers', $recipients, $headers, $id, $email, $wc_object );

            if ( $recipients != '' ) {
                $headers .= $bcc_or_cc . ': ' . $recipients . "\r\n";
            }

        }

        return $headers;

    }

    /**
    * adds static attachments to wc emails
    *
    * @since 3.2
    * @access public
    * @static
    * @hook woocommerce_email_attachments
    * @param Array $attachments
    * @param String $status
    * @param WC_Order $order
    * @return Array
    */
    public static function add_attachments( $attachments, $status , $order ) {

        $nr_of_attachments = get_option( 'de_shop_emails_file_attachments_nr', 1 );

        if ( intval( $nr_of_attachments ) > 0 ) {

            // send for every customer email
            $send_modus = true;
            $is_customer_mail = str_replace( 'customer_', '', $status ) != $status;

            // if it's not an email to the customer, check if admin option is activated
            if ( get_option( 'german_market_email_attachements_all_wc_emails', 'on' ) === 'on' ) {
                if ( ! $is_customer_mail ) {
                    $send_modus = get_option( 'wgm_email_footer_in_admin_emails', 'on' ) == 'on';
                }
            } else {

                $selected_mails = get_option( 'german_market_email_attachements_emails', array() );
                $send_modus = in_array( $status, $selected_mails );               
            }

            $send_modus = apply_filters( 'de_shop_emails_file_attachments_send', $send_modus, $attachments, $status , $order );

            if ( $send_modus ) {

                $path_array = wp_upload_dir();
                $path       = untrailingslashit( ( $path_array[ 'basedir' ] ) ); // wp upload path
                $url        = untrailingslashit( ( $path_array[ 'baseurl' ] ) ); // wp upload url
                $url        = str_replace( 'https://', 'http://', $url );

                for ( $i = 1; $i <= $nr_of_attachments; $i++ ) {

                    $attachment_url = get_option( 'de_shop_emails_file_attachment_' . $i );
                    $attachment_url = str_replace( 'https://', 'http://', $attachment_url );

                    if ( $attachment_url != '' ) {

                        $attachment_path = str_replace( $url, $path, $attachment_url );

                        if ( is_file( $attachment_path ) ) {
                            $attachments[] = $attachment_path;
                        }

                    }

                }

            }

        }

        return $attachments;
    }

    /**
    * Show notice in E-Mail: You have ticked "... digital notice"
    *
    * @since GM v3.2
    * @wp-hook woocommerce_email_order_meta
    * @param WC_Order $order
    * @param Boolean $sent_to_admin
    * @param Boolean $plain_text
    **/
    public static function repeat_digital_content_notice( $order, $sent_to_admin, $plain_text = false ) {

        if ( ! ( get_option( 'woocommerce_de_repeat_digital_content', 'on' ) == 'on' ) ) {

           if ( apply_filters( 'woocommerce_de_repeat_digital_content_do_return_if_off', true ) ) {
                return;
            }

        }

        if ( apply_filters( 'woocommerce_de_repeat_digital_content_do_return_if_on', false ) ) {
            return;
        }

        $has_virtual = FALSE;
        if ( is_object( $order ) && method_exists( $order, 'get_items' ) ) {
            foreach ( $order->get_items() as $item ) {

                if ( empty( $item[ 'variation_id' ] ) ) {
                    $product = wc_get_product( $item[ 'product_id' ] );
                } else {
                    $product = wc_get_product( $item[ 'variation_id' ] );
                }

                if ( ! $product ) {
                    break;
                }

                if ( WGM_Helper::is_digital( $product->get_id() ) ) {
                    $has_virtual = TRUE;
                    break;
                }
            }
        }

       if ( $has_virtual ) {

            if ( get_option( 'german_market_checkbox_2_digital_content_activation' ) != 'off' ) {

                $default_text = __( 'For digital content: You explicitly agree that we continue with the execution of our contract before expiration of the revocation period. You hereby also declare you are aware of the fact that you lose your right of revocation with this agreement.', 'woocommerce-german-market' );

                $text = sprintf( __( 'Cancellation of the right of revocation is confirmed', 'woocommerce-german-market' ) . ' - ' . __( 'You have ticked: "%s"', 'woocommerce-german-market' ), get_option( 'woocommerce_de_checkbox_text_digital_content', $default_text ) );

                $new_line = $plain_text ? "\n" : '';
                $p_start  = $plain_text ? '' : '<p>';
                $p_end    = $plain_text ? '' : '</p>';

                if ( get_option( 'woocommerce_de_repeat_digital_content_notice_position', 'after' ) == 'after' ) {

                    echo apply_filters( 'wgm_display_digital_revocation_emails_pdf', $p_start . $new_line . $text . $p_end, $text, 'after', $plain_text );

                } else {

                    echo apply_filters( 'wgm_display_digital_revocation_emails_pdf', $p_start . $text . $new_line . $p_end, $text, 'before', $plain_text );
                }

            }

        }

    }

}
