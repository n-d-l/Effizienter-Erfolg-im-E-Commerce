<?php
/**
 * Template for Sepa Mandate
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/sepa-mandate.php
 *
 * @version     3.4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$sepa_args = $args[ 'sepa_args' ];

$content = WGM_Sepa_Direct_Debit::generatre_mandate_preview( $sepa_args, $sepa_args[ 'mandate_id' ], $sepa_args[ 'date' ] );

echo wpautop( WGM_Template::remove_vc_shortcodes( $content ) );
