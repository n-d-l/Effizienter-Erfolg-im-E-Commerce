<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="updated wgm-activation-message">
	<div class="wgm-activation-panel">
		<?php $locale = get_locale();
		$is_de = ( stripos( $locale, 'de' ) === 0 ) ? TRUE : FALSE;
		$url = $is_de ? 'https://marketpress.de/product/woocommerce-german-market/' : 'https://marketpress.com/product/woocommerce-german-market/';
		$mp_url = 'https://marketpress.de/';
		?>
		<h2><?php _e( 'Yay, you’re activating WooCommerce German Market!', 'woocommerce-german-market' ); ?></h2>
		<form action="<?php echo untrailingslashit( get_admin_url() ); ?>/admin.php?page=german-market&tab=license" method="post" >
			
			<?php wp_nonce_field( 'wgm-installation', 'wgm-installation-done' ); ?>

			<div class="wgm-activation-license-key-box">
				<h3><?php _e( 'Please, activate your license!', 'woocommerce-german-market' ) ?></h3>

				<p>
					<label for="license-key"><?php echo sprintf( __( 'Enter a valid license key from %s below:', 'woocommerce-german-market' ),'<a href="' . $mp_url . '" target="_blank">MarketPress</a>' ); ?></label><br />
					<input type="text" name="license-key" id="license-key" value="" style="width: 400px; "/><br />
					<?php echo sprintf( ' (<a href="%1$suser-login/" target="_blank">%2$s</a>)', $mp_url, __( 'Help! I need to retrieve my key.', 'woocommerce-german-market' ) ); ?>
					<a href="<?php echo $url; ?>" target="_blank"><div class="wp-badge wgm-badge">German Market</div></a>
				</p>
			</div>


			<p><?php _e( 'WooCommerce German Market will automatically create legally required pages with sample copy and configure specific WooCommerce settings for your shop. Check what’s going to happen below, then complete the activation process by clicking the large button at the bottm of this dialogue.', 'woocommerce-german-market' ); ?></p>

			<div class="wgm-activation-panel-col wgm-activation-panel-col-1">
				<h3><?php _e( 'Legally Required Shop Pages & Disclaimers', 'woocommerce-german-market') ?></h3>

					<p><label for="woocommerce_de_install_de_pages">
						<input type="checkbox" id="woocommerce_de_install_de_pages" name="woocommerce_de_install_de_pages" checked="checked" />
						<strong><?php _e( 'Create additional order confirmation page and pages with legal information?', 'woocommerce-german-market' ); ?></strong>
						<?php _e( 'These pages are legally required if your shop is based in Germany.', 'woocommerce-german-market' ); ?>
					</label></p>
					<ul class="wgm-activation-pages-list">
						<?php
						$pages = WGM_Helper::get_default_pages();
						foreach( $pages as $page ){
							$post_titles[] = $page[ 'post_title' ];
						}
						$pages =  array_map( 'ucfirst', $post_titles );
						foreach( $pages as $page ) {
							printf( '<li><span class="dashicons dashicons-media-text"></span>&#160;<em>%s</em></li>', $page );
						}
						?>
					</ul>

					<p><label for="woocommerce_de_install_de_pages_overwrite">
						<input type="checkbox" id="woocommerce_de_install_de_pages_overwrite" name="woocommerce_de_install_de_pages_overwrite" />
						<strong><?php _e( 'Override existing pages?', 'woocommerce-german-market' ); ?></strong>
						<?php _e( 'Existing pages will be identified by page title.', 'woocommerce-german-market' ); ?>
					</label></p>

					<h3 style="margin-top: 30px;"><?php _e( 'Interfaces for legal textes', 'woocommerce-german-market' ); ?></h3>

					<p><?php _e( 'German Market includes Add-Ons to generate legal textes for your WordPress pages with the interfaces of the corresponding provider. You can already activate these Add-Ons right now. An account of the provider is needed.', 'woocommerce-german-market' ); ?></p>

					<p>
						<label for="woocommerce_de_activate_protected_shops">
							<input type="checkbox" id="woocommerce_de_activate_protected_shops" name="woocommerce_de_activate_protected_shops" />
							<?php _e( 'Activate "Protected Shops" Add-On', 'woocommerce-german-market' ); ?>
						</label>

						<br />
						
						<label for="woocommerce_de_activate_it_recht_kanzlei">
							<input type="checkbox" id="woocommerce_de_activate_it_recht_kanzlei" name="woocommerce_de_activate_it_recht_kanzlei" />
							<?php _e( 'Activate "IT-Recht Kanzlei" Add-On', 'woocommerce-german-market' ); ?>
						</label>
					</p>

			</div>

			<div class="wgm-activation-panel-col wgm-activation-panel-col-2">
				<h3><?php _e( 'Default Settings', 'woocommerce-german-market') ?></h3>
					<p>	
						<label for="woocommerce_de_install_default_settings">
							<input type="checkbox" id="woocommerce_de_install_default_settings" name="woocommerce_de_install_default_settings" checked="checked" />
							<?php _e( 'WooCommerce German Market will apply the following default settings to your existing WooCommerce setup.', 'woocommerce-german-market' ); ?>
						</label>
					</p>
					<table class="wgm-settings-table">
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Currency:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php _e( 'EUR (€)', 'woocommerce-german-market' ); ?></em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Base Location:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em>
								
								<?php 
									$base_country = get_option( 'woocommerce_default_country', 'DE' );
									if ( $base_country != 'AT' ) {
										_e( 'Germany', 'woocommerce-german-market' );
									} else {
										_e( 'Austria', 'woocommerce-german-market' );
									}
								?>

							</em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Tax:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php _e( 'enabled', 'woocommerce-german-market' ); ?></em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Enter prices:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php _e( 'including tax', 'woocommerce-german-market' ); ?></em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Display prices in shop:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php _e( 'including tax', 'woocommerce-german-market' ); ?></em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Display prices in cart and during checkout:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php _e( 'including tax', 'woocommerce-german-market' ); ?></em></td>
						</tr>
						<?php $default_tax_rates = WGM_Defaults::get_default_tax_rates(); ?>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Default tax rate:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php echo number_format( $default_tax_rates[ 0 ][ 'rate' ], 2, ',', '.' ); ?>%</em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Reduced tax rate:', 'woocommerce-german-market' ); ?></strong></td>
							<td><em><?php echo number_format( $default_tax_rates[ 1 ][ 'rate' ], 2, ',', '.' ); ?>%</em></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-admin-settings"></span>&#160;<strong><?php _e( 'Default measuring units:', 'woocommerce-german-market' ); ?></strong></td>
							<td>
								<ul class="ul-square">
								<?php
								$default_product_attributes = WGM_Defaults::get_default_product_attributes();
								foreach( $default_product_attributes[ 0 ][ 'elements' ] as $scale_units ) {
									printf( '<li><em>%s</em></li>', __(  $scale_units[ 'description' ], 'woocommerce-german-market' ) );
								}
								?>
							</ul>
							</td>
						</tr>
					</table>

					<?php
						$install_lang = WGM_Helper::get_installation_language();
						$current_language = substr( get_user_locale(), 0, 2 );

						if ( $install_lang != $current_language ) {

							if ( 'de' === $install_lang ) {
								$language_name = __( 'German', 'woocommerce-german-market' );
							} else {
								$language_name = __( 'English', 'woocommerce-german-market' );
							}

							?>
							<p>	
								<label for="woocommerce_de_install_texts_in_default_lang">
									<input type="checkbox" id="woocommerce_de_install_texts_in_default_lang" name="woocommerce_de_install_texts_in_default_lang" checked="checked" />
									<?php echo sprintf( __( 'Save default settings texts in %s.', 'woocommerce-german-market' ), $language_name ); ?>
								</label>
							</p>
							<?php

						}
					?>

			</div>

			<h3 class="clear"><?php _e( 'Finish Activation', 'woocommerce-german-market') ?></h3>
			<p><?php _e( 'After you have finished this activation dialogue, re-visit your newly created pages and customize all legal copy to meet your individual business requirements.', 'woocommerce-german-market' ); ?></p>
            <input type="hidden" id="woocommerce_de_install_de_options" name="woocommerce_de_install_de_options" value="true" />
            <p><input type="submit" class="button-primary" name="woocommerce_de_install" value="<?php _e( 'Finish activation with settings selected above', 'woocommerce-german-market' );?>" /></p>

		</form>
	</div>
</div>
