<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_Admin_Sales' ) ) :

	class PAFW_Admin_Sales {

		static $default_excluded_order_status = array( 'wc-cancelled', 'wc-failed', 'wc-on-hold', 'wc-pending', 'wc-cancel-request', 'wc-checkout-draft', 'trash' );
		static function get_order_total_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";

				$sql = "SELECT count( orders.id) count, SUM(orders.total_amount + orders.tax_amount) order_total
					FROM {$wpdb->prefix}wc_orders orders
					LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
					WHERE
						orders.type = 'shop_order'
						AND orders.status IN ( {$order_statuses_where} )
						{$date_where}
					";

				return $wpdb->get_row( $sql, ARRAY_A );
			} else {
				$date_where = "AND ( ( posts.post_type  = 'shop_order' AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' ) OR (posts.post_type  = 'shop_order_refund' AND posts.post_date BETWEEN '{$date_from}' AND '{$date_to}'))";

				$order_total_sql = "SELECT SUM(ordertotal_meta.meta_value) order_total
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type IN ('shop_order', 'shop_order_refund' )
						AND posts.post_status IN ( {$order_statuses_where} )
						{$date_where}
					";

				$excluded_order_statuses[] = 'wc-refunded';
				$order_statuses_where      = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

				$order_count_sql = "SELECT count( posts.ID )
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type ='shop_order'
						AND posts.post_status IN ( {$order_statuses_where} )
						AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}'
					";

				return array(
					'count'       => $wpdb->get_var( $order_count_sql ),
					'order_total' => $wpdb->get_var( $order_total_sql )
				);
			}
		}
		static function get_summary_data() {
			$first_day_of_week = ( new DateTime( current_time( 'mysql' ) ) )->modify( 'last sunday' )->format( 'Y-m-d 00:00:00' );

			return array(
				'today' => self::get_order_total_by_date( date( 'Y-m-d 00:00:00', strtotime( current_time( 'mysql' ) ) ) ),
				'week'  => self::get_order_total_by_date( $first_day_of_week ),
				'month' => self::get_order_total_by_date( date( 'Y-m-01 00:00:00', strtotime( current_time( 'mysql' ) ) ) ),
				'year'  => self::get_order_total_by_date( date( 'Y-01-01 00:00:00', strtotime( current_time( 'mysql' ) ) ) )
			);
		}
		static function get_daily_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			$excluded_order_statuses = array_diff( $excluded_order_statuses, array( 'wc-refunded' ) );

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$start_date = date( 'Y-m-d', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-d', strtotime( $date_to ) );

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";
				$gmt_offset = get_option( 'gmt_offset', 0 );

				$sql = "SELECT DATE_FORMAT( DATE_ADD( STR_TO_DATE( order_data.date_paid_gmt, '%Y-%m-%d %H:%i:%s' ), INTERVAL {$gmt_offset} HOUR ), '%Y-%m-%d')  date, SUM(orders.total_amount + orders.tax_amount) value
					FROM {$wpdb->prefix}wc_orders orders
					LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
					WHERE
						orders.type = 'shop_order'
						AND orders.status IN ( {$order_statuses_where} )
						{$date_where}
					GROUP BY date";
			} else {
				$date_where = "AND ( ( posts.post_type  = 'shop_order' AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' ) 
							  OR (posts.post_type  = 'shop_order_refund' AND posts.post_date BETWEEN '{$date_from}' AND '{$date_to}')
							)";

				$sql = "SELECT IF( posts.post_type  = 'shop_order', DATE_FORMAT(paiddate_meta.meta_value, '%Y-%m-%d'), DATE_FORMAT(posts.post_date, '%Y-%m-%d')) date, SUM(ordertotal_meta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type IN ('shop_order', 'shop_order_refund' ) 
						AND posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY date";
			}

			$results = $wpdb->get_results( $sql, ARRAY_A );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_weekly_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$start_date = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_from ) ) . ' -1 days' ) );
			$end_date   = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_to ) ) . ' -1 days' ) );

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";
				$gmt_offset = get_option( 'gmt_offset', 0 );

				$sql = "SELECT YEAR(sales_data.localdate) year, WEEK(sales_data.localdate) week, DATE_FORMAT(sales_data.localdate - INTERVAL (MOD(WEEKDAY(sales_data.localdate)+1, 7)) DAY, '%Y-%m-%d') date, sum(order_total) value
						FROM (
							SELECT DATE_ADD( STR_TO_DATE( order_data.date_paid_gmt, '%Y-%m-%d %H:%i:%s' ), INTERVAL {$gmt_offset} HOUR ) localdate,
	                            orders.total_amount + orders.tax_amount order_total
							FROM {$wpdb->prefix}wc_orders orders
							LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
							WHERE
								orders.type = 'shop_order'
								AND orders.status IN ( {$order_statuses_where} )
								{$date_where}
							) sales_data
						GROUP BY year, week";
			} else {
				$date_where = "AND ( ( posts.post_type  = 'shop_order' AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' ) 
							  OR (posts.post_type  = 'shop_order_refund' AND posts.post_date BETWEEN '{$date_from}' AND '{$date_to}')
							)";

				$sql = "SELECT IF( posts.post_type  = 'shop_order', YEAR(paiddate_meta.meta_value), YEAR(posts.post_date)) year,
						       IF( posts.post_type  = 'shop_order', WEEK(paiddate_meta.meta_value), WEEK(posts.post_date)) week, 
						       IF( posts.post_type  = 'shop_order', DATE_FORMAT(paiddate_meta.meta_value - INTERVAL (MOD(WEEKDAY(paiddate_meta.meta_value)+1, 7)) DAY, '%Y-%m-%d'), DATE_FORMAT(posts.post_date - INTERVAL (MOD(WEEKDAY(posts.post_date)+1, 7)) DAY, '%Y-%m-%d')) date,
							   SUM(ordertotal_meta.meta_value) value	
						FROM {$wpdb->posts} posts
						LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
						LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
						WHERE
							posts.post_type IN ('shop_order', 'shop_order_refund' ) 
							AND posts.post_status IN ( {$order_statuses_where}  )
							{$date_where}
						GROUP BY year, week";
			}

			$results = $wpdb->get_results( $sql, ARRAY_A );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_monthly_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$start_date = date( 'Y-m-01', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-01', strtotime( $date_to ) );

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";
				$gmt_offset = get_option( 'gmt_offset', 0 );

				$sql = "SELECT YEAR(sales_data.localdate) year, MONTH(sales_data.localdate) month, DATE_FORMAT(sales_data.localdate, '%Y-%m-01') date, sum(order_total) value
						FROM (
							SELECT DATE_ADD( STR_TO_DATE( order_data.date_paid_gmt, '%Y-%m-%d %H:%i:%s' ), INTERVAL {$gmt_offset} HOUR ) localdate,
	                               orders.total_amount + orders.tax_amount order_total
							FROM {$wpdb->prefix}wc_orders orders
							LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
							WHERE
								orders.type = 'shop_order'
								AND orders.status IN ( {$order_statuses_where} )
								{$date_where}
							) sales_data
						GROUP BY year, month";
			} else {
				$date_where = "AND ( ( posts.post_type  = 'shop_order' AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' ) 
							  OR (posts.post_type  = 'shop_order_refund' AND posts.post_date BETWEEN '{$date_from}' AND '{$date_to}')
							)";

				$sql = "SELECT IF( posts.post_type  = 'shop_order', YEAR(paiddate_meta.meta_value), YEAR(posts.post_date)) year,
						       IF( posts.post_type  = 'shop_order', MONTH(paiddate_meta.meta_value), MONTH(posts.post_date)) month, 
						       IF( posts.post_type  = 'shop_order', DATE_FORMAT(paiddate_meta.meta_value, '%Y-%m-01'), DATE_FORMAT(posts.post_date, '%Y-%m-01')) date,
				  			   SUM(ordertotal_meta.meta_value) value
						FROM {$wpdb->posts} posts
						LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
						LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
						WHERE
							posts.post_type IN ('shop_order', 'shop_order_refund' ) 
							AND posts.post_status IN ( {$order_statuses_where}  )
							{$date_where}
					GROUP BY year, month";
			}

			$results = $wpdb->get_results( $sql, ARRAY_A );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_sales_by_hour( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			$excluded_order_statuses[] = 'wc-refunded';

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";
				$gmt_offset = get_option( 'gmt_offset', 0 );

				$sql = "SELECT HOUR( DATE_ADD( STR_TO_DATE( order_data.date_paid_gmt, '%Y-%m-%d %H:%i:%s' ), INTERVAL {$gmt_offset} HOUR ) ) hour, SUM(orders.total_amount + orders.tax_amount) value
					FROM {$wpdb->prefix}wc_orders orders
					LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
					WHERE
						orders.type = 'shop_order'
						AND orders.status IN ( {$order_statuses_where} )
						{$date_where}
					GROUP BY hour";
			} else {
				$date_where = "AND ( paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' )";

				$sql = "SELECT HOUR(paiddate_meta.meta_value ) hour, count( posts.ID) count, SUM(ordertotal_meta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY hour";
			}

			return $wpdb->get_results( $sql, ARRAY_A );
		}
		static function get_sales_by_day_of_week( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			$excluded_order_statuses[] = 'wc-refunded';

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";
				$gmt_offset = get_option( 'gmt_offset', 0 );

				$sql = "SELECT DAYOFWEEK( DATE_ADD( STR_TO_DATE( order_data.date_paid_gmt, '%Y-%m-%d %H:%i:%s' ), INTERVAL {$gmt_offset} HOUR ) ) day_of_week, SUM(orders.total_amount + orders.tax_amount) value
					FROM {$wpdb->prefix}wc_orders orders
					LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
					WHERE
						orders.type = 'shop_order'
						AND orders.status IN ( {$order_statuses_where} )
						{$date_where}
					GROUP BY day_of_week";
			} else {
				$date_where = "AND ( paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' )";

				$sql = "SELECT DAYOFWEEK(paiddate_meta.meta_value) day_of_week, count(posts.ID) count, SUM(ordertotal_meta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY day_of_week";
			}

			$day_of_weeks = array(
				'1' => '일요일',
				'2' => '월요일',
				'3' => '화요일',
				'4' => '수요일',
				'5' => '목요일',
				'6' => '금요일',
				'7' => '토요일'
			);

			$result       = $wpdb->get_results( $sql, ARRAY_A );
			$keys         = wp_list_pluck( $result, 'day_of_week' );
			$keys         = array_flip( $keys );
			$missing_data = array_diff_key( $day_of_weeks, $keys );
			foreach ( $missing_data as $key => $value ) {
				$result[] = array(
					'day_of_week' => (string) $key,
					'count'       => "0",
					'value'       => "0"
				);
			}

			$sort_result = array();
			foreach ( $result as $value ) {
				$sort_result[ $value['day_of_week'] ]                = $value;
				$sort_result[ $value['day_of_week'] ]['day_of_week'] = $day_of_weeks[ $value['day_of_week'] ];
			}

			ksort( $sort_result );

			$result = array_values( $sort_result );

			return $result;

		}
		static function get_sales_by_order_status( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59', strtotime( current_time( 'mysql' ) ) );
			}

			if ( PAFW_HPOS::enabled() ) {
				$date_from = pafw_get_gmdate( $date_from );
				$date_to   = pafw_get_gmdate( $date_to );

				$date_where = "AND ( order_data.date_paid_gmt BETWEEN '{$date_from}' AND '{$date_to}' )";

				$sql = "SELECT orders.status order_status, count( orders.id ) count, SUM(orders.total_amount + orders.tax_amount) amount
					FROM {$wpdb->prefix}wc_orders orders
					LEFT JOIN {$wpdb->prefix}wc_order_operational_data AS order_data ON orders.id = order_data.order_id
					WHERE
						orders.type = 'shop_order'
						{$date_where}
					GROUP BY order_status";
			} else {
				$sql = "SELECT posts.post_status order_status, count( posts.ID ) count, SUM(ordertotal_meta.meta_value) amount
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} AS ordertotal_meta ON posts.ID = ordertotal_meta.post_id AND ordertotal_meta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta} AS paiddate_meta ON posts.ID = paiddate_meta.post_id AND paiddate_meta.meta_key = '_paid_date'
					WHERE
						posts.post_type = 'shop_order'
						AND ( 
							( posts.post_status = 'wc-on-hold' AND posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' ) 
							OR ( posts.post_type  = 'shop_order' AND paiddate_meta.meta_value BETWEEN '{$date_from}' AND '{$date_to}' )
						)
					GROUP BY order_status";
			}

			$result = $wpdb->get_results( $sql, ARRAY_A );

			$merged_order_statuses = array(
				'processing' => array(
					'order-received',
					'place-order'
				)
			);

			$merged_order_statuses = apply_filters( 'pafw_sales_statistics_merged_order_statuses', $merged_order_statuses );

			if ( ! empty( $merged_order_statuses ) ) {
				$_result = array();
				foreach ( $result as $item ) {
					$_result[ $item['order_status'] ] = $item;
				}

				$result = $_result;

				foreach ( $merged_order_statuses as $key => $order_statuses ) {
					if ( empty( $result[ 'wc-' . $key ] ) ) {
						$result[ 'wc-' . $key ] = array(
							'order_status' => 'wc-' . $key,
							'count'        => 0,
							'amount'       => 0
						);
					}

					foreach ( $order_statuses as $order_status ) {
						if ( ! empty( $result[ 'wc-' . $order_status ] ) ) {
							$result[ 'wc-' . $key ]['count']  += $result[ 'wc-' . $order_status ]['count'];
							$result[ 'wc-' . $key ]['amount'] += $result[ 'wc-' . $order_status ]['amount'];
							unset( $result[ 'wc-' . $order_status ] );
						}
					}
				}
			}

			foreach ( $result as &$item ) {
				$item['amount'] = number_format( $item['amount'] );
				$item['count']  = number_format( $item['count'] );
			}

			return $result;
		}

		static function get_data() {
			$date_from = wc_clean( $_REQUEST['date_from'] ) . ' 00:00:00';
			$date_to   = wc_clean( $_REQUEST['date_to'] ) . ' 23:59:59';
			$interval  = wc_clean( $_REQUEST['interval'] );

			if ( '1d' == $interval ) {
				$data = self::get_daily_sales_by_date( $date_from, $date_to );
			} else if ( '1w' == $interval ) {
				$data = self::get_weekly_sales_by_date( $date_from, $date_to );
			} else if ( '1M' == $interval ) {
				$data = self::get_monthly_sales_by_date( $date_from, $date_to );
			}

			wp_send_json_success( array(
				'order_stat_by_date'         => $data,
				'order_stat_by_day_of_week'  => self::get_sales_by_day_of_week( $date_from, $date_to ),
				'order_stat_by_hour'         => self::get_sales_by_hour( $date_from, $date_to ),
				'order_stat_by_order_status' => self::get_sales_by_order_status( $date_from, $date_to ),
			) );
		}
	}

endif;
