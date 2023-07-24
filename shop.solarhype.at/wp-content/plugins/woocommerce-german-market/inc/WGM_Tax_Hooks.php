<?php

/**
 * Class WGM_Tax_Hooks
 *
 * This Class is loaded in the plugin file
 * Load WGM_Tax by autoloader
 */
class WGM_Tax_Hooks {

	public static function init() {

		add_filter( 'wgm_product_summary_parts', array( 'WGM_Tax', 'add_tax_part' ), 1, 2 );

		if ( ! function_exists( 'wc_tax_enabled' ) ) {
			return;
		}
		
		if ( wc_tax_enabled() && 'excl' === get_option( 'woocommerce_tax_display_cart' ) ) {
			add_filter( 'woocommerce_cart_tax_totals', array( 'WGM_Tax', 'woocommerce_cart_tax_or_order_totals' ) , 10, 2 );
			add_filter( 'woocommerce_order_get_tax_totals', array( 'WGM_Tax', 'woocommerce_cart_tax_or_order_totals' ) , 10, 2 );
		}

		add_action( 'woocommerce_email_order_details', array( 'WGM_Tax', 'new_line_excl_incl_string_in_emails' ), 10, 4 );
		add_action( 'gm_before_email_customer_confirm_order', array( 'WGM_Tax', 'new_line_excl_incl_string_in_email_customer_confirm_order' ), 10, 3 );

		if ( self::is_kur() ) {
			add_filter( 'woocommerce_get_order_item_totals', array( 'WGM_Tax', 'remove_tax_order_item_totals' ), 10, 2 );
		}

		// WooCommerce summarize taxes by code, this code should contain the percent rate, so rates with same label but different percent are not summarized
		add_filter( 'woocommerce_rate_code', array( 'WGM_Tax', 'woocommerce_rate_code_add_percent_to_code' ), 10, 2 );
	}

	/**
	 * Returns true if the current Shop has activated the "kur"-option (*K*lein*u*nternehmer*r*egelung).
	 *
	 * @author  ChriCo
	 *
	 * @issue   #418
	 * @return  bool true|false
	 */
	public static function is_kur() {

		return ( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) === 'on' );
	}
}
