<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Zocker {

	/**
	* Theme Zocker
	*
	* @since v3.12.5
	* @wp-hook after_setup_theme
	* @tested with theme version 1.1
	* @return void
	*/
	public static function init() {

		// loop
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
		
		// single
		add_filter( 'german_market_compatibility_elementor_price_data', '__return_false' );
		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
	}
}


function zocker_loop_product_summary() {
    global $product;
    echo '<div class="product-content">';
        // Product Rating
        woocommerce_template_loop_rating();

        // Product Title
        echo '<h3 class="product-name text-normal font-theme fs-20 lh-base mb-2"><a class="text-inherit" href="'.esc_url( get_permalink() ).'">'.esc_html( get_the_title() ).'</a></h3>';
        // Product Price
        echo woocommerce_template_loop_price();
        WGM_Template::woocommerce_de_price_with_tax_hint_loop();

    echo '</div>';
}

function zocker_woocommerce_single_product_price_rating() {
    global $product;
    echo '<!-- Product Price -->';
    WGM_Template::woocommerce_de_price_with_tax_hint_single();
    echo '<!-- End Product Price -->';
    // Product Rating
    woocommerce_template_loop_rating();
}
