<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use AbcAeffchen\Sephpa\SephpaCreditTransfer;
use AbcAeffchen\SepaUtilities\SepaUtilities;
use AbcAeffchen\Sephpa\SephpaDirectDebit;

/**
 * SEPA Direct Debit Gateway
 *
 * Provides a SEPA Direct Debit.
 *
 * @class 		WGM_Gateway_Sepa_Direct_Debit
 * @extends		WC_Payment_Gateway
 * @version		1.0
 */
class WGM_Gateway_Sepa_Direct_Debit extends WC_Payment_Gateway {

	public static $instances = 0;
    /**
     * Init Payment Gateway
     */
    function __construct() {
		
		self::$instances++;

		$this->id           			= 'german_market_sepa_direct_debit';
		$this->method_title 			= __( 'SEPA Direct Debit', 'woocommerce-german-market' );
		$this->has_fields  				= true;
		$this->method_description 		= __( 'Take payments via SEPA Direct Debit.', 'woocommerce-german-market' ) . ' <small><em>' . __( 'Provided by German Market.', 'woocommerce-german-market' ) . '</em></small>';

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->supports                 = array(
			'add_payment_method',
		);

		// Get settings
		$this->enabled 			  		= $this->get_option( 'enabled', 'no' );
		$this->title              		= $this->get_option( 'title' );
		$this->description        		= $this->get_option( 'description' );
		$this->instructions       		= $this->get_option( 'instructions' );
		$this->enable_for_methods 		= $this->get_option( 'enable_for_methods', array() );
		$this->user_availability  		= apply_filters( 'wgm_gateway_sepa_option_user_availability', $this->get_option( 'user_availability' ) );
	    $this->order_status 	  		= $this->get_option( 'order_status', 'processing' );
	    $this->data_store 		  		= $this->get_option( 'data_storing', 'raw' );
	    $this->mask_option 		  		= $this->get_option( 'iban_mask', 3 );
	    $this->mask_option_admin   		= $this->get_option( 'iban_mask_admin', 'yes' );
	    $this->mask_symbol 	 	  		= $this->get_option( 'iban_mask_symbol', '*' );
	    $this->mandate_reference  		= $this->get_option( 'mandate_reference', __( 'MANDATE', 'woocommerce-german-market' ) . '{order-id}' );
	    $this->email_subject 	  		= $this->get_option( 'sepa_mandate_email_subject', __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' ) );
	    $this->email_heading 	  		= $this->get_option( 'sepa_mandate_email_heading', __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' ) );
	    $this->email_type 	  	  		= $this->get_option( 'sepa_mandate_email_type', 'html' );
	    $this->email_admin		  		= $this->get_option( 'sepa_mandate_email_recipient', 'customer_and_admin' );
	    $this->email_pdf		  		= $this->get_option( 'sepa_mandate_email_pdf', 'no' );
	    $this->email_admin_mask   		= $this->get_option( 'sepa_mandate_email_admin_mask_iban', 'off' );
	    $this->email_admin_address		= $this->get_option( 'sepa_mandate_email_address', get_option( 'admin_email' ) );
	    $this->creditor_identifier 		= $this->get_option( 'creditor_identifier', '' );

	    $this->prenotification_activaction 		= $this->get_option( 'prenotification_activaction', 'off' );
	    $this->prenotification_due_date			= $this->get_option( 'prenotification_due_date', 14 );
	    $this->prenotification_output_in_email	= $this->get_option( 'prenotification_output_in_email', 'after' );
	    $this->prenotification_text 			= $this->get_option( 'prenotification_text', __( 'On [due date] or in up to three subsequent days, we will execute a SEPA direct debit of [amount] via the SEPA mandate [mandate_id] as instructed. The debit can be identified by the creditor identifier number [creditor_identifier].', 'woocommerce-german-market' ) );

	    $this->pain_format 		  		= $this->get_option( 'pain_format', 'pain.008.002.02' );
	    $this->xml_due_date_option 		= $this->get_option( 'xml_due_date_option', 'x_days' );
	    $this->xml_due_date_option_days = $this->get_option( 'xml_due_date_option_days', 1 );
	    $this->rest_api_activaction		= $this->get_option( 'rest_api_activaction', 'off' );
	    $this->rest_api_iban_masking	= $this->get_option( 'rest_api_iban_masking', 'off' );
	    $this->xml_include_refunds		= $this->get_option( 'xml_include_refunds', 'exclude' );

	    // Can user save or edit payment information?
		$this->customer_can_save_payment_information = $this->get_option( 'checkout_customer_can_save_payment_information', 'off' );
		$this->customer_can_edit_payment_information = $this->get_option( 'my_account_customer_can_edit_payment_information', 'off' );

	    // process when order has been made
	    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	    
	    // show instructions on settings page
	    add_action( 'woocommerce_thankyou_german_market_sepa_direct_debit', array( $this, 'thankyou' ) );

	    // show data in emails
	    if ( self::$instances == 1 ) {
		    add_action( 'woocommerce_email_customer_details', array( $this, 'email_customer_details' ), 20, 3 );
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

			if ( 'off' !== $this->prenotification_activaction ) {

				if ( 'before' === $this->prenotification_output_in_email ) {
					add_action( 'woocommerce_email_before_order_table', array( $this, 'email_prenotification' ), 20, 3 );
				} else {
					add_action( 'woocommerce_email_after_order_table', array( $this, 'email_prenotification' ), 20, 3 );
				}

				add_action( 'wp_wc_invoice_pdf_start_template', array( $this, 'prenotification_before_invoice_pdf' ) );
				add_action( 'wp_wc_invoice_pdf_end_template', array( $this, 'prenotification_after_invoice_pdf' ) );
			}
		}

	    // output sepa fields in backend in each order
	    if ( self::$instances == 1 ) {
	   		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'output_admin_billing_fields' ) );
	   		add_filter( 'wgm_sepa_direct_debit_logged_in_user', array( $this, 'add_saved_data_to_mandat_preview_args' ) );
	   	}

	    // save data into order and user
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ), 5, 3 );

		// save data in backend
		add_action( 'woocommerce_before_order_object_save', array( $this, 'sava_admin_data' ), 10, 2 );

		// bulk sepa download
		if ( self::$instances == 1 ) {
			add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ) );
			add_action( 'load-edit.php', array( $this, 'bulk_action' ) );
		}

		// sepa xml download errors
		if ( get_option( '_german_market_sepa_xml_error', '' ) != '' ) {
			add_action( 'manage_posts_extra_tablenav', array( $this, 'admin_xml_error_notice' ) );
		}

		add_action( 'woocommerce_admin_order_actions', array( $this, 'admin_order_actions' ), 10, 2 );

		// Logging of Checkout Checkbox
		if ( get_option( 'gm_order_review_checkboxes_logging', 'off' ) == 'on' ) {
			add_filter( 'german_market_checkbox_logging_checbkox_texts_array', array( $this, 'checkbox_logging' ), 10, 4 );
		}

		// My Account 'Payment Methods' Section
		add_action( 'woocommerce_checkout_order_processed',			array( $this, 'maybe_save_customer_payment_information' ), 99999 );
		add_filter( 'woocommerce_saved_payment_methods_list',		array( $this, 'add_sepa_to_woocommerce_payment_methods_list' ), 10, 2 );
		add_filter( 'woocommerce_get_credit_card_type_label',		array( $this, 'get_brand_string' ), 10, 1 );

		if ( 'off' == $this->customer_can_edit_payment_information ) {
			add_action( 'woocommerce_after_account_payment_methods', array( $this, 'my_account_disable_add_payment_button' ) );
        }

		// Catch option 'Delete stored payment ifnromation' for scheduler.
		add_action( 'woocommerce_update_options_payment_gateways_german_market_sepa_direct_debit', array( $this, 'process_admin_options' ) );

		do_action( 'german_market_sepa_direct_debit_after_construct', $this );
	}

	/**
     * Add saved sepa data to mandate preview
     *
     * @wp-hook wgm_sepa_direct_debit_logged_in_user
	 * @param Array $data
     * @return Array
	 */
	public function add_saved_data_to_mandat_preview_args( $data ) {

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {

			$fields  	= self::get_payment_fields();
			
			foreach ( $fields as $key => $field ) {
				
				$meta_key 	= self::build_meta_key( $key );
				$value    	= get_user_meta( $user_id, $meta_key, true );
				
				if ( $this->encryption_possible_on() ) {
					$value = $this->decrypt( $value, $user_id );
				}
				
				if ( 'iban' === $key ) {
					$value = $this->mask_iban( $value );

				}
				if ( ! empty( $value ) ) {
					$data[ $key ] = $value;
				}
			}
		}

		return $data;
	}
	
	/**
     * Returns the user meta key string.
     *
     * @access public
     * @static
     *
	 * @param string $key
     *
     * @return string
	 */
    public static function build_meta_key( $key ) {

        $meta_key = 'german_market_sepa_' . esc_attr( $key );

        return $meta_key;
    }

	/**
	 * Returns the post key string.
	 *
	 * @access public
	 * @static
	 *
	 * @param string $key
	 *
	 * @return string
	 */
    public static function build_post_key( $key ) {

	    $post_key = 'german-market-sepa-' . esc_attr( $key );

	    return $post_key;
    }

	/**
	 * Our schedule function to delete stored payment information.
     *
     * @hook german_market_sepa_bulk_delete_stored_payment_information
     *
     * @access public
     * @static
     *
     * @return void
	 */
    public static function bulk_schedule_delete_stored_payment_information() {

	    $fields     = self::get_payment_fields();
	    $user_query = new WP_User_Query(
		    array(
			    'meta_key'     => 'german_market_sepa_iban',
			    'meta_value'   => null,
			    'meta_compare' => '!=',
			    'number'       => 10,
		    )
	    );

	    $users = $user_query->get_results();
	    $total = $user_query->get_total();

	    foreach ( $users as $user ) {
		    $user_id = intval( $user->ID );
		    foreach ( $fields as $key => $field ) {
			    $meta_key = self::build_meta_key( $key );
			    delete_user_meta( $user_id, $meta_key );
		    }
	    }

	    // Start another schedule if we found more then 10 users.
        if ( 10 < $total ) {
	        WC()->queue()->add( 'german_market_sepa_bulk_delete_stored_payment_information', array(), 'german_market_sepa' );
        }

    }

	/**
     * Processing payment gateway settings.
     *
	 * @hook woocommerce_update_options_payment_gateways_german_market_sepa_direct_debit
     *
     * @access public
     *
     * @return bool was anything saved?
	 */
    public function process_admin_options() {

	    if ( isset( $_POST[ 'woocommerce_german_market_sepa_direct_debit_delete_stored_sepa_payment_information' ] ) && '1' == $_POST[ 'woocommerce_german_market_sepa_direct_debit_delete_stored_sepa_payment_information' ] ) {

		    // Setup scheduler if we found more than 10 users with stored SEPA information.
		    WC()->queue()->add( 'german_market_sepa_bulk_delete_stored_payment_information', array(), 'german_market_sepa' );

           // unset( $_POST[ 'woocommerce_german_market_sepa_direct_debit_delete_stored_sepa_payment_information' ] );
	    }

	    return parent::process_admin_options();
    }

	/**
	 * Updating custom SEPA fields.
	 *
	 * @wp-hook personal_options_update, edit_user_profile_update

	 * @access public
	 * @static
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public static function update_profile_sepa_fields( $user_id ) {

		if ( ! ( current_user_can( 'edit_user', $user_id ) && current_user_can( 'manage_woocommerce' ) ) ) {
			return;
		}

		$editable = ( 'on' == get_option( 'woocommerce_german_market_sepa_direct_debit_my_account_customer_can_edit_payment_information', 'off' ) ) ? true : false;
		if ( current_user_can('administrator') ) {
			$editable = true;
		}

        if ( ! $editable ) {
            return;
        }

		$fields  = self::get_payment_fields();
		$gateway = new WGM_Gateway_Sepa_Direct_Debit();

		foreach ( $fields as $key => $field ) {
			$meta_key = self::build_meta_key( $key );
			$value    = $_POST[ $meta_key ];
			if ( $gateway->encryption_possible_on() ) {
				$value = $gateway->encrypt( $value, $user_id );
			}
			update_user_meta( $user_id, $meta_key, $value );
		}

	}

	/**
	 * Add SEPA fields to admin area.
	 *
	 * @wp-hook show_user_profile, edit_user_profile
	 *
	 * @access public
	 * @static
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public static function add_sepa_fields_to_user_section( $user ) {

		$user_id = $user->ID;
		$fields  = self::get_payment_fields();
		$gateway = new WGM_Gateway_Sepa_Direct_Debit();

		if ( ! ( current_user_can( 'edit_user', $user_id ) && current_user_can( 'manage_woocommerce' ) ) ) {
			return;
		}

        $readonly = ( 'off' == get_option( 'woocommerce_german_market_sepa_direct_debit_my_account_customer_can_edit_payment_information', 'off' ) );
		if ( current_user_can('administrator') ) {
		    $readonly = false;
        }

		?>
        <h2><?php echo __( 'SEPA Direct Debit from German Market', 'woocommerce-german-market' ); ?></h2>
        <table class="form-table">
        <?php

		foreach ( $fields as $key => $field ) {

			$meta_key = self::build_meta_key( $key );
			$value    = get_user_meta( $user_id, $meta_key, true );
			if ( $gateway->encryption_possible_on() ) {
				$value = $gateway->decrypt( $value, $user_id );
			}

			?>
            <tr>
                <th><label for="<?php echo $meta_key; ?>"><?php echo __( $field[ 'label' ], 'woocommerce-german-market' ); ?></label></th>
                <td>
                    <input type="text" name="<?php echo $meta_key; ?>" id="<?php echo $meta_key; ?>" value="<?php echo $value; ?>" class="regular-text" <?php echo ( true === $readonly ? 'readonly' : '' ); ?>/>
                    <p class="description"></p>
                </td>
            </tr>
            <?php

		}

		?>
        </table>
		<?php
	}

	/**
     * Ajax callback to check for exisiting customer SEPA payment information.
     *
	 * @hook wp_ajax_german_market_sepa_check_stored_user_information
     *
     * @access public
     * @static
     *
     * @return json, die()
	 */
    public static function check_for_stored_user_payment_information() {

	    if ( ! check_ajax_referer( 'german-market-payment-information-nonce', 'security', false ) ) {
		    wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
	    }

        if ( ! isset( $_REQUEST[ 'user' ] ) ) {
            die();
        }

	    $fields        = self::get_payment_fields();
	    $customer_id   = intval( $_REQUEST[ 'user' ] );
	    $payment_infos = array();
	    $gateway       = new WGM_Gateway_Sepa_Direct_Debit();

	    get_user_by( 'id', $customer_id );

	    foreach ( $fields as $key => $field ) {
		    $meta_key = self::build_meta_key( $key );
		    $value    = get_user_meta( $customer_id, $meta_key, true );
		    if ( $gateway->encryption_possible_on() ) {
			    $value = $gateway->decrypt( $value, $customer_id );
		    }
		    $payment_infos[ '_' . $meta_key ] = $value;
	    }

        echo json_encode( $payment_infos );
	    die();
    }

	/**
	 * Disables the 'Add payment method' button in 'My Account' Section.
     *
     * @access public
     *
     * @return void
	 */
    public function my_account_disable_add_payment_button() {

	    add_filter( 'woocommerce_available_payment_gateways', function( $gateways ) {
		    if ( is_wc_endpoint_url( 'payment-methods' ) ) {
			    if ( isset( $gateways[ 'german_market_sepa_direct_debit' ] ) ) {
				    unset( $gateways[ 'german_market_sepa_direct_debit' ] );
			    }
		    }
		    return $gateways;
	    });
    }

	/**
	 * Ajax callback to delete stored Sepa payment information.
     *
     * @hook wp_ajax_german_market_sepa_delete_user_information
     *
     * @access public
     * @static
     *
     * @return void, die()
	 */
    public static function my_account_delete_user_payment_information() {

	    if ( ! check_ajax_referer( 'german-market-payment-methods-nonce', 'security', false ) ) {
		    wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-german-market' ), '', array( 'response' => 403 ) );
	    }

	    // Check if given user id is current logged in user id.
        if ( isset( $_REQUEST[ 'user' ] ) && '' != $_REQUEST[ 'user' ] && get_current_user_id() == $_REQUEST[ 'user' ] ) {

	        $fields      = self::get_payment_fields();
	        $customer_id = get_current_user_id();

	        foreach ( $fields as $key => $field ) {
		        $meta_key = self::build_meta_key( $key );
		        delete_user_meta( $customer_id, $meta_key );
	        }

	        wc_add_notice( __( 'The stored SEPA payment information were successfully removed.', 'woocommerce-german-market' ), 'success' );

        } else {
	        wc_add_notice( __( 'The given user ID cant be verified.', 'woocommerce-german-market' ), 'error' );
        }

        die( wp_safe_redirect( wp_get_referer() ) );
    }

	/**
     * Returns the brand string.
     *
     * @hook woocommerce_get_credit_card_type_label
     *
     * @access public
     *
	 * @param string $brand
     *
     * @return string
	 */
    public function get_brand_string( $brand ) {

        // Return brand name if it's not empty (e.g. Credit Cards) or user is not logged in.
	    if ( ! is_user_logged_in() || '' !== $brand ) {
		    return $brand;
	    }

	    $fields           = self::get_payment_fields();
	    $customer_id      = get_current_user_id();
	    $brands_stringify = '';

	    foreach ( $fields as $key => $field ) {
		    $meta_key = self::build_meta_key( $key );
		    $value    = get_user_meta( $customer_id, $meta_key, true );
		    if ( $this->encryption_possible_on() ) {
			    $value = $this->decrypt( $value, $customer_id );
		    }
		    if ( 'iban' == $key ) {
			    $brands_stringify = strtoupper( $field[ 'label' ] ) . ': ' . $this->mask_iban( $value );
		    }
	    }

	    return $brands_stringify;
    }

	/**
     * Manipulating WooCommerce 'saved payment methods' for page in 'my account' section.
     *
     * @hook woocommerce_saved_payment_methods_list
     *
     * @access public
     *
	 * @param array $methods_list
	 * @param int   $customer_id
	 *
	 * @return array
	 */
    public function add_sepa_to_woocommerce_payment_methods_list( $methods_list, $customer_id ) {

	    if ( ! is_user_logged_in() ) {
	        return $methods_list;
        }

	    $fields                = self::get_payment_fields();
	    $customer_id           = get_current_user_id();
	    $has_saved_information = false;

	    foreach ( $fields as  $key => $field ) {
		    $meta_key = self::build_meta_key( $key );
		    $value    = get_user_meta( $customer_id, $meta_key, true );
		    if ( '' != $value ) {
		        $has_saved_information = true;
            }
	    }

	    if ( ! $has_saved_information ) {
		    return $methods_list;
	    }

	    $methods_list[ 'sepa' ] = array(
		    array(
			    'expires' => apply_filters( 'german_market_sepa_default_expires_string', __( 'Never', 'woocommerce-german-market' ) ),
			    'method'  => array(
			            'brand' => '', // We need to fii it via hook / filter.
                ),
			    'actions' => array(
			            'delete' => array(
				            'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=german_market_sepa_delete_user_information&user=' . get_current_user_id() ), 'german-market-payment-methods-nonce' ),
                            'name' => __( 'Delete', 'woocommerce-german-market' ),
                        ),
                ),
		    )
	    );

	    return $methods_list;
	}

	/**
     * Saving payment method in 'my account' section.
     *
	 * @access public
	 *
	 * @return array|void
	 */
	public function add_payment_method() {

		if ( ! is_user_logged_in() ) {

			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit();

		} else {

			$current_user_id = get_current_user_id();
			$fields          = self::get_payment_fields();

            foreach ( $fields as  $key => $field ) {
                $post_key = self::build_post_key( $key );
                $meta_key = self::build_meta_key( $key );
                $value    = $_POST[ $post_key ];
                if ( $this->encryption_possible_on() ) {
                    $value = $this->encrypt( $value, $current_user_id );
                }
                update_user_meta( $current_user_id, $meta_key, $value );
            }

            return array(
                'result'   => 'success',
                'redirect' => wc_get_endpoint_url( 'payment-methods' ),
            );
		}

	}

	/**
     * Saving customers Sepa payment information on demand.
     *
     * @hook woocommerce_checkout_order_processed
     *
     * @access public
     *
	 * @param array  $data
	 * @param object $errors
     *
     * @return void
	 */
    public function maybe_save_customer_payment_information() {

        if ( is_user_logged_in() ) {

	        $fields          = self::get_payment_fields();
	        $current_user_id = get_current_user_id();

	        if ( isset( $_POST[ 'german-market-sepa-save-payment-information' ] ) && 'yes' == $_POST[ 'german-market-sepa-save-payment-information' ] ) {

		        foreach ( $fields as  $key => $field ) {
		            $post_key = self::build_post_key( $key );
		            $meta_key = self::build_meta_key( $key );
			        $value    = $_POST[ $post_key ];
			        if ( $this->encryption_possible_on() ) {
				        $value = $this->encrypt( $value, $current_user_id );
			        }
			        update_user_meta( $current_user_id, $meta_key, $value );
		        }
	        }
        }
    }

	/**
	* Checkbox Logging
	*
	* @since 3.8.2
	*
	* @wp-hook german_market_checkbox_logging_checbkox_texts_array
	* @param Array $checkboxes_texts
	* @param String $pre_symbol
	* @param Array $posted_data
	* @param WC_Order $order
	* @return Array
	**/
	function checkbox_logging( $checkboxes_texts, $pre_symbol, $posted_data, $order ) {

		if ( isset( $_REQUEST[ 'gm-sepa-direct-debit-checkbox' ] ) ) {

			$confirmation_text = get_option( 'woocommerce_german_market_sepa_direct_debit_checkbox_confirmation_text', __( 'I agree to the [link]sepa direct debit mandate[/link].', 'woocommerce-german-market' ) );
			$confirmation_text = str_replace( 
				array( '[link]', '[/link]' ),
				array( '<a href="#" id="gm-sepa-mandate-preview" style="cursor: pointer;">', '</a>' ),
				$confirmation_text
			);

			$checkboxes_texts[] = $pre_symbol . strip_tags( $confirmation_text );
		}

		return $checkboxes_texts;
	}

	/**
	* Output admin biling fields
	*
	* @since 3.6.2
	*
	* @wp-hook woocommerce_admin_order_data_after_billing_address
	* @param WC_Order $order
	* @return void
	**/
	function output_admin_billing_fields( $order ) {

		$fields = $this->admin_billing_fields();

		?>
        <div class="edit_address edit_billing_address" id="edit_billing_address_container">

			<?php foreach ( $fields as  $key => $field ) {

				woocommerce_wp_text_input( $field );

				if ( apply_filters( 'german_market_sepa_show_raw_data_in_backend', false ) ) {

					if ( $key == 'german-market-mandate-reference' ) {
						$key = 'german-market-sepa-mandate-reference';
					}

					$post_meta_key = '_' . str_replace( '-', '_', $key );
					echo '<p class="form-field"><b>' . __( 'Raw Data:', 'woocommerce-german-market' ) . '</b> ' . $order->get_meta( $post_meta_key ) . '</p>';

				}

			}

			$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=german_market_sepa_check_stored_user_information' ), 'german-market-payment-information-nonce' );
			?>
            <p class="form-field ajax_check_customer_payment_information" style="margin: 15px 0; display: none;">
                <a style="cursor:  pointer;" data-link="<?php echo $url; ?>"><?php echo __( 'Load SEPA payment data from user profile', 'woocommerce-german-market' ); ?></a>
            </p>

        </div>
		<?php
	}

	/**
	* Mask IBAN
	*
	* @param String $iban
	* @return String
	**/
	private function mask_iban( $iban ) {
		
		if ( intval( $this->mask_option ) > 0 ) {

			$string_length 		= strlen( $iban );

			if ( ! $string_length > 0 ) {
				return $iban;
			}

			$show_length 		= intval( $this->mask_option );
			$mask_length 		= $string_length - $show_length;
			$not_masked_start 	= -1 * $show_length;
			$iban_not_masked	= substr( $iban, $not_masked_start, $show_length );
			$masked_string 		= str_repeat( $this->mask_symbol, $mask_length );

			$iban = $masked_string . $iban_not_masked;

		}

		return $iban;
	}

	/**
	* Show Sepa Data in plain emails
	*
	* @param WC_Order $order
	* @return void
	**/
	public function email_customer_details_plain_text( $order, $sent_to_admin ) {

		echo "\n" . strtoupper( apply_filters( 'german_market_sepa_email_headline', __( 'SEPA Direct Debit Data', 'woocommerce-german-market' ) ) ) . "\n\n";
							
		$email_fields = array();

		$sepa_fields = self::get_payment_fields();

		foreach ( $sepa_fields as $key => $sepa_field ) {

			if ( $this->encryption_possible_on() ) {
				$value = $this->decrypt( $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) ), $order );
			} else {
				$value = $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) );
			}

			if ( $key == 'iban' ) {
					    			
    			if ( $sent_to_admin ) {
    				
    				if ( $this->mask_option_admin == 'yes' ) {
    					$value = $this->mask_iban( $value );
    				}

    			} else {
	    			
	    			$value = $this->mask_iban( $value );

				}
			}

			$email_fields[ $sepa_field[ 'label' ] ] = $value;

		}

		$email_fields = apply_filters( 'german_market_sepa_email_fields', $email_fields );

		foreach ( $email_fields as $label => $value ) {
			echo preg_replace( '#<br\s*/?>#i', "\n", $label . ': ' . $value ) . "\n";
		}

	}

	/**
	* Show Sepa Data in Emails
	*
	* @wp-hook woocommerce_email_customer_details
	* @param WC_Order $order
	* @param Boolean $sent_to_admin
	* @param Boolean $plain_text
	* @return void
	**/
    public function email_customer_details( $order, $sent_to_admin, $plain_text ) {

    	if ( ! ( is_object( $order ) && method_exists( $order, 'get_payment_method' ) ) ) {
    		return;
    	}

    	if ( 'german_market_sepa_direct_debit' != $order->get_payment_method() ) {
    		return;
    	}
    	
    	$sepa_fields = self::get_payment_fields();
   		
   		// return if no sepa data is available
   		if ( empty( $sepa_fields ) ) {
   			return;
   		}

   		if ( apply_filters( 'german_market_sepa_dont_show_email_customer_details', false, $order ) ) {
   			return;
   		}

    	if ( $plain_text ) {
    		$this->email_customer_details_plain_text( $order, $sent_to_admin );
    		return;
    	}

    	$text_align = is_rtl() ? 'right' : 'left';

		?>

		<h3><?php echo apply_filters( 'german_market_sepa_email_headline', __( 'SEPA Direct Debit Data', 'woocommerce-german-market' ) ); ?></h3>
		<table id="sepa-direct-debit" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
			<tr>
				<td class="td" style="text-align:<?php echo $text_align; ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">

					<p class="text">
						
						<?php
							
							$email_fields = array();

				    		foreach ( $sepa_fields as $key => $sepa_field ) {

				    			if ( $this->encryption_possible_on() ) {
				    				$value = $this->decrypt( $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) ), $order );
				    			} else {
				    				$value = $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) );
				    			}

				    			if ( $key == 'iban' ) {
					    			
					    			if ( $sent_to_admin ) {
					    				
					    				if ( $this->mask_option_admin == 'yes' ) {
					    					$value = $this->mask_iban( $value );
					    				}

					    			} else {
						    			
						    			$value = $this->mask_iban( $value );

									}
								}

				    			$email_fields[ $sepa_field[ 'label' ] ] = $value;

				    		}

				    		$email_fields = apply_filters( 'german_market_sepa_email_fields', $email_fields );

				    		foreach ( $email_fields as $label => $value ) {

				    			if ( ! empty( $value ) ) {
				    				echo $label . ': ' . $value . '<br />';
				    			}

				    		}

						?>

					</p>
				</td>
			</tr>
		</table>

		<?php

    }

    /**
	 * Add prenotification content to the WC emails.
	 *
	 * @since 3.10.4.1
	 * @wp-hook woocommerce_email_before_order_table || woocommerce_email_after_order_table
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
    public function email_prenotification( $order, $sent_to_admin, $plain_text = false ) {

    	if ( 'german_market_sepa_direct_debit' != $order->get_payment_method() ) {
    		return;
    	}

    	if ( $sent_to_admin ) {
    		return;
    	}
    	
    	$sepa_fields = self::get_payment_fields();
   		
   		// return if no sepa data is available
   		if ( empty( $sepa_fields ) ) {
   			return;
   		}

   		$run_function = false;

   		if ( 'completed' === $this->prenotification_activaction && $order->has_status( 'completed' ) ) {
   			$run_function = true;
   		} else if ( 'processing' === $this->prenotification_activaction && $order->has_status( 'processing' ) ) {
   			$run_function = true;
   		} else if ( 'on-hold' === $this->prenotification_activaction && $order->has_status( 'on-hold' ) ) {
   			$run_function = true;
   		}

   		if ( ! WGM_Helper::method_exists( $order, 'get_currency' ) ) {
   			return;
   		}

   		if ( $run_function ) { 

   			// init vars
   			$prenotification_text 	= $this->prenotification_text;
   			$amount 				= wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) );
   			$creditor_identifier 	= $this->creditor_identifier;
   			$mandate_id 			= $order->get_meta( '_german_market_sepa_mandate_reference' );
   			if ( $this->encryption_possible_on() ) {
    			$mandate_id = $this->decrypt( $mandate_id, $order );
    		}

    		// build due date
    		$due_date = new DateTime( current_time( 'Y-m-d' ) );
    		$extra_days = intval( $this->prenotification_due_date );

    		if ( $extra_days > 0 ) {
    			$due_date->add( new DateInterval( 'P' . $extra_days . 'D' ) );
    		}
    		
    		$due_date_formatted = apply_filters( 'german_market_sepa_direct_debit_prenotification_formatted_due_date', date_i18n( get_option( 'date_format' ), $due_date->getTimestamp() ), $due_date );

    		$prenotification_text  = str_replace(

    			array( '[creditor_identifier]', '[mandate_id]', '[amount]', '[due-date]' ),
    			array( $creditor_identifier, $mandate_id, $amount, $due_date_formatted ),
    			$prenotification_text

    		);

    		if ( ! $plain_text ) {
   				?><p><?php echo wp_kses_post( wpautop( wptexturize( $prenotification_text ) ) ); ?></p><?php
   			} else {
   				echo "\n" . wp_kses_post( $prenotification_text ) . "\n";
   			}

   		}
    }

    /**
	 * Don't show prenotification content in invoice pdf
	 *
	 * @since 3.10.4.1
	 * @wp-hook wp_wc_invoice_pdf_start_template 
	 * @param void
	 */
    public function prenotification_before_invoice_pdf() {
    	remove_action( 'woocommerce_email_before_order_table', array( $this, 'email_prenotification' ), 20, 3 );
    	remove_action( 'woocommerce_email_after_order_table', array( $this, 'email_prenotification' ), 20, 3 );
    }

    /**
	 * Don't show prenotification content in invoice pdf
	 *
	 * @since 3.10.4.1
	 * @wp-hook wp_wc_invoice_pdf_end_template 
	 * @param void
	 */
    public function prenotification_after_invoice_pdf() {

    	if ( 'before' === $this->prenotification_output_in_email ) {
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_prenotification' ), 20, 3 );
		} else {
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_prenotification' ), 20, 3 );
		}
    }

    /**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

		if ( ! $sent_to_admin && 'german_market_sepa_direct_debit' === $order->get_payment_method() && apply_filters( 'german_market_sepa_email_instructions', ( $order->has_status( 'on-hold' ) || $order->has_status( 'pending' ) || $order->has_status( 'processing' ) ), $order ) ) {
			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}

		}

	}

    /**
	* Save Data in Backend
	*
	* @wp-hook woocommerce_before_order_object_save
	* @return void
	**/
    public function sava_admin_data( $order, $data_store ) {

    	if ( is_admin() && is_a( $order, 'WC_Order' ) ) {

    		$sepa_fields = self::get_payment_fields();

			foreach ( $sepa_fields as $key => $sepa_field ) {

				$post_key = '_german_market_sepa_' . str_replace( '-', '_', esc_attr( $key ) );
				
				if ( isset( $_POST[ $post_key ] ) ) {
					
					if ( $this->encryption_possible_on() ) {
						$value = $this->encrypt( $_POST[ $post_key ], $order );
					} else {
						$value = $_POST[ $post_key ];
					}

					$order->update_meta_data( $post_key, $value );
				}

			}

			if ( isset( $_POST[ '_german_market_sepa_mandate_reference' ] ) ) {
    			
    			if ( $this->encryption_possible_on() ) {
    				$value = $this->encrypt( $_POST[ '_german_market_sepa_mandate_reference' ], $order );
    			} else {
    				$value = $_POST[ '_german_market_sepa_mandate_reference' ];
    			}

    			$order->update_meta_data( '_german_market_sepa_mandate_reference', $value );
    		}

			$order->save_meta_data();

    	}
    	
    }

    /**
	* Admin Billing Fields
	*
	* @param Array $fields
	* @return Array
	**/
    public function admin_billing_fields( $fields = array(), $order = false ) {

    	if ( ! $order ) {
	    	global $post;
	    	$order = wc_get_order( $post->ID );
	    }

    	if ( ! WGM_Helper::method_exists( $order, 'get_payment_method' ) ) {
    		return $fields;
    	}

    	if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {

    		$sepa_fields = self::get_payment_fields();

    		foreach ( $sepa_fields as $key => $sepa_field ) {

    			if ( $this->encryption_possible_on() ) {
    				$value = $this->decrypt( $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) ), $order );
    			} else {
    				$value = $order->get_meta( '_german_market_sepa_' . esc_attr( $key ) );
    			}

    			$fields[ 'german-market-sepa-' . esc_attr( $key ) ] = array(
    				'label' => $sepa_field[ 'label' ],
		    		'id'  	=> '_german_market_sepa_' . esc_attr( $key ),
					'show'  => apply_filters( 'german_market_sepa_admin_fields_show', false, $key ),
					'value'	=> $value,
    			);

    		}

    		if ( $this->encryption_possible_on() ) {
				$value = $this->decrypt( $order->get_meta( '_german_market_sepa_mandate_reference' ), $order );
			} else {
				$value = $order->get_meta( '_german_market_sepa_mandate_reference' );
			}

    		$fields[ 'german-market-mandate-reference' ] = array(
    			'label' => __( 'Mandate Reference', 'woocommerce-german-market' ),
	    		'id'  	=> '_german_market_sepa_mandate_reference',
	    		'value' => $value,
				'show'  => apply_filters( 'german_market_sepa_admin_fields_show', false, 'mandate-reference' ),
    		);

    	}

    	return $fields;

    }

    /**
	* Returns empty Array if encyription is possible
	*
	* @return Array
	**/
    private function encryption_possible() {
    	
    	$errors = array();

    	if ( ! function_exists( 'random_bytes' ) ) {
    		$errors[] = __( 'Encryption is not possible because the function <code>random_bytes</codes> is not available on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' );
    	}

    	if ( ! function_exists( 'openssl_encrypt' ) ) {
    		$errors[] = __( 'Encryption is not possible because the function <code>openssl_encrypt</codes> is not available on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' );
    	}

    	if ( ! function_exists( 'openssl_decrypt' ) ) {
    		$errors[] = __( 'Encryption is not possible because the function <code>openssl_decrypt</codes> is not available on your server. Please contact your admin / webhoster.', 'woocommerce-german-market' );
    	}

    	if ( ! defined( 'GERMAN_MARKET_SEPA_ENCRYPTION' ) ) {
    		$errors[] = __( "To enable encryption, please copy the following line into your wp_config.php file:<br /><code>define( 'GERMAN_MARKET_SEPA_ENCRYPTION', 'Your Passhprase');</code><br />Replace 'Your Passphrase' with an indivudual passphrase. This passhrase should never be changed or removed. Save the passphrase locally to not forget it.", 'woocommerce-german-market' );
    	}

    	return $errors;
    }

    /**
	* Returns true if encryption is possible and enabled
	*
	* @return boolean
	**/
    private function encryption_possible_on() {
    	return empty( $this->encryption_possible() ) && $this->data_store == 'encryption';
    }

    /**
	 * Encrypt string
     *
     * @access private
	 *
	 * @param string       $string
	 * @param WC_Order|int $mixed  Can be a given order object or a customer ID.
     *
	 * @return string
	 **/
		private function encrypt( $string, $mixed ) {

	    if ( is_object( $mixed ) ) {
		    $iv = apply_filters( 'german_market_sepa_encryption_iv', substr( $mixed->get_cart_hash(), 0, 16 ), $mixed );
		} else
		if ( is_int( $mixed ) ) {
			$iv = substr( md5( $mixed ), 0, 16 );
		}
		$method = apply_filters( 'german_market_sepa_encryption_method', 'aes-256-ctr', $mixed );

		return openssl_encrypt( $string, $method, GERMAN_MARKET_SEPA_ENCRYPTION, 0, $iv );
	}

	/**
	 * Derypt string
     *
     * @access private
	 *
	 * @param string       $string
	 * @param WC_Order|int $mixed  Can be a given order object or a customer ID.
     *
	 * @return string
	 **/
	private function decrypt( $string, $mixed ) {

		if ( is_object( $mixed ) ) {
			$iv = apply_filters( 'german_market_sepa_encryption_iv', substr( $mixed->get_cart_hash(), 0, 16 ), $mixed );
		} else
		if ( is_int( $mixed ) ) {
			$iv = substr( md5( $mixed ), 0, 16 );
		}
		$method = apply_filters( 'german_market_sepa_encryption_method', 'aes-256-ctr', $mixed );

		return openssl_decrypt( $string, $method, GERMAN_MARKET_SEPA_ENCRYPTION, 0, $iv );
	}

    /**
	 * Save meta data
	 *
	 * @wp-hook woocommerce_checkout_update_order_meta
     *
     * @access public
     *
	 * @param Integer $order_id
	 * @param Array   $posted
     *
	 * @return void
	 **/
	public function update_order_meta( $order_id, $posted ) {

		$order = wc_get_order( $order_id );

		if ( WGM_Helper::method_exists( $order, 'get_payment_method' ) ) {

			if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {

			    $customer_id = $order->get_user_id(); // or $order->get_customer_id();
				$sepa_fields = self::get_payment_fields();

				foreach ( $sepa_fields as $key => $field ) {

					$field_key     = self::build_post_key( $key );
                    $user_meta_key = self::build_meta_key( $key );

                    if ( is_user_logged_in() && isset( $_POST[ 'german-market-sepa-use-payment-information' ] ) && 'saved' == $_POST[ 'german-market-sepa-use-payment-information' ] ) {

                        $post_value = get_user_meta( $customer_id, $user_meta_key, true );
                        // Decrypt stored user meta if needed.
	                    if ( $this->encryption_possible_on() ) {
		                    $post_value = $this->decrypt( $post_value, $customer_id );
	                    }
	                    if ( isset( $_POST[ $field_key ] ) ) {
	                        $_POST[ $field_key ] = $post_value;
                        }

                    } else {

	                    if ( $key == 'iban' || $key == 'bic' ) {
		                    $post_value = str_replace( ' ', '', $_POST[ $field_key ] );
		                    $post_value = strtoupper( $_POST[ $field_key ] );
	                    } else {
		                    $post_value = $_POST[ $field_key ];
	                    }

                    }

					if ( isset( $_POST[ $field_key ] ) ) {

						if ( $this->encryption_possible_on() ) {
							$value = $this->encrypt( $post_value, $order );
						} else {
							$value = $post_value;
						}

						$order->update_meta_data( '_' . str_replace( '-', '_', $field_key ), $value );
					}

				}

				// mandate reference
				$mandate_value = $this->build_mandate_reference( $order );
				if ( $this->encryption_possible_on() ) {
					$mandate_value = $this->encrypt( $mandate_value, $order );
				}

				$order->update_meta_data( '_german_market_sepa_mandate_reference', $mandate_value );

				$order->save_meta_data();

			}
		}

	}

	private function build_mandate_reference( $order ) {

		$placeholders = apply_filters( 'gm_sepa_direct_debit_mandate_ref_placeholders', array(
			'{order-id}' => $order->get_order_number(),
		), $order );

		if ( class_exists( 'WP_WC_Running_Invoice_Number_Functions' ) ) {
			$running_invoice_number = new WP_WC_Running_Invoice_Number_Functions( $order );	
			$placeholders[ '{invoice-number}' ] = $running_invoice_number->get_invoice_number();
		}

		$mandate_reference = $this->mandate_reference;

		foreach ( $placeholders as $search => $replace ) {
			$mandate_reference = str_replace( $search, $replace, $mandate_reference );
		}

		return $mandate_reference;

	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		
		// German Market styles
		?>
		<h3><?php _e( 'SEPA Direct Debit', 'woocommerce-german-market' ); ?></h3>
		<p><?php _e( 'Allows payments by SEPA direct debit.', 'woocommerce-german-market' ); 
		echo WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/sepa.mp4' ); ?></p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table> <?php
	}


	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		global $woocommerce;

		if ( is_admin() ) {

			// Init
			$possible_statuses = array(
				'pending'		=> __( 'Pending Payment', 'woocommerce-german-market' ),
				'processing'	=> __( 'Processing', 'woocommerce-german-market' ),
				'on-hold'		=> __( 'On Hold', 'woocommerce-german-market' ),
				'completed'		=> __( 'Completed', 'woocommerce-german-market' ),
			);

			$shipping_methods = array();

			foreach ( $woocommerce->shipping->get_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_method_title();
			}

			$shipping_methods [ 'no_shipping_needed' ] = __( 'No shipping needed (for virtual orders)', 'woocommerce-german-market' );

			$data_store_options = array(
				'raw' => __( 'Save raw data in orders (unencrypted)', 'woocommerce-german-market' )
			);

			if ( empty( $this->encryption_possible() ) ) {
				
				$data_store_options[ 'encryption' ] = __( 'Sava data encrypted', 'woocommerce-german-market' );
				$data_store_info = '';
				
				if ( $this->get_option( 'data_storing', 'raw' ) == 'encryption' ) {
					$data_store_info = __( 'If you deactivate encryption, all the user sepa data that has alrady been saved encrypted will not be readable.', 'woocommerce-german-market' );
				}

			} else {
				$erros = $this->encryption_possible();
				$data_store_info = implode( '<br /><br />', $erros );
			}

			// mandate reference info
			$mandate_info = apply_filters( 'gm_sepa_direct_debit_mandate_ref_placeholders_text', __( 'Enter the format of the mandate reference ID. You can (you should) use <code>{order-id}</code> as a placeholder. You can add a prefix and / or a suffix.', 'woocommerce-german-market' ) );

			if ( class_exists( 'WP_WC_Running_Invoice_Number_Functions' ) ) {
				$mandate_info = apply_filters( 'gm_sepa_direct_debit_mandate_ref_placeholders_text', __( 'Enter the format of the mandate reference ID. You can (you should) use <code>{order-id}</code> or <code>{invoice-number}</code> as a placeholder. You can add a prefix and / or a suffix.', 'woocommerce-german-market' ) );
			}

			// service fee
			if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'off' ) {
				$fee_notice = sprintf( __( 'Collect an extra service fee for "SEPA Direct Debit" payments. Enter amount in %s excluding tax.', 'woocommerce-german-market' ), esc_attr( get_option( 'woocommerce_currency' ) ) );
			} else {
				$fee_notice = sprintf( __( 'Collect an extra service fee for "SEPA Direct Debit" payments. Enter amount in %s including tax.', 'woocommerce-german-market' ), esc_attr( get_option( 'woocommerce_currency' ) ) );
			}

			// user availability
			$sentence_min_orders_1_1 = __( 'Only for registered users with at least 1 completed order', 'woocommerce-german-market' );
			$sentence_min_orders_1_2 = __( 'Only for registered users with at least 2 completed orders', 'woocommerce-german-market' );
			$min_orders_for_setting = apply_filters( 'wgm_sepa_direct_debit_min_orders', 3 );
			$sentence_min_orders_1_3 = sprintf( __( 'Only for registered users with at least %s completed orders', 'woocommerce-german-market' ), $min_orders_for_setting );
			$sentence_min_orders_2 = sprintf( __( 'Choose whether "SEPA Direct Debit" is available for all users, only registered users or only registered users that have at least 1, 2 or %s completed order.', 'woocommerce-german-market' ), $min_orders_for_setting );

			// Set form fields
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable SEPA Direct Debit', 'woocommerce-german-market' ),
					'label' => __( 'Enable SEPA Direct Debit', 'woocommerce-german-market' ),
					'type' => 'checkbox',
					'description' => '',
					'default' => 'no'
				),
				'title' => array(
					'title' => __( 'Title', 'woocommerce-german-market' ),
					'type' => 'text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-german-market' ),
					'default' => __( 'SEPA Direct Debit', 'woocommerce-german-market' ),
					'desc_tip'      => true,
				),
				'description' => array(
					'title' => __( 'Description', 'woocommerce-german-market' ),
					'type' => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website in order.', 'woocommerce-german-market' ),
					'default' => '',
				),
				
				'instructions' => array(
					'title' => __( 'Instructions', 'woocommerce-german-market' ),
					'type' => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website and in the order emails.', 'woocommerce-german-market' ),
					'default' => __( 'The amount will be automatically debited from your account via SEPA direct debit.', 'woocommerce-german-market' )
				),

				'creditor_information' => array(
					'title' => __( 'Creditor Information', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Your company information.', 'woocommerce-german-market' ),
					'default' => '',
				),

				'creditor_account_holder' => array(
					'title' => __( 'Account Holder', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Name of your Account Holder.', 'woocommerce-german-market' ),
					'default' => '',
				),

				'iban' => array(
					'title' => __( 'IBAN', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Your bank IBAN.', 'woocommerce-german-market' ),
					'default' => '',
				),

				'bic' => array(
					'title' => __( 'BIC', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Your bank BIC.', 'woocommerce-german-market' ),
					'default' => '',
				),

				'creditor_identifier' => array(
					'title' => __( 'Creditor Identifier Number', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Your Creditor Identifier Number.', 'woocommerce-german-market' ),
					'default' => '',
				),

				'direct_debit_mandate' => array(
					'title' => __( 'Direct Debit Mandate', 'woocommerce-german-market' ),
					'type' => 'textarea',
					'css' => 'min-height: 300px;',
					'desc_tip' => __( 'This text will be used as preview direct debit mandate and as email template text.', 'woocommerce-german-market' ),
					'description' => __( 'You can use the following placeholders:', 'woocommerce-german-market' ) . ' <code>[creditor_information], [creditor_identifier], [creditor_account_holder], [creditor_iban], [creditor_bic], [mandate_id], [street], [city], [postcode], [country], [date], [account_holder], [account_iban], [account_bic]</code>, <code>[amount]</code>',
					'default' => __( '[creditor_information]
Creditor Identifier: [creditor_identifier]
Mandate Reference Number: [mandate_id].
<h4>SEPA - Direct Debit Mandate</h4>
I hereby authorize the payee to collect a payment from my account by direct debit. At the same time, I instruct my credit institution to settle the direct debit drawn by the payee on my account.
Note: I can request the refund of the debited amount within eight weeks, beginning with the debiting date. The conditions of my credit credit institution are applied here.

<strong>Payer:</strong>
Account Holder: [account_holder]
Street: [street]
Postcode: [postcode]
City: [city]
Country: [country]
IBAN: [account_iban]
BIC: [account_bic]

[city], [date], [account_holder]

This letter has been created automatically and is valid without signature.

<hr />
Please note: The deadline for the advance information of the SEPA direct debit will be reduced to one day.', 'woocommerce-german-market' ),
				),

				'checkbox_confirmation' => array(
					'title' => __( 'Checkbox Confirmation', 'woocommerce-german-market' ),
					'type' => 'select',
					'options' => array(
						'activated'   => __( 'Activated', 'woocommerce-german-market' ),
						'deactivated' => __( 'Deactivated', 'woocommerce-german-market' ),
					),
					'desc_tip' => __( 'Activate the checkbox confirmation during checkout and the preview of the direct debit mandate.', 'woocommerce-german-market' ),
					'default' => 'activated',
				),

				'checkbox_confirmation_text' => array(
					'title' => __( 'Checkbox Text', 'woocommerce-german-market' ),
					'type' => 'text',
					'desc_tip' => __( 'Choose the label for the checkbox text. You can use [link]sepa direct debit mandate[/link] as a placeholder.', 'woocommerce-german-market' ),
					'default' => __( 'I agree to the [link]sepa direct debit mandate[/link].', 'woocommerce-german-market' ),
				),

				'mandate_reference' => array(
					'title'			=> __( 'Mandate Reference', 'woocommerce-german-market' ),
					'type'			=> 'text',
					'default'		=> __( 'MANDATE', 'woocommerce-german-market' ) . '{order-id}',
					'description'	=> $mandate_info,
				),

				'iban_mask' => array(
					'title'			=> __( 'IBAN Masking', 'woocommerce-german-market' ) . 
					' ' . __( 'in Customer Emails', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> apply_filters( 'gm_sepa_direct_debit_masking', array(
						0	=> __( 'Don\'t mask IBAN', 'woocommerce-german-market' ),
						1 	=> __( 'Only show last digit', 'woocommerce-german-market' ),
						2   => __( 'Only show last two digitis', 'woocommerce-german-market' ),
						3	=> __( 'Only show last three digitis', 'woocommerce-german-market' ),
						4	=> __ ('Only show last four digits', 'woocommerce-german-market' ),
						5	=> __( 'Only show last five digits', 'woocommerce-german-market' )
					) ),
					'default'		=> 3,
					'desc_tip'		=> __( 'You can mask the IBAN in emails.', 'woocommerce-german-market' ),
				),

				'iban_mask_admin' => array(
					'title'			=> __( 'IBAN Masking in Admin Emails', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> apply_filters( 'gm_sepa_direct_debit_masking_admin', array(
						'yes'		=> __( 'Mask IBAN in admin emails as in customer emails', 'woocommerce-german-market' ),
						'no'		=> __( 'Do not mask IBAN in emails send to admin', 'woocommerce-german-market' ),
					) ),
					'default'		=> 'yes',
					'desc_tip'		=> __( 'IBAN masking can be turned off when the email is sent to an admin.', 'woocommerce-german-market' ),
				),

				'iban_mask_symbol' => array(
					'title'			=> __( 'IBAN Masking Symbol', 'woocommerce-german-market' ),
					'type'			=> 'text',
					'default'		=> '*',
					'custom_attributes' => array(
						'maxlength' => 1
					),
					'css'			=> 'width: 30px;',
					'desc_tip'		=> __( 'If you mask the IBAN, you can choose the symbol used to mask the digits.', 'woocommerce-german-market' ),
				),

				'data_storing' => array(
					'title'			=> __( 'Data Storing in Orders', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'raw',
					'options' 		=> $data_store_options, 
					'desc_tip'		=> __( 'How to save the additional data in orders.', 'woocommerce-german-market' ),
					'description'	=> $data_store_info
				),

				'order_status' => array(
					'title'			=> __( 'Order Status', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'processing',
					'options'		=> $possible_statuses,
					'desc_tip'		=> __( 'Choose the order status of the customer\'s order after the customer finished the order process. We recommend to the set the option "Processing".', 'woocommerce-german-market' ),
				),

				'woocommerce_german_market_sepa_direct_debit_fee' => array(
					'title' 		=> __( 'Service Fee', 'woocommerce-german-market' ),
					'type' 			=> 'text',
					'css'  			=> 'width:50px;',
					/* translators: %s = default currency, e.g. EUR */
					'desc_tip' 		=> $fee_notice,
					'default' 		=> '',
					'description'	=> __( '<span style="color: #f00;">Attention!</span> Please inform yourself about the legalities regarding the charging of fees for payments:<br><a href="https://www.it-recht-kanzlei.de/verbot-extra-kosten-kartenzahlungen.html" target="_blank">https://www.it-recht-kanzlei.de/verbot-extra-kosten-kartenzahlungen.html</a>', 'woocommerce-german-market' ),
				),

				'user_availability' => array(
					'title'			=> __( 'User Availability', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'all_users',
					'options'		=> array(
							'all_users'					=> __( 'All Users', 'woocommerce-german-market' ),
							'registered_users'			=> __( 'Only for registered users', 'woocommerce-german-market' ),
							'completed_order_users'		=> $sentence_min_orders_1_1,
							'completed_order_users_2'	=> $sentence_min_orders_1_2,
							'completed_order_users_3'	=> $sentence_min_orders_1_3
					),
					'description'	=> $sentence_min_orders_2
				),

				'enable_for_methods' => array(
					'title' 		=> __( 'Enable for shipping methods', 'woocommerce-german-market' ),
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'			=> 'width: 450px;',
					'default' 		=> '',
					'description' 	=> __( 'If "SEPA Direct Debit" is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-german-market' ),
					'options'		=> $shipping_methods,
					'desc_tip'      => true,
				),

				// CHECKOUT & 'MY ACCOUNT' SECTION
				'my_account_title' => array(
					'title' 		=> __( 'Save SEPA data of a user for reuse', 'woocommerce-german-market' ),
					'type' 			=> 'title',
				),

				'checkout_customer_can_save_payment_information' => array(
					'title'			=> __( 'Optional saving on the checkout page', 'woocommerce-german-market' ),
					'description' 	=> __( 'If this setting is activated, a logged-in customer has the option on the checkout page to save his SEPA data in order to use them again for the next purchase. There is then no need to enter the SEPA data again. Alternatively, new SEPA payment data can also be entered when making a new purchase.', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> array(
						'off'	=> __( 'Deactivated', 'woocommerce-german-market' ),
						'on' 	=> __( 'Activated', 'woocommerce-german-market' ),
					),
					'default'		=> 'off',
					'desc_tip'		=> true,
				),

				'my_account_customer_can_edit_payment_information' => array(
					'title'			=> __( 'Edit SEPA data on "My Account" page', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> array(
						'off'	=> __( 'Deactivated', 'woocommerce-german-market' ),
						'on' 	=> __( 'Activated', 'woocommerce-german-market' ),
					),
					'default'		=> 'off',
					'description'	=> __( 'If this setting is enabled, the customer can edit, delete or add their SEPA payment details on the "My Account" page.', 'woocommerce-german-market' ),
					'desc_tip'		=> true,
				),

				'delete_stored_sepa_payment_information' => array(
					'title'			=> __( 'Delete stored payment information?', 'woocommerce-german-market' ),
					'label'         => __( 'Yes, I would like to delete the stored SEPA payment data of all users.', 'woocommerce-german-market' ),
					'type'          => 'checkbox',
					'description'   => __( 'If this setting is enabled, the SEPA payment data of all customers stored in the customer profile will be deleted in a background process.', 'woocommerce-german-market' ),
					'desc_tip'		=> true,
					'default'       => 'no'
				),

				'sepa_mandate_email_title' => array(
					'title' 		=> __( 'Sepa Mandate Email', 'woocommerce-german-market' ),
					'type' 			=> 'title',
					'description'	=> WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/archivierung-sepa-lastschriftmandate.mp4' ) . '<br />' . __( 'This email is sent after the cusomer has finished the order process.', 'woocommerce-german-market' )
				),

				'sepa_mandate_email_subject' => array(
					'title'			=> __( 'Email Subject', 'woocommerce-german-market' ),
					'type'			=> 'text',
					'default'		=> __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' ),
				),

				'sepa_mandate_email_heading' => array(
					'title'			=> __( 'Email Heading', 'woocommerce-german-market' ),
					'type'			=> 'text',
					'default'		=> __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' ),
				),

				'sepa_mandate_email_type' => array(
					'title'			=> __( 'Email Type', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'html',
					'options'		=> array(
						'html'	=> __( 'HTML', 'woocommerce-german-market' ),
						'plain' => __( 'Plain Text', 'woocommerce-german-market' )
					)
				),

				'sepa_mandate_email_recipient' => array(
					'title' => __( 'Direct Debit Mandat Email Recipients', 'woocommerce-german-market' ),
					'type' => 'select',
					'options' => array(
						'customer'   		 => __( 'Send Email only to customer', 'woocommerce-german-market' ),
						'customer_and_admin' => __( 'Send Email to customer and admin', 'woocommerce-german-market' ),
					),
					'desc_tip' => __( 'The email with the Direct Debit Mandat can be send to the admin, too.', 'woocommerce-german-market' ),
					'default' => 'customer_and_admin',
				),

				'sepa_mandate_email_pdf' => array(
					'title' => __( 'PDF Attachement for Direct Debit Mandat Email', 'woocommerce-german-market' ),
					'type' => 'select',
					'options' => array(
						'no'   		 		 => __( 'No PDF attachement', 'woocommerce-german-market' ),
						'admin' 			 => __( 'PDF attachment in admin email', 'woocommerce-german-market' ),
						'customer' 			 => __( 'PDF attachment in customer email', 'woocommerce-german-market' ),
						'admin_and_customer' => __( 'PDF attachment in admin email and customer email', 'woocommerce-german-market' ),
					),
					'desc_tip' => __( 'A PDF file with the Dircet Debit Mandat can be attached additionally in the Direct Debit Mandat email.', 'woocommerce-german-market' ),
					'default' => 'no',
				),

				'sepa_mandate_email_admin_mask_iban' => array(
					'title'			=> __( 'IBAN Masking for admin', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> apply_filters( 'gm_sepa_direct_debit_masking', array(
						'off'	=> __( 'Don\'t mask IBAN', 'woocommerce-german-market' ),
						'on' 	=> __( 'Mask IBAN', 'woocommerce-german-market' ),
					) ),
					'default'		=> 'off',
					'desc_tip'		=> __( 'Probably, you do not want the iban to be masked when information is send to the admin. E.g. when use this email for archiving.', 'woocommerce-german-market' ),
				),

				'sepa_mandate_email_address' => array(
					'title'			=> __( 'Admin Email Address', 'woocommerce-german-market' ),
					'type'			=> 'text',
					'default'		=> get_option( 'admin_email' ),
				),

				// Prenotification
				'prenotification_api_title' => array(
					'title' 		=> __( 'Prenotification', 'woocommerce-german-market' ),
					'type' 			=> 'title',
				),

				'prenotification_activaction' => array(
					'title'			=> __( 'Activate prenotification in WooCommerce emails', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> array(
						'off'			=> __( 'Deactivated', 'woocommerce-german-market' ),
						'completed' 	=> __( 'Activated for status "Completed"', 'woocommerce-german-market' ),
						'processing' 	=> __( 'Activated for status "Processing"', 'woocommerce-german-market' ),
						'on-hold'		=> __( 'Activated for status "On-hold"', 'woocommerce-german-market' ),
					),
					'default'		=> 'off'
				),

				'prenotification_due_date' => array(
					'title'			=> __( 'Due Date in prenotification, X days after sending the e-mail', 'woocommerce-german-market' ),
					'type'			=> 'number',
					'default'		=> 14,
					'custom_attributes' => array(
							'step' 	=> '1',
							'min'	=> '0',
						),
					'css'			=> 'width: 50px;',
					'description'	=> __( 'The prenotification must include a due date. This due date will be the date on which email with the prenotification was sent, plus the here set number of days.', 'woocommerce-german-market' ),
				),

				'prenotification_output_in_email' => array(
					'title'			=> __( 'Output in email', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'after',
					'options'		=> array(
						'before'	=> __( 'Before order details', 'woocommerce-german-market' ),
						'after' 	=> __( 'After order details', 'woocommerce-german-market' ),
					),
				),

				'prenotification_text' => array(
					'title'			=> __( 'Prenotification Text', 'woocommerce-german-market' ),
					'type' 			=> 'textarea',
					'default'		=> __( 'On [due date] or in up to three subsequent days, we will execute a SEPA direct debit of [amount] via the SEPA mandate [mandate_id] as instructed. The debit can be identified by the creditor identifier number [creditor_identifier].', 'woocommerce-german-market' ),
					'description' 	=> __( 'You can use the following placeholders:', 'woocommerce-german-market' ) . ' <code>[creditor_identifier]</code>, <code>[mandate_id]</code>, <code>[amount]</code>, <code>[due-date]</code>',
					'css' 			=> 'min-height: 100px;',
				),

				// XML Export
				'xml_export' => array(
					'title' 		=> __( 'XML Export', 'woocommerce-german-market' ),
					'type' 			=> 'title',
					'id'			=> 'german-market-xml-export',
				),

				'pain_format' => array(
					'title'			=> __( 'Pain Format', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'pain.008.002.02',
					'options'		=> array(
							'SEPA_PAIN_008_001_02'					=> 'pain.008.001.02',
							'SEPA_PAIN_008_001_02_AUSTRIAN_003'		=> 'pain.008.001.02.austrian.003',
							'SEPA_PAIN_008_002_02'					=> 'pain.008.002.02',
							'SEPA_PAIN_008_003_02'					=> 'pain.008.003.02'
					),
					'description'	=> __( 'You have to enter the XML Export Pain format that your banks needs. Maybe your bank needs the format pain.008.001.02, pain.008.002.02 or pain.008.003.02 For further information, please ask your credit institution.', 'woocommerce-german-market' )
				),

				'xml_include_refunds' => array(
		   			'title'			=> __( 'Include refunds in amounts of XML files', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'exlcude',
					'options'		=> array(
							'exclude'		=> __( 'Exlude Refunds', 'woocommerce-german-market' ),
							'include'		=> __( 'Include Refunds', 'woocommerce-german-market' ),
						),
					'description'	=> __( 'The amounts of the order can include refunds that you already have made or not.', 'woocommerce-german-market' ),
		   		),

		   );

		   // Due date in XML Files
		   if ( get_option( 'woocommerce_de_due_date', 'off' ) == 'on' ) {

		   		$this->form_fields[ 'xml_due_date_option' ] = array(
		   			'title'			=> __( 'Due Date in XML File', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'default'		=> 'x_days',
					'options'		=> array(
							'min'		=> __( 'Earliest Due Date of all Orders included in the XML file, but not before today', 'woocommerce-german-market' ),
							'max'		=> __( 'Latest Due Date of all Orders included in the XML file', 'woocommerce-german-market' ),
							'x_days'	=> __( '"X" days after download, use the option below', 'woocommerce-german-market' ),
						),
					'description'	=> __( 'The SEPA XML-File has one specific due date, even if there the orders that are included in the XML file have different due dates. You can choose here which due date should be used for the XML File.', 'woocommerce-german-market' ),
		   		);

		   		$this->form_fields[ 'xml_due_date_option_days' ] = array(
		   			'title'			=> __( 'Due Date: X days after Download (today)', 'woocommerce-german-market' ),
					'type'			=> 'number',
					'default'		=> 1,
					'custom_attributes' => array(
							'step' 	=> '1',
							'min'	=> '0',
						),
					'css'			=> 'width: 50px;',
					'description'	=> __( 'If you set the the option above to <em>"X" days after download<em>, set up here the days.', 'woocommerce-german-market' ) . ' ' . __( 'The due date will be the day of the XML download (today) plus these number of days.', 'woocommerce-german-market' ),
		   		);

		   } else {

		   		$this->form_fields[ 'xml_due_date_option_days' ] = array(
		   			'title'			=> __( 'Due Date in XML File (Number of Days after Download)', 'woocommerce-german-market' ),
					'type'			=> 'number',
					'default'		=> 1,
					'custom_attributes' => array(
							'step' 	=> '1',
							'min'	=> '0',
						),
					'css'			=> 'width: 50px;',
					'description'	=> __( 'The SEPA XML-File has one specific due date, even if there the orders that are included in the XML file have different due dates. You can choose here which due date should be used for the XML File.', 'woocommerce-german-market' ) . ' ' . __( 'The due date will be the day of the XML download (today) plus these number of days.', 'woocommerce-german-market' ),
		   		);

		   }

		   // REST API
		   $this->form_fields[ 'rest_api_title' ] = array(
					'title' 		=> __( 'REST-API', 'woocommerce-german-market' ),
					'type' 			=> 'title',
			);

		   $this->form_fields[ 'rest_api_activaction' ] = array(
					'title'			=> __( 'Include SEPA data in REST API for orders', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> array(
						'off'	=> __( 'Deactivated', 'woocommerce-german-market' ),
						'on' 	=> __( 'Activated', 'woocommerce-german-market' ),
					),
					'default'		=> 'off'
			);

		   $this->form_fields[ 'rest_api_iban_masking' ] = array(
					'title'			=> __( 'Mask IBAN in REST API', 'woocommerce-german-market' ),
					'type'			=> 'select',
					'options'		=> array(
						'off'	=> __( 'Deactivated', 'woocommerce-german-market' ),
						'on' 	=> __( 'Activated', 'woocommerce-german-market' ),
					),
					'desc_tip'		=> __( 'If activated, the general masking options are used that can be set above.', 'woocommerce-german-market' ),
					'default'		=> 'off'
			);

		}
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @access public
	 * @return bool
	 */
	function is_available() {
		
		global $woocommerce;

		$chosen_method = null;

		if ( ( $this->enabled != 'no' ) && ( ! empty( $this->enable_for_methods ) ) ) {

			if ( is_wc_endpoint_url( get_option( 'woocommerce_checkout_pay_endpoint' ) ) ) {

                $order_id = absint( get_query_var( 'order-pay' ) );
				$order = new WC_Order( $order_id );

				if ( ! $order->get_shipping_method() )
					return $this->available_if_no_shipping_needed();

				$chosen_method = $order->get_shipping_method();

			} elseif ( ( WC()->cart ) && ( ! WC()->cart->needs_shipping() ) ) {
				return $this->available_if_no_shipping_needed();
			} else {
				
				if ( isset( $woocommerce->session->chosen_shipping_methods ) ) {
					$chosen_method = $woocommerce->session->chosen_shipping_methods;
				}
			
			}

			$found = false;

			if ( $chosen_method ) {

				if ( is_array( $chosen_method ) && count( $chosen_method ) == 1 ) {
					$chosen_method = $chosen_method[ 0 ];
				}

				foreach ( $this->enable_for_methods as $method_id ) {
					if ( ( is_string( $chosen_method ) && strpos( $chosen_method, $method_id ) === 0 ) ||
						 ( is_array( $chosen_method ) && in_array( $method_id, $chosen_method ) ) ) {
						$found = true;
						break;
					}
				}

			}
			
			return $found;
			
		}

		// user availability
		$user_availability = $this->user_availability;
		$user_id = get_current_user_id();

		if ( $user_availability == 'registered_users' ) {
			
			if ( ! $user_id > 0 ) {
				return false;
			}

		} else if ( $user_availability == 'completed_order_users' || $user_availability == 'completed_order_users_2' || $user_availability == 'completed_order_users_3' ) {

			if ( ! $user_id > 0 ) {
				return false;
			}

			$max_check = 1;

			if ( $user_availability == 'completed_order_users_2' ) {
				$max_check = 2;
			} else if ( $user_availability == 'completed_order_users_3' ) {
				$max_check = apply_filters( 'wgm_sepa_direct_debit_min_orders', 3 );
			}
			
			$orders = get_posts( array(
			    'numberposts' => -1,
			    'meta_key'    => '_customer_user',
			    'meta_value'  => $user_id,
			    'post_type'   => 'shop_order',
			    'post_status' => 'wc-completed',
			) );

			if ( count( $orders ) < $max_check ) {
				return false;
			}

		}

		return parent::is_available();
	}

	/**
	 * Returns true if and only if 
	 * The order does not need shipping (is virtual), no shipping methods are available, the user selected "no shipping needed" in "enable for methods"
	 *
	 * @access private
	 * @since 3.5.7
	 * @return Boolean
	 */
	private function available_if_no_shipping_needed() {

		if ( in_array( 'no_shipping_needed', $this->enable_for_methods ) && 'yes' === $this->enabled ) {
			return true;
		}

		return false;
	}


	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
	function process_payment ( $order_id ) {
		
		global $woocommerce;

		$order = wc_get_order( $order_id );

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$this->update_order_meta( $order_id, array() );
		}

		// Set oder status
		$order->update_status( $this->order_status, __( 'SEPA Direct Debit', 'woocommerce-german-market' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order->get_id() );

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Send sepa mandate email
		if ( apply_filters( 'gm_sepa_send_sepa_email', true ) ) {
			$this->send_sepa_mail( $order );
	    }

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_order_received_url()
		);
	}

	/**
	 * send order id
	 *
	 * @access public
	 * @param WC_Order $order
	 * @return void
	 */
	public function send_sepa_mail( $order ) {

		$sepa_mail = include( untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'WGM_Email_Sepa.php' );

		$args = array(

            'street'    			=> $order->get_billing_address_1(),
            'city'      			=> $order->get_billing_city(),
            'zip'       			=> $order->get_billing_postcode(),
            'country'   			=> $order->get_billing_country(),
            'holder'    			=> $order->get_meta( '_german_market_sepa_holder' ),
            'iban'      			=> $order->get_meta( '_german_market_sepa_iban' ),
            'bic'      				=> $order->get_meta( '_german_market_sepa_bic' ),
            'mandate_id'			=> $order->get_meta( '_german_market_sepa_mandate_reference' ),
            'date'					=> date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) ),
            'amount'				=> wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ),

            'email_subject' 		=> $this->email_subject,
            'email_heading' 		=> $this->email_heading,
            'email_type'			=> $this->email_type,
            'email_admin'			=> $this->email_admin,
            'email_pdf'				=> $this->email_pdf,
            'email_admin_iban_mask' => $this->email_admin_mask,
            'email_admin_address'	=> $this->email_admin_address,
        );

		if ( $this->encryption_possible_on() ) {
			$args[ 'holder' ] 		= $this->decrypt( $args[ 'holder' ], $order );
			$args[ 'iban' ] 		= $this->decrypt( $args[ 'iban' ], $order );
			$args[ 'bic' ] 			= $this->decrypt( $args[ 'bic' ], $order );
			$args[ 'mandate_id' ] 	= $this->decrypt( $args[ 'mandate_id' ], $order );
		}

		$args[ 'unmasked_iban' ] = $args[ 'iban' ];
		$args[ 'iban' ] = $this->mask_iban( $args[ 'iban' ] );

		$sepa_mail->set_args( $args );

        $sepa_mail->trigger( $order->get_id() );
	}


	/**
	 * Output for the order received page.
	 *
	 * @access public
	 * @return void
	 */
	function thankyou() {
		echo $this->instructions != '' ? wpautop( $this->instructions ) : '';
	}

	/**
	* Get Payment Fields
	* @return Array
	**/
	public static function get_payment_fields(){

		return  apply_filters( 'gm_sepa_fields_in_checkout', array(

			'holder'			=> array(
				'label'			=> __( 'Account Holder', 'woocommerce-german-market' ),
				'required'		=> true,
				'autocomplete'	=> 'off',
				'placeholder'	=> '',
				'value'			=> ''
			),

			'iban'			=> array(
				'label'			=> __( 'IBAN', 'woocommerce-german-market' ),
				'required'		=> true,
				'autocomplete'	=> 'off',
				'placeholder'	=> '',
				'value'			=> ''
			),

			'bic'			=> array(
				'label'			=> __( 'BIC/SWIFT', 'woocommerce-german-market' ),
				'required'		=> true,
				'autocomplete'	=> 'off',
				'placeholder'	=> '',
				'value'			=> '',
				'p-style'		=> apply_filters( 'gm_sepa_fields_in_checkout_bic_p_style', 'margin: 0 0 1.5em;' ) // looks better in theme twentyseventeen
			),

		) );
	}

	/**
	 * Additonal Checkout fields
	 *
	 * @return void
	 */
	public function payment_fields() {
	    global $wp;

		$description        = apply_filters( 'gm_sepa_description_in_checkout', $this->description );
		$is_myaccount_page  = false;
		$stored_iban_string = '';

		if ( trailingslashit( home_url( $wp->request ) ) == trailingslashit( wc_get_account_endpoint_url( 'add-payment-method' ) ) ) {
			$is_myaccount_page = true;
		}

		if ( trim( $description != '' ) ) {
			echo '<p class="german-market-sepa-description">' . $description . '</p>';
		}

		$sepa_fields_in_checkout = self::get_payment_fields();

		if ( empty( $sepa_fields_in_checkout ) ) {
			return;
		}

		$has_saved_payment_information = false;
		if ( is_user_logged_in() && ( 'on' == $this->customer_can_save_payment_information ) ) {
			$current_user_id = get_current_user_id();
			foreach ( $sepa_fields_in_checkout as $key => $field ) {
				$user_meta_key = self::build_meta_key( $key );
				$value         = get_user_meta( $current_user_id, $user_meta_key, true );
				if ( '' != $value ) {
				    $has_saved_payment_information = true;
					if ( 'iban' == $key ) {
						if ( $this->encryption_possible_on() ) {
							$value = $this->decrypt( $value, $current_user_id );
						}
						$stored_iban_string = '<br><span>' . strtoupper( $field[ 'label' ] ) . ': ' . $this->mask_iban( $value ) . '</span>';
					}
				}
			}
		}

		?>
        <fieldset id="german-market-sepa-checkout-fields">

			<?php

			$defaults = array(
				'label'			=> '',
				'value' 		=> '',
				'required'		=> true,
				'placeholder'	=> '',
				'value'			=> '',
				'autocomplete'	=> 'off',
				'p-style'		=> apply_filters( 'gm_sepa_fields_in_checkout_default_p_style', '' ),
			);

            if ( is_user_logged_in() && ( 'on' == $this->customer_can_save_payment_information ) && ( true === $has_saved_payment_information ) && ( false === $is_myaccount_page ) ) {

            	$checked_saved_option	= true === $has_saved_payment_information ? 'checked' : '';
            	$checked_new_option  	= '';

            	if ( is_wc_endpoint_url( 'order-pay' ) ) {
            		if ( isset( $_REQUEST[ 'german-market-sepa-use-payment-information' ] ) ) {
            			if ( 'new' === $_REQUEST[ 'german-market-sepa-use-payment-information' ] ) {
            				$checked_saved_option 	= '';
            				$checked_new_option		= 'checked';
            			}
            		}
            	}

				?>
                <div class="german-market-sepa-radio-wrapper">
                    <div class="german-market-sepa-radio-wrapper-content">
                        <input type="radio"
                               id="german_market_sepa_use_payment_information"
                               name="german-market-sepa-use-payment-information"
                               value="saved"
                               class="radio-button"
                               <?php echo $checked_saved_option; ?>
                        />
                        <label for="german_market_sepa_use_payment_information"><?php echo apply_filters( 'german_market_sepa_checkout_used_stored_data', __( 'Use stored SEPA payment data', 'woocommerce-german-market' ) ); ?><?php echo $stored_iban_string; ?></label>
                    </div>
                    <div class="german-market-sepa-radio-wrapper-content">
                        <input type="radio"
                               id="german_market_sepa_use_payment_information_new"
                               name="german-market-sepa-use-payment-information"
                               value="new"
                               class="radio-button"
                               <?php echo $checked_new_option; ?>
                        />
                        <label for="german_market_sepa_use_payment_information_new"><?php echo apply_filters( 'german_market_sepa_checkout_enter_new_data', __( 'Enter new SEPA payment data', 'woocommerce-german-market' ) ); ?></label>
                    </div>
                </div>
				<?php
			}

			foreach ( $sepa_fields_in_checkout as $key => $field ) {

				$field         = wp_parse_args( $field, $defaults );
				$user_meta_key = self::build_meta_key( $key );

				$p_style = $field[ 'p-style' ] != '' ? ' style="'. $field[ 'p-style' ] . '"'  : '';

				?><p class="form-row form-row-wide"<?php echo $p_style;?>><?php

				if ( $field[ 'label' ] != '' ) { ?>
                    <label for="german-market-sepa-<?php echo esc_attr( $key );?>"><?php echo $field[ 'label' ]; ?>
						<?php if ( $field[ 'required' ] ) { ?>
                            <abbr class="required" title="<?php echo __( 'required', 'woocommerce-german-market' ); ?>">*</abbr>
						<?php } ?>
                    </label> <?php
				}

				if ( is_wc_endpoint_url( 'order-pay' ) ) {
					if ( isset( $_REQUEST[ 'german-market-sepa-' . $key ] ) ) {
						$field[ 'value' ] = $_REQUEST[ 'german-market-sepa-' . $key ];
					} else {
						$field[ 'value' ] = '';
					}
				}

				?>
                <input type="text"
                       id="german-market-sepa-<?php echo esc_attr( $key );?>"
                       name="german-market-sepa-<?php echo esc_attr( $key );?>"
                       value="<?php echo esc_attr( $field[ 'value' ] ); ?>"
                       autocomplete="<?php echo esc_attr( $field[ 'autocomplete' ] ); ?>"
                       placeholder="<?php echo esc_attr( $field[ 'placeholder' ] ); ?>"
                       class="gm-required-<?php echo $field[ 'required' ] ? 'yes' : 'no'; ?>"
                />
                </p>
                <?php

			}

			if ( true === $is_myaccount_page ) {
                if ( true === $has_saved_payment_information ) {
	                echo '<p>' . __( 'If you press the "Add payment method" button, your already saved SEPA payment information will be overwritten.', 'woocommerce-german-market' ) . '</p>';
	                echo '<p>' . __( 'The changed payment information will be effective from the next order.', 'woocommerce-german-market' ) . '</p>';
                } else {
	                echo '<p>' . __( 'Please fill in the required fields.', 'woocommerce-german-market' ) . '</p>';
                }
            }

			if ( is_user_logged_in() && ( false === $is_myaccount_page ) && ( 'on' == $this->customer_can_save_payment_information ) ) {
				if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
					?>
	                <div class="german-market-sepa-save-payment-information-checkbox" style="<?php echo ( true === $has_saved_payment_information ? 'display: none;' : '' ); ?>">
	                    <input type="checkbox"
	                           id="german-market-sepa-save-payment-information"
	                           name="german-market-sepa-save-payment-information"
	                           value="yes"
	                    />
	                    <label for="german-market-sepa-save-payment-information"><?php echo apply_filters( 'german_market_sepa_checkout_save_data', __( 'Save SEPA payment data', 'woocommerce-german-market' ) ); ?></label>
	                </div>
					<?php
				}
			}

			// support for manual order confirmation
			if ( is_wc_endpoint_url( 'order-pay' ) ) {

				$order_id = wc_get_order_id_by_order_key( $_REQUEST[ 'key' ] );
				$the_order = wc_get_order( $order_id );

				?>
                <input type="hidden" name="billing_address_1" value="<?php echo $the_order->get_billing_address_1(); ?>" />
                <input type="hidden" name="billing_postcode"  value="<?php echo $the_order->get_billing_postcode(); ?>" />
                <input type="hidden" name="billing_city"      value="<?php echo $the_order->get_billing_city(); ?>" />
                <input type="hidden" name="billing_country"   value="<?php echo $the_order->get_billing_country(); ?>" />
				<?php

			}

			?>

        </fieldset>
		<?php

	}

	/**
	* Validate Additional Fields if second checkout page is enabled
	*
	* @wp-hook gm_checkout_validation called in inc/WGM_Sepa_Direct_debit
	* @param Integer $error_count
	* @return Integer
	**/
	public static function validate_required_fields( $error_count = 0 ) {

		if ( isset( $_REQUEST[ 'payment_method' ] ) && $_REQUEST[ 'payment_method' ] == 'german_market_sepa_direct_debit' ) {

            if ( ! isset( $_REQUEST[ 'german-market-sepa-use-payment-information' ] ) || ( isset( $_REQUEST[ 'german-market-sepa-use-payment-information' ] ) && 'new' == $_REQUEST[ 'german-market-sepa-use-payment-information' ] ) ) {

	            $sepa_fields_in_checkout = self::get_payment_fields();

	            foreach ( $sepa_fields_in_checkout as $key => $field ) {

		            if ( isset( $field[ 'required' ] ) && $field[ 'required' ] ) {

			            $request_key   = self::build_post_key( $key );
			            $entered_value = $_REQUEST[ $request_key ];

			            if ( trim( $entered_value ) == '' ) {

				            wc_add_notice( sprintf( __( '<strong>%s</strong> is a required field.', 'woocommerce-german-market' ), $field[ 'label' ] ), 'error' );
				            $error_count ++;
			            }

		            }
	            }

	            // validate iban
	            if ( isset( $_REQUEST[ 'german-market-sepa-iban' ] ) && trim( $_REQUEST[ 'german-market-sepa-iban' ] ) != '' ) {

		            // include library for iban validation
		            include_once( untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'php-iban' . DIRECTORY_SEPARATOR . 'oophp-iban.php' );

		            $validator  = new IBAN( trim( $_REQUEST[ 'german-market-sepa-iban' ] ) );
		            $validation = $validator->Verify();

		            if ( ! $validation ) {
			            wc_add_notice( apply_filters( 'gm_sepa_direct_debit_iban_validation_error_notice', __( 'Please enter a valid IBAN.', 'woocommerce-german-market' ) ), 'error' );
			            $error_count ++;
		            }

	            }

	            // validate bic
	            if ( isset( $_REQUEST[ 'german-market-sepa-bic' ] ) && trim( $_REQUEST[ 'german-market-sepa-bic' ] ) != '' ) {

		            if ( ! preg_match( '/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/', $_REQUEST[ 'german-market-sepa-bic' ] ) ) {
			            wc_add_notice( apply_filters( 'gm_sepa_direct_debit_bic_validation_error_notice', __( 'Please enter a valid BIC', 'woocommerce-german-market' ) ), 'error' );
			            $error_count ++;
		            }
	            }

            }

            if ( is_wc_endpoint_url( 'order-pay' ) ) {
	            if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) && isset( $_POST[ 'payment_method' ] ) && $_POST[ 'payment_method' ] == 'german_market_sepa_direct_debit' && empty( $_POST[ 'gm-sepa-direct-debit-checkbox' ] ) ) {
		            $notice_text = apply_filters( 'gm_sepa_direct_debit_checkbox_validation_notce', __( 'You have to agree to the sepa direct debit mandate.', 'woocommerce-german-market' ) );
		            wc_add_notice( $notice_text, 'error' );
		            $error_count ++;
	            }
            }

		}

		return $error_count;
	}

	/**
	* Validate Additional Fields if second checkout page is disabeld
	**/
	public function validate_fields() { 

		if ( get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'off' ) {
			$error_count = self::validate_required_fields( 0 );
		}

	}

	/**
	* Add checkbox
	*
	* @wp-hook woocommerce_de_review_order_after_submit called in inc/WGM_Sepa_Direct_debit
	* @param Integer $error_count
	* @return Integer
	**/
	public static function checkout_field_checkbox( $review_order ) {

		$rtn_before = apply_filters( 'gm_sepa_checkout_field_checkbox', false );
		if ( $rtn_before ) {
			return $review_order;
		}

		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings', array() );
		if ( isset( $sdd_settings[ 'checkbox_confirmation_text' ] ) && ! empty( $sdd_settings[ 'checkbox_confirmation_text' ] ) ) {
			$confirmation_text = $sdd_settings[ 'checkbox_confirmation_text' ];
		} else {
			$confirmation_text = __( 'I agree to the [link]sepa direct debit mandate[/link].', 'woocommerce-german-market' );
		}

		$confirmation_text = str_replace( 
			array( '[link]', '[/link]' ),
			array( '<a href="#" id="gm-sepa-mandate-preview" style="cursor: pointer;">', '</a>' ),
			$confirmation_text
		);

		$show_checkbox_style = '';
		$class = '';
		$confirm_order_page_enabled = get_option( 'woocommerce_de_secondcheckout', 'off' ) == 'on';

		if ( $confirm_order_page_enabled ) {

			$payment_method = WGM_Session::get( 'payment_method', 'first_checkout_post_array' );
			if ( $payment_method != 'german_market_sepa_direct_debit' ) {
				$show_checkbox_style = 'style="display:none;"';
			}

		} else {

			$show_checkbox_style = 'style="display:none;"';
			$class = ' gm-sepa-direct-debit-second-checkout-disabled';
		}

		// support for manual order confirmation
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$show_checkbox_style = 'style="display:none;"';
			$class = ' gm-sepa-direct-debit-order-pay';
		}

		$required 	= '&nbsp;<span class="required">*</span>';

		$checkout_validated = false;
		
		// Has checkout already been validated
		$post_data = array();			
		if ( isset( $_REQUEST[ 'post_data' ] ) ) {

			parse_str( $_REQUEST[ 'post_data' ], $post_data );

			if ( isset( $post_data[ '_wp_http_referer' ] ) ) {
				
				if ( str_replace( 'wc-ajax=update_order_review', '', $post_data[ '_wp_http_referer' ] ) != $post_data[ '_wp_http_referer' ] ) {
					$checkout_validated = true;
				}

			}

		}

		$p_class 	= WGM_Template::get_validation_p_class( 'gm-sepa-direct-debit-checkbox', $checkout_validated, $post_data );

		$review_order .= sprintf(
			'<p class="form-row german-market-checkbox-p ' . $class . ' ' . $p_class . '" ' . $show_checkbox_style . '>
				<label for="gm-sepa-direct-debit-checkbox" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="gm-sepa-direct-debit-checkbox" id="gm-sepa-direct-debit-checkbox" />
					<span class="gm-sepa-direct-debit-checkbox-text">%s</span>' . $required . '
				</label>
				
				<div id="gm-sepa-mandate-preview-text" style="display: none;"></div>
			</p>',
			$confirmation_text
		);
		
		return $review_order;
	}

	/**
	 * Validate checkbox
	 *
	 * @wp-hook gm_checkout_validation_fields called in inc/WGM_Sepa_Direct_debit
     *
	 * @access public
	 *
     * @param array $data
	 * @param int   $error_count
     *
	 * @return int
	 **/
	public static function checkout_field_checkbox_validation( $data, $error_count = 0 ) {

		$rtn_before = apply_filters( 'gm_sepa_checkout_field_checkbox', false );
		if ( $rtn_before ) {
			return $error_count;
		}

		if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) && isset( $_POST[ 'payment_method' ] ) && $_POST[ 'payment_method' ] == 'german_market_sepa_direct_debit' && empty( $_POST[ 'gm-sepa-direct-debit-checkbox' ] ) ) {
			$notice_text = apply_filters( 'gm_sepa_direct_debit_checkbox_validation_notce', __( 'You have to agree to the sepa direct debit mandate.', 'woocommerce-german-market' ) );
			wc_add_notice( $notice_text, 'error' );
			$error_count++;
		}

		return $error_count;
	}

	/**
	 * Validate checkbox
	 *
	 * @wp-hook gm_checkout_validation_fields called in inc/WGM_Sepa_Direct_debit
     *
	 * @access public
	 *
     * @param array $data
	 * @param int   $error_count
     *
	 * @return int
	 **/
	public static function checkout_field_checkbox_validation_second_checkout( $error_count = 0 ) {

		if ( WGM_Sepa_Direct_Debit::need_to_vaildate_sepa_checkbox() ) {

			$rtn_before = apply_filters( 'gm_sepa_checkout_field_checkbox', false );
			if ( $rtn_before ) {
				return $error_count;
			}

			if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) && isset( $_POST[ 'payment_method' ] ) && $_POST[ 'payment_method' ] == 'german_market_sepa_direct_debit' && empty( $_POST[ 'gm-sepa-direct-debit-checkbox' ] ) ) {
				$notice_text = apply_filters( 'gm_sepa_direct_debit_checkbox_validation_notce', __( 'You have to agree to the sepa direct debit mandate.', 'woocommerce-german-market' ) );
				wc_add_notice( $notice_text, 'error' );
				$error_count++;
			}
		}

		return $error_count;
	}

	/**
	* add bulk action download 
	*
	* @access public
	* @hook admin_footer
	* @return void
	*/
	public function bulk_admin_footer() {
		
		global $post_type;

		if ( 'shop_order' == $post_type ) {
			?>
			<script type="text/javascript">
			jQuery(function() {
				jQuery('<option>').val('gm_download_sepa_xml').text('<?php _e( 'Downloads SEPA XML', 'woocommerce-german-market' )?>').appendTo('select[name="action"]');
				jQuery('<option>').val('gm_download_sepa_xml').text('<?php _e( 'Downloads SEPA XML', 'woocommerce-german-market' )?>').appendTo('select[name="action2"]');
			});
			</script>
			<?php
		}
	}

	/**
	* Do bulk action sepa xml
	*
	* @access public
	* @hook load-edit.php
	* @return void
	*/
	public function bulk_action() {

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();

		// return if it's not the sepa xml action
		if ( $action != 'gm_download_sepa_xml' ) {
			return;
		}

		// return if no orders are checked
		if ( ! isset( $_REQUEST[ 'post' ] ) ) {
			return;
		}

		$post_ids = array_map( 'absint', (array) $_REQUEST[ 'post' ] );

		// return if no order is checked
		if ( empty( $post_ids ) ) {
			return;
		}

		$sepa_orders = array();

		foreach ( $post_ids as $post_id ) {

			$order = wc_get_order( $post_id );
			if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {
    			$sepa_orders[] = $order;
    		}

		}

		if ( empty( $sepa_orders ) ) {
			return;
		}

		$sdd_settings = get_option( 'woocommerce_german_market_sepa_direct_debit_settings' );

		if ( ( ! isset( $sdd_settings[ 'xml_due_date_option_days' ] ) ) || empty( $sdd_settings[ 'xml_due_date_option_days' ] ) ) {
			$sdd_settings[ 'xml_due_date_option_days' ] = 1;
		}

		$message_id = apply_filters( 'german_market_sepa_xml_message_id', $sdd_settings[ 'bic' ] . '00' . date( 'YmdHis', current_time( 'timestamp' ) ) );
		$payment_id = apply_filters( 'german_market_sepa_xml_payment_id', 'PAY-ID-' . date( 'YmdHis', current_time( 'timestamp' ) ) );
		
		$file   = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Sephpa' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .'autoloader.php';
        $file_2 = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'SepaUtilities' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SepaUtilities.php';

        require_once( $file );
        require_once( $file_2 );

		// Due Date
		$xml_due_date = new DateTime();
		$xml_due_date->setTime( 0, 0, 1 );

		if ( get_option( 'woocommerce_de_due_date', 'off' ) == 'on' ) {

			if ( $sdd_settings[ 'xml_due_date_option' ] == 'x_days' ) {
				
				$extra_number_of_days = $sdd_settings[ 'xml_due_date_option_days' ];
				$xml_due_date->add( new DateInterval( 'P' . $extra_number_of_days . 'D' ) );
			
			} else if ( $sdd_settings[ 'xml_due_date_option' ] == 'min' ) {

				$min_date = false;

				foreach ( $sepa_orders as $sepa_order ) {

					$order_due_date = $sepa_order->get_meta( '_wgm_due_date' );

					if ( empty( $order_due_date ) ) {
						continue;
					}

					if ( ! $min_date ) {
						$min_date = new DateTime( $order_due_date );
					} else {

						$order_due_date_date_time = new DateTime( $order_due_date );
						if ( $order_due_date_date_time < $min_date ) {
							$min_date = $order_due_date_date_time;
						}

					}

				}

				if ( $min_date ) {
					if ( $min_date > $xml_due_date ) {
						$xml_due_date = $min_date;
					}
				}
				
			} else if ( $sdd_settings[ 'xml_due_date_option' ] == 'max' ) {

				$max_date = false;

				foreach ( $sepa_orders as $sepa_order ) {

					$order_due_date = $sepa_order->get_meta( '_wgm_due_date' );

					if ( empty( $order_due_date ) ) {
						continue;
					}

					if ( ! $max_date ) {
						$max_date = new DateTime( $order_due_date );
					} else {

						$order_due_date_date_time = new DateTime( $order_due_date );
						if ( $order_due_date_date_time > $max_date ) {
							$max_date = $order_due_date_date_time;
						}

					}

				}

				if ( $max_date ) {
					$xml_due_date = $max_date;
				}

			}

		} else {

			$extra_number_of_days = $sdd_settings[ 'xml_due_date_option_days' ];
			$xml_due_date->add( new DateInterval( 'P' . $extra_number_of_days . 'D' ) );
		
		}

		$args = apply_filters( 'german_market_sepa_sephpa_direct_debit_args', array(
		    'pmtInfId'      => $payment_id,
		    'lclInstrm'     => SepaUtilities::LOCAL_INSTRUMENT_CORE_DIRECT_DEBIT,
		    'seqTp'         => SepaUtilities::SEQUENCE_TYPE_ONCE,
		    'cdtr'          => $sdd_settings[ 'creditor_account_holder' ],
		    'iban'          => preg_replace('/\s+/', '', $sdd_settings[ 'iban' ] ),
		    'bic'           => preg_replace('/\s+/', '', $sdd_settings[ 'bic' ] ),
		    'ci'            => preg_replace('/\s+/', '', $sdd_settings[ 'creditor_identifier' ] ),
			// optional
		    'reqdColltnDt'  => $xml_due_date->format( 'Y-m-d' )
		) );
		
		$error = false;

		$pains = array(
			'SEPA_PAIN_008_001_02' 				=> SepaUtilities::SEPA_PAIN_008_001_02,
    		'SEPA_PAIN_008_001_02_AUSTRIAN_003'	=> SepaUtilities::SEPA_PAIN_008_001_02_AUSTRIAN_003,
   			'SEPA_PAIN_008_002_02'				=> SepaUtilities::SEPA_PAIN_008_002_02,
    		'SEPA_PAIN_008_003_02' 				=> SepaUtilities::SEPA_PAIN_008_003_02,
		);

		$pain_format = isset( $pains[ $this->pain_format ] ) ? $pains[ $this->pain_format ] : SepaUtilities::SEPA_PAIN_008_001_02;

		try {
			$direct_debit = new SephpaDirectDebit( $sdd_settings[ 'creditor_account_holder' ], $message_id, $pain_format, $args, array(), apply_filters( 'german_market_sepa_check_and_sanitize', true ) );
			
		} catch ( Exception $e ) {
			$error = $e->getMessage() . ', Settings';
		}

		if ( $error ) {
			update_option( '_german_market_sepa_xml_error', $error );
			return;
		}
		
		$include_refunds = $sdd_settings[ 'xml_include_refunds' ];
		if ( empty( $include_refunds ) ) {
			$include_refunds = 'exclude';
		}

		$counter = 1;

		foreach ( $sepa_orders as $sepa_order ) {
			
			$order_sepa_fields = array();
			$sepa_fields = self::get_payment_fields();

    		foreach ( $sepa_fields as $key => $sepa_field ) {

    			if ( $this->encryption_possible_on() ) {
    				$value = $this->decrypt( $sepa_order->get_meta( '_german_market_sepa_' . esc_attr( $key ) ), $sepa_order );
    			} else {
    				$value = $sepa_order->get_meta( '_german_market_sepa_' . esc_attr( $key ) );
    			}

    			$order_sepa_fields[ $key ] = $value;

    		}

    		$order_sepa_fields[ 'mandate_id' ] = $sepa_order->get_meta( '_german_market_sepa_mandate_reference' );
    		if ( $this->encryption_possible_on() ) {
    			$order_sepa_fields[ 'mandate_id' ] = $this->decrypt( $order_sepa_fields[ 'mandate_id' ], $sepa_order );
    		}

    		try {
				$direct_debit->addPayment( array(
					// required information about the debtor
					    'pmtId'         => $payment_id . '-' . $counter,     // ID of the payment (EndToEndId)
					    'instdAmt'      => $include_refunds == 'exclude' ? $sepa_order->get_total() : ( $sepa_order->get_total() - $sepa_order->get_total_refunded() ), // amount
					    'mndtId'        => $order_sepa_fields[ 'mandate_id' ],            // Mandate ID
					    'dtOfSgntr'     => date_i18n( 'Y-m-d', strtotime( $sepa_order->get_date_created() ) ),            // Date of signature
					    'bic'           => $order_sepa_fields[ 'bic' ],           // BIC of the Debtor
					    'dbtr'          => $order_sepa_fields[ 'holder' ],        // (max 70 characters)
					    'iban'          => $order_sepa_fields[ 'iban' ],// IBAN of the Debtor
					// optional
					    'rmtInf'        => apply_filters( 'german_market_sepa_xml_remittance_information', sprintf( __( 'Order %s', 'woocommerce-german-market' ), $sepa_order->get_order_number() ), $sepa_order ), // unstructured information about the remittance (max 140 characters)
					    // only use this if 'amdmntInd' is 'true'. at least one must be used
					) );
			} catch ( Exception $e ) {
				$error = $e->getMessage() . 'order-id: '. $order->get_order_number();
			}

			if ( $error ) {
				update_option( '_german_market_sepa_xml_error', $error );
				return;
			}
		
			$counter++;

		}

		$filename = apply_filters( 'german_market_sepa_xml_filename', 'sepa-xml-export-' . date( 'Y-m-d-H-i' ) . '-' . $payment_id . '.xml' );

		try {

			foreach ( $sepa_orders as $sepa_order ) {
				$sepa_order->update_meta_data( '_german_market_sepa_has_xml_export', 'yes' );
				$sepa_order->save_meta_data();
			}

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' ); 
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$direct_debit->download();

			exit();

		} catch ( Exception $e ) {
			$error = $e->getMessage();
		}
		
		if ( $error ) {
			update_option( '_german_market_sepa_xml_error', $error );
		}	

	}

	/**
	* Show XML Download Errors
	*
	* @access public
	* @hook admin_notices
	* @return void
	*/
	public function admin_xml_error_notice() {

		$class = 'german-market-error-admin';

		$link = get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=german_market_sepa_direct_debit';

		$message = sprintf( __( 'An error occurred during SEPA XML Download.<br />Please, check whether you have entered and saved all the SEPA options about the creditor (Account Holder, IBAN, BIC, Creditor Identifier Number, Pain Format) here: <a href="%s">SEPA Settings</a>.<br />If you still get errors after this, please contact <a href="https://marketpress.de/hilfe/" target="_blank">MarketPress Support</a> and convey this error message: %s', 'woocommerce-german-market' ), $link, get_option( '_german_market_sepa_xml_error', 'No Message' ) );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 

		delete_option( '_german_market_sepa_xml_error' );

	}

	/**
	* adds a small download button to the admin page for orders
	*
	* @since 0.0.1
	* @access public
	* @static 
	* @hook woocommerce_admin_order_actions
	* @arguments $actions, $theOrder
	* @return $actions
	*/	
	public function admin_order_actions( $actions, $order ) {
		
		if ( $order->get_payment_method() == 'german_market_sepa_direct_debit' ) {
			
			$args = array(

				'action'				=> 'german_market_download_sepa_mandate',
				'order_id'				=> $order->get_id(),

	            'street'    			=> $order->get_billing_address_1(),
	            'city'      			=> $order->get_billing_city(),
	            'zip'       			=> $order->get_billing_postcode(),
	            'country'   			=> $order->get_billing_country(),
	            'holder'    			=> $order->get_meta( '_german_market_sepa_holder' ),
	            'iban'      			=> $order->get_meta( '_german_market_sepa_iban' ),
	            'bic'      				=> $order->get_meta( '_german_market_sepa_bic' ),
	            'mandate_id'			=> $order->get_meta( '_german_market_sepa_mandate_reference' ),
	            'date'					=> date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) ),

	            'email_admin_iban_mask' => $this->email_admin_mask,

	        );

	        if ( $this->encryption_possible_on() ) {
				$args[ 'holder' ] 		= $this->decrypt( $args[ 'holder' ], $order );
				$args[ 'iban' ] 		= $this->decrypt( $args[ 'iban' ], $order );
				$args[ 'bic' ] 			= $this->decrypt( $args[ 'bic' ], $order );
				$args[ 'mandate_id' ] 	= $this->decrypt( $args[ 'mandate_id' ], $order );
			}

			$args[ 'unmasked_iban' ] = $args[ 'iban' ];
			$args[ 'iban' ] = $this->mask_iban( $args[ 'iban' ] );

			$query = http_build_query( $args );

			$has_xml_export = $order->get_meta( '_german_market_sepa_has_xml_export' );
			$extra_action = $has_xml_export === 'yes' ? ' has_xml_export' : '';
			
			// sepa_mandate pdf button
			$create_pdf = array( 
				'url' 		=>	wp_nonce_url( admin_url( 'admin-ajax.php?' . $query ), 'german-market-sepa-mandate' ), 
				'name' 		=> __( 'Download Sepa Mandate', 'woocommerce-german-market' ),
				'action' 	=> 'sepa_mandate' . $extra_action,
			);

			$actions[ 'sepa_mandate' ] = $create_pdf;

		}
	
		return $actions;
	}

	/**
	* Build SEPA Data for REST API
	*
	* @since 3.6.2
	* @access public
	* @static 
	* @param WC_Order $order
	* @return Array
	*/	
	public function get_api_data( $order ) {

		$fields = $this->admin_billing_fields( array(), $order );
		$sepa = array();
    
		foreach ( $fields as $key => $field ) {

			if ( $key == 'german-market-sepa-iban' ) {
				if ( $this->rest_api_iban_masking == 'on' ) {
					$field[ 'value' ] = $this->mask_iban( $field[ 'value' ] );
				}
			}

			$sepa[ str_replace( array( 'german-market-sepa-', 'german-market-' ), '', $key ) ] = $field[ 'value' ];

		}

		return $sepa;

	} 

}
