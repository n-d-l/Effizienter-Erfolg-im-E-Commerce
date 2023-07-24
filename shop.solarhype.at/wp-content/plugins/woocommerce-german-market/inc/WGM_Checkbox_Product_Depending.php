<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Checkbox_Product_Depending
 *
 * @author MarketPress
 */
class WGM_Checkbox_Product_Depending {

	/**
	 * @var WGM_Checkbox_Product_Depending
	 */
	private static $instance = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Checkbox_Product_Depending
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Checkbox_Product_Depending();
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		if ( is_admin() ) {
			
			// add options in German Market backend
			add_filter( 'woocommerce_de_ui_options_checkout_checkboxes_after_custom', 	array( $this, 'ui_options' ), 10, 3 );

			// add product options
			add_action( 'woocommerce_product_options_general_product_data',  			array( $this, 'product_options_simple' ),  10 );
			add_action( 'woocommerce_product_after_variable_attributes', 				array( $this, 'product_options' ), 10, 3 );

			// save product options
			add_filter( 'german_market_add_process_product_meta_meta_keys', 			array( $this, 'save_product_options' ) );
		}

		// add checkboxes
		add_filter( 'woocommerce_de_review_order_after_submit', array( $this, 'review_order' ) );

		// mark required checkbox as invalid
		add_filter( 'german_market_checkout_checkbox_is_required', array( $this, 'checkbox_is_required' ), 10, 4 );

		// validate checkboxes without second checkout page
		add_filter( 'gm_checkout_validation_fields', array( $this, 'validation_without_second_checkout_page' ), 10, 3 );

		// validate checkboxes with second checkout page
		add_filter( 'gm_checkout_validation_fields_second_checkout', array( $this, 'validation_wit_second_checkout_page' ) );

		// validate checkboxes on pay for order page
		add_filter( 'gm_checkout_validation_fields_pay_for_order', array( $this, 'validation_wit_second_checkout_page' ) );

		// checkbox logging
		add_filter( 'german_market_checkbox_logging_checbkox_texts_array', array( $this, 'checkbox_logging' ), 10, 4 );

		// Repeat notice in emails
		add_action( 'woocommerce_email_order_meta',	array( $this, 'repeat_notice' ), 60, 3 );

