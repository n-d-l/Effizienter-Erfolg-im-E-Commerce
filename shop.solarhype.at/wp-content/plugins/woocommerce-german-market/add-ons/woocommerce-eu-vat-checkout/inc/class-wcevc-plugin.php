<?php

/**
 * Class WCEVC_Plugin
 */
class WCEVC_Plugin {

	/**
	 * @var null|WCEVC_Plugin
	 */
	private static $instance = NULL;

	/**
	 * @return WCEVC_Plugin
	 */
	public static function get_instance() {

		if ( self::$instance === NULL ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * @return  WCEVC_Plugin
	 */
	private function __construct() { 

	}

	/**
	 * Start the plugin on plugins_loaded hook.
	 *
	 * @return  void
	 */
	public function run() {

		include_once( 'class-wcevc-settings.php' );
		$settings = WCEVC_Settings::get_instance();
		add_filter( 'woocommerce_de_ui_left_menu_items', array( $settings, 'german_market_menu' ) );
		add_action( 'woocommerce_de_ui_update_options', array( $settings, 'import_tax_rates' ) );
		add_action( 'woocommerce_de_ui_update_options', array( $settings, 'cn_number_handler' ) );

		require_once( 'class-general-tax-output.php' );
		$general_tax_output = WGM_General_Tax_Output::get_instance();
		
		$option_wcvmfp_enabled = get_option( 'wcevc_enabled_wgm', 'off' );
		if ( $option_wcvmfp_enabled === 'off' ) {
			return;
		}

		require_once( 'class-wcevc-calculations.php' );
		$calculations = WCEVC_Calculations::get_instance();
		add_filter( 'woocommerce_product_get_price',  array( $calculations, 'get_price_for_downloadable_products' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price',  array( $calculations, 'get_price_for_downloadable_products' ), 10, 2 );
		if ( 'all_products' === get_option( 'wcevc_enabled_wgm', 'off' ) || 'all_products_eu' === get_option( 'wcevc_enabled_wgm', 'off' ) ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', array( $calculations, 'adjust_non_base_location_prices' ), 100 );
		}
	}

	/**
	 * Install callback with check, if WooCommerce is installed.
	 *
	 * @return  void
	 */
	public function activate() {
		require_once( 'class-wcevc-settings.php' );
		$settings = WCEVC_Settings::get_instance();
		$settings->add_default_options();
	}
}
