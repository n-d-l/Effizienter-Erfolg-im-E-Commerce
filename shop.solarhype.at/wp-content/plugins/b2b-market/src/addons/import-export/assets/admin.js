jQuery( function( $ ) {

    /* modal helper */
    function modal_close() {
        $( '.modal' ).fadeOut( 1000 );
    }
    function modal_destroy() {
        $( '.modal' ).trigger( 'closeModal' );
    }

    /* copy export */
    $( '#b2b_market_export_output' ).click( function( e ) {
        $( this ).select();
        document.execCommand('copy' );
    });

    function handleCheckboxes( what = 'export' ) {
        let button = $( '#b2b_market_' + what + '_button_wrapper input[type=submit]' );
        if ( 'import' === what && $( '#b2b_market_import_groups input[type=submit]' ).length ) {
            button = $( '#b2b_market_import_groups input[type=submit]' );
        }
        let button_select_all   = $( '#b2b_market_select_groups_all' );
        let checkboxes          = $( '#b2b_market_' + what + '_wrapper #b2b_market_' + what + '_groups input[type=checkbox]' ).not( '#b2b_market_select_groups_all' );
        let checkboxes_total    = $( checkboxes ).length;
        let checkboxes_selected = $( '#b2b_market_' + what + '_wrapper #b2b_market_' + what + '_groups input[type=checkbox]:checked' ).not( '#b2b_market_select_groups_all' ).length;
        if ( checkboxes_total == checkboxes_selected ) {
            $( button_select_all ).prop( 'checked', true );
        }
        $( checkboxes ).on( 'change', function() {
            checkboxes_selected = ( false === $( this ).prop( 'checked' ) ) ? checkboxes_selected - 1 : checkboxes_selected + 1;
            if ( checkboxes_selected != checkboxes_total ) {
                if ( $( this ).attr( 'id' ) != 'b2b_market_export_plugin_settings' && $( this ).attr( 'id' ) != 'b2b_market_export_save_file' ) {
                    $( button_select_all ).prop( 'checked', false );
                }
            } else {
                $( button_select_all ).prop( 'checked', true );
            }
            if ( checkboxes_selected > 0 ) {
                if ( $( button ).hasClass( 'disabled' ) ) {
                    $( button ).removeClass( 'disabled' );
                }
            } else {
                if ( ! $( button ).hasClass( 'disabled' ) ) {
                    $( button ).addClass( 'disabled' );
                }
            }
        });
        $( button_select_all ).on( 'change', function() {
            if ( true === $( this ).prop( 'checked' ) ) {
                $( checkboxes ).not( 'input#b2b_market_export_plugin_settings,input#b2b_market_export_save_file' ).prop( 'checked', true );
                checkboxes_selected = checkboxes_total;
                if ( $( button ).hasClass( 'disabled' ) ) {
                    $( button ).removeClass( 'disabled' );
                }
            } else {
                $( checkboxes ).not( 'input#b2b_market_export_plugin_settings,input#b2b_market_export_save_file' ).prop( 'checked', false );
                checkboxes_selected = $( '#b2b_market_' + what + '_wrapper #b2b_market_' + what + '_groups input[type=checkbox]:checked' ).length;
                if ( 0 == checkboxes_selected && ! $( button ).hasClass( 'disabled' ) ) {
                    $( button ).addClass( 'disabled' );
                }
            }
        });
    }

    function handleJsonData( data ) {
        let plugin_settings = false;
        $( '#b2b_market_import_groups_options > div' )
        .empty()
         .append( '<label htmlFor="b2b_market_select_groups_all"><input type="checkbox" id="b2b_market_select_groups_all" name="b2b_market_select_groups_all" value="on"/> ' + exporter.l18n_select_all + '</label>' );
        $.each( data, function( index, item ) {
            if ( 'options' == index ) {
                // Plugin settings found.
                plugin_settings = true;
            } else {
                // Group settings found.
                $( '#b2b_market_import_groups_options > div' ).append( '<label for="b2b_market_import_group_' + item.slug + '"><input type="checkbox" id="b2b_market_import_group_' + item.slug + '" name="b2b_market_import_group_' + item.slug + '" value="' + item.slug + '" /> ' + exporter.l18n_group + ' "' + item.title + '"</label>' );
            }
        });
        if ( true === plugin_settings ) {
            // Adding a checkbox for plugin settings.
            $( '#b2b_market_import_groups_options > div' ).append( '<label for="b2b_market_import_settings"><input type="checkbox" id="b2b_market_import_settings" name="b2b_market_import_settings" value="settings" /> ' + exporter.l18n_options + '</label>' );
        }
        $( '#b2b_market_import_groups_options > div' ).append( '<input type="submit" name="import_button" class="save-bm-options button disabled" value="' + exporter.l18n_import_button_label + '">' );
    }

    function handleImportButton() {
        if ( $( '#b2b_market_import_groups input[type=submit]' ).length ) {
            let button = $( '#b2b_market_import_groups input[type=submit]' );
            $( button ).on( 'click', function( e ){
                e.preventDefault();
                let groups              = [];
                let checkboxes          = $( '#b2b_market_import_groups input[type=checkbox]' ).not( '#b2b_market_select_groups_all' );
                let checkboxes_selected = $( '#b2b_market_import_groups input[type=checkbox]:checked' ).not( '#b2b_market_select_groups_all' ).length;
                if ( 0 == checkboxes_selected ) {
                    // Nothing selected.
                    return;
                }
                $.each( checkboxes, function(){
                    if ( 'settings' != $( this ).val() && true == $( this ).is( ':checked' ) ) {
                        groups.push( $( this ).val() );
                    }
                });
                $.ajax({
                    url:      exporter.ajaxurl,
                    dataType: 'json',
                    method:   'POST',
                    data: {
                        action:          'trigger_import',
                        security:        exporter.nonce,
                        import_groups:   groups,
                        import_settings: $( '#b2b_market_import_settings' ).is( ':checked' ) ? 'on' : 'off',
                        import_raw_data: $( '#b2b_market_import_raw_data' ).val(),
                    },
                }).done( function ( data ){
                    console.log( data );
                    if ( 'success' == data.status ) {
                        $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                            .empty()
                            .append( '<div class="ajax-response success">' + data.message + '</div>' );
                        $( '#b2b_market_import_groups_options .ajax-response' ).show();
                    } else {
                    }
                });
            });
        }
    }

    /**
     * Check if input contains B2B Market datas.
     *
     * @param  data JSON decoded object
     * @return bool
     */
    function checkB2BMarketData( data ) {
        let valid = false;
        if ( 'object' == typeof data ) {
            $.each( data, function( index, item ) {
                if ( 'options' == index ) {
                    valid = true;
                } else {
                    if ( item.slug != undefined ) {
                        valid = true;
                    }
                }
            });
        }
        return valid;
    }

    /**
     * Handle the Import.
     */
    if ( $( '#b2b_market_import_wrapper' ).length ) {
        $( '#b2b_market_import_raw_data' ).on( 'change, keyup', function(){
            if ( '' != $( this ).val() ) {
                if ( $( '#b2b_market_import_button_wrapper .button' ).hasClass( 'disabled' ) ) {
                    $( '#b2b_market_import_button_wrapper .button' ).removeClass( 'disabled' );
                }
                $( '#b2b_market_import_groups_options .form-fields-wrapper' ).empty();
                $( '#b2b_market_import_groups_options .form-fields-wrapper' ).append( exporter.l18n_upload_first );
            } else {
                if ( ! $( '#b2b_market_import_file' ).prop( 'files' ) ) {
                    if ( ! $( '#b2b_market_import_button_wrapper .button' ).hasClass( 'disabled' ) ) {
                        $( '#b2b_market_import_button_wrapper .button' ).addClass( 'disabled' );
                    }
                }
            }
        });
        $( '#b2b_market_import_file' ).on( 'change', function(){
            if ( $( '#b2b_market_import_file' ).prop( 'files' ) ) {
                if ( $( '#b2b_market_import_button_wrapper .button' ).hasClass( 'disabled' ) ) {
                    $( '#b2b_market_import_button_wrapper .button' ).removeClass( 'disabled' );
                }
            }
            $( '#b2b_market_import_raw_data' ).val( '' );
            $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                .empty()
                .append( exporter.l18n_upload_first );
        });
        $( '#b2b_market_import_button_wrapper .button' ).on( 'click', function( e ){
            e.preventDefault();
            if ( $( this ).hasClass( 'disabled' ) ) {
                return;
            }
            let data        = false;
            let raw_data    = $( '#b2b_market_import_raw_data' ).val();
            let import_file = $( '#b2b_market_import_file' ).prop( 'files' );
            if ( '' == raw_data && ! import_file ) {
                // no import data.
                return;
            }
            if ( '' != raw_data ) {
                try {
                    data = $.parseJSON( raw_data );
                }
                catch( e ) {
                    $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                        .empty()
                        .append( '<div class="ajax-response failure">' + exporter.l18n_json_error + '</div>' );
                    $( '#b2b_market_import_groups_options .ajax-response' ).show();
                    // invalid json data.
                    return;
                };
                if ( true === checkB2BMarketData( data ) ) {
                    handleJsonData( data );
                    handleCheckboxes( 'import' );
                    handleImportButton();
                } else {
                    $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                        .empty()
                        .append( '<div class="ajax-response failure">' + exporter.l18n_json_error + '</div>' );
                    $( '#b2b_market_import_groups_options .ajax-response' ).show();
                    // invalid json data.
                    return;
                }
            } else
            if ( import_file ) {
                let data       = false;
                let fileReader = new FileReader();
                fileReader.onload = function () {
                    data = fileReader.result.split( ',' )[ 1 ];  // data <-- in this var we have the file data in Base64 format
                    let raw_data = atob( data ); // decode base64 string
                    try {
                        data = $.parseJSON( raw_data );
                    }
                    catch( e ) {
                        $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                            .empty()
                            .append( '<span class="error">' + exporter.l18n_json_error + '</span>' );
                        // invalid json data.
                        return;
                    };
                    $( '#b2b_market_import_raw_data' ).val( raw_data );
                    if ( true === checkB2BMarketData( data ) ) {
                        handleJsonData( data );
                        handleCheckboxes( 'import' );
                        handleImportButton();
                    } else {
                        $( '#b2b_market_import_groups_options .form-fields-wrapper' )
                            .empty()
                            .append( '<div class="ajax-response failure">' + exporter.l18n_json_error + '</div>' );
                        $( '#b2b_market_import_groups_options .ajax-response' ).show();
                        // invalid json data.
                        return;
                    }
                };
                fileReader.readAsDataURL( $( '#b2b_market_import_file' ).prop( 'files' )[ 0 ] );
            }
        });
    }

    /**
     * Handle the Export.
     */
    if ( $( '#b2b_market_export_wrapper' ).length ) {
        handleCheckboxes( 'export' );
        $( '#b2b_market_export_button_wrapper input.button' ).on( 'click', function( e ){
            e.preventDefault();
            if ( $( this ).hasClass( 'disabled' ) ) {
                return;
            }
            let groups = [];
            $.each( $( 'input[name="b2b_market_export_groups[]"]:checked' ), function(){
                groups.push( $( this ).val() );
            });
            $.ajax({
                url:      exporter.ajaxurl,
                dataType: 'json',
                method:   'POST',
                data: {
                    action:            'trigger_export',
                    security:          exporter.nonce,
                    export_all_groups: $( '#b2b_market_select_groups_all' ).is( ':checked' ) ? 'on' : 'off',
                    export_groups:     groups,
                    export_settings:   $( '#b2b_market_export_plugin_settings' ).is( ':checked' ) ? 'on' : 'off',
                    export_to_file:    $( '#b2b_market_export_save_file' ).is( ':checked' ) ? 'on' : 'off',
                },
            }).done( function ( data ){

                $('.modal').easyModal({
                    top: 300,
                    autoOpen: true,
                    overlayOpacity: 0.2,
                    overlayClose: true,
                    closeOnEscape: true,
                });

                setTimeout( modal_close, 1000 );
                setTimeout( modal_destroy, 2000 );

                if ( 'success' === data.status ) {
                    $( '#b2b_market_export_output' ).val( data.raw_data );
                    if ( false !== data.filename ) {
                        let filename = data.filename;
                        let blob     = new Blob([ data.raw_data ], { type: "application/octetstream" } );
                        let isIE     = false || !!document.documentMode;
                        if ( isIE ) {
                            window.navigator.msSaveBlob( blob, filename );
                        } else {
                            var url = window.URL || window.webkitURL;
                            link = url.createObjectURL( blob );
                            var a = $("<a />");
                            a.attr( 'download', filename );
                            a.attr( 'href', link );
                            $( 'body' ).append( a );
                            a[ 0 ].click();
                            $( 'body' ).remove( a );
                        }
                    }
                } else {

                }
            });
        });
    }

});
