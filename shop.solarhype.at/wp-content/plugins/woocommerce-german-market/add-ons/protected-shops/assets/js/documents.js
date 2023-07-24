jQuery( document ).ready( function() {
    
    // copy to clipboard
    jQuery( '.copy-button' ).click( function() {

        // show message
        jQuery( this ).parent().find( '.copied-success' ).slideDown();

        // find text to copy
        if ( jQuery( this ).hasClass( 'copy-css-button' ) ) {
            
            var text_to_copy = jQuery( this ).parent().find( '.css-preview' ).html();

        } else if ( jQuery( this ).hasClass( 'copy-html-button' ) ) {

            var text_to_copy = jQuery( this ).parent().find( '.text-or-html-preview' ).html();
       
        } else {

             var text_to_copy = jQuery( this ).parent().find( '.gm-protected-shops-preview-text' ).html();

        }

        // copy text to clipboard
        if ( navigator.clipboard && window.isSecureContext ) {
                    
            const type = "text/plain";
            const blob = new Blob([text_to_copy], { type });
            const data = [new ClipboardItem({ [type]: blob })];
            navigator.clipboard.write( data );

        } else {

            var $temp = jQuery( '<textarea>' );
            jQuery( "body" ).append( $temp );
            $temp.val( text_to_copy ).select();
            document.execCommand( "copy" );
            $temp.remove();

        }

        // hide message
        jQuery( this ).parent().find( '.copied-success' ).delay( 4000 ).slideUp();

    });

    // hide save buttons if there's an api error
    if ( ! jQuery( '.copy-button ' ).length ) {
        jQuery( '.save-wgm-options' ).hide();
    }

    // save page
    jQuery( '.gm-ps-save-to-page' ).click( function() {

        var page_id =  jQuery( this ).parent().parent().parent().parent().find( '.page-assignment' ).val();
        
        var $success_message_copy       = jQuery( this ).parent().find( '.copy-page-success' );
        var $success_message_pdf_docx   = jQuery( this ).parent().find( '.copy-pdf-docx-success' );

        $success_message_copy.hide();
        $success_message_pdf_docx.hide();

        jQuery( this ).parent().find( '.copied-page-error' ).hide();

        // get texts
        if ( jQuery( this ).hasClass( 'just-text' ) ) {

            var html = jQuery( this ).parent().parent().parent().parent().find( '.gm-protected-shops-preview-text' ).html();
            var css  = '';

        } else {

            var html = jQuery( this ).parent().parent().parent().parent().find( '.text-or-html-preview' ).html();
            var css  = jQuery( this ).parent().parent().parent().parent().find( '.css-preview' ).html();  
            
        }

        var option_name = jQuery( this ).parent().parent().parent().parent().find( '.page-assignment' ).attr( 'id' );

        var type = jQuery( this ).parent().parent().parent().parent().find( '.document-type' ).html();
        
        // do ajax and save
        var data = {
            'action': 'gm_ps_save_page',
            'page_id' : page_id,
            'html_content': html,
            'css_content': css,
            'nonce': gm_ps_ajax.nonce,
            'option_name': option_name,
            'type' : type
        };

        // loader
        var $loader = jQuery( this ).parent().find( '.gm-ps-background-icon' );
        
        $loader.fadeIn();

        jQuery.post( gm_ps_ajax.url, data, function( response ) {
            
            var success = response.indexOf( 'success_' ) !== -1;
            
            if ( success ) {

                $loader.hide();

                if ( page_id > 0 ) {
                    $success_message_copy.slideDown();
                }

                $success_message_pdf_docx.slideDown();

                var last_update_string = response.replace( 'success_', '' );

                jQuery( '.last-update-string.' + option_name ).html( last_update_string );

            } else {

                alert( response );
            }

        });

    });

} );
