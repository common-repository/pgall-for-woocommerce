<?php


//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	abstract class PAFW_Payment_Gateway extends WC_Payment_Gateway {
		protected $master_id = '';
		protected $api_version = '3.1';
		protected $pg_title = '';
		protected static $logger = null;
		public $method_title = null;
		public $view_transaction_url = '';
		protected $key_for_test = array();
		protected $skip_check_test_user = false;
		public function __construct( $skip_check_test_user = false ) {
			$settings = pafw_get_settings( $this->id );

			if ( $settings ) {
				$this->settings = $settings->get_settings();

				$this->adjust_settings();

				$this->countries  = array( 'KR' );
				$this->has_fields = false;

				if ( 'yes' == get_option( 'pafw-gw-' . $this->master_id ) ) {
					$this->enabled = pafw_get( $this->settings, 'enabled', 'no' );

					$this->skip_check_test_user = $skip_check_test_user;
					add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
					add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'process_payment_response' ) );

					add_action( 'pafw_' . $this->id . '_payment', array( $this, 'wc_api_request_payment' ) );
					add_action( 'pafw_' . $this->id . '_fail', array( $this, 'wc_api_request_fail' ) );
					add_action( 'pafw_' . $this->id . '_cancel', array( $this, 'wc_api_request_cancel' ) );
					add_action( 'pafw_' . $this->id . '_vbank_noti', array( $this, 'wc_api_vbank_noti' ) );
				} else {
					$this->enabled = 'no';
				}
			}

			$this->supports[] = 'pafw';
		}
		function get_master_id() {
			return $this->master_id;
		}
		function get_api_version() {
			return $this->api_version;
		}
		function get_pg_title() {
			return $this->pg_title;
		}

		function payment_window_mode() {
			if ( wp_is_mobile() ) {
				return 'page';
			} else {
				return 'iframe';
			}
		}

		function issue_bill_key_mode() {
			return 'transaction';
		}
		function is_test_key() {
			return in_array( $this->get_merchant_id(), $this->key_for_test );
		}
		function cancel_bill_key_when_change_to_same_payment_method() {
			return true;
		}
		function issue_bill_key_when_change_payment_method() {
			return true;
		}

		static function gateway_domain() {
			return 'https://payment.codemshop.com';
		}

		function gateway_url( $command ) {
			return sprintf( '%s/%s/pg/%s/%s/', self::gateway_domain(), $this->get_api_version(), $this->get_master_id(), $command );
		}
		function adjust_settings() {
		}
		function is_vbank( $order = null ) {
			return $this->supports( 'pafw-vbank' );
		}
		function is_escrow( $order = null ) {
			return $this->supports( 'pafw-escrow' );
		}
		function add_log( $msg ) {
			if ( is_null( self::$logger ) ) {
				self::$logger = new WC_Logger();
			}

			self::$logger->add( $this->id, $msg );
		}
		function validate_payment_method_of_order( $order ) {
			return $this->id == $order->get_payment_method();
		}
		public function woocommerce_payment_complete_order_status( $order_status, $order_id, $order = null ) {
			if ( ! empty( $this->settings['order_status_after_payment'] ) ) {
				$order_status = $this->settings['order_status_after_payment'];
			}

			return $order_status;
		}
		public function is_available() {
			if ( ! current_user_can( 'manage_options' ) && ! $this->skip_check_test_user && ( is_checkout() || is_add_payment_method_page() ) && 'production' != pafw_get( $this->settings, 'operation_mode', 'production' ) ) {
				$user = wp_get_current_user();

				if ( empty( $user ) || ! is_user_logged_in() || $user->user_login != pafw_get( $this->settings, 'test_user_id' ) ) {
					return false;
				}
			}

			if ( current_user_can( 'manage_options' ) || in_array( get_woocommerce_currency(), apply_filters( 'pafw_supported_currencies', array( 'KRW' ) ) ) ) {
				return parent::is_available();
			} else {
				return false;
			}
		}
		public function is_refundable( $order, $screen = 'admin' ) {
			if ( 'admin' == $screen ) {
				return ! in_array( $order->get_status(), array( 'pending', 'refunded', 'cancelled' ) );
			} else {
				$order_statuses = pafw_get( $this->settings, 'possible_refund_status_for_' . $screen );

				return is_array( $order_statuses ) && in_array( $order->get_status(), $order_statuses );
			}
		}
		public function is_fully_refundable( $order, $screen = 'admin' ) {
			return $this->is_refundable( $order, $screen ) && 0 == $order->get_total_refunded() && ( ! $this->is_vbank( $order ) || 'yes' != $order->get_meta( '_pafw_vbank_noti_received' ) );
		}
		function has_enough_stock( $order ) {
			if ( function_exists( 'wcs_is_subscription' ) && ( wcs_is_subscription( $order ) || wcs_order_contains_renewal( $order ) || wcs_order_contains_early_renewal( $order ) || wcs_order_contains_switch( $order ) ) ) {
				return;
			}

			if ( is_a( $order, 'WC_Abstract_Order' ) && 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();

					if ( $product && $product->exists() ) {
						if ( $product->managing_stock() && ! $product->has_enough_stock( $item['qty'] ) ) {
							throw new Exception( sprintf( __( '결제오류 : [%d] %s 상품의 재고가 부족합니다.', 'pgall-for-woocommerce' ), $product->get_id(), $product->get_title() ), '1101' );
						}
					}
				}
			}

			do_action( 'pafw_has_enough_stock', $order );
		}
		function thankyou_page( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_thankyou_page', $order );

			if ( $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array( 'order' => $order ), '', PAFW()->template_path() );
			} else {
				wc_get_template( 'pafw/thankyou_page.php', array( 'payment_method' => $this->id, 'title' => $this->title ), '', PAFW()->template_path() );
			}
		}
		public function check_shop_order_capability() {
			if ( ! is_user_logged_in() || ! current_user_can( 'publish_shop_orders' ) ) {
				throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
			}
		}
		public function cancel_order( $order ) {
			if ( ! $this->is_refundable( $order, 'mypage' ) ) {
				wc_add_notice( __( '주문을 취소할 수 없는 상태입니다. 관리자에게 문의해 주세요.', 'pgall-for-woocommerce' ), 'error' );

				return;
			}
			if ( in_array( $order->get_status(), array( 'pending', 'failed' ) ) ) {
				$order->update_status( 'cancelled' );
				wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

				return;
			}
			if ( ( $this->is_vbank( $order ) && $order->get_status() == 'on-hold' ) && ! $this->supports( 'pafw-vbank-cancel' ) ) {
				$order->update_status( 'cancelled' );
				wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

				return;
			}

			$transaction_id = $this->get_transaction_id( $order );

			if ( ! empty( $transaction_id ) ) {
				try {
					$response = PAFW_Gateway::request_cancel( $order, __( '사용자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_001', 'pgall-for-woocommerce' ), $this );
					if ( $response == "success" ) {
						if ( $_POST['refund_request'] ) {
							unset( $_POST['refund_request'] );
						}

						$order->update_status( 'cancelled' );

						wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

						$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
						$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
						$order->save();

						$this->add_payment_log( $order, '[ 결제 취소 완료 ]', '사용자에 의해 주문이 취소 되었습니다.' );
					}
				} catch ( Exception $e ) {
					if ( apply_filters( 'pafw_force_update_order_status_to_cancel', true, $order ) ) {

						$order->update_status( 'cancelled' );

						$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
						$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
						$order->save();
					}

					wc_add_notice( $e->getMessage(), 'error' );
					$order->add_order_note( sprintf( __( '사용자 주문취소 시도 실패 (에러메세지 : %s)', 'pgall-for-woocommerce' ), $e->getMessage() ) );
				}
			} else {
				wc_add_notice( __( '주문 취소 시도중 오류 (에러메시지 : 거래번호 없음)가 발생했습니다. 관리자에게 문의해주세요.', 'pgall-for-woocommerce' ), 'error' );
				$order->add_order_note( sprintf( __( '사용자 주문취소 시도 실패 (에러메세지 : %s)', 'pgall-for-woocommerce' ), '결제수단 및 거래번호 없음' ) );
			}
		}
		function get_transaction_id( $order ) {
			$transaction_id = $order->get_transaction_id();

			if ( empty( $transaction_id ) && $this->is_vbank( $order ) ) {
				$transaction_id = $order->get_meta( '_pafw_vacc_tid' );
			}

			return $transaction_id;
		}
		public function get_order( $order_id = null, $order_key = null ) {
			$order = apply_filters( 'pafw_get_order', null, $order_id );

			if ( is_null( $order ) ) {
				if ( is_null( $order_id ) && isset( $_REQUEST['order_id'] ) ) {
					$order_id = wc_clean( $_REQUEST['order_id'] );
				}

				if ( is_null( $order_key ) && isset( $_REQUEST['order_key'] ) ) {
					$order_key = wc_clean( $_REQUEST['order_key'] );
				}

				if ( is_null( $order_id ) ) {
					throw new Exception( __( '필수 파라미터가 누락되었습니다. [주문아이디]', 'pgall-for-woocommerce' ), '1001' );
				}

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					throw new Exception( __( '주문을 찾을 수 없습니다.', 'pgall-for-woocommerce' ), '1002' );
				}

				if ( ! is_null( $order_key ) && $order_key != $order->get_order_key() ) {
					throw new Exception( __( '주문 정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '1003' );
				}
			}

			return $order;
		}
		function process_order_pay() {
			$params = array();

			if ( ! empty( $_POST['data'] ) ) {
				parse_str( wc_clean( $_POST['data'] ), $params ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				$_POST = array_merge( $_POST, $params );
			}

			wp_send_json_success( $this->process_payment( wc_clean( $_POST['order_id'] ) ) );
		}
		function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			return $this->get_payment_form( $order_id, $order->get_order_key() );
		}
		function get_payment_form( $order_id = null, $order_key = null ) {
			try {
				$order = $this->get_order( $order_id, $order_key );

				$response = PAFW_Gateway::register_order( $order, $this );

				return array_merge( array( 'result' => 'success' ), $response );
			} catch ( Exception $e ) {
				wp_send_json( array(
					'result'   => 'failure',
					'messages' => $e->getMessage(),
				) );
			}
		}
		function wc_api_request_payment() {
			try {
				$order = null;

				if ( empty( $_GET['transaction_id'] ) || empty( $_GET['auth_token'] ) || empty( $_GET['order_id'] ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9000' );
				}

				$order = $this->get_order( wc_clean( $_GET['order_id'] ) );

				if ( ! pafw_is_subscription( $order ) && $_GET['transaction_id'] != $order->get_meta( 'pafw_transaction_id' ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9100' );
				}

				$this->validate_order_status( $order );


				PAFW_Gateway::request_approval( $this, $order );

			} catch ( Exception $e ) {
				$this->handle_exception( $e, $order );
			}
		}
		function wc_api_request_fail() {
			$object = null;

			if ( isset( $_GET['order_id'] ) ) {
				$object = wc_get_order( wc_clean( $this->get_order_id_from_txnid( $_GET['order_id'] ) ) );
			} else if ( isset( $_GET['user_id'] ) ) {
				$object = get_userdata( wc_clean( $_GET['user_id'] ) );
			}

			$e = new Exception( sprintf( "[PAFW-ERR-%s] %s", wc_clean( $_GET['res_cd'] ), wc_clean( $_GET['res_msg'] ) ) );

			$this->handle_exception( $e, $object );
		}
		function wc_api_request_cancel() {
			$object = null;

			if ( isset( $_GET['order_id'] ) ) {
				$order_id = $this->get_order_id_from_txnid( wc_clean( $_GET['order_id'] ) );
				$object   = wc_get_order( $order_id );
			} else if ( isset( $_GET['user_id'] ) ) {
				$object = get_userdata( wc_clean( $_GET['user_id'] ) );
			}

			$e = new Exception( __( '결제를 취소하셨습니다.', 'pgall-for-woocommerce' ) );

			$this->handle_exception( $e, $object );
		}

		function process_payment_response() {
			$this->add_log( "Process Payment Response : " . wc_clean( $_REQUEST['type'] ) );

			try {
				if ( empty( $_REQUEST['type'] ) ) {
					throw new Exception( __( '잘못된 요청입니다. - REQUEST TYPE 없음.', 'pgall-for-woocommerce' ) );
				}

				do_action( 'pafw_' . $this->id . '_' . wc_clean( $_REQUEST['type'] ) );

				die();
			} catch ( Exception $e ) {
				wp_die( $e->getMessage() );
			}
		}

		function get_escrow_company_name() {
			return pafw_get( $this->settings, 'delivery_company_name' );
		}
		function get_order_id_from_txnid( $txnid ) {
			$ids = explode( '_', $txnid );

			if ( count( $ids ) > 0 ) {
				return $ids[0];
			}

			return -1;
		}
		function get_txnid( $order ) {
			$txnid = $order->get_meta( '_pafw_txnid', true );

			if ( empty( $txnid ) ) {
				$txnid = $order->get_id() . '_' . date( "ymd" ) . '_' . date( "his" );
				$order->update_meta_data( '_pafw_txnid', $txnid );
				$order->save_meta_data();
			}

			return $txnid;
		}
		function clear_txnid( $order ) {
			$order->delete_meta_data( '_pafw_txnid' );
			$order->save_meta_data();
		}
		function validate_txnid( $order, $txnid ) {
			return $txnid == $order->get_meta( '_pafw_txnid' );
		}
		function get_api_url( $type ) {
			if ( is_array( $type ) ) {
				$api_url = add_query_arg( $type, untrailingslashit( WC()->api_request_url( get_class( $this ), pafw_check_ssl() ) ) );
			} else {
				$api_url = add_query_arg( array( 'type' => $type ), untrailingslashit( WC()->api_request_url( get_class( $this ), pafw_check_ssl() ) ) );
			}

			return $api_url;
		}
		function validate_order_status( $order, $auto_cancel = false ) {
			if ( ! pafw_is_subscription( $order ) && ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'on-hold', 'pending', 'failed' ), $order ) ) ) {
				$paid_date      = $order->get_date_paid();
				$transaction_id = $this->get_transaction_id( $order );

				if ( empty( $paid_date ) || empty( $transaction_id ) ) {
					if ( $auto_cancel && ! empty( $transaction_id ) ) {
						PAFW_Gateway::request_cancel( $order, '시스템 자동 취소 처리', '0', $this );
					}
					throw new Exception( sprintf( __( '유효하지 않은 주문입니다. 주문상태(%s)가 잘못 되었거나 결제 대기시간 초과로 취소된 주문입니다.', 'pgall-for-woocommerce' ), $order->get_status() ), '2001' );

				} else {
					throw new Exception( __( '이미 결제가 완료된 주문입니다.', 'pgall-for-woocommerce' ), '2002' );
				}
			}
		}
		function woocommerce_view_order( $order_id, $order ) {
			if ( $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array( 'order' => $order ), '', PAFW()->template_path() );
			}

			if ( $this->is_escrow( $order ) ) {
				if ( 'yes' == $order->get_meta( '_pafw_escrow_register_delivery_info' ) ) {
					$delivery_shipping_num = $order->get_meta( '_pafw_escrow_tracking_number' );
					$delivery_company_name = pafw_get( $this->settings, 'delivery_company_name' );

					wc_get_template( 'pafw/escrow.php', array(
						'order'                 => $order,
						'delivery_shipping_num' => $delivery_shipping_num,
						'delivery_company_name' => $delivery_company_name
					), '', PAFW()->template_path() );
				}
			}
		}
		public function my_account_my_orders_actions( $actions, $order ) {
			if ( $this->validate_payment_method_of_order( $order ) && $this->is_refundable( $order, 'mypage' ) ) {

				$cancel_endpoint    = get_permalink( wc_get_page_id( 'cart' ) );
				$myaccount_endpoint = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );

				$actions['cancel'] = array(
					'url'  => wp_nonce_url( add_query_arg( array(
						'pafw-cancel-order' => 'true',
						'order_key'         => $order->get_order_key(),
						'order_id'          => $order->get_id(),
						'redirect'          => $myaccount_endpoint
					), $cancel_endpoint ), 'pafw-cancel-order-' . $order->get_id() . '-' . $order->get_order_key() ),
					'name' => __( 'Cancel', 'woocommerce' )
				);
			} else {
				unset( $actions['cancel'] );
			}

			return $actions;
		}
		public function refund_request() {

			$this->check_shop_order_capability();

			$order = $this->get_order();

			if ( ! $this->is_refundable( $order ) ) {
				throw new Exception( __( '주문을 취소할 수 없는 상태입니다.', 'pgall-for-woocommerce' ) );
			}

			$transaction_id = $this->get_transaction_id( $order );

			if ( empty( $transaction_id ) ) {
				throw new Exception( __( '주문 정보에 오류가 있습니다. [ 거래번호 없음 ]', 'pgall-for-woocommerce' ) );
			}

			if ( PAFW_Gateway::request_cancel( $order, __( '관리자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_002', 'pgall-for-woocommerce' ), $this ) ) {
				if ( isset( $_POST['refund_request'] ) ) {
					unset( $_POST['refund_request'] );
				}

				$order->update_status( 'refunded', '관리자에 의해 주문이 취소 되었습니다.' );

				$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
				$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 결제 취소 완료 ]', '관리자에 의해 주문이 취소 되었습니다.' );

				wp_send_json_success( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ) );
			}

		}
		public function cancel_unpaid_order( $order ) {
			if ( 'on-hold' != $order->get_status() || ! $this->supports( 'pafw-vbank' ) || empty( $this->get_transaction_id( $order ) ) ) {
				return false;
			}
			$vacc_date = $order->get_meta( '_pafw_vacc_date' );
			$vacc_date = date( 'Ymd235959', strtotime( $vacc_date ) );
			if ( strtotime( $vacc_date ) > strtotime( current_time( 'mysql' ) ) ) {
				return false;
			}

			try {
				PAFW_Gateway::request_cancel( $order, __( '관리자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_002', 'pgall-for-woocommerce' ), $this );
			} catch ( Exception $e ) {
			}

			$order->update_status( 'cancelled', __( '[무통장입금 자동취소] 지불되지 않은 주문이 취소 처리 되었습니다.', 'pgall-for-woocommerce' ) );

			$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
			$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
			$order->save_meta_data();

			$this->add_payment_log( $order, '[무통장입금 자동취소 성공]', '지불되지 않은 주문이 취소 처리 되었습니다.' );

			return true;
		}

		public function woocommerce_email_before_order_table( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && 'on-hold' == $order->get_status() && $this->id == $order->get_payment_method() && $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array( 'order' => $order ), '', PAFW()->template_path() );
			}
		}

		public function mshop_email_customer_details( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && 'on-hold' == $order->get_status() && $this->id == $order->get_payment_method() && $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array( 'order' => $order ), '', PAFW()->template_path() );
			}
		}
		public function handle_exception( $e, $order, $redirect = true ) {
			try {
				if ( $e->getCode() ) {
					$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				} else {
					$message = $e->getMessage();
				}

				$this->add_log( "[오류] " . $message . "\n" . print_r( wc_clean( $_REQUEST ), true ) );

				if ( $order && is_a( $order, 'WC_Abstract_Order' ) && ! pafw_is_subscription( $order ) ) {
					$order->add_order_note( $message );
					if ( empty( $order->get_date_paid() ) && 'pending' == $order->get_status() ) {
						$order->update_status( 'failed' );
					}

					do_action( 'pafw_payment_fail', $order, $e->getCode(), $e->getMessage() );
				}

				if ( $redirect ) {
					PAFW_Gateway::redirect( $order, $this, $message, false );
				} else {
					return $message;
				}
			} catch ( Exception $e ) {

			}
		}
		function add_payment_log( $order, $title, $messages = array(), $success = true ) {
			if ( $order ) {
				$logs = array();

				$logs[] = sprintf( '<span class="pafw-log-title %s">%s</span>', $success ? 'success' : 'fail', $title );
				if ( is_array( $messages ) ) {
					foreach ( $messages as $label => $text ) {
						$logs[] = sprintf( '%s : %s', $label, $text );
					}
				} else {
					$logs[] = $messages;
				}

				$log = implode( '<br>', $logs );

				$order->add_order_note( $log );
			}
		}
		function get_cash_receipts( $order ) {
			if ( $this->supports( 'pafw-cash-receipt' ) ) {
				$cash_receipts = $order->get_meta( '_pafw_cash_receipts' );

				return '' == trim( $cash_receipts ) ? '미발행' : '발행';
			}

			return '';
		}
		public function get_order_items( $order ) {
			$order_items = array();

			if ( is_a( $order, 'WC_Abstract_Order' ) ) {
				foreach ( apply_filters( 'pafw_get_order_items', $order->get_items(), $order ) as $item_id => $item ) {
					$order_items[] = array(
						'item_id'    => $item_id,
						'product_id' => $item->get_product_id(),
						'amount'     => round( ( floatval( $item->get_total() ) + floatval( $item->get_total_tax() ) ) / $item->get_quantity(), wc_get_price_decimals() ),
						'quantity'   => $item->get_quantity(),
						'name'       => pafw_remove_emoji( wp_strip_all_tags( $item->get_name() ) ),
					);
				}
			}

			return apply_filters( 'pafw_gateway_order_items', $order_items );
		}
		function payment_complete( $order, $tid ) {
			if ( ! pafw_is_subscription( $order ) && is_a( $order, 'WC_Abstract_Order' ) && ! $this->is_vbank( $order ) ) {

				$order->payment_complete( $tid );

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
			}
		}

		static function enqueue_frontend_script() {
		}
		function get_title() {
			$title = parent::get_title();

			if ( ! is_admin() || empty( $_REQUEST['page'] ) || ! in_array( wc_clean( $_REQUEST['page'] ), array( 'wc-settings', 'mshop_payment' ) ) ) {
				if ( $this->is_test_key() ) {
					$title = __( '[테스트] ', 'pgall-for-woocommerce' ) . $title;
				}
			}

			return $title;
		}
		function get_description() {
			$description = parent::get_description();

			if ( is_add_payment_method_page() && ! $this->supports( 'pafw_key_in_payment' ) ) {
				$description = '';
			} else if ( ! is_admin() || empty( $_REQUEST['page'] ) || ! in_array( wc_clean( $_REQUEST['page'] ), array( 'wc-settings', 'mshop_payment' ) ) ) {
				if ( $this->is_test_key() ) {
					if ( $this->is_vbank() ) {
						$description = __( '<span style="font-size: 0.9em; color: red;">[ 가상계좌 입금 테스트 시 환불 처리가 어렵습니다. 실제 입금 테스트는 결제대행사에 문의해주세요. ]</span><br>', 'pgall-for-woocommerce' ) . $description;
					} else {
						$description = __( '<span style="font-size: 0.9em; color: red;">[ 실제 과금이 되지 않거나 자정에 자동으로 취소가 됩니다. ]</span><br>', 'pgall-for-woocommerce' ) . $description;
					}
				}
			}

			return $description;
		}
		function get_receipt_popup_params() {
			return array();
		}

		function get_merchant_id() {
			return '';
		}

		public function get_subscription_meta_key( $meta_key ) {
			return '_pafw_' . $meta_key;
		}
		public function get_card_field_name( $field_name ) {
			return 'pafw_' . $this->get_master_id() . '_' . $field_name;
		}
		public function get_card_param( $post_data, $key, $default = '' ) {
			$value = pafw_get( $post_data, $this->get_card_field_name( $key ), $default );

			if ( 'expiry_month' == $key ) {
				$value = sprintf( "%02d", intval( $value ) );
			} else if ( 'expiry_year' == $key ) {
				$value = sprintf( "20%02d", intval( $value ) );
			}

			return preg_replace( '/\s+/', '', $value );
		}
		public function subscription_additional_charge( $order_id = null, $amount = null, $card_quota = null, $return = false ) {
			try {
				check_ajax_referer( 'pgall-for-woocommerce' );

				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
				}

				$this->process_additional_charge( wc_clean( $_REQUEST['order_id'] ), wc_clean( $_REQUEST['amount'] ), pafw_get( $_REQUEST, 'card_quota', '00' ) );

				wp_send_json_success( '추가 과금 요청이 정상적으로 처리되었습니다.' );
			} catch ( Exception $e ) {
				wp_send_json_error( sprintf( __( '[ 추가과금실패 ][PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		public function process_additional_charge( $order_id, $amount, $card_quota ) {
			define( 'PAFW_ADDITIONAL_CHARGE', true );

			$order = wc_get_order( $order_id );

			if ( $order ) {
				$params = array(
					'is_renewal'           => function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ),
					'card_quota'           => $card_quota,
					'is_additional_charge' => true,
					'amount_to_charge'     => $amount
				);

				$response = PAFW_Gateway::request_subscription_payment( $order, $this, $params );
				$history = $order->get_meta( '_pafw_additional_charge_history' );
				if ( empty( $history ) ) {
					$history = array();
				}

				$history[ $response["transaction_id"] ] = array(
					'status'         => 'PAYED',
					'auth_date'      => $response['paid_date'],
					'charged_amount' => $amount
				);;

				$order->update_meta_data( '_pafw_additional_charge_history', $history );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 추가 과금 성공 ]', array(
					'거래요청번호' => $response["transaction_id"],
					'추가과금금액' => wc_price( $amount, array( 'currency' => $order->get_currency() ) ),
					'할부개월수'  => '00' == $card_quota ? __( '일시불', 'pgall-for-woocommerce' ) : sprintf( '%d개월', intval( $card_quota ) )
				) );
			} else {
				throw new Exception( __( '주문 정보를 찾을 수 없습니다.', 'pgall-for-woocommerce' ), '5002' );
			}
		}
		public function change_payment_method_for_subscription( $subscription, $params ) {
//			/** 결제수단 변경 중 빌링키가 삭제되지 않도록 filter detach   */
//			remove_action( 'woocommerce_subscription_status_cancelled', array( $this, 'cancel_subscription' ) );
//			remove_action( 'woocommerce_subscription_cancelled_' . $this->id, array( $this, 'cancel_subscription' ) );
//
//			/** 동일한 결제 수단으로 변경하는 경우, 기존에 발급된 빌링키는 취소한다. */
//			if ( $this->id == $subscription->get_payment_method() && $this->cancel_bill_key_when_change_to_same_payment_method() ) {
//				$bill_key = $subscription->get_meta( $this->get_subscription_meta_key( 'bill_key' ) );
//
//				if ( ! empty( $bill_key ) ) {
//					$this->cancel_bill_key( $bill_key );
//					$this->clear_bill_key( $subscription, $subscription->get_customer_id() );
//					$this->add_payment_log( $subscription, '[ 정기결제 빌링키 삭제 성공 ]' );
//				}
//			}
//
//			if ( $this->issue_bill_key_when_change_payment_method() ) {
//				$bill_key = $this->get_bill_key( $subscription );
//
//				if ( ! empty( $params ) && ( 'yes' == pafw_get( $params, $this->get_master_id() . '_issue_bill_key' ) || empty( $bill_key ) ) ) {
//					PAFW_Gateway::issue_bill_key( $subscription, $this );
//				}
//			}
//
//			WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $subscription, $this->id );
//
//			$this->add_payment_log( $subscription, '[ 결제 수단 변경 ]', array(
//				'결제수단' => $this->title
//			) );

			return true;
		}

		function woocommerce_scheduled_subscription_payment( $amount_to_charge, $order ) {
			try {
				$gateway = $this;
				$token   = PAFW_Token::get_token_for_order( $order );

				if ( $token->get_gateway_id() != $gateway->id ) {
					if ( apply_filters( 'pafw_use_customer_default_payment_token', true ) ) {
						$gateway = pafw_get_payment_gateway( $token->get_gateway_id() );

						if ( is_null( $gateway ) ) {
							throw new Exception( __( "사용가능한 결제 방법이 없습니다. 내계정 - 결제방법 페이지에서 결제수단을 등록해주세요.", "pgall-for-woocommerce" ), 7101 );
						}

						PAFW_Token::update_token( $order, $token );

						$order = wc_get_order( $order );
					} else {
						throw new Exception( __( "사용가능한 결제 방법이 없습니다. 내계정 - 결제방법 페이지에서 결제수단을 등록해주세요.", "pgall-for-woocommerce" ), 7102 );
					}
				}

				PAFW_Gateway::request_subscription_payment( $order, $gateway, array( 'is_renewal' => true, 'amount_to_charge' => $amount_to_charge ), $token );
			} catch ( Exception $e ) {
				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$order->update_status( 'failed', $message );
			}
		}
		function cancel_subscription( $subscription ) {
			if ( 'subscription' == pafw_get( $this->settings, 'management_batch_key', 'subscription' ) && $subscription && $subscription->get_payment_method() == $this->id ) {
				try {
					$other_subscription_ids = array();

					$order_ids     = $subscription->get_related_orders( 'ids', array( 'parent' ) );
					$subscriptions = wcs_get_subscriptions_for_order( reset( $order_ids ), array( 'order_type' => 'parent', 'subscription_status' => 'active' ) );

					if ( ! empty( $subscriptions ) ) {
						$other_subscription_ids = array_diff( array_keys( $subscriptions ), array( $subscription->get_id() ) );
					}

					if ( empty( $other_subscription_ids ) ) {
						$bill_key = $subscription->get_meta( $this->get_subscription_meta_key( 'bill_key' ) );

						if ( ! empty( $bill_key ) ) {
							$this->cancel_bill_key( $bill_key );

							$this->clear_bill_key( $subscription, $subscription->get_customer_id() );

							$this->add_payment_log( $subscription, '[ 빌링키 삭제 ]', $this->get_title() );
						}
					}
				} catch ( Exception $e ) {
				}
			}
		}
		public function clear_bill_key( $subscription, $user_id ) {
			if ( $subscription ) {
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'auth_date' ) );
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'bill_key' ) );
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'card_code' ) );
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'card_name' ) );
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'auth_date' ) );
				$subscription->delete_meta_data( $this->get_subscription_meta_key( 'card_num' ) );
				$subscription->delete_meta_data( '_pafw_is_bill_key_for_order' );
				$subscription->save_meta_data();
			}
			if ( $user_id > 0 ) {
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'bill_key' ) );
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'card_num' ) );
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'card_code' ) );
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'card_name' ) );
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'payment_method_type' ) );
				delete_user_meta( $user_id, $this->get_subscription_meta_key( 'register_date' ) );
			}
		}
		public function subscription_cancel_batch_key() {
			if ( ! current_user_can( 'publish_shop_orders' ) ) {
				throw new Exception( __( '[ERR-0000003] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( empty( $_POST['subscription_id'] ) || empty( $_REQUEST['batch_key'] ) ) {
				throw new Exception( __( '[ERR-0000001] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
			}

			$subscription = wcs_get_subscription( absint( wp_unslash( $_POST['subscription_id'] ) ) );
			if ( empty( $subscription ) || $this->id !== $subscription->get_payment_method() ) {
				throw new Exception( __( '[ERR-0000002] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
			}

			$this->cancel_bill_key( wc_clean( $_REQUEST['batch_key'] ) );

			$this->add_payment_log( $subscription, '[ 정기결제 빌링키 삭제 성공 ]' );

			wp_send_json_success( __( '정기결제 배치키 비활성화가 정상적으로 처리되었습니다.' ) );
		}
		function get_payment_form_params_by_gateway( $order ) {
		}

		function get_gateway_params() {
			return array(
				'mall_name'    => get_option( 'blogname' ),
				'merchant_id'  => $this->get_merchant_id(),
				'merchant_key' => $this->get_merchant_key(),
				'return_url'   => $this->get_api_url( 'payment' )
			);
		}
		public function cancel_bill_key( $bill_key ) {
			if ( $this->supports( 'pafw_cancel_bill_key' ) ) {
				PAFW_Gateway::cancel_bill_key( $bill_key, $this );
			}

			return true;
		}
		public function subscription_cancel_additional_charge() {
			check_ajax_referer( 'pgall-for-woocommerce' );

			if ( ! current_user_can( 'publish_shop_orders' ) ) {
				throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
			}

			$order = wc_get_order( absint( wp_unslash( $_REQUEST['order_id'] ) ) );

			PAFW_Gateway::cancel_additional_charge( $order, $this );

			wp_send_json_success( '추가 과금 취소 요청이 정상적으로 처리되었습니다.' );
		}
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			return PAFW_Gateway::process_refund( $order_id, $amount, $reason, $this );
		}

		function cancel_payment_request_by_user() {
			do_action( 'pafw_payment_cancel' );
			wp_send_json_success();
		}

		public function get_logo_url() {
			return plugins_url( '/assets/gateways/' . $this->get_master_id() . '/images/logo.png', PAFW_PLUGIN_FILE );
		}

		public function subscription_payment_info() {
			$bill_key = get_user_meta( get_current_user_id(), $this->get_subscription_meta_key( 'bill_key' ), true );

			ob_start();

			wc_get_template( 'pafw/card-info.php', array( 'payment_gateway' => $this, 'bill_key' => $bill_key ), '', PAFW()->template_path() );

			return ob_get_clean();
		}

		function delete_payment_method() {
			try {
				$bill_key = get_user_meta( get_current_user_id(), $this->get_subscription_meta_key( 'bill_key' ), true );

				if ( ! empty( $bill_key ) ) {
					$this->cancel_bill_key( $bill_key );
				}

				$this->clear_bill_key( null, get_current_user_id() );

				wp_send_json_success();
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}
		function escrow_register_delivery_info() {
			$this->check_shop_order_capability();

			if ( empty( $_REQUEST['escrow_type'] ) || empty( $_REQUEST['tracking_number'] ) ) {
				throw new Exception( __( '필수 파라미터가 누락되었습니다.', 'pgall-for-woocommerce' ) );
			}

			$order = $this->get_order();

			PAFW_Gateway::register_shipping( $order, $this );

			$order->update_status( pafw_get( $this->settings, 'order_status_after_enter_shipping_number' ) );

			wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
		}
		function issue_cash_receipt( $order_id ) {
			try {
				$order = $this->get_order( $order_id );

				if ( empty( $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ) || empty( $order->get_meta( '_pafw_bacs_receipt_usage' ) ) ) {
					throw new Exception( __( '현금영수증 발행에 필요한 정보가 없습니다.', 'pgall-for-woocommerce' ) );
				}

				$response = PAFW_Gateway::issue_cash_receipt( $order, $this );

				$order->update_meta_data( '_pafw_bacs_receipt_tid', pafw_get( $response, 'transaction_id' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_issue_date', pafw_get( $response, 'issue_date' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_issue_usage', pafw_get( $response, 'issue_type' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_receipt_number', pafw_get( $response, 'receipt_number' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_total_price', pafw_get( $response, 'total_price' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_tax_amount', pafw_get( $response, 'tax_amount' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_tax_free_amount', pafw_get( $response, 'tax_free_amount' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_vat', pafw_get( $response, 'vat' ) );
				$order->update_meta_data( '_pafw_bacs_receipt_via', $this->get_pg_title() );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 현금영수증 발행 ]', array(
					'TID'      => pafw_get( $response, 'transaction_id' ),
					'현금영수증 번호' => pafw_get( $response, 'receipt_number' ),
					'결제금액'     => wc_price( pafw_get( $response, 'total_price' ), array( 'currency' => $order->get_currency() ) )
				) );

				PAFW_Cash_Receipt::update_receipt_request( $order_id, array(
					'status'         => PAFW_Cash_Receipt::STATUS_ISSUED,
					'receipt_number' => pafw_get( $response, 'receipt_number' ),
					'message'        => __( '현금영수증이 발행되었습니다.', 'pgall-for-woocommerce' ),
				) );

				return array_merge( array( 'result' => 'success' ), $response );
			} catch ( Exception $e ) {

				PAFW_Cash_Receipt::update_receipt_request( $order_id, array(
					'status'  => PAFW_Cash_Receipt::STATUS_FAILED,
					'message' => $e->getMessage(),
				) );

				throw $e;
			}
		}
		function cancel_cash_receipt( $order_id ) {
			try {
				$order = $this->get_order( $order_id );

				if ( ! empty( $order->get_meta( '_pafw_bacs_receipt_tid' ) ) ) {
					PAFW_Gateway::cancel_cash_receipt( $order, __( '현금영수증 발행취소', 'pgall-for-woocommerce' ), $this );

					$this->add_payment_log( $order, '[ 현금영수증 발행취소 ]', array(
						'TID' => $order->get_meta( '_pafw_bacs_receipt_tid' ),
					), false );

					$order->delete_meta_data( '_pafw_bacs_receipt_tid' );
					$order->delete_meta_data( '_pafw_bacs_receipt_issue_date' );
					$order->delete_meta_data( '_pafw_bacs_receipt_issue_usage' );
					$order->delete_meta_data( '_pafw_bacs_receipt_receipt_number' );
					$order->delete_meta_data( '_pafw_bacs_receipt_total_price' );
					$order->delete_meta_data( '_pafw_bacs_receipt_tax_amount' );
					$order->delete_meta_data( '_pafw_bacs_receipt_tax_free_amount' );
					$order->delete_meta_data( '_pafw_bacs_receipt_vat' );
					$order->delete_meta_data( '_pafw_bacs_receipt_via' );
					$order->save_meta_data();
				}

				PAFW_Cash_Receipt::update_receipt_request( $order_id, array(
					'status'  => PAFW_Cash_Receipt::STATUS_CANCELLED,
					'message' => __( '현금영수증이 발급이 취소되었습니다.', 'pgall-for-woocommerce' )
				) );
			} catch ( Exception $e ) {

				PAFW_Cash_Receipt::update_receipt_request( $order_id, array(
					'status'  => PAFW_Cash_Receipt::STATUS_FAILED,
					'message' => $e->getMessage(),
				) );

				throw $e;
			}
		}
		function process_key_in_subscription_payment( $order_id ) {
			$token = null;
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			try {
				if ( isset( $_POST['issavedtoken'] ) && $_POST['issavedtoken'] && isset( $_POST['token'] ) && intval( $_POST['token'] ) > 0 ) {
					$token = new WC_Payment_Token_PAFW( $_POST['token'] );
					$quota = pafw_get( $_POST, 'pafw_token_card_quota_' . $_POST['token'], '00' );
				} else {
					$quota = pafw_get( $_REQUEST, 'pafw_' . $this->get_master_id() . '_card_quota', '00' );
				}

				if ( is_null( $token ) ) {
					$token = PAFW_Gateway::issue_bill_key( $order, $this );
				} else {
					PAFW_Token::update_token( $order, $token );
				}

				if ( pafw_is_subscription( $order ) ) {
					pafw_maybe_set_payment_token( $order, $token );

					return array(
						'result'       => 'success',
						'redirect'     => $order->get_view_order_url(),
						'redirect_url' => $order->get_view_order_url()
					);
				} else {
					$order->set_payment_method( $this );

					if ( $order->get_total() > 0 ) {
						PAFW_Gateway::request_subscription_payment( $order, $this, array( 'card_quota' => $quota ), $token );
					} else {
						$order->payment_complete();
					}

					return array(
						'result'       => 'success',
						'redirect_url' => $order->get_checkout_order_received_url()
					);
				}
			} catch ( Exception $e ) {
				$message = sprintf( "[결제오류] %s [%s]", $e->getMessage(), $e->getCode() );

				$order->add_order_note( $message );

				do_action( 'pafw_payment_fail', $order, $e->getCode(), $e->getMessage() );

				throw $e;
			}
		}
		function process_auth_subscription_payment( $order_id ) {
			$token = null;
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			try {
				if ( isset( $_POST['issavedtoken'] ) && $_POST['issavedtoken'] && isset( $_POST['token'] ) && intval( $_POST['token'] ) > 0 ) {
					$token = new WC_Payment_Token_PAFW( $_POST['token'] );
					$quota = pafw_get( $_POST, 'pafw_token_card_quota_' . $_POST['token'], '00' );
				} else {
					$quota = pafw_get( $_REQUEST, 'pafw_' . $this->get_master_id() . '_card_quota', '00' );
				}

				if ( is_null( $token ) ) {
					$order->update_meta_data( '_pafw_card_quota', $quota );
					$order->save_meta_data();

					do_action( 'pafw_process_payment', $order );

					return $this->get_payment_form( $order_id, $order->get_order_key() );
				} else if ( pafw_is_subscription( $order ) ) {
					pafw_maybe_set_payment_token( $order, $token );

					return array(
						'result'       => 'success',
						'redirect'     => $order->get_view_order_url(),
						'redirect_url' => $order->get_view_order_url()
					);
				} else {
					$order->set_payment_method( $this );

					if ( $order->get_total() > 0 ) {
						PAFW_Gateway::request_subscription_payment( $order, $this, array( 'card_quota' => $quota ), $token );
					} else {
						$order->payment_complete();
					}

					PAFW_Token::update_token( $order, $token );

					return array(
						'result'       => 'success',
						'redirect'     => $order->get_checkout_order_received_url(),
						'redirect_url' => $order->get_checkout_order_received_url()
					);
				}
			} catch ( Exception $e ) {
				$message = sprintf( "[결제오류] %s [%s]", $e->getMessage(), $e->getCode() );

				$order->add_order_note( $message );

				do_action( 'pafw_payment_fail', $order, $e->getCode(), $e->getMessage() );

				throw $e;
			}
		}
		public function quota_field() {
			ob_start();
			wc_get_template( 'quota/payment-method.php', array( 'payment_gateway' => $this ), '', PAFW()->template_path() );
			ob_end_flush();
		}
	}
}