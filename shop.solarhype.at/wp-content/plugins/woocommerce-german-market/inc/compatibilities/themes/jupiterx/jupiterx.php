<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Jupiterx {

	/**
	* Theme JupiterX
	*
	* @since v3.10.2
	* @tested with theme version 3.3.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		add_action( 'woocommerce_before_shop_loop_item', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 50 );

		// for some loops that are marked as "single"
		add_filter( 'wgm_template_get_wgm_product_summary_choose_hook', function( $hook, $woocommerce_loop ) {
			if ( $hook == 'single' ) {
				$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
				foreach ( $debug_backtrace as $elem ) {
					if ( $elem[ 'function' ] == 'woocommerce_de_price_with_tax_hint_loop' ) {
						$hook = 'loop';
					}
				}
			}
			return $hook;
		}, 10, 2 );
	}
}
