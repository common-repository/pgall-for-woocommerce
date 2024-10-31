<?php

defined( 'ABSPATH' ) || exit;
class PAFW_Install {
	static $update_functions = array(
		'5.0.0' => array( __CLASS__, 'update_5_0_0_function' )
	);
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'pafw_db_version' ), PAFW_VERSION, '<' ) ) {
			self::install();
		}
	}
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'pafw_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'pafw_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::create_tables();
		self::init_scheduled_action();

		$current_version = get_option( 'pafw_db_version', null );

		foreach ( self::$update_functions as $version => $callback ) {
			if ( version_compare( $current_version, $version, '<' ) ) {
				$callback();
			}
		}

		self::update_db_version();

		delete_transient( 'pafw_installing' );
	}

	public static function init_scheduled_action() {
		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) {

				as_unschedule_all_actions( 'pafw_cancel_unfinished_payment_request' );

				as_schedule_recurring_action(
					time(),
					HOUR_IN_SECONDS,
					'pafw_cancel_unfinished_payment_request'
				);
			}
		} catch ( Exception $e ) {

		}

		if ( wp_next_scheduled( 'pafw_cron' ) ) {
			wp_clear_scheduled_hook( 'pafw_cron' );
		}
	}
	public static function update_db_version( $version = null ) {
		delete_option( 'pafw_db_version' );
		add_option( 'pafw_db_version', is_null( $version ) ? PAFW_VERSION : $version );
	}

	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'pafw_transaction';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE `$table_name` (
                      `id` bigint(20) NOT NULL AUTO_INCREMENT,
                      `date` datetime NOT NULL,
                      `payment_method` varchar(50) NOT NULL,
                      `payment_method_title` varchar(50) NOT NULL,
                      `device_type` varchar(10) NOT NULL,
                      `order_id` bigint(20) DEFAULT NULL,
                      `order_total` float DEFAULT NULL,
                      `user_id` bigint(20) DEFAULT NULL,
                      `result_code` int(11) DEFAULT NULL,
                      `result_message` varchar(1000) DEFAULT NULL,
                      `error_code` varchar(20) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `s1` (`date`,`payment_method`,`result_code`,`device_type`) USING BTREE
                    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
		$table_name = $wpdb->prefix . 'pafw_bacs_receipt';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE `$table_name` (
					  `id` bigint(20) NOT NULL AUTO_INCREMENT,
					  `order_id` bigint(20) NOT NULL,
					  `customer_id` bigint(20) NOT NULL,
					  `status` varchar(255) DEFAULT NULL,
					  `receipt_number` varchar(100) DEFAULT NULL,
					  `message` varchar(1000) DEFAULT NULL,
					  `date` datetime DEFAULT NULL,
					  PRIMARY KEY (`id`),
					  KEY `receipt_number` (`receipt_number`),
					  KEY `status` (`status`),
					  KEY `customer_id` (`order_id`,`customer_id`) USING BTREE
                    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
	public static function update_5_0_0_function() {
		as_schedule_single_action(
			time(),
			"pafw_migrate_token",
			array(
				"paged" => 1
			)
		);
	}
}

PAFW_Install::init();
