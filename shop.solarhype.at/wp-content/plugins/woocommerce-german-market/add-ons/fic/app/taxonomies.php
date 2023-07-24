<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Register taxonomies
*
* @return void
* @hook woocommerce_register_taxonomy
*
*/
function gm_fic_register_taxonomies() {

	// Register Nutritional Values
	register_taxonomy( 'gm_fic_nutritional_values',
       array( 'product', 'product_variation' ),
       array(
               'hierarchical'          => TRUE,
               'label'                 => __( 'Nutritional Values', 'woocommerce-german-market' ),
               'labels'                => array(
	                   'name'              => __( 'Nutritional Values', 'woocommerce-german-market' ),
	                   'singular_name'     => __( 'Nutritional Value', 'woocommerce-german-market' ),
	                   'menu_name'         => _x( 'Nutritional Values', 'Admin menu item', 'woocommerce-german-market' ),
	                   'search_items'      => __( 'Search Nutritional Values', 'woocommerce-german-market' ),
	                   'all_items'         => __( 'All Nutritional Values', 'woocommerce-german-market' ),
	                   'parent_item'       => __( 'Parent Nutritional Value', 'woocommerce-german-market' ),
	                   'parent_item_colon' => __( 'Parent Nutritional Value:', 'woocommerce-german-market' ),
	                   'edit_item'         => __( 'Edit Nutritional Value','woocommerce-german-market' ),
	                   'update_item'       => __( 'Update Nutritional Value', 'woocommerce-german-market' ),
	                   'add_new_item'      => __( 'Add New Nutritional Value', 'woocommerce-german-market' ),
	                   'new_item_name'     => __( 'New Nutritional Value Name', 'woocommerce-german-market' ),
	                   'not_found'		   => __( 'No Nutritional Value found', 'woocommerce-german-market' ),
               ),
               'public'                => FALSE,
               'show_ui'               => TRUE,
               'show_in_nav_menus'     => FALSE,
               'show_in_quick_edit'    => FALSE,
               'meta_box_cb'           => FALSE,
               'query_var'             => is_admin(),
               'capabilities'          => array(
	                   'manage_terms' => 'manage_product_terms',
	                   'edit_terms'   => 'edit_product_terms',
	                   'delete_terms' => 'delete_product_terms',
	                   'assign_terms' => 'assign_product_terms',
               ),
               'rewrite'               => FALSE,
       )
	);

	if ( get_option( 'woocommerce_de_fic_installed', 'no' ) != 'yes' ) {

		WGM_Helper::load_installation_language_textdomain();

		$nutritionals = gm_fic_get_default_nutritionals();

		$term_ids = array();

		foreach ( $nutritionals as $key => $nutritional ) {

			$parent_id = 0;

			if ( $nutritional[ 'parent' ] ) {
				if ( isset( $term_ids[ $nutritional[ 'parent' ] ] ) ) {
					$parent_id = $term_ids[ $nutritional[ 'parent' ] ];
				}
			}

			if ( term_exists( $nutritional[ 'label' ], 'gm_fic_nutritional_values' ) ) {
				continue;
			}

			$return_value = wp_insert_term( $nutritional[ 'label' ], 'gm_fic_nutritional_values', array(
					'slug'		=> $key,
					'parent'	=> $parent_id
				)
			);

			$term_ids[ $key ] = $return_value[ 'term_id' ];

		}

		WGM_Helper::reset_textdomain();
		
		update_option( 'woocommerce_de_fic_installed', 'yes' );

	}

}
