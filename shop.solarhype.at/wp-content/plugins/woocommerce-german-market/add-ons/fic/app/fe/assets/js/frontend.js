jQuery( document ).ready(function() {

	var variation_found = false;

	jQuery( document ).on( 'found_variation', 'form.cart', function( event, variation ) {
		
		variation_found = true;

		var variation_id = variation.variation_id;
		//console.log( 'found variation' );
		// Nutritional Values
		var data = {
			'action': 'gm_fic_product_update_variation',
			'nonce': gm_fix_ajax.nonce,
			'id': variation_id,
		};

		jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {
			
			if ( jQuery( '#gm_fic_nutritional_values' ).length )  {
				jQuery( '#gm_fic_nutritional_values' ).html( response );
			}

		});

		// Allergens
		var data = {
			'action': 'gm_fic_product_update_variation_allergens',
			'nonce': gm_fix_ajax.nonce,
			'id': variation_id,
		};
		
		jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {

			if ( jQuery( '#gm_fic_allergens' ).length )  {
				jQuery( '#gm_fic_allergens' ).html( response );
			}

		});

		// Ingredients
		var data = {
			'action': 'gm_fic_product_update_variation_ingredients',
			'nonce': gm_fix_ajax.nonce,
			'id': variation_id,
		};
		
		jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {

			if ( jQuery( '#gm_fic_ingredients' ).length )  {
				jQuery( '#gm_fic_ingredients' ).html( response );
			}

		});

		variation_found = false;

	});

	var doing_ajax_hide_variation = false;

	jQuery('.single_variation').on( 'hide_variation', function() {
		
		if ( ! doing_ajax_hide_variation ) {

			doing_ajax_hide_variation = true;

			var product_id = jQuery( 'input[type="hidden"][name="product_id"]' ).val();

			// Nutritional Values
			var data = {
				'action': 'gm_fic_product_update_variation',
				'nonce': gm_fix_ajax.nonce,
				'id': product_id,
			};

			jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {
			
				if ( jQuery( '#gm_fic_nutritional_values' ).length )  {
					jQuery( '#gm_fic_nutritional_values' ).html( response );
					doing_ajax_hide_variation = false;
				}

			});

			// Allergens
			var data = {
				'action': 'gm_fic_product_update_variation_allergens',
				'nonce': gm_fix_ajax.nonce,
				'id': product_id,
			};
			
			jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {

				if ( jQuery( '#gm_fic_allergens' ).length )  {
					jQuery( '#gm_fic_allergens' ).html( response );
					doing_ajax_hide_variation = false;
				}

			});

			// Ingredients
			var data = {
				'action': 'gm_fic_product_update_variation_ingredients',
				'nonce': gm_fix_ajax.nonce,
				'id': product_id,
			};
			
			jQuery.post( gm_fix_ajax.ajax_url, data, function( response ) {

				if ( jQuery( '#gm_fic_ingredients' ).length )  {
					jQuery( '#gm_fic_ingredients' ).html( response );
					doing_ajax_hide_variation = false;
				}

			});

		}

	});

});
