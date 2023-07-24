<?php

class WGM_Session {

	public static function add( $key, $value, $namespace = NULL ) {

		if ( ! is_object( WC()->session ) || is_null( WC()->session ) )
			return;

		if ( is_null( $namespace ) ) {
			WC()->session->$key = $value;
		} else {
			WC()->session->{$namespace . $key} = $value;
		}
	}

	public static function get( $key, $namespace = NULL ) {

		if ( ! is_object( WC()->session ) || is_null( WC()->session ) )
			return;

		if ( is_null( $namespace ) ) {
			return WC()->session->$key;
		} else {
			return WC()->session->{$namespace . $key};
		}
	}

	public static function is_set( $key, $namespace = NULL ) {

		if ( ! is_object( WC()->session ) || is_null( WC()->session ) )
			return;

		if ( is_null( $namespace ) ) {
			return isset( WC()->session->$key );
		} else {
			return isset( WC()->session->{$namespace . $key} );
		}
	}

	public static function remove( $key, $namespace = NULL ) {

		if ( ! is_object( WC()->session ) || is_null( WC()->session ) )
			return;

		if ( is_null( $namespace ) ) {
			unset( WC()->session->$key );
		} else {
			unset( WC()->session->{$namespace . $key} );
		}
	}
}
