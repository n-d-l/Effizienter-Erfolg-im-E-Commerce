<?php

/**
 * Class that handles product meta data.
 */
class BM_Product_Meta {

	/**
	 * Constructor for BM_Product_Meta
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_admin_tab' ), 99, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_admin_tab_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
	}

	/**
	 * Add tab in product edit screen
	 *
	 * @param array $product_data_tabs current tabs.
	 * @return array
	 */
	public function add_product_admin_tab( $product_data_tabs ) {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( is_null( $product ) ) {
			return;
		}
		if ( ! $product->is_type( 'grouped' ) && ! $product->is_type( 'variable' ) ) {
			$product_data_tabs['b2b-market'] = array(
				'label'  => __( 'B2B Market', 'b2b-market' ),
				'target' => 'b2b_fields',
			);
		}

		return $product_data_tabs;
	}

	/**
	 * Add fields to admin tab;
	 *
	 * @return void
	 */
	public function add_product_admin_tab_fields() {
		global $post;

		$product   = wc_get_product( $post->ID );
		$qty_addon = get_option( 'bm_addon_quantities', 'off' );

		wp_nonce_field( basename( __FILE__ ), 'bm_product_nonce' );

		if ( is_null( $product ) ) {
			return;
		}
		?>
		<?php if ( ! $product->is_type( 'grouped' ) ) : ?>
			<div id="b2b_fields" class="panel woocommerce_options_panel">
				<p><?php $this->get_rrp_meta( $post->ID ); ?></p>
				<p><?php $this->get_group_price_meta( $post->ID ); ?></p>
				<p><?php $this->get_bulk_price_meta( $post->ID ); ?></p>
				<?php if ( 'on' === $qty_addon ) : ?>
					<p><?php $this->get_quantities_meta( $post->ID ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Add rrp meta field.
	 *
	 * @param  int $product_id given post id.
	 * @return void
	 */
	protected function get_rrp_meta( $product_id ) {

		$product = wc_get_product( $product_id );
		$rrp     = get_post_meta( $product_id, 'bm_rrp', true );

		?>
		<h1><?php esc_html_e( 'RRP', 'b2b-market' ); ?></h1>
		<div class="group-price-container">
			<label for="bm_rrp"><?php esc_html_e( 'rrp:', 'b2b-market' ); ?></label>
			<input class="space-right" type="number" step="0.0001" min="0" name="bm_rrp" value="<?php echo ( ! is_array( $rrp ) ? esc_attr( $rrp ) : '' ); ?>" id="bm_rrp"><br>
		</div>
		<?php
	}

	/**
	 * Get group price meta fields.
	 *
	 * @param  int $product_id given post id.
	 * @return void
	 */
	protected function get_group_price_meta( $product_id ) {

		$product        = wc_get_product( $product_id );
		$current_groups = BM_User::get_all_customer_group_ids();
		$copy_groups    = array();

		if ( empty( $current_groups ) ) {
			return;
		}

		$repeatable_suffix = '';

		if ( 'variation' === $product->get_type() ) {
			$repeatable_suffix = '-' . $product->get_id();
		}

		$options = array(
			__( 'Fix Price', 'b2b-market' )              => 'fix',
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		/* rebuild array for copy function */
		foreach ( $current_groups as $group_id ) {
			$group                            = get_post( $group_id );
			$copy_groups[ $group->post_name ] = $group->post_title;
		}
		?>
		<h1><?php esc_html_e( 'Group Prices', 'b2b-market' ); ?></h1>
		<div class="group-price-container">
		<?php
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

			$copy         = get_post_meta( $product_id, 'bm_' . $group_slug . 'copy_for_group', true );
			$group_prices = get_post_meta( $product_id, 'bm_' . $group_slug . 'group_prices', false );

			$copy_select = '';

			if ( ! empty( $copy_groups ) ) {
				$copy_select  = '<select name="bm_' . $group_slug . 'copy_for_group">';
				$copy_select .= '<option value="copy">' . __( 'Copy from', 'b2b-market' ) . '</option>';

				foreach ( $copy_groups as $name => $title ) {
					if ( $copy === $name ) {
						$copy_select .= '<option selected value="' . $name . '">' . $title . '</option>';
					} elseif ( $group_object->post_name !== $name ) {
						$copy_select .= '<option value="' . $name . '">' . $title . '</option>';
					}
				}
				$copy_select .= '</select>';
			}
			?>
			<article class="beefup" id="group-price">
			<h2 class="beefup__head">
				<?php esc_html_e( 'Group Prices for', 'b2b-market' ); ?>
				<b><?php echo esc_html( get_the_title( $group_id ) ); ?></b>
				<span class="bm-copy"><?php echo $copy_select; ?></span>
			</h2>
			<div class="beefup__body">
				<div class="bm-groupprices-inner">
				<?php
				$counter = 0;

				if ( ! empty( $group_prices ) && is_array( $group_prices ) ) {
					foreach ( $group_prices as $group_price ) {
						if ( isset( $group_price ) && ! empty( $group_price ) ) {
							foreach ( $group_price as $price ) {

								if ( isset( $price['group_price_type'] ) ) {
									$selected = $price['group_price_type'];
								} else {
									$selected = 'fix';
								}
								?>

								<?php if ( ! empty( $price['group_price'] ) ) : ?>
									<p>
										<label for="<?php echo esc_attr( $group_slug ); ?>group_price[<?php echo esc_attr( $counter ); ?>][group_price]"><?php esc_html_e( 'Group Price', 'b2b-market' ); ?></label>
										<input type="number" step="0.0001" min="0" name="<?php echo esc_attr( $group_slug ); ?>group_price[<?php echo esc_attr( $counter ); ?>][group_price]" value="<?php echo esc_attr( $price['group_price'] ); ?>" />

										<label for="<?php echo esc_attr( $group_slug ); ?>group_price[<?php echo esc_attr( $counter ); ?>][price_type]"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label>
										<select id="group_price_type" name="<?php echo esc_attr( $group_slug ); ?>group_price[<?php echo esc_attr( $counter ); ?>][group_price_type]" class="price_type">
										<?php if ( isset( $options ) ) : ?>
											<?php foreach ( $options as $label => $value ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
										</select>

										<span class="button b2b-remove"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
									</p>
									<?php $counter++; ?>
								<?php endif; ?>
								<?php
							}
						}
					}
				}
				?>
				<div class="new-group-price">
					<span id="here-group-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>"></span>
					<span class="button add-group-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?> additional-row"><?php esc_html_e( 'Add', 'b2b-market' ); ?></span>
				</div>
				<script>
					var $group_prices =jQuery.noConflict();
					$group_prices(document).ready(function() {
						var count      = <?php echo esc_attr( $counter ); ?>;
						var group_slug = '<?php echo esc_attr( $group_slug ); ?>';
						var group_class = 'group-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>';

						var group_price_label = '<?php esc_html_e( "Group Price", "b2b-market" ); ?>';
						var group_price_type_label = '<?php esc_html_e( "Price-Type", "b2b-market" ); ?>';

						var group_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
						var group_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
						var group_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
						var group_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

						$group_prices('.add-' + group_class).click(function() {
							count = count + 1;
							var content = '<p><label for="' + group_slug + 'group_price[' + count + '][group_price]">' + group_price_label + '</label><input type="number" step="0.0001" min="0" name="' + group_slug + 'group_price[' + count + '][group_price]" value="" />' +
							'<label for="' + group_slug + 'group_price[' + count + '][group_price_type]">' + group_price_type_label + '</label>' +
							'<select id="group_price_type" name="' + group_slug + 'group_price[' + count + '][group_price_type]" class="group_price_type">' +
							'<option value="fix">' + group_price_type_fix_label + '</option>' +
							'<option value="discount">' + group_price_type_discount_label + '</option>' +
							'<option value="discount-percent">' + group_price_type_discount_percent_label + '</option>' +
							'</select>' +
							'<span class="button remove">' + group_price_remove_label + '</span>' + 
							'</p>';

							$group_prices('#here-' + group_class ).append(content);

							return false;
						});
						$group_prices(document).on('click', 'span.b2b-remove', function(){
							$group_prices(this).parent().remove();
							$group_prices('.variable_stock_status select').change();
						});
					});
					</script>
				</div>
			</article>
			<?php
		}
		?>
		</div>
		<?php
	}

	/**
	 * Add bulk price meta fields.
	 *
	 * @param  int $product_id given post id.
	 * @return void
	 */
	protected function get_bulk_price_meta( $product_id ) {
		$product        = wc_get_product( $product_id );
		$current_groups = BM_User::get_all_customer_group_ids();
		$copy_groups    = array();

		if ( empty( $current_groups ) ) {
			return;
		}

		$repeatable_suffix = '';

		if ( 'variation' === $product->get_type() ) {
			$repeatable_suffix = '-' . $product->get_id();
		}

		$options = array(
			__( 'Fix Price', 'b2b-market' )              => 'fix',
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		/* rebuild array for copy function */
		foreach ( $current_groups as $group_id ) {
			$group                            = get_post( $group_id );
			$copy_groups[ $group->post_name ] = $group->post_title;
		}
		?>
		<h1><?php esc_html_e( 'Bulk Prices', 'b2b-market' ); ?></h1>
		<div class="bulk-price-container">
		<?php
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

			$copy_bulk   = get_post_meta( $product_id, 'bm_' . $group_slug . 'bulk_copy_for_group', true );
			$bulk_prices = get_post_meta( $product_id, 'bm_' . $group_slug . 'bulk_prices', false );

			/* copy function */
			$copy_select = '';

			if ( isset( $copy_groups ) && ! empty( $copy_groups ) ) {
				$copy_select  = '<select name="bm_' . $group_slug . 'bulk_copy_for_group">';
				$copy_select .= '<option value="copy">' . __( 'Copy from', 'b2b-market' ) . '</option>';

				foreach ( $copy_groups as $name => $title ) {
					if ( $copy_bulk === $name ) {
						$copy_select .= '<option selected value="' . $name . '">' . $title . '</option>';
					} elseif ( $group_object->post_name !== $name ) {
						$copy_select .= '<option value="' . $name . '">' . $title . '</option>';
					}
				}
				$copy_select .= '</select>';
			}
			?>
			<article class="beefup" id="bulk-price">
			<h2 class="beefup__head">
				<?php esc_html_e( 'Bulk prices for', 'b2b-market' ); ?>
				<b><?php echo esc_html( get_the_title( $group_id ) ); ?></b>
				<span class="bm-copy"><?php echo $copy_select; ?></span>
			</h2>
			<div class="beefup__body">
				<div class="bm-bulkprices-inner">
					<p><?php esc_html_e( 'Bulk prices are applied if the current quantity fits in the configured quantity range ', 'b2b-market' ); ?></p>
					<?php
					$counter = 0;

					if ( ! empty( $bulk_prices ) && is_array( $bulk_prices ) ) {
						if ( isset( $bulk_prices ) && ! empty( $bulk_prices ) ) {
							foreach ( $bulk_prices as $bulk_price ) {
								foreach ( $bulk_price as $price ) {

									if ( isset( $price['bulk_price_type'] ) ) {
										$selected = $price['bulk_price_type'];
									} else {
										$selected = 'fix';
									}
									?>

									<?php if ( isset( $price['bulk_price'] ) || isset( $price['bulk_price_from'] ) || isset( $price['bulk_price_to'] ) ) : ?>
										<p>
											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price]"><?php esc_html_e( 'Bulk Price', 'b2b-market' ); ?></label>
											<input type="number" step="0.0001" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price]" value="<?php echo esc_attr( $price['bulk_price'] ); ?>" />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_from]"><?php esc_html_e( 'Amount (from)', 'b2b-market' ); ?></label>
											<input type="number" step="1" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_from]" value="<?php echo esc_attr( $price['bulk_price_from'] ); ?>" />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_to]"><?php esc_html_e( 'Amount (to)', 'b2b-market' ); ?></label>
											<input type="number" step="1" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_to]" value="<?php if ( esc_attr( $price['bulk_price_to'] ) != 0 ) { echo esc_attr( $price['bulk_price_to'] ); } ?>" <?php if ( isset( $price['bulk_price_to'] ) && esc_attr( $price[ 'bulk_price_to'] ) == 0 ) { echo 'placeholder="∞"'; } ?> />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_type]"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label>
											<select id="bulk_price_type" name="<?php echo esc_attr( $group_slug ); ?>bulk_price[<?php echo esc_attr( $counter ); ?>][bulk_price_type]" class="bulk_price_type">
											<?php if ( isset( $options ) ) : ?>
												<?php foreach ( $options as $label => $value ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
												<?php endforeach; ?>
											<?php endif; ?>
											</select>
											<span class="button b2b-remove"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
										</p>
										<?php $counter++; ?>
									<?php endif; ?>
									<?php
								}
							}
						}
					}
					?>
				<div class="new-bulk">
					<span id="here-bulk-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>"></span>
					<span class="button add-bulk-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?> additional-row"><?php esc_html_e( 'Add', 'b2b-market' ); ?></span>
				</div>
				<script>
					var $bulk_prices =jQuery.noConflict();
					$bulk_prices(document).ready(function() {
						var count      = <?php echo esc_attr( $counter ); ?>;
						var group_slug = '<?php echo esc_attr( $group_slug ); ?>';
						var group_class = 'bulk-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>';

						var bulk_price_label = '<?php esc_html_e( "Bulk Price", "b2b-market" ); ?>';
						var bulk_price_from_label = '<?php esc_html_e( "Amount (from)", "b2b-market" ); ?>';
						var bulk_price_to_label = '<?php esc_html_e( "Amount (to)", "b2b-market" ); ?>';
						var bulk_price_type_label = '<?php esc_html_e( "Price-Type", "b2b-market" ); ?>';

						var bulk_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
						var bulk_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
						var bulk_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
						var bulk_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

						$bulk_prices('.add-' + group_class).click(function() {
							count = count + 1;
							var content = '<p><label for="' + group_slug + 'bulk_price[' + count + '][bulk_price]">' + bulk_price_label + '</label><input type="number" step="0.0001" min="0" name="' + group_slug + 'bulk_price[' + count + '][bulk_price]" value="" />' +
							'<label for="' + group_slug + 'bulk_price[' + count + '][bulk_price_from]">' + bulk_price_from_label + '</label><input type="number" step="1" min="0" name="' + group_slug + 'bulk_price[' + count + '][bulk_price_from]" value="" />' +
							'<label for="' + group_slug + 'bulk_price[' + count + '][bulk_price_to]">' + bulk_price_to_label + '</label><input type="number" step="1" min="0" name="' + group_slug + 'bulk_price[' + count + '][bulk_price_to]" value="" />' +
							'<label for="' + group_slug + 'bulk_price[' + count + '][bulk_price_type]">' + bulk_price_type_label + '</label>' +
							'<select id="bulk_price_type" name="' + group_slug + 'bulk_price[' + count + '][bulk_price_type]" class="bulk_price_type">' +
							'<option value="fix">' + bulk_price_type_fix_label + '</option>' +
							'<option value="discount">' + bulk_price_type_discount_label + '</option>' +
							'<option value="discount-percent">' + bulk_price_type_discount_percent_label + '</option>' +
							'</select>' +
							'<span class="button remove">' + bulk_price_remove_label + '</span>' + 
							'</p>';

							$bulk_prices('#here-' + group_class ).append(content);

							return false;
						});
						$bulk_prices(document).on('click', 'span.b2b-remove', function(){
							$bulk_prices(this).parent().remove();
							$bulk_prices('.variable_stock_status select').change();
						});
					});
					</script>
				</div>
			</article>
			<?php
		}
		?>
		</div>
		<?php
	}

