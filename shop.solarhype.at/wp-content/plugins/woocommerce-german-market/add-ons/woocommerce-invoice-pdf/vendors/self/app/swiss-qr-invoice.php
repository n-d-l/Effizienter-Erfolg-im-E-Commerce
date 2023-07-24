<?php

use Sprain\SwissQrBill as QrBill;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Invoice_Pdf_Swiss_Qr_Invoice' ) ) {

	/**
	* swiss qr invoice creation
	*
	* @WP_WC_Invoice_Pdf_Girocode
	* @version 1.0.0
	* @category	Class
	*/
	class WP_WC_Invoice_Pdf_Swiss_Qr_Invoice {

		public $order;
		public $is_test = false;
		
		/**
		* construct
		* 
		* @access public
		* @param WC_Order | null $order
		* @return void
		*/
		public function __construct( $order, $args ) {
			$this->order = $order;

			if ( ! is_object( $args[ 'order' ] ) ) {
				$this->is_test = true;
			}
		}

		/**
		* get QrBill, using data from $this->order
		* 
		* @return QrBill\QrBill
		*/
		public function get_qr_bill_object_by_order() {

			$required_options = array(
				'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_name' 		=> wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_address' 	=> get_option( 'woocommerce_store_address', '' ),
				'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_postcode' 	=> get_option( 'woocommerce_store_postcode', '' ),
				'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_city' 		=> get_option( 'woocommerce_store_city', '' ),
				'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_country' 	=> WC()->countries->get_base_country(),
			);

			$variant = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_variant', 'qr' );
			
			if ( 'qr' === $variant ) {
				$required_options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_qr_iban' ] = ''; 
				$required_options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_customer_id' ] = ''; 
			} else if ( 'scor' === $variant ) {
				$required_options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_v3_iban' ] = ''; 
				$required_options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_creditor_reference' ] = ''; 
			} else if ( 'non' === $variant ) {
				$required_options[ 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_v3_iban' ] = ''; 
			}

			foreach ( $required_options as $key => $default ) {
				if ( empty( trim( get_option( $key, $default ) ) ) ) {
					throw new Exception( 'Required field missing: ' . $key );
				}
			}

			// create data
			if ( ! $this->is_test ) {
				$name 					= $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name();
				$address_1 				= $this->order->get_billing_address_1();
				$postcode_and_city 		= $this->order->get_billing_postcode() . ' ' . $this->order->get_billing_city();
				$country 				= $this->order->get_billing_country();
			} else {
				$name 					= __( 'John', 'woocommerce-german-market' ) . ' ' . __( 'Doe', 'woocommerce-german-market' );
				$address_1 				= __( '42 Example Avenue', 'woocommerce-german-market' );
				$postcode_and_city 		= __( 'Springfield, IL 61109', 'woocommerce-german-market' );
				$country 				= 'CH';
			}

			if ( is_object( $this->order ) && method_exists( $this->order, 'get_billing_first_name' ) ) {
				$order_total			= $this->order->get_total();
				$currency				= $this->order->get_currency();
				$order_number 			= $this->order->get_order_number();
			} else {
				$order_total			= 0.01;
				$currency 				= 'CHF';
				$order_number 			= '10001';
			}

			$qrBill = QrBill\QrBill::create();

			// Add creditor information
			// Who will receive the payment and to which bank account?
			$qrBill->setCreditor(
			    QrBill\DataGroup\Element\CombinedAddress::create(
			        get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_name', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ),
			        get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_address', get_option( 'woocommerce_store_address', '' ) ),
			        get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_postcode', get_option( 'woocommerce_store_postcode', '' ) ) . ' ' . get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_city', get_option( 'woocommerce_store_city', '' ) ),
			        get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_remit_recipient_country', WC()->countries->get_base_country() ),
			    ));

			if ( 'qr' === $variant ) {
				$qr_iban_or_iban = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_qr_iban', '' );
			} else {
				$qr_iban_or_iban = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_v3_iban', '' );
			}

			$qrBill->setCreditorInformation(
			    QrBill\DataGroup\Element\CreditorInformation::create(
			        $qr_iban_or_iban
			    ));

			// Add debtor information
			// Who has to pay the invoice? This part is optional.
			//
			// Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
			// They are interchangeable for creditor as well as debtor.
			$qrBill->setUltimateDebtor(
			    QrBill\DataGroup\Element\CombinedAddress::create(
			        $name,
			        $address_1,
			        $postcode_and_city,
			        $country,
			    ));

			// Add payment amount information
			// What amount is to be paid?
			$qrBill->setPaymentAmountInformation(
			    QrBill\DataGroup\Element\PaymentAmountInformation::create(
			        $currency,
			        $order_total
			    ));

			if ( 'qr' === $variant ) {

				$reference_customer_id = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_customer_id', '' );
				if ( empty( $reference_customer_id ) ) {
					$reference_customer_id = null;
				}

				$reference_option = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_v1_internal', '' );
				
				$internal_reference_number = $order_number;

				if ( 'invoice_number' === $reference_option && class_exists( 'Woocommerce_Running_Invoice_Number' ) ) {
					
					if ( is_object( $this->order ) && method_exists( $this->order, 'get_billing_first_name' ) ) {
						$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $this->order );	
						$internal_reference_number = $running_invoice_number->get_invoice_number();
					}
				
				} else if ( 'order_number' !== $reference_option ) {
					$internal_reference_number = apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_internal_reference_custom', $internal_reference_number, $this->order );
				}

				$internal_reference_number = preg_replace( '/[^0-9.]+/', '', $internal_reference_number );

				// Add payment reference
				// This is what you will need to identify incoming payments.
				$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
				    $reference_customer_id,  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
				    $internal_reference_number // A number to match the payment with your internal data, e.g. an invoice number
				);

				$qrBill->setPaymentReference(
				    QrBill\DataGroup\Element\PaymentReference::create(
				        QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
				        $referenceNumber
				    ));

			} else if ( 'scor' === $variant ) {

				$reference_number = get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_v2_creditor_reference', '' );
				$reference_number = $this->replace_placeholders( $reference_number, $this->order );

				// only alphanumeric input
				$reference_number = preg_replace( '/[^a-zA-Z0-9]+/', '', $reference_number );

				// max 21 chars
				if ( strlen( $reference_number ) > 21 ) {
					$reference_number = substr( $reference_number, 0, 21 );
				}

				$referenceNumber = QrBill\Reference\RfCreditorReferenceGenerator::generate(
				    $reference_number,
				);
				
				$qrBill->setPaymentReference(
				    QrBill\DataGroup\Element\PaymentReference::create(
				        QrBill\DataGroup\Element\PaymentReference::TYPE_SCOR,
				        $referenceNumber
				    ));

			} else if ( 'non' === $variant ) {

				$qrBill->setPaymentReference(
				    QrBill\DataGroup\Element\PaymentReference::create(
				        QrBill\DataGroup\Element\PaymentReference::TYPE_NON,
				        $referenceNumber
				    ));
			}

			$addtional_info = $this->replace_placeholders( get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_additional_information_text', __( 'Order {{order-number}}', 'woocommerce-german-market' ) ), $this->order );

			if ( ! empty( trim( $addtional_info ) ) ) {
				// Optionally, add some human-readable information about what the bill is for.
				$qrBill->setAdditionalInformation(
				    QrBill\DataGroup\Element\AdditionalInformation::create(
				        $addtional_info
				    )
				);
			}

			return $qrBill;
		}

		/**
		* get language used for swiss qr invoice
		* supported languages: en, de, it, fr
		* 
		* @return String
		*/
		public function get_language() {

			$language 		= 'en';

			$locale 		= get_locale();
			$locale_array 	= explode( '_', $locale );

			if ( is_array( $locale_array ) && isset( $locale_array[ 0 ] ) ) {
				$locale_language = $locale_array[ 0 ];

				if ( 'de' === $locale_language ) {
					$language = 'de';
				} else if ( 'it' === $locale_language ) {
					$language = 'it';
				} else if ( 'fr' === $locale_language ) {
					$language = 'fr';
				}
			}

			return apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_language', $language, $this->order );
		}

		/**
		* make markup for swiss qr code invoice
		* in invoice pdf
		* 
		* @return void
		*/
		public function make_markup() {

			try {

				$qrBill = $this->get_qr_bill_object_by_order();

				$output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput( $qrBill, $this->get_language() );

				$html = $output
				    ->setPrintable(false)
				    ->getPaymentPart();

			} catch ( Exception $e ) {
				$this->log_error( $e );
				return;
			}
			
			// this element is used to get the current y-position
			?><span class="before-fine-print" style="height: 0; line-height: 0; font-size: 0;"></span><?php

			echo $html;

			if ( 'on' === get_option( 'wp_wc_invoice_pdf_swiss_qr_invoice_hide_page_numbers', 'on' ) ) {
				$this->add_js_save_page_number_of_swiss_qr_invoice();
			}
			
			if ( ! ( ( 'no' !== get_option( 'wp_wc_invoice_pdf_show_fine_print', 'no' ) ) && ( get_option( 'wp_wc_invoice_pdf_fine_print_new_page', true ) ) ) ) { 
				?><div style="page-break-after: always;"></div><?php
			}

		}

		/**
		* save current page so that the page number of this page can be hidden
		* add a page break if there is not enough space 
		* 
		* @return void
		*/
		private function add_js_save_page_number_of_swiss_qr_invoice() {
			?>
			<script type="text/php">
				if ( ! isset( $GLOBALS[ 'hide_page_number_pages' ] ) ) {
					$hide_page_number_pages = array();
				}
				$GLOBALS[ 'hide_page_number_pages' ][] = $pdf->get_page_number();
			</script>
			<?php
		}

		/**
		* Replace Placholders
		* 
		* @access public
		* @param Strint $text
		* @param WC_Order $order
		* @return $text
		*/
		public function replace_placeholders( $text, $order ) {

			$can_use_order = is_object( $order ) && method_exists( $order, 'get_billing_first_name' );
			$placeholders = array(
				'{{first-name}}'	=> $can_use_order ? $order->get_billing_first_name() : __( 'John', 'woocommerce-german-market' ),
				'{{last-name}}'		=> $can_use_order ? $order->get_billing_last_name() : __( 'Doe', 'woocommerce-german-market' ),
				'{{order-number}}'	=> $can_use_order ? $order->get_order_number() : rand( 1000, 99999 ),
				'{{order-total}}'	=> $can_use_order ? strip_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) : wc_price( 0.01 ) 
			);

			$placeholders = apply_filters( 'wp_wc_invoice_pdf_swiss_qr_invoice_placeholders', $placeholders, $order );

			$text = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $text );

			return apply_filters( 'wp_wc_invoice_pdf_girocode_placeholders_text', $text, $order );
		}

		/**
		* logs an error in wc log
		* 
		* @access public
		* @return void
		*/
		private function log_error( $e ) {
			$logger 	= wc_get_logger();
			$context 	= array( 'source' => 'german-market-swiss-qr-invoice' );
			
			$message = '';

			if ( is_object( $this->order ) && method_exists( $this->order, 'get_id' ) ) {
				$message .= 'Order: ' . $this->order->get_id();
			}

			if ( is_object( $e ) && method_exists( $e, 'getMessage' ) ) {
				if ( ! empty( $message ) ) {
					$message .= ', ';
				}
				$message .= $e->getMessage();

			}

			$logger->info( $message, $context );
		}

	} // end class

} // end if
