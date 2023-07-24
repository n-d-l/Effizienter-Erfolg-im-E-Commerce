<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WGM_Plugin_Compatibility_WPML
 * @author MarketPress
 */
class WGM_Plugin_Compatibility_WPML {

	static $instance = NULL;

	/**
	* singleton getInstance
	*
	* @access public
	* @static
	* @return class WGM_Plugin_Compatibility_WPML
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Plugin_Compatibility_WPML();
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

		add_filter( 'woocommerce_de_ui_left_menu_items', array( $this, 'add_wpml_menu' ) );

		if ( function_exists( 'icl_register_string' ) && function_exists( 'icl_t' ) && function_exists( 'icl_st_is_registered_string' ) && function_exists( 'wcml_loader' ) ) {
			
			add_filter( 'woocommerce_find_rates',												array( $this, 'translate_woocommerce_find_rates' ), 10 );
			add_filter( 'wgm_translate_tax_label',												array( $this, 'translate_tax_label' ) );
			add_filter( 'woocommerce_de_get_deliverytime_label_term',							array( $this, 'wpml_translate_delivery_times' ), 10, 2 );
			add_filter( 'woocommerce_de_get_deliverytime_label_term_by_term', 					array( $this, 'wpml_translate_delivery_times_order_item' ), 10, 2 );
			add_filter( 'gm_delivery_time_value_in_checkout', 									array( $this, 'wpml_delivery_time_value_in_checkout' ), 10, 2 );
			add_filter( 'german_market_measuring_unit',											array( $this, 'wpml_translate_measuring_unit' ), 10, 3 );
			add_filter( 'german_market_ppu_co_woocommerce_order_formatted_line_subtotal', 		array( $this, 'wpml_translate_measuring_unit_in_order' ), 10, 3 );
			add_filter( 'add_deliverytime_to_order_item', 										array( $this, 'wpml_save_delivery_time_in_default_lang_in_order_items' ), 10, 2 );
			add_filter( 'german_market_used_product_for_price_per_unit',						array( $this, 'wpml_used_product_for_price_per_unit' ) );
			add_filter( 'wp_wc_invoice_pdf_additional_pdf_tac_pages_array',						array( $this, 'wpml_additional_pdf_pages' ) );
			add_filter( 'wp_wc_invoice_pdf_additional_pdf_ecovation_pages_array',				array( $this, 'wpml_additional_pdf_pages' ) );
			add_action( 'wgm_double_opt_in_customer_registration_before_bulk_resend_email',		array( $this, 'wpml_resend_double_opt_in_before' ) );
			add_action( 'wgm_double_opt_in_customer_registration_after_bulk_resend_email',		array( $this, 'wpml_resend_double_opt_in_after' ) );
			add_filter( 'german_market_attribute_name_add_to_cart',								array( $this, 'wpml_german_market_attribute_name_add_to_cart' ), 10, 2 );
			add_filter( 'german_market_get_installation_language', 								array( $this, 'wpml_german_market_get_installation_language' ) );
			add_action( 'admin_notices', 														array( $this, 'admin_notice_translate_nutritional_values' ) );
			add_action( 'admin_notices', 														array( $this, 'admin_notice_general_admin_notice' ) );
			add_filter( 'wcml_js_lock_fields_ids', 												array( $this, 'wpml_js_lock_fields_ids' ) );
			add_filter( 'wcml_js_lock_fields_input_names', 										array( $this, 'wpml_js_lock_fields_names' ) );
			add_filter( 'german_market_requirements_panel_classes', 							array( $this, 'requirements_panel_classes' ) );
			add_filter( 'german_market_term_value_in_backend', 									array( $this, 'term_value_in_backend' ), 10, 4 );
			add_filter( 'german_market_terms_in_backend', 										array( $this, 'terms_in_backend' ), 10, 3 );
			add_filter( 'woocommerce_de_sale_label_term_id_for_frontend', 						array( $this, 'sale_label_term_id_for_frontend' ), 10, 2 );
			add_filter( 'german_market_get_post_meta_value_translatable', 						array( $this, 'get_post_meta_value_translatable' ), 10, 3 );
			add_filter( 'german_market_pa_measuring_terms_in_backend', 							array( $this, 'pa_measuring_terms_in_backend' ), 10, 3 );
			add_filter( 'gm_product_used_product_to_get_translatable_settings', 				array( $this, 'used_product_to_get_translatable_settings' ) );
			add_filter( 'gm_fic_nutritional_values_term_name', 									array( $this, 'fic_nutritional_values_term_name' ), 10, 3 );
			add_filter( 'woocommerce_product_after_variable_attributes',						array( $this, 'locking_fields_for_variations' ) );
			add_action( 'render_german_market_menu_save_options', 								array( $this, 'page_options_in_backend' ) );
			add_action( 'current_screen', 														array( $this, 'remove_language_switcher' ) );
			 
			// Preventing caching when outputting taxes for order items
			add_filter( 'german_market_use_cache_in_add_mwst_rate_to_product_order_item', '__return_false' );

			// Checkout Strings
			$options = WGM_Helper::get_translatable_options();

			// Register Strings
			if ( is_admin() ) {

				add_action( 'current_screen', function() {

					$screen = get_current_screen();

					if ( 
						$screen->id === 'wpml-string-translation/menu/string-translation' ||
						$screen->id === 'woocommerce_page_german-market'
					) {

						$tax_classes = WC_Tax::get_tax_classes();
						$tax_classes[] = 'standard';
						$tax_classes[] = '';

						foreach ( $tax_classes as $tax_class ) {

						 	$rates = WC_Tax::get_rates_for_tax_class( $tax_class );
						 	foreach ( $rates as $rate ) {
						 		$label = $rate->tax_rate_name;

						 		if ( ! icl_st_is_registered_string( 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $label ) ) {
			                        do_action( 'wpml_register_single_string', 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $label, $label, false, WGM_Helper::get_installation_language() );
			                    }

			                    $label_with_percent = $label . sprintf( ' (%s)', str_replace( '.', wc_get_price_decimal_separator(), WC_Tax::get_rate_percent( $rate->tax_rate_id ) ) );
			                    if ( ! icl_st_is_registered_string( 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $label_with_percent ) ) {
			                    	do_action( 'wpml_register_single_string', 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $label_with_percent, $label_with_percent, false, WGM_Helper::get_installation_language() );
			                    }
						 	}
						}
					}
				});

				// FIC 
				add_action( 'admin_init', function() {
					if ( 'on' === get_option( 'wgm_add_on_fic', 'off' ) ) {
						$terms = get_terms( 'gm_fic_nutritional_values', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );
						foreach ( $terms as $term ) {
							do_action( 'wpml_register_single_string', 'German Market: Nutritional Values', $term->name, $term->name, false, WGM_Helper::get_installation_language() );
						}
					}
				});
			}

			// legacy
			foreach ( $options as $option => $default ) {

	            if ( ( ! ( is_admin() && isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'german-market' ) ) || ( ! is_admin() ) ) {
	            	add_filter( 'option_' . $option, array( $this, 'translate_woocommerce_checkout_options' ), 10, 2 );
	            	//add_filter( 'default_option_' . $option, array ( $this, 'translate_empty_translate_woocommerce_checkout_options' ), 10, 3 );
	            }

			}

		}

		if ( is_admin() && class_exists( 'WCML_Admin_Menus' ) && class_exists( 'SitePress' ) && ( ( get_option( 'wgm_add_on_woocommerce_invoice_pdf', 'off' ) == 'on' ) || ( get_option( 'wgm_add_on_woocommerce_return_delivery_pdf', 'off' ) == 'on' ) ) ) {

			if ( get_option( 'wgm_add_on_woocommerce_invoice_pdf', 'off' ) == 'on' ) {
				add_action( 'wp_wc_invoice_pdf_start_template', array( $this, 'wpml_invoice_pdf_admin_download_switch_lang' ) );
				add_action( 'wp_wc_invoice_pdf_end_template', array( $this, 'wpml_invoice_pdf_admin_download_reswitch_lang' ) );
				add_action( 'wp_wc_invoice_pdf_email_additional_attachment_before', array( $this, 'wpml_invoice_pdf_admin_download_switch_lang' ) );
				add_action( 'wp_wc_invoice_pdf_before_get_template_page_numbers', array( $this, 'wpml_invoice_pdf_admin_download_switch_lang' ) );
				add_action( 'wp_wc_invoice_pdf_after_get_template_page_numbers', array( $this, 'wpml_invoice_pdf_admin_download_reswitch_lang' ) );
				add_action( 'wp_wc_invoice_pdf_before_backend_download_switch', array( $this, 'wpml_invoice_pdf_admin_download_switch_lang' ) );

				// Deactivate saving pdf content
				if ( get_option( 'german_market_wpml_deactivate_saving_pdf_content', 'default' ) == 'deactivate' ) {
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
			}

			if ( get_option( 'wgm_add_on_woocommerce_return_delivery_pdf', 'off' ) == 'on' ) {
				add_action( 'wcreapdf_pdf_before_create', array( $this, 'wpml_retoure_pdf_admin_download_switch_lang' ), 10, 3 );
				add_action( 'wcreapdf_pdf_after_create', array( $this, 'wpml_retoure_pdf_admin_download_reswitch_lang' ), 10, 2 );
				add_action( 'wcreapdf_pdf_before_output', array( $this, 'wpml_retoure_pdf_admin_download_switch_lang' ), 10, 3 );
				add_action( 'wcreapdf_pdf_after_output', array( $this, 'wpml_retoure_pdf_admin_download_reswitch_lang' ), 10, 2 );
			}

			// Payment Method Problem in WPML
			add_filter( 'wp_wc_invoice_pdf_html_before_rendering', function( $html, $args ) {
				if ( isset( $args[ 'order' ] ) ) {
					$html = self::wpml_repair_payment_methods( $html, $args[ 'order' ], $args );
				}
				return $html;
			}, 10, 2 );

			// Shipping method translation
			add_action( 'wp_wc_invoice_pdf_start_template', function( $args ) {
				if ( isset( $args[ 'order' ] ) ) {
					add_filter( 'woocommerce_get_order_item_totals', array( $this, 'wpml_repair_shipping_method_title' ), 10, 3 );
				}	
			});

			add_action( 'wp_wc_invoice_pdf_end_template', function( $args ) {
				remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'wpml_repair_shipping_method_title' ), 10, 3 );
			});

			// Running invoice number add on: email subject and header
			// are not auto-translated when sending emails in backend
			add_action( 'wp_wc_running_invoice_email_before_replace', function( $value, $option_key, $order ) {

				if ( is_object( $order ) && method_exists( $order, 'get_meta' ) ) {

					$order_language = $order->get_meta( 'wpml_language' );

					if ( ! empty( $order_language ) ) {
						$unfiltered_option_value = apply_filters( 'wpml_unfiltered_admin_string', $value, $option_key );
						$value = apply_filters( 'wpml_translate_single_string', $value, 'admin_texts_' . $option_key, $option_key, $order_language );
					}
				}

				return $value;
				
			}, 10, 3 );

		}
	}

	/**
	 * this helps to translate the shipping method title in invoice pdf
	 * it is not done by wpml
	 * fix only for invoice pdf of german market (not WC E-Mails)
	 * 
	 * @since 3.21
	 * @wp-hook woocommerce_get_order_item_totals
	 * @param Array $total_rows
	 * @param WC_Order $order
	 * @param String $tax_display
	 * @return Array
	 */
	public function wpml_repair_shipping_method_title( $total_rows, $order, $tax_display ) {

		if ( isset( $total_rows[ 'shipping' ] ) && isset( $total_rows[ 'shipping' ][ 'value' ] ) ) {

			if ( is_object( $order ) && method_exists( $order, 'get_shipping_methods' ) ) {
				foreach ( $order->get_shipping_methods() as $m ) {

					

					if ( is_object( $m ) && method_exists( $m, 'get_instance_id' ) ) {
						
						$original_option =  get_option( 'woocommerce_' . $m->get_method_id(). '_' . $m->get_instance_id() . '_settings' );

						$title = isset( $original_option[ 'title' ] ) ? $original_option[ 'title' ] : $m->get_method_title();

						$translated = apply_filters( 'wpml_translate_single_string', $title, 'admin_texts_woocommerce_shipping', $m->get_method_id().$m->get_instance_id().'_shipping_method_title' );

						
						$total_rows[ 'shipping' ][ 'value' ] = str_replace( $m->get_method_title(), $translated, $total_rows[ 'shipping' ][ 'value' ] );

					}
				}
			}	
		}

		return $total_rows;
	}

