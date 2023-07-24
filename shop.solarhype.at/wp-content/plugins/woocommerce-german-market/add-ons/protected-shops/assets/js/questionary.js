jQuery( document ).ready( function() {
    
    var build_url			= jQuery( '#gm-ps-build-url' ).val();
    var save_url 			= jQuery( '#gm-ps-save-url' ).val();
    var tamplate_path		= jQuery( '#gm-ps-template-path' ).val();
    var translation_path	= jQuery( '#gm-ps-translation-path' ).val();

    var options = {
        container: "#main-questionary",
        buildUrl: build_url, 
        saveUrl: save_url,
        templatePath: tamplate_path,
         beforeReload: function() {
                jQuery('#gm-ps-background-loader').show();
                jQuery('#gm-ps-background-icon').fadeIn( 'fast' );
        },
        afterReload: function() {
            jQuery('#gm-ps-background-icon').fadeOut( 'fast' );
            jQuery('#gm-ps-background-loader').hide();
        }
    };

    ps.questionary( options );

} );
