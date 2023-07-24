<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Cash on Delivery Gateway
 *
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class 		WC_Gateway_COD
 * @extends		WC_Payment_Gateway
 * @version		2.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Patrick Garman, Julian Jöris ( changes for WGM )
 */
class WGM_Gateway_Cash_on_delivery_v2 extends WC_Payment_Gateway {

    /**
     * Init Payment Gateway
     */
    function __construct() {
		$this->id          			= 'cash_on_delivery';
		$this->icon         		= apply_filters( 'woocommerce_cod_icon', '' );
		$this->method_title 		= __( 'Cash on Delivery', 'woocommerce-german-market' );
		$this->has_fields   		= false;
		$this->method_description 	= __( 'Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce-german-market' );

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		// Get settings
		$this->enabled 			  = $this->get_option( 'enabled', 'no' );
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
	    $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;
	    $this->fee_taxes 		  = $this->get_option( 'fee_tax', 'default' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_cash_on_delivery', array( $this, 'thankyou' ) );

	    // Customer Emails
	    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

	    // Disable Fee Tax
	    if ( 'no-tax' === $this->fee_taxes ) {
	    	add_filter( 'woocommerce_de_calculate_gateway_fees_tax', 	array( $this, 'disable_fee_tax' ), 10, 2 );
	    	add_filter( 'woocommerce_de_show_gateway_fees_tax', 		array( $this, 'disable_fee_tax' ), 10, 2 );
	    }

    }

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		?>
		<h3><?php _e( 'Cash on Delivery', 'woocommerce-german-market' ); ?></h3>
		<p><?php _e( 'Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce-german-market' ); ?></p>
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

		$shipping_methods = array();

		if ( is_admin() )

			foreach ( $woocommerce->shipping->get_shipping_methods() as $method ) {
				if ( WGM_Helper::method_exists( $method, 'get_method_title' ) ) {
					$shipping_methods[ $method->id ] = $method->get_method_title(); // since WoocCommerce v2.6
				} else {
					$shipping_methods[ $method->id ] = $method->get_title(); // for WooCommerce < v2.6
				}
			}

			$shipping_methods [ 'no_shipping_needed' ] = __( 'No shipping needed (for virtual orders)', 'woocommerce-german-market' );

		if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'off' ) {
			$fee_notice = sprintf( __( 'Collect an extra service fee for COD payments. Enter amount in %s excluding tax.', 'woocommerce-german-market' ), esc_attr( get_option( 'woocommerce_currency' ) ) );
		} else {
			$fee_notice = sprintf( __( 'Collect an extra service fee for COD payments. Enter amount in %s including tax.', 'woocommerce-german-market' ), esc_attr( get_option( 'woocommerce_currency' ) ) );
		}

		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable COD', 'woocommerce-german-market' ),
				'label' => __( 'Enable Cash on Delivery', 'woocommerce-german-market' ),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce-german-market' ),
				'type' => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-german-market' ),
				'default' => __( 'Cash on Delivery', 'woocommerce-german-market' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce-german-market' ),
				'type' => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-german-market' ),
				'default' => __( 'Pay with cash upon delivery.', 'woocommerce-german-market' ),
			),
			'instructions' => array(
				'title' => __( 'Instructions', 'woocommerce-german-market' ),
				'type' => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website and in the order emails.', 'woocommerce-german-market' ),
				'default' => __( 'Pay with cash upon delivery.', 'woocommerce-german-market' )
			),
			
