<?php

/**
 * Class WGM_Ui
 *
 * German Market Userinterface
 *
 * @author MarketPress
 */
class WGM_Ui {

	/**
	 * @var WGM_Ui
	 */
	private static $instance = null;

	/**
	 * @var String
	 */
	private $current_screen_id = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Ui
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Ui();
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {

		// add submenu
		add_action( 'admin_menu', array( $this, 'add_german_market_submenu' ), 51 );

		add_filter( 'woocommerce_screen_ids', array( $this, 'screen_ids' ) );

		// enqueue woocommerce admin styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// our checkbox
		add_action( 'woocommerce_admin_field_wgm_ui_checkbox', array( $this, 'wgm_ui_checkbox' ) );

		// save checkbox correct so we can use of WGM <= 3.0
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'woocommerce_admin_settings_sanitize_option' ), 10, 3 );

		add_action( 'woocommerce_admin_field_german_market_textarea', array( $this, 'output_textarea' ) );

		// let other add actions or remove our actions
		do_action( 'wgm_ui_after_actions', $this );

		// reinstall required pages
		add_action( 'woocommerce_de_ui_update_options', array( $this, 'reinstall_required_pages' ) );

		// reinstall measuring units
		add_action( 'woocommerce_de_ui_update_options', array( $this, 'reinstall_measuring_units' ) );

		// ajax for hiding pro version infos
		add_action( 'wp_ajax_german_market_news', array( $this, 'hide_news' ) );

	}

	/**
	* Ajax for showing pro version infos
	* 
	* @wp-hook wp_ajax_german_market_news
	* @access public
	* @return void
	*/
	public function hide_news() {
		
		if ( check_ajax_referer( 'german_market_news', 'nonce' ) ) {
			$show_news = 'yes' !== get_transient( 'german_market_hide_news' );
			
			if ( $show_news ) {
				set_transient( 'german_market_hide_news', 'yes' );
			} else {
				delete_transient( 'german_market_hide_news' );
			}

			echo 'success';
		} else {
			echo 'error';
		}

		exit();
	}

	/**
	* Reinstall Measuring Units
	*
	* @wp-hook woocommerce_de_ui_update_options
	* @access public
	* @param Array $options
	* @return void
	*/
	public function reinstall_measuring_units( $options ) {

		if ( isset( $_REQUEST[ 'woocommerce_de_reinstall_measuring_units' ] ) ) {

			WGM_Helper::load_installation_language_textdomain();

			WGM_Installation::install_default_attributes();
			delete_option( 'woocommerce_de_reinstall_measuring_units' );

			WGM_Helper::reset_textdomain();
			?>
			<div class="notice-wgm notice-success">
		        <p><?php echo __( 'Measuring units have been reinstalled.', 'woocommerce-german-market' ); ?></p>
		    </div>
		    <?php
		}
	}

	/**
	* Reinstall Required Pages
	*
	* @wp-hook woocommerce_de_ui_update_options
	* @access public
	* @param Array $options
	* @return void
	*/
	public function reinstall_required_pages( $options ) {

		if ( isset( $_REQUEST[ 'woocommerce_de_reinstall_required_pages' ] ) ) {

			if ( $_REQUEST[ 'woocommerce_de_reinstall_required_pages' ] != '0' ) {

				WGM_Helper::load_installation_language_textdomain();

				$lang = WGM_Helper::get_installation_language();

				if ( $_REQUEST[ 'woocommerce_de_reinstall_required_pages' ] == '1' ) {
					WGM_Installation::install_default_pages( true, $lang );

				} else if ( $_REQUEST[ 'woocommerce_de_reinstall_required_pages' ] == '2' ) {
					WGM_Installation::install_default_pages( false, $lang );
				}

				WGM_Helper::reset_textdomain();

				?>
				<div class="notice-wgm notice-success">
			        <p><?php echo __( 'Required pages have been reinstalled.', 'woocommerce-german-market' ); ?></p>
			    </div>
			    <?php

			}

			delete_option( 'woocommerce_de_reinstall_required_pages' );

		}
	}

	/**
	* Add German Market Screens to WooCommerce Screens
	*
	* @wp-hook woocommerce_screen_ids
	* @access public
	* @param Array $screen_ids
	* @return Arrray
	*/
	public function screen_ids( $screen_ids ) {
			$screen_ids[] = apply_filters( 'german_market_screen_id_slug', 'woocommerce_page_german-market' );
			$screen_ids[] = 'woocommerce_page_wgm-refunds';
			return $screen_ids;
	}

	/**
	* Add submenu
	*
	* @wp-hook admin_enqueue_scripts
	* @access public
	* @return void
	*/
	public function enqueue_scripts_and_styles() {

		// load only for German Market Backend Menu
		$current_screen = get_current_screen();

		if ( $current_screen->id == $this->current_screen_id ) {

			$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
			$suffix = $script_debug ? '' : '.min';
			do_action( 'woocommerce_settings_start' );

			//wp_enqueue_script( 'woocommerce_settings', WC()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'selectWoo' ), WC()->version, true );

			wp_localize_script(
				'woocommerce_settings', 'woocommerce_settings_params', array(
					'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce-german-market' ),
					'i18n_moved_up'    => __( 'Item moved up', 'woocommerce-german-market' ),
					'i18n_moved_down'  => __( 'Item moved down', 'woocommerce-german-market' ),
				)
			);

			// Media Uploader
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );

			// German Market styles
			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : 'min.';
			wp_enqueue_style( 'woocommerce_de_admin', plugins_url( '/css/backend.' . $min . 'css', Woocommerce_German_Market::$plugin_base_name ), array(), Woocommerce_German_Market::$version );


		}
	}

	/**
	* Add submenu
	*
	* @wp-hook admin_menu
	* @access public
	*/
	public function add_german_market_submenu() {

		$submenu_page = add_submenu_page(
			'woocommerce',
			__( 'German Market', 'woocommerce-german-market' ),
			__( 'German Market', 'woocommerce-german-market' ),
			apply_filters( 'wgm_ui_capability', 'manage_woocommerce' ),
			'german-market',
			array( $this, 'render_german_market_menu' )
		);

		$this->current_screen_id = $submenu_page;

	}

	/**
	* Output type wgm_ui_checkbox
	*
	* @access public
	* @hook woocommerce_admin_field_wgm_ui_checkbox
	* @return void
	*/
	public function wgm_ui_checkbox( $value ){

		$option_value    = WC_Admin_Settings::get_option( $value['id'], $value['default'] );

		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $value );
		extract( $field_description );

		$visbility_class = array();

		if ( ! isset( $value['hide_if_checked'] ) ) {
			$value['hide_if_checked'] = false;
		}
		if ( ! isset( $value['show_if_checked'] ) ) {
			$value['show_if_checked'] = false;
		}
		if ( 'yes' == $value['hide_if_checked'] || 'yes' == $value['show_if_checked'] ) {
			$visbility_class[] = 'hidden_option';
		}
		if ( 'option' == $value['hide_if_checked'] ) {
			$visbility_class[] = 'hide_options_if_checked';
		}
		if ( 'option' == $value['show_if_checked'] ) {
			$visbility_class[] = 'show_options_if_checked';
		}

		?>
			<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
				<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?><?php echo $tooltip_html; ?></th>
				<td class="forminp forminp-checkbox">
					<fieldset>
		<?php

		if ( ! empty( $value['title'] ) ) {
			?>
				<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
			<?php
		}

		?>

			<label class="switch" for="<?php echo $value['id'] ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="checkbox"
					class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
					value="on"
					<?php
						if ( $value[ 'id' ] == 'wp_wc_invoice_pdf_inline_style' ) {
							checked( $option_value, true );
						} else if ( $value[ 'id' ] == 'wp_wc_invoice_pdf_remove_css_style' ) {
							if ( $option_value == 0 || $option_value == '0' ) {
								$option_value = false;
							}
							checked( $option_value, false );
						} else if ( $value[ 'id' ] == 'wp_wc_invoice_pdf_force_html_output' ) {
							checked( $option_value, 'yes' );
						} else if ( $value[ 'id' ] == 'wp_wc_running_invoice_number_multisite_global' ) {
							checked( $option_value, 'yes' );
						} else {
							checked( $option_value, 'on' );
						}

						if ( isset( $value[ 'custom_attributes' ][ 'disabled' ] ) ) {
							?>disabled<?php
						}
					?>

				/>
				<div class="slider round gm-slider"></div>

			</label>

			<?php
				$off_active = $option_value == 'off' ? 'active' : 'clickable';
				$on_active  = $option_value == 'on' ? 'active' : 'clickable';
			?>
			<p class="screen-reader-buttons">
				<span class="gm-ui-checkbox switcher-german-market off <?php echo $off_active; ?>"><?php echo __( 'Off', 'woocommerce-german-market' ); ?></span>
				<span class="gm-ui-checkbox delimter">|</span>
				<span class="gm-ui-checkbox switcher-german-market on <?php echo $on_active; ?>"><?php echo __( 'On', 'woocommerce-german-market' ); ?></span>
			</p>

			<?php
				if ( isset( $value[ 'desc' ] ) && $value[ 'desc' ] != '' ) {
					?><br /><span class="description"><?php echo $value[ 'desc' ]; ?></span><?php
				}
			?>
		<?php

		if ( ! isset( $value['checkboxgroup'] ) || 'end' == $value['checkboxgroup'] ) {
						?>
						</fieldset>
					</td>
				</tr>
			<?php
		} else {
			?>
				</fieldset>
			<?php
		}

	}

	/**
	* Save type wgm_ui_checkbox
	*
	* @access public
	* @hook woocommerce_admin_settings_sanitize_option
	* @param Mixed $value
	* @param Array $option
	* @param Mixed $raw_value
	* @return $value
	*/
	public function woocommerce_admin_settings_sanitize_option( $value, $option, $raw_value ) {

		// if it is our checkbox type
		if ( $option[ 'type' ] == 'wgm_ui_checkbox' ) {

			// invoice-pdf => compatible with WGM code <= 3.0
			if ( $option[ 'id' ] == 'wp_wc_invoice_pdf_inline_style' ) {
				$value = is_null( $raw_value ) ? false : true;
			} else if ( $option[ 'id' ] == 'wp_wc_invoice_pdf_remove_css_style' ) {
				$value = is_null( $raw_value ) ? true : false;
			} else if ( $option[ 'id' ] == 'wp_wc_invoice_pdf_force_html_output' ) {
				$value = is_null( $raw_value ) ? 'no' : 'yes';
			} else {
				// save as "on" or "off" to be compatible with WGM code <= 3.0
				$value = is_null( $raw_value ) ? 'off' : 'on';
			}

		} else if ( $option[ 'type' ] == 'german_market_textarea' ) {

			$value = html_entity_decode( wp_kses_post( trim( $raw_value ) ) );
		}

		return $value;
	}

	/**
	* Get left menu items
	*
	* @access private
	* @return array
	*/
	private function get_left_menu_items() {

		$german_market = array(
			'title'		=> __( 'General', 'woocommerce-german-market' ),
			'slug'		=> 'general',
			'submenu'	=> array(

				array(
					'title'		=> __( 'Required Pages', 'woocommerce-german-market' ),
					'slug'		=> 'required-pages',
					'callback'	=> array( $this, 'required_pages' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Revocation Policy', 'woocommerce-german-market' ),
					'slug'		=> 'revocation-policy',
					'callback'	=> array( $this, 'revocation_policy' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Templates For Legal Texts', 'woocommerce-german-market' ),
					'slug'		=> 'legal-text-templates',
					'callback'	=> array( $this, 'legal_text_templates' ),
					'options'	=> 'no'
				),

				array(
					'title'		=> __( 'Small Trading Exemption', 'woocommerce-german-market' ),
					'slug'		=> 'small-trading-exemption',
					'callback'	=> array( $this, 'small_trading_exemption' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Delivery Times', 'woocommerce-german-market' ),
					'slug'		=> 'delivery-times',
					'callback'	=> array( $this, 'delivery_times' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Sale Labels', 'woocommerce-german-market' ),
					'slug'		=> 'sale-labels',
					'callback'	=> array( $this, 'sale_labels' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Products', 'woocommerce-german-market' ),
					'slug'		=> 'products',
					'callback'	=> array( $this, 'products' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Ordering', 'woocommerce-german-market' ),
					'slug'		=> 'cart_and_checkout',
					'callback'	=> array( $this, 'cart_and_checkout' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Checkout Checkboxes', 'woocommerce-german-market' ),
					'slug'		=> 'checkout_checkboxes',
					'callback'	=> array( $this, 'checkout_checkboxes' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Global Options', 'woocommerce-german-market' ),
					'slug'		=> 'global',
					'callback'	=> array( $this, 'global_tab' ),
					'options'	=> 'yes'
				),

				array(
					'title'		=> __( 'Emails', 'woocommerce-german-market' ),
					'slug'		=> 'emails',
					'callback'	=> array( $this, 'emails' ),
					'options'	=> 'yes'
				),

			)
		);

		$german_market = apply_filters( 'woocommerce_de_ui_menu_german_market', $german_market );

		$add_ons = array(
				'title'		=> __( 'Add-ons', 'woocommerce-german-market' ),
				'slug'		=> 'add-ons',
				'new'		=> 'yes',
				'callback'	=> array( $this, 'render_add_ons' )
		);

		$add_ons = apply_filters( 'woocommerce_de_ui_menu_add_ons', $add_ons );

		$items = array(
			0 	=> $german_market,
			500 => $add_ons,
		);

		$items = apply_filters( 'woocommerce_de_ui_left_menu_items', $items );
		ksort( $items );
		return $items;
	}

	/**
	* Add Submenu to WooCommerce Menu
	*
	* @add_submenu_page
	* @access public
	*/
	public function render_german_market_menu() {

		do_action( 'render_german_market_menu_save_options' );

		?>
		<div class="wrap">

			<div class='german-market'>

				<?php
					$news = get_site_transient( 'german_market_news' );

					if ( ! $news ) {
						$has_news_check = get_site_transient( 'german_market_news_check' );
						if ( ! $has_news_check ) {
							$autoupdater = Woocommerce_German_Market::$autoupdater;
							$news = $autoupdater->get_news_and_update_news();
							set_site_transient( 'german_market_news_check', 'yes', HOUR_IN_SECONDS );
						}
					}

					if ( $news ) { ?>
						
						<div class="german-market-news">

							<div class="wrap german-market-notice-wrap german-market-news-heading">
								
								<div class="wp-heading-inline german-market-heading-title">News</div>
								 <div class="icon">
		                        	<?php
		                        		$show_news 		= 'yes' !== get_transient( 'german_market_hide_news' );
		                        		$style_for_show = $show_news ? ' style="display: none;"' : '';
		                        		$style_for_hide = $show_news ? '' : ' style="display: none;"';
		                        	?>
		                        	<span class="show dashicons dashicons-arrow-down-alt2"<?php echo $style_for_show; ?>></span>
		                            <span class="hide dashicons dashicons-no-alt"<?php echo $style_for_hide; ?>></span>
		                        </div>
							</div>

		                    <div class="german-market-news-inner"<?php echo $style_for_hide; ?>>
		                        <div class="german-market-the-news elements-count-<?php echo( count( get_object_vars( $news ) ) ); ?>">
			                    	<?php 
			                    	$i = 0;
			                    	foreach ( $news as $news_element ) {

			                    		$news_date 			= $news_element->date;
			                    		$news_date_obj 		= new DateTime( $news_date );
			                    		$news_date_format 	= $news_date_obj->format( 'd.m.Y' );
			                    		$i++;
			                    		?>
			                    		<div class="german-market-news-element element-<?php echo $i; ?>">
			                    			<div class="german-market-headline"><?php echo esc_html( $news_element->headline ); ?></div>
			                    			<div class="german-market-date"><?php echo $news_date_format; ?></div>
			                    			<div class="german-market-content"><?php echo esc_html( $news_element->content ); ?></div>

			                    			<?php
			                    				if ( isset( $news_element->button_text ) && isset( $news_element->button_link ) && ( ! empty( $news_element->button_text ) ) && ( ! empty( $news_element->button_link ) ) ) {
			                    					?>
			                    					<a href="<?php echo $news_element->button_link;?>" target="_blank"><?php echo $news_element->button_text; ?></a>
			                    					<?php
			                    				}
			                    			?>
			                    		</div>
			                    		<?php

			                    	}
		                    	?>
		                    	</div>

		                        <div class="german-market-news-boxes">
		    
		                            <?php
		                            $info_boxes = array(
		    
		                                array( 
		                                    'text'		=> __( 'Check out our documentation:', 'woocommerce-german-market' ),
		                                    'href'		=> __( 'https://marketpress.com/documentation/german-market/', 'woocommerce-german-market' ),
		                                    'a-text'	=> __( 'Read', 'woocommerce-german-market' ),
		                                ),

		                                array( 
		                                    'text'		=> __( 'Overview of all updates:', 'woocommerce-german-market' ),
		                                    'href'		=> __( 'https://marketpress.com/changelog-german-market/', 'woocommerce-german-market' ),
		                                    'a-text'	=> __( 'Changelog', 'woocommerce-german-market' ),
		                                ),

		                                array( 
		                                    'text'		=> __( 'Read latest news:', 'woocommerce-german-market' ),
		                                    'href'		=> __( 'https://marketpress.com/news/', 'woocommerce-german-market' ),
		                                    'a-text'	=> __( 'Blog', 'woocommerce-german-market' ),
		                                ),
		    
		                                array( 
		                                    'text'		=> __( 'You need help?', 'woocommerce-german-market' ),
		                                    'href'		=> __( 'https://marketpress.com/support', 'woocommerce-german-market' ),
		                                    'a-text'	=> __( 'Support', 'woocommerce-german-market' ),
		                                ),
		                            );
		    
		                            foreach ( $info_boxes as $box ) { ?>
		    
		                                <div class="german-market-news-box"><!-- einzelne Box -->
		                                    <p><?php echo $box[ 'text' ]; ?></p>
		                                    <a href="<?php echo $box[ 'href' ]; ?>" target="_blank"><?php echo $box[ 'a-text' ]; ?></a>
		                                </div> 
		                            <?php } ?>				          
		                           
		                        </div>
		                	</div>
						</div>

						<?php
					}

				?>
				<div class="german-market-left-menu">

					<div class="logo"></div>

                    <div class="mobile-menu-outer">
                        <div class="mobile-menu-button">
                            <div class="txt"><?php echo __( 'Menu', 'woocommerce-german-market' ); ?></div>
                            <div class="mobile-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>

					<ul>

						<?php

							$page_url = get_admin_url() . 'admin.php?page=german-market';

							$left_menu_items = $this->get_left_menu_items();

							$i = 0;

							foreach ( $left_menu_items as $item ) {

								$i++;

								$classes = array();

								// slug
								$classes[] = $item[ 'slug' ];

								// current tab
								if ( isset ( $_GET[ 'tab' ] ) ) {

									if ( $_GET[ 'tab' ] == $item[ 'slug' ] ) {
										$classes[] = 'current';
										$current = $item;
									}

								} else {

									if ( $i == 1 ) {
										$classes[] = 'current'; // if tab is not set, first item is current
										$current = $item;
									}

								}

								// new
								if ( isset( $item[ 'new' ] ) && $item[ 'new' ] ) {
									$classes[] = 'new';
								}

								// info
								if ( isset( $item[ 'info' ] ) && $item[ 'info' ] ) {
									$classes[] = 'info';
								}

								$classes = apply_filters( 'woocommerce_de_ui_left_menu_item_class', $classes, $item );
								$class_string = implode( ' ', $classes );

								?><li class="<?php echo $class_string; ?>"><a href="<?php echo $page_url . '&tab=' . $item[ 'slug' ]; ?>" title="<?php echo esc_attr( $item[ 'title' ] ); ?>"><?php echo $item[ 'title' ];?></a></li><?php

							}
						?>

					</ul>

					<div class="german-market-footer-menu">
						<?php echo __( sprintf( 'Version %s', Woocommerce_German_Market::$version ), 'woocommerce-german-market' );?>
					</div>

				</div>

				<div class="german-market-main-menu">

					<?php $this->render_content( $current ); ?>

				</div>

			</div>

		</div>
		<?php
	}

	/**
	* Render German Market Tab
	*
	* @access private
	* @return array
	*/
	private function render_content( $item ) {

		$callback = isset( $item[ 'callback' ] ) ? $item[ 'callback' ] : '';
		$page_url = get_admin_url() . 'admin.php?page=german-market&tab=' . $item[ 'slug' ];
		$current  = $item;

		?><h1><?php echo $item[ 'title' ]; ?></h1><?php

		do_action( 'woocommerce_de_ui_after_title', $item );

		// submenu
		if ( isset( $item[ 'submenu' ] ) ) {

			$submenu = $item[ 'submenu' ];
			$classes = array();

			?><ul class="submenu"><?php

				$i=0;

				foreach ( $submenu as $sub_item ) {

					$i++;

					$classes = array();

					// current sub tab
					if ( isset ( $_GET[ 'sub_tab' ] ) ) {

						if ( $_GET[ 'sub_tab' ] == $sub_item[ 'slug' ] ) {
							$classes[]	= 'current';
							$current 	= $sub_item;
							$callback	= isset( $sub_item[ 'callback' ] ) ? $sub_item[ 'callback' ] : $callback;
						}

					} else {

						if ( $i == 1 ) {
							$classes[] = 'current'; // if tab is not set, first item is current
							$current 	= $sub_item;
							$callback	= isset( $sub_item[ 'callback' ] ) ? $sub_item[ 'callback' ] : $callback;
						}

					}

					$classes = apply_filters( 'woocommerce_de_ui_sub_menu_item_class', $classes, $sub_item );
					$class_string = implode( ' ', $classes );
					?><li class="<?php echo $class_string; ?>"><a href="<?php echo $page_url . '&sub_tab=' . $sub_item[ 'slug' ]; ?>" title="<?php echo esc_attr( $sub_item[ 'title' ] ); ?>"><?php echo $sub_item[ 'title' ];?></a></li><?php

				}

			?></ul><?php

			do_action( 'woocommerce_de_ui_after_submenu', $item );

		}

		do_action( 'woocommerce_de_ui_before_callback', $callback );

		$is_option_page = ( isset( $current[ 'options' ] ) ) && ( $current[ 'options' ] == 'yes' );

		// callback
		if ( isset( $callback ) ) {

			if ( ( is_array( $callback ) && method_exists( $callback[ 0 ], $callback[ 1 ] ) ) || ( ! ( is_array( $callback )  ) && function_exists( $callback ) ) ) {

				if ( $is_option_page ) {

					$options = call_user_func( $callback );

					// save settings
					if ( isset( $_POST[ 'submit_save_wgm_options' ] ) ) {

						if ( ! wp_verify_nonce( $_POST[ 'update_wgm_settings' ], 'woocommerce_de_update_wgm_settings' ) ) {

							?>
							<div class="notice-wgm notice-error">
						        <p><?php echo __( 'Sorry, but something went wrong while saving your settings. Please, try again.', 'woocommerce-german-market' ); ?></p>
						    </div>
						    <?php

						} else {

							woocommerce_update_options( $options );

							do_action( 'woocommerce_de_ui_update_options', $options );

							if ( apply_filters( 'woocommerce_de_ui_show_settings_have_been_save_message', true ) ) {
								?>
								<div class="notice-wgm notice-success">
							        <p><?php echo __( 'Your settings have been saved.', 'woocommerce-german-market' ); ?></p>
							    </div>
							    <?php
							}

						}

					}

					?><form method="post"><?php

						$this->save_button( 'top', $current );

						wp_nonce_field( 'woocommerce_de_update_wgm_settings', 'update_wgm_settings' );
						woocommerce_admin_fields( $options );

						$this->save_button( 'bottom', $current );

					?></form><?php


				} else {
					call_user_func( $callback );
				}

			}

		}

		do_action( 'woocommerce_de_ui_after_callback', $callback );

	}

	/**
	* Render Options for required pages
	*
	* @access public
	* @return void
	*/
	public function required_pages() {

		$pages_link          = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'edit.php?post_type=page' ),
			__( 'Pages', 'woocommerce-german-market' )
		);

		$pages = WGM_Helper::get_default_pages();
		$post_titles = array();
		foreach ( $pages as $page ) {
			$post_titles[] = $page[ 'post_title' ];
		}

		$post_titles_string = implode( ', ', $post_titles );

		$reinstall_required_pages_desc = sprintf( __( 'You can reinstall the required pages with the default legal text templates. If you override existing pages, your changes of the legal text templates in the pages will be lost. The following pages will be reinstalled: %s.', 'woocommerce-german-market' ), $post_titles_string ) .  WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/erforderliche-seiten.mp4' );

		$options = array(

			/**
			 * Required Pages
			 */
			array(
				'name' => __( 'Required Pages', 'woocommerce-german-market' ),
				'type' => 'title',
				'desc' => sprintf(
				/* translators: %s = link to Pages admin section */
					__( 'Some of the following pages are mandatory, some recommended, for online stores based in Germany or Austria. Assign one of your already existing pages to each option. If you haven’t created said pages during activation of WooCommerce German Market, go to %s and create them now. Text templates can be found in the plugin folder of your WordPress installation at <code>woocommerce-german-market/text-templates</code>.<br /><br /><strong>Disclaimer:</strong> You can use those templates, but you <em>must</em> still customize them to fit the individual case of your online business. In your own best interest, consult a legal adviser and have them check your store before you open it to the public. Legal compliance of your online business is your sole responsibility.',
					    'woocommerce-german-market' ),
					$pages_link
				),
				'id'   => 'de_pages',
			),

			/* Page: Legal Information */
			array(
				/* translators: title or link text for a page displaying mandatory legal information, e.g. “Impressum” in German. */
				'name'     => __( 'Legal Information', 'woocommerce-german-market' ),
				'desc'     => __( 'Mandatory legal business information.', 'woocommerce-german-market' ) . ' ' . __( 'The Legal Information page mandatory for any business based in Germany or Austria must contain: company name, CEO, (summonable) company address, phone, fax (if available), website URL, email address, name of finance authority in charge, tax number, VAT Ident Number (VATIN), court in charge, German HRB number (if applicable), bank account owner, bank account number, bank code, bank name, SWIFT, IBAN.<br /><strong>Please note:</strong> For certain occupational category, e.g. pharmacists, architects, lawyers and opticians, additonal business information may be required. Those can vary depending on profession. Consult a legal adviser to make sure about which pieces of information are required for your particular business.',
				                  'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'impressum' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Page: Shipping & Delivery */
			array(
				'name'     => __( 'Shipping & Delivery', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Mandatory customer information regarding shipping and delivery.',
				                  'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'versandkosten__lieferung' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Page: Privacy */
			array(
				'name'     => __( 'Privacy', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Mandatory customer information regarding privacy and data security on your site.',
				                  'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'datenschutz' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Page: Payment Methods */
			array(
				'name'     => __( 'Payment Methods', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Mandatory customer information regarding payment methods offered on your site.',
				                  'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'zahlungsarten' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Page: Confirm & Place Order */
			array(
				'name'     => __( 'Confirm & Place Order', 'woocommerce-german-market' ),
				'desc_tip'     => __( 'Mandatory second checkout page where customers confirm and finally send their order.', 'woocommerce-german-market' ) . ' ' . __( 'This must be a child page of your Checkout page. It must contain the [woocommerce_de_check] shortcode.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'check' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Customer Notice */
			array(
				'name'              => __( 'Customer Notice on Confirm & Place Order Page', 'woocommerce-german-market' ),
				'desc_tip'          => __( 'Provide a final notice for your customers (e.g. regarding customs, fees etc.) before they place their order.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: before order review table on Confirm & Place Order page.', 'woocommerce-german-market' ),
				'id'                => 'woocommerce_de_last_checkout_hints',
				'type'              => 'textarea',
				'custom_attributes' => array( 'rows' => '10', 'cols' => '80' ),
				'default'           => '',
				'args'              => ''
			),

			/* Reinstall Required Pages */
			array(
				'name'              => __( 'Reinstall Required Pages', 'woocommerce-german-market' ),
				'id'                => 'woocommerce_de_reinstall_required_pages',
				'type'              => 'select',
				'options'			=> array(
							0	=>  __( '... Please choose', 'woocommerce-german-market' ),
							1	=>	__( 'Reinstall Required Pages (Override existing pages)', 'woocommerce-german-market' ),
							2	=>	__( 'Reinstall Required Pages (Skip existing pages)', 'woocommerce-german-market' ),
						),
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'desc'				=> $reinstall_required_pages_desc,
			),

			array( 'type' => 'sectionend', 'id' => 'de_pages' )
		);

		$options = apply_filters( 'woocommerce_de_ui_options_required_pages', $options );
		return $options;

	}

	/**
	* Render Options for Legal Text Templates
	*
	* @access public
	* @since 3.9.1
	* @return void
	*/
	public function legal_text_templates() {

		?>
		<style>
			.save-wgm-options{ display: none; }
		</style>

		<h2><?php echo __( 'Templates For Legal Texts', 'woocommerce-german-market' ); ?></h2>
		<div class="legal_text_templates_description">
			<p><?php echo WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/erforderliche-seiten.mp4' ) . '<br>' . __( 'Here you can see all templates for the legal texts. You can copy each text to the clipboard and paste it in the corresponding WordPress page. If you use the "classic editor", make sure you are use the "text modus" of the editor when pasting. If using "Gutenberg" you have to use the "Code-Editor" when pasting the text.', 'woocommerce-german-market' ); ?>
			</p>
			<p><strong><?php echo __( 'Last Update of Legal Texts:', 'woocommerce-german-market' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( '2022-05-24' ) ); ?></strong></p>
		</div>

		<?php

		$legal_texts = array(

			'legal_information'		=> array(
					'german_file' 		=> 'impressum.html',
					'english_file'		=> 'en/legal_information.html',
					'title'				=>  __( 'Legal Information', 'woocommerce-german-market' ),
			),

			'terms_and_conditions'	=> array(
					'german_file' 		=> 'allgemeine_geschaeftsbedingungen.html',
					'english_file'		=> 'en/terms_and_conditions.html',
					'title'				=> __( 'Terms and Conditions', 'woocommerce-german-market' ),
			),

			'shipping_delivery'		=> array(
					'german_file' 		=> 'versand_&_lieferung.html',
					'english_file'		=> 'en/shipping_and_delivery.html',
					'title'				=>  __( 'Shipping & Delivery', 'woocommerce-german-market' ),
			),

			'privacy'				=> array(
					'german_file' 		=> 'datenschutz.html',
					'english_file'		=> 'en/privacy.html',
					'title'				=>  __( 'Privacy', 'woocommerce-german-market' ),
			),

			'revocation'			=> array(
					'german_file' 		=> 'widerruf.html',
					'english_file'		=> 'en/revocation_policy.html',
					'title'				=>  __( 'Revocation Policy', 'woocommerce-german-market' ),
			),

			'revocation_digital'	=> array(
					'german_file' 		=> 'widerruf_fuer_digitale_inhalte.html',
					'english_file'		=> 'en/revocation_policy_for_digital_content.html',
					'title'				=>  __( 'Revocation Policy for Digital Content', 'woocommerce-german-market' ),
			),

			'payment_methods'	=> array(
					'german_file' 		=> 'zahlungsweisen.html',
					'english_file'		=> 'en/payment_methods.html',
					'title'				=>  __( 'Payment Methods', 'woocommerce-german-market' ),
			),

		);

		$path_for_templates = Woocommerce_German_Market::$plugin_path . 'text-templates' . DIRECTORY_SEPARATOR;

		foreach ( $legal_texts as $key => $legal_text ) {

			?><div class="legal-text-de-en">

				<h3><?php echo $legal_text[ 'title' ]; ?> </h3><?php

				$german_file_path = $path_for_templates . $legal_text[ 'german_file' ];


				if ( file_exists( $german_file_path ) && $german_file_path != $path_for_templates ) {

					$file_contents = file_get_contents( $german_file_path );
					?>

					<div class="legal-text <?php echo $key; ?>">
						<span class="legal-text-language-de"><?php echo __( 'German Template', 'woocommerce-german-market' ); ?></span>
						<div class="legal-text-contents-html <?php echo $key;?>">
							<?php echo apply_filters( 'the_content', $file_contents ); ?>
						</div>
						<div class="buttons">
							<button type="button" class="button-secondary copy-to-clipboard html"><?php echo __( 'Copy HTML Text to Clipboard', 'woocommerce-german-market' ); ?></button>
							<span class="copied-success"><?php echo __( 'The text has been copied to clipboard', 'woocommerce-german-market' ); ?> ✓</span>
						</div>
					</div>
					<?php

				}

				$english_file_path = $path_for_templates . $legal_text[ 'english_file' ];

				if ( file_exists( $english_file_path ) && $english_file_path != $path_for_templates ) {

					$file_contents = file_get_contents( $english_file_path );
					?>

					<div class="legal-text <?php echo $key; ?>">
						<span class="legal-text-language-en"><?php echo __( 'English Template', 'woocommerce-german-market' ); ?></span>
						<div class="legal-text-contents-html <?php echo $key;?>">
							<?php echo apply_filters( 'the_content', $file_contents ); ?>
						</div>
						<div class="buttons">
							<button type="button" class="button-secondary copy-to-clipboard html"><?php echo __( 'Copy HTML Text to Clipboard', 'woocommerce-german-market' ); ?></button>
							<span class="copied-success legal-texts"><?php echo __( 'The text has been copied to clipboard', 'woocommerce-german-market' ); ?> ✓</span>
						</div>
					</div>
					<?php

				}

			?></div><?php

		}

		return;

	}

	/**
	* Render Options for revocation policy
	*
	* @access public
	* @return void
	*/
	public function revocation_policy() {

		$options = array(

			/**
			 * Revocation
			 */
			array(
				'name' => __( 'Revocation Policy', 'woocommerce-german-market' ),
				'type' => 'title',
				'desc' => __( 'Enter details regarding your revocation policy.', 'woocommerce-german-market' ),
				'id'   => 'de_widerruf'
			),

			/* Revocation Period */
			array(
				'name'     => __( 'Revocation Period', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Enter a number of days as revocation period. Default: 14 days.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'widerrufsfrist' ),
				'type'     => 'number',
				'default'  => '14'
			),

			/* Revocation Address */
			array(
				'name'              => __( 'Revocation Address', 'woocommerce-german-market' ),
				'desc_tip'              => __( 'Legal name and summonable (i.e. a physical) address of your company. (P.O. box won’t do!).', 'woocommerce-german-market' ) . ' ' . __( 'Enter your legal company address. New lines will be parsed as line breaks.', 'woocommerce-german-market' ),
				'id'                => WGM_Helper::get_wgm_option( 'widerrufsadressdaten' ),
				'custom_attributes' => array( 'rows' => '10', 'cols' => '80' ),
				'type'              => 'textarea',
				'default'           => ''
			),

			/* Page: Revocation Policy */
			array(
				'name'     => __( 'Revocation Policy', 'woocommerce-german-market' ),
				'desc'     => __( 'Mandatory customer information regarding rights of revocation.', 'woocommerce-german-market' ) . ' ' . __( 'On your Revocation Policy page use the following shortcodes to display the field values you have defined above: [woocommerce_de_disclaimer_deadline] as a placeholder for Revocation Period and [woocommerce_de_disclaimer_address_data] as a placeholder for Revocation Address.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'widerruf' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			/* Page: Revocation Policy */
			array(
				'name'     => __( 'Revocation Policy for Digital Content', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Mandatory customer information regarding rights of revocation for digital content.',
				                  'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'widerruf_fuer_digitale_medien' ),
				'type'     => 'single_select_page',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
			),

			array( 'type' => 'sectionend', 'id' => 'de_widerruf' )

		);

		$options = apply_filters( 'woocommerce_de_ui_options_revocation_policy', $options );
		return $options;

	}

	/**
	* Render Options for small trading exemption
	*
	* @access public
	* @return void
	*/
	public function small_trading_exemption() {

		$options = array(

						/**
			 * Small trading exemption
			 */
			array(
				/* translators: the infamous “Kleinunternehmerregelung” */
				'name' => __( 'Small Trading Exemption', 'woocommerce-german-market' ),
				'type' => 'title',
				'desc' => __( 'Small Trading Exemption may apply to micro-businesses according UStG §19 (Germany) or UStG §6 (Austria). You’ll know whether it applies to your business.<br />The option below will simply act as a shortcut to deactivate tax calculations in WooCommerce. If you have set this to yes and will set it to no later on, you will have to re-enable tax calcualtion manually.',
				              'woocommerce-german-market' ),
				'id'   => 'de_kleinunternehmerregelung'
			),

			/* Disable Tax Calculations for STE */
			array(
				'name'     => __( 'Disable Tax Calculations for STE', 'woocommerce-german-market' ),
				'desc'     => '',
				'desc_tip' => __( 'Disable all taxes', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			array(
				'name'		=> __( 'Tax notice', 'woocommerce-german-market' ),
				'desc_tip'	=> __( 'The tax notice that is shown in your shop if you activate the Small Trading Exemption', 'woocommerce-german-market' ),
				'desc'		=> __( 'Default for Germany:', 'woocommerce-german-market' ) . ' <i>' . __( 'VAT exempted according to UStG §19', 'woocommerce-german-market' ) . '</i><br />' . __( 'Default for Austria:', 'woocommerce-german-market' ) . ' <i>' .  __( 'VAT exempted according to UStG §6', 'woocommerce-german-market' ) . '</i>',
				'id' 		=> 'gm_small_trading_exemption_notice',
				'type'		=> 'text',
				'default'	=> WGM_Template::get_default_ste_string(),
				'css'		=> 'width: 400px;'
			),

			array(
				'name'		=> __( 'Tax notice for External/Affiliate products', 'woocommerce-german-market' ),
				'desc_tip'	=> __( 'You can use another text for External/Affiliate products', 'woocommerce-german-market' ),
				'desc'		=> __( 'Default for Germany:', 'woocommerce-german-market' ) . ' <i>' . __( 'VAT exempted according to UStG §19', 'woocommerce-german-market' ) . '</i><br />' . __( 'Default for Austria:', 'woocommerce-german-market' ) . ' <i>' .  __( 'VAT exempted according to UStG §6', 'woocommerce-german-market' ) . '</i>',
				'id' 		=> 'gm_small_trading_exemption_notice_extern_products',
				'type'		=> 'text',
				'default'	=> WGM_Template::get_default_ste_string(),
				'css'		=> 'width: 400px;'
			),

			array( 'type' => 'sectionend', 'id' => 'de_kleinunternehmerregelung' ),
		);

		$options = apply_filters( 'woocommerce_de_ui_options_small_trading_exemption', $options );
		return $options;

	}

	/**
	* Render Options for small delivery_times
	*
	* @access public
	* @return void
	*/
	public function delivery_times() {

		$lieferzeit_strings = array();
		$lieferzeit_strings[ -1 ] = __( 'not specified', 'woocommerce-german-market' );
		$lieferzeit_strings = $lieferzeit_strings + WGM_Defaults::get_term_strings( 'product_delivery_times' );

		$delivery_times_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'edit-tags.php?taxonomy=product_delivery_times&post_type=product' ),
			/* translators: link text for Delivery Times admin page */
			__( 'Products → Delivery Times', 'woocommerce-german-market' )
		);

		$options = array();

		/**
		 * Delivery Times
		 */
		$options[] = array(
				'name' => __( 'Delivery Times', 'woocommerce-german-market' ),
				'type' => 'title',
				'desc' => sprintf(
				/* translators: %s = link to Products → Delivery Times */
					__( 'Delivery times can be configured in %s. Once you have customized yours, set a default delivery time for new products here.',
					    'woocommerce-german-market' ),
					$delivery_times_link
				),
				'id'   => 'de_lieferzeiten'
			);

		/* Default Delivery Times */
		$options[] = array(
				'name'     => __( 'Default Delivery Time', 'woocommerce-german-market' ),
				'desc_tip'     => __( 'Default delivery time for new products', 'woocommerce-german-market' ) . ' ' . __( 'This value can be overwritten for each product', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'global_lieferzeit' ),
				'type'     => 'select',
				'default'  => -1,
				'options'  => $lieferzeit_strings,
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
			);

		/* Loop: Delivery Times */
		$options[] = array(
				'name'     => __( 'Show Delivery Times in Shop', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Displays a product’s delivery time on product loop pages.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below add-to-cart button.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_delivery_time_overview' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		/* Product: Delivery Times */
		$options[] = array(
				'name'     => __( 'Show Delivery Times on Product Pages', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Displays a product’s delivery time on product pages.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_show_delivery_time_product_page',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			);

		/* Checkout: Delivery Times */
		$options[] = array(
				'name'     => __( 'Show Delivery Times during Checkout', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Displays a product’s delivery time during checkout.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_show_delivery_time_checkout',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		/* Order summary: Delivery Times */
		$options[] = array(
				'name'     => __( 'Show Delivery Times on Order Summary', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Displays a product’s delivery time on order summary.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_show_delivery_time_order_summary',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			);

		if ( class_exists( 'Woocommerce_Invoice_Pdf' ) ) {
			$options[] = array(
					'name'     => __( 'Show Delivery Times in Invoice PDFs', 'woocommerce-german-market' ),
					'id'       => 'woocommerce_de_show_delivery_time_invoice_pdf',
					'type'     => 'wgm_ui_checkbox',
					'default'  => 'on',
				);
		}

		if ( class_exists( 'Woocommerce_Return_Delivery_Pdf' ) ) {
			$options[] = array(
					'name'     => __( 'Show Delivery Times in Return Note PDFs', 'woocommerce-german-market' ),
					'id'       => 'woocommerce_de_show_delivery_time_retoure_pdf',
					'type'     => 'wgm_ui_checkbox',
					'default'  => 'off',
				);

			$options[] = array(
					'name'     => __( 'Show Delivery Times in Delivery Note PDFs', 'woocommerce-german-market' ),
					'id'       => 'woocommerce_de_show_delivery_time_delivery_pdf',
					'type'     => 'wgm_ui_checkbox',
					'default'  => 'off',
				);
		}

		$options[] = array( 'type' => 'sectionend', 'id' => 'de_sale_label' );

		$options = apply_filters( 'woocommerce_de_ui_options_delivery_times', $options );
		return $options;
	}

	/**
	* Render Options for sale_labels
	*
	* @access public
	* @return void
	*/
	public function sale_labels() {

		$sale_label_strings = WGM_Defaults::get_term_strings( 'product_sale_labels' );

		$sale_labels_link    = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'edit-tags.php?taxonomy=product_sale_labels&post_type=product' ),
			/* translators: link text for Delivery Times admin page */
			__( 'Products → Sale Labels', 'woocommerce-german-market' )
		);

		$options = array(

			/**
			 *  Sale labels
			 */
			array(
				'name' => __( 'Sale Labels', 'woocommerce-german-market' ),
				'type' => 'title',
				'desc' => sprintf(
				/* translators: %s = link to Products → Delivery Times */
					__( 'Sale labels can be configured in %s. Once you have customized yours, set a default sale label for new products here.',
					    'woocommerce-german-market' ),
					$sale_labels_link
				),
				'id'   => 'de_lieferzeiten'
			),

			/* Default Sale Labels */
			array(
				'name'     => __( 'Default Sale Label', 'woocommerce-german-market' ),
				'desc_tip'     => __( 'Default sale label for new products', 'woocommerce-german-market' ) . ' ' .__( 'This value can be overwritten for each product', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'global_sale_label' ),
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  => 1,
				'options'  => $sale_label_strings
			),

			/* Loop: Sale Labels */
			array(
				'name'     => __( 'Show Sale Labels in Shop', 'woocommerce-german-market' ),
				'desc_tip'     => __( 'Displays a product’s sale label on product loop pages.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below add-to-cart button.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_sale_label_overview' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			/* Product Page: Sale Labels */
			array(
				'name'     => __( 'Show Sale Labels on Product Pages', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Displays a product’s sale label on product pages.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below add-to-cart button.','woocommerce-german-market' ),
				'id'       => 'woocommerce_de_show_sale_label_product_page',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			array( 'type' => 'sectionend', 'id' => 'de_lieferzeiten' )

		);

		$options = apply_filters( 'woocommerce_de_ui_options_sale_labels', $options );
		return $options;
	}

	/**
	* Render Options for products
	*
	* @access public
	* @return void
	*/
	public function products(){

		$options = array();

		/**
		 * Products
		 */
		$options[] = array(
			'name' => _x( 'Products', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'desc' => __( 'The following options apply to the front-end of your online store.', 'woocommerce-german-market' ),
			'id'   => 'de_products'
		);

		/* Single Product: Non-EU Shipping Disclaimer */
		$options[] = array(
			'name'     => __( 'Show Non-EU Shipping Disclaimer', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Adds a disclaimer on single product pages that there may occur additional costs (e.g. customs or taxes) when shipping to non-EU countries.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below delivery time.', 'woocommerce-german-market' ),
			'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_extra_cost_hint_eu' ),
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		);

		/* Single Product: Non-EU Shipping Disclaimer */
		$options[] = array(
			'name'     => __( 'Non-EU Shipping Disclaimer', 'woocommerce-german-market' ),
			'id'       => 'woocommerce_de_show_extra_cost_hint_eu_text',
			'type'     => 'textarea',
			'default'  => __( 'Additional costs (e.g. for customs or taxes) may occur when shipping to non-EU countries.', 'woocommerce-german-market' ),
			'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
		);

		/* Loop + Single Product: Advertise Free Shipping */
		$options[] = array(
			'name'     => __( 'Advertise Free Shipping', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Replaces the mandatory Shipping page link with a line saying “Free Shipping”. This will <strong>not change any of your shipping settings</strong>. Make sure free shipping applies to all products before enabling this option.',
			                  'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below tax line.', 'woocommerce-german-market' ),
			'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_free_shipping' ),
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		);

		$options[ 'attribute_in_product_name' ] = array(
			'name'     => __( 'Product Attributes in product name', 'woocommerce-german-market' ),
			'desc_tip' => __( 'As default, the variation attributes are shown in the product name since WooCommerce 3.0. If this option is deactivated, the attributes are shown separated under the product name.', 'woocommerce-german-market' ),
			'id'       => 'german_market_attribute_in_product_name',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array(
			'name'     => __( 'Show Product Attributes not used for Variations', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If activated, product attibutes that are not used for variations are shown separated under the product name in cart, checkout and order summaries.', 'woocommerce-german-market' ),
			'id'       => 'gm_show_product_attributes',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array(
			'name'     => __( 'Show Single Price of Order Items in Orders', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If activated, the single price of order items is shown in orders after the product title if the quantity is greater 1. For example: (each 12,00 €, delivery time: 2 weeks). If this option is deactivated the output will be: (delivery time: 2 weeks). The single price is calculated as follows: item line total : item quantity.', 'woocommerce-german-market' ),
			'id'       => 'gm_show_single_price_of_order_items',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on'
		);

		$options[] = array(
			'name'     => __( 'Default Template for "Requirements (digital)"', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If you set up your digital and downloadable products, a default text for "Requirements (digital)" can be pre-set. This text is shown in the corresponding backend field if you create a new product.', 'woocommerce-german-market' ),
			'id'       => 'gm_default_template_requirements_digital',
			'type'     => 'textarea',
			'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'de_products' );

		/**
		 * Price presentation for Variable Products
		 */
		$options[] = array(
			'name' => _x( 'GTIN', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'desc' => __( 'Adds a GTIN field to products backend. This is GTIN field can be shown on product pages. A shortcode [gm_product_gtin] can be use to display the gtin on product pages, you can add the product ID on other pages by using the parameter product_id [gm_product_gtin product-id=99], use this parameter even in variations of variable products. The GTIN as saved as product meta data with the key _gm_gtin (you may need this key name for export plugins).', 'woocommerce-german-market' ),
			'id'   => 'german_market_gtin'
		);

		$options[] = array(
			'name'     => __( 'Activation', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If activated, a GTIN field is added to the backend of the products.', 'woocommerce-german-market' ),
			'id'       => 'gm_gtin_activation',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array(
			'name'     => __( 'Show on Single Product Pages', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If activated, the GTIN is shown on product pages.', 'woocommerce-german-market' ),
			'id'       => 'gm_gtin_product_pages',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'german_market_gtin' );
		/**
		 * Price presentation for Variable Products
		 */
		$options[] = array(
			'name' => _x( 'Price presentation for Variable Products', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'desc'	   => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/preisdarstellung-variable-produkte-und-varianten.mp4' ),
			'id'   => 'german_market_price_presentation_variable_products'
		);

		$options[] = array(
			'name'     => __( 'When a variation of a variable product has been selected', 'woocommerce-german-market' ),
			'id'       => 'german_market_price_presentation_variable_products',
			'type'     => 'select',
			'options'  => array(
								'woocommerce'  	=> __( 'Do not change WooCommerce behaviour', 'woocommerce-german-market' ),
								'gm_default' 	=> __( 'Hide price of the variable product (price range)', 'woocommerce-german-market' ),
								'gm_sepcial'	=> __( 'Replace variable product price through variation price', 'woocommerce-german-market' ),
							),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width: 350px;',
			'default'  => 'gm_default',
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'german_market_price_presentation_variable_products' );

		/**
		 * Add-To Cart Button In Shops
		 */
		$options[] = array(
			'name' => _x( 'Change "Add To Cart" Button in Shop Pages', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'desc' => __( 'Instead showing the "Add To Cart"-Button on shop pages, you can show a <strong>link to the product</strong>.', 'woocommerce-german-market' ),
			'id'   => 'german_market_add_to_cart_in_shop_pages'
		);

		$options[] = array(
			'name'     => __( 'Button Text', 'woocommerce-german-market' ),
			'id'       => 'german_market_add_to_cart_in_shop_pages_text',
			'type'     => 'text',
			'default'  => __( 'Show Product', 'woocommerce-german-market' ),
			'css'	   => 'width: 400px;'
		);

		$options[] = array(
			'name'     => __( 'Product Types', 'woocommerce-german-market' ),
			'desc'	   => __( 'Choose the type of products for which the "Add To Cart"-Button should be exchanged', 'woocommerce-german-market' ),
			'id'       => 'german_market_add_to_cart_in_shop_pages_product_types',
			'type'     => 'multiselect',
			'options'  => array(
				'grouped'	=> __( 'Grouped Products', 'woocommerce-german-market' ),
				'simple'	=> __( 'Simple Products', 'woocommerce-german-market' ),
				'variable'	=> __( 'Variable Products', 'woocommerce-german-market' ),
			),
			'class'	   => 'wc-enhanced-select',
			'css'	   => 'width: 400px;'
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'german_market_add_to_cart_in_shop_pages' );

		/**
		 * Product Images
		 */

		if ( class_exists( 'Woocommerce_Invoice_Pdf' ) ) {
			$pdf_description = sprintf( __( 'Settings for images in Invoice PDFs can be found <a href="%s">here</a>.', 'woocommerce-german-market' ), get_admin_url() . 'admin.php?page=german-market&tab=invoice-pdf&sub_tab=invoice_content#wp_wc_invoice_pdf_show_sku_in_invoice' );
		} else {
			$pdf_description = '';
		}

		$options[] = array(
			'name' => _x( 'Product Images', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'desc' => $pdf_description,
			'id'   => 'product_images'
		);

		$options[] = array(
			'name'     => __( 'Product Images on Cart Page', 'woocommerce-german-market' ),
			'id'       => 'german_market_product_images_in_cart',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on'
		);

		$options[] = array(
			'name'     => __( 'Product Images on Checkout Page', 'woocommerce-german-market' ),
			'id'       => 'german_market_product_images_in_checkout',
			'type'     => 'wgm_ui_checkbox',
			'desc_tip' => sprintf( __( 'German Market adds some simple CSS to style this product image with the CSS-Classes "%s". You can add some custom style in your child theme.', 'woocommerce-german-market' ), 'german-market-product-image checkout' ),
			'default'  => 'off'
		);

		$options[] = array(
			'name'     => __( 'Product Images for Order Summaries', 'woocommerce-german-market' ),
			'desc_tip' => sprintf( __( 'German Market adds some simple CSS to style this product image with the CSS-Classes "%s". You can add some custom style in your child theme.', 'woocommerce-german-market' ), 'german-market-product-image order' ),
			'id'       => 'german_market_product_images_in_order',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array(
			'name'     => __( 'Product Images in emails', 'woocommerce-german-market' ),
			'id'       => 'german_market_product_images_in_emails',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off'
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'product_images' );

		/**
		 * PPU
		 */
		$options[] = array(
			'name' => _x( 'Price per Unit', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'id'   => 'gm_price_per_unit'
		);

		/* Loop: PPU */
		$options[] = array(
			'name'     => __( 'Show Price per Unit', 'woocommerce-german-market' ),
			'desc_tip'     => __( 'Displays a product’s price per unit on product loop pages.','woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below tax line, above shipping link.','woocommerce-german-market' ),
			'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_price_per_unit' ),
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
		);

		$options[] = array(
			'name'     => __( 'Show Price per Unit in Checkout & Orders', 'woocommerce-german-market' ),
			'desc_tip'     => __( 'Displays a product’s price per during checkout and in orders.','woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below tax line, above shipping link.','woocommerce-german-market' ),
			'id'       => 'woocommerce_de_show_ppu_checkout',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		);

		/* PPU in Invoice PDF since GM 3.5.2 */
		$is_ppu_checkout_option_on = ( get_option( 'woocommerce_de_show_ppu_checkout', 'off' ) == 'on' );
		if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
			$is_ppu_checkout_option_on = isset( $_REQUEST[ 'woocommerce_de_show_ppu_checkout' ] );
		}

		if ( class_exists( 'Woocommerce_Invoice_Pdf' ) && $is_ppu_checkout_option_on ) {

			/* PPU in Invoice PDF since GM 3.5.2 */
			$options[] = array(
				'name' 		=>__( 'Show Price per Unit in Invoice PDFs', 'woocommerce-german-market' ),
				'desc_tip'  => __( 'Displays a product’s price per unit in Invoice PDFs','woocommerce-german-market' ),
				'id'       => 'woocommerce_de_show_ppu_invoice_pdf',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		}

		$options[] = array(
			'name'     => __( 'Automatic Calculation', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If you choose the autmatic calculation for the price per unit, the price per unit will automatically change if the price of the product changes. If price per unit output was used in this shop before activating automatic calculation, you have to enter the "Complete product quantity" for every product.', 'woocommerce-german-market' ),
			'id'       => 'woocommerce_de_automatic_calculation_ppu',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'on',
			'desc'	   => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/videogm/automatische-grundpreisberechnung.mp4' )
		);

		$options[] = array(
			'name'     => __( 'Automatic Calculation - Use WooCommerce Weight Unit and Product Weights', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Instead of entering the price per unit data for each product you can use the weight unit of woocommerce and each products weight. Useful, if the weihts have already been set for the products.', 'woocommerce-german-market' ),
			'id'       => 'woocommerce_de_automatic_calculation_use_wc_weight',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
			'desc'	   => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/automatische-grundpreisberechnung-woocommerce-gewicht.mp4' )
		);

		$options[] = array(
			'name'     => sprintf ( __( 'Automatic Calculation - Use WooCommerce Weight Unit and Product Weights - Scale Unit', 'woocommerce-german-market' ), get_option( 'woocommerce_weight_unit' ) ),
			'desc_tip' => sprintf( __( 'You are using "%s" in your WooCommerce settings for the product weights. This unit can could be converted to one of the following units.', 'woocommerce-german-market' ), get_option( 'woocommerce_weight_unit', 'kg' ) ),
			'id'       => 'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit',
			'type'     => 'select',
			'options'  => array(
								'kg'  => __( 'kg', 'woocommerce-german-market' ),
								'g'   => __( 'g', 'woocommerce-german-market' ),
								'lbs' => __( 'lbs', 'woocommerce-german-market' ),
								'oz'  => __( 'oz', 'woocommerce-german-market' )
							),
			'default'  => get_option( 'woocommerce_weight_unit', 'kg' ),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:300px;',
		);

		$options[] = array(
			'name'     => sprintf ( __( 'Automatic Calculation - Use WooCommerce Weight Unit and Product Weights - Quantity to display', 'woocommerce-german-market' ), get_option( 'woocommerce_weight_unit' ) ),
			'desc_tip' => sprintf( __( 'The global "quantity to display" if product weights are used. E.g.: (%s / 100 %s) - 100 ist the "Quantitiy to display" you have to enter here.', 'woocommerce-german-market' ), wc_price( 1.99), get_option( 'woocommerce_weight_unit', 'kg' ) ),
			'id'       => 'woocommerce_de_automatic_calculation_use_wc_weight_mult',
			'type'     => 'number',
			'custom_attributes'	 => array( 'step' => '0.01', 'min' => 0 ),
			'default'  => 1,
		);

		$option = get_option( 'woocommerce_de_ppu_outpout_format', '([price] / [mult] [unit])' );
		if ( isset( $_REQUEST[ 'woocommerce_de_ppu_outpout_format' ] ) ) {
			$option = $_REQUEST[ 'woocommerce_de_ppu_outpout_format' ];
		}

		$warning = __( 'All three shown placeholders have to be used, otherwise the default setting will be applied!', 'woocommerce-german-market' );
		// check if option uses all three placeholders
		if ( ( str_replace( '[price]', '', $option ) == $option ) || ( str_replace( '[mult]', '', $option ) == $option ) || ( str_replace( '[unit]', '', $option ) == $option ) ) {
			$warning = '<span style="color: #f00; font-weight: bold;">' . $warning .  '</span>';
		}

		$options[] = array(
			'name'     => __( 'Prefix Output Format', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Prefix Output Format in frontend', 'woocommerce-german-market' ),
			'desc'	   => __( 'You can use the following placeholders', 'woocommerce-german-market' ) . ': <code>[complete-product-quantity]</code>' . __( 'and', 'woocommerce-german-market' ) . ' <code>[unit]</code>.',
			'id'       => 'woocommerce_de_ppu_outpout_format_prefix',
			'type'     => 'text',
			'default'  => '',
		);

		$options[] = array(
			'name'     => __( 'Output Format', 'woocommerce-german-market' ),
			'desc_tip' => __( 'Output Format in frontend.', 'woocommerce-german-market' ),
			'desc'	   => __( 'You can use the following placeholders', 'woocommerce-german-market' ) . ': <code>[price]</code>, <code>[mult]</code> ' . __( 'and', 'woocommerce-german-market' ) .' <code>[unit]</code>.'  . '<br />' . __( 'The default setting <code>([price] / [mult] [unit])</code> leads to the following output example:', 'woocommerce-german-market' ) . ' <code>(' . wc_price( 9.99 ) . ' / 100 ml)' . '</code><br />' . $warning,
			'id'       => 'woocommerce_de_ppu_outpout_format',
			'type'     => 'text',
			'default'  => '([price] / [mult] [unit])',
		);

		$options[] = array(
			'name'     => __( 'Reinstall Measuring Units', 'woocommerce-german-market' ),
			'desc_tip' => __( 'To configure the price per units, German Market adds the product attribute "Measuring Unit". If you were unable to select measuring units when configuring the price per unit, you probably skipped installing the measuring units when you installed German Market, or you deleted this product attribute. If you enable and then save this setting, the  measuring units will be reinstalled. You can find the measuring units in the menu "Products -> Attributes". There you can also add your own terms for measuring units.', 'woocommerce-german-market' ),
			'id'       => 'woocommerce_de_reinstall_measuring_units',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'gm_price_per_unit' );

		/**
		 * Age Rating
		 */
		$options[] = array(
			'name' => _x( 'Age Rating', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'id'   => 'gm_age_rating',
			'desc' => sprintf(
						__( 'If this option is activated, you can specify a minimum age in each product which your buyer must have. If products with age rating are included in the cart, a checkbox will be displayed in the checkout to confirm that the customer is the required age. If this option is activated, you can customize the checkbox <a href="%s">here</a>.<br /><br />Furthermore, it can be set for each shipping method, whether they are only available for orders with products that require an age rating, during the ordering process. If the setting is set for at least one shipping method, only these shipping methods will be available if products with age restrictions are included in the order. If such products are not included, these shipping methods are not displayed.%s', 'woocommerce-german-market' ) . __( 'Depending on which age-restricted products are sold in the shop, further precautionary measures may have to be taken, such as a direct verification of the age and verification of the ID. In addition, a shipping service provider is required, which checks the age at delivery. It is always necessary to coordinate with an online law firm to determine if this process will be sufficient for the purpose.', 'woocommerce-german-market' ),
						admin_url() . 'admin.php?page=german-market&tab=general&sub_tab=checkout_checkboxes',
						WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/altersfreigabe.mp4' )
					  )
		);

		$options[] = array(
			'name'     => __( 'Activation', 'woocommerce-german-market' ),
			'id'       => 'german_market_age_rating',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
		);

		$options[] = array(
			'name'     => __( 'Standard age rating for all products', 'woocommerce-german-market' ),
			'id'       => 'german_market_age_rating_default_age_rating',
			'type'     => 'number',
			'custom_attributes' => array( 'min'	=> 0 ),
			'css'	   => 'width: 50px;',
			'default'  => '',
			'desc'	   => __( 'Years', 'woocommerce-german-market' ),
			'desc_tip' => __( 'If all your products have an age rating, you don\'t have to set up this age rating in every product. You can enter a global age rating here. If necessary, this option can be overridden in every product. To remove the global setting, enter 0 or nothing and save your settings.', 'woocommerce-german-market' ),
			'class'	   => 'german-market-unit',
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'gm_age_rating' );

		$product_reviews_desc = __( 'If customers can submit product reviews in your WooCommerce shop and you display them in your shop, you need to show your customers if and how you ensure that reviews come from customers who actually bought the product.', 'woocommerce-german-market' );
		$product_reviews_desc.= PHP_EOL . PHP_EOL . __( 'You can find the WooCommerce settings whether product reviews are enabled and whether only verified owners can submit reviews at:', 'woocommerce-german-market' );

		$product_settings_link =  get_admin_url() . 'admin.php?page=wc-settings&tab=products';
		$product_reviews_desc.= PHP_EOL . sprintf( '<a href="%s"><em>' . __( 'WooCommerce -> Settings -> Products -> Reviews', 'woocommerce-german-market' ) . '</em></a>', $product_settings_link );

		$product_reviews_desc.= PHP_EOL . PHP_EOL . __( 'If you have activated the "Enable product reviews" setting (or had it activated in the past), it is likely that product reviews will be displayed in the frontend.', 'woocommerce-german-market' );
		$product_reviews_desc.= PHP_EOL . PHP_EOL . __( 'The following settings can be used to display the required information text in close proximity to the product reviews.', 'woocommerce-german-market' );

		$product_reviews_desc.= PHP_EOL . PHP_EOL . __( 'If you do not use these settings or use your own texts, you should have it legally checked whether this complies with the legal requirements.', 'woocommerce-german-market' );
		$product_reviews_desc.= PHP_EOL . PHP_EOL . __( 'German Market adds CSS styles to the output of the information text. Check the output of the information text, because with the large number of available themes, the output might need to be customized by you with custom styles.' ,'woocommerce-german-market' );
		
		$options[] = array(
			'name' => _x( 'Legal Information for Product Reviews', 'options panel heading', 'woocommerce-german-market' ),
			'type' => 'title',
			'id'   => 'gm_legal_information_product_reviews_head',
			'desc' => $product_reviews_desc,
		);

		$options[] = array(
			'name'     => __( 'Show Infomation Text before Product Reviews', 'woocommerce-german-market' ),
			'id'       => 'gm_legal_information_product_reviews_wc_review_before',
			'type'     => 'wgm_ui_checkbox',
			'default'  => 'off',
			'desc_tip' => __( 'If the reviews are displayed on a product page, the corresponding information text can be displayed before the first review if this setting is enabled. If you output complete ratings with a widget, please make sure that you place the information text manually in close proximity to this widget, as this setting has no influence on widgets. The shortcode [gm_product_review_info] can also be used for output.', 'woocommerce-german-market' ),
		);

		$options[] = array(
			'name'	=> __( 'Information Text', 'woocommerce-german-market' ),
			'id'	=> 'gm_legal_information_product_reviews_information_text',
			'type'	=> 'select',
			'options' => array(
				'not_verified'	=> WGM_Legal_Information_Product_Reviews::get_not_verified_text(),
				'verified'		=> WGM_Legal_Information_Product_Reviews::get_verified_text(),
				'custom' 		=> __( 'Custom text', 'woocommerce-german-market' ),
			),
			'class'		=> 'wc-enhanced-select',
			'css'		=> 'min-width:400px;',
			'default'	=> WGM_Legal_Information_Product_Reviews::get_default_info_text_setting(),
			'desc_tip'	=> __( 'With this setting you choose the information text according to your WooCommerce setting »<strong>Reviews can only be left by "verified owners"</strong>«. If you have disabled this setting in WooCommerce, you can use the first option. If you have enabled it, you can use the second option. Alternatively, you can use the third option to enter a custom text as the information text yourself.', 'woocommerce-german-market' ),
		);

		$options[] = array(
			'name'     => __( 'Custom Text', 'woocommerce-german-market' ),
			'id'       => 'gm_legal_information_product_reviews_custom_text',
			'type'     => 'textarea',
			'default'  => __( 'The reviews are not checked for authenticity before they are published. They can therefore also come from consumers who have not actually purchased the rated products.', 'woocommerce-german-market' ),
			'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
		);

		$options[] = array(
			'name'	=> __( 'Star Ratings', 'woocommerce-german-market' ),
			'id'	=> 'gm_legal_information_product_reviews_star_ratings',
			'type'	=> 'select',
			'options' => array(
				'nothing'		=> __( "Don't change output", 'woocommerce-german-market' ),
				'hide'			=> __( 'Hide star rating', 'woocommerce-german-market' ),
				'complete_text' => __( 'Show complete information text after star rating', 'woocommerce-german-market' ),
				'short_text'	=> __( 'Show a short information that opens a popup with complete information text', 'woocommerce-german-market' ),
			),
			'class'    => 'wc-enhanced-select',
			'css'      => 'min-width:400px;',
			'default'  => 'nothing',
			'desc_tip' => __( 'If applicable, star ratings in your theme are output without the complete review. Even if only the star rating is displayed, the information text must be displayed near the star rating. To implement this, you can either display the full information text or a short information text that displays the full text when user clicks on it. Alternatively, you can hide the star rating completely. If the star rating is issued within a complete product rating, this setting does not take effect.', 'woocommerce-german-market' )
		);

		$options[] = array(
			'name'	=> __( 'Short Information Text', 'woocommerce-german-market' ),
			'id'	=> 'gm_legal_information_product_reviews_short_information_text',
			'type'	=> 'text',
			'css'      => 'min-width:400px;',
			'default'  => WGM_Legal_Information_Product_Reviews::get_short_text_default(),
			'desc_tip' => __( 'This text is displayed after star ratings. The full information text becomes visible when clicking on the short information text.', 'woocommerce-german-market' ),
			'desc'	=> __( 'The default text of this setting is:', 'woocommerce-german-market' ) . ' <em>' . WGM_Legal_Information_Product_Reviews::get_short_text_default() . '</em>',
		);

		$options[] = array( 'type' => 'sectionend', 'id' => 'gm_legal_information_product_reviews_head' );

		$options = apply_filters( 'woocommerce_de_ui_options_products', $options );
		return $options;
	}

	/**
	* Render Options for cart_and_checkout
	*
	* @access public
	* @return void
	*/
	public function cart_and_checkout() {

		$options = array();

		$options[] = array(
				'name' => _x( 'Cart & Checkout', 'options panel heading', 'woocommerce-german-market' ),
				'desc' => __( 'The following options apply to the front-end of your online store.', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_cart_checkout'
			);

			/* Cart: Show Links to Legal Pages */
		$options[] = array(
				'name'     => __( 'Show Links to Legal Pages', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Adds a hint line to the cart table showing links to Shipping, Revocation and Payment Methods pages.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below last line item.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_disclaimer_cart' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			);

		$options[] = array(
				'name'     => __( 'Learn more about shipping costs, payment methods and our revocation policy', 'woocommerce-german-market' ),
				'desc_tip' => __( 'You can use [link-shipping][/link-shipping], [link-payment][/link-payment], [link-revocation][/link-revocation], [link-privacy][/link-privacy], [link-terms][/link-terms] to add the specific links.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_learn_more_about_shipping_payment_revocation',
				'type'     => 'textarea',
				'default'  => __( 'Learn more about [link-shipping]shipping costs[/link-shipping], [link-payment]payment methods[/link-payment] and our [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
			);

			/* Cart: Disclaimer For Estimated Taxes & Shipping Costs */
		$options[] = array(
				'name'     => __( 'Disclaimer For Estimated Taxes & Shipping Costs', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Adds a disclaimer note to the cart page that taxes and shipping costs will be estimated until customers have entered billing and shipping data during checkout.', 'woocommerce-german-market' ) . ' ' . __( 'Display position may vary. Default: below cart totals.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_estimate_cart' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			);

		$options[] = array(
				'name'     => __( 'Text', 'woocommerce-german-market' ) . ': '. __( 'Disclaimer For Estimated Taxes & Shipping Costs', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_estimate_cart_text',
				'type'     => 'textarea',
				'default'  => __( 'Note: Shipping and taxes are estimated and will be updated during checkout based on your billing and shipping information.', 'woocommerce-german-market' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
			);

			/* hide flat rate shipping if free shipping is available */
		$options[] = array(
				'name'     => __( 'Hide shipping rates when free shipping is available', 'woocommerce-german-market' ),
				'id'       => 'wgm_dual_shipping_option',
				'type'     => 'wgm_ui_checkbox',
				'desc_tip' => __( 'After changing this setting, the option becomes effective for shop customers only when a new WooCommerce session is started. With the WooCommerce tools, you can also delete all sessions. Local Pickup is always displayed.', 'woocommerce-german-market' ),
				'default'  => 'off',
			);

			/* Cart + Checkout: Product Short Descriptions */
		$options[] = array(
				'name'     => __( 'Show Short Description', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Display product short description during checkout.', 'woocommerce-german-market' ) . ' ' . __( 'Adds short description to line items if available.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'woocommerce_de_show_show_short_desc' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Show Short Description - Variations', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If the option above is enabled, you can choose for variations of variable products if only the product short description should be display, only the description of the variation or both.', 'woocommerce-german-market' ),
				'id'       => 'german_market_short_description_of_variations',
				'type'     => 'select',
				'default'  => 'only_short_description',
				'options'  => array(
					'only_short_description' 			=> __( 'Only Product Short Desctiption', 'woocommerce-german-market' ),
					'only_variation_description'		=> __( 'Only Description of Variation', 'woocommerce-german-market' ),
					'short_and_variation_description'	=> __( 'Product Short Desciption and Description of Variation', 'woocommerce-german-market' ),
				),
				'class'   => 'wc-enhanced-select',
				'css'     => 'width: 400px;',
			);

			/* Checkout: Avoid Free Items In Cart */
		$options[] = array(
				'name'     => __( 'Avoid Free Items In Cart', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If you enable this option, customers can\'t checkout with a cart that contains one or more free items. So you can avoid unwanted orders. You can choose your error message in the option below.',
				                  'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_avoid_free_items_in_cart',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Avoid Free Items In Cart - Error Message', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Choose the error message for the option above.',
				                  'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_avoid_free_items_in_cart_message',
				'type'     => 'textarea',
				'default'  => __( 'Sorry, you can\'t proceed to checkout. Please contact our support.', 'woocommerce-german-market' ),
				'custom_attributes' => array( 'rows' => '3', 'cols' => '80' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
			);

		$options[] = array(
				'name'     => __( 'Confirm & Place Order Page', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Adds the second checkout page "Confirm & Place Order" to the checkout process.',
				                  'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_secondcheckout',
				'desc'	   => __( 'If other plugins are used and this causes problems during the checkout process, this option should be disabled.', 'woocommerce-german-market' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Order Button Text', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Text of the "Order Button"', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_order_button_text',
				'type'     => 'text',
				'default'  => __( 'Place binding order', 'woocommerce-german-market' ),
				'css'	   => 'width: 500px;'
			);

		$options[] = array(
				'name'     => __( 'Text - Notice: Digital content.', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Text for the notice "For digital content".', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_checkbox_text_digital_content_notice',
				'type'     => 'textarea',
				'default'  => __( 'Notice: Digital content are products not being delivered on any physical medium (e.g. software downloads, e-books etc.).', 'woocommerce-german-market' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
			);

		$options[] = array( 'type' => 'sectionend', 'id' => 'woocommerce_de_avoid_free_items_in_cart' );

		// sort products
		if ( apply_filters( 'german_market_sort_products_is_available', true ) ) { 

			$options[] = array(
					'name' => _x( 'Sort Items', 'options panel heading', 'woocommerce-german-market' ),
					'desc' => __( 'With this setting you can sort items by name or SKU in the cart as well as in orders.', 'woocommerce-german-market' ),
					'type' => 'title',
					'id'   => 'de_sortable_products'
				);

			$options[] = array(
					'name'    => __( 'Sort Cart Items by', 'woocommerce-german-market' ),
					'id'      => 'gm_cart_sort_products_by',
					'type'    => 'select',
					'options' => array(
						'standard' => __( 'WoocCommerce Standard', 'woocommerce-german-market' ),
						'name'     => __( 'Item Name', 'woocommerce-german-market' ),
						'sku'      => __( 'Product SKU', 'woocommerce-german-market' ),
					),
					'default' => 'standard',
					'class'   => 'wc-enhanced-select',
					'css'     => 'width: 400px;',
					'desc_tip'=> __( "This setting affects your cart, i.e. in the mini cart, cart and checkout page. If you choose the 'WoocCommerce Standard' option, no sorting will take place.", 'woocommerce-german-market' ),
				);

			$options[] = array(
					'name'    => __( 'Cart Items - Sort Sequence', 'woocommerce-german-market' ),
					'id'      => 'gm_cart_sort_products_ascdesc',
					'type'    => 'select',
					'options' => array(
						'asc'      => __( 'Ascending', 'woocommerce-german-market' ),
						'desc'     => __( 'Descending', 'woocommerce-german-market' ),
					),
					'default' => 'asc',
					'class'   => 'wc-enhanced-select',
					'css'     => 'width: 400px;',
				);

			$options[] = array(
					'name'    => __( 'Sort Order Items by', 'woocommerce-german-market' ),
					'id'      => 'gm_checkout_sort_products_by',
					'type'    => 'select',
					'options' => array(
						'standard' => __( 'WoocCommerce Standard', 'woocommerce-german-market' ),
						'name'     => __( 'Item Name', 'woocommerce-german-market' ),
	                    'sku'      => __( 'Product SKU', 'woocommerce-german-market' ),
					),
					'default' => 'standard',
					'class'   => 'wc-enhanced-select',
					'css'     => 'width: 400px;',
					'desc_tip'=> __( "This setting affects items in orders, i.e. everywhere where the sales process has been completed. E.g. on the 'Thank you page', in emails, in invoice PDFs, delivery notes, return notes and in the backend.", 'woocommerce-german-market' ),
				);

			$options[] = array(
					'name'    => __( 'Order Items - Sort Sequence', 'woocommerce-german-market' ),
					'id'      => 'gm_checkout_sort_products_ascdesc',
					'type'    => 'select',
					'options' => array(
						'asc'      => __( 'Ascending', 'woocommerce-german-market' ),
						'desc'     => __( 'Descending', 'woocommerce-german-market' ),
					),
					'default' => 'asc',
					'class'   => 'wc-enhanced-select',
					'css'     => 'width: 400px;',
				);

			$options[] = array( 'type' => 'sectionend', 'id' => 'de_sortable_products' );
		}

		$options[] = array(
				'name' => _x( 'Checkout Template / Theme Options', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_checkout_template_theme_options',
				'desc' => __( 'If you recognize any problems in your shop during checkout (e.g. missing submit button) this is probably caused by your theme overriding the "form-checkout" template of German Market. Activating one of the following options helps to fix these problems.', 'woocommerce-german-market' )
			);

			/* Template form-checkout.php */
		$options[] = array(
				'name'     => __( 'Force to use German Market Checkout Template', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If you activate this option, you can force to use the German Market template instead of your theme template.', 'woocommerce-german-market' ),
				'id'       => 'gm_force_checkout_template',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Deactivate German Market Hooks', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If you activate this option, German Market will not hook into your checkout page to change the order of payment methods and order review.', 'woocommerce-german-market' ),
				'id'       => 'gm_deactivate_checkout_hooks',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Handle Layout Shifts', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If this setting is deactivated, the h3-headings will be positioned outside the div container "order_review" (WooCommerce default). If you activate this option h3-headings will be positioned within the div container "order_review". This setting is only effective if the option "Deactivate German Market Hooks" is deactivated. You can use this setting if you notice layout problems e.g. with two-column checkout pages.', 'woocommerce-german-market' ),
				'id'       => 'gm_checkout_order_review_headings_in_order_review_div',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array( 'type' => 'sectionend', 'id' => 'de_checkout_template_theme_options' );

		$options[] = array(
				'name' => _x( 'Manual Order Confirmation', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_manual_order_confirmation',
				'desc' => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/videogm/manuelle-bestellannahme.mp4' )
			);

		$options[] = array(
				'name'     => __( 'Manual Order Confirmation', 'woocommerce-german-market' ),
				'desc_tip' => __( 'With this option "Manual Order Confirmation" it is possible to check each order manually and to approve it before the order is accepted. If the option is activated, the customer receives only the e-mail order confirmation with the acceptance of the offer. Subsequently, the respective order can be checked and approved manually in the order overview.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_manual_order_confirmation',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] = array(
				'name'     => __( 'Customer can choose the payment method only after the manual confirmation', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If the "Manual Order Confirmation" is active and this setting is also active, no payment methods are displayed in the shop. Instead, a hint text can be displayed. When the order has been manually confirmed by the store admin, the customer receives an email with the payment link, which leads to the "Pay for order" page. Only on this page he can choose a payment method.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_manual_order_confirmation_payment_methods_only_pay_order',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] =	array(
				'name'		=> __( 'No payment method in checkout - Hint text', 'woocommerce-german-market' ),
				'id'		=> 'woocommerce_de_manual_order_confirmation_payment_methods_hint_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'There are no payment methods available on the checkout page. Your order will be checked manually. If the order is accepted, you will receive an email with a link to pay for the order. There you can choose a payment method.', 'woocommerce-german-market' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;',
				'custom_attributes' => array( 'rows' => '3', 'cols' => '100' ),
			);

		$options[] = array( 'type' => 'sectionend');

		$options = apply_filters( 'woocommerce_de_ui_options_cart_and_checkout', $options );
		return $options;
	}

	/**
	* Render Options for checkout_checkboxes
	*
	* @access public
	* @return void
	*/
	public function checkout_checkboxes() {

		$description_text_error = __( 'This error is shown if the option "Opt-In" is activated and the customer has not checked the checkbox.', 'woocommerce-german-market' );
		$description_opt_in 	= __( 'If this option is activated a checkbox is shown that the customer has to check to checkout. If the customer does not check the checkbox, a error message will be shown. If the option is deactivated, only the text will be shown without a checkbox.', 'woocommerce-german-market' );

		$options = array();

			// General Options
		$options[] = 	array(
				'name' => _x( 'General', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_general',
			);

		$options[] =	array(
				'name'     => __( 'Show Checkout Boxes before Order Summary on Checkout Page', 'woocommerce-german-market' ),
				'desc_tip' => __( 'This option is not applied if the "Confirm & Place Order Page" is enabled or the "German Market Hooks" has been disabled (you find this option within the "Orderin").', 'woocommerce-german-market' ),
				'id'       => 'gm_order_review_checkboxes_before_order_review',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] =	array(
				'name'     => __( 'Logging Checkout Checkboxes', 'woocommerce-german-market' ),
				'desc_tip' => __( 'You can log which checkbox a user has checked during checkout. This is useful if you use optional checkboxes. If activated, the text of the checked checkboxes will be saved as order private order notes.', 'woocommerce-german-market' ),
				'id'       => 'gm_order_review_checkboxes_logging',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_general' );

			// Checkbox 1 - Terms & Conditions, Privacy Declaration and Revocation Policy
		$options[] =	array(
				'name' => _x( 'Checkbox - Terms & Conditions, Privacy Declaration and Revocation Policy', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_terms_and_conditions',
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on',
				'desc_tip'	=> __( 'You can only deactivate this option if you enable the Option "Force to use German Market terms Template". (Default: On)', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[] =	array(
				'name'     => __( 'Force to use German Market "terms" Template', 'woocommerce-german-market' ),
				'desc_tip' => __( 'If this option is activated, the "terms.php" template of German Market is forced to be used. (Default: On)', 'woocommerce-german-market' ),
				'id'       => 'gm_force_term_template',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			);

		$options[] =	array(
				'name'		=> __( 'Text - Not Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_text_no_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation]revocation policy[/link-revocation].', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Text - Only Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_text_digital_only_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Text - Digital and Not Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_text_mix_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I have read and accept the [link-terms]terms and conditions[/link-terms], the [link-privacy]privacy policy[/link-privacy], the [link-revocation]revocation policy[/link-revocation] and [link-revocation-digital]revocation policy for digital content[/link-revocation-digital].', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Error Text - Not Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_error_text_no_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You must accept our Terms & Conditions, privacy policy and revocation policy.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array(
				'name'		=> __( 'Error Text - Only Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_error_text_digital_only_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You must accept our Terms & Conditions, privacy policy and revocation policy for digital content.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array(
				'name'		=> __( 'Error Text - Digital and Not Digital', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_1_tac_pd_rp_error_text_mix_digital',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You must accept our Terms & Conditions, privacy policy, revocation policy and revocation policy for digital content.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkboxes_gm_checkbox_terms_and_conditions' );

			// Checkbox 2 - For digital content
		$options[] =	array(
				'name' => _x( 'Checkbox - For digital content', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_digital_content',
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_2_digital_content_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on'
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_2_digital_content_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'optional'		=> __( 'Optional Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[] =	array(
				'name'     => __( 'Checkbox Text', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Text of the Checkbox "For digital content".', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_checkbox_text_digital_content',
				'type'     => 'textarea',
				'default'  => __( 'For digital content: You explicitly agree that we continue with the execution of our contract before expiration of the revocation period. You hereby also declare you are aware of the fact that you lose your right of revocation with this agreement.', 'woocommerce-german-market' ),
				'css'	   => 'width: 100%; min-width: 400px; height: 75px;'
			);

		$options[] =	array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'woocommerce_de_checkbox_error_text_digital_content',
				'type'     	=> 'textarea',
				'default'  	=> __( 'Please confirm the waiver for your rights of revocation regarding digital content.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array(
				'name'     => __( 'For digital content - Repetition.', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Repeat the text above in emails and possibly in invoice pdfs.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_repeat_digital_content',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on'
			);

		$options[] =	array(
				'name'     => __( 'For digital content - Position of Text Repetition in Emails', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Position of the Repetition of the the text above in Emails', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_repeat_digital_content_notice_position',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  => 'after',
				'options'  => array(
					'before' => __( 'Before Order table', 'woocommerce-german-market' ),
					'after'  => __( 'After Order Table', 'woocommerce-german-market' )
				)
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_digital_content' );

			// Checkbox Age Rating
		$options[ 'age-rating-title' ] = array(
				'name' => _x( 'Checkbox - Age Rating', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_age_rating',
				'desc' => __( 'This checkbox is only shown if the order includes items with an age rating.', 'woocommerce-german-market' ),
			);

		$options[ 'age-rating-activation' ] = array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_age_rating_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on'
			);

		$options[ 'age-rating-opt-in' ] = array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_age_rating_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'optional'		=> __( 'Optional Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[ 'age-rating-text' ] = array(
				'name'		=> __( 'Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_age_rating_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I confirm that I am at least [age] years of age.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use the following placeholder:', 'woocommerce-german-market' ) . ' [age]',
			);

		$options[ 'age-rating-error-text' ] = array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_age_rating_error_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You have to confirm that you are at least [age] years of age.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error . ' ' . __( 'You can use the following placeholder:', 'woocommerce-german-market' ) . ' [age]',
			);

		$options[ 'age-rating-sectioned' ] = array( 'type' => 'sectionend', 'id' => 'gm_checkbox_age_rating' );

			// Checkbox 3 - Shipping Service Provider
		$options[] =	array(
				'name' => _x( 'Checkbox - Send Personal Data to Shipping Service Provider', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_shipping_service_provider',
				'desc' => __( 'This checkbox is only shown if the order needs shipping', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_3_shipping_service_provider_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on'
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_3_shipping_service_provider_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'optional'		=> __( 'Optional Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[] =	array(
				'name'		=> __( 'Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_3_shipping_service_provider_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I agree that my personal data is send to the shipping service provider.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;'
			);

		$options[] =	array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_3_shipping_service_provider_error_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You have to agree that your personal data is send to the shipping service provider.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_shipping_service_provider' );

			// Checkbox 4 - Custom Checkbox
		$options[] =	array(
				'name' => _x( 'Custom Checkbox', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_custom',
				'desc' => __( 'Here you can add an additional custom checkbox', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_4_custom_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'off'
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_4_custom_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'optional'		=> __( 'Optional Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),

			);

		$options[] =	array(
				'name'		=> __( 'Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_4_custom_text',
				'type'     	=> 'textarea',
				'default'  	=> '',
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'german_market_checkbox_4_custom_error_text',
				'type'     	=> 'textarea',
				'default'  	=> '',
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_custom' );

		$options = apply_filters( 'woocommerce_de_ui_options_checkout_checkboxes_after_custom', $options, $description_opt_in, $description_text_error );

			// Checkbox 5 - My Account Registration
		$options[] =	array(
				'name' => _x( 'My Account Registration', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_5_my_account_registration',
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_5_my_account_registration_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on',
				'desc_tip'	=> __( 'If this option is deactivated, the default woocommerce "Registration privacy policy" text will be shown.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_5_my_account_registration_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[] =	array(
				'name'		=> __( 'Text', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_5_my_account_registration_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_5_my_account_registration_error_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You must accept the privacy policy.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_my_account_registration' );

			// Checkbox 6 - Product Reviews
		$options[] =	array(
				'name' => _x( 'Product Reviews', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'gm_checkbox_6_product_review',
			);

		$options[] =	array(
				'name'		=> __( 'Activation', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_6_product_review_activation',
				'type'     	=> 'wgm_ui_checkbox',
				'default'	=> 'on',
			);

		$options[] =	array(
				'name'		=> __( 'Opt-In', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_6_product_review_opt_in',
				'type'     	=> 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  	=> 'on',
				'desc_tip'	=> $description_opt_in,
				'options'	=> array(
						'on'			=> __( 'Required Checkbox', 'woocommerce-german-market' ),
						'off'			=> __( 'No Checkbox, just Text', 'woocommerce-german-market' ),
				),
			);

		$options[] =	array(
				'name'		=> __( 'Text', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_6_product_review_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'I have read and accept the [link-privacy]privacy policy[/link-privacy].', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> __( 'You can use [link-terms][/link-terms], [link-privacy][/link-privacy], [link-revocation][/link-revocation], [link-revocation-digital][/link-revocation-digital] to add the specific links.', 'woocommerce-german-market' ),
			);

		$options[] =	array(
				'name'		=> __( 'Error Text', 'woocommerce-german-market' ),
				'id'		=> 'gm_checkbox_6_product_review_error_text',
				'type'     	=> 'textarea',
				'default'  	=> __( 'You must accept the privacy policy.', 'woocommerce-german-market' ),
				'css'	   	=> 'width: 100%; min-width: 400px; height: 75px;',
				'desc_tip'	=> $description_text_error,
			);

		$options[] =	array( 'type' => 'sectionend', 'id' => 'gm_checkbox_6_product_review' );

		if ( get_option( 'german_market_age_rating', 'off' ) == 'off' ) {
			unset( $options[ 'age-rating-title' ] );
			unset( $options[ 'age-rating-activation' ] );
			unset( $options[ 'age-rating-opt-in' ] );
			unset( $options[ 'age-rating-text' ] );
			unset( $options[ 'age-rating-error-text' ] );
			unset( $options[ 'age-rating-sectioned' ] );
		}

		$options = apply_filters( 'woocommerce_de_ui_options_checkout_checkboxes', $options );
		return $options;

	}

	/**
	* Render Options for global
	*
	* @access public
	* @return void
	*/
	public function global_tab(){

		$locale              = get_locale();
		$is_de               = ( stripos( $locale, 'de' ) === 0 ) ? TRUE : FALSE;
		$support_url         = $is_de ? 'https://marketpress.de/hilfe/' : 'https://marketpress.com/help/';

		$tax_class_options 	 = array(
			'highest_rate'		=> __( 'Highest Tax Rate', 'woocommerce-german-market' ),
			'lowest_rate'		=> __( 'Lowest Tax Rate', 'woocommerce-german-market' ),
			'highest_amount'	=> __( 'Highest Tax Amount', 'woocommerce-german-market' ),
			'lowest_amount'		=> __( 'Lowest Tax Amount', 'woocommerce-german-market' ),
			'standard_rate'		=> __( 'Standard Rate', 'woocommerce-german-market' ),
			'no_tax'			=> __( 'No Tax', 'woocommerce-german-market' )
		);

		$wc_tax_classes = WC_Tax::get_tax_classes();

		foreach ( $wc_tax_classes as $wc_tax_class ) {
			$tax_class_options[ $wc_tax_class ] = '"' . $wc_tax_class . '" ' . __( 'Rate', 'woocommerce-german-market' );
		}

		$options = array(

			array(
				'name' => _x( 'Global', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_global'
			),

			/* Global: Default Tax Label */
			array(
				'name'     => __( 'Default Tax Label', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Sets the default tax label (e.g. “VAT”). If empty, “VAT” will be used.', 'woocommerce-german-market' ),
				'id'       => WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ),
				'type'     => 'text',
				'default'  => __( 'VAT', 'woocommerce-german-market' ),
			),

			/* Global: Deactivate Split Tax Calculation */
			array(
				'name'     => __( 'Prorated Tax Calculation For Fees & Shipping Cost', 'woocommerce-german-market' ),
				'desc'	   => __( 'Mandatory method of calculating taxes for fees & shipping costs.', 'woocommerce-german-market' ) . ' ' . sprintf(
					__( 'Calculates taxes for fees and shipping costs based on the number of line items and tax rates present in cart. (Rarely considered, yet legally mandatory method in Germany and Austria. To learn more about how fee taxes are calculated read <a href="%s" target="_blank">this post</a>, or contact the <a href="%s" target="_blank">support team</a>.)',
					    'woocommerce-german-market' ),
					'https://marketpress.de/dokumentation/german-market/steuerberechnung-bei-nebenleistungen/',
					$support_url
				),
				'id'       => WGM_Helper::get_wgm_option( 'wgm_use_split_tax' ),
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			),

			array(
				'name'     => __( 'Tax Class for Shipping and Fees if "Prorated Tax Calculation For Fees & Shipping Cost" is turned off', 'woocommerce-german-market' ),
				'desc'	   => __( 'If "Prorated Tax Calculation For Fees & Shipping Cost" is disabled, it can be chosen which tax class should be applied for shipping costs and fees. Either the class with the highest/lowest tax rate or the highest/lowest tax amount in cart can be chosen or a specific tax class.', 'woocommerce-german-market' ),
				'id'       => 'gm_tax_class_if_splittax_is_off',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'default'  => 'highest_rate',
				'options'  => $tax_class_options,
			),

			array(
				'name'     => __( 'Gross Shipping Costs and Gross Fees', 'woocommerce-german-market' ),
				'desc'	   => __( 'Treat shipping costs and fees that you can enter in WooCommerce settings as gross prices. Please not, that in that case, the corresponding net prices can vary, depending on the applied tax rates of the items in the customer\'s cart, especially when the option "Prorated Tax Calculation For Fees & Shipping Cost" is activated.', 'woocommerce-german-market' ),
				'id'       => 'gm_gross_shipping_costs_and_fees',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			/* Global: Due Date  */
			array(
				'name'     => __( 'Due Date', 'woocommerce-german-market' ),
				'desc_tip' => __( 'For each payment gateway you can specify a due date for orders. The due date will be shown in the customer emails and in the invoice pdf.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_due_date',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
				'desc'	   => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/videogm/faelligkeitsdatum.mp4' )
			),

			array(
				'name'     => __( 'Do not save Customer IP in Order', 'woocommerce-german-market' ),
				'desc_tip' => __( 'By default, WooCommerce saves the customer IP as meta data to an order. Activate this option to prevent saving the customer IP.', 'woocommerce-german-market' ),
				'id'       => 'woocommerce_de_do_not_save_customer_ip_in_order',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_global' ),

			/* Global: Double Opt-in Customer Registration  */
			array(
				'name' => _x( 'Double Opt-in Customer Registration', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_double_opt_in_section',
				'desc' => __( 'To use the double-opt in customer registration, the "My account" page must be used in WooCommerce.', 'woocommerce-german-market' ),
			),

			array(
 				'name'    => __( 'Activation', 'woocommerce-german-market' ),
 				'desc_tip'=> __( 'If customer chooses to create a customer account an email with an activation link will be sent by mail. Customer account will be marked as activated if user clicks on the link within the email.','woocommerce-german-market' ),
 				'id'      => 'wgm_double_opt_in_customer_registration',
 				'type'    => 'wgm_ui_checkbox',
 				'default' => 'off',
 				'desc'	   => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/double-opt-in-kundenregistrierung.mp4' )
 			),

 			array(
 				'name'    => __( 'Management', 'woocommerce-german-market' ),
 				'desc_tip'=> __( 'If activated, you can see in the WordPress user list for which user the activation is still waiting and who has already clicked the activation link.', 'woocommerce-german-market' ),
 				'id'      => 'wgm_double_opt_in_customer_registration_management',
 				'type'    => 'wgm_ui_checkbox',
 				'default' => 'on',
 			),

 			array(
 				'name'    => __( 'Auto-Delete not Activated User Accounts', 'woocommerce-german-market' ),
 				'desc_tip'=> __( 'If activated, you can set up a number of days after which users that have not activated their account will automatically be deleted.','woocommerce-german-market' ),
 				'id'      => 'wgm_double_opt_in_customer_registration_autodelete',
 				'type'    => 'wgm_ui_checkbox',
 				'default' => 'off',
 			),

 			array(
 				'name'    => __( 'Auto-Delete: After x Days', 'woocommerce-german-market' ),
 				'desc_tip'=> __( 'If you activated the option above, you can set up the number of days here after not activated users will be automatically deleted.', 'woocommerce-german-market' ),
 				'id'      => 'wgm_double_opt_in_customer_registration_autodelete_days',
 				'type'    => 'number',
 				'custom_attributes' => array(
					'min'	=> 1,
				),
 				'default' => 14,
 			),

 			array(
 				'name'    => __( 'Auto-Delete: Extra Text', 'woocommerce-german-market' ),
 				'desc_tip'=> __( 'This text will be also shown in the Double-Opt-In Email if you activate "Auto-Delete".', 'woocommerce-german-market' ),
 				'id'      => 'wgm_double_opt_in_customer_registration_autodelete_extratext',
 				'type'    => 'german_market_textarea',
 				'default' => __( 'If you don\'t activate your account, it will be automatically deleted after [days] days.', 'woocommerce-german-market' ),
 				'css'	  => 'width: 100%; min-width: 400px; height: 100px;',
 				'desc'	  => __( 'You can use the following placeholder:', 'woocommerce-german-market' ) . ' [days]',
 			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_double_opt_in_section' ),

			/* Global: Double Opt-in Customer Registration  */
			array(
				'name' => _x( 'Frontend CSS-Styles and JavaScript', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_js_and_css'
			),

			/* Global: Use WGM CSS  */
			array(
				'name'     => __( 'Load Default CSS', 'woocommerce-german-market' ),
				'desc_tip' => __( 'Loads WooCommerce German Market’s CSS styles in the front-end. Disable if not needed. <strong>Note:</strong> Visual inconsistencies may occur even if you keep this enabled, depending on the WooCommerce templates included in your current theme. Usually those can be fixed via custom CSS, or in a child theme.', 'woocommerce-german-market' ),
				'id'       => 'load_woocommerce_de_standard_css',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'on',
			),

			/* Global: Use WGM CSS  */
			array(
				'name'     => __( 'Load Frontend JavaScript in footer', 'woocommerce-german-market' ),
				'id'       => 'german_market_frontend_js_in_footer',
				'type'     => 'wgm_ui_checkbox',
				'default'  => 'off',
			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_js_and_css' ),

		);

		$options = apply_filters( 'woocommerce_de_ui_options_global', $options );
		return $options;
	}

	/**
	* Render Options for emails
	*
	* @access public
	* @return void
	*/
	public function emails() {

		// template files - init files and directories
		$use_plain = get_option( 'wgm_plain_text_order_confirmation_mail', 'off' );
		if ( isset( $_REQUEST[ 'submit_save_wgm_options' ] ) ) {
			if ( isset( $_REQUEST[ 'wgm_plain_text_order_confirmation_mail' ] ) ) {
				$use_plain = 'on';
			} else {
				$use_plain = 'off';
			}
		}

		if ( 'on' === $use_plain ) {
			$plain_string 		= 'plain/';
			$plain_dir 	  		= DIRECTORY_SEPARATOR . 'plain';
			$edit_template_name = 'customer-confirm-order-plain';
		} else {
			$plain_string 		= '';
			$plain_dir 			= '';
			$edit_template_name = 'customer-confirm-order';
		}

		$core_file_string		= 'plugins/woocommerce-german-market/templates/woocommerce-german-market/emails/' . $plain_string . 'customer-confirm-order.php';
		$theme_file_string		= 'yourtheme/woocommerce-german-market/emails/' . $plain_string . 'customer-confirm-order.php';

		$core_file				= untrailingslashit( plugin_dir_path( Woocommerce_German_Market::$plugin_filename ) ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'woocommerce-german-market' . DIRECTORY_SEPARATOR . 'emails' . $plain_dir . DIRECTORY_SEPARATOR . 'customer-confirm-order.php';
		$theme_template_dir		= get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'woocommerce-german-market' . DIRECTORY_SEPARATOR . 'emails' . $plain_dir . DIRECTORY_SEPARATOR;
		$theme_file				= $theme_template_dir  . DIRECTORY_SEPARATOR . 'customer-confirm-order.php';

		// template file - move core file to template
		if ( isset( $_GET[ 'gm_template_nonce' ] ) && wp_verify_nonce( $_GET[ 'gm_template_nonce' ], 'customer_confirm_order' ) ) {
			if ( isset( $_GET[ 'move_template' ] ) && ( $edit_template_name === $_GET[ 'move_template' ] ) ) {
				if (  wp_mkdir_p( dirname( $theme_template_dir ) ) && ! file_exists( $theme_file ) ) {
					  $template_file = $core_file;
					  // Copy template file
					  wp_mkdir_p( $theme_template_dir );
					  copy( $template_file, $theme_file );
					  echo '<div class="updated fade"><p>' . __( 'Template file copied to theme.', 'woocommerce-german-market' ) . '</p></div>';
				 }

			}

			// template file - delete theme file
			if ( isset( $_GET[ 'delete_template' ] ) && ( $edit_template_name === $_GET[ 'delete_template' ] ) ) {
				if ( file_exists( $theme_file ) ) {
					unlink( $theme_file );
					echo '<div class="updated fade"><p>' . __( 'Template file deleted from theme.', 'woocommerce-german-market' ) . '</p></div>';
				}
			}
		}

		// template file - output buttons and texts
		if ( file_exists( $theme_file ) ) {
			$template_file_desc = __( 'This template cotaining the content of the order confirmation mail has been overridden by your theme and can be found in:', 'woocommerce-german-market' ) . ' <code>' . $theme_file_string . '</code>';
			if ( is_writable( $theme_file ) ) {

				$href = add_query_arg( 'gm_template_nonce', wp_create_nonce( 'customer_confirm_order' ) );
				$href = add_query_arg( 'delete_template', $edit_template_name, $href );
				$href = remove_query_arg( 'move_template', $href );

				$template_file_desc	 = '<a href="' . $href . '" class="delete_template button" style="float: right; margin-top: -4px; margin-left: 10px;">' . __( 'Delete template file', 'woocommerce-german-market' ) . '</a>' . $template_file_desc;
			}
		} else {
			$template_file_desc		= __( 'To override and edit the template that contains the content of the order confirmation mail copy <code>[file_1]</code> to your theme folder: <code>[file_2]</code>.', 'woocommerce-german-market' );
			$template_file_desc		= str_replace( array( '[file_1]', '[file_2]' ), array( $core_file_string, $theme_file_string ), $template_file_desc );
			if ( ( is_dir( $theme_template_dir ) && is_writable( $theme_template_dir ) ) || is_writable( get_stylesheet_directory() ) ) {

				$href = add_query_arg( 'gm_template_nonce', wp_create_nonce( 'customer_confirm_order' ) );
				$href = add_query_arg( 'move_template', $edit_template_name, $href );
				$href = remove_query_arg( 'delete_template', $href );

				$template_file_desc = '<a href="' . $href . '" class="button" style="float: right; margin-top: -4px; margin-left: 10px;">' . __( 'Copy file to theme', 'woocommerce-german-market' ) . '</a>' . $template_file_desc;
			}
		}

		// template file - js
		wc_enqueue_js("
						jQuery('a.delete_template').click(function(){
							var answer = confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'woocommerce-german-market' ) ) . "');
							if (answer)
								return true;
							return false;
						});
					");

		$options = array(

			array(
				'name' => _x( 'Order Confirmation Mail', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_emails_order_confirmation',
			),

			array(
				'name'    => __( 'Send Order Confirmation Mail', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Send Order Confirmation Mail after an Order is completed.',
				                 'woocommerce-german-market' ),
				'id'      => WGM_Helper::get_wgm_option( 'wgm_send_order_confirmation_mail' ),
				'type'    => 'wgm_ui_checkbox',
				'default' => 'on',
			),

			array(
				'name'    => __( 'Subject', 'woocommerce-german-market' ),
				'id'      => 'gm_order_confirmation_mail_subject',
				'type'    => 'text',
				'default' => __( 'Your {site_title} order confirmation from {order_date}', 'woocommerce-german-market' ),
				'desc'    => __( 'You can use the following placeholders', 'woocommerce-german-market' ) . ': <code>{site_title}</code>, <code>{order_number}</code>, <code>{order_date}</code>.',
				'css'	  => 'width: 500px;'
			),

			array(
				'name'    => __( 'Email Heading', 'woocommerce-german-market' ),
				'id'      => 'gm_order_confirmation_mail_heading',
				'type'    => 'text',
				'default' => __( 'Order Confirmation', 'woocommerce-german-market' ),
				'desc'    => __( 'You can use the following placeholders', 'woocommerce-german-market' ) . ': <code>{site_title}</code>, <code>{order_number}</code>, <code>{order_date}</code>.',
				'css'	  => 'width: 500px;'
			),

			array(
				'name'    => __( 'Email Text', 'woocommerce-german-market' ),
				'id'      => 'gm_order_confirmation_mail_text',
				'type'    => 'german_market_textarea',
				'default' => __( 'With this e-mail we confirm that we have received your order. However, this is not a legally binding offer until payment is received.', 'woocommerce-german-market' ),
				'desc'    => __( 'You can use the following placeholders', 'woocommerce-german-market' ) . ': <code>{first-name}</code>, <code>{last-name}</code>',
				'css'	  => 'width: 100%; min-width: 400px; height: 100px;'
			),

			array(
				'name'    => __( 'Do not send order confirmation email until the thank you page is reached', 'woocommerce-german-market' ),
				'id'      => 'gm_order_confirmation_mail_send_on_thank_you_page',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
				'desc'    => __( 'The order confirmation email is sent by default when the order is created in WooCommerce. This is usually at the time when the customer presses the "Place binding order" button on the checkout page (to be precise, when WooCommerce executes the "woocommerce_checkout_process" action). Sending the order confirmation email is legally correct in this way.', 'woocommerce-german-market' ) . '<br><br>' . __( 'If a customer is redirected to a payment provider after clicking the "Place binding order" button and returns to the checkout page because he cancels the payment process with the third party provider, the order confirmation email will be sent again when clicking the "Place binding order" button again.', 'woocommerce-german-market' ) . '<br><br>' . __( 'Some store operators want the order confirmation email to be sent only when the customer reaches the thank you page. This can be implemented by activating this setting. The email is then sent once when the customer reaches the thank you page. The order has already been received in the WooCommerce shop before. If you activate this setting, please check if the time of sending and the text of the email meets the legal requirements.', 'woocommerce-german-market' ),
			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_emails_order_confirmation' ),

			array(
				'name' => _x( 'Order Confirmation Mail Template', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_emails_order_confirmation_template',
				 'desc' => $template_file_desc
			),

			array(
				'name'    => __( 'Use plain text Order Confirmation mail', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Use the plain text version of the Order Confirmation Mail.',
				                 'woocommerce-german-market' ),
				'id'      => WGM_Helper::get_wgm_option( 'wgm_plain_text_order_confirmation_mail' ),
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_emails_order_confirmation_template' ),

			array(
				'name' => _x( 'Legal Texts Attachments', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_emails_legal_text_attachments'
			),

			array(
				'name'    => __( 'Send Legal Texts in Emails', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Adds the necessary legal texts inline to the emails.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_footer_general',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'on',
			),

			array(
				'name'    => __( 'Email attachments for Shop Admin Emails', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Adds the necessary legal texts and attachment files also to the emails ("New order", "Cancelled order" and "Failed order") that are send to the shop admin.',
				                 'woocommerce-german-market' ),
				'id'      => 'wgm_email_footer_in_admin_emails',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'on',
			),

			array( 'type' => 'sectionend', 'id' => 'de_shop_emails_legal_text_attachments' ),
		);
		
		$all_mails = WC()->mailer()->get_emails();
		$mail_options = array();

		foreach ( $all_mails as $email ) {
			$mail_options[ $email->id ] = $email->title;
		}

		$mail_options[ 'customer_order_confirmation' ] = __( 'Order Confirmation', 'woocommerce-german-market' );
		$mail_options[ 'double_opt_in_customer_registration' ] = __( 'Double Opt-in Customer Registration', 'woocommerce-german-market' );
		$mail_options[ 'german_market_email_sepa' ] = __( 'SEPA Direct Debit Mandate', 'woocommerce-german-market' );

		asort( $mail_options );

		$file_attachments = array(

			array(
				'name' => _x( 'File Attachments', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_emails_file_attachments',
				'desc' => WGM_Ui::get_video_layer( 'https://s3.eu-central-1.amazonaws.com/videogm/statische-mail-anhaenge.mp4' )
			),

			array(
				'name'    => __( 'Number of Files', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'If you deactivated the necessary legal texts in the emails, you should add PDF files with your terms of condition, recovation policy, etc. Choose the number of files that you want to attache here.',
				                 'woocommerce-german-market' ),
				'id'      => 'de_shop_emails_file_attachments_nr',
				'type'    => 'number',
				'custom_attributes' => array(
					'min'	=> 0,
					'max'	=> apply_filters( 'de_shop_emails_file_attachments_nr', 10 )
				),
				'default' => 1,
			),

		);

		$image_upload_button = '<button type="button" class="button-secondary de_shop_emails_file_attachments_upload_button" id="de_shop_emails_file_attachments_upload_button_PART" style="margin: 3px 0;">' . __( 'Attachment Upload', 'woocommerce-german-market' ) . '</button>';

		$image_remove_button = '<button type="button" class="button-secondary de_shop_emails_file_attachments_remove_button" id="de_shop_emails_file_attachments_remove_button_PART" style="margin: 3px 3px;">' . __( 'Remove Attachment', 'woocommerce-german-market' ) . '</button>';

		for ( $i = 1; $i <= apply_filters( 'de_shop_emails_file_attachments_nr', 10 ); $i++ ) {

			$the_image_upload_button = str_replace( 'PART', $i, $image_upload_button );
			$the_image_remove_button = str_replace( 'PART', $i, $image_remove_button );

			$file_attachments[] = array(
				'name'    => __( 'Attachment', 'woocommerce-german-market' ) . ' ' . $i,
				'desc'	  => $the_image_upload_button . $the_image_remove_button,
				'desc_tip'=> __( 'Click the upload button to use the media uploader. Click the "Insert into Post" button in the media uploader to add the attachment here.', 'woocommerce-german-market' ),
				'id'      => 'de_shop_emails_file_attachment_' . $i,
				'type'    => 'text',
				'default' => '',
				'class'	  => 'de_shop_emails_file_attachment',
				'css'     => 'min-width:500px;'
			);
		}

		$file_attachments[] = array(
				'name'    => __( 'Attach to all WooCommerce emails', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'When the setting is disabled, you can select the WooCommerce emails to attach the files to. When the setting is enabled, the file attachments are added to all WooCommerce emails, respecting the setting "Email attachments for Shop Admin Emails".', 'woocommerce-german-market' ),
				'id'      => 'german_market_email_attachements_all_wc_emails',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'on',
			);

		$file_attachments[] = array(
				'name' 		=> __( 'Emails', 'woocommerce-german-market' ),
				'id'   		=> 'german_market_email_attachements_emails',
				'type'		=> 'multiselect',
				'default'  	=>  array(),
				'options'	=> $mail_options,
				'class'		=> 'wc-enhanced-select',
				'desc_tip' 	=> __( 'Here you can select the emails that should contain the file attachments.', 'woocommerce-german-market' ),
				'css'	  	=> 'width: 300px;'
			);

		$file_attachments[] = array( 'type' => 'sectionend', 'id' => 'de_shop_emails_file_attachments' );

		$bcc = apply_filters( 'german_market_options_bcc_emails', array(

			array(
				'name' => _x( 'BCC / CC Recipients', 'options panel heading', 'woocommerce-german-market' ),
				'type' => 'title',
				'id'   => 'de_shop_emails_cc_bcc',
				'desc' => __( 'You can add CC or BCC recipients to the emails that are send to the customer.', 'woocommerce-german-market' ),
			),

			array(
				'name'    => __( 'Email addresses', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Enter comma-seperated email addresses of Recipients.','woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_receivers',
				'type'    => 'text',
				'default' => '',
				'css'	  => 'width: 300px;'
			),

			array(
				'name'    => __( 'BBC / CC', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Choose whether the recipients are added as BCC or CC.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_type',
				'type'    => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'options' => array(
					'bcc'	=> 'BCC',
					'cc'	=> 'CC'
				),
				'default' => 'BCC',
				'css'	  => 'width: 300px;'
			),

			array(
				'name'    => __( 'Order Confirmation', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to Order Confirmation Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_order_confirmation',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'On Hold', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to On Hold Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_on_hold_order',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'Processing Order', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to Processing Order Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_processing_order',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'Completed Order', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to Completed Order Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_completed_order',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'Refunded Order', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to Refunded Order Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_refunded_order', // customer_partially_refunded_order
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'Invoice', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to Invoice Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_invoice',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'New Account', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to "New Account" Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_new_account',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			'double_opt_in' => array(
				'name'	  => __( 'Double Opt-in Customer Registration', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Add BCC / CC recipients to "Double Opt-in Customer Registration" Email.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_double_opt_in_customer_registration',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),

			array(
				'name'    => __( 'Customer Note', 'woocommerce-german-market' ),
				'desc_tip'=> __( 'Customer note emails are sent when you add a note to an order.', 'woocommerce-german-market' ),
				'id'      => 'wgm_email_cc_bcc_customer_note',
				'type'    => 'wgm_ui_checkbox',
				'default' => 'off',
			),


			'last_key_sectioned' => array( 'type' => 'sectionend', 'id' => 'de_shop_emails_cc_bcc' ),

		) );

		// remove double opt in email if not activated
		if ( 'off' === get_option( 'wgm_double_opt_in_customer_registration', 'off' ) ) {
			unset( $bcc[ 'double_opt_in' ] );
		}

		$options = array_merge( $options, $file_attachments, $bcc );

		$options = apply_filters( 'woocommerce_de_ui_options_emails', $options );
		return $options;

	}

	/**
	* Render Add-On Tab
	*
	* @access public
	* @return array
	*/
	public static function render_add_ons() {

		// Init
		$add_ons = self::get_addons();

		// Update Options
		if ( isset( $_POST[ 'update_add_ons' ] ) ) {

			if ( ! wp_verify_nonce( $_POST[ 'update_add_ons' ], 'woocommerce_de_update_add_ons' ) ) {

				?>
				<div class="notice notice-error">
			        <p><?php echo __( 'Sorry, but something went wrong while saving your settings. Please, try again.', 'woocommerce-german-market' ); ?></p>
			    </div>
			    <?php

			} else {

				foreach ( $add_ons as $add_on ) {

					if ( isset( $_POST[ $add_on[ 'id' ] ] ) ) {
						$current_activation = $add_on[ 'on-off' ];
						$new_activation = $current_activation == 'on' ? 'off' : 'on';
						update_option( $add_on[ 'id' ], $new_activation );
					}

				}

				// Do a little trick (add-ons are activated after second reload)
				wp_safe_redirect( get_admin_url() . 'admin.php?page=german-market&tab=add-ons&updated_wgm_add_ons=' . time() );
				exit();

			}

		}

		// Show notice when settings have been saved
		if ( isset( $_REQUEST[ 'updated_wgm_add_ons' ] ) ) {

			// If someone reloads the page, the message should not be shown
			if ( intval( $_REQUEST[ 'updated_wgm_add_ons' ] ) + 1 >= time() ) {

				?>
				<div class="notice notice-success">
			        <p><?php echo __( 'Your settings have been saved.', 'woocommerce-german-market' ); ?></p>
			    </div>
			    <?php

			}
		}

		?>
			<form method="post">

				<?php wp_nonce_field( 'woocommerce_de_update_add_ons', 'update_add_ons' ); ?>

				<div class="add-ons">

					<div class="description">
					</div>

					<?php

						foreach ( $add_ons as $add_on ) {

							?>
								<div class="add-on-box <?php echo $add_on[ 'on-off' ]; ?>">

									<div class="icon logo-box">
										<?php if ( $add_on[ 'image' ] != '' ) { ?>

											<img src="<?php echo $add_on[ 'image' ]; ?>" alt="logo" />

										<?php } else if ( $add_on[ 'dashicon' ] != '' ) { ?>

											<span class="dashicons dashicons-<?php echo $add_on[ 'dashicon' ]; ?>"></span>

										<?php } else { ?>

											<span class="dashicons dashicons-admin-generic"></span>

										<?php } ?>

									</div>

									<div class="on-off-box">

										<label class="switch">
											<?php

												if ( $add_on[ 'on-off' ] == 'on' ) {

													?><input type="submit" class="add-on-switcher on" name="<?php echo $add_on[ 'id' ]; ?>" value="" /><div class="slider round"></div><?php

												} else if ( $add_on[ 'on-off' ] == 'off' ) {

													?><input type="submit" class="add-on-switcher off" name="<?php echo $add_on[ 'id' ]; ?>" value="" /><div class="slider round"></div><?php

												}

											?>
										</label>

									</div>

									<span style="clear: both; display: block;"></span>

									<div class="title">
										<?php echo $add_on[ 'title' ]; ?>
									</div>

									<div class="description">
										<?php echo $add_on[ 'description']; ?>
									</div>

									<div class="video">
											<?php if ( isset( $add_on[ 'video' ] ) && $add_on[ 'video' ] != '' ) {
												echo self::get_video_layer( $add_on[ 'video' ] );
											}
										?>
									</div>

								</div>

						<?php } ?>

				</div>

			</form>

		<?php
	}

	/**
	* Get Add-Ons
	*
	* @access private
	* @return array
	*/
	private static function get_addons() {

		$refund_on_off = 'always-off';
		$add_ons_refund = array( 'wgm_add_on_sevdesk', 'wgm_add_on_lexoffice', 'wgm_add_on_woocommerce_running_invoice_number', 'wgm_add_on_woocommerce_invoice_pdf' );
		foreach ( $add_ons_refund as $add_on_refund ) {
			if ( get_option ( $add_on_refund ) == 'on' ) {
				$refund_on_off ='always-on';
				break;
			}
		}

		$add_ons = array(

			array(
				'title'				=> __( 'Refunds', 'woocommerce-german-market' ),
				'description'		=> __( 'German Market supports special actions for your refunds. The "All Refunds" submenu is automatically available if one of these add-ons is activated: Invoice Number, Invoice PDF, Lexoffice, sevDesk.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-storno.jpg',
				'dashicon'			=> 'welcome-view-site',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/storno.mp4',
				'on-off'			=> $refund_on_off,
				'id'				=> 'refund'
			),

			array(
				'title'				=> __( 'EU VAT Number Check', 'woocommerce-german-market' ),
				'description'		=> __( 'Adds a field for value-added tax identitification number (VATIN) during checkout. Validates field entries against the official web site of the <a href="http://ec.europa.eu/taxation_customs/vies/vieshome.do?locale=en">European Commission</a>', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-eumwst.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/videogm/ustid.mp4',
				'on-off'			=> get_option( 'wgm_add_on_woocommerce_eu_vatin_check' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_woocommerce_eu_vatin_check'
			),

			array(
				'title'				=> __( 'EU VAT Checkout', 'woocommerce-german-market' ),
				'description'		=> __( 'The add-on supports you when selling your products to other EU member states. You can import tax rates of EU countries, fix gross prices and set a generalized tax output.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-eumwst.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://videogm.s3.eu-central-1.amazonaws.com/eu-mehrwertsteuer-add-on.mp4',
				'on-off'			=> get_option( 'wgm_add_on_woocommerce_eu_vat_checkout' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_woocommerce_eu_vat_checkout'
			),

			array(
				'title'				=> __( 'Return / Delivery Note PDF', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on adds a Retoure PDF as an attachment to customer emails, enables backend download of the pdf and customer download on the my account page.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-retourepdf.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/retourenschein.mp4',
				'on-off'			=> get_option( 'wgm_add_on_woocommerce_return_delivery_pdf' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_woocommerce_return_delivery_pdf'
			),

			array(
				'title'				=> __( 'Invoice Number', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on adds a running invoice number to your orders.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-rnr.jpg',
				'dashicon'			=> '',
				'video'				=> '',
				'on-off'			=> get_option( 'wgm_add_on_woocommerce_running_invoice_number' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_woocommerce_running_invoice_number'
			),

			array(
				'title'				=> __( 'Invoice PDF', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on adds an Invoice PDF as an attachment to customer emails, enables backend download of the pdf and customer download on the my account page.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-rechnungpdf.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/rechnungspdf.mp4',
				'on-off'			=> get_option( 'wgm_add_on_woocommerce_invoice_pdf' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_woocommerce_invoice_pdf'
			),

			array(
				'title'				=> __( 'Lexoffice', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on is a Lexoffice API.', 'woocommerce-german-market' ) . '<br /><br />' . sprintf ( __( "You can register <a href=\"%s\" target=\"_blank\">here</a> if you don't have a lexoffice account, yet", 'woocommerce-german-market' ), 'https://app.lexoffice.de/signup?pid=1443' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-lexoffice.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/lexoffice.mp4',
				'on-off'			=> get_option( 'wgm_add_on_lexoffice' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_lexoffice'
			),

			array(
				'title'				=> __( 'sevDesk', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on activates the sevDesk API.', 'woocommerce-german-market' ) . '<br /><br />' . sprintf ( __( "You can register <a href=\"%s\" target=\"_blank\">here</a> if you don't have a sevDesk account, yet.", 'woocommerce-german-market' ), 'https://sevdesk.de/register/?utm_source=integrations&utm_medium=referral&utm_campaign=marketpress' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-sevdesk.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/sevdesk.mp4',
				'on-off'			=> get_option( 'wgm_add_on_sevdesk' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_sevdesk'
			),

			array(
				'title'				=> __( 'IT-Recht Kanzlei', 'woocommerce-german-market' ),
				'description'		=> __( '<a href="http://www.it-recht-kanzlei.de/Service/german-market-agb.php?partner_id=294" target="_blank">Dunning-proof legal texts</a> for your shop. Monthly only 8.90 € + monthly cancelable.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-it-recht-kanzlei.png',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/it-recht-kanzlei.mp4',
				'on-off'			=> get_option( 'wgm_add_on_it_recht_kanzlei' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_it_recht_kanzlei'
			),

			array(
				'title'				=> __( 'Protected Shops', 'woocommerce-german-market' ),
				'description'		=> __( 'Benefit from our <a href="https://www.protectedshops.de/unsere-schutzpakete" target="_blank">exclusive partner offer</a>. With the coupon code PS-GM-3X, new customers of Protected Shops receive 3 months for free - use 15 months and pay only 12 months.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-protected-shops.png',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/protected-shops.mp4',
				'on-off'			=> get_option( 'wgm_add_on_protected_shops' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_protected_shops'
			),

			array(
				'title'				=> __( 'Billbee', 'woocommerce-german-market' ),
				'description'		=> __( 'This Add-On allows you to open your WooCommerce order direcetly in Billbee.', 'woocommerce-german-market' ) . '<br /><br />' . sprintf ( __( "You can register <a href=\"%s\" target=\"_blank\">here</a> for a free and not binding 30-day test period.", 'woocommerce-german-market' ), 'https://www.billbee.io?acc=yqxflqpu' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-billbee.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/marketpress-videos/german-market/billbee.mp4',
				'on-off'			=> get_option( 'wgm_add_on_billbee' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_billbee'
			),

			array(
				'title'				=> __( 'FIC', 'woocommerce-german-market' ),
				'description'		=> __( 'This add-on allows you to add legally nesseccary information about nutritional values and allergenes to your products respecting the EU Food Information for Consumers Regulation.', 'woocommerce-german-market' ),
				'image'				=> plugins_url() . '/woocommerce-german-market/images/addon-fic.jpg',
				'dashicon'			=> '',
				'video'				=> 'https://s3.eu-central-1.amazonaws.com/videogm/lmiv.mp4',
				'on-off'			=> get_option( 'wgm_add_on_fic' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_fic'
			),

			array(
				'title'				=> __( 'Temporary Tax Reduction', 'woocommerce-german-market' ),
				'description'		=> __( 'Manage temporary tax reduction. <br><br>If it is legally necessary to adjust the tax rates for a limited period of time, you can do so with this add-on.', 'woocommerce-german-market' ),
				'dashicon'			=> 'backup',
				'image'				=> '',
				'on-off'			=> get_option( 'wgm_add_on_temporary_tax_reduction' ) == 'on' ? 'on' : 'off',
				'id'				=> 'wgm_add_on_temporary_tax_reduction',
				'video'				=> 'https://marketpress-videos.s3.eu-central-1.amazonaws.com/german-market/zeitweise-mwst-senkung.mp4',
			),

		);

		return apply_filters( 'woocommerce_de_add_ons_menu_list', $add_ons );

	}

	/**
 	* Get Save Button
 	*
 	* @return void
 	*/
	private function save_button( $class = 'top', $item = array() ) {

		if ( 'top' === $class && isset( $item[ 'hide_top_button' ] ) && true === $item[ 'hide_top_button' ] ) {
			return;
		}

		$button_text = __( 'Save changes', 'woocommerce-german-market' );
		if ( isset( $item[ 'save_button_text' ] ) ) {
			$button_text = apply_filters( 'wgm_ui_save_button_text', $item[ 'save_button_text' ] );
		}

		?><input type="submit" name="submit_save_wgm_options" class="save-wgm-options <?php echo $class; ?>" value="<?php echo $button_text; ?>" /><?php
	}

	/**
	* Get Video Div
	*
	* @access public
	* @static
	* @param String $text
	* @param String $url
	* @return String
	*/
	public static function get_video_layer( $url ) {
		return '<div class="wgm-video-wrapper">
					<span class="url">' . $url . '</span>
					<a class="open"><span class="dashicons dashicons-format-video icon"></span>' . __( 'Video', 'woocommerce-german-market' ) .'</a>
					<div class="videoouter">
                        <div class="videoinner">
                            <a class="close">' . __( 'Close', 'woocommerce-german-market' ) .'<span class="dashicons dashicons-no-alt icon"></span></a>
                            <div class="video"></div>
                        </div>
                    </div>
				</div>';
	}

	/**
	* Output type wcreapdf_textarea
	*
	* @since 3.7.2
	* @static
	* @access public
	* @hook woocommerce_admin_german_market_textarea
	* @return void
	*/
	public function output_textarea( $value ) {

		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $value );
		extract( $field_description );

		$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default'] );

		// readonly and example html
		$readonly = '';
		if ( isset( $value[ 'custom_attributes' ] ) ) {
			$custom_attributes	= $value[ 'custom_attributes' ];
			if ( isset ( $custom_attributes[ 'readonly' ] ) ) {
				$readonly = ' readonly ';
			}
			if ( isset ( $custom_attributes[ 'return_html' ] ) ) {
				$option_value = $custom_attributes[ 'return_html' ];
			}
		}
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?></label><?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ) ?>">
				<textarea
					name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					<?php echo $readonly; ?>style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
					class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
					><?php echo esc_textarea( $option_value );  ?></textarea>
					<br /><p class="description"><?php echo $value[ 'desc' ]; ?></p>
			</td>
		</tr><?php
	}

}
