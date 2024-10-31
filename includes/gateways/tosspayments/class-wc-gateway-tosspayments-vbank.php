<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_TossPayments_Vbank' ) ) {

		class WC_Gateway_TossPayments_Vbank extends WC_Gateway_TossPayments {
			public function __construct() {
				$this->id = 'tosspayments_vbank';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '가상계좌 무통장입금', 'pgall-for-woocommerce' );
					$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->supports[] = 'pafw-cash-receipt';
				$this->supports[] = 'pafw-vbank';
			}
			public function process_approval_response( $order, $response ) {
				$order->update_meta_data( '_pafw_vacc_tid', $response['vacc_tid'] );
				$order->update_meta_data( '_pafw_vacc_num', $response['vacc_num'] );
				$order->update_meta_data( '_pafw_vacc_bank_code', $response['vacc_bank_code'] );
				$order->update_meta_data( '_pafw_vacc_bank_name', $response['vacc_bank_name'] );
				$order->update_meta_data( '_pafw_vacc_holder', $response['vacc_holder'] );
				$order->update_meta_data( '_pafw_vacc_depositor', $response['vacc_depositor'] );
				$order->update_meta_data( '_pafw_vacc_date', $response['vacc_date'] );
				$order->update_meta_data( '_pafw_cash_receipts', $response['vacc_tid'] );
				$order->update_meta_data( "_pafw_receipt_url", pafw_get( $response, 'receipt_url' ));
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array(
					'거래번호' => $response['vacc_tid']
				) );

				//가상계좌 주문 접수시 재고 차감여부 확인
				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				$order->set_date_paid( null );
				$order->save();
			}
			function wc_api_vbank_noti() {
				$REMOTE_IP  = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );
				$request_ip = ip2long( $REMOTE_IP );
				$this->add_log( '가상계좌 입금통보 시작 : ' . $REMOTE_IP );

				$json    = file_get_contents( 'php://input' );
				$payload = json_decode( $json, true );

				$this->add_log( print_r( $payload, true ) );

				try {
					if ( 'DONE' != pafw_get( $payload, 'status' ) ) {
						throw new Exception( __( '[PAFW-ERR-8900] 가상계좌 입금완료 통보가 아닙니다..', 'pgall-for-woocommerce' ) );
					}

					$order_id = $this->get_order_id_from_txnid( pafw_get( $payload, 'orderId' ) );

					$order = wc_get_order( $order_id );

					if ( ! is_a( $order, 'WC_Order' ) ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8001] 주문정보 없음 - %s', 'pgall-for-woocommerce' ), pafw_get( $payload, 'orderId' ) ) );
					}

					if ( $order->get_payment_method() != $this->id ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8002] 결제수단 불일치 - %s', 'pgall-for-woocommerce' ), $order->get_payment_method() ) );
					}

					if ( 'on-hold' != $order->get_status() ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8003] 주문상태 오류 - %s', 'pgall-for-woocommerce' ), $order->get_status() ) );
					}

					if ( pafw_get( $payload, 'secret' ) != $this->get_transaction_id( $order ) ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8004] SECRET 불일치 [%s] [%s]', 'pgall-for-woocommerce' ), wc_clean( pafw_get( $payload, 'secret' ) ), $this->get_transaction_id( $order ) ) );
					}

					if ( pafw_get( $payload, 'orderId' ) != $order->get_meta( '_pafw_txnid' ) ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8005] 거래번호 불일치 [%s] [%s]', 'pgall-for-woocommerce' ), wc_clean( pafw_get( $payload, 'orderId' ) ), $order->get_meta( '_pafw_txnid' ) ) );
					}

					$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
					$order->update_meta_data( '_pafw_vbank_noti_transaction_date', wc_clean( pafw_get( $payload, 'createdAt' ) ) );
					$order->save_meta_data();

					$order->add_order_note( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), pafw_get( $payload, 'secret' ), pafw_get( $payload, 'orderId' ) ) );
					$this->add_log( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), pafw_get( $payload, 'secret' ), pafw_get( $payload, 'orderId' ) ) );

					$order->payment_complete( wc_clean( pafw_get( $payload, 'secret' ) ) );

					if ( pafw_order_need_shipping( $order ) ) {
						$order->update_status( $this->settings['order_status_after_payment'] );
					}

					$order->set_date_paid( current_time( 'timestamp', true ) );
					$order->save();

					do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

					echo 'OK';
					exit();

				} catch ( Exception $e ) {
					if ( $order ) {
						$order->add_order_note( $e->getMessage() );
					}
					$this->add_log( $e->getMessage() );
					$this->add_log( print_r( wc_clean( $_REQUEST ), true ) );
					echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
					exit();
				}
			}

			function get_cash_receipts( $order ) {
				$cash_receipts = $order->get_meta( '_pafw_cash_receipts' );

				return empty( $cash_receipts ) ? '미발행' : '발행';
			}
		}
	}

} // class_exists function end