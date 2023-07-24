<?php
/**
 * Checkout terms and conditions checkbox
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'off' ) {
	$current_action = current_filter();
	if ( $current_action != apply_filters( 'gm_checkout_terms_filter_name_return_check', 'woocommerce_de_add_review_order' ) ) {
		return;
	}
}

do_action( 'woocommerce_checkout_terms_and_conditions' );

if ( get_option( 'german_market_checkbox_1_tac_pd_rp_activation', 'on' ) == 'off' ) {
	return;
}

$terms_and_condition_text = WGM_Template::get_terms_text();

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) ) : ?>
	<?php 

	do_action( 'woocommerce_checkout_before_terms_and_conditions' ); 

	// Has checkout already been validated
	$p_class = WGM_Template::get_validation_p_class( 'terms', 'maybe' ); 

	?>

	<p class="form-row <?php echo $p_class; ?>">
		
		<label for="terms" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">

			<?php if ( get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'on' ) { ?>
				<input type="checkbox" class="input-checkbox" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?> id="terms" />
			<?php } 
		
			?><span class="woocommerce-terms-and-conditions-checkbox-text"><?php echo $terms_and_condition_text; ?></span><?php

			 if ( get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'on' ) { 
				?>&nbsp;<span class="required">*</span><?php 
			} ?>

		</label>
		
		<?php if ( get_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' ) == 'on' ) { ?>
			<input type="hidden" name="terms-field" value="1" />
		<?php } ?>

	</p>
	<?php do_action( 'woocommerce_checkout_after_terms_and_conditions' ); ?>
<?php endif; ?>
