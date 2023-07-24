<?php
/**
 * Automatic class Loader
 * @author ap
 */
class WGM_Loader {

    /**
     * Registers autoloader function to spl_autoload
     * @access public
     * @static
     * @author ap
     * @return void
     */
    public static function register(){
		spl_autoload_register( 'WGM_Loader::load' );
	}

    /**
     * Unregisters autoloader function with spl_autoload
     * @access public
     * @static
     * @author ap
     * @return void
     */
    public static function unregister(){
		spl_autoload_unregister( 'WGM_Loader::load' );
	}

    /**
     * Autloading function
     * @param string $classname
     * @access public
     * @static
     * @author ap
     * @return void
     */
    public static function load( $classname ){
		
        $file =  dirname( __FILE__ ) . DIRECTORY_SEPARATOR . ucfirst( $classname ) . '.php';
		
        // php sepa xml
        if ( strpos( strtolower( $classname ), 'digitick\sepa' ) !== false ) {
            $path = untrailingslashit( Woocommerce_German_Market::$plugin_path ) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'php-sepa-xml';
            $file = $path . DIRECTORY_SEPARATOR . ucfirst( str_replace( 'Digitick/Sepa/', '', str_replace( '\\', '/', $classname ) ) . '.php' );
        }

        if ( $classname == 'WGM_Manual_Order_Confirmation' && get_option( 'german_market_use_manual_order_confirmation_2_beta', 'no' ) == 'yes' ) {
            $file =  dirname( __FILE__ ) . DIRECTORY_SEPARATOR . ucfirst( $classname ) . '_2.php';
        }
       
        if ( file_exists( $file ) ) {
           require_once $file; 
        }

	}

}
