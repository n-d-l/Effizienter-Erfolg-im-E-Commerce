jQuery( document ).ready( function() {

    // copy to clipboard
    jQuery( '.copy-to-clipboard' ).click( function() {

        // show message
        jQuery( this ).parent().find( '.copy-success' ).slideDown();

        // find text to copy
        var text_to_copy = jQuery( this ).parent().parent().find( 'input[type="text"]' ).val();

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
        jQuery( this ).parent().find( '.copy-success' ).delay( 4000 ).slideUp();

    });

    // refresh api token
    jQuery( '.refresh' ).click( function() {

        // show message
        jQuery( this ).parent().find( '.refreshed-success' ).slideDown();

        var random_string = '';
        var allowed_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-=';

        for ( var i = 0; i < 50; i++ ) {
            random_string += allowed_chars.charAt( Math.floor( Math.random() * allowed_chars.length ) );
        }

        jQuery( this ).parent().parent().find( 'input[type="text"]' ).val( random_string );

        // hide message
        jQuery( this ).parent().find( '.refreshed-success' ).delay( 4000 ).slideUp();

    });

} );
