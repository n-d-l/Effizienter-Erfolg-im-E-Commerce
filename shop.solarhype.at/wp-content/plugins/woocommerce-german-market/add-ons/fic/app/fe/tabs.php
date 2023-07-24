<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* Add Tabs to Product Page
*
* @wp-hook woocommerce_product_tabs
* @param Array $tabs
* @return Array
**/
function gm_fic_product_tab( $tabs ) {

	if ( is_admin() ) {

		// Compatibility for WooCommerce Tab Manager
		if ( function_exists( 'init_woocommerce_tab_manager' ) ) {

			$tabs[ 'gm_fic_ingredients' ] = array(
				'title' 	=> get_option( 'gm_fic_ui_frontend_labels_ingredients', __( 'Ingredients', 'woocommerce-german-market' ) ),
				'priority' 	=> 24,
				'callback' 	=> 'gm_fic_tab_content_ingredients'
			);

			$tabs[ 'gm_fic_nutritional_values' ] = array(
				'title' 	=> get_option( 'gm_fic_ui_frontend_labels_nutritional_values', __( 'Nutritional Values', 'woocommerce-german-market' ) ),
				'priority' 	=> 25,
				'callback' 	=> 'gm_fic_tab_content_nutritional_values'
			);

			$tabs[ 'gm_fic_allergens' ] = array(
				'title' 	=> get_option( 'gm_fic_ui_frontend_labels_allergens', __( 'Allergens', 'woocommerce-german-market' ) ),
				'priority' 	=> 26,
				'callback' 	=> 'gm_fic_tab_content_allergens'
			);

		}

		return $tabs;
	}

	// Do we have to add the tabs?
	global $product;

	if ( ! $product ) {
		return $tabs;
	}

	$add_tabs = gm_fic_have_to_add_tabs( $product );
	extract( $add_tabs, EXTR_OVERWRITE );

	// add tab nutritional_values
	if ( $add_nutritional_values ) {

		$tabs[ 'gm_fic_nutritional_values' ] = array(
			'title' 	=> get_option( 'gm_fic_ui_frontend_labels_nutritional_values', __( 'Nutritional Values', 'woocommerce-german-market' ) ),
			'priority' 	=> 25,
			'callback' 	=> 'gm_fic_tab_content_nutritional_values'
		);

	}

	// add tab allergens info
	if ( $add_allergens_info ) {

		$tabs[ 'gm_fic_allergens' ] = array(
			'title' 	=> get_option( 'gm_fic_ui_frontend_labels_allergens', __( 'Allergens', 'woocommerce-german-market' ) ),
			'priority' 	=> 26,
			'callback' 	=> 'gm_fic_tab_content_allergens'
		);

	}

	// add tab ingredients
	if ( $add_ingredients ) {

		$tabs[ 'gm_fic_ingredients' ] = array(
			'title' 	=> get_option( 'gm_fic_ui_frontend_labels_ingredients', __( 'Ingredients', 'woocommerce-german-market' ) ),
			'priority' 	=> 24,
			'callback' 	=> 'gm_fic_tab_content_ingredients'
		);

	}

	return $tabs;
}

/**
* return array with boolean if a tab has to be added
*
* @since 3.10.5.0.1
* @param WC_Product $product
* @return Array
*/
function gm_fic_have_to_add_tabs( $product ) {

	// Do we have to add the tabs
	$add_allergens_info 		= false;
	$add_ingredients 			= false;
	$add_nutritional_values		= false;

	$id = $product->get_id();
	$terms = get_terms( 'gm_fic_nutritional_values', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );

	if ( $product->get_type() == 'variable' ) {

		$children = $product->get_children();
		foreach ( $children as $child ) {

			// allergens
			if ( ! $add_allergens_info ) {
				$allergens_info = get_post_meta( $child, '_allergens_info', true );
				if ( ! empty( $allergens_info ) ) {
					$add_allergens_info = true;
				}
			}

			// nutritional values
			if ( ! $add_nutritional_values ) {
				foreach ( $terms as $term ) {
					$post_meta = get_post_meta( $child, '_nutritional_values_' . $term->slug, true );
					if ( $post_meta != '' ) {
						$add_nutritional_values = true;
						break;
					}
				}
			}

			// ingredients
			if ( ! $add_ingredients ) {
				$ingredients = get_post_meta( $child, '_fic_ingredients', true );
				if ( ! empty( $ingredients ) ) {
					$add_ingredients = true;
				}
			}

			if ( $add_allergens_info && $add_ingredients && $add_nutritional_values ) {
				break;
			}

		}

	}

	// allergens
	if ( ! $add_allergens_info ) {
		$allergens_info = get_post_meta( $id, '_allergens_info', true );
	 	$add_allergens_info = ! empty( $allergens_info );
	}

 	// ingredients
 	if ( ! $add_ingredients ) {
	 	$ingredients = get_post_meta( $id, '_fic_ingredients', true );
	 	$add_ingredients = ! empty( $ingredients );
	}

 	// nutritional values
 	if ( ! $add_nutritional_values ) {
	 	foreach ( $terms as $term ) {

			$post_meta = get_post_meta( $id, '_nutritional_values_' . $term->slug, true );
			if ( $post_meta != '' ) {
				$add_nutritional_values = true;
				break;
			}

		}
	}

	return array(
		'add_allergens_info' 		=> $add_allergens_info,
		'add_ingredients' 			=> $add_ingredients,
		'add_nutritional_values'	=> $add_nutritional_values,
	);

}

