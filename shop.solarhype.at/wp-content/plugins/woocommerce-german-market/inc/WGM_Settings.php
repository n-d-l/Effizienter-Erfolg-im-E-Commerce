<?php
/**
 * Backend Settings
 *
 * @author jj, ap
 */
Class WGM_Settings {


	/**
	 * Register taxonomies
	 *
	 * @access public
	 * @author dw
	 * @static
	 * @return void
	 * @hook woocommerce_register_taxonomy
	 *
	 */
	public static function register_taxonomies() {

		// Register delivery times
		register_taxonomy( 'product_delivery_times',
		                   array( 'product', 'product_variation' ),
		                   array(
				                   'hierarchical'          => TRUE,
				                   'update_count_callback' => '_update_post_term_count',
				                   'label'                 => __( 'Delivery Times', 'woocommerce-german-market' ),
				                   'labels'                => array(
						                   'name'              => __( 'Delivery Times', 'woocommerce-german-market' ),
						                   'singular_name'     => __( 'Delivery Time', 'woocommerce-german-market' ),
						                   'menu_name'         => _x( 'Delivery Times', 'Admin menu item',
						                                              'woocommerce-german-market' ),
						                   'search_items'      => __( 'Search Delivery Times',
						                                              'woocommerce-german-market' ),
						                   'all_items'         => __( 'All Delivery Times',
						                                              'woocommerce-german-market' ),
						                   'parent_item'       => __( 'Parent Delivery Time',
						                                              'woocommerce-german-market' ),
						                   'parent_item_colon' => __( 'Parent Delivery Time:',
						                                              'woocommerce-german-market' ),
						                   'edit_item'         => __( 'Edit Delivery Time',
						                                              'woocommerce-german-market' ),
						                   'update_item'       => __( 'Update Delivery Time',
						                                              'woocommerce-german-market' ),
						                   'add_new_item'      => __( 'Add New Delivery Time',
						                                              'woocommerce-german-market' ),
					                   /* translators: label for a new delivery time entry */
						                   'new_item_name'     => __( 'New Delivery Time Name',
						                                              'woocommerce-german-market' )
				                   ),
				                   'public'                => FALSE,
				                   'show_ui'               => TRUE,
				                   'show_in_nav_menus'     => FALSE,
				                   'show_in_quick_edit'    => FALSE,
				                   'meta_box_cb'           => FALSE,
				                   'query_var'             => is_admin(),
				                   'capabilities'          => array(
						                   'manage_terms' => 'manage_product_terms',
						                   'edit_terms'   => 'edit_product_terms',
						                   'delete_terms' => 'delete_product_terms',
						                   'assign_terms' => 'assign_product_terms',
				                   ),
				                   'rewrite'               => FALSE,
		                   )
		);

		// Register delivery times
		register_taxonomy( 'product_sale_labels',
		                   array( 'product', 'product_variation' ),
		                   array(
				                   'hierarchical'          => TRUE,
				                   'update_count_callback' => '_update_post_term_count',
				                   'label'                 => __( 'Sale Labels', 'woocommerce-german-market' ),
				                   'labels'                => array(
						                   'name'              => __( 'Sale Labels', 'woocommerce-german-market' ),
						                   'singular_name'     => __( 'Sale Label', 'woocommerce-german-market' ),
						                   'menu_name'         => _x( 'Sale Labels', 'Admin menu item',
						                                              'woocommerce-german-market' ),
						                   'search_items'      => __( 'Search Sale Labels',
						                                              'woocommerce-german-market' ),
						                   'all_items'         => __( 'All Sale Labels', 'woocommerce-german-market' ),
						                   'parent_item'       => __( 'Parent Sale Label',
						                                              'woocommerce-german-market' ),
						                   'parent_item_colon' => __( 'Parent Sale Label:',
						                                              'woocommerce-german-market' ),
						                   'edit_item'         => __( 'Edit Sale Label', 'woocommerce-german-market' ),
						                   'update_item'       => __( 'Update Sale Label',
						                                              'woocommerce-german-market' ),
						                   'add_new_item'      => __( 'Add New Sale Label',
						                                              'woocommerce-german-market' ),
					                   /* translators: label for a new Sale Label entry */
						                   'new_item_name'     => __( 'New Sale Label Name',
						                                              'woocommerce-german-market' )
				                   ),
				                   'public'                => FALSE,
				                   'show_ui'               => TRUE,
				                   'show_in_nav_menus'     => FALSE,
				                   'show_in_quick_edit'    => FALSE,
				                   'meta_box_cb'           => FALSE,
				                   'query_var'             => is_admin(),
				                   'capabilities'          => array(
						                   'manage_terms' => 'manage_product_terms',
						                   'edit_terms'   => 'edit_product_terms',
						                   'delete_terms' => 'delete_product_terms',
						                   'assign_terms' => 'assign_product_terms',
				                   ),
				                   'rewrite'               => FALSE,
		                   )
		);
	}

	/**
	 * let the user dertermine, if he wants to use the imprint from the
	 * page or use the custom text
	 *
	 * @access public
	 *
	 * @param array
	 */
	public static function imprint_email_settings( $settings_array ) {

		if ( get_option( 'wgm_email_footer_general', 'on' ) == 'off' ) {
			return $settings_array;
		}

		foreach ( $settings_array as $position => $item ) {
			if ( isset( $item[ 'id' ] ) && 'woocommerce_email_footer_text' === $item[ 'id' ] ) {
				$settings_array[ $position ][ 'desc' ] = $settings_array[ $position ][ 'desc' ] . '<br />' .
				                                         __( 'You should enter the content of your Legal Information page here if you choose to use this field.',
				                                             'woocommerce-german-market' );

				$imprint_checkbox = array(
						'name' => __( 'Use Email Footer Text', 'woocommerce-german-market' ),
						'desc' => __( 'Append the content of the text field below to email footers, rather than appending the content of your Legal Information page.',
						              'woocommerce-german-market' ),
						'id'   => WGM_Helper::get_wgm_option( 'woocommerce_de_use_backend_footer_text_for_imprint_enabled' ),
						'type' => 'checkbox'
				);
				array_splice( $settings_array, $position, 0, array( $imprint_checkbox ) );
				break;
			}
		}

		return $settings_array;
	}

	/**
	* Save custom product meta.
	 *
	 * Attempts to unify saving for simple products and variable products
	*
	* @access public
	* @author jj, ap
	* @uses update_post_meta
	* @param int $post_id
	* @param array $post
	* @return void
	*/
	public static function add_process_product_meta( $post_id, $post = NULL ) {

		$simple = ( current_action() !== 'woocommerce_ajax_save_product_variations' );

		if ( ! empty( $_POST[ 'variable_post_id' ] ) ) {
			$variation_ids = $_POST[ 'variable_post_id' ];
		} else {
			$variation_ids = array();
		}

		/**
		 * meta_key => fallback_value
		 */
		$meta_keys = apply_filters( 'german_market_add_process_product_meta_meta_keys', array(
				'_digital'											=> '',
				'_lieferzeit'               						=> 0,
				'_sale_label'               						=> 0,
				'_suppress_shipping_notice' 						=> '',
				'_alternative_shipping_information'					=> '',
				'_variable_used_setting_shipping_info'				=> '',

				'_unit_regular_price_per_unit'      				=> '',
				'_unit_regular_price_per_unit_mult' 				=> '',
				'_regular_price_per_unit'           				=> '',

				'_unit_sale_price_per_unit'      					=> '',
				'_unit_sale_price_per_unit_mult' 					=> '',
				'_sale_price_per_unit'           					=> '',

				'_auto_ppu_complete_product_quantity' 				=> '',
				'_price_per_unit_product_weights_completely_off' 	=> '',

				'product_function_desc_textarea'					=> '',
				'_variation_requirements'							=> '',

				'_gm_gtin'											=> '',
		) );
		
		if ( $simple ) {
			foreach ( $meta_keys as $key => $fallback_value ) {
				$value = self::get_post_value( $key, $fallback_value );
				
				if ( ! isset( $_POST[ $key ] ) ) {
					
					if ( $key == '_suppress_shipping_notice' ) {
						delete_post_meta( $post_id, '_suppress_shipping_notice' );
					}

					if ( $key == '_digital' ) {
						update_post_meta( $post_id, $key, 'no' );
					}

					continue;
				}

				if ( $key === '_digital' ) {
					$value = 'yes';
				}

				if ( ! is_array( $value ) ) {
					$value = stripslashes( $value );
				}

				update_post_meta( $post_id, $key, $value );
			}
		} 

		foreach ( $variation_ids as $i => $post_id ) {

			foreach ( $meta_keys as $key => $fallback_value ) {
				$value = self::get_post_value( $key, $fallback_value, $i );

				if ( $key == '_suppress_shipping_notice' ) {

					if ( ! isset( $_POST[ '_suppress_shipping_notice_variable' ][ $i ] ) ) {
						delete_post_meta( $post_id, $key );
						continue;
					}
				}

				if ( $key == '_digital' ) {

					if ( ! isset( $_POST[ 'variable_is_digital' ][ $i ] ) ) {
						update_post_meta( $post_id, $key, 'no' );
						continue;
					}
				}

				if ( $key === '_digital' ) {
					$value = 'yes';
				}

				if ( ! is_array( $value ) ) {
					$value = stripslashes( $value );
				}

				update_post_meta( $post_id, $key, $value );
			}

		}

	}

	/**
	 * Grab a specific element from the $_POST array by key
	 *
	 * If a variation index is passed, the key will be changed to match the variable attributes.
	 * Then, if an array is found under the new key, the element matching the variation index will be returned
	 *
	 * @param            $key
	 * @param bool|FALSE $fallback
	 * @param bool|FALSE $variation
	 *
	 * @return bool
	 */
	public static function get_post_value( $key, $fallback = FALSE, $variation = FALSE ) {

		if ( $variation !== FALSE ) {
			$key .= '_variable';
			if ( ! isset( $_POST[ $key ] ) ) {
				return $fallback;
			}

			if ( is_array( $_POST[ $key ] ) && isset( $_POST[ $key ][ $variation ] ) ) {
				return $_POST[ $key ][ $variation ];
			}
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			return $fallback;
		}

		return $_POST[ $key ];
	}

	/**
	* add delivery time link to products
	*
	* @access	public
	* @author	jj, ap
	* @uses		apply_filters
	* @static
	* @return	array
	*/
	public static function add_product_write_panel_tabs( $tabs ) {

		global $thepostid;

		$tabs[ 'prerequisites' ] = array(
				'label'  => __( 'Requirements', 'woocommerce-german-market' ),
				'target' => 'product_function_desc',
				'class'  => apply_filters( 'german_market_requirements_panel_classes', array( 'show_if_digital', 'show_if_variation_is_downloadable', 'show_if_downloadable' ) ),
		);

		$tabs[ 'price_per_unit_options' ] = array(
				'label'  => __( 'Price per Unit', 'woocommerce-german-market' ),
				'target' => 'price_per_unit_options',
				'class'  => array( 'hide_if_virtual' ),
		);

		return $tabs;

	}

	/**
	* add delivery time control and shipping control to products
	*
	* @access public
	* @author jj, ap
	* @uses maybe_unserialize, get_the_ID, get_post_meta, selected, woocommerce_wp_text_input, get_woocommerce_currency_symbol
	* @static
	* @return void
	*/
	public static function add_product_write_panels() {

		global $thepostid;

		?>
		<div id="product_function_desc" class="panel woocommerce_options_panel" style="display: block; ">
			<?php
			
			if ( metadata_exists( 'post', $thepostid, 'product_function_desc_textarea' ) ) {
				$value = get_post_meta( $thepostid, 'product_function_desc_textarea', true );
			} else {
				$value = get_option( 'gm_default_template_requirements_digital' );
			}

			$field = array(
					'label' => __( 'Requirements (digital)', 'woocommerce-german-market' ),
					'id'    => 'product_function_desc_textarea',
					'value' => $value,
			);

			woocommerce_wp_textarea_input( $field );

			?>
		</div>
		<?php
	}

	public static function add_deliverytime_options_simple() {
		self::add_deliverytime_options( NULL, NULL, NULL );
	}

	public static function add_deliverytime_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

		/**
		 * This method can be used for both regular products as well as variations.
		 * Within a variation, styling and markup is a little bit different, so in addition to changing the post ID to the variation,
		 * also add a bit of additional markup
		 */
		$is_variation = ( ! is_null( $variation ) );
		$name_suffix  = '';

		if ( $is_variation ) {
			$name_suffix = '_variable[' . $loop . ']';
			$id          = $variation->ID;

		} else {
			?>
			<div class="options_group">
			<?php
			$id = get_the_ID();

		}
		$data          = maybe_unserialize( get_post_meta( $id, '_lieferzeit', TRUE ) );
		$data_shipping = apply_filters( 'german_market_get_post_meta_value_translatable', maybe_unserialize( get_post_meta( $id, '_suppress_shipping_notice', TRUE ) ), $id, '_suppress_shipping_notice' );
		$data_alt_notice = get_post_meta( $id, '_alternative_shipping_information', TRUE );

		$terms = get_terms( 'product_delivery_times', array( 'orderby' => 'id', 'hide_empty' => 0, 'suppress_filter' => true ) );

		if ( is_numeric( $data ) ) {
			$lieferzeit = (int) $data;
		} else {
			
			$lieferzeit = get_option( WGM_Helper::get_wgm_option( 'global_lieferzeit' ) );
			
			// Af variation should have "Same as parent" as default
			if ( $is_variation ) {
				$lieferzeit = -1;
			}
		
		}

		$lieferzeit = apply_filters( 'german_market_term_value_in_backend', $lieferzeit, $id, '_lieferzeit', 'product_delivery_times' );
		$terms = apply_filters( 'german_market_terms_in_backend', $terms, $id, $lieferzeit );

		$parent_option_label = ($is_variation)?__( 'Same as parent', 'woocommerce-german-market' ):__( 'Select', 'woocommerce-german-market' );

		$label_style 		= $is_variation ? 'style="width: 30%; float: left;"' : '';
		$input_style 		= $is_variation ? 'style="width: 50%; float: left;"' : '';
		$select_style		= $is_variation ? 'style="margin-left: 5px;"' : '';
		$span_style			= $is_variation ? 'style="float: left; width: 65%; margin-left: 30%; margin-bottom: 12px;"' : 'style="float: left;"';
		$p_class 			= $is_variation ? 'form-row form-row-full form-field' : 'form-field';
		$translation_class 	= $is_variation ? 'german-market-variation-input-not-translatable' : '';
		?>

		<p class="<?php echo $p_class; ?>">
			<label for="_lieferzeit<?php echo $name_suffix; ?>" <?php echo $label_style; ?>><?php _e( 'Delivery Time:', 'woocommerce-german-market' ); ?></label>
			<select name="_lieferzeit<?php echo $name_suffix ?>" id="_lieferzeit<?php echo $name_suffix ?>" <?php echo $select_style; ?> class="select short <?php echo $translation_class; ?>">
				<option value="-1"><?php echo $parent_option_label ?></option>
				<?php
				foreach ( $terms as $i ) {
					echo '<option value="' . $i->term_id . '"' . selected( $i->term_id, $lieferzeit, FALSE ) . '>';
					echo $i->name . '</option>';
				}
				?>
			</select>
		</p>
		
		<?php $show_settings = 'style="display: none;"';?>

		<?php if ( $is_variation ) { ?>

			<div class="german-market-shipping-information-variation" style="border: 1px solid #eee; padding: 10px; box-sizing: border-box;">

			<b><?php echo __( 'Shipping Information', 'woocommerce-german-market' ); ?>:</b>

			<p class="form-field _regular_price_per_unit_field ppu_variable ppu_auto_calc">
				<label <?php echo $label_style; ?> for="_variable_used_setting_shipping_info<?php echo $name_suffix; ?>"><?php echo __( 'Used Setting', 'woocommerce-german-market' ); ?>:</label>

				<?php 

					$used_setting 	= get_post_meta( $variation->ID, '_variable_used_setting_shipping_info', TRUE );
					$special_is_set = intval( $used_setting ) == 1 ? 'selected="selected"' : '';
					$show_settings 	= $used_setting == 1 ? '' : 'style="display: none;"';

				?>
				<select name="_variable_used_setting_shipping_info<?php echo $name_suffix; ?>" class="_variable_used_setting_shipping_info <?php echo $translation_class; ?>" id="_variable_used_setting_shipping_info<?php echo $name_suffix; ?>" data-loop="<?php echo $loop; ?>">
					<option value="-1"><?php echo __( 'Same as parent', 'woocommerce-german-market' ); ?></option>
					<option value="1" <?php echo $special_is_set; ?>><?php echo __( 'Following Special Variation Setting', 'woocommerce-german-market' ); ?></option>
				</select>

			</p>

			<div id="gm_shipping_info_special_[<?php echo $loop;?>]" class="gm_shipping_info_specia gm_shipping_info_special_<?php echo $loop; ?>" <?php echo $show_settings; ?>>

		<?php } ?>


			<?php $class = apply_filters( 'german_market_wgm_product_shipping_information_admin_p_class', 'form-field show_if_simple show_if_variable show_if_external' ); ?>
			<p class="<?php echo $class; ?>" style="display:block">

				<label for="_alternative_shipping_information<?php echo $name_suffix; ?>" <?php echo $label_style; ?>>
					<?php _e( 'Alternative Shipping Information', 'woocommerce-german-market' ); ?>:
				</label>
				
				<input type="text" name="_alternative_shipping_information<?php echo $name_suffix; ?>" id="_alternative_shipping_information<?php echo $name_suffix; ?>" value="<?php echo esc_attr( $data_alt_notice ); ?>" <?php echo $input_style; ?>/>
				
				<?php echo wc_help_tip( esc_attr( __( 'Instead of the general shipping information you can enter a special information just for this product.', 'woocommerce-german-market' ) ) ); ?>
				
				<br />

				<span <?php echo $span_style; ?>>
					<em>
						<?php echo __( 'You can use the following placeholder', 'woocommerce-german-market' ); ?>: <code>[link-shipping]</code><?php echo __( 'Shipping', 'woocommerce-german-market' ); ?><code>[/link-shipping]</code>. 
					</em>
				</span>

			</p>

			<?php 
				$extra_style = '';

				if ( $is_variation ) { 
					$extra_style = 'style ="float: left; width: 30%;"';
					 ?><br /><?php 
				} 
			?>

			<p class="<?php echo $class; ?>" style="display:block; clear: both;">

				<label <?php echo $extra_style; ?> for="_suppress_shipping_notice<?php echo $name_suffix; ?>">
					<?php _e( 'Disable Shipping Information', 'woocommerce-german-market' ); ?>:
				</label>
				
				<input type="checkbox" class="checkbox <?php echo $translation_class; ?>" name="_suppress_shipping_notice<?php echo $name_suffix; ?>" id="_suppress_shipping_notice<?php echo $name_suffix; ?>" value="on" <?php checked( $data_shipping, 'on' ); ?>/>
				
				<?php echo wc_help_tip( esc_attr( __( 'Don’t display shipping information for this product (e.g. if it is virtual/digital).', 'woocommerce-german-market' ) ) ); ?>
			
			</p>

		<?php if ( $is_variation ) {
			?></div></div><?php
		}
		?>

		<?php
		if ( ! $is_variation ) {
			?>
			</div>
			<?php
		}
	}

	/**
	 * Textarea for requirements of variations
	 *
	 * @param   int $loop
	 * @param 	array $variation_data
	 * @param 	WP_Post $variation
	 * @return  void
	 */
	public static function add_requirements_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

		$is_variable_downloadable = get_post_meta( $variation->ID, '_downloadable', true );
		$is_variable_digital = get_post_meta( $variation->ID, '_digital', true );
		$show = $is_variable_digital == 'yes' || $is_variable_downloadable == 'yes';
		
		if ( metadata_exists( 'post', $variation->ID, '_variation_requirements' ) ) {
			$value = get_post_meta( $variation->ID, '_variation_requirements', true );
		} else {
			$value = get_option( 'gm_default_template_requirements_digital', '' );
		}

		if ( ! is_string( $value ) ) {
			$value = '';
		}

		?><div class="show_if_variation_downloadable_or_digital" <?php echo ( ! $show ) ? 'style="display:none;' : ''; ?>><?php

			woocommerce_wp_textarea_input( 
					array( 
						'id'	=> '_variation_requirements_variable[' . $loop . ']', 
						'label'	=> __( 'Requirements (digital)', 'woocommerce-german-market' ),
						'value'	=> $value,
						'style'	=> 'width: 100%'
					)
				);

		?></div><?php

	}

	public static function add_sale_label_options_simple() {
		self::add_sale_label_options( NULL, NULL, NULL );
	}

	public static function add_sale_label_options( $loop = NULL, $variation_data = NULL, $variation = NULL ) {

		/**
		 * This method can be used for both regular products as well as variations.
		 * Within a variation, styling and markup is a little bit different, so in addition to changing the post ID to the variation,
		 * also add a bit of additional markup
		 */
		$is_variation = ( ! is_null( $variation ) );
		$name_suffix = '';

		if ( $is_variation ) {

			$name_suffix = '_variable[' . $loop . ']';
			$id = $variation->ID;

		} else {
			?>
			<div class="options_group">
			<?php
			$id = get_the_ID();

		}

		$data 		= maybe_unserialize( get_post_meta( $id, '_sale_label', TRUE ) );
		$terms 		= get_terms( 'product_sale_labels', array( 'orderby' => 'id', 'hide_empty' => 0 ) );

		$data = apply_filters( 'german_market_term_value_in_backend', $data, $id, '_sale_label', 'product_sale_labels' );
		$terms = apply_filters( 'german_market_terms_in_backend', $terms, $id, $data );

		if ( is_numeric( $data ) ) {
			$sale_label_value = (int) $data;
		} else {
			
			$sale_label_value = get_option( WGM_Helper::get_wgm_option( 'global_sale_label' ) );

			// Af variation should have "Same as parent" as default
			if ( $is_variation ) {
				$sale_label_value = -1;
			}

		}
		
		$parent_option_label 	= ($is_variation)?__( 'Same as parent', 'woocommerce-german-market' ):__( 'Use the default', 'woocommerce-german-market' );
		$label_style 			= $is_variation ? 'style="width: 30%; float: left;"' : '';
		$select_style			= $is_variation ? 'style="margin-left: 5px;"' : '';
		$p_class 				= $is_variation ? 'form-row form-row-full form-field' : 'form-field';
		$translation_class 		= $is_variation ? 'german-market-variation-input-not-translatable' : '';
		?>

		<p class="<?php echo $p_class; ?>">
			<label for="_sale_label<?php echo $name_suffix ?>" <?php echo $label_style; ?>><?php _e( 'Sale Label:', 'woocommerce-german-market' ); ?></label>
			<select name="_sale_label<?php echo $name_suffix ?>" id="_sale_label<?php echo $name_suffix ?>" <?php echo $select_style; ?> class="select short <?php echo $translation_class; ?>">
				<option value="-2"><?php _e( 'Select', 'woocommerce-german-market' ); ?></option>
				<option value="-1" <?php echo $sale_label_value == '-1' ? 'selected="selected"' : '';?>><?php echo $parent_option_label ?></option>
				<?php
				foreach ( $terms as $i ) {
					$selected = $i->term_id == intval( $sale_label_value ) ? 'selected="selected"' : '';
					echo '<option value="' . $i->term_id . '"' . $selected . '>';
					echo $i->name . '</option>';
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
	 * Prints a woocommerce settigs html text field.
	 * Copied from woocommerce core, extended to field after it (select box for scale units)
	 *
	 * @since	1.1.5beta
	 * @static
	 * @global	$thepostid, $post, $woocommerce
	 * @access	public
	 * @param 	array $field
	 * @return	void
	 */
	public static function extended_woocommerce_text_input( $field ) {

		global $thepostid, $post, $woocommerce;

		$thepostid 					= empty( $thepostid ) ? $post->ID : $thepostid;
		$field[ 'placeholder' ] 	= isset( $field[ 'placeholder' ] ) ? $field[ 'placeholder' ] : '';
		$field[ 'class' ]			= isset( $field[ 'class' ] ) ? $field[ 'class' ] : 'short';
		$field[ 'wrapper_class' ]	= isset( $field[ 'wrapper_class' ] ) ? $field[ 'wrapper_class' ] : '';
		$field[ 'value' ]			= isset( $field[ 'value' ] ) ? $field[ 'value' ] : get_post_meta( $thepostid, $field[ 'id' ], true );
		$field[ 'name' ]			= isset( $field[ 'name' ] ) ? $field[ 'name' ] : $field[ 'id' ];
		$field[ 'type' ]			= isset( $field[ 'type' ] ) ? $field[ 'type' ] : 'text';

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $field[ 'custom_attributes' ] ) && is_array( $field[ 'custom_attributes' ] ) )
			foreach ( $field[ 'custom_attributes' ] as $attribute => $value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field[ 'wrapper_class' ] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

		if ( ! empty( $field[ 'between_input_and_desscription' ] ) ) {
			echo '<span>' . $field[ 'between_input_and_desscription' ] . '</span>';
		}

		if ( ! empty( $field['description'] ) ) {
			if ( isset( $field['desc_tip'] ) ) {
				echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
			} else {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
			}

		}
		echo '</p>';
	}

	/**
	* If desired, force SSL for own checkout sites too
	*
	* @access	public
	* @global	$post
	* @static
	* @return	bool
	*/
	public static function unforce_ssl_checkout() {
		global $post;

		return ! has_shortcode( $post->post_content, 'woocommerce_de_check' );
	}

	/**
	* Description for flat rate shipping costs in backend when gross prcies are activated
	*
	* @access	public
	* @since 	3.5
	* @wp-hook  woocommerce_shipping_instance_form_fields_flat_rate
	* @static
	* @param 	Array $form_fields
	* @return	Array
	*/
	public static function change_flat_rate_cost_description( $form_fields ) {


		if ( get_option( 'gm_gross_shipping_costs_and_fees', 'off' ) == 'on' ) {
			
			foreach ( $form_fields as $key => $form_field ) {

				if ( $key == 'cost' ) {
					$form_fields[ $key ][ 'description' ] = __( 'Enter a cost including tax.', 'woocommerce-german-market' );
				}
				
			}

		}
		
		return $form_fields;
	}

}
