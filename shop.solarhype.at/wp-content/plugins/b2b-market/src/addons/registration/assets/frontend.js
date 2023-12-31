jQuery( function( $ ) {

    $( '#b2b_role' ).on( 'change', function() {

        var group_val  = $( this ).find( ':selected' ).val();
        var group_data = group_val.split( '__' );
        var group      = group_data[ 1 ];

        if ( typeof registration !== 'undefined' ) {
            $( '#b2b_uid_field' ).css({ display: 'block' });
            $( '#b2b_company_registration_number_field').css({ display: 'block' });
            if ( jQuery.inArray( group, registration.net_tax_groups ) != -1 ) {
                $( '#b2b_uid_field label span').html('<abbr class="required" title="erforderlich">*</abbr>');
                $( '#b2b_uid_field' ).css({ display: 'block' });
                $( '#b2b_company_registration_number_field').css({ display: 'block' });
            } else {
                $( '#b2b_uid_field' ).css({ display: 'none' });
                $( '#b2b_company_registration_number_field').css({ display: 'none' });
            }
        }
    }).change();

});
