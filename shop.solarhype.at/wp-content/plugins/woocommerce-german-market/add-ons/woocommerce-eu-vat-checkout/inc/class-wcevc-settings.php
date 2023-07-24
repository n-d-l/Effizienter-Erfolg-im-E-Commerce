<?php

/**
 * Class WCEVC_Settings
 */
class WCEVC_Settings {

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @var null|WCEVC_Settings
	 */
	private static $instance = NULL;

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * @return WCEVC_Settings
	 */
	public static function get_instance() {

		if ( self::$instance === NULL ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return  WCEVC_Settings
	 */
	private function __construct() {

		// disable/enable plugin for downloadable products
		$this->options[]= array(
			'title'   => __( 'EU VAT Checkout', 'woocommerce-german-market' ),
			'id'      => 'wcevc_enabled_wgm',
			'desc'    => '<br />' . __( 'Fixate gross prices and re-calculate taxes during checkout according to tax rates set for billing country.', 'woocommerce-german-market' ),
			'default' => 'off',
			'type'    => 'select',
			'options' => array(
				'off'           	=> __( 'Disable', 'woocommerce-german-market' ),
				'downloadable'  	=> __( 'Enable for downloadable products, for EU countries', 'woocommerce-german-market' ),
				'all_products_eu'	=> __( 'Enable for all products, for EU countries', 'woocommerce-german-market' ),
				'all_products'		=> __( 'Enable for all products', 'woocommerce-german-market' ),
			)
		);

	}

	/**
	 * Add German Market Submenu Menu
	 *
	 * @wp-hook woocommerce_de_ui_left_menu_items
	 * @param Array $items
	 * @return Array
	 */
	public static function german_market_menu( $items ) {

		$items[ 271 ] = array( 
				'title'		=> __( 'EU VAT Checkout', 'woocommerce-german-market' ),
				'slug'		=> 'eu_vat_checkout',
				'submenu'	=> array(

					array(
						'title'		=> __( 'General', 'woocommerce-german-market' ),
						'slug'		=> 'general',
						'callback'	=> array( __CLASS__, 'general_settings' ),
						'options'	=> 'yes'
					),

					array(
						'title'				=> __( 'Import EU Tax Rates', 'woocommerce-german-market' ),
						'slug'				=> 'import_eu_tax_rates',
						'callback'			=> array( __CLASS__, 'import_eu_tax_rates' ),
						'options'			=> 'yes',
						'save_button_text' 	=> __( 'Import Tax Rates', 'woocommerce-german-market' ),
						'hide_top_button'	=> true,
					),

					array(
						'title'				=> __( 'Import EU Tax Rates by CN Number', 'woocommerce-german-market' ),
						'slug'				=> 'import_cn_number',
						'callback'			=> array( __CLASS__, 'import_cn_number' ),
						'options'			=> 'yes',
						'save_button_text' 	=> __( 'Check CN number and get tax rates', 'woocommerce-german-market' ),
						'hide_top_button'	=> true,
					),

					array(
						'title'				=> __( 'Generalized tax output', 'woocommerce-german-market' ),
						'slug'				=> 'generalized_tax_output',
						'callback'			=> array( __CLASS__, 'generalized_tax_output' ),
						'options'			=> 'yes',
					),

				)
			);

		return $items;
	}

	/**
	* Render Options for general settings
	* 
	* @access public
	* @return Array
	*/
	public static function general_settings() {
		$settings = array();

		$requirements = '<strong>' . sprintf( 
			__( 'For the following setting of the add-on to be effective, the setting %s must be set to the option %s.', 'woocommerce-german-market' ),
			'"<em>' . __( 'WooCommerce -> Settings -> Tax -> Prices entered with tax', 'woocommerce-german-market' ) . '</em>"',
			'"<em>' . __( 'Yes, I will enter prices inclusive of tax', 'woocommerce-german-market' )  . '</em>"'
		) . '</strong>';

		$desciption = __( 'If you calculate different taxes for different countries in your store, the prices may vary depending on the tax rate. To prevent prices from changing when a different tax rate is applied during the order process (e.g. because the customer changes the billing or delivery country at checkout), you can use the following settings.', 'woocommerce-german-market' );

		$settings[]	= array( 
			'title' 	=> __( 'General Tax Settings', 'woocommerce-german-market' ), 
			'type' 		=> 'title', 
			'id' 		=> 'wcevc_general_settings',
			'desc'    	=> $requirements . '<br><br>' . $desciption,
		);

		$desc_setting = sprintf( __( 'If you choose the setting <em>"Enable for all products"</em>, the final price will be fixed for buyers of all countries (regardless of the tax rate). This behavior is still experimental in WooCommerce, you can find more information <a href="%s" target="_blank">here</a>. If you use this setting, the following example results:', 'woocommerce-german-market' ), 'https://github.com/woocommerce/woocommerce/wiki/How-Taxes-Work-in-WooCommerce#prices-including-tax---experimental-behavior' ); 

		$desc_setting .= 	'<br><br><em>' . 
							__( 'Germany: 119 Euro incl. 19% VAT', 'woocommerce-german-market' ) . '<br>' . 
							__( 'Austria: 119 Euro incl. 20% VAT', 'woocommerce-german-market' ) . '<br>' .
							__( 'USA: 119 Euro (without VAT)', 'woocommerce-german-market' ) . 
							'</em><br><br>';

		$desc_setting .= __( 'If you use the setting <em>"Enable for all products, for EU countries"</em>, the behavior just explained will only be applied if the country for which the taxes are calculated (billing country or shipping country - depending on the WooCommerce setting) is a member state of the EU. ', 'woocommerce-german-market' ); 

		$desc_setting .= 	'<br><br><em>' . 
							__( 'Germany: 119 Euro incl. 19% VAT', 'woocommerce-german-market' ) . '<br>' . 
							__( 'Austria: 119 Euro incl. 20% VAT', 'woocommerce-german-market' ) . '<br>' .
							__( 'USA: 100 Euro (without VAT)', 'woocommerce-german-market' ) . 
							'</em><br><br>';

		$settings[] = array(
				'name' 		=> __( 'Fix Prices', 'woocommerce-german-market' ),
				'id'   		=> 'wcevc_enabled_wgm',
				'type' 		=> 'select',
				'default' 	=> 'off',
				'options' 	=> array(
					'off'           	=> __( 'Disable', 'woocommerce-german-market' ),
					'downloadable'  	=> __( 'Enable for downloadable products, for EU countries', 'woocommerce-german-market' ),
					'all_products_eu'	=> __( 'Enable for all products, for EU countries', 'woocommerce-german-market' ),
					'all_products'		=> __( 'Enable for all products', 'woocommerce-german-market' ),
				),
				'class'		=> 'wc-enhanced-select',
				'css'		=> 'min-width: 350px;',
				'desc'		=> $desc_setting,
			);

		$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_general_settings' );

		$settings = apply_filters( 'wgm_wcevc_admin_general_settings', $settings );
		return $settings;
	}

	/**
	* Run "import eu tax rates"
	* 
	* @access public
	* @wp-hook woocommerce_de_ui_update_options
	* @return void
	*/
	public static function import_tax_rates() {

		if ( isset( $_REQUEST[ 'wcevc_import_woocommerce_tax_class' ] ) ) {
			
			// Do not save these settings
			delete_option( 'wcevc_import_woocommerce_tax_class' );
			delete_option( 'wcevc_import_tax_rate_to_be_imported' );
			delete_option( 'wcevc_import_tax_rate_delete_existing' );

			require_once( 'class-wcevc-tax-rate-importer.php' );

			$wc_tax_class 			= $_REQUEST[ 'wcevc_import_woocommerce_tax_class' ];
			$rate_to_be_imported 	= $_REQUEST[ 'wcevc_import_tax_rate_to_be_imported' ];
			$delete_existing 		= isset( $_REQUEST[ 'wcevc_import_tax_rate_delete_existing' ] ) ? 'yes' : 'no';

			$importer = new WCEVC_Tax_Rate_Importer( $wc_tax_class, $rate_to_be_imported, $delete_existing );
			$importer->import();

			// build a message with link to tax class with imported rates
			$url = add_query_arg(
				array(
					'section'	=> $wc_tax_class,
					'tab'		=> 'tax',
					'page' 		=> 'wc-settings',
				),
				admin_url( 'admin.php' )
			);

			if ( 'standard' === $wc_tax_class ) {
				$menu = __( 'WooCommerce -> Settings -> Taxes -> Standard rates', 'woocommerce-german-market' );
			} else {
				$menu = __( 'WooCommerce -> Settings -> Taxes', 'woocommerce-german-market' );
				$tax_class = WC_Tax::get_tax_class_by( 'slug', $wc_tax_class );
				if ( isset( $tax_class[ 'name' ] ) ) {
					 $menu .= ' -> ' . $tax_class[ 'name' ];
				}
			}

			?>
			<div class="notice-wgm notice-success">
				<p><?php echo sprintf( __( 'The tax rates have been imported and can be seen at <a href="%s">%s</a>','woocommerce-german-market' ), $url, $menu ); ?></p>
			</div>
			<?php

			// don't show "settings have been saved"
			add_filter( 'woocommerce_de_ui_show_settings_have_been_save_message', '__return_false' );
		}
	}

	/**
	* Render Options for "import eu tax rates"
	* 
	* @access public
	* @return Array
	*/
	public static function import_eu_tax_rates() {

		$settings = array();

		$desciption = sprintf( 
			__( 'Using these options you can import the tax rates of the member states of the EU into your WooCommerce tax classes . These tax rates were taken from the <a href="%s" target="_blank">Europa website</a> (as of May 2021). You can find them in <a href="%s" target="_blank">this PDF document</a>. No liability is assumed for the accuracy, timeliness and completeness of the tax rates. Please double check the tax rates after importing them yourself.', 'woocommerce-german-market' ),
			'https://ec.europa.eu/taxation_customs/business/vat_en',
			'https://ec.europa.eu/taxation_customs/system/files/2021-06/vat_rates_en.pdf'
		);

		$example = '<strong>' . __( 'Example of use:', 'woocommerce-german-market' ) . '</strong><br><em>' . __( 'If you ship to all EU countries and want to calculate the respective tax in these countries as well, because you exceed the general delivery threshold and participate in the OSS procedure, you import the standard EU rates into your standard WooCommerce rate.', 'woocommerce-german-market' ) . '</em>';

		$settings[]	= array( 
			'title' 	=> __( 'Import EU Tax Rates', 'woocommerce-german-market' ), 
			'type' 		=> 'title', 
			'id' 		=> 'wcevc_import_eu_rates',
			'desc'    	=> $desciption . '<br><br>' . $example,
		);

		$tax_classes = WC_Tax::get_tax_rate_classes();

		$wc_tax_classes = array(
			'standard'	=> __( 'Standard', 'woocommerce-german-market' )
		);

		foreach ( $tax_classes  as $tax_class ) {
			$wc_tax_classes[ $tax_class->slug ] = $tax_class->name;
		}

		$settings[]	= array( 
			'title' 	=> __( 'WooCommerce Tax Class', 'woocommerce-german-market' ), 
			'type' 		=> 'select', 
			'id' 		=> 'wcevc_import_woocommerce_tax_class',
			'desc'    	=> __( 'Select the tax class to which you want to import the tax rates.', 'woocommerce-german-market' ),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
			'options'	=> $wc_tax_classes,
		);

		$settings[]	= array( 
			'title' 	=> __( 'Tax rate to be imported', 'woocommerce-german-market' ), 
			'type' 		=> 'select', 
			'id' 		=> 'wcevc_import_tax_rate_to_be_imported',
			'desc'    	=> __( 'Select the tax rate to import.', 'woocommerce-german-market' ),
			'options'	=> array(
				'standard'		=> __( 'Standard EU rates', 'woocommerce-german-market' ),
				'reduced'		=> __( 'Reduced EU rates', 'woocommerce-german-market' ),
				'de_standard'	=> __( 'German standard tax rate (19%) for all EU countries', 'woocommerce-german-market' ),
				'de_reduced'	=> __( 'German reduced tax rate (7%) rate for all EU countries', 'woocommerce-german-market' ),
				'at_standard'	=> __( 'Austrian standard tax rate (20%) for all EU countries', 'woocommerce-german-market' ),
				'at_reduced'	=> __( 'Austrian reduced tax rate (10%) rate for all EU countries', 'woocommerce-german-market' ),
			),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
		);

		$settings[]	= array( 
			'title' 	=> __( 'Delete existing rates in the tax class before import', 'woocommerce-german-market' ), 
			'type' 		=> 'wgm_ui_checkbox', 
			'id' 		=> 'wcevc_import_tax_rate_delete_existing',
			'default'	=> 'off',
		);

		$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_import_eu_rates' );

		$settings = apply_filters( 'wgm_wcevc_admin_import_eu_tax_rates_settings ', $settings );
		return $settings;
	}

	/**
	* Run "CN number check"
	* 
	* @access public
	* @wp-hook woocommerce_de_ui_update_options
	* @return void
	*/
	public static function cn_number_handler() {

		if ( isset( $_REQUEST[ 'wcevc_import_cn_number' ] ) ) {
			
			// Do not save these settings
			delete_option( 'wcevc_import_cn_number' );

			// don't show "settings have been saved"
			add_filter( 'woocommerce_de_ui_show_settings_have_been_save_message', '__return_false' );

		} else if ( isset( $_REQUEST[ 'wcevc_import_cn_number_woocommerce_tax_class' ] ) ) {

			require_once( 'class-wcevc-cn-number-importer.php' );
			WCEVC_CN_Number_Importer::import_rates();
		}
	}

	/**
	* Render Options for "Import EU Tax Rates by CN Number"
	* 
	* @access public
	* @return Array
	*/
	public static function import_cn_number() {

		$settings = array();
		$load_default_settings = true;

		if ( isset( $_REQUEST[ 'wcevc_import_cn_number' ] ) ) {
			
			$cn_number 	= esc_attr( $_REQUEST[ 'wcevc_import_cn_number' ] );
			
			if ( isset( $_REQUEST[ 'wcevc_import_cn_number_search_all_categories' ] ) ) {
				$categories = self::get_all_cn_categories();
			} else if ( isset( $_REQUEST[ 'wcevc_import_cn_number_categories' ] ) ) {
				$categories	= $_REQUEST[ 'wcevc_import_cn_number_categories' ];
			} else {
				$categories = array();
			}
			
			require_once( 'class-wcevc-cn-number-importer.php' );
			$cn_number_importer = new WCEVC_CN_Number_Importer( $cn_number, $categories );
			$rates = $cn_number_importer->get_eu_api_respond_for_cn_number();
	
			if ( ! isset( $rates[ 'error' ] ) ) {

				// new text for "save button"
				add_filter( 'wgm_ui_save_button_text', function( $text ) {
					return __( 'Import Tax Rates', 'woocommerce-german-market' );
				});

				$description = sprintf( __( 'In this menu you will see a selection field for each EU member state, in which each option represents a tax rate that was returned by the EU server as a response to the search query regarding the CN number %s. Additional information is included for each option. Check this information and select the correct tax rate. If you see an exclamation point next to the country name, there are multiple tax rates to choose from.', 'woocommerce-german-market' ), '<strong>' . $cn_number . '</strong>' );

				$description .= '<br><br>' . __( 'Note that currently by default both standard tax rates and tax rates which apply to special regions are always output with as response of the EU interface, since these rates can be applied by default for all categories and CN numbers.', 'woocommerce-german-market' );

				$description .= '<br><br>' . __( 'Furthermore, you can then select the tax name for each EU member state. This tax name will then be used in the selected tax class of WooCommerce.', 'woocommerce-german-market' );

				$description .= '<br><br>' . __( 'In the bottom part you can select the tax class of WooCommerce to which you want to import the selected tax rates.', 'woocommerce-german-market' );

				$settings[]	= array( 
					'title' 	=> __( 'Rates for EU Countries', 'woocommerce-german-market' ), 
					'type' 		=> 'title', 
					'id' 		=> 'wcevc_import_cn_number_eu_countries',
					'desc'		=> $description,
				);

				$countries = WC()->countries->countries;

				foreach ( $rates as $country_code => $infos ) {

					// watch out for the country code of Greece
					$used_country_code = 'EL' === $country_code ? 'GR' : $country_code;
					$options = array();

					$title_country_name = $used_country_code;

					// add full country name
					if ( isset( $countries[ $used_country_code ] ) ) {
						$title_country_name .= ' - ' . $countries[ $used_country_code ];
					}

					// add exclamation mark if there is a choice of rates
					if ( count( $infos ) > 1 ) {
						$title_country_name .= ' !';
					}

					// make options for select
					foreach ( $infos as $counter => $rate_info ) {
						
						$rate_in_percent = $rate_info[ 'rate' ];
						$options_key = $rate_in_percent . '_' . $counter;

						$options[ $options_key ] = trim( $rate_in_percent . '% ' . $rate_info[ 'type' ] );
						
						// if there is more than one rate, add some addional information from eu api
						if ( true ) {

							if ( ! empty( $rate_info[ 'description' ] ) ) {
								$options[ $options_key ] .= ' ' . $rate_info[ 'description' ];
							}

							if ( ! empty( $rate_info[ 'comment' ] ) ) {
								$options[ $options_key ] .= ' ' . $rate_info[ 'comment' ];
							}

							if ( ! empty( $rate_info[ 'cn_codes' ] ) ) {
								$options[ $options_key ] .= ' - CN Codes: ' . implode( ', ', $rate_info[ 'cn_codes' ] );
							}

						}
					}

					if ( empty( $options ) ) {
						continue;
					}

					ksort( $options, SORT_NUMERIC );

					$settings[]	= array( 
						'title' 	=> $title_country_name, 
						'type' 		=> 'select', 
						'id' 		=> 'wcevc_import_tax_nc_number_' . $country_code,
						'options'	=> $options,
						'class'		=> 'wc-enhanced-select',
						'css'		=> 'min-width: 350px;',
					);

					$default_vat_label = get_option( WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) );

					$first_array_key = array_key_first( $options );
					if ( str_replace( 'REDUCED', '', $options[ $first_array_key ] ) != $options[ $first_array_key ] ) {
						$default_vat_label = __( 'red. VAT', 'woocommerce-german-market' );
					}

					$settings[]	= array( 
						'title' 	=> $used_country_code . ' ' . __( 'Tax name', 'woocommerce-german-market' ), 
						'type' 		=> 'text', 
						'id' 		=> 'wcevc_import_nc_number_' . $country_code . '_label',
						'default'	=> $default_vat_label,
					);

				}

				$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_import_cn_number_eu_countries' );

				// add option to tax class
				$tax_classes = WC_Tax::get_tax_rate_classes();

				$wc_tax_classes = array(
					'standard'	=> __( 'Standard', 'woocommerce-german-market' )
				);

				foreach ( $tax_classes  as $tax_class ) {
					$wc_tax_classes[ $tax_class->slug ] = $tax_class->name;
				}

				$settings[]	= array( 
					'title' 	=> __( 'WooCommerce Tax Class', 'woocommerce-german-market' ), 
					'type' 		=> 'title', 
					'id' 		=> 'wcevc_import_cn_number_wc_tax_class',
				);

				$settings[]	= array( 
					'title' 	=> __( 'Tax Class', 'woocommerce-german-market' ), 
					'type' 		=> 'select', 
					'id' 		=> 'wcevc_import_cn_number_woocommerce_tax_class',
					'desc'    	=> __( 'Select the tax class to which you want to import the tax rates.', 'woocommerce-german-market' ),
					'class'		=> 'wc-enhanced-select',
					'css'		=> 'min-width: 350px;',
					'options'	=> $wc_tax_classes,
				);

				$settings[]	= array( 
					'title' 	=> __( 'Delete existing rates in the tax class before import', 'woocommerce-german-market' ), 
					'type' 		=> 'wgm_ui_checkbox', 
					'id' 		=> 'wcevc_import_cn_number_delete_existing',
					'default'	=> 'off',
				);

				$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_import_cn_number_wc_tax_class' );

				$load_default_settings = false;
			} else {
				
				// eror handling
				if ( is_array( $rates[ 'error' ] ) && isset( $rates[ 'error' ][ 'message' ] ) ) {
					?>
					<div class="notice-wgm notice-error">
						<p><?php echo esc_attr( $rates[ 'error' ][ 'message' ] ); ?></p>
					</div>
					<?php
				} else {

					$error = $rates[ 'error' ];
					$error_text = '';

					if ( is_object( $error ) ) {

						if ( method_exists( $error, 'getCode' ) ) {
							
							if ( isset( $error->detail->retrieveVatRatesFaultMsg->error->code ) ) {
								$code = $error->detail->retrieveVatRatesFaultMsg->error->code;
							} else {
								$code = $error->getCode();
							}


							if ( "00004" === $code ) {
								$error_text = sprintf( __( 'The CN Code "%s" does not exist.', 'woocommerce-german-market' ), esc_attr( $_REQUEST[ 'wcevc_import_cn_number' ] ) );
							} else {

								if ( method_exists( $error, 'getMessage' ) ) {
									$error_text = __( 'There was the following error during the API query:', 'woocommerce-german-market' ) . '<br>' . $error->getMessage();
									if ( isset( $error->detail->retrieveVatRatesFaultMsg->error ) ) {
										$error_text .= '<br>' . print_r( $error->detail->retrieveVatRatesFaultMsg->error, true );
									}
								}

							}
						}
					}

					if ( ! empty( $error_text ) ) {
						?>
						<div class="notice-wgm notice-error">
							<p><?php echo $error_text; ?></p>
						</div>
						<?php
					}
				}
			}
		} 

		if ( $load_default_settings ) {


			$description = sprintf( __( 'With the help of this menu, you can determine the respective tax rates of the EU member states on the basis of a <a href="%s" target="_blank">CN number</a> of a product.', 'woocommerce-german-market' ), __( 'https://ec.europa.eu/taxation_customs/business/calculation-customs-duties/what-is-common-customs-tariff/combined-nomenclature_en', 'woocommerce-german-market' ) );

			$description .= '<br><br>' . __( 'For this purpose we use the service of:', 'woocommerce-german-market' );
			$description .= ' <a href="https://ec.europa.eu/taxation_customs/tedb/vatSearchForm.html" target="_blank">https://ec.europa.eu/taxation_customs/tedb/vatSearchForm.html</a>';

			$description .= '<br><br>' . __( 'To use the service, enter a CN code in this menu. Since not all member states have yet fully entered the database, it is currently still necessary that you also select at least one suitable category. In the future, a direct search will also be possible with the CN number alone. However, upon request, some EU countries have not yet completely transmitted the data.', 'woocommerce-german-market' );

			$description .= '<br><br>' . __( 'After you have clicked on "Check CN number and get tax rates", you will be taken to a menu where you will be given a selection of tax rates for each EU member state, which were returned by the EU interface as a result of the search. You can then check them, select them and then import them into a special tax class that you have created in WooCommerce.', 'woocommerce-german-market' );

			// Default settings
			$settings[]	= array( 
				'title' 	=> __( 'Import EU Tax Rates by CN Number', 'woocommerce-german-market' ), 
				'type' 		=> 'title', 
				'id' 		=> 'wcevc_import_cn_number',
				'desc'    	=> $description,
			);

			$settings[]	= array( 
				'title' 	=> __( 'CN Number', 'woocommerce-german-market' ), 
				'type' 		=> 'text', 
				'id' 		=> 'wcevc_import_cn_number',
				'class'		=> 'wcevc_import_cn_number_input',
				'default'	=> isset( $_REQUEST[ 'wcevc_import_cn_number' ] ) ? esc_attr( $_REQUEST[ 'wcevc_import_cn_number' ] ) : '',
			);

			$categories = self::get_all_cn_categories();

			$settings[] = array(
				'title'		=> __( 'Categories', 'woocommerce-german-market' ),
				'type' 		=> 'multiselect', 
				'id' 		=> 'wcevc_import_cn_number_categories',
				'options'	=> array_combine( $categories, $categories ),
				'class'		=> 'wc-enhanced-select wcevc_import_cn_number_categories_select',
				'css'		=> 'min-width: 350px;',
			);

			$settings[] = array(
				'title'		=> __( 'Search in all Categories', 'woocommerce-german-market' ),
				'type' 		=> 'wgm_ui_checkbox', 
				'id' 		=> 'wcevc_import_cn_number_search_all_categories',
				'default'	=> 'off',
			);

			$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_import_cn_number' );

			$settings = apply_filters( 'wgm_wcevc_admin_import_eu_tax_rates_settings ', $settings );
		}

		return $settings;
	}

	/**
	* Renrurn all CN Categories
	* 
	* @access public
	* @return Array
	*/
	public static function get_all_cn_categories() {
		return array(
					'100_YEARS_OLD', 'ACCOMMODATION', 'AGRICULTURAL_PRODUCTION', 'BICYCLES_REPAIR', 'BROADCASTING_SERVICES', 'CERAMICS',
				 	'CHILDREN_CAR_SEATS', 'CLOTHING_REPAIR', 'CULTURAL_EVENTS', 'DOMESTIC_CARE' , 'ENAMELS' , 'FOODSTUFFS' , 'HAIRDRESSING', 
				 	'HOUSING_PROVISION', 'IMPRESSIONS', 'LOAN_LIBRARIES', 'MEDICAL_CARE', 'MEDICAL_EQUIPMENT', 'NEWSPAPERS', 'PARKING',
				 	'PERIODICALS', 'PHARMACEUTICAL_PRODUCTS', 'PHOTOGRAPHS', 'PICTURES', 'POSTAGE', 'PRIVATE_DWELLINGS', 'REGION' , 
				 	'RESTAURANT' , 'SCULPTURE_CASTS' , 'SCULPTURES' , 'SHOES_REPAIR' , 'SOCIAL_WELLBEING' , 'SPORTING_EVENTS' , 'SPORTING_FACILITIES' , 
				 	'STREET_CLEANING' , 'SUPER_TEMPORARY' , 'SUPPLY_ELECTRICITY' , 'SUPPLY_GAS' , 'SUPPLY_HEATING' , 'SUPPLY_WATER' , 'TAPESTRIES' ,
				 	'TEMPORARY' , 'TRANSPORT_PASSENGERS' , 'UNDERTAKERS_SERVICES' , 'WINDOW_CLEANING' , 'WRITERS_SERVICES' , 'ZERO_RATE' ,
				 	'ZERO_REDUCED_RATE' , 'ZOOLOGICAL'
				 );
	}

	/**
	* Render Options for "Generalized tax output"
	* 
	* @access public
	* @return Array
	*/
	public static function generalized_tax_output() {

		$settings = array();

		$settings[]	= array( 
			'title' 	=> __( 'Generalized Tax Output', 'woocommerce-german-market' ), 
			'type' 		=> 'title', 
			'id' 		=> 'wcevc_general_tax_output_activation_title',
			'desc'		=> __( 'By default, the output of the VAT in German Market is as follows: "Includes 1,99 € VAT (19%)". When this setting is activated, the output is replaced by the generalized sentence "Incl. VAT", which you can also change in the following settings. This setting does not affect the invoice PDFs of German Market.', 'woocommerce-german-market')
		);

		$settings[]	= array( 
			'title' 	=> __( 'Activation', 'woocommerce-german-market' ), 
			'type' 		=> 'select', 
			'id' 		=> 'wcevc_general_tax_output_activation',
			'options'	=> array(
				'off'						=> __( 'Deactivated', 'woocommerce-german-market' ),
				'product_level'				=> __( 'Activated on product level', 'woocommerce-german-market' ),
				'product_level_and_cart'	=> __( 'Activated on product level and cart', 'woocommerce-german-market' ),
				'on'						=> __( 'Activate everywhere', 'woocommerce-german-market' ),
			),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
			'default'	=> 'off',
			'desc_tip'	=> __( '"Product level" means shop pages and product pages. If you choose to activate the generalized tax output everywhere, you can set up exceptions for specific emails.', 'woocommerce-german-market' ),
		);

		$settings[] = array(
			'name'		 => __( 'General tax output', 'woocommerce-german-market' ),
			'type'		 => 'text',
			'default'  	 => __( 'Incl. tax', 'woocommerce-german-market' ),
			'id'  		 => 'wcevc_general_tax_output_text_string',
		);

		$all_mails 			= WC()->mailer()->get_emails();
		$exception_wc_mails = apply_filters( 'wcevc_generalized_tax_output_mail_exception_wc_mails_pre', array(
			'customer_new_account', 'customer_reset_password'
		) );
		
		$mail_exception_options = apply_filters( 'wcevc_generalized_tax_output_mail_exception_options_pre', array(
			'customer_order_confirmation' => __( 'Order Confirmation', 'woocommerce-german-market' )
		) );

		foreach ( $all_mails as $email ) {
			
			if ( ! in_array( $email->id, $exception_wc_mails ) ) {
				$mail_exception_options[ $email->id ] = $email->title;
			}

		}

		$settings[]	= array( 
			'title' 	=> __( 'Exceptions for Emails', 'woocommerce-german-market' ), 
			'type' 		=> 'multiselect', 
			'id' 		=> 'wcevc_general_tax_output_exceptions_for_emails',
			'options'	=> $mail_exception_options,
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width: 350px;',
			'desc_tip'	=> __( 'For emails selected here, the generalized tax output will not be applied. The default output will be used.', 'woocommerce-german-market' ),
		);

		$settings[]	= array( 'type' => 'sectionend', 'id' => 'wcevc_general_tax_output_activation_title' );

		$settings = apply_filters( 'wgm_wcevc_admin_generalized_tax_output_settings ', $settings );
		return $settings;
	}

	/**
	 * Set the default options of our Plugins on activation
	 *
	 * @return void
	 */
	public function add_default_options() {
		foreach ( $this->options as $option ) {
			add_option( $option[ 'id' ], $option[ 'default' ] );
		}
	}
}
