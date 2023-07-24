<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController; 

/**
 * Helper Functions for High Performance Order Storage from WooCommerce
 *
 */
class WGM_Hpos {

    private static $instance = null;

    /**
    * Singletone get_instance
    *
    * @static
    * @return WGM_Compatibilities
    */
    public static function get_instance() {
        if ( self::$instance == NULL) {
            self::$instance = new WGM_Hpos(); 
        }
        return self::$instance;
    }

    /**
    * Singletone constructor
    *
    * @access private
    */
    private function __construct() {}

    /**
    * get id of "edit-shop_order" screen
    *
    * @return String
    */
    public static function get_edit_shop_order_screen() {
        return  wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() 
                ? wc_get_page_screen_id( 'shop-order' ) 
                : 'edit-shop_order';
    }

    /**
    * get if current screen id is "edit-shop_order" screen
    *
    * @return Boolean
    */
    public static function is_edit_shop_order_screen() {
        return get_current_screen()->id === self::get_edit_shop_order_screen();
    }

    /**
    * get hook for order bulk actions
    *
    * @return String
    */
    public static function get_hook_for_order_bulk_actions() {
        return 'bulk_actions-' . self::get_edit_shop_order_screen();
    }

    /**
    * get hook for order bulk actions
    *
    * @return String
    */
    public static function get_hook_for_order_handle_bulk_actions() {
        return 'handle_bulk_actions-' . self::get_edit_shop_order_screen();
    }

    /**
    * get hook for "manage_shop_order_posts_custom_column"
    *
    * @return String
    */
    public static function get_hook_manage_shop_order_custom_column() {
         $screen =  wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() 
                ? wc_get_page_screen_id( 'shop-order' ) 
                : 'shop_order_posts';
        return 'manage_' . $screen . '_custom_column';
    }
}
