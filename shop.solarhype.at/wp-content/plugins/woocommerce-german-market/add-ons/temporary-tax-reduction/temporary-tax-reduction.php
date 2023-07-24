<?php
/* 
 * Add-on Name:	Temporary Tax Reduction
 * Version:		1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// German Market Menu
add_filter( 'woocommerce_de_ui_left_menu_items', 'german_market_temporary_tax_reduction_settings' );

// Frontend Rates
if ( german_market_temporary_tax_reduction_find_is_frontend_activated() ) {
	add_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 30, 2 );
	add_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 30, 2 );
}

add_action( 'woocommerce_checkout_order_processed', 'german_market_temporary_tax_reduction_checkout_order_processed', 5, 3 );

// Backend
add_action( 'woocommerce_process_shop_order_meta', 'german_market_temporary_reduction_tax_process_shop_order_meta',10 , 2 );
add_action( 'woocommerce_admin_order_data_after_order_details', 'german_market_temporary_tax_reduction_backend_checkbox', 50 );

// Recaluclating
add_action( 'woocommerce_order_before_calculate_taxes', 'german_market_temporary_tax_reduction_order_before_calculate_taxes', 10, 2 );

// Invoice pdfs
add_action( 'wp_wc_invoice_pdf_start_template', 'german_market_temporary_tax_reduction_wp_wc_invoice_pdf_start_template' );
add_action( 'wp_wc_invoice_pdf_end_template', 'german_market_temporary_tax_reduction_wp_wc_invoice_pdf_end_template' );

// E-Mails
add_action( 'woocommerce_email_order_details', 'german_market_temporary_tax_reduction_email_order_details', 0 );
add_action( 'woocommerce_email_customer_details', 'german_market_temporary_tax_reduction_email_customer_details', 9999 );

// sevDesk
add_action( 'sevdesk_woocommerce_api_before_send', 'german_market_temporary_tax_reduction_email_order_details' );
add_action( 'sevdesk_woocommerce_api_after_send', 'german_market_temporary_tax_reduction_email_customer_details' );
add_action( 'sevdesk_woocommerce_api_before_send_refund', 'german_market_temporary_tax_reduction_email_order_details' );
add_action( 'sevdesk_woocommerce_api_after_send_refund', 'german_market_temporary_tax_reduction_email_customer_details' );

// lexoffice
add_action( 'woocommerce_de_lexoffice_api_before_send', 'german_market_temporary_tax_reduction_email_order_details' );
add_action( 'woocommerce_de_lexoffice_api_after_send', 'german_market_temporary_tax_reduction_email_customer_details' );
add_action( 'woocommerce_de_lexoffice_api_before_send_refund', 'german_market_temporary_tax_reduction_email_order_details' );
add_action( 'woocommerce_de_lexoffice_api_after_send_refund', 'german_market_temporary_tax_reduction_email_customer_details' );

/**
* Add German Market Submenu
* 
* @wp-hook woocommerce_de_ui_left_menu_items
* @param Array
* @return Array
*/
function german_market_temporary_tax_reduction_settings( $items ) {

	$items[ 490 ] = array( 
				'title'		=> __( 'Temporary Tax Reduction', 'woocommerce-german-market' ),
				'slug'		=> 'temporary-tax-reduction',
				'callback'	=>'german_market_temporary_tax_reduction_render_settings',
				'options'	=> true
		);

	return $items;
}

