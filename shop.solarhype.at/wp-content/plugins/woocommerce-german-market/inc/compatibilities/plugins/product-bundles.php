<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Plugin_Compatibility_WC_Product_Bundles
 * Compatibility functions for WooCommerce Product Bundles plugin.
 *
 * @author MarketPress
 */
class WGM_Plugin_Compatibility_WC_Product_Bundles {

	static $instance = NULL;

	/**
	 * singleton getInstance
	 *
	 * @access public
	 * @static
	 *
	 * @return WGM_Plugin_Compatibility_WC_Product_Bundles WGM_Plugin_Compatibility_WC_Product_Bundles
	 */
	public static function get_instance() {

		if ( self::$instance == NULL) {
			self::$instance = new WGM_Plugin_Compatibility_WC_Product_Bundles();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function __construct() {

		add_filter( 'german_market_avoid_called_twice_in_add_mwst_rate_to_product_order_item', 	'__return_true' );

		add_filter( 'gm_add_mwst_rate_to_product_item_return',									array( $this, 'wc_bundles_gm_add_mwst_rate_to_product_item_return' ), 10, 3 );
		add_filter( 'gm_show_taxes_in_cart_theme_template_return_empty_string',					array( $this, 'wc_bundles_gm_tax_rate_in_cart' ), 10, 2 );
		add_filter( 'german_market_ppu_co_woocommerce_add_cart_item_data_return', 				array( $this, 'wc_bundles_dont_add_ppu_to_cart_item_data' ), 10, 4 );
		add_filter( 'german_market_ppu_co_woocommerce_add_order_item_meta_wc_3_return', 		array( $this, 'wc_bundles_dont_add_ppu_to_order_item_meta' ), 10, 4 );
		add_filter( 'german_market_delivery_time_co_woocommerce_add_cart_item_data_return', 	array( $this, 'wc_bundles_dont_add_ppu_to_cart_item_data' ), 10, 4 );
		add_filter( 'woocommerce_de_add_delivery_time_to_product_title', 						array( $this, 'wc_bundles_dont_show_delivery_time_in_order' ), 10, 3 );
		add_filter( 'wp_wc_invvoice_pdf_td_product_name_style', 								array( $this, 'wc_bundles_invoice_pdf_padding_for_bundled_products' ), 10, 2 );
		add_filter( 'wp_wc_invoice_pdf_tr_class', 												array( $this, 'wc_bundles_invoice_pdf_tr_class' ), 10, 2 );
		add_filter( 'german_market_sort_woocommerce_order_get_items',                           array( $this, 'wc_bundles_sort_woocommerce_order_get_items' ), 10, 4 );
		add_filter( 'german_market_sort_woocommerce_cart_items',                                array( $this, 'wc_bundles_sort_woocommerce_cart_items' ), 10, 2 );
		add_filter( 'german_market_add_woocommerce_de_templates_force_original',                array( $this, 'wc_bundles_add_woocommerce_de_templates_force_original' ), 10, 2 );
		add_filter( 'woocommerce_after_template_part',                                          array( $this, 'wc_bundles_woocommerce_after_template_part' ), 10, 4 );

		add_action( 'wp_head',																	array( $this, 'wc_bundles_styles' ) );
	}

	/**
	 *
	 * @access public
	 *
	 * @param bool   $boolean
	 * @param string $template_name
	 *
	 * @return bool|mixed
	 */
	public function wc_bundles_add_woocommerce_de_templates_force_original( $boolean, $template_name ) {

		if ( $template_name == 'cart/cart.php' ) {
			$boolean = true;
		}

		if ( ! has_filter( 'woocommerce_cart_item_subtotal', array( 'WGM_Template', 'add_mwst_rate_to_product_item' ) ) ) {
			add_filter( 'woocommerce_cart_item_subtotal', array( 'WGM_Template', 'show_taxes_in_cart_theme_template' ), 10, 3 );
		}

		return $boolean;
	}

	/**
	 *
	 * @param string $template_name
	 * @param string $template_path
	 * @param $located
	 * @param array  $args
	 *
	 * @return void
	 */
	public function wc_bundles_woocommerce_after_template_part( $template_name, $template_path, $located, $args ) {

		if ( $template_name == 'single-product/bundled-item-price.php' ) {

			if ( isset( $args[ 'bundled_item' ] ) ) {

				$bundled_item = $args[ 'bundled_item' ];
				if ( $bundled_item->is_priced_individually() ) {
					echo WGM_Tax::get_tax_line( $bundled_item->product );
				}

			}

		}
	}

	/**
	 * This function is responsible for sorting the products on edit order page.
	 *
	 * @hook woocommerce_order_get_items
	 *
	 * @access public
	 *
	 * @param $array   $pproducts_result
	 * @param array    $items Array of Items.
	 * @param WC_Order $order Order Object.
	 * @param array    $types Type of Item.
	 *
	 * @return mixed
	 */
	public function wc_bundles_sort_woocommerce_order_get_items( $products_result, $items, $order, $types ) {

		$sort_products_by    = get_option( 'gm_checkout_sort_products_by', 'standard' );
		$sort_products_order = get_option( 'gm_checkout_sort_products_ascdesc', 'asc' );

		if ( is_array( $types ) && 'line_item' != $types[ 0 ] ) {
			return $items;
		}

		$products_processing = array();
		$products_unsortable = array();
		$products_result     = array();
		$sort_order          = 'asc' === $sort_products_order ? SORT_ASC : SORT_DESC;
		$sort_flag           = SORT_REGULAR;
		$is_numeric          = true; // We assume SKU is numeric by default

		foreach ( $items as $key => $item ) {

			// Do we have a bundled product?
			if ( isset( $item[ 'bundled_by' ] ) && ! empty( $item[ 'bundled_by' ] ) ) {
				continue;
			}

			if ( ! method_exists( $item, 'get_product' ) ) {
				continue;
			}

			$_product = $item->get_product();

			$sku      = WGM_Sortable_Products::get_the_sku( $_product );
			$name     = WGM_Sortable_Products::get_the_title( $_product );
			$bundled  = self::wc_bundles_get_the_bundled_items( $item );

			// Product is not sortable
			if ( ( 'sku' === $sort_products_by && empty( $sku ) ) || ( 'name' === $sort_products_by && empty( $name ) ) ) {
				$products_unsortable[] = array(
					'key'     => $key,
					'sku'     => $sku,
					'name'    => $name,
					'bundled' => $bundled,
				);
				continue;
			}

			// Product is sortable.
			$products_processing[] = array(
				'key'     => $key,
				'sku'     => $sku,
				'name'    => $name,
				'bundled' => $bundled,
			);

			if ( 'sku' === $sort_products_by && true === $is_numeric ) {
				if ( ! is_numeric( $sku ) ) {
					$is_numeric = false;
				}
			}

		}

		// Sort 'numeric' only if we didnt find alphanumeric SKU's
		if ( 'sku' === $sort_products_by && true === $is_numeric ) {
			$sort_flag = SORT_NUMERIC;
		}

		// Sort by column.
		$column = array_column( $products_processing, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_processing );

		// Sort by column.
		$column = array_column( $products_unsortable, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_unsortable );

		// Merging sorted and unsortable array
		$products_processing = array_merge( $products_processing, $products_unsortable );

		// Assigning sorted items.
		foreach ( $products_processing as $order_item ) {
			$order_key = $order_item[ 'key' ];
			$products_result[ $order_key ] = $items[ $order_key ];
			if ( isset( $order_item[ 'bundled' ] ) && 0 < count( $order_item[ 'bundled' ] ) ) {
				foreach ( $order_item[ 'bundled' ] as $bundled_item ) {
					foreach ( $items as $key => $item ) {
						$bundled_cart_key = $item->get_meta( '_bundle_cart_key' );
						if ( $bundled_cart_key == $bundled_item ) {
							$products_result[ $key ] = $item;
						}
					}
				}
			}
		}

		return $products_result;
	}

	/**
	 * This function is responsible for sorting the products on edit order page.
	 *
	 * @hook woocommerce_order_get_items
	 *
	 * @access public
	 *
	 * @param array    $products_result Array of Items.
	 * @param WC_Cart  $cart_content Cart objects
	 *
	 * @return mixed
	 */
	public function wc_bundles_sort_woocommerce_cart_items( $products_result, $cart_content ) {

		$sort_products_by    = get_option( 'gm_cart_sort_products_by', 'standard' );
		$sort_products_order = get_option( 'gm_cart_sort_products_ascdesc', 'asc' );

		$products_processing = array();
		$products_unsortable = array();
		$products_result     = array();
		$sort_order          = 'asc' === $sort_products_order ? SORT_ASC : SORT_DESC;
		$sort_flag           = SORT_REGULAR;
		$is_numeric          = true; // We assume SKU is numeric by default

		foreach ( $cart_content as $cart_key => $item ) {

			// Do we have a bundled product?
			if ( isset( $item[ 'bundled_by' ] ) && ! empty( $item[ 'bundled_by' ] ) ) {
				continue;
			}

			$sku     = WGM_Sortable_Products::get_the_sku( $item[ 'data' ] );
			$name    = WGM_Sortable_Products::get_the_title( $item[ 'data' ] );
			$bundled = self::wc_bundles_get_the_bundled_items( $item );

			// Product is not sortable
			if ( ( 'sku' === $sort_products_by && empty( $sku ) ) || ( 'name' === $sort_products_by && empty( $name ) ) ) {
				$products_unsortable[] = array(
					'key'     => $cart_key,
					'sku'     => $sku,
					'name'    => $name,
					'bundled' => $bundled,
				);
				continue;
			}

			// Product is sortable.
			$products_processing[] = array(
				'key'     => $cart_key,
				'sku'     => $sku,
				'name'    => $name,
				'bundled' => $bundled,
			);

			if ( 'sku' === $sort_products_by && true === $is_numeric ) {
				if ( ! is_numeric( $sku ) ) {
					$is_numeric = false;
				}
			}

		}

		// Sort 'numeric' only if we didnt find alphanumeric SKU's
		if ( 'sku' === $sort_products_by && true === $is_numeric ) {
			$sort_flag = SORT_NUMERIC;
		}

		// Sort by column.
		$column = array_column( $products_processing, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_processing );

		// Sort by column.
		$column = array_column( $products_unsortable, $sort_products_by );
		array_multisort( $column, $sort_order, $sort_flag, $products_unsortable );

		// Merging sorted and unsortable array
		$products_processing = array_merge( $products_processing, $products_unsortable );

		// Assigning sorted items.
		foreach ( $products_processing as $cart_item ) {
			$cart_key = $cart_item[ 'key' ];
			$products_result[ $cart_key ] = WC()->cart->cart_contents[ $cart_key ];
			if ( isset( $cart_item[ 'bundled' ] ) && 0 < count( $cart_item[ 'bundled' ] ) ) {
				foreach ( $cart_item[ 'bundled' ] as $bundled_item ) {
					$products_result[ $bundled_item ] = WC()->cart->cart_contents[ $bundled_item ];
				}
			}
		}

		return $products_result;
	}

	/**
	 * This helper function returns the bundled items.
	 *
	 * @access private
	 * @static
	 *
	 * @param object|array $item
	 *
	 * @return array
	 */
	private static function wc_bundles_get_the_bundled_items( $item ) {

		$result = array();

		if ( isset( $item[ 'bundled_items' ] ) && 0 < count( $item[ 'bundled_items' ] ) ) {
			$result = $item[ 'bundled_items' ];
		}

		return $result;
	}

	/**
	 * Plugin WooCommerce Product Bundles: No GM Data for Variations
	 *
	 * @since 3.8.2
	 *
	 * @wp-hook wp_head
	 *
	 * @access public
	 *
	 * @return void
	 */
	function wc_bundles_styles(){

		?>
		<style>
            .bundled_item_cart_details .wgm-info{ display: none; }
            .bundled_item_cart_details .price + .wgm-info.woocommerce-de_price_taxrate{ display: block; }
            table.shop_table td.product-subtotal span.wgm-tax { display: none; }
            table.shop_table td.product-subtotal .woocommerce-Price-amount ~ span.wgm-tax { display: block; }
		</style>
		<?php
	}

	/**
	 * Plugin WooCommerce Product Bundles: Add tr class to bundled items
	 *
	 * @since 3.10.4
	 *
	 * @wp-hook wp_wc_invoice_pdf_tr_class
	 *
	 * @access public
	 *
	 * @param string $tr_class
	 * @param item   $args
	 *
	 * @return String
	 */
	public function wc_bundles_invoice_pdf_tr_class( $tr_class, $item ) {

		if ( isset( $item[ 'bundled_by' ] ) ) {
			$tr_class .= ' bundled';
		}

		return trim( $tr_class );
	}

	/**
	 * Plugin WooCommerce Product Bundles: Order Item Names in Invoice PDFs
	 *
	 * @since 3.10.2
	 *
	 * @wp-hook wp_wc_invvoice_pdf_td_product_name_style
	 *
	 * @access public
	 *
	 * @param string $style
	 * @param item   $args
	 *
	 * @return String
	 */
	public function wc_bundles_invoice_pdf_padding_for_bundled_products( $style, $item ) {

		if ( isset( $item[ 'bundled_by' ] ) ) {
			$style = 'padding-left: 2em;';
		}

		return $style;
	}

	/**
	 * Plugin WooCommerce Product Bundles: Don't show taxes of bundled items
	 *
	 * @since 3.8.2
	 *
	 * @wp-hook gm_add_mwst_rate_to_product_item_return
	 *
	 * @access public
	 *
	 * @param Boolean    $booleand
	 * @param WC_Prodcut $product
	 * @param Array      $item
	 *
	 * @return Bollean
	 */
	public function wc_bundles_gm_add_mwst_rate_to_product_item_return( $boolean, $product, $cart_item ) {

		if ( isset( $cart_item[ 'bundled_by' ] ) ) {
			$boolean = true;

			if ( isset( $cart_item[ 'data' ]->bundled_cart_item->item_data ) ) {

				$item_data = $cart_item[ 'data' ]->bundled_cart_item->item_data;

				if ( isset( $item_data[ 'priced_individually' ] ) && $item_data[ 'priced_individually' ] == 'yes' ) {
					$boolean = false;
				}

			}
		}

		return $boolean;
	}

	/**
	 * Plugin WooCommerce Product Bundles: Don't add PPU to order item data
	 *
	 * @since v3.8.2
	 *
	 * @wp-hook german_market_ppu_co_woocommerce_add_order_item_meta_wc_3_return
	 *
	 * @access public
	 *
	 * @param boolean $boolean
	 * @param integer $item_id
	 * @param array   $item
	 * @param integer $order_id
	 *
	 * @return boolean
	 */
	public function wc_bundles_dont_add_ppu_to_order_item_meta( $boolean, $item_id, $item, $order_id ) {

		if ( isset( $item[ 'bundled_by' ] ) ) {
			$boolean = true;
		}

		return $boolean;
	}

	/**
	 * Plugin WooCommerce Product Bundles: Don't show delivery time in orders
	 *
	 * @since v3.8.2
	 *
	 * @wp-hook woocommerce_de_add_delivery_time_to_product_title
	 *
	 * @access public
	 *
	 * @param string $return
	 * @param string $item_name
	 * @param array  $item
	 *
	 * @return string
	 */
	public function wc_bundles_dont_show_delivery_time_in_order( $return, $item_name, $item ) {

		if ( isset( $item[ 'bundled_by' ] ) ) {
			$return = $item_name;
		}

		return $return;
	}

	/**
	 * Plugin WooCommerce Product Bundles: Don't add PPU to cart item data
	 *
	 * @since v3.8.2
	 *
	 * @wp-hook german_market_ppu_co_woocommerce_add_cart_item_data_return
	 * @wp-hook german_market_delivery_time_co_woocommerce_add_cart_item_data_return
	 *
	 * @access public
	 *
	 * @param boolean $boolean
	 * @param array $cart_item_data
	 * @param integer $product_id
	 * @param integer $variation_id
	 *
	 * @return string
	 */
	public function wc_bundles_dont_add_ppu_to_cart_item_data( $boolean, $cart_item_data, $product_id, $variation_id ) {

		if ( isset( $cart_item_data[ 'bundled_by' ] ) ) {
			$boolean = true;
		}

		return $boolean;
	}

	/**
	 * Plugin WooCommerce Product Bundles: Don't show taxes of bundled items
	 *
	 * @since v3.8.2
	 *
	 * @wp-hook gm_show_taxes_in_cart_theme_template_return_empty_string
	 *
	 * @access public
	 *
	 * @param boolean $string
	 * @param array   $cart_item
	 *
	 * @return boolean
	 */
	public function wc_bundles_gm_tax_rate_in_cart( $boolean, $cart_item ) {

		if ( isset( $cart_item[ 'bundled_by' ] ) ) {

			$boolean = true;

			if ( isset( $cart_item[ 'data' ]->bundled_cart_item->item_data ) ) {

				$item_data = $cart_item[ 'data' ]->bundled_cart_item->item_data;

				if ( isset( $item_data[ 'priced_individually' ] ) && $item_data[ 'priced_individually' ] == 'yes' ) {
					$boolean = false;
				}

			}

		}

		return $boolean;
	}

}
