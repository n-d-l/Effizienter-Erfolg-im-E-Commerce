<?php

class RGN_Options {

	/**
	 * IE_Options constructor.
	 */
	public function __construct() {
	}

	/**
	 * @param $items
	 *
	 * @return mixed
	 */
	public function add_menu_item( $items ) {

		$items[5] = array(
			'title'    => __( 'Registration', 'b2b-market' ),
			'slug'     => 'registration',
			'submenu'  => array(
				array(
					'title'    => __( 'Registration', 'b2b-market' ),
					'slug'     => 'registration',
					'callback' => array( $this, 'registration_tab' ),
					'options'  => 'yes',
				),
				array(
					'title'    => __( 'General', 'b2b-market' ),
					'slug'     => 'general_registration',
					'callback' => array( $this, 'general_tab' ),
					'options'  => 'yes',
				),
			),
		);

		return $items;

	}

	/**
	 * @return array|mixed|void
	 */
	public function registration_tab() {

		/* export */
		$options = array();
		$groups  = new BM_User();

		$guest_group = get_option( 'bm_guest_group' );

		foreach ( $groups->get_all_customer_groups() as $group ) {

			foreach ( $group as $key => $value ) {

				$heading = array(
					'name' => get_the_title( $value ),
					'type' => 'title',
					'id'   => $key . 'register_options_title',
				);

				$end = array(
					'type' => 'sectionend',
					'id'   => $key . '_register_options_end',
				);

				$customer_groups = array(
					'name'    => __( 'Activate for registration', 'b2b-market' ),
					'id'      => 'register_' . $key,
					'type'    => 'bm_ui_checkbox',
					'default' => 'off',
				);
				$customer_groups_sort = array(
					'name'    => __( 'Sequence', 'b2b-market' ),
					'id'      => 'sort_' . $key,
					'type'    => 'number',
					'custom_attributes' => array( 'min' => '0' ),
				);
				$customer_groups_name = array(
					'name'    => __( 'Custom name', 'b2b-market' ),
					'id'      => 'custom_name_' . $key,
					'type'    => 'text',
				);

				if ( $value != $guest_group ) {
					array_push( $options, $heading );
					array_push( $options, $customer_groups );
					array_push( $options, $customer_groups_name );
					array_push( $options, $customer_groups_sort );
					array_push( $options, $end );
				}
			}
		}

		$options = apply_filters( 'woocommerce_bm_ui_register_options', $options );

		return $options;

	}

	/**
	 * @return array|mixed|void
	 */
	public function general_tab() {

		$options = array();

		$heading_double_optin = array(
			'name' => __( 'Do you want to activate Double Opt-in?', 'b2b-market' ),
			'type' => 'title',
			'id'   => 'double_optin_title',
		);
		array_push( $options, $heading_double_optin );

		// Check for German Market and show option or text.
		$gm_optin = get_option( 'wgm_double_opt_in_customer_registration' );

		if ( get_option( 'wgm_double_opt_in_customer_registration' ) != 'on' || ! class_exists( 'Woocommerce_German_Market' ) ) {
			$double_optin = array(
				'name'    => __( 'Activate Double Opt-In', 'b2b-market' ),
				'id'      => 'bm_double_opt_in_customer_registration',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			);
			array_push( $options, $double_optin );

			$double_optin_delete = array(
				'name'    => __( 'Autodelete non activated accounts after 14 days.', 'b2b-market' ),
				'id'      => 'b2b_market_double_opt_in_auto_delete',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			);
			array_push( $options, $double_optin_delete );
		} else {
			$double_optin = array(
				'name'    => __( 'Double Opt-In is already activated in German Market.', 'b2b-market' ),
				'id'      => 'bm_gm_double_optin_active',
				'type'    => 'text',
				'desc'    => __( 'The Double Opt-In from B2B Market is automatically deactivated when it is activated in German Market as they are the same solution and do not have to be included multiples times.', 'b2b-market' ),
			);
			array_push( $options, $double_optin );
		}

		$end = array(
			'type' => 'sectionend',
			'id'   => 'double_optin_options',
		);

		array_push( $options, $end );

		$heading_remove_label = array(
			'name' => __( 'Labels and Placeholders', 'b2b-market' ),
			'type' => 'title',
			'id'   => 'remove_label_title',
		);
		array_push( $options, $heading_remove_label );

		$remove_label = array(
			'name'    => __( 'Remove Label', 'b2b-market' ),
			'desc'    => __( 'Remove the label for the customer group select field.', 'b2b-market' ),
			'id'      => 'bm_remove_label_registration',
			'type'    => 'bm_ui_checkbox',
			'default' => 'off',
		);
		array_push( $options, $remove_label );

		$placeholder_instead_of_label = array(
			'name'    => __( 'Placeholder instead of Label', 'b2b-market' ),
			'desc'    => __( 'Use a placeholder instead of the label for all registration fields.', 'b2b-market' ),
			'id'      => 'bm_placeholder_instead_of_label',
			'type'    => 'bm_ui_checkbox',
			'default' => 'off',
		);
		array_push( $options, $placeholder_instead_of_label );


		array_push( $options, $end );

		$options = apply_filters( 'woocommerce_bm_ui_register_options', $options );

		return $options;

	}

}