/**
* Render German Market Submenu
* 
* @return Array
*/
function german_market_temporary_tax_reduction_render_settings() {

	// Init options
	$options = array();

	// Activation and Dates
	$options[] = array(
			'name'		 => __( 'Activation', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'german_market_temporary_tax_reduction_activation_section',
			'desc'		 => WGM_Ui::get_video_layer( 'https://marketpress-videos.s3.eu-central-1.amazonaws.com/german-market/zeitweise-mwst-senkung.mp4' )
		);

	$options[] = array(
			'name'		=> __( 'Activation', 'woocommerce-german-market' ),
			'id'   		=> 'german_market_temporary_tax_reduction_activation',
			'type' 		=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
			'desc'		=> sprintf( __( 'Your current time is: %s. If this time is not correct, check your general WordPress settings.', 'woocommerce-german-market' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) ) ), 
		);

	$options[] = array(
			'name'		=> __( 'Start Date (included)', 'woocommerce-german-market' ),
			'id'   		=> 'german_market_temporary_tax_reduction_activation_start_date',
			'type' 		=> 'date',
			'default'  	=> '2020-07-01',
			'desc'		=> __( '00:00:00 o\' clock', 'woocommerce-german-market' ),
			'class'		=> 'german-market-unit',
		);

	$options[] = array(
			'name'		=> __( 'End Date (included)', 'woocommerce-german-market' ),
			'id'   		=> 'german_market_temporary_tax_reduction_activation_end_date',
			'type' 		=> 'date',
			'default'  	=> '2020-12-31',
			'desc'		=> __( '23:59:59 o\' clock', 'woocommerce-german-market' ),
			'class'		=> 'german-market-unit',
		);

	$options[] = array(
			'type'		 => 'sectionend',
			'id'  		 => 'german_market_temporary_tax_reduction_activation_section_end',
		);

	// Tax Rates
	$tax_classes = WC_Tax::get_tax_classes();
				 
	array_unshift( $tax_classes, 'standard' );

	foreach ( $tax_classes as $tax_class ) {

	 	$rates = WC_Tax::get_rates_for_tax_class( $tax_class );

	 	if ( empty( $rates ) && 'standard' === $tax_class ) {
	 		$rates = WC_Tax::get_rates_for_tax_class( '' );
	 	}

	 	if ( empty( $rates ) ) {
	 		continue;
	 	}

	 	$options[] = array(
			'name'		 => sprintf( __( 'Tax Class: %s', 'woocommerce-german-market' ), 'standard' === $tax_class ? __( 'Standard', 'woocommerce-german-market' ) : $tax_class ),
			'type'		 => 'title',
			'id'  		 => 'german_market_temporary_tax_reduction_title_' . sanitize_title( $tax_class ),
			'desc'		 => __( 'Select the tax rates to be changed temporarily and enter the changed rate. If a rate is not to be changed, leave the field empty.', 'woocommerce-german-market' ),
		);

	 	foreach ( $rates as $rate ) {
	 		
	 		$name_array = array();

	 		if ( isset( $rate->tax_rate_name ) && ! empty( $rate->tax_rate_name ) ) {
	 			$name_array[]  = $rate->tax_rate_name;
	 		}

	 		if ( isset( $rate->tax_rate ) && ! empty( $rate->tax_rate ) ) {
	 			$name_array[]  = round( $rate->tax_rate, 2 ) . '%';
	 		}

	 		if ( isset( $rate->tax_rate_country ) && ! empty( $rate->tax_rate_country ) ) {
	 			$name_array[]  = $rate->tax_rate_country;
	 		}

	 		if ( isset( $rate->tax_rate_state ) && ! empty( $rate->tax_rate_state ) ) {
	 			$name_array[]  = $rate->tax_rate_state;
	 		} 
				
			$default = '';

			if ( isset( $rate->tax_rate_country ) && isset( $rate->tax_rate ) ) {

				if ( 'DE' === $rate->tax_rate_country && 19.0 === floatval( $rate->tax_rate ) ) {
					$default = 16;
				} else if ( 'DE' === $rate->tax_rate_country && 7.0 === floatval( $rate->tax_rate ) ) {
					$default = 5;
				}

			}

	 		$options[] = array(
				'name'		=> implode( ', ', $name_array ),
				'id'		=> 'german_market_temporary_tax_reduction_rate_' . $rate->tax_rate_id,
				'type'     	=> 'number',
				'default'  	=> $default,
				'class'		=> 'temporary-tax-rate german-market-unit',
				'desc'		=> '%',
				'css'		=> 'text-align: right;',
				'custom_attributes' => array(
					'min'	=> 0,
					'step'	=> 0.1
					)
				);

	 	}

	 	$options[] = array(
			'type'		 => 'sectionend',
			'id'  		 => 'german_market_temporary_tax_reduction_title_end' . sanitize_title( $tax_class ),
		);

	 }

	 if ( 'on' === get_option( 'german_market_temporary_tax_reduction_tax_output', 'off' ) ) {
	 
		// Tax output
		$options[] = array(
				'name'		 => __( 'Tax Output', 'woocommerce-german-market' ),
				'type'		 => 'title',
				'id'  		 => 'german_market_temporary_tax_output_section',
				'desc'		 => __( 'The settings for generalized tax output in this add-on have been removed. Please use the add-on "EU VAT Checkout", which now provides an identical function.', 'woocommerce-german-market' ),
			);


		$options[] = array(
				'type'		 => 'sectionend',
				'id'  		 => 'german_market_temporary_tax_output_section_end',
			);
	}

	return $options;
}

