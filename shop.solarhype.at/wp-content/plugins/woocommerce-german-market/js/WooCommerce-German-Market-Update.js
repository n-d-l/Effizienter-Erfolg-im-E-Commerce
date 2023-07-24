jQuery( document ).ready( function() { 
    
	jQuery( '.gm-3-2-update-notice button.notice-dismiss' ).ready( function() {

		jQuery( '.gm-3-2-update-notice button.notice-dismiss' ).on( 'click', function() {

			var data = {
				'action': 'woocommerce_de_dismiss_update_notice',
			};

			jQuery.post( gm_ajax_object.ajax_url, data, function( response ) {

			});

		});

	});

	jQuery( '.gm-3-2-update-notice-legal-texts button.notice-dismiss' ).ready( function() {

		jQuery( '.gm-3-2-update-notice-legal-texts button.notice-dismiss' ).on( 'click', function() {

			var data = {
				'action': 'woocommerce_de_dismiss_update_notice_legal_texts',
			};

			jQuery.post( gm_ajax_object.ajax_url, data, function( response ) {
				
			});

		});

	});

	jQuery( '.gm-ppu-auto-calc button.notice-dismiss' ).ready( function() {

		jQuery( '.gm-ppu-auto-calc button.notice-dismiss' ).on( 'click', function() {

			var data = {
				'action': 'woocommerce_de_dismiss_update_notice_ppu_auto_calc',
			};

			jQuery.post( gm_ajax_object.ajax_url, data, function( response ) {
				
			});

		});

	});

});
