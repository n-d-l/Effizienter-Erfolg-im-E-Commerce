<?php
/**
 * Sepa Mandate Email
 *
 * @author      MarketPress
 * @package     WooCommerce_German_Market
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $this->heading, $email ); ?>

<?php do_action( 'german_market_email_sepa_before_content', 'html' ); ?>

<?php echo $content; ?>

<?php do_action( 'german_market_email_sepa_after_content', 'html'  ); ?>

<?php do_action( 'woocommerce_email_footer', $email  ); ?>
