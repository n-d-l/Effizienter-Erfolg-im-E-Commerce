<?php
/**
 * Second Checkout Template
 *
 * Last edited: 2016-06-16
 * @package WGM/templates/woocommerce-german-market
 * @version 3.8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Second Checkout Setup.
 */
define( 'WGM_CHECKOUT', TRUE );
add_filter( 'woocommerce_is_checkout', '__return_true' );
// Get checkout object.
$checkout = WC()->checkout();

// Define as checkout process, to ensure correct price calculation.
if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
	define( 'WOOCOMMERCE_CHECKOUT', TRUE );

// Text for order submission button.
$buy_button_text = get_option( 'woocommerce_de_order_button_text', __( 'Place binding order', 'woocommerce-german-market' ) );
$buy_button_text = apply_filters( 'woocommerce_de_buy_button_text', $buy_button_text );

// Messages.
if ( ! WGM_Session::is_set( 'first_checkout_post_array' ) || ! WGM_Session::get( 'first_checkout_post_array' ) ) {

	$cart_url = wc_get_cart_url();
	$message  = sprintf(
		/* translators: %s = cart URL */
		__( 'Your order does not seem to contain any products. Please review your <a href="%s">shopping cart</a>.', 'woocommerce-german-market' ),
		$cart_url
	);
	$message = apply_filters(
		'wgm_second_checkout_message_error',
		sprintf( '<p>%s</p>', $message ),
		esc_url( $cart_url )
	);

	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( $message, 'error' );
		wc_print_notices();
	}
	
	return;

} else {

	$message  = sprintf(
		/* translators: %s = text of order submission button */
		__( '<strong>You’re almost there!</strong> We’re asking you to review your order items, address data, payment and shipping options one last time. When you’re ready to make your purchase, press <em>%s</em>.', 'woocommerce-german-market' ),
		$buy_button_text
	);
	$message = apply_filters(
		'wgm_second_checkout_message_success',
		sprintf( '<p>%s</p>', $message )
	);

	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( $message, apply_filters( 'wgm_second_checkout_message_success_type', 'notice' ) );
		wc_print_notices();
	}
}

// If checkout registration is disabled and user is not logged in, user cannot checkout.
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce-german-market' ) );
	return;
}

// Filter hook to include new pages inside the payment method.
$get_checkout_url = apply_filters( 'woocommerce_get_checkout_url', wc_get_checkout_url() );

// remove coupon field in second checkout
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

do_action( 'woocommerce_before_checkout_form', $checkout );

/**
 * ## Checkout form.
 *
 * Merged from woocommerce/templates/checkout/form-checkout.php
 * Last checked upstream: 2015-08-05
 */
