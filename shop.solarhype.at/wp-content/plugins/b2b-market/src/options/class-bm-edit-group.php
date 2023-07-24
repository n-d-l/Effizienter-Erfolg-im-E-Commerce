<?php

class BM_Edit_Group {

	/**
	 * @var string
	 */
	public $meta_prefix;
	/**
	 * @var string|void
	 */
	public $slug;
	/**
	 * @var string
	 */
	public $group_admin_url;


	/**
	 * BM_Edit_Group constructor.
	 */
	public function __construct() {
		$this->meta_prefix     = 'bm_';
		$this->slug            = __( 'customer_groups', 'b2b-market' );
		$this->group_admin_url = admin_url() . DIRECTORY_SEPARATOR . 'admin.php?page=b2b-market&tab=groups';

		$this->init();

	}


	/**
	 * initialize settings area
	 */
	public function init() {

		if ( isset( $_GET['group_id'] ) && 'new' != $_GET['group_id'] ) {
			$group_id = $_GET['group_id'];
		} elseif ( isset( $_GET['group_id'] ) && 'new' == $_GET['group_id'] ) {
			$group_id = '';
		} else {
			$group_id = '';
		}

		$group = get_post( $group_id );

		?>
		<div class="group-box">
			<a class="button" id="backtogroups" href="<?php echo $this->group_admin_url; ?>" style="margin-bottom:15px;"><?php _e( 'Return to all groups', 'b2b-market' ); ?></a>
			<form id="new_post" name="new_post" method="post" action="">
				<div class="group-line">
					<h3><?php _e( 'Title', 'b2b-market' ); ?></h3>
					<input class="space-right b2b-group-title" type="text" name="customer_group_title"
					value="<?php echo get_the_title( $group_id ); ?>" placeholder="Title">
					<p style="color:red;" class="b2b-name-warning"><?php _e( 'You should not name your Customer Groups like native roles with admin permission cause we are overwriting the permissions. If you name a group "Administator" you can not login after the group is created.', 'b2b-market' ); ?></p>
				</div>
				<div class="group-line">
					<h3><?php esc_html_e( 'Group Price', 'b2b-market' ); ?></h3>
					<?php $this->group_price_output( $group_id ); ?>
				</div>

				<div class="group-line">
					<h3><?php esc_html_e( 'Bulk Price', 'b2b-market' ); ?></h3>
					<?php $this->bulk_price_output( $group_id ); ?>
				</div>
				<div class="group-line">
					<h3><?php _e( 'Restrictions', 'b2b-market' ); ?></h3>
					<?php $this->conditional_display_output( $group_id ); ?>
				</div>
				<div class="group-line">
					<h3><?php _e( 'Discounts', 'b2b-market' ); ?></h3>
					<?php $this->automatic_actions_output( $group_id ); ?>
				</div>
				<div class="group-flex-line">
					<div class="group-line">
						<h3><?php _e( 'Tax Control', 'b2b-market' ); ?></h3>
						<?php $this->tax_control_output( $group_id ); ?>
					</div>
					<div class="group-line">
						<h3><?php _e( 'Additional Control', 'b2b-market' ); ?></h3>
						<?php $this->price_control_output( $group_id ); ?>
					</div>
				</div>
				<p align="right">
					<input class="button" type="submit" value="<?php _e( 'Save Group', 'b2b-market' ); ?>" tabindex="6" id="submit" name="submit"/>
				</p>
			</form>
		</div>

		<?php

		do_action( 'woocommerce_bm_ui_after_save_button' );
		$this->save( $group_id );

	}

