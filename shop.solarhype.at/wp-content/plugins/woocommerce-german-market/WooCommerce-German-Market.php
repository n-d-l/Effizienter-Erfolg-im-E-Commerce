<?php
/**
 * Plugin Name:     German Market
 * Description:     Extension for WooCommerce providing features for legal compliance when your e-commerce business is based in Germany or Austria.
 * Author:          MarketPress
 * Version:         3.23
 * Licence:         GPLv3
 * Author URI:      https://marketpress.com
 * Plugin URI:		https://marketpress.com/shop/plugins/woocommerce-german-market/
 * Update URI:		https://marketpress.com/shop/plugins/woocommerce-german-market/
 * Text Domain:     woocommerce-german-market
 * Domain Path:     /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 5.1.0
 * WC tested up to: 7.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woocommerce_German_Market {

    /**
     * Plugin version
     * @var string
     */
    static public $version = '3.23';

    /**
     * Singleton object holder
     * @var mixed
     */
    static private $instance = NULL;

    /**
     * @var mixed
     */
    static public $plugin_name = 'WooCommerce German Market';

    /**
     * @var mixed
     */
    static public $textdomain = 'woocommerce-german-market';

    /**
     * @var mixed
     */
    static public $plugin_base_name = NULL;

    /**
     * @var mixed
     */
    static public $plugin_url = NULL;

    /**
     * @var string
     */
    static public $plugin_filename = __FILE__;

    static public $plugin_path;

    static public $autoupdater;


    /**
     * Plugin constructor. Init basic plugin behaviour and register hooks.
     */
	public function __construct() {

		define( 'WGM_ADD_ONS_PATH', untrailingslashit( plugin_dir_path( __FILE__ )  ) . DIRECTORY_SEPARATOR . 'add-ons' );

		require_once( untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'WGM_Loader.php' );
		WGM_Loader::register();

		// Load the textdomain
		$this->load_plugin_textdomain();

		// check for WC 3.0.0+
		if ( ! defined( 'GERMAN_MARKET_PREVENT_CHECK' ) ) {
	        if ( ! self::is_wc_3_0() ) {
	        	require_once( untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'WGM_Installation.php' );
	        	add_action( 'admin_notices', array( 'WGM_Installation', 'wc_3_0_0_notice' ) );
	        	return;
	        }

	        if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
	        	require_once( untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'WGM_Installation.php' );
	        	add_action( 'admin_notices', array( 'WGM_Installation', 'php_5_6_notice' ) );
	        	return;
			}
		}

		add_action( 'admin_init', array( 'Woocommerce_German_Market', 'on_activate_site' ) );
		add_action( 'init', array( 'Woocommerce_German_Market', 'init' ) );
		add_action( 'german_market_install_default_attributes_terms', array( 'WGM_Installation', 'install_default_attributes_terms' ) );

		// require Auto Updater
		if ( ! class_exists( 'MarketPress_Auto_Update_German_Market' ) ) {
            require_once untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'marketpress-autoupdater' . DIRECTORY_SEPARATOR . 'class-MarketPress_Auto_Update.php';
        }

        $plugin_data = new stdClass();
        $plugin_data->plugin_slug       = 'woocommerce-german-market';
        $plugin_data->shortcode         = 'wgm';
        $plugin_data->plugin_name       = self::$plugin_name;
        $plugin_data->plugin_base_name  = self::$plugin_base_name;
        $plugin_data->plugin_url        = self::$plugin_url;
        $plugin_data->version           = self::$version;

        $autoupdate = new MarketPress_Auto_Update_German_Market();
        self::$autoupdater = $autoupdate;
        $autoupdate->setup( $plugin_data );

       require_once untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'marketpress-improve-plugin' . DIRECTORY_SEPARATOR . 'class-marketpress-improve-german-market.php';
       $improve_german_market = new MarketPress_Improve_GermanMarket();

       //$wgm_hpos = WGM_Hpos::get_instance(); // enable if needed

		if ( get_option( 'german_market_frontend_init', 'on' ) == 'on' ) {

			$this->general_init();

			if ( self::is_frontend() ) {
				$this->frontend_init();
			}

		}

		if ( is_admin() ) {
			$this->backend_init();
		}

		/**
		* Load Modules
		* since 3.0
		*/
		WGM_Add_Ons::init();

		if ( get_option( 'german_market_frontend_init', 'on' ) != 'on' ) {
			return;
		}

		/**
		 * Orders
		 */
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( 'WGM_Template', 'add_mwst_rate_to_product_order_item' ), 10, 3 );
		add_action( 'woocommerce_order_item_meta_start', array( 'WGM_Template', 'woocommerce_order_item_meta_start_short_desc' ), 10, 4 );
		add_action( 'woocommerce_order_item_meta_start', array( 'WGM_Template', 'woocommerce_order_item_meta_requirements' ), 11, 4 );
		add_action( 'woocommerce_checkout_process', array( 'WGM_Template', 'shipping_address_check' ), 10, 1 );
		add_filter( 'woocommerce_order_item_name', array( 'WGM_Template', 'add_delivery_time_to_product_title' ), 10, 2 );
		add_filter( 'woocommerce_order_get_items', array( 'WGM_Template', 'filter_order_item_name' ), 10, 2 );
		add_filter( 'woocommerce_get_formatted_order_total', array( 'WGM_Template', 'kur_review_order_item' ), 1, 2 );
		add_filter( 'woocommerce_get_order_item_totals', array( 'WGM_Template', 'get_order_item_totals' ), 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals', array( 'WGM_Fee', 'add_tax_string_to_fee_order_item' ), 10, 2 );
		add_filter( 'woocommerce_order_get_tax_totals', array( 'WGM_Fee', 'add_fee_to_order_tax_totals' ), 10, 2 );
		add_filter( 'woocommerce_order_shipping_to_display', array( 'WGM_Shipping', 'shipping_tax_for_thankyou' ), 10, 2 );
		add_action( 'woocommerce_new_order_item', array( 'WGM_Template', 'add_deliverytime_to_order_item' ), 10, 3 );
		add_action( 'woocommerce_review_order_after_order_total', array( 'WGM_Template', 'kur_review_order_notice' ), 1 );

		// Changes for split tax calculation for WC 3.5
		add_action( 'woocommerce_order_item_after_calculate_taxes', 			array( 'WGM_Tax', 'recalc_taxes' ), 10, 2 );
		add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', 	array( 'WGM_Tax', 'recalc_taxes' ), 10, 2 );
		add_action( 'woocommerce_order_item_fee_after_calculate_taxes', 		array( 'WGM_Tax', 'recalc_taxes' ), 10, 2 );

		/**
		 * Misc
		 */
		add_filter( 'woocommerce_locate_template', 								array( 'WGM_Template', 'add_woocommerce_de_templates' ), 10, 3 );
		add_filter( 'woocommerce_payment_gateways', 							array( 'WGM_Cash_On_Delivery', 'remove_standard_cod' ), 1 );
		add_filter( 'woocommerce_payment_gateways', 							array( 'WGM_Cash_On_Delivery', 'add_cash_on_delivery_gateway' ) );
		add_filter( 'woocommerce_unforce_ssl_checkout', 						array( 'WGM_Settings', 'unforce_ssl_checkout' ) );
		add_filter( 'woocommerce_countries_ex_tax_or_vat', 						array( 'WGM_Helper', 'remove_woo_vat_notice' ), 10, 1 );
		add_filter( 'woocommerce_countries_inc_tax_or_vat', 					array( 'WGM_Helper', 'remove_woo_vat_notice' ), 10, 1 );
		add_filter( 'woocommerce_package_rates', 								array( 'WGM_Shipping', 'add_taxes_to_package_rates' ), 10 );
		add_filter( 'woocommerce_get_shipping_tax', 							array( 'WGM_Shipping', 'remove_kur_shipping_tax' ), 10 );
		add_filter( 'woocommerce_paypal_args', 									array( 'WGM_Helper', 'paypal_fix' ), 10, 2 );
		add_filter( 'pre_set_transient_woocommerce_cache_excluded_uris',		array( 'Woocommerce_German_Market', 'exclude_checkout_from_cache' ) );
		add_action( 'wp', 														array( 'Woocommerce_German_Market', 'exclude_checkout_from_cache_2' ) );
		add_action( 'woocommerce_before_template_part',							array( 'WGM_Helper', 'change_payment_gateway_order_button_text' ), 99, 4 );
		add_action( 'woocommerce_hidden_order_itemmeta', 						array( 'WGM_Template', 'add_hidden_order_itemmeta' ), 10 );

		/**
		* Purchase On Account Gateway since GM 3.2
		*/
		WGM_Purchase_On_Demand::get_instance();

		/**
		* SEPA Direct Debit Gateway since GM 3.3
		*/
		WGM_Sepa_Direct_Debit::get_instance();

	}

	public function general_init() {

		// Price Per Unit
		WGM_Price_Per_Unit::init();

		// Price Per Unit for Variations @since 3.0
		include_once( 'inc/WGM_Helper.php' );
		include_once( 'inc/price-per-unit-for-variations/price-per-unit-for-variations.php' );
		wcppufv_init();

		WGM_Tax_Hooks::init();
		add_filter( 'wgm_product_summary_parts', array( 'WGM_Shipping', 'add_shipping_part' ), 10, 2 );
		WGM_Product::init();
		WGM_Double_Opt_In_Customer_Registration::init();

		// Sortable products in cart & checkout
		WGM_Sortable_Products_Hooks::init();

		// Age Rating
		if ( get_option( 'german_market_age_rating', 'off' ) == 'on' ) {
			WGM_Age_Rating::get_instance();
		}

		// Legal Information for Produrct Reviews
		$legal_information_for_product_reviews = WGM_Legal_Information_Product_Reviews::get_instance();

		/**
		 * Emails
		 */
		add_filter( 'woocommerce_email_footer_text',							array( 'WGM_Email', 'get_email_de_footer' ), 5 );
		add_action( 'woocommerce_email_footer', 								array( 'WGM_Email', 'disable_footer_text_for_admin_emails' ) );
		add_filter( 'woocommerce_email_headers',								array( 'WGM_Email', 'woocommerce_email_headers_bcc_cc' ), 10, 4 );
		add_action( 'woocommerce_email_order_meta',								array( 'WGM_Email', 'cache_order' ), 10, 1 );
		add_filter( 'woocommerce_email_attachments', 							array( 'WGM_Email', 'add_attachments' ), 10, 3 );

		// Repeat digital notice in emails
		if ( get_option( 'woocommerce_de_repeat_digital_content_notice_position', 'after' ) == 'after' ) {
			add_action( 'woocommerce_email_order_meta',							array( 'WGM_Email', 'repeat_digital_content_notice' ), 50, 3 );
		} else { // before order content
			add_action( 'woocommerce_email_before_order_table',					array( 'WGM_Email', 'repeat_digital_content_notice' ), 30, 3 );
		}

		/**
		 * Taxonomies
		 */
		add_action( 'woocommerce_register_taxonomy',							array( 'WGM_Settings', 'register_taxonomies' ) );
		add_action( 'woocommerce_register_taxonomy', 							array( 'WGM_Defaults', 'register_default_lieferzeiten_strings' ) );
		add_action( 'woocommerce_register_taxonomy', 							array( 'WGM_Defaults', 'register_default_sale_strings' ) );
		add_filter( 'wc_tax_enabled', array( 'WGM_Tax', 'is_cart_tax_enabled' ) );

		/**
		 * Attributes in product names
		 */
		if ( get_option( 'german_market_attribute_in_product_name', 'off' ) == 'off' ) {

			add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );
			add_filter( 'woocommerce_cart_item_name', array( 'WGM_Template', 'attribute_in_product_name' ), 1, 3 );
			add_filter( 'woocommerce_order_item_name', array( 'WGM_Template', 'attribute_in_product_name_order' ), 10, 3 );
		}

		/**
		* WooCommerce Compatibilities
		*/
		WGM_Compatibilities::get_instance();

		/**
		* Manual Order Confirmation
		*/
		WGM_Manual_Order_Confirmation::get_instance();

		/**
		* Due Date
		*/
		WGM_Due_Date::get_instance();

		/**
		* Product Depending Checkbox
		*/
		WGM_Checkbox_Product_Depending::get_instance();

		/**
		* Price Per Unit in Checkout & Orders since GM v3.2
		**/
		if ( get_option( 'woocommerce_de_show_ppu_checkout', 'off' ) == 'on' ) {

			add_filter( 'woocommerce_cart_item_price', 					array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal',				array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_cart_item_price' ), 10, 3 );
			add_action( 'woocommerce_new_order_item',					array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_add_order_item_meta_wc_3' ), 10, 3 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', 	array( 'WGM_Price_Per_Unit', 'ppu_co_woocommerce_order_formatted_line_subtotal' ), 10, 3 );

			if ( get_option( 'woocommerce_de_show_ppu_invoice_pdf' ) == '' ) {
				update_option( 'woocommerce_de_show_ppu_invoice_pdf', 'on' );
			}
		}

		add_filter( 'woocommerce_checkout_create_order', array( 'WGM_Helper', 'do_not_save_customer_ip_in_order' ), 1, 2 );

		WGM_Shortcodes::register();

		do_action( 'german_market_after_general_init' );

	}

	public function frontend_init() {

		WGM_Embed::init();

		/**
		 * General
		 */
		add_filter( 'body_class', 												array( 'WGM_Helper', 'add_checkout_body_classes' ) );
		add_action( 'wp_enqueue_scripts', 										array( 'Woocommerce_German_Market', 'enqueue_frontend_scripts' ), 15 );

		/**
		 * Shop
		 */
		add_filter( 'woocommerce_product_get_name', 							array( 'WGM_Template', 'add_virtual_product_notice' ), 1, 2 );
		add_filter( 'woocommerce_product_title', 								array( 'WGM_Template', 'add_virtual_product_notice' ), 1, 2 );
		add_action( 'woocommerce_single_product_summary',						array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		add_action( 'woocommerce_after_shop_loop_item_title',					array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		add_filter( 'woocommerce_blocks_product_grid_item_html',				array( 'WGM_Template', 'german_market_woocommerce_blocks_price' ), 10, 3 );
		add_filter( 'woocommerce_available_variation', 							array( 'WGM_Helper', 'prepare_variation_data' ), 10, 3 );
		add_filter( 'woocommerce_show_variation_price', 						'__return_true' );
		add_filter( 'woocommerce_get_price_html', 								array( 'WGM_Template', 'add_sale_label_to_price' ), 15, 2 );
		add_filter( 'woocommerce_variable_price_html',  						array( 'WGM_Template', 'add_sale_label_to_variable_products' ), 15, 2 );
		add_action( 'wgm_after_shipping_fee_single', 							array( 'WGM_Template', 'add_template_loop_shop' ), 11 );
		add_action( 'woocommerce_single_product_summary', 						array( 'WGM_Template', 'add_digital_product_prerequisits' ), 20 );
		add_filter( 'wgm_product_summary_parts', 								array( 'WGM_Template', 'add_product_summary_price_part' ), 0, 3 );
		add_filter( 'wgm_product_summary_parts',								array( 'WGM_Template', 'add_extra_costs_non_eu' ), 50, 3 );
		add_action( 'woocommerce_grouped_product_list_before_price',			array( 'WGM_Template', 'init_grouped_product_adaptions' ) );

		/**
		* Delivery Time in Checkout since GM v3.2
		**/
		if ( get_option( 'woocommerce_de_show_delivery_time_checkout', 'off' ) == 'on' ) {
			add_filter( 'woocommerce_add_cart_item_data', 			array( 'WGM_Template', 'delivery_time_co_woocommerce_add_cart_item_data' ), 10, 3 );
			add_filter( 'woocommerce_get_cart_item_from_session', 	array( 'WGM_Template', 'delivery_time_co_woocommerce_get_cart_item_from_session' ), 10, 3 );
			add_filter( 'woocommerce_get_item_data', 				array( 'WGM_Template', 'delivery_time_co_woocommerce_get_item_data' ), 10, 2 );
		}

		/**
		* Widgets
		**/
		//add_action( 'woocommerce_after_template_part', 						array( 'WGM_Template', 'widget_after_content_product' ), 10, 4 );
		add_action( 'woocommerce_widget_product_item_end', 						array( 'WGM_Template', 'widget_product_item_end' ) );
		add_action( 'woocommerce_widget_cart_item_quantity',					array( 'WGM_Template', 'mini_cart_price' ), 10, 3 );

		/**
		 * Cart
		 */
		add_action( 'woocommerce_cart_contents', 								array( 'WGM_Template', 'add_shop_table_cart' ) );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', 			array( 'WGM_Template', 'add_shopping_cart' ) );
		add_action( 'woocommerce_review_order_before_submit',					array( 'WGM_Template', 'add_wgm_checkout_session' ) );

		/**
		 * Checkout
		 */

		if ( get_option( 'gm_deactivate_checkout_hooks', 'off' ) == 'off' ) {
			add_filter( 'woocommerce_order_button_html', 						array( 'WGM_Template', 'remove_order_button_html' ), 9999 );
		}

		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'on' ) {

			add_action( 'wgm_review_order_before_submit',						array( 'WGM_Template', 'add_review_order' ) );
			add_filter( 'woocommerce_checkout_show_terms',						array( 'WGM_Template', 'remove_terms_from_checkout_page' ) );
			add_filter( 'woocommerce_payment_successful_result',				array( 'WGM_Template', 'validate_payment_result_redirect' ) );
			add_filter( 'allowed_redirect_hosts', 								array( 'WGM_Template', 'add_payment_result_to_allowed_hosts' ) );
			add_action( 'woocommerce_after_checkout_validation', 				array( 'WGM_Template', 'do_de_checkout_after_validation' ), 1, 2 );
			add_action( 'woocommerce_review_order_after_submit', 				array( 'WGM_Template', 'print_order_button_html' ), 9999 );

		} else {

			if ( get_option( 'gm_deactivate_checkout_hooks', 'off' ) == 'off' ) {

				// reorder checkout page if second checkout is disabled
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

				add_action( 'woocommerce_de_checkout_payment', 						'woocommerce_checkout_payment' );

				if ( get_option( 'gm_order_review_checkboxes_before_order_review', 'off' ) == 'on' ) {
					add_action( 'woocommerce_de_checkout_payment', 					array( 'WGM_Template', 'add_review_order' ) );
				}

				if ( get_option( 'gm_order_review_checkboxes_before_order_review', 'off' ) == 'off' ) {
					add_action( 'woocommerce_checkout_order_review', 				array( 'WGM_Template', 'add_review_order' ), 15 );
				}

				add_action( 'woocommerce_checkout_order_review', 					array( 'WGM_Template', 'print_order_button_html' ), 9999 );

			} else {

				add_action( 'woocommerce_review_order_before_submit',				array( 'WGM_Template', 'add_review_order' ) );

			}

			// TOC always, validate all checkboxes
			if ( get_option( 'gm_force_term_template', 'on' ) == 'on' ) {
				add_action( 'woocommerce_de_add_review_order',						array( 'WGM_Template', 'terms_and_conditions' ) );
			}

			add_action( 'woocommerce_after_checkout_validation', 				array( 'WGM_Template', 'checkout_after_validation_without_sec_checkout' ), 10, 2 );

		}

		// Checkbox Loggins since 3.8.2
		if ( get_option( 'gm_order_review_checkboxes_logging', 'off' ) == 'on' ) {
			add_action( 'woocommerce_checkout_order_processed', array( 'WGM_Template', 'checkbox_logging' ), 10, 3 );
		}

		// Handle Return Button on 2nd Checkout page => load order notes and ship to different adress
		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'on' ) {

			add_filter( 'woocommerce_checkout_get_value', 					array( 'WGM_Template', 'woocommerce_checkout_get_value_order_comments' ), 10, 2 );
			add_filter( 'woocommerce_ship_to_different_address_checked', 	array( 'WGM_Template', 'woocommerce_ship_to_different_address_checked' ) );
		}

		add_filter( 'woocommerce_cart_totals_fee_html', 						array( 'WGM_Fee', 'show_gateway_fees_tax' ), 10, 2 );
		add_action( 'woocommerce_cart_calculate_fees', 							array( 'WGM_Fee', 'add_fee_to_gateway_page' ), 10, 1 );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', 		array( 'WGM_Fee', 'cart_totals_get_fees_from_cart_taxes' ), 10, 3 );

		// Cart Totals
		add_filter( 'woocommerce_cart_item_subtotal', 							array( 'WGM_Template', 'show_taxes_in_cart_theme_template' ), 10, 3 );
		add_action( 'woocommerce_cart_totals_after_order_total', 				array( 'WGM_Template', 'kur_notice' ), 1 );
		add_filter( 'woocommerce_cart_totals_order_total_html',					array( 'WGM_Template', 'woocommerce_cart_totals_excl_tax_string' ) );
		add_filter( 'woocommerce_cart_shipping_method_full_label', 				array( 'WGM_Shipping', 'add_shipping_tax_notice' ), 10, 2 );
		add_action( 'woocommerce_cart_totals_before_order_total',				array( 'WGM_Template', 'add_mwst_rate_to_cart_totals' ) );
		add_action( 'woocommerce_cart_totals_after_order_total',				array( 'WGM_Template', 'remove_mwst_rate_from_cart_totals' ) );
		add_action( 'woocommerce_review_order_before_order_total',				array( 'WGM_Template', 'add_mwst_rate_to_cart_totals' ) );
		add_action( 'woocommerce_review_order_after_order_total',				array( 'WGM_Template', 'remove_mwst_rate_from_cart_totals' ) );

		/**
		 * Pay Order
		 */
		add_action( 'woocommerce_pay_order_before_submit', 						array( 'WGM_Template', 'add_review_order' ) );
		add_action( 'wp', 														array( 'WGM_Template', 'pay_order_validation_of_revocation_policy' ), 19 );
		add_action( 'wp', 														array( 'WGM_Template', 'pay_order_validation_of_terms_and_conditions' ), 21 );

		/**
		 * Checkout
		 */
 		add_action( 'woocommerce_after_checkout_validation',					array( 'WGM_Template', 'avoid_free_items_in_cart' ), 0, 2 );
		add_filter( 'option_woocommerce_enable_checkout_login_reminder', 		array( 'WGM_Template', 'remove_login_from_second_checkout' ), 10, 2 );
		WGM_Email:: init_order_confirmation_mail();
		add_filter( 'woocommerce_checkout_cart_item_quantity',					array( 'WGM_Template', 'add_product_short_desc_to_checkout_title' ), 10, 3 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', 					array( 'WGM_Template', 'add_product_function_desc' ), 11, 2 );
		add_filter( 'woocommerce_order_button_text', 							array( 'WGM_Template', 'change_order_button_text' ), 1, 1 );
		add_filter( 'woocommerce_proceed_to_checkout', 							array( 'WGM_Template', 'add_cart_estimate_notice' ), 0 );
		add_filter( 'woocommerce_package_rates', 								array( 'WGM_Template', 'hide_flat_rate_shipping_when_free_is_available' ), 9, 2 );

		// don't show privacy policy text in checkout if german market checkbox is used
		if ( 'on' === get_option( 'german_market_checkbox_1_tac_pd_rp_activation', 'on' ) && 'on' === get_option( 'gm_force_term_template', 'on') ) {
			remove_action( 'woocommerce_checkout_terms_and_conditions', 			'wc_checkout_privacy_policy_text', 20 );
		}

		/**
		* My Account User Registration
		*/
		add_action( 'woocommerce_register_form', 								array( 'WGM_Template', 'my_account_registration_fields' ), 99 );
		add_filter( 'woocommerce_registration_errors', 							array( 'WGM_Template', 'my_account_registration_fields_validation_and_errors' ) );
		if ( get_option( 'gm_checkbox_5_my_account_registration_activation', 'on' ) == 'on' ) {
			remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );
		}

		/**
		* Product Review Privacy Policy
		*/
		add_filter( 'woocommerce_product_review_comment_form_args', array( 'WGM_Template', 'product_review_privacy_policy' ), 50 );
		add_filter( 'preprocess_comment', array( 'WGM_Template', 'product_review_privacy_policy_validation' ) );

		do_action( 'german_market_after_frontend_init' );

	}

	public function backend_init(  ) {

		/**
		 * Admin
		 */
		add_action( 'admin_enqueue_scripts',									array( 'Woocommerce_German_Market', 'enqueue_admin_scripts' ), 15 );
		add_action( 'admin_notices',											array( 'WGM_Installation', 'install_notice' ) );
		add_action( 'current_screen',											array( 'Woocommerce_German_Market', 'media_uploader' ) );

		/**
		* WC 3.4 Information in Customizer
		**/
		add_filter( 'gettext', array( 'WGM_Installation', 'wc_3_4_info' ), 10, 3 );

		/**
		 * WooCommerce Settings
		 */
		add_filter( 'woocommerce_email_settings',								array( 'WGM_Settings', 'imprint_email_settings' ) );

		/**
		* WooCommerce Settings 3.1
		*/
		WGM_Ui::get_instance();

		/**
		 * Edit Products
		 */
		//
		add_action( 'woocommerce_product_options_general_product_data',  		array( 'WGM_Settings', 'add_deliverytime_options_simple' ),  10 );
		add_action( 'woocommerce_product_options_general_product_data',  		array( 'WGM_Settings', 'add_sale_label_options_simple' ),  10 );
		add_action( 'woocommerce_product_after_variable_attributes',  			array( 'WGM_Settings', 'add_deliverytime_options' ),  10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes',  			array( 'WGM_Settings', 'add_sale_label_options' ),  10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes',  			array( 'WGM_Settings', 'add_requirements_options' ),  10, 3 );
		add_filter( 'woocommerce_product_data_tabs',					 	    array( 'WGM_Settings', 'add_product_write_panel_tabs' ) );
		add_action( 'woocommerce_product_data_panels',						 	array( 'WGM_Settings', 'add_product_write_panels' ) );
		add_action( 'woocommerce_process_product_meta',							array( 'WGM_Settings', 'add_process_product_meta' ), 10 );
		add_action( 'woocommerce_ajax_save_product_variations', 				array( 'WGM_Settings', 'add_process_product_meta' ), 10, 2 );
		add_filter( 'manage_edit-product_delivery_times_columns', 				array( 'WGM_Helper', 'remove_deliverytime_postcount_columns' ), 10, 2 );

		/**
		 * Refunds
		 */
		WGM_Refunds::get_instance();

		/**
		 * Plugin row meta (support link)
		 */
		add_filter( 'plugin_row_meta', 											array( 'WGM_Backend', 'plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 		array( 'WGM_Backend', 'plugin_action_links' ) );

		/**
		* New Version information legal texts
		**/
		/*
		if ( get_option( 'woocommerce_de_update_legal_texts', 'on' ) == 'on' ) {
			add_action( 'admin_notices', 												array( 'WGM_Installation', 'legal_texts_version_three_two' ) );
			add_action( 'admin_enqueue_scripts', 										array( 'WGM_Installation', 'second_checkout_version_three_two_scripts' ) );
			add_action( 'wp_ajax_woocommerce_de_dismiss_update_notice_legal_texts', 	array( 'WGM_Installation', 'legal_texts_version_dismiss' ) );
		}

		/***
		* Description for flat rate shipping costs in backend when gross prcies are activated
		**/
		add_filter( 'woocommerce_shipping_instance_form_fields_flat_rate', array( 'WGM_Settings', 'change_flat_rate_cost_description' ) );

		/***
		* Changes for WooCommerce 3.3
		**/
		add_filter( 'default_hidden_columns', array( 'WGM_Backend', 'default_hidden_columns' ), 20, 2 );

		/***
		* Notice for B2B, Atomion, Salesman
		**/
		WGM_Installation::marketpress_notices_other_products();
	}

	/**
	* Creates an Instance of this Class
	*
	* @access public
	* @since 0.0.1
	* @return Woocommerce_German_Market
	*/
	public static function get_instance() {

		if ( NULL === self::$instance ) {
			/**
			 * Initialize static vars
			 */
			self::$plugin_base_name = plugin_basename( __FILE__ );
			self::$plugin_path = plugin_dir_path( __FILE__ );

			/**
			 * Create singleton instance
			 */
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the localization
	 *
	 * @since	0.5
	 * @access	public
	 * @uses	load_plugin_textdomain, plugin_basename
	 * @return	void
	 */
	public function load_plugin_textdomain() {
		add_filter( 'plugin_locale', array( __CLASS__, 'language_for_at_and_ch' ), 10, 2 );
		load_plugin_textdomain( self::$textdomain, FALSE, dirname( self::$plugin_base_name ) . '/languages');
	}

	/**
	 * Load translation for Austrian German and Swiss German
	 * de_AT => de_DE_formal, de_CH => de_DE_formal, de_CH_informal =>
	 *
	 * @since	3.10.2
	 * @access	public
	 * @wp-hook	plugin_locale
	 * @param 	String $locale
	 * @param 	String $domain
	 * @return	String
	 */
	public static function language_for_at_and_ch( $locale, $domain = '' ) {
		if ( $domain == 'woocommerce-german-market' || $domain == 'marketpress-autoupdater' || $domain == 'marketpress-plugin-improve' ) {

			if ( get_option( 'german_market_change_plugin_locale_at_ch', 'yes') == 'yes' ) {
				if ( $locale == 'de_AT' ) {
					$locale = 'de_DE';
				} else if ( $locale == 'de_CH' ) {
					$locale = 'de_DE_formal';
				} else if ( $locale == 'de_CH_informal' ) {
					$locale = 'de_DE';
				}
			}

		}

		return $locale;
	}

	/**
	* registers the css styles
	*
	* @static
	* @uses		get_option, wp_register_style, wp_enqueue_style, plugins_url
	* @access	public
	* @return	void
	*/
	public static function load_styles() {

		// Admin styles
		if ( is_admin() ) {

			// load activation css
			if( intval( get_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ) ) ) !== 1 ) {
				wp_register_style( 'woocommerce-de-activation-style', plugins_url( '/css/activation.css', self::$plugin_base_name ), array(), Woocommerce_German_Market::$version );
				wp_enqueue_style( 'woocommerce-de-activation-style' );
			}

		// Frontend styles
		} else {

			add_action( 'wp_enqueue_scripts', array( 'Woocommerce_German_Market', 'enqueue_frontend_styles' ), 15 );

		}
	}

	/**
	* registers the css styles frontend
	*
	* @static
	* @since 	3.5.3.
	* @wp-hook  wp_enqueue_scripts
	* @access	public
	* @return	void
	*/
	public static function enqueue_frontend_styles() {

		// experimentally behaviour: include scripts and styles only for wc stuff @since GM 3.5.3
		$include_scripts = true;
		if ( apply_filters( 'gm_include_frotend_js_and_css_only_for_wc_content', false ) ) {
			$include_scripts = is_woocommerce() || is_shop() || is_cart() || is_checkout() || is_account_page();
		}

		if ( ! $include_scripts ) {
			return;
		}

		if ( get_option( 'load_woocommerce_de_standard_css', 'on' ) == 'on' ) {

			$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
			$suffix = $script_debug ? '' : '.min';

			wp_register_style( 'woocommerce-de_frontend_styles', plugins_url( '/css/frontend' . $suffix . '.css', self::$plugin_base_name ), array(), Woocommerce_German_Market::$version );
			wp_enqueue_style( 'woocommerce-de_frontend_styles' );
		}

	}

	/**
	* enqueue admin scripts and pass variables into the global scope
	*
	* @static
	* @uses		wp_enqueue_script, wp_localize_script, plugin_dir_url
	* @access 	public
	* @return	void
	*/
	public static function enqueue_admin_scripts() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

		// Beta: Do not load GM Scripts on every backend page
		// This will be extended in GM 4.0
		$always_load_gm_js = false;
		$do_not_load_gm_js = false;

		// Exceptions (Compatabilities)
		if ( function_exists( 'mfn_builder_scripts' ) ) {
			$always_load_gm_js = false;
		}

		if ( ( isset( $_REQUEST[ 'page' ] ) ) && ( $_REQUEST[ 'page' ] == 'wc-settings' ) && ( isset( $_REQUEST[ 'tab' ] ) ) && ( $_REQUEST[ 'tab' ] == 'improved_options' ) ) {
			$do_not_load_gm_js = true;
		}

		$do_not_load_gm_js = apply_filters( 'german_market_admin_do_not_load_gm_js', $do_not_load_gm_js );

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';

		if ( ! $do_not_load_gm_js ) {

			if ( $always_load_gm_js || in_array( $screen_id, wc_get_screen_ids() ) ) {

				wp_enqueue_script( 'woocommerce_de_admin', plugins_url( '/js/WooCommerce-German-Market-Admin.' . $min . 'js', self::$plugin_base_name ), array( 'jquery', 'woocommerce_admin' ), Woocommerce_German_Market::$version );
	        	wp_localize_script( 'woocommerce_de_admin', 'woocommerce_product_attributes_msg', array( 'msg' => '<small>' . sprintf( __( 'You can add more units at <a href="%s">Products &rarr; Attributes</a>.', 'woocommerce-german-market' ), admin_url() . 'edit-tags.php?taxonomy=pa_masseinheit&post_type=product' ) . '</small>' ) );

		        $sepa_settings   = get_option( 'woocommerce_german_market_sepa_direct_debit_settings', array() );
				$sepa_ajax_check = ( isset( $sepa_settings[ 'checkout_customer_can_save_payment_information' ] ) && ( 'on' == $sepa_settings[ 'checkout_customer_can_save_payment_information' ] ) ) ? 'on' : 'off';

				wp_localize_script( 'woocommerce_de_admin', 'german_market_sepa_options', array(
					'check_for_stored_payment_information' => $sepa_ajax_check,
				) );

				wp_localize_script( 'woocommerce_de_admin', 'german_market_news', array(
						'ajax_url' 	=> admin_url( 'admin-ajax.php' ),
			        	'nonce'		=> wp_create_nonce( 'german_market_news' )
				) );
	    	}

	    }

        $screen = get_current_screen();
        if ( $screen->id == 'edit-shop_order' ) {
        	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
			wp_enqueue_style( 'woocommerce_de_admin', plugins_url( '/css/backend.' . $min . 'css', Woocommerce_German_Market::$plugin_base_name ), array(), Woocommerce_German_Market::$version );
        }

        wp_enqueue_style( 'german_market_product_panel_icons', plugins_url( '/css/backend-panel-icons.' . $min . 'css', Woocommerce_German_Market::$plugin_base_name ), array(), Woocommerce_German_Market::$version );

	}

	/**
	* enqueue admin scripts for media upload
	*
	* @static
	* @wp-hook current_screen
	* @since GM 3.5.2
	* @return void
	*/
	public static function media_uploader() {

		$screen = get_current_screen();
		$import_media_script = false;

		if ( $screen->id == apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' ) ) {

			if ( isset( $_REQUEST[ 'sub_tab' ] ) ) {
				if ( $_REQUEST[ 'sub_tab' ] == 'emails' || $_REQUEST[ 'sub_tab' ] == 'images' || $_REQUEST[ 'sub_tab' ] == 'pdf_settings_delivery_note' ) {
					$import_media_script = true;
				}
			}

			if ( isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab'] == 'preferences-wcreapdf' ) {
				$import_media_script = true;
			}

			if ( $import_media_script ) {
				add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
			}

		}

	}

	/**
	* enqueue frontend scripts and pass variables into the global scope
	*
	* @static
	* @uses		wp_enqueue_script, get_option, wp_localize_script, wp_get_referer, plugin_dir_url
	* @access 	public
	* @return	void
	*/
	public static function enqueue_frontend_scripts() {

		global $page_id;

		// experimentally behaviour: include scripts and styles only for wc stuff @since GM 3.5.3
		$include_scripts = true;
		if ( apply_filters( 'gm_include_frotend_js_and_css_only_for_wc_content', false ) ) {
			$include_scripts = is_woocommerce() || is_shop() || is_cart() || is_checkout() || is_account_page();
		}

		if ( ! $include_scripts ) {
			return;
		}

		$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix = $script_debug ? '' : '.min';

		wp_enqueue_script( 'woocommerce_de_frontend', plugins_url( '/js/WooCommerce-German-Market-Frontend' . $suffix . '.js', self::$plugin_base_name ), array( 'jquery' ), Woocommerce_German_Market::$version, get_option( 'german_market_frontend_js_in_footer', 'off' ) == 'on' );

		// SEPA Direct Debit ajax
		wp_localize_script( 'woocommerce_de_frontend', 'sepa_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gm-sepa-direct-debit' ) ) );

		if ( $page_id == get_option( 'woocommerce_checkout_page_id' ) && wp_get_referer() && strstr( WGM_Helper::get_check_url(), wp_get_referer() ) ) {
			wp_localize_script( 'woocommerce_de_frontend', 'woocommerce_remove_updated_totals', array( 'val' => '1' ) );
		} else {
			wp_localize_script( 'woocommerce_de_frontend', 'woocommerce_remove_updated_totals', array( 'val' => '0' ) );
		}

		wp_localize_script( 'woocommerce_de_frontend', 'woocommerce_payment_update', array( 'val' => apply_filters( 'gm_frontend_script_payment_update', '1' ) ) );
		wp_localize_script( 'woocommerce_de_frontend', 'german_market_price_variable_products', array( 'val' => get_option( 'german_market_price_presentation_variable_products', 'gm_default' ) ) );
		wp_localize_script( 'woocommerce_de_frontend', 'german_market_price_variable_theme_extra_element', array( 'val' => apply_filters( 'german_market_price_variable_theme_extra_element', 'none' ) ) );
		wp_localize_script( 'woocommerce_de_frontend', 'german_market_legal_info_product_reviews', array( 'element' => apply_filters( 'german_market_legal_info_product_reviews_element', '.woocommerce-Reviews .commentlist' ) ) );
	}

	/**
	 * @return bool
	 */
	public static function is_frontend(){
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}


	/**
	 * Check if current WooCommerce Version is equal or above 3.3
	 *
	 * @since	2.3
	 * @author	ap
	 * @access	public
	 * @global	$woocommerce
	 * @static
	 * @return	boolean is above or not
	 */
	public static function is_wc_3_0() {
		global $woocommerce;

	   	if ( ! is_object( $woocommerce ) ) {
	   		return false;
	   	}

	  	if ( version_compare( $woocommerce->version, '5.1.0', ">=" ) ) {
	      return true;
	    }

	  return false;
	}

    /**
     * Returns plugin version
     * @since 2.3.1
     * @author ap
     * @access public
     * @static
     * @return string
     */
    public static function get_version(){
        return self::$version;
    }


    /**
     * Check if the current site is wgm checkout
     * @author ap
     * @access public
     * @static
     * @return boolean
     */
	public static function is_wgm_checkout(){
		return defined( 'WGM_CHECKOUT' );
	}


    /**
     * Called when plugin is initialized
     * @author ap
     * @access public
     * @static
     */
	public static function init(){
		self::load_styles();
		WGM_Helper::check_kleinunternehmerregelung();
		WGM_Installation::upgrade_deliverytimes();
        WGM_Installation::upgrade_system();
	}

	/**
	 * Exclude second checkout page from WooCommerce cache
	 * @param array $page_uris
	 * @access public
	 * @since 2.4.10
	 * @author ap, cb
	 * @wp-hook pre_set_transient_woocommerce_cache_excluded_uris
	 *
	 * @return array $page_uris
	 */
	public static function exclude_checkout_from_cache( $page_uris ) {
		$wgm_checkout_2     = absint( get_option( 'woocommerce_check_page_id' ) );

		if ( empty( $wgm_checkout_2 ) ) {
			return $page_uris;
		}

		$wgm_checkout_uri   = 'p=' . $wgm_checkout_2;

		if ( ! in_array( $wgm_checkout_uri , $page_uris ) ) {
			$page_uris[] = $wgm_checkout_uri ;
		}

		$page = get_post( $wgm_checkout_2 );
		if ( $page === null ) {
			return $page_uris;
		}

		$wgm_checkout_uri  ='/' . $page->post_name;

	     if (  ! in_array( $wgm_checkout_uri , $page_uris ) ) {
		    $page_uris[] = $wgm_checkout_uri ;
	    }

	    return $page_uris;
	}

	/**
	 * Exclude second checkout page from WooCommerce cache
	 * @access public
	 * @since 3.8.2
	 * @wp-hook wp
	 * @return void
	 */
	public static function exclude_checkout_from_cache_2() {

		if ( ! is_blog_installed() ) {
			return;
		}

		$page_ids = array_filter( array( absint( get_option( 'woocommerce_check_page_id' ) ) ) );

		if ( empty( $page_ids ) ) {
			return;
		}

		if ( is_page( $page_ids ) ) {

			if ( method_exists( 'WC_Cache_Helper', 'set_nocache_constants' ) ) {
				WC_Cache_Helper::set_nocache_constants();
			}

			if ( function_exists( 'nocache_headers' ) ) {
				nocache_headers();
			}

		}

	}

	/**
	* Run activation on every site
	*
	* @static
	* @access public
	* @static
	* @wp-hook admin_init
	*/
	public static function on_activate_site() {

		if ( is_admin() && 'german_market' === get_option( 'german_market_activated_plugin' )  ) {
			delete_option( 'german_market_activated_plugin' );
			WGM_Installation::on_activate();
		}
	}

	/**
	* Run activation hook
	*
	* @static
	* @access public
	* @static
	* @register_activation_hook
	*/
	public static function on_activate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ){
				switch_to_blog( $site_id );
				add_option( 'german_market_activated_plugin', 'german_market' );
				restore_current_blog();
			}
		} else {
			add_option( 'german_market_activated_plugin', 'german_market' );
		}
	}

	/**
	* Deactivation of the plugin
	*
	* @register_deactivation_hook
	* @static
	* @return void
	*/
	public static function on_deactivate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ){
				switch_to_blog( $site_id );
				self::on_deactivate_site();
				restore_current_blog();
			}
		} else {
			self::on_deactivate_site();
		}
	}

	/**
	* Deactivation of the plugin on each site of multisite
	*
	* @static
	* @return void
	*/
	public static function on_deactivate_site() {

		// Dectivate Add-ons
		$all_add_ons = WGM_Add_Ons::get_all_add_ons();

		foreach ( $all_add_ons as $add_on_id => $add_on_file ) {

			// include files
			require_once( $add_on_file );

			// get class name
			$add_on_class = WGM_Add_ons::get_class_name( $add_on_id );

			// if method 'active' exists in add-on
			if ( method_exists( $add_on_class, 'deactivate' ) ) {

				// run this method
				call_user_func( array( $add_on_class, 'deactivate' ) );
			}
		}

		if ( class_exists( 'WC_Action_Queue' ) ) {
			WC()->queue()->cancel_all( 'german_market_double_opt_in_auto_delete' );
			delete_option( 'wgm_double_opt_on_customer_registration_autodelete_is_set_up' );
		}
	}

	/**
	* run uninstall hook
	*
	* @register_uninstall_hook
	* @static
	* @access public
	* @return void
	*/
	public static function on_uninstall() {

		if ( ! defined( 'WGM_ADD_ONS_PATH' ) ) {
			define( 'WGM_ADD_ONS_PATH', untrailingslashit( plugin_dir_path( __FILE__ )  ) . DIRECTORY_SEPARATOR . 'add-ons' );
		}

		if ( class_exists( 'Woocommerce_German_Market_Light' ) ) {
			return;
		}

		define( 'WGM_UNINSTALL_ADD_ONS', TRUE );

		require_once( untrailingslashit( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'WGM_Loader.php' );
		WGM_Loader::register();

		if ( is_multisite() ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ){
				switch_to_blog( $site_id );
				self::on_uninstall_site();
				restore_current_blog();
			}
		} else {
			self::on_uninstall_site();
		}
	}

	/**
	* delete options on uninstall
	*
	* @static
	* @access public
	*/
	public static function on_uninstall_site() {

		WGM_Add_Ons::uninstall();

		// uninstall WGM options
		foreach ( WGM_Defaults::get_options() as $key => $option ) {
			delete_option( $option );
		}

		// clean all
		$prefixes = array(
			'wgm_',
			'wgm-',
			'woocommerce_de_',
			'german_market_',
			'gm_checkbox_',
			'woocommerce_german_market_',
			'gm_order_confirmation_mail_',
			'de_shop_emails_file_attachment_',
			'gm_gtin_',
			'gm_order_review_',
			'gm_checkbox_',
			'gm_force_checkout_template',
			'gm_deactivate_checkout_hooks',
			'gm_small_trading_exemption_notice',
			'load_woocommerce_de_standard_css',
			'gm_legal_information_product_',
		);

		$all_wordpress_options = wp_load_alloptions();

		foreach ( $prefixes as $prefix ) {

			$length_of_prefix = strlen( $prefix );

			foreach ( $all_wordpress_options as $option_key => $option_value ) {

				if ( substr( $option_key, 0, $length_of_prefix ) == $prefix ) {
					delete_option( $option_key );
				}

			}

		}

		$taxonomies = array(
			'product_delivery_times',
			'pa_measuring-unit',
			'product_sale_labels',
		);

		foreach ( $taxonomies as $taxonomy ) {

			$taxonomy_terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
			foreach ( $taxonomy_terms as $term ) {

				if ( is_object( $term ) && isset( $term->term_id ) ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
				
			}

		}


	}

} // end class

