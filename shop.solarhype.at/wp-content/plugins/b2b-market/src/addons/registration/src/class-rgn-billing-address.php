<?php

/**
 * Class to handle adress filtering in WooCommerce.
 */
class RGN_Address {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of MPCN_Address.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for MPCN_Address.
	 */
	public function __construct() {
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'admin_localisation_fields' ), 50, 1 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address_replacement' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'add_billing_order_fields' ), 10, 2 );
	}

	/**
	 * Adding custom placeholder to WoocCmmerce formatted billing address
	 *
	 * @param  array $address_formats array of address formats.
	 * @return array
	 */
	public function admin_localisation_fields( $address_formats ) {
		$show_ust_id         = apply_filters( 'bm_show_vatid_admin_order', true );
		$show_customer_group = apply_filters( 'bm_show_group_admin_order', true );

		foreach ( $address_formats as $country_code => $address_format ) {
			if ( $show_ust_id && $show_customer_group ) {
				$address_formats[ $country_code ] .= "\n{uid}\n{group}";
			} else {
				if ( $show_ust_id ) {
					$address_formats[ $country_code ] .= "\n{uid}";
				}
				if ( $show_customer_group ) {
					$address_formats[ $country_code ] .= "\n{group}";
				}
			}
		}

		return $address_formats;
	}

	/**
	 * Cnr placeholder replacement to WooCommerce formatted billing address
	 *
	 * @param array $replacements array of strings for replacement.
	 * @param array $args array of additional arguments.
	 * @return array
	 */
	public function set_formatted_address_replacement( $replacements, $args ) {
		$replacements['{uid}']   = ! empty( $args['uid'] ) ? $args['uid'] : '';
		$replacements['{group}'] = ! empty( $args['group'] ) ? $args['group'] : '';
		return $replacements;
	}

	/**
	 * Get the cnr meta value to be displayed in admin Order edit pages
	 *
	 * @param array  $address array of adress fields.
	 * @param object $order current order object.
	 * @return array
	 */
	public function add_billing_order_fields( $address, $order ) {
		$user   = new WP_User( $order->get_user_id() );
		$vat_id = get_user_meta( $order->get_user_id(), 'b2b_uid', true );
		$groups = array();

		if ( ! empty( $user->roles ) ) {
			$roles = new WP_Roles();
			$names = $roles->get_names();

			foreach ( $user->roles as $role ) {
				if ( isset( $names[ $role ] ) ) {
					$groups[ $role ] = $names[ $role ];
				}
			}
		}

		$groups_string = implode( ', ', $groups );

		if ( isset( $vat_id ) && ! empty( $vat_id ) ) {
			$address['uid']   = __( 'VAT-ID', 'b2b-market' ) . ': ' . $vat_id;
			$address['group'] = __( 'Customer Group', 'b2b-market' ) . ': ' . $groups_string;
		}
		return $address;
	}
}