	/**
	 * Handling repeatable fields for group prices.
	 *
	 * @param int $group_id current group id.
	 * @return void
	 */
	public function group_price_output( $group_id ) {
		global $sitepress;

		$group_prices = get_post_meta( $group_id, 'bm_group_prices', false );
		$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false ) );

		// set options for price type.
		$options = array(
			esc_html__( 'Fix Price', 'b2b-market' )              => 'fix',
			esc_html__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			esc_html__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		// build the options for repeatable product cat selection.
		$cats = '<option value="0">' . esc_html__( 'All categories', 'b2b-market' ) . '</option>';

		if ( isset( $product_cats ) && ! empty( $product_cats ) ) {
			foreach ( $product_cats as $cat ) {
				$cats .= '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
			}
		}
		?>
		<div class="bm-groupprices-inner">
			<p><?php esc_html_e( 'Modify the price for each product assigned to this customer group', 'b2b-market' ); ?></p>
		<?php
		$counter = 0;

		if ( ! empty( $group_prices ) && is_array( $group_prices ) && count( $group_prices ) > 0 ) {
			foreach ( $group_prices as $group_price ) {
				if ( is_array( $group_price ) ) {
					foreach ( $group_price as $price ) {

						// find selected type.
						if ( isset( $price['group_price_type'] ) ) {
							$selected = $price['group_price_type'];
						} else {
							$selected = 'fix';
						}

						// find selected cat.
						if ( isset( $price['group_price_category'] ) ) {
							$selected_cat = BM_Helper::get_translated_object_ids( $price[ 'group_price_category' ], 'category' );
						} else {
							$selected_cat = 0;
						}

						if ( isset( $price['group_price'] ) ) {

							$group_price_key          = 'group_price[' . esc_attr( $counter ) . '][group_price]';
							$group_price_type_key     = 'group_price[' . esc_attr( $counter ) . '][group_price_type]';
							$group_price_category_key = 'group_price[' . esc_attr( $counter ) . '][group_price_category]';

							?>
							<div class="bm-price-row">
								<span>
									<label for="<?php echo $group_price_key; ?>"><?php esc_html_e( 'Group Price', 'b2b-market' ); ?></label><br>
									<input type="number" step="0.0001" min="0" name="<?php echo $group_price_key; ?>" value="<?php echo esc_html( $price['group_price'] ); ?>" />
								</span>
								<span>
									<label for="<?php echo $group_price_type_key; ?>"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label><br>
									<select id="group_price_type" name="<?php echo $group_price_type_key; ?>" class="group_price_type">
									<?php
									if ( isset( $options ) ) :
										foreach ( $options as $label => $value ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
									</select>
								</span>
								<span>
									<label for="<?php echo $group_price_category_key; ?>"><?php esc_html_e( 'Product Category', 'b2b-market' ); ?></label><br>
									<select id="group_price_category" name="<?php echo $group_price_category_key; ?>" class="group_price_category">
										<option value="0"><?php esc_html_e( 'All categories', 'b2b-market' ); ?></option>
										<?php
										if ( isset( $product_cats ) ) :
											foreach ( $product_cats as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"<?php selected( $selected_cat, esc_attr( $cat->term_id ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</span>
								<span>
									<span class="button remove"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
								</span>
							</div>
							<?php
							$counter++;
						}
					}
				}
			}
		}
		?>
		<div class="new-group-price">
			<span id="here-group-price"></span>
			<span class="button add-group-price"><?php esc_html_e( 'Add', 'b2b-market' ); ?></span>
		</div>
		<script>
		jQuery(document).ready(function ($) {
			var count = <?php echo esc_html( $counter ); ?>;

			var group_price_label = '<?php esc_html_e( "Group Price", "b2b-market" ); ?>';
			var group_price_type_label = '<?php esc_html_e( "Price Type", "b2b-market" ); ?>';
			var group_price_category_label = '<?php esc_html_e( "Product Category", "b2b-market" ); ?>';

			var group_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
			var group_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
			var group_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
			var group_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

			var cats = '<?php echo $cats; ?>';

			$(".add-group-price").click(function() {
				count = count + 1;
				var content = '<div class="bm-price-row"><span><label for="group_price[' + count + '][group_price]">' + group_price_label + '</label><input type="number" step="0.0001" min="0" name="group_price[' + count + '][group_price]" value="" /></span>' +
				'<span><label for="group_price[' + count + '][group_price_type]">' + group_price_type_label + '</label>' +
				'<select id="group_price_type" name="group_price[' + count + '][group_price_type]" class="group_price_type">' +
				'<option value="fix">' + group_price_type_fix_label + '</option>' +
				'<option value="discount">' + group_price_type_discount_label + '</option>' +
				'<option value="discount-percent">' + group_price_type_discount_percent_label + '</option>' +
				'</select></span>' +
				'<span><label for="group_price[' + count + '][group_price_category]">' + group_price_category_label + '</label>' +
				'<select id="group_price_type" name="group_price[' + count + '][group_price_category]" class="group_price_category">' +
				cats +
				'</select></span>' +
				'<span><span class="button remove">' + group_price_remove_label + '</span></span>' + 
				'</div>';

				$('#here-group-price').append(content);
				return false;
			});
			$(document).click('.remove',function(e){
				if ( $(e.target).hasClass('remove') ) {
					$(e.target).parent().parent().remove();
				}
			});
		});
		</script>
		</div>
		<?php
	}

	/**
	 * Handling repeatable fields for bulk prices.
	 *
	 * @param int $group_id current group id.
	 * @return void
	 */
	public function bulk_price_output( $group_id ) {
		global $sitepress;

		$bulk_prices  = get_post_meta( $group_id, 'bm_bulk_prices', false );
		$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false ) );

		// set options for price type.
		$options = array(
			esc_html__( 'Fix Price', 'b2b-market' )              => 'fix',
			esc_html__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			esc_html__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		// build the options for repeatable product cat selection.
		$cats = '<option value="0">' . esc_html__( 'All categories', 'b2b-market' ) . '</option>';

		if ( isset( $product_cats ) && ! empty( $product_cats ) ) {
			foreach ( $product_cats as $cat ) {
				$cats .= '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
			}
		}
		?>
		<div class="bm-bulkprices-inner">
			<p><?php esc_html_e( 'Bulk prices are applied if the current quantity fits in the configured quantity range ', 'b2b-market' ); ?></p>
		<?php
		$counter = 0;

		if ( ! empty( $bulk_prices ) && is_array( $bulk_prices ) && count( $bulk_prices ) > 0 ) {
			foreach ( $bulk_prices as $bulk_price ) {
				if ( is_array( $bulk_price ) ) {
					foreach ( $bulk_price as $price ) {

						// find selected type.
						if ( isset( $price['bulk_price_type'] ) ) {
							$selected = $price['bulk_price_type'];
						} else {
							$selected = 'fix';
						}

						// find selected cat.
						if ( isset( $price['bulk_price_category'] ) ) {
							$selected_cat = BM_Helper::get_translated_object_ids( $price[ 'bulk_price_category' ], 'category' );
						} else {
							$selected_cat = 0;
						}

						if ( isset( $price['bulk_price'] ) || isset( $price['bulk_price_from'] ) || isset( $price['bulk_price_to'] ) ) {

							$bulk_price_key          = 'bulk_price[' . esc_attr( $counter ) . '][bulk_price]';
							$bulk_price_from_key     = 'bulk_price[' . esc_attr( $counter ) . '][bulk_price_from]';
							$bulk_price_to_key       = 'bulk_price[' . esc_attr( $counter ) . '][bulk_price_to]';
							$bulk_price_type_key     = 'bulk_price[' . esc_attr( $counter ) . '][bulk_price_type]';
							$bulk_price_category_key = 'bulk_price[' . esc_attr( $counter ) . '][bulk_price_category]';

							?>
							<div class="bm-price-row">
								<span>
									<label for="<?php echo $bulk_price_key; ?>"><?php esc_html_e( 'Bulk Price', 'b2b-market' ); ?></label><br>
									<input type="number" step="0.01" min="0" name="<?php echo $bulk_price_key; ?>" value="<?php echo esc_html( $price['bulk_price'] ); ?>" />
								</span>
								<span>
									<label for="<?php echo $bulk_price_from_key; ?>"><?php _e( 'Amount (from)', 'b2b-market' ); ?></label><br>
									<input type="number" step="1" min="0" name="<?php echo $bulk_price_from_key; ?>" value="<?php echo esc_html( $price['bulk_price_from'] ); ?>" />
								</span>
								<span>
									<label for="<?php echo $bulk_price_to_key; ?>"><?php esc_html_e( 'Amount (to)', 'b2b-market' ); ?></label><br>
									<input type="number" step="1" min="0" name="<?php echo $bulk_price_to_key; ?>" value="<?php if ( esc_attr( $price['bulk_price_to'] ) != 0 ) { echo esc_html( $price['bulk_price_to'] ); } ?>" <?php if ( isset( $price['bulk_price_to'] ) && esc_attr( $price['bulk_price_to'] ) == 0) { echo 'placeholder="∞"'; } ?> />
								</span>
								<span>
									<label for="<?php echo $bulk_price_type_key; ?>"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label><br>
									<select id="bulk_price_type" name="<?php echo $bulk_price_type_key; ?>" class="bulk_price_type">
									<?php
									if ( isset( $options ) ) :
										foreach ( $options as $label => $value ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
									</select>
								</span>
								<span>
									<label for="<?php echo $bulk_price_category_key; ?>"><?php esc_html_e( 'Product Category', 'b2b-market' ); ?></label><br>
									<select id="bulk_price_category" name="<?php echo $bulk_price_category_key; ?>" class="bulk_price_category">
										<option value="0"><?php esc_html_e( 'All categories', 'b2b-market' ); ?></option>
										<?php
										if ( isset( $product_cats ) ) :
											foreach ( $product_cats as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"<?php selected( $selected_cat, esc_attr( $cat->term_id ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</span>
								<span>
									<span class="button remove"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
								</span>
							</div>
							<?php
							$counter++;
						}
					}
				}
			}
		}
		?>
		<div class="new-bulk-price">
			<span id="here-bulk-price"></span>
			<span class="button add-bulk"><?php esc_html_e( 'Add', 'b2b-market' ); ?></span>
		</div>
		<script>
		jQuery(document).ready(function ($) {
			var count = <?php echo esc_html( $counter ); ?>;
			var bulk_price_label = '<?php esc_html_e( "Bulk Price", "b2b-market" ); ?>';
			var bulk_price_from_label = '<?php esc_html_e( "Amount (from)", "b2b-market" ); ?>';
			var bulk_price_to_label = '<?php esc_html_e( "Amount (to)", "b2b-market" ); ?>';
			var bulk_price_type_label = '<?php esc_html_e( "Price Type", "b2b-market" ); ?>';
			var bulk_price_category_label = '<?php esc_html_e( "Product Category", "b2b-market" ); ?>';

			var bulk_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
			var bulk_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
			var bulk_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
			var bulk_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

			var cats = '<?php echo $cats; ?>';

			$(".add-bulk").click(function() {
				count = count + 1;
				var content = '<div class="bm-price-row"><span><label for="bulk_price[' + count + '][bulk_price]">' + bulk_price_label + '</label><input type="number" step="0.0001" min="0" name="bulk_price[' + count + '][bulk_price]" value="" /></span>' +
				'<span><label for="bulk_price[' + count + '][bulk_price_from]">' + bulk_price_from_label + '</label><input type="number" step="1" min="0" name="bulk_price[' + count + '][bulk_price_from]" value="" /></span>' +
				'<span><label for="bulk_price[' + count + '][bulk_price_to]">' + bulk_price_to_label + '</label><input type="number" step="1" min="0" name="bulk_price[' + count + '][bulk_price_to]" value="" /></span>' +
				'<span><label for="bulk_price[' + count + '][bulk_price_type]">' + bulk_price_type_label + '</label>' +
				'<select id="bulk_price_type" name="bulk_price[' + count + '][bulk_price_type]" class="bulk_price_type">' +
				'<option value="fix">' + bulk_price_type_fix_label + '</option>' +
				'<option value="discount">' + bulk_price_type_discount_label + '</option>' +
				'<option value="discount-percent">' + bulk_price_type_discount_percent_label + '</option>' +
				'</select></span>' +
				'<span><label for="bulk_price[' + count + '][bulk_price_category]">' + bulk_price_category_label + '</label>' +
				'<select id="bulk_price_type" name="bulk_price[' + count + '][bulk_price_category]" class="bulk_price_category">' +
				cats +
				'</select></span>' +
				'<span><span class="button remove">' + bulk_price_remove_label + '</span></span>' + 
				'</div>';

				$('#here-bulk-price').append(content);
				return false;
			});
			$(document).click('.remove',function(e){
				if ( $(e.target).hasClass('remove') ) {
					$(e.target).parent().parent().remove();
				}
			});
		});
		</script>
		</div>
		<?php
	}

	/**
	 * output conditional fields
	 *
	 * @param $group_id
	 */
	public function conditional_display_output( $group_id ) {
		global $sitepress;

		$kg_conditional_products     = get_post_meta( $group_id, $this->meta_prefix . 'conditional_products', true );
		$kg_conditional_categories   = get_post_meta( $group_id, $this->meta_prefix . 'conditional_categories', true );
		$kg_conditional_all_products = get_post_meta( $group_id, $this->meta_prefix . 'conditional_all_products', true );
		$kg_min_order_amount         = get_post_meta( $group_id, $this->meta_prefix . 'min_order_amount', true );
		$kg_min_order_amount_message = get_post_meta( $group_id, $this->meta_prefix . 'min_order_amount_message', true );

		if ( ! empty( $kg_conditional_products ) ) {
			$kg_conditional_products_array      = explode( ',', $kg_conditional_products );
			$kg_translated_conditional_products = BM_Helper::get_translated_object_ids( $kg_conditional_products_array, 'post' );
			$kg_conditional_products            = implode( ',', $kg_translated_conditional_products );
		}
		if ( ! empty( $kg_conditional_categories ) ) {
			$kg_conditional_categories_array      = explode( ',', $kg_conditional_categories );
			$kg_translated_conditional_categories = BM_Helper::get_translated_object_ids( $kg_conditional_categories_array, 'category' );
			$kg_conditional_categories            = implode( ',', $kg_translated_conditional_categories );
		}

		if ( empty( $kg_min_order_amount_message ) ) {
			$kg_min_order_amount_message = __( 'You need to spend at least [min-amount] to complete your order.', 'b2b-market' );
		}

		if ( false == $kg_conditional_all_products ) {
			$kg_conditional_all_products = 'off';
		}

		$off_active = $kg_conditional_all_products == 'off' ? 'active' : 'clickable';
		$on_active  = $kg_conditional_all_products == 'on' ? 'active' : 'clickable';

		if ( 'on' == $kg_conditional_all_products ) {
			$check = 'checked="checked"';
		} else {
			$check = '';
		}
		$content  = '<p><b>' . __( 'Blacklist', 'b2b-market' ) . '</b></p><p>' . __( 'Choose which products and categories you want to exclude for this customer group.', 'b2b-market' ) . '</p>';
		$content .= '<div class="b2b-third  selection-products"><label for="' . $this->meta_prefix . 'conditional_products">' . __( 'Products:', 'b2b-market' ) . '</label><br><input id="searchable-conditional-products" class="space-right" size="100" type="text" name="' . $this->meta_prefix . 'conditional_products" value="' . esc_textarea( $kg_conditional_products ) . '"></div>';
		$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'conditional_categories">' . __( 'Product Categories:', 'b2b-market' ) . '</label><br><input id="searchable-conditional-categories" class="space-right" size="100" type="text" name="' . $this->meta_prefix . 'conditional_categories" value="' . esc_textarea( $kg_conditional_categories ) . '"></div>';

		$content .= '<div class="b2b-third"><span>' . __( 'Invert to Whitelist', 'b2b-market' ) . '</span><br>';
		$content .= '<label class="switch" style="margin-top:5px;" for="' . $this->meta_prefix . 'conditional_all_products">
				<input
					name="' . $this->meta_prefix . 'conditional_all_products"
					id="' . $this->meta_prefix . 'conditional_all_products"
					type="checkbox"
					class="' . esc_attr( isset( $value['class'] ) ? $value['class'] : '' ) . '"
					value="on"
					' . $check . '
				/>
				<div class="slider round bm-slider"></div>
			</label> 
			<p class="screen-reader-buttons">
				<span class="bm-ui-checkbox switcher off ' . $off_active . '">' . __( 'Off', 'b2b-market' ) . '</span>
				<span class="bm-ui-checkbox delimter">|</span>
				<span class="bm-ui-checkbox switcher on ' . $on_active . '">' . __( 'On', 'b2b-market' ) . '</span>
			</p></div>';
		$content  .= '<p style="display: block;float: left;width: 100%;"><b>' . __( 'Minimum order amount', 'b2b-market' ) . '</b></p><p>' . __( 'Set a minimum order <b>amount</b> for your customer group to <b>complete an order</b>. You can use the shortcode <b>[min-amount]</b> to dynamically add the amount to your error message.', 'b2b-market' ) . '</p>';
		$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'min_order_amount">' . __( 'Minimum order amount:', 'b2b-market' ) . '</label><input type="number" step="0.0001" min="0"  name="' . $this->meta_prefix . 'min_order_amount" value="' . esc_textarea( $kg_min_order_amount ) . '"></div>';
		$content .= '<div class="b2b-half"><label for="' . $this->meta_prefix . 'min_order_amount_message">' . __( 'Min order amount error message:', 'b2b-market' ) . '</label><textarea rows="3" cols="40" id="' . $this->meta_prefix . 'min_order_amount_message" name="' . $this->meta_prefix . 'min_order_amount_message" placeholder="' . __( 'You need to spend at least [min-amount] to complete your order.', 'b2b-market' ) . '">' . esc_textarea( $kg_min_order_amount_message ) . '</textarea></div>';

		echo $content;
	}

	/**
	 * output tax fields
	 *
	 * @param $group_id
	 */
	public function tax_control_output( $group_id ) {

		$content  = '<div class="bm-tax-settings"><p>' . __( 'Show net prices instead of gross price?', 'b2b-market' ) . '</p>';
		$tax_type = get_post_meta( $group_id, $this->meta_prefix . 'tax_type', true );
		$vat_type = get_post_meta( $group_id, $this->meta_prefix . 'vat_type', true );

		$guest_group = get_option( 'bm_guest_group' );

		if ( false == $tax_type ) {
			$tax_type = 'off';
		}
		if ( false == $vat_type ) {
			$vat_type = 'off';
		}

		$off_active_tax = $tax_type == 'off' ? 'active' : 'clickable';
		$on_active_tax  = $tax_type == 'on' ? 'active' : 'clickable';

		$off_active_vat = $vat_type == 'off' ? 'active' : 'clickable';
		$on_active_vat  = $vat_type == 'on' ? 'active' : 'clickable';

		if ( 'on' == $tax_type ) {
			$check_tax = 'checked="checked"';
		} else {
			$check_tax = '';
		}

		if ( 'on' == $vat_type ) {
			$check_vat = 'checked="checked"';
		} else {
			$check_vat = '';
		}

		$content .= '<label class="switch" style="margin-top:5px;" for="' . $this->meta_prefix . 'tax_type">
				<input
					name="' . $this->meta_prefix . 'tax_type"
					id="' . $this->meta_prefix . 'tax_type"
					type="checkbox"
					class="' . esc_attr( isset( $value['class'] ) ? $value['class'] : '' ) . '"
					value="on"
					' . $check_tax . '
				/>
				<div class="slider round bm-slider"></div>
			</label> 
			<p class="screen-reader-buttons">
				<span class="bm-ui-checkbox switcher off ' . $off_active_tax . '">' . __( 'Off', 'b2b-market' ) . '</span>
				<span class="bm-ui-checkbox delimter">|</span>
				<span class="bm-ui-checkbox switcher on ' . $on_active_tax . '">' . __( 'On', 'b2b-market' ) . '</span>
			</p>';

		if ( $group_id != $guest_group ) :
			$content .= '<p>' . __( 'Use VAT validation for this group registration?', 'b2b-market' ) . '</p>';
			$content .= '<label class="switch" style="margin-top:5px;" for="' . $this->meta_prefix . 'vat_type">
					<input
						name="' . $this->meta_prefix . 'vat_type"
						id="' . $this->meta_prefix . 'vat_type"
						type="checkbox"
						class="' . esc_attr( isset( $value['class'] ) ? $value['class'] : '' ) . '"
						value="on"
						' . $check_vat . '
					/>
					<div class="slider round bm-slider"></div>
				</label> 
				<p class="screen-reader-buttons">
					<span class="bm-ui-checkbox switcher off ' . $off_active_vat . '">' . __( 'Off', 'b2b-market' ) . '</span>
					<span class="bm-ui-checkbox delimter">|</span>
					<span class="bm-ui-checkbox switcher on ' . $on_active_vat . '">' . __( 'On', 'b2b-market' ) . '</span>
				</p>';
		endif;

		$content .= '</div>';

		echo $content;
	}

	/**
	 * output price fields
	 *
	 * @param $group_id
	 */
	public function price_control_output( $group_id ) {

		$content     = '<div class="bm-tax-settings"><p>' . __( "Show sale badge and sale price for products when a customer group discount is active.", 'b2b-market' ) . '<br><small>' . __( "We currently don't support percentage based calculations for the sale badge.", 'b2b-market' ) . '</small></p>';
		$sale_badge  = get_post_meta( $group_id, $this->meta_prefix . 'show_sale_badge', true );

		if ( false === $sale_badge ) {
			$sale_badge = 'off';
		}

		$off_active_badge = $sale_badge === 'off' ? 'active' : 'clickable';
		$on_active_badge  = $sale_badge === 'on' ? 'active' : 'clickable';

		if ( 'on' === $sale_badge ) {
			$check_badge = 'checked="checked"';
		} else {
			$check_badge = '';
		}

		$content .= '<label class="switch" style="margin-top:5px;" for="' . $this->meta_prefix . 'show_sale_badge">
				<input
					name="' . $this->meta_prefix . 'show_sale_badge"
					id="' . $this->meta_prefix . 'show_sale_badge"
					type="checkbox"
					class="' . esc_attr( isset( $value[ 'class' ] ) ? $value[ 'class' ] : '' ) . '"
					value="on"
					' . $check_badge . '
				/>
				<div class="slider round bm-slider"></div>
			</label> 
			<p class="screen-reader-buttons">
				<span class="bm-ui-checkbox switcher off ' . $off_active_badge . '">' . __( 'Off', 'b2b-market' ) . '</span>
				<span class="bm-ui-checkbox delimter">|</span>
				<span class="bm-ui-checkbox switcher on ' . $on_active_badge . '">' . __( 'On', 'b2b-market' ) . '</span>
			</p>';
		$content .= '</div>';

		echo $content;
	}

	/**
	 * output automatic action fields
	 *
	 * @param $group_id
	 */
	public function automatic_actions_output( $group_id ) {
		global $sitepress;

		$discount_products     = get_post_meta( $group_id, $this->meta_prefix . 'discount_products', true );
		$discount_categories   = get_post_meta( $group_id, $this->meta_prefix . 'discount_categories', true );
		$discount_all_products = get_post_meta( $group_id, $this->meta_prefix . 'discount_all_products', true );

		if ( ! empty( $discount_products ) ) {
			$discount_products_array      = explode( ',', $discount_products );
			$translated_discount_products = BM_Helper::get_translated_object_ids( $discount_products_array, 'post' );
			$discount_products            = implode( ',', $translated_discount_products );
		}
		if ( ! empty( $discount_categories ) ) {
			$discount_categories_array      = explode( ',', $discount_categories );
			$translated_discount_categories = BM_Helper::get_translated_object_ids( $discount_categories_array, 'category' );
			$discount_categories            = implode( ',', $translated_discount_categories );
		}

		if ( false == $discount_all_products ) {
			$discount_all_products = 'off';
		}

		$off_active = $discount_all_products == 'off' ? 'active' : 'clickable';
		$on_active  = $discount_all_products == 'on' ? 'active' : 'clickable';

		if ( 'on' == $discount_all_products ) {
			$check = 'checked="checked"';
		} else {
			$check = '';
		}

		/* first order discount */
		$discount_name = get_post_meta( $group_id, $this->meta_prefix . 'discount_name', true );
		$discount      = get_post_meta( $group_id, $this->meta_prefix . 'discount', true );
		$discount_type = get_post_meta( $group_id, $this->meta_prefix . 'discount_type', true );

		/* discount per category */
		$goods_categories    = get_post_meta( $group_id, $this->meta_prefix . 'goods_discount_categories', true );
		$goods_product_count = get_post_meta( $group_id, $this->meta_prefix . 'goods_product_count', true );
		$goods_discount      = get_post_meta( $group_id, $this->meta_prefix . 'goods_discount', true );
		$goods_discount_type = get_post_meta( $group_id, $this->meta_prefix . 'goods_discount_type', true );

		if ( ! empty( $goods_categories ) ) {
			$goods_categories_array      = explode( ',', $goods_categories );
			$translated_goods_categories = BM_Helper::get_translated_object_ids( $goods_categories_array, 'category' );
			$goods_categories            = implode( ',', $translated_goods_categories );
		}

		$discount_types = array(
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'order-discount-fix',
			__( 'Discount (%)', 'b2b-market' )           => 'order-discount-percent',
		);
		$guest_group = get_option( 'bm_guest_group' );

		$content = '';

		if ( $group_id != $guest_group ) {
			/* first order */
			$content = '<div class="discount-box"><b>' . __( 'First Order', 'b2b-market' ) . '</b><p>' . __( 'Set a discount for the first order of a user from this group', 'b2b-market' ) . '</p>';

			$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'discount_name">' . __( 'Label:', 'b2b-market' ) . '</label><input type="text" name="' . $this->meta_prefix . 'discount_name" value="' . esc_textarea( $discount_name ) . '"></div>';
			$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'discount">' . __( 'Discount:', 'b2b-market' ) . '</label><input type="number" step="0.0001" min="0"  name="' . $this->meta_prefix . 'discount" value="' . esc_textarea( $discount ) . '"></div>';
			$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'discount_type">' . __( 'Discount-Type:', 'b2b-market' ) . '</label><br><select name="' . $this->meta_prefix . 'discount_type" id="' . $this->meta_prefix . 'discount_type">';

			if ( is_array( $discount_types ) ) {
				foreach ( $discount_types as $key => $value ) {
					if ( $value == $discount_type ) {
						$content .= '<option selected value="' . esc_attr( $value ) . '">' . $key . '</option>';
					} else {
						$content .= '<option value="' . esc_attr( $value ) . '">' . esc_textarea( $key ) . '</option>';
					}
				}
			}
			$content .= '</select></div>';
			$content .= '<div class="b2b-third  selection-products"><label for="' . $this->meta_prefix . 'discount_products">' . __( 'Products:', 'b2b-market' ) . '</label><br><input id="discount-products" size="100" type="text" name="' . $this->meta_prefix . 'discount_products" value="' . esc_textarea( $discount_products ) . '"></div>';
			$content .= '<div class="b2b-third"><label for="' . $this->meta_prefix . 'discount_categories">' . __( 'Product Categories:', 'b2b-market' ) . '</label><br><input id="discount-categories" size="100" type="text" name="' . $this->meta_prefix . 'discount_categories" value="' . esc_textarea( $discount_categories ) . '"></div>';

			$content .= '<div class="b2b-third"><span>' . __( 'Override and activate for all products:', 'b2b-market' ) . '</span><br>';
			$content .= '<label class="switch" style="margin-top:5px;" for="' . $this->meta_prefix . 'discount_all_products">
					<input
						name="' . $this->meta_prefix . 'discount_all_products"
						id="' . $this->meta_prefix . 'discount_all_products"
						type="checkbox"
						class="' . esc_attr( isset( $value['class'] ) ? $value['class'] : '' ) . '"
						value="on"
						' . $check . '
					/>
					<div class="slider round bm-slider"></div>
				</label> 
				<p class="screen-reader-buttons">
					<span class="bm-ui-checkbox switcher off ' . $off_active . '">' . __( 'Off', 'b2b-market' ) . '</span>
					<span class="bm-ui-checkbox delimter">|</span>
					<span class="bm-ui-checkbox switcher on '. $on_active . '">'. __( 'On', 'b2b-market' ) . '</span>
				</p></div></div>';
		}
		/* category */

		$content .= '<div class="goods discount-box">';
		$content .= '<b>' . __( 'Products from Category', 'b2b-market' ) . '</b><p>' . __( 'Set a discount if a customer has the defined quantity of products from the given category in their cart.', 'b2b-market' ) . '</p>';

		$content .= '<div class="goods-part"><label for="' . $this->meta_prefix . 'goods_discount_categories">' . __( 'Product Categories', 'b2b-market' ) . ': </label><br><input id="searchable-discount-categories" class="space-right" size="100" type="text" name="' . $this->meta_prefix . 'goods_discount_categories" value="' . esc_textarea( $goods_categories ) . '"></div>';

		$content .= '<div class="goods-part"><label for="' . $this->meta_prefix . 'goods_product_count">' . __( 'Quantity', 'b2b-market' ) . ': </label><br><input type="number" min="0" name="' . $this->meta_prefix . 'goods_product_count" value="' . esc_textarea( $goods_product_count ) . '"></div>';

		$content .= '<div class="goods-part"><label for="' . $this->meta_prefix . 'goods_discount">' . __( 'Discount', 'b2b-market' ) . ': </label><br><input type="number" step="0.0001" min="0" name="' . $this->meta_prefix . 'goods_discount" value="' . esc_textarea( $goods_discount ) . '"></div>';

		$content .= '<div class="goods-part"><label for="' . $this->meta_prefix . 'goods_discount_type">' . __( 'Discount-Type', 'b2b-market' ) . ': </label><br><select class="space-right" name="' . $this->meta_prefix . 'goods_discount_type" id="' . $this->meta_prefix . 'goods_discount_type">';

		if ( isset( $discount_types ) ) {
			foreach ( $discount_types as $key => $value ) {
				if ( $value == $goods_discount_type ) {
					$content .= '<option selected value="' . esc_attr( $value ) . '">' . $key . '</option>';
				} else {
					$content .= '<option value="' . esc_attr( $value ) . '">' . esc_textarea( $key ) . '</option>';
				}
			}
		}
		$content .= '</select></div>';
		$content .= '</div>';

		echo $content;

		$cart_discounts = get_post_meta( $group_id, 'bm_cart_discounts', false );

		// set options for price type.
		$options = array(
			esc_html__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			esc_html__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		?>
		<div class="discount-box"><b><?php echo __( 'Cart Discount', 'b2b-market' ); ?></b><p><?php echo __( 'Set cart discount(s) for this customer group if their cart reached a certain amount.', 'b2b-market' ); ?></p>
		   <div class="bm-cartdiscounts-inner">
			   <?php
				$counter = 0;

				if ( ! empty( $cart_discounts ) && is_array( $cart_discounts ) && count( $cart_discounts ) > 0 ) {
					foreach ( $cart_discounts as $cart_discount ) {
						if ( is_array( $cart_discount ) ) {
							foreach ( $cart_discount as $discount ) {

								// find selected type.
								if ( isset( $discount[ 'cart_discount_type' ] ) ) {
									$selected = $discount[ 'cart_discount_type' ];
								} else {
									$selected = 'fix';
								}

								if ( isset( $discount[ 'cart_discount' ] ) || isset( $discount[ 'cart_discount_from' ] ) ) {
									?>
									<div class="bm-discount-row">
										<span>
											<label for="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount]"><?php esc_html_e( 'Cart Discount', 'b2b-market' ); ?></label>
											<input type="number" step="0.0001" min="0" name="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount]" value="<?php echo esc_html( $discount[ 'cart_discount' ] ); ?>" />
										</span>
										<span>
											<label for="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount_from]"><?php _e( 'Cart Total Amount (from)', 'b2b-market' ); ?></label>
											<input type="number" step="1" min="0" name="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount_from]" value="<?php echo esc_html( $discount['cart_discount_from'] ); ?>" />
										</span>
										<span>
											<label for="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount_type]"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label>
											<select id="cart_discount_type" name="cart_discount[<?php echo esc_attr( $counter ); ?>][cart_discount_type]" class="cart_discount_type">
												<?php
												if ( is_array( $options ) ) {
													foreach ( $options as $label => $value ) {
														?><option value="<?php echo esc_attr( $value ); ?>"<?php echo selected( $selected, $value ); ?>><?php echo esc_html( $label ); ?></option><?php
													}
												}
												?>
											</select>
										</span>
										<span>
											<span class="button remove"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
										</span>
									</div>
									<?php

									$counter++;
								}
							}
						}
					}
				}
				?>
			   <div class="new-cart-discount">
				   <span id="here-cart-discount"></span>
				   <span class="button add-discount"><?php esc_html_e( 'Add', 'b2b-market' ); ?></span>
			   </div>
			   <script>
				   jQuery(document).ready(function ($) {
					   var count = <?php echo esc_html( $counter ); ?>;
					   var cart_discount_label          = '<?php esc_html_e( "Cart Discount", "b2b-market" ); ?>';
					   var cart_discount_from_label     = '<?php esc_html_e( "Cart Total Amount (from)", "b2b-market" ); ?>';
					   var cart_discount_type_label     = '<?php esc_html_e( "Price Type", "b2b-market" ); ?>';

					   var cart_discount_type_discount_label         = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
					   var cart_discount_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
					   var cart_discount_remove_label                = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

					   $( '.add-discount' ).on( 'click', function() {
						   count += 1;
						   var content = '<div class="bm-discount-row"><span><label for="cart_discount[' + count + '][cart_discount]">' + cart_discount_label + '</label><input type="number" step="0.0001" min="0" name="cart_discount[' + count + '][cart_discount]" value="" /></span>' +
							   '<span><label for="cart_discount[' + count + '][cart_discount_from]">' + cart_discount_from_label + '</label><input type="number" step="1" min="0" name="cart_discount[' + count + '][cart_discount_from]" value="" /></span>' +
							   '<span>' +
							   '   <label for="cart_discount[' + count + '][cart_discount_type]">' + cart_discount_type_label + '</label>' +
							   '   <select id="cart_discount_type" name="cart_discount[' + count + '][cart_discount_type]" class="cart_discount_type">' +
							   '       <option value="discount">' + cart_discount_type_discount_label + '</option>' +
							   '       <option value="discount-percent">' + cart_discount_type_discount_percent_label + '</option>' +
							   '   </select>' +
							   '</span>' +
							   '</span>' +
							   '<span><span class="button remove">' + cart_discount_remove_label + '</span></span>' +
							   '</div>';

						   $( '#here-cart-discount' ).append( content );
						   return false;
					   });
					   $( document ).click('.remove',function(e){
						   if ( $(e.target).hasClass('remove') ) {
							   $(e.target).parent().parent().remove();
						   }
					   });
				   });
			   </script>
		   </div>
		</div>
		<?php
	}

/**
	 * Save meta.
	 *
	 * @param int $group_id current group id.
	 * @return void
	 */
	public function save( $group_id ) {

		// Check nonce.
		if ( ! isset( $_POST['submit'] ) ) {
			return;
		}

		// Check if new group.
		if ( isset( $_GET['group_id'] ) && 'new' == $_GET['group_id'] ) {
			$args = array(
				'post_title'   => sanitize_text_field( $_POST['customer_group_title'] ),
				'post_name'    => sanitize_text_field( $_POST['customer_group_title'] ),
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'customer_groups',
			);

			$group_id = wp_insert_post( $args );

		} else {
			// Or update existing group.
			if ( isset( $_POST['customer_group_title'] ) ) {

				$group_object = get_post( $group_id );
				$role         = $group_object->post_name;

				$user_args = array(
					'role__in' => array( $role ),
					'fields'   => 'ids',
				);

				$old_users = get_users( $user_args );

				$args = array(
					'ID'           => $group_id,
					'post_title'   => sanitize_text_field( $_POST['customer_group_title'] ),
					'post_name'    => $group_object->post_name,
					'post_content' => '',
					'post_status'  => 'publish',
				);

				wp_update_post( $args );

				// Migrate users to new group if exists.
				if ( isset( $old_users ) && ! empty( $old_users ) ) {

					$new_group = get_post( $group_id );
					$new_role  = $new_group->post_name;

					foreach ( $old_users as $user_id ) {

						$current_user = new WP_User( $user_id );

						$current_user->remove_role( $role );
						$current_user->add_role( $new_role );
					}
				}

				// Delete old role.
				if ( 'customer' !== $role ) {
					remove_role( $role );
				}
			}
		}

		// Update metadata.
		$metadata = array(
			'bm_min_order_amount'          => ( ! empty( $_POST[ 'bm_min_order_amount' ] ) ? $_POST[ 'bm_min_order_amount' ] : '' ),
			'bm_min_order_amount_message'  => ( ! empty( $_POST[ 'bm_min_order_amount_message' ] ) ? $_POST[ 'bm_min_order_amount_message' ] : '' ),
			'bm_conditional_categories'    => ( ! empty( $_POST[ 'bm_conditional_categories' ] ) ? $_POST[ 'bm_conditional_categories' ] : '' ),
			'bm_conditional_products'      => ( ! empty( $_POST[ 'bm_conditional_products' ] ) ? $_POST[ 'bm_conditional_products' ] : '' ),
			'bm_discount_categories'       => ( ! empty( $_POST[ 'bm_discount_categories' ] ) ? $_POST[ 'bm_discount_categories' ] : '' ),
			'bm_discount_products'         => ( ! empty( $_POST[ 'bm_discount_products' ] ) ? $_POST[ 'bm_discount_products' ] : '' ),
			'bm_discount_name'             => ( ! empty( $_POST[ 'bm_discount_name' ] ) ? $_POST[ 'bm_discount_name' ] : '' ),
			'bm_discount'                  => ( ! empty( $_POST[ 'bm_discount' ] ) ? $_POST[ 'bm_discount' ] : '' ),
			'bm_discount_type'             => ( ! empty( $_POST[ 'bm_discount_type' ] ) ? $_POST[ 'bm_discount_type' ] : '' ),
			'bm_goods_discount_categories' => ( ! empty( $_POST[ 'bm_goods_discount_categories' ] ) ? $_POST[ 'bm_goods_discount_categories' ] : '' ),
			'bm_goods_product_count'       => ( ! empty( $_POST[ 'bm_goods_product_count' ] ) ? $_POST[ 'bm_goods_product_count' ] : '' ),
			'bm_goods_discount'            => ( ! empty( $_POST[ 'bm_goods_discount' ] ) ? $_POST[ 'bm_goods_discount' ] : '' ),
			'bm_goods_discount_type'       => ( ! empty( $_POST[ 'bm_goods_discount_type' ] ) ? $_POST[ 'bm_goods_discount_type' ] : '' ),
		);

		if ( ! empty( $_POST['group_price'] ) ) {
			$metadata['bm_group_prices'] = $_POST['group_price'];
		} else {
			$metadata['bm_group_prices'] = '';
		}

		if ( ! empty( $_POST['bulk_price'] ) ) {
			$metadata['bm_bulk_prices'] = $_POST['bulk_price'];
		} else {
			$metadata['bm_bulk_prices'] = '';
		}

		if ( ! empty( $_POST['cart_discount'] ) ) {
			$metadata['bm_cart_discounts'] = $_POST['cart_discount'];
		} else {
			$metadata['bm_cart_discounts'] = '';
		}

		if ( ! empty( $_POST['bm_conditional_all_products'] ) ) {
			$metadata['bm_conditional_all_products'] = $_POST['bm_conditional_all_products'];
		} else {
			$metadata['bm_conditional_all_products'] = 'off';
		}

		if ( ! empty( $_POST['bm_discount_all_products'] ) ) {
			$metadata['bm_discount_all_products'] = $_POST['bm_discount_all_products'];
		} else {
			$metadata['bm_discount_all_products'] = 'off';
		}

		if ( ! empty( $_POST['bm_tax_type'] ) ) {
			$metadata['bm_tax_type'] = $_POST['bm_tax_type'];
		} else {
			$metadata['bm_tax_type'] = 'off';
		}

		if ( ! empty( $_POST[ 'bm_vat_type' ] ) ) {
			$metadata['bm_vat_type'] = $_POST['bm_vat_type'];
		} else {
			$metadata['bm_vat_type'] = 'off';
		}

		if ( ! empty( $_POST[ 'bm_show_sale_badge' ] ) ) {
			$metadata[ 'bm_show_sale_badge' ] = $_POST[ 'bm_show_sale_badge' ];
		} else {
			$metadata[ 'bm_show_sale_badge' ] = 'off';
		}

		foreach ( $metadata as $key => $value ) {
			if ( isset( $value ) && ! empty( $value ) ) {
				if ( 'bm_vat_type' === $key ) {
					update_post_meta( $group_id, $key, 'on' );
				}

				if ( 'bm_tax_type' === $key ) {
					update_post_meta( $group_id, $key, 'on' );
				}

				if ( 'bm_show_sale_badge' === $key ) {
					update_post_meta( $group_id, $key, 'on' );
				}

				if ( 'bm_discount_all_products' === $key ) {
					update_post_meta( $group_id, $key, 'on' );
				}

				// Check if goods discount type.
				if ( 'bm_goods_discount_type' === $key ) {
					$coupon = new WC_Coupon( 'category_discount' );

					if ( ! empty( $coupon->get_id() ) ) {
						wp_delete_post( $coupon->get_id() );
					}
				}
				update_post_meta( $group_id, $key, $value );
			} else {
				delete_post_meta( $group_id, $key );
			}
		}

		// Refresh cached product prices transient.
		BM_Helper::force_regenerate_woocommerce_price_hashes();

		// Add user role.
		$role = new BM_User();
		$role->add_customer_group( $group_id );

		// update options.
		$payment_shipping_addon = get_option( 'bm_addon_shipping_and_payment' );

		if ( 'on' == $payment_shipping_addon ) {
			CSP_PaymentManager::update_payment_options_for_group();
			CSP_ShippingManager::update_shipping_options_for_group();
		}
		// Indicator for saved options.
		update_option( 'bm_all_options_saved', date( 'Y-m-d-H-i' ) );

		// Refresh cached product prices transient.
		WC_Cache_Helper::get_transient_version( 'product', true );

		$query = new WC_Product_Query( array(
			'type'   => 'variable',
			'return' => 'ids',
			'limit'  => -1,
		) );
		$products = $query->get_products();

		if ( ! empty( $products ) ) {
			foreach ( $products as $product_id ) {
				delete_transient( 'wc_product_children_' . $product_id );
			}
		}

		// Safe redirect.
		wp_safe_redirect( get_admin_url() . 'admin.php?page=b2b-market&tab=groups&group_id=' . $group_id );
		exit();
	}
}

new BM_Edit_Group();
