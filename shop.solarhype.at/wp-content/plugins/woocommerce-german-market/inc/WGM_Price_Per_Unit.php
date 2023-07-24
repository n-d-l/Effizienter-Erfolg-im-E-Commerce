<?php

class WGM_Price_Per_Unit {

	public static function init() {

		add_filter( 'wgm_product_summary_parts', array( __CLASS__, 'add_ppu_part' ), 10, 2 );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'add_product_write_panel' ) );
		add_filter( 'wp_wc_invoice_formatted_line_subtotal', array( __CLASS__, 'repair_invoice_pdf' ), 10, 3 );

		self::install_and_set_automatic_calculation_ppu();	
	}

	/**
	 * Deactivate Auto Calc if the option has never been set
	 * and there are products that have ppu meta data
	 *
	 * @since 3.11
	 * @return void
	 */
	public static function install_and_set_automatic_calculation_ppu() {

		// correct installation of ppu
		if ( empty( get_option( 'woocommerce_de_automatic_calculation_ppu' ) ) ) {

			// option has never been set
			// check, if there are ppu meta fields in any product
			$args = array(
		        'post_type' => 'product',
		        'post_status' => 'any',
		       	'meta_query' => array(
				    array(
				        'key' => '_unit_regular_price_per_unit',
				        'compare' => 'EXISTS',
				    )
	    		)
	    	);
		    
		    $ppu_is_used = false;

		    $the_query = new WP_Query( $args ); 

		    if ( $the_query->have_posts() ) {
		    	$ppu_is_used = true;
		    
		    } else {

		    	$args = array(
			        'post_type' => 'product_variation',
			        'post_status' => 'any',
			       	'meta_query' => array(
					    array(
					        'key' => '_v_unit_regular_price_per_unit',
					        'compare' => 'EXISTS',
					    )
		    		)
		    	);

		    	 $the_query = new WP_Query( $args ); 

		    	if ( $the_query->have_posts() ) {
		    		$ppu_is_used = true;
		    	}

		    }

		    if ( $ppu_is_used ) {
		    	update_option( 'woocommerce_de_automatic_calculation_ppu', 'off' );
		    } else {
		    	update_option( 'woocommerce_de_automatic_calculation_ppu', 'on' );
		    }
		}
	}

	public static function add_ppu_part( $parts, $product ) {

		if ( get_option( 'woocommerce_de_show_price_per_unit', 'on' ) == 'on' ) {
			$parts[ 'ppu' ] = self::get_price_per_unit_string( $product );
		}

		return $parts;
	}

	public static function get_price_per_unit_string( $product ) {

		$result              = '';
		$price_per_unit_data = self::get_price_per_unit_data( $product );

		if ( empty( $price_per_unit_data ) ) {
			return $result;
		}

		$result .= apply_filters(
			'wmg_price_per_unit_loop',
			sprintf( '<span class="wgm-info price-per-unit price-per-unit-loop ppu-variation-wrap">' . trim( self::get_prefix( $price_per_unit_data ) . ' ' . self::get_output_format() ) . '</span>',
			         wc_price( str_replace( ',', '.', apply_filters( 'wgm_price_per_unit_get_price_per_unit_string_price_per_unit', $price_per_unit_data[ 'price_per_unit' ], $product ) ), apply_filters( 'wgm_ppu_wc_price_args', array() ) ),
			         str_replace( '.', wc_get_price_decimal_separator(), $price_per_unit_data[ 'mult' ] ),
			         $price_per_unit_data[ 'unit' ]
			),
			wc_price( str_replace( ',', '.', $price_per_unit_data[ 'price_per_unit' ] ) ),
			$price_per_unit_data[ 'mult' ],
			$price_per_unit_data[ 'unit' ]
		);

		return $result;
	}

	/**
	 * Get Output format prefix
	 *
	 * @since 3.10.1
	 * @param Array $price_per_unit_data
	 * @return String
	 */	
	public static function get_prefix( $price_per_unit_data ) {

		$prefix = '';

		if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {

			$prefix = get_option( 'woocommerce_de_ppu_outpout_format_prefix' );

			if ( isset( $price_per_unit_data[ 'unit' ] ) ) {
				$prefix = str_replace( '[unit]', $price_per_unit_data[ 'unit' ], $prefix );
			}

			if ( isset( $price_per_unit_data[ 'complete_product_quantity' ] ) ) {

				$price_per_unit_data[ 'complete_product_quantity' ] = str_replace( '.', wc_get_price_decimal_separator(), $price_per_unit_data[ 'complete_product_quantity' ] );

				$prefix = str_replace( '[complete-product-quantity]', $price_per_unit_data[ 'complete_product_quantity' ], $prefix );
			}		

		}

		return apply_filters( 'wgm_price_per_unit_get_prefix', $prefix, $price_per_unit_data );

	}

	/**
	 * Get Output format 
	 * If not all 3 Placeholders are used in option, the default setting will be used to avoid errors with sprintf
	 *
	 * @since 3.6.4
	 * @return String
	 */	
	public static function get_output_format() {

		$default = '([price] / [mult] [unit])';

		$option = get_option( 'woocommerce_de_ppu_outpout_format', $default );

		// check if option uses all three placeholders, if not, use default!
		if ( ( str_replace( '[price]', '', $option ) == $option ) || ( str_replace( '[mult]', '', $option ) == $option ) || ( str_replace( '[unit]', '', $option ) == $option ) ) {
			$option = $default;
		}

		$return = str_replace( array( '[price]', '[mult]', '[unit]' ), array( '%1$s', '%2$s', '%3$s' ), $option );

		return $return;
	}

	/**
	 * Retrives price per unit data
	 *
	 * @param WC_Product $_product
	 *
	 * @access public
	 * @static
	 * @author ap
	 * @return array
	 */
	public static function get_price_per_unit_data( $_product ) {

		$_product = apply_filters( 'german_market_used_product_for_price_per_unit', $_product );

		$id = $_product->get_id();

		$complete_product_quantity = '';

		if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {

			$complete_product_price 	= apply_filters( 'german_market_get_price_per_unit_data_complete_product_price', wc_get_price_to_display( $_product ), $_product );

			$complete_product_quantity 	= $_product->get_meta( '_auto_ppu_complete_product_quantity' );
			$unit 						= apply_filters( 'german_market_measuring_unit', $_product->get_meta( '_unit_regular_price_per_unit' ), $_product );
			$mult 						= $_product->get_meta( '_unit_regular_price_per_unit_mult' );
			
			if ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {
				
				$price_per_unit_product_weights_completely_off = $_product->get_meta( '_price_per_unit_product_weights_completely_off' );
				
				if ( $price_per_unit_product_weights_completely_off == 'on' ) {
					return array();
				}

				if ( empty( $complete_product_quantity ) || empty( $unit ) || empty( $mult ) ) {

					$complete_product_quantity 	= wc_get_weight( $_product->get_weight(), get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) ), get_option( 'woocommerce_weight_unit', 'kg' ) );
					$unit 						= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', get_option( 'woocommerce_weight_unit', 'kg' ) );
					$mult 						= get_option( 'woocommerce_de_automatic_calculation_use_wc_weight_mult', 1 );
				}

			}
			
			$price_per_unit 			= self::automatic_calculation( $complete_product_price, $complete_product_quantity, $mult );

		} else {

			$price          = ( $_product->is_on_sale() ) ? 'sale' : 'regular';
			$price_per_unit = str_replace( ',', '.', $_product->get_meta( '_' . $price . '_price_per_unit' ) );
			$unit           = apply_filters( 'german_market_measuring_unit', $_product->get_meta( '_unit_' . $price . '_price_per_unit' ), $_product);
			$mult           = $_product->get_meta( '_unit_' . $price . '_price_per_unit_mult' );

		}

		if ( $price_per_unit && $unit && $mult ) {
			return apply_filters( 'german_market_ppu_get_price_per_unit_data', compact( 'price_per_unit', 'unit', 'mult', 'complete_product_quantity' ), $_product );
		} else {
			return array();
		}

	}

	/**
	 * Calculate Price for automatic calculation
	 *
	 * @param 	Float complete_product_price
	 * @param 	Float complete_product_quantity
	 * @param 	Float mult
	 * @static
	 * @return Float
	 */
	public static function automatic_calculation( $complete_product_price, $complete_product_quantity, $mult ) {
		
		if ( floatval( $complete_product_quantity ) != 0.0 && floatval( $mult ) ) {
			return floatval( $complete_product_price ) / floatval( $complete_product_quantity ) * floatval( $mult );
		}

		return '';
		
	}

	/**
	 * Price Per Unit Product Tab Contents
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function add_product_write_panel() {

		if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {
			self::add_product_write_panel_automatic_calculation();
			return;
		}

		?>
		<div id="price_per_unit_options" class="panel woocommerce_options_panel" style="display: block; ">
			<?php
			$smalltax                         = '<br /><small> ' . __( 'VAT included',
			                                                           'woocommerce-german-market' ) . ' </small>';
			$regular_price_per_unit_selection = array( 'id' => '_unit_regular_price_per_unit' );

			$mult_field = '<span style="float: left;">&nbsp;&#47; &nbsp;</span> <input type="text" style="width: 40px;" name="_unit_regular_price_per_unit_mult" value="' . get_post_meta( get_the_ID(), '_unit_regular_price_per_unit_mult', TRUE ) . '" />';

			// Price
			WGM_Settings::extended_woocommerce_text_input(
				array(
					'id'                             => '_regular_price_per_unit',
					'label'                          => __( 'Default Price',
					                                        'woocommerce-german-market' ) . ' (' . get_woocommerce_currency_symbol() . ')' . $smalltax,
					'between_input_and_desscription' => $mult_field . self::select_scale_units( $regular_price_per_unit_selection )
				)
			);

			$sale_price_per_unit_selection = array( 'id' => '_unit_sale_price_per_unit' );

			$mult_field = '<span style="float: left;">&nbsp;&#47; &nbsp;</span> <input type="text" style="width: 40px;" name="_unit_sale_price_per_unit_mult" value="' . get_post_meta( get_the_ID(), '_unit_sale_price_per_unit_mult', TRUE ) . '" />';

			// Special Price
			WGM_Settings::extended_woocommerce_text_input(
				array(
					'id'                             => '_sale_price_per_unit',
					'label'                          => __( 'Sale Price',
					                                        'woocommerce-german-market' ) . ' (' . get_woocommerce_currency_symbol() . ')' . $smalltax,
					'between_input_and_desscription' => $mult_field . self::select_scale_units( $sale_price_per_unit_selection )
				)
			);
			?>
		</div>
		<?php
	}


	/**
	 * Price Per Unit Product Tab Contents for automatic calculation
	 *
	 * @access public
	 * @static
	 * @since 3.6.4
	 * @return void
	 */
	public static function add_product_write_panel_automatic_calculation() {

		$product = wc_get_product( get_the_ID() );

		if ( ! WGM_Helper::method_exists( $product, 'get_type' ) ) {
			return;
		}
		
		?>
		<div id="price_per_unit_options" class="panel woocommerce_options_panel automatic-calculation-ppu" style="display: block; ">

			<?php
				if ( $product->get_type() == 'variable' ) {
					?><p class="_regular_price_per_unit_field"><?php
							echo __( 'The price per unit can be set up in every variation of your variable product. Be default, in every variation the following settings are used until you choose "Special Variation Setting". Because of the fact that a variable product does not have a price, there will be no ouput for the price per unit of the variable product.', 'woocommerce-german-market' );
					?></p><?php
				}

				if ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {
					?><p class="_regular_price_per_unit_field"><?php
						echo __( 'You are using the products weight for the automatic calculation of the price per unit. You can override this setting for the price per unit if you just enter some data here.', 'woocommerce-german-market' );
					?></p><?php
				}
			?>
			<p class="form-field _regular_price_per_unit_field">
				<label for="_unit_regular_price_per_unit"><?php echo __( 'Scale Unit', 'woocommerce-german-market' ); ?>:</label>
				<?php echo self::select_scale_units( array( 'id' => '_unit_regular_price_per_unit' ) ); ?>
			</p>

			<p class="form-field _regular_price_per_unit_field">
				<label for="_auto_ppu_complete_product_quantity"><?php echo __( 'Complete product quantity', 'woocommerce-german-market' ); ?>:</label>
				<input type="number" min="0" step="<?php echo apply_filters( 'german_market_auto_ppu_step', '0.0001' ); ?>" name="_auto_ppu_complete_product_quantity" id="_auto_ppu_complete_product_quantity" value="<?php echo get_post_meta( get_the_ID(), '_auto_ppu_complete_product_quantity', TRUE ); ?>" />
			</p>

			<p class="form-field _regular_price_per_unit_field">
				<label for="_unit_regular_price_per_unit_mult"><?php echo __( 'Quantity to display', 'woocommerce-german-market' ); ?>:</label>
				<input type="number" min="0" step="<?php echo apply_filters( 'german_market_auto_ppu_step', '0.0001' ); ?>" name="_unit_regular_price_per_unit_mult" id="_unit_regular_price_per_unit_mult" value="<?php echo get_post_meta( get_the_ID(), '_unit_regular_price_per_unit_mult', TRUE ); ?>" />
			</p>

			<?php if ( get_option( 'woocommerce_de_automatic_calculation_use_wc_weight', 'off' ) == 'on' ) {

				$price_per_unit_product_weights_completely_off = get_post_meta( get_the_ID(), '_price_per_unit_product_weights_completely_off', TRUE );
				$pre_select = $price_per_unit_product_weights_completely_off == 'on' ? 'selected="selected"' : '';

				?><p class="form-field _regular_price_per_unit_field">
				<label for="_price_per_unit_product_weights_completely_off"><?php echo __( 'Don\'t show price per unit for this product', 'woocommerce-german-market' ); ?>:</label>

				<select name="_price_per_unit_product_weights_completely_off" style="margin-right: 5px;">
					<option value="off"><?php echo __( 'Off', 'woocommerce-german-market' ); ?></option>
					<option value="on" <?php echo $pre_select; ?>><?php echo __( 'On', 'woocommerce-german-market' ); ?></option>
				</select>

				<?php if ( $product->get_type() == 'variable' ) { 
					echo __( 'This setting also affects the variations.', 'woocommerce-german-market' );
				} ?>
				
			</p><?php

			} ?>

		</div>
		<?php

	}

	/**
	 * Make a select field for scale_units
	 *
	 * @access      public
	 *
	 * @param    array $field
	 *
	 * @uses        get_post_meta, get_terms, selected
	 * @global         $thepostid , $post, $woocommerce
	 * @static
	 * @return    string html
	 */
	public static function select_scale_units( $field ) {

		global $thepostid, $post, $woocommerce;

		if ( ! $thepostid ) {
			$thepostid = $post->ID;
		}

		if ( ! isset( $field[ 'class' ] ) ) {
			$field[ 'class' ] = 'select short';
		}

		if ( ! isset( $field[ 'value' ] ) ) {
			$field[ 'value' ] = get_post_meta( $thepostid, $field[ 'id' ], TRUE );
		}

		$default_product_attributes = WGM_Defaults::get_default_product_attributes();
		$attribute_taxonomy_name    = wc_attribute_taxonomy_name( $default_product_attributes[ 0 ][ 'attribute_name' ] );
		$terms                      = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );

		$terms = apply_filters( 'german_market_pa_measuring_terms_in_backend', $terms, $thepostid, $field[ 'value' ] );

		// fallback to depcracted bug
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$attribute_taxonomy_name    = 'pa_masseinheit';
			$terms                      = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );
		}

		// Select field output
		$select = sprintf( '<select name="%s" id="%s">', esc_attr( $field[ 'id' ] ), esc_attr( $field[ 'id' ] ) );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $value ) {

				$select .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $value->name ),
					selected( $field[ 'value' ], $value->name, FALSE ),
					! empty( $value->description ) ? esc_attr( $value->description )
						: esc_attr( __( 'Fill in attribute description!', 'woocommerce-german-market' ) )
				);
			}
		}
		$select .= '</select>';

		return $select;
	}

	/**
	* Price Per Unit in Checkout: Show PPU in Cart and Checkout
	*
	* @wp-hook woocommerce_cart_item_price
	* @wp-hook woocommerce_cart_item_subtotal
	* @since GM v3.2
	* @static
	* @access public
	* @param String $price
	* @param Array $cart_item_session_data
	* @param String $cart_item_key
	* @return String
	**/
	public static function ppu_co_woocommerce_cart_item_price( $price, $cart_item, $cart_item_key ) {
		
		if ( current_filter() == 'woocommerce_cart_item_subtotal' && ! is_checkout() ) {
			return $price;
		}

		$product_id 	= $cart_item[ 'product_id' ];
		$variation_id 	= $cart_item[ 'variation_id' ];

		if ( apply_filters( 'german_market_ppu_co_woocommerce_add_cart_item_data_return', false, $cart_item, $product_id, $variation_id ) ) {
			return $price;
		}

		$ppu_string = '';

		if ( $variation_id && $variation_id > 0 ) {
			
			$product = new WC_Product_Variation( $variation_id );
			$ppu_string = wcppufv_get_price_per_unit_string_by_product( $product );
			
			if ( $ppu_string == '' ) {
				$product = wc_get_product( $product_id );
				$ppu_string = self::get_price_per_unit_string( $product );
			}

		} else {
			
			$product = wc_get_product( $product_id  );
			$ppu_string = self::get_price_per_unit_string( $product );

		}

		return $price . apply_filters( 'german_market_ppu_co_woocommerce_cart_item_price_ppu_string', $ppu_string, $cart_item, $cart_item_key );
	}

	/**
	* Store into order
	*
	* @wp-hook woocommerce_new_order_item
	* @since GM v3.2.2
	* @static
	* @access public
	* @param Integer $item_id
	* @param Object $item
	* @param Integer $order_id
	* @return void
	**/
	public static function ppu_co_woocommerce_add_order_item_meta_wc_3( $item_id, $item, $order_id  ) {

		if ( apply_filters( 'german_market_ppu_co_woocommerce_add_order_item_meta_wc_3_return', false, $item_id, $item, $order_id ) ) {
			return;
		}

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			
			$product = $item->get_product();

			if ( ! $product ) {
				return;
			}
			
			if ( ! WGM_Helper::method_exists( $product, 'get_type' ) ) {
				return;
			}
			
			if ( $product->get_type() == 'variation' ) {

				$ppu_string = wcppufv_get_price_per_unit_string_by_product( $product );
			
				if ( $ppu_string == '' ) {
					$parent_product = wc_get_product( $product->get_parent_id() );
					$ppu_string = self::get_price_per_unit_string( $parent_product );
				}

			} else {

				$ppu_string = self::get_price_per_unit_string( $product );

			}

			if ( $ppu_string != '' ) {
				wc_add_order_item_meta( $item_id, '_gm_ppu' , $ppu_string );
			}

		}
		
	}

	/**
	* Price Per Unit in Checkout: Show PPU in Order
	*
	* @wp-hook woocommerce_order_formatted_line_subtotal
	* @since GM v3.2
	* @static
	* @access public
	* @param String $subtotal
	* @param WC_Order_item $item
	* @param WC_Order $order
	* @return String
	**/
	public static function ppu_co_woocommerce_order_formatted_line_subtotal( $subtotal, $item, $order ) {
		return $subtotal . self::ppu_co_woocommerce_order_formatted_line_subtotal_get_extra_ppu( $item, $order );
	}

	/**
	* Get Extra PPU String
	*
	* @since GM v3.10.5.0.1
	* @static
	* @access public
	* @param WC_Order_item $item
	* @param WC_Order $order
	* @return String
	**/
	public static function ppu_co_woocommerce_order_formatted_line_subtotal_get_extra_ppu( $item, $order ) {

		$extra_ppu = '';

		if ( $item->get_meta( '_gm_ppu' ) != '' ) {
			$extra_ppu = '<br />' . apply_filters( 'german_market_ppu_co_woocommerce_order_formatted_line_subtotal', $item->get_meta( '_gm_ppu' ), $item, $order ); 
		}

		return $extra_ppu;
	}

	/**
	* repair_invoice_pdf
	*
	* @since GM v3.10.5.0.1
	* @static
	* @access public
	* @param String $subtotal
	* @param WC_Order_item $item
	* @param WC_Order $order
	* @return String
	**/
	public static function repair_invoice_pdf( $subtotal, $item, $order ) {

		if ( 'off' === get_option ( 'woocommerce_de_show_ppu_invoice_pdf', 'off' ) ) {
			$subtotal = str_replace( self::ppu_co_woocommerce_order_formatted_line_subtotal_get_extra_ppu( $item, $order ), '', $subtotal );
		}

		return $subtotal;
	}

	/**
	* Price Per Unit in Invoice PDFs
	* @removed IN 3.12.6
	* @wp-hook wp_wc_invoice_pdf_start_template
	* @since GM v7.1
	* @static
	* @access public
	* @return void
	**/
	public static function ppu_invoice_pdfs_remove_ppu() {
		remove_filter( 'woocommerce_order_formatted_line_subtotal', array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_order_formatted_line_subtotal' ), 10, 3 );
	}

	/**
	* Price Per Unit in Invoice PDFs
	* @removed IN 3.12.6
	* @wp-hook wp_wc_invoice_pdf_end_template
	* @since GM v7.1
	* @static
	* @access public
	* @return void
	**/
	public static function ppu_invoice_pdfs_remove_ppu_filter() {
		if ( ! has_filter( 'woocommerce_order_formatted_line_subtotal', array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_order_formatted_line_subtotal' ) ) ) {
			add_filter( 'woocommerce_order_formatted_line_subtotal', array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_order_formatted_line_subtotal' ), 10, 3 );
		}
	}

}