	/**
	 * Add quantity meta fields
	 *
	 * @param  int $product_id given post id.
	 * @return void
	 */
	protected function get_quantities_meta( $product_id ) {
		$product        = wc_get_product( $product_id );
		$current_groups = BM_User::get_all_customer_group_ids();
		$copy_groups    = array();

		if ( empty( $current_groups ) ) {
			return;
		}

		$options = array(
			__( 'Fix Price', 'b2b-market' )              => 'fix',
			__( 'Discount (fixed Value)', 'b2b-market' ) => 'discount',
			__( 'Discount (%)', 'b2b-market' )           => 'discount-percent',
		);

		/* rebuild array for copy function */
		foreach ( $current_groups as $group_id ) {
			$group                            = get_post( $group_id );
			$copy_groups[ $group->post_name ] = $group->post_title;
		}
		?>
		<h1><?php esc_html_e( 'Quantities', 'b2b-market' ); ?></h1>
		<?php if ( 'variable' == $product->get_type() ) : ?>
			<p style="padding-left:15px"><?php esc_html_e( 'You can find the pricing fields for B2B Market directly within each variation.', 'b2b-market' ); ?></p>
		<?php endif; ?>
		<div class="group-quantity-container">
		<?php
		foreach ( $current_groups as $group ) {

			$group_object = get_post( $group );
			$group_slug   = $group_object->post_name . '_';

			$copy_qty      = get_post_meta( $product_id, 'bm_' . $group_slug . 'quantity_copy_for_group', true );
			$min_quantity  = get_post_meta( $product_id, 'bm_' . $group_slug . 'min_quantity', true );
			$max_quantity  = get_post_meta( $product_id, 'bm_' . $group_slug . 'max_quantity', true );
			$step_quantity = get_post_meta( $product_id, 'bm_' . $group_slug . 'step_quantity', true );

			$copy_select = '';

			if ( isset( $copy_groups ) && ! empty( $copy_groups ) ) {
				$copy_select  = '<select name="bm_' . $group_slug . 'quantity_copy_for_group">';
				$copy_select .= '<option value="copy">' . __( 'Copy from', 'b2b-market' ) . '</option>';

				foreach ( $copy_groups as $name => $title ) {
					if ( $copy_qty === $name ) {
						$copy_select .= '<option selected value="' . $name . '">' . $title . '</option>';
					} elseif ( $group_object->post_name !== $name ) {
						$copy_select .= '<option value="' . $name . '">' . $title . '</option>';
					}
				}

				$copy_select .= '</select>';
			}
			?>
			<article class="beefup" id="group-quantity">
				<h2 class="beefup__head"><?php esc_html_e( 'Quantities', 'b2b-market' ); ?>
					<b><?php echo esc_html( get_the_title( $group ) ); ?></b>
					<span class="bm-copy"><?php echo $copy_select; ?></span>
				</h2>
				<div class="beefup__body">
					<label for="bm_<?php echo esc_attr( $group_slug ); ?>min_quantity"><?php esc_html_e( 'Min:', 'b2b-market' ); ?></label>
					<input class="space-right" type="number" min="0" name="bm_<?php echo esc_attr( $group_slug ); ?>min_quantity" value="<?php echo esc_attr( $min_quantity ); ?>" id="bm_<?php echo esc_attr( $group_slug ); ?>min_quantity">

					<label for="bm_<?php echo esc_attr( $group_slug ); ?>max_quantity"><?php esc_html_e( 'Max:', 'b2b-market' ); ?></label>
					<input class="space-right" type="number" min="0" name="bm_<?php echo esc_attr( $group_slug ); ?>max_quantity" value="<?php echo esc_attr( $max_quantity ); ?>" id="bm_<?php echo esc_attr( $group_slug ); ?>max_quantity">

					<label for="bm_<?php echo esc_attr( $group_slug ); ?>'step_quantity"><?php esc_html_e( 'Step:', 'b2b-market' ); ?></label>
					<input class="space-right" type="number" min="0" name="bm_<?php echo esc_attr( $group_slug ); ?>step_quantity" value="<?php echo esc_attr( $step_quantity ); ?>" id="bm_<?php echo esc_attr( $group_slug ); ?>step_quantity">
				</div>
			</article>
			<?php
		}
		?>
		</div>
		<?php
	}

