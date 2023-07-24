jQuery( document ).ready( function() { 
    
	jQuery( '.gm-3-2-expires-one button.notice-dismiss' ).ready( function() {

		jQuery( '.gm-3-2-expires-one button.notice-dismiss' ).on( 'click', function() {

			var data = {
				'action': 'woocommerce_de_dismiss_licence_notice_one',
			};

			jQuery.post( gm_licence_ajax_object.ajax_url, data, function( response ) {
				console.log( response );
			});

		});

	});

	jQuery( '.gm-3-2-expires-two button.notice-dismiss' ).ready( function() {

		jQuery( '.gm-3-2-expires-two button.notice-dismiss' ).on( 'click', function() {

			var data = {
				'action': 'woocommerce_de_dismiss_licence_notice_two',
			};

			jQuery.post( gm_licence_ajax_object.ajax_url, data, function( response ) {
				console.log( response );
			});

		});

	});

});