/**
* Returns wheter option is activated and current time is between start and end date
* 
* @return Boolean
*/
function german_market_temporary_tax_reduction_find_is_frontend_activated() {

	$activated = 'on' === get_option( 'german_market_temporary_tax_reduction_activation', 'off' );

	if ( $activated ) {

		$current_time	 = current_time( 'timestamp' );
		$start_date 	 = get_option( 'german_market_temporary_tax_reduction_activation_start_date', '2020-07-01' );
		$end_date 		 = get_option( 'german_market_temporary_tax_reduction_activation_end_date', '2020-12-31' );

		$error = false;

		try {
			$start 			 = new DateTime( $start_date . ' 00:00:00' );
			$end  			 = new DateTime( $end_date . '23:59:59' );
		} catch ( Exception $e ) {

			$error = true;
			error_log( 'German Market: Temporary Tax Reduction:' . $error );
		}
		
		if ( ! $error ) {

			$current_time 	 = current_time( 'timestamp' );

			if ( ! ( $start->getTimestamp() <= $current_time && $current_time <= $end->getTimestamp() ) ) {
				$activated = false;
			}
		}

	}

	return $activated;
}

/**
* Manipulate Tax Rates
* 
* @wp-hook woocommerce_find_rates
* @param Array $matched_tax_rates
* @param Array args
* @return Array
*/
function german_market_temporary_tax_reduction_find_rates ( $matched_tax_rates, $args = array() ) {
		
	foreach ( $matched_tax_rates as $rate_id => $value ) {
		
		$temporarily_rate = get_option( 'german_market_temporary_tax_reduction_rate_' . $rate_id, '' );
		
		if ( ! empty ( $temporarily_rate ) ) {
			$new_value = $value;
			$new_value[ 'rate' ] = floatval( $temporarily_rate );
			$matched_tax_rates[ $rate_id ] = $new_value;
		}

	}

	return $matched_tax_rates;
} 

/**
* Manipulate Tax Rate Outpu
* 
* @wp-hook woocommerce_rate_percent
* @param String $rate_percent
* @param Integer rate_id
* @return String
*/
function german_market_temporary_tax_reduction_rate_percent( $rate_percent, $rate_id ) {

	$temporarily_rate = get_option( 'german_market_temporary_tax_reduction_rate_' . $rate_id, '' );
		
	if ( ! empty ( $temporarily_rate ) ) {
		$rate_percent = floatval( $temporarily_rate ) . '%';
	}

	return $rate_percent;
}

