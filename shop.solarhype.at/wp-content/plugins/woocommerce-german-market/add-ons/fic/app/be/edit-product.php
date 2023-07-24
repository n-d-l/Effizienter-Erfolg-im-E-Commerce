<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Add Product Meta
*
* @wp-hook woocommerce_product_options_general_product_data
* @wp-hook woocommerce_product_options_general_product_data
* @param Mixed $loop
* @param Mixed $variation_data
* @param Mixed $variation
* @return void
**/
function gm_fic_product_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

	// init
	$is_variation = ( ! is_null( $variation ) );
	$name_suffix = '';
	$label_style = 'width: 300px;';
	$input_style = '';
	$class_prefix = $is_variation ? '-variation' : '';

	$div_style = $is_variation ? 'float: left; width: 100%; padding: 0 12px 12px;' : 'border-bottom: 1px solid #eee;';
	?>
	<div class="gm-fic" style="<?php echo $div_style; ?>">

		<p class="form-field">
			<label style="width: 300px; float: left; cursor: pointer;" class="gm-fic-show-food-data<?php echo $class_prefix; ?>"><strong><?php echo __( 'Show Food Data', 'woocommerce-german-market' ); ?></strong></label>
			<label style="width: 300px; float: left; pointer: underline; display: none;" class="gm-fic-hide-food-data<?php echo $class_prefix; ?>"><strong><?php echo __( 'Hide Food Data', 'woocommerce-german-market' ); ?></strong></label>
		</p>

		<div class="gm-fic-data" style="display: none;">

	<?php

	if ( ! $is_variation ) {

		$id = get_the_ID();
		?><div class="options_group"><?php
	
	} else {

		$name_suffix = '_variable[' . $loop . ']';
		$id          = $variation->ID;
		$label_style = 'width: 300px; float:left;';
		$input_style = 'width: 40%;';
		echo '<br>';

	}

	?>	
	<p class="form-field">
		<label style="cursor: default; width: 100%;">
			<b><?php echo get_option( 'gm_fic_ui_frontend_labels_ingredients', __( 'Ingredients', 'woocommerce-german-market' ) ); ?></b>
			<small><br /><?php echo __( 'You can use <code>[h][/h]</code> to highlight special ingredients, for instance to highlight allergenes: <code>water, [h]milk[/h], sugar, salt</code>.', 'woocommerce-german-market' ); ?></small>
			<?php if ( $variation ) { ?>
					<small><br /><?php echo __( 'If this field is empty in this variation it will inherit its value from the parent product.', 'woocommerce-german-market' );?></small>
			<?php } ?>
		</label>
	</p>

	<?php
		$ingredients = get_post_meta( $id, '_fic_ingredients', true );
	?>

	<p class="form-field">
		<label style="width: 300px; float: left;" for="_fic_ingredients<?php echo $name_suffix; ?>"><?php echo __( 'Ingredients', 'woocommerce-german-market' ); ?>:</label>
		<textarea style="width: 50%; min-height: 75px;" name="_fic_ingredients<?php echo $name_suffix; ?>" id="_fic_ingredients<?php echo $name_suffix; ?>"><?php echo $ingredients; ?></textarea>
	</p>


	<?php


	if ( ! $is_variation ) {
		?></div><div class="options_group"><?php
	}

	?>	
		<p class="form-field">
			<label style="cursor: default; width: 100%;">
				<b><?php echo get_option( 'gm_fic_ui_frontend_labels_nutritional_values', __( 'Nutritional Values', 'woocommerce-german-market' ) ); ?></b>
				<small><br /><?php echo __( '* Fields are legally required', 'woocommerce-german-market' ); ?></small>
				<?php if ( $variation ) { ?>
					<small><br /><?php echo __( 'Values of empty fields in this variation will inherit their value from the parent product.', 'woocommerce-german-market' );?></small>
				<?php } ?>

			</label>
		</p>

		<?php

			$post_custom_keys = get_post_custom_keys( $id );
			if ( ! is_array( $post_custom_keys ) ) {
				$post_custom_keys = array();
			}

			if( ! in_array( '_nutritional_values_remark', $post_custom_keys ) ) {
				$remark = get_option( 'gm_fic_ui_frontend_remark_nutritional_values', __( 'Nutritional values per 100g', 'woocommerce-german-market' ) );
			} else {
				$remark = get_post_meta( $id, '_nutritional_values_remark', true );
			}
		?>

		<p class="form-field">
			<label style="width: 300px; float: left;" for="_nutritional_values_remark<?php echo $name_suffix; ?>"><?php echo __( 'Remark', 'woocommerce-german-market' ); ?>:</label>
			<textarea style="width: 50%;" name="_nutritional_values_remark<?php echo $name_suffix; ?>" id="_nutritional_values_remark<?php echo $name_suffix; ?>"><?php echo $remark; ?></textarea>
		</p>

		<?php

		do_action( 'gm_fic_after_nutritional_values_remark', $name_suffix, $id );

		$terms = get_terms( 'gm_fic_nutritional_values', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );
		$default_nutritionals = gm_fic_get_default_nutritionals();
		$prefix = get_option( 'gm_fic_ui_frontend_prefix_nutritional_values', __( '- of which', 'woocommerce-german-market' ) );
		
		if ( $prefix != '' ) {
			$prefix = $prefix . ' ';
		}
		
		foreach ( $terms as $term ) {

			$value = get_post_meta( $id, '_nutritional_values_' . $term->slug, true );

			$required = false;
			if ( isset( $default_nutritionals[ $term->slug ] ) ) {
				$required = isset( $default_nutritionals[ $term->slug ][ 'required' ] ) && $default_nutritionals[ $term->slug ][ 'required' ];
			}

			$required_string = $required ? '*' : '';
			
			// prefix if term has parent
			$prefix_string = '';
			if ( isset( $default_nutritionals[ $term->slug ] ) ) {
				$has_parent = isset( $default_nutritionals[ $term->slug ][ 'parent' ] ) && $default_nutritionals[ $term->slug ][ 'parent' ];
				if ( $has_parent ) {
					$prefix_string = $prefix;
				}
			} else {
				if ( isset( $term->parent ) && $term->parent > 0 ) {
					$prefix_string = $prefix;
				}
			}
			
			?>
				<p class="form-field">
					<label style="<?php echo $label_style; ?>" for="_nutritional_values_<?php echo $term->slug . $name_suffix; ?>"><?php echo $prefix_string . apply_filters( 'gm_fic_nutritional_values_term_name', $term->name, $term, true ) . $required_string; ?>:</label>
					<input style="<?php echo $input_style; ?>" type="text" name="_nutritional_values_<?php echo $term->slug . $name_suffix; ?>" id="_nutritional_values_<?php echo $term->slug . $name_suffix; ?>" value="<?php echo $value; ?>"/>
				</p>

			<?php

			do_action( 'gm_fic_after_nutritional_values_input', $name_suffix, $prefix_string, $term, $required_string, $label_style, $input_style, $id );
	
		}
		
		if ( ! $is_variation ) {
			?></div><div class="options_group"><?php
		}
		?>
        
		<p class="form-field" style="margin-bottom: 0; padding-bottom: 0;">
			<label style="cursor: default; width: 100%;">
				<b><?php echo get_option( 'gm_fic_ui_frontend_labels_allergens', __( 'Allergens', 'woocommerce-german-market' ) ); ?></b>
				<?php if ( $variation ) { ?>
					<small><br /><?php echo __( 'If this field is empty in this variation it will inherit its value from the parent product.', 'woocommerce-german-market' );?></small>
				<?php } ?>
			</label>
		</p>
        
        <?php $allergens_info = get_post_meta( $id, '_allergens_info', true ); ?>
        <?php $style = $is_variation ? '' : 'margin-top: 0;'; ?>

        <p class="form-field" style="<?php echo $style; ?>">
			<label style="width: 300px; float: left;" for="_allergens_info<?php echo $name_suffix; ?>"><?php echo __( 'Information', 'woocommerce-german-market' ); ?>:</label>
			<textarea style="width: 50%;" name="_allergens_info<?php echo $name_suffix; ?>" id="_allergens_info<?php echo $name_suffix; ?>"><?php echo $allergens_info; ?></textarea>
		</p>
        <?php


		if ( ! $is_variation ) {
			?></div><div class="options_group"><?php
		}
		?>
        
		<p class="form-field" style="margin-bottom: 0; padding-bottom: 0;">
			<label style="cursor: default; width: 100%;">
				<b><?php echo __( 'Alcohol Content', 'woocommerce-german-market' ); ?></b>
				<?php if ( $variation ) { ?>
					<small><br /><?php echo __( 'If this field is empty in this variation it will inherit its value from the parent product.', 'woocommerce-german-market' );?></small>
				<?php } ?>
			</label>
		</p>

		<?php 
			$alc_value = get_post_meta( $id, '_alcohol_value', true );
			$alc_unit = get_post_meta( $id, '_alcohol_unit', true );

			if ( $alc_unit == '' && $alc_value == '' ) {
				$alc_unit = get_option( 'gm_fic_ui_alocohol_default_unit', __( '% vol', 'woocommerce-german-market' ) );
			} 
		?>

		<p class="form-field" style="<?php echo $style; ?>">
			<label style="width: 300px; float: left;" for="_alcohol_value<?php echo $name_suffix; ?>"><?php echo __( 'Value', 'woocommerce-german-market' ); ?>:</label>
			<input type="text" style="width: 100px;" class="german-market-variation-input-not-translatable" name="_alcohol_value<?php echo $name_suffix; ?>" id="_alcohol_value<?php echo $name_suffix; ?>" value="<?php echo $alc_value; ?>" />
		</p>

		<p class="form-field" style="<?php echo $style; ?>">
			<label style="width: 300px; float: left;" for="_alcohol_unit<?php echo $name_suffix; ?>"><?php echo __( 'Unit', 'woocommerce-german-market' ); ?>:</label>
			<input type="text" style="width: 100px;" name="_alcohol_unit<?php echo $name_suffix; ?>" id="_alcohol_unit<?php echo $name_suffix; ?>" value="<?php echo $alc_unit; ?>" />
		</p>
        
        
        <?php


	if ( ! $is_variation ) {
		?></div><?php
	}

	?></div></div><?php

}

