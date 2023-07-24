<?php
/**
 * Template for girocode
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/girocode.php
 *
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
* Available variables:
* 
* Text next to QR-Code:     $font, $font_size, $font_colo, $text_align, $vertival_align, $cell_padding,
*                           $padding_left, $padding_right, $style
*
* QR-Code:                  $complete_size, $margin, $border_width, $border_color, $td_width, $qr_code_markup
*
* Text under QR-Code:       $text_under_qr_code, $text_under_font, $text_under_size, $text_under_align,
*                           $text_under_color, $text_under_style
*
* Global:                   $girocode_alignment, first_td, $last_td
*
* Do not use any line breaks within html markup, it could be replaced through a <br>-tag
*/

$markup = '<table class="qr-code" style="width: 100%;" cellspacing="0" cellpadding="0" border="0"><tr>';
$markup .= $first_td;
$markup .= '<td style="width: ' . $td_width . $unit . '; height: ' . $td_width . $unit . '; box-sizing: border-box; font-size: 0;">';
$markup .=  '<div style="border: solid ' . $border_width . 'px ' . $border_color . '; display: inline-block; padding: ' . $margin . 'cm; box-sizing: border-box; font-size: 0;">';
$markup .=  $qr_code_markup;
$markup .=  '</div>';    
$markup .=  '</td>';
$markup .= $last_td;

if ( ! empty( trim( $text_under_qr_code ) ) ) {
    if ( 'left' === $girocode_alignment ) {
        $markup .='<tr><td style="' . $text_under_style .'">' . $text_under_qr_code . '</td><td>&nbsp;</td></tr>';
    } else {
        $markup .='<tr><td>&nbsp;</td><td style="' . $text_under_style .'">' . $text_under_qr_code . '</td></tr>';
    }
    
}

$markup .= '</tr></table>';
echo $markup;
