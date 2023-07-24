jQuery( document ).ready( function( $ ) {
	
	var german_market_eu_vatin_admin = {

		init: function() {
			
			if ( update_billing_vat.option_value === 'on' ) {
				$( '#customer_user' ).on( 'change', this.change_customer_user );
				$( '.gm_load_customer_billing_vat' ).on( 'click', this.load_manually );
			}
			
		},

		change_customer_user: function() {

			var user_id = $( '#customer_user' ).val();

			if ( user_id ) {
				if ( $( '#billing_vat' ).length && $( '#billing_vat' ).val() === '' ) {
					if ( ! $( '#billing_vat' ).hasClass( 'loading' ) ) {
						$( '#billing_vat' ).addClass( 'loading' );
						german_market_eu_vatin_admin.load_billing_vat_from_profile( user_id, 'hide_error' );
					}
				}
			};
		},

		load_manually: function( event ) {

			event.preventDefault();

			var user_id = $( '#customer_user' ).val();
			if ( user_id ) {
				german_market_eu_vatin_admin.load_billing_vat_from_profile( user_id, 'show_error' );
			} else {
				window.alert( update_billing_vat.messages.no_customer_selected );
			}
		},

		load_billing_vat_from_profile: function( user_id, show_error ) {
			
			if ( $( '#billing_vat' ).length ) {
				
				$( '#billing_vat' ).prop( 'disabled', true );

				var data = {
					user_id : user_id,
					action  : 'wcvat_admin_load_vat_from_profile',
					security: update_billing_vat.nonce
				};

				$.ajax({
					url: update_billing_vat.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {

						if ( response !== 'empty' ) {
							$( '#billing_vat' ).val( response );
						} else {
							if ( show_error === 'show_error' ) {
								window.alert( update_billing_vat.messages.no_vat_saved );
							}
						}
						
					},
					complete: function() {
						$( '#billing_vat' ).prop( 'disabled', false );
						$( '#billing_vat' ).removeClass( 'loading' );
					}
				});
			}
		}

	};

	german_market_eu_vatin_admin.init();

});
