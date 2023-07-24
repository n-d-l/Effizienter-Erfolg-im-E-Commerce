<?php

/**
 * Class WGM_Double_Opt_In_Customer_Registration
 *
 * Add Double Opt In Customer Registration
 *
 * @author  MarketPress
 */
class WGM_Double_Opt_In_Customer_Registration {

	public static $send_pw_link = false;
	/**
     * Init Hooks and filters
     *
     * @static
     * @return void
     */
	public static function init() {

		if ( ! function_exists( 'WC' ) ) {
        	return;
    	}
    	
		// Only if Double Opt-in Customer Registration has been activated
		if ( self::double_opt_in_is_activated() ) {

			self::$send_pw_link = version_compare( WC_VERSION, '6.0.0', '>=' );

			// Deactivate WooCommerce 'created customer notification' email
			add_action( 'woocommerce_email', array( __CLASS__, 'deactive_woocommerce_created_customer_notification' ), 99 );

			// Activate WGM 'created customer notification' email
			add_action( 'woocommerce_created_customer_notification', array( __CLASS__, 'woocommerce_created_customer_notification' ), 10, 3 );

			// Check activation action, if user access activation url
			add_action( 'template_redirect', array( __CLASS__, 'check_activation_action' ) );

			// No user login for users without activaiton
			add_filter( 'wp_authenticate_user', array( __CLASS__, 'wp_authenticate_user' ) , 10, 2 );

			// Prevent WooCommerce from logging in the user after registration
			add_filter( 'woocommerce_registration_auth_new_customer', array( __CLASS__, 'auth_new_customer' ), 10, 2 );

			// No logged in user after order process is finished
			add_action( 'woocommerce_thankyou', array( __CLASS__, 'logout_user' ) , 10, 2 );

			// No logged in user after regristration on my account page
			add_action( 'wp', array( __CLASS__, 'logout_user_my_account_page' ) );

			// Mangagement
			if ( get_option( 'wgm_double_opt_in_customer_registration_management', 'on' ) == 'on' ) {

				if ( is_admin() ) {

					add_filter( 'manage_users_columns', 		array( __CLASS__, 'user_tabe_column' ) );
					add_filter( 'manage_users_custom_column', 	array( __CLASS__, 'render_user_tabe_column' ), 10, 3 );

					add_action( 'restrict_manage_users', 		array( __CLASS__, 'filter_user_table' ) );
					add_filter( 'pre_get_users', 				array( __CLASS__, 'filter_user' ) );

					add_filter( 'bulk_actions-users', 			array( __CLASS__, 'bulk_resend_email_select' ) );
					add_filter( 'handle_bulk_actions-users', 	array( __CLASS__, 'bulk_resend_email' ), 10, 3 );
					add_filter( 'handle_bulk_actions-users', 	array( __CLASS__, 'bulk_manual_activation' ), 10, 3 );
					add_action( 'admin_notices', 				array( __CLASS__, 'bulk_admin_notice' ) );

				}
				
				// Auto Delete
				if ( get_option( 'wgm_double_opt_in_customer_registration_autodelete', 'off' ) == 'on' ) {
					
					$run = true;
					if ( method_exists( 'WC_Install', 'needs_db_update' ) ) {
						$run = ! WC_Install::needs_db_update();
					}

					if ( $run && class_exists( 'WC_Action_Queue' ) ) {

						if ( get_option( 'wgm_double_opt_on_customer_registration_autodelete_is_set_up', 'no' ) == 'no' ) {
							add_action( 'admin_init', array( __CLASS__, 'start_scheduler' ) );
						}

						add_action( 'german_market_double_opt_in_auto_delete', array( __CLASS__, 'german_market_double_opt_in_auto_delete' ) );

					}
				
				}

			}
			
		}

		// Maybe stop Scheduler
		if (  ( get_option( 'wgm_double_opt_on_customer_registration_autodelete_is_set_up', 'no' ) != 'no' ) &&
			  ( ( get_option( 'wgm_double_opt_in_customer_registration_autodelete', 'off' ) == 'off' ) || ( get_option( 'wgm_double_opt_in_customer_registration_management', 'on' ) == 'off' ) || ( get_option( 'wgm_double_opt_in_customer_registration' ) != 'on'  ) )
		) {

			if ( class_exists( 'WC_Action_Queue' ) ) {
				WC()->queue()->cancel_all( 'german_market_double_opt_in_auto_delete' );
				delete_option( 'wgm_double_opt_on_customer_registration_autodelete_is_set_up' );
			}

		}


	}

