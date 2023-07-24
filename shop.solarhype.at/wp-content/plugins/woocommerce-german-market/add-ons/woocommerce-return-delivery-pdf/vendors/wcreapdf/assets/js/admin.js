jQuery( document ).ready( function( $ ) {

    $( '#woocomerce_wcreapdf_wgm_image_upload_button' ).click( function() {
	   
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

            if ( ! $( '#woocomerce_wcreapdf_wgm_pdf_logo_url' ).length ) {
                $( '#woocomerce_wcreapdf_wgm_pdf_logo_url_delivery' ).val( attachment.url );
            } else {
                $( '#woocomerce_wcreapdf_wgm_pdf_logo_url' ).val( attachment.url );
            }

        });

        // Finally, open the modal on click
        frame.open();
		
    });
	
	$( '#woocomerce_wcreapdf_wgm_image_remove_button' ).click( function() {
		
         if ( ! $( '#woocomerce_wcreapdf_wgm_pdf_logo_url' ).length ) {
            $( '#woocomerce_wcreapdf_wgm_pdf_logo_url_delivery' ).val( '' );
        } else {
            $( '#woocomerce_wcreapdf_wgm_pdf_logo_url' ).val( '' );
        }

    });
	
});