	/**
	* Add "WPML Support" Menu in German Market UI
	*
	* @since v3.9.2
	* @wp-hook woocommerce_de_ui_left_menu_items
	* @param Array $menu
	* @return Array
	*/
	public function add_wpml_menu( $menu ) {
		$menu[ 1 ] = array(
			'title'		=> __( 'WPML Support', 'woocommerce-german-market' ),
			'slug'		=> 'wpml-support',
			'callback'	=> array( $this, 'wpml_menu' ),
		);
		return $menu;
	}

	/**
	* Render "WPML Support" Menu in German Market UI
	*
	* @since v3.9.2
	* @return void
	*/
	public function wpml_menu() {

		global $sitepress;
		$current_language = apply_filters( 'wpml_current_language', NULL );

		?>
		<p>
			<?php echo sprintf( 
				__( 'You can find a series of tutorial videos (in German language) on translating German Market with WPML at the following link: %s', 'woocommerce-german-market' ),
				'<a href="https://marketpress.de/dokumentation/german-market/german-market-fuer-woocommerce-mit-wpml-uebersetzen/" target="_blank">' . __( 'Tutorial videos', 'woocommerce-german-market' ) . '</a>'
				); ?>
		</p>

		<h2><?php echo __( 'Admin Options', 'woocommerce-german-market' ); ?></h2>

		<p>
			<?php echo __( 'There are several admin options in German Market that you may want to translate with WPML. Follow the following steps to translate these options. ', 'woocommerce-german-market' ); ?>
		</p>

		<p>
			<ol>
				<li><?php echo( __( 'Configure WPML', 'woocommerce-german-market' ) ); ?></li>
				<li><?php echo( __( 'Activate "WPML String Translation"', 'woocommerce-german-market' ) ); ?></li>
				<li><?php echo( __( 'Set all German Market Options in your default WPML language', 'woocommerce-german-market' ) ); ?></li>
				<li><?php echo( __( 'Translate the German Market Options with WPML String Translation.', 'woocommerce-german-market' ) ); ?></li>
			</ol>
		</p>

		<p>
			<?php echo __( 'If you have done the first three steps you will see a table below that shows you all German Market options that can be translated with WPML String Translations. This table helps you to find the corresponding strings in WPML. In the right column of each row you will find a link to WPML String Translation to get the correct string that you want to translate.', 'woocommerce-german-market' ); ?>
		</p>

		<p>
			<?php echo __( 'Consider, if you change an admin option you have to update your translation for this option.', 'woocommerce-german-market' ); ?>
		</p>

		<?php

		if ( function_exists( 'icl_register_string' ) && function_exists( 'icl_t' ) && function_exists( 'icl_st_is_registered_string' ) ) {

			global $sitepress;

			$default_language = $sitepress->get_default_language();
			$current_language = apply_filters( 'wpml_current_language', NULL );

			$options = WGM_Helper::get_translatable_options();

			$counter = 0;
			?>
			<table class="widefat">

				<tr>
					<th style="font-weight: bold;"><?php echo __( 'Name', 'woocommerce-german-market' ); ?></th>
					<th style="font-weight: bold;"><?php echo __( 'Value', 'woocommerce-german-market' ); ?></th>
					<th style="font-weight: bold;"><?php echo __( 'Translate in WPML String Translations', 'woocommerce-german-market' ); ?></th>
				</tr>

				<?php
				foreach ( $options as $key => $value ) {

					$current_option = get_option( $key );
					if ( empty( $current_option ) ) {
						continue;
					}

					$style = $counter%2 == 0 ? 'background-color: #ddd;' : '';
					?>
					<tr>
						<td style="<?php echo $style; ?>"><?php echo $key; ?></td>
						<td style="<?php echo $style; ?>"><?php echo get_option( $key ); ?></td>
						<td style="<?php echo $style; ?>"><a href="<?php echo get_admin_url(); ?>admin.php?page=wpml-string-translation/menu/string-translation.php&context=admin_texts_<?php echo $key; ?>" target="_blank">WPML String Translation</a></td>
					</tr>

					<?php

					$counter++;
				}
				?>
			</table>

			<br />
			<form method="post">

			<p>
				<?php

				echo __( 'You can add the default German or English translations of your German Market settings by clicking one of the buttons below. First, set up all your German Market options in your default language. For instance, you have already set up all German Market settings in English. Afterwards, you can add the default German translations to the "WPML String translation" by clicking the button "Install default German translations of strings".', 'woocommerce-german-market' );
				?>

				<br /><br />
				<strong>
					<?php echo __( 'Existing string translations will be replaced!', 'woocommerce-german-market' );

					?>
				</strong>
			</p>

			<input type="submit" value="<?php echo __( 'Install default German translations of strings', 'woocommerce-german-market' ); ?>" class="button-secondary" name="german_market_install_wpml_translations_de" />
			<input type="submit" value="<?php echo __( 'Install default English translations of strings', 'woocommerce-german-market' ); ?>" class="button-secondary" name="german_market_install_wpml_translations_en" />

			<?php

			// install string translations
			if ( isset( $_REQUEST[ 'german_market_install_wpml_translations_de' ] ) || isset( $_REQUEST[ 'german_market_install_wpml_translations_en' ] ) ) {

				if ( isset( $_REQUEST[ 'german_market_install_wpml_translations_de' ] ) ) {
					$language = 'de';
				} else {
					$language = 'en';
				}

				$sitepress->switch_lang( $language );
				$options = WGM_Helper::get_translatable_options();

				foreach ( $options as $key => $value_in_translation_language ) {

					$sitepress->switch_lang( $default_language );
					$value_in_default_language = get_option( $key );
					$sitepress->switch_lang( $language );

					if ( empty( $value_in_default_language ) ) {
						continue;
					}

					$string_id = icl_get_string_id( $value_in_default_language, 'admin_texts_' . $key );
					icl_add_string_translation( $string_id, $language, $value_in_translation_language, ICL_TM_COMPLETE );

				}

				$sitepress->switch_lang( $current_language ); // to do

				if ( isset( $_REQUEST[ 'german_market_install_wpml_translations_de' ] ) ) {
					$success_text = __( 'German string translations have been added!', 'woocommerce-german-market' );
				} else {
					$success_text = __( 'English string translations have been added!', 'woocommerce-german-market' );
				}

				?>
				<div class="notice notice-success is-dismissible">
			        <p><?php echo $success_text; ?></p>
			    </div><?php

			}

 			?>
 			</form>

 			<?php
 			if ( function_exists( 'german_market_fic_init' ) ) {

 				?>
 				<br /><hr />
 				<h2><?php echo __( 'Nutritional Values (FIC Add-On)', 'woocommerce-german-market' ); ?></h2>

 				<p>
				<?php echo sprintf( __( 'To translate the names of the nutritional values, please use <a href="%s">WPML String Translation</a>. The nutritional values can be find within the domain "German Market: Nutritional Values".', 'woocommerce-german-market' ), get_admin_url() . 'admin.php?page=wpml-string-translation/menu/string-translation.php&context=German Market: Nutritional Values' ); ?>
 				</p>
 				<?php
 			}

 			?>

 			<?php

			if ( isset( $_REQUEST[ 'german_market_wpml_pdf_language_save' ] ) ) {
				if ( isset( $_POST[ 'german-market-wpml' ] ) && wp_verify_nonce( $_POST[ 'german-market-wpml' ], 'german-market-wpml-settings' ) ) {
					update_option( 'german_market_wpml_pdf_language', $_REQUEST[ 'german_market_wpml_pdf_language' ] );
					update_option( 'german_market_wpml_deactivate_saving_pdf_content', $_REQUEST[ 'german_market_wpml_deactivate_saving_pdf_content' ] );
				}
			}

			?>
			<form method="post">
				<br /><hr />
				<h2><?php echo __( 'PDF Languages', 'woocommerce-german-market' ); ?></h2>

				<p>
					<?php echo __( 'By default, all pdf files generated by German Market (invoice pdf, delivery note, return delivery note and SEPA mandate pdf) are generated in the WPML order language. That is the language that the customer has used when finishing the order. If you want to change the language of the files, you have to change the WPML order language.', 'woocommerce-german-market'  ); ?>
				</p>

				<p>
					<?php echo __( 'You can force German Market to download the respective pdf files in a special language in the backend. Files that are downloaded in the frontend and files sent as email attachements will still be in the order language of the customer. You can set up a special language here:', 'woocommerce-german-market' ); ?>

					<br /><br />
					<select name="german_market_wpml_pdf_language">
						<option value="order_lang"><?php echo __( 'Order language', 'woocommerce-german-market' ); ?></option>
						<?php

						$current_option = get_option( 'german_market_wpml_pdf_language', 'order_lang' );
						$languages = apply_filters( 'wpml_active_languages', NULL, array( 'skip_missing' => 0 ) );
						foreach ( $languages as $lang ) {
							?><option value="<?php echo $lang[ 'language_code' ]; ?>" <?php selected( $lang[ 'language_code' ], $current_option ); ?>><?php echo $lang[ 'translated_name' ]; ?></option><?php
						}
						?>
					</select>

					<br /><br />

					<?php echo __( 'Important note for invoice pdfs: If an order is marked as completed, the content of the invoice pdf is saved and everytime the pdf is generated, the saved content will be uesed to output the pdf file. That means, that the language of the pdf file won\'t change without deleting the saved content before. You can delete the saved content of a pdf file by clicking the button with the "x" next to the pdf download button in the menu "WooCommerce -> Orders". Nevertheless, you have the opportunity not to save the pdf content at all. To use this opportunity, set the following option to "Deactivate saving invoice pdf content".', 'woocommerce-german-market' );
					?>

					<br /><br />

					<?php $current_option = get_option( 'german_market_wpml_deactivate_saving_pdf_content', 'default' ); ?>

					<select name="german_market_wpml_deactivate_saving_pdf_content" style="min-width: 400px;">
						<option value="default"><?php echo __( 'Default behaviour', 'woocommerce-german-market' ); ?></option>
						<option value="deactivate"  <?php selected( 'deactivate', $current_option ); ?>><?php echo __( 'Deactivate saving invoice pdf content', 'woocommerce-german-market' ); ?></option>
					</select>

					<br /><br />
					<input type="submit" value="<?php echo __( 'Save changes', 'woocommerce-german-market' ); ?>" class="button-secondary" name="german_market_wpml_pdf_language_save" />
					<br /><br />

				</p>

				<?php wp_nonce_field( 'german-market-wpml-settings', 'german-market-wpml' ); ?>

			</form>
			<hr />

			<?php

		}

	}

