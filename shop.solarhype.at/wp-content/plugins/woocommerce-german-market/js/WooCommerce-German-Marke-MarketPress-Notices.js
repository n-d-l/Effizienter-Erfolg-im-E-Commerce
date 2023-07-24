jQuery( document ).ready( function() { 

	jQuery( '.marketpress-atomion-gm-b2b-notice-in-gm button.notice-dismiss' ).ready( function() {

		jQuery( '.marketpress-atomion-gm-b2b-notice-in-gm button.notice-dismiss' ).on( 'click', function() {
			
			var data = {
				'action': 'gm_dismiss_marketpress_notice',
				'nonce' : gm_marketpress_ajax_object.nonce
			};

			jQuery.post( gm_marketpress_ajax_object.ajax_url, data, function( response ) {
			});

		});

	});

});
