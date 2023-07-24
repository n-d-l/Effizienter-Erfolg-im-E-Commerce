<?php
/**
 * Template for default css styles
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/invoice-default-styles.php
 *
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

//////////////////////////////////////////////////
// init
//////////////////////////////////////////////////

// general
$user_unit 					= get_option( 'wp_wc_invoice_pdf_user_unit', 'cm' );
$color						= get_option( 'wp_wc_invoice_pdf_body_color', '#000000' );
$font						= get_option( 'wp_wc_invoice_pdf_content_font', 'Helvetica' );
$font_size					= get_option( 'wp_wc_invoice_pdf_content_font_size', 10 );
$font_size_small			= intval( $font_size ) - 3;

// billing address field
$billing_addr_note_font_size= $font_size_small - 1;
$billing_address_margin_top = self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_top_margin' ) );
$billing_address_width		= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_width' ) );
$billing_address_height		= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_height' ) );
$billing_address_margin_b	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_bottom_margin' ) );
$billing_addr_helper_height	= ( $user_unit == 'cm' ) ? ( $billing_address_height - 2.23 ) : ( $billing_address_height - 0.878 );
$billing_addr_helper_width	= ( $user_unit == 'cm' ) ? ( $billing_address_width - 1 ) : ( $billing_address_width - 0.40 );
$billing_addr_border_color	= get_option( 'wp_wc_invoice_pdf_billing_address_border_color', '#dddddd' );
$billing_addr_border_px		= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_border_width', 1 ) );
$billing_addr_border_radius	= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_billing_address_border_radius', ( ( $user_unit == 'cm' ) ? 0.3 : 0.1 ) ) );
$additoinal_notation 		= get_option( 'wp_wc_invoice_pdf_billing_address_additional_notation', '' );

// subject and welcome text
$font_size_subject			= intval( $font_size ) + 5;
$refund_font_size_small		= $font_size;
$margin_after_subject		= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_invoice_start_margin_after_subject', 0.75 ) );

// table
$border_color				= get_option( 'wp_wc_invoice_pdf_table_border_color', '#dddddd' );
$border_width				= self::convert_to_css_numeric( get_option( 'wp_wc_invoice_pdf_table_border_width', 1 ) );
$thick_border				= get_option( 'wp_wc_invoice_pdf_table_thick_border', 3 );
$cell_padding				= get_option( 'wp_wc_invoice_pdf_table_cell_padding', 5 );

// fine print
$fine_print_font			= get_option( 'wp_wc_invoice_pdf_fine_print_font', 'Helvetica' );
$fine_print_font_size		= get_option( 'wp_wc_invoice_pdf_fine_print_font_size', 6 );
$fine_print_color			= get_option( 'wp_wc_invoice_pdf_fine_print_color', '#000' );
$fine_print_h3				= intval( $fine_print_font_size ) + 2;
$fine_print_h2				= $fine_print_h3 + 1;
$fine_print_h1				= $fine_print_h2 + 1;

?>
<style>
	strong, b, th {
		font-weight: <?php echo get_option( 'wp_wc_invoice_pdf_default_font_weight_bold', 'bold' ); ?>
	}

	p,  time, table, tr, th, td, span, h1, h2, h3, h4, h5, h6,  {
		line-height: normal;
		vertical-align: middle;	
	}

	tr td p { 
		padding-top: 0; 
		padding-bottom: 0; 
		margin-top: 0; 
		margin-bottom: 0;
	}

	br.wcvat-br {
		display: none;
	}

	.helper-billing-address {
		border: <?php echo ( ( $billing_addr_border_color == '' ) ? 'none' : ( 'solid ' . $billing_addr_border_px .'px ' . $billing_addr_border_color ) ); ?>;
		border-radius: <?php echo $billing_addr_border_radius . $user_unit; ?>;
		width: <?php echo $billing_address_width . $user_unit ?>;
		margin-left: -0.5cm;
		margin-top: <?php echo $billing_address_margin_top . $user_unit; ?>;
		margin-bottom: <?php echo $billing_address_margin_b . $user_unit; ?>;	
	}

	<?php if ( apply_filters( 'wp_wc_invoice_pdf_frame_reflower_table_fix', false ) ) { ?>

		table {
			border-collapse: seperate;
    		border-spacing: 0.0000001cm;
		}

	<?php } ?>	

	table.billing-address, table.billing-address tr td {
		width: <?php echo $billing_addr_helper_width . $user_unit; ?>;
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	
	table.billing-address{ }
		
	table.billing-address tr td.additional-notation {
		padding: 0.5cm 0.5cm 0 0.5cm;	
		height: 1.27cm;
		font-size: <?php echo $billing_addr_note_font_size; ?>pt;
	}
	
	table.billing-address tr td.address {
		padding: <?php echo ( ( $additoinal_notation != '{{blank}}' ) ? 0 : '0.5cm' ); ?> 0.5cm 0.5cm 0.5cm;	
		height: <?php echo ( ( ( $billing_address_height != 0 ) && ( $billing_addr_helper_height > 0  ) ) ? ( $billing_addr_helper_height . $user_unit ) : 'auto' );?> ;
	}
	
	table.subject, table.subject tr {
		width: 100%;	
	}
	
	table.subject, table.subject tr td {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size_subject; ?>pt;
		color: <?php echo $color; ?>;
		margin-bottom: <?php echo $margin_after_subject . $user_unit; ?>;
		font-weight: <?php echo get_option( 'wp_wc_invoice_pdf_default_font_weight_bold', 'bold' ); ?>;
		vertical-align: top;
	}
	
	table.subject tr td.invoice-date { 
		font-size: <?php echo $font_size_small; ?>pt; 
		text-align: right;
		font-weight: normal;
		width: 30%;
	}

	table.subject tr td.refund-subject-line-2 { 
		font-size: <?php echo $refund_font_size_small; ?>pt; 
		font-weight: normal;
	}
	
	table.subject tr td.subject { 
		width: 70%;
	}
	
	table.welcome-text, table.welcome-text tr td, table.welcome-text tr td {
		width: 100%;
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin-bottom: 1em;
	}
	
	table.before-order-table, table.before-order-table tr td, table.before-order-table tr td p{
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	
	table.before-order-table tr td p{
		margin: 0;	
	}
	
	table.before-order-table {
		margin-bottom: 1em;
		width: 100%;
	}
	
	table.before-order-table tr td h1, table.before-order-table tr td h2, table.before-order-table tr td h3, table.before-order-table tr td h4, table.before-order-table tr td h5, table.before-order-table tr td h6, table.before-order-table tr td span {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin: 0.5em 0;
	}
	
	table.before-order-table tr td ul {
	  margin: 0;
	  margin-top: 0.5em;
	  list-style: none;	
	}
	
	table.invoice-table{	
		width: 100%; 
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin-bottom: 0;
		margin-top: 0;
	}
	
	table.items-table {
		border-top: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>; 
		border-left: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
		border-right: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
	}
	
	table.totals-table {
		border-left: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
		border-right: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
		border-bottom: <?php echo 'solid '  . $border_width . 'px ' . $border_color; ?>;  
	}
	
	table.invoice-table span {
		font-family: <?php echo $font; ?>;
	}
	
	table.invoice-table thead tr th { 
		text-align:left; 
		border: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
	}
	
	table.invoice-table tbody tr td { 
		text-align: left; 
		vertical-align: middle; 
		border: <?php echo 'solid ' . $border_width . 'px ' . $border_color; ?>;  
	}
	
	table.invoice-table tbody tr td.sku { }
	
	table.invoice-table tbody tr td.product-name { 
		word-wrap: break-word; 
		width: 50%;
	}
	
	table.invoice-table tbody tr td.product-name span.smaller { 
		font-size: <?php echo $font_size_small; ?>pt;
		display: block;
	}
	
	table.invoice-table tbody tr td.product-name span.purchase-note {
		font-size: <?php echo $font_size_small; ?>pt;
		display: block;
	}
	
	table.invoice-table tbody tr td.quantity { }
	
	table.invoice-table tbody tr td.subtotal { }
	
	table.invoice-table tbody tr td.subtotal > span { 
		display: block; 
	}
	
	table.invoice-table tbody tr td.subtotal span.product-tax{
		font-size: <?php echo $font_size_small; ?>pt; 
	}
	
	table.invoice-table tbody tr td.subtotal span span { }
	
	table.invoice-table tr td.totals, table.invoice-table th.totals { 
		text-align:left; border: <?php echo 'solid ' . $border_width .'px ' . $border_color; ?>; 
	}
	
	table.invoice-table tr th.extra-border, table.invoice-table tr td.extra-border { 
		border-top-width: <?php echo $thick_border; ?>px; 
	}
	
	table.invoice-table tr td.totals small {
		font-size: <?php echo $font_size_small; ?>pt;
	}
	
	table.shipping-address {
		margin-top: 1em;	
	}
	
	table.shipping-address, table.shipping-address tr td{
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	
	table.shipping-address tr td h1, table.shipping-address tr td h2, table.shipping-address tr td h3, table.shipping-address tr td h4, table.shipping-address tr td h5, table.shipping-address tr td h6, table.shipping-address tr td span {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin: 0;
	}
	
	table.shipping-address tr td h3.title {
		margin-bottom: 0.25em;	
	}
	
	table.after-order-table {
		margin-top: 1em;
		width: 100%;
	}
	
	table.after-order-table, table.after-order-table tr td{
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	
	table.after-order-table tr td h1, table.after-order-table tr td h2, table.after-order-table tr td h3, table.after-order-table tr td h4, table.after-order-table tr td h5, table.after-order-table tr td h6, table.after-order-table tr td span, table.after-order-table tr td p, table.after-order-table tr td p b {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin: 0;
	}
	
	table.order-meta {
		margin-top: 1em;	
	}
	
	table.order-meta, table.order-meta tr td, table.order-meta, table.order-meta tr td p, table.order-meta, table.order-meta tr td p strong {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	table.order-meta tr td h1, table.order-meta tr td h2, table.order-meta tr td h3, table.order-meta tr td h4, table.order-meta tr td h5, table.order-meta tr td h6, table.order-meta tr td span {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin: 0;
	}
	
	table.after-content-text {
		margin-top: 1em;
		width: 100%;
	}
	
	table.after-content-text, table.after-content-text tr td {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		vertical-align: top;
	}
	table.after-content-text tr td h1, table.after-content-text tr td h2, table.after-content-text tr td h3, table.after-content-text tr td h4, table.after-content-text tr td h5, table.after-content-text tr td h6, table.after-content-text tr td span {
		font-family: <?php echo $font; ?>;
		font-size: <?php echo $font_size; ?>pt;
		color: <?php echo $color; ?>;
		margin: 0;
	}

	table.invoice-table tbody tr td.net_prices, table.invoice-table thead tr th.header_net_prices{
		text-align: right;
	}

	table.after-order-table tr td > * + table.qr-code {
	    margin-top: 1em;
	}
	
	.fine_print, .fine_print p{ 
		color: <?php echo $fine_print_color; ?>; 
		font-size: <?php echo $fine_print_font_size; ?>pt; 
		font-family: <?php echo $fine_print_font; ?>;
		text-align: justify;
	}	
	
	.fine_print p{ margin: 0.5em 0; }
	
	.fine_print h1, .fine_print h2, .fine_print h3, .fine_print h4, .fine_print h5, .fine_print h6 {
		color: <?php echo $fine_print_color; ?>;
		font-family: <?php echo $fine_print_font; ?>;
		margin: 0.5em 0;
	}
	
	.fine_print h1 {
		font-size: <?php echo $fine_print_h1; ?>pt;	
	}
	
	.fine_print h2 { 
		font-size: <?php echo $fine_print_h2; ?>pt;	
	}
	
	.fine_print h3 { 
		font-size: <?php echo $fine_print_h3; ?>pt;	
	}

	<?php if ( get_option( 'wp_wc_invoice_pdf_paper_orientation', 'portrait' ) != 'portrait' ) { 
		$height	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '21cm' : '8.5in';
		$width	= ( get_option( 'wp_wc_invoice_pdf_paper_size', 'A4' ) == 'A4' ) ? '29.7cm' : '11in';
	?>
	
	.background-color{ 
		width: <?php echo $width; ?>; height: <?php echo $height; ?>;
	}

	.after-order-table table thead{ display: table !important; }
	
	<?php } ?>
</style>
