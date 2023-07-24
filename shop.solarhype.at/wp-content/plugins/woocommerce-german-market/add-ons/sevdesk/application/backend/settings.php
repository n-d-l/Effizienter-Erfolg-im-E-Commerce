<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Backend Settings German Market 3.1
*
* wp-hook woocommerce_de_ui_left_menu_items
* @param Array $items
* @return Array
*/
function sevdesk_woocommerce_de_ui_left_menu_items( $items ) {

	$items[ 320 ] = array( 
				'title'		=> __( 'sevDesk', 'woocommerce-german-market' ),
				'slug'		=> 'sevdesk',
				'callback'	=>'sevdesk_woocommerce_de_ui_render_options',
				'options'	=> 'yes'
		);

	return $items;
}

/**
* Render Options for global
* 
* @return void
*/
function sevdesk_woocommerce_de_ui_render_options() {

	if ( isset ( $_REQUEST[ 'woocommerce_de_sevdesk_api_token' ] ) ) {
		wp_safe_redirect( get_admin_url() . 'admin.php?page=german-market&tab=sevdesk' );
	}

	$description = '';

	if ( ! function_exists( 'curl_init' ) ) {

		$description = '<span style="color: #f00;">' . __( 'The PHP cURL library seems not to be present on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' ) . ' ' . __( 'The sevDesk Add-On will not work without the cURL library.', 'woocommerce-german-market' ) . '</span><br /><br />';

	}

	$description .= __( 'Please enter your API token in the field above. To retrieve your API token, log in to your <a href="https://my.sevdesk.de" target="_blank">sevDesk</a> account and go to <i>settings -> user</i>. Select your user account, under „edit user“ you will find your API token at the bottom of the page.', 'woocommerce-german-market' ) . '<br /><br />' . sprintf ( __( "You can register <a href=\"%s\" target=\"_blank\">here</a> if you don't have a sevDesk account, yet.", 'woocommerce-german-market' ), 'https://sevdesk.de/register/?utm_source=integrations&utm_medium=referral&utm_campaign=marketpress' );
	
	$settings[] = array(
		'name' => __( 'Authorization', 'woocommerce-german-market' ),
		'type' => 'title',
		'id'   => 'sevdesk',
		'desc' => $description
	);

	$settings[] = array(
		'name'              => __( 'API Token', 'woocommerce-german-market' ),	
		'id'                => 'woocommerce_de_sevdesk_api_token',
		'type'              => 'text',
		'css'				=> 'min-width: 300px; max-width: 100%;'
	);

	$settings[] = array( 'type' => 'sectionend', 'id' => 'sevdesk' );

	$settings[] = array(
		'name' => __( 'Settings', 'woocommerce-german-market' ),
		'type' => 'title',
		'id'   => 'sevdesk_settings',
	);

	$description = __( 'Activate this option to send data of your WooCommerce customers to sevDesk to be used there as contacts (customers).', 'woocommerce-german-market' );

	$settings[] = array(
		'name'				=> __( 'Send Customer Data', 'woocommerce-german-market' ),
		'desc_tip'			=> $description,
		'id'				=> 'woocommerce_de_sevdesk_send_customer_data',
		'type'     			=> 'wgm_ui_checkbox',
		'default'			=> 'off',
	);

	if ( get_option( 'woocommerce_de_sevdesk_send_customer_data', 'off' ) == 'on' ) {

		$description = __( 'Prefix for your sevDesk customer numbers for persons, followed by the wordpress user id.', 'woocommerce-german-market' );

		$settings[] = array(
			'name'				=> __( 'Prefix - Person Customer Number', 'woocommerce-german-market' ),
			'desc_tip'			=> $description,
			'id'				=> 'woocommerce_de_sevdesk_customer_number_prefix',
			'type'              => 'text',
			'default'			=> '',
		);

		$description = __( 'If you activate this option, a company will be added as a contact to sevDesk if the user has an billing company in the user profile.', 'woocommerce-german-market' );

		$settings[] = array(
			'name'				=> __( 'Create Companies', 'woocommerce-german-market' ),
			'desc_tip'			=> $description,
			'id'				=> 'woocommerce_de_sevdesk_customer_add_company',
			'type'              => 'wgm_ui_checkbox',
		);

		$description = __( 'Prefix for your sevDesk customer numbers for companies, followed by the wordpress user id (which is the same as for the person). Only usesd if you activate the setting "Create Companies"', 'woocommerce-german-market' );

		$settings[] = array(
			'name'				=> __( 'Prefix - Company Customer Number', 'woocommerce-german-market' ),
			'desc_tip'			=> $description,
			'id'				=> 'woocommerce_de_sevdesk_customer_company_number_prefix',
			'type'              => 'text',
			'default'			=> '',
		);

		$settings[] = array(
			'name'		=> __( 'Guest Users', 'woocommerce-german-market' ),
			'id'				=> 'woocommerce_de_sevdesk_guest_users',
			'type'              => 'select',
			'options' 			=> array(
									'no'	=> __( 'Do not create a customer in sevDesk', 'woocommerce-german-market' ),
									'yes'	=> __( 'Create customer in sevDesk', 'woocommerce-german-market' ),
			),
			'default'			=> 'no',
			'css'				=> 'width: 400px;'
		);

		$description = __( 'The prefix for guest users is followed by the prefix for customer numbers or company number. After the guest prefix the order number is followed.', 'woocommerce-german-market' );

		$settings[] = array(
			'name'				=> __( 'Prefix - Guest Users', 'woocommerce-german-market' ),
			'desc_tip'			=> $description,
			'id'				=> 'woocommerce_de_sevdesk_customer_guest_prefix',
			'type'              => 'text',
			'default'			=> __( 'Guest-', 'woocommerce-german-market' ),
		);

	}

	if ( get_option( 'woocommerce_de_sevdesk_api_token' ) != '' ) {

		if ( function_exists( 'curl_init' ) ) {
			
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, sevdesk_woocommerce_api_get_base_url() . 'CheckAccount/?register=0' );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$response = curl_exec( $ch );
			$result_array = json_decode( $response, true );
			curl_close( $ch );

			$check_accounts = array();
			
			if ( isset ( $result_array[ 'objects' ] ) ) {

				foreach ( $result_array[ 'objects' ] as $check_account ) {
					$check_accounts[ $check_account [ 'id' ] ] = $check_account[ 'name' ];
				}

				$description = __( 'Choose your check account to book your vouchers.', 'woocommerce-german-market' ) . ' ' . __( 'When using a check account that is not of type "offline", vouchers can not be marked as "paid" by this API. In that case vouchers will be automatically be marked as paid by sevDesk.', 'woocommerce-german-market' );

				$settings[] = array(
					'name'				=> __( 'Check Account', 'woocommerce-german-market' ),
					'desc_tip'			=> $description,
					'id'				=> 'woocommerce_de_sevdesk_check_account',
					'type'				=> 'select',
					'options'			=> $check_accounts

				);

				$settings[] = array(
					'name'				=> __( 'Individual Check Accounts for Payment Gateways', 'woocommerce-german-market' ),
					'type'				=> 'wgm_ui_checkbox',
					'id'				=> 'woocommerce_de_sevdesk_individual_gateway_check_accounts',
					'default'			=> 'off',
					'desc_tip'			=> __( 'If activated, you can set up an individual check account in each payment gateway. If no individual check account is selected for a payment gateway, the default check account will be used.', 'woocommerce-german-market' )

				);

				$settings[] = array(
					'name'				=> __( 'Synchronization of Voucher Status on Backend Order Page', 'woocommerce-german-market' ),
					'desc_tip'			=> __( 'If there are long loading times on the order overview page in the backend (WooCommerce -> Orders), you can deactivate this option. Each call checks whether the voucher still exists in the sevDesk account. If an order has to be sent again to sevDesk after the corresponding voucher has been deleted at sevDesk, this option must be activated to allow a resending.', 'woocommerce-german-market' ),
					'id'				=> 'woocommerce_de_sevdesk_backend_sync',
					'type'              => 'wgm_ui_checkbox',
					'default'			=> 'on'

				);

			}

		}

	}

	$settings[] = array(
		'name'				=> __( 'Payment Status', 'woocommerce-german-market' ),
		'id'				=> 'woocommerce_de_sevdesk_payment_status',
		'type'              => 'select',
		'default'			=> 'completed',
		'options'			=> array(
				'completed' => __( 'Mark sevDesk voucher as paid if WooCommerce order is paid (order status is completed or processing)', 'woocommerce-german-market' ),
				'never'		=> __( 'Never mark sevDesk vouchers as paid', 'woocommerce-german-market' )
			),
		'css'				=> 'width: 600px;',
		'desc_tip'			=> __( 'When using a check account that is not of type "offline", vouchers can not be marked as "paid" by this API. In that case vouchers will be automatically be marked as paid by sevDesk.', 'woocommerce-german-market' ),

	);

	$settings[] = array( 'type' => 'sectionend', 'id' => 'sevdesk_settings' );

	if ( get_option( 'woocommerce_de_sevdesk_api_token' ) != '' ) {

		if ( function_exists( 'curl_init' ) ) {

			$settings[] = array(
				'name' => __( 'Booking Accounts', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'sevdesk_booking',
			);
			
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, sevdesk_woocommerce_api_get_base_url() . 'AccountingType?offset=0&useClientAccountingChart=true&embed=accountingSystemNumber&countAll=true&limit=-1&onlyOwn=false&emptyState=false' );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$response = curl_exec( $ch );
			$result_array = json_decode( $response, true );
			curl_close( $ch );

			$booking_accounts = array();
			
			if ( isset ( $result_array[ 'objects' ] ) ) {

				foreach ( $result_array[ 'objects' ] as $booking_account ) {
					
					$booking_account_value = '';
					
					if ( isset( $booking_account[ 'accountingSystemNumber' ][ 'number' ] ) ) {
						$booking_account_value .= $booking_account[ 'accountingSystemNumber' ][ 'number' ] . ' ';
					}

					$booking_account_value .= $booking_account[ 'name' ];
					
					$booking_accounts[ $booking_account[ 'id' ] ] = $booking_account_value;
				}

			}

			if ( has_filter( 'woocommerce_de_sevdesk_booking_accounts' ) ) {
				
				$settings = apply_filters( 'woocommerce_de_sevdesk_booking_accounts', $settings, $booking_accounts );
			
			} else {
				
				$settings[] = array(
						'name'				=> __( 'Booking Account for Order Items', 'woocommerce-german-market' ),
						'id'				=> 'woocommerce_de_sevdesk_booking_account_order_items',
						'type'				=> 'select',
						'options'			=> $booking_accounts,
						'default'			=> 26,
						'class'				=> 'sevdesk_select_booking_account'
				);

				$settings[] = array(
						'name'				=> __( 'Booking Account for Order Shipping', 'woocommerce-german-market' ),
						'id'				=> 'woocommerce_de_sevdesk_booking_account_order_shipping',
						'type'				=> 'select',
						'options'			=> $booking_accounts,
						'default'			=> 26,
						'class'				=> 'sevdesk_select_booking_account'
				);

				$settings[] = array(
						'name'				=> __( 'Booking Account for Order Fees', 'woocommerce-german-market' ),
						'id'				=> 'woocommerce_de_sevdesk_booking_account_order_fees',
						'type'				=> 'select',
						'options'			=> $booking_accounts,
						'default'			=> 26,
						'class'				=> 'sevdesk_select_booking_account'
				);

				$settings[] = array(
						'name'				=> __( 'Booking Account for Refund Positions', 'woocommerce-german-market' ),
						'id'				=> 'woocommerce_de_sevdesk_booking_account_refunds',
						'type'				=> 'select',
						'options'			=> $booking_accounts,
						'default'			=> 27,
						'class'				=> 'sevdesk_select_booking_account'
				);

				$settings[] = array(
						'name'				=> __( 'Individual booking accounts for products', 'woocommerce-german-market' ),
						'id'				=> 'woocommerce_de_sevdesk_individual_product_booking_accounts',
						'type'				=> 'wgm_ui_checkbox',
						'default'			=> 'off',
						'desc_tip'			=> __( 'If activated, you can set up individual booking accounts in every product. If no individual booking account is selected for a product, the default booking account will be used (booking account for order items or for refund positions).', 'woocommerce-german-market' )
				);
			
			}

			$settings 	= apply_filters( 'woocommerce_de_sevdesk_additional_booking_accounts', $settings, $booking_accounts );
			$settings[] = array( 'type' => 'sectionend', 'id' => 'sevdesk_booking' );

		}

	}

	$settings[] = array( 
			'name'		 => __( 'Automatic Transmission', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'sevdesk_automatic_transmission',
		);

	$settings[] = array(
			'name'		=> __( 'Completed Order', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_sevdesk_automatic_completed_order',
			'desc_tip'	=> __( 'If activated, the voucher will be send automatically to sevDesk if the order is marked as completed.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
		);

	$settings[] = array(
			'name'		=> __( 'Refunds', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_sevdesk_automatic_refund',
			'desc_tip'	=> __( 'If activated, the voucher will be send automatically to sevDesk if an refund is created.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
		);

	$settings[] = array(
			'type'		=> 'sectionend',
			'id' 		=> 'sevdesk_automatic_transmission' 
		);

	$settings[] = array(
			'name'		 => __( 'Voucher Number', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'secdesk_voucher_number',
		);

	$settings[] = apply_filters( 'sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_order', array(
			'name'		 => __( 'Voucher Number for orders', 'woocommerce-german-market' ),
			'type'		 => 'text',
			'id'  		 => 'sevdesk_voucher_description_order',
			'default'	 => sevdesk_woocommerce_get_default_value( 'sevdesk_voucher_description_order' ),
			'desc'		 => __( 'You can use the following placeholder', 'woocommerce-german-market' ) . ': ' . __( 'Order Number - <code>{{order-number}}</code>', 'woocommerce-german-market' ),
		) );

	$settings[] = apply_filters( 'sevdesk_woocommerce_de_ui_render_option_sevdesk_voucher_description_refund', array(
			'name'		 => __( 'Voucher Number for refunds', 'woocommerce-german-market' ),
			'type'		 => 'text',
			'id'  		 => 'sevdesk_voucher_description_refund',
			'default'	 => sevdesk_woocommerce_get_default_value( 'sevdesk_voucher_description_refund' ),
			'desc'		 => __( 'You can use the following placeholders:', 'woocommerce-german-market' ) . ' ' . __( 'Refund ID - <code>{{refund-id}}</code>, Order Number - <code>{{order-number}}</code>', 'woocommerce-german-market' ),
		) );

	$settings = apply_filters( 'sevdesk_woocommerce_de_ui_settings_after_voucher_number', $settings );

	$settings[] = array(
			'type'		=> 'sectionend',
			'id' 		=> 'secdesk_voucher_number' 
		);

	$settings = apply_filters( 'sevdesk_woocommerce_de_ui_render_options', $settings );
	return( $settings );

}

/**
* Individual Accounts: Save Meta Data
*
* @since 3.8.2 
* @wp-hook woocommerce_process_product_meta
* @param Integer $post_id
* @param WP_Post $post
* @return void 
**/
function sevdesk_woocommerce_accounts_save_meta( $post_id, $post = NULL ) {

	if ( isset( $_POST[ '_sevdesk_field_order_account' ] ) ) {
		update_post_meta( $post_id, '_sevdesk_field_order_account', $_POST[ '_sevdesk_field_order_account' ] );
	}

	if ( isset( $_POST[ '_sevdesk_field_refund_account' ] ) ) {
		update_post_meta( $post_id, '_sevdesk_field_refund_account', $_POST[ '_sevdesk_field_refund_account' ] );
	}

}

/**
* Individual Accounts: Add Product Tab
*
* @since 3.8.2 
* @wp-hook woocommerce_product_data_tabs
* @param Array $tabs
* @return Array 
**/
function sevdesk_woocommerce_accounts_product_tab( $tabs ) {

	$tabs[ 'german_market_sevdesk' ] = array(
			'label'  => 'sevDesk',
			'target' => 'sevdesk_accounts_product_panel_setting',
	);

	return $tabs;
}

/**
* Individual Accounts: Render Product Tab
*
* @since 3.8.2 
* @wp-hook woocommerce_product_data_panels
* @return void 
**/
function sevdesk_woocommerce_accounts_product_panel(){
	$product = wc_get_product( get_the_ID() );
	?>
	<div id="sevdesk_accounts_product_panel_setting" class="panel woocommerce_options_panel sevdesk" style="display: block; ">

		<?php
		$booking_accounts = array();		
		if ( get_option( 'woocommerce_de_sevdesk_api_token' ) != '' ) {

			if ( function_exists( 'curl_init' ) ) {

				$settings[] = array(
					'name' => __( 'Booking Accounts', 'woocommerce-german-market' ),
					'type' => 'title',
					'id'   => 'sevdesk_booking',
				);
				
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, sevdesk_woocommerce_api_get_base_url() . 'AccountingType?offset=0&useClientAccountingChart=true&embed=accountingSystemNumber&countAll=true&limit=-1&onlyOwn=false&emptyState=false' );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
				$response = curl_exec( $ch );
				$result_array = json_decode( $response, true );
				curl_close( $ch );
				
				if ( isset ( $result_array[ 'objects' ] ) ) {

					foreach ( $result_array[ 'objects' ] as $booking_account ) {
						$number = isset( $booking_account[ 'accountingSystemNumber' ][ 'number' ] ) ? $booking_account[ 'accountingSystemNumber' ][ 'number' ] : '';
						$booking_accounts[ $booking_account [ 'id' ] ] = trim( $number . ' ' . $booking_account[ 'name' ] );
					}

				}

			}

		}

		?>

		<p class="form-field _sevdesk_fields">
			<label for="_sevdesk_field_order_account" style="width: 300px;"><?php echo __( 'Booking account in orders:', 'woocommerce-german-market' ); ?></label>
			
			<?php $setting = get_post_meta( get_the_ID(), '_sevdesk_field_order_account', true );?>
			<select name="_sevdesk_field_order_account" id="_sevdesk_field_order_account" class="sevdesk_select_booking_account" style="width: 50%;">

				<option value="-1"><?php echo __( 'Default Booking Acount', 'woocommerce-german-market' ); ?></option>

				<?php foreach ( $booking_accounts as $key => $value ) { ?>

					<option value="<?php echo $key;?>" <?php echo ( intval( $setting ) == intval( $key ) ) ? 'selected="selected"' : ''; ?>><?php echo $value; ?></option>

				<?php } ?>
			
			</select>
		</p>

		<p class="form-field _sevdesk_fields">
			
			<label for="_sevdesk_field_refund_account" style="width: 300px;"><?php echo __( 'Booking account in refunds:', 'woocommerce-german-market' ); ?></label>
			
			<?php $setting = get_post_meta( get_the_ID(), '_sevdesk_field_refund_account', true ); ?>
			<select name="_sevdesk_field_refund_account" id="_sevdesk_field_refund_account" class="sevdesk_select_booking_account" style="width: 50%;">

				<option value="-1"><?php echo __( 'Default Booking Acount', 'woocommerce-german-market' ); ?></option>
				<?php foreach ( $booking_accounts as $key => $value ) { ?>

					<option value="<?php echo $key;?>" <?php echo ( intval( $setting ) == intval( $key ) ) ? 'selected="selected"' : ''; ?>><?php echo $value; ?></option>

				<?php } ?>
			
			</select>

		</p>

	</div>
	<?php

}

/**
* Init actions and hooks needed for the check account field in gateways
*
* @since 3.8.2
* @wp-hook admin_init
* @return void
*/
function woocommerce_de_sevdesk_gateway_check_accounts_init(){

	// return if WooCommerce is not active
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	if ( ! ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'wc-settings' && isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'checkout' && isset( $_REQUEST[ 'section' ] ) ) ) {
		return;
	}

	// add filter for eacht payment gateway
	foreach ( WC()->payment_gateways()->get_payment_gateway_ids() as $gateway_id ) {
		add_filter( 'woocommerce_settings_api_form_fields_' . $gateway_id, 'woocommerce_de_sevdesk_gateway_check_accounts_field' );
	}
}

/**
* Field in Payment Gateways
*
* @since 3.8.2
* @param Array $settings
* @return Array
*/
function woocommerce_de_sevdesk_gateway_check_accounts_field( $settings ) {

	$settings[ 'sevdesk_title' ] = array(
		'title' 		=> __( 'sevDesk', 'woocommerce-german-market' ),
		'type' 			=> 'title',
		'default'		=> '',
	);

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, sevdesk_woocommerce_api_get_base_url() . 'CheckAccount/?register=0' );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . get_option( 'woocommerce_de_sevdesk_api_token' ) ,'Content-Type:application/x-www-form-urlencoded' ) );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$response = curl_exec( $ch );
	$result_array = json_decode( $response, true );
	curl_close( $ch );

	$check_accounts = array();
	
	if ( isset ( $result_array[ 'objects' ] ) ) {

		$check_accounts[ 'default' ] = __( 'Default Check Account', 'woocommerce-german-market' );

		foreach ( $result_array[ 'objects' ] as $check_account ) {
			$check_accounts[ $check_account [ 'id' ] ] = $check_account[ 'name' ];
		}

	}

	$settings[ 'sevdesk_check_account' ] = array(
		'title'				=> __( 'Check Account', 'woocommerce-german-market' ),
		'id'				=> 'woocommerce_de_sevdesk_check_account',
		'type'				=> 'select',
		'options'			=> $check_accounts

	);

	return $settings;

}
