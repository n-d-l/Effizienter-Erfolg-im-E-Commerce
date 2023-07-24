jQuery(document).ready(function( $ ) {

    $('.bm-admin-bar-current-group').css('background', '#2ECC71');

    /* dynamic admin bar customer group selector */
    $( '#wp-admin-bar-customer-groups-default li a' ).each( function( index ) {
        $( this ).on( 'click', function() {
            let customer_group = $( this ).attr( 'href' ).substr( 1 );

            /* use ajax to trigger add_role with PHP */
            $.ajax({
                type: 'POST',
                url: ajax.ajax_url,
                data: {'action' : 'assign_customer_group', 'group': customer_group },
                dataType: 'json',
                success: function(data) {
                    location.reload();                 
                }
              });  
        });
    });
});
  
