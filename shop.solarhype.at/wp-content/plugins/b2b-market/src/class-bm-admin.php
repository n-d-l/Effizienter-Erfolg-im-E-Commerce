<?php

class BM_Admin {

	/**
	 * BM_Admin constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * hooks and includes for other classes
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'add_special_groups' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_assets' ) );

		/* admin bar role switcher */
		add_action( 'admin_bar_menu', array( $this, 'add_customer_group_admin_selector' ), 999 );
		add_action( 'wp_ajax_assign_customer_group', array( $this, 'assign_customer_group' ) );
		add_action( 'wp_ajax_nopriv_assign_customer_group', array( $this, 'assign_customer_group' ) );

		$this->marketpress_notices();

		if ( ! get_option( 'bm_upgraded_108' ) ) {
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'upgrade_scripts' ) );
			add_action( 'wp_ajax_dismiss_upgrade_notice', array( $this, 'dismiss_upgrade_notice' ) );
			add_action( 'wp_ajax_run_bm_update_migration', array( $this, 'handle_migration' ) );
		}

		add_action( 'bm_run_migration', array( $this, 'run_update_migration' ) );

		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'options' . DIRECTORY_SEPARATOR . 'class-bm-options.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'options' . DIRECTORY_SEPARATOR . 'class-bm-list-table.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-helper.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-user.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-conditionals.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-product-meta.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-variation-meta.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-whitelist.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-price.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-show-discounts.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-update-price.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-admin-orders.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-tax.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-public.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-shortcode.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-automatic-actions.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'hooks.php' );
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-compatibilities.php' );

		// Depricated classes.
		require_once( B2B_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'class-bm-live-price.php' );

		BM_Options::get_instance();
		BM_Price::get_instance();
		BM_Admin_Orders::get_instance();
		BM_Compatibilities::get_instance();

		$this->addons_init();

		add_action( 'before_delete_post', array( $this, 'delete_related_postmeta' ) );

		/* order admin columns */
		if ( 'on' === get_option( 'bm_show_groups_in_orders', 'on' ) ) {
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_customer_groups_column_header' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_customer_groups_column_content' ) );
		}

		add_action( 'woocommerce_bm_ui_update_options', function() {
			update_option( 'bm_all_options_saved', date( 'Y-m-d-H-i' ) );
		} );
	}

	/**
	 * init for addons
	 */
	public function addons_init() {
		$addons = array(
			'bm_addon_shipping_and_payment',
			'bm_addon_import_and_export',
			'bm_addon_registration',
			'bm_addon_slack',
			'bm_addon_quantities',
		);

		foreach ( $addons as $addon ) {

			if ( 'bm_addon_shipping_and_payment' == $addon ) {
				if ( get_option( $addon ) == 'on' ) {
					require_once( B2B_ADDON_PATH . 'conditional-shipping-payment' . DIRECTORY_SEPARATOR . 'class-csp.php' );
				}
			}
			if ( 'bm_addon_import_and_export' == $addon ) {
				if ( get_option( $addon ) == 'on' ) {
					require_once( B2B_ADDON_PATH . 'import-export' . DIRECTORY_SEPARATOR . 'class-ie.php' );
				}
			}
			if ( 'bm_addon_registration' == $addon ) {
				if ( get_option( $addon ) == 'on' ) {
					require_once( B2B_ADDON_PATH . 'registration' . DIRECTORY_SEPARATOR . 'class-rgn.php' );
				}
			}
			if ( 'bm_addon_quantities' == $addon ) {
				if ( get_option( $addon ) == 'on' ) {
					require_once( B2B_ADDON_PATH . 'min-max-quantities' . DIRECTORY_SEPARATOR . 'class-bm-quantities.php' );
				}
			}
			if ( 'bm_addon_slack' == $addon ) {
				if ( get_option( $addon ) == 'on' ) {
					require_once( B2B_ADDON_PATH . 'slack-connector' . DIRECTORY_SEPARATOR . 'slack-connector.php' );
				}
			}
		}
	}


	/**
	 * handler for enqueue admin scripts
	 */
	public function add_admin_assets() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';

		global $my_admin_page;
		$screen = get_current_screen();

		if ( apply_filters( 'bm_admin_screen_base', 'woocommerce_page_b2b-market' ) === $screen->base || 'post' === $screen->base && 'product' === $screen->post_type || is_plugin_active( 'woocommerce-branding/woocommerce-branding.php' ) ) {

			wp_enqueue_style( 'select-woo-css', B2B_PLUGIN_URL . '/assets/admin/selectWoo.min.css', BM::$version, 'all' );
			wp_enqueue_script( 'select-woo-js', B2B_PLUGIN_URL . '/assets/admin/selectWoo.full.min.js', array( 'jquery' ), BM::$version, true );
			wp_enqueue_style( 'bm-admin', B2B_PLUGIN_URL . '/assets/admin/bm-admin.' . $min . 'css', BM::$version, 'all' );

			$count_products  = wp_count_posts( 'product' );
			$group_admin_url = admin_url() . 'admin.php?page=b2b-market&tab=groups';

			if ( intval( $count_products->publish ) > apply_filters( 'bm_max_selectable_products_in_customer_group', 1000 ) ) {

				$autocomplete_data = array(
					'product_max'        => true,
					'categories'         => BM_Helper::get_available_categories(),
					'bulk_valid_message' => __( 'Please check the values for amount (from) and amount (to). There should be never the same value.', 'b2b-market' ),
					'admin_url'          => $group_admin_url,
					'nocache'            => get_option( 'bm_activate_no_cache' ),
					'ajax_url'           => admin_url( 'admin-ajax.php' ),
					'settings_saved'     => __( 'Your settings have been saved.', 'b2b-market' ),
				);
			} else {
				$autocomplete_data = array(
					'products'           => BM_Helper::get_available_products(),
					'categories'         => BM_Helper::get_available_categories(),
					'bulk_valid_message' => __( 'Please check the values for amount (from) and amount (to). There should be never the same value.', 'b2b-market' ),
					'admin_url'          => $group_admin_url,
					'nocache'            => get_option( 'bm_activate_no_cache' ),
					'ajax_url'           => admin_url( 'admin-ajax.php' ),
					'settings_saved'     => __( 'Your settings have been saved.', 'b2b-market' ),
				);
			}
		}

		if ( is_admin() ) {
			wp_enqueue_script( 'beefup', B2B_PLUGIN_URL . '/assets/admin/jquery.beefup.min.js', array( 'jquery' ), BM::$version, true );
			wp_enqueue_script( 'bm-admin', B2B_PLUGIN_URL . '/assets/admin/bm-admin.' . $min . 'js', array( 'jquery', 'beefup' ), BM::$version, true );
			wp_localize_script( 'bm-admin', 'bm_admin_js', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'bm-admin-nonce' ),
			) );
			if ( isset( $autocomplete_data ) ) {
				wp_localize_script( 'bm-admin', 'autocomplete_data', $autocomplete_data );
			}
		}

		if ( is_admin() && 'shop_order' === $screen->id ) {
			wp_enqueue_script( 'bm-admin-bar', B2B_PLUGIN_URL . '/assets/admin/bm-admin-bar.' . $min . 'js', array( 'jquery' ), BM::$version, true );
			wp_localize_script( 'bm-admin-bar', 'ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	/**
	 * register post type "customer_groups"
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Customer Groups', 'post type general name', 'b2b-market' ),
			'singular_name'      => _x( 'Customer Group', 'post type singular name', 'b2b-market' ),
			'menu_name'          => _x( 'Customer Groups', 'admin menu', 'b2b-market' ),
			'name_admin_bar'     => _x( 'Customer Group', 'add new on admin bar', 'b2b-market' ),
			'add_new'            => _x( 'Add New', 'b2b-market' ),
			'add_new_item'       => __( 'Add New Customer Group', 'b2b-market' ),
			'new_item'           => __( 'New Customer Group', 'b2b-market' ),
			'edit_item'          => __( 'Edit Customer Group', 'b2b-market' ),
			'view_item'          => __( 'View Customer Group', 'b2b-market' ),
			'all_items'          => __( 'All Customer Groups', 'b2b-market' ),
			'search_items'       => __( 'Search Customer Groups', 'b2b-market' ),
			'parent_item_colon'  => __( 'Parent Customer Group', 'b2b-market' ),
			'not_found'          => __( 'No Customer Groups found.', 'b2b-market' ),
			'not_found_in_trash' => __( 'No Customer Groups found in Trash.', 'b2b-market' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', 'b2b-market' ),
			'public'             => false,
			'show_in_rest'       => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'customer_groups' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'customer_groups', $args );
	}

	/**
	 * Add special groups initially
	 *
	 * @return void
	 */
	public function add_special_groups() {

		// expecting there is no special group.
		$guest_group    = get_post( get_option( 'bm_guest_group' ) );
		$customer_group = get_post( get_option( 'bm_customer_group' ) );

		if ( isset( $guest_group ) && ! empty( $guest_group ) && isset( $customer_group ) && ! empty( $customer_group ) ) {
			return;
		}

		$args = array(
			'post_type' => 'customer_groups',
		);

		$groups = get_posts( $args );

		foreach ( $groups as $group ) {

			$possible_guest_group_names    = array( 'Gast', 'Gäste', 'Guest', 'Guests', 'gast', 'gäste', 'guest', 'guests' );
			$possible_customer_group_names = array( 'Kunde', 'Kunden', 'Customer', 'Customers', 'customer', 'kunde', 'kunden', 'customers' );

			if ( in_array( $group->post_title, $possible_guest_group_names ) ) {
				$guest_group = true;
				update_option( 'bm_guest_group', $group->ID );
			}
			if ( in_array( $group->post_title, $possible_customer_group_names ) ) {
				$customer_group = true;
				update_option( 'bm_customer_group', $group->ID );
			}
		}

		if ( ! $guest_group ) {
			$args = array(
				'post_title'   => __( 'Guest', 'b2b-market' ),
				'post_name'    => 'guest',
				'post_type'    => 'customer_groups',
				'post_content' => '',
				'post_status'  => 'publish',
			);
			$guest = wp_insert_post( $args );
			update_option( 'bm_guest_group', $guest );
			update_post_meta( $guest, 'bm_all_products', 'on' );
		}
		if ( ! $customer_group ) {
			$args = array(
				'post_title'   => __( 'Customer', 'b2b-market' ),
				'post_name'    => 'customer',
				'post_type'    => 'customer_groups',
				'post_content' => '',
				'post_status'  => 'publish',
			);
			$customer = wp_insert_post( $args );
			update_option( 'bm_customer_group', $customer );
			update_post_meta( $customer, 'bm_all_products', 'on' );
		}

	}

	/**
	 * Add Admin notices, infos for other MarketPress products
	 *
	 * @wp-hook 	admin_notices
	 * @return 		void
	 */
	public function marketpress_notices() {

		if ( class_exists( 'WGM_Ui' ) || function_exists( 'atomion_setup' ) ) {
			return;
		}

		$first_run = get_option( 'bm_first_marketpress_notices', '' );
		if ( empty( $first_run ) ) {

			$first_run = time();
			update_option( 'bm_first_marketpress_notices', $first_run );
		}

		if ( intval( $first_run ) + 2 * HOUR_IN_SECONDS > time() ) {
			return;
		}

    	if ( function_exists( 'get_current_user_id' ) ) {
    		$user_id = get_current_user_id();
    		if ( 
    			( $user_id > 0 ) &&
    			( get_user_meta( $user_id, 'b2b_marketpress_notices_other_products', true ) !== '1.0' )
    		){

				add_action( 'admin_notices', array( $this, 'marketpress_notices_gm_and_atomion' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'backend_script_market_press_notices' ) );
				add_action( 'wp_ajax_b2b_dismiss_marketprss_notice', array( $this, 'backend_script_market_press_dismiss_notices' ) );

			}
		}
	}

	/**
	 * Add Admin notices German Market and B2B Market
	 *
	 * @wp-hook 	admin_notices
	 * @return 		void
	 */
	public function marketpress_notices_gm_and_atomion() {

		$gm_exists      = false;
		$atomion_exists = false;
		$salesman_exists	= false;

		$old_message_dismissed = get_option( 'b2b_marketpress_notice_gm_atomion', 'on' ) === '1.0';

		if ( ! function_exists( 'is_plugin_inactive' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_inactive( 'woocommerce-german-market/woocommerce-german-market.php' ) && ( is_dir( WP_PLUGIN_DIR . '/woocommerce-german-market' ) ) ) {
			$gm_exists = true;
		} elseif ( class_exists( 'Woocommerce_German_Market' ) ) {
			$gm_exists = true;
		}

		if ( is_dir( WP_CONTENT_DIR . '/themes/wordpress-theme-atomion' ) ) {
			$atomion_exists = true;
		} elseif ( function_exists( 'atomion_setup' ) ) {
			$atomion_exists = true;
		}

		$text = '';

		$domain = 'com';
		$lang = get_locale();

		if ( substr( $lang, 0, 2 ) == 'de' ) {
			$domain = 'de';
		}

		$atomion_link = '<a href="https://marketpress.' . $domain . '/shop/themes/wordpress-theme-atomion/?mp-notice-from=gm" target="_blank">Atomion</a>';
		$gm_link = '<a href="https://marketpress.' . $domain . '/shop/plugins/woocommerce/woocommerce-german-market/?mp-notice-from=atomion" target="_blank">German Market</a>';
		$salesman_link = '<a href="https://marketpress.' . $domain . '/shop/plugins/salesman/?mp-notice-from=atomion" target="_blank">Salesman</a>';

		if ( ! $salesman_exists ) {

			if ( 
				( ( ! $gm_exists ) && ( ! $atomion_exists ) && ( $old_message_dismissed ) ) ||
				( ( $gm_exists || $atomion_exists ) )
			){

				$text = sprintf( 
							__( 'You use our plugin <strong>B2B Market</strong>. That\'s great! Take a look at our new plugin <strong>%s</strong>, which combines features to increase customer loyalty, usability and traffic. Bonus points, recommendation programme and much more: selling has never been easier.', 'b2b-market' ), 
							$salesman_link
						 );

			} else {

				$text = sprintf( 
							__( 'You use our plugin <strong>B2B Market</strong>. That\'s great! Take a look at our plugins <strong>%s</strong> and <strong>%s</strong> as well as our theme <strong>%s</strong>, they fit perfectly.', 'b2b-market' ), 
							$salesman_link,
							$gm_link,
							$atomion_link
						 );

			}

		} else if ( ( ! $gm_exists ) && ( ! $atomion_exists ) ) {

			if ( ! $old_message_dismissed ) {
				$text = sprintf(
					__( 'You use our plugin <strong>B2B Market</strong>. That\'s great! Take a look at the plugin <strong>%s</strong> and the theme <strong>%s</strong>, they fit perfectly.', 'b2b-market' ),
					$gm_link,
					$atomion_link,
				);
			}
		}

		if ( ! empty( $text ) ) {
			?>
			<div class="notice notice-warning is-dismissible marketpress-atomion-gm-b2b-notice-in-b2b">
				<p><?php echo $text; ?></p>
			</div>
			<?php
		}
	}

	/**
	* Load JavaScript so you can dismiss the MarketPress Plugin Notice
	*
	* @wp-hook admin_enqueue_scripts
	* @return void
	*/
	public function backend_script_market_press_notices() {
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
		wp_enqueue_script( 'b2b-marketpress-notices', B2B_PLUGIN_URL . '/assets/admin/backend-marketpress-notices.' . $min . 'js', array( 'jquery' ), BM::$version );
	    wp_localize_script( 'b2b-marketpress-notices', 'b2b_marketpress_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce'	=> wp_create_nonce( 'b2b_marketpress_notices' ) ) );
	}

	/**
	* Dismiss MarketPress Notice
	*
	* @wp-hook wp_ajax_atomion_dismiss_marketprss_notice
	* @return void
	*/
	public function backend_script_market_press_dismiss_notices() {

		if ( check_ajax_referer( 'b2b_marketpress_notices', 'nonce' ) ) {

			update_option( 'b2b_marketpress_notice_gm_atomion', '1.0' );

			if ( function_exists( 'get_current_user_id' ) ) {
	    		$user_id = get_current_user_id();
	    		echo $user_id;
	    		if ( $user_id > 0 ) {
					update_user_meta( $user_id, 'b2b_marketpress_notices_other_products', '1.0' );
	    		}
	    	}

	    	 echo 'success';

	    } else {
	    	echo 'error';
	    }

	    exit();
	}

	/**
	 * Enqueue migrator scripts to submit upgrade for 1.0.8.3 via AJAX.
	 *
	 * @return void
	 */
	public function upgrade_scripts() {
		wp_enqueue_script( 'bm-upgrade', B2B_PLUGIN_URL . '/assets/admin/bm-upgrade.js', array( 'jquery' ), BM::$version );
		wp_localize_script( 'bm-upgrade', 'upgrader', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'spinner' => B2B_PLUGIN_URL . '/assets/admin/img/spinner.gif' ) );
	}


	/**
	 * Add Admin notices for B2B Market 1.0.8 migration.
	 *
	 * @return void
	 */
	public function upgrade_notice() {
		$upgraded          = get_option( 'bm_upgraded_108' );
		$upgrade_with_cron = get_option( 'bm_upgrade_108_with_cron' );

		if ( true == $upgrade_with_cron ) {
			$text = __( 'Scheduled the migration process with cron. The migration message will dissapear as soon as the migration is finished.', 'b2b-market' );
		} else {
			$text = sprintf(
				__( 'The update from B2B Market version 1.0.7 (or lower) to a higher version involves major changes in the structure and pricing. We strongly recommend testing the update and migration on a %s environment first and backing up the store beforehand. Since this process can take some time, we also recommend temporarily putting the store into maintenance mode until the migration is complete. If it is a new installation, you can simply close this message.', 'b2b-market' ),
				'<a href="https://marketpress.de/testumgebung-woocommerce/" target="_blank">Staging</a>'
			);
		}

		if ( ! $upgraded) {
			?>
			<div class="notice notice-warning is-dismissible b2b-upgrade-notice">
				<p>
					<?php echo $text; ?>
					<?php if ( ! $upgrade_with_cron ) : ?>
						<button class="button" style="margin-left: 10px;" id="b2b-run-migration"><?php esc_html_e( 'Run migration', 'b2b-market' ); ?></button>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}
	}


	/**
	 * Dismiss upgrade notice.
	 *
	 * @return void
	 */
	public function dismiss_upgrade_notice() {
		update_option( 'bm_upgraded_108', true );
		exit();
	}


	public function delete_related_postmeta( $postid ) {

		global $post_type;

		if ( $post_type != 'customer_groups' ) {
			return;
		}

		$group  = get_post( $postid );
		$prefix = 'bm_' . $group->post_name;

		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$prefix}%'" );
	}

	/**
	 * Add customer group column header
	 *
	 * @param  array $columns array of columns.
	 * @return array
	 */
	public function add_customer_groups_column_header( $columns ) {
		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {

			$new_columns[ $column_name ] = $column_info;

			if ( 'order_number' === $column_name ) {
				$new_columns['customer_group'] = __( 'Customer Group', 'b2b-market' );
			}
		}

		return $new_columns;
	}

	/**
	 * Add custom group column content
	 *
	 * @param array $column array of columns.
	 * @return void
	 */
	public function add_customer_groups_column_content( $column ) {
		global $post;
		global $wp_roles;

		if ( 'customer_group' === $column ) {
			$customer_id   = get_post_meta( $post->ID, '_customer_user', true );
			$customer_meta = get_userdata( $customer_id );

			if ( is_object( $customer_meta ) ) {
				$customer_groups = $customer_meta->roles;
				$all_roles       = $wp_roles->roles;

				if ( isset( $all_roles ) && ! empty( $all_roles ) ) {
					foreach ( $all_roles as $role_key => $role_details ) {
						if ( isset( $customer_groups[0] ) && ( $role_key === $customer_groups[0] ) ) {
							$current_role_name = $role_details['name'];
							echo esc_html( $current_role_name );
						}
					}
				}
			}
		}
	}

	/**
	 * Add customer selector to admin bar
	 *
	 * @return void
	 */
	public function add_customer_group_admin_selector() {
		if ( is_admin() ) {
			return;
		}
		$use_top_bar = apply_filters( 'bm_allow_topbar_selector', true );

		if ( ! $use_top_bar ) {
			return;
		}

		/* get current user role for highlighting */
		$current_user = new WP_User( get_current_user_id() );

		global $wp_admin_bar;

		/* check capabitlities */

		$wp_admin_bar->add_menu( array(
			'id'    => 'customer-groups',
			'title' => __( 'Switch Customer Group', 'b2b-market' ),
		) );

		/* get all customer groups and add them to main menu bar item */
		$customer_groups = get_posts( apply_filters( 'bm_switchable_customer_groups', array( 'posts_per_page' => -1, 'post_type' => 'customer_groups' ) ) );

		foreach ( $customer_groups as $group ) {

			if ( 'gast' !== $group->post_name && 'guest' !== $group->post_name ) {

				if ( in_array( $group->post_name, $current_user->roles ) ) {
					$wp_admin_bar->add_menu( array(
						'parent' => 'customer-groups',
						'meta'   => array( 'class' => 'bm-admin-bar-current-group' ),
						'id'     => $group->post_name,
						'title'  => $group->post_title,
						'href'   => '#' . $group->post_name,
					));
				} else {
					$wp_admin_bar->add_menu( array(
						'parent' => 'customer-groups',
						'id'     => $group->post_name,
						'title'  => $group->post_title,
						'href'   => '#' . $group->post_name,
					));
				}
			}
		}
		$wp_admin_bar->add_menu( array(
			'parent' => 'customer-groups',
			'id'     => 'no-group',
			'title'  => __( 'No Customer Group', 'b2b-market' ),
			'href'   => '#no-group',
		));
	}

	/**
	 * Assign customer groups via ajax
	 *
	 * @return void
	 */
	public function assign_customer_group() {

		$group_slug      = esc_html( $_POST[ 'group' ] );
		$current_user_id = get_current_user_id();
		$current_user    = new WP_User( $current_user_id );
		$customer_groups = get_posts( array(
				'posts_per_page' => -1,
				'post_type' => 'customer_groups' )
		);

		if ( in_array( $group_slug, $current_user->roles ) ) {
			return;
		}

		foreach ( $customer_groups as $group ) {
			$group_post_name = $group->post_name;
			if ( is_numeric( $group_post_name ) ) {
				$group_post_name = intval( $group_post_name );
			}
			if ( $group_slug == $group_post_name ) {
				$current_user->add_role( $group->post_name );
			} elseif ( 'no-group' === $group_slug ) {
				$current_user->remove_role( $group_post_name );
			} else {
				$current_user->remove_role( $group_post_name );
			}

		}

		// Refresh cached product prices transient.
		BM_Helper::force_regenerate_woocommerce_price_hashes();
	}

	/**
	 * Run B2B Market 1.0.8 migration.
	 *
	 * @return void
	 */
	public function run_update_migration() {
		$current_groups = BM_User::get_all_customer_group_ids();

		// Products.
		$product_args = array(
			'post_type'   => 'product',
			'numberposts' => -1,
			'fields'      => 'ids',
		);

		$products = get_posts( $product_args );

		foreach ( $products as $product_id ) {
			$product     = wc_get_product( $product_id );
			$is_variable = false;

			if ( 'variable' == $product->get_type() ) {
				$variations  = $product->get_children();
				$is_variable = true;
			}

			foreach ( $current_groups as $group_id ) {
				$group_object = get_post( $group_id );
				$group_slug   = $group_object->post_name . '_';

				// Is variable?
				if ( $is_variable ) {
					foreach ( $variations as $variation_id ) {
						foreach ( $current_groups as $group_id ) {
							$group_object = get_post( $group_id );
							$group_slug   = $group_object->post_name . '_';

							// Migrate group price fields.
							$group_price = array(
								'group_price'      => get_post_meta( $variation_id, 'bm_' . $group_slug . 'price', true ),
								'group_price_type' => get_post_meta( $variation_id, 'bm_' . $group_slug . 'price_type', true ),
							);

							if ( isset( $group_price['group_price'] ) && ! empty( $group_price['group_price'] ) ) {
								update_post_meta( $variation_id, 'bm_' . $group_slug . 'group_prices', array( $group_price ) );
								// Delete old group price fields.
								delete_post_meta( $variation_id, 'bm_' . $group_slug . 'price' );
								delete_post_meta( $variation_id, 'bm_' . $group_slug . 'price_type' );
							}

							error_log( 'Variation mit der ID ' . $variation_id . ' in Kundengruppe mit der ID ' . $group_id . ' migriert.' );
						}
					}

					// Delete old meta from variable product.
					delete_post_meta( $product->get_id(), 'bm_' . $group_slug . 'group_prices' );
					delete_post_meta( $product->get_id(), 'bm_' . $group_slug . 'bulk_prices' );
					delete_post_meta( $product->get_id(), 'bm_' . $group_slug . 'rrp' );
					delete_post_meta( $product->get_id(), 'bm_' . $group_slug . 'copy_for_group' );
					delete_post_meta( $product->get_id(), 'bm_' . $group_slug . 'bulk_copy_for_group' );
					delete_post_meta( $product->get_id(), '_min_' . $group_slug . 'price_saved' );
				} else {
					// Migrate simple products.
					$group_price = array(
						'group_price'      => get_post_meta( $product_id, 'bm_' . $group_slug . 'price', true ),
						'group_price_type' => get_post_meta( $product_id, 'bm_' . $group_slug . 'price_type', true ),
					);

					if ( isset( $group_price['group_price'] ) && ! empty( $group_price['group_price'] ) ) {
						update_post_meta( $product_id, 'bm_' . $group_slug . 'group_prices', array( $group_price ) );
						// Delete old group price fields.
						delete_post_meta( $product_id, 'bm_' . $group_slug . 'price' );
						delete_post_meta( $product_id, 'bm_' . $group_slug . 'price_type' );
					}
				}
			}

			error_log( 'Produkt mit der ID ' . $product_id . ' migriert.' );
		}

		// Migrate global prices.
		$global_price      = get_option( 'bm_global_base_price' );
		$global_price_type = get_option( 'bm_global_base_price_type' );

		if ( ! empty( $global_price ) && ! empty( $global_price_type ) ) {
			$global_prices = array(
				array(
					'group_price'      => $global_price,
					'group_price_type' => $global_price_type,
				)
			);
			update_option( 'bm_global_group_prices', $global_prices );
		}

		error_log( 'Globale Preise migriert.' );

		// Migrate customer groups.
		foreach ( $current_groups as $group_id ) {
			$group_object = get_post( $group_id );
			$group_slug   = $group_object->post_name . '_';

			// Check migration type.
			$all_products = get_post_meta( $group_id, 'bm_all_products', true );
			$group_cats   = explode( ',', get_post_meta( $group_id, 'bm_categories', true ) );
			$product_ids  = explode( ',', get_post_meta( $group_id, 'bm_products', true ) );

			if ( 'on' == $all_products ) {
				$group_price      = get_post_meta( $group_id, 'bm_price', true );
				$group_price_type = get_post_meta( $group_id, 'bm_price_type', true );
				$group_prices     = array();

				if ( ! empty( $group_price ) ) {
					$group_price_entry = array(
						'group_price'          => $group_price,
						'group_price_type'     => $group_price_type,
						'group_price_category' => '0'
					);

					$group_prices[] = $group_price_entry;
					update_post_meta( $group_id, 'bm_group_prices', $group_prices );
				}
			} elseif ( ! empty( $group_cats ) || ! empty( $product_ids ) ) {

				if ( ! empty( $group_cats ) ) {
					if ( '' == $group_cats[0] ) {
						unset( $group_cats[0] );
					}

					$group_price      = get_post_meta( $group_id, 'bm_price', true );
					$group_price_type = get_post_meta( $group_id, 'bm_price_type', true );
					$bulk_price       = get_post_meta( $group_id, 'bm_bulk_prices' );
					$group_prices     = array();
					$bulk_prices      = array();

					foreach ( $group_cats as $cat_id ) {

						if ( ! empty( $group_price ) ) {
							$group_price_entry = array(
								'group_price'          => $group_price,
								'group_price_type'     => $group_price_type,
								'group_price_category' => $cat_id
							);

							$group_prices[] = $group_price_entry;
							update_post_meta( $group_id, 'bm_group_prices', $group_prices );
						}
						if ( ! empty( $bulk_price ) ) {
							foreach ( $bulk_price as $price ) {
								foreach( $price as $value ) {
									$bulk_price_entry = array(
										'bulk_price'          => $value['bulk_price'],
										'bulk_price_from'     => $value['bulk_price_from'],
										'bulk_price_to'       => $value['bulk_price_to'],
										'bulk_price_type'     => $value['bulk_price_type'],
										'bulk_price_category' => $cat_id
									);
									$bulk_prices[] = $bulk_price_entry;
								}
							}
							update_post_meta( $group_id, 'bm_bulk_prices', $bulk_prices );
						}
						error_log( 'Gruppenpreise von Kategorie mit der ID ' . $cat_id . ' mit Daten aus Kundengruppe geupdatet.' );
					}
				}

				if ( ! empty( $product_ids ) ) {
					if ( '' == $product_ids[0] ) {
						unset( $product_ids[0] );
					}

					$group_price      = get_post_meta( $group_id, 'bm_price', true );
					$group_price_type = get_post_meta( $group_id, 'bm_price_type', true );

					foreach ( $product_ids as $product_id ) {

						$product = wc_get_product( $product_id );

						if ( ! ( is_object( $product ) && method_exists( $product, 'is_type' ) ) ) {
							continue;
						}

						if ( ! $product->is_type( 'variable' ) && ! $product->is_type( 'variation' ) ) {
							$new_group_prices = get_post_meta( $product_id, 'bm_' . $group_slug . 'group_prices', $group_prices );
							$new_bulk_prices  = get_post_meta( $product_id, 'bm_' . $group_slug . 'bulk_prices', $bulk_prices );

							if ( empty( $new_group_prices ) ) {
								$group_prices = array(
									array(
										'group_price'      => $group_price,
										'group_price_type' => $group_price_type,
									)
								);
								update_post_meta( $product_id, 'bm_' . $group_slug . 'group_prices', $group_prices );
								error_log( 'Gruppenpreise von Produkt mit der ID ' . $product_id . ' mit Daten aus Kundengruppe geupdatet.' );
							}

							if ( empty( $new_bulk_prices ) ) {
								$bulk_prices = array();

								foreach ( $bulk_price as $price ) {
									foreach ( $price as $value ) {
										$bulk_price_entry = array(
											'bulk_price'      => $value['bulk_price'],
											'bulk_price_from' => $value['bulk_price_from'],
											'bulk_price_to'   => $value['bulk_price_to'],
											'bulk_price_type' => $value['bulk_price_type'],
										);
										$bulk_prices[] = $bulk_price_entry;
									}
								}
								update_post_meta( $product_id, 'bm_' . $group_slug . 'bulk_prices', $bulk_prices );
								error_log( 'Staffelpreise von Produkt mit der ID ' . $product_id . ' mit Daten aus Kundengruppe geupdatet.' );
							}
						}
					}
				}
			}
		}

		// Update option for message.
		update_option( 'bm_upgraded_108', true );
		delete_option( 'bm_upgrade_108_with_cron' );
		wp_unschedule_event( current_time( 'mysql' ), 'bm_run_migration', array() );
		exit();
	}

	/**
	 * Create form config file with ajax or cron.
	 *
	 * @return void
	 */
	public function handle_migration() {

		if ( ! defined( 'DISABLE_WP_CRON' ) || 'DISABLE_WP_CRON' !== true ) {
			if ( ! wp_next_scheduled( 'bm_run_migration' ) ) {
				wp_schedule_single_event( time(), 'bm_run_migration' );
			}
			$response = array( 'success' => true, 'message' => __( 'Scheduled the migration process with cron. The migration message will dissapear as soon as the migration is finished.', 'b2b-market' ) );
			update_option( 'bm_upgrade_108_with_cron', true );
		} else {
			// Cron isn't available.
			$this->run_update_migration();
			$response = array( 'success' => true );
		}

		print wp_json_encode( $response );
		exit;
	}

}

new BM_Admin();