/**
* Save if temporary tax reduction has been applied
* 
* @wp-hook woocommerce_checkout_order_processed
* @param Integer $order_id
* @param Array posted_data
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_checkout_order_processed( $order_id, $posted_data, $order ) {

	if ( german_market_temporary_tax_reduction_find_is_frontend_activated() ) {
		$order->update_meta_data( '_german_market_temporary_tax_reduction_rate_percent', 'yes' );
		$order->save_meta_data();
	}

}

/**
* Add Checkbox to backend
* 
* @wp-hook woocommerce_admin_order_data_after_order_details
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_backend_checkbox( $order ) {

	$temporary_tax_reduction = $order->get_meta( '_german_market_temporary_tax_reduction_rate_percent' );
	
	?>
	<p class="form-field form-field-wide">
		<input style="width: 17px; height: 17px; float: left;" type="checkbox" name="german_market_temporary_tax_reduction" id="german_market_temporary_tax_reduction" <?php echo checked( 'yes', $temporary_tax_reduction, false ); ?> />
		<input type="hidden" name="german_market_temporary_tax_reduction_available" id="german_market_temporary_tax_reduction_available" />
		<label for="german_market_temporary_tax_reduction"><?php echo __( 'Temporary Tax Reduction', 'woocommerce-german-market' ); ?>
		<small><?php echo __( 'When changing this option: First update order, afterwards you can recalculate order, send emails or create pdfs.', 'woocommerce-german-market' ); ?></small>
		</label>
	</p>
	<?php
}

/**
* Save Checkbox in backend
* 
* @wp-hook woocommerce_admin_order_data_after_order_details
* @param Integer $post_id
* @param WP_Post $post
* @return void
*/
function german_market_temporary_reduction_tax_process_shop_order_meta( $order_id, $post_or_order_object ) {

	if ( is_object( $post_or_order_object ) && method_exists( 'post_or_order_object', 'update_meta' ) ) {
		$order = $post_or_order_object;
	} else {
		$order = wc_get_order( $order_id );
	}

	if ( isset( $_REQUEST[ 'german_market_temporary_tax_reduction' ] ) ) {
		$order->update_meta_data( '_german_market_temporary_tax_reduction_rate_percent', 'yes' );
	} else {
		$order->delete_meta_data( '_german_market_temporary_tax_reduction_rate_percent' );
	}

	$order->save_meta_data();
}

/**
* Used before output orders (emails, pdfs) and recalculation
* 
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_add_hooks_start( $order ) {

	if ( ! WGM_Helper::method_exists( $order, 'get_meta' ) ) {
		return;
	}

	if ( ! german_market_temporary_tax_reduction_find_is_frontend_activated() ) {

		if ( 'yes' === $order->get_meta( '_german_market_temporary_tax_reduction_rate_percent' ) ) {
			
			if ( ! has_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates' ) ) {
				add_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 30, 2 );
			}

			if ( ! has_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent' ) ) {
				add_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 30, 2 );
			}
		}
	
	} else {

		if ( 'yes' !== $order->get_meta( '_german_market_temporary_tax_reduction_rate_percent' ) ) {
			remove_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 30, 2 );
			remove_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 30, 2 );
		}


	}

}

/**
* Used after output orders (emails, pdfs) and recalculation
* 
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_add_hooks_end( $order ) {

	if ( ! WGM_Helper::method_exists( $order, 'get_meta' ) ) {
		return;
	}
	
	if ( ! german_market_temporary_tax_reduction_find_is_frontend_activated() ) {

		if ( 'yes' === $order->get_meta( '_german_market_temporary_tax_reduction_rate_percent' ) ) {
			remove_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 30, 2 );
			remove_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 30, 2 );
		}
	
	} else {

		if ( 'yes' !== $order->get_meta( '_german_market_temporary_tax_reduction_rate_percent' ) ) {
			
			if ( ! has_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates' ) ) {
				add_filter( 'woocommerce_find_rates', 'german_market_temporary_tax_reduction_find_rates', 30, 2 );
			}

			if ( ! has_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent' ) ) {
				add_filter( 'woocommerce_rate_percent', 'german_market_temporary_tax_reduction_rate_percent', 30, 2 );
			}
		}
	}
}

/**
* Check Meta before recaculating taxes
* 
* @wp-hook woocommerce_order_before_calculate_taxes
* @param Array $args
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_order_before_calculate_taxes( $args, $order ) {
	german_market_temporary_tax_reduction_add_hooks_start( $order );	
}

/**
* Check Meta before output taxes in Invoice PDFs
* 
* @wp-hook wp_wc_invoice_pdf_start_template
* @param Array $args
* @return void
*/
function german_market_temporary_tax_reduction_wp_wc_invoice_pdf_start_template( $args ) {
	
	if ( WGM_Helper::method_exists( $args[ 'order' ], 'get_meta' ) ) {
		$order = $args[ 'order' ];
		german_market_temporary_tax_reduction_add_hooks_start( $order );	
	}
}

