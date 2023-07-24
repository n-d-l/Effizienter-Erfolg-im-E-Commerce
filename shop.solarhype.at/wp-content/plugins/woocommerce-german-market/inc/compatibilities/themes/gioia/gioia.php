<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class German_Market_Theme_Compatibility_Gioia {

	/**
	* Theme gioia: 2xPrice in Loop
	*
	* @since v3.8.2
	* @tested with theme version 1.1
	* @wp-hook after_setup_theme
	* @return void
	*/
	public static function init() {
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Theme_Compatibilities', 'theme_support_hide_gm_price_in_loop' ), 10, 3 );
	}
}
