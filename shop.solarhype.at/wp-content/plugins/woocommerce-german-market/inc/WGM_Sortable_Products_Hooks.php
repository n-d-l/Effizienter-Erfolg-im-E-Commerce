<?php

/**
 * Class WGM_Sortable_Products_Hooks
 *
 * This Class is loaded in the plugin file
 * Load WGM_Sortable_Products by autoloader
 */
class WGM_Sortable_Products_Hooks {

	// Initialize the Hooks
	public static function init() {
		
		// incompatibilities
		if ( defined( 'THEMECOMPLETE_EPO_PLUGIN_FILE' ) || defined( 'TM_EPO_VERSION' ) ) { // WooCommerce TM Extra Product Options
			add_filter( 'german_market_sort_products_is_available', '__return_false' );
		}

		if ( apply_filters( 'german_market_sort_products_is_available', true ) ) { 
			
			// Sorting Order Items
			if ( 'standard' !== get_option( 'gm_checkout_sort_products_by', 'standard' ) ) {
				add_filter( 'woocommerce_order_get_items', array( 'WGM_Sortable_Products', 'gm_sort_woocommerce_order_get_items' ), 10, 3 );
			}
			
			// Sorting Cart Items
			if ( 'standard' !== get_option( 'gm_cart_sort_products_by', 'standard' ) ) {
				add_action( 'woocommerce_cart_loaded_from_session', array( 'WGM_Sortable_Products', 'gm_sort_woocommerce_cart_items' ) );
			}
		}
	}

}
