<?php

class CSP_ShippingManager {

	/**
	 * Singleton.
	 *
	 * @static
	 * @var class
	 */
	static $instance = null;

	/**
	 * @var array
	 */
	private $group;

	/**
	 * Singleton getInstance.
	 *
	 * @access public
	 * @static
	 *
	 * @return class CSP_ShippingManager
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CSP_ShippingManager constructor.
	 */
	public function __construct() {
		$this->group = BM_Conditionals::get_validated_customer_group();
		add_filter( 'woocommerce_package_rates', array( $this, 'disable_shipping_method_for_group' ), 10, 2 );
		add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_if_free_available' ), 100 );
		add_filter( 'wgm_dual_shipping_unset_shipping_method', '__return_false', 10, 2 );
	}
	/**
	 * @param $rates
	 * @param $package
	 *
	 * @return mixed
	 */
	public function disable_shipping_method_for_group( $rates, $package ) {

		if ( ! is_null( $this->group ) ) {

			$group_object = get_post( $this->group );
			$slug         = $group_object->post_name;

			if ( isset( $rates ) && ! empty( $rates ) ) {
				foreach ( $rates as $rate ) {

					$status = get_option( 'bm_shipping_method_enable_' . $rate->method_id . '_' . $slug );

					if ( 'on' != $status ) {
						unset( $rates[ $rate->id ] );
					}
					/* deactivate specific rates */
					$specific_rates = get_option( 'bm_shipping_rates_disabled_' . $slug );
					$specific_rates = explode( ',', $specific_rates );

					if ( isset( $specific_rates ) && ! empty( $specific_rates ) ) {
						foreach ( $specific_rates as $specific_rate ) {
							unset( $rates[ $specific_rate ] );
						}
					}
				}
			}
		}
		return $rates;
	}

	public static function update_shipping_options_for_group() {

		$groups = new BM_User();

		foreach ( $groups->get_all_customer_groups() as $group ) {

			foreach ( $group as $key => $value ) {

				foreach ( WC()->shipping()->load_shipping_methods() as $shipping_method ) {

					if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {

						$default = 'on';

						if ( get_option( 'bm_shipping_method_enable_' . $shipping_method->id . '_' . $key ) === false ) {
							update_option( 'bm_shipping_method_enable_' . $shipping_method->id . '_' . $key, 'on' );
						}
					} else {

						$default = 'off';

						if ( get_option( 'bm_shipping_method_enable_' . $shipping_method->id . '_' . $key ) === false ) {
							update_option( 'bm_shipping_method_enable_' . $shipping_method->id . '_' . $key, 'off' );
						}
					}
				}
			}
		}
	}

	/**
	 * Hide other shipping rates if free shipping is available.
	 *
	 * @param  array $rates given rates.
	 * @return array
	 */
	public function hide_shipping_if_free_available( $rates ) {
		$free = array();
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'free_shipping' === $rate->method_id ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}
		return ! empty( $free ) ? apply_filters( 'bm_filter_csp_shipping_manager_hide_shipping_if_free_available', $free, $rates ) : $rates;
	}

}

CSP_ShippingManager::get_instance();
