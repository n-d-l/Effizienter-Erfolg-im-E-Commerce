<?php
/**
 * Feature Name: Adding VAT field to Admin & My Account
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com
 */

class WC_VAT_Field_Integration {

	/**
	 * Updating custom VAT field.
	 *
	 * @wp-hook personal_options_update

	 * @access public
	 * @static
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public static function update_profile_billing_vat_field( $user_id ) {

		if ( ! ( current_user_can( 'edit_user', $user_id ) && current_user_can( 'manage_woocommerce' ) ) ) {
			return;
		}

		update_user_meta( $user_id, 'billing_vat', sanitize_text_field( $_POST[ 'billing_vat' ] ) );
	}

	/**
	 * Add fields to admin area.
	 *
	 * @wp-hook show_user_profile, edit_user_profile
	 *
	 * @access public
	 * @static
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public static function add_vat_field_to_user_section( $user ) {

		$user_id = $user->ID;

		if ( ! ( current_user_can( 'edit_user', $user_id ) && current_user_can( 'manage_woocommerce' ) ) ) {
			return;
		}

		$vat = get_user_meta( $user_id, 'billing_vat', true );

		?>
		<h2><?php echo __( '"EU VAT Number Check" Add-on of German Market', 'woocommerce-german-market' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="billing_vat"><?php echo __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ); ?></label></th>
				<td>
					<input type="text" name="billing_vat" id="billing_vat" value="<?php echo $vat; ?>" class="regular-text">
					<p class="description"></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Add fields to admin area.
	 *
	 * @hook woocommerce_account_edit-address_endpoint
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function add_vat_field_to_my_account_page() {

		// Only on account pages
		if ( ! is_account_page() ) return false;

		global $wp;
		if ( isset( $wp->query_vars[ 'edit-address' ] ) && ( ! empty( $wp->query_vars[ 'edit-address' ] ) ) ) {
			return;
		}

		$user    = wp_get_current_user();
		$user_id = $user->ID;
		
		$vat = get_user_meta( $user_id, 'billing_vat', true );
		$label = get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) );

		?>
		
			<form method="post" name="save-billing-vat-form" id="save_billing_vat_form">
				
				<div class="woocommerce-address-fields">
					
					<div class="woocommerce-address-fields__field-wrapper">
						<?php 

						$field_args = apply_filters( 'wcvat_vat_myaccount_field_args', array(
							'type'			=> 'text',
							'label'			=> $label,
							'placeholder'	=> $label,
						) );

						woocommerce_form_field( 'billing_vat', $field_args, esc_attr( $vat ) );

						wp_nonce_field( 'save_billing_vat_action', 'billing_vat_nonce_' . $user_id, false );
						?>
					</div>

				<?php
				$button_markup = apply_filters( 'wcvat_vat_myaccount_button_markup', '
				<p>
					<button type="submit" class="woocommerce-button button" name="save_account_details">
						%s
					</button>
				</p>' );

				$button_text = apply_filters( 'wcvat_vat_myaccount_button_text', sprintf( _x( 'Save %s', 'save-vat-number-my-account', 'woocommerce-german-market' ), $label ), $label );

				echo apply_filters( 'wcvat_vat_myaccount_button_output', sprintf( $button_markup, $button_text ), $button_markup, $button_text, $label );

				?>
				</div>
			</form>
		<?php
	}

	/**
	 * Save vat number as user meta 
	 *
	 * @hook woocommerce_account_edit-wp
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function save_vat_field_on_my_account_page() {

		if ( ! is_account_page() ) return false;

		global $wp;
		if ( isset( $wp->query_vars[ 'edit-address' ] ) && ( ! empty( $wp->query_vars[ 'edit-address' ] ) ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( isset( $_POST[ 'billing_vat_nonce_' . $user_id ] ) ) {
			if ( wp_verify_nonce( $_POST[ 'billing_vat_nonce_' . $user_id ], 'save_billing_vat_action' ) ) {
				update_user_meta( $user_id, 'billing_vat', sanitize_text_field( $_POST[ 'billing_vat' ] ) );
				
				$label = get_option( 'vat_options_label', __( 'EU VAT Identification Number (VATIN)', 'woocommerce-german-market' ) );
				$message = sprintf( __( '%s has been successfully updated.', 'woocommerce-german-market' ), $label );
				wc_add_notice( $message, 'success' );

			} else {
				$message = sprintf( __( 'An error occurred while saving, please try again.', 'woocommerce-german-market' ), $label );
				wc_add_notice( $message, 'error' );
			}
		}
	}
	/**
	 * load billing vat in backend order from profile
	 *
	 * @hook wp_ajax_wcvat_admin_load_vat_from_profile-wp
	 * @access public
	 * @static
	 * @return void
	 */
	public static function load_billing_vat_from_profile_order_user_change() {

		$return_value = 'empty';

		if ( ! ( isset( $_REQUEST[ 'nonce' ] ) && wp_verify_nonce( $_REQUEST[ 'nonce' ], 'wcvat_update_billing_vat' ) ) ) {
			$nonce = $_REQUEST[ 'security' ];
			$user_id = $_REQUEST[ 'user_id' ];

			if ( $user_id > 0 ) {
				$billing_vat = get_user_meta( $user_id, 'billing_vat', true );
				if ( ! empty( $billing_vat ) ) {
					$return_value = $billing_vat;
				}
			}

			echo $return_value;
		}

		exit();
	}

}
