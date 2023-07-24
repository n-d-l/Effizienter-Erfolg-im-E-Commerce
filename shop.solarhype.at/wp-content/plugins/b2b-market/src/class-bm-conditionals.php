<?php
/**
 * Class which handles all conditional logic
 */
class BM_Conditionals {
	/**
	 * Get current user groups for a the current logged in user
	 *
	 * @return int
	 */
	public static function get_validated_customer_group() {
		$current_user = apply_filters( 'bm_current_customer', wp_get_current_user() );

		/* is guest? */
		if ( 0 == $current_user->ID ) {
			$group_id = apply_filters( 'bm_use_same_group', get_option( 'bm_guest_group' ) );
			return $group_id;
		}
		/* has user id? */
		if ( 0 != $current_user->ID ) {
			/* is customer? */
			if ( in_array( 'customer', $current_user->roles ) ) {
				$group_id = apply_filters( 'bm_use_same_group', get_option( 'bm_customer_group' ) );
				return $group_id;
			}
			foreach ( $current_user->roles as $slug ) {
				$group = get_page_by_path( $slug, OBJECT, 'customer_groups' );

				if ( ! is_null( $group ) ) {
					$group_id = $group->ID;
					return $group_id;
				}
			}
		}
	}

	/**
	 * Checks if cart amount match customer group setting for min amount.
	 *
	 * @return void
	 */
	public static function is_cart_min_amount_passed() {
		/* get metadata */
		$group_id   = self::get_validated_customer_group();
		$min_amount = apply_filters( 'bm_min_amount_value', get_post_meta( $group_id, 'bm_min_order_amount', true ) );
		$message    = apply_filters( 'bm_min_amount_message', get_post_meta( $group_id, 'bm_min_order_amount_message', true ) );
		$tax_type   = get_post_meta( $group_id, 'bm_tax_type', true );

		if ( 'on' === $tax_type ) {
			$total = WC()->cart->get_subtotal();
		} else {
			$total = WC()->cart->subtotal;
		}

		if ( empty( $min_amount ) ) {
			return;
		}

		if ( round( $total, 2 ) < $min_amount ) {
			wc_print_notice( str_replace( '[min-amount]', wc_price( $min_amount ), $message ), 'error' );
		}
	}
	/**
	 * Checks if checkout amount match customer group setting for min amount.
	 *
	 * @return void
	 */
	public static function is_checkout_min_amount_passed() {
		/* get metadata */
		$group_id   = self::get_validated_customer_group();
		$min_amount = apply_filters( 'bm_min_amount_value', get_post_meta( $group_id, 'bm_min_order_amount', true ) );
		$message    = apply_filters( 'bm_min_amount_message', get_post_meta( $group_id, 'bm_min_order_amount_message', true ) );
		$tax_type   = get_post_meta( $group_id, 'bm_tax_type', true );

		if ( 'on' === $tax_type ) {
			$total = WC()->cart->get_subtotal();
		} else {
			$total = WC()->cart->subtotal;
		}

		if ( empty( $min_amount ) ) {
			return;
		}

		if ( round( $total, 2 ) < $min_amount ) {
			throw new Exception( str_replace( '[min-amount]', wc_price( $min_amount ), $message ) );
		}
	}
}
