<?php

class BM_User {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of BM_Price.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * BM_User constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * hooks
	 */
	public function init() {
		add_action( 'before_delete_post', array( $this, 'delete_customer_group' ) );
	}

	/**
	 * @param $post_id
	 */
	public function add_customer_group( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( get_role( 'automatisch-gespeicherter-entwurf' ) ) {
			remove_role( 'automatisch-gespeicherter-entwurf' );
		}

		$customer_group = get_post( $post_id );

		$role = get_role( $customer_group->post_name );

		if ( null == $role || 'customer' !== $customer_group->post_name ) {
			add_role( $customer_group->post_name, get_the_title( $post_id ), array(
				'read'    => true,
				'level_0' => true,
			));
		}
	}


	/**
	 * @param $post_id
	 */
	public function delete_customer_group( $post_id ) {

		$customer_group = get_post( $post_id );

		if ( 'customer' !== $customer_group->post_name ) {
			$role = get_role( $customer_group->post_name );

			if ( isset( $role ) && ! empty( $role->name ) ) {
				remove_role( $role->name );
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_all_customer_groups() {
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => 'customer_groups',
			'post_status'    => 'publish',
		);

		$posts           = get_posts( $args );
		$customer_groups = array();

		foreach ( $posts as $customer_group ) {
			array_push( $customer_groups, array( $customer_group->post_name => $customer_group->ID ) );
		}
		return $customer_groups;
	}

	/**
	 * @return array
	 */
	public static function get_all_customer_group_ids() {
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => 'customer_groups',
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$customer_group_ids = get_posts( $args );
		return $customer_group_ids;
	}
}

new BM_User();
