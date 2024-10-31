<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PAFW_Admin_Dashboard' ) ) {
	class PAFW_Admin_Dashboard {

		public static function init() {
			if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ) {
				add_action( 'wp_dashboard_setup', array( __CLASS__, 'setup_dashboard_widget' ) );
			}
		}
		public static function setup_dashboard_widget() {
			wp_register_style( 'pafw-dashboard', plugins_url( '/assets/css/dashboard.css', PAFW_PLUGIN_FILE ), array(), PAFW_VERSION );
			wp_register_style( 'font-awesome', '//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' );

			wp_enqueue_style( 'pafw-dashboard' );
			wp_enqueue_style( 'font-awesome' );

			add_meta_box(
				'codem_sell_status',
				__( '판매 진행 현황', 'pgall-for-woocommerce' ),
				array( __CLASS__, 'sell_status_widget' ),
				'dashboard',
				'normal',
				'high'
			);

			add_meta_box(
				'codem_sell_total',
				__( '판매 실적 & 판매현황', 'pgall-for-woocommerce' ),
				array( __CLASS__, 'sell_total_widget' ),
				'dashboard',
				'side',
				'high'
			);
		}

		static function sell_status_widget() {
			ob_start();
			include( 'views/html-order-status.php' );
			ob_end_flush();
		}
		static function get_sale_data_for_legacy( $order_type, $date_from, $date_to, $order_statuses ) {
			global $wpdb;

			$query = "SELECT SUM(meta.meta_value) AS order_total, COUNT(posts.ID) AS order_count 
                      FROM {$wpdb->posts} AS posts
                      LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
                      WHERE
                          meta.meta_key       = '_order_total' 
                          AND posts.post_type     = '{$order_type}'
                          AND posts.post_status IN ( {$order_statuses})
                          AND posts.post_date_gmt BETWEEN '{$date_from}' AND '{$date_to}'";

			return $wpdb->get_row( $query, ARRAY_A );
		}
		static function get_sale_data_for_hpos( $order_type, $date_from, $date_to, $order_statuses ) {
			global $wpdb;

			$query = "SELECT SUM( total_amount ) AS order_total, COUNT( id ) AS order_count 
                      FROM {$wpdb->prefix}wc_orders
                      WHERE
                          type = '{$order_type}'
                          AND status IN ( {$order_statuses})
                          AND date_created_gmt between '{$date_from}' AND '{$date_to}'
            ";

			return $wpdb->get_row( $query, ARRAY_A );
		}
		static function sell_total_widget() {
            $sale_data_method = PAFW_HPOS::enabled() ? 'get_sale_data_for_hpos' : 'get_sale_data_for_legacy';

			$from_date = date( "Y-m-d 00:00:00", strtotime( current_time( 'mysql' ) ) );
			$to_date   = date( "Y-m-d 23:59:59", strtotime( current_time( 'mysql' ) ) );

			$date_from_gmt = date( 'Y-m-d H:i:s', strtotime( $from_date ) - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
			$date_to_gmt   = date( 'Y-m-d H:i:s', strtotime( $to_date ) - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

			$order_statuses = "'wc-" . implode( "','wc-", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'shipped', 'order-received', 'refunded' ) ) ) . "'";

			$today_sales  = self::$sale_data_method( 'shop_order', $date_from_gmt, $date_to_gmt, $order_statuses );
			$today_refund = self::$sale_data_method( 'shop_order_refund', $date_from_gmt, $date_to_gmt, $order_statuses );

			$from_date = date( "Y-m-01 00:00:00", strtotime( current_time( 'mysql' ) ) );
			$to_date   = date( "Y-m-d 23:59:59", strtotime( current_time( 'mysql' ) ) );

			$date_from_gmt = date( 'Y-m-d H:i:s', strtotime( $from_date ) - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
			$date_to_gmt   = date( 'Y-m-d H:i:s', strtotime( $to_date ) - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

			$month_sales  = self::$sale_data_method( 'shop_order', $date_from_gmt, $date_to_gmt, $order_statuses );
			$month_refund = self::$sale_data_method( 'shop_order_refund', $date_from_gmt, $date_to_gmt, $order_statuses );

			?>
            <div class="contets_box_con">
                <div class="chat_box chat_top gray">
                    <h2>TODAY</h2>
                    <p class="b_line"></p>
                    <p class="badge red"><?php echo number_format( $today_sales['order_count'] ); ?></p>
                    <h1><?php echo wc_price( $today_sales['order_total'] + $today_refund['order_total'] ); ?></h1>
                </div>
                <div class="chat_box chat_top gray">
                    <h2>MONTH</h2>
                    <p class="b_line"></p>
                    <p class="badge red"><?php echo number_format( $month_sales['order_count'] ); ?></p>
                    <h1><?php echo wc_price( $month_sales['order_total'] + $month_refund['order_total'] ); ?></h1>
                </div>
            </div>
			<?php
		}
	}

	PAFW_Admin_Dashboard::init();
}