	/**
     * Start Scheduler in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook admin_init
     * @return void
     */
	public static function start_scheduler() {
		
		$start_time 	= current_time( 'timestamp' ) + HOUR_IN_SECONDS;
		$recurring_time = apply_filters( 'wgm_double_opt_on_customer_registration_autodelete_schedule_time', DAY_IN_SECONDS );

		WC()->queue()->cancel_all( 'german_market_double_opt_in_auto_delete' );

		WC()->queue()->schedule_recurring( $start_time, $recurring_time, 'german_market_double_opt_in_auto_delete', array(), 'german-market' );

		update_option( 'wgm_double_opt_on_customer_registration_autodelete_is_set_up', 'yes' );

	}

	/**
     * Auto Delete users that have not activate their account
     *
     * @since 3.9.2
     * @static
     * @wp-hook german_market_double_opt_in_auto_delete
     * @return void
     */
	public static function german_market_double_opt_in_auto_delete() {

		$activation_days 			= intval( get_option( 'wgm_double_opt_in_customer_registration_autodelete_days', 14 ) );
		$activation_days_seconds 	= $activation_days * DAY_IN_SECONDS;
		$time_now 		 			= current_time( 'timestamp' ); 

		// Get Users
		$args = array(
			'meta_key'     => '_wgm_double_opt_in_activation_status',
			'meta_value'   => 'waiting',
		); 

		$users = get_users( $args );

		if ( ! function_exists( 'wp_delete_user' ) ) {
			include_once( ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'user.php' );
		}
		
		foreach ( $users as $user ) {

			$user_activation_time = intval( get_user_meta( $user->ID, '_wgm_double_opt_in_activation_time', true ) );

			if ( $user_activation_time + $activation_days_seconds < $time_now ) {
				wp_delete_user( $user->ID );
			}

		}

	}

	/**
     * Auto Delete: Return Extra Text
     * Replace placeholder [days]
     *
     * @since 3.9.2
     * @static
     * @return String
     */
	public static function get_autodelete_extra_text() {

		$return_string = '';

		if ( ( get_option( 'wgm_double_opt_in_customer_registration_management', 'on' ) == 'on' ) && ( get_option( 'wgm_double_opt_in_customer_registration_autodelete', 'off' ) == 'on' ) ) {
			
			$days = get_option( 'wgm_double_opt_in_customer_registration_autodelete_days', 14 );
			$text = get_option( 'wgm_double_opt_in_customer_registration_autodelete_extratext', __( 'If you don\'t activate your account, it will be automatically deleted after [days] days.', 'woocommerce-german-market' ) );

			$return_string = str_replace( '[days]', $days, $text );

		}

		return $return_string;

	}

