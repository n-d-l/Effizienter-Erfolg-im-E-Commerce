<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Storefront {

	/**
	* Theme Storefront
	*
	* @since 3.15
	* @tested with theme version 4.1.0
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		// product review short info text in "sticky add to cart"
		$option = get_option( 'gm_legal_information_product_reviews_star_ratings', 'nothing' );
		if ( 'short_text' === $option ) {
			add_action( 'wp_head', array( __CLASS__, 'extra_css_short_product_review_info' ) );
		} else if ( 'complete_text' === $option ) {
			add_action( 'wp_head', array( __CLASS__, 'extra_css_complete_product_review_info' ) );
		}
	}

	/**
	* add inline style to wp_head
	*
	* @wp-hook wp_head
	* @return void
	*/
	public static function extra_css_short_product_review_info() {
		?>
		<style>
			.storefront-sticky-add-to-cart { overflow:visible!important; }
		</style><?php
	}

	/**
	* add inline style to wp_head
	*
	* @wp-hook wp_head
	* @return void
	*/
	public static function extra_css_complete_product_review_info() {
		?>
		<style>
			.storefront-sticky-add-to-cart__content-product-info { max-width: 66.66%; }
		</style><?php
	}
}
