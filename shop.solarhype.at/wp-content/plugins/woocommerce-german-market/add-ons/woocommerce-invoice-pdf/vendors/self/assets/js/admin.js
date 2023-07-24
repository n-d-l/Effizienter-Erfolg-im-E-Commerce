jQuery( document ).ready( function( $ ) {

    // girocode
    // jpg format does not support colors
    if ( jQuery( '#wp_wc_invoice_pdf_girocode_format' ).length ) {

        jQuery( '#wp_wc_invoice_pdf_girocode_format' ).ready( function(){
            german_market_girocode_format_color();
        });

        jQuery( '#wp_wc_invoice_pdf_girocode_format' ).change( function() {
            german_market_girocode_format_color();
        });
    }

    function german_market_girocode_format_color() {

        var option_value = jQuery( '#wp_wc_invoice_pdf_girocode_format' ).val();

        if ( 'jpg' === option_value ) {
            jQuery( '#wp_wc_invoice_pdf_girocode_dark_color' ).parent().parent().hide();
            jQuery( '#wp_wc_invoice_pdf_girocode_bright_color' ).parent().parent().hide();
        } else {
            jQuery( '#wp_wc_invoice_pdf_girocode_dark_color' ).parent().parent().show();
            jQuery( '#wp_wc_invoice_pdf_girocode_bright_color' ).parent().parent().show();
        }
    }

    if ( jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries_option' ).length ) {

        jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries_option' ).ready( function(){
            german_market_girocode_billing_countries();
        });

        jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries_option' ).change( function() {
            german_market_girocode_billing_countries();
        });
    }

    function german_market_girocode_billing_countries() {

        var option_value = jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries_option' ).val();

        if ( 'all' === option_value ) {
            jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries' ).parent().parent().hide();
        } else {
            jQuery( '#wp_wc_invoice_pdf_girocode_billing_countries' ).parent().parent().show();
        }

    }

    // swiss qr code variants
    if ( jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_variant' ).length ) {

        jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_variant' ).ready( function(){
            german_market_swiss_qr_invoice_variant();
        });

        jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_variant' ).change( function() {
            german_market_swiss_qr_invoice_variant();
        });
    }

    function german_market_swiss_qr_invoice_variant() {

        var option_value = jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_variant' ).val();
        console.log( option_value );

        if ( 'qr' === option_value ) {
            jQuery( '.swiss_qr_invoice_v3' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v2' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v1' ).parent().parent().show();
        } else if ( 'scor' === option_value  ) {
            jQuery( '.swiss_qr_invoice_v1' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v3' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v2' ).parent().parent().show();
        } else if ( 'non' === option_value ) {
            jQuery( '.swiss_qr_invoice_v1' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v2' ).parent().parent().hide();
            jQuery( '.swiss_qr_invoice_v3' ).parent().parent().show();
        }
    }

    if ( jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries_option' ).length ) {

        jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries_option' ).ready( function(){
            german_market_swiss_qr_invoice_billing_countries();
        });

        jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries_option' ).change( function() {
            german_market_swiss_qr_invoice_billing_countries();
        });
    }

    function german_market_swiss_qr_invoice_billing_countries() {

        var option_value = jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries_option' ).val();

        if ( 'all' === option_value ) {
            jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries' ).parent().parent().hide();
        } else {
            jQuery( '#wp_wc_invoice_pdf_swiss_qr_invoice_billing_countries' ).parent().parent().show();
        }

    }

    $( '#wp_wc_invoice_pdf_image_upload_button_footer, #wp_wc_invoice_pdf_image_upload_button_header, #wp_wc_invoice_pdf_image_upload_button_background' ).click( function() {

		var this_id 		= ( $(this).attr( 'id' ) );
		var formfield_id = this_id.replace( 'wp_wc_invoice_pdf_image_upload_button_', 'wp_wc_invoice_pdf_image_url_' );
		var frame;
		
		// If the media frame already exists, reopen it.
        if ( frame ) {
          frame.open();
          return;
        }
        
        // Create a new media frame
        frame = wp.media({
          multiple: false  // Set to true to allow multiple files to be selected
        });

        // When an image is selected in the media frame...
        frame.on( 'select', function() {
          
          // Get media attachment details from the frame state
          var attachment = frame.state().get( 'selection' ).first().toJSON();
          jQuery( '#' + formfield_id ).val( attachment.url );

        });

        // Finally, open the modal on click
        frame.open();
		
    });

    if ( $( '#wp_wc_invoice_pdf_frontend_download_refund_pdf' ).length ) {
        $( '#wp_wc_invoice_pdf_frontend_download_refund_pdf' ).on( 'change', function() {
            let row = $( this ).closest( 'tr' ).next();
            if ( $( this ).is( ':checked' ) ) {
                row.show();
            } else {
                row.hide();
            }
        });
        $( '#wp_wc_invoice_pdf_frontend_download_refund_pdf' ).change();
    }

	$( '#wp_wc_invoice_pdf_image_remove_button_header, #wp_wc_invoice_pdf_image_remove_button_footer, #wp_wc_invoice_pdf_image_remove_button_background' ).click( function() {
		var this_id 		= ( $(this).attr( 'id' ) );
		var formfield_id = this_id.replace( 'wp_wc_invoice_pdf_image_remove_button_', 'wp_wc_invoice_pdf_image_url_' );
		$( '#' + formfield_id ).val( '' );
    });

    $( '.refund_pdf' ).click( function() {
    	var refund_id = $( this ).attr( 'data-refund-id' );
    	var delete_button = $( this ).next( "[data-refund-id='" + refund_id + "']" ).show();
    });

    $( '.invoice_pdf' ).click( function() {
    	if (  ! $( this ).hasClass( 'always_create_new' ) ) {
    		$( this ).next( '.invoice_pdf_delete_content' ).css( 'display', 'inline-block' );
    	}
    });

    $( '.gm-select-all-refunds' ).click( function() {
    	var checked = $( this ).is( ":checked" );
    	$( '.gm-select-refund, .gm-select-all-refunds' ).prop( 'checked', checked ); 
    });

    if ( $( 'select#wp_wc_invoice_pdf_emails_attachment_format' ).length ) {
        let rows = $( '#wp_wc_invoice_pdf_emails_link_position, #wp_wc_invoice_pdf_emails_link_label_text, #wp_wc_invoice_pdf_emails_link_text, #wp_wc_invoice_pdf_emails_link_download_behaviour, #wp_wc_invoice_pdf_emails_refunds_link_label_text, #wp_wc_invoice_pdf_emails_refunds_link_text' ).closest( 'tr' );
        $( 'select#wp_wc_invoice_pdf_emails_attachment_format' ).on( 'change', function() {
            if ( 'link' == $( this ).val() ) {
                $( rows ).show();
            } else {
                $( rows ).hide();
            }
        });
        $( 'select#wp_wc_invoice_pdf_emails_attachment_format' ).change();
    }

});
