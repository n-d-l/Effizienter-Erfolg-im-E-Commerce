<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Blocksy {

	/**
	* Theme Blocksy
	*
	* @since Version: 3.10.6.0.6
	* @wp-hook after_setup_theme
	* @tested with theme version 1.7.68
	* @return void
	*/
	public static function init() {

		add_action( 'wp', function() {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 12 );
		}, 20 );
		
		add_action( 'wp_head', array( __CLASS__, 'additional_css' ) );
	}

	/**
	* Some additonal CSS for Floating Bar
	*
	* @since Version: 3.10.6.0.9
	* @wp-hook wp_head
	* @return void
	*/
	public static function additional_css() {
		?>
		<style>
			@media (min-width: 690px) {
				.floating-bar-content .wgm-info {
				    display: none;
				}
			}

			@media (max-width: 689.98px) {
				.floating-bar-actions .wgm-info {
   					 display: none;
				}
			}

			.floating-bar-content .legacy-itemprop-offers .price {
				display: none;
			}

			.floating-bar-content .wgm-info {
				font-size: 10px;
			}

			.floating-bar-actions .legacy-itemprop-offers .price {
				display: none;
			}

			.floating-bar-actions .wgm-info {
				font-size: 10px;
			}

			.ct-quick-view-card .ct-price-container .price {
				display: none;
			}

			.ct-quick-view-card .ct-price-container .legacy-itemprop-offers .price {
				display: block;
				margin-bottom: 0;
			}

			.ct-quick-view-card .ct-price-container .legacy-itemprop-offers {
				margin-bottom: 1em;
			}

		</style>
		<?php
	}
}
