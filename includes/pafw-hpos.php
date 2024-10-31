<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

final class PAFW_HPOS {
	protected static $hpos_enabled = null;
	static function init() {
	}
	static function enabled() {
		if ( is_null( self::$hpos_enabled ) ) {
			self::$hpos_enabled = class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ? OrderUtil::custom_orders_table_usage_is_enabled() : false;
		}

		return self::$hpos_enabled;
	}
	static function get_order( $order ) {
		if ( is_int( $order ) ) {
			return wc_get_order( $order );
		} else if ( $order instanceof WP_Post ) {
			return wc_get_order( $order->ID );
		}

		return $order;
	}
	static function get_order_type( $order_id ) {
		return self::enabled() ? OrderUtil::get_order_type( $order_id ) : get_post_type( $order_id );
	}
	static function get_shop_order_screen( $order_types = 'shop_order' ) {
		$screens = array();

		if ( is_array( $order_types ) ) {
			foreach ( $order_types as $order_type ) {
				$screens[] = PAFW_HPOS::enabled() ? wc_get_page_screen_id( str_replace( '_', '-', $order_type ) ) : $order_type;
			}
		} else {
			$screens[] = PAFW_HPOS::enabled() ? wc_get_page_screen_id( str_replace( '_', '-', $order_types ) ) : $order_types;
		}

		return $screens;
	}
	static function is_order( $order_id ) {
		return PAFW_HPOS::enabled() ? OrderUtil::is_order( $order_id, wc_get_order_types() ) : in_array( get_post_type( $order_id ), wc_get_order_types() );
	}
	static function get_order_admin_url( $order_status = '' ) {
		if ( PAFW_HPOS::enabled() ) {
			return add_query_arg( 'status', $order_status, OrderUtil::get_order_admin_new_url() );
		} else {
			return add_query_arg( 'post_status', $order_status, admin_url( "edit.php?post_type=shop_order" ) );
		}
	}
}

PAFW_HPOS::init();