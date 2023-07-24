<?php

class WGM_Age_Rating {

	/**
	 * @var WGM_Age_Rating
	 * @since v3.7.2
	 */
	private static $instance = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Compatibilities
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Age_Rating();	
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

			// Add Product Panels
			add_action( 'woocommerce_product_data_tabs', 						array( $this, 'add_product_tab' ), 20 );
			add_action( 'woocommerce_product_data_panels', 						array( $this, 'add_product_write_panel' ) );

			// Fields for Variations
			add_action( 'woocommerce_product_after_variable_attributes', 		array( $this, 'variation_fields' ), 10, 3 );

			// Save Meta
			add_action( 'woocommerce_process_product_meta',						array( $this, 'save_meta' ), 10 );
			add_action( 'woocommerce_ajax_save_product_variations', 			array( $this, 'save_meta' ), 10, 2 );

			// Shpping Class Option
			add_action( 'admin_init',						 					array( $this, 'form_field_in_shipping_mehtods' ) );	
		
		} else {

			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_rates' ), 100 );
		}
		

		do_action( 'after_wgm_age_rating_construct', $this );

	}

	/**
	* Form Field (Checkbox) in shipping methods
	*
	* @since 	3.8.2
	* @wp-hook 	admin_init
	* @return 	void
	*/
	public function form_field_in_shipping_mehtods() {
		global $woocommerce;
		foreach ( $woocommerce->shipping->get_shipping_methods() as $method ) {
			add_filter( 'woocommerce_shipping_instance_form_fields_' . $method->id, array( $this, 'shipping_setting' ), 10,  1 );
		}
	}

	/**
	* Change available shipping classes in cart
	*
	* @wp-hook 	woocommerce_package_rates
	* @param 	Aray $rates
	* @return 	Array
	*/
	public function available_shipping_rates( $rates ) {

		if ( self::get_age_rating_of_cart_or_order() > 0 ) {
			
			// age rate order
			$age_rating_rates = array();

			foreach ( $rates as $rate ) {

				$rate_options = get_option( 'woocommerce_' . str_replace( ':', '_', $rate->get_id() ) . '_settings' );
				
				if ( isset( $rate_options[ 'age_rating' ] ) && $rate_options[ 'age_rating' ] == 'yes' ) {

					$age_rating_rates[ $rate->get_id() ] = $rate;
					
				}

			}

		} else {

			// no products with age rates
			foreach ( $rates as $rate ) {

				$rate_options = get_option( 'woocommerce_' . str_replace( ':', '_', $rate->get_id() ) . '_settings' );
				
				if ( isset( $rate_options[ 'age_rating' ] ) && $rate_options[ 'age_rating' ] == 'yes' ) {

					unset( $rates[ $rate->get_id() ] );
					
				}

			}
		}

		if ( ! empty( $age_rating_rates ) ) {
			$rates = $age_rating_rates;
		}

		return $rates;
	}

	/**
	* Add Setting to Shipping Classes
	*
	* @wp-hook 	woocommerce_shipping_instance_form_fields_{id}
	* @param 	Aray $settings
	* @return 	Array
	*/
	public function shipping_setting( $settings  ) {

		$settings[ 'age_rating' ] = array(

			'title'		=> __( 'Only use for Age Rating', 'woocommerce-german-market' ),
			'type'		=> 'checkbox',
			'default'	=> 'false',

		);
		
		return $settings;

	}

	/**
	* Add Product Data Tab
	*
	* @wp-hook 	woocommerce_product_data_tabs
	* @param 	Aray $tabs
	* @return 	Array
	*/
	public function add_product_tab( $tabs ) {

		$tabs[ 'german_market_age_rating' ] = array(
				'label'  => __( 'Age Rating', 'woocommerce-german-market' ),
				'target' => 'german_market_age_rating',
		);

		return $tabs;
	}

	/**
	* Render Product Data Tab
	*
	* @wp-hook 	woocommerce_product_data_panels
	* @return 	void
	*/
	public function add_product_write_panel() {

		$product = wc_get_product( get_the_ID() );
		?>
		<div id="german_market_age_rating" class="panel woocommerce_options_panel age-rating" style="display: block; ">

			<?php
				if ( $product->get_type() == 'variable' ) {
					?><p class="_age_rating_description"><?php
							echo __( 'The age rating can be set up in every variation of your variable product. Be default, in every variation the following settings are used until you choose "Special Variation Setting".', 'woocommerce-german-market' );
					?></p><?php
				}
			?>
			<p class="form-field _age_rating_field">
				<label for="_age_rating_age" style="width: 200px;"><?php echo __( 'Required age to buy this product', 'woocommerce-german-market' ); ?>:</label>
				<input type="number" min="0" name="_age_rating_age" id="_age_rating_age" value="<?php echo $product->get_meta( '_age_rating_age' ); ?>" style="width: 50px; margin-right: 5px;"/><?php echo __( 'Years', 'woocommerce-german-market' ); ?>
			</p>

		</div>
		<?php

	}

	public function variation_fields(  $loop, $variation_data, $variation ) { ?>
		
		<tr>
			<td colspan="2" >

				<div class="german-market-ppu-variation" style="border: 1px solid #eee; padding: 10px; box-sizing: border-box;">
					
					<b><?php echo __( 'Age Rating', 'woocommerce-german-market' ); ?>:</b>

					<?php
					$used_setting 	= get_post_meta( $variation->ID, '_v_used_setting_age_rating', TRUE );
					
					$special_is_set = intval( $used_setting ) == 1 ? 'selected="selected"' : '';
					$label_style 	= 'style="width: 30%; float: left;"';
					$input_style	= 'style="width: 50px; margin-right: 5px;"';
					$show_settings 	= $used_setting == 1 ? '' : 'style="display: none;"';
					?>
					
					<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc">
						<label <?php echo $label_style; ?> for="_v_used_setting_age_rating[<?php echo $loop; ?>]"><?php echo __( 'Used Setting', 'woocommerce-german-market' ); ?>:</label>

						<select name="_v_used_setting_age_rating[<?php echo $loop; ?>]" class="_v_used_setting_age_rating german-market-variation-input-not-translatable" data-loop="<?php echo $loop; ?>">
							<option value="-1"><?php echo __( 'Same as parent', 'woocommerce-german-market' ); ?></option>
							<option value="1" <?php echo $special_is_set; ?>><?php echo __( 'Following Special Variation Setting', 'woocommerce-german-market' ); ?></option>
						</select>

					</p>

					<div id="gm_age_rating_parent_special[<?php echo $loop;?>]" class="gm_age_rating_parent_special gm_age_rating_parent_special<?php echo $loop; ?>" <?php echo $show_settings; ?>>

						<p class="form-field _age_rating_field">
							<label <?php echo $label_style; ?> for="_variable_age_rating_age[<?php echo $loop; ?>]"><?php echo __( 'Required age to buy this product', 'woocommerce-german-market' ); ?>:</label>
							<input <?php echo $input_style; ?> type="number" min="0" name="_variable_age_rating_age[<?php echo $loop; ?>]" id="_variable_age_rating_age[<?php echo $loop; ?>]" value="<?php echo get_post_meta( $variation->ID, '_age_rating_age', TRUE ); ?>" class="german-market-variation-input-not-translatable"/><?php echo __( 'Years', 'woocommerce-german-market' ); ?>
						</p>

					</div>

				</div>

			</td>
		</tr>

		<?php
	}
	
	/**
	* Save Meta Data
	*
	* @wp-hook 	woocommerce_process_product_meta, woocommerce_ajax_save_product_variations
	* @param 	Integer $post_id
	* @param 	Post $post
	* @return 	void
	*/
	public function save_meta( $post_id, $post = NULL ) {

		if ( isset( $_POST[ '_age_rating_age' ] ) ) {
			update_post_meta( $post_id, '_age_rating_age', $_POST[ '_age_rating_age' ] );
		}

		if ( ! empty( $_POST[ 'variable_post_id' ] ) ) {
			$variation_ids = $_POST[ 'variable_post_id' ];
		} else {
			$variation_ids = array();
		}

		/**
		 * meta_key => fallback_value
		 */
		$meta_keys = array(

				'_v_used_setting_age_rating' 	=> '',
				'_variable_age_rating_age'		=> '',

		);

		foreach ( $variation_ids as $i => $post_id ) {

			foreach ( $meta_keys as $key => $fallback_value ) {
				
				if ( isset( $_POST[ $key ][ $i ] ) ) {
					$value = $_POST[ $key ][ $i ];
				} else{
					continue;
				}

				if ( $key == '_variable_age_rating_age' ) {
					$key = '_age_rating_age';
				}

				update_post_meta( $post_id, $key, $value );
				
			}

		}

	}

	/**
	* Returns the age rating of a product
	* not regarding the general age rating
	*
	* @since 3.10.5.0.1
	* @static
	* @param WC_Product $product
	* @return Integer
	*/
	public static function get_age_rating_or_product( $product, $use_intval = true ) {

		$product = apply_filters( 'gm_product_used_product_to_get_translatable_settings', $product );
		
		// for variations of variable products:
		// check if general setting is used or special setting
		if ( $product->get_type() == 'variation' ) {
			
			$used_age_rating = intval( $product->get_meta( '_v_used_setting_age_rating' ) );

			if ( intval( $used_age_rating  ) != 1 ) { 
				
				// get setting of parent product
				$parent_prodcut_product 	= wc_get_product( $product->get_parent_id() );

				$product_age_rating 		= $parent_prodcut_product->get_meta( '_age_rating_age' );
				
				if ( $use_intval ) {
					$product_age_rating = intval( $product_age_rating );
				}
			
			} else {
				
				// get special age
				$product_age_rating 		= $product->get_meta( '_age_rating_age' );

			}

		} else {

			$product_age_rating = $product->get_meta( '_age_rating_age' );

			if ( $use_intval ) {
				$product_age_rating = intval( $product_age_rating );
			}
		
		}

		return $product_age_rating;
	}
	
	/**
	* Returns the age rating of a cart or an order
	* or return 0 if there are no age rating products
	*
	* @static
	* @return 	Integer
	*/
	public static function get_age_rating_of_cart_or_order( $order = false ) {

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

		$default_age_rating 	= intval( get_option( 'german_market_age_rating_default_age_rating', '' ) );
		$cart_rating 			= 0;

		foreach ( $cart as $item ) {

			if ( empty( $item[ 'variation_id' ] ) ) {
				$product = wc_get_product( $item[ 'product_id' ] );
			} else {
				$product = wc_get_product( $item[ 'variation_id' ] );
			}

			if ( ! WGM_Helper::method_exists( $product, 'get_meta' ) ) {
				continue;
			}

			$product_age_rating = self::get_age_rating_or_product( $product );

			if ( empty( $product_age_rating ) ) {
				$product_age_rating = $default_age_rating;
			}

			if ( $product_age_rating > $cart_rating ) {
				$cart_rating = $product_age_rating;
			}	

		}

		return $cart_rating;

	}

}
