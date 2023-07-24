<?php

class WP_WC_Running_Invoice_Number_Compatibilities_Plugin_B2B_Market {

	/**
	 * @var WGM_Due_date
	 * @since v3.2
	 */
	private static $instance = null;
	
	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Due_date
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WP_WC_Running_Invoice_Number_Compatibilities_Plugin_B2B_Market();	
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		// add description to option
		add_filter( 'wp_wc_running_invoice_number_placeholder_desc', 			array( $this, 'placeholder_desc' ) );
		add_filter( 'wp_wc_running_invoice_number_placeholder_invoice_pdf', 	array( $this, 'placeholder_desc_with_explanation' ) );
		add_filter( 'wp_wc_running_invoice_number_placeholder_refund_pdf', 		array( $this, 'placeholder_desc_with_explanation' ) );
		
		// replace placholder
		add_filter( 'wp_wc_invoice_number_before_construct_suffix', array( $this, 'replace_placeholder' ), 10, 2 );
		add_filter( 'wp_wc_invoice_number_before_construct_prefix', array( $this, 'replace_placeholder' ), 10, 2 );
		add_filter( 'wp_wc_invoice_pdf_replace_return', 			array( $this, 'replace_placeholder' ), 10, 2 );

		do_action( 'wp_wc_running_invoice_number_compatibilities_plugin_b2b_market_after_construct', $this );
	}

	/**
	* add placeholder {{b2b-customer-group}} to option description
	*
	* @wp-hook wp_wc_running_invoice_number_placeholder_desc
	* @param String $desc
	* @return String
	*/
	public function placeholder_desc( $desc ) {
		return $desc . ', <code>{{b2b-customer-group}}</code>';
	}

	/**
	* add placeholder {{b2b-customer-group}} to option description (with explanation)
	*
	* @wp-hook wp_wc_running_invoice_number_placeholder_invoice_pdf
	* @param String $desc
	* @return String
	*/
	public function placeholder_desc_with_explanation( $desc ) {
		return $desc . ', ' . __( 'B2B Market Customer Group:', 'woocommerce-german-market' ) . ' ' . '<code>{{b2b-customer-group}}</code>';
	}

	/**
	* Replace Placeholder
	*
	* @wp-hook wp_wc_invoice_number_before_construct_suffix
	* @wp-hook wp_wc_invoice_number_before_construct_prefix
	* @param String $suffix_or_prefix
	* @param WC_Order $order
	* @param WP_WC_Running_Invoice_Number_Functions $running_invoice_number_object
	* @return String
	*/
	public function replace_placeholder( $suffix_or_prefix, $order ) {

		if ( WGM_Helper::method_exists( $order, 'get_user_id' ) ) {
			$user_group = self::get_customer_group_by_user_id( $order->get_user_id() );
		} else {
			$user_group = 'B2B-Test';
		}
		
		return str_replace( '{{b2b-customer-group}}', $user_group, $suffix_or_prefix );
	}

	/**
	* get "B2B Market" group by $user_id
	* to do: replace against own B2B function if available
	*
	* @param Integer $user_id
	* @return String
	*/
	public function get_customer_group_by_user_id( $user_id ) {

		$user_group = '';
		
		if ( $user_id > 0 ) {

			// search in user roles.
			$user_data = get_userdata( $user_id );
			foreach ( $user_data->roles as $slug ) {
				$group = get_page_by_path( $slug, OBJECT, 'customer_groups' );
				if ( ! is_null( $group ) ) {
					$user_group = $group->post_title;
					break;
				}
			}
			
			if ( empty( $user_group ) ) {
				$user_group = apply_filters( 'wp_wc_invoice_number_b2b_market_guest_placeholder', __( 'Customer', 'woocommerce-german-market' ) );
			}
			
		} else {
			$user_group = apply_filters( 'wp_wc_invoice_number_b2b_market_guest_placeholder', __( 'Guest', 'woocommerce-german-market' ) );
		}

		return $user_group;
	}
 
}