	/**
	 * Save meta fields
	 *
	 * @param int    $product_id current post id.
	 * @param object $post current post object.
	 * @return void
	 */
	public function save_meta( $post_id ) {

		if ( ! empty( $_POST['bm_rrp'] ) ) {
			update_post_meta( $post_id, 'bm_rrp', $_POST['bm_rrp'] );
		} else {
			delete_post_meta( $post_id, 'bm_rrp' );
		}

		$current_groups = BM_User::get_all_customer_group_ids();
		$product        = wc_get_product( $post_id );

		/* group prices */
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

			if ( ! empty( $_POST[ $group_slug . 'group_price' ] ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'group_prices', $_POST[ $group_slug . 'group_price' ] );
			} else {
				delete_post_meta( $post_id, 'bm_' . $group_slug . 'group_prices' );
			}

			if ( ! empty( $_POST[ $group_slug . 'bulk_price' ] ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'bulk_prices', $_POST[ $group_slug . 'bulk_price' ] );
			} else {
				delete_post_meta( $post_id, 'bm_' . $group_slug . 'bulk_prices' );
			}

			if ( ! empty( $_POST[ 'bm_' . $group_slug . 'min_quantity' ] ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'min_quantity', $_POST[ 'bm_' . $group_slug . 'min_quantity' ] );
			} else {
				delete_post_meta( $post_id, 'bm_' . $group_slug . 'min_quantity' );
			}

			if ( ! empty( $_POST[ 'bm_' . $group_slug . 'max_quantity' ] ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'max_quantity', $_POST[ 'bm_' . $group_slug . 'max_quantity' ] );
			} else {
				delete_post_meta( $post_id, 'bm_' . $group_slug . 'max_quantity' );
			}

			if ( ! empty( $_POST[ 'bm_' . $group_slug . 'step_quantity' ] ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'step_quantity', $_POST[ 'bm_' . $group_slug . 'step_quantity' ] );
			} else {
				delete_post_meta( $post_id, 'bm_' . $group_slug . 'step_quantity' );
			}

			/* save copy options */
			$group_copy = isset( $_POST[ 'bm_' . $group_slug . 'copy_for_group' ] ) ? $_POST[ 'bm_' . $group_slug . 'copy_for_group' ] : false;

			if ( ! empty( $group_copy ) && ! is_array( $group_copy )  ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'copy_for_group', $group_copy );

				if ( 'copy' !== $group_copy && isset( $_POST[ $group_copy . '_group_price' ] ) ) {
					update_post_meta( $post_id, 'bm_' . $group_slug . 'group_prices', $_POST[ $group_copy . '_group_price' ] );
				}
			}

