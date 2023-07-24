<?php

class BM_Update_Price {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of BM_Price.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Maybe enqueue assets
	 *
	 * @return void
	 */
	public static function load_assets() {

		$product  = wc_get_product( get_the_id() );
		$group_id = BM_Conditionals::get_validated_customer_group();

		if ( empty( $group_id ) || empty( $product ) ) {
			// no scripts to add.
			return;
		}

		// now adding script and localize.
		$min             = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
		$current_theme   = get_stylesheet();
		$using_quickview = get_theme_mod( 'wcpc_enable_qv_setting' );

		if ( is_product() || ( ( 'wordpress-theme-atomion' == $current_theme || 'wordpress-theme-atomion-child' == $current_theme ) && false !== $using_quickview ) ) {
			wp_enqueue_script( 'bm-update-price-js', B2B_PLUGIN_URL . '/assets/public/bm-update-price.' . $min . 'js', array( 'jquery' ), BM::$version, false );
			wp_localize_script( 'bm-update-price-js', 'bm_update_price', array(
				'ajax_url'                              => admin_url( 'admin-ajax.php' ),
				'nonce'                                 => wp_create_nonce( 'update-price-nonce' ),
				'bulk_price_table_bg_color'             => get_option( 'bm_bulk_price_table_active_row_background_color', '#eaeaea' ),
				'bulk_price_table_font_color'           => get_option( 'bm_bulk_price_table_active_row_font_color', '#222222' ),
				'bulk_price_table_class'                => apply_filters( 'b2b_bulk_price_table_class', 'bm-bulk-table' ),
				'bulk_price_table_pick_min_max_qty'     => apply_filters( 'b2b_bulk_price_pick_min_max_qty', 'min' ),
				'german_market_price_variable_products' => ( class_exists( 'Woocommerce_German_Market' ) ? get_option( 'german_market_price_presentation_variable_products', 'gm_default' ) : 'gm_default' ),
			) );
		}
	}

	/**
	 * Add hidden id for js live price
	 *
	 * @return void
	 */
	public static function add_hidden_id_field() {
		?>
		<span id="current_id" style="visibility:hidden;" data-id="<?php echo esc_attr( get_the_id() ); ?>"></span>
		<?php
	}

	/**
	 * Live update price with ajax
	 *
	 * @return void
	 */
	public static function update_price() {
		// if id or qty missing return false.
		$response = array( 'sucess' => false );
		$nonce    = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'update-price-nonce' ) ) {
			die();
		}

		if ( empty( $_POST[ 'id' ] ) ) {
			print wp_json_encode( $response );
			exit;
		}

		if ( empty( $_POST[ 'qty' ] ) ) {
			print wp_json_encode( $response );
			exit;
		}

		// setup escaped postdata.
		$product_id = esc_attr( $_POST['id'] );
		$qty        = esc_attr( $_POST['qty'] );

		// setup handling data.
		$product     = wc_get_product( $product_id );
		$group_id    = BM_Conditionals::get_validated_customer_group();

		// No price updater for product bundles.
		if ( $product->is_type( 'bundle' ) || $product->is_type( 'yith_bundle' ) ) {
			exit;
		}

		// if customer_group missing return false.
		if ( empty( $group_id ) ) {
			print wp_json_encode( $response );
			exit;
		}

		$product_price = $product->get_regular_price();

		if ( '' != $product->get_sale_price() ) {
			$product_price = floatval( $product->get_sale_price() );
		}

		// Check sale price end date.
		$sale_price_start_date = get_post_meta( $product_id, '_sale_price_dates_from', true );
		$sale_price_end_date   = get_post_meta( $product_id, '_sale_price_dates_to', true );

		if ( ! empty( $sale_price_end_date ) ) {
			$timezone = new DateTimeZone( wp_timezone_string() );
			$today    = wp_date("d-m-Y", strtotime( 'today' ), $timezone );
			$start    = wp_date("d-m-Y", intval( $sale_price_start_date ), $timezone );
			$expire   = wp_date("d-m-Y", intval( $sale_price_end_date ), $timezone );

			$today_date  = new DateTime( $today );
			$start_date  = new DateTime( $start );
			$expire_date = new DateTime( $expire );

			if ( ( $expire_date < $today_date ) || ( $start_date > $today_date ) ) {
				$product_price = $product->get_regular_price();
			}
		}

		// Skip B2B price if we have a 'Sale price' of 0.
		if ( $product_price > 0 ) {
			// get pricing data.
			$cheapest_price = BM_Price::get_price( $product_price, $product, $group_id, $qty );
			$cheapest_price = BM_Tax::get_tax_price( $product, $cheapest_price );
		} else {
			$cheapest_price = $product_price;
		}

		// JSON response preparation.
		$response = array(
			'success'     => true,
			'id'          => $product_id,
			'price'       => wc_price( $cheapest_price ),
			'price_value' => $cheapest_price,
			'totals'      => wc_price( $cheapest_price * $qty ),
		);

		// WGM PPU compatibility.
		if ( class_exists( 'WGM_Price_Per_Unit' ) ) {
			$ppu_data = BM_Price::calculate_unit_price( $cheapest_price, $product, $qty );
			if ( ! empty( $ppu_data[ 'ppu' ] ) ) {
				$response['ppu'] = $ppu_data[ 'ppu' ];
			}
		}

		print wp_json_encode( $response );
		exit;
	}
}