?>
<form name="checkout" method="post" class="checkout wgm-second-checkout" action="<?php echo esc_url( $get_checkout_url ); ?>">

	<?php /**@if Checkout fields available. */
	if ( sizeof( $checkout->checkout_fields ) > 0 ) :

		/**
		 * ## Customer Details.
		 */
		do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		<div class="col2-set" id="customer_details">
			<div class="col-1">
				<?php WGM_Template::second_checkout_form_billing(); ?>
			</div>
		<?php if( WGM_Template::should_be_shipping_to_shippingadress() ) : ?>
			<div class="col-2">
				<?php WGM_Template::second_checkout_form_shipping(); ?>
			</div>
		<?php endif; ?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_customer_details' );

		/**
		 * ## Customer Order Comments.
		 */
		$order_comments = WGM_Template::checkout_readonly_field(
			'order_comments',
			array(
				'type'  => 'textarea',
				'class' => array( 'notes' ),
				'name'  => 'order_comments',
				'label' => '',
			)
		);

		// Display order comments.
		if( $order_comments && is_array( $order_comments ) ) {
			$hidden_fields[] = $order_comments[ 1 ];
			printf(
				'<div class="wgm-second-checkout-user-note"><h3 style="' . apply_filters( 'wgm_second_checkout_style_h3', '' ) . '">%s</h3><p>%s</p></div>%s',
				__( 'Order Notes', 'woocommerce-german-market' ),
				$order_comments[ 0 ],
				implode( '', $hidden_fields )
			);
		}

		/**
		 * ## Payment
		 */
		if ( WGM_Session::is_set('payment_method', 'first_checkout_post_array' )  ) : ?>
			<div class="wgm_second_checkout payment wgm_second_checkout_payment">
				<?php do_action( 'wgm_before_second_checkout_payment_method' ); ?>
				<h3 style="<?php echo apply_filters( 'wgm_second_checkout_style_h3', '' ); ?>"><?php _e( 'Payment Method', 'woocommerce-german-market' ) ?></h3>
				<?php // Print gateway icon if available.

				$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
				
				// Add Support for Amazon Payments Advances
				if ( WGM_Session::get( 'payment_method', 'first_checkout_post_array' ) == 'amazon_payments_advanced' ) {
					$payment_gateways = WC()->payment_gateways();
					foreach ( $payment_gateways->payment_gateways as $payment_gateway ) {
						if ( is_a( $payment_gateway, 'WC_Gateway_Amazon_Payments_Advanced' ) ) {
							$available_gateways[ 'amazon_payments_advanced' ] = $payment_gateway;
							break;
						}
					}
				}

				$gateway = $available_gateways[ WGM_Session::get( 'payment_method', 'first_checkout_post_array' ) ];
				$icon    = WGM_Helper::method_exists( $gateway, 'get_icon' ) ? $gateway->get_icon() : '';

				// Print title ?>
				<h4 id="payment_method" style="<?php echo apply_filters( 'wgm_second_checkout_style_h4', '' ); ?>"><?php echo apply_filters( 'gm_2ndcheckout_gateway_label', $gateway->title ); ?></h4>

				<?php echo apply_filters( 'woocommerce_gateway_icon', $icon, $gateway->id ); ?>

				<span class="wgm-break"></span>
				<?php do_action( 'wgm_after_second_checkout_payment_method' ); ?>
			</div>
		<?php endif;

		/**
		 * ## Checkout Notice
		 */
		$last_hint = get_option( 'woocommerce_de_last_checkout_hints' );
		if ( $last_hint && trim( $last_hint ) != '' ) : ?>
			<div class="checkout_hints">
				<h3 style="<?php echo apply_filters( 'wgm_second_checkout_style_h3', '' ); ?>"><?php echo __( 'Please take note:', 'woocommerce-german-market' ); ?></h3>
				<p><?php echo $last_hint; ?></p>
			</div>
			<span class="wgm-break"></span>
		<?php endif;

	endif; /**@endif Checkout fields available. */

	// Up here or else won’t send. @todo Needed?
	

	/**
	 * ## Order Details.
	 */
	?>
	<h3 id="order_review_heading" style="<?php echo apply_filters( 'wgm_second_checkout_style_h3', '' ); ?>"><?php _e( 'Your order', 'woocommerce-german-market' ); ?></h3>
	<?php // Copied from woocommerce core ( woocommerce-ajax.php ), important to update values.
	if ( WGM_Session::is_set( 'shipping_method', 'first_checkout_post_array' ) )
		$_SESSION['_chosen_shipping_method'] = WGM_Session::get( 'shipping_method', 'first_checkout_post_array' );

	if ( WGM_Session::is_set( 'country', 'first_checkout_post_array' ) )
		WC()->customer->set_country( WGM_Session::get( 'country', 'first_checkout_post_array' ) );

	if ( WGM_Session::is_set( 'state', 'first_checkout_post_array' ) )
		WC()->customer->set_state( WGM_Session::get( 'state', 'first_checkout_post_array' ) );

	if ( WGM_Session::is_set( 'postcode', 'first_checkout_post_array' ) )
		WC()->customer->set_postcode( WGM_Session::get( 'postcode', 'first_checkout_post_array' ) );

	if ( WGM_Session::is_set( 's_country', 'first_checkout_post_array' ) )
		WC()->customer->set_shipping_country( WGM_Session::get( 's_country', 'first_checkout_post_array' ) );

	if ( WGM_Session::is_set( 's_state', 'first_checkout_post_array') )
		WC()->customer->set_shipping_state( WGM_Session::get( 's_state', 'first_checkout_post_array') );

	if ( WGM_Session::is_set( 's_postcode', 'first_checkout_post_array' ) )
		WC()->customer->set_shipping_postcode( WGM_Session::get( 's_postcode', 'first_checkout_post_array' ) );

	WC()->cart->calculate_totals();

	/**
	 * ## Review Order
	 *
	 * Merged from woocommerce/templates/checkout/review-order.php
	 * Last checked upstream: 2015-08-05
	 */
	?>
	<div id="order_review">

		<table class="shop_table">
			<thead>
				<tr>
					<th class="product-name"><?php _e( 'Product', 'woocommerce-german-market' ); ?></th>
					<th class="product-total"><?php _e( 'Total', 'woocommerce-german-market' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php

			/**
			 * ## Cart Contents (Products).
			 */
			do_action( 'woocommerce_review_order_before_cart_contents' );

			// Cart items.
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :

				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', TRUE, $cart_item, $cart_item_key ) ) : ?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
						<td class="product-name">
							<?php
							// Product name.
							echo apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;';

							// Product quantity.
							echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key );

							// Product data.
							echo wc_get_formatted_cart_item_data( $cart_item );
							?>
						</td>
						<td class="product-total">
							<?php
							// Product total.
							echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
						</td>
					</tr>
				<?php endif;
			endforeach;

			do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
			</tbody>
			<tfoot>
				<?php
				/**
				 * ## Subtotal.
				 */
					if ( get_option( 'woocommerce_tax_display_cart', 'incl' ) == 'incl' ) {
						?>
						<tr class="cart-subtotal">
							<th><?php _e( 'Subtotal', 'woocommerce-german-market' ); ?></th>
							<td><?php wc_cart_totals_subtotal_html(); ?></td>
						</tr>

					<?php
				}
				/**
				 * ## Coupons.
				 */
				foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr( $code ); ?>">
						<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
						<td><?php WGM_Template::checkout_totals_coupon_html( $coupon ); ?></td>
					</tr>
				<?php endforeach;

				/**
				 * ## Shipping.
				 */
				if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) :

					do_action('woocommerce_review_order_before_shipping'); ?>
						<tr>
							<th><?php _e( 'Shipping', 'woocommerce-german-market' ); ?> </th>
							<td>
						<?php /*+@wgm Chosen shipping method. */
						$packages = WC()->shipping->get_packages();
						foreach ( $packages as $i => $package ) :
							foreach ( $package['rates'] as $key => $method ) :
								if ( WC()->session->chosen_shipping_methods[ $i ] == $key ) : ?>
								<span for="shipping_method_<?php echo $i; ?>_<?php echo sanitize_title( $method->id ); ?>"><?php echo wp_kses_post( wc_cart_totals_shipping_method_label( $method ) ); ?></span>
								<?php endif;
							endforeach;
						endforeach; /**-@wgm */ ?>
							</td>
						</tr>
					<?php do_action('woocommerce_review_order_after_shipping');
				endif;

				/**
				 * ## Fees.
				 */
				foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<tr class="fee">
						<th><?php echo esc_html( $fee->name ); ?></th>
						<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
					</tr>
				<?php endforeach;

				/**
				 * ## Taxes.
				 */

				/**@if Prices EXCLUDING tax. */
				if ( wc_tax_enabled() && get_option( 'woocommerce_tax_display_cart' ) === 'excl' ) :
					$taxes = WC()->cart->get_taxes();

					// A. Multiple tax rates in cart.
					if ( sizeof( $taxes ) > 0 ) :

						$has_compound_tax = FALSE;

						foreach ( $taxes as $key => $tax ) :
							if ( WC_Tax::is_compound( $key ) ) {
								$has_compound_tax = TRUE;
								continue;
							} ?>
							<tr class="tax-rate tax-rate-<?php echo $key; ?>">

								<?php
									$label =  WC_Tax::get_rate_label( $key );

									// percent is not shown in the label, yet
									if ( str_replace( '%', '', $label ) == $label ) {
										$rate_percent = WC_Tax::get_rate_percent( $key);
										$label .= apply_filters( 'woocommerce_de_tax_label_add_if_tax_is_excl', ' (' . $rate_percent . ')', $rate_percent );
									}

								?>

								<th><?php echo $label; ?></th>
								<td><?php echo wc_price( $tax ); ?></td>
							</tr>
						<?php endforeach;

						if ( $has_compound_tax ) : ?>
							<tr class="order-subtotal">
								<th><?php _e( 'Subtotal', 'woocommerce-german-market' ); ?></th>
								<td><?php echo WC()->cart->get_cart_subtotal( TRUE ); ?></td>
							</tr>
						<?php endif;

						foreach ( $taxes as $key => $tax ) :
							if ( ! WC_Tax::is_compound( $key ) ) {
								continue;
							} ?>
							<tr class="tax-rate tax-rate-<?php echo $key; ?>">
								<th><?php echo WC_Tax::get_rate_label( $key ); ?></th>
								<td><?php echo wc_price( $tax ); ?></td>
							</tr>
						<?php endforeach;

					// B. Single tax rate in cart.
					elseif ( WC()->cart->get_cart_tax() ) :
						?>
						<tr class="tax">
							<th><?php _e( 'Tax', 'woocommerce-german-market' ); ?></th>
							<td><?php echo WC()->cart->get_cart_tax(); ?></td>
						</tr>
						<?php
					endif;

				endif; /**@endif Prices EXCLUDING tax. */

				/**
				 * ## Total.
				 */
				do_action( 'woocommerce_review_order_before_order_total' ); ?>
				<tr class="total">
					<th><?php _e( 'Total', 'woocommerce-german-market' ); ?></th>
					<td><?php wc_cart_totals_order_total_html(); ?></td>
				</tr>
				<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
			</tfoot>
		</table>

		<?php
		/**
		 * ## Submit.
		 */
		?>
		<div class="form-row place-order wgm-place-order">

			<div class="wgm-place-order-disabled"></div>
			<?php 
				// Checkbox for Terms & Conditions
				remove_filter( 'woocommerce_checkout_show_terms', array( 'WGM_Template', 'remove_terms_from_checkout_page' ) );
				wc_get_template( 'checkout/terms.php' );
				add_filter( 'woocommerce_checkout_show_terms', array( 'WGM_Template', 'remove_terms_from_checkout_page' ) );

				// WGM Checkboxes
				do_action( 'wgm_review_order_before_submit' );
			?>

			<a href="<?php echo wc_get_checkout_url(); ?>" title="<?php esc_attr_e( 'Go back to previous page', 'woocommerce-german-market' ) ?>">
				<input type="button" class="button wgm-go-back-button" id="place_order_back" value="<?php esc_attr_e( 'Go back to previous page', 'woocommerce-german-market' ) ?>" />
			</a>
			<input type="submit" class="button alt checkout-button wgm-place-order" name="woocommerce_checkout_place_order" id="place_order" value="<?php echo esc_attr( $buy_button_text ); ?>" />

			<?php do_action( 'woocommerce_review_order_after_payment' ); ?>

			<?php // Correct referer.
			$wgm_session_first_checkout_post_array = WGM_Session::get( 'first_checkout_post_array' );
			$wgm_session_first_checkout_post_array[ '_wp_http_referer' ] = $_SERVER['REQUEST_URI'];
			// $wgm_session_first_checkout_post_array[ 'widerruf' ] = '1';
			WGM_Template::print_hidden_fields( $wgm_session_first_checkout_post_array, array_keys( $wgm_session_first_checkout_post_array ) ); ?>
		</div>
		<div class="clear"></div>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
