<?php
/**
 * Feature Name: Options Page
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Backend Settings German Market 3.1
*
* wp-hook woocommerce_de_ui_left_menu_items
* @param Array $items
* @return Array
*/
function wcvat_woocommerce_de_ui_left_menu_items( $items ) {

	$items[ 270 ] = array( 
				'title'		=> __( 'EU VAT Number Check', 'woocommerce-german-market' ),
				'slug'		=> 'eu_vat_number_check',
				'callback'	=> 'wcvat_woocommerce_de_ui_render_options',
				'options'	=> 'yes'
		);

	return $items;
}

/**
* Render Options for global
* 
* @return void
*/
function wcvat_woocommerce_de_ui_render_options() {

	$settings = array(

		array(
			'title' => __( 'VATIN Check', 'woocommerce-german-market' ),
			'type'  => 'title',
			'desc'  => __( 'A <a href="http://en.wikipedia.org/wiki/VAT_identification_number" target="_blank">value-added tax identification number</a> is an identifier used in many countries, including the countries of the European Union, for value added tax purposes. Common abbrevations in English are <em>VAT identification number</em> or <em>VATIN</em>.<br /><br />Any VAT number allocated inside the European Union (EU) can be validated online at the official website of the European Commission: <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank">VIES VAT</a>. Validation confirms whether a number is currently allocated, and provides the name or other identifying details of the allocated individual or entity.<br /><br />This add-on allows you to check the European VAT number of your customers during checkout, in order to obtain a tax free intracommunity delivery if necessary.', 'woocommerce-german-market' ),
			'id'    => 'vat_options'
		),
		
		array(
			'title'    => __( 'Field Label', 'woocommerce-german-market' ),
			'desc_tip'     => __( 'Depending on your WordPress Theme, this label will be displayed before or after the VATIN field during checkout.', 'woocommerce-german-market' ),
			'id'       => 'vat_options_label',
			'default'  => __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ),
			'type'     => 'text',
			'css'      => 'width: 400px;',
			'autoload' => FALSE
		),

		array(
			'title'    => __( 'Notice: "Tax free intracommunity delivery"', 'woocommerce-german-market' ),
			'desc_tip'     => __( 'Notice that is shown after the customer\'s order', 'woocommerce-german-market' ),
			'id'       => 'vat_options_notice',
			'default'  => __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ),
			'type'     => 'textarea',
			'css'      => 'width: 400px;',
			'autoload' => FALSE
		),

		array(
			'title'    => __( 'Notice: "Tax-exempt export delivery"', 'woocommerce-german-market' ),
			'desc_tip' => __( "Notice if the customer's billing country is a non-EU country", 'woocommerce-german-market' ),
			'id'       => 'vat_options_non_eu_notice',
			'default'  => __( 'Tax-exempt export delivery', 'woocommerce-german-market' ),
			'type'     => 'textarea',
			'css'      => 'width: 400px;',
			'autoload' => FALSE
		),

		array(
			'title'    => __( 'VAT number is editable in "My Account" page and user profile in backend', 'woocommerce-german-market' ),
			'desc_tip' => __( 'With this option you can decide if the customer can edit the VAT number on the "My Account" page. In addition, as an admin, it is possible to edit the VAT number in the user profile (backend). The saved VAT number in the profile is used on the checkout page to pre-fill the VAT number field.', 'woocommerce-german-market' ),
			'id'       => 'vat_options_billing_vat_editable',
			'default'  => 'off',
			'type'     => 'wgm_ui_checkbox',
		),

		array(
			'id'   => 'vat_options',
			'type' => 'sectionend',
		),

		array(
			'title' => __( 'Requester - Vat Number', 'woocommerce-german-market' ),
			'type'  => 'title',
			'desc'  => __( 'To verify a vat number of one your customers, the service of "VIES VAT" will be used. If you enter your own vat number, it will be passed to the service and return a "consultation number". If you have enabled logging (see options below), you will see this "consultation number" in the order note. Get more information here: <a href="https://ec.europa.eu/taxation_customs/vies/help.html" targe="_blank">https://ec.europa.eu/taxation_customs/vies/help.html</a>.', 'woocommerce-german-market' ),
			'id'    => 'vat_requester'
		),

		array( 
			'name'		=> __( 'Requester - Member State', 'woocommerce-german-market' ),
			'type'		=> 'select',
			'default'	=> '-',
			'options'	=> array(
				'-' => '-', 
				'AT' => 'AT', 'BE' => 'BE', 'BG' => 'BG', 'CY' => 'CY', 'CZ' => 'CZ',
				'DE' => 'DE', 'DK' => 'DK', 'EE' => 'EE', 'EL' => 'EL', 'ES' => 'ES',
				'FI' => 'FI', 'FR' => 'FR', 'HR' => 'HR', 'HU' => 'HU', 'IE' => 'IE',
				'IT' => 'IT', 'LT' => 'LT', 'LU' => 'LU', 'LV' => 'LV', 'MT' => 'MT',
				'NL' => 'NL', 'PL' => 'PL', 'PT' => 'PT', 'RO' => 'RO', 'SE' => 'SE',
				'SI' => 'SI', 'SK' => 'SK', 'XI' => 'XI',
			),
			'id'		=> 'german_market_vat_requester_member_state',
			'desc_tip'	=> __( 'Choose your member state, these are the first two letters of your vat number', 'woocommerce-german-market' ),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
		),

		array( 
			'name'		=> __( 'Requester - Vat Number', 'woocommerce-german-market' ),
			'type'		=> 'text',
			'default'	=> '',
			'id'		=> 'german_market_vat_requester_vat_number',
			'desc_tip'	=> __( 'Enter your vat number without your member state (first two letters of your complete vat number). For instance, if your vat number is "DE123456789", enter "123456789" in this field', 'woocommerce-german-market' ),
		),

		array(
			'id'   => 'vat_requester',
			'type' => 'sectionend',
		),

		array(
			'title' => __( 'Logging', 'woocommerce-german-market' ),
			'type'  => 'title',
			'id'	=> 'vat_logging',
			'desc'	=> sprintf( 
				__( 'To validate the VAT numbers, the service of %s is used. If logging is enabled, the response of this API is stored as a private order note. Additionally, any errors are listed in the WooCommerce logs.', 'woocommerce-german-market' ),
				'<a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank">https://ec.europa.eu/taxation_customs/vies/</a>'
			),
		),

		array(
			'name'     => __( 'Enable Logging', 'woocommerce-german-market' ),
			'id'       => 'german_market_vat_logging',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		),

		array(
			'id'   => 'vat_logging',
			'type' => 'sectionend',
		),

		array(
			'title' => __( 'Display VAT number field', 'woocommerce-german-market' ),
			'type'  => 'title',
			'desc'  => __( 'By default, the field for the vat number is displayed on the checkout page if the customer\'s country is an EU country that does not correspond to your shop base country. Otherwise, the field is not displayed.', 'woocommerce-german-market' )
					. '<br><br>' . sprintf( 
							__( 'The "customer\'s country" is either the country of the billing address or the country of the delivery address. This depends on your selected WooCommerce settings "Calculate tax based on", which you can set in the menu %s.', 'woocommerce-german-market' ),
							'"<em>' . __( 'WooCommerce -> Settings -> Tax', 'woocommerce-german-market' ) . '</em>"'
						) 
					. '<br><br>' . __( 'With the following setting you can change the default behaviour of when the tax number field should be displayed.', 'woocommerce-german-market' ),
			'id'    => 'vat_display_vat_number_field'
		),

		array(
			'name'		=> __( 'Display VAT number field', 'woocommerce-german-market' ),
			'type'		=> 'select',
			'options'	=> array(
				'eu_optional'      => __( 'VAT field is displayed as optional if customer\'s country is an EU country (and not the base country)', 'woocommerce-german-market' ),
				'eu_mandatory'     => __( 'VAT field is displayed as required if customer\'s country is an EU country (and not the base country)', 'woocommerce-german-market' ),
				'always_optional'  => __( 'Always display VAT field as optional', 'woocommerce-german-market' ),
				'always_mandatory' => __( 'Always display VAT field as required', 'woocommerce-german-market' ),
				'hide_vat_field'   => __( 'Deactivated', 'woocommerce-german-market' ),
			),
			'default'	=> 'eu_optional',
			'id'		=> 'german_market_display_vat_number_field',
			'desc_tip'	=> __( 'If the country to be checked is not an EU country, but the field is reuqired to fill in, no full validation of the vat number takes place, it is only checked if there is an entry in the field.', 'woocommerce-german-market' ),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
		),

		array(
			'id'   => 'vat_display_vat_number_field',
			'type' => 'sectionend',
		),

		array(
			'title' => __( 'Backend Order Table', 'woocommerce-german-market' ),
			'type'  => 'title',
			'id'	=> 'vat_backend_order_table'
		),

		array(
			'name'     => sprintf( __( 'Show %s below customer name', 'woocommerce-german-market' ), get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) ),
			'desc'     => '',
			'id'       => 'vat_options_backend_show_vatid',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
		),

		array(
			'name'     => __( 'Show vat info below total amount', 'woocommerce-german-market' ),
			'desc'     => '',
			'id'       => 'vat_options_backend_show_vat_info',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
		),

		array(
			'id'   => 'vat_backend_order_table',
			'type' => 'sectionend',
		),

		/*
		array(
			'title' => __( 'United Kingdom (UK) in EU', 'woocommerce-german-market' ),
			'type'  => 'title',
			'id'	=> 'vat_united_kingdom'
		),

		array(
			'name'     => __( 'Treat the United Kingdom as an EU country', 'woocommerce-german-market' ),
			'id'       => 'german_market_vat_options_united_kingdom',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
		),

		array(
			'id'   => 'vat_united_kingdom',
			'type' => 'sectionend',
		),
		*/

	);

	$settings = apply_filters( 'wcvat_woocommerce_de_ui_render_options', $settings );
	return( $settings );

}
