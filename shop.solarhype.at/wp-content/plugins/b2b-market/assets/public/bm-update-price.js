jQuery( function( $ ) {

	/**
	 * get qty element.
	 *
	 * @type {string}
	 */
	var qty_element = '.qty';

	/**
	 * Last active bulk prices.
	 */
	var last_bulk_prices = false;

	/**
	 * Initial quantity.
	 */
	var initial_quantity = false;

	/**
	 * WPC Composite plugin & WPClever Produkt B Bundle.
	 */
	if ( $( '.wooco_component_product_qty_input' ).length || $( '.woosb-bundled' ).length ) {
		var qty_element    = 'form.cart .qty';
	}

	/**
	 * Theme compatibilites.
	 * Avada theme
	 */
	if ( $( '.custom-qty' ).length > 0 ) {
		qty_element = '.custom-qty';
	}

	/**
	 * Enfold theme
	 */
	if ( $( '.custom-qty' ).length > 0 ) {
		qty_element = '.custom-qty';
	}

	/**
	 * Erado theme
	 */
	if ( $( '.tc' ).length > 0 ) {
		qty_element = '.tc';
	}

	/**
	 * Handle totals for variable products.
	 */
	if ( $( '.single_variation_wrap' ).length ) {

		$( '.single_variation_wrap' ).on( 'show_variation', function( event, variation ) {
			// Germaized check.
			if ( ( 'undefined' !== typeof wc_gzd_add_to_cart_variation_params ) && $( '.woocommerce-variation-price' ).length ) {
				$( '.variations_form .woocommerce-variation-price' ).hide();
			}
			$( qty_element ).change();
		} );

		if ( $( '.bm-price-totals' ).length ) {
			var mut = new MutationObserver( function( mutations ) {
				mutations.forEach( function( mutationRecord ) {
					if ( mutationRecord.target.classList.contains( 'disabled' ) ) {
						$( '.bm-price-totals' ).hide();
					} else {
						$( '.bm-price-totals' ).show();
					}
				} );
			} );

			var target = $( '.single_variation_wrap button[type=submit]' )[ 0 ];

			mut.observe( target, {
				'attributes': true,
			} );
		}
	}

	/**
	 * Updating the price markup.
	 *
	 * @param price_data price array
	 * @param exclude    exclude css class
	 *
	 * @return void
	 */
	$.fn.updatePrice = function( price_data, exclude = '' ) {
		/**
		 * Check for sale price.
		 */
		if ( $( this ).find( '.price > ins' ).length ) {
			/**
			 * Germanized check.
			 */
			if ( exclude != '' ) {
				$( this ).find( '.price:not(' + exclude + ') ins > .amount' ).replaceWith( price_data[ 'price' ] );
			} else {
				$( this ).find( '.price > ins > .amount' ).replaceWith( price_data[ 'price' ] );
			}
		} else {
			/**
			 * Germanized check.
			 */
			if ( exclude != '' ) {
				if ( $( '.woocommerce-variation' ).length ) {
					if ( $( this ).find( '.woocommerce-variation .price:not(' + exclude + ')' ).length ) {
						$( this ).find( '.woocommerce-variation .price:not(' + exclude + ') > .amount' ).replaceWith( price_data[ 'price' ] );
					} else {
						$( this ).find( '.price:not(' + exclude + ')' ).empty().html( price_data[ 'price' ] );
					}
				} else {
					$( this ).find( '.price:not(' + exclude + ') > .amount' ).replaceWith( price_data[ 'price' ] );
				}
			} else {
				$( this ).find( '.price > .amount' ).replaceWith( price_data[ 'price' ] );
			}
		}
		/**
		 * Update ppu.
		 */
		if ( $( this ).find( '.price-per-unit .amount' ).length && '' != price_data[ 'ppu' ] ) {
			/**
			 * German Market Check.
			 */
			if ( ! $( this ).find( '.price' ).hasClass( 'wc-gzd-additional-info' ) ) {
				if ( $( '.woocommerce-variation-price' ).length ) {
					$( this ).find( '.woocommerce-variation-price .price-per-unit .amount' ).replaceWith( price_data[ 'ppu' ] );
				} else {
					$( this ).find( '.price-per-unit .amount' ).replaceWith( price_data[ 'ppu' ] );
				}
			}
		}
	};

	/**
	 * B2B Bulk Price table
	 */
	let bulk_price_table_selector = '.' + bm_update_price.bulk_price_table_class;
	let bulk_price_table_pick_min_max_qty = bm_update_price.bulk_price_table_pick_min_max_qty;

	if ( 'min' != bulk_price_table_pick_min_max_qty && 'max' != bulk_price_table_pick_min_max_qty ) {
		bulk_price_table_pick_min_max_qty = 'min';
	}

	let bulk_table_rows = null;
	let background_color = bm_update_price.bulk_price_table_bg_color;
	let font_color = bm_update_price.bulk_price_table_font_color;
	let quantity_field = null;

	/**
	 * Updating the colors in bulk price table.
	 *
	 * @return void
	 */
	function updateBulkPriceTable() {
		let quantity = parseInt( $( quantity_field ).val() );
		/**
		 * Walking through table rows.
		 */
		$.each( $( bulk_table_rows ), function( key, row ) {
			let bulk_quantity_from = parseInt( $( this ).find( 'td' ).eq( 1 ).text() );
			let bulk_quantity_to = parseInt( $( this ).find( 'td' ).eq( 2 ).text() );
			if ( bulk_quantity_from <= quantity && (
				bulk_quantity_to >= quantity || isNaN( bulk_quantity_to )
			) ) {
				if ( bulk_quantity_from <= quantity && isNaN( bulk_quantity_to ) ) {
					if ( $( this ).next().find( 'td' ).length ) {
						bulk_quantity_to = $( this ).next().find( 'td' ).eq( 1 ).text();
						if ( bulk_quantity_from <= quantity && bulk_quantity_to > quantity ) {
							$( row ).css( {
								'background-color': background_color,
								'color': font_color,
							} );
						} else {
							$( row ).css( {
								'background-color': '',
								'color': '',
							} );
						}
					} else {
						$( row ).css( {
							'background-color': background_color,
							'color': font_color,
						} );
					}
				} else {
					$( row ).css( {
						'background-color': background_color,
						'color': font_color,
					} );
				}
			} else {
				/**
				 * Reset colors.
				 */
				$( row ).css( {
					'background-color': '',
					'color': '',
				} );
			}
		} );
	}

	/**
	 * Initalize all dynamic variables and set 'click' event.
	 *
	 * @return void
	 */
	function handleBulkPriceTable() {
		if ( $( bulk_price_table_selector ).length ) {
			bulk_table_rows = $( '.bm-bulk-table tbody > tr' );
			quantity_field = $( 'form.cart input.qty' );
			initial_quantity = $( quantity_field ).val();

			$( bulk_table_rows ).css( {
				cursor: 'pointer',
			} );

			$( bulk_table_rows ).on( 'click', function() {
				let bulk_quantity_from = parseInt( $( this ).find( 'td' ).eq( 1 ).text() );
				let bulk_quantity_to   = parseInt( $( this ).find( 'td' ).eq( 2 ).text() );
				if ( bulk_quantity_from < initial_quantity ) {
					bulk_quantity_from = initial_quantity;
				}
				if ( last_bulk_prices !== $( this ).index() ) {
					/**
					 *  Reset colors.
					 */
					$( bulk_table_rows ).css( {
						'background-color': '',
						'color': '',
					} );
					$( this ).css( {
						'background-color': background_color,
						'color': font_color,
					} );
					$( quantity_field ).val( ( isNaN( bulk_quantity_to ) || 'min' == bulk_price_table_pick_min_max_qty ) ? bulk_quantity_from : bulk_quantity_to );
					last_bulk_prices = $( this ).index();
				} else {
					/**
					 *  Reset colors and quantity.
					 */
					$( bulk_table_rows ).css( {
						'background-color': '',
						'color': '',
					} );
					$( quantity_field ).val( initial_quantity );
					last_bulk_prices = false;
				}
				$( quantity_field ).change();
			} );

			updateBulkPriceTable();
		}
	}

	handleBulkPriceTable();

	/**
	 * Observer for Variations with bulk prices
	 */
	function handleVariationSelect() {
		if ( $( '.single_variation_wrap' ).length ) {
			if ( $( '.single-product' ).length ) {
				var product = $( '.single-product' );
			} else
			if ( $( '.woocommerce.product-content .product' ).length ) {
				var product = $( '.woocommerce.product-content .product' );
			}
			let price = $( '.legacy-itemprop-offers' );
			product.on( 'found_variation', '.variations_form', function() {
				if ( $( '.woocommerce-variation-bulk-prices' ).length ) {
					handleBulkPriceTable();
				}
			} );
		}
	}

	handleVariationSelect();

	/**
	 * Observer for Atomion Quickview,
	 */
	if ( $( '#atomion-quick-view-modal' ).length ) {
		var target = $( '#atomion-quick-view-modal' )[ 0 ];
		let triggered = false; // to prevent multiple js events on same elements

		var observer = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				if ( 'block' == $( target ).css( 'display' ) ) {
					var newNodes = mutation.addedNodes;
					if ( newNodes !== null ) {
						if ( $( 'form.cart input.qty' ).length && ! triggered ) {
							handleBulkPriceTable();
							handleVariationSelect();
							watchQtyInput();
							triggered = true;
							$( 'form.cart input.qty' ).change();
						}
					}
				} else {
					triggered = false;
				}
			} );
		} );

		var config = {
			attributes: true,
			childList: true,
			characterData: true,
		};

		observer.observe( target, config );
	}

	/**
	 * Detecting quantity change and trigger price update.
	 *
	 * @return void
	 */
	function watchQtyInput() {
		/**
		 * Mmodify prices on quantity change.
		 */
		$( qty_element ).on( 'change', function() {
			var id = $( '#current_id' ).data( 'id' );
			var qty = $( this ).val();

			/**
			 * check if variable product
			 */
			if ( $( '.variation_id' ).length > 0 ) {
				id = $( '.variation_id' ).val();
			}

			/**
			 * Check if grouped product.
			 */
			if ( 'woocommerce-grouped-product-list-item__quantity' != $( qty_element ).parent().parent().attr( 'class' ) ) {
				/**
				 * Get updated price with ajax.
				 */
				$.ajax( {
					type: 'POST',
					url: bm_update_price.ajax_url,
					data: {
						'action': 'update_price',
						'id': id,
						'qty': qty,
						'nonce': bm_update_price.nonce,
					},
					dataType: 'json',
					success: function( data ) {
						if ( 0 != data ) {
							if ( data[ 'price_value' ] > 0 ) {

								/**
								 * Trigger update Bulk Price Table row.
								 */
								if ( $( bulk_price_table_selector ).length ) {
									updateBulkPriceTable();
								}

								/**
								 * TM Extra Product Options
								 */
								if ( ( 'undefined' !== typeof $.epoAPI ) && ( $.isFunction( $.epoAPI.addFilter ) ) ) {
									// Modify price.
									$.epoAPI.addFilter( 'tc_adjust_product_total_price_without_options', function( product_total_price ) {
										return data[ 'price_value' ] * $( qty_element ).val();
									}, 10, 1 );
									// Trigger TM Product Options update.
									$( window ).trigger( 'tm-do-epo-update' );
								};

								/**
								 * Woocommerce Custom Product Addons
								 */
								if ( $( '.wcpa_price_summary' ).length ) {
									if ( $( '.single_variation_wrap' ).length ) {
										$( '.single_variation_wrap .woocommerce-variation-price .price > .amount' ).replaceWith( data[ 'price' ] );
									}
									$( '.wcpa_price_summary .wcpa_product_total .wcpa_price' ).empty().html( data[ 'price' ] );
									let b2b_price    = data[ 'price_value' ];
									let option_total = parseFloat( $( '.wcpa_price_summary .wcpa_options_total .price_value' ).text().replace( ',', '.' ) );
									let price_total  = ( b2b_price + option_total ).toFixed( 2 );
									$( '.wcpa_price_summary .wcpa_total .price_value' ).html( price_total.toString().replace( '.', ',' ) );
									if ( $( '.bm-price-totals' ).lnegth ) {
										var replaced = $( '.bm-price-totals bdi' ).html().replace( /([0-9]+[,|.][0-9]+)/g, price_total ).replace( '.', ',' );
										$( '.bm-price-totals bdi' ).html( replaced );
									}
									if ( $( '.wcpa_validate_field' ).length ) {
										$( '.wcpa_validate_field input' ).on( 'change', function() {
											option_total = parseFloat( $( '.wcpa_price_summary .wcpa_options_total .price_value' ).text().replace( ',', '.' ) );
											price_total  = ( b2b_price + option_total ).toFixed( 2 );
											$( '.wcpa_price_summary .wcpa_total .price_value' ).html( price_total.toString().replace( '.', ',' ) );
											if ( $( '.bm-price-totals' ).length ) {
												var replaced = $( '.bm-price-totals bdi' ).html().replace( /([0-9]+[,|.][0-9]+)/g, price_total ).replace( '.', ',' );
												$( '.bm-price-totals bdi' ).html( replaced );
											}
										} );
									}
								}

								/**
								 * WooCommerce Product Bundles
								 */
								if ( $( '.bundled_product' ).length > 0 ) {
									if ( $( '.bundled_product' ).find( '.variations' ) ) {
										return false;
									}
								}

								/**
								 * Regular Markup & exclude Germanized CSS class by default
								 */
								if ( ! $( '.single_variation_wrap' ).length ) {
									if ( $( '.summary > .legacy-itemprop-offers > .price' ).length ) {
										$( '.summary > .legacy-itemprop-offers' ).updatePrice( data, '.wc-gzd-additional-info' );
									} else
									if ( $( '.summary > .price' ).length ) {
										var selector = '.summary';
										if ( $( selector + ' > .price del' ).length ) {
											$( selector + ' > .price ins > .amount' ).replaceWith( data[ 'price' ] );
										} else {
											$( selector + ' > .price .amount' ).replaceWith( data[ 'price' ] );
										}
									} else
									// Check for Cornerstone builder.
									if ( $( '.cornerstone-builder.summary > .x-div > .x-div > .price').length ) {
										selector = '.cornerstone-builder.summary > .x-div > .x-div ';
										if ( $( selector + ' > .price del' ).length ) {
											$( selector + ' > .price ins > .amount' ).replaceWith( data[ 'price' ] );
										} else {
											$( selector + ' > .price .amount' ).replaceWith( data[ 'price' ] );
										}
									}
								} else {
									// Compatibility WooCommerce Tax Toggle
									if ( Cookies.get( 'woocommerce_show_tax' ) !== undefined && Cookies.get( 'woocommerce_show_tax' ) !== null ) {
										showTaxVar = Cookies.get( 'woocommerce_show_tax' );
										if ( $( '.woocommerce-variation-price > .price > .product-tax-on > .amount' ).length ) {
											$( '.woocommerce-variation-price > .price > .product-tax-on > .amount' ).replaceWith( data[ 'price' ] );
										}
									} else {
										var selector = '.woocommerce-variation-price';
										if ( 'gm_sepcial' == bm_update_price.german_market_price_variable_products ) {
											selector = '#german-market-variation-price';
										}
										if ( $('.summary > .price' ).length && ( ! $( '.woocommerce-variation-price > .price' ).length || $( '.woocommerce-variation-price' ).css( 'display' ) == 'none' ) ) {
											selector = '.summary';
										}
										// Check for Cornerstone builder.
										if ( $( '.cornerstone-builder.summary > .x-div > .x-div > .price').length ) {
											selector = '.cornerstone-builder.summary > .x-div > .x-div ';
										}
										if ( $( selector + ' > .price' ).length ) {
											if ( $( selector + ' > .price del' ).length ) {
												$( selector + ' > .price ins > .amount' ).replaceWith( data[ 'price' ] );
											} else {
												$( selector + ' > .price > .amount' ).replaceWith( data[ 'price' ] );
											}
											if ( $( selector + ' > .price-per-unit' ).length ) {
												$( selector + ' > .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
											}
										}
									}
								}

								/**
								 * AVIA Theme Builder
								 */
								if ( $( '.av-woo-purchase-button > .price' ).length ) {
									$( '.av-woo-purchase-button > .price > .amount' ).replaceWith( data[ 'price' ] );
									if ( $( '.av-woo-purchase-button > .legacy-itemprop-offers .price-per-unit' ).length ) {
										$( '.av-woo-purchase-button > .legacy-itemprop-offers .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
									}
								}

								/**
								 * Themify Shoppe-Child
								 */
								if ( $( '.module-product-price .price' ).length ) {
									$( '.module-product-price' ).updatePrice( data );
									if ( $( '.woocommerce-variation-price' ).length ) {
										$( '.woocommerce-variation .price > .amount' ).replaceWith( data[ 'price' ] );
									}
								}

								/**
								 * Elementor.
								 */
								if ( $( '.elementor-widget-wrap div[data-widget_type="woocommerce-product-price.default"]' ).length ) {
									if ( ! $( '.elementor-widget-wrap .single_variation_wrap' ).length ) {
										if ( $( 'div[data-widget_type="woocommerce-product-price.default"] .elementor-widget-container .price' ).length ) {
											$( 'div[data-widget_type="woocommerce-product-price.default"] .elementor-widget-container' ).updatePrice( data );
										}
									}
								}
								if ( $( 'div[data-widget_type="woocommerce-product-add-to-cart.default"] .elementor-widget-container .woocommerce-variation-price .price' ).length ) {
									$( 'div[data-widget_type="woocommerce-product-add-to-cart.default"] .elementor-widget-container .woocommerce-variation-price' ).updatePrice( data );
								}

								/**
								 * Avada.
								 */
								else if ( $( '.summary-container .price' ).length ) {
									$( '.summary-container' ).updatePrice( data );
									$( '.summary-container .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
								}

								/**
								 * Divi.
								 */
								else if ( $( '.et_pb_module > .et_pb_module_inner > .price' ).length ) {
									if ( $( '.et_pb_module > .et_pb_module_inner > .price del' ).length ) {
										$( '.et_pb_module > .et_pb_module_inner > .price ins' ).empty().html( data[ 'price' ] );
									} else {
										$( '.et_pb_module > .et_pb_module_inner > .price .amount' ).replaceWith( data[ 'price' ] );
									}
									$( '.et_pb_module_inner .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
									if ( $( '.woocommerce-variation-price' ).length ) {
										$( '.woocommerce-variation-price .price .amount' ).replaceWith( data[ 'price' ] );
									}
								}

								/**
								 * Avada mit Fusion Builder.
								 */
								else if ( $( '.fusion-woo-price-tb .price' ).length ) {
									$( '.fusion-woo-price-tb' ).updatePrice( data );
								}

								/**
								 * Avada mit Fusion Builder.
								 */
								else if ( $( '.fusion-woo-price-tb .price' ).length ) {
									$( '.fusion-woo-price-tb' ).updatePrice( data );
								}

								/**
								 * Uncode.
								 */
								else if ( $( '.uncont .price' ).length ) {
									if ( $( '.uncont .t-entry-text' ).length == 0 ) {
										$( '.uncont' ).updatePrice( data );
									}
								}
								if ( $( '.row-parent.product .uncont .price' ).length == 0 ) {
									$( '.row-parent.product .uncont div .amount' ).replaceWith( data[ 'price' ] );
								}

								/**
								 * Yootheme.
								 */
								else if ( $( '.uk-grid-item-match .price' ).length ) {
									$( '.uk-grid-item-match' ).updatePrice( data );
								}

								/**
								 * Handmade theme & variation swatches.
								 */
								else if ( $( '.summary-product .price' ).length ) {
									$( '.summary-product' ).updatePrice( data );
									$( '.summary-product .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
									if ( $( '.woocommerce-variation.single_variation' ).length ) {
										$( '.woocommerce-variation.single_variation .woocommerce-variation-price .price > .amount' ).replaceWith( data[ 'price' ] );
									}
								}

								/**
								 * some other themes.
								 */
								else if ( $( '.product-summary .price' ).length ) {
									$( '.product-summary' ).updatePrice( data );
									$( '.product-summary .price-per-unit .amount' ).replaceWith( data[ 'ppu' ] );
								}

								/**
								 * UX Builder - Flatsome
								 */
								else if ( $( '.woocommerce-variation.single_variation' ).length ) {
									$( '.woocommerce-variation.single_variation .woocommerce-variation-price .price > .amount' ).replaceWith( data[ 'price' ] );
								} else if ( $( '.product-price-container .product-page-price' ).length ) {
									$( '.product-price-container .product-page-price .amount' ).replaceWith( data[ 'price' ] );
								}

								/**
								 * Flatsome
								 */
								if ( $( '.price-wrapper > .product-page-price.price' ).length ) {
									if ( $( '.price-wrapper > .product-page-price.price > .amount' ).length > 1 ) {
										$( '.price-wrapper > .product-page-price.price' ).empty().html( data[ 'price' ] );
									} else {
										$( '.price-wrapper > .product-page-price.price > .amount' ).replaceWith( data[ 'price' ] );
									}
								}

								/**
								 * Totals.
								 */
								if ( $( '.bm-price-totals' ).length ) {
									if ( data[ 'totals' ] != '' ) {
										$( '.bm-price-totals > span.totals-price' ).empty().html( data[ 'totals' ] );
									}
									$( '.bm-price-totals' ).show();
								}

							}
						}
					},
				} );
			}
		} );

		$( qty_element ).change();
	}

	watchQtyInput();

} );
