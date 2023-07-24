<?php

class BM_Options {

	/**
	 * @var BM_Options
	 */
	private static $instance = null;

	/**
	 * @var String
	 */
	private $current_screen_id = null;

	/**
	 * Singletone get_instance
	 *
	 * @static
	 * @return BM_Options
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new BM_Options();
		}

		return self::$instance;
	}

	/**
	 * Singletone constructor
	 *
	 * @access private
	 */
	private function __construct() {

		// add submenu
		add_action( 'admin_menu', array( $this, 'add_bm_submenu' ), 51 );

		// our checkbox
		add_action( 'woocommerce_admin_field_bm_ui_checkbox', array( $this, 'bm_ui_checkbox' ) );

		// code textarea
		add_action( 'woocommerce_admin_field_bm_ui_code', array( $this, 'output_code' ) );

		// our repeatables
		add_action( 'woocommerce_admin_field_bm_repeatable', array( $this, 'bm_repeatable' ) );

		// our repeatables
		add_action( 'woocommerce_admin_field_bm_group_repeatable', array( $this, 'bm_group_repeatable' ) );

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'woocommerce_admin_settings_sanitize_option' ), 10, 3 );

		// let other add actions or remove our actions
		do_action( 'bm_ui_after_actions', $this );

		// Code mirror for code fields
		add_action( 'admin_enqueue_scripts', array( $this, 'codemirror_enqueue_scripts' ) );
	}

	/**
	 * Add submenu
	 *
	 * @wp-hook admin_menu
	 * @access public
	 */
	public function add_bm_submenu() {

		$submenu_page = add_submenu_page(
			'woocommerce',
			__( 'B2B Market', 'b2b-market' ),
			__( 'B2B Market', 'b2b-market' ),
			apply_filters( 'b2b_ui_capability', 'manage_woocommerce' ),
			'b2b-market',
			array( $this, 'render_bm_menu' )
		);

		$this->current_screen_id = $submenu_page;
		add_action( 'load-woocommerce_page_b2b-market', array( $this, 'save_bm_options' ) );
	}

	/**
	 * Force regenerating price hashes when 'all customers' group saved.
	 *
	 * @acces public
	 * @static
	 *
	 * @return void
	 */
	public static function save_bm_options() {
		if ( isset( $_POST[ 'submit_save_bm_options' ] ) && isset( $_POST[ 'group_price' ] ) ) {
			// Refresh cached product prices transient.
			BM_Helper::force_regenerate_woocommerce_price_hashes();
		}
	}

	/**
	 * Output type bm_repeater_fields
	 *
	 * @access public
	 * @return void
	 */
	public function bm_repeatable( $value ) {

		$bulk_prices = get_option( 'bm_global_bulk_prices' );

		$options = array(
			__( 'Fix Price', 'b2b-market' )              => 'fix',
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		?>
		<table class="form-table">
			<div class="bm-bulkprices-inner">
			<label class="titledesc"><?php _e( 'Bulk Prices', 'b2b-market' ); ?></label>
				<p><?php _e( 'Bulk prices are applied if the current quantity fits in the configured quantity range ', 'b2b-market' ); ?></p>
			<?php

			/* filled with existing data */
			$counter = 0;
			if ( isset( $bulk_prices ) && ! empty( $bulk_prices ) ) {
				if ( count( $bulk_prices ) > 0 ) {
					foreach ( $bulk_prices as $price ) {

						if ( isset( $price['bulk_price_type'] ) ) {
							$selected = $price['bulk_price_type'];
						} else {
							$selected = 'fix';
						}

						if ( isset( $price['bulk_price'] ) || isset( $price['bulk_price_from'] ) || isset( $price['bulk_price_to'] ) ) {
							?>
							<p>
								<label for="bulk_price[<?php echo $counter; ?>][bulk_price]"><?php _e( 'Bulk Price', 'b2b-market' ); ?></label>
								<input type="number" step="0.0001" min="0" name="bulk_price[<?php echo $counter; ?>][bulk_price]" value="<?php echo $price['bulk_price']; ?>" />

								<label for="bulk_price[<?php echo $counter; ?>][bulk_price_from]"><?php _e( 'Amount (from)', 'b2b-market' ); ?></label>
								<input type="number" step="1" min="0" name="bulk_price[<?php echo $counter; ?>][bulk_price_from]" value="<?php echo $price['bulk_price_from']; ?>" />

								<label for="bulk_price[<?php echo $counter; ?>][bulk_price_to]"><?php _e( 'Amount (to)', 'b2b-market' ); ?></label>
								<input type="number" step="1" min="0" name="bulk_price[<?php echo $counter; ?>][bulk_price_to]" value="<?php if ( esc_attr( $price['bulk_price_to'] ) != 0 ) { echo $price['bulk_price_to']; } ?>" <?php if ( isset( $price['bulk_price_to'] ) && esc_attr( $price['bulk_price_to'] ) == 0) { echo 'placeholder="∞"'; } ?> />

								<label for="bulk_price[<?php echo $counter; ?>][bulk_price_type]"><?php _e( 'Price-Type', 'b2b-market' ); ?></label>
								<select id="bulk_price_type" name="bulk_price[<?php echo $counter; ?>][bulk_price_type]" class="bulk_price_type">
								<?php
								if ( isset( $options ) ) : 
									foreach ( $options as $label => $value ) : ?>
									<option value="<?php echo $value; ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
								</select>

								<span class="button remove"><?php _e( 'Remove', 'b2b-market' ); ?></span>
							</p>
							<?php
							$counter++;
						}
					}
				}
			}
			?>
			<div class="new-bulk">
				<span id="here"></span>
				<span class="button add"><?php _e( 'Add', 'b2b-market' ); ?></span>
			</div>
			<script>
				var $b2b =jQuery.noConflict();
				$b2b(document).ready(function() {
					var count = <?php echo $counter; ?>;

					var bulk_price_label = '<?php _e( "Bulk Price", "b2b-market" ); ?>';
					var bulk_price_from_label = '<?php _e( "Amount (from)", "b2b-market" ); ?>';
					var bulk_price_to_label = '<?php _e( "Amount (to)", "b2b-market" ); ?>';
					var bulk_price_type_label = '<?php _e( "Price-Type", "b2b-market" ); ?>';

					var bulk_price_type_fix_label = '<?php _e( "Fix Price", "b2b-market" ); ?>';
					var bulk_price_type_discount_label = '<?php _e( "Discount (fixed Value)", "b2b-market" ); ?>';
					var bulk_price_type_discount_percent_label = '<?php _e( "Discount (%)", "b2b-market" ); ?>';
					var bulk_price_remove_label = '<?php _e( "Remove", "b2b-market" ); ?>';

					$b2b(".add").click(function() {
						count = count + 1;
						var content = '<p><label for="bulk_price[' + count + '][bulk_price]">' + bulk_price_label + '</label><input type="number" step="0.0001" min="0" name="bulk_price[' + count + '][bulk_price]" value="" />' +
						'<label for="bulk_price[' + count + '][bulk_price_from]">' + bulk_price_from_label + '</label><input type="number" step="1" min="0" name="bulk_price[' + count + '][bulk_price_from]" value="" />' +
						'<label for="bulk_price[' + count + '][bulk_price_to]">' + bulk_price_to_label + '</label><input type="number" step="1" min="0" name="bulk_price[' + count + '][bulk_price_to]" value="" />' +
						'<label for="bulk_price[' + count + '][bulk_price_type]">' + bulk_price_type_label + '</label>' +
						'<select id="bulk_price_type" name="bulk_price[' + count + '][bulk_price_type]" class="bulk_price_type">' +
						'<option value="fix">' + bulk_price_type_fix_label + '</option>' +
						'<option value="discount">' + bulk_price_type_discount_label + '</option>' +
						'<option value="discount-percent">' + bulk_price_type_discount_percent_label + '</option>' +
						'</select>' +
						'<span class="button remove">' + bulk_price_remove_label + '</span>' + 
						'</p>';

						$b2b('#here').append(content);

						return false;
					});

					$b2b(document).on('click', '.remove', function(){
						$b2b(this).parent().remove();
					});
				});
				</script>
			</div>
		</table>
		
		<?php
	}

	/**
	 * Output type bm_repeater_fields
	 *
	 * @access public
	 * @return void
	 */
	public function bm_group_repeatable( $value ) {

		$group_prices = get_option( 'bm_global_group_prices' );

		$options = array(
			__( 'Fix Price', 'b2b-market' )              => 'fix',
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		?>
		<table class="form-table">
			<div class="bm-groupprices-inner">
			<label class="titledesc"><?php _e( 'Group Prices', 'b2b-market' ); ?></label>
			<?php

			/* filled with existing data */
			$counter = 0;
			if ( isset( $group_prices ) && ! empty( $group_prices ) ) {
				if ( count( $group_prices ) > 0 ) {
					foreach ( $group_prices as $price ) {

						if ( isset( $price['group_price_type'] ) ) {
							$selected = $price['group_price_type'];
						} else {
							$selected = 'fix';
						}

						if ( isset( $price['group_price'] ) ) {
							?>
							<p>
								<label for="group_price[<?php echo $counter; ?>][group_price]"><?php _e( 'Price', 'b2b-market' ); ?></label>
								<input type="number" step="0.0001" min="0" name="group_price[<?php echo $counter; ?>][group_price]" value="<?php echo $price['group_price']; ?>" />

								<label for="group_price[<?php echo $counter; ?>][group_price_type]"><?php _e( 'Price-Type', 'b2b-market' ); ?></label>
								<select id="group_price_type" name="group_price[<?php echo $counter; ?>][group_price_type]" class="group_price_type">
								<?php
								if ( isset( $options ) ) : 
									foreach ( $options as $label => $value ) : ?>
									<option value="<?php echo $value; ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
								</select>

								<span class="button remove"><?php _e( 'Remove', 'b2b-market' ); ?></span>
							</p>
							<?php
							$counter++;
						}
					}
				}
			}
			?>
			<div class="new-bulk">
				<span id="here-group-price"></span>
				<span class="button add-group-price"><?php _e( 'Add', 'b2b-market' ); ?></span>
			</div>
			<script>
				var $group_prices =jQuery.noConflict();
				$group_prices(document).ready(function() {
					var count = <?php echo $counter; ?>;

					var group_price_label = '<?php _e( "Price", "b2b-market" ); ?>';
					var group_price_type_label = '<?php _e( "Price-Type", "b2b-market" ); ?>';

					var group_price_type_fix_label = '<?php _e( "Fix Price", "b2b-market" ); ?>';
					var group_price_type_discount_label = '<?php _e( "Discount (fixed Value)", "b2b-market" ); ?>';
					var group_price_type_discount_percent_label = '<?php _e( "Discount (%)", "b2b-market" ); ?>';
					var group_price_remove_label = '<?php _e( "Remove", "b2b-market" ); ?>';

					$group_prices(".add-group-price").click(function() {
						count = count + 1;
						var content = '<p><label for="group_price[' + count + '][group_price]">' + group_price_label + '</label><input type="number" step="0.0001" min="0" name="group_price[' + count + '][group_price]" value="" />' +
						'<label for="group_price[' + count + '][group_price_type]">' + group_price_type_label + '</label>' +
						'<select id="group_price_type" name="group_price[' + count + '][group_price_type]" class="group_price_type">' +
						'<option value="fix">' + group_price_type_fix_label + '</option>' +
						'<option value="discount">' + group_price_type_discount_label + '</option>' +
						'<option value="discount-percent">' + group_price_type_discount_percent_label + '</option>' +
						'</select>' +
						'<span class="button remove">' + group_price_remove_label + '</span>' + 
						'</p>';

						$b2b('#here-group-price').append(content);

						return false;
					});

					$b2b(document).on('click', '.remove', function(){
						$b2b(this).parent().remove();
					});
				});
				</script>
			</div>
		</table>
		
		<?php
	}

	/**
	 * Output type wgm_ui_checkbox
	 *
	 * @access public
	 * @hook woocommerce_admin_field_wgm_ui_checkbox
	 * @return void
	 */
	public function bm_ui_checkbox( $value ) {

	$option_value    = WC_Admin_Settings::get_option( $value['id'], $value['default'] );

		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $value );
		extract( $field_description );

		$visbility_class = array();

		if ( ! isset( $value['hide_if_checked'] ) ) {
			$value['hide_if_checked'] = false;
		}
		if ( ! isset( $value['show_if_checked'] ) ) {
			$value['show_if_checked'] = false;
		}
		if ( 'yes' == $value['hide_if_checked'] || 'yes' == $value['show_if_checked'] ) {
			$visbility_class[] = 'hidden_option';
		}
		if ( 'option' == $value['hide_if_checked'] ) {
			$visbility_class[] = 'hide_options_if_checked';
		}
		if ( 'option' == $value['show_if_checked'] ) {
			$visbility_class[] = 'show_options_if_checked';
		}

		?>
			<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
				<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?><?php echo $tooltip_html; ?></th>
				<td class="forminp forminp-checkbox">
					<fieldset>
		<?php

		if ( ! empty( $value['title'] ) ) {
			?>
				<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
			<?php
		}
		
		?>
			
			<label class="switch" for="<?php echo $value['id'] ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="checkbox"
					class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
					value="on"
					<?php checked( $option_value, 'on' ); ?>

				/>
				<div class="slider round bm-slider"></div>

			</label> 
			
			<?php
				$off_active = $option_value == 'off' ? 'active' : 'clickable';
				$on_active  = $option_value == 'on' ? 'active' : 'clickable';
			?>
			<p class="screen-reader-buttons">
				<span class="bm-ui-checkbox switcher off <?php echo $off_active; ?>"><?php echo __( 'Off', 'b2b-market' ); ?></span>
				<span class="bm-ui-checkbox delimter">|</span>
				<span class="bm-ui-checkbox switcher on <?php echo $on_active; ?>"><?php echo __( 'On', 'b2b-market' ); ?></span>
			</p>

			<?php
				if ( isset( $value[ 'desc' ] ) && $value[ 'desc' ] != '' ) {
					?><br /><span class="description"><?php echo $value[ 'desc' ]; ?></span><?php
				}
			?>
		<?php

		if ( ! isset( $value['checkboxgroup'] ) || 'end' == $value['checkboxgroup'] ) {
						?>
						</fieldset>
					</td>
				</tr>
			<?php
		} else {
			?>
				</fieldset>
			<?php
		}
	}


	/**
	 * Output a code textarea
	 *
	 * @param string $value given value.
	 * @return void
	 */
	public static function output_code( $value ) {

		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $value );
		extract( $field_description );

		$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default'] );
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?></label><?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ) ?>">
				<textarea class="code-textarea"
					name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
					rows="10" cols="40"
					><?php echo $option_value; ?></textarea>
					<br /><span class="description"><?php echo $value[ 'desc' ]; ?></span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueue build in code mirror js for code fields.
	 *
	 * @param string $hook current hook.
	 * @return void
	 */
	public function codemirror_enqueue_scripts( $hook ) {

		$screen = get_current_screen();

		if ( 'woocommerce_page_b2b-market' === $screen->base ) {
			$bm_code['codeEditor'] = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			wp_localize_script( 'jquery', 'cm_settings', $bm_code );

			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );		
		}
	}

	/**
	 * Save type wgm_ui_checkbox
	 *
	 * @access public
	 * @hook woocommerce_admin_settings_sanitize_option
	 *
	 * @param Mixed $value
	 * @param Array $option
	 * @param Mixed $raw_value
	 *
	 * @return $value
	 */
	public function woocommerce_admin_settings_sanitize_option( $value, $option, $raw_value ) {

		if ( 'bm_ui_checkbox' == $option['type'] ) {
			$value = is_null( $raw_value ) ? 'off' : 'on';
		}

		if ( isset( $option[ 'type'] ) && $option[ 'type' ] == 'bm_ui_code' ) {
			return html_entity_decode( wp_kses_post( trim( $raw_value ) ) );
		}

		if ( 'bm_repeatable' == $option['type'] ) {

			/* bulk meta */

			if ( isset( $_POST['bulk_price'] ) && ! empty( $_POST['bulk_price'] ) ) {
				$bulk_prices = $_POST['bulk_price'];
			}

			if ( isset( $bulk_prices ) && ! empty( $bulk_prices ) ) {
				update_option( 'bm_global_bulk_prices', $bulk_prices );
			} else {
				delete_option( 'bm_global_bulk_prices' );
			}
		}

		if ( 'bm_group_repeatable' == $option['type'] ) {

			/* group meta */
			if ( isset( $_POST['group_price'] ) && ! empty( $_POST['group_price'] ) ) {
				$group_prices = $_POST['group_price'];
			}

			if ( isset( $group_prices ) && ! empty( $group_prices ) ) {
				update_option( 'bm_global_group_prices', $group_prices );
			} else {
				delete_option( 'bm_global_group_prices' );
			}
		}

		return $value;
	}

	/**
	 * Get left menu items
	 *
	 * @access private
	 * @return array
	 */
	private function get_left_menu_items() {

		$groups = array(
			'title'    => __( 'Customer Groups', 'b2b-market' ),
			'slug'     => 'groups',
			'callback' => array( $this, 'groups_tab' ),
		);

		$groups = apply_filters( 'woocommerce_bm_ui_menu_b2b_market', $groups );

		$all_users = array(
			'title'    => __( 'All Customers', 'b2b-market' ),
			'slug'     => 'global',
			'options'  => 'yes',
			'submenu'  => array(
				array(
					'title'    => __( 'All Customers', 'b2b-market' ),
					'slug'     => 'global',
					'callback' => array( $this, 'global_tab' ),
					'options'  => 'yes',
				),
			),
		);

		$all_users = apply_filters( 'woocommerce_bm_ui_menu_b2b_market', $all_users );

		$german_market_options = false;

		/*
		if ( class_exists( 'Woocommerce_German_Market' ) ) {
			$german_market_options = array(
				'title'    => __( 'German Market', 'b2b-market' ),
				'slug'     => 'german-market',
				'callback' => array( $this, 'german_market_tab' ),
				'options'  => 'yes',
			);
		}*/

		$options = array(
			'title'    => __( 'Options', 'b2b-market' ),
			'slug'     => 'options',
			'options'  => 'yes',
			'submenu'  => array(
				array(
					'title'    => __( 'General', 'b2b-market' ),
					'slug'     => 'misc',
					'callback' => array( $this, 'misc_tab' ),
					'options'  => 'yes',
				),
				array(
					'title'    => __( 'Administration', 'b2b-market' ),
					'slug'     => 'administration',
					'callback' => array( $this, 'admin_tab' ),
					'options'  => 'yes',
				),
				array(
					'title'    => __( 'Price Display', 'b2b-market' ),
					'slug'     => 'price_display',
					'callback' => array( $this, 'price_display_tab' ),
					'options'  => 'yes',
				),
			),
		);

		if ( false !== $german_market_options ) {
			array_splice( $options[ 'submenu' ], 1, 0, array( $german_market_options ) );
		}

		$options = apply_filters( 'woocommerce_bm_ui_menu_b2b_market', $options );

		$add_ons = array(
			'title'    => __( 'Add-ons', 'b2b-market' ),
			'slug'     => 'add-ons',
			'new'      => 'yes',
			'callback' => array( $this, 'render_add_ons' ),
		);

		$add_ons = apply_filters( 'woocommerce_bm_ui_menu_add_ons', $add_ons );

		$items = array(
			0   => $all_users,
			1   => $groups,
			500 => $add_ons,
			600 => $options,
		);

		$items = apply_filters( 'woocommerce_bm_ui_left_menu_items', $items );
		ksort( $items );

		return $items;
	}

	/**
	 * Add Submenu to WooCommerce Menu
	 *
	 * @add_submenu_page
	 * @access public
	 */
	public function render_bm_menu() {

		do_action( 'render_bm_menu_save_options' );

		?>
		<div class="wrap">
			<div class='b2b-market'>
				<div class="b2b-market-left-menu">
					<div class="logo"></div>
						<div class="mobile-menu-outer">
							<div class="mobile-menu-button">
								<div class="txt"><?php echo __( 'Menu', 'b2b-market' ); ?></div>
									<div class="mobile-icon">
										<span></span>
										<span></span>
										<span></span>
										<span></span>
									</div>
							</div>
						</div>
						<ul>

						<?php

						$page_url = get_admin_url() . 'admin.php?page=b2b-market';

						$left_menu_items = $this->get_left_menu_items();
						$current         = '';

						$i = 0;

						foreach ( $left_menu_items as $item ) {

							$i ++;

							$classes = array();

							// slug
							$classes[] = $item['slug'];

							// current tab
							if ( isset( $_GET['tab'] ) ) {

								if ( $_GET['tab'] == $item['slug'] ) {
									$classes[] = 'current';
									$current   = $item;
								}
							} else {

								if ( 1 == $i ) {
									$classes[] = 'current'; // if tab is not set, first item is current
									$current   = $item;
								}
							}

							// new
							if ( isset( $item['new'] ) && $item['new'] ) {
								$classes[] = 'new';
							}

							// info
							if ( isset( $item['info'] ) && $item['info'] ) {
								$classes[] = 'info';
							}

							$classes      = apply_filters( 'woocommerce_de_ui_left_menu_item_class', $classes, $item );
							$class_string = implode( ' ', $classes );

							?>
							<li class="<?php echo $class_string; ?>">
								<a href="<?php echo $page_url . '&tab=' . $item['slug']; ?>"
								title="<?php echo esc_attr( $item['title'] ); ?>"><?php echo $item['title']; ?></a>
							</li>
						<?php } ?>
						<li>
						</ul>
						<div class="b2b-market-footer-menu">
						<?php echo __( sprintf( 'Version %s', BM::$version ), 'b2b-market' );?>
					</div>
					</div>
					
					<div class="b2b-market-main-menu">
					<?php $this->render_content( $current ); ?>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Render B2B Market Tab
	 *
	 * @access private
	 * @return array
	 */
	private function render_content( $item ) {

		$callback = isset( $item['callback'] ) ? $item['callback'] : '';
		$page_url = get_admin_url() . 'admin.php?page=b2b-market&tab=' . $item['slug'];
		$current  = $item;

		?>
		<h1><?php echo $item['title']; ?></h1>
		<?php

		do_action( 'woocommerce_de_ui_after_title', $item );

		// submenu
		if ( isset( $item['submenu'] ) ) {

			$submenu = $item['submenu'];
			$classes = array();

			?>
		<ul class="submenu">
			<?php
			$i = 0;

			foreach ( $submenu as $sub_item ) {

				$i ++;

				$classes = array();

				// current sub tab
				if ( isset( $_GET['sub_tab'] ) ) {

					if ( $_GET['sub_tab'] == $sub_item['slug'] ) {
						$classes[] = 'current';
						$current   = $sub_item;
						$callback  = isset( $sub_item['callback'] ) ? $sub_item['callback'] : $callback;
					}
				} else {

					if ( 1 == $i ) {
						$classes[] = 'current'; // if tab is not set, first item is current
						$current   = $sub_item;
						$callback  = isset( $sub_item['callback'] ) ? $sub_item['callback'] : $callback;
					}
				}

				$classes      = apply_filters( 'woocommerce_de_ui_sub_menu_item_class', $classes, $sub_item );
				$class_string = implode( ' ', $classes );
				?>
			<li class="<?php echo $class_string; ?>">
				<a href="<?php echo $page_url . '&sub_tab=' . $sub_item['slug']; ?>"
				title="<?php echo esc_attr( $sub_item['title'] ); ?>"><?php echo $sub_item['title']; ?></a>
			</li>
			<?php } ?>
		</ul>
			<?php
			do_action( 'woocommerce_de_ui_after_submenu', $item );
		}

		do_action( 'woocommerce_de_ui_before_callback', $callback );

		$is_option_page = isset( $current[ 'options' ] );

		// callback
		if ( isset( $callback ) ) {

			if ( ( is_array( $callback ) && method_exists( $callback[0], $callback[1] ) ) || ( ! ( is_array( $callback ) ) && function_exists( $callback ) ) ) {

				if ( $is_option_page ) {

					$options = call_user_func( $callback );

					// save settings
					if ( isset( $_POST['submit_save_bm_options'] ) ) {

						if ( ! wp_verify_nonce( $_POST['update_bm_settings'], 'woocommerce_de_update_bm_settings' ) ) {

							?>
					<div class="notice-bm notice-error">
						<p><?php echo __( 'Sorry, but something went wrong while saving your settings. Please, try again.', 'b2b-market' ); ?></p>
					</div>
							<?php

						} else {

							woocommerce_update_options( $options );

							do_action( 'woocommerce_bm_ui_update_options', $options );

							?>
					<div class="notice-bm notice-success">
						<p><?php echo __( 'Your settings have been saved.', 'b2b-market' ); ?></p>
					</div>
					<?php } } ?>
					<form method="post">
					<?php

					if ( 'yes' === $current[ 'options' ] ) {
						$this->save_button( 'top' );
					}

					wp_nonce_field( 'woocommerce_de_update_bm_settings', 'update_bm_settings' );
					woocommerce_admin_fields( $options );

					if ( 'yes' === $current[ 'options' ] ) {
						$this->save_button( 'bottom' );
					}

					/* add hook for custom actions with button displays on bottom of tab */
					do_action( 'woocommerce_bm_ui_after_save_button' );

					?>
					</form>
					<script>
					jQuery(document).ready(function($) {
						$('.code-textarea').each(function(){
							wp.codeEditor.initialize($(this), cm_settings);
						});
					})
					</script>
					<?php
				} else {
					call_user_func( $callback );
				}
			}
		}

		do_action( 'woocommerce_de_ui_after_callback', $callback );

	}

	/**
	 * Render Options for global
	 *
	 * @return void
	 */
	public function groups_tab() {
		$list_table = new BM_ListTable();
		$list_table->prepare_items();

		?>

		<div class="wrap b2b-group-table">
			<form id="groups-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>"/>
				<?php $list_table->display(); ?>
				<div class="alignright">
					<a href="" class="button action new-group"><?php _e( 'Add new Customer Group', 'b2b-market' ); ?></a>
				</div>
			</form>
		</div>
		<?php
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'options' . DIRECTORY_SEPARATOR . 'class-bm-edit-group.php' );
		do_action( 'woocommerce_bm_ui_after_save_button' );
	}

	/**
	 * Render Options for global
	 *
	 * @access public
	 * @return array
	 */
	public function global_tab() {

		$locale      = get_locale();
		$is_de       = ( stripos( $locale, 'de' ) === 0 ) ? true : false;
		$support_url = $is_de ? 'https://marketpress.de/hilfe/' : 'https://marketpress.com/help/';

		$price_types = array(
			'fix'              => __( 'Fixed Price', 'b2b-market' ),
			'discount'         => __( 'Discount (fixed Value)', 'b2b-market' ),
			'discount-percent' => __( 'Discount (%)', 'b2b-market' ),
		);

		$options = array(

			array(
				'name' => __( 'Prices (All Customers)', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_global_price_title',
				'desc' => __( 'All prices which are defined here are valid for all customers including guests and all members of all customer groups.<br> B2B Market checks for the cheapest price to apply for the current customer.', 'b2b-market' ),
			),
			array(
				'name' => _x( 'Group Prices', 'b2b-market' ),
				'id'   => 'bm_global_group_prices',
				'type' => 'bm_group_repeatable',
			),
			array(
				'name' => _x( 'Bulk Prices', 'b2b-market' ),
				'id'   => 'bm_global_bulk_prices',
				'type' => 'bm_repeatable',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'bm_global_end',
			),
		);

		$options = apply_filters( 'bm_de_ui_options_global', $options );

		return $options;
	}
	/**
	 * Render Options for misc
	 *
	 * @access public
	 * @return array
	 */
	public function misc_tab() {

		$locale      = get_locale();
		$is_de       = ( stripos( $locale, 'de' ) === 0 ) ? true : false;
		$support_url = $is_de ? 'https://marketpress.de/hilfe/' : 'https://marketpress.com/help/';

		$options = array(

			array(
				'name' => __( 'Compatibility', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_global_compatiblity_title',
			),
			array(
				'name'     => __( 'Deactivate Whitelist Function', 'b2b-market' ),
				'id'       => 'deactivate_whitelist_hooks',
				'type'     => 'bm_ui_checkbox',
				'default'  => 'off',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_global_end',
			),
			array(
				'name' => __( 'Discount Message', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_global_discount_message_title',
			),
			array(
				'name'              => __( 'Here you can add a dismissable teaser on the top of your shop to inform customers about discounts.', 'b2b-market' ),
				'id'                => 'bm_global_discount_message',
				'type'              => 'textarea',
				'custom_attributes' => array( 'rows' => '10', 'cols' => '80' ),
				'default'           => '',
				'args'              => '',
			),
			array(
				'name'    => __( 'Background-Color', 'b2b-market' ),
				'id'      => 'bm_global_discount_message_background_color',
				'type'    => 'color',
				'default' => '#2fac66',
				'css'     => 'width: 100px;',
			),
			array(
				'name'    => __( 'Font-Color', 'b2b-market' ),
				'id'      => 'bm_global_discount_message_font_color',
				'type'    => 'color',
				'default' => '#FFFFFF',
				'css'     => 'width: 100px;',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'b2b_global_end',
			),
		);

		$options = apply_filters( 'woocommerce_de_ui_options_global', $options );

		return $options;
	}

	/**
	 * Render options for 'WooCommerce German Market'
	 *
	 * @return array
	 */
	public function german_market_tab() {

		$options = array(
			array(
				'name' => __( 'German Market', 'b2b-market' ),
				'desc' => __( 'Modify the behaviour of German Market per customer group', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_german_market_title',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'bm_german_market_title',
			),
		);

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'customer_groups',
		);
		$groups = get_posts( $args );

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$options[] = array(
					'name'    => $group->post_title,
					'type'    => 'title',
					'id'      => 'bm_german_market_group_title_' . $group->ID,
				);
				$options[] = array(
					'name'    => __( 'Hide Terms and Conditions', 'b2b-market' ),
					'type'    => 'bm_ui_checkbox',
					'id'      => 'bm_german_market_hide_toc_group_' . $group->ID,
					'default' => 'off',
				);
				$options[] = array(
					'name'    => __( 'Alternative Terms and Conditions Page', 'b2b-market' ),
					'type'    => 'single_select_page',
					'id'      => 'bm_german_market_toc_page_id_group_' . $group->ID,
					'class'   => 'wc-enhanced-select',
					'default' => '',
				);
				$options[] = array(
					'name'    => __( 'Hide Revocation', 'b2b-market' ),
					'type'    => 'bm_ui_checkbox',
					'id'      => 'bm_german_market_hide_revocation_group_' . $group->ID,
					'default' => 'off',
				);
				$options[] = array(
					'name'    => __( 'Revocation Page', 'b2b-market' ),
					'type'    => 'single_select_page',
					'id'      => 'bm_german_market_revocation_page_id_group_' . $group->ID,
					'class'   => 'wc-enhanced-select',
					'default' => '',
				);
				$options[] = array(
					'type'    => 'sectionend',
					'id'      => 'bm_german_market_group_title_' . $group->ID,
				);
			}
		}

		return $options;
	}

	/**
	 * Render options for Administration
	 *
	 * @return array
	 */
	public function admin_tab() {

		$locale      = get_locale();
		$is_de       = ( stripos( $locale, 'de' ) === 0 ) ? true : false;
		$support_url = $is_de ? 'https://marketpress.de/hilfe/' : 'https://marketpress.com/help/';

		$options = array(

			array(
				'name' => __( 'Administration', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_administration_title',
			),
			array(
				'name'     => __( 'Activate No-Cache Mode in Admin', 'b2b-market' ),
				'id'       => 'bm_activate_no_cache',
				'type'     => 'bm_ui_checkbox',
				'default'  => 'off',
			),
			array(
				'name'     => __( 'Show customer groups in orders', 'b2b-market' ),
				'id'       => 'bm_show_groups_in_orders',
				'type'     => 'bm_ui_checkbox',
				'default'  => 'on',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_administration_end',
			),
		);

		$options = apply_filters( 'woocommerce_de_ui_options_global', $options );

		return $options;
	}
	/**
	 * Render options for Price Display
	 *
	 * @return array
	 */
	public function price_display_tab() {
		$locale      = get_locale();
		$is_de       = ( stripos( $locale, 'de' ) === 0 ) ? true : false;
		$support_url = $is_de ? 'https://marketpress.de/hilfe/' : 'https://marketpress.com/help/';

		$options = array(

			array(
				'name'        => __( 'General settings', 'b2b-market' ),
				'desc'        => __( 'Additional settings', 'b2b-market' ),
				'type'        => 'title',
				'id'          => 'bm_totals_title',
			),
			array(
				'name'    => __( 'Show total price on product page', 'b2b-market' ),
				'id'      => 'bm_bulk_price_table_show_totals',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
				'desc'    => __( 'Shows the updated total price on the product page, in case of e.g. change of quantity.', 'b2b-market' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_rrp_end',
			),

			array(
				'name'        => __( 'RRP', 'b2b-market' ),
				'desc' => __( 'Modify the RRP visibility and output.', 'b2b-market' ),
				'type'        => 'title',
				'id'          => 'bm_rrp_title',
			),
			array(
				'name'    => __( 'Use RRP', 'b2b-market' ),
				'id'      => 'bm_use_rrp',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Show RRP for all customers', 'b2b-market' ),
				'id'      => 'bm_show_rrp_all_customers',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Show RRP for variable products (if all RRP are equal)', 'b2b-market' ),
				'id'      => 'bm_show_rrp_variable_products',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
				'desc'    => __( 'The RRP is output by default when the variant is selected, even if this option is disabled.', 'b2b-market' ),
			),
			array(
				'name'    => __( 'RRP Label', 'b2b-market' ),
				'id'      => 'bm_rrp_label',
				'type'    => 'text',
				'default' => __( 'RRP', 'b2b-market' ),
			),
			array(
				'name'    => __( 'RRP Price Format', 'b2b-market' ),
				'id'      => 'bm_rrp_price_format',
				'type'    => 'select',
				'default' => 1,
				'options' => array(
					'gross'       => __( 'Always gross', 'b2b-market' ),
					'group-based' => __( 'Based on customer group tax settings', 'b2b-market' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_rrp_end',
			),
			array(
				'name'        => __( 'Bulk Prices', 'b2b-market' ),
				'desc' => __( 'Modify the bulk prices output.', 'b2b-market' ),
				'type'        => 'title',
				'id'          => 'bm_buk_price_title',
			),
			array(
				'name'    => __( 'Show bulk price message on shop pages', 'b2b-market' ),
				'id'      => 'bm_bulk_price_on_shop',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Show bulk price message on product pages', 'b2b-market' ),
				'id'      => 'bm_bulk_price_on_product',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Bulk price message', 'b2b-market' ),
				'id'      => 'bm_bulk_price_discount_message',
				'type'    => 'textarea',
				'default' => __( 'From [bulk_qty]x only [bulk_price] each.', 'b2b-market' ),
			),
			array(
				'name'    => __( 'Show bulk price message below price', 'b2b-market' ),
				'id'      => 'bm_bulk_price_below_price',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Show bulk price table on product page', 'b2b-market' ),
				'id'      => 'bm_bulk_price_table_on_product',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Background-Color active bulk prices in table', 'b2b-market' ),
				'id'      => 'bm_bulk_price_table_active_row_background_color',
				'type'    => 'color',
				'default' => '#eaeaea',
				'css'     => 'width: 100px;',
			),
			array(
				'name'    => __( 'Font-Color for active row in bulk price table', 'b2b-market' ),
				'id'      => 'bm_bulk_price_table_active_row_font_color',
				'type'    => 'color',
				'default' => '#222222',
				'css'     => 'width: 100px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_rrp_end',
			),
			array(
				'name'        => __( 'Cart Discount Notice', 'b2b-market' ),
				'desc' => __( 'Shows the percentage discount per item in your cart.', 'b2b-market' ),
				'type'        => 'title',
				'id'          => 'bm_cart_display_title',
			),
			array(
				'name'    => __( 'Activate for single item price', 'b2b-market' ),
				'id'      => 'bm_cart_item_price_discount',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'    => __( 'Activate for subtotal', 'b2b-market' ),
				'id'      => 'bm_cart_item_subtotal_discount',
				'type'    => 'bm_ui_checkbox',
				'default' => 'off',
			),
			array(
				'name'              => __( 'Discount Text', 'b2b-market' ),
				'id'                => 'bm_cart_item_discount_text',
				'desc'              => __( 'Use the shortcode [percent] to show the percentage discount,[old-price] to show the regular price without any discounts and [absolute-discount] to show the absolute discount.', 'b2b-market' ),
				'type'              => 'textarea',
				'custom_attributes' => array( 'rows' => '5', 'cols' => '60' ),
				'default'           => sprintf( __( 'You will save [percent] %s compared to the original price of [old-price]', 'b2b-market' ), '%' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'b2b_cart_display_end',
			),
			array(
				'name' => __( 'Hide Prices for Customer Groups', 'b2b-market' ),
				'desc' => __( 'Completly hides the prices for products and shows the alternative message below. This option also completly blocks the ability to put products in the cart and go to checkout.', 'b2b-market' ),
				'type' => 'title',
				'id'   => 'bm_price_display_title',
			),
		);

		$groups = new BM_User();

		foreach ( $groups->get_all_customer_groups() as $group ) {
			foreach ( $group as $key => $value ) {

				if ( 'alle-kunden' === $key ) {
					continue;
				}

				$price_display_for_group = array(
					'name'    => ucfirst( $key ),
					'id'      => 'bm_hide_price_' . $key,
					'type'    => 'bm_ui_checkbox',
					'default' => 'off',
				);

				array_push( $options, $price_display_for_group );

			}
		}

		$message = array(
			'name'              => __( 'When you hide a price for one or more customer groups, this message will be shown instead. You can also use HTML.', 'b2b-market' ),
			'id'                => 'bm_hide_price_message',
			'type'              => 'textarea',
			'custom_attributes' => array( 'rows' => '10', 'cols' => '80' ),
			'default'           => sprintf( __( 'You need to login <a href="%s">here</a> before you can buy any products' ), get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ),
			'args'              => '',
		);

		array_push( $options, $message );

		$end = array(
			'type' => 'sectionend',
			'id'   => 'b2b_price_display_end',
		);

		array_push( $options, $end );

		$options = apply_filters( 'woocommerce_de_ui_options_global', $options );

		return $options;
	}


	/**
	 * Render Add-On Tab
	 *
	 * @access public
	 * @return void
	 */
	public static function render_add_ons() {

		// Init
		$add_ons = self::get_addons();

		// Update Options
		if ( isset( $_POST['update_add_ons'] ) ) {

			if ( ! wp_verify_nonce( $_POST['update_add_ons'], 'woocommerce_bm_update_add_ons' ) ) {

				?>
				<div class="notice notice-error">
					<p><?php echo __( 'Sorry, but something went wrong while saving your settings. Please, try again.', 'b2b-market' ); ?></p>
				</div>
				<?php

			} else {

				foreach ( $add_ons as $add_on ) {

					if ( isset( $_POST[ $add_on['id'] ] ) ) {
						$current_activation = $add_on['on-off'];
						$new_activation     = $current_activation == 'on' ? 'off' : 'on';
						update_option( $add_on['id'], $new_activation );
					}
				}

				// Do a little trick (add-ons are activated after second reload)
				wp_safe_redirect( get_admin_url() . 'admin.php?page=b2b-market&tab=add-ons&updated_bm_add_ons=' . time() );
				exit();
			}
		}

		// Show notice when settings have been saved
		if ( isset( $_REQUEST['updated_bm_add_ons'] ) ) {

			// If someone reloads the page, the message should not be shown
			if ( intval( $_REQUEST['updated_bm_add_ons'] ) + 1 >= time() ) {

				?>
				<div class="notice notice-success">
					<p><?php echo __( 'Your settings have been saved.', 'b2b-market' ); ?></p>
				</div>
				<?php
			}
		}

		?>
		<form method="post">
		<?php wp_nonce_field( 'woocommerce_bm_update_add_ons', 'update_add_ons' ); ?>
			<div class="add-ons">
				<div class="description"></div>
				<?php

				foreach ( $add_ons as $add_on ) {

					?>
				<div class="add-on-box <?php echo $add_on['on-off']; ?>">
					<div class="icon logo-box">
						<?php if ( $add_on['image'] != '' ) { ?>
							<img src="<?php echo $add_on['image']; ?>" alt="logo"/>
							<?php } elseif ( '' != $add_on['dashicon'] ) { ?>
								<span class="dashicons dashicons-<?php echo $add_on['dashicon']; ?>"></span>
							<?php } else { ?>
								<span class="dashicons dashicons-admin-generic"></span>
							<?php } ?>
					</div>
					<div class="on-off-box">
						<label class="switch">
						<?php

						if ( $add_on['on-off'] == 'on' ) { ?>
							<input type="submit" class="add-on-switcher on"name="<?php echo $add_on['id']; ?>"
							value="" />
						<div class="slider round"></div>
						<?php
						} elseif ( 'off' == $add_on['on-off'] ) { ?>
							<input type="submit" class="add-on-switcher off"
							name="<?php echo $add_on['id']; ?>" value="" />
							<div class="slider round"></div>
							<?php } ?>
						</label>
					</div>
					<span style="clear: both; display: block;"></span>
					<div class="title">
					<?php echo $add_on['title']; ?>
					</div>
					<div class="description">
					<?php echo $add_on['description']; ?>
					</div>
				</div>
				<?php } ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Get Add-Ons
	 *
	 * @access private
	 * @return array
	 */
	private static function get_addons() {

		$refund_on_off     = 'always-off';
		$bm_add_ons_refund = array(
			'bm_shipping_and_payment',
		);
		foreach ( $bm_add_ons_refund as $bm_add_on_refund ) {
			if ( get_option( $bm_add_on_refund ) == 'on' ) {
				$refund_on_off = 'always-on';
				break;
			}
		}

		$bm_add_ons = array(
			array(
				'title'       => __( 'Conditional Shipping & Payments', 'b2b-market' ),
				'description' => __( 'B2B-Market Shipping & Payments let you control the conditional displays of shipping and payments options per customer group', 'b2b-market' ),
				'image'       => B2B_PLUGIN_URL . '/assets/admin/img/bedingt-versand.jpg',
				'dashicon'    => '',
				'video'       => 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'      => get_option( 'bm_addon_shipping_and_payment' ) == 'on' ? 'on' : 'off',
				'id'          => 'bm_addon_shipping_and_payment',
			),
			array(
				'title'       => __( 'Import & Export', 'b2b-market' ),
				'description' => __( 'B2B-Market Import & Export let you export your customer groups with all pricing options. Works also with plugin settings', 'b2b-market' ),
				'image'       => B2B_PLUGIN_URL . '/assets/admin/img/import-export.jpg',
				'dashicon'    => '',
				'video'       => 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'      => get_option( 'bm_addon_import_and_export' ) == 'on' ? 'on' : 'off',
				'id'          => 'bm_addon_import_and_export',
			),
			array(
				'title'       => __( 'Registration', 'b2b-market' ),
				'description' => __( 'Allow Users to registrate for specific Customer Groups. Use Double Opt-In and Vat-Check.', 'b2b-market' ),
				'image'       => B2B_PLUGIN_URL . '/assets/admin/img/registrierung.jpg',
				'dashicon'    => '',
				'video'       => 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'      => get_option( 'bm_addon_registration' ) == 'on' ? 'on' : 'off',
				'id'          => 'bm_addon_registration',
			),
			array(
				'title'       => __( 'Min & Max Quantities', 'b2b-market' ),
				'description' => __( 'B2B-Market Min & Max Quantities let you define min and max quantities for products per user group. You could also define steps for each product and group.', 'b2b-market' ),
				'image'       => B2B_PLUGIN_URL . '/assets/admin/img/import-export.jpg',
				'dashicon'    => '',
				'video'       => 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'      => get_option( 'bm_addon_quantities' ) == 'on' ? 'on' : 'off',
				'id'          => 'bm_addon_quantities',
			),
			array(
				'title'       => __( 'Slack Connector', 'b2b-market' ),
				'description' => __( 'Allow users to connect woocommerce with slack and get notified about orders, new customers and more.', 'b2b-market' ),
				'image'       => B2B_PLUGIN_URL . '/assets/admin/img/slackconnector.jpg',
				'dashicon'    => '',
				'video'       => 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'      => get_option( 'bm_addon_slack' ) == 'on' ? 'on' : 'off',
				'id'          => 'bm_addon_slack',
			),
		);


		return apply_filters( 'woocommerce_bm_add_ons_menu_list', $bm_add_ons );

	}

	/**
	 * Get Save Button
	 *
	 * @return void
	 */
	private function save_button( $class = 'top' ) {
		?>
		<input type="submit" name="submit_save_bm_options" class="save-bm-options <?php echo $class; ?>"
		value="<?php echo __( 'Save changes', 'b2b-market' ); ?>" />
		<?php
	}

	/**
	 * Get Video Div
	 *
	 * @access privat
	 * @static
	 *
	 * @param String $text
	 * @param String $url
	 *
	 * @return String
	 */
	public static function get_video_layer( $url ) {
		return '<div class="bm-video-wrapper">
					<span class="url">' . $url . '</span>
					<a class="open"><span class="dashicons dashicons-format-video icon"></span>' . __( 'Video', 'b2b-market' ) . '</a>
					<div class="videoouter">
                        <div class="videoinner">
                            <a class="close">' . __( 'Close', 'b2b-market' ) . '<span class="dashicons dashicons-no-alt icon"></span></a>
                            <div class="video"></div>
                        </div>
                    </div>
				</div>';
	}

}