	/**
     * Management: Make Notice in Backend for Resending Mails
     *
     * @since 3.9.2
     * @static
     * @wp-hook admin_notices-users
     * @return void
     */
	public static function bulk_admin_notice() {
		
		if ( ! empty( $_REQUEST[ 'bulk_double_opt_in_mails' ] ) ) {
			
			$emailed_count =  $_REQUEST[ 'bulk_double_opt_in_mails' ];
			if ( $emailed_count == 'none' ) {
				$emailed_count = 0;
			}

			?>
			<div class="notice notice-success is-dismissible">
		        <p><?php echo sprintf( __( 'Number of resend Double Opt In Activation Emails: %s', 'woocommerce-german-market' ), $emailed_count ); ?></p>
		    </div>
			<?php	

  		}

  		if ( ! empty( $_REQUEST[ 'bulk_double_opt_in_manual_activation' ] ) ) {

  			$count = $_REQUEST[ 'bulk_double_opt_in_manual_activation' ];
			if ( $count == 'none' ) {
				$count = 0;
			}

			?>
			<div class="notice notice-success is-dismissible">
		        <p><?php echo sprintf( __( 'Number of manually activated accounts: %s', 'woocommerce-german-market' ), $count ); ?></p>
		    </div>
			<?php	

  		}
	}

	/**
     * Management: Bulk Action: Resend Emails
     *
     * @since 3.9.2
     * @static
     * @wp-hook handle_bulk_actions-users
     * @param String redirect_to
     * @param String $doaction
	 * @param Array $post_ids
     * @return String
     */
	public static function bulk_resend_email( $redirect_to, $doaction, $post_ids ) {

		if ( $doaction != 'resend_double_opt_in_activation_email' ) {
			return $redirect_to;
		}

		$counter = 0;

		foreach ( $post_ids as $user_id ) {
			
			$activation_status = get_user_meta( $user_id, '_wgm_double_opt_in_activation_status', true );

			if ( $activation_status == 'waiting' ) {

				$counter++;

				$user_data = get_userdata( $user_id );

				$new_customer_data = array(
					'user_email'	=> $user_data->user_email,
					'user_pass'		=> '',
					'user_login'	=> $user_data->user_login,
				);

				do_action( 'wgm_double_opt_in_customer_registration_before_bulk_resend_email', $user_id );

				self::woocommerce_created_customer_notification( $user_id, $new_customer_data, false, true );

				do_action( 'wgm_double_opt_in_customer_registration_after_bulk_resend_email', $user_id );
			}

		}

		if ( $counter == 0 ) {
			$counter = 'none';
		}

		$redirect_to = add_query_arg( 'bulk_double_opt_in_mails', $counter, $redirect_to );
  		return $redirect_to;

	}

	/**
     * Management: Bulk Action: Manual Activation
     *
     * @since 3.10.2
     * @static
     * @wp-hook handle_bulk_actions-users
     * @param String redirect_to
     * @param String $doaction
	 * @param Array $post_ids
     * @return String
     */
	public static function bulk_manual_activation( $redirect_to, $doaction, $post_ids ) {

		if ( $doaction != 'manual_double_opt_in_activation' ) {
			return $redirect_to;
		}

		$counter = 0;

		foreach ( $post_ids as $user_id ) {
			
			$activation_status = get_user_meta( $user_id, '_wgm_double_opt_in_activation_status', true );

			if ( $activation_status == 'waiting' ) {

				$counter++;

				update_user_meta( $user_id, '_wgm_double_opt_in_activation_status', 'activated' );
				delete_user_meta( $user_id, '_wgm_double_opt_in_activation_time' );
				delete_user_meta( $user_id, '_wgm_double_opt_in_activation_lang' );
			}

		}

		if ( $counter == 0 ) {
			$counter = 'none';
		}

		$redirect_to = add_query_arg( 'bulk_double_opt_in_manual_activation', $counter, $redirect_to );
  		return $redirect_to;
	}

	/**
     * Management: Add Bulk Action
     *
     * @since 3.9.2
     * @static
     * @wp-hook bulk_actions-users
     * @param Array $actions
     * @return Array
     */
	public static function bulk_resend_email_select( $actions ) {
		$actions[ 'resend_double_opt_in_activation_email' ] = __( 'Resend Double Opt In Activation Email', 'woocommerce-german-market' );
		$actions[ 'manual_double_opt_in_activation' ] = __( 'Double Opt In: Manual Account Activation', 'woocommerce-german-market' );
		return $actions;
	}

