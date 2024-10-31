<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Cancel_Unpaid_Order' ) ) :

	class PAFW_Cancel_Unpaid_Order {

		public static function init() {
			if ( 'yes' == get_option( 'pafw-gw-support-cancel-unpaid-order', 'no' ) && get_option( 'pafw-gw-cancel-unpaid-order-days', '3' ) > 0 ) {
				add_filter( 'cron_schedules', array( __CLASS__, 'pafw_cancel_unpaid_order_interval' ) );
				add_action( 'wp', array( __CLASS__, 'cancel_unpaid_order_cron_init' ) );
				add_action( 'pafw_cancel_unpaid_order_hook', array( __CLASS__, 'cancel_unpaid_order' ) );
			}
		}

		public static function cancel_unpaid_order_cron_init() {

			if ( ! wp_next_scheduled( 'pafw_cancel_unpaid_order_hook' ) ) {
				wp_schedule_event( time(), 'pafw_cancel_unpaid_order_interval', 'pafw_cancel_unpaid_order_hook' );
			} else {
				$schedule = wp_get_schedule( 'pafw_cancel_unpaid_order_hook' );

				if ( $schedule != 'pafw_cancel_unpaid_order_interval' ) {
					$timestamp = wp_next_scheduled( 'pafw_cancel_unpaid_order_hook' );
					wp_unschedule_event( $timestamp, 'pafw_cancel_unpaid_order_hook', array() );
					wp_schedule_event( time(), 'pafw_cancel_unpaid_order_interval', 'pafw_cancel_unpaid_order_hook' );
				}
			}
		}
		public static function pafw_cancel_unpaid_order_interval( $schedules ) {

			$schedules['pafw_cancel_unpaid_order_interval'] = array(
				'interval' => 1800,
				'display'  => __( '매 30 분 간격', 'mshop-bacs-restore-stock' )
			);

			return $schedules;
		}

		public static function get_supported_gateways() {
			$supported_gateway_ids = array();
			$available_gateways    = WC()->payment_gateways()->payment_gateways();
			foreach ( $available_gateways as $gateway_id => $gateway ) {
				if ( 'bacs' == $gateway_id || $gateway->supports( 'pafw-vbank' ) ) {
					$supported_gateway_ids[] = $gateway_id;
				}
			}

			return apply_filters( 'pafw_cancel_unpaid_order_supported_gateway_ids', $supported_gateway_ids );
		}
		public static function get_unpaid_orders( $days, $payment_gateway_ids ) {
			$date = date( "Y-m-d H:i:s", strtotime( '-' . absint( $days ) . ' day', strtotime( current_time( 'mysql' ) ) ) - intval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS );

			return wc_get_orders( array(
				'date_before'    => $date,
				'limit'          => -1,
				'status'         => 'wc-on-hold',
				'payment_method' => $payment_gateway_ids
			) );
		}

		public static function cancel_unpaid_order() {
			if ( 'yes' == get_option( 'pafw-gw-support-cancel-unpaid-order', 'no' ) && get_option( 'pafw-gw-cancel-unpaid-order-days', '3' ) > 0 ) {
				$days          = get_option( 'pafw-gw-cancel-unpaid-order-days', '3' );
				$gateway_ids   = self::get_supported_gateways();
				$unpaid_orders = self::get_unpaid_orders( $days, $gateway_ids );

				if ( ! empty( $unpaid_orders ) ) {
					foreach ( $unpaid_orders as $unpaid_order ) {
						if ( apply_filters( 'woocommerce_cancel_unpaid_order', 'checkout' === $unpaid_order->get_created_via(), $unpaid_order ) ) {
							$payment_method = $unpaid_order->get_payment_method();

							if ( 'bacs' == $payment_method ) {
								$unpaid_order->update_status( 'cancelled', __( '[무통장입금 자동취소] 지불되지 않은 무통장입금(Bacs) 주문이 취소 처리 되었습니다.', 'pgall-for-woocommerce' ) );
							} else {
								$payment_gateway = pafw_get_payment_gateway( $payment_method );
								if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array( $payment_gateway, 'cancel_unpaid_order' ) ) ) {
									$payment_gateway->cancel_unpaid_order( $unpaid_order );
								}
							}
						}
					}
				}
			}
		}

	}

	PAFW_Cancel_Unpaid_Order::init();

endif;