		// Repeat notice in invoice pdfs
		add_action( 'wp_wc_invoice_pdf_start_template', array( $this, 'repeat_notice_invoice_pdf' ) );
		add_action( 'wp_wc_invoice_pdf_end_template', array( $this, 'repeat_notice_invoice_pdf_end' ) );
	}

	/**
	* Repeat the checkbox separate option for invoic pdfs
	* 
	* @access public
	* wp-hook wp_wc_invoice_pdf_start_template
	* @param Array $args
	* @return void
	*/
	public function repeat_notice_invoice_pdf( $args ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();
		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {
			if ( 'on' === get_option( 'gm_checkbox_product_depending_repetition_invoice_pdf_' . $i, 'off' ) ) {
				add_filter( 'gm_checkbox_product_depending_repetition_if_off_' . $i, '__return_false' );
			} else {
				add_filter( 'gm_checkbox_product_depending_repetition_if_off_' . $i, '__return_true' );
			}
		}

	}

	/**
	* Repeat the checkbox separate option for invoic pdfs
	* Remove all filters again
	* 
	* @access public
	* wp-hook wp_wc_invoice_pdf_end_template
	* @return void
	*/
	public function repeat_notice_invoice_pdf_end() {
		$nr_of_checkboxes = $this->get_nr_of_checkboxes();
		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {
			remove_all_filters( 'gm_checkbox_product_depending_repetition_if_off_' . $i );
		}
	}

	/**
	* Repeat the checkbox text after order table
	* 
	* @access public
	* wp-hook woocommerce_email_order_meta
	* @param WC_Order $order
	* @param Boolean $sent_to_admin
	* @param Boolean $plain_text
	* @return void
	*/
	public function repeat_notice( $order, $sent_to_admin, $plain_text = false ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();
		
		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {

			if ( ! ( ( 'on' === get_option( 'gm_checkbox_product_depending_activation_' . $i , 'off' ) ) && ( 'on' === get_option( 'gm_checkbox_product_depending_repetition_' . $i, 'off' ) ) ) ) {
           
	           if ( apply_filters( 'gm_checkbox_product_depending_repetition_if_off_' . $i, true ) ) {
	                continue;
	            }

	        }
	        
	        if ( apply_filters( 'gm_checkbox_product_depending_repetition_if_off_' . $i, false ) ) {
	            continue;
	        }

			if ( $this->cart_or_order_needs_checkbox( $i, $order ) ) {

	            $text = sprintf( __( 'You have ticked: "%s"', 'woocommerce-german-market' ), get_option( 'gm_checkbox_product_depending_text_' . $i, '' ) );
	                
	            $new_line = $plain_text ? "\n" : '';
	            $p_start  = $plain_text ? '' : '<p class="product-depending-checkbox-' . $i . '">';
	            $p_end    = $plain_text ? '' : '</p>';

	            echo apply_filters( 'gm_checkbox_product_depending_repition_text_' . $i, $p_start . $new_line . $text . $p_end, $text, 'after', $plain_text );
	        }
            
		}
	}

	/**
	* Checkbox Logging
	* 
	* @access public
	* wp-hook german_market_checkbox_logging_checbkox_texts_array
	* @param Array $checkboxes_texts
	* @param String $pre_symbol
	* @param Array $posted_data
	* @param WC_Order $order
	* @return Array
	*/
	public function checkbox_logging( $checkboxes_texts, $pre_symbol, $posted_data, $order ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();
		
		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {
			
			if ( isset( $_REQUEST[ 'german-market-product-depending-checkbox-' . $i ] ) ) {
				$custom_text = get_option( 'gm_checkbox_product_depending_text_' . $i, '' );
				$custom_text = WGM_Template::replace_placeholders_terms_privacy_revocation( $custom_text );
				$checkboxes_texts[ 'german-market-product-depending-checkbox-' . $i ] = $pre_symbol . strip_tags( $custom_text );
			}
		}

		return $checkboxes_texts;
	}

	/**
	* Validate Checkbox
	* Second Checkout Page is enabled
	* And Pay Order Page
	* 
	* @access public
	* 
	* wp-hook gm_checkout_validation_fields_second_checkout
	* wp-hook gm_checkout_validation_fields_pay_for_order
	* 
	* @param Integer $error_count
	* @return Integer
	*/
	public function validation_wit_second_checkout_page( $error_count ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();

		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {

			if ( ( get_option( 'gm_checkbox_product_depending_activation_' .$i , 'off' ) == 'on' ) && ( get_option( 'gm_checkbox_product_depending_opt_in_' . $i, 'on' ) == 'on' ) ) {

				if ( $this->cart_or_order_needs_checkbox( $i ) ) {
					if ( ! isset( $_POST[ 'german-market-product-depending-checkbox-' . $i ] ) ) {

						$notice_text = get_option( 'gm_checkbox_product_depending_error_text_' . $i, '' );
						wc_add_notice( $notice_text, 'error' );
						$error_count++;

					}

					$error_count = apply_filters( 'gm_product_depending_checkbox_after_validation_with_second_co_' . $i, $error_count );
				}

			}

		}

		return $error_count;
	}

	/**
	* Validate Checkbox
	* Second Checkout Page is disabled
	*
	* @access public
	* @param Integer $more_errors
	* @param Object $errors
	* @return Integer
	*/
	public function validation_without_second_checkout_page( $more_errors, $errors, $posted_data ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();

		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {

			if ( ( get_option( 'gm_checkbox_product_depending_activation_' .$i , 'off' ) == 'on' ) && ( get_option( 'gm_checkbox_product_depending_opt_in_' . $i, 'on' ) == 'on' ) ) {

				if ( $this->cart_or_order_needs_checkbox( $i ) ) {

					if ( isset( $posted_data[ 'german-market-product-depending-checkbox-' . $i ] ) ) {
						$data_product_depending_checkbox = $posted_data[ 'german-market-product-depending-checkbox-' . $i ];
					} else if ( isset( $_POST[ 'german-market-product-depending-checkbox-' . $i ] ) ) {
						$data_product_depending_checkbox = $_POST[ 'german-market-product-depending-checkbox-' . $i ];
					} else {
						$data_product_depending_checkbox = '';
					}

					if ( empty( $data_product_depending_checkbox ) ) {

						$notice_text = get_option( 'gm_checkbox_product_depending_error_text_' . $i, '' );
						$errors->add( 'gm_checkbox_product_depending_activation_' . $i, $notice_text );

					} else {

						$more_errors = apply_filters( 'gm_product_depending_checkbox_after_validation_success_without_second_co_' . $i, $more_errors, $errors, $posted_data );
					}

					$more_errors = apply_filters( 'gm_product_depending_checkbox_after_validation_without_second_co_' . $i, $more_errors, $errors, $posted_data );
				}

			}

		}

		return $more_errors;
	}

	/**
	* Add required class to invalid checkbox
	*
	* @access public
	* wp-hook german_market_checkout_checkbox_is_required
	* @param Boolean $required
	* @param String $field_name
	* @param Boolean | String $checkout_validated
	* @param Array $post_data
	* @return Boolean
	*/
	public function checkbox_is_required( $required, $field_name, $checkout_validated, $post_data ) {

		$check_field_name = str_replace( 'german-market-product-depending-checkbox-', '', $field_name );

		if ( $field_name != $check_field_name ) {
			$checkbox_nr = $check_field_name;
			$required = get_option( 'gm_checkbox_product_depending_activation_' . $check_field_name, 'on' ) == 'on';
		}

		return $required;
	}

	/**
	* Checks whether cart or order needs checkbox $i
	*
	* @access public
	* @param Integer $nr_of_checkbox
	* @param WC_Order $order
	* @return Boolean
	*/
	public function cart_or_order_needs_checkbox( $nr_of_checkbox = 1, $order = false ) {

		$needs_checkbox = false;

		// init cart or order
		if ( $order ) {
			$cart = $order->get_items();	
		
		} else {

			// Is that the order pay page?
			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				
				global $wp;
				$order_key  = $_GET['key'];
				$order_id   = absint( $wp->query_vars['order-pay'] );
				$order      = wc_get_order( $order_id );
				$cart 		= $order->get_items();	

			} else {
				$cart = WC()->cart->get_cart();
			}

		}

		// check all cart items
		foreach ( $cart as $item ) {

			$is_variation = false;

			if ( empty( $item[ 'variation_id' ] ) ) {
				$product = wc_get_product( $item[ 'product_id' ] );
			} else {
				$product = wc_get_product( $item[ 'variation_id' ] );
				$is_variation = true;
			}

			if ( ! WGM_Helper::method_exists( $product, 'get_meta' ) ) {
				continue;
			}

			$product = apply_filters( 'gm_product_used_product_to_get_translatable_settings', $product );

			$meta = intval( $product->get_meta( '_gm_product_depending_checkbox' ) );
			
			if ( $is_variation ) {
				if ( -1 === $meta ) {
					$parent_product = $product->get_parent_id();
					$meta = intval( get_post_meta( $parent_product, '_gm_product_depending_checkbox', true ) );
				}
			}

			$meta = apply_filters( 'gm_product_depending_checkbox_product_meta', $meta, $product );

			if ( intval( $nr_of_checkbox ) === $meta ) {
				$needs_checkbox = true;
				break;
			}

		}

		return apply_filters( 'gm_product_depending_checkbox_cart_or_order_needs_checkbox', $needs_checkbox, $nr_of_checkbox, $order );
	}

	/**
	* Add Checkboxes to German Market Checkboxes (review order)
	*
	* @access public
	* @wp-hook woocommerce_de_review_order_after_submit
	* @param String $review
	* @return review_order
	*/
	public function review_order( $review_order ) {

		$nr_of_checkboxes = $this->get_nr_of_checkboxes();

		for ( $i = 1; $i <= $nr_of_checkboxes; $i++ ) {

			if ( get_option( 'gm_checkbox_product_depending_activation_' .$i , 'off' ) == 'on' ) {

				if ( ! $this->cart_or_order_needs_checkbox( $i ) ) {
					continue;
				}

				$custom_text = get_option( 'gm_checkbox_product_depending_text_' . $i, '' );

				// If Opt-In is needed
				if ( get_option( 'gm_checkbox_product_depending_opt_in_' . $i, 'on' ) == 'on' || get_option( 'gm_checkbox_product_depending_opt_in_' . $i, 'on' ) == 'optional' ) {
					
					$checked = isset( $_POST[ 'german-market-product-depending-checkbox-' . $i ] ) ? checked( $_POST[ 'german-market-product-depending-checkbox-' . $i ], 'on', FALSE ) : '';
					if ( $checked == '' ) {
						if ( isset( $_POST[ 'post_data' ] ) ) {
							parse_str( $_REQUEST[ 'post_data' ], $post_data );
							$checked = isset( $post_data[ 'german-market-product-depending-checkbox-' . $i ] ) ? checked( $post_data[ 'german-market-product-depending-checkbox-' . $i ], 'on', FALSE ) : '';

						}
					}
					
					$p_class 	= '';
					$required 	= get_option( 'gm_checkbox_product_depending_opt_in_' . $i, 'on' ) == 'on' ? '&nbsp;<span class="required">*</span>' : '';

					if ( ! empty( $required ) ) {
						$p_class = WGM_Template::get_validation_p_class( 'german-market-product-depending-checkbox-' . $i, 'maybe' );
					}

					$review_order .= apply_filters( 'gm_product_depending_checkbox_review_order_' . $i, sprintf(
						'<p class="german-market-checkbox-p form-row ' . $p_class . '">
							<label for="german-market-product-depending-checkbox-' . $i . '" class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox ">
								<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox german-market-product-depending-checkbox" %s name="german-market-product-depending-checkbox-' . $i . '" id="german-market-product-depending-checkbox-' . $i . '" />
								<span class="german-market-product-depending-checkbox-">%s</span>' . $required .'
							</label>
					</p>',
						$checked,
						WGM_Template::replace_placeholders_terms_privacy_revocation( $custom_text )
					) );

				} else {

					$review_order .= sprintf(
						'<p class="german-market-checkbox-p form-rows">
						<label for="german-market-product-depending-checkbox-' . $i . '" class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox "><span class="german-market-custom-checkbox-text">%s</span></label>
					</p>',
						WGM_Template::replace_placeholders_terms_privacy_revocation( $custom_text )
					);

				}

			}

		}

		return $review_order;
	}

	/**
	* Add Product Options in Backend
	*
	* @access public
	* @wp-hook woocommerce_product_options_general_product_data
	* @return void
	*/
	public function product_options_simple() {
		$this->product_options( NULL, NULL, NULL );
	}

	/**
	* Save Meta Data in Product
	*
	* @access public
	* @wp-hook german_market_add_process_product_meta_meta_keys
	* @param Array $meta_keys
	* @return Array
	*/
	public function save_product_options( $meta_keys ) {
		$meta_keys[ '_gm_product_depending_checkbox' ] = 0;
		return $meta_keys;
	}

	/**
	* Add Product Options in Backend
	*
	* @access public
	* @wp-hook woocommerce_product_after_variable_attributes
	* @param $integer $loop
	* @param Array $variation_data
	* @param Array $variation
	* @return void
	*/
	public function product_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

		/**
		 * This method can be used for both regular products as well as variations.
		 * Within a variation, styling and markup is a little bit different, so in addition to changing the post ID to the variation,
		 * also add a bit of additional markup
		 */
		$is_variation = ( ! is_null( $variation ) );
		$name_suffix = '';

		// init options
		$get_nr_of_checkboxes = $this->get_nr_of_checkboxes();
		$options = array();

		if ( $is_variation ) {
			$options[ -1 ] = __( 'Same as parent', 'woocommerce-german-market' );
		}

		$options[ 0 ] = __( 'No Checkbox Required', 'woocommerce-german-market' );

		$did_sth = false;
		for ( $i = 1; $i <= $get_nr_of_checkboxes; $i++ ) {

			if ( 'on' === get_option( 'gm_checkbox_product_depending_activation_' . $i, 'off' ) ) {

				$checkbox_title = get_option( 'gm_checkbox_product_depending_text_' . $i );

				if ( ! empty( $checkbox_title ) ) {
					$options[ $i ] = $checkbox_title;
					$did_sth = true;
				}
			}

		}

		if ( ! $did_sth ) {
			return;
		}

		if ( $is_variation ) {

			$name_suffix = '_variable[' . $loop . ']';
			$id = $variation->ID;

		} else {
			?>
			<div class="options_group">
			<?php
			$id = get_the_ID();

		}
		
		$saved_meta = apply_filters( 'german_market_get_post_meta_value_translatable', get_post_meta( $id, '_gm_product_depending_checkbox', TRUE ), $id, '_gm_product_depending_checkbox' );

		if ( '' === $saved_meta ) {
			$saved_meta = $is_variation ? -1 : 0;
		}
		
		$label_style 		= $is_variation ? 'style="width: 30%; float: left;"' : '';
		$select_style		= $is_variation ? 'style="margin-left: 5px;"' : '';
		$p_class 			= $is_variation ? 'form-row form-row-full form-field' : 'form-field';
		$translation_class 	= $is_variation ? 'german-market-variation-input-not-translatable' : '';
		?>

		<p class="<?php echo $p_class; ?>">
			<label for="_gm_product_depending_checkbox<?php echo $name_suffix ?>" <?php echo $label_style; ?>><?php _e( 'Product Depending Checkbox:', 'woocommerce-german-market' ); ?></label>

			<?php echo wc_help_tip( __( 'If a checkbox is selected here, it will be displayed on the checkout page when the product is in the cart. The product-dependent checkbox can be configured at "WooCommerce -> German Market -> Checkout Checkboxes".', 'woocommerce-german-market' ) ); ?>
			
			<select name="_gm_product_depending_checkbox<?php echo $name_suffix ?>" id="_gm_product_depending_checkbox<?php echo $name_suffix ?>" class="select short <?php echo $translation_class; ?>" <?php echo $select_style; ?>>
				
				<?php
				foreach ( $options as $key => $value ) {
					$selected = $key == intval( $saved_meta ) ? 'selected="selected"' : '';
					echo '<option value="' . $key . '"' . $selected . '>';
					echo $value . '</option>';
				}
				?>
			</select>

			
		</p>

		<?php
		if ( ! $is_variation ) {
			?>
			</div>
			<?php
		}

	}

	/**
	* Get Number of Checkboxes
	*
	* @access public
	* @return Integer
	*/
	public function get_nr_of_checkboxes() {
		return intval( max( apply_filters( 'german_market_number_of_product_depending_checkboxes', 1 ), 1 ) );
	}

	/**
	* Options in GM Ui
	*
	* @access public
	* @wp-hook woocommerce_de_ui_options_checkout_checkboxes_after_custom
	* 
	* @param Array $options
	* @param String $description_opt_in
	* @param String $description_text_error
	* @return Array
	*/
	public function ui_options( $options, $description_opt_in, $description_text_error ) {

		$get_nr_of_checkboxes = $this->get_nr_of_checkboxes();

		for ( $i = 1; $i <= $get_nr_of_checkboxes; $i++ ) {

			$pre_title = ( $get_nr_of_checkboxes > 1 ) ? $i . '. ' : '';

			$options[] =	array(
					'name' => $pre_title . _x( 'Product Depending Checkbox', 'options panel heading', 'woocommerce-german-market' ),
					'type' => 'title',
					'id'   => 'gm_checkbox_product_depending_title_' . $i,
					'desc' => __( 'Here you can add an additional custom product depending checkbox. In the settings of a product you can specify whether the purchase of a product requires the approval of this checkbox on the checkout page.', 'woocommerce-german-market' ),
				);

			$options[] =	array(
					'name'		=> __( 'Activation', 'woocommerce-german-market' ),
					'id'		=> 'gm_checkbox_product_depending_activation_' . $i,
					'type'     	=> 'wgm_ui_checkbox',
					'default'	=> 'off'
				);

			$options[] =	array(
					'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
					'id'		=> 'gm_checkbox_product_depending_opt_in_' . $i,
					'type'     	=> 'select',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width: 350px;',
					'default'  	=> 'on',
					'desc_tip'	=> $description_opt_in,
					'options'	=> array(
							'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
							'optional'		=> __( 'Optional Checkbox', 'woocommerce-german-market' ),
							'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ), 
					),

				);

			$options[] =	array(
					'name'		=> __( 'Text', 'woocommerce-german-market' ),
					'id'		=> 'gm_checkbox_product_depending_text_' . $i,
					'type'     	=> 'textarea',
					'default'  	=> '',
					'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				);

			$options[] =	array(
					'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
					'id'		=> 'gm_checkbox_product_depending_error_text_' . $i,
					'type'     	=> 'textarea',
					'default'  	=> '',
					'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
					'desc_tip'	=> $description_text_error,
				);

			$options[] =	array(
				'name'     => __( 'Repetition in Emails', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Repeat the Text in Emails.', 'woocommerce-german-market' ),
				'id'       => 'gm_checkbox_product_depending_repetition_' . $i,
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off'
			);

			if ( class_exists( 'Woocommerce_Invoice_Pdf' ) ) {
				$options[] =	array(
					'name'     => __( 'Repetition in Invoice PDFs', 'woocommerce-german-market' ),
					'desc_tip' => __( 'Repeat the Text in Invoice PDFs.', 'woocommerce-german-market' ),
					'id'       => 'gm_checkbox_product_depending_repetition_invoice_pdf_' . $i,
					'type'     => 'wgm_ui_checkbox',
					'default'  => 'off'
				);
			}

			$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_product_depending_title_' . $i );

		}

		return $options;
	}

}
