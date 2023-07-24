<?php
/**
 * Customer confirmation order email
 *
 * @author      MarketPress
 * @package     WooCommerce_German_Market
 * @version     2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email ); 
$pw_info = ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && ! empty( $user_pass ) ) ? ' ' . sprintf( __( 'Your password has been automatically generated: %s', 'b2b-market' ), '<strong>' . $user_pass . '</strong><br />' ) : '';
?>
	
	<p>
		<?php echo sprintf( __( 'Thanks for creating a customer account on %s. Your username is %s.', 'b2b-market' ), esc_html ( get_bloginfo( 'name' ) ), '<strong>' . esc_html( $user_login ) . '</strong>' ); ?>
	</p>

	<?php if ( ! empty( $pw_info ) ) { ?>

		<p>
			<?php echo $pw_info; ?>
		</p>

	<?php } ?>

	<p>
		<?php echo __( 'Please follow the activation link to activate your account:', 'b2b-market' ); ?>
	</p>

	<p>
		<a href="<?php echo $activation_link; ?>"><?php echo $activation_link; ?></a>
	</p>

	<?php 

	if ( ! $resend ) {
		
		$extra_text = RGN_Double_Opt_In_Registration::get_autodelete_extra_text();
		if ( ! empty( $extra_text ) ) { ?>
			<p>
				<?php echo $extra_text; ?>
			</p>
		<?php }
	} ?>

	<p>
		<?php echo sprintf( __( 'If you haven\'t created an account on %s please ignore this email.', 'b2b-market' ), esc_html( get_bloginfo( 'name' ) ) );?>
	</p>


<?php do_action( 'woocommerce_email_footer', $email ); ?>
