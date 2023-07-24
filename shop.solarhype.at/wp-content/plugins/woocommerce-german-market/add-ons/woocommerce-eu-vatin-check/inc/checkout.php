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
 * Add the VAT field to checkout before order notes.
 *
 * @wp-hook woocommerce_before_order_notes
 *
 * @param WC_Checkout $checkout
 *
 * @return void
 */
function wcvat_woocommerce_add_vat_field( $checkout ) {

	$default = apply_filters( 'wcvat_woocommerce_billing_fields_vat_default', '' );
	$vat     = '';

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$vat          = get_user_meta( $current_user->ID, 'billing_vat', true );
	}

	woocommerce_form_field( 'billing_vat', array(
		'type'        => 'text',
		'class'       => array( 'form-row' ),
		'label'       => get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ),
		'placeholder' => get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ),
		'required'    => apply_filters( 'wcvat_vat_field_is_required', false ),
		'default'     => $default,
	), apply_filters( 'wcvat_woocommerce_billing_fields_vat_value', $vat ) );

}

/**
 * Shows the VAT in order received
 *
 * @wp-hook	woocommerce_order_details_after_customer_details
 * @param	object $order
 * @return	void
 */
function wcvat_order_details_after_customer_details( $order ) {

	$vat = $order->get_meta( 'billing_vat' );

	if ( ! empty( trim( $vat ) ) ) {
		echo apply_filters( 'wcvat_woocommerce_email_after_order_table_vat_id_markup', get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) . ': ' . $vat, $vat );
	}
}

/**
 * Validates the user input and
 * loads the VAT Validator to
 * check it.
 *
 * @return void
 */
function wcvat_woocommerce_after_checkout_validation() {

    $vat_required = false;

    // Checking if VAT number is mandatory needed

    $base_location     = wc_get_base_location();
	$base_country      = $base_location[ 'country' ];
	$billing_country   = ( isset( $_POST[ 'billing_country' ] ) && '' != $_POST[ 'billing_country' ] ) ? $_POST[ 'billing_country' ] : '';
    $shipping_country  = ( isset( $_POST[ 'shipping_country' ] ) && '' != $_POST[ 'shipping_country' ] ) ? $_POST[ 'shipping_country' ] : '';
	$eu_countries      = WC()->countries->get_european_union_countries();
	$tax_based_on      = apply_filters( 'wcvat_tax_based_on', get_option( 'woocommerce_tax_based_on', 'billing' ) );
	$display_vat_field = apply_filters( 'wcvat_display_vat_field', get_option( 'german_market_display_vat_number_field', 'eu_optional' ) );

	// Check if shipping country will be the billing country if 'ship to different address' is not used
	if ( '' == $shipping_country || ! isset( $_POST[ 'ship_to_different_address' ] ) ) {
	    $shipping_country = $billing_country;
    }

	if ( 'always_mandatory' == $display_vat_field ) {
	    $vat_required = true;
		if ( ( 'billing' == $tax_based_on && ! in_array( $billing_country, $eu_countries ) ) || ( 'shipping' == $tax_based_on && ! in_array( $shipping_country, $eu_countries ) ) ) {
			$vat_required = false;
		}
    } else if ( 'eu_mandatory' == $display_vat_field ) {
	    // check if country is an EU country
	    if ( 'billing' == $tax_based_on && $billing_country != $base_country ) {
		    if ( in_array( $billing_country, $eu_countries ) ) {
			    $vat_required = true;
		    }
	    } else if ( 'shipping' == $tax_based_on && $shipping_country != $base_country ) {
            if ( in_array( $shipping_country, $eu_countries ) ) {
                $vat_required = true;
            }
        }
    } else {
        $vat_required = false;
    }

    $vat_required = apply_filters( 'wcvat_vat_field_is_required', $vat_required );

	if ( ( ! isset( $_POST[ 'billing_vat' ] ) || '' == trim( $_POST[ 'billing_vat' ] ) ) && ( true === $vat_required || 'always_mandatory' == $display_vat_field ) ) {

		wc_add_notice( __( 'Please enter a valid VAT Identification Number registered in a country of the EU.', 'woocommerce-german-market' ), 'error' );
		add_filter( 'gm_checkout_validation_first_checkout', 'wcvat_validation_first_checkout' );

	} else if ( isset( $_POST[ 'billing_vat' ] ) && '' != $_POST[ 'billing_vat' ] ) {

		// set the input
		$input = array( strtoupper( substr( $_POST[ 'billing_vat' ], 0, 2 ) ), strtoupper( substr( $_POST[ 'billing_vat' ], 2 ) ) );

		// set country to billing country by default
		$country = $billing_country;
        if ( 'shipping' == $tax_based_on ) {
	        // set country to shipping country
	        $country = $shipping_country;
        }

		// Validate the input
		if ( ! class_exists( 'WC_VAT_Validator' ) ) {
			require_once 'class-wc-vat-validator.php';
		}

		$validator = new WC_VAT_Validator( $input, $country );

		if ( ! $validator->is_valid() ) {
			
			if ( $validator->has_errors() ) {
				
				if ( $validator->get_last_error_code() != '200' ) {
					wc_add_notice( $validator->get_last_error_message(), 'error' );
				} else {
					wc_add_notice( __( 'Please enter a valid VAT Identification Number registered in a country of the EU.', 'woocommerce-german-market' ), 'error' );
				}
			

				add_filter( 'gm_checkout_validation_first_checkout', 'wcvat_validation_first_checkout' );

			}
		}
	}
}

