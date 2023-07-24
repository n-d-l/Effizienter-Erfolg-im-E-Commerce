<?php

/**
 * Class that handles product meta data.
 */
class BM_Variation_Meta {

	/**
	 * Constructor for BM_Variation_Meta
	 */
	public function __construct() {
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_qty_metabox' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_qty_metabox' ), 10, 2 );
	}

	/**
	 * Add fields to variation.
	 *
	 * @param array  $loop current lopp.
	 * @param array  $variation_data varation data.
	 * @param object $variation current variation.
	 * @return void
	 */
	public function add_variation_fields( $loop, $variation_data, $variation ) {
		?>
		<div id="b2b_fields">
			<p><?php $this->get_rrp_meta( $variation->ID ); ?></p>
			<p><?php $this->get_group_price_meta( $variation->ID ); ?></p>
			<p><?php $this->get_bulk_price_meta( $variation->ID ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add rrp meta field.
	 *
	 * @param  int $variation_id given post id.
	 * @return void
	 */
	protected function get_rrp_meta( $variation_id ) {
		$rrp = get_post_meta( $variation_id, 'bm_rrp', true );
		?>
		<h1><?php esc_html_e( 'RRP', 'b2b-market' ); ?></h1>
		<div class="group-price-container">
			<label for="bm_rrp[<?php echo esc_attr( $variation_id ); ?>]"><?php esc_html_e( 'rrp:', 'b2b-market' ); ?></label>
			<input class="space-right" type="number" step="0.0001" min="0" name="bm_rrp[<?php echo esc_attr( $variation_id ); ?>]" value="<?php echo esc_attr( $rrp ); ?>" id="bm_rrp"><br>
		</div>
		<?php
	}

	/**
	 * Get group price meta fields.
	 *
	 * @param  int $variation_id given post id.
	 * @return void
	 */
	protected function get_group_price_meta( $variation_id ) {
		$product        = wc_get_product( $variation_id );
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

			$copy         = get_post_meta( $variation_id, 'bm_' . $group_slug . 'copy_for_group', true );
			$group_prices = get_post_meta( $variation_id, 'bm_' . $group_slug . 'group_prices', false );

			$copy_select = '';

			/* CSS identifier */
			$container_id = "bm-group-prices-" . $group_id . "-" . $variation_id;

			if ( ! empty( $copy_groups ) ) {
				$copy_select  = '<select name="bm_' . $group_slug . 'copy_for_group[' . $variation_id . ']">';
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
				<div id="<?php echo $container_id; ?>" class="bm-groupprices-inner">
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
										<label for="<?php echo esc_attr( $group_slug ); ?>group_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][group_price]"><?php esc_html_e( 'Price', 'b2b-market' ); ?></label>
										<input type="number" step="0.0001" min="0" name="<?php echo esc_attr( $group_slug ); ?>group_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][group_price]" value="<?php echo esc_attr( $price['group_price'] ); ?>" />

										<label for="<?php echo esc_attr( $group_slug ); ?>group_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][price_type]"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label>
										<select id="group_price_type" name="<?php echo esc_attr( $group_slug ); ?>group_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][group_price_type]" class="price_type">
										<?php if ( isset( $options ) ) : ?>
											<?php foreach ( $options as $label => $value ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
										</select>

										<span class="button remove" id="group-remove-<?php echo esc_attr( $counter ); ?>"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
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
						var count        = <?php echo esc_attr( $counter ); ?>;
                        var variation_id = <?php echo esc_attr( $variation_id ); ?>;
						var group_slug   = '<?php echo esc_attr( $group_slug ); ?>';
						var group_class  = 'group-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>';

						var group_price_label = '<?php esc_html_e( "Group Price", "b2b-market" ); ?>';
						var group_price_type_label = '<?php esc_html_e( "Price-Type", "b2b-market" ); ?>';

						var group_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
						var group_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
						var group_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
						var group_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

						$group_prices( '#<?php echo $container_id; ?> .additional-row' ).click(function() {
							count = count + 1;
							var content = '<p><label for="' + group_slug + 'group_price_' + variation_id + '[' + count + '][group_price]">' + group_price_label + '</label><input type="number" step="0.0001" min="0" name="' + group_slug + 'group_price_' + variation_id + '[' + count + '][group_price]" value="" />' +
							'<label for="' + group_slug + 'group_price_' + variation_id + '[' + count + '][group_price_type]">' + group_price_type_label + '</label>' +
							'<select id="group_price_type" name="' + group_slug + 'group_price_' + variation_id + '[' + count + '][group_price_type]" class="group_price_type">' +
							'<option value="fix">' + group_price_type_fix_label + '</option>' +
							'<option value="discount">' + group_price_type_discount_label + '</option>' +
							'<option value="discount-percent">' + group_price_type_discount_percent_label + '</option>' +
							'</select>' +
							'<span class="button remove" id="group-remove-' + count + '">' + group_price_remove_label + '</span>' + 
							'</p>';

							$group_prices( '#<?php echo $container_id; ?> #here-' + group_class ).append(content);

							return false;
						});
						$group_prices(document).on('click', '#group-remove-' + count, function(){
							$group_prices(this).remove();
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
	 * @param  int $variation_id given post id.
	 * @return void
	 */
	protected function get_bulk_price_meta( $variation_id ) {
		$product        = wc_get_product( $variation_id );
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

			$copy_bulk   = get_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_copy_for_group', true );
			$bulk_prices = get_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_prices', false );

			/* copy function */
			$copy_select = '';

			/* CSS identifier */
			$container_id = "bm-bulkprices-" . $group_id . "-" . $variation_id;

			if ( isset( $copy_groups ) && ! empty( $copy_groups ) ) {
				$copy_select  = '<select name="bm_' . $group_slug . 'bulk_copy_for_group[' . $variation_id . ']">';
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
				<div id="<?php echo $container_id; ?>" class="bm-bulkprices-inner">
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
											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price]"><?php esc_html_e( 'Bulk Price', 'b2b-market' ); ?></label>
											<input type="number" step="0.0001" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price]" value="<?php echo esc_attr( $price['bulk_price'] ); ?>" />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_from]"><?php esc_html_e( 'Amount (from)', 'b2b-market' ); ?></label>
											<input type="number" step="1" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_from]" value="<?php echo esc_attr( $price['bulk_price_from'] ); ?>" />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_to]"><?php esc_html_e( 'Amount (to)', 'b2b-market' ); ?></label>
											<input type="number" step="1" min="0" name="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_to]" value="<?php if ( esc_attr( $price['bulk_price_to'] ) != 0 ) { echo esc_attr( $price['bulk_price_to'] ); } ?>" <?php if ( isset( $price['bulk_price_to'] ) && esc_attr( $price[ 'bulk_price_to'] ) == 0 ) { echo 'placeholder="∞"'; } ?> />

											<label for="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_type]"><?php esc_html_e( 'Price-Type', 'b2b-market' ); ?></label>
											<select id="bulk_price_type" name="<?php echo esc_attr( $group_slug ); ?>bulk_price_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $counter ); ?>][bulk_price_type]" class="bulk_price_type">
											<?php if ( isset( $options ) ) : ?>
												<?php foreach ( $options as $label => $value ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selected, $value ); ?>><?php echo $label; ?></option>
												<?php endforeach; ?>
											<?php endif; ?>
											</select>
											<span class="button remove" id="bulk-remove-<?php echo esc_attr( $counter ); ?>"><?php esc_html_e( 'Remove', 'b2b-market' ); ?></span>
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
						var count        = <?php echo esc_attr( $counter ); ?>;
						var group_slug   = '<?php echo esc_attr( $group_slug ); ?>';
						var group_class  = 'bulk-<?php echo esc_attr( $group_object->post_name ); ?><?php echo esc_html( $repeatable_suffix ); ?>';
                        var variation_id = <?php echo esc_attr( $variation_id ); ?>;
						var bulk_price_label = '<?php esc_html_e( "Bulk Price", "b2b-market" ); ?>';
						var bulk_price_from_label = '<?php esc_html_e( "Amount (from)", "b2b-market" ); ?>';
						var bulk_price_to_label = '<?php esc_html_e( "Amount (to)", "b2b-market" ); ?>';
						var bulk_price_type_label = '<?php esc_html_e( "Price-Type", "b2b-market" ); ?>';

						var bulk_price_type_fix_label = '<?php esc_html_e( "Fix Price", "b2b-market" ); ?>';
						var bulk_price_type_discount_label = '<?php esc_html_e( "Discount (fixed Value)", "b2b-market" ); ?>';
						var bulk_price_type_discount_percent_label = '<?php esc_html_e( "Discount (%)", "b2b-market" ); ?>';
						var bulk_price_remove_label = '<?php esc_html_e( "Remove", "b2b-market" ); ?>';

						$group_prices( '#<?php echo $container_id; ?> .additional-row' ).click(function() {
							count = count + 1;
							var content = '<p><label for="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price]">' + bulk_price_label + '</label><input type="number" step="0.0001" min="0" name="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price]" value="" />' +
							'<label for="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_from]">' + bulk_price_from_label + '</label><input type="number" step="1" min="0" name="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_from]" value="" />' +
							'<label for="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_to]">' + bulk_price_to_label + '</label><input type="number" step="1" min="0" name="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_to]" value="" />' +
							'<label for="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_type]">' + bulk_price_type_label + '</label>' +
							'<select id="bulk_price_type" name="' + group_slug + 'bulk_price_' + variation_id + '[' + count + '][bulk_price_type]" class="bulk_price_type">' +
							'<option value="fix">' + bulk_price_type_fix_label + '</option>' +
							'<option value="discount">' + bulk_price_type_discount_label + '</option>' +
							'<option value="discount-percent">' + bulk_price_type_discount_percent_label + '</option>' +
							'</select>' +
							'<span class="button remove" id="bulk-remove-' + count + '">' + bulk_price_remove_label + '</span>' + 
							'</p>';

							$bulk_prices( '#<?php echo $container_id; ?> #here-' + group_class ).append(content);

							return false;
						});
						$bulk_prices(document).on('click', '#bulk-remove-' + count, function(){
							$bulk_prices(this).remove();
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
	 * Save meta fields
	 *
	 * @param int $variation_id current post id.
	 * @return void
	 */
	public function save_variation_meta( $variation_id ) {

		if ( ! empty( $_POST['bm_rrp'][ $variation_id ] ) ) {
			update_post_meta( $variation_id, 'bm_rrp', $_POST['bm_rrp'][ $variation_id ] );
		} else {
			delete_post_meta( $variation_id, 'bm_rrp' );
		}

		$current_groups = BM_User::get_all_customer_group_ids();

		/* group prices */
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

			if ( ! empty( $_POST[ $group_slug . 'group_price_' . $variation_id ] ) ) {
				update_post_meta( $variation_id, 'bm_' . $group_slug . 'group_prices', $_POST[ $group_slug . 'group_price_' . $variation_id ] );
			} else {
				delete_post_meta( $variation_id, 'bm_' . $group_slug . 'group_prices' );
			}

			if ( ! empty( $_POST[ $group_slug . 'bulk_price_' . $variation_id  ] ) ) {
				update_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_prices', $_POST[ $group_slug . 'bulk_price_' . $variation_id ] );
			} else {
				delete_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_prices' );
			}

			/* save copy options */
			$group_copy = $_POST[ 'bm_' . $group_slug . 'copy_for_group' ][ $variation_id ];

			if ( ! empty( $group_copy ) ) {
				update_post_meta( $variation_id, 'bm_' . $group_slug . 'copy_for_group', $group_copy );

				if ( 'copy' !== $group_copy && isset( $_POST[ $group_copy . '_group_price_' . $variation_id ] ) ) {
					update_post_meta( $variation_id, 'bm_' . $group_slug . 'group_prices', $_POST[ $group_copy . '_group_price_' . $variation_id ] );
				}
			}

			$bulk_copy = $_POST[ 'bm_' . $group_slug . 'bulk_copy_for_group' ][ $variation_id ];

			if ( ! empty( $bulk_copy ) ) {
				update_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_copy_for_group', $bulk_copy );

				if ( 'copy' !== $bulk_copy && isset( $_POST[ $bulk_copy . '_bulk_price_' . $variation_id ] ) ) {
					update_post_meta( $variation_id, 'bm_' . $group_slug . 'bulk_prices', $_POST[ $bulk_copy . '_bulk_price_' . $variation_id ] );
				}
			}
		}
	}

	/**
	 * Adds the qty metabox.
	 *
	 * @param array $post_type array of post types.
	 * @return void
	 */
	public function add_qty_metabox( $post_type ) {
		global $post;

		if ( ! is_object( $post ) ) {
			return;
		}

		$product   = wc_get_product( $post->ID );
		$qty_addon = get_option( 'bm_addon_quantities' );

		if ( ! is_object( $product ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) && 'on' == $qty_addon ) {
			add_meta_box( 'bm-qty', __( 'B2B Market Quantity Addon', 'b2b-market' ), array( $this, 'render_qty_addon' ), 'product', 'normal', 'low' );
		}
	}

	/**
	 * Render qty addon metabox.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_qty_addon( $post ) {
		$product        = wc_get_product( $post->ID );
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
		<div id="b2b_fields">
			<h1><?php esc_html_e( 'Quantities', 'b2b-market' ); ?></h1>
			<p style="padding-left:15px"><?php esc_html_e( 'You can find the pricing fields for B2B Market directly within each variation.', 'b2b-market' ); ?></p>
			<div class="group-quantity-container">
			<?php
			foreach ( $current_groups as $group ) {

				$group_object = get_post( $group );
				$group_slug   = $group_object->post_name . '_';

				$copy_qty      = get_post_meta( $post->ID, 'bm_' . $group_slug . 'quantity_copy_for_group', true );
				$min_quantity  = get_post_meta( $post->ID, 'bm_' . $group_slug . 'min_quantity', true );
				$max_quantity  = get_post_meta( $post->ID, 'bm_' . $group_slug . 'max_quantity', true );
				$step_quantity = get_post_meta( $post->ID, 'bm_' . $group_slug . 'step_quantity', true );

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

						<label for="bm_<?php echo esc_attr( $group_slug ); ?>step_quantity"><?php esc_html_e( 'Step:', 'b2b-market' ); ?></label>
						<input class="space-right" type="number" min="0" name="bm_<?php echo esc_attr( $group_slug ); ?>step_quantity" value="<?php echo esc_attr( $step_quantity ); ?>" id="bm_<?php echo esc_attr( $group_slug ); ?>step_quantity">
					</div>
				</article>
				<?php
			}
			?>
			</div>
		</div>
		<?php
	}


	/**
	 * Save the meta when the post is saved.
	 *
	 * @Hook woocommerce_process_product_meta
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @param WP_Post $post
	 */
	public function save_qty_metabox( $post_id, $post = null ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$current_groups = BM_User::get_all_customer_group_ids();
		$product        = wc_get_product( $post_id );

		/* group prices */
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

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

new BM_Variation_Meta();