	/**
     * Management: Filter User in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook pre_get_users
     * @param WP_Query $query
     * @return void
     */
	public static function filter_user( $query ) {

		global $pagenow;
		if ( is_admin() && 'users.php' == $pagenow ) {
			
			if ( isset( $_REQUEST[ 'gm_double_opt_in_filter_top' ] ) && ! empty( $_REQUEST[ 'gm_double_opt_in_filter_top' ] ) ) {

				$query->set( 'meta_key', '_wgm_double_opt_in_activation_status' );
				$value = $_REQUEST[ 'gm_double_opt_in_filter_top' ] == 'waiting' ? 'waiting' : 'activated';
				$query->set( 'meta_value', $value );
			}
			
		}

	}

	/**
     * Management: Show Select Box for Filtering and Submit Button in User Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook restrict_manage_users
     * @param String $top_or_bottom
     * @return void
     */
	public static function filter_user_table( $top_or_bottom ) {

		if ( $top_or_bottom == 'bottom' ) {
			return;
		}

		// create sprintf templates for <select> and <option>s
		$st = '<select name="gm_double_opt_in_filter_%s" style="float:none;"><option value="">%s</option>%s</select>';
		
		$selected_string = ' selected="selected"';

		if ( isset( $_REQUEST[ 'gm_double_opt_in_filter_top' ] ) && ! empty( $_REQUEST[ 'gm_double_opt_in_filter_top' ] ) ) {
			$selected = $_REQUEST[ 'gm_double_opt_in_filter_top' ];
		} else {
			$selected = '';
		}

		$select_waiting 	= $selected == 'waiting' 	? $selected_string : '';
		$select_activated 	= $selected == 'activated' 	? $selected_string : '';

		$options  = '<option value="waiting"' . $select_waiting . '>' . _x( 'Waiting', 'Double Opt In Status', 'woocommerce-german-market' ) . '</option>';
		$options .= '<option value="activated"' . $select_activated . '>' . _x( 'Activated', 'Double Opt In Status', 'woocommerce-german-market' ) . '</option>';

		$select = sprintf( $st, $top_or_bottom, __( 'Double Opt In Filter...', 'woocommerce-german-market' ), $options );

		// output <select> and submit button
		echo $select;
		submit_button( __( 'Filter' ), null, $top_or_bottom, false );

	}

	/**
     * Management: Render User Column in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook manage_users_custom_column
     * @param Array $columns
     * @param String $val
     * @param String $column_name
     * @param Integer $user_id
     * @return String
     */
	public static function render_user_tabe_column( $val, $column_name, $user_id ) {

		switch ( $column_name ) {
			
			case 'double_opt_in' :
				
				$double_opt_in_info = '—';
				$activation_status = get_user_meta( $user_id, '_wgm_double_opt_in_activation_status', true );

				if ( $activation_status == 'waiting' ) {
					
					$double_opt_in_info = _x( 'Waiting', 'Double Opt In Status', 'woocommerce-german-market' );

					$waiting_since = get_user_meta( $user_id, '_wgm_double_opt_in_activation_time', true );
					if ( ! empty( $waiting_since ) ) {
						$double_opt_in_info .= ' ' . sprintf( _x( 'since %s', 'Double Opt In Status in Backend', 'woocommerce-german-market' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $waiting_since ) );
					}

				} else if ( $activation_status == 'activated' ) {
					$double_opt_in_info = _x( 'Activated', 'Double Opt In Status in Backend', 'woocommerce-german-market' );
				}

				return $double_opt_in_info;
			
			default:
		}
		
		return $val;

	}

	/**
     * Management: Add User Column in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook manage_users_columns
     * @param Array $columns
     * @return Array
     */
	public static function user_tabe_column( $columns ) {
		$columns[ 'double_opt_in' ] = __( 'Double Opt In', 'woocommerce-german-market' );
   		return $columns;
	}