/**
 * If 2nd Checkout is enabled in German Market
 *
 * @wp-hook	gm_checkout_validation_first_checkout
 * @param	Integer $error_count
 * @return	Integer
 */
function wcvat_validation_first_checkout( $error_count ) {

	$error_count++;

	return $error_count;
}

/**
 * Adds the VAT Number to the E-Mails
 *
 * NOT IN USE SINCE v.3.9.1.9
 *
 * @wp-hook	woocommerce_email_order_meta_keys
 * @param	array $keys
 * @return	array $keys
 */
function wcvat_custom_checkout_field_order_meta_keys( $keys ) {

	$keys[ get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) ] = 'billing_vat';

	return $keys;
}

/**
 * Notice: "Tax free intracommunity delivery" and VAT ID in emails
 *
 * @wp-hook	woocommerce_email_after_order_table
 * @param	WC_Order $order
 * @return	void
 */
function wcvat_woocommerce_email_after_order_table( $order ) { 
	
	$notice       = '';
	$eu_countries = WC()->countries->get_european_union_countries();
	$country      = $order->get_billing_country();
	$postcode 	  = $order->get_billing_postcode();

	if ( 'shipping' == apply_filters( 'wcvat_tax_based_on', get_option( 'woocommerce_tax_based_on', 'billing' ) ) && '' != $order->get_shipping_country() ) {
		$country = $order->get_shipping_country();
		$postcode = $order->get_shipping_postcode();
	}

	if ( trim( $order->get_meta( 'billing_vat' ) ) !== '' ) {

		if ( $country != WC()->countries->get_base_country() ) {
			
			if ( in_array( $country, $eu_countries ) ) {
				$notice = get_option( 'vat_options_notice', __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ) );
			}
		}
	}

	if ( 
		( ! in_array( $country, $eu_countries ) ) ||
		( WGM_Helper::is_vat_postcode_exemptions( $country, $postcode ) ) 
	) {
		$notice = apply_filters( 'wcvat_woocommerce_vat_notice_not_eu', get_option( 'vat_options_non_eu_notice', __( 'Tax-exempt export delivery', 'woocommerce-german-market' ) ), $order );
	}

	// only print notice if order has no taxes!
	// (maybe someone entered an invalid vat id)
	if ( $order->get_total_tax() > 0.0 ) {
		$notice = '';
	}

	// VAT ID
	$vat_id = $order->get_meta( 'billing_vat' );
	if ( ! empty( $vat_id ) ) {

		if ( ! empty( $notice ) ) {
			$notice .= '<br />';
		}

		$notice .= apply_filters( 'wcvat_woocommerce_email_after_order_table_vat_id_markup', get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) . ': ' . $vat_id, $vat_id );

	}

	if ( $notice != '' ) {
		echo apply_filters( 'wcvat_woocommerce_email_after_order_table', '<p><b>' . $notice . '</b></p>', $order );
	}

}

/**
 * Notice: "Tax free intracommunity delivery" in my-account
 *
 * @wp-hook	woocommerce_order_details_after_order_table
 * @param	WC_Order $order
 * @return	void
 */
