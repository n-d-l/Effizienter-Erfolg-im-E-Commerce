<?php
/**
 * Installation
 *
 * @author jj,ap
 */
class WGM_Installation {

	/**
	* Activation of the plugin, and create the default pages and Prefereces
	* @access	public
	* @static
	* @uses		version_compare, deactivate_plugins, wp_sprintf, update_option
	* @return	void
	*/
	public static function on_activate() {
		global $wpdb;

		if ( apply_filters( 'german_market_prevent_requirement_check', true ) ) {
		
			// check wp version
			if ( ! version_compare( $GLOBALS[ 'wp_version' ], '5.4', '>=' ) ) {
				$gm = Woocommerce_German_Market::get_instance();
				$gm->load_plugin_textdomain();
				deactivate_plugins( Woocommerce_German_Market::$plugin_filename );
				die(
					__( 'German Market requires WordPress 5.4+. Please update your WordPress installation to a recent version first, then try activating German Market again.', 'woocommerce-german-market' )
				);
			}

			// check php version
			if ( version_compare( PHP_VERSION, '7.2.0', '<' ) ) {
				deactivate_plugins( Woocommerce_German_Market::$plugin_filename ); // Deactivate ourself
				$gm = Woocommerce_German_Market::get_instance();
				$gm->load_plugin_textdomain();
				die(
					sprintf(
						__( 'WooCommerce German Market requires PHP 7.2+. Your server is currently running PHP %s. Please ask your web host to upgrade to a recent, more stable version of PHP.', 'woocommerce-german-market' ),
						PHP_VERSION
					)
				);
			}

	        // test if woocommerce is installed
	        if ( Woocommerce_German_Market::is_wc_3_0() !== true ) {
	            deactivate_plugins( Woocommerce_German_Market::$plugin_filename ); // Deactivate ourself
	            $gm = Woocommerce_German_Market::get_instance();
				$gm->load_plugin_textdomain();
	            die(
				    __( 'German Market requires WooCommerce 5.1.0+. Please install a recent version of WooCommerce first, then try activating German Market again.', 'woocommerce-german-market' )
	            );
	        }
	    }

        // set the status to installed
		update_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ), 0 );

		// install the default options
		WGM_Installation::install_default_options();

		// Activate Add-ons
		$all_add_ons = WGM_Add_Ons::get_all_add_ons();

		foreach ( $all_add_ons as $add_on_id => $add_on_file ) {
			
			// include files
			require_once( $add_on_file );

			// get class name
			$add_on_class = WGM_Add_ons::get_class_name( $add_on_id );
			
			// if method 'active' exists in add-on
			if ( method_exists( $add_on_class, 'activate' ) ) {

				// run this method
				call_user_func( array( $add_on_class, 'activate' ) );
			}
		}

		// some other plugins needs German Market to have priority 20, but there are other plugins that let german market not load if it has a priority higher than 10
		update_option( 'german_market_loading_priority', 20 );
	}

	/**
	* Handle install notice
	*
	* @access	public
	* @static
	* @uses		is_plugin_inactive, deactivate_plugins, get_option, wp_verify_nonce, update_option
	* @return	void
	*/
	public static function install_notice() {

		if ( intval( get_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ) ) ) == 1 ) {
			return;
		}

		if ( intval( get_option( 'woocommerce_de_previous_installed' ) ) == 1 ) {
			return;
		}
		
		if ( isset( $_REQUEST[ 'wgm-installation-done' ] ) && wp_verify_nonce( $_REQUEST[ 'wgm-installation-done' ], 'wgm-installation' ) ) {

			// license key
			if ( isset( $_REQUEST[ 'license-key' ] ) ) {

				$autoupdater = Woocommerce_German_Market::$autoupdater;

				$response = $autoupdater->get_license_key_checkup( trim( str_replace( ' ', '', $_REQUEST[ 'license-key' ] ) ) );

				if ( $response == 'true' ) {
					$autoupdater->reset_plugin_transient();
				} else if ( $response == 'licensekeynotfound' ) {
					$autoupdater->key = '';
					$autoupdater->activation_error = 'licensekeynotfound';
				} else if ( $response == 'noproducts' ) {
					$autoupdater->key = '';
					$autoupdater->activation_error = 'noproducts';
				} else if ( $response == 'expired' ) {
					$autoupdater->key = '';
					$autoupdater->activation_error = 'expired';
				} else if ( $response == 'urllimit' ) {
					$autoupdater->key = '';
					$autoupdater->activation_error = 'urllimit';
				}

			}

			$overwrite = isset( $_REQUEST[ 'woocommerce_de_install_de_pages_overwrite' ] );

			WGM_Helper::load_installation_language_textdomain();

			if ( isset( $_REQUEST[ 'woocommerce_de_install_default_settings' ] ) ){
				WGM_Installation::install_de_options();
			}

			if ( isset( $_REQUEST[ 'woocommerce_de_install_de_pages' ] ) ) {
				$lang = WGM_Helper::get_installation_language();
				WGM_Installation::install_default_pages( $overwrite, $lang );
			}

			if ( isset( $_REQUEST[ 'woocommerce_de_install_texts_in_default_lang' ] ) ) {
				foreach ( WGM_Helper::get_translatable_options() as $key => $option_value ) {
					if ( ! empty( $option_value ) ) {
						update_option( $key, $option_value );
					}
				}
			}

			WGM_Helper::reset_textdomain();

			// activate add-ons for legal texts
			if ( isset( $_REQUEST[ 'woocommerce_de_activate_protected_shops' ] ) ) {
				update_option( 'wgm_add_on_protected_shops', 'on' );
			}

			if ( isset( $_REQUEST[ 'woocommerce_de_activate_it_recht_kanzlei' ] ) ) {
				update_option( 'wgm_add_on_it_recht_kanzlei', 'on' );
			}

			// set woocommerce de installed
			update_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ), 1 );

			?>
			<div class="updated">
				<p><?php _e( 'Congratulations, you have successfully installed WooCommerce German Market.', 'woocommerce-german-market' ); ?></p>
			</div>
			<?php

			WGM_Helper::update_option_if_not_exist( 'woocommerce_de_previous_installed', 1 );

			return;
		}

		WGM_Template::load_template( 'install_notice.php' );
	}

	/**
	* Install default options
	*
	* @uses update_option
	* @static
	* @author jj, ap
	* @return void
	*/
	public static function install_default_options() {

		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'woocommerce_de_append_imprint_to_mail' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'woocommerce_de_append_withdraw_to_mail' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'woocommerce_de_append_terms_to_mail' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'load_woocommerce-de_standard_css' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'woocommerce_de_show_Widerrufsbelehrung' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'wgm_display_digital_revocation' ), 'on' );
		WGM_Helper::update_option_if_not_exist( WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ), 'on' );

		update_option( 'woocommerce_price_thousand_sep', '.' );
		update_option( 'woocommerce_price_decimal_sep', ',' );
	}

	/**
	* Install german specific options
	*
	* @access public
	* @author jj, ap
	* @static
	* @return void
	*/
	public static function install_de_options() {

		// set currency and country to EUR and germany
		update_option( 'woocommerce_currency', apply_filters( 'woocommerce_de_currency', WGM_Defaults::$woocommerce_de_currency ) );
		
		$base_country = get_option( 'woocommerce_default_country', 'DE' );
		if ( $base_country != 'DE' && $base_country != 'AT' ) {
			update_option( 'woocommerce_default_country', apply_filters( 'woocommerce_de_default_country', WGM_Defaults::$woocommerce_de_default_country ) );
		}
		
		// When having a fresh woocommerce2 installation, we should copy into the new database

		WGM_Installation::set_default_woocommerce2_tax_rates( WGM_Defaults::get_default_tax_rates() );

		update_option( 'woocommerce_prices_include_tax', apply_filters( 'woocommerce_de_prices_include_tax', 'yes' ) );

		// tax default settings
		// make tax calculation default
		update_option( 'woocommerce_calc_taxes', 'yes' );
		// was deleted in woocommerce2 see: https://github.com/woothemes/woocommerce/commit/9eb63a8518448fe0e99820ba924f2bee850e9ddc#L0R1031
		//update_option( 'woocommerce_display_cart_prices_excluding_tax', apply_filters( 'woocommerce_de_display_cart_prices_excluding_tax', 'no' ) );
		update_option( 'woocommerce_tax_display_cart', apply_filters( 'woocommerce_de_woocommerce_tax_display_cart', 'incl' ) );
		update_option( 'woocommerce_tax_display_shop', apply_filters( 'woocommerce_de_woocommerce_tax_display_shop', 'incl' ) );
		// was also deleted and not used by WC German Market
		//update_option( 'woocommerce_display_totals_excluding_tax', apply_filters( 'woocommerce_de_display_totals_excluding_tax', 'no' ) );

		// install product attribues
		WGM_Installation::install_default_attributes();
	}

	/**
	 * Set the default tax rates for Woocommerce
	 * Copied from Woocommerce2 Core file admin/includes/updates/woocommerce-update-2.0.php
	 *
	 * @access	public
	 * @static
	 * @global	$wpdb
	 * @since	1.1.5
	 * @param	$tax_rates
	 * @return	array
	 */
	public static function set_default_woocommerce2_tax_rates( $tax_rates ) {

        $name = "tax-rates-en.csv";

        if ( get_locale() == 'de_DE' ) {
            $name = "tax-rates-de.csv";
        }

        $base_country = get_option( 'woocommerce_default_country', 'DE' );
		if ( $base_country == 'AT' ) {
			 $name = "tax-rates-at.csv";
		}

		$file = dirname( plugin_dir_path( __FILE__ ) ) . '/import/' . $name;

		if ( ! is_file( $file ) ) {
			die( __( 'WooCommerce German Market tax rates (csv) not found. Please contact our support staff to help you get going with this.', 'woocommerce-german-market' ) );
		}

		self::import_csv( $file );
	}


    /**
     * Import rates form CSV file.
     *
     * * WC_Tax_Rate_Importer::import uses WP_Importer as a hard
     *  dependency and does various outputs. This method prevents
     *  those circumstances.
     *
     * @author dw
     * @access public
     * @static
     * @param string $file
     * @return void
     */
    public static function import_csv( $file ) {

		global $wpdb;

		$new_rates = array();

		ini_set( 'auto_detect_line_endings', '1' );

		if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

			$header = fgetcsv( $handle, 0 );

			if ( sizeof( $header ) == 10 ) {

				$loop = 0;

				while ( ( $row = fgetcsv( $handle, 0 ) ) !== FALSE ) {

					list( $country, $state, $postcode, $city, $rate, $name, $priority, $compound, $shipping, $class ) = $row;

					$country = trim( strtoupper( $country ) );
					$state   = trim( strtoupper( $state ) );

					if ( $country == '*' )
						$country = '';
					if ( $state == '*' )
						$state = '';
					if ( $class == 'standard' )
						$class = '';

					$wpdb->insert(
						$wpdb->prefix . "woocommerce_tax_rates",
						array(
							'tax_rate_country'  => $country,
							'tax_rate_state'    => $state,
							'tax_rate'          => wc_format_decimal( $rate, 4 ),
							'tax_rate_name'     => trim( $name ),
							'tax_rate_priority' => absint( $priority ),
							'tax_rate_compound' => $compound ? 1 : 0,
							'tax_rate_shipping' => $shipping ? 1 : 0,
							'tax_rate_order'    => $loop,
							'tax_rate_class'    => sanitize_title( $class )
						)
					);

					$tax_rate_id = $wpdb->insert_id;

					$postcode  = wc_clean( $postcode );
					$postcodes = explode( ';', $postcode );
					$postcodes = array_map( 'strtoupper', array_map( 'wc_clean', $postcodes ) );
					foreach( $postcodes as $postcode ) {
						if ( ! empty( $postcode ) && $postcode != '*' ) {
							$wpdb->insert(
								$wpdb->prefix . "woocommerce_tax_rate_locations",
								array(
									'location_code' => $postcode,
									'tax_rate_id'   => $tax_rate_id,
									'location_type' => 'postcode',
								)
							);
						}
					}

					$city   = wc_clean( $city );
					$cities = explode( ';', $city );
					$cities = array_map( 'strtoupper', array_map( 'wc_clean', $cities ) );
					foreach( $cities as $city ) {
						if ( ! empty( $city ) && $city != '*' ) {
							$wpdb->insert(
							$wpdb->prefix . "woocommerce_tax_rate_locations",
								array(
									'location_code' => $city,
									'tax_rate_id'   => $tax_rate_id,
									'location_type' => 'city',
								)
							);
						}
					}

					$loop ++;
			    }

			}

		    fclose( $handle );
		}
	}

	/**
	* install default product attributes
	*
	* @access public
	* @static
	* @return void
	*/
	public static function install_default_attributes() {

		global $woocommerce, $wpdb;

		foreach ( WGM_Defaults::get_default_product_attributes() as $attr ) {

			$taxonomy_labels = wc_get_attribute_taxonomy_labels();

			$id = false;

			if ( ! isset( $taxonomy_labels[ $attr[ 'attribute_name' ] ] ) ) {

				$args      = array(
					'name' => $attr[ 'attribute_label' ],
					'slug' => wc_attribute_taxonomy_name( $attr[ 'attribute_name' ] ),
					'type' => $attr[ 'attribute_type' ],
				);

				$id = wc_create_attribute( $args );

			}

			wp_schedule_single_event( time(), 'german_market_install_default_attributes_terms' );
		}
	}

	
	/**
	* install default product attributes terms
	*
	* @access public
	* @static
	* @wp-hook german_market_install_default_attributes_terms
	* @return void
	*/
	public static function install_default_attributes_terms() {

		WGM_Helper::load_installation_language_textdomain();

		foreach ( WGM_Defaults::get_default_product_attributes() as $attr ) {

			$new_tax_name = wc_attribute_taxonomy_name( $attr[ 'attribute_name' ] );

			foreach ( $attr[ 'elements' ] as $element ) {
				if ( ! term_exists( $element[ 'tag-name' ], $new_tax_name ) ) {
					$insert_term = wp_insert_term( $element[ 'tag-name' ], $new_tax_name, $element );
				}
			}
		}

		WGM_Helper::reset_textdomain();
	}

	/**
	* insert the default pages, and overwrite existing pages, if wanted.
	*
	* @author jj, ap
	* @access public
	* @static
	* @uses globals $wpdb, apply_filters, wp_insert_post, wp_update_post, update_option
	* @param bool $overwrite overwrite existing pages
	* @return void
	*/
	public static function install_default_pages( $overwrite = FALSE, $lang = 'de' ) {
		global $wpdb;
		// filter for change/add pages on auto insert on activation
		$pages = apply_filters( 'woocommerce_de_insert_pages', WGM_Helper::get_default_pages( $lang ) );

		foreach ( $pages as $page ) {
			$check_sql = "SELECT ID, post_name FROM $wpdb->posts WHERE post_name = %s LIMIT 1";

			$post_name_db = str_replace( '&', '', $page[ 'post_name' ] );
			$post_name_check = $wpdb->get_row( $wpdb->prepare( $check_sql, $post_name_db ), ARRAY_A );

			$post_id = NULL;

			// only if not page exist, add page
			if ( ( ! isset( $post_name_check[ 'post_name' ] ) ) || ( $post_name_db !== $post_name_check[ 'post_name' ] ) ) {
                $post_id = wp_insert_post( $page );
			} else {
               // overwrite the content of the old pages
               $post_id = $post_name_check[ 'ID' ];
				if( $overwrite ) {
					$page[ 'ID' ] = $post_id;
					wp_update_post( $page );
				}
			}

			// insert default option
			if( $post_id && in_array( WGM_Defaults::get_german_option_name( $page[ 'post_name' ] ), array_keys( WGM_Defaults::get_options() ) ) ) {
				update_option( WGM_Helper::get_wgm_option( $page[ 'post_name' ] ), $post_id );

			}
		}

	}

    /**
     * Update routine to imigrate old deliverytime to new format. Used for upgrade form version 2.2.3 to 2.2.4
     * @access public
     * @static
     * @author ap
     * @reutrn void
     */
    public static function upgrade_deliverytimes(){

		$option = 'wgm_upgrade_deliverytimes';

		if( !get_option( $option, false ) ) {

			add_option( $option, true );

			$terms = get_terms( 'product_delivery_times', array( 'orderby' => 'id', 'hide_empty' => 0 ) );
			$old_terms = WGM_Defaults::get_lieferzeit_strings();

			if( count( $old_terms ) > count( $terms ) ) {
				$missing = new stdClass();
				$missing->term_id = -1;
				array_unshift( $terms, $missing );
			}

			$products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1 ) );

			foreach( $products as $product ) {

				$deliverytime_index = get_post_meta( $product->ID, '_lieferzeit', TRUE );

				// Don't change the default delivery time
				if( (int) $deliverytime_index == -1 ) continue;

				if( ! array_key_exists( $deliverytime_index, $terms ) )
					$term_id = -1;
				else
					$term_id = $terms[ $deliverytime_index ]->term_id;

				update_post_meta( $product->ID, '_lieferzeit', $term_id );
			}

			$global_delivery = get_option( WGM_Helper::get_wgm_option( 'global_lieferzeit' ) );
			$term_id = $terms[ $global_delivery ]->term_id;

			update_option( WGM_Helper::get_wgm_option( 'global_lieferzeit' ), $term_id );
		}
	}

    /**
     * shows deliverytimes upgrade notice need for upgrade form 2.2.3 to 2.2.4
     * @access public
     * @static
     * @author ap
     * @return mixed
     */
    public static function upgrade_deliverytimes_notice() {

		if ( array_key_exists( 'woocommerce_de_upgrade_deliverytimes' , $_POST ) )
			update_option( 'wgm_upgrade_deliverytimes_notice_off', 1 );

		if ( get_option( 'woocommerce_de_previous_installed' ) === FALSE ) {
			update_option( 'wgm_upgrade_deliverytimes_notice_off', 1 );
			return false;
		}

		if( get_option( 'wgm_upgrade_deliverytimes_notice_off' ) )
			return false;


		$screen = get_current_screen();

		if( $screen->id != 'woocommerce_page_woocommerce_settings' )
			WGM_Template::load_template( 'deliverytimes_upgrade_notice.php' );
	}


    /**
     * upgrades for new v2.4
     */
    public static function upgrade_system(){
        if( ! get_option( 'wgm_upgrade_24', false ) ){

            //Shipping fees now have to be allways displayed
            update_option( WGM_Helper::get_wgm_option( 'woocommerce_de_show_shipping_fee_overview_single' ), 'on' );
            update_option( WGM_Helper::get_wgm_option( 'woocommerce_de_show_shipping_fee_overview' ), 'on' );

            update_option( 'wgm_upgrade_24', true );
        }

	    // Exclude checkout page from cache when updating to 2.4.10
	    if( ! get_option( 'wgm_upgrade_2410', false ) ) {
		    $wc_page_uris       = get_transient( 'woocommerce_cache_excluded_uris' );
		    $wgm_checkout_2     = absint( get_option( 'woocommerce_check_page_id' ) );
		    $wgm_checkout_uri   = 'p=' . $wgm_checkout_2;

		    $wc_page_uris[] = $wgm_checkout_uri;
		    $page = get_post( $wgm_checkout_2 );

		    if ( ! is_null( $page ) ) {
			    $wc_page_uris[] = '/' . $page->post_name;
		    }

		    set_transient( 'woocommerce_cache_excluded_uris', $wc_page_uris );
		    update_option( 'wgm_upgrade_2410', true );
	    }
    }

     /**
     * Update notice for GM 3.2: legal texts changed
     *
     * @access public
     * @static
     * @wp-hook admin_notices
     * @return void
     */
    public static function legal_texts_version_three_two() {

    	$class = 'notice notice-success is-dismissible gm-3-2-update-notice-legal-texts';
		$message = sprintf( __( '<b>Update Notice for German Market 3.2: Attention!</b> With the German Market Update 3.2 the legal texts were updated. Please check the templates and customize your shop. For more information about the update of the legal texts, please visit our <a href="%s">site</a>.', 'woocommerce-german-market' ), 'https://marketpress.de/dokumentation/german-market/rechtstexteupdate/' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 

    }

    /**
     * Dismiss Update Notice
     *
     * @access public
     * @static
     * @wp-hook wp_ajax_woocommerce_de_dismiss_update_notice_legal_texts
     * @return void
     */
    public static function legal_texts_version_dismiss() {
    	update_option( 'woocommerce_de_update_legal_texts', 'off' );
    	exit();
    }

     /**
     * WC 3.0.0+ Notice
     *
     * @access public
     * @static
     * @wp-hook admin_notices
     * @return void
     */
    public static function wc_3_0_0_notice() {
		$class = 'notice notice-error gm-3-2-2-wc-3-0-0';
		$message = __( '<b>German Market is activated, but not effective.</b> German Market requires WooCommerce 5.1.0+. Please install a recent version of WooCommerce first.', 'woocommerce-german-market' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
    }

    /**
     * PHP 5.6 Notice
     *
     * @access public
     * @static
     * @wp-hook admin_notices
     * @return void
     */
    public static function php_5_6_notice() {
		$class = 'notice notice-error gm-3-2-3-php-5-6';
		$message = sprintf( __( '<b>German Market is activated, but not effective.</b> German Market requires PHP 7.2+. Your server is currently running PHP %s. Please ask your web host to upgrade to a recent, more stable version of PHP.', 'woocommerce-german-market' ), PHP_VERSION );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
    }

    public static function wc_3_4_info( $translated_text, $text, $domain ) {

    	if ( $domain == 'woocommerce' ) {

    		if ( 	$text == 'Optionally add some text about your store privacy policy to show during checkout.' || 
    				$text == 'Optionally add some text for the terms checkbox that customers must accept.' ) {

    				$german_market_settings_page = admin_url( 'admin.php?page=german-market&tab=general&sub_tab=checkout_checkboxes' );

    					$translated_text .= '<br /><b>' . sprintf( __( 'Possibly, this customizer setting is not effective because it has been adopted and expanded by German Market. It can be found %shere%s.', 'woocommerce-german-market' ), '<a href="' . $german_market_settings_page . '">', '</a>' ) . '</b/>';
    	
    		} else if ( $text == 'This section controls the display of your website privacy policy. The privacy notices below will not show up unless a privacy page is first set.' ) {

    			$german_market_settings_page = admin_url( 'admin.php?page=german-market&tab=general&sub_tab=checkout_checkboxes' );
    			
    			$translated_text .= '<br /><br /><b>' . sprintf( __( 'Possibly, these settings are not effective because they have been adopted and expanded by German Market. They can be found %shere%s.', 'woocommerce-german-market' ), '<a href="' . $german_market_settings_page . '">', '</a>' ) . '</b>';

    		} 

    	};

    	return $translated_text;

    }

    /**
	 * Run notices for other MarketPress products
	 *
	 * @since 		3.22
	 * @return 		void
	 */
    public static function marketpress_notices_other_products() {

    	$first_run = get_option( 'german_market_first_marketpress_notices', '' );
		if ( empty( $first_run ) ) {

			$first_run = time();
			update_option( 'german_market_first_marketpress_notices', $first_run );
		}

		if ( intval( $first_run ) + 2 * HOUR_IN_SECONDS > time() ) {
			return;
		}

    	if ( function_exists( 'get_current_user_id' ) ) {
    		$user_id = get_current_user_id();
    		if ( 
    			( $user_id > 0 ) &&
    			( get_user_meta( $user_id, 'german_market_marketpress_notices_other_products', true ) !== '1.0' )
    		){

    			add_action( 'admin_notices', 							array( 'WGM_Installation', 'marketpress_notices_b2b_and_atomion' ) );
   				add_action( 'admin_enqueue_scripts', 					array( 'WGM_Installation', 'backend_script_market_press_notices' ) );
   				add_action( 'wp_ajax_gm_dismiss_marketpress_notice', 	array( 'WGM_Installation', 'backend_script_marketpress_dismiss_notices' ) );

    		}
    	}

    }

    /**
	 * Add Admin notices German Market and B2B Market
	 *
	 * @since 		3.7.1
	 * @wp-hook 	admin_notices
	 * @return 		void
	 */
	public static function marketpress_notices_b2b_and_atomion() {

		$b2b_exists 		= false;
		$atomion_exists 	= false;
		$salesman_exists	= false;

		$old_message_dismissed = get_option( 'german_market_notice_b2b_atomion_in_gm', 'on' ) === '1.0';

		if ( ! function_exists( 'is_plugin_inactive' ) ) {
		    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_dir( WP_CONTENT_DIR . '/themes/wordpress-theme-atomion' ) ) {
			$atomion_exists = true;
		} else if ( function_exists( 'atomion_setup' ) ) {
			$atomion_exists = true;
		}

		if ( is_plugin_inactive( 'b2b-market/b2b-market.php' ) && ( is_dir( WP_PLUGIN_DIR . '/b2b-market' ) ) ) {
		    $b2b_exists = true;
		} else if ( method_exists( 'BM', 'set_activate_option' ) ) {
			$b2b_exists = true;
		}

		if ( is_plugin_inactive( 'salesman/salesman.php' ) && ( is_dir( WP_PLUGIN_DIR . '/salesman' ) ) ) {
		    $salesman_exists = true;
		} else if ( class_exists( 'MarketPress_Salesman' ) ) {
			$salesman_exists = true;
		}

		$text = '';
		$domain = 'com';
		$lang = get_locale();

		if ( substr( $lang, 0, 2 ) == 'de' ) {
			$domain = 'de';
		}

		$b2b_link = '<a href="https://marketpress.' . $domain . '/shop/plugins/b2b-market/?mp-notice-from=gm" target="_blank">B2B Market</a>';
		$atomion_link = '<a href="https://marketpress.' . $domain . '/shop/themes/wordpress-theme-atomion/?mp-notice-from=gm" target="_blank">Atomion</a>';
		$salesman_link = '<a href="https://marketpress.' . $domain . '/shop/plugins/salesman/?mp-notice-from=gm" target="_blank">Salesman</a>';

		if ( ! $salesman_exists ) {

			if ( 
				( ( ! $b2b_exists ) && ( ! $atomion_exists ) && ( $old_message_dismissed ) ) ||
				( ( $b2b_exists || $atomion_exists ) )
			){

				$text = sprintf( 
							__( 'You use our plugin <strong>German Market</strong>. That\'s great! Take a look at our new plugin <strong>%s</strong>, which combines features to increase customer loyalty, usability and traffic. Bonus points, recommendation programme and much more: selling has never been easier.', 'woocommerce-german-market' ), 
							$salesman_link
						 );

			} else {

				$text = sprintf( 
							__( 'You use our plugin <strong>German Market</strong>. That\'s great! Take a look at our plugins <strong>%s</strong> and <strong>%s</strong> as well as our theme <strong>%s</strong>, they fit perfectly.', 'woocommerce-german-market' ), 
							$salesman_link,
							$b2b_link,
							$atomion_link
						 );

			}
			
		} else {
			if ( ! $old_message_dismissed ) {
				if ( ( ! $b2b_exists ) && ( ! $atomion_exists ) ) {
					
					$text = sprintf( 
							__( 'You use our plugin <strong>German Market</strong>. That\'s great! Take a look at the plugin <strong>%s</strong> and the theme <strong>%s</strong>, they fit perfectly.', 'woocommerce-german-market' ), 
							$b2b_link,
							$atomion_link
						 );
				
				}
			}
		}

		if ( ! empty( $text ) ) {
			?>
		    <div class="notice notice-warning is-dismissible marketpress-atomion-gm-b2b-notice-in-gm">
		        <p><?php echo $text; ?></p>
		    </div>
		    <?php
		}
		
	}

	/**
	* Load JavaScript so you can dismiss the MarketPress Plugin Notice
	*
	* @since 		3.7.1
	* @wp-hook 		admin_enqueue_scripts
	* @return 		void
	*/
	public static function backend_script_market_press_notices() {
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
		wp_enqueue_script( 'gm_marketpress_notices', plugins_url( '/js/WooCommerce-German-Marke-MarketPress-Notices.' . $min . 'js', Woocommerce_German_Market::$plugin_base_name ), array( 'jquery' ), Woocommerce_German_Market::$version );
	    wp_localize_script( 'gm_marketpress_notices', 'gm_marketpress_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce'	=> wp_create_nonce( 'gm_marketpress_notices' ) ) );
	}

	/**
	* Dismiss MarketPress Notice
	*
	* @since 		3.7.1
	* @wp-hook 		wp_ajax_gm_dismiss_marketprss_notice
	* @return 		void
	*/
	public static function backend_script_marketpress_dismiss_notices() {
		
		if ( check_ajax_referer( 'gm_marketpress_notices', 'nonce' ) ) {

			update_option( 'german_market_notice_b2b_atomion_in_gm', '1.0' );

			if ( function_exists( 'get_current_user_id' ) ) {
	    		$user_id = get_current_user_id();
	    		if ( $user_id > 0 ) {
					update_user_meta( $user_id, 'german_market_marketpress_notices_other_products', '1.0' );
	    		}
	    	}

	    	 echo 'success';

	    } else {
	    	echo 'error';
	    }

	    exit();
	}

}