			'woocommerce_cash_on_delivery_fee' => array(
				'title' 		=> __( 'Service Fee', 'woocommerce-german-market' ),
				'type' 			=> 'text',
				'css'  			=> 'width:50px;',
				/* translators: %s = default currency, e.g. EUR */
				'desc_tip' 		=> $fee_notice,
				'default' 		=> '',
				'description' 	=> __( '<span style="color: #f00;">Attention!</span> Please inform yourself about the legalities regarding the charging of fees for payments:<br><a href="https://www.it-recht-kanzlei.de/verbot-extra-kosten-kartenzahlungen.html" target="_blank">https://www.it-recht-kanzlei.de/verbot-extra-kosten-kartenzahlungen.html</a>,<br><a href="https://www.it-recht-kanzlei.de/surcharging-verbot-nachnahme-gesonderte-gebuehr.html
" target="_blank">https://www.it-recht-kanzlei.de/surcharging-verbot-nachnahme-gesonderte-gebuehr.html
</a>', 'woocommerce-german-market' ),
			),

			'fee_tax' => array(
				'title' 		=> __( 'Service Fee - Tax', 'woocommerce-german-market' ),
				'type' 			=> 'select',
				'desc_tip' 		=> __( 'By default, fees are subject to prorated tax calculation for fees & shipping cost. You can find out more in the menu "WooCommerce -> German Market -> General -> Global Options", where you can also set preferences in this regard. You can also set an exception here, so that no taxes are calculated for the set fee here.', 'woocommerce-german-market' ),
				'default' 		=> 'default',
				'options'		=> array(
					'default'	=> __( 'Calculate Taxes', 'woocommerce-german-market' ),
					'no-tax'	=> __( 'No Taxes', 'woocommerce-german-market' ),

				)
				
			),
			
			'enable_for_methods' => array(
				'title' 		=> __( 'Enable for shipping methods', 'woocommerce-german-market' ),
				'type' 			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default' 		=> '',
				'description' 	=> __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-german-market' ),
				'options'		=> $shipping_methods,
				'desc_tip'      => true,
			),
			'enable_for_virtual' => array(
				'title'             => __( 'Accept for virtual orders', 'woocommerce-german-market' ),
				'label'             => __( 'Accept COD if the order is virtual', 'woocommerce-german-market' ),
				'type'              => 'checkbox',
				'default'           => 'yes'
			)
	   );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @access public
	 * @return bool
	 */
	function is_available() {
		global $woocommerce;

		if ( ! $this->enable_for_virtual ) {
			if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
				return false;
			}

			if ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				$needs_shipping = false;

				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						
						if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
							$_product = $item->get_product();
						} else {
							$_product = $order_obj->get_product_from_item( $item );
						}

						if ( $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}

				$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

				if ( $needs_shipping ) {
					return false;
				}
			}

			if ( apply_filters( 'german_market_gateway_enable_for_virtuell_off_return_false', false ) ) {
				return false;
			}
		}

		if ( ( ! empty( $this->enable_for_methods ) || apply_filters( 'german_market_gateway_cash_on_delviery_enable_for_shipping_methods_enter_if', false ) ) ) {

			if ( is_wc_endpoint_url( get_option( 'woocommerce_checkout_pay_endpoint' ) ) ) {

                $order_id = absint( get_query_var( 'order-pay' ) );
				$order = new WC_Order( $order_id );

				if ( ! $order->get_shipping_method() )
					return $this->available_if_no_shipping_needed();

				$chosen_method = $order->get_shipping_method();

			} elseif ( empty( $woocommerce->session->chosen_shipping_methods ) ) {
				return $this->available_if_no_shipping_needed();
			} else {
				$chosen_method = $woocommerce->session->chosen_shipping_methods;
			}

			if ( empty( $this->enable_for_methods ) ) {
				$found = true;
			} else {
				$found = false;
			}
			
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

			$found = apply_filters( 'german_market_gateway_cash_on_delviery_enable_for_shipping_methods', $found, $chosen_method );
			
			if ( ! $found )
				return $this->available_if_no_shipping_needed();
		}

		return apply_filters( 'german_market_gateway_cash_on_delviery_is_available', parent::is_available() );
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

		if ( in_array( 'no_shipping_needed', $this->enable_for_methods ) && $this->enabled == 'yes' ) {
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
	function process_payment ($order_id) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Mark as processing or on-hold (payment won't be taken until delivery)
		$order->update_status( apply_filters( 'woocommerce_cod_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ), __( 'Payment to be made upon delivery.', 'woocommerce-german-market' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order->get_id() );

		// Remove cart
		$woocommerce->cart->empty_cart();

		if( isset( $_SESSION['_chosen_payment_method']) )
			unset( $_SESSION['_chosen_payment_method'] );
		if( isset( $_SESSION[ 'first_checkout_post_array' ][ 'payment_method' ] ) )
			unset( $_SESSION[ 'first_checkout_post_array' ][ 'payment_method' ] );

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_order_received_url()
		);
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
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'cash_on_delivery' === $order->get_payment_method() ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	/**
	 * Deactivate fee tax
	 * 
	 * @since 3.10.5.1
	 * @access public
	 * @wp-hook woocommerce_de_calculate_gateway_fees_tax
	 * @wp-hook woocommerce_de_show_gateway_fees_tax
	 * @param Boolean $boolean
	 * @param WC_Fee $fee
	 * @return Boolean
	 */
    public function disable_fee_tax( $boolean, $fee ) {

    	$id = null;

    	if ( isset( $fee->id ) ) {
			$id = $fee->id;
		} else if ( isset( $fee->object->id ) ) {
			$id = $fee->object->id;
		} else if ( WGM_Helper::method_exists( $fee, 'get_id' ) ) {
			$id = sanitize_title( $fee->get_id() );
		}

		if ( $id ) {
	    
			if ( $id == $this->id || $id == sanitize_title( $this->title ) ) {
				$boolean = false;
			}

		}
    	return $boolean;
    }

}
