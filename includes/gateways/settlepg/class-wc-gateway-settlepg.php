<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_SettlePG extends PAFW_Payment_Gateway {

		protected $target_recurrent_id = null;

		protected $product_code = null;

		public function __construct() {
			$this->master_id = 'settlepg';

			parent::__construct();

			$this->pg_title     = __( '헥토파이낸셜 전자결제(PG)', 'pgall-for-woocommerce' );
			$this->method_title = __( '헥토파이낸셜 전자결제(PG)', 'pgall-for-woocommerce' );

			if ( 'yes' == $this->enabled ) {
				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_filter( 'pafw_approval_params_' . $this->id, array( $this, 'add_approval_request_params' ), 10, 2 );
				add_filter( 'pafw_cancel_params_' . $this->id, array( $this, 'add_cancel_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
				add_action( 'pafw_' . $this->id . '_complete', array( $this, 'wc_api_payment_complete' ) );
			}
		}

		function payment_window_mode() {
			if ( wp_is_mobile() ) {
				return 'page';
			} else {
				return 'popup';
			}
		}
		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
		public function get_merchant_key() {
			return pafw_get( $this->settings, 'merchant_key' );
		}
		public function add_register_order_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'license_key'        => pafw_get( $this->settings, 'license_key' ),
				'account_date_limit' => pafw_get( $this->settings, 'account_date_limit', 3 ),
			);

			return $params;
		}
		public function add_cancel_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'license_key' => pafw_get( $this->settings, 'license_key' ),
				'vacc_num'    => $order->get_meta( '_pafw_vacc_num' )
			);

			return $params;
		}
		public function add_approval_request_params( $params, $order ) {
			$args = array();

			foreach ( $_POST as $key => $value ) {
				$args[ $key ] = pafw_convert_to_utf8( $value );
			}

			$params[ $this->get_master_id() ] = $args;

			return $params;
		}
		function wc_api_request_payment() {
			try {
				$order = null;


				if ( empty( $_GET['order_id'] ) || empty( $_POST['outStatCd'] ) || ! in_array( $_POST['outStatCd'], array( '0021', '0051' ) ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9100' );
				}

				$order = $this->get_order( $_GET['order_id'] );

				if ( in_array( $_POST['bizType'], array( 'B1', 'B0', 'A0' ) ) ) {
					$this->validate_order_status( $order );

					PAFW_Gateway::process_approval( $this, $order );
				} else if ( in_array( $_POST['bizType'], array( 'F1', 'B1' ) ) ) {
					if ( is_callable( array( $this, 'process_vbank_noti' ) ) ) {
						$this->process_vbank_noti();
					} else {
						throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9200' );
					}
				} else {
					$order->add_order_note( sprintf( '노티수신 : %s', $_POST['bizType'] ) );
					$order->add_order_note( json_encode( $_POST ) );
				}

				echo 'OK';
				die();
			} catch ( Exception $e ) {
				$this->handle_exception( $e, $order, false );
				echo 'FAIL';
				die();
			}
		}
		function wc_api_payment_complete() {
			try {
				$order = null;

				if ( empty( $_GET['order_id'] ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9000' );
				}

				do_action( 'pafw_gateway_before_api_request' );

				$order = $this->get_order( $_GET['order_id'] );

				PAFW_Gateway::redirect( $order, $this );
			} catch ( Exception $e ) {
				$this->handle_exception( $e, $order );
			}
		}
		public function get_subscription_meta_key( $meta_key ) {
			return '_pafw_settlepg_' . $meta_key;
		}

		function issue_bill_key_when_change_payment_method() {
			return false;
		}
	}
}