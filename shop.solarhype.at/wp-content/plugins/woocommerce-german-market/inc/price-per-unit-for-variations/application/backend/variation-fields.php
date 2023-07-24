<?php
/**
 * Feature Name: Variation Fields
 * Descriptions: These functions are adding the fields
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   https://marketpress.com
 * Licence:      GPLv3
 */

/**
 * Adds the field to the variations
 * 
 * @param	int $loop the current loop count
 * @param	array $variation_data the data of the current variation
 * @param	object $variation the variation post object
 * @return	void
 */
function wcppufv_add_field( $loop, $variation_data, $variation ) {

	?>
	<tr>
		<td colspan="2" >

			<div class="german-market-ppu-variation" style="border: 1px solid #eee; padding: 10px; box-sizing: border-box;">
				
				<b><?php echo __( 'Price Per Unit', 'woocommerce-german-market' ); ?>:</b>

				<?php 
				
				if ( get_option( 'woocommerce_de_automatic_calculation_ppu', 'on' ) == 'on' ) {
					wcppufv_add_product_write_panel_automatic_calculation( $loop, $variation_data, $variation );
				} else { 
					
					$smalltax = '<br /><small> ' . __( 'incl. Vat',  'woocommerce-german-market' ) . ' </small>';
					$regular_price_per_unit_selection = array( 'id' => 'variable_unit_regular_price_per_unit[' . $loop . ']', 'name' => '_v_unit_regular_price_per_unit' );

					$mult_field = '<span>&nbsp;&#47; &nbsp;</span> <input type="text" style="width: 40px;" name="variable_unit_regular_price_per_unit_mult[' . $loop . ']" value="'.  get_post_meta( $variation->ID, '_v_unit_regular_price_per_unit_mult', TRUE ) .'" />';

					// Price
					WGM_Settings::extended_woocommerce_text_input( array(
						'id'                             => 'variable_regular_price_per_unit[' . $loop . ']',
						'label'                          => __( 'Regular price',  'woocommerce-german-market' ) . ' (' . get_woocommerce_currency_symbol() . ')' . $smalltax,
						'value'                          => get_post_meta( $variation->ID, '_v_regular_price_per_unit', TRUE ),
						'between_input_and_desscription' => $mult_field . wcppufv_select_scale_units( $variation->ID, $regular_price_per_unit_selection ),
						'custom_attributes'				 => array( 'style' => 'width: 50%; max-width: 200px;' ) 
					) );

					$sale_price_per_unit_selection = array( 'id' => 'variable_unit_sale_price_per_unit[' . $loop . ']', 'name' => '_v_unit_sale_price_per_unit' );

					$mult_field = '<span>&nbsp;&#47; &nbsp;</span> <input type="text" style="width: 40px;" name="variable_unit_sale_price_per_unit_mult[' . $loop . ']" value="'.  get_post_meta( $variation->ID, '_v_unit_sale_price_per_unit_mult', TRUE ) .'" />';

					// Special Price
					WGM_Settings::extended_woocommerce_text_input( array(
						'id'                             => 'variable_sale_price_per_unit[' . $loop . ']',
						'label'                          => __( 'Sale Price',  'woocommerce-german-market' ) . ' (' . get_woocommerce_currency_symbol() . ')' . $smalltax ,
						'value'                          => get_post_meta( $variation->ID, '_v_sale_price_per_unit', TRUE ),
						'between_input_and_desscription' => $mult_field . wcppufv_select_scale_units( $variation->ID, $sale_price_per_unit_selection ),
						'custom_attributes'				 => array( 'style' => 'width: 50%; max-width: 200px;' ) 
					) );
					
				} ?>

			</div>

		</td>
	</tr>
	<?php
}

/**
 * Adds the field to the variations if automatic calculation is activated
 * 
 * @since 	3.6.4
 * @param	int $loop the current loop count
 * @param	array $variation_data the data of the current variation
 * @param	object $variation the variation post object
 * @return	void
 */
