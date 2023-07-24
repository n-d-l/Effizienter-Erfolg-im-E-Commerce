<?php
/**
 * Backend Functions
 *
 * @author MarketPress
 * @since WGM 3.0
 */
Class WGM_Backend {

	/**
	 * Add support link to plugin row meta
	 *
	 * @access public
	 * @static
	 * @wphook plugin_row_meta
	 * @param Array $links
	 * @param String $file
	 * @return void
	 */
	public static function plugin_row_meta( $links, $file ) {

		if ( strpos( $file, 'WooCommerce-German-Market.php' ) !== false ) {
			
			$links[ 'documentation' ] 	= sprintf( '<a href="%s" target="_blank">%s</a>', 'https://marketpress.de/dokumentation/german-market/', 											__( 'Documentation', 'woocommerce-german-market' ) );
			$links[ 'videos' ] 			= sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.youtube.com/watch?v=spYbS4MACzI&list=PLnf1BSfzpccGlCI6bQdjfbQE42CoKACe7&index=2/', 	__( 'Tutorial videos', 'woocommerce-german-market' ) );
			$links[ 'support' ] 		= sprintf( '<a href="%s" target="_blank">%s</a>', 'https://marketpress.de/hilfe/', 																	__( 'Support', 'woocommerce-german-market' ) );

		}
	
		return $links;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @since 3.11
	 * @access public
	 * @static
	 * @param mixed $links Plugin Action links.
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=german-market' ) . '">' . esc_html__( 'Settings', 'woocommerce-german-market' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Show WooCommerce Actions in Order Table List
	 *
	 * @access public
	 * @static
	 * @since 3.5.5
	 * @wphook default_hidden_columns
	 * @param Array $hidden
	 * @param WP_Screen $screen
	 * @param String $file
	 * @return Array
	 */
	public static function default_hidden_columns ( $hidden, $screen ) {

		if ( $screen->id == 'edit-shop_order' ) {
			$array_key_wc_actions = array_search( 'wc_actions', $hidden );
			
			if ( $array_key_wc_actions ) {
				unset( $hidden[ $array_key_wc_actions ] );
			}

		}

		return $hidden;

	}

}
