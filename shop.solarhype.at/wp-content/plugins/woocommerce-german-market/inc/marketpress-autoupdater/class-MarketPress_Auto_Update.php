<?php

/**
 * Class MarketPress_Auto_Update
 */
class MarketPress_Auto_Update_German_Market {

	/**
	 * @var null|MarketPress_Auto_Update
	 */
	private static $instance = NULL;

	/**
	 * Check if the plugin comes from marketpress
	 * dashboard
	 *
	 * @since	0.1
	 * @var		bool
	 */
	private $is_marketpress = FALSE;

	/**
	 * The URL for the update check
	 *
	 * @since	0.1
	 * @var		string
	 */
	public $url_update_check = '';
	private $update_check_response = '';

	/**
	 * The URL for the update package
	 *
	 * @since	0.1
	 * @var		string
	 */
	public $url_update_package = '';

	/**
	 * The holder for all our licenses
	 *
	 * @since	0.1
	 * @var		array
	 */
	public $licenses = '';

	/**
	 * The license key
	 *
	 * @since	0.1
	 * @var		array
	 */
	public $key = '';

	/**
	 * The URL for the key check
	 *
	 * @since	0.1
	 * @var		string
	 */
	public $url_key_check = '';

	/**
	 * @var StdClass
	 */
	private $plugin_data;

	/**
	 * Name of the plugin sanitized
	 *
	 * @var string
	 */
	private $sanitized_plugin_name;

	/**
	 * Activation Error
	 *
	 * @var string
	 */
	public $activation_error;
	public $page;
	public $menu_hook;
	public static $cache;

