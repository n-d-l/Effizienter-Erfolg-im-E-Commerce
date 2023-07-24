<?php
/**
 * Template for invoice content
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/revocation-policy.php
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

$pages = apply_filters( 'wp_wc_invoice_pdf_additional_pdf_ecovation_pages_array', array(
	'wp_wc_invoice_pdf_additional_pdf_recovation_policy_page' 			=> get_page( get_option( 'woocommerce_widerruf_page_id') ),
	'wp_wc_invoice_pdf_additional_pdf_recovation_policy_digital_page' 	=> get_page( get_option( 'woocommerce_widerruf_fuer_digitale_medien_page_id' ) ),
), $args );

foreach ( $pages as $option => $page ) {

	// Don't print page if option is not set
	if ( get_option( $option ) != 'yes' ) {
		continue;
	}

	if ( is_int( $page ) ) {
		$page = get_post( $page );
	}

	if ( get_option( 'wp_wc_invoice_pdf_additional_pdf_show_headlines', 'on' ) == 'on' ) {
		?><h1><?php echo $page->post_title; ?></h1><?php
	}

	do_action( 'wp_wc_invoice_pdf_before_the_content' );
	
	if ( has_filter( 'wp_wc_invoice_pdf_additional_pdf_content_filter' ) ) {
		echo apply_filters( 'wp_wc_invoice_pdf_additional_pdf_content_filter', $page->post_content, $page );
	} else if ( apply_filters( 'german_market_email_footer_the_content_filter', true, $page) ) {
		echo apply_filters( 'the_content', WGM_Template::remove_vc_shortcodes( $page->post_content ) );
	} else {
		echo WGM_Template::remove_vc_shortcodes( $page->post_content );
	}

	do_action( 'wp_wc_invoice_pdf_after_the_content' );
}
