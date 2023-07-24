<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_View_Order_Download' ) ) {

	/**
	 * frontend download on customer account, view-order
	 *
	 * @class WCREAPDF_View_Order_Download
	 * @version    1.0
	 * @category    Class
	 */
	class WP_WC_Invoice_Pdf_View_Order_Download {

		/**
		 * download button on view-order page
		 *
		 * @hook woocommerce_order_details_after_order_table
		 *
		 * @since 0.0.1
		 *
		 * @access public
		 * @static
		 *
		 * @return void
		 */
		public static function make_download_button( $order ) {

			if ( ! is_user_logged_in() ) {
				return;
			}

			// manual order confirmation
			if ( 'yes' == get_post_meta( $order->get_id(), '_gm_needs_conirmation', true ) ) {
				return;
			}

			// double-opt-in check
			if ( 'on' == get_option( 'wgm_double_opt_in_customer_registration' ) ) {

				$user              = wp_get_current_user();
				$activation_status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );

				if ( $activation_status == 'waiting' ) {
					return;
				}

			}

			// if you don't set html5 attribut download and open link in current tab you get in chrome: Resource interpreted as Document but transferred with MIME type application
			$a_href       = esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_wp_wc_invoice_pdf_view_order_invoice_download&order_id=' . $order->get_id() ), 'wp-wc-invoice-pdf-download-view-order' ) );
			$a_target     = ( get_option( 'wp_wc_invoice_pdf_view_order_link_behaviour', 'new' ) == 'new' ) ? 'target="_blank"' : '';
			$a_download   = ( get_option( 'wp_wc_invoice_pdf_view_order_download_behaviour', 'inline' ) == 'inline' ) ? '' : ' download';
			$a_attributes = trim( $a_target . $a_download );
			$button_text  = get_option( 'wp_wc_invoice_pdf_view_order_button_text', __( 'Download Invoice Pdf', 'woocommerce-german-market' ) );
			$status       = $order->get_status();

			if ( false !== $status && 'yes' == get_option( 'wp_wc_invoice_pdf_frontend_download_' . $status, 'no' ) ) {

				if ( apply_filters( 'wp_wc_invoice_pdf_view_order_button_custom_view', true, $order ) ) {

					if ( has_action( 'wp_wc_invoice_pdf_view_order_button' ) ) {
						do_action( 'wp_wc_invoice_pdf_view_order_button', $a_href, $a_target, $a_attributes, $button_text, $order );
					} else {
						?>
	                    <p class="download-invoice-pdf">
	                        <a href="<?php echo $a_href; ?>" class="button"<?php echo ( $a_attributes != '' ) ? ' ' . $a_attributes : ''; ?> style="<?php echo apply_filters( 'wp_wc_invoice_pdf_download_buttons_inline_style', 'margin: 0.15em 0;' ); ?>"><?php echo $button_text; ?></a>
	                    </p>
						<?php
					}
				}
			}

            if ( 'on' == get_option( 'wp_wc_invoice_pdf_frontend_download_refund_pdf', 'off' ) ) {

                $invoice_number = '';

	            foreach ( $order->get_refunds() as $refund ) {

		            $refund_id     = $refund->get_id();
		            $refund_number = '';
		            $a_href        = esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_wp_wc_invoice_pdf_view_order_refund_download&refund_id=' . $refund_id ), 'wp-wc-refund-pdf-download-view-order' ) );

                    $button_text = get_option( 'wp_wc_invoice_pdf_view_order_refund_button_text', __( 'Download Refund #{{refund-id}} Pdf', 'woocommerce-german-market' ) );

                    $placeholder_search = array( '{{refund-id}}' );
                    $placeholder_replace = array( $refund_id );

                    // button text contains {{invoice-number}}
                    if ( str_replace( '{{invoice-number}}', '', $button_text ) != $button_text ) {
                    	if ( empty( $invoice_number ) ) {
                    		// Check if Invoice number add-on is activated?
				            if ( class_exists( 'Woocommerce_Running_Invoice_Number' ) ) {
			                    $running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );
			                    $invoice_number = $running_invoice_number->get_invoice_number();
				            }
				            $placeholder_search[]  = '{{invoice-number}}';
				            $placeholder_replace[] = $invoice_number;
                    	}
                    }

                    // button text contains {{refund-number}}
                    if ( str_replace( '{{refund-number}}', '', $button_text ) != $button_text ) {
                    	if ( empty( $refund_number ) ) {
                    		if ( class_exists( 'Woocommerce_Running_Invoice_Number' ) ) {
                    			$running_refund_number = new WP_WC_Running_Invoice_Number_Functions( $refund );
                       	 		$refund_number 	= $running_refund_number->get_invoice_number();
                       	 	}
                       	 	$placeholder_search[]  = '{{refund-number}}';
				            $placeholder_replace[] = $refund_number;
                    	}
                    }

		            $button_text = str_replace( $placeholder_search, $placeholder_replace, $button_text );

		            if ( has_action( 'wp_wc_invoice_pdf_view_order_refund_button' ) ) {
			            do_action( 'wp_wc_invoice_pdf_view_order_refund_button', $a_href, $a_target, $a_attributes, $button_text, $order, $refund_id );
		            } else {
			            ?>
                        <p class="download-invoice-pdf">
                            <a href="<?php echo $a_href; ?>" class="button"<?php echo ( '' != $a_attributes ) ? ' ' . $a_attributes : ''; ?> style="<?php echo apply_filters( 'wp_wc_invoice_pdf_download_buttons_inline_style', 'margin: 0.15em 0;' ); ?>"><?php echo $button_text; ?></a>
                        </p>
			            <?php
		            }

	            }
            }
		}

		/**
		 * download pdf frontend
		 *
		 * @hook wp_ajax_woocommerce_wcreapdf_view_order_download
         *
		 * @since 0.0.1
         *
		 * @access public
         * @static
         *
		 * @return void
		 */
		public static function download_pdf() {

			if ( ! check_ajax_referer( 'wp-wc-invoice-pdf-download-view-order', 'security', false ) ) {
				wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
			}

			// init
			$order_id = $_REQUEST[ 'order_id' ];
			$order    = wc_get_order( $order_id );
			$status   = $order->get_status();
			// creata pdf only if user is allowed to
			if ( false !== $status && current_user_can( 'view_order', $order_id ) ) {
				do_action( 'wp_wc_invoice_pdf_before_frontend_download', $order );
				$args    = array(
					'order'         => $order,
					'output_format' => 'pdf',
					'output'        => get_option( 'wp_wc_invoice_pdf_view_order_download_behaviour' ),
					'filename'      => apply_filters( 'wp_wc_invoice_pdf_frontend_filename', get_option( 'wp_wc_invoice_pdf_file_name_frontend', get_bloginfo( 'name' ) . '-' . __( 'Invoice-{{order-number}}', 'woocommerce-german-market' ) ), $order ),
					'frontend'		=> 'yes',
				);
				$invoice = new WP_WC_Invoice_Pdf_Create_Pdf( $args );
			} else {
				$redirect = apply_filters( 'wp_wc_invoice_pdf_view_order_redirect_link', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				wp_safe_redirect(  $redirect );
			}
			exit();
		}

		/**
		* download refund pdf
		*
		* @access public
		* @static 
		* @return void
		*/	
		public static function download_refund_pdf() {

			if ( ! check_ajax_referer( 'wp-wc-refund-pdf-download-view-order', 'security', false ) ) {
				wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
			}

			// init
			$refund_id 	= $_REQUEST[ 'refund_id' ];
			$refund 	= wc_get_order( $refund_id );#
			$order_id 	= $refund->get_parent_id();
			$order 		= wc_get_order( $order_id );

			if ( current_user_can( 'view_order', $order_id ) ) {
				do_action( 'wp_wc_invoice_pdf_before_refund_backend_download', $refund_id );
				do_action( 'wp_wc_invoice_pdf_before_backend_download_switch', array( 'order' => $order, 'admin' => true ) );	

				add_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );

				// get filename
				$filename = get_option( 'wp_wc_invoice_pdf_refund_file_name_backend', 'Refund-{{refund-id}} for order {{order-number}}' );
				// replace {{refund-id}}, the other placeholders will be managed by the class WP_WC_Invoice_Pdf_Create_Pdf
				$filename = str_replace( '{{refund-id}}', $refund_id, $filename );
				$filename = apply_filters( 'wp_wc_invoice_pdf_refund_backend_filename', $filename, $refund );

				$args = array( 
					'order'				=> $order,
					'refund'			=> $refund,
					'output_format'		=> 'pdf',
					'output'			=> get_option( 'wp_wc_invoice_pdf_view_order_download_behaviour' ),
					'filename'			=> WP_WC_Invoice_Pdf_Email_Attachment::repair_filename( $filename ),
					'frontend'			=> 'yes',
				);
				
				$refund = new WP_WC_Invoice_Pdf_Create_Pdf( $args );

				remove_filter( 'wp_wc_invoice_pdf_template_invoice_content', array( 'WP_WC_Invoice_Pdf_Backend_Download', 'load_storno_template' ) );
			} else {
				$redirect = apply_filters( 'wp_wc_invoice_pdf_view_order_redirect_link', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				wp_safe_redirect( $redirect );
			}

			exit();
		}

	} // end class

} // end if
