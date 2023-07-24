<?php
/**
 * Class which handles the tax status
 */
class BM_Tax {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Shop tax setting
	 *
	 * @var string
	 */
	public $shop_tax;

	/**
	 * Cart tax setting
	 *
	 * @var string
	 */
	public $cart_tax;

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
	 * Constructor
	 */
	public function __construct() {
		$this->shop_tax = get_option( 'woocommerce_tax_display_shop' );
		$this->cart_tax = get_option( 'woocommerce_tax_display_cart' );
	}

	/**
	 * Filter the tax status based on user group settings
	 *
	 * @param string $value given tax value.
	 * @return string
	 */
	public function filter_tax_display( $value ) {
		$group_id = BM_Conditionals::get_validated_customer_group();
		$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

		if ( 'on' == $tax_type ) {
			$value = 'excl';
		}

		return $value;
	}

	/**
	 * Filter the tax status based on user group settings
	 *
	 * @param string $value
	 * @return void
	 */
	public function filter_wcevc_general_tax_display( $value ) {
		$group_id = BM_Conditionals::get_validated_customer_group();
		$tax_type = get_post_meta( $group_id, 'bm_tax_type', true );

		if ( 'on' == $tax_type ) {
			$value = __( 'excl. VAT', 'b2b-market' );
		}

		return $value;
	}
	/**
	 * Add a hash to the current user session to apply tax settings for variations
	 *
	 * @param string $hash
	 * @return void
	 */
	public function tax_display_add_hash_user_id( $hash ) {
		$hash[] = get_current_user_id();
		return $hash;
	}

	/**
	 * Get price with correct tax display.
	 *
	 * @param  WC_Product $product current product object.
	 * @param  float      $price current price without formatting.
	 *
	 * @return float
	 */
	public static function get_tax_price( $product, $price ) {
		$group_id     = BM_Conditionals::get_validated_customer_group();
		$tax_type     = get_post_meta( $group_id, 'bm_tax_type', true );
		$tax_input    = get_option( 'woocommerce_prices_include_tax' );
		$tax_based_on = get_option( 'woocommerce_tax_based_on', 'base' );

		if ( 'on' === $tax_type ) {
			$args    = array( 'price' => $price );
			$use_net = apply_filters( 'bm_net_admin_tax', false );
			if ( $use_net ) {
				return wc_get_price_including_tax( $product, $args );
			} else {
				return wc_get_price_excluding_tax( $product, $args );
			}
		} else {
			$args = array( 'price' => $price );
			if ( 'no' === $tax_input ) {
				return wc_get_price_including_tax( $product, $args );
			} else {
				if ( 'billing' === $tax_based_on || 'shipping' === $tax_based_on ) {
					return wc_get_price_including_tax( $product, $args );
				}
				return $price;
			}
		}
	}
}
