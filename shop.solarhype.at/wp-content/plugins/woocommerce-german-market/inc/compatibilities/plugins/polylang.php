<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Plugin_Compatibility_Polylang
 * @author MarketPress
 */
class WGM_Plugin_Compatibility_Polylang {

	static $instance = NULL;

	static $current_pdf_lang = '';

	/**
	* singleton getInstance
	*
	* @access public
	* @static
	* @return class WGM_Plugin_Compatibility_Polylang
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Plugin_Compatibility_Polylang();
		}
		return self::$instance;
	}

	/**
	* Constructor
	*
	* @access private
	* @return void
	*/
	private function __construct() {

		// add "Polylang Support" menu to German Market UI
		add_filter( 'woocommerce_de_ui_left_menu_items', array( $this, 'add_polylang_menu' ) );

		add_filter( 'german_market_backend_save_texts_only_if_not_equal_to_default_text', '__return false' );

		// Checkout Strings
		$options = WGM_Helper::get_translatable_options();

		foreach ( $options as $option => $default  ) {
			pll_register_string( $option, get_option( $option, $default ), 'German Market: Checkout Option', true );
			add_filter( 'option_' . $option, array( $this, 'translate_woocommerce_checkout_options_polylang' ), 10, 2 );
		}

		// Delivery Times
		add_action( 'init', function() {
			$delivery_times = get_terms( 'product_delivery_times', array( 'orderby' => 'id', 'hide_empty' => 0 ) );
			foreach ( $delivery_times as $term ) {
				pll_register_string( $term->slug, $term->name, 'German Market: Delivery Time', true );
			}
		});

		if ( is_admin() ) {

			$tax_classes = WC_Tax::get_tax_classes();

			$tax_classes[] = 'standard';
			$tax_classes[] = '';

			foreach ( $tax_classes as $tax_class ) {

			 	$rates = WC_Tax::get_rates_for_tax_class( $tax_class );
			 	foreach ( $rates as $rate ) {
			 		$label = $rate->tax_rate_name;
		            pll_register_string( 'tax rate label: ' . $label, $label, 'German Market: WooCommerce Tax Rate', true  );

		            $label_with_percent = $label . sprintf( ' (%s)', str_replace( '.', wc_get_price_decimal_separator(), WC_Tax::get_rate_percent( $rate->tax_rate_id ) ) );
		            pll_register_string( 'tax rate label: ' . $label_with_percent, $label_with_percent, 'German Market: WooCommerce Tax Rate', true  );
			 	}
			}
		}

		add_filter( 'woocommerce_find_rates',	array( $this, 'polylang_translate_woocommerce_find_rates' ), 10 );
		add_filter( 'wgm_translate_tax_label',	array( $this, 'polylang_translate_tax_label' ) );

		add_filter( 'woocommerce_de_get_deliverytime_string_label_string', array( $this, 'polylang_woocommerce_de_get_deliverytime_string_label_string' ), 10, 2 );

		if ( get_option( 'wgm_add_on_woocommerce_invoice_pdf', 'off' ) == 'on' ) {
			add_action( 'wp_wc_invoice_pdf_start_template', 					array( $this, 'polylang_invoice_pdf_admin_download_switch_lang' ) );
			add_action( 'wp_wc_invoice_pdf_end_template', 						array( $this, 'polylang_invoice_pdf_admin_download_reswitch_lang' ) );
			add_action( 'wp_wc_invoice_pdf_email_additional_attachment_before', array( $this, 'polylang_invoice_pdf_admin_download_switch_lang' ) );
			add_action( 'wp_wc_invoice_pdf_before_get_template_page_numbers', 	array( $this, 'polylang_invoice_pdf_admin_download_switch_lang' ) );
			add_action( 'wp_wc_invoice_pdf_after_get_template_page_numbers', 	array( $this, 'polylang_invoice_pdf_admin_download_reswitch_lang' ) );
			add_action( 'wp_wc_invoice_pdf_before_backend_download_switch', 	array( $this, 'polylang_invoice_pdf_admin_download_switch_lang' ) );
		}

		if ( get_option( 'wgm_add_on_woocommerce_return_delivery_pdf', 'off' ) == 'on' ) {
			add_action( 'wcreapdf_pdf_before_create', 	array( $this, 'polylang_retoure_pdf_admin_download_switch_lang' ), 10, 3 );
			add_action( 'wcreapdf_pdf_after_create', 	array( $this, 'polylang_retoure_pdf_admin_download_reswitch_lang' ), 10, 2 );
			add_action( 'wcreapdf_pdf_before_output', 	array( $this, 'polylang_retoure_pdf_admin_download_switch_lang' ), 10, 3 );
			add_action( 'wcreapdf_pdf_after_output', 	array( $this, 'polylang_retoure_pdf_admin_download_reswitch_lang' ), 10, 2 );
		}

		add_action( 'change_locale', function( $locale ) {
			load_plugin_textdomain( Woocommerce_German_Market::$textdomain, FALSE, dirname( Woocommerce_German_Market::$plugin_base_name ) . '/languages');
		});

		// Deactivate saving pdf content
		if ( get_option( 'german_market_polylang_deactivate_saving_pdf_content', 'default' ) == 'deactivate' ) {
			add_action( 'admin_init', function() {
				add_filter( 'wp_wc_invoice_pdf_create_new_but_dont_save', '__return_true' );

				add_filter( 'woocommerce_admin_order_actions', function( $actions, $order ) {

					if ( isset( $actions[ 'invoice_pdf_delete_content' ] ) ) {
						unset( $actions[ 'invoice_pdf_delete_content' ] );
					}

					return $actions;

				}, 10,2 );
			});
		}

		add_filter( 'wp_wc_invoice_pdf_html_before_rendering', function( $html, $args ) {
			if ( isset( $args[ 'order' ] ) ) {
				$html = self::polylang_repair_payment_methods( $html, $args[ 'order' ], $args );
			}
			return $html;
		}, 10, 2 );

		// Repair payment instructions in manual order confirmation
		add_filter( 'gm_manual_order_confirmation_notice_payment_instructions', function( $text, $gateway, $order ) {

			$order_locale = pll_get_post_language( $order->get_id(), 'locale' );

			if ( ! empty( $order_locale ) ) {

				if ( isset( $gateway->settings ) ) {
					$settings = $gateway->settings;
					if ( is_array( $settings ) && isset( $settings[ 'instructions' ] ) && ! empty( $settings[ 'instructions' ] ) ) {

						$instructions_in_order_language = pll_translate_string( $settings[ 'instructions' ], $order_locale );

						if ( isset( $gateway->instructions ) && $gateway->instructions != $instructions_in_order_language ) {
							$text = str_replace( $gateway->instructions, $instructions_in_order_language, $text );
						}

					}
				}
			}

			return $text;

		}, 10, 3 );

		// Preventing caching when outputting taxes for order items
		add_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );

	}

	/**
	* Try to translate saved shipping method title and payment method title in invoice pdf
	*
	* Save WC payment title / shipping method title in backend
	* Translate titles with string translations of polylang (using Polylang for WooCommerce)
	*
	* @since 3.11.1.8
	* @param String $html
	* @param WC_Order
	* @param Array $args
	* @return String
	*/
	public function polylang_repair_payment_methods( $html, $order, $args ) {

		if ( WGM_Helper::method_exists( $order, 'get_payment_method_title' ) ) {

			$order_locale 	= pll_get_post_language( $order->get_id(), 'locale' );
			$used_language 	= $order_locale;

			if ( isset( $args[ 'admin' ] ) && get_option( 'german_market_polylang_pdf_language', 'order_lang' ) != 'order_lang' ) {
				$used_language = get_option( 'german_market_polylang_pdf_language', 'order_lang' );
				if ( ! class_exists( 'Polylang_Woocommerce' ) && 'order_lang' === $order_locale ) {
					$used_language = pll_default_language( 'locale' );
				}
			}

			if ( $order_locale != $used_language ) {

				// translate payment method title
				$order_locale_payment_method_title 	= $order->get_payment_method_title();
				if ( ! empty( $order_locale_payment_method_title ) ) {
					$used_language_payment_method_title	= pll_translate_string( $order_locale_payment_method_title, $used_language );
					if ( $order_locale_payment_method_title != $used_language_payment_method_title ) {
						$html = str_replace( $order_locale_payment_method_title, $used_language_payment_method_title, $html );
					}
				}

				// translate shipping method title
				$order_locale_shipping_method_title = $order->get_shipping_method();
				if ( ! empty( $order_locale_shipping_method_title ) ) {
					$used_language_shipping_method_title = pll_translate_string( $order_locale_shipping_method_title, $used_language );
					if ( $order_locale_shipping_method_title != $used_language_shipping_method_title ) {
						$html = str_replace( $order_locale_shipping_method_title, $used_language_shipping_method_title, $html );
					}
				}
			}
		}

		return $html;
	}

	/**
	* Add "Polylang Support" Menu in German Market UI
	*
	* @since 3.11.1.8
	* @wp-hook woocommerce_de_ui_left_menu_items
	* @param Array $menu
	* @return Array
	*/
	public function add_polylang_menu( $menu ) {
		$menu[ 2 ] = array(
			'title'		=> __( 'Polylang Support', 'woocommerce-german-market' ),
			'slug'		=> 'polylang-support',
			'callback'	=> array( $this, 'polylang_menu' ),
		);
		return $menu;
	}

	/**
	* Render "Polylang Support" Menu in German Market UI
	*
	* @since 3.11.1.8
	* @return void
	*/
	public function polylang_menu() {

		if ( isset( $_REQUEST[ 'german_market_polylang_pdf_language_save' ] ) ) {
			if ( isset( $_POST[ 'german-market-polylang' ] ) && wp_verify_nonce( $_POST[ 'german-market-polylang' ], 'german-market-polylang-settings' ) ) {
				update_option( 'german_market_polylang_pdf_language', $_REQUEST[ 'german_market_polylang_pdf_language' ] );
				update_option( 'german_market_polylang_deactivate_saving_pdf_content', $_REQUEST[ 'german_market_polylang_deactivate_saving_pdf_content' ] );
			}
		}

		?>
		<form method="post">
			<br /><hr />
			<h2><?php echo __( 'PDF Languages', 'woocommerce-german-market' ); ?></h2>

			<?php if ( class_exists( 'Polylang_Woocommerce' ) ) { ?>
				<p>
					<?php echo __( 'By default, all pdf files generated by German Market (invoice pdf, delivery note and return delivery note) are generated in the Polylang order language (you have to use the plugin "Polylang for WooCommerce" to do so). That is the language that the customer has used when finishing the order. If you want to change the language of the files, you have to change the Polylang order language.', 'woocommerce-german-market'  ); ?>
				</p>
			<?php } ?>

			<p>
				<?php echo __( 'You can force German Market to download the respective pdf files in a special language in the backend. Files that are downloaded in the frontend and files sent as email attachements will still be in the order language of the customer. You can set up a special language here:', 'woocommerce-german-market' ); ?>

				<br /><br />
				<select name="german_market_polylang_pdf_language">

					<?php if ( class_exists( 'Polylang_Woocommerce' ) ) {
						?><option value="order_lang"><?php echo __( 'Order language', 'woocommerce-german-market' ); ?></option><?php
					}

					$default = class_exists( 'Polylang_Woocommerce' ) ? 'order_lang' : pll_default_language( 'locale' );
					$current_option = get_option( 'german_market_polylang_pdf_language', 'order_lang' );

					$language_locales = pll_languages_list( array( 'fields' => 'locale' ) );
					$language_names = pll_languages_list( array( 'fields' => 'name' ) );
					foreach ( $language_locales as $key => $locale ) {
						?><option value="<?php echo $locale; ?>" <?php selected( $locale, $current_option ); ?>><?php echo $language_names[ $key ]; ?></option><?php
					}
					?>
				</select>

				<br /><br />

				<?php echo __( 'Important note for invoice pdfs: If an order is marked as completed, the content of the invoice pdf is saved and everytime the pdf is generated, the saved content will be uesed to output the pdf file. That means, that the language of the pdf file won\'t change without deleting the saved content before. You can delete the saved content of a pdf file by clicking the button with the "x" next to the pdf download button in the menu "WooCommerce -> Orders". Nevertheless, you have the opportunity not to save the pdf content at all. To use this opportunity, set the following option to "Deactivate saving invoice pdf content".', 'woocommerce-german-market' );
				?>

				<br /><br />

				<?php $current_option = get_option( 'german_market_polylang_deactivate_saving_pdf_content', 'default' ); ?>

				<select name="german_market_polylang_deactivate_saving_pdf_content" style="min-width: 400px;">
					<option value="default"><?php echo __( 'Default behaviour', 'woocommerce-german-market' ); ?></option>
					<option value="deactivate"  <?php selected( 'deactivate', $current_option ); ?>><?php echo __( 'Deactivate saving invoice pdf content', 'woocommerce-german-market' ); ?></option>
				</select>

				<br /><br />
				<input type="submit" value="<?php echo __( 'Save changes', 'woocommerce-german-market' ); ?>" class="button-secondary" name="german_market_polylang_pdf_language_save" />
				<br /><br />

			</p>

			<?php wp_nonce_field( 'german-market-polylang-settings', 'german-market-polylang' ); ?>
		</form>
		<hr />

		<?php

	}

	/**
	* Polylang Support: Translate WooCommerce Checkout Strings
	*
	* @access public
	* @wp-hook option_{option}
	* @param String $value
	* @param String $option
	* @return String
	*/
	public function translate_woocommerce_checkout_options_polylang( $value, $option ) {

		if ( ! empty( self::$current_pdf_lang ) ) {
			$value = pll_translate_string( $value, self::$current_pdf_lang );
		} else {
			$value = pll__( $value );
		}

		return $value;
	}

	/**
	* Polylang Support: Translate WooCommerce Tax Rates
	*
	* @since 3.11.1.8
	* @access public
	* @wp-hook woocommerce_find_rates
	* @param Array $matched_tax_rates
	* @return Array
	*/
	public function polylang_translate_woocommerce_find_rates( $matched_tax_rates ) {

        foreach ( $matched_tax_rates as &$rate ) {

                if ( $rate[ 'label' ] ) {

                    $has_translation = null;
                	$auto_register = true;

                    $rate[ 'label' ] = pll__( $rate[ 'label' ] );
                }

                unset($rate);

        }

        reset($matched_tax_rates);
        return $matched_tax_rates;
	}

	/**
	* Polylang Support: Translate Tax Labels for order items
	*
	* @since 3.11.1.8
	* @access public
	* @wp-hook wgm_translate_tax_label
	* @param String $tax_label
	* @return String
	*/
	public function polylang_translate_tax_label( $tax_label ) {

		// WPML
		if ( function_exists( 'pll__' ) ) {
			$tax_label = pll__( $tax_label );
		}

		return $tax_label;
	}

	/**
	* Plugin Polylang Support: Show delivery times in the correct way
	*
	* @since v3.5.1
	* @last update v3.8.2
	* @wp-hook woocommerce_de_get_deliverytime_string_label_string
	* @param String $string
	* @param WC_Product $product
	* @return String
	*/
	public function polylang_woocommerce_de_get_deliverytime_string_label_string( $string, $product ) {
		return pll__( $string );
	}

	/**
	* Plugin Polylang Support: Switch PDF Language
	*
	* @since 3.11.1.8
	* @wp-hook wp_wc_invoice_pdf_start_template
	* @wp-hook wp_wc_invoice_pdf_email_additional_attachment_before
	* @wp-hook wp_wc_invoice_pdf_before_get_template_page_numbers
	* @wp-hook wp_wc_invoice_pdf_before_backend_download_switch
	* @param Array $args
	* @return void
	*/
	public function polylang_invoice_pdf_admin_download_switch_lang( $args ) {

		if ( ! apply_filters( 'german_market_polylang_switch_pdf_lang', true ) ) {
			return;
		}

		if ( self::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

		$order 		= $args[ 'order' ];
		$is_test 	= is_string( $args[ 'order' ] ) && $args[ 'order' ] == 'test';

		if ( ! $is_test ) {

			if ( WGM_Helper::method_exists( $order, 'get_meta' ) ) {
				$order_locale = pll_get_post_language( $order->get_id(), 'locale' );

				if ( isset( $args[ 'admin' ] ) && get_option( 'german_market_polylang_pdf_language', 'order_lang' ) != 'order_lang' ) {
					$order_locale = get_option( 'german_market_polylang_pdf_language', 'order_lang' );
					if ( ! class_exists( 'Polylang_Woocommerce' ) && 'order_lang' === $order_locale ) {
						$order_locale = pll_default_language( 'locale' );
					}
				}

				if ( ( ! is_null( $order_locale ) ) && ( ! empty( $order_locale ) ) ) {
					self::$current_pdf_lang = $order_locale;
					switch_to_locale( $order_locale );
				}
			}
		}
	}

	/**
	* Plugin Polylang Support: Re-Switch PDF Language
	*
	* @since 3.11.1.8
	* @wp-hook wp_wc_invoice_pdf_end_template
	* @wp-hook polylang_invoice_pdf_admin_download_reswitch_lang
	* @param Array $args
	* @return void
	*/
	public function polylang_invoice_pdf_admin_download_reswitch_lang( $args ) {

		if ( ! apply_filters( 'german_market_polylang_switch_pdf_lang', true ) ) {
			return;
		}

		if ( self::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	self::$current_pdf_lang = '';
		restore_previous_locale();
	}

	/**
	* Plugin Polylang Support: Switch PDF Language
	*
	* @since 3.11.1.8
	* @wp-hook wp_wc_invoice_pdf_start_template
	* @wp-hook wp_wc_invoice_pdf_email_additional_attachment_before
	* @wp-hook polylang_invoice_pdf_admin_download_switch_lang
	* @wp-hook wp_wc_invoice_pdf_before_backend_download_switch
	* @param Array $args
	* @return void
	*/
	public function polylang_retoure_pdf_admin_download_switch_lang( $delivery_or_retoure, $order, $admin = false ) {

		if ( ! apply_filters( 'german_market_polylang_switch_pdf_lang', true ) ) {
			return;
		}

		if ( self::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	if ( ! WGM_Helper::method_exists( $order , 'get_meta' ) ) {
    		return;
    	}

    	if ( $admin ) {
			$order_locale = pll_get_post_language( $order->get_id(), 'locale' );

			if ( $admin && get_option( 'german_market_wpml_pdf_language', 'order_lang' ) != 'order_lang' ) {
				$order_locale = get_option( 'german_market_wpml_polylang_language', 'order_lang' );
				if ( ! class_exists( 'Polylang_Woocommerce' ) && 'order_lang' === $order_locale ) {
					$order_locale = pll_default_language( 'locale' );
				}
			}

			if ( ( ! is_null( $order_locale ) ) && ( ! empty( $order_locale ) ) ) {
				self::$current_pdf_lang = $order_locale;
				switch_to_locale( $order_locale );
			}
		}
	}

	/**
	* Plugin Polylang Support: Re-Switch PDF Language
	*
	* @since 3.11.1.8
	* @wp-hook wcreapdf_pdf_after_create
	* @wp-hook wcreapdf_pdf_after_output
	* @param Array $args
	* @return void
	*/
	public function polylang_retoure_pdf_admin_download_reswitch_lang( $delivery_or_retoure, $order ) {

		if ( ! apply_filters( 'german_market_polylang_switch_pdf_lang', true ) ) {
			return;
		}

		if ( self::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	self::$current_pdf_lang = '';
		restore_previous_locale();
	}

	/**
	* Polylang Support: Translate PDF files in backend in admin language
	*
	* @since 3.11.1.8
	* @access public
	* @wp-hook pll_is_ajax_on_front (called in this hook)
	* @param Boolean $is_ajax_on_front
	* @return Boolean
	*/
	public static function polylang_is_ajax_on_front( $pll_is_ajax_on_front ) {

		if ( ! apply_filters( 'german_market_polylang_switch_pdf_lang', true ) ) {
			return $pll_is_ajax_on_front;
		}

		if ( ! self::is_frontend_ajax() ) {
			$allowed_actions = array(
				'woocommerce_wp_wc_invoice_pdf_invoice_download',
				'woocommerce_wp_wc_invoice_pdf_refund_download',
				'woocommerce_wcreapdf_download',
				'woocommerce_wcreapdf_download_delivery',
				'sevdesk_woocommerce_edit_shop_order',
				'sevdesk_woocommerce_edit_shop_order_refund',
				'lexoffice_woocommerce_edit_shop_order',
				'lexoffice_woocommerce_edit_shop_order_refund',
				'german_market_manual_order_confirmation',
			);
			if ( isset( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], $allowed_actions ) ) {
				$pll_is_ajax_on_front = false;
			}
		}

		return $pll_is_ajax_on_front;
	}

	/**
	* Returns true if ajax is executed from frontend
	*
	* @since 3.8.1
	* @access public
	* @return Boolean
	*/
    public static function is_frontend_ajax() {

    	$script_filename = isset( $_SERVER[ 'SCRIPT_FILENAME' ] ) ? $_SERVER[ 'SCRIPT_FILENAME' ] : '';

		//Try to figure out if frontend AJAX request... If we are DOING_AJAX; let's look closer
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

	    	$ref = '';

			if ( ! empty( $_REQUEST[ '_wp_http_referer' ] ) ) {
				$ref = wp_unslash( $_REQUEST[ '_wp_http_referer' ] );
			} elseif ( ! empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
				$ref = wp_unslash( $_SERVER[ 'HTTP_REFERER' ] );
			}

			// If referer does not contain admin URL and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request
			if ( ( ( strpos( $ref, admin_url() ) === false ) && ( basename( $script_filename ) === 'admin-ajax.php' ) ) ) {
			  return true;
			}
		}

		return false;
	}
}