	/**
	* WPML Support: Translate WooCommerce Tax Rates for WPML
	*
	* @access public
	* @wp-hook woocommerce_find_rates
	* @param Array $matched_tax_rates
	* @return Array
	*/
	public function translate_woocommerce_find_rates( $matched_tax_rates ) {

		global $sitepress;

        foreach( $matched_tax_rates as &$rate ) {

                if ( $rate[ 'label' ] ) {

                    $has_translation = null;
                	$auto_register = true;
					$lang = $sitepress->get_current_language();

                    $rate[ 'label' ] = icl_t( 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $rate[ 'label' ], $rate[ 'label' ], $has_translation, $auto_register, $lang );
                }

                unset($rate);

        }

        reset($matched_tax_rates);

        return $matched_tax_rates;

	}

	/**
	* WPMP Support: Translate Tax Labels for order items
	*
	* @access public
	* @wp-hook option_{wgm_translate_tax_label}
	* @param String $tax_label
	* @return String
	*/
	public function translate_tax_label( $tax_label ) {

		// WPML
		if ( function_exists( 'icl_register_string' ) && function_exists( 'icl_t' ) && function_exists( 'icl_st_is_registered_string' ) ) {
			$tax_label = icl_t( 'German Market: WooCommerce Tax Rate', 'tax rate label: ' . $tax_label, $tax_label );
		}

		return $tax_label;
	}