function wcvat_woocommerce_order_details_after_order_table( $order ) {
	
	$notice       = '';
	$eu_countries = WC()->countries->get_european_union_countries();
	$country      = $order->get_billing_country();
	$postcode 	  = $order->get_billing_postcode();
	$uk_exception = false;

	if ( 'shipping' == apply_filters( 'wcvat_tax_based_on', get_option( 'woocommerce_tax_based_on', 'billing' ) ) && '' != $order->get_shipping_country() ) {
		$country = $order->get_shipping_country();
		$postcode = $order->get_shipping_postcode();
	}

	if ( trim( $order->get_meta( 'billing_vat' ) ) !== '' ) {

		if ( $country != WC()->countries->get_base_country() ) {

			if ( in_array( $country, $eu_countries ) ) {
				$notice = get_option( 'vat_options_notice', __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ) );
			} else if ( 'GB' === $country ) {
				
				if ( is_object( $order ) && method_exists( $order, 'get_date_created' ) ) {
					$date_created = $order->get_date_created();
					$first_of_january = new DateTime( '2021-01-01 00:00:00' );
					if ( $date_created < $first_of_january ) {
						$notice = get_option( 'vat_options_notice', __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ) );
						$uk_exception = true;
					}
				}
				
			}

		}
	}

	if ( 
		( ( ! in_array( $country, $eu_countries ) ) && ( ! $uk_exception ) ) ||
		( WGM_Helper::is_vat_postcode_exemptions( $country, $postcode ) ) 
	) {
		$notice = apply_filters( 'wcvat_woocommerce_vat_notice_not_eu', get_option( 'vat_options_non_eu_notice', __( 'Tax-exempt export delivery', 'woocommerce-german-market' ) ), $order );
	}

	// only print notice if order has no taxes!
	// (maybe someone entered an invalid vat id)
	if ( $order->get_total_tax() > 0.0 ) {
		$notice = '';
	}

	if ( $notice != '' ) {
		echo apply_filters( 'wcvat_woocommerce_order_details_after_order_table', '<p>' . $notice . '</p>', $order );
	}

}

/**
 * Get "tax exempt" status of an order as String
 *
 * @param	WC_Order $order
 * @return	String
 */
function wcvat_woocommerce_order_details_status( $order ) {
	return WGM_Helper::wcvat_woocommerce_order_details_status( $order );
}

/**
 * Notice: "Tax free intracommunity delivery" in checkout
 *
 * @wp-hook	woocommerce_review_order_after_order_total
 * @return	void
 */
function wcvat_woocommerce_checkout_details_after_order_table() {

	if ( apply_filters( 'wcvat_woocommerce_checkout_details_after_order_table_disable', false ) ) {
		return;
	}

	$notice = '';
	$eu_countries 		= WC()->countries->get_european_union_countries();
	
	$billing_vat 		= WC()->checkout->get_value( 'billing_vat' );
	$billing_country 	= WC()->checkout->get_value( 'billing_country' );
	$billing_postcode 	= WC()->checkout->get_value( 'billing_postcode' );

	if ( 'shipping' == apply_filters( 'wcvat_tax_based_on', get_option( 'woocommerce_tax_based_on', 'billing' ) ) && '' != WC()->checkout->get_value( 'shipping_country' ) ) {
		$billing_country 	= WC()->checkout->get_value( 'shipping_country' );
		$billing_postcode 	= WC()->checkout->get_value( 'shipping_postcode' );
	}

	if ( ! $billing_vat ) {
		if ( isset( $_REQUEST[ 'post_data' ] ) ) {
			$post_data = array();
			parse_str( $_REQUEST[ 'post_data' ], $post_data );
			if ( isset( $post_data[ 'billing_vat' ] ) ) {
				$billing_vat = $post_data[ 'billing_vat' ];
			}
		}
	}

	if ( ! $billing_country ) {
		if ( isset( $_REQUEST[ 'post_data' ] ) ) {
			$post_data = array();
			parse_str( $_REQUEST[ 'post_data' ], $post_data );
			if ( isset( $post_data[ 'billing_country' ] ) ) {
				$billing_country = $post_data[ 'billing_country' ];
			}
		}
	}

	if ( '' != $billing_vat ) {

		if ( WC()->countries->get_base_country() != $billing_country ) {
			
			if ( in_array( $billing_country, $eu_countries ) ) {
				$notice = get_option( 'vat_options_notice', __( 'Tax free intracommunity delivery', 'woocommerce-german-market' ) );
			}

		}

	}

	if ( 
		( ! in_array( $billing_country, $eu_countries ) ) ||
		( WGM_Helper::is_vat_postcode_exemptions( $billing_country, $billing_postcode ) )
	) {
		$notice = apply_filters( 'wcvat_woocommerce_vat_notice_not_eu_checkout', get_option( 'vat_options_non_eu_notice', __( 'Tax-exempt export delivery', 'woocommerce-german-market' ) ), WC()->checkout );
	}

	// only print notice if order has no taxes!
	// (maybe someone entered an invalid vat id)
	if ( WC()->cart->get_total_tax() > 0.0 ) {
		$notice = '';
	}

	if ( $notice != '' ) {
		echo '<tr class="wcvat-notice-german-market"><th colspan="2" class="wcvat-notice-german-market-th">';
		echo apply_filters( 'wcvat_woocommerce_order_details_after_order_table_checkout', $notice, WC()->chekcout );
		echo '</th></tr>';
	}

}

