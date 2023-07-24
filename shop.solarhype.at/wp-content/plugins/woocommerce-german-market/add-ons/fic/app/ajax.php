<?php
/**
* Ajax Update Variation Nutritional Values
*
* @wp-hook wp_ajax_gm_fic_product_update_variation
* @wp-hook wp_ajax_nopriv_gm_fic_product_update_variation
* @return void
**/
function gm_fic_product_update_variation() {
	if ( isset ( $_REQUEST[ 'id' ] ) ) {
		$id = $_REQUEST[ 'id' ];
		gm_fic_tab_content_nutritional_values_by_id( $id );
	}
	exit();
}

/**
* Ajax Update Variation Allergens
*
* @wp-hook wp_ajax_gm_fic_product_update_variation_allergens
* @wp-hook wp_ajax_nopriv_gm_fic_product_update_variation_allergens
* @return void
**/
function gm_fic_product_update_variation_allergens() {
	if ( isset ( $_REQUEST[ 'id' ] ) ) {
		$id = $_REQUEST[ 'id' ];
		gm_fic_tab_content_allergens_by_id( $id );
	}
	exit();
}

/**
* Ajax Update Variation Ingredients
*
* @wp-hook wp_ajax_gm_fic_product_update_variation_ingredients
* @wp-hook wp_ajax_nopriv_gm_fic_product_update_variation_ingredients
* @return void
**/
function gm_fic_product_update_variation_ingredients() {
	if ( isset ( $_REQUEST[ 'id' ] ) ) {
		$id = $_REQUEST[ 'id' ];
		gm_fic_tab_content_ingredients_by_id( $id );
	}
	exit();
}