/**
* Render Tab for Nutritional Values
*
* @return void
**/
function gm_fic_tab_content_nutritional_values() {

	global $product;
	$id = $product->get_id();

	if ( apply_filters( 'gm_fic_ui_frontend_display_h2', true, 'nutritional_values' ) ) {
		?>
		<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_nutritional_values', __( 'Nutritional Values', 'woocommerce-german-market' ) ); ?></h2>
	<?php } ?>

	<div id="gm_fic_nutritional_values">
		<?php gm_fic_tab_content_nutritional_values_by_id( $id ); ?>
	</div>
	<?php

}

/**
* Render Tab for Allergens
*
* @return void
**/
function gm_fic_tab_content_allergens() {

	global $product;
	$id = $product->get_id();

	if ( apply_filters( 'gm_fic_ui_frontend_display_h2', true, 'allergens' ) ) {
		?>
		<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_allergens', __( 'Allergens', 'woocommerce-german-market' ) ); ?></h2>

	<?php } ?>

	<div id="gm_fic_allergens">
		<?php gm_fic_tab_content_allergens_by_id( $id ); ?>
	</div>

	<?php
}

/**
* Render Tab for Ingredients
*
* @return void
**/
function gm_fic_tab_content_ingredients() {

	global $product;
	$id = $product->get_id();

	if ( apply_filters( 'gm_fic_ui_frontend_display_h2', true, 'ingredients' ) ) {
		?>
		<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_ingredients', __( 'Ingredients', 'woocommerce-german-market' ) ); ?></h2>

	<?php } ?>

	<div id="gm_fic_ingredients">
		<?php gm_fic_tab_content_ingredients_by_id( $id ); ?>
	</div>

	<?php

}

