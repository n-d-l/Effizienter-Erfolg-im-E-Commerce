<?php
/**
 * FIC
 *
 * Uninstalling Options
 */

// Prevent direct access.
if ( ! ( defined( 'WGM_UNINSTALL_ADD_ONS' ) || defined( 'WP_UNINSTALL_PLUGIN' ) ) ) {
	exit;
}

$all_wordpress_options = wp_load_alloptions();

foreach ( $all_wordpress_options as $option_key => $option_value ) {
	
	if ( substr( $option_key, 0, 10 ) == 'gm_fic_ui_' ) {
		delete_option( $option_key );
	}

}

$taxonomies = array(
	'gm_fic_nutritional_values',
);

foreach ( $taxonomies as $taxonomy ) {

	$taxonomy_terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
	foreach ( $taxonomy_terms as $term ) {

		if ( is_object( $term ) && isset( $term->term_id ) ) {
			wp_delete_term( $term->term_id, $taxonomy );
		}
	}
}