			$bulk_copy = isset( $_POST[ 'bm_' . $group_slug . 'bulk_copy_for_group' ] ) ? $_POST[ 'bm_' . $group_slug . 'bulk_copy_for_group' ] : false;

			if ( ! empty( $bulk_copy ) && ! is_array( $bulk_copy ) ) {
				update_post_meta( $post_id, 'bm_' . $group_slug . 'bulk_copy_for_group', $bulk_copy );

				if ( 'copy' !== $bulk_copy && isset( $_POST[ $bulk_copy . '_bulk_price' ] ) ) {
					update_post_meta( $post_id, 'bm_' . $group_slug . 'bulk_prices', $_POST[ $bulk_copy . '_bulk_price' ] );
				}
			}

			if ( ! empty( $_POST[ 'bm_' . $group_slug . 'quantity_copy_for_group' ]) ) {
				$copy_qty = $_POST[ 'bm_' . $group_slug . 'quantity_copy_for_group' ];

				if ( 'copy' !== $copy_qty ) {
					if ( ! empty( $_POST[ 'bm_' . $copy_qty . '_min_quantity' ] ) ) {
						update_post_meta( $post_id, 'bm_' . $group_slug . 'min_quantity', $_POST[ 'bm_' . $copy_qty . '_min_quantity' ] );
					}

					if ( ! empty( $_POST[ 'bm_' . $copy_qty . '_max_quantity' ] ) ) {
						update_post_meta( $post_id, 'bm_' . $group_slug . 'max_quantity', $_POST[ 'bm_' . $copy_qty . '_max_quantity' ] );
					}

					if ( ! empty( $_POST[ 'bm_' . $copy_qty . '_step_quantity' ] ) ) {
						update_post_meta( $post_id, 'bm_' . $group_slug . 'step_quantity', $_POST[ 'bm_' . $copy_qty . '_step_quantity' ] );
					}
				}
			}
		}
	}
}

new BM_Product_Meta();
