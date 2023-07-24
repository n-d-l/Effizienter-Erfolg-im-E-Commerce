<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Astra{

	/**
	* Theme Astra: Doubled Price in loop
	*
	* @since v3.7.1
	* @tested with theme version 1.4.10
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// shop
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

		// for some loops that are marked as "single"
		add_filter( 'wgm_template_get_wgm_product_summary_choose_hook', function( $hook, $woocommerce_loop ) {
			if ( 'single' === $hook ) {
				$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
				foreach ( $debug_backtrace as $elem ) {
					if ( 'woocommerce_de_price_with_tax_hint_loop' === $elem[ 'function' ] ) {
						$hook = 'loop';
						break;
					}
				}
			} else if ( 'loop' === $hook ) {
				$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
				foreach ( $debug_backtrace as $elem ) {
					if ( 'woocommerce_de_price_with_tax_hint_single' === $elem[ 'function' ] ) {
						$hook = 'single';
						break;
					}
				}
			}
			return $hook;
		}, 10, 2 );

		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_action( 'astra_woo_shop_price_after',array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ) );

		if ( class_exists( 'ASTRA_Ext_WooCommerce_Markup' ) ) {

			// Astra Pro Plugin

			add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
			remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );

			add_action( 'astra_woo_single_price_before', function() {
				echo '<div class="legacy-itemprop-offers">';

			});

			add_action( 'astra_woo_single_price_after', function() {

				global $product;

				if ( $product instanceof WC_Product_Grouped ) {
					return;
				}

				echo WGM_Template::get_wgm_product_summary( $product, 'theme_support_astra' );

				echo '</div>';

				if ( apply_filters( 'gm_compatibility_is_variable_wgm_template', true, $product ) ) {



					if ( is_a( $product, 'WC_Product_Variable' ) ) {
						WGM_Template::add_digital_product_prerequisits( $product );
					}

				}
			});

		}

		// two step checkout
		if ( function_exists( 'astra_get_option' ) ) {
			$two_step_checkout = astra_get_option( 'two-step-checkout' );
			if ( $two_step_checkout ) {
				add_filter( 'german_market_add_woocommerce_de_templates_force_original', '__return_true' );
			}
		}
	}
}