	/**
	 * @return Woocommerce_German_Market_Auto_Update
	 */
	public static function get_instance() {

		if ( self::$instance === NULL ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load textdomain
	 *
	 * @var string
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'marketpress-autoupdater', false,  plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Setting up some data, all vars and start the hooks
	 * needs from main plugin: plugin_name, plugin_base_name, plugin_url
	 *
	 * @param   stdClass $plugindata
	 *
	 * @return  void
	 */
	public function setup( $plugindata ) {

		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		self::load_textdomain();
		self::$cache = array();
		require_once ABSPATH . 'wp-includes/pluggable.php';

		$this->plugin_data = $plugindata;
		$this->sanitized_plugin_name = $plugindata->plugin_slug;
		$this->page = 'german-market';
		$this->menu_hook = 'woocommerce_de_ui_left_menu_items';
		
		// Get all our licenses
		$this->get_key();

		// Setting up the license checkup URL
		$phpversion = ( function_exists( 'phpversion' ) ) ? phpversion() : '0';
		// load WooCommerce version
		$wooversion = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		// load WordPress version
		$wpversion = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '0';
		// get current locale
		$locale = function_exists( 'get_locale' ) ? get_locale() : 'en_US';

		$parameter = array(
						$this->key,
						$this->sanitized_plugin_name,
						sanitize_title_with_dashes( network_site_url() ),
						$plugindata->version,
						$phpversion,
						$wpversion,
						$wooversion,
						$locale
					);

		$parameter_string = implode( '/', $parameter );

		// Setting up the license checkup URL
		$this->url_key_check = trailingslashit( 'https://marketpress.de/mp-key/' . $parameter_string );
		$this->url_update_check = trailingslashit( 'https://marketpress.de/mp-version/' . $parameter_string );
		$this->url_update_package = trailingslashit( 'https://marketpress.de/mp-download/' . $this->key . '/' . $this->sanitized_plugin_name . '/' . sanitize_title_with_dashes( network_site_url() ). '/' . $plugindata->version. '/' . $phpversion );

		// upgrade notice
		add_action( 'in_plugin_update_message-' . $this->plugin_data->plugin_base_name, array( $this, 'license_update_notice' ), 10, 2 );

		// provide plugin info
		add_filter( 'plugins_api', array( $this, 'provide_plugin_info' ), 10, 3);

		// add scheduled event for the key checkup
		add_filter( $this->plugin_data->shortcode . '_license_key_checkup', array( $this, 'license_key_checkup' ) );
		if ( ! wp_next_scheduled( $this->plugin_data->shortcode . '_license_key_checkup' ) )
			wp_schedule_event( time(), 'daily', $this->plugin_data->shortcode . '_license_key_checkup' );

		// Add Filter for the license check ( the cached state of the checkup )
		add_filter( $this->plugin_data->shortcode . '_license_check', array( $this, 'license_check' ) );

		// Version Checkup
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_version' ) );

		// add autoupdate css and js
		add_action( 'admin_print_styles-plugins.php', array( $this, 'print_styles_and_scripts' ) );

		// menu
		add_filter( $this->menu_hook, array( $this, 'add_menu' ), 9999 );

		// admin_notice
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		// WooCommerce Tested Up To
		add_filter( 'woocommerce_get_plugins_with_header', array( $this, 'woocommerce_tested_up_to' ) );
		add_filter( 'woocommerce_get_plugins_for_woocommerce', array( $this, 'woocommerce_tested_up_to' ) );

		// Update Error Message When License Expired
		add_filter( 'upgrader_pre_download', array( $this, 'update_error_message_license_expired' ), 10, 4 );

	}

	/**
	 * Update Error Message When License Expired
	 *
	 * @wp-hook upgrader_pre_download
	 * @param Mixed $reply
	 * @param Mixed $package
	 * @return Mixed
	 */
	public function update_error_message_license_expired( $reply, $package, $wp_upgrader, $hook_extra = array() ) {

		if ( empty( $package ) ) {
			if ( isset( $this->plugin_data->plugin_base_name ) ) {
				
				$plugin_base_name = $this->plugin_data->plugin_base_name;

				if ( is_array( $hook_extra ) && isset( $hook_extra[ 'plugin' ] ) && $hook_extra[ 'plugin' ] === $plugin_base_name ) {
					return new WP_Error( 'no_package', strip_tags( sprintf( __( 'The license has been %sexpired%s!', 'marketpress-autoupdater' ), '<span style="color: #f00;">', '</span>' ) ) );
				}
			}
		}

		return $reply;
	}

	/**
	 * Save News
	 *
	 * @param String $response
	 * @return void
	 */
	public function update_news( $response ) {
		
		if ( is_object( $response ) && isset( $response->news ) ) {
			
			$news = $response->news;
			
			if ( isset( $news->version ) ) {
				
				$news_version_api = $news->version;
				$news_version_wp = get_site_transient( 'german_market_news_version' );

				if ( empty( $news_version_wp ) ) {
					$news_version = '0.0';
				}
				
				if ( version_compare( $news_version_api, $news_version_wp, '>' ) ) {
					if ( isset( $news->the_news ) ) {
						set_site_transient( 'german_market_news', $news->the_news );
						set_site_transient( 'german_market_news_version', $news_version_api );
						delete_transient( 'german_market_hide_news' );
					}
				}
			}
		}
	}

	/**
	 * Get News and save news
	 *
	 * @param String $response
	 * @return void
	 */
	public function get_news_and_update_news() {

		$news = false;

		$remote = wp_remote_get( $this->url_update_check );

		if ( ! is_wp_error( $remote ) ) {
			$response = json_decode( wp_remote_retrieve_body( $remote ) );
			$this->update_news( $response );
			$news = get_site_transient( 'german_market_news' );
		}

		return $news;
	}

	/**
	 * Get "WC tested up to" by API
	 *
	 * @wp-hook woocommerce_get_plugins_with_header
	 * @wp-hook woocommerce_get_plugins_for_woocommerce
	 * @param Array $matches
	 * @param String $header
	 * @param Array $plugins
	 * @return Array
	 */
	function woocommerce_tested_up_to( $matches ) {
		
		if ( isset( Woocommerce_German_Market::$plugin_base_name ) && ( ! is_null( Woocommerce_German_Market::$plugin_base_name ) ) && isset( $matches[ Woocommerce_German_Market::$plugin_base_name ] ) ) {
			
			if ( isset( $matches[ Woocommerce_German_Market::$plugin_base_name ][ 'WC tested up to' ] ) ) {

				$wc_tested_up_to 		= get_site_transient( 'marketpress_wc_tested_up_to_' . $this->sanitized_plugin_name );
				$current_api_version  	= get_site_transient( 'marketpress_wc_tested_up_to_current_version_' . $this->sanitized_plugin_name );
				$current_plugin_version = $current_version = $this->plugin_data->version;

				if ( $wc_tested_up_to && $current_api_version && $current_plugin_version ) {

					// only if current plugin version is up to date (newest api version)
					if ( version_compare( $current_plugin_version, $current_api_version, '>=' ) ) {

						if ( version_compare( $matches[ Woocommerce_German_Market::$plugin_base_name ][ 'WC tested up to' ], $wc_tested_up_to, '<' ) ) {
							$matches[ Woocommerce_German_Market::$plugin_base_name ][ 'WC tested up to' ] = $wc_tested_up_to;
						}
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Save WC Tested Up To Data from API
	 *
	 * @param String $response
	 * @return void
	 */
	function update_wc_tested_up_to( $response ) {

		if ( is_object( $response ) && isset( $response->wc_tested_up_to ) && isset( $response->version ) ) {
			set_site_transient( 'marketpress_wc_tested_up_to_' . $this->sanitized_plugin_name, $response->wc_tested_up_to, 7 * DAY_IN_SECONDS );
			set_site_transient( 'marketpress_wc_tested_up_to_current_version_' . $this->sanitized_plugin_name, $response->version, 7 * DAY_IN_SECONDS );
		}
	}

	/**
	 * add admin notices for license errors and warnings
	 *
	 * @wp-hook admin_notices
	 * @return void
	 */
	public function admin_notice() {

		$error_message = get_site_transient( 'marketpress_license_error_message_' . $this->sanitized_plugin_name );
		
		if ( ! empty( $error_message ) ) {

			$check_page = trim( str_replace( 'woocommerce-', '', $this->sanitized_plugin_name ) );
			$is_plugin_backend = isset( $_REQUEST[ 'page' ] ) && str_replace( $check_page, '', $_REQUEST[ 'page' ] ) != $_REQUEST[ 'page' ];

			$show = false;

			if ( $is_plugin_backend ) {
				$show = ! ( isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'license' );
			} else {

				$screen = get_current_screen();

				if ( isset( $screen->id ) ) {
					if ( $screen->id == 'dashboard' ) {
						$show = true;
					}
				}

			}

			if ( $show ) {

				$plugin_name = trim( str_replace( 'WooCommerce,', '', $this->plugin_data->plugin_name ) );
				$link = untrailingslashit( get_admin_url() ) . '/admin.php?page=' . $this->page . '&tab=license';
				?>
				<div class="notice notice-error <?php echo $this->sanitized_plugin_name; ?>">
			        <p>
			        	<strong><?php echo $plugin_name; ?>:</strong> <?php echo $error_message; ?>
			        	<small><br><?php echo sprintf( __( 'The license has already been updated? Click %shere%s to check the license status and hide this message.', 'marketpress-autoupdater' ), '<a href="' . $link . '">', '</a>' ); ?></small>
			        </p>

			    </div>
			    <?php

			}
		
		} else {

			$access_expires_date = get_site_transient( 'marketpress_license_access_expires_' . $this->sanitized_plugin_name );

			if ( ! empty( $access_expires_date ) ) {

				$licence_expires_date = new DateTime( $access_expires_date );
				$today = new DateTime();
				$today->setTime( 23, 59, 59 );
				$licence_expires_date->setTime( 23,59,59 );
				
				$interval = $today->diff( $licence_expires_date );
				$days =  intval( $interval->format( '%R%a' ) );

				$message = '';

				if ( $days >= 0 && $days <= 30 ) {
					
					$class = 'notice notice-warning ' . $this->sanitized_plugin_name;

					$plugin_name = str_replace( 'WooCommerce', '', $this->plugin_data->plugin_name );

					if ( $days > 1 ) {
						$message = sprintf( __( 'Your %s licence is going to expire in %s days.', 'marketpress-autoupdater' ), '<strong>' . $plugin_name . '</strong>', $days );
					} else if ( $days == 1 ) {
						$message = sprintf( __( 'Your %s licence is going to expire in one day.', 'marketpress-autoupdater' ), '<strong>' . $plugin_name . '</strong>', $days );
					} else if ( $days == 0 ) {
						$message = sprintf( __( 'Your %s licence is going to expire today.', 'marketpress-autoupdater' ), '<strong>' . $plugin_name . '</strong>', $days );
					}
					
					$mp_url = 'https://marketpress.de';
		
					if ( $this->key ) {
						$is_en_key = substr( trim( $this->key ), 0 , 3 ) == 'EN-';
						if ( $is_en_key ) {
							$mp_url = 'https://marketpress.com';
						}
					}

					$mp_link = sprintf( '<a href="%s" target="_blank">%s</a>', $mp_url, $mp_url );

					$message .= '<br><span>' . sprintf( __( 'With an expired license you will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link )  . '</span>';
					

					$check_page = trim( str_replace( 'woocommerce-', '', $this->sanitized_plugin_name ) );
					$is_plugin_backend = isset( $_REQUEST[ 'page' ] ) && str_replace( $check_page, '', $_REQUEST[ 'page' ] ) != $_REQUEST[ 'page' ];

					$show = false;

					if ( $is_plugin_backend ) {
						$show = ! ( isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] == 'license' );
					} else {

						$screen = get_current_screen();

						if ( isset( $screen->id ) ) {
							if ( $screen->id == 'dashboard' ) {
								$show = true;
							}
						}

					}

					if ( $show ) {
						$link = untrailingslashit( get_admin_url() ) . '/admin.php?page=' . $this->page . '&tab=license';
						$small = '<br><small>' . sprintf( __( 'The license has already been updated? Click %shere%s to check the license status and hide this message.', 'marketpress-autoupdater' ), '<a href="' . $link . '">', '</a>' ) . '</small>';
						printf( '<div class="%1$s"><p>%2$s%3$s</p></div>', $class, $message, $small ); 
					}

				}

			}

		}

	}

	/**
	 * license menu in plugin
	 *
	 * @wp-hook woocommerce_de_ui_left_menu_items
	 * @param Array $menu
	 * @return menu
	 */
	public function add_menu( $menu ) {

		// delete key
		if ( isset( $_REQUEST[ 'delete-license-key' ] ) ) {
			if ( wp_verify_nonce( $_POST[ $this->page . '-delete-license-key-from-option' ], $this->page . '-delete-license-key' ) ) {
				$this->remove_license_key();
			}
		}

		// update key
		if ( isset( $_REQUEST[ 'activate-license-key' ] ) ) {
			if ( wp_verify_nonce( $_POST[ $this->page . '-delete-license-key-from-option' ], $this->page . '-delete-license-key' ) ) {
				
				$response = $this->get_license_key_checkup( trim( str_replace( ' ', '', $_REQUEST[ 'license-key' ] ) ) );

				if ( $response == 'true' ) {
					$this->reset_plugin_transient();
				} else if ( $response == 'licensekeynotfound' ) {
					$this->key = '';
					$this->activation_error = 'licensekeynotfound';
				} else if ( $response == 'noproducts' ) {
					$this->key = '';
					$this->activation_error = 'noproducts';
				} else if ( $response == 'expired' ) {
					$this->key = '';
					$this->activation_error = 'expired';
				} else if ( $response == 'urllimit' ) {
					$this->key = '';
					$this->activation_error = 'urllimit';
				}
	
			}
		}

		$this->get_key();
		$key_value = $this->key;

		if ( empty( $key_value ) ) {

			$is_staging = false;
			$site_url = get_site_url();
			$stagings = array( 'localhost', 'staging', 'development' );

			if ( str_replace( $stagings, '', $site_url ) != $site_url ) {
				$is_staging = true;
			}

			if ( ! $is_staging ) {
				$menu = array();
			}
		
		}

		$menu[ 1000 ] = array(
			'title'		=> __( 'License', 'marketpress-autoupdater' ),
			'slug'		=> 'license',
			'callback'	=> array( $this, 'license_menu' ),
		);

		return $menu;

	}

	/**
	 * license menu in plugin - callback function
	 *
	 * @return void
	 */
	public function license_menu() {

		$redirect = false;

		$is_wpconfig_key = false;

		if ( defined( 'MARKETPRESS_KEY' ) && MARKETPRESS_KEY != '' ) {
			$key_value 			= MARKETPRESS_KEY;
			$is_wpconfig_key 	= true;
		} else {
			$site_option 		= get_site_option( 'marketpress_licenses' );
			$key_value 			= isset( $site_option[ $this->sanitized_plugin_name . '_license' ] ) ? $site_option[ $this->sanitized_plugin_name . '_license' ] : '';
		}

		$key_length  	= strlen( $key_value );
		$star_length	= floor( $key_length * 0.8 );
		$stars 			= str_pad( '', $star_length, '*' );

		if ( ! empty( $this->activation_error ) ) {
			?>
			<div class="<?php echo $this->page; ?>-error-admin marketpress-error-admin" style="display: block;  margin-top: .7rem; width: 100%; float: left; box-sizing: border-box; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 1px 12px; border-left: 4px solid #dc3232; margin-bottom: .7rem;">
				<p>
					<?php 
					
					if ( $this->activation_error == 'licensekeynotfound' ) {
						echo $this->get_error_message_for_license( 'activation_licensekeynotfound' );
					} else if ( $this->activation_error == 'noproducts' ) {
						echo $this->get_error_message_for_license( 'activation_noproducts' );
					} else if ( $this->activation_error == 'expired' ) {
						echo $this->get_error_message_for_license( 'activation_expired' );
					} else if ( $this->activation_error == 'urllimit' ) {
						echo $this->get_error_message_for_license( 'activation_urllimit' );
					}

					?>

				</p>
			</div>
			<?php
		}

		?>

		<form method="post" action="<?php echo untrailingslashit( get_admin_url() ); ?>/admin.php?page=<?php echo $this->page; ?>&tab=license">
			
			<?php wp_nonce_field( $this->page . '-delete-license-key', $this->page . '-delete-license-key-from-option' ); ?>
			
			<table class="form-table">

				<tr>
					<th scope="row" style="vertical-align: top;">
						<label for="license-key"><?php echo __( 'License Key', 'marketpress-autoupdater' ); ?>:</label>
					</th>
					<td>
						
						<input type="text" name="license-key" id="license-key" value="<?php echo substr_replace( $key_value, $stars, 0, $star_length ); ?>" <?php echo empty( $key_value ) ? '' : 'disabled="disabled"'; ?> />
						
						<?php if ( ( ! $is_wpconfig_key ) && ( ! empty( $key_value ) ) )  { ?>
							
							<br /><br />
							<input type="submit" name="delete-license-key" value="<?php echo __( 'Delete License Key', 'marketpress-autoupdater' ); ?>" class="button-secondary">
						
						<?php } else if ( empty( $key_value ) ) { ?>
							
							<br /><br />
							<input type="submit" name="activate-license-key" value="<?php echo __( 'Activate License Key', 'marketpress-autoupdater' ); ?>" class="button-secondary">
						
						<?php } ?>

					</td>
				</tr>

				<?php 

				$access_expires = false;

				if ( ! empty( $key_value ) ) {
					
					$url_update_check = str_replace( 'mp-version//', 'mp-version/' . trim( $key_value ) . '/', $this->url_update_check );
					$remote = wp_remote_get( $url_update_check );
					$status = null;
					$access_expires = null;

					if ( is_wp_error( $remote ) ) {
						$status = 'wp-error';
					} else {

						$response = json_decode( wp_remote_retrieve_body( $remote ), true );

						if ( isset( $response[ 'status' ] ) ) {
							
							$status = $response[ 'status' ];

							if ( isset( $response[ 'version' ] ) ) {
								$this->set_current_product_version( $response[ 'version' ] );
							}

							if ( $status == 'true' ) {
								
								if ( isset( $response[ 'access_expires' ] ) ) {
									$access_expires = new DateTime( $response[ 'access_expires' ] );
									$this->set_access_expires_date( $response[ 'access_expires' ] );
								}
							}
						}

					}
				}

				?>

				<tr>
					<th scope="row" style="vertical-align: top;">
						<?php echo __( 'License Status', 'marketpress-autoupdater' ); ?>:
					</th>
					<td>
						<?php

						$mp_url = 'marketpress.de';

						if ( empty( $key_value ) ) {

							$msg  = sprintf( __( 'Enter a valid license key from %s below.', 'marketpress-autoupdater' ),
								'<a href="https://' . $mp_url . '" target="_blank">MarketPress</a>'
							);
							$msg .= sprintf( ' (<a href="https://%1$s/user-login/" target="_blank">%2$s</a>)',
								$mp_url,
								__( 'Help! I need to retrieve my key.', 'marketpress-autoupdater' )
							);

							echo $msg;

						} else if ( $status == 'true' ) {

								echo sprintf( __( '<strong>All is fine.</strong> You are using a valid license key from %s for this plugin.', 'marketpress-autoupdater' ),
							'<a href="https://' . $mp_url . '/" target="_blank">MarketPress</a>'
								);

								delete_site_transient( 'marketpress_license_error_message_' . $this->sanitized_plugin_name );
						
						} else {

							if ( isset( $response[ 'autoupdatestatus' ] ) ) {

								if ( $response[ 'autoupdatestatus' ] == 'expired' && isset( $response[ 'access_expires' ] ) ) {
									echo $this->get_error_message_for_license( 'expired', array( 'access_expires' => $response[ 'access_expires' ], 'key' => $key_value ) );
									$this->license_key_checkup_before_return_false( 'expired' );
								} else if ( $response[ 'autoupdatestatus' ] == 'licensekeynotfound' ) {
									echo $this->get_error_message_for_license( 'licensekeynotfound', array( 'key' => $key_value ) );
									$this->license_key_checkup_before_return_false( 'licensekeynotfound' );
								} else if ( $response[ 'autoupdatestatus' ] == 'noproducts' ) {
									echo $this->get_error_message_for_license( 'noproducts', array( 'key' => $key_value ) );
									$this->license_key_checkup_before_return_false( 'noproducts' );
								} else if ( $response[ 'autoupdatestatus' ] == 'urllimit' ) {
									echo $this->get_error_message_for_license( 'urllimit', array( 'key' => $key_value ) );
									$this->license_key_checkup_before_return_false( 'urllimit' );
								}

							} else {

								echo $this->get_error_message_for_license( 'unkownerror', array( 'key' => $key_value ) );

							}

						}

						?>

					</td>
				</tr>

				<?php 

					if ( $access_expires && $status == 'true' ) {

						?>
						<tr>
							<th scope="row" style="vertical-align: top;">
								<?php echo __( 'Access Expires', 'marketpress-autoupdater' ); ?>:
							</th>
							<td><?php echo date_i18n( get_option( 'date_format' ), $access_expires->getTimestamp() );?></td>
						</tr>
						<?php 
					}

				?>

			</table>
		</form>
		<?php

	}

	/**
	 * get license error messages
	 *
	 * @param String $status
	 * @param Array $args
	 * @return String
	 */
	public function get_error_message_for_license( $status, $args = array() ) {

		$message = '';

		$mp_url = 'https://marketpress.de';
		
		if ( isset( $args[ 'key' ] ) ) {
			$is_en_key = substr( trim( $args[ 'key' ] ), 0 , 3 ) == 'EN-';
			if ( $is_en_key ) {
				$mp_url = 'https://marketpress.com';
			}
		}

		$mp_link = sprintf( '<a href="%s" target="_blank">%s</a>', $mp_url, $mp_url );

		if ( $status == 'activation_licensekeynotfound' ) {

			$message 	= __( 'Your license could not be activated, the license key could not be found.', 'marketpress-autoupdater' );
			$message  	.= '<br>' . sprintf( __( 'Enter a valid license key from %s below.', 'marketpress-autoupdater' ), '<a href="https://' . $mp_url . '" target="_blank">MarketPress</a>' );
			$message 	.= sprintf( ' (<a href="%s/user-login/" target="_blank">%2$s</a>)', $mp_url, __( 'Help! I need to retrieve my key.', 'marketpress-autoupdater' ) );

		} else if ( $status == 'activation_noproducts' ) {

			$message 	= sprintf( __( 'Your license could not be activated, there is no valid license for %s for this key.', 'marketpress-autoupdater' ), '<strong>' . $this->plugin_data->plugin_name . '</strong>' );
			$message   .= '<br>' . sprintf( __( 'Please check your licenses at %s.', 'marketpress-autoupdater' ), '<a href="https://' . $mp_url . '" target="_blank">MarketPress</a>' );

		} else if ( $status == 'expired' ) {

			if ( isset( $args[ 'access_expires' ] ) ) { 
				$access_expires = new DateTime( $args[ 'access_expires' ] );
				$localized_time = date_i18n( get_option( 'date_format' ), $access_expires->getTimestamp() );

				$message = '<strong>' . sprintf( __( 'The license has been %sexpired%s since %s%s%s!', 'marketpress-autoupdater' ), '<span style="color: #f00;">', '</span>', '<span style="color: #f00;">', $localized_time, '</span>' ) . '</strong>';
				$message .= '<br><span style="color: #f00;">' . sprintf( __( 'With an expired license you will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link )  . '</span>';

			} else {

				$message = '<strong>' . sprintf( __( 'The license has been %sexpired%s!', 'marketpress-autoupdater' ), '<span style="color: #f00;">', '</span>' ) . '</strong>';
				$message .= '<br><span style="color: #f00;">' . sprintf( __( 'With an expired license you will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link )  . '</span>';

			}

		} else if ( $status == 'activation_expired' ) {
		
			$message = __( 'Your license could not be activated because it has already expired.', 'marketpress-autoupdater' );
			$message .= '<br>' . sprintf( __( 'Please visit %s to renew your license.', 'marketpress-autoupdater' ), $mp_link );
		
		} else if ( $status == 'activation_urllimit' ) {

			$message = __( 'Your license could not be activated because your domain limit has been reached.', 'marketpress-autoupdater' );
			$message .= '<br>' . sprintf( __( 'Please visit %s to manage the domains you use or to upgrade your license.', 'marketpress-autoupdater' ), $mp_link );

		} else if ( $status == 'licensekeynotfound' ) {

			$message = '<strong>' . __( 'Your license key could not be found.', 'marketpress-autoupdater' ) . '</strong>';
			$message .= '<br><span style="color: #f00;">' . sprintf ( __( 'You will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link ) . '<span>';

		} else if ( $status == 'noproducts' ) {

			$message 	= sprintf( __( 'There is no valid license for %s for the used license key.', 'marketpress-autoupdater' ), '<strong>' . $this->plugin_data->plugin_name . '</strong>' );
			$message   .= '<br><span style="color: #f00;">' . sprintf ( __( 'You will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link ) . '<span>';

		} else if ( $status == 'urllimit' ) {

			$message = '<strong>' . __( 'The domain limit has been reached for this license.', 'marketpress-autoupdater' ) . '</strong>';
			$message .= '<br><span style="color: #f00;">' . __( 'You will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . sprintf( __( 'The owner of the license should please visit %s to manage the domains or to upgrade the license.', 'marketpress-autoupdater' ), $mp_link ) . '</span>';
			
		} else if ( $status == 'unkownerror' ) {

			$message = '<strong>' . __( 'There is something wrong with this license.', 'marketpress-autoupdater' ) . '</strong>';
			$message .= '<br><span style="color: #f00;">' . __( 'You will not receive any updates or support.', 'marketpress-autoupdater' ) . '</span>';
			$message .= '<br>' . __( 'Please try again later to check the license status. If this error still exists, please contact our support team at "support@marketpress.com".', 'marketpress-autoupdater' );

		}
 
		if ( isset( $args[ 'strip_tags' ] ) ) {
			$message = strip_tags( $message , '<br>' );
		}

		return $message;

	}

	/**
	 * add css for autoupdate
	 *
	 * @uses	wp_enqueue_style, plugin_dir_url, untrailingslashit
	 */
	public function print_styles_and_scripts() {
		wp_enqueue_style( $this->plugin_data->shortcode. '-autoupdate', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/css/autoupdater.css' );
	}

	/**
	 * Setting up the key
	 *
	 * @since	0.1
	 * @uses	get_site_option
	 * @return	void
	 */
	public function get_key() {

		// Check if theres a key in the config
		if ( defined( 'MARKETPRESS_KEY' ) && MARKETPRESS_KEY != '' )
			$this->key = MARKETPRESS_KEY;

		// MarketPress Key
		if ( $this->key == '' && get_site_option( 'marketpress_license' ) != '' )
			$this->key = get_site_option( 'marketpress_license' );

		// Check if the plugin is valid
		$user_data = get_site_option( 'marketpress_user_data' );
		if ( isset( $user_data[ $this->sanitized_plugin_name ] ) && $user_data[ $this->sanitized_plugin_name ]->valid == 'false' ) {
			$this->key = '';
		} else if ( isset( $user_data[ $this->sanitized_plugin_name ] ) && $user_data[ $this->sanitized_plugin_name ]->valid == 'true' ) {
			$this->key = '';
			$this->is_marketpress = TRUE;
		}

		// Get all our licenses
		$this->licenses = get_site_option( 'marketpress_licenses', array() );
		if ( isset( $this->licenses[ $this->sanitized_plugin_name . '_license' ] ) ) {
			$this->key = $this->licenses[ $this->sanitized_plugin_name . '_license' ];
			$this->is_marketpress = FALSE;
		}
	}

	/**
	 * Checks over the transient-update-check for plugins if new version of
	 * this plugin os available and is it, shows a update-message into
	 * the backend and register the update package in the transient object
	 *
	 * @since	0.1
	 * @param	object $transient
	 * @uses	wp_remote_get, wp_remote_retrieve_body, get_site_option,
	 * 			get_site_transient, set_site_transient
	 * @return	object $transient
	 */
	public function check_plugin_version( $transient ) {

		if ( empty( $transient->checked ) ){
			return $transient;
		}

		if ( 'no' === get_option( 'gm_check_plugin_version', 'yes' ) ) {
			return $transient;
		}

		$response = $this->get_license_key_checkup();

		if ( $response != 'true' ) {
			
			if ( 'expired' === $response ) {

				$version = get_site_transient( 'marketpress_current_version_' . $this->sanitized_plugin_name );
				$current_version = $this->plugin_data->version;

				if ( ! empty( $version ) ) {
					if ( version_compare( $current_version, $version, '<' ) ) {
						$hashlist	= get_site_transient( 'update_hashlist' );
						if ( ! is_array( $hashlist ) ) {
							$hashlist = array();
						}
						$hash		= crc32( __FILE__ . $version );
						$hashlist[]	= $hash;
						set_site_transient( 'update_hashlist' , $hashlist );

						$info					= new stdClass();
						$info->url				= $this->plugin_data->plugin_url;
						$info->slug				= $this->plugin_data->plugin_slug;
						$info->plugin			= $this->plugin_data->plugin_base_name;
						$info->new_version		= $version;
						$info->package 			= null;
						$info->icons 			= array(
							'1x' => untrailingslashit( plugin_dir_url( Woocommerce_German_Market::$plugin_filename ) ) . '/images/germanmarketlogo.png',
						);

						$mp_url = 'https://marketpress.de';
		
						if ( $this->key ) {
							$is_en_key = substr( trim( $this->key ), 0 , 3 ) == 'EN-';
							if ( $is_en_key ) {
								$mp_url = 'https://marketpress.com';
							}
						}

						$mp_link = sprintf( '<a href="%s" target="_blank">%s</a>', $mp_url, $mp_url );

						$message = '<strong>' . sprintf( __( 'The license has been %sexpired%s!', 'marketpress-autoupdater' ), '<span style="color: #f00;">', '</span>' ) . '</strong>' . PHP_EOL;

						$message .= '<br><span>' . sprintf( __( 'With an expired license you will not receive any updates or support.', 'marketpress-autoupdater' ) . ' ' . __( 'The owner of the license should please visit %s and renew the license.', 'marketpress-autoupdater' ), $mp_link )  . '</span>';

						$info->upgrade_notice	= $message;

						$transient->response[ $this->plugin_data->plugin_base_name ] = $info;

						return $transient;
					}
				}
			}

			if ( isset( $transient->response[ $this->plugin_data->plugin_base_name ] ) ){
				unset( $transient->response[ $this->plugin_data->plugin_base_name ] );
			}
			
			return $transient;
		}

		// Connect to our remote host
		if ( empty( $this->update_check_response ) ) {
			$remote = wp_remote_get( $this->url_update_check );

			// If the remote is not reachable or any other errors occured,
			// we have to break up
			if ( is_wp_error( $remote ) ) {
				if ( isset( $transient->response[ $this->plugin_data->plugin_base_name ] ) )
					unset( $transient->response[ $this->plugin_data->plugin_base_name ] );

				return $transient;
			}

			$response = json_decode( wp_remote_retrieve_body( $remote ) );
			$this->update_wc_tested_up_to( $response );
			$this->update_news( $response );
			$this->update_check_response = $response;

		} else {

			$response = $this->update_check_response;

		}
		
		if ( $response->status != 'true' ) {
			if ( isset( $transient->response[ $this->plugin_data->plugin_base_name ] ) )
				unset( $transient->response[ $this->plugin_data->plugin_base_name ] );

			return $transient;
		}

		$version = $response->version;
		$current_version = $this->plugin_data->version;

		// Yup, insert the version
		if ( version_compare( $current_version, $version, '<' ) ) {
			$hashlist	= get_site_transient( 'update_hashlist' );
			if ( ! is_array( $hashlist ) ) {
				$hashlist = array();
			}
			$hash		= crc32( __FILE__ . $version );
			$hashlist[]	= $hash;
			set_site_transient( 'update_hashlist' , $hashlist );

			$upgrade_notice = '';
			if ( ! empty( $response->notice ) )
				$upgrade_notice = wp_kses_post( $response->notice );

			$info					= new stdClass();
			$info->url				= $this->plugin_data->plugin_url;
			$info->slug				= $this->plugin_data->plugin_slug;
			$info->plugin			= $this->plugin_data->plugin_base_name;
			$info->package			= $this->url_update_package;
			$info->new_version		= $version;
			$info->icons 			= array(
				'1x' => untrailingslashit( plugin_dir_url( Woocommerce_German_Market::$plugin_filename ) ) . '/images/germanmarketlogo.png',
			);

			if ( $upgrade_notice )
				$info->upgrade_notice	= $upgrade_notice;

			$transient->response[ $this->plugin_data->plugin_base_name ] = $info;

			return $transient;
		}

		// Always return a transient object
		if ( isset( $transient->response[ $this->plugin_data->plugin_base_name ] ) )
			unset( $transient->response[ $this->plugin_data->plugin_base_name ] );

		return $transient;
	}

	/**
	 * Disables the checkup
	 *
	 * @since	0.1
	 * @param	object $transient
	 * @return	object $transient
	 */
	public function dont_check_plugin_version( $transient ) {
		unset( $transient->response[ $this->plugin_data->plugin_base_name ] );
		return $transient;
	}

	/**
	 * Things to do when license check does not return status true
	 */
	public function license_key_checkup_before_return_false( $status = '' ) {

		delete_site_transient( 'marketpress_license_access_expires_' . $this->sanitized_plugin_name );

		update_site_option( 'marketpress_license_status_' . $this->sanitized_plugin_name, 'false' );
		$this->reset_plugin_transient();

		if ( ! empty( $status ) ) {

			$license_error_message = $this->get_error_message_for_license( $status );
			if ( ! empty( $license_error_message ) ) {
				set_site_transient( 'marketpress_license_error_message_' . $this->sanitized_plugin_name, $license_error_message, 2 * DAY_IN_SECONDS );
			}

		}

	}

	/**
	 * Reset Plugin Transients
	 */
	public function reset_plugin_transient() {

		$site_transient_update_plugins = get_site_transient( 'update_plugins' );
		$update_site_transient = false;

		if ( isset( $site_transient_update_plugins->response[ $this->plugin_data->plugin_base_name ] ) ) {			
			unset( $site_transient_update_plugins->response[ $this->plugin_data->plugin_base_name ] );
			$update_site_transient = true;
		}

		if ( isset( $site_transient_update_plugins->checked[ $this->plugin_data->plugin_base_name ] ) ) {			
			unset( $site_transient_update_plugins->checked[ $this->plugin_data->plugin_base_name ] );
			$update_site_transient = true;
		}

		if ( $update_site_transient ) {
			remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_version' ) );
			set_site_transient( 'update_plugins', $site_transient_update_plugins );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_version' ) );
		}

	}

	/**
	 * Set Access Expires Date
	 *
	 * @param String $access_expires_date
	 * @return void
	 */
	public function set_access_expires_date( $access_expires_date ) {
		set_site_transient( 'marketpress_license_access_expires_' . $this->sanitized_plugin_name, $access_expires_date );
	}

	/**
	 * Set Current Product Version from API
	 *
	 * @param String $version
	 * @return void
	 */
	public function set_current_product_version( $version ) {
		set_site_transient( 'marketpress_current_version_' . $this->sanitized_plugin_name, $version );
	}

	/**
	 * Check the license-key and cache the response
	 *
	 * @param 	String $key
	 * @return	String
	 */
	public function get_license_key_checkup( $key = '' ) {

		if ( empty( $key ) ) {
			$key = $this->key;
		}

		if ( ! empty( trim( $key ) ) ) {
			if ( isset( self::$cache[ 'license_key_checkup' ][ $key ] ) ) {
				return self::$cache[ 'license_key_checkup' ][ $key ];
			}
		}

		$response = $this->license_key_checkup( $key );
		
		if ( ! isset( self::$cache[ 'license_key_checkup' ] ) ) {
			self::$cache[ 'license_key_checkup' ] = array();
		}

		self::$cache[ 'license_key_checkup' ][ $key ] = $response;

		return $response;

	}

	/**
	 * Check the license-key 
	 *
	 * @param 	String $key
	 * @return	String
	 */
	public function license_key_checkup( $key = '' ) {

		// Request Key
		if ( $key != '' )
			$this->key = $key;

		// Check if there's a key
		if ( trim( $this->key ) == '' ) {
			// Deactivate Plugin first
			$this->license_key_checkup_before_return_false();
			return 'wrongkey';
		}

		// Update URL Key Checker
		$this->url_key_check = trailingslashit( 'https://marketpress.de/mp-key/' . $this->key . '/' . $this->sanitized_plugin_name . '/' . sanitize_title_with_dashes( network_site_url() ) );

		// Connect to our remote host
		$remote = wp_remote_get( $this->url_key_check );

		// If the remote is not reachable or any other errors occured,
		// we believe in the goodwill of the user and return true
		if ( is_wp_error( $remote ) ) {
			$this->licenses[ $this->sanitized_plugin_name . '_license' ] = $this->key;
			update_site_option( 'marketpress_licenses' , $this->licenses );
			update_site_option( 'marketpress_license_status_' . $this->sanitized_plugin_name, 'true' );
			return 'true';
		}

		// Okay, get the response
		$response = json_decode( wp_remote_retrieve_body( $remote ) );

		// Something not explainable happened
		if ( ! isset( $response ) || $response == '' ) {
			return 'true';
		}

		// Okay, get the response
		$response = json_decode( wp_remote_retrieve_body( $remote ) );

		if ( isset( $response->access_expires ) ) {
			$this->set_access_expires_date( $response->access_expires );
		}

		if ( isset( $response->version ) ) {
			$this->set_current_product_version( $response->version );
		}

		if ( isset( $response->autoupdatestatus ) ) {
			
			if ( $response->autoupdatestatus == 'expired' ) {
				$this->license_key_checkup_before_return_false( 'expired' );
				return 'expired';
			}

			if ( $response->autoupdatestatus == 'urllimit' ) {
				$this->license_key_checkup_before_return_false( 'urllimit' );
				return 'urllimit';
			}

			if ( $response->autoupdatestatus == 'licensekeynotfound' ) {
				$this->license_key_checkup_before_return_false( 'licensekeynotfound ');
				return 'licensekeynotfound';
			}

			if ( $response->autoupdatestatus == 'noproducts' ) {
				$this->license_key_checkup_before_return_false( 'noproducts' );
				return 'noproducts';
			}

		}

		$this->licenses[ $this->sanitized_plugin_name . '_license' ] = $this->key;
		update_site_option( 'marketpress_licenses' , $this->licenses );
		update_site_option( 'marketpress_license_status_' . $this->sanitized_plugin_name, 'true' );
		delete_site_transient( 'marketpress_license_error_message_' . $this->sanitized_plugin_name );
		delete_site_transient( 'marketpress_license_access_expires_' . $this->sanitized_plugin_name );

		if ( $response->status != 'true' ) {
			return 'unkownerror';
		}

		if ( $response->status == 'true' ) {
			return 'true';
		}

		exit;
	}

	/**
	 * Checks the cached state of the license checkup
	 *
	 * @since	0.1
	 * @uses	get_site_option
	 * @return	boolean
	 */
	public function license_check() {
		return get_site_option( 'marketpress_license_status_' . $this->sanitized_plugin_name );
	}

	/**
	 * Removes the plugins key from the licenses
	 *
	 * @since	0.1
	 * @uses	update_site_option, wp_safe_redirect, admin_url
	 * @return	void
	 */
	public function remove_license_key() {

		if ( isset( $this->licenses[ $this->sanitized_plugin_name . '_license' ] ) ) {
			unset( $this->licenses[ $this->sanitized_plugin_name . '_license' ] );
		}

		update_site_option( 'marketpress_licenses' , $this->licenses );

		$this->key = '';

		// Renew License Check
		$this->get_license_key_checkup();

	}

	/**
	 * Display the upgrade notice in the plugin listing
     */
	public function license_update_notice( $plugin_data, $response ) {

		$upgrade = isset( $plugin_data[ 'update' ] ) && $plugin_data[ 'update' ] == TRUE ? ' update' : '';

		if ( ! empty( $plugin_data[ 'upgrade_notice' ] ) && ! empty( $upgrade ) ) :
			echo '</p>';
			echo '<hr class="marketpress-update-notice">';
			echo '<p class="dummy marketpress-update-notice">' . $plugin_data[ 'upgrade_notice' ];
		endif;
	}

	/**
	 * Show Update Details
	 *
	 * @wp-hook plugins_api
	 * @param StdObject $data
	 * @param String $action
	 * @param Array $args
	 * @return StdObject
	 */
	public function provide_plugin_info( $data, $action = null, $args = null ) {

		if ( $action != 'plugin_information' OR empty( $args->slug ) OR $args->slug !== $this->sanitized_plugin_name ) {
            return $data;
        }

		$data = new StdClass;
        $data->author        = '<a href="https://marketpress.de" target="_blank">MarketPress</a>';

        if ( empty( $this->update_check_response ) ) {
			
			$remote = wp_remote_get( $this->url_update_check );

			if ( ! is_wp_error( $remote ) ) {
				
				$response = json_decode( wp_remote_retrieve_body( $remote ), true );
				$this->update_wc_tested_up_to( $response );
				$this->update_news( $response );
				$this->update_check_response = $response;

			} else {

				$response = array();
			}	

		} else {

			$response = $this->update_check_response;

		}

		if ( ! is_array( $response ) ) {
			$response = json_decode( json_encode( $response ), true );
		}
		
		if ( isset( $response[ 'product_name' ] ) ) {
        	$data->name = $response[ 'product_name' ];
        	$data->slug = sanitize_title( $data->name );
        }

        $data->sections      = array();

        if ( isset( $response[ 'changelog' ] ) ) {
        	$data->sections      = array( 'Changelog' => nl2br( $response[ 'changelog' ] ) );
        }

        if ( isset( $response[ 'last_updated' ] ) ) {
        	$data->last_updated  = $response[ 'last_updated' ];
        }

        if ( isset( $response[ 'wordpress_required' ] ) ) {
        	$data->requires  = $response[ 'wordpress_required' ];
        }

        if ( isset( $response[ 'wordpress_tested_up_to' ] ) ) {
        	$data->tested  = $response[ 'wordpress_tested_up_to' ];
        }

        if ( isset( $response[ 'version' ] ) ) {
        	$data->version  = $response[ 'version' ];
        }

        if ( isset( $response[ 'header_image' ] ) ) {
        	$data->banners = array();
       		$data->banners[ 'high' ] = $response[ 'header_image' ] ;
        }

        if ( isset( $response[ 'product_url' ] ) ) {
        	$data->homepage  = $response[ 'product_url' ];
        }

		return $data;

	}

}
