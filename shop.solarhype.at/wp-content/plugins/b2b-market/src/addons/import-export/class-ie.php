<?php

if ( ! class_exists( 'IE' ) ) {
	/**
	 * Import/Export class
	 */
	class IE {

		/**
		 * Initialize migration addon
		 *
		 * @return void
		 */
		public static function init() {
			if ( self::is_active() === true ) {

				require_once( B2B_ADDON_PATH . 'import-export' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'class-ie-options.php' );
				require_once( B2B_ADDON_PATH . 'import-export' . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class-ie-exporter.php' );
				require_once( B2B_ADDON_PATH . 'import-export' . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class-ie-importer.php' );
				require_once( B2B_ADDON_PATH . 'import-export' . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class-ie-migrator.php' );

				$option_page = new IE_Options();

				add_filter( 'woocommerce_bm_ui_left_menu_items', array( $option_page, 'add_menu_item' ) );
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'exporter_scripts' ) );
			}
		}

		/**
		 * Enqueue scripts
		 *
		 * @return void
		 */
		public static function exporter_scripts() {

			wp_register_script( 'modal-js', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/easyModal.js', array( 'jquery' ), BM::$version, true );
			wp_register_script( 'export-js', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/admin.js', array( 'jquery' ), BM::$version, true );

			wp_enqueue_script( 'modal-js' );
			wp_enqueue_script( 'export-js' );

			wp_localize_script( 'export-js', 'exporter', array(
				'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				'nonce'                    => wp_create_nonce( 'start_export' ),
				'l18n_options'             => __( 'Plugin Settings', 'b2b-market' ),
				'l18n_select_all'          => __( 'Select all / Unselect all', 'b2b-market' ),
				'l18n_group'               => __( 'Group', 'b2b-market' ),
				'l18n_import_button_label' => __( 'Start Import', 'b2b-market' ),
				'l18n_upload_first'        => __( 'Please upload your import data first.', 'b2b-market' ),
				'l18n_json_error'          => __( 'Error occurred: Cannot read or convert import data.', 'b2b-market' ),
				'export_url'               => get_admin_url() . 'admin.php?page=b2b-market&tab=import_and_export&sub_tab=export',
				'import_url'               => get_admin_url() . 'admin.php?page=b2b-market&tab=import_and_export&sub_tab=import',
				'migrate_url'              => get_admin_url() . 'admin.php?page=b2b-market&tab=import_and_export&sub_tab=migrator',
			) );
		}

		/**
		 * Check status
		 *
		 * @return boolean
		 */
		private static function is_active() {
			$status = get_option( 'bm_addon_import_and_export' );

			if ( 'on' === $status ) {
				return true;
			}

			return false;

		}
	}
	IE::init();
}
