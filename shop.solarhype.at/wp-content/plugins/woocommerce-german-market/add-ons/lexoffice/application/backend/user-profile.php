<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Render Setting fiels
*
* @wp-hook show_user_profile, edit_user_profile
* @param WP_User $user
* @return void
**/
function lexoffice_woocommerce_profile_fields( $user ) {
	
	if ( ! ( current_user_can( 'edit_user', $user->ID ) && current_user_can( 'manage_woocommerce' ) ) ) {
		return;
	}

	if ( get_option( 'woocommerce_de_lexoffice_too_many_contacts', 'no' ) == 'yes' ) {
		return;
	}

	?>
	<style>
		td.lexoffice-contacts div{ width: 25em; }
	</style>

	<h3><?php echo __( 'Lexoffice Contact', 'woocommerce-german-market' ); ?> </h3>

	<table class="form-table">

		<tr>
			<th><label for="lexoffice_contact"><?php echo __( 'Contact', 'woocommerce-german-market' ); ?></label></th>

			<td class="lexoffice-contacts">
				<select name="lexoffice_contact" id="lexoffice_contact" class="wc-enhanced-select-nostd">

					<option value="0"><?php echo __( 'Collective Contact', 'woocommerce-german-market' ); ?>
					<?php
						$lexoffice_users = lexoffice_woocommerce_get_all_contacts();
						$current_setting = get_user_meta( $user->ID, 'lexoffice_contact', true );

						foreach ( $lexoffice_users as $lexoffice_user ) {
							
							$id = $lexoffice_user[ 'id' ];
							
							$display_name = '';

							if ( isset( $lexoffice_user[ 'person' ] ) ) {
								$display_name = isset( $lexoffice_user[ 'person' ][ 'firstName' ] ) ? $lexoffice_user[ 'person' ][ 'lastName' ] . ', ' . $lexoffice_user[ 'person' ][ 'firstName' ] : $lexoffice_user[ 'person' ][ 'lastName' ];
							} else if ( isset( $lexoffice_user[ 'company' ] ) ) {
								$display_name = $lexoffice_user[ 'company' ][ 'name' ];
							}

							if ( $display_name != '' ) {
								$selected = $id == $current_setting ? ' selected="selected"' : '';
								?><option value="<?php echo $id; ?>"<?php echo $selected;?>><?php echo $display_name; ?></option><?php
							}

						}
					?>
				</select>
				<span class="description"><?php echo __( 'Assign a lexoffice user to your WooCommerce user', 'woocommerce-german-market' ); ?></span>
			</td>
		</tr>

	</table>

	<?php

}

/**
* Save Profile Settings
*
* @wp-hook personal_options_update, edit_user_profile_update
* @param Integer $user_id
* @return void
**/
function lexoffice_woocommerce_save_profile_fields( $user_id ) {

	if ( current_user_can( 'edit_user', $user_id ) ) {
		update_user_meta( $user_id, 'lexoffice_contact', $_POST[ 'lexoffice_contact' ] );
	}

}
	
