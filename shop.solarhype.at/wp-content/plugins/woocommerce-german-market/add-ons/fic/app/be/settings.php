<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Backend Settings German Market 3.1
*
* wp-hook woocommerce_de_ui_options_global
* @param Array $items
* @return Array
*/
function gm_fic_woocommerce_de_ui_left_menu_items( $items ) {

	$items[ 400 ] = array( 
				'title'		=> __( 'FIC', 'woocommerce-german-market' ),
				'slug'		=> 'fic',
				'callback'	=>'gm_fic_ui_render_options',
				'options'	=> true,
		);

	return $items;
}

/**
* Render Options for global
* 
* @return void
*/
function gm_fic_ui_render_options() {

	$options = array(

		array(
			'name' => __( 'Labels and Default Texts', 'woocommerce-german-market' ),
			'type' => 'title',
			'id'   => 'gm_fic_ui_frontend_labels_title',
		),

		array(
			'name'		=> __( 'Ingredients', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'This label is shown on the product pages', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_frontend_labels_ingredients',
			'type'		=> 'text',
			'default'	=> __( 'Ingredients', 'woocommerce-german-market' ),
		),

		array(
			'name'		=> __( 'Nutritional Values', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'This label is shown on the product pages', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_frontend_labels_nutritional_values',
			'type'		=> 'text',
			'default'	=> __( 'Nutritional Values', 'woocommerce-german-market' ),
		),

		array(
			'name'		=> __( 'Allergens', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'This label is shown on the product pages', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_frontend_labels_allergens',
			'type'		=> 'text',
			'default'	=> __( 'Allergens', 'woocommerce-german-market' ),
		),

		array(
			'name'		=> __( 'Default Remark for Nutritional Values', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'This label is shown on the product pages', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_frontend_remark_nutritional_values',
			'type'		=> 'textarea',
			'default'	=> __( 'Nutritional values per 100g', 'woocommerce-german-market' ),
			'css'		=> 'min-width: 400px;'
		),

		array(
			'name'		=> __( 'Prefix for Nutritional Values Subcategories', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'For Example: Saturates ar a subcategory of Fats. So you can print in frontend after "Fats": " - of which Suturates". Then you have to enter "- of which" as the prefix.', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_frontend_prefix_nutritional_values',
			'type'		=> 'text',
			'default'	=> __( '- of which', 'woocommerce-german-market' ),
			'css'		=> 'min-width: 400px;'
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'gm_fic_ui_frontend_labels_title' 
		),

		array(
			'name' => __( 'Alcohol Content', 'woocommerce-german-market' ),
			'type' => 'title',
			'id'   => 'gm_fic_ui_alocohol_content_title',
		),

		array(
			'name'		=> __( 'Default Unit', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'Default unit used for the alcohol content of products.', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_alocohol_default_unit',
			'type'		=> 'text',
			'default'	=> __( '% vol', 'woocommerce-german-market' ),
		),

		array(
			'name'		=> __( 'Prefix', 'woocommerce-german-market' ),
			'desc_tip'  => __( 'Prefix before alcohol content.', 'woocommerce-german-market' ),
			'id'		=> 'gm_fic_ui_alocohol_prefix',
			'type'		=> 'text',
			'default'	=> __( 'alc.', 'woocommerce-german-market' ),
		),

		array(
			'name'     => __( 'Show Alcohol Content in Shop', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Displays a product’s alcohol content on product loop pages.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below add-to-cart button.', 'woocommerce-german-market' ),
			'id'       => 'gm_fic_ui_alocohol_loop',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		),

		array(
			'name'     => __( 'Show Alcohol Content on Product Pages', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Displays a product’s alcohol content on product pages.', 'woocommerce-german-market' ),
			'id'       => 'gm_fic_ui_alocohol_product_page',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
		),

		array(
			'name'     => __( 'Show Alcohol Content during Checkout and in Orders', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Displays a product’s alcohol content during checkout and in orders.', 'woocommerce-german-market' ),
			'id'       => 'gm_fic_ui_alocohol_checkout',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'gm_fic_ui_alocohol_content_title' 
		)

	);

	$options = apply_filters( 'gm_fic_backend_settings_render_options', $options );
	return( $options );

}
