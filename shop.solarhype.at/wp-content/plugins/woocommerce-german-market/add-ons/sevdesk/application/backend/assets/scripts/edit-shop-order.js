jQuery( document ).ready( function(){

	jQuery( '.sevdesk-woocomerce-default' ).click( function() {

		// only if button has not been clicked before
		if ( ! ( jQuery( this ).hasClass( 'sevdesk-woocommerce-x' ) || jQuery( this ).hasClass( 'sevdesk-woocommerce-error' ) ) ) {
			return;
		}

		// get order id
		var order_id = jQuery( this ).attr( 'data-order-id' );

		// before doing ajax
		jQuery( this ).removeClass( 'sevdesk-woocommerce-x' ).removeClass( 'sevdesk-woocommerce-error dashicons dashicons-no' ).addClass( 'sevdesk-woocommerce-loader' );

		// set jQuery( this ) to a variable so we can use it in jQuery.post
		var button = jQuery( this );

		// set args
		var data = {
			action: 'sevdesk_woocommerce_edit_shop_order',
			security: sevdesk_ajax.nonce,
			order_id: order_id
		};

		// refund?
		if ( jQuery( this ).attr( 'data-refund-id' ) ) {
			var refund_id = jQuery( this ).attr( 'data-refund-id' );
			data.refund_id = refund_id;
			data.action = 'sevdesk_woocommerce_edit_shop_order_refund';
		}

		// do ajax
		jQuery.post( sevdesk_ajax.url, data, function( response ) {
			
			// error handling
			if ( response != 'SUCCESS' ) {
				jQuery( button ).html( '' );
				jQuery( button ).removeClass( 'sevdesk-woocommerce-loader sevdesk-not-completed' ).addClass( 'sevdesk-woocommerce-error dashicons dashicons-no' );
				var error_message = '<div id="message" class="error notice"><p>' + response + '</p></div>';
				jQuery( error_message ).insertAfter( '.wrap h1' ).hide().slideDown( 'fast' );

			// success handling
			} else {

				jQuery( button ).removeClass( 'sevdesk-woocommerce-loader' ).addClass( 'sevdesk-woocommerce-yes' );

			}

			console.log( response );

		} );

	});

});
