<?php
/**
 * Class that handles compatibility with German market.
 *
 * @package neve-german-market-compatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class German_Market_Neve_Compatibility
 */
class German_Market_Neve_Compatibility {
	
	/**
	 * Init function
	 */
	public function init() {
		$this->fix_shop_loop();
		$this->fix_single_product();
		$this->fix_checkout();
	}
	
	/**
	 * Fix GM on shop.
	 */
	private function fix_shop_loop(){
		// Remove the price added in German Market plugin
		add_filter( 'wgm_product_summary_parts', array( $this, 'hide_gm_price_in_loop') , 10, 3 );
		
		// Remove the "plus shipping" text
		remove_action( 'woocommerce_after_shop_loop_item_title', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ), 5 );
		
		// Replace the "plus shipping" text where it should be
		$hook = 'nv_shop_item_price_after';
		$alignment = get_theme_mod( 'neve_product_content_alignment', 'left' );
		if ( $alignment === 'inline' ) {
			$hook = 'nv_shop_item_title_after';
		}
		add_action( $hook, array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_loop' ) );
	}
	
	/**
	 * Remove German Market Price in Shop
	 *
	 * @param array $parts Parts.
	 * @param \WC_Product $product Woo product.
	 * @param string $hook Current hook.
	 *
	 * @return array
	 **/
	public function hide_gm_price_in_loop( $parts, $product, $hook ) {
		if ( $hook == 'loop' && isset( $parts[ 'price' ] ) ) {
			unset( $parts[ 'price' ] );
		}
		
		return $parts;
	}
	
	/**
	 * Fix GM on single product.
	 */
	private function fix_single_product() {
		
		// for some loops that are marked as "single"
		add_filter( 'wgm_template_get_wgm_product_summary_choose_hook', function( $hook, $woocommerce_loop ) {
			if ( $hook == 'single' ) {
				$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 10 );
				foreach ( $debug_backtrace as $elem ) {
					if ( $elem[ 'function' ] == 'woocommerce_de_price_with_tax_hint_loop' ) {
						$hook = 'loop';
					}
				}
			}
			return $hook;
		}, 10, 2 );
		
		add_filter( 'wgm_product_summary_parts', array( $this, 'hide_gm_price_in_single' ), 10, 3 );
		remove_action( 'woocommerce_single_product_summary', array( 'WGM_Template', 'woocommerce_de_price_with_tax_hint_single' ), 7 );
		
		add_action( 'nv_product_price_before', array( $this, 'wrap_price' ) );
		
		add_action( 'nv_product_price_after', array( $this, 'add_gm_data' ) );
	
	}
	
	/**
	 * Remove Price in Single Pages
	 *
	 * @param array $parts
	 * @param \WC_Product $product
	 * @param string $hook
	 * @return array
	 **/
	public function hide_gm_price_in_single( $parts, $product, $hook ) {
		if ( $hook == 'single' && isset( $parts[ 'price' ] ) ) {
			unset( $parts[ 'price' ] );
		}
		
		return $parts;
	}
	
	/**
	 * Wrap price on single product.
	 */
	public function wrap_price() {
		global $product;
		
		if ( $product instanceof WC_Product_Grouped ) {
			return;
		}
		echo '<div class="legacy-itemprop-offers">';
	}
	
	/**
	 * Add GM functionality.
	 */
	public function add_gm_data() {
		global $product;
		
		if ( $product instanceof WC_Product_Grouped ) {
			return;
		}
		
		echo WGM_Template::get_wgm_product_summary( $product, 'theme_support_astra' );
		
		echo '</div>';
		
		if ( apply_filters( 'gm_compatibility_is_variable_wgm_template', true, $product ) ) {
			
			
			
			if ( is_a( $product, 'WC_Product_Variable' ) ) {
				WGM_Template::add_digital_product_prerequisits( $product );
			}
			
		}
	}
	
	/**
	 * Fix checkout.
	 */
	private function fix_checkout() {
		$layout = get_theme_mod( 'neve_checkout_page_layout', 'standard' );
		if ( $layout !== 'stepped' ){
			return false;
		}
		
		add_action( 'woocommerce_after_checkout_form', array( $this, 'enqueue_checkout_script' ) );
		
		return true;
	}
	
	/**
	 * Script for stepped layout.
	 */
	public function enqueue_checkout_script() {
		echo '<script>

		window.addEventListener(\'load\', function() {
			document.getElementById(\'place_order\').style.display = \'none\';
			var isVisible = document.getElementById(\'p-shipping-service-provider\').style.display !== \'none\';
			document.getElementById(\'p-shipping-service-provider\').style.display = \'none\';
			document.getElementById(\'terms\').parentNode.style.display = \'none\';
			
			var navSteps = document.querySelectorAll( \'.nv-checkout-step\' );
			for (var i = 0; i < navSteps.length; i++) {
				navSteps[i].addEventListener(\'click\', function() {
					var step = this;
					selectStep(step, isVisible);
				} );
			}

			var nextStepButton = document.querySelectorAll( \'.next-step-button-wrapper .button\' );
			for (let i = 0; i < navSteps.length; i++) {
				if ( typeof nextStepButton[i] !== \'undefined\' ){
					nextStepButton[i].addEventListener( \'click\', function() {
						var step = this;
						selectStep(step, isVisible);
					});
				}
			}
			
			function selectStep( step, isVisible ){
				document.getElementById(\'place_order\').style.display = \'none\';
				document.getElementById(\'p-shipping-service-provider\').style.display = \'none\';
				document.getElementById(\'terms\').parentNode.style.display = \'none\';
			
				var currentStep = step.dataset.step;
				if ( currentStep === \'payment\' ) {
					document.getElementById(\'place_order\').style.display = \'block\';
					if ( isVisible ){
						document.getElementById(\'p-shipping-service-provider\').style.display = \'block\';
					}
					document.getElementById(\'terms\').parentNode.style.display = \'block\';
				}
			}
		});
		
		</script>';
	}
	
}
