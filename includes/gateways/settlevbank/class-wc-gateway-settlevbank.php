<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_Settlevbank extends PAFW_Payment_Gateway {

		protected $target_recurrent_id = null;

		protected $product_code = null;

		public function __construct() {
			$this->master_id = 'settlevbank';

			parent::__construct();

			$this->pg_title     = __( '헥토파이낸셜 010가상계좌', 'pgall-for-woocommerce' );
			$this->method_title = __( '헥토파이낸셜 010가상계좌', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_filter( 'pafw_cancel_params_' . $this->id, array( $this, 'add_cancel_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
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
				'account_date_limit' => pafw_get( $this->settings, 'account_date_limit', 72 )
			);

			return $params;
		}
		public function add_cancel_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'user_id'     => $order->get_customer_id(),
				'user_phone'  => $order->get_meta( '_pafw_vacc_num' ),
				'license_key' => pafw_get( $this->settings, 'license_key' )
			);

			return $params;
		}
		public function process_approval_response( $order, $response ) {
			$order->update_meta_data( '_pafw_vacc_tid', $response['vacc_tid'] );
			$order->update_meta_data( '_pafw_vacc_num', $response['vacc_num'] );
			$order->update_meta_data( '_pafw_vacc_bank_code', $response['vacc_bank_code'] );
			$order->update_meta_data( '_pafw_vacc_bank_name', $response['vacc_bank_name'] );
			$order->update_meta_data( '_pafw_vacc_date', $response['vacc_date'] );
			$order->save_meta_data();

			$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]' );

			//가상계좌 주문 접수시 재고 차감여부 확인
			pafw_reduce_order_stock( $order );

			$order->update_status( $this->settings['order_status_after_vbank_payment'] );

			$order->set_date_paid( null );
			$order->save();
		}
		public function wc_api_vbank_noti() {
			$this->add_log( '010가상계좌 입금통보 시작 : ' . print_r( wc_clean( $_REQUEST ), true ) );

			try {
				if ( $this->get_merchant_id() != pafw_get( $_POST, 'mchtId' ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2001' );
				}

				if ( '0021' != pafw_get( $_POST, 'outStatCd' ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), pafw_get( $_POST, 'outStatCd' ) );
				}

				$order_id = $this->get_order_id_from_txnid( pafw_get( $_POST, 'mchtTrdNo' ) );
				$order    = wc_get_order( $order_id );

				if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2002' );
				}

				if ( 'on-hold' != $order->get_status() ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2003' );
				}

				if ( $order->get_total() != pafw_get( $_POST, 'trdAmt' ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2005' );
				}

				$transaction_id = pafw_get( $_POST, 'trdNo' );
				if ( empty( $transaction_id ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2006' );
				}

				$hash_data = array(
					$_POST['outStatCd'],
					$_POST['trdDtm'],
					$this->get_merchant_id(),
					$this->get_txnid( $order ),
					$order->get_total(),
					pafw_get( $this->settings, 'license_key' )
				);

				if ( $_POST['pktHash'] != hash( 'sha256', implode( '', $hash_data ) ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '3001' );
				}

				$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
				$order->update_meta_data( '_pafw_vbank_noti_transaction_date', pafw_get( $_POST, 'trdDtm' ) );
				$order->update_meta_data( '_pafw_vbank_noti_deposit_bank', pafw_get( $_POST, 'bankNm' ) );
				$order->update_meta_data( '_pafw_vbank_noti_depositor', pafw_get( $_POST, 'dpstrNm' ) );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 가상계좌 입금 완료 ]', array(
					'거래번호' => $transaction_id
				) );

				$order->payment_complete( $transaction_id );

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

				if ( pafw_order_need_shipping( $order ) ) {
					$order->update_status( $this->settings['order_status_after_payment'] );
				}

				$order->set_date_paid( current_time( 'timestamp', true ) );
				$order->save();

				echo 'OK';
			} catch ( Exception $e ) {
				$this->add_log( sprintf( __( '010가상계좌 입금통보 처리 오류 : [PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
			die();
		}
	}
}