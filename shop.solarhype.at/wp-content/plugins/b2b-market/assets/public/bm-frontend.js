jQuery( function( $) {

	if ( 'undefined' !== typeof bm_frontend_js ) {
		if ( 'gm_sepcial' == bm_frontend_js.german_market_price_variable_products ) {
			var product = $( '.single-product' );
			product.on( 'found_variation', '.variations_form', function() {
				if ( $( '#german-market-variation-price .woocommerce-variation-bulk-discount-string' ).length ) {
					$( '#german-market-variation-price .woocommerce-variation-bulk-discount-string' ).remove();
				}
				var variation_content = $( '.woocommerce-variation.single_variation ');
				if ( $( variation_content ).find( '.woocommerce-variation-bulk-prices' ).length ) {
					$( variation_content ).find( '> div:not(.woocommerce-variation-bulk-prices)' ).hide();
					$( variation_content ).show();
				}
				if ( $( variation_content ).find( '.woocommerce-variation-bulk-discount-string' ).length ) {
					var discount_string = $( variation_content ).find( '.woocommerce-variation-bulk-discount-string' ).clone();
					$( discount_string ).css( 'display', '' );
					$( '#german-market-variation-price .wgm-info:last' ).after( discount_string );
				}
			});
			product.on( 'reset_variation', '.variations_form', function() {
				if ( $( '#german-market-variation-price .woocommerce-variation-bulk-discount-string' ).length ) {
					$( '#german-market-variation-price .woocommerce-variation-bulk-discount-string' ).remove();
				}
			});
		}
	}

});