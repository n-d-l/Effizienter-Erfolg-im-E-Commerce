<?php

class RGN_Registration {

	/**
	 * Constructor for RGN_Registration
	 */
	public function __construct() {
		add_action( 'woocommerce_register_form', array( $this, 'ouptput_registration_fields' ), 10 );
		add_action( 'woocommerce_edit_account_form', array( $this, 'ouptput_registration_fields' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ), 10, 1 );
		add_filter( 'woocommerce_registration_errors', array( $this, 'validate_registration_fields' ), 10 );
		add_filter( 'woocommerce_save_account_details_errors', array( $this, 'validate_registration_fields' ), 10 );
		add_action( 'woocommerce_created_customer', array( $this, 'save_registration_fields' ) );
		add_action( 'woocommerce_save_account_details', array( $this, 'save_registration_fields' ) );
		add_filter( 'wcvat_woocommerce_billing_fields_vat_value', array( $this, 'add_vat_to_checkout' ), 10, 1 );
	}

	/**
	 * Output the registration fields markup
	 *
	 * @return void
	 */
	public function ouptput_registration_fields() {
		$fields = $this->get_registration_fields();

		foreach ( $fields as $key => $field_args ) {

			if ( is_user_logged_in() && isset( $field_args['hide_in_account'] ) && ( true === $field_args['hide_in_account'] ) ) {
				return;
			}
			if ( is_user_logged_in() && isset( $field_args['hide_in_registration'] ) && ( true === $field_args['hide_in_registration'] ) ) {
				return;
			}
			$value = null;

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				$value   = get_user_meta( $user_id, $key, true );
			}

			$value = isset( $field_args['value'] ) ? $field_args['value'] : $value;
			woocommerce_form_field( $key, $field_args, $value );
		}
	}

	/**
	 * Return the registration fields
	 *
	 * @return array
	 */
	public function get_registration_fields() {

		$groups       = RGN_Helper::get_selectable_groups();
		$remove_label = get_option( 'bm_remove_label_registration' );
		$options      = array();
		$sortable     = array();

		// check if groups empty --> return 

		foreach ( $groups as $key => $value ) {

			$sort        = get_option( 'sort_' . $key );
			$custom_name = get_option( 'custom_name_' . $key );
			$name        = get_the_title( $value );

			if ( isset( $custom_name ) && ! empty( $custom_name ) ) {
				$name = $custom_name;
			}

			if ( isset( $sort ) && ! empty( $sort ) ) {
				$sorting = $sort;
			} else {
				$sorting = 1;
			}
			$options[ $sorting . '__' . $key ] = $name;
		}

		ksort( $options );

		$options = apply_filters( 'bm_sort_registration_groups', $options );

		$placeholder_instead_of_label = get_option( 'bm_placeholder_instead_of_label' );

		if ( 'on' === $placeholder_instead_of_label ) {
			$label = 'placeholder';
		} else {
			$label = 'label';
		}

		$ust_id = array(
			'type'  => 'text',
			$label => __( 'VAT-ID', 'b2b-market' ),
			'hide_in_account' => apply_filters( 'b2b_hide_in_account', true ),
		);

		$company_registration_number = array(
			'type'                 => 'text',
			$label                => __( 'Company registration number', 'b2b-market' ),
			'hide_in_account'      => apply_filters( 'b2b_hide_in_account', true ),
		);

		$selectable_groups = array(
			'type'            => 'select',
			'options'         => $options,
			'hide_in_account' => apply_filters( 'b2b_hide_in_account', true ),
		);

		$required_fields = apply_filters( 'bm_required_registration_fields', false );

		if ( $required_fields ) {
			$ust_id['required']                      = true;
			$company_registration_number['required'] = true;
			$selectable_groups['required']           = true;
		}

		if ( 'off' === $remove_label ) {
			$selectable_groups['label'] = apply_filters( 'bm_registration_label', __( 'Customer Group', 'b2b-market' ) );
		}

		$fields = array( 'b2b_role' => $selectable_groups, 'b2b_uid' => $ust_id, 'b2b_company_registration_number' => $company_registration_number );
		return apply_filters( 'bm_account_fields', $fields );
	}

	/**
	 * Add registration fields to checkout
	 *
	 * @param array $checkout_fields array of current registration fields.
	 * @return array
	 */
	public function add_checkout_fields( $checkout_fields ) {
		$fields = $this->get_registration_fields();

		foreach ( $fields as $key => $field_args ) {

			$required_customer_group = apply_filters( 'bm_required_checkout_customer_group', false );

			if ( 'b2b_role' === $key && true === $required_customer_group ) {
				$field_args['required'] = true;
			} else {
				$field_args['required'] = apply_filters( 'bm_required_checkout_fields', false );
			}

			$field_args['priority']             = isset( $field_args['priority'] ) ? $field_args['priority'] : 0;
			$checkout_fields['account'][ $key ] = $field_args;
		}

		if ( ! empty( $checkout_fields['account']['account_password'] ) && ! isset( $checkout_fields['account']['account_password']['priority'] ) ) {
			$checkout_fields['account']['account_password']['priority'] = 0;
		}

		return $checkout_fields;
	}

	/**
	 * Registration fields validation
	 *
	 * @param object $validation_errors current validation errors.
	 * @return object
	 */
	public function validate_registration_fields( $validation_errors ) {
		$b2b_groups = RGN_Helper::get_net_tax_groups();

		if ( ! isset( $b2b_groups ) || empty( $b2b_groups ) ) {
			return $validation_errors;
		}

		if ( ! isset( $_POST['b2b_role'] ) && ! isset( $_POST['b2b_uid'] ) ) {
			return $validation_errors;
		}

		/* general available without tax net option */
		$b2b_role = explode( '__', esc_html( $_POST['b2b_role'] ) );
		$b2b_uid  = esc_html( $_POST['b2b_uid'] );

		foreach ( $b2b_groups as $group ) {
			if ( $group == $b2b_role[1] ) {

				/* check uid post */
				if ( empty( $b2b_uid ) && false === apply_filters( 'bm_vat_not_required', false ) ) {
					$validation_errors->add( 'b2b_uid_error', __( 'VAT is required!', 'b2b-market' ) );
				} else {
					/* check uid validation */
					$validator = new RGN_VAT_Validator( array(
						substr( $b2b_uid, 0, 2 ),
						substr( $b2b_uid, 2 ),
					) );

					if ( false === $validator->is_valid() && false === apply_filters( 'bm_vat_not_required', false ) ) {
						$validation_errors->add( 'b2b_uid_error', __( 'VAT is not valid!', 'b2b-market' ) );
					}
				}

				$required_fields = apply_filters( 'bm_required_registration_fields', false );

				if ( $required_fields ) {
					$b2b_crn = esc_html( $_POST['b2b_company_registration_number'] );

					if ( ! isset( $b2b_crn ) || empty( $b2b_crn ) ) {
						$validation_errors->add( 'b2b_company_registration_number_error', __( 'Company registration number is required!', 'b2b-market' ) );
					}
				}
			}
		}
		return $validation_errors;
	}

	/**
	 * Save registration fields to account
	 *
	 * @param int $customer_id current customer id.
	 * @return void
	 */
	public function save_registration_fields( $customer_id ) {
		if ( ! isset( $_POST['b2b_role']) || empty( $_POST['b2b_role']) ) {
			return;
		}

		if ( isset( $_POST['b2b_uid'] ) && ! empty( isset( $_POST['b2b_uid'] ) ) ) {
			update_user_meta( $customer_id, 'b2b_uid', sanitize_text_field( $_POST['b2b_uid'] ) );
		}

		if ( isset( $_POST['b2b_company_registration_number'] ) && ! empty( $_POST['b2b_company_registration_number'] ) ) {
			update_user_meta( $customer_id, 'b2b_company_registration_number', sanitize_text_field( $_POST['b2b_company_registration_number'] ) );
		}

		// Update in German Market.
		$gm_vat_id = get_user_meta( $customer_id, 'billing_vat', true );

		if ( empty( $gm_vat_id ) ) {
			update_user_meta( $customer_id, 'billing_vat', sanitize_text_field( $_POST['b2b_uid'] ) );
		}

		$b2b_role = explode( '__', $_POST['b2b_role'] );

		if ( isset( $b2b_role[1] ) && ! empty( $b2b_role[1] ) && $b2b_role[1] != 'customer' ) {
			$user = new WP_User( $customer_id );
			$user->remove_role( 'customer' );
			$user->add_role( $b2b_role[1] );
		}
	}

	/**
	 * For German Market Add-On "EU VAT Number Check"
	 * Prefill checkout b2b vat id if current default value is empty
	 *
	 * @param  String $vat_id
	 * @return String
	 */
	public function add_vat_to_checkout( $vat_id ) {

		if ( empty( $vat_id ) ) {

			// Get vat id from B2B Market.
			$user_id    = get_current_user_id();
			$b2b_vat_id = get_user_meta( $user_id, 'b2b_uid', true );

			if ( ! empty( $b2b_vat_id ) ) {
				$vat_id = $b2b_vat_id;
			}
		}

		return $vat_id;
	}
}

new RGN_Registration();