/**
* Save Product Meta
*
* @wp-hook woocommerce_process_product_meta
* @wp-hook woocommerce_ajax_save_product_variations
* @param Integer $post_id
* @param WP_Post $post
* @return void
**/
function gm_fic_product_options_save( $post_id, $post = NULL ) {

	$simple = ( current_action() !== 'woocommerce_ajax_save_product_variations' );

	if ( ! empty( $_POST[ 'variable_post_id' ] ) ) {
		$variation_ids = $_POST[ 'variable_post_id' ];
	} else {
		$variation_ids = array();
	}

	$terms = get_terms( 'gm_fic_nutritional_values', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );
	foreach ( $terms as $term ) {
		$meta_keys[ '_nutritional_values_' . $term->slug ] = '';
	}


	$meta_keys[ '_nutritional_values_remark' ] = '';
	$meta_keys[ '_allergens_info' ] = '';
	$meta_keys[ '_alcohol_value' ] = '';
	$meta_keys[ '_alcohol_unit' ] = '';
	$meta_keys[ '_fic_ingredients' ] = '';
	
	if ( $simple ) {
		foreach ( $meta_keys as $key => $fallback_value ) {
			$value = WGM_Settings::get_post_value( $key, $fallback_value );
			
			if ( ! is_array( $value ) ) {
				$value = stripslashes( $value );
			}

			update_post_meta( $post_id, $key, $value );
		}
	}

	foreach ( $variation_ids as $i => $post_id ) {

		foreach ( $meta_keys as $key => $fallback_value ) {
			$value = WGM_Settings::get_post_value( $key, $fallback_value, $i );

			if ( ! is_array( $value ) ) {
				$value = stripslashes( $value );
			}

			update_post_meta( $post_id, $key, $value );
		}

	}

}
