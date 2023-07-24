<?php

class IE_Options {

	/**
	 * IE_Options constructor.
	 */
	public function __construct() {}

	/**
	 * @param $items
	 *
	 * @return mixed
	 */
	public function add_menu_item( $items ) {

		$items[4] = array(
			'title'   => __( 'Import and Export', 'b2b-market' ),
			'slug'    => 'import_and_export',
			'options' => false,
			'submenu' => array(
				array(
					'title'    => __( 'Export', 'b2b-market' ),
					'slug'     => 'export',
					'callback' => array( $this, 'export_tab' ),
					'options'  => false,
				),
				array(
					'title'    => __( 'Import', 'b2b-market' ),
					'slug'     => 'import',
					'callback' => array( $this, 'import_tab' ),
					'options'  => false,
				),
				/*
				array(
					'title'    => __( 'Migrator', 'b2b-market' ),
					'slug'     => 'migrator',
					'callback' => array( $this, 'migrator_tab' ),
					'options'  => 'yes',
				),
				*/
			),
		);

		return $items;

	}

	/**
	 * @return array|mixed|void
	 */
	public function export_tab() {

		/* export */
		$options         = array();
		$groups          = new BM_User();
		$button_disabled = true;

		?>
		<form id="b2b_market_export_form" name="b2b_market_export_form">
			<div id="b2b_market_export_wrapper">
				<div id="b2b_market_export_groups">
					<h2><?php echo _e( 'Which Customer Groups do you want export?', 'b2b-market' ) ?></h2>
					<div class="form-fields-wrapper">
						<label for="b2b_market_select_groups_all">
							<input type="checkbox" id="b2b_market_select_groups_all" name="b2b_market_select_groups_all" value="on" /> <?php echo _e( 'Select all / Unselect all', 'b2b-market' ); ?>
						</label>
						<?php

						foreach ( $groups->get_all_customer_groups() as $group ) {
							foreach ( $group as $key => $value ) {
								$checked = ( 'on' == get_option( 'export_' . $key, 'off' ) ?? true );
								if ( true === $checked ) {
									$button_disabled = false;
								}
								?>
								<label for="b2b_market_export_group_<?php echo $value; ?>">
									<input type="checkbox" id="b2b_market_export_group_<?php echo $value; ?>" name="b2b_market_export_groups[]" value="<?php echo $value; ?>" <?php echo ( $checked ? 'checked' : '' ); ?> /> <?php echo ucfirst( $key ); ?>
								</label>
								<?php
							}
						}

						?>
					</div>
					<h2><?php echo _e( 'Do you want export the B2B Market Plugin Settings also?', 'b2b-market' ) ?></h2>
					<div class="form-fields-wrapper">
						<label for="b2b_market_export_plugin_settings">
							<?php
							$checked = ( 'on' == get_option( 'export_plugin_settings', 'off' ) ?? true );
							if ( true === $checked ) {
								$button_disabled = false;
							}
							?>
							<label for="b2b_market_export_plugin_settings">
								<input type="checkbox" id="b2b_market_export_plugin_settings" name="b2b_market_export_plugin_settings" value="on" <?php echo ( $checked ? 'checked' : '' ); ?> /> <?php echo _e( 'Yes, export the plugin settings too.', 'b2b-market' ); ?>
							</label>
							<label for="b2b_market_export_save_file">
								<input type="checkbox" id="b2b_market_export_save_file" name="b2b_market_export_save_file" value="on" /> <?php echo _e( 'I want to save the export as a file on my local computer.', 'b2b-market' ); ?>
							</label>
						</label>
					</div>
				</div>
				<div id="b2b_market_export_button_wrapper">
					<input type="submit" name="export_button" class="save-bm-options button <?php echo ( $button_disabled ? 'disabled' : '' ); ?>" value="<?php echo __( 'Start Export', 'b2b-market' ); ?>">
				</div>
				<div id="b2b_market_export_code">
					<h2><?php echo _e( 'Export Output', 'b2b-market' ) ?></h2>
					<div class="form-fields-wrapper">
						<label for="b2b_market_export_output">
							<textarea id="b2b_market_export_output" cols="80" rows="15"><?php echo get_option( 'export_options_raw_data', '' ); ?></textarea>
						</label>
					</div>
				</div>
			</div>
			<div class="modal"><h3><?php _e( 'Export complete', 'b2b-market' ); ?>.</h3><p><?php _e( 'Your export was successfull', 'b2b-market' ); ?>.</div>
		</form>
		<?php

		return $options;
	}

	/**
	 * @return array|mixed|void
	 */
	public function import_tab() {

		$options = array();

		?>
		<form id="b2b_market_import_form" name="b2b_market_import_form">
			<div id="b2b_market_import_wrapper">
				<div id="b2b_market_import_step1">
					<h2><?php echo sprintf( __( 'Step %s', 'b2b-market' ), 1 ) . ' - ' . __( 'Import Data', 'b2b-market' ); ?></h2>
					<div class="form-fields-wrapper">
						<label for="b2b_market_import_raw_data">
							<textarea id="b2b_market_import_raw_data" rows="15" style="width: 100%;" placeholder="<?php echo __( 'Please insert your export data here or upload an export file below.', 'b2b-market' ); ?>"></textarea>
						</label>
						<div class="b2b-spacer"><hr/><span><?php echo __( 'or', 'b2b-market' ); ?></span></div>
						<input type="file" id="b2b_market_import_file" style="width: 100%;" />
						<div id="b2b_market_import_button_wrapper">
							<input type="submit" name="import_button" class="save-bm-options button disabled" value="<?php echo __( 'Analyze Data', 'b2b-market' ); ?>">
						</div>
					</div>
				</div>
				<div id="b2b_market_import_groups">
					<h2><?php echo sprintf( __( 'Step %s', 'b2b-market' ), 2 ) . ' - ' . __( 'Choose what to Import', 'b2b-market' ); ?></h2>
					<div id="b2b_market_import_groups_options">
						<div class="form-fields-wrapper">
							<?php echo __( 'Please choose here, after successful analysis of the data, what do you want to import.', 'b2b-market' ); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal"><h3><?php _e( 'Import complete', 'b2b-market' ); ?>.</h3><p><?php _e( 'Your import was successfull', 'b2b-market' ); ?>.</div>
		</form>
		<?php

		return $options;
	}

	/**
	 * @return array|mixed|void
	 */
	public function migrator_tab() {

		$options = array();

		$heading = array(
			'name' => __( 'Migrate all Settings from your current installation of Role Based Prices', 'b2b-market' ),
			'type' => 'title',
			'id'   => 'migrator_options',
		);

		array_push( $options, $heading );

		$end = array(
			'type' => 'sectionend',
			'id'   => 'migrator_options_file_attachement',
		);

		array_push( $options, $end );

		$options = apply_filters( 'woocommerce_bm_ui_migrator_options', $options );

		return $options;
	}

}
