<?php
/**
 * Sepa Mandate Email Plain
 *
 * @author      MarketPress
 * @package     WooCommerce_German_Market
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php echo "= " . $this->heading . " =\n\n"; ?>

<?php echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>

<?php do_action( 'german_market_email_sepa_before_content', 'plain' ); ?>

<?php echo strip_tags( WGM_Sepa_Direct_Debit::generatre_mandate_preview( $this->args, $this->args[ 'mandate_id' ], $this->args[ 'date' ] ) ); ?>

<?php do_action( 'german_market_email_sepa_after_content', 'plain' ); ?>

<?php echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>
