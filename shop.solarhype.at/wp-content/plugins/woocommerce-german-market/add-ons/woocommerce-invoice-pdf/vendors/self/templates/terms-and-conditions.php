<?php
/**
 * Template for invoice content
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/terms_and_conditions.php
 *
 * @version     3.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// default styles from plugin
$color						= get_option( 'wp_wc_invoice_pdf_additional_pdf_text_color', '#000000' );
$font						= get_option( 'wp_wc_invoice_pdf_additional_pdf_font', 'Helvetica' );
$font_size					= get_option( 'wp_wc_invoice_pdf_additional_pdf_font_size', 10 );
?>
	<style>
		p,  time, table, tr, th, td, span, h1, h2, h3, h4, h5, h6,  {
			font-family: <?php echo $font; ?>;
			line-height: normal;
			vertical-align: middle;	
			color: <?php echo $color; ?>;
		}

		h1{ font-size: 12pt; }
		h2, h3, h4, h5, h6 { font-size: 11pt; }

		p{
			font-family: <?php echo $font; ?>;
			font-size: <?php echo $font_size - 2; ?>pt;
			margin: 0.75em 0;
			text-align: justify;
		}

		ul li{
			font-family: <?php echo $font; ?>;
			font-size: <?php echo $font_size - 2; ?>pt;
		}
		
	</style>
<?php

if ( ! isset( $args ) ) {
	$args = array();
}

$pages = apply_filters( 'wp_wc_invoice_pdf_additional_pdf_tac_pages_array', array(
	'wp_wc_invoice_pdf_additional_pdf_legal_information_page' 		=> get_page( get_option( 'woocommerce_impressum_page_id') ),
	'wp_wc_invoice_pdf_additional_pdf_terms_page' 					=> get_page( get_option( 'woocommerce_terms_page_id' ) ),
	'wp_wc_invoice_pdf_additional_pdf_privacy_page'					=> get_page( get_option( 'woocommerce_datenschutz_page_id' ) ),
	'wp_wc_invoice_pdf_additional_pdf_shipping_and_delivery_page'	=> get_page( get_option( 'woocommerce_versandkosten__lieferung_page_id' ) ),
	'wp_wc_invoice_pdf_additional_pdf_payment_methods_page'			=> get_page( get_option( 'woocommerce_zahlungsarten_page_id' ) )
), $args );

foreach ( $pages as $option => $page ) {

	// Headline "General Customer Information" if shipping_and_delivery_page or payment_methods_page us shown
	if ( $option == 'wp_wc_invoice_pdf_additional_pdf_shipping_and_delivery_page' ) {

		if ( get_option( 'wp_wc_invoice_pdf_additional_pdf_show_headlines', 'on' ) == 'on' ) {
			if ( get_option( 'wp_wc_invoice_pdf_additional_pdf_shipping_and_delivery_page' ) == 'yes' || get_option( 'wp_wc_invoice_pdf_additional_pdf_payment_methods_page' ) == 'yes' ) {
				?><h1><?php echo __( 'General Customer Information', 'woocommerce-german-market' ); ?></h1><?php
			}
		}
	
	}

	// Don't print page if option is not set
	if ( get_option( $option ) != 'yes' ) {
		continue;
	}

	if ( is_int( $page ) ) {
		$page = get_post( $page );
	}

	// Choose headline size and print content
	$h_size = ( $option == 'wp_wc_invoice_pdf_additional_pdf_shipping_and_delivery_page' || $option == 'wp_wc_invoice_pdf_additional_pdf_payment_methods_page' ) ? '2' : '1';

	if ( get_option( 'wp_wc_invoice_pdf_additional_pdf_show_headlines', 'on' ) == 'on' ) {
		?><h<?php echo $h_size;?>><?php echo $page->post_title; ?></h<?php echo $h_size;?>><?php
	}

	do_action( 'wp_wc_invoice_pdf_before_the_content' );

	if ( has_filter( 'wp_wc_invoice_pdf_additional_pdf_content_filter' ) ) {
		echo apply_filters( 'wp_wc_invoice_pdf_additional_pdf_content_filter', $page->post_content, $page );
	} else if ( apply_filters( 'german_market_email_footer_the_content_filter', true, $page ) ) {
		echo apply_filters( 'the_content', WGM_Template::remove_vc_shortcodes( $page->post_content ) );
	} else {
		echo WGM_Template::remove_vc_shortcodes( $page->post_content );
	}
	
	do_action( 'wp_wc_invoice_pdf_after_the_content' );
}