/**
 * Display the VAT Field in the Backend
 *
 * @wp-hook woocommerce_admin_order_data_after_order_details
 * @param object $order
 * @return void
 */
function wcvat_woocommerce_admin_order_data_after_billing_address( $order ) {
	
	$vat_id = '';

	if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {
		$vat_id = $order->get_meta( 'billing_vat' );
	}
	
	?>
	<p class="form-field form-field-wide">
		<label for="billing_vat"><?php echo get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) ) ?>:</label>
		
		<?php  if ( get_option( 'vat_options_billing_vat_editable', 'off' ) === 'on' ) { ?>
			<a href="#" class="gm_load_customer_billing_vat"><?php esc_html_e( 'Load from profile', 'woocommerce-german-market' ); ?></a>
		<?php } ?>
		
		<input type="text" name="billing_vat" id="billing_vat" value="<?php echo $vat_id; ?>" />

	</p>
	<?php
}

/**
 * Save the VAT Field in the backend
 *
 * @wp-hook woocommerce_process_shop_order_meta
 * @param Integer $post_id
 * @param WC_Post $post
 * @return void
 */
function wcvat_woocommerce_admin_save_vat_id_field( $order_id, $post_or_order_object ) {
	
	if ( is_object( $post_or_order_object ) && method_exists( 'post_or_order_object', 'update_meta' ) ) {
		$order = $post_or_order_object;
	} else {
		$order = wc_get_order( $order_id );
	}

	if ( isset( $_REQUEST[ 'billing_vat' ] ) ) {
		$order->update_meta_data( 'billing_vat', esc_attr( $_REQUEST[ 'billing_vat' ] ) );
		$order->save_meta_data();
	}
}

/**
 * Save the VAT woocommerce_checkout_create_order at the order
 *
 * @param WC_order $order
 * @param Array $posted
 * @return void
 */
function wcvat_woocommerce_checkout_update_order_meta( $order, $posted ) {
	
	if ( ! empty( $_REQUEST[ 'billing_vat' ] ) ) {	
		// save in order
		$vat = sanitize_text_field( $_REQUEST[ 'billing_vat' ] );
    	$order->update_meta_data( 'billing_vat', $vat );
		$order->save();

		// save in user profile
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( apply_filters( 'wcvat_save_billing_vat_in_user_meta', true ) ) {
				update_user_meta( $current_user->ID, 'billing_vat', $vat );
			}
		}
	}
}

/**
 * Save order notes (api resonse)
 *
 * @param Integer $order_id
 * @param Array $posted
 * @return void
 */
function wcvat_woocommerce_checkout_update_order_meta_order_notes_log( $order_id, $data ) {

	if ( 'on' === get_option( 'german_market_vat_logging', 'off' ) ) {

		$order 	= wc_get_order( $order_id );
		
		if ( method_exists( $order, 'get_meta' ) ) {

			$vat_id = $order->get_meta( 'billing_vat' );

			if ( apply_filters( 'wcvat_vat_logging_enabled', true, $order, $vat_id ) ) {

				$validator 				= new WC_VAT_Validator( $vat_id );
				$api_response_formatted = $validator->get_api_response_formatted();
				
				if ( $api_response_formatted && ! empty( $api_response_formatted ) ) {
					$order->add_order_note( $api_response_formatted, apply_filters( 'wcvat_vat_logging_is_customer_note', 0 ), apply_filters( 'wcvat_vat_logging_added_by_user', false ) );

					// addionaly, save raw api response as meta-data
					if ( apply_filters( 'wcvat_logging_save_raw_api_response_as_meta', true ) ) {
						$api_response_raw = $validator->get_api_response();
						if ( $api_response_raw && ! empty( $api_response_raw ) ) {
							$order->update_meta_data( '_wcvat_raw_api_response', $api_response_raw );
							$order->save_meta_data();
						}
					}
				}	
			}
		}
	}
}