/**
* Get Nutritional Values Table(ajax and none-ajax)
*
* @param Integer $id
* @return void
**/
function gm_fic_tab_content_nutritional_values_by_id( $id ) {

	?>
	<span class="gm_fic_nutritional_values_remark"><?php echo apply_filters( 'gm_fic_nutritional_values_remark', get_post_meta( $id, '_nutritional_values_remark', true ) ); ?></span>

		<?php

		$terms 					= get_terms( 'gm_fic_nutritional_values', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );
		$default_nutritionals 	= gm_fic_get_default_nutritionals();
		$prefix 				= get_option( 'gm_fic_ui_frontend_prefix_nutritional_values', __( '- of which', 'woocommerce-german-market' ) );
		if ( $prefix != '' ) {
			$prefix = ' ' . $prefix . ' ';
		}

		?><table class="gm-fic-nutritional-values"><?php

		$terms = apply_filters( 'gm_fic_order_terms_frontend', $terms );

		do_action( 'gm_fic_nutritional_values_before_trs', $id );

		foreach ( $terms as $term ) {

			$post_key		= '_nutritional_values_' . $term->slug;
			$post_value		= get_post_meta( $id, $post_key, true );

			if ( $post_value == '' ) {

				// if post_value is empty and it is a variation => try to get value from parent product
				$product = wc_get_product( $id );
				if ( is_a( $product, 'WC_Product_Variation' ) ) {
					$post_value = get_post_meta( $product->get_parent_id(), $post_key, true );
				}
			}

			// if value is empty and this field is not required => continue
			$required = false;
			if ( isset( $default_nutritionals[ $term->slug ] ) ) {
				$required = isset( $default_nutritionals[ $term->slug ][ 'required' ] ) && $default_nutritionals[ $term->slug ][ 'required' ];
			}

			$required = apply_filters( 'gm_fic_nutritional_values_is_term_required', $required, $term, $default_nutritionals, $id );

			if ( ! $required && $post_value == '' ) {
				continue;
			}

			$label = apply_filters( 'gm_fic_nutritional_values_term_name', $term->name, $term );
			$class = 'nut-' . $term->slug;

			// prefix if term has parent
			if ( isset( $default_nutritionals[ $term->slug ] ) ) {
				$has_parent = isset( $default_nutritionals[ $term->slug ][ 'parent' ] ) && $default_nutritionals[ $term->slug ][ 'parent' ];
				if ( $has_parent ) {
					$label = $prefix . $label;
					$class .= ' ' . 'nut-' . $default_nutritionals[ $term->slug ][ 'parent' ] . ' nut-sub-' . $default_nutritionals[ $term->slug ][ 'parent' ];
				}
			} else {
				if ( isset( $term->parent ) && $term->parent > 0 ) {
					$label = $prefix . $label;
				}
			}

			?>
			<tr class="gm-fic-nutritional-values-tr <?php echo $class; ?>">
				<td class="gm-fic-nutritional-values-td gm-fic-nutritional-values-td-label <?php echo $class; ?>"><?php echo $label; ?></td>
				<td class="gm-fic-nutritional-values-td gm-fic-nutritional-values-td-value <?php echo $class; ?>"><?php echo $post_value; ?></td>
				<?php do_action( 'gm_fic_nutritional_values_after_term_in_tr', $id, $term ); ?>
			</tr>
			<?php

			do_action( 'gm_fic_nutritional_values_after_tr', $id );

		}

		?></table><?php

		do_action( 'gm_fic_nutritional_values_after_table', $id );

}

/**
* Get Allergens Table(ajax and none-ajax)
*
* @param Integer $id
* @return void
**/
function gm_fic_tab_content_allergens_by_id( $id ) {

	$allergens_info = get_post_meta( $id, '_allergens_info', true );

	if ( $allergens_info == '' ) {

		// if allergens_info is empty and it is a variation => try to get value from parent product
		$product = wc_get_product( $id );
		if ( is_a( $product, 'WC_Product_Variation' ) ) {
			$allergens_info = get_post_meta( $product->get_parent_id(), '_allergens_info', true );
		}
	}

	do_action( 'gm_fic_tab_content_allergens_by_id_before_content' );

	echo apply_filters( 'gm_fic_tab_content_allergens_by_id_content', apply_filters( 'the_content', $allergens_info ), $allergens_info );

	do_action( 'gm_fic_tab_content_allergens_by_id_after_content' );
}

/**
* Get Ingredient Table(ajax and none-ajax)
*
* @param Integer $id
* @return void
**/
function gm_fic_tab_content_ingredients_by_id( $id ) {

	$ingredients = get_post_meta( $id, '_fic_ingredients', true );

	if ( $ingredients == '' ) {

		// if $ingredients is empty and it is a variation => try to get value from parent product
		$product = wc_get_product( $id );
		if ( is_a( $product, 'WC_Product_Variation' ) ) {
			$ingredients = get_post_meta( $product->get_parent_id(), '_fic_ingredients', true );
		}
	}

	$ingredients = str_replace( array( '[h]', '[/h]' ), array( '<span class="gm-fic-highlighted-ingredient">', '</span>' ), $ingredients );

	do_action( 'gm_fic_tab_content_ingredients_by_id_before_content' );

	echo apply_filters( 'gm_fic_tab_content_ingredients_by_id_content', apply_filters( 'the_content', $ingredients ), $ingredients );

	do_action( 'gm_fic_tab_content_ingredients_by_id_after_content' );
}

/**
* Register JS
*
* @wp-hook wp_enqueue_scripts
* @return void
**/
function gm_fic_product_tab_scripts() {

	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

	$in_footer = 'on' === get_option( 'german_market_frontend_js_in_footer', 'off' );
	wp_register_script( 'gm-fic-frontend', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/js/frontend.' . $min . 'js', array( 'jquery' ), Woocommerce_German_Market::$version, $in_footer );
	wp_enqueue_script( 'gm-fic-frontend' );
	wp_localize_script( 'gm-fic-frontend', 'gm_fix_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gm-fic-frontend' ) ) );

}