	/**
	* WPML: Translate delivery times for order items
	*
	* @since v3.12.6
	* @wp-hook woocommerce_de_get_deliverytime_label_term_by_term
	* @param Object $label_term
	* @param WC_Order_Item $order_item
	* @return Object
	*/
	public function wpml_translate_delivery_times_order_item( $label_term, $order_item ) {

		if ( apply_filters( 'gm_wpml_has_wrong_settings_for_label_terms', false ) ) {
			return $label_term;
		}

		global $sitepress;

		if ( is_object( $label_term ) && isset( $label_term->term_id ) ) {
			$new_term_id = icl_object_id( $label_term->term_id, 'product_delivery_times', true, $sitepress->get_current_language() );
			$label_term = get_term( $new_term_id, 'product_delivery_times' );
		}

		return $label_term;
	}

	/**
	* WPML: Translate delivery times
	*
	* @since v3.5.5
	* @wp-hook woocommerce_de_get_deliverytime_label_term
	* @param Object $label_term
	* @param WC_Product $product
	* @return void
	*/
	public function wpml_translate_delivery_times( $label_term, $product ) {

		global $sitepress, $woocommerce_wpml;

		if ( apply_filters( 'gm_wpml_has_wrong_settings_for_label_terms', false ) ) {
			return $label_term;
		}

		if ( ! WGM_Helper::method_exists( $product, 'get_id' ) ) {
			return $label_term;
		}
		
		if ( is_null( $woocommerce_wpml->products ) ) {
			return $label_term;
		}
		
		$original_product_id = $woocommerce_wpml->products->get_original_product_id( $product->get_id() );

		if ( $original_product_id > 0 ) {
			$term_id = WGM_Template::get_term_id_from_product_meta( '_lieferzeit', wc_get_product( $original_product_id ) );
			
			if ( is_numeric( $term_id ) ) {
				$new_term_id = icl_object_id( $term_id, 'product_delivery_times', true, $sitepress->get_current_language() );
				$label_term = get_term( $new_term_id, 'product_delivery_times' );
			} else {
				$label_term = null;
			}
		}

		return $label_term;
	}

	/**
	* WPML: Translate delivery times in cart and checkout
	*
	* @since v3.16
	* @wp-hook gm_delivery_time_value_in_checkout
	* @param String $delivery_time
	* @param Array $cart_item
	* @return String
	*/
	function wpml_delivery_time_value_in_checkout ( $delivery_time, $cart_item ) {

		if ( isset( $cart_item[ 'data' ] ) ) {
			$product = $cart_item[ 'data' ];
			if ( is_object( $product ) ) {
				$delivery_time = WGM_Template::get_deliverytime_string( $product );
			}
		}

		return $delivery_time;
	}

	/**
	* WPML: Translate Measuring Unit
	*
	* @since v3.11
	* @wp-hook german_market_measuring_unit
	* @param String $unit
	* @param Boolean|String $translation_lang
	* @return String
	*/
	public function wpml_translate_measuring_unit( $unit, $product, $translation_lang = false ) {

		global $sitepress, $woocommerce_wpml;
		$new_unit = $unit;
		
		if ( is_null( $woocommerce_wpml->products ) ) {
			return $unit;
		}
		
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
				
			$original_product_id = $woocommerce_wpml->products->get_original_product_id( $product->get_id() );
			
			$value = get_post_meta( $original_product_id, '_unit_regular_price_per_unit', TRUE );
			$args = array( 'element_id' => $original_product_id, 'element_type' => 'post' );
			$language_code = apply_filters( 'wpml_element_language_code', null, $args );

			$current_lang = $sitepress->get_current_language();
			$sitepress->switch_lang( $language_code );
			$terms = get_terms( 'pa_measuring-unit', 'orderby=name&hide_empty=0' );
			$sitepress->switch_lang( $current_lang );

			if ( is_wp_error( $terms ) ) {
				return $unit;
			}

			foreach ( $terms as $term ) {

				if ( $term->name === $value ) {
					$new_term = get_term( $term->term_id );
					$new_unit = $new_term->name;
					break;
				}
			}
		}

