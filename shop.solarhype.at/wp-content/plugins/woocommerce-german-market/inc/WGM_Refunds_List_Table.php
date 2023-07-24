<?php
// load the base class for the list
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WGM_Refunds_List_Table extends WP_List_Table {

	/**
	 * Some additional arguments
	 * 
	 * @type	array
	 */
	public $args = array();

	/**
	 * Set the needed columns vor the views. Other plugins
	 * are able to filter through this via 'mpl_topic_list_columns'
	 * 
	 * @return	array $columns the columns
	 */
	public function get_columns() {

		$columns = array(
			'refund'	=> apply_filters( 'wgm_refunds_render_refund_id_head', __( 'ID', 'woocommerce-german-market' ) ),
			'order'		=> __( 'Order', 'woocommerce-german-market' ),
			'date'		=> __( 'Date', 'woocommerce-german-market' ),
			'amount'	=> _x( 'Amount', 'refund list table', 'woocommerce-german-market' ),
			'actions'	=> __( 'Actions', 'woocommerce-german-market' ),
		);

		return apply_filters( 'wgm_refunds_backend_columns', $columns );
	}

	/**
	 * Set the sortable columns vor the views. Other plugins
	 * are able to filter through this via 'mpl_topic_sortable_columns'
	 * 
	 * @return	array $columns the columns
	 */
	public function get_sortable_columns() {
		
		$columns = array(
			'refund' 	=> array( 'refund', FALSE ),
			'order' 	=> array( 'order', FALSE ),
			'date' 		=> array( 'date', FALSE ),
			'amount' 	=> array( 'amount', FALSE ),
		);

		return $columns;
	}

	/**
	 * Displays the column content of a particular item
	 * 
	 * @param	array $item the current item
	 * @param	string $column_name the current column
	 * @return	string the column content of the given item
	 */
	public function column_default( $item, $column_name ) {

		$column_content = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		
		if ( $column_name == 'refund' ) {
			$column_content = apply_filters( 'wgm_refunds_render_refund_id', '#' . $column_content, $column_content, $item, $this );
		} else if ( $column_name == 'order' ) {
			$column_content = $this->get_order_title( $column_content );
		} else if ( $column_name == 'date' ) {
			$column_content = $this->get_refund_date( $column_content );
		} else if ( $column_name == 'amount' ) {
			$column_content = $column_content;
		} else if ( $column_name == 'actions' ) {

			$actions = $column_content;
			$column_content = '<div class="refund_actions column-order_actions">';
			
			foreach ( $actions as $action_key => $action ) {
				$class = isset( $action[ 'class' ] ) ? $action[ 'class' ] : '';
				$href = isset( $action[ 'url' ] ) ? 'href="' . esc_attr( $action[ 'url' ] ) . '"' : '';
				$extra_style = isset( $action[ 'style' ] ) ? ' ' . $action[ 'style' ] : ''; 
				$style = apply_filters( 'wgm_refunds_style_icons', 'float: left; margin: 0 4px 2px 0;' . $extra_style );
				$data_string = '';
				if ( isset( $action[ 'data' ] ) && is_array( $action[ 'data' ] ) ) {
					foreach ( $action[ 'data' ] as $key => $value ) {
						$data_string .= ' data-' . $key . '="' . $value . '"';
					}
				}
				$column_content .= sprintf( '<a style="%s" class="button %s %s" ' . $href . ' title="%s" ' . $data_string . '>%s</a>', $style, $action_key, $class, $action[ 'name' ], esc_attr( $action[ 'name' ] ) );
			}
			
			$column_content .= '</div>';

		}

		return $column_content;
	}

	/**
	 * Prepares the items, setup the correct headers and place
	 * the items
	 * 
	 * @param	array $args some arguments passed to prepare everything
	 * @return	void
	 */
	public function prepare_items() {

		// set the pagination
		$current_page = intval( $this->get_pagenum() );
		$this->set_pagination_args( array(
  			'total_items' => $this->args[ 'total_posts' ],
  			'per_page'    => $this->args[ 'posts_per_page' ]
  		) );

		// sorting
		$order = ( ! empty( $_GET[ 'order' ] ) ) ? $_GET[ 'order' ] : 'desc';
		$orderby = 'id';

		// get orders args with default order
		$get_orders_args = array(
			'type'   => 'shop_order_refund',
			'limit'  => $this->args[ 'posts_per_page' ],
			'offset' => ( $current_page - 1 ) * intval( $this->args[ 'posts_per_page' ] ),
			'order'	 => $order,
			'orderby'=> $orderby
		);

		// reset order and order by
		if ( ! empty( $_GET[ 'orderby' ] ) ) {
			
			if ( $_GET[ 'orderby' ] == 'order' ) {
				$get_orders_args[ 'orderby' ]= 'parent';
			} else if ( $_GET[ 'orderby' ] == 'date' ) {
				$get_orders_args[ 'orderby' ] = 'date';
			} else if ( $_GET[ 'orderby' ] == 'amount' ) {
				add_action( 'pre_get_posts', array( $this, 'pre_gets_posts' ) ); // can't set meta_value_key in wc_get_orders, so we do it that way
			}

		}

		// get orders
		$refunds_raw = wc_get_orders( $get_orders_args );
		remove_action( 'pre_get_posts', array( $this, 'pre_gets_posts' ) );

		// prepare refunds
       	$refunds = array();
       	foreach ( $refunds_raw as $refund_raw ) {

       		$refunds[] = apply_filters( 'wgm_refunds_array', 
       			array(
	       			'refund'	=> $refund_raw->get_id(),
					'order'		=> $refund_raw->get_parent_id(),
					'date'		=> $refund_raw->get_date_created()->getTimestamp(),
					'amount'	=> wc_price( $refund_raw->get_amount(), array( 'currency' => $refund_raw->get_currency() ) ),
					'actions'	=> apply_filters( 'wgm_refunds_actions', array(), $refund_raw ),
				)
       		);

       	}

		$this->items = $refunds;
		
		// set the columns
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

	}

	/**
	 * Displays the message if no permissions has been found
	 * 
	 * @return	void
	 */
	public function no_items() {
		echo __( 'No refunds found.', 'woocommerce-german-market' );
	}

		/**
	* Render Order Title
	* 
	* @param Integer $order_id
	* @access private
	* @return String
	*/
	private function get_order_title( $order_id ) {

		$the_order = wc_get_order( $order_id );
		$post = get_post( $order_id );

		if ( WGM_Helper::method_exists( $the_order, 'get_user_id' ) && $the_order->get_user_id() ) {
			$user_info = get_userdata( $the_order->get_user_id() );
		}

		if ( ! empty( $user_info ) ) {

			$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

			if ( $user_info->first_name || $user_info->last_name ) {
				$username .= esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), ucfirst( $user_info->first_name ), ucfirst( $user_info->last_name ) ) );
			} else {
				$username .= esc_html( ucfirst( $user_info->display_name ) );
			}

			$username .= '</a>';

		} else {
			
			if ( WGM_Helper::method_exists( $the_order, 'get_billing_first_name' ) && ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) ) {
				$username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
			} else if ( WGM_Helper::method_exists( $the_order, 'get_billing_company' ) && $the_order->get_billing_company() ) {
				$username = trim( $the_order->get_billing_company() );
			} else {
				$username = __( 'Guest', 'woocommerce-german-market' );
			}
		}

		$return_string = '';

		if ( WGM_Helper::method_exists( $the_order, 'get_order_number' ) ) {
			$return_string = sprintf( _x( '%s by %s', 'Order number by X', 'woocommerce' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>', $username );
		}
		
		return $return_string;

	}

	/**
	* Render Refund Date
	* 
	* @param Integer $refund_id
	* @access private
	* @return String
	*/
	private function get_refund_date( $refund_date ) {

		$t_time = date( __( 'Y/m/d g:i:s A', 'woocommerce' ), $refund_date );
		$h_time =  apply_filters( 'woocommerce_de_post_date_column_time', date_i18n( wc_date_format(), $refund_date ) . ' ' . date( wc_time_format(), $refund_date ), $refund_date );
		return '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</abbr>';

	}

	/**
	* Set query args if we sort by amound
	* 
	* @param WP_Query $query
	* @access public
	* @return void
	*/
	public function pre_gets_posts( $query ) {
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', '_refund_amount' );
	}

}
