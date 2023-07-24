<?php
/**
 * Add-on Name:	WooCommerce EU VAT Number Check
 * Description: Adds a field for value-added tax identitification number (VATIN) during checkout. Validates field entries against the official web site of the <a href="http://ec.europa.eu/taxation_customs/vies/vieshome.do?locale=en">European Commission</a>.
 * Author:      MarketPress
 * Author URI:  http://marketpress.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// needed constants
define( 'WCVAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WCVAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCVAT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// kickoff
function wcvat_init() {

	if ( 'off' === get_option( 'wgm_add_on_woocommerce_eu_vatin_check', 'off' ) ) {
		return;
	}

	// helpers
	require_once dirname( __FILE__ ) . '/inc/helpers.php';

	// if we are here, this plugin is clearly active
	// so we need to check if the requirements are
	// setted. If not we need an admin notice to inform
	// the administration about the tasks to be done to
	// get this plugin work
	if ( ! wcvat_system_check() )
		return;

	// Load the frontend scripts
	if ( ! is_admin() ) {

		// load the frontend scripts
		require_once dirname( __FILE__ ) . '/inc/frontend/script.php';
		add_action( 'wp_enqueue_scripts', 'wcvat_wp_enqueue_scripts' );

		// load the frontend styles
		require_once dirname( __FILE__ ) . '/inc/frontend/style.php';
		add_action( 'wp_enqueue_scripts', 'wcvat_wp_enqueue_styles' );
	}

	// loads the validator
	require_once dirname( __FILE__ ) . '/inc/class-wc-vat-validator.php';

	// adding billing field to admin section and account page
	require_once dirname( __FILE__ ) . '/inc/class-wc-vat-field-integration.php';

	// load the tax stuff
	require_once dirname( __FILE__ ) . '/inc/tax.php';
	add_action( 'woocommerce_init', 'wcvat_recalculate_cart' );

	// the checkout form with the ajax validation
	require_once dirname( __FILE__ ) . '/inc/checkout.php';

	if ( 'hide_vat_field' != get_option( 'german_market_display_vat_number_field', 'eu_optional' ) ) {
		add_action( 'woocommerce_before_order_notes',               'wcvat_woocommerce_add_vat_field' );
	}
	add_filter( 'woocommerce_checkout_process', 					'wcvat_woocommerce_after_checkout_validation' );
	add_action( 'woocommerce_email_after_order_table', 				'wcvat_woocommerce_email_after_order_table', 10, 1);
	add_action( 'woocommerce_checkout_create_order', 				'wcvat_woocommerce_checkout_update_order_meta', 10, 2 );
	add_action( 'woocommerce_checkout_update_order_meta', 			'wcvat_woocommerce_checkout_update_order_meta_order_notes_log', 10, 2 );
	add_action( 'wp_ajax_wcvat_check_vat', 							'wcvat_check_vat' );
	add_action( 'wp_ajax_nopriv_wcvat_check_vat', 					'wcvat_check_vat' );
	add_action( 'woocommerce_order_details_after_order_table', 		'wcvat_woocommerce_order_details_after_order_table', 1 );
	add_action( 'woocommerce_order_details_after_order_table', 		'wcvat_order_details_after_customer_details', 2 );
	add_action( 'woocommerce_before_calculate_totals', 				'wcvat_woocommerce_before_calculate_totals' );
	add_action( 'woocommerce_review_order_after_order_total', 		'wcvat_woocommerce_checkout_details_after_order_table', 999 );
	add_action( 'init', 											'wcvat_vat_exempt_first_login' );

	/**
	 * VAT field in user admin section & my account page
	 */
	if ( 'on' === get_option( 'vat_options_billing_vat_editable', 'off' ) ) {
		add_action( 'woocommerce_account_edit-address_endpoint',        array( 'WC_VAT_Field_Integration', 'add_vat_field_to_my_account_page' ) );
		add_action( 'wp', 												array( 'WC_VAT_Field_Integration', 'save_vat_field_on_my_account_page' ) );
		add_action( 'show_user_profile',                                array( 'WC_VAT_Field_Integration', 'add_vat_field_to_user_section' ), 30 );
		add_action( 'personal_options_update',                          array( 'WC_VAT_Field_Integration', 'update_profile_billing_vat_field' ), 10, 1 );
		add_action( 'edit_user_profile',                                array( 'WC_VAT_Field_Integration', 'add_vat_field_to_user_section' ), 30 );
		add_action( 'edit_user_profile_update',                         array( 'WC_VAT_Field_Integration', 'update_profile_billing_vat_field' ), 10, 1 );
		add_action( 'wp_ajax_wcvat_admin_load_vat_from_profile', 		array( 'WC_VAT_Field_Integration', 'load_billing_vat_from_profile_order_user_change' ) );
	}

	// everything below is just in the admin panel
	if ( ! is_admin() )
		return;

	add_action( 'woocommerce_admin_order_data_after_order_details', 'wcvat_woocommerce_admin_order_data_after_billing_address', 25 );
	add_action( 'woocommerce_process_shop_order_meta', 				'wcvat_woocommerce_admin_save_vat_id_field', 10, 2 );

	// load the backend styles and scripts
	require_once dirname( __FILE__ ) . '/inc/backend/style.php';
	add_action( 'admin_enqueue_scripts', 'wcvat_admin_enqueue_styles' );

	// load the options page
	require_once dirname( __FILE__ ) . '/inc/backend/options-page.php';
	add_filter( 'woocommerce_de_ui_left_menu_items', 'wcvat_woocommerce_de_ui_left_menu_items' );

	// order list
	require_once dirname( __FILE__ ) . '/inc/backend/order-list.php';
	add_action( 'admin_init', function() {
		add_action( WGM_Hpos::get_hook_manage_shop_order_custom_column(), 'wcvat_order_list_data', 20, 2 );
	});
	

}

wcvat_init();
