<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<?php if ( sizeof( $checkout->checkout_fields ) > 0 ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="col2-set" id="customer_details">
			<div class="col-1">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

	<?php
		$headings_out_div 	= '';
		$headings_in_div  	= '';

		if ( get_option( 'gm_deactivate_checkout_hooks', 'off' ) == 'off' ) { 
			$headings = '<h3 id="order_review_heading">' . __( 'Payment Method', 'woocommerce-german-market' ) . '</h3>';
		 } else {
			$headings = '<h3 id="order_review_heading">' . __( 'Your order', 'woocommerce' ) . '</h3>';
		}

		if ( 'off' === get_option( 'gm_checkout_order_review_headings_in_order_review_div', 'off' ) ) {
			$headings_out_div = $headings;
		} else {
			$headings_in_div = $headings;
		}

		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'off' ) { ?>

			<?php echo $headings_out_div; ?>

			<div id="order_review" class="woocommerce-checkout-review-order">

				<?php echo $headings_in_div; ?>

				<?php do_action( 'woocommerce_de_checkout_payment' ); ?>
				
				<?php if ( get_option( 'gm_deactivate_checkout_hooks', 'off' ) == 'off' ) { ?>
					<h3 id="order_review_heading"><?php _e( 'Your order', 'woocommerce' ); ?></h3>
				<?php } ?>
				
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

		<?php } else { ?>

			<h3 id="order_review_heading"><?php _e( 'Your order', 'woocommerce' ); ?></h3>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

		<?php } ?>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