		if ( $new_unit === $unit ) {

			// legacy: using string translations
			$lang = $sitepress->get_current_language();
			$default_lang = $sitepress->get_default_language();

			if ( $lang == $default_lang ) {
				return $unit;
			}

			if ( $translation_lang ) {
				$lang = $translation_lang;
			}

			$unit = apply_filters( 'wpml_translate_single_string', $unit, 'German Market: Measuring unit name', $unit, $lang );

		} else {
			$unit = $new_unit;
		}

		return $unit;
	}

	/**
	* WPML: Translate Measuring Unit
	*
	* @since v3.11
	* @wp-hook german_market_ppu_co_woocommerce_order_formatted_line_subtotal
	* @param String $unit_string
	* @param WC_Order_Item $item
	* @param WC_Order $order
	* @param Boolean|String $translation_lang
	* @return String
	*/
	public function wpml_translate_measuring_unit_in_order( $unit_string, $item, $order, $translation_lang = false ) {

		global $sitepress;
		$lang = $sitepress->get_current_language();
		$default_lang = $sitepress->get_default_language();

		if ( $translation_lang ) {
			$lang = $translation_lang;
		}

		$order_language = $order->get_meta( 'wpml_language' );
		if ( empty( $order_language ) ) {
			$order_language = $lang;
		}

		if ( $order_language === $lang ) {
			return $unit_string;
		}

		$units_order_language 	= array();
		$units_current_language = array();

		$sitepress->switch_lang( $default_lang );
		$units = get_terms( 'pa_measuring-unit', array( 'orderby' => 'slug', 'hide_empty' => 0 ) );
		$sitepress->switch_lang( $lang );

		if ( is_wp_error( $units ) ) {
			return $unit_string;
		}

		foreach ( $units as $unit ) {
			if ( strlen( $unit->name ) >= 3 ) {
				$units_order_language[]   = apply_filters( 'wpml_translate_single_string', $unit->name, 'German Market: Measuring unit name', $unit->name, $order_language );
				$units_current_language[] = apply_filters( 'wpml_translate_single_string', $unit->name, 'German Market: Measuring unit name', $unit->name, $lang );
			}
		}

		$count = 1;
		$unit_string = str_replace( $units_order_language, $units_current_language, $unit_string, $count );

		return $unit_string;
	}

	/**
	* WPML: Save delivery time in order item meta, id of term in default wpml language
	*
	* @since v3.8.2
	* @wp-hook add_deliverytime_to_order_item
	* @param Integer $term_id
	* @param WC_Order_Product $product
	* @return Array
	*/
	public function wpml_save_delivery_time_in_default_lang_in_order_items( $term_id, $product ) {

		global $sitepress;
		$default_wpml_language = $sitepress->get_default_language();

		if ( WGM_Helper::method_exists( $product, 'get_id' ) ) {

			$default_language_product_id = icl_object_id( $product->get_id(), get_post_type( $product->get_id() ), false, $default_wpml_language );
			if ( $default_language_product_id > 0 ) {
				$new_term_id = icl_object_id( $term_id, 'product_delivery_times', true, $default_language_product_id );
				$label_term = get_term( $new_term_id, 'product_delivery_times' );
			}
		}

		return $term_id;
	}

	/**
	* WPML: Get translated product to get copied price per unit
	*
	* @since v3.9.2
	* @wp-hook wpml_used_product_for_price_per_unit
	* @param WC_Product $_product
	* @return WC_Product
	*/
	public function wpml_used_product_for_price_per_unit( $_product ) {

		if ( WGM_Helper::method_exists( $_product, 'get_id' ) ) {
			$default_lang = apply_filters( 'wpml_default_language', NULL );
			$id = $_product->get_id();
			$id = apply_filters( 'wpml_object_id', $id, 'product', true, $default_lang );
			$_product = wc_get_product( $id );
		}

		return $_product;
	}

	/**
	* WPML: Translate Pages of Addional PDFs in Invoice PDF Add-On
	*
	* @since v3.8.2
	* @wp-hook wp_wc_invoice_pdf_additional_pdf_ecovation_pages_array
	* @wp-hook wp_wc_invoice_pdf_additional_pdf_tac_pages_array
	* @param Array $pages
	* @return Array
	*/
	public function wpml_additional_pdf_pages( $pages ) {

		if ( function_exists( 'icl_object_id' ) ) {
			foreach ( $pages as $key => $page ) {
				$pages[ $key ] = icl_object_id( $page->ID );
			}
		}

		return $pages;
	}

	/**
	* WPML Support: Switch language of doubple-opt-in resender
	*
	* @since 3.9.2
	* @wp-hook wgm_double_opt_in_customer_registration_before_bulk_resend_email
	* @param Integer $user_id
	* @return void
	*/
    public function wpml_resend_double_opt_in_before( $user_id ) {

    	global $sitepress;
    	$user_language = get_user_meta( $user_id, '_wgm_double_opt_in_activation_lang', true );
    	$user_language_array = explode( '_', $user_language );

    	if ( is_array( $user_language_array ) ) {
    		$user_language = $user_language_array[ 0 ];
    	}

    	if ( ! empty( $user_language ) ) {
    		$sitepress->switch_lang( $user_language );
    	}
    }

    /**
	* WPML Support: Switch language of doubple-opt-in resender
	*
	* @since 3.9.2
	* @wp-hook wgm_double_opt_in_customer_registration_after_bulk_resend_email
	* @param Integer $user_id
	* @return void
	*/
    public function wpml_resend_double_opt_in_after( $user_id ) {

    	global $sitepress;
    	$sitepress->switch_lang( $sitepress->get_default_language() );
    }

    /**
	* WPML Support: Translate product attribute names when attributes are saved when add to cart
	*
	* @since 3.10.2
	* @param String $attribute_name
	* @param WC_Product $product
	* @return String
	*/
    public function wpml_german_market_attribute_name_add_to_cart( $attribute_name, $product ) {

    	global $woocommerce_wpml;
    	if ( $woocommerce_wpml->strings ) {
    		$attribute_name =  $woocommerce_wpml->strings->translated_attribute_label( $attribute_name, $attribute_name, $product );
    	}

    	return $attribute_name;
    }

    /**
	* WPML Support: Translate Empty WooCommerce Checkout Strings
	*
	* @access public
	* @wp-hook option_{option}
	* @param String $value
	* @param String $option
	* @return String
	*/
	public function translate_empty_translate_woocommerce_checkout_options( $default, $option, $passed_default ) {

		global $sitepress;
		$default_lang = $sitepress->get_default_language();

		$default_sentence 	= $this->translate_woocommerce_checkout_options( $default, $option, $default_lang );
		$translation 		= $this->translate_woocommerce_checkout_options( $default, $option );

		if ( $translation != $default_sentence ) {
			$default = $translation;
		}

		return $default;
	}

	/**
	* WPML Support: Translate WooCommerce Checkout Strings
	*
	* @access public
	* @wp-hook option_{option}
	* @param String $value
	* @param String $option
	* @return String
	*/
	public function translate_woocommerce_checkout_options( $value, $option, $translation_lang = false ) {

		global $sitepress;
		$lang = $sitepress->get_current_language();
		$default_lang = $sitepress->get_default_language();

		if ( $lang == $default_lang ) {
			return $value;
		}

		if ( $translation_lang ) {
			$lang = $translation_lang;
		}

		if ( str_replace( 'wp_wc_invoice_pdf_', '', $option ) != $option ) {
			$value = apply_filters( 'wpml_translate_single_string', $value, 'German Market: Invoice PDF', $option, $lang );

		} else if ( str_replace( 'wp_wc_running_invoice_', '', $option ) != $option ) {
			$value = apply_filters( 'wpml_translate_single_string', $value, 'German Market: Running Invoice Number', $option, $lang );

		} else if ( ( str_replace( 'woocomerce_wcreapdf_wgm_', '', $option ) != $option ) || ( str_replace( 'woocommerce_wcreapdf_wgm_', '', $option ) != $option  ) ) {
			$value = apply_filters( 'wpml_translate_single_string', $value, 'German Market: Return Delivery Note', $option, $lang );

		} else {

			$value = apply_filters( 'wpml_translate_single_string', $value, 'German Market: Checkout Option', $option, $lang );
		}

		return $value;
	}

	/**
	* WPML Support: Switch language of invoice in backend downloads
	*
	* @since 3.8.1
	* @access public
	* @wp-hook wcreapdf_pdf_before_create
	* @param Array $args
	* @return void
	*/
    public function wpml_invoice_pdf_admin_download_switch_lang( $args ) {

    	if ( WGM_Compatibilities::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

		global $sitepress;

		if ( is_array( $args ) && isset( $args[ 'order' ] ) ) {
			$order 		= $args[ 'order' ];
			$is_test 	= is_string( $args[ 'order' ] ) && $args[ 'order' ] == 'test';

			if ( ! $is_test ) {

				if ( WGM_Helper::method_exists( $order, 'get_meta' ) ) {

					$order_language = $order->get_meta( 'wpml_language' );

					if ( isset( $args[ 'admin' ] ) && get_option( 'german_market_wpml_pdf_language', 'order_lang' ) != 'order_lang' ) {
						$order_language = get_option( 'german_market_wpml_pdf_language', 'order_lang' );
					}

					if ( ! empty( $order_language ) ) {
						$sitepress->switch_lang( $order_language );
					}
				}
			}
		}
    }

    /**
	* WPML Support: Reswitch language of invoice pdf in backend downloads
	*
	* @since 3.8.1
	* @access public
	* @wp-hook wp_wc_invoice_pdf_end_template
	* @return void
	*/
    public function wpml_invoice_pdf_admin_download_reswitch_lang() {

    	if ( WGM_Compatibilities::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	global $sitepress;
    	$sitepress->switch_lang( $sitepress->get_default_language() );
    }

    /**
	* WPML Support: Switch language of retoure and delivery pdf in backend downloads
	*
	* @since 3.8.1
	* @access public
	* @wp-hook wcreapdf_pdf_before_create
	* @param Array $settings
	* @param WC_Order $order
	* @return void
	*/
    public function wpml_retoure_pdf_admin_download_switch_lang( $delivery_or_retoure, $order, $admin = false ) {

    	if ( WGM_Compatibilities::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	if ( ! WGM_Helper::method_exists( $order , 'get_meta' ) ) {
    		return;
    	}

		global $sitepress;

		$order_language = $order->get_meta( 'wpml_language' );


		if ( $admin && get_option( 'german_market_wpml_pdf_language', 'order_lang' ) != 'order_lang' ) {
			$order_language = get_option( 'german_market_wpml_pdf_language', 'order_lang' );
		}

		if ( ! empty( $order_language ) ) {
			$sitepress->switch_lang( $order_language );
		}
    }

    /**
	* WPML Support: Reswitch language of retoure and delivery pdf in backend downloads
	*
	* @since 3.8.1
	* @access public
	* @wp-hook wcreapdf_pdf_after_create
	* @param Array $settings
	* @param WC_Order $order
	* @return Array
	*/
    public function wpml_retoure_pdf_admin_download_reswitch_lang( $delivery_or_retoure, $order ) {

    	if ( WGM_Compatibilities::is_frontend_ajax() ) {
    		return;
    	}

    	if ( ! current_user_can( 'manage_woocommerce' ) ) {
    		return;
    	}

    	global $sitepress;
    	$sitepress->switch_lang( $sitepress->get_default_language() );

    }

    /**
	* WPML Support: Gateway Instructions and Titles are not translated as they should
	*
	* @since 3.9.2
	* @param String $content
	* @param WC_Order $order
	* @return Sring
	*/
    public static function wpml_repair_payment_methods( $content, $order, $args ) {
    	if ( WGM_Helper::method_exists( $order, 'get_payment_method' ) ) {

				if ( WGM_Helper::method_exists( $order, 'get_meta' ) ) {

					$order_language = $order->get_meta( 'wpml_language' );

					if ( isset( $args[ 'admin' ] ) && get_option( 'german_market_wpml_pdf_language', 'order_lang' ) != 'order_lang' ) {
						$order_language = get_option( 'german_market_wpml_pdf_language', 'order_lang' );
					}

					if ( ! empty( $order_language ) ) {

						global $sitepress;

						$default_wpml_language 	= $sitepress->get_default_language();
						$current_language 		= $sitepress->get_current_language();

						$payment_method_id 	= $order->get_payment_method();
						$payment_gateways   = WC_Payment_Gateways::instance();
						$all_gateways 		= $payment_gateways->payment_gateways();

						if ( isset( $all_gateways[ $payment_method_id ] ) ) {

							$used_gateway = $all_gateways[ $payment_method_id ];

							$gateway_method_title = $used_gateway->title;

							if ( isset( $used_gateway->instructions ) ) {
								$gateway_method_instructions = $used_gateway->instructions;
							}

							$settings = $used_gateway->settings;

							$settings_language_instructions = isset( $settings[ 'instructions' ] ) ? $settings[ 'instructions' ] : false;
							$settings_language_title 		= $settings[ 'title' ];

							if ( $settings_language_instructions ) {
								$current_language_instructions 	= apply_filters( 'wpml_translate_single_string', $settings_language_instructions, 'admin_texts_woocommerce_gateways', $payment_method_id .'_gateway_instructions', $current_language );
							}

							$current_language_title 		= apply_filters( 'wpml_translate_single_string', $settings_language_title, 'admin_texts_woocommerce_gateways', $payment_method_id .'_gateway_title', $current_language );

							if ( $settings_language_instructions ) {
								$order_language_instructions 	= apply_filters( 'wpml_translate_single_string', $settings_language_instructions, 'admin_texts_woocommerce_gateways', $payment_method_id .'_gateway_instructions', $order_language );
							}

							$order_language_title 			= apply_filters( 'wpml_translate_single_string', $settings_language_title, 'admin_texts_woocommerce_gateways', $payment_method_id .'_gateway_title', $order_language );

							// string replace: payment instructions
							if ( $settings_language_instructions ) {
								$content = str_replace( $current_language_instructions, $order_language_instructions, $content );
							}

							if ( isset( $used_gateway->instructions ) ) {
								if ( isset( $order_language_instructions ) ){
									$content = str_replace( $gateway_method_instructions, $order_language_instructions, $content );
								}
							}

							// string replace: payment title
							if ( ! empty( $order_language_title ) ) {

								$content = str_replace( $current_language_title, $order_language_title, $content );
								$content = str_replace( $gateway_method_title, $order_language_title, $content );

								if ( ! empty($order->get_payment_method_title() ) ) {
									$content = str_replace( $order->get_payment_method_title(), $order_language_title, $content );
								}
							}
						}
					}
				}
			}

			return $content;
    }

    /**
	* When WPML is activated, get default installation language by WPML
	* instead of get_option( 'WPLANG' )
	*
	* @wp-hook german_market_get_installation_language
	* @since 3.16
	* @param String $language
	* @return Sring
	*/
    public function wpml_german_market_get_installation_language( $language ) {

    	global $sitepress;
		$wpml_default_language = $sitepress->get_default_language();
    	
    	if ( ! empty( $wpml_default_language ) ) {
    		$language = $wpml_default_language;
    	}

    	return $language;
    }

    /**
	* Show an admin notice in backend of "nutritional values" taxonomy
	* that WPML String Translation has to be used 
	*
	* @wp-hook admin_notices
	* @since 3.16
	* @return void
	*/
    public function admin_notice_translate_nutritional_values() {

		if ( get_current_screen()->id === 'edit-gm_fic_nutritional_values' ) {

			?>
			<div class="notice notice-info">
				<p>
					<?php echo sprintf( __( 'To translate the names of the nutritional values, please use <a href="%s">WPML String Translation</a>. The nutritional values can be find within the domain "German Market: Nutritional Values".', 'woocommerce-german-market' ), get_admin_url() . 'admin.php?page=wpml-string-translation/menu/string-translation.php&context=German Market: Nutritional Values' ); ?>
				</p>
			</div>
			<?php
		}

	}

	/**
	* Show an admin notice in backend of German Market
	*
	* @wp-hook admin_notices
	* @since 3.16
	* @return void
	*/
	public function admin_notice_general_admin_notice() {

		if ( get_current_screen()->id === 'woocommerce_page_german-market' ) { 

			$is_wpml_support_subtab = false;
			if ( isset( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] === 'wpml-support' ) {
				$is_wpml_support_subtab = true;
			}

			if ( ! $is_wpml_support_subtab ) {

				global $sitepress;
				$default_language = $sitepress->get_default_language();
				$locale = get_locale();
				$language_code = substr( $locale, 0, 2 );
				$default_language_name = apply_filters( 'wpml_translated_language_name', null, $default_language, $language_code )
				
				?>
				<div class="notice notice-info">
					<p>
						<?php echo sprintf( 
							__( '<strong>WPML translation</strong>: Please set all settings in your WPML default language (%s). For page assignments, please also use the corresponding page in the WPML default language. Translation instructions can be found in the submenu <a href="%s">WPML Support</a>.', 'woocommerce-german-market' ),
							$default_language_name,
							get_admin_url() . 'admin.php?page=german-market&tab=wpml-support' 
						); ?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	* translate nutritional values term names
	*
	* @since v3.16
	* @wp-hook gm_fic_nutritional_values_term_name
	* @param String $term_name
	* @param WP_Term $term
	* @param Boolean $is_backend
	* @return String
	*/
	public function fic_nutritional_values_term_name( $term_name, $term, $is_backend = false ) {

		if ( $is_backend ) {
			$locale = get_locale();
			$language_code =  substr( $locale, 0, 2 );
		} else {
			$language_code = apply_filters( 'wpml_current_language', NULL );
		}
		
		return apply_filters( 'wpml_translate_single_string', $term_name, 'German Market: Nutritional Values', $term_name, $language_code );
	}

	/**
	* use original product to get german market settings / output (copy)
	*
	* @since v3.16
	* @wp-hook gm_product_used_product_to_get_translatable_settings
	* @param WC_Product $product
	* @return WC_Product
	*/
	public function used_product_to_get_translatable_settings( $product ) {

		global $sitepress, $woocommerce_wpml;
		
		if ( is_null( $woocommerce_wpml->products ) ) {
			return $product;
		}
		
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			if ( ! $woocommerce_wpml->products->is_original_product( $product->get_id() ) ) {
				$original_product_id = $woocommerce_wpml->products->get_original_product_id( $product->get_id() );
				$product = wc_get_product( $original_product_id );
			}
		}

		return $product;

	}

	/**
	* show correct measuring unit in ppu in translated products in backend
	*
	* @since v3.16
	* @wp-hook german_market_pa_measuring_terms_in_backend
	* @param Array $terms
	* @param Integer $thepostid
	* @param String $value
	* @return Array
	*/
	public function pa_measuring_terms_in_backend( $terms, $thepostid, $value ) {

		$wpml_terms = array();
		global $sitepress, $woocommerce_wpml;

		if ( is_null( $woocommerce_wpml->products ) ) {
			return $terms;
		}

		if ( ! $woocommerce_wpml->products->is_original_product( $thepostid ) ) {
			
			$original_product_id = $woocommerce_wpml->products->get_original_product_id( $thepostid );

			$args = array( 'element_id' => $original_product_id, 'element_type' => 'post' );
			$language_code = apply_filters( 'wpml_element_language_code', null, $args );

			foreach ( $terms as $term ) {

				$translated_term_id = icl_object_id( $term->term_id, 'pa_measuring-unit', true, $language_code );
				$translated_term = get_term( $translated_term_id );
				
				if ( $translated_term->name === $value ) {
					$wpml_terms[] = $term;
					break;
				}
			}

			if ( ! empty( $wpml_terms ) ) {
				$terms = $wpml_terms;
			} else {
				$new_term = new stdClass();
				$new_term->name = $value;
				$new_term->description = $value;
				$terms = array( $new_term );
			}
		}

		return $terms;
	}

	/**
	* copy post meta value from original product for translated product
	*
	* @since v3.16
	* @wp-hook german_market_get_post_meta_value_translatable
	* @param Mixed $meta_value
	* @param Integer $product_id
	* @param String $meta_key
	* @return Mixed
	*/
	public function get_post_meta_value_translatable ( $meta_value, $product_id, $meta_key ) {

		global $sitepress, $woocommerce_wpml;
		
		if ( is_null( $woocommerce_wpml->products ) ) {
			return $meta_value;
		}

		if ( ! $woocommerce_wpml->products->is_original_product( $product_id ) ) {
			$original_product_id = $woocommerce_wpml->products->get_original_product_id( $product_id );
			$meta_value = get_post_meta( $original_product_id, $meta_key, TRUE );
		}
		
		return $meta_value;
	}

	/**
	* translate sale label in frontend
	*
	* @since v3.16
	* @wp-hook woocommerce_de_sale_label_term_id_for_frontend
	* @param Integer $term_id
	* @param WC_Product $product
	* @return Integer
	*/
	public function sale_label_term_id_for_frontend ( $term_id, $product ) {

		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			$id = $product->get_id();

			global $sitepress, $woocommerce_wpml;

			if ( is_null( $woocommerce_wpml->products ) ) {
				return $term_id;
			}

			if ( ! $woocommerce_wpml->products->is_original_product( $id ) ) {
				$original_product_id = $woocommerce_wpml->products->get_original_product_id( $id );
				$term_id = WGM_Template::get_term_id_from_product_meta( '_sale_label', wc_get_product( $original_product_id ) );

				$args = array( 'element_id' => $id, 'element_type' => 'post' );
				$language_code = apply_filters( 'wpml_element_language_code', null, $args );

				$term_id = icl_object_id( $term_id, 'product_sale_labels', true, $language_code );
			}
		}

		return $term_id;
	}

	/**
	* show correct terms translated products in backend
	*
	* @since v3.16
	* @wp-hook german_market_terms_in_backend
	* @param Array $terms
	* @param Integer $id
	* @param WP_Term $the_term
	* @return Array
	*/
	public function terms_in_backend( $terms, $id, $the_term ) {

		global $sitepress, $woocommerce_wpml;

		if ( is_null( $woocommerce_wpml->products ) ) {
			return $terms;
		}

		if ( ! $woocommerce_wpml->products->is_original_product( $id ) ) {
			$term = get_term( $the_term );
			if ( is_object( $term ) ) {
				$terms = array( $term );
			}
		}

		return $terms;
	}

	/**
	* show correct term value in translated products in backend
	*
	* @since v3.16
	* @wp-hook german_market_term_value_in_backend
	* @param WP_Term $value
	* @param Integer $id
	* @param String $meta_key
	* @param String $taxonomy_slug
	* @return WP_Term
	*/
	public function term_value_in_backend( $value, $id, $meta_key, $taxonomy_slug ) {

		global $sitepress, $woocommerce_wpml;

		if ( is_null( $woocommerce_wpml->products ) ) {
			return $value;
		}

		if ( ! $woocommerce_wpml->products->is_original_product( $id ) ) {
			$original_product_id = $woocommerce_wpml->products->get_original_product_id( $id );
			$term_id = WGM_Template::get_term_id_from_product_meta( $meta_key, wc_get_product( $original_product_id ) );

			$args = array( 'element_id' => $id, 'element_type' => 'post' );
			$language_code = apply_filters( 'wpml_element_language_code', null, $args );

			$value = icl_object_id( $term_id, $taxonomy_slug, true, $language_code );
		}

		return $value;
	}

	/**
	* Requirements tab has to be visible in translations
	*
	* @since v3.16
	* @wp-hook german_market_requirements_panel_classes
	* @param Array $classes
	* @return Array
	*/
	public function requirements_panel_classes ( $classes ) {

		global $thepostid;

		$wpml_post_language_details = apply_filters( 'wpml_post_language_details', array(), $thepostid );

		if ( isset( $wpml_post_language_details[ 'different_language' ] ) && true === $wpml_post_language_details[ 'different_language' ] ) {
			if ( ! isset( $wpml_post_language_details[ 'language_code' ] ) ) {
				// it's a new translation => show tab
				$classes = array();
			}
		}

		return $classes;
	}

	/**
	* Lock Fields in Product Backend
	*
	* @since v3.16
	* @wp-hook wcml_js_lock_fields_ids
	* @param Array $ids
	* @return Array
	*/
	public function wpml_js_lock_fields_ids( $ids ) {
		$ids[] = '_digital';
		$ids[] = '_lieferzeit';
		$ids[] = '_sale_label';
		$ids[] = '_suppress_shipping_notice';
		$ids[] = '_unit_regular_price_per_unit';
		$ids[] = '_gm_product_depending_checkbox';
		$ids[] = '_age_rating_age';
		$ids[] = '_alcohol_value';
		$ids[] = '_regular_price_per_unit';
		$ids[] = '_sale_price_per_unit';
		$ids[] = '_unit_sale_price_per_unit';
		return $ids;
	}

	/**
	* Lock Fields in Product Backend
	*
	* @since v3.16
	* @wp-hook wcml_js_lock_fields_input_names
	* @param Array $ids
	* @return Array
	*/
	public function wpml_js_lock_fields_names( $names ) {
		$names[] = '_unit_regular_price_per_unit';
		$names[] = '_auto_ppu_complete_product_quantity';
		$names[] = '_unit_regular_price_per_unit_mult';
		$names[] = '_unit_sale_price_per_unit_mult';
		return $names;
	}

	/**
	* Lock Fields in Product Backend - For Variations
	*
	* @since v3.16
	* @wp-hook woocommerce_product_after_variable_attributes
	* @return void
	*/
	public function locking_fields_for_variations() {

		global $woocommerce_wpml;
     	
     	if ( is_null( $woocommerce_wpml->products ) ) {
			return;
		}
		
	    //Get the product ID
	    if ( isset( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] ) ) {
	        $product_id = $_GET['post'];
	    } elseif ( isset( $_POST['action'], $_POST['product_id'] ) && 'woocommerce_load_variations' === $_POST['action'] ) {
	        $product_id = $_POST['product_id'];
	    }
	 
	    if ( ! $product_id ) {
	            return; //Return if no product ID
	    } elseif ( ! $woocommerce_wpml->products->is_original_product( $product_id ) && 'auto-draft' !== get_post_status( $product_id ) ) {
	        // Only add if the product is translation.
	        ?>
	        <script type="text/javascript">
	            jQuery('.variable_used_setting_ppu, .variable_is_digital, .german-market-variation-input-not-translatable' ).each(function(){
	                if (!jQuery(this).prop('readonly')) {
	                    jQuery(this).prop('readonly', true);
	                    jQuery(this).prop('disabled', true);
	                    jQuery(this).after(jQuery('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
	                }
	            });
	        </script>
	        <?php
	    }
	}

	/**
	* For "Select Fields of WordPress Pages"
	* Turn off WPML that exclude all pages of other languages
	*
	* @since v3.16
	* @wp-hook render_german_market_menu_save_options
	* @return void
	*/
	public function page_options_in_backend() {
		global $sitepress;
		remove_filter( 'get_pages', array( $sitepress, 'exclude_other_language_pages2' ), 10, 2 );
	}

	/**
	* Don't show language switcher in German Market Settings
	*
	* @since v3.16
	* @wp-hook current_screen
	* @return void
	*/
	public function remove_language_switcher() {
		$screen = get_current_screen();
		
		if ( $screen->id == 'woocommerce_page_german-market' ) {
			global $sitepress;
			remove_action( 'wp_before_admin_bar_render', array( $sitepress, 'admin_language_switcher' ) );
		}

	}

}