if ( class_exists( 'Woocommerce_German_Market' ) ) {

	add_action( 'plugins_loaded', array( 'Woocommerce_German_Market', 'get_instance' ), get_option( 'german_market_loading_priority', 20 ) );

	if ( version_compare( PHP_VERSION, '5.6.0', '>' ) ) {
		register_activation_hook( 	__FILE__, array( 'Woocommerce_German_Market', 'on_activate' ) );
		register_uninstall_hook( 	__FILE__, array( 'Woocommerce_German_Market', 'on_uninstall' ) );
		register_deactivation_hook( __FILE__, array( 'Woocommerce_German_Market', 'on_deactivate' ) );
	}

	// Plugin WP Staging
	// Restore Options for Invoice PDF Add-On & Running Invoice PDF Add-On
	add_action( 'wpstg.clone_first_run', function() {
		require_once( 'inc/WGM_Compatibilities.php' );
		WGM_Compatibilities::wp_staging_repair_invoice_and_running_invoice_number_add_on_options();
	});

	// Polylang
	add_filter( 'pll_is_ajax_on_front', function( $pll_is_ajax_on_front ) {
		require_once( plugin_dir_path( __FILE__ ) . 'inc' . DIRECTORY_SEPARATOR . 'compatibilities' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'polylang.php' );
		return WGM_Plugin_Compatibility_Polylang::polylang_is_ajax_on_front( $pll_is_ajax_on_front );
	});

	// Plugin "Weglot Translate" -> PDFs won't open in FF
	if ( 
		isset( $_REQUEST[ 'action' ] )
		&& ( 	$_REQUEST[ 'action' ] === 'woocommerce_wp_wc_invoice_pdf_view_order_invoice_download' || 
				$_REQUEST[ 'action' ] === 'woocommerce_wp_wc_invoice_pdf_view_order_refund_download' )
	) {
		add_filter( 'weglot_cancel_init', '__return_true' );
	}
}
