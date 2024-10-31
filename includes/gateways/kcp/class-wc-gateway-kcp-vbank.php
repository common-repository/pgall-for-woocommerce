<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_VBank' ) ) :

	class WC_Gateway_Kcp_VBank extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_vbank';

			parent::__construct();

			$this->settings['bills_cmd'] = 'vcnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '가상계좌', 'pgall-for-woocommerce' );
				$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
			$this->supports[] = 'pafw-cash-receipt';
			$this->supports[] = 'pafw-vbank';

			add_action( 'pafw_' . $this->id . '_common_return', array( $this, 'wc_api_vbank_noti' ) );
		}

		public function get_vbank_list() {
			return array(
				"39" => "경남은행",
				"45" => "새마을금고",
				"35" => "제주은행",
				"34" => "광주은행",
				"07" => "수협",
				"81" => "하나은행",
				"04" => "국민은행",
				"88" => "신한은행",
				"27" => "한국씨티은행",
				"03" => "기업은행",
				"48" => "신협",
				"54" => "HSBC",
				"11" => "농협",
				"05" => "외환은행",
				"23" => "SC은행",
				"31" => "대구은행",
				"20" => "우리은행",
				"02" => "산업은행",
				"32" => "부산은행",
				"71" => "우체국",
				"37" => "전북은행",
				"64" => "산림조합"
			);
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
		public function wc_api_vbank_noti() {
			try {
				$site_cd  = wc_clean( $_POST ["site_cd"] );
				$tno      = wc_clean( $_POST ["tno"] );
				$order_no = wc_clean( $_POST ["order_no"] );

				$order = wc_get_order( $order_no );

				if ( $site_cd !== $this->get_merchant_id() ) {
					throw new Exception( __( '사이트 코드 불일치', 'pgall-for-woocommerce' ), '7000001' );
				} else if ( empty( $order ) || $tno != $this->get_transaction_id( $order ) ) {
					throw new Exception( sprintf( __( '주문 정보 오류 ( %s, %s, %s )', 'pgall-for-woocommerce' ), $order_no, $tno, $this->get_transaction_id( $order ) ), '7000002' );
				} else {
					$tx_cd = wc_clean( $_POST ["tx_cd"] );
					switch ( $tx_cd ) {
						case self::TX_VACC_DEPOSIT :
							$this->process_vbank_notification( $order );
							break;
						case self::TX_ESCROW_DELIVERY :
						case self::TX_ESCROW_CONFIRM :
						case self::TX_ESCROW_CANCEL_IMMEDIATELY :
						case self::TX_ESCROW_CANCEL :
						case self::TX_ESCROW_WITHHOLD_SETTLEMENT :
							$this->process_escrow_notification( $order );
							break;
						default:
							throw new Exception( __( '유효하지 않은 TX_CD', 'pgall-for-woocommerce' ) );
					}
				}
			} catch ( Exception $e ) {
				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( $message );
			}

			$this->send_common_return_response();
		}
		protected function process_escrow_notification( $order ) {
			$this->add_log( 'process_escrow_notification' );
			$tx_cd = wc_clean( $_POST ["tx_cd"] );
			$tx_tm = wc_clean( $_POST ["tx_tm"] );

			switch ( $tx_cd ) {
				case self::TX_ESCROW_DELIVERY :
					// TO-DO
					break;
				case self::TX_ESCROW_CONFIRM :
					if ( 'Y' == $_POST["st_cd"] ) {
						$order->update_status( 'completed' ); //주문처리완료 상태
						$order->update_meta_data( '_pafw_escrow_order_confirm', 'yes' );
						$order->update_meta_data( '_pafw_escrow_order_confirm_time', current_time( 'mysql' ) );
						$order->save_meta_data();

						$this->add_payment_log( $order, '[ 에스크로 구매확정 ]', array(
							'처리시각' => $tx_tm
						) );
					} else {
						$cancel_message = iconv( 'euc-kr', 'UTF-8', $_POST["can_msg"] );
						$order->update_status( 'cancel-request' );  //주문처리완료 상태로 변경
						$order->update_meta_data( '_pafw_escrow_order_confirm_reject', 'yes' );
						$order->update_meta_data( '_pafw_escrow_order_confirm_reject_time', current_time( 'mysql' ) );
						$order->update_meta_data( '_pafw_escrow_order_confirm_reject_message', $cancel_message );
						$order->save_meta_data();

						$this->add_payment_log( $order, '[ 에스크로 구매거절 ]', array(
							'처리시각' => $tx_tm,
							'취소사유' => $cancel_message
						), false );
					}
					break;
			}
		}
		protected function process_vbank_notification( $order ) {
			$this->add_log( 'process_vbank_notification' );

			$site_cd  = wc_clean( $_POST ["site_cd"] );                 // 사이트 코드
			$tno      = wc_clean( $_POST ["tno"] );                 // KCP 거래번호
			$order_no = wc_clean( $_POST ["order_no"] );                 // 주문번호
			$tx_cd    = wc_clean( $_POST ["tx_cd"] );                 // 업무처리 구분 코드
			$tx_tm    = wc_clean( $_POST ["tx_tm"] );                 // 업무처리 완료 시간
			$ipgm_name = wc_clean( $_POST["ipgm_name"] );                // 주문자명
			$remitter  = wc_clean( $_POST["remitter"] );                // 입금자명
			$ipgm_mnyx = wc_clean( $_POST["ipgm_mnyx"] );                // 입금 금액
			$bank_code = wc_clean( $_POST["bank_code"] );                // 은행코드
			$account   = wc_clean( $_POST["account"] );                // 가상계좌 입금계좌번호
			$op_cd     = wc_clean( $_POST["op_cd"] );                    // 처리구분 코드
			$noti_id   = wc_clean( $_POST["noti_id"] );                // 통보 아이디
			$cash_a_no = wc_clean( $_POST["cash_a_no"] );                // 현금영수증 승인번호
			$cash_a_dt = wc_clean( $_POST["cash_a_dt"] );                // 현금영수증 승인시간

			$wc_tno     = $this->get_transaction_id( $order );
			$wc_account = $order->get_meta( '_pafw_vacc_num' );

			if ( $account != $wc_account ) {
				throw new Exception( __( '입금 계좌정보 불일치', 'pgall-for-woocommerce' ), '7000004' );
			} else if ( 'on-hold' != $order->get_status() ) {
				throw new Exception( __( '유효하지 않은 주문상태', 'pgall-for-woocommerce' ), '7000004' );
			} else if ( floatval( $ipgm_mnyx ) != $order->get_total() ) {
				throw new Exception( sprintf( __( '입금금액 불일치 : %s, %s', 'pgall-for-woocommerce' ), $ipgm_mnyx, $order->get_total() ), '7000005' );
			} else {
				$vbank_list = $this->get_vbank_list();

				$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
				$order->update_meta_data( '_pafw_vbank_noti_transaction_date', $tx_tm );
				$order->update_meta_data( '_pafw_vbank_noti_deposit_bank', isset( $vbank_list[ $bank_code ] ) ? $vbank_list[ $bank_code ] : $bank_code );
				$order->update_meta_data( '_pafw_vbank_noti_depositor', $remitter );

				$message = '';
				if ( 'sandbox' === $this->settings['operation_mode'] ) {
					$message .= '[개발 환경 ] ';
				}

				$this->add_payment_log( $order, '[ 가상계좌 입금완료 ]', array(
					'입금시각'  => $tx_tm,
					'통보아이디' => $noti_id
				) );

				$order->payment_complete( $tno );

				if ( pafw_order_need_shipping( $order ) ) {
					$order->update_status( $this->settings['order_status_after_payment'] );
				}

				$order->set_date_paid( current_time( 'timestamp', true ) );
				$order->save();

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
			}
		}
	}

endif;