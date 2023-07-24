<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Theme_Compatibilities
 * @author MarketPress
 */
class WGM_Theme_Compatibilities {

	static $instance = NULL;

	public static $theme_compatibilities_path;

	/**
	* singleton getInstance
	*
	* @access public
	* @static
	* @return class WGM_Theme_Compatibilities
	*/			
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Theme_Compatibilities();	
		}
		return self::$instance;
	}

	/**
	* constructor
	*
	* @since 0.0.1
	* @access private
	* @return void
	*/	
	private function __construct() {

		spl_autoload_register( array( __CLASS__, 'theme_autoload' ) );

		$the_theme = wp_get_theme();

		$themes = array(
			'aurum' 					=> 'German_Market_Theme_Compatibility_Aurum',
			'annakait'					=> 'German_Market_Theme_Compatibility_Aurum',
			'avada'						=> 'German_Market_Theme_Compatibility_Avada',
			'furlen'					=> 'German_Market_Theme_Compatibility_Furlen',
			'worldmart'					=> 'German_Market_Theme_Compatibility_Worldmart',
			'learts'					=> 'German_Market_Theme_Compatibility_Learts',
			'rigid'						=> 'German_Market_Theme_Compatibility_Rigid',
			'hyperon'					=> 'German_Market_Theme_Compatibility_Hyperon',
			'eat-eco'					=> 'German_Market_Theme_Compatibility_Eco',
			'drile'						=> 'German_Market_Theme_Compatibility_Drile',
			'oceanwp'					=> 'German_Market_Theme_Compatibility_Oceanwp',
			'astra'						=> 'German_Market_Theme_Compatibility_Astra',
			'sport'						=> 'German_Market_Theme_Compatibility_Sport',
			'handlavet'					=> 'German_Market_Theme_Compatibility_Handlavet',
			'zonex'						=> 'German_Market_Theme_Compatibility_Zonex',
			'blaze'						=> 'German_Market_Theme_Compatibility_Blaze',
			'themify-shoppe'			=> 'German_Market_Theme_Compatibility_Themifyshoppe',
			'emarket'					=> 'German_Market_Theme_Compatibility_Emarket',
			'urus'						=> 'German_Market_Theme_Compatibility_Urus',
			'panaderia'					=> 'German_Market_Theme_Compatibility_Panaderia',
			'vipex'						=> 'German_Market_Theme_Compatibility_Vipex',
			'hongo'						=> 'German_Market_Theme_Compatibility_Hongo',
			'wineforyou-hon'			=> 'German_Market_Theme_Compatibility_Hongo',
			'jupiterx'					=> 'German_Market_Theme_Compatibility_Jupiterx',
			'kartpul'					=> 'German_Market_Theme_Compatibility_Kartpul',
			'klippe'					=> 'German_Market_Theme_Compatibility_Klippe',
			'ecode'						=> 'German_Market_Theme_Compatibility_Ecode',
			'technics'					=> 'German_Market_Theme_Compatibility_Technics',
			'hello-elementor'			=> 'German_Market_Theme_Compatibility_Helloelementor',
			'blocksy'					=> 'German_Market_Theme_Compatibility_Blocksy',
			'goya'						=> 'German_Market_Theme_Compatibility_Goya',
			'kitring'					=> 'German_Market_Theme_Compatibility_Kitring',
			'erado'						=> 'German_Market_Theme_Compatibility_Erado',
			'kalium'					=> 'German_Market_Theme_Compatibility_Kalium',
			'ciloe'						=> 'German_Market_Theme_Compatibility_Ciloe',
			'elessi-theme'				=> 'German_Market_Theme_Compatibility_Elessitheme',
			'alishop'					=> 'German_Market_Theme_Compatibility_Alishop',
			'shopkeeper'				=> 'German_Market_Theme_Compatibility_Shopkeeper',
			'supro'						=> 'German_Market_Theme_Compatibility_Supro',
			'tonda'						=> 'German_Market_Theme_Compatibility_Tonda',
			'chromium'					=> 'German_Market_Theme_Compatibility_Chromium',
			'bazien'					=> 'German_Market_Theme_Compatibility_Bazien',
			'peggi'						=> 'German_Market_Theme_Compatibility_Peggi',
			'zass'						=> 'German_Market_Theme_Compatibility_Zass',
			'gioia'						=> 'German_Market_Theme_Compatibility_Gioia',
			'teepro'					=> 'German_Market_Theme_Compatibility_Teepro',
			'techmarket'				=> 'German_Market_Theme_Compatibility_Techmarket',
			'woovina'					=> 'German_Market_Theme_Compatibility_Woovina',
			'gardening'					=> 'German_Market_Theme_Compatibility_Gardening',
			'pearl'						=> 'German_Market_Theme_Compatibility_Pearl',
			'crexis'					=> 'German_Market_Theme_Compatibility_Crexis',
			'kanna'						=> 'German_Market_Theme_Compatibility_Kanna',
			'bikeway'					=> 'German_Market_Theme_Compatibility_Bikeway',
			'woostroid'					=> 'German_Market_Theme_Compatibility_Woostroid',
			'woostroid2'				=> 'German_Market_Theme_Compatibility_Woostroid',
			'lotusgreen'				=> 'German_Market_Theme_Compatibility_Lotusgreen',
			'dokan'						=> 'German_Market_Theme_Compatibility_Dokan',
			'zigcy-lite'				=> 'German_Market_Theme_Compatibility_Zigcylite',
			'movedo'					=> 'German_Market_Theme_Compatibility_Movedo',
			'lucia-vineyards'			=> 'German_Market_Theme_Compatibility_Luciavineyards',
			'bazaar'					=> 'German_Market_Theme_Compatibility_Bazaar',
			'riode'						=> 'German_Market_Theme_Compatibility_Riode',
			'june'						=> 'German_Market_Theme_Compatibility_June',
			'mrtailor'					=> 'German_Market_Theme_Compatibility_Mrtailor',
			'bridge'					=> 'German_Market_Theme_Compatibility_Bridge',
			'mybag'						=> 'German_Market_Theme_Compatibility_Mybag',
			'pawfriends'				=> 'German_Market_Theme_Compatibility_Pawfriends',
			'sentinal'					=> 'German_Market_Theme_Compatibility_Sentinal',
			'brunn'						=> 'German_Market_Theme_Compatibility_Brunn',
			'zocker'					=> 'German_Market_Theme_Compatibility_Zocker',
			'wordpress-theme-atomion' 	=> 'German_Market_Theme_Compatibility_Atomion',
			'zamona'					=> 'German_Market_Theme_Compatibility_Zamona',
			'chaai'						=> 'German_Market_Theme_Compatibility_Chaai',
			'ambient'					=> 'German_Market_Theme_Compatibility_Ambient',
			'bacola'					=> 'German_Market_Theme_Compatibility_Bacola',
			'rt-theme-20'				=> 'German_Market_Theme_Compatibility_Rttheme20',
			'waveride'					=> 'German_Market_Theme_Compatibility_Waveride',
			'storefront'				=> 'German_Market_Theme_Compatibility_Storefront',
			'lacomete'					=> 'German_Market_Theme_Compatibility_Lacomete',
		);

		$template 	= strtolower( str_replace( '-child', '', $the_theme->get_template() ) );
		$stylesheet = strtolower( str_replace( '-child', '', $the_theme->get_stylesheet() ) );
		$textdomain = strtolower( $the_theme->get( 'TextDomain' ) );

		$compatibility_theme_class = false;

		if ( isset( $themes[ $template ] ) ) {
			$compatibility_theme_class = $themes[ $template ];
		} else if ( isset( $themes[ $stylesheet ] ) ) {
			$compatibility_theme_class = $themes[ $stylesheet ];
		} else if ( isset( $themes[ $textdomain ] ) ) {
			$compatibility_theme_class = $themes[ $textdomain ];
		}

		if ( $compatibility_theme_class )  {
			if ( method_exists( $compatibility_theme_class, 'init' ) ) {
				add_action( 'after_setup_theme', array( $compatibility_theme_class, 'init' ), 99 );
			}
		}
	}

	/**
	* Autoload Theme Compatibility Class
	*
	* @access public
	* @static
	* @return void
	*/	
	public static function theme_autoload( $class ) {

		if ( strpos( $class, 'German_Market_Theme_Compatibility_' ) !== false ) {

			$filename = str_replace( 'German_Market_Theme_Compatibility_', '', $class );
			$filename = strtolower( $filename );

			$file = WGM_Compatibilities::$theme_compatibilities_path . $filename . DIRECTORY_SEPARATOR . $filename . '.php';

			if ( $file && is_readable( $file ) ) {
				include_once( $file );
			}
		}
	}

	/**
	* Remove Price in Shop
	*
	* @since v3.5.4
	* wp-hook wgm_product_summary_parts
	* @param Array $parts
	* @param WC_Product $product
	* @param String $hook
	* @return String
	**/
	public static function theme_support_hide_gm_price_in_loop( $parts, $product, $hook ) {

		if ( $hook == 'loop' && isset( $parts[ 'price' ] ) ) {
			unset( $parts[ 'price' ] );
		}

		return $parts;

	}

	/**
	* Remove Price in Single Pages
	*
	* @since v3.7
	* wp-hook wgm_product_summary_parts
	* @param Array $parts
	* @param WC_Product $product
	* @param String $hook
	* @return String
	**/
	public static function theme_support_hide_gm_price_in_single( $parts, $product, $hook ) {

		if ( $hook == 'single' && isset( $parts[ 'price' ] ) ) {
			unset( $parts[ 'price' ] );
		}

		return $parts;
	}

	/**
	* Theme Support: Remove GM Price in Shop
	*
	* @since v3.5.3
	* @wp-hook wgm_product_summary_parts
	* @param Array $output_parts
	* @param WC_Product $product
	* @param String $hook
	* @return Array
	*/
	public static function theme_support_wgm_remove_price_in_summary_parts_in_shop( $output_parts, $product, $hook ) {

		if ( $hook == 'single' ) {

			if ( isset( $output_parts[ 'price' ] ) ) {
				unset( $output_parts[ 'price' ] );
			}

		}

		return $output_parts;
	}
}