/**
 * AJAX callback to check the VAT
 *
 * @wp-hook	wp_ajax_wcvat_check_vat, wp_ajax_nopriv_wcvat_check_vat
 * @return	void
 */
function wcvat_check_vat() {

	// get the billing vat
	$billing_vat = $_REQUEST[ 'vat' ];
	$raw_billing_vat = strtoupper( substr( $billing_vat, 0, 2 ) ) . strtoupper( substr( $billing_vat, 2 ) );
	$billing_vat = array( strtoupper( substr( $billing_vat, 0, 2 ) ), strtoupper( substr( $billing_vat, 2 ) ) );
	$billing_country = $_REQUEST[ 'country' ];

	$response = array( 'success' => '', 'data' => '' );

	// No need to validate if field is empty
	// The following block code does not work as expected, cause setting the sessions takes too long
	// see wcvat_woocommerce_before_calculate_totals, first comments, the first code block in this functions fixes the problem
	if ( trim( $_REQUEST[ 'vat' ] ) == '' ) {
		
		$response[ 'success' ] = FALSE;
		$response[ 'data' ]    = __( 'Field is empty.', 'woocommerce-german-market' );

		// add taxes
		WGM_Session::add( 'eu_vatin_check_exempt', false );
		WGM_Session::remove( 'eu_vatin_check_billing_vat' );
		WC()->customer->set_is_vat_exempt( false );
		echo json_encode( $response );
		exit;
	}

	// validate the billing_vat
	if ( ! class_exists( 'WC_VAT_Validator' ) ) {
		require_once 'class-wc-vat-validator.php';
	}

	$validator = new WC_VAT_Validator( $billing_vat, $billing_country );

	if ( $validator->is_valid() === FALSE ) {

		// add taxes
		WGM_Session::add( 'eu_vatin_check_exempt', false );
		WGM_Session::remove( 'eu_vatin_check_billing_vat' );
		$response[ 'success' ] = FALSE;

	} else {
		
		if ( apply_filters( 'wcvat_check_vat_is_billing_country_base_country', $billing_country == WC()->countries->get_base_country(), $billing_country ) ) {

			// add taxes
			WGM_Session::add( 'eu_vatin_check_exempt', false );
			WGM_Session::remove( 'eu_vatin_check_billing_vat' );

		} else {

			// remove taxes
			WGM_Session::add( 'eu_vatin_check_exempt', true );
			WGM_Session::add( 'eu_vatin_check_billing_vat', $billing_vat );
		}

		// output response
		$response[ 'success' ] = TRUE;
	}

	echo json_encode( $response );
	exit;
}

/**
 * Check VAT exempt with WGM_Session Class
 *
 * @wp-hook	woocommerce_before_calculate_totals
 * @return	void
 */
function wcvat_woocommerce_before_calculate_totals() {
	
	// if billing vat is empty => not vat exempted
	// in most cases when switching the country to base country
	// the session variable is set to slow, so we need this check
	if ( isset( $_REQUEST[ 'post_data' ] ) ) {
		
		parse_str( $_REQUEST[ 'post_data' ], $post_data );
		
		$billing_vat_is_empty = true;

		if ( isset( $post_data[ 'billing_vat' ] ) ) {

			if ( $post_data[ 'billing_vat' ] != '' ) {
				$billing_vat_is_empty = false;
			}

		}

		if ( $billing_vat_is_empty ) {
			WC()->customer->set_is_vat_exempt( false );
			WGM_Session::add( 'eu_vatin_check_exempt', false );
			WGM_Session::remove( 'eu_vatin_check_billing_vat' );
			return;
		}
		
	}

	if ( WGM_Session::get( 'eu_vatin_check_exempt' ) ) {
			WC()->customer->set_is_vat_exempt( true );
	}

}

/**
 * Add UK to european union countries by backend option
 *
 * @wp-hook	woocommerce_european_union_countries
 * @param Array $countries
 * @return Array
 */
function wcvat_woocommerce_european_union_countries_uk( $countries ) {

	if ( get_option( 'german_market_vat_options_united_kingdom', 'on' ) == 'on' ) {
		$countries[] = 'GB';
	}

	return $countries;

}