function wcppufv_add_product_write_panel_automatic_calculation( $loop, $variation_data, $variation ) {

	$regular_price_per_unit_selection = array( 'id' => 'variable_unit_regular_price_per_unit[' . $loop . ']', 'name' => '_v_unit_regular_price_per_unit' );

	$used_setting 	= get_post_meta( $variation->ID, '_v_used_setting_ppu', TRUE );
	$special_is_set = intval( $used_setting ) == 1 ? 'selected="selected"' : '';
	$label_style 	= 'style="width: 30%; float: left;"';
	$input_style	= 'style="width: 25%; max-width: 100px;"';
	$show_settings 	= $used_setting == 1 ? '' : 'style="display: none;"';
	?>
	
	<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc">
		<label <?php echo $label_style; ?> for="variable_used_setting_ppu[<?php echo $loop; ?>]"><?php echo __( 'Used Setting', 'woocommerce-german-market' ); ?>:</label>

		<select name="variable_used_setting_ppu[<?php echo $loop; ?>]" class="variable_used_setting_ppu" data-loop="<?php echo $loop; ?>">
			<option value="-1"><?php echo __( 'Same as parent', 'woocommerce-german-market' ); ?></option>
			<option value="1" <?php echo $special_is_set; ?>><?php echo __( 'Following Special Variation Setting', 'woocommerce-german-market' ); ?></option>
		</select>

	</p>



	<div id="gm_ppu_auot_calc_parent_special[<?php echo $loop;?>]" class="gm_ppu_auot_calc_parent_special gm_ppu_auot_calc_parent_special_<?php echo $loop; ?>" <?php echo $show_settings; ?>>

		<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc">
			<label <?php echo $label_style; ?> for="_unit_regular_price_per_unit"><?php echo __( 'Scale Unit', 'woocommerce-german-market' ); ?>:</label>
			<?php echo wcppufv_select_scale_units( $variation->ID, $regular_price_per_unit_selection ); ?>
		</p>

		<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc">
			<label <?php echo $label_style; ?> for="variable_auto_ppu_complete_product_quantity[<?php echo $loop; ?>]"><?php echo __( 'Complete product quantity', 'woocommerce-german-market' ); ?>:</label>
			<input <?php echo $input_style; ?> type="number" min="0" step="<?php echo apply_filters( 'german_market_auto_ppu_step', '0.0001' ); ?>" name="variable_auto_ppu_complete_product_quantity[<?php echo $loop; ?>]" id="variable_auto_ppu_complete_product_quantity[<?php echo $loop; ?>]" value="<?php echo get_post_meta( $variation->ID, '_v_auto_ppu_complete_product_quantity', TRUE ); ?>" class="german-market-variation-input-not-translatable"/>
		</p>

		<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc" style="clear: both;">
			<label <?php echo $label_style; ?> for="variable_unit_regular_price_per_unit_mult[<?php echo $loop; ?>]"><?php echo __( 'Quantity to display', 'woocommerce-german-market' ); ?>:</label>
			<input <?php echo $input_style; ?> type="number" min="0" step="<?php echo apply_filters( 'german_market_auto_ppu_step', '0.0001' ); ?>" name="variable_unit_regular_price_per_unit_mult[<?php echo $loop; ?>]" id="variable_unit_regular_price_per_unit_mult[<?php echo $loop; ?>]" value="<?php echo get_post_meta( $variation->ID, '_v_unit_regular_price_per_unit_mult', TRUE ); ?>" class="german-market-variation-input-not-translatable"/>
		</p>

	</div>

	<?php
}

/**
 * Adds the field to the variations
 * 
 * @param	int $post_id
 * @return	void
 */
function wcppufv_save_field( $post_id, $post = NULL ) {

	$simple = ( current_action() !== 'woocommerce_ajax_save_product_variations' );

		if ( ! empty( $_POST[ 'variable_post_id' ] ) ) {
			$variation_ids = $_POST[ 'variable_post_id' ];
		} else {
			$variation_ids = array();
		}

		/**
		 * meta_key => fallback_value
		 */
		$meta_keys = array(

				'variable_regular_price_per_unit'      			=> '',
				'variable_unit_regular_price_per_unit_mult' 	=> '',
				'variable_unit_regular_price_per_unit'			=> '',

				'variable_sale_price_per_unit'      			=> '',
				'variable_unit_sale_price_per_unit_mult' 		=> '',
				'variable_unit_sale_price_per_unit'         	=> '',

				'variable_auto_ppu_complete_product_quantity' 	=> '',
				'variable_used_setting_ppu'						=> '',

		);

		foreach ( $variation_ids as $i => $post_id ) {

			foreach ( $meta_keys as $key => $fallback_value ) {
				
				if ( isset( $_POST[ $key ][ $i ] ) ) {
					$value = $_POST[ $key ][ $i ];
				} else{
					continue;
				}

				update_post_meta( $post_id, str_replace( 'variable', '_v', $key ), $value );
				
			}

		}

}
