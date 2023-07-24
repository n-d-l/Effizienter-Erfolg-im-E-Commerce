<?php
/**
 * Customer confirm order email
 *
 * @author        MarketPress
 * @package       WooCommerce_German_Market
 * @version       2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( 'Thanks for creating a customer account on %s. Your username is %s.', 'woocommerce-german-market' ), esc_html ( get_bloginfo( 'name' ) ), '<strong>' . esc_html( $user_login ) . '</strong>' );

$pw_info = ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && ! empty( $user_pass ) ) ? ' ' . sprintf( __( 'Your password has been automatically generated: %s', 'woocommerce-german-market' ), '<strong>' . $user_pass . '</strong><br />' ) : '';

if ( ! empty( $pw_info ) ) {

 echo "\n\n" . $pw_info;

}

echo "\n\n" . __( 'Please follow the activation link to activate your account:', 'woocommerce-german-market' );

echo "\n\n" . $activation_link . "\n\n";

if ( ! $resend ) {
	$extra_text = WGM_Double_Opt_In_Customer_Registration::get_autodelete_extra_text();
	if ( ! empty( $extra_text ) ) {
		echo $extra_text . "\n\n";
	}
}

echo sprintf( __( 'If you haven\'t created an account on %s please ignore this email.', 'woocommerce-german-market' ), esc_html( get_bloginfo( 'name' ) ) );

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
