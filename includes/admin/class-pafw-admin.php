<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_Admin' ) ) :

	class PAFW_Admin {

		static function init() {
			add_action( 'admin_menu', 'PAFW_Admin::admin_menu' );

			add_action( 'add_meta_boxes', array( 'PAFW_Meta_Box_Payment_Info', 'add_meta_boxes' ), 10, 2 );
			add_action( 'add_meta_boxes', array( 'PAFW_Meta_Box_Cash_Receipt', 'add_meta_boxes' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( 'PAFW_Admin', 'admin_enqueue_scripts' ) );
		}

		static function admin_menu() {
			add_menu_page( __( '심플페이 결제관리', 'pgall-for-woocommerce' ), __( '심플페이 결제관리', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_setting', '', PAFW()->plugin_url() . '/assets/images/mshop-icon.png', '20.876503947292' );
			add_submenu_page( 'pafw_setting', __( '결제설정', 'pgall-for-woocommerce' ), __( '결제설정', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_setting', 'PAFW_Admin_Settings::output' );
			if ( ! empty( PAFW()->get_enabled_payment_gateways() ) ) {
				add_submenu_page( 'pafw_setting', __( '리뷰설정', 'pgall-for-woocommerce' ), __( '리뷰설정', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_review_setting', 'PAFW_Admin_Review_Settings::output' );
				add_submenu_page( 'pafw_setting', __( '주문상태 설정', 'pgall-for-woocommerce' ), __( '주문상태 설정', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_order_status_setting', 'PAFW_Admin_Order_Status_Control_Settings::output' );
				add_submenu_page( 'pafw_setting', __( '결제수단 노출제어', 'pgall-for-woocommerce' ), __( '결제수단 노출제어', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_payment_method_setting', 'PAFW_Admin_Payment_Method_Control_Settings::output' );

				if ( PAFW_Cash_Receipt::is_enabled() ) {
					add_submenu_page( 'pafw_setting', __( '현금영수증', 'pgall-for-woocommerce' ), __( '현금영수증', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_cash_receipt', 'PAFW_Admin_Cash_Receipts::output' );
				}

				add_submenu_page( 'pafw_setting', __( '매출통계', 'pgall-for-woocommerce' ), __( '매출통계', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_sales_statistics', 'PAFW_Admin::sales_statistics' );
				add_submenu_page( 'pafw_setting', __( '결제통계', 'pgall-for-woocommerce' ), __( '결제통계', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_health_status', 'PAFW_Admin::payment_statistics' );
			}

			add_submenu_page( 'pafw_setting', __( '온라인 가입신청', 'pgall-for-woocommerce' ), __( '온라인 가입신청', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_apply_service', '' );
			add_submenu_page( 'pafw_setting', __( '매뉴얼', 'pgall-for-woocommerce' ), __( '매뉴얼', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_manual', '' );
		}

		static function sales_statistics() {
			if ( 0 == count( PAFW()->get_enabled_payment_gateways() ) ) {
				include( 'views/guide.php' );
			} else {
				include( 'views/sales-statistics.php' );
			}
		}

		static function payment_statistics() {
			if ( 0 == count( PAFW()->get_enabled_payment_gateways() ) ) {
				include( 'views/guide.php' );
			} else {
				include( 'views/payment-statistics.php' );
			}
		}

		static function admin_enqueue_scripts() {
			wp_enqueue_script( 'pafw-admin-menu', PAFW()->plugin_url() . '/assets/js/admin/admin-menu.js', array( 'jquery' ), PAFW_VERSION );
			wp_localize_script( 'pafw-admin-menu', '_pafw_admin_menu', array(
				'apply_service_url' => 'https://www.codemshop.com/pgall/apply-online/',
				'pafw_manual'       => 'https://manual.codemshop.com/docs/pgall/'
			) );
		}
	}

	PAFW_Admin::init();

endif;
