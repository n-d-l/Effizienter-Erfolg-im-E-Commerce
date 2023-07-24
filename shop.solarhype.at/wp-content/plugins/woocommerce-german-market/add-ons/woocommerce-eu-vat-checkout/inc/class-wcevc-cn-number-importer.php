<?php

/**
 * Class WCEVC_CN_Number_Importer
 */
class WCEVC_CN_Number_Importer{

	private $cn_number;
	private $categories;

	/**
	* Simple Constructor
	*
	* @access private
	* @return void
	*/
	public function __construct( $cn_number, $categories ) { 
		$this->cn_number = $cn_number;
		$this->categories = $categories;
	}

	/**
	* Connect to eu server and get tax rates for a cn number
	*
	* @access public
	* @return Array
	*/
	public function get_eu_api_respond_for_cn_number() {

		$wc = WC();
		$eu_countries = $wc->countries->get_european_union_countries();
		$key_of_greece = array_search( 'GR', $eu_countries );
		
		if ( false !== $key_of_greece ) {
			$eu_countries[ $key_of_greece ] = 'EL';
		}
		
		$parameter = array( 'memberStates' => $eu_countries, 'situationOn' => wp_date( 'Y-m-d' ), 'cnCodes' => array( $this->cn_number ) );
		if ( ! empty( $this->categories ) ) {
			$parameter[ 'categories' ] = $this->categories;
		}

		$response_array = array();
		
		try {

			$soap = new SoapClient( 'https://ec.europa.eu/taxation_customs/tedb/ws/VatRetrievalService.wsdl' );
			$result = $soap->retrieveVatRates( $parameter );
			if ( isset( $result->vatRateResults ) ) {
				foreach ( $result->vatRateResults as $result ) {

					$country_code 	= $result->memberState;
					$rate 			= $result->rate->value;
					$comment 		= isset( $result->comment ) ? esc_attr( strip_tags( $result->comment ) ) : '';
					$description	= isset( $result->category->description ) ? esc_attr( strip_tags( $result->category->description ) ) : '';
					$type 			= isset( $result->type ) ? $result->type : '';

					$cn_codes 		= array();

					if ( isset( $result->cnCodes->code ) ) {
						if ( is_array( $result->cnCodes->code ) ) {
							foreach ( $result->cnCodes->code as $the_code ) {
								if ( isset( $the_code->value ) ) {
									$cn_codes[] = $the_code->value;
								}
							}
						}
					}

					if ( ! isset( $response_array[ $country_code ] ) ) {
						$response_array[ $country_code ] = array();
					}

					$response_array[ $country_code ][] = array( 'rate' => $rate, 'comment' => $comment, 'description' => $description, 'type' => $type, 'cn_codes' => $cn_codes );
				}

				ksort( $response_array );
			}

			delete_option( 'wcevc_import_cn_number_categories' );
			delete_option( 'wcevc_import_cn_number' );
			delete_option( 'wcevc_import_cn_number_search_all_categories' );
			
		} catch ( Exception $e ) {
			$response_array[ 'error' ] = $e;
		}

		return $response_array;
	}

	/**
	* Import chosen tax rates
	*
	* @access public
	* @return void
	*/
	public static function import_rates() {

		if ( wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {

			$tax_class 	= $_REQUEST[ 'wcevc_import_cn_number_woocommerce_tax_class' ];

			// delete tax classes 
			if ( isset( $_REQUEST[ 'wcevc_import_cn_number_delete_existing' ] ) ) {
				$rates = WC_Tax::get_rates_for_tax_class( $tax_class );
				if ( is_array( $rates ) ) {
					foreach ( $rates as $key => $rate ) {
						WC_Tax::_delete_tax_rate( $key );
					}
				}
			}

			foreach ( $_REQUEST as $key => $select_field_value_with_percent ) {
				
				// get rate percent from select value (20_0, 19_2, 15_1)
				$percent_to_import_array = explode( '_', $select_field_value_with_percent );
				
				if ( isset( $percent_to_import_array[ 0 ] ) ) {
					$percent = $percent_to_import_array[ 0 ];
				} else {
					$percent = $select_field_value_with_percent;
				}

				if ( str_replace( 'wcevc_import_tax_nc_number_', '', $key ) !== $key ) {

					$country_code = str_replace( 'wcevc_import_tax_nc_number_', '', $key );

					$key_for_vat_label = 'wcevc_import_nc_number_' . $country_code . '_label';
					$vat_label = isset( $_REQUEST[ $key_for_vat_label ] ) ? $_REQUEST[ $key_for_vat_label ] : '';
					if ( empty( $vat_label ) ){
						$vat_label = get_option( WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) );
					}

					$tax_rate = apply_filters( 'wcevc_tax_rate_cn_number_importer_tax_rate_to_import', array(
				
						'tax_rate_country'  => $country_code,
						'tax_rate_state'    => '*',
						'tax_rate'          => $percent,
						'tax_rate_name'     => $vat_label,
						'tax_rate_priority' => 1,
						'tax_rate_compound' => 0,
						'tax_rate_shipping' => 1,
						'tax_rate_order'    => 1,
						'tax_rate_class'    => $tax_class,

					), $key, $tax_class );

					$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

				}

				delete_option( $key );
			}

			delete_option( 'wcevc_import_cn_number_woocommerce_tax_class' );
			delete_option( 'wcevc_import_cn_number_categories' );

			// build a message with link to tax class with imported rates
			$url = add_query_arg(
				array(

					'section'	=> $tax_class,
					'tab'		=> 'tax',
					'page' 		=> 'wc-settings',
				),
				admin_url( 'admin.php' )
			);

			if ( 'standard' === $tax_class ) {
				$menu = __( 'WooCommerce -> Settings -> Taxes -> Standard rates', 'woocommerce-german-market' );
			} else {
				$menu = __( 'WooCommerce -> Settings -> Taxes', 'woocommerce-german-market' );
				$tax_class = WC_Tax::get_tax_class_by( 'slug', $tax_class );
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
}
