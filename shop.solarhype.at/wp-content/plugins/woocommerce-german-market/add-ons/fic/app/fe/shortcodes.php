<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WGM_FIC_Shortcodes {

	/**
	 * @var WGM_FIC_Shortcodes
	 * @since v3.10.5.0.1
	 */
	private static $instance = null;

	private static $runtime_cache = array();

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Compatibilities
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_FIC_Shortcodes();	
		}
		return self::$instance;
	}


	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {
		add_shortcode( 'gm_product_ingredients', 			array( __CLASS__, 'ingredients_callback' ) );
		add_shortcode( 'gm_product_nutritional_values', 	array( __CLASS__, 'nutritional_values_callback' ) );
		add_shortcode( 'gm_product_allergens', 				array( __CLASS__, 'allergens_callback' ) );
		add_shortcode( 'gm_product_alcohol', 				array( __CLASS__, 'alcohol_callback' ) );
	}

	/**
	* callback for shortcode [gm_product_alcohol]
	*
	* @param Array $atts
	* @return String
	*/
	public static function alcohol_callback( $atts ) {

		global $product;
		$used_product = WGM_Shortcodes::get_product_by_shortcode_attribute( $atts, $product );
		$alcohol = '';

		if ( ! WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			return $alcohol;
		}

		$parts = gm_fic_add_alcohol_content_to_product_info( array(), $used_product, 'single', false );

		if ( isset( $parts[ 'alc' ] ) ) {
			$alcohol = $parts[ 'alc' ];
		}

		$alcohol = WGM_Shortcodes::maybe_remove_markup_of_shortcode_return_value_by_attribute( $alcohol, $atts );

		return apply_filters( 'german_market_shortcode_alcohol', $alcohol, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_ingredients]
	*
	* @param Array $atts
	* @return String
	*/
	public static function ingredients_callback( $atts ) {

		global $product;
		$used_product = WGM_Shortcodes::get_product_by_shortcode_attribute( $atts, $product );
		$ingredients = '';
		
		if ( ! WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			return $ingredients;
		}

		if ( isset( self::$runtime_cache[ $used_product->get_id() ] ) ) {
			$have_to_add_infos = self::$runtime_cache[ $used_product->get_id() ];
		} else {
			$have_to_add_infos = gm_fic_have_to_add_tabs( $used_product );
			self::$runtime_cache[ $used_product->get_id() ] = $have_to_add_infos;
		}

		if ( $have_to_add_infos[ 'add_ingredients' ] ) {

			$show_headline = true;

			if ( isset( $atts[ 'show_headline' ] ) && 'no' === $atts[ 'show_headline' ] ) {
				$show_headline = false;
			}

			ob_start();
			if ( $show_headline ) { ?>
				<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_ingredients', __( 'Ingredients', 'woocommerce-german-market' ) ); ?></h2>
			<?php } ?> 

			<div id="gm_fic_ingredients" class="gm_fic_ingredients">
				<?php gm_fic_tab_content_ingredients_by_id( $used_product->get_id() ); ?>
			</div>
			<?php

			$ingredients = ob_get_clean();

		}

		return apply_filters( 'german_market_shortcode_ingredients', $ingredients, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_product_nutritional_values]
	*
	* @param Array $atts
	* @return String
	*/
	public static function nutritional_values_callback( $atts ) {

		global $product;
		$used_product = WGM_Shortcodes::get_product_by_shortcode_attribute( $atts, $product );
		$nutritional_values = '';

		if ( ! WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			return $nutritional_values;
		}		
		
		if ( isset( self::$runtime_cache[ $used_product->get_id() ] ) ) {
			$have_to_add_infos = self::$runtime_cache[ $used_product->get_id() ];
		} else {
			$have_to_add_infos = gm_fic_have_to_add_tabs( $used_product );
			self::$runtime_cache[ $used_product->get_id() ] = $have_to_add_infos;
		}

		if ( $have_to_add_infos[ 'add_nutritional_values' ] ) {

			$show_headline = true;

			if ( isset( $atts[ 'show_headline' ] ) && 'no' === $atts[ 'show_headline' ] ) {
				$show_headline = false;
			}

			ob_start();
			if ( $show_headline ) { ?>
				<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_nutritional_values', __( 'Nutritional Values', 'woocommerce-german-market' ) ); ?></h2>
			<?php } ?> 

			<div id="gm_fic_nutritional_values" class="gm_fic_nutritional_values">
				<?php gm_fic_tab_content_nutritional_values_by_id( $used_product->get_id() ); ?>
			</div>
			<?php

			$nutritional_values = ob_get_clean();

		}

		return apply_filters( 'german_market_shortcode_nutritional_values', $nutritional_values, $used_product, $atts );
	}

	/**
	* callback for shortcode [gm_allergens]
	*
	* @param Array $atts
	* @return String
	*/
	public static function allergens_callback( $atts ) {

		global $product;
		$used_product = WGM_Shortcodes::get_product_by_shortcode_attribute( $atts, $product );
		$allergens = '';

		if ( ! WGM_Helper::method_exists( $used_product, 'get_id' ) ) {
			return $allergens;
		}

		if ( isset( self::$runtime_cache[ $used_product->get_id() ] ) ) {
			$have_to_add_infos = self::$runtime_cache[ $used_product->get_id() ];
		} else {
			$have_to_add_infos = gm_fic_have_to_add_tabs( $used_product );
			self::$runtime_cache[ $used_product->get_id() ] = $have_to_add_infos;
		}

		if ( $have_to_add_infos[ 'add_allergens_info' ] ) {

			$show_headline = true;

			if ( isset( $atts[ 'show_headline' ] ) && 'no' === $atts[ 'show_headline' ] ) {
				$show_headline = false;
			}

			ob_start();
			if ( $show_headline ) { ?>
				<h2><?php echo get_option( 'gm_fic_ui_frontend_labels_allergens', __( 'Allergens', 'woocommerce-german-market' ) ); ?></h2>
			<?php } ?> 

			<div id="gm_fic_allergens" class="gm_fic_allergens">
				<?php gm_fic_tab_content_allergens_by_id( $used_product->get_id() ); ?>
			</div>
			<?php

			$allergens = ob_get_clean();

		}

		return apply_filters( 'german_market_short_code_allergens', $allergens, $used_product, $atts );
	}

}
