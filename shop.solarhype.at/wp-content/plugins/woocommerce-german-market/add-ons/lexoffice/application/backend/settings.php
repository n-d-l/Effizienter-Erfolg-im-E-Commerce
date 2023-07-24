<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Backend Settings German Market 3.1
*
* wp-hook woocommerce_de_ui_options_global
* @param Array $items
* @return Array
*/
function lexoffice_woocommerce_de_ui_left_menu_items( $items ) {

	$items[ 310 ] = array( 
				'title'		=> __( 'Lexoffice', 'woocommerce-german-market' ),
				'slug'		=> 'lexoffice',
				'callback'	=>'lexoffice_woocommerce_de_ui_render_options',
				'options'	=> true
		);

	return $items;
}

/**
* Render Options for global
* 
* @return void
*/
function lexoffice_woocommerce_de_ui_render_options() {

	// revoke
	if ( isset( $_POST[ 'submit_save_wgm_options' ] ) ) { 
		if ( wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {
			if ( isset( $_REQUEST[ 'woocommerce_de_lexoffice_revoke' ] ) &&  $_REQUEST[ 'woocommerce_de_lexoffice_revoke' ] == 'on' ) {
				
				lexoffice_woocomerce_api_revoke_auth();

				?>
				<div class="notice-wgm notice-success">
			        <p><?php echo __( 'The authorization has been revoked.', 'woocommerce-german-market' ); ?></p>
			    </div>
			    <?php
			}
		}
	}


	$description = '';

	if ( ! function_exists( 'curl_init' ) ) {

		$description = '<span style="color: #f00;">' . __( 'The PHP cURL library seems not to be present on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' ) . ' ' . __( 'The lexoffice Add-On will not work without the cURL library.', 'woocommerce-german-market' ) . '</span><br /><br />';

	}

	$description .= sprintf( __( "Please visit <a href=\"%s\" target=\"_blank\">this page</a> and log in with your lexoffice user account. Please accept all items of the next step. You will then receive an authorization code. Copy the code into the field below and save your settings. If you have any problems sending data to lexoffice, please renew the code as described above.", 'woocommerce-german-market' ), 
		'https://app.lexoffice.de/api/oauth2/authorize?client_id=de16ad78-9c84-4877-bf25-14005d83743a&redirect_uri=/api/oauth2/authorization_code&response_type=code' );
		
	$description .= '<br /><br />' . sprintf ( __( "You can register <a href=\"%s\" target=\"_blank\">here</a> if you don't have a lexoffice account, yet", 'woocommerce-german-market' ), 'https://app.lexoffice.de/signup?pid=1443' ) . '.';

	$lexoffice_contacts = lexoffice_woocommerce_get_all_contacts();
	$guest_options = array();
	$guest_options[ 'collective_contact' ] = __( 'Use "Collective Contact"', 'woocommerce-german-market' );
	$guest_options[ 'create_new_user' ] = __( 'Create a new lexoffice User', 'woocommerce-german-market' );

	foreach ( $lexoffice_contacts as $lexoffice_user ) {
		
		$display_name = '';

		if ( isset( $lexoffice_user[ 'person' ] ) ) {
			$display_name = isset( $lexoffice_user[ 'person' ][ 'firstName' ] ) ? $lexoffice_user[ 'person' ][ 'lastName' ] . ', ' . $lexoffice_user[ 'person' ][ 'firstName' ] : $lexoffice_user[ 'person' ][ 'lastName' ];
		} else if ( isset( $lexoffice_user[ 'company' ] ) ) {
			$display_name = $lexoffice_user[ 'company' ][ 'name' ];
		}

		if ( $display_name != '' ) {
			$guest_options[ $lexoffice_user[ 'id' ] ] = sprintf( __( 'Use "%s"', 'woocommerce-german-market' ), $display_name );
		}
		
	}

	$options = array(

		array(
			'name'		 => __( 'Authorization', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'lexoffice',
			'desc'		 => $description
		),

		array(
			'name'		=> __( 'Authorization Code', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_authorization_code',
			'type'		=> 'text'
		),

		array(
			'name'		=> __( 'Revoke Authorization', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_revoke',
			'desc_tip'	=> __( 'Enable this option to revoke the authorization, i.e. the connection between your lexoffice account and your online shop will be removed.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'lexoffice' 
		),

		array(
			'name'		 => __( 'Contacts', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'lexoffice_contacts',
		),

		array(
			'name'		=> __( 'lexoffice Contacts', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_contacts',
			'desc_tip'	=> __( 'You can choose whether to use only the "Collective Contact" for each WooCommerce user or to have the possibility to assing every WoocCommerce user to one of your lexoffice contacts.', 'woocommerce-german-market' ),
			'type'     	=> 'select',
			'default'  	=> 'collective_contact',
			'options'	=> array(
				'collective_contact'	=> __( 'Only use the Collective Contact', 'woocommerce-german-market' ),
				'lexoffice_contacts'	=> __( 'Use lexoffice Contacts', 'woocommerce-german-market' ),
			),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		),

		'woocommerce_de_lexoffice_create_new_user' => array(
			'name'		=>  __( 'Create new lexoffice Users', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_create_new_user',
			'desc_tip'	=> __( 'If enabled, a new lexoffice user is automatically created if you send an order to lexoffice with an WooCommerce user that is not assigned to any lexoffice user, yet.', 'woocommerce-german-market' ),
			'type'     	=> 'select',
			'default'  	=> 'on',
			'options'	=> array(
					'on'  => __( 'Create a new user', 'woocommerce-german-market' ),
					'off' => __( 'Use "Collective Contact"', 'woocommerce-german-market' )
			),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		),

		'woocommerce_de_lexoffice_user_update' => array(
			'name'		=> __( 'Automatic User Update', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_user_update',
			'desc_tip'	=> __( 'Update the lexofficer user data when a new order is send to lexoffice.', 'woocommerce-german-market' ),
			'type'     	=> 'select',
			'default'  	=> 'on',
			'options'	=> array(
					'on'  => __( 'Update lexoffice User', 'woocommerce-german-market' ),
					'off' => __( 'Don\'t update the lexoffice User', 'woocommerce-german-market' )
			),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		),

		'woocommerce_de_lexoffice_guest_user' => array(
			'name'		=> __( 'Guest Users', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_guest_user',
			'desc_tip'	=> __( 'If you have enabled guest checkout you can manage here how to connect a guest user with your lexoffice contacts.', 'woocommerce-german-market' ),
			'type'     	=> 'select',
			'options'	=> $guest_options,
			'default'  	=> 'collective_contact',
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'lexoffice_contacts' 
		),

		array(
			'name'		 => __( 'Automatic Transmission', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'lexoffice_automatic_transmission',
		),

		array(
			'name'		=> __( 'Completed Order', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_automatic_completed_order',
			'desc_tip'	=> __( 'If activated, the voucher will be send automatically to lexoffice if the order is marked as completed.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
		),

		array(
			'name'		=> _x( 'Refunds', 'lexoffice setting', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_automatic_refund',
			'desc_tip'	=> __( 'If activated, the voucher will be send automatically to lexoffice if an refund is created.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'off',
		),

		array( 
			'type'		=> 'sectionend',
			'id' 		=> 'lexoffice_automatic_transmission' 
		),

		array(
			'name'		 => __( 'Transmission of Refunds', 'woocommerce-german-market' ),
			'type'		 => 'title',
			'id'  		 => 'lexoffice_transmission_refunds',
		),

		array(
			'name'		=> __( 'Transmit order before transmitting refund', 'woocommerce-german-market' ),
			'id'		=> 'woocommerce_de_lexoffice_transmit_order_before_refund',
			'desc_tip'	=> __( 'If this setting is enabled, the order will be transmitted to lexoffice before the refund is transmitted, if it has not been done before.', 'woocommerce-german-market' ),
			'type'     	=> 'wgm_ui_checkbox',
			'default'  	=> 'on',
		),

		array( 
			'type'		=> 'sectionend',
		),

	);

	$options = apply_filters( 'lexoffice_woocommerce_de_ui_render_options', $options );
	return( $options );

}

/**
* Update bearer when saving options
* 
* @wp-hook woocommerce_de_ui_update_options
* @param Array $options
* @return void
*/
function lexoffice_woocommerce_de_ui_update_options( $options ) {
	
	if ( isset( $_POST[ 'submit_save_wgm_options' ] ) ) { 
		if ( wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {
			
			$last_used_code = get_option( 'lexoffice_woocommerce_last_auth_code', '' );

			if ( isset( $_REQUEST[ 'woocommerce_de_lexoffice_authorization_code' ] ) && ( $last_used_code != $_REQUEST[ 'woocommerce_de_lexoffice_authorization_code' ] ) ) {

				delete_option( 'lexoffice_woocommerce_barear' );
				delete_option( 'lexoffice_woocommerce_refresh_token' );
				delete_option( 'lexoffice_woocommerce_refresh_time' );
				delete_option( 'lexoffice_woocommerce_last_auth_code' );

				// Update Bearer
				lexoffice_woocomerce_api_get_bearer();

			}

			// Revoke 
			if ( isset( $_REQUEST[ 'woocommerce_de_lexoffice_revoke' ] ) && $_REQUEST[ 'woocommerce_de_lexoffice_revoke' ] == 'on' ) {

				update_option( 'woocommerce_de_lexoffice_revoke', 'off' );

				delete_option( 'lexoffice_woocommerce_barear' );
				delete_option( 'lexoffice_woocommerce_refresh_token' );
				delete_option( 'lexoffice_woocommerce_refresh_time' );
				delete_option( 'lexoffice_woocommerce_last_auth_code' );
				delete_option( 'woocommerce_de_lexoffice_authorization_code' );

			}
		}
	}
	
}
