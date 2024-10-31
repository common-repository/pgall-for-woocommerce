<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Cash_Receipt' ) ) {

	class PAFW_Cash_Receipt {

		const STATUS_PENDING = 'pending';
		const STATUS_ISSUED = 'issued';
		const STATUS_CANCELLED = 'cancelled';
		const STATUS_FAILED = 'failed';

		const USAGE_ID = 'ID';
		const USAGE_POE = 'POE';

		const ISSUE_TYPE_PHONE = 'phone';
		const ISSUE_TYPE_SOCIAL = 'social';
		const ISSUE_TYPE_CARD = 'card';
		const ISSUE_TYPE_BIZ_REG = 'biz_reg';

		protected static $supported_gateway = array(
			'inicis'  => 'WC_Gateway_Inicis_Stdcard',
			'nicepay' => 'WC_Gateway_Nicepay_Card',
			'kcp'     => 'WC_Gateway_Kcp_Card',
			'lguplus' => 'WC_Gateway_Lguplus_Card',
			'tosspayments' => 'WC_Gateway_Tosspayments_Card',
		);
		public static function get_usage() {
			return array(
				self::USAGE_ID  => __( '개인소득공제용', 'pgall-for-woocommerce' ),
				self::USAGE_POE => __( '사업자증빙용(세금계산서용)', 'pgall-for-woocommerce' ),
			);
		}
		public static function get_issue_type() {
			return array(
				self::ISSUE_TYPE_PHONE   => __( '휴대폰번호', 'pgall-for-woocommerce' ),
				self::ISSUE_TYPE_SOCIAL  => __( '주민등록번호', 'pgall-for-woocommerce' ),
				self::ISSUE_TYPE_CARD    => __( '현금영수증 카드번호', 'pgall-for-woocommerce' ),
				self::ISSUE_TYPE_BIZ_REG => __( '사업자 등록번호', 'pgall-for-woocommerce' ),
			);
		}
		public static function get_statuses() {
			return array(
				self::STATUS_PENDING   => __( '발급대기', 'pgall-for-woocommerce' ),
				self::STATUS_ISSUED    => __( '발급완료', 'pgall-for-woocommerce' ),
				self::STATUS_CANCELLED => __( '발급취소', 'pgall-for-woocommerce' ),
				self::STATUS_FAILED    => __( '발급실패', 'pgall-for-woocommerce' ),
			);
		}
		public static function get_status_name( $status ) {
			$statuses = self::get_statuses();

			return pafw_get( $statuses, $status );
		}
		public static function get_usage_label( $usage ) {
			$statuses = self::get_usage();

			return pafw_get( $statuses, $usage );
		}
		public static function get_issue_type_label( $type ) {
			$issue_types = self::get_issue_type();

			return pafw_get( $issue_types, $type );
		}
		public static function get_enabled_gateway() {
			foreach ( self::$supported_gateway as $gateway => $payment_method_class ) {
				if ( 'yes' == get_option( 'pafw-gw-' . $gateway ) ) {
					return $payment_method_class;
				}
			}

			return null;
		}

		public static function get_user_default_receipt_info( $user_id ) {
			if ( $user_id > 0 && 'yes' == get_user_meta( $user_id, '_pafw_bacs_receipt', true ) ) {
				$usage      = get_user_meta( $user_id, '_pafw_bacs_receipt_usage', true );
				$issue_type = get_user_meta( $user_id, '_pafw_bacs_receipt_issue_type', true );
				$reg_no     = get_user_meta( $user_id, '_pafw_bacs_receipt_reg_number', true );

				return sprintf( "%s (%s : %s)", self::get_usage_label( $usage ), self::get_issue_type_label( $issue_type ), $reg_no );
			}

			return '';
		}
		public static function is_enabled() {
			return 'yes' == get_option( 'pafw_use_bacs_receipt', 'no' ) && ! is_null( self::get_enabled_gateway() );
		}

		public static function get_gateway() {
			$payment_method_class = self::get_enabled_gateway();

			if ( ! is_null( $payment_method_class ) ) {
				return new $payment_method_class();
			}

			return null;
		}
		public static function get_receipt_request( $order_id ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'pafw_bacs_receipt';

			return $wpdb->get_row( "SELECT * FROM {$table_name} WHERE order_id = $order_id", ARRAY_A );
		}
		public static function insert_receipt_request( $order ) {
			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'pafw_bacs_receipt',
				array(
					'order_id'    => $order->get_id(),
					'customer_id' => $order->get_customer_id(),
					'status'      => self::STATUS_PENDING,
					'date'        => current_time( 'mysql' )
				),
				array(
					'%d',
					'%d',
					'%s',
					'%s',
				)
			);

			return $wpdb->insert_id;
		}
		public static function update_receipt_request( $order_id, $args ) {
			global $wpdb;

			$wpdb->update(
				$wpdb->prefix . 'pafw_bacs_receipt',
				$args,
				array(
					'order_id' => $order_id
				)
			);
		}
		public static function get_receipt_requests( $args ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'pafw_bacs_receipt';

			$page_size = isset( $args['pageSize'] ) ? intval( $args['pageSize'] ) : 20;
			$page      = pafw_get( $args, 'page', 0 );

			if ( $page_size > 0 ) {
				$limit = 'LIMIT ' . $page_size;
			}

			$wheres = array();

			if ( ! empty( pafw_get( $args, 'customer' ) ) ) {
				$wheres[] = sprintf( 'customer_id IN (%s)', pafw_get( $args, 'customer' ) );
			}

			if ( ! empty( pafw_get( $args, 'term' ) ) ) {
				list( $date_from, $date_to ) = explode( ',', pafw_get( $args, 'term' ) );
				if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
					$wheres[] = 'date between "' . $date_from . ' 00:00:00" AND "' . $date_to . ' 23:59:59" ';
				}
			}

			if ( 'ALL' != pafw_get( $args, 'status', 'ALL' ) ) {
				$wheres[] = sprintf( 'status = "%s"', pafw_get( $args, 'status', 'ALL' ) );
			}

			if ( $page_size > 0 && $page > 0 ) {
				$limit = 'LIMIT ' . ( $page * $page_size ) . ', ' . $page_size;
			}

			$where = ! empty( $wheres ) ? 'WHERE ' . implode( ' AND ', $wheres ) : '';

			$query = "
				SELECT SQL_CALC_FOUND_ROWS *
				FROM {$table_name}
				{$where}
				ORDER BY id desc
				{$limit}";

			return array(
				'results'     => $wpdb->get_results( $query, ARRAY_A ),
				'total_count' => $wpdb->get_var( "SELECT FOUND_ROWS();" ),
			);
		}

		public static function get_issue_receipt_order_statuses() {
			return explode( ',', get_option( 'pafw_bacs_receipt_issue_statuses' ) );
		}

		public static function get_cancel_receipt_order_statuses() {
			return explode( ',', get_option( 'pafw_bacs_receipt_cancel_statuses' ) );
		}
		public static function maybe_process_cash_receipt( $order_id, $old_status, $new_status ) {
			if ( self::is_enabled() ) {
				$order = wc_get_order( $order_id );

				try {
					if ( $order && 'bacs' == $order->get_payment_method() ) {
						$gateway = PAFW_Cash_Receipt::get_gateway();

						if ( is_null( $gateway ) ) {
							throw new Exception( __( '현금영수증 발행을 지원하는 결제대행사가 없습니다.', 'pgall-for-woocommerce' ) );
						}

						if ( in_array( $order->get_status(), self::get_issue_receipt_order_statuses() ) && empty( $order->get_meta( '_pafw_bacs_receipt_tid' ) ) ) {
							$gateway->issue_cash_receipt( $order_id );
						} else if ( in_array( $order->get_status(), self::get_cancel_receipt_order_statuses() ) ) {
							$gateway->cancel_cash_receipt( $order_id );
						}
					}
				} catch ( Exception $e ) {
					$order->add_order_note( $e->getMessage() );
				}
			}
		}

	}

}