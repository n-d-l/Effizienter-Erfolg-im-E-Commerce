/**
 * Feature Name: Frontend Scripts
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com/
 */

/** Menu **/
(
	function( $ ) {
		var wcvat_frontend_scripts = {

			_valid: false,

			// Pseudo-Constructor of this class
			init  : function() {

				// hide VAT by default if it's not set to 'display always' in German Market settings
				if ( 'always_optional' != wcvat_script_vars.display_vat_field && 'always_mandatory' != wcvat_script_vars.display_vat_field ) {
					wcvat_frontend_scripts.hide_vatin_field();
				}

				// ajaxify billing VAT field
				if ( $( '#billing_vat_field' ).length ) {
					wcvat_frontend_scripts.ajax_check_vat_field();
				}

				if ( $( '#billing_country' ).length ) {
					wcvat_frontend_scripts.billing_country_handle();
				}

				if ( $( '#shipping_country' ).length ) {
					wcvat_frontend_scripts.shipping_country_handle();
				}
			},

			shipping_country_handle : function() {

				if ( 'shipping' == wcvat_script_vars.tax_based_on ) {

					$( '#shipping_country' ).ready( function(){
						$( '#shipping_country' ).trigger( 'change' );
					});

					$( document ).on( 'change', '#ship-to-different-address-checkbox', function( e ) {
						if ( $( this ).is(':checked' ) ) {
							$( '#shipping_country' ).trigger('change' );
						} else {
							$( '#billing_country' ).trigger('change' );
						}
					});

					$( document ).on( 'change', '#shipping_country', function( e ) {

						let display_vat_field = wcvat_script_vars.display_vat_field;

						if ( 'eu_optional' == display_vat_field || 'eu_mandatory' == display_vat_field ) {

							if ( wcvat_script_vars.base_country_hide || wcvat_script_vars.base_country_hide == 1 ) {

								if ( ( ( wcvat_script_vars.base_country == $( '#shipping_country' ).val() ) && ( ! wcvat_script_vars.show_for_basecountry_hide_eu_countries ) ) || $( '#shipping_country' ).val() == '' )  {

									$( '#billing_vat' ).val( '' ).trigger( 'blur' );
									$( '#billing_vat_field' ).hide();

								} else if ( ! wcvat_script_vars.eu_countries.includes( $( '#shipping_country' ).val() ) ) {

									$( '#billing_vat' ).val( '' ).trigger( 'blur' );
									$( '#billing_vat_field' ).hide();

								} else {

									$( '#billing_vat_field' ).show();

									if ( 'eu_mandatory' == display_vat_field ) {
										$( '#billing_vat_field .optional' ).hide();
										if ( $( '#billing_vat_field .required' ).length == 0 ) {
											$( '<abbr class="required required-text" title="' + wcvat_script_vars.required_title_text + '">*</abbr>' ).insertAfter( '#billing_vat_field .optional' );
										} else {
											$( '#billing_vat_field .required' ).show();
										}
									}

									// Triggering VAT ID check
									$( '#billing_vat' ).trigger( 'blur' );

								}

							}

						} else
						if ( 'always_optional' == display_vat_field || 'always_mandatory' == display_vat_field ) {

							$( '#billing_vat_field' ).show();

							if ( 'always_mandatory' == display_vat_field ) {
								$( '#billing_vat_field .optional' ).hide();
								if ( $( '#billing_vat_field .required' ).length == 0 ) {
									$( '<abbr class="required required-text" title="' + wcvat_script_vars.required_title_text + '">*</abbr>' ).insertAfter( '#billing_vat_field .optional' );
								} else {
									$( '#billing_vat_field .required' ).show();
								}
							}

							// Triggering VAT ID check
							$( '#billing_vat' ).trigger( 'blur' );

						}

					});

				} else {

					$( '#billing_country' ).trigger( 'change' );

				}

			},

			billing_country_handle : function() {

				$( '#billing_country' ).ready( function() {
					$('#billing_country').trigger('change' );
				});

				$( document ).on( 'change', '#billing_country', function( e ) {

					if ( 'billing' == wcvat_script_vars.tax_based_on ||
						( 'shipping' == wcvat_script_vars.tax_based_on && $( '#ship-to-different-address-checkbox' ).not(':checked' ).length ) ||
						( 'shipping' == wcvat_script_vars.tax_based_on && $( '#ship-to-different-address-checkbox' ).length == 0 )
					) {

						let display_vat_field = wcvat_script_vars.display_vat_field;

						if ('eu_optional' == display_vat_field || 'eu_mandatory' == display_vat_field) {

							if (wcvat_script_vars.base_country_hide || wcvat_script_vars.base_country_hide == 1) {

								if (((wcvat_script_vars.base_country == $('#billing_country').val()) && (!wcvat_script_vars.show_for_basecountry_hide_eu_countries)) || $('#billing_country').val() == '') {

									$('#billing_vat').val('').trigger('blur');
									$('#billing_vat_field').hide();

								} else if (!wcvat_script_vars.eu_countries.includes($('#billing_country').val())) {

									$('#billing_vat').val('').trigger('blur');
									$('#billing_vat_field').hide();

								} else {

									$('#billing_vat_field').show();

									if ('eu_mandatory' == display_vat_field) {
										$('#billing_vat_field .optional').hide();
										if ($('#billing_vat_field .required').length == 0) {
											$('<abbr class="required required-text" title="' + wcvat_script_vars.required_title_text + '">*</abbr>').insertAfter('#billing_vat_field .optional');
										} else {
											$('#billing_vat_field .required').show();
										}
									}

									// Triggering VAT ID check
									$('#billing_vat').trigger('blur');

								}

							}

						} else if ('always_optional' == display_vat_field || 'always_mandatory' == display_vat_field) {

							$('#billing_vat_field').show();

							if ('always_mandatory' == display_vat_field) {
								$('#billing_vat_field .optional').hide();
								if ($('#billing_vat_field .required').length == 0) {
									$('<abbr class="required required-text" title="' + wcvat_script_vars.required_title_text + '">*</abbr>').insertAfter('#billing_vat_field .optional');
								} else {
									$('#billing_vat_field .required').show();
								}
							}

							// Triggering VAT ID check
							$('#billing_vat').trigger('blur');

						}

					}

				});

			},

			hide_vatin_field           : function() {
				// $( '#billing_vat_field' ).hide();
			},

			// AJAX check for the VAT-Field
			ajax_check_vat_field       : function() {

				var lock = false;

				$( 'input#billing_vat' ).ready( function(){

					if ( '' != $( 'input#billing_vat' ).val() ) {
						$( 'input#billing_vat' ).trigger( 'blur' );
					}

				});

				$( document ).on( 'blur', 'input#billing_vat', function( e ) {

					let display_vat_field = wcvat_script_vars.display_vat_field;

					if ( true === lock ) {
						return false;
					}

					if ( '' == $( this ).val() ) {
						wcvat_frontend_scripts.clean_up_badges();
						$( 'body' ).trigger( 'update_checkout' );
						return false;
					}

					lock = true;

					if ( jQuery( '#place_order' ).length ) {
						jQuery( '#place_order' ).prop( 'disabled' ,true );
					}

					// set vat
					let vat       = $( this ).val();
					let vat_field = $( this );
					let country   = '';
					let required  = false;

					if ( 'shipping' == wcvat_script_vars.tax_based_on ) {
						if ( $( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
							country = $( '#shipping_country' ).val();
						} else {
							country = $( '#billing_country' ).val();
						}
					} else {
						country = $( '#billing_country' ).val();
					}

					// Do not validate VAT if country isnt an EU country
					if ( ! wcvat_script_vars.eu_countries.includes( country ) ) {
						lock = false;
						wcvat_frontend_scripts.clean_up_badges();
						if ( jQuery( '#place_order' ).length ) {
							jQuery( '#place_order' ).prop( 'disabled' ,false );
						}
						return false;
					}

					if ( 'always_mandatory' == display_vat_field ) {
						required = true;
					} else
					if ( 'eu_mandatory' == display_vat_field ) {
						// required if country is an EU country and not the shop basis country
						if ( wcvat_script_vars.base_country != country && wcvat_script_vars.eu_countries.includes( country ) ) {
							required = true;
						}
					}

					wcvat_frontend_scripts.clean_up_badges();
					vat_field.after( wcvat_script_vars.spinner );

					// set the post vars
					var post_vars = {
						action       : 'wcvat_check_vat',
						vat          : vat,
						country      : country,
						required     : required,
						tax_based_on : wcvat_script_vars.tax_based_on,
					};

					$.ajax( {
						data     : post_vars,
						url      : wcvat_script_vars.ajaxurl,
						async    : true,
						dataType : 'json'
					} )

						.always( function() {
							//clean up
							wcvat_frontend_scripts.clean_up_badges();
						} )

						.done( function( response ) {

							if ( response ) {
								if ( false === response.success ) {

									if ( '' != vat ) {
										vat_field.addClass( 'error' );
										vat_field.after( wcvat_script_vars.error_badge );
									}

								} else {
									$( '.error-badge' ).remove();
									vat_field.removeClass( 'error' );
									vat_field.after( wcvat_script_vars.correct_badge );
									wcvat_frontend_scripts._valid = true;
								}
							}
						} )

						.always( function() {
							lock = false;

							if ( wcvat_script_vars.trigger_update_checkout ) {
								$( 'body' ).trigger( 'update_checkout' );
							}

							if ( jQuery( '#place_order' ).length ) {
								jQuery( '#place_order' ).prop( 'disabled' ,false );
							}

						} );
				} );
			},

			clean_up_badges: function() {
				$( '.error-badge' ).remove();
				$( '.spinner-badge' ).remove();
				$( '.correct-badge' ).remove();
				$( '.spinner-badge' ).remove();
			}
		};

		$( document ).ready( wcvat_frontend_scripts.init );
	}
)( jQuery );
