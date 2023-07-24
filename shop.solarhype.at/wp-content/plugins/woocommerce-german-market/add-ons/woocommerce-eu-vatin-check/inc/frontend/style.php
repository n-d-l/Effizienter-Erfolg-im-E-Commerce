<?php
/**
 * Feature Name: Styles
 * Author:		 Inpsyde GmbH for MarketPress.com
 * Author URI:   http://marketpress.com
 * Licence:      GPLv3
 */

/**
 * Enqueue styles and scripts.
 *
 * @wp-hook wp_enqueue_scripts
 *
 * @return  Void
 */
function wcvat_wp_enqueue_styles() {

	$styles = wcvat_get_styles();

	foreach( $styles as $key => $style ){
		wp_enqueue_style(
			$key,
			$style[ 'src' ],
			$style[ 'deps' ],
			$style[ 'version' ],
			$style[ 'media' ]
		);
	}
}

/**
 * Returning our Styles
 *
 * @return  Array
 */
function wcvat_get_styles(){

	$suffix = wcvat_get_script_suffix();

	// $handle => array( 'src' => $src, 'deps' => $deps, 'version' => $version, 'media' => $media )
	$styles = array();

	// adding the main-CSS
	$styles[ 'woocommerce-eu-vatin-check-style' ] = array(
		'src'       => wcvat_get_asset_directory_url( 'css' ) .  'frontend' . $suffix . '.css',
	    'deps'      => NULL,
	    'version'   => NULL,
	    'media'     => NULL
	);

	return apply_filters( 'wcvat_get_styles', $styles );
}
