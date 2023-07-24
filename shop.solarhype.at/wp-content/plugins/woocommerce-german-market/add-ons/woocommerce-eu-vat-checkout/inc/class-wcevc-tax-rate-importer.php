<?php

/**
 * Class WCEVC_Tax_Rate_Importer
 */
class WCEVC_Tax_Rate_Importer{

	private $wc_tax_class;
	private $tax_rate_to_import;
	private $delete_existing_rates;

	/**
	* Simple Constructor
	*
	* @access private
	* @return void
	*/
	public function __construct( $wc_tax_class = '', $tax_rate_to_import = 'standard', $delete_existing_rates = 'no' ) { 
		$this->wc_tax_class = $wc_tax_class;
		$this->tax_rate_to_import = $tax_rate_to_import;
		$this->delete_existing_rates = $delete_existing_rates;
	}

	/**
	* Delete tax rates of the tax class $this->wc_tax_class()
	*
	* @access private
	* @return void
	*/
	public function import() {

		if ( 'yes' === $this->delete_existing_rates ) {
			$this->delete_rates_of_tax_class();
		}

		$rates = $this->get_tax_rates();

		foreach ( $rates as $country_key => $rate ) {

			$the_country_key = substr( $country_key, 0, 2 );

			$vat_label = get_option( WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) );

			if ( ! isset( $rate[ 'postcode' ] ) ) {
				if ( 'standard' === $this->tax_rate_to_import ) {
					$percent = isset( $rate[ 'standard' ] ) ? $rate[ 'standard' ] : false;
				} else if ( 'reduced' === $this->tax_rate_to_import ) {
					$percent 	= isset( $rate[ 'reduced' ] ) ? $rate[ 'reduced' ] : false;
					$vat_label	= __( 'red. VAT', 'woocommerce-german-market' );
				} else if ( 'de_standard' === $this->tax_rate_to_import ) {
					$percent 	= 19;
				} else if ( 'de_reduced' === $this->tax_rate_to_import ) {
					$percent 	= 7;
					$vat_label	= __( 'red. VAT', 'woocommerce-german-market' );
				} else if ( 'at_standard' === $this->tax_rate_to_import ) {
					$percent 	= 20;
				} else if ( 'at_reduced' === $this->tax_rate_to_import ) {
					$percent 	= 10;
					$vat_label	= __( 'red. VAT', 'woocommerce-german-market' );
				}
			} else {
				$percent = isset( $rate[ 'standard' ] ) ? $rate[ 'standard' ] : false;
			}

			if ( false === $percent ) {
				continue;
			}

			$tax_rate = apply_filters( 'wcevc_tax_rate_importer_tax_rate_to_import', array(
				
				'tax_rate_country'  => $the_country_key,
				'tax_rate_state'    => '*',
				'tax_rate'          => $percent,
				'tax_rate_name'     => $vat_label,
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => $this->wc_tax_class,

			), $rate, $this->wc_tax_class, $this->tax_rate_to_import );

			$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

			if ( isset( $rate[ 'postcode' ] ) ) {
				WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $rate[ 'postcode' ] );
			}
		}
	}

	/**
	* Delete tax rates of the tax class $this->wc_tax_class()
	*
	* @access private
	* @return void
	*/
	private function delete_rates_of_tax_class() {

		$rates = WC_Tax::get_rates_for_tax_class( $this->wc_tax_class );
		if ( is_array( $rates ) ) {
			foreach ( $rates as $key => $rate ) {
				WC_Tax::_delete_tax_rate( $key );
			}
		}
	}

	/**
	* Get EU Rates
	*
	* @access private
	* @return Array
	*/
	private function get_tax_rates(){

		return apply_filters( 'wcevc_tax_rate_importer_eu_rates', array(

			'BE' => array(
						'standard'	=> 21.00,
						'reduced'	=> 12.00,
					),

			'BG' => array(
						'standard'	=> 20.00,
						'reduced'	=> 9.00,
					),

			'CZ' => array(
						'standard'	=> 21.00,
						'reduced'	=> 15.00,
					),

			'DK' => array(
						'standard'	=> 25.00,
						'reduced'	=> 25.00,
					),

			'DE' => array(
						'standard'	=> 19.00,
						'reduced'	=> 7.00,
					),

			'DE_helgoland' => array(
						'postcode'	=> '27498',
						'standard'	=> 0.0,
					),

			'DE_buesingen' => array(
						'postcode'	=> '78266',
						'standard'	=> 0.0,
					),

			'EE' => array(
						'standard'	=> 20.00,
						'reduced'	=> 9.00,
					),

			'GR' => array(
						'standard'	=> 24.00,
						'reduced'	=> 13.00,
					),

			'ES' => array(
						'standard'	=> 21.00,
						'reduced'	=> 10.00,
					),

			'ES_canary_islands' => array(
						'postcode'	=> '35*',
						'standard'	=> 0.00,
					),

			'ES_canary_islands_2' => array(
						'postcode'	=> '38*',
						'standard'	=> 0.00,
					),

			'ES_ceuta' => array(
						'postcode'	=> '51*',
						'standard'	=> 0.00,
					),

			'ES_melilla' => array(
						'postcode'	=> '32*',
						'standard'	=> 0.00,
					),

			'FR' => array(
						'standard'	=> 20.00,
						'reduced'	=> 10.00,
					),

			'FR_guadeloupe' => array(
						'postcode'	=> '971*',
						'standard'	=> 0.00,
					),

			'FR_martinique' => array(
						'postcode'	=> '972*',
						'standard'	=> 0.00,
					),

			'FR_french_guiana' => array(
						'postcode'	=> '973*',
						'standard'	=> 0.00,
					),

			'FR_reunion' => array(
						'postcode'	=> '974*',
						'standard'	=> 0.00,
					),

			'FR_mayotte' => array(
						'postcode'	=> '976*',
						'standard'	=> 0.00,
					),

			'GB' => array(
						'postcode'	=> 'BT*',
						'standard'	=> 20.00,
					),

			'HR' => array(
						'standard'	=> 25.00,
						'reduced'	=> 13.00,
					),

			'IE' => array(
						'standard'	=> 23.00,
						'reduced'	=> 13.50,
					),

			'IT' => array(
						'standard'	=> 22.00,
						'reduced'	=> 10.00,
					),

			'IT_livigno' => array(
						'postcode'	=> '22060',
						'standard'	=> 0.00,
					),

			'IT_Lugano' => array(
						'postcode'	=> '23030',
						'standard'	=> 0.00,
					),

			'CY' => array(
						'standard'	=> 19.00,
						'reduced'	=> 9.00,
					),

			'LV' => array(
						'standard'	=> 21.00,
						'reduced'	=> 12.00,
					),

			'LT' => array(
						'standard'	=> 21.00,
						'reduced'	=> 9.00,
					),

			'LU' => array(
						'standard'	=> 17.00,
						'reduced'	=> 8.00,
					),

			'HU' => array(
						'standard'	=> 27.00,
						'reduced'	=> 18.00,
					),

			'MT' => array(
						'standard'	=> 18.00,
						'reduced'	=> 7.00,
					),

			'NL' => array(
						'standard'	=> 21.00,
						'reduced'	=> 9.00,
					),

			'AT' => array(
						'standard'	=> 20.00,
						'reduced'	=> 10.00,
					),

			'PL' => array(
						'standard'	=> 23.00,
						'reduced'	=> 8.00,
					),

			'PT' => array(
						'standard'	=> 23.00,
						'reduced'	=> 13.00,
					),

			'RO' => array(
						'standard'	=> 19.00,
						'reduced'	=> 9.00,
					),

			'SI' => array(
						'standard'	=> 22.00,
						'reduced'	=> 9.50,
					),

			'SK' => array(
						'standard'	=> 20.00,
						'reduced'	=> 10.00,
					),

			'FI' => array(
						'standard'	=> 24.00,
						'reduced'	=> 14.00,
					),

			'FI_asland_inslands' => array(
						'postcode'	=> '22*',
						'standard'	=> 0.00,
					),

			'SE' => array(
						'standard'	=> 25.00,
						'reduced'	=> 12.00,
					),

		));
	}
}