	/**
     * User is logged in after registration on my account page => logout user if activation status is not activated
     *
     * @since 3.5.1
     * @static
     * @wp-hook wp
     * @return void
     */
	public static function logout_user_my_account_page() {

		if ( is_user_logged_in() && ! is_checkout() ) {

			$user = wp_get_current_user();
			$activation_status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );

			if ( $activation_status == 'waiting' ) {

				if ( apply_filters( 'german_market_double_opt_in_remove_all_actions', true  ) ) {
					remove_all_actions( 'wp_logout' );
				}
				
				wp_logout();
				do_action( 'gm_double_opt_in_before_logout_user_my_account_page_redirect', $user->ID );

				$my_account_url		= untrailingslashit( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				$url_parse			= parse_url( $my_account_url );
				if ( ! isset( $url_parse[ 'query' ] ) ) {
					$url_parse[ 'query' ] = '';
				}
				$redirect_link_args = empty( $url_parse[ 'query' ] ) ? ( '?gm_double_opt_in_message=true' ) : ( '&gm_double_opt_in_message=true' );

				wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) . $redirect_link_args );
				exit();

			}

		} else if ( is_user_logged_in() && is_wc_endpoint_url( 'order-received' ) ) {

			$user = wp_get_current_user();
			$activation_status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );

			if ( $activation_status == 'waiting' ) {

				if ( apply_filters( 'german_market_double_opt_in_remove_all_actions', true  ) ) {
					remove_all_actions( 'wp_logout' );
				}
				
				wp_logout();
				do_action( 'gm_double_opt_in_before_logout_user_my_account_page_redirect', $user->ID );
				do_action( 'gm_double_opt_in_before_logout_user_my_account_page_redirect_order_received', $user->ID );
			}

		} else if ( isset( $_REQUEST[ 'gm_double_opt_in_message' ] ) ) {

			$text = apply_filters( 'gm_doucle_opt_in_logout_user_my_account_page_text', __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-german-market' ) );
			
			$extra_text = self::get_autodelete_extra_text();
			if ( ! empty( $extra_text ) ) {
				$text .= '<br />' . nl2br( $extra_text );
			}

			wc_add_notice( $text, 'success' );

		}

	}

	/**
     * User is logged in after registration => logout user if activation status is not activated
     *
     * @static
     * @wp-hook woocommerce_thankyou
     * @return void
     */
	public static function logout_user() {

		if ( is_user_logged_in() ) {

			$user = wp_get_current_user();
			$activation_status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );
			
			if ( $activation_status == 'waiting' ) {
				
				wp_logout();

			}

		}

	}

	/**
     * Check if Douple Opt-in Customer Registration is activated
     *
     * @static
     * @return Bool
     */
	private static function double_opt_in_is_activated() {
		return ( get_option( 'wgm_double_opt_in_customer_registration' ) == 'on' );
	}

	/**
     * Deactivate WooCommerce 'created customer notification' email
     *
     * @static
     * @hook woocommerce_email
     * @return void
     */
	public static function deactive_woocommerce_created_customer_notification( $object ) {
		remove_action( 'woocommerce_created_customer_notification', array( $object, 'customer_new_account' ), 10, 3 );

		add_action( 'woocommerce_created_customer_notification', function() {
			add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false', 99 );
		}, 1 );
	}

	/**
     * Activate WGM 'created customer notification' email
     *
     * @static
     * @hook woocommerce_created_customer_notification
     * @return void
     */
	public static function woocommerce_created_customer_notification( $customer_id, $new_customer_data = array(), $password_generated = false, $resend = false ) {

		if ( ! $customer_id ) {
			return;
		}

		$user_email = $new_customer_data[ 'user_email' ];
		$user_pass = ! empty( $new_customer_data[ 'user_pass' ] ) ? $new_customer_data[ 'user_pass' ] : '';

		if ( ! $password_generated ) {
			$user_pass = '';
		}

		if ( self::$send_pw_link ) {
			$user_pass = '';
		}

		// add user meta
		if ( ( $resend && apply_filters( 'wgm_double_opt_in_activation_resend_new_code', true ) ) || ( ! $resend ) ) {
			$activation_code = wp_create_nonce( '_wgm_double_opt_in_activation' ) . md5( rand( 1, 100000 ) );
			update_user_meta( $customer_id, '_wgm_double_opt_in_activation', $activation_code );
		} else {
			$activation_code = get_user_meta( $customer_id, '_wgm_double_opt_in_activation', true );
		}
		
		update_user_meta( $customer_id, '_wgm_double_opt_in_activation_status', 'waiting' );
		
		if ( ! $resend ) {
			update_user_meta( $customer_id, '_wgm_double_opt_in_activation_time', current_time( 'timestamp' ) );
			
			// language support for resend emails
			$current_user_language = update_user_meta( $customer_id, '_wgm_double_opt_in_activation_lang', apply_filters( 'wgm_double_opt_in_activation_lang', get_locale() ) );
		}

		// build activation link
		$my_account_url		= untrailingslashit( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
		$url_parse			= parse_url( $my_account_url );
		if ( ! isset( $url_parse[ 'query' ] ) ) {
			$url_parse[ 'query' ] = '';
		}

		$activation_link = $my_account_url;
		$activation_link .= ( empty( $url_parse[ 'query' ] ) ) ? ( '/?account-activation=' . $activation_code ) : ( '&account-activation=' . $activation_code );
		$activation_link = apply_filters( 'wgm_double_opt_in_activation_link', $activation_link, $customer_id );

		$mail = include( 'WGM_Email_Double_Opt_In_Customer_Registration.php' );
		
		if ( apply_filters( 'wgm_double_opt_in_send_mail_first', true, $resend ) ) {
			$mail->trigger( $customer_id, $activation_link, $user_email, $new_customer_data[ 'user_login' ], $user_pass, $resend );
		}
		
	}

	/**
     * What happens if an user wants to activate the new user account
     *
     * @static
     * @hook woocommerce_created_customer_notification
     * @return void
     */
	public static function check_activation_action() {

		if ( is_account_page() && isset( $_GET[ 'account-activation' ] ) ) {

			// get activation code
			$activation_code = $_GET[ 'account-activation' ];
			
			// get the user
			$users = get_users(
				array(
					'meta_key'		=> '_wgm_double_opt_in_activation',
					'meta_value'	=> $activation_code
				)
			);

			if ( ! empty( $users ) ) {

				// get user
				$user = array_shift( $users );

				// get status
				$status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );

				if ( $status == 'waiting' ) {

					// activate the account
					update_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', 'activated' );
					delete_user_meta( $user->ID, '_wgm_double_opt_in_activation_time' );
					delete_user_meta( $user->ID, '_wgm_double_opt_in_activation_lang' );

					// send WC-mail
					WC()->mailer()->customer_new_account( $user->ID, array(), self::$send_pw_link );

					// login user
					if ( apply_filters( 'german_market_double_opt_in_login_after_activation', true ) ) {
						if ( ! is_user_logged_in() ) {
							wc_set_customer_auth_cookie( $user->ID );
						}
					}

					// add notice
					wc_add_notice( __( 'Your account has been successfully activated.', 'woocommerce-german-market' ), 'success' );
					
					// do what you want
					do_action( 'wgm_double_opt_in_activation_user_activated' );

				} else if ( $status == 'activated' ) {

					// account has already been activated
					wc_add_notice( __( 'Your account has already been activated.', 'woocommerce-german-market' ), 'notice' );
				
				} else {

					// sth strange happend
					do_action( 'wgm_double_opt_in_activation_user_activated_status_' . $status );

				}

			} else {

				// Something went wrong
				wc_add_notice( __( 'Your account cannot be activated. The activation code cannot be found.', 'woocommerce-german-market' ), 'error' );
			}

		} else if ( is_account_page() && isset( $_GET[ 'resend-account-activation' ] ) && isset( $_GET[ 'user' ] ) ) {

			// send new activation link
			if ( wp_verify_nonce( $_GET[ 'resend-account-activation' ], '_wgm_double_opt_in_activation_again' . md5( $_GET[ 'user' ] ) ) ) {

				$activation_code = wp_create_nonce( '_wgm_double_opt_in_activation' ) . md5( rand( 1, 100000 ) );
				update_user_meta( $_GET[ 'user' ], '_wgm_double_opt_in_activation_status', 'waiting' );
				update_user_meta( $_GET[ 'user' ], '_wgm_double_opt_in_activation', $activation_code );

				// build activation link
				$my_account_url		= untrailingslashit( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				$url_parse			= parse_url( $my_account_url );
				if ( ! isset( $url_parse[ 'query' ] ) ) {
					$url_parse[ 'query' ] = '';
				}

				$activation_link = $my_account_url;
				$activation_link .= ( empty( $url_parse[ 'query' ] ) ) ? ( '/?account-activation=' . $activation_code ) : ( '&account-activation=' . $activation_code );
				$activation_link = apply_filters( 'wgm_double_opt_in_activation_link', $activation_link, $_GET[ 'user' ] );

				$user_data = get_userdata( $_GET[ 'user' ] );

				$mail = include( 'WGM_Email_Double_Opt_In_Customer_Registration.php' );
				$mail->trigger( $_GET[ 'user' ], $activation_link, $user_data->user_email, $user_data->user_login, false, true );

				wc_add_notice( __( 'A new activation link has been sent to your e-mail.', 'woocommerce-german-market' ), 'success' );

			}

		}

	}

	/**
     * No user login for users without activation
     *
     * @static
     * @hook wp_authenticate_user
     * @param WP_User $user
     * @param String $password
     * @return WP_User (or throws an error)
     */
	public static function wp_authenticate_user( $user, $password ) {

		$activation_status = get_user_meta( $user->ID, '_wgm_double_opt_in_activation_status', true );
		
		$nonce = wp_create_nonce( '_wgm_double_opt_in_activation_again' . md5( $user->ID ) );

		$my_account_url		= untrailingslashit( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
		$url_parse			= parse_url( $my_account_url );
		if ( ! isset( $url_parse[ 'query' ] ) ) {
			$url_parse[ 'query' ] = '';
		}

		$resend_link = $my_account_url;
		$resend_link .= ( empty( $url_parse[ 'query' ] ) ) ? ( '/?resend-account-activation=' . $nonce . '&user=' . $user->ID ) : ( '&resend-account-activation=' . $nonce . '&user=' . $user->ID );

		if ( $activation_status == 'waiting' ) {
			
			do_action( 'german_market_double_opt_in_wp_authenticate_user_waiting', $user );
			
			if ( apply_filters( 'german_market_double_opt_in_throw_error', true ) ) {
				return new WP_Error( 'wgm_user_login_without_activation', sprintf( __( 'Please activate your account through clicking on the activation link received via email. Click <a href="%s">here</a> if you need a new activation link.', 'woocommerce-german-market' ), $resend_link ) );
			}

			do_action( 'german_market_double_opt_in_wp_authenticate_user_waiting_after_error', $user );
			
		}
		
		return $user;
	}

	/**
     * No user login for users without activation
     *
     * @static
     * @hook woocommerce_registration_auth_new_customer
     * @param do_autho_new_customer $Boolean
     * @param WP_User $user
     * @return Boolean
     */
	public static function auth_new_customer( $do_autho_new_customer, $user ) {

		if ( ! is_checkout() ) {
			$text = apply_filters( 'gm_doucle_opt_in_logout_user_my_account_page_text', __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-german-market' ) );
			wc_clear_notices();
			wc_add_notice( $text, 'success' );
		}

		return false;

	}
}
