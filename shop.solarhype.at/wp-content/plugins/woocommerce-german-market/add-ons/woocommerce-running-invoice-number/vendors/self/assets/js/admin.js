jQuery( document ).ready( function( $ ) {
	
     // update refund number in wp list
    $( '.edit-refund-number' ).click( function() {

        var refund_id = $( this ).attr( 'data-refund-id' );
        $( '#edit-refund-number-text-field-' + refund_id ).show();
        $( '#edit-refund-number-button-' + refund_id ).show();
        $( '#refund-number-' + refund_id ).hide();
        $( this ).hide();

    });

     $( '.refund-number-save-button' ).click( function() {


		var refund_id = $( this ).attr( 'data-refund-id' );
		var new_refund_number = $( '#edit-refund-number-text-field-' + refund_id ).val();

		$( '#refund-number-' + refund_id ).html( new_refund_number );
		$( '#edit-refund-number-text-field-' + refund_id ).hide();
        $( '#edit-refund-number-a-' + refund_id ).show();
        $( '#refund-number-' + refund_id ).show();
        $( this ).hide();

        var data = {
			action: 'wp_wc_running_invoice_number_update_refund_number',
			security: wpwcrin_ajax.nonce,
			refund_id: refund_id,
			new_refund_number: new_refund_number
		};

		$.post( wpwcrin_ajax.url, data, function( response ) {
			// save data
		});

     });

	// on post, when clicked on save order, update order and download invoice is selected
	$( '.wp-wc-invoice-pdf' ).click( function( e ) {
	
		if ( $( '#order_invoice_number' ).length ) {
			var invoice_number = $( '#order_invoice_number' ).val();
			
			if ( invoice_number.trim() == '' ) {

				e.preventDefault();

				if ( $( '#post_ID' ).length ) {
					
					var post_id = $( '#post_ID' ).val();
					
					var data = {
						action: 'wp_wc_running_invoice_number_ajax_backend_post',
						security: wpwcrin_ajax.nonce,
						order_id: post_id
					};

					var wp_wc_running_invoice_number_href = $( this ).attr( 'href' );
					
					$.post( wpwcrin_ajax.url, data, function( response ) {

						var invoice_number_and_date = response.split( '[[SEPARATOR]]' );

						// replace values if fields exist and value is empty
						if ( $( '#order_invoice_number' ).length ) {
							if ( $( '#order_invoice_number' ).val().trim() == '' ) {
								$( '#order_invoice_number' ).val( invoice_number_and_date[ 0 ] );
								// change output
								$( '#order_invoice_number' ).attr( 'readonly', 'readonly' );
								$( '#wp_wc_invoice_remove_read_only' ).show();
								$( '#wp_wc_invoice_number_delete' ).show();
								$( '.wp_wc_invoice_remove_read_only_sep_delete' ).show();
								$( '.wp_wc_invoice_generate' ).hide();	
							}
						}
						
						if ( $( '#order_invoice_date' ).length ) {
							if ( $( '#order_invoice_date' ).val().trim() == '' ) {
								$( '#order_invoice_date' ).val( invoice_number_and_date[ 1 ] );
							}
						}

						window.location = wp_wc_running_invoice_number_href;
						
					});
					
				}

			}
		}
				
	});
	
	// on post, when clicked on generate invoice number and invoice date
	$( '.wp_wc_invoice_generate' ).click( function() {
		
		var order_id = $( this ).attr( 'name' );
		
		var data = {
			action: 'wp_wc_running_invoice_number_ajax_backend_post',
			security: wpwcrin_ajax.nonce,
			order_id: order_id
		};
		
		$.post( wpwcrin_ajax.url, data, function( response ) {		
			var invoice_number_and_date = response.split( '[[SEPARATOR]]' );
			
			// insert data
			$( '#order_invoice_number' ).val( invoice_number_and_date[ 0 ] );
			$( '#order_invoice_date' ).val( invoice_number_and_date[ 1 ] );
			
			// change output
			$( '#order_invoice_number' ).attr( 'readonly', 'readonly' );
			$( '#wp_wc_invoice_remove_read_only' ).show();
			$( '#wp_wc_invoice_number_delete' ).show();
			$( '.wp_wc_invoice_remove_read_only_sep_delete' ).show();
			$( '.wp_wc_invoice_generate' ).hide();			
			
		});	
			
	});

	$( '.wp_wc_invoice_generate_orders_page' ).click( function() {
		
		$( this ).hide();

		var order_id = $( this ).attr( 'data-order-id' );
		var a_element = $( this );
		
		var data = {
			action: 'wp_wc_running_invoice_number_ajax_backend_shop_order',
			security: wpwcrin_ajax.nonce,
			order_id: order_id
		};

		$.post( wpwcrin_ajax.url, data, function( response ) {		
			$( a_element ).parent().append( response );
		});	
			
	});
	
	// remove readonly on post when clicked
	$( '.wp_wc_invoice_remove_read_only' ).click( function() {
		$( this ).css( 'visibility', 'hidden' );
		$( '#order_invoice_number' ).removeAttr( 'readonly' ).focus();
		$( '#wp_wc_invoice_number_delete' ).hide();
		$( '.wp_wc_invoice_remove_read_only_sep_delete' ).hide();
	});	

	$( '.wp_wc_invoice_number_delete' ). click( function() {

		var order_id = $( this ).attr( 'data-order-id' );
		var data = {
			action: 'wp_wc_running_invoice_number_delete',
			security: wpwcrin_ajax.nonce,
			order_id: order_id
		};

		$.post( wpwcrin_ajax.url, data, function( response ) {
			
			if ( 'SUCCESS' == response ) {
				$( '#order_invoice_number' ).val( '' );
			}

		});
	});
	
	// when download button is clicked on shop_order page, update invoice number and date
	$ ( '.button.wc-action-button.invoice_pdf' ).click( function() {

		var href = $( this ).attr( 'href' );
		
		var data = {
			action: 'wp_wc_running_invoice_number_show_after_invoice_pdf_clicked',
			security: wpwcrin_ajax.nonce,
			href: href
		};

		$.post( wpwcrin_ajax.url, data, function( response ) {

			var array_id_and_invoice_nr = response.split( '[[SEPARATOR]]' );

			if ( array_id_and_invoice_nr[ 1 ] ) {
				if ( $( '#invoice_number_' + array_id_and_invoice_nr[ 0 ] ).length ) {
					$( '#invoice_number_' + array_id_and_invoice_nr[ 0 ] ).html( array_id_and_invoice_nr[ 1 ] );
				}
			} else {

				$.post( wpwcrin_ajax.url, data, function( response ) {
					var array_id_and_invoice_nr = response.split( '[[SEPARATOR]]' );
					if ( array_id_and_invoice_nr[ 1 ] ) {
						if ( $( '#invoice_number_' + array_id_and_invoice_nr[ 0 ] ).length ) {
							$( '#invoice_number_' + array_id_and_invoice_nr[ 0 ] ).html( array_id_and_invoice_nr[ 1 ] );
						}
					}
				});

			}
		});	
		
	});

	// live example on settings page
    $( '#wp_wc_running_invoice_number_prefix, #wp_wc_running_invoice_number_digits, #wp_wc_running_invoice_number_suffix, #wp_wc_running_invoice_number_next' ).keyup( function() {
		
		// init
		var prefix		= $( '#wp_wc_running_invoice_number_prefix' ).val();
		var digits		= parseInt( $( '#wp_wc_running_invoice_number_digits' ).val() );
		var suffix		= $( '#wp_wc_running_invoice_number_suffix' ).val();
		var example		= parseInt( $( '#wp_wc_running_invoice_number_next' ).val() );
		
		var today = new Date();
		var day = today.getDate();
		day = ( day <= 9 ) ? '0' + day : day;
		var month = today.getMonth() + 1;
		month = ( month <= 9 ) ? '0' + month : month;
		var year = today.getFullYear();
		var year_2 = year.toString().slice( -2 );
		prefix = prefix.replace( '{{year}}', year );
		prefix = prefix.replace( '{{year-2}}', year_2 );
		prefix = prefix.replace( '{{month}}', month );
		prefix = prefix.replace( '{{day}}', day );
		suffix = suffix.replace( '{{year}}', year );
		suffix = suffix.replace( '{{year-2}}', year_2 );
		suffix = suffix.replace( '{{month}}', month );
		suffix = suffix.replace( '{{day}}', day );

		if ( example == 0 ) {
			example = 1;	
		}
		
		// number of digits
		var number_of_digits_example = isNaN( example ) ? 1 : example.toString().length;
		var length_or_running_number = isNaN( digits ) ? 0 : digits;
		var running_number = isNaN( example ) ? '1' : example.toString();
		if ( number_of_digits_example < length_or_running_number ) {
			var diff = length_or_running_number - number_of_digits_example;
			for ( var i = 1; i <= diff; i++ ) {
				running_number = '0' + running_number;	
			}
		}
		
		// validate input
		$( '#wp_wc_running_invoice_number_digits' ).val( ( isNaN( digits ) ? '' : digits ) );
		$( '#wp_wc_running_invoice_number_next' ).val( ( isNaN( example ) ? '' : example ) );
		
		// show example
		$( '#wp_wc_running_invoice_number_example' ).val( prefix + running_number + suffix );
    });

	$( '#wp_wc_running_invoice_number_digits, #wp_wc_running_invoice_number_next' ).change( function() {
		var digits		= parseInt( $( '#wp_wc_running_invoice_number_digits' ).val() );
		var example		= parseInt( $( '#wp_wc_running_invoice_number_next' ).val() );
		
		if ( example == 0 ) {
			$( '#wp_wc_running_invoice_number_next' ).val( 1 );
		}
		
	});

	// live example refund on settings page
    $( '#wp_wc_running_invoice_number_prefix_refund, #wp_wc_running_invoice_number_digits_refund, #wp_wc_running_invoice_number_suffix_refund, #wp_wc_running_invoice_number_next_refund' ).keyup( function() {
		
		// init
		var prefix		= $( '#wp_wc_running_invoice_number_prefix_refund' ).val();
		var digits		= parseInt( $( '#wp_wc_running_invoice_number_digits_refund' ).val() );
		var suffix		= $( '#wp_wc_running_invoice_number_suffix_refund' ).val();
		var example		= parseInt( $( '#wp_wc_running_invoice_number_next_refund' ).val() );

		var today = new Date();
		var day = today.getDate();
		day = ( day <= 9 ) ? '0' + day : day;
		var month = today.getMonth() + 1;
		month = ( month <= 9 ) ? '0' + month : month;
		var year = today.getFullYear();
		var year_2 = year.toString().slice( -2 );
		prefix = prefix.replace( '{{year}}', year );
		prefix = prefix.replace( '{{year-2}}', year_2 );
		prefix = prefix.replace( '{{month}}', month );
		prefix = prefix.replace( '{{day}}', day );
		suffix = suffix.replace( '{{year}}', year );
		suffix = suffix.replace( '{{year-2}}', year_2 );
		suffix = suffix.replace( '{{month}}', month );
		suffix = suffix.replace( '{{day}}', day );
		
		if ( example == 0 ) {
			example = 1;	
		}
		
		// number of digits
		var number_of_digits_example = isNaN( example ) ? 1 : example.toString().length;
		var length_or_running_number = isNaN( digits ) ? 0 : digits;
		var running_number = isNaN( example ) ? '1' : example.toString();
		if ( number_of_digits_example < length_or_running_number ) {
			var diff = length_or_running_number - number_of_digits_example;
			for ( var i = 1; i <= diff; i++ ) {
				running_number = '0' + running_number;	
			}
		}
		
		// validate input
		$( '#wp_wc_running_invoice_number_digits_refund' ).val( ( isNaN( digits ) ? '' : digits ) );
		$( '#wp_wc_running_invoice_number_next_refund' ).val( ( isNaN( example ) ? '' : example ) );
		
		// show example
		$( '#wp_wc_running_invoice_number_example_refund' ).val( prefix + running_number + suffix );
    });

	$( '#wp_wc_running_invoice_number_digits_refund, #wp_wc_running_invoice_number_next_refund' ).change( function() {
		var digits		= parseInt( $( '#wp_wc_running_invoice_number_digits_refund' ).val() );
		var example		= parseInt( $( '#wp_wc_running_invoice_number_next_refund' ).val() );
		
		if ( example == 0 ) {
			$( '#wp_wc_running_invoice_number_next_refund' ).val( 1 );
		}
		
	});

	// update refund number when backend download
	$( '.refund_pdf' ).click( function() {
    	
    	var refund_id = $( this ).attr( 'data-refund-id' );
		var span_id = 'refund-number-' + refund_id;
		var span_content = jQuery( '#' + span_id ).html();
		
		if ( span_content == '' ) {

			// let's wait a short moment
			setTimeout( function(){
	  			
	  			var data = {
					action: 'wp_wc_running_invoice_number_show_refund_number',
					security: wpwcrin_ajax.nonce,
					refund_id: refund_id,
				};

	  			$.post( wpwcrin_ajax.url, data, function( response ) {		
					jQuery( '#' + span_id ).html( response );
	    		});

			}, 750 );

		}

    });

});