/**
* Check Meta before output taxes in Invoice PDFs
* 
* @wp-hook wp_wc_invoice_pdf_end_template
* @param Array $args
* @return void
*/
function german_market_temporary_tax_reduction_wp_wc_invoice_pdf_end_template( $args ) {

	if ( WGM_Helper::method_exists( $args[ 'order' ], 'get_meta' ) ) {
		$order = $args[ 'order' ];
		german_market_temporary_tax_reduction_add_hooks_end( $order );
	}
}

/**
* Check Meta before output taxes in emails
* 
* @wp-hook woocommerce_email_order_details
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_email_order_details( $order ) {
	german_market_temporary_tax_reduction_add_hooks_start( $order );	
}

/**
* Check Meta before output taxes in emails
* 
* @wp-hook woocommerce_email_customer_details
* @param WC_Order $order
* @return void
*/
function german_market_temporary_tax_reduction_email_customer_details( $order ) {
	german_market_temporary_tax_reduction_add_hooks_end( $order );	
}

/**
* General Tax output
* 
* @wp-hook wgm_get_totals_tax_string
* @param String $tax_total_string
* @param Array $tax_string_array
* @param String $tax_totals
* @param Mixed $tax_display
* @return String
*/
function german_market_temporary_tax_reduction_wgm_get_totals_tax_string( $tax_total_string, $tax_string_array, $tax_totals, $tax_display ) {
	
	if ( ! empty( $tax_total_string ) ) {
		$tax_total_string = '<span class="wgm-tax includes_tax"><br />' . get_option( 'german_market_temporary_tax_reduction_general_output', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</span>';
	}

	return $tax_total_string;
}

/**
* General Tax output
* 
* @wp-hook wgm_product_summary_parts_after
* @param Array $output_parts
* @param WC_Product $product
* @param String $hook
* @return Array
*/
function german_market_temporary_tax_reduction_wgm_product_summary_parts_after( $output_parts, $product, $hook ) {

	if ( isset( $output_parts[ 'tax' ] ) && ! empty( $output_parts[ 'tax' ] ) ) {
		if ( ! empty( trim( strip_tags( $output_parts[ 'tax' ] ) ) ) ) {
			$output_parts[ 'tax' ] = '<div class="wgm-info woocommerce-de_price_taxrate ">' . get_option( 'german_market_temporary_tax_reduction_general_output', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</div>';
		}
	}
	
	return $output_parts;

}

/**
* General Tax output
* 
* @wp-hook wgm_get_tax_line
* @param String $tax_line
* @param WC_Product $product
* @return String
*/
function german_market_temporary_tax_reduction_wgm_get_tax_line( $tax_line, $product ) {

	if ( ! empty( $tax_line ) ) {
		$tax_line = get_option( 'german_market_temporary_tax_reduction_general_output', __( 'Incl. tax', 'woocommerce-german-market' ) );
	}

	return $tax_line;
}

/**
* General Tax output
* 
* @wp-hook wgm_get_excl_incl_tax_string
* @param String $msg
* @param String $type
* @param String $rate
* @param String $amount
* @return String
*/
function german_market_temporary_tax_reduction_wgm_get_excl_incl_tax_string( $msg, $type, $rate, $amount ) {
	
	if ( ! empty( $msg ) ) {
		$msg = get_option( 'german_market_temporary_tax_reduction_general_output', __( 'Incl. tax', 'woocommerce-german-market' ) );
	}

	return $msg;
}

/**
* General Tax output, deactivate cart.php of GM
* 
* @wp-hook german_market_add_woocommerce_de_templates_force_original
* @param Boolean $boolean
* @param String $template_name
* @return Boolean
*/
function german_market_temporary_tax_reduction_cart_template( $boolean, $template_name ) {

	if ( $template_name == 'cart/cart.php' ) {
		$boolean = true;
	}

	return $boolean;

}

/**
* General Tax output, Mini cart
* 
* @wp-hook german_market_mini_cart_price_tax
* @param String $string
* @return String
*/
function german_market_temporary_tax_reduction_mini_cart_price_tax( $string ) {

	if ( ! empty( $string ) ) {
		$string = '<div class="wgm-info woocommerce-de_price_taxrate">' . get_option( 'german_market_temporary_tax_reduction_general_output', __( 'Incl. tax', 'woocommerce-german-market' ) ) . '</div>';
	}

	return $string;
}
