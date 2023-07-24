<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-list-table.php' );
}

class BM_ListTable extends WP_List_Table {

	/**
	 * BM_ListTable constructor.
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => 'customer_group',
			'plural'   => 'customer_groups',
			'ajax'     => true,
		) );

	}

	/**
	 * add edit and delete links under group title
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_customer_group( $item ) {

		$edit_url   = wp_nonce_url( admin_url() . 'post.php?post=' . $item['ID'] . '&action=edit', 'edit-post' );
		$delete_url = get_delete_post_link( $item['ID'], '', true );

		$actions = array(
			'edit'   => '<a data-group="' . $item['ID'] . '" href="">' . __( 'Edit', 'b2b-market' ) . '</a>',
			'delete' => '<a href="' . $delete_url . '">' . __( 'Delete', 'b2b-market' ) . '</a>',
		);

		return sprintf( '%1$s <span style="color:silver">(id:%2$s)</span>%3$s', $item['title'], $item['ID'], $this->row_actions( $actions ) );
	}

	/**
	 * add column for group price
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_group_slug( $item ) {

		$group = get_post( $item['ID'] );

		return $group->post_name;
	}

	/**
	 * add column for bulk prices
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_pricing_used( $item ) {

		$group_prices = get_post_meta( $item['ID'], 'bm_group_prices', true );
		$bulk_prices  = get_post_meta( $item['ID'], 'bm_bulk_prices', true );

		$set = __( 'No', 'b2b-market' );

		if ( isset( $group_prices ) && ! empty( $group_prices ) ) {
			$set = __( 'Yes', 'b2b-market' );
		}

		if ( isset( $bulk_prices ) && ! empty( $bulk_prices ) ) {
			$set = __( 'Yes', 'b2b-market' );
		}

		return $set;
	}

	/**
	 * add column for products
	 *
	 * @param $item
	 *
	 * @return mixed|string
	 */
	public function column_tax_display( $item ) {

		$tax_mode    = get_post_meta( $item['ID'], 'bm_tax_type', true );
		$tax_display = __( 'Gross', 'b2b-market' );

		if ( 'on' == $tax_mode ) {
			$tax_display = __( 'Net', 'b2b-market' );
		}

		return $tax_display;
	}

	/**
	 * get all columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'customer_group'     => __( 'Customer Group', 'b2b-market' ),
			'group_slug'         => __( 'Group Slug', 'b2b-market' ),
			'pricing_used'       => __( 'Pricing Rules', 'b2b-market' ),
			'tax_display'        => __( 'Tax display', 'b2b-market' ),
		);
		return $columns;
	}

	/**
	 * get sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'customer_group' => array( 'title', false ),
		);

		return $sortable_columns;
	}


	/**
	 * get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		/*
		 * currently collide with bm-admin-ajax.js cause of redirect
		 */

		/*
		$actions = array(
			'delete' => __( 'Delete', 'b2b-market' )
		);

		return $actions;
		*/
	}


	/**
	 * process bulk actions
	 */
	public function process_bulk_action() {

		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
		}

		$action = $this->current_action();

		switch ( $action ) {

			case 'delete':

				/* delete all product meta for group */

				$group  = get_post( $_GET['group'] );
				$prefix = 'bm_' . $group->post_name;

				global $wpdb;

				$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$prefix}%'" );


				/* delete group itself */
				wp_delete_post( $_GET['group'], true );

				break;

			default:
				// do nothing
				return;
				break;
		}

		return;
	}


	/**
	 *  prepare loop for output
	 */
	public function prepare_items() {
		/* args */
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		/* get data for table */
		$data = array();

		$args   = array(
			'posts_per_page' => - 1,
			'post_type'      => 'customer_groups',
		);
		$groups = get_posts( $args );

		foreach ( $groups as $group ) {
			$arr = array(
				'title' => $group->post_title,
				'ID'    => $group->ID,
			);
			array_push( $data, $arr );
		}

		usort( $data, array( $this, 'usort_reorder' ) );

		/* handles pagination */
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );
		$data         = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->items = $data;
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * modify usort for reordering groups
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
		$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( 'asc' === $order) ? $result : - $result;
	}
}
