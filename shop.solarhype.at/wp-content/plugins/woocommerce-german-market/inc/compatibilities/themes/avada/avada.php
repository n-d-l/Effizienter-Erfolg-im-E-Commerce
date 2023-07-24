<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Avada {

	/**
	* Theme Avada Support
	*
	* @access public
	* Tested with Theme Version 5.7.2
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {

		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'remove_double_digital' ), 99, 3 );

		// removed in 3.8.1
		//add_filter( 'woocommerce_pay_order_button_html', array( $this, 'pay_for_order_page_checkboxes' ) );

		// Avada Builder
		if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
			add_action( 'woocommerce_after_template_part', function( $template_name, $template_path, $located, $args ) {

				if ( 'single-product/price.php' === $template_name || 'loop/price.php' === $template_name ) {

					$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
					foreach ( $debug_backtrace as $debug ) {

						if ( 'get_woo_price_content' === $debug[ 'function' ] || 'fusion_wc_get_template' === $debug[ 'function' ] ) {

							add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
							add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
							echo '<div style="order: 3; width: 100%;">';
							WGM_Template::woocommerce_de_price_with_tax_hint_single( 'avada_builder' );
							echo '</div>';
							remove_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_single' ), 10, 3 );
							remove_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );

							break;
						}
					}
				}

			}, 10, 4 );
		}
	}

	/**
	* Theme Avada Support: Checkboxes on pay for order page
	*
	* @access public
	* @wp-hook woocommerce_pay_order_button_html
	* @param String $markup
	* @return String
	*/
	public static function pay_for_order_page_checkboxes( $markup ) {

		if ( is_wc_endpoint_url( 'order-pay' ) ) {

			$markup = WGM_Template::add_review_order() . $markup;

		}

		return $markup;

	}

	/**
	* Theme Avada Support: Double "[Digital]" during checkout, very simple solution
	*
	* @access public
	* @wp-hook woocommerce_cart_item_name
	* @return void
	*/
	public static function remove_double_digital( $title, $cart_item, $cart_item_key ) {
		return str_replace( '[Digital] [Digital]', '[Digital]', $title );
	}

}
