jQuery( function( $ ) {

	$('.b2b-upgrade-notice button.notice-dismiss').ready( function() {
		$('.b2b-upgrade-notice button.notice-dismiss').on( 'click', function() {
			$.ajax({
				type: 'post',
				dataType: 'json',
				url: upgrader.ajax_url,
				data: { 'action': 'dismiss_upgrade_notice' },
				success: function(response) {
		            $('.b2b-upgrade-notice').fadeOut( 1000 );
				}
			});
		});
	});

	$('#b2b-run-migration').ready( function() {
	    $('#b2b-run-migration').on('click', function(e) {
			$.ajax({
				type: 'post',
				dataType: 'json',
				url: upgrader.ajax_url,
				data: { 'action': 'run_bm_update_migration' },
				beforeSend: function() {
					$('.b2b-upgrade-notice').append('<span class="spinner bm-spinner"><img src="' + upgrader.spinner + '" /></span>');
				},
				success: function(response) {
					if ( response.success ) {
						if( response.message.length > 0 ) {
							$('.b2b-upgrade-notice').find('p').replaceWith('<p>' + response.message + '</p>');
							$('.spinner').remove();
						} else {
							$('.spinner').remove();
							$('.b2b-upgrade-notice').fadeOut(1000);
						}
					}
				}
			  });
	    });
	});
});
