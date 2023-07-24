<?php

class WGM_Embed {

	public static function init() {

		// Filter all of the content that's going to be embedded.
		//add_filter( 'the_title', array( __CLASS__, 'the_title' ), PHP_INT_MAX );
		add_filter( 'the_excerpt_embed', array( __CLASS__, 'the_excerpt' ), PHP_INT_MAX );

		//// Make sure no comments display. Doesn't make sense for products.
		//remove_action( 'embed_content_meta', 'print_embed_comments_button' );

		//// In the comments place let's display the product rating.
		//add_action( 'embed_content_meta', array( __CLASS__, 'get_ratings' ), 5 );

		// Add some basic styles.
		add_action( 'embed_head', array( __CLASS__, 'print_embed_styles' ) );
	}

	/**
	 * Create the title for embedded products - we want to add the price to it.
	 *
	 * @since 2.4.11
	 *
	 * @param string $title Embed title.
	 *
	 * @return string
	 */
	public static function the_excerpt( $title ) {

		// Make sure we're only affecting embedded products.
		if ( WC_Embed::is_embedded_product() ) {

			remove_filter( 'wgm_product_summary_parts', array( 'WGM_Template', 'add_product_summary_price_part' ), 0 );

			// Get product.
			$_product = wc_get_product( get_the_ID() );

			$tax_line = '<p class="wgm_embed">';
			$tax_line .= WGM_Template::get_wgm_product_summary($_product);
			$tax_line .= WGM_Template::get_extra_costs_eu();
			$tax_line .= WGM_Template::get_digital_product_prerequisits($_product);
			$tax_line .= '<br>';

			$title = $tax_line . $title;
		}

		return $title;
	}

	/**
	 * Basic styling.
	 */
	public static function print_embed_styles() {

		if ( ! WC_Embed::is_embedded_product() ) {
			return;
		}
		?>
		<style type="text/css">
			.wgm_embed, .wgm-info {
				display: block;
				text-align: right;
				color: #82878c;
				font: 400 14px/1.5 'Open Sans', sans-serif;
			}
		</style>
		<?php
	}
}
