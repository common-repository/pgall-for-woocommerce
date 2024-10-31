<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis_StdVbank' ) ) {
	return;
}

class WC_Gateway_Inicis_StdVbank extends WC_Gateway_Inicis {
	public function __construct() {
		$this->id = 'inicis_stdvbank';

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
		$this->supports[] = 'pafw-vbank-refund';
		$this->supports[] = 'pafw-vbank-cancel';

		add_filter( 'pafw_vbank_refund_params_' . $this->id, array( $this, 'add_vbank_refund_params' ), 10, 2 );

		add_action( 'pafw_' . $this->id . '_mobile_noti', array( $this, 'wc_api_vbank_mobile_noti' ) );
	}
	public function add_vbank_refund_params( $params, $order ) {
		$params['inicis'] = array(
			'refund_acc_num'   => pafw_get( $_REQUEST, 'refund_acc_num' ),
			'refund_bank_code' => pafw_get( $_REQUEST, 'refund_bank_code' ),
			'refund_acc_name'  => pafw_get( $_REQUEST, 'refund_acc_name' ),
			'refund_reason'    => pafw_get( $_REQUEST, 'refund_reason' ),
		);

		return $params;
	}
	public function get_accept_methods() {
		$accept_methods = parent::get_accept_methods();

		if ( wp_is_mobile() ) {
			if ( 'yes' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'vbank_receipt=Y';
			}
		} else {
			if ( 'yes' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'va_receipt';
			}
			$vbank_limit      = pafw_get( $this->settings, 'account_date_limit', 3 );
			$accept_methods[] = sprintf( 'vbank(%s)', date( 'Ymd2359', strtotime( "+{$vbank_limit} days" ) ) );
		}

		return $accept_methods;
	}

	function get_vbank_list() {
		return array(
			"02" => "산업(02)",
			"03" => "기업(03)",
			"04" => "국민(04)",
			"05" => "외환(05)",
			"06" => "국민(주택)(06)",
			"07" => "수협(07)",
			"11" => "농협(11)",
			"12" => "농협(12)",
			"16" => "농협(축협)(16)",
			"20" => "우리(20)",
			"21" => "조흥(21)",
			"23" => "제일(23)",
			"25" => "서울(25)",
			"26" => "신한(26)",
			"27" => "한미(27)",
			"31" => "대구(31)",
			"32" => "부산(32)",
			"34" => "광주(34)",
			"35" => "제주(35)",
			"37" => "전북(37)",
			"38" => "강원(38)",
			"39" => "경남(39)",
			"41" => "비씨(41)",
			"45" => "새마을(45)",
			"48" => "신협(48)",
			"50" => "상호저축은행(50)",
			"53" => "씨티(53)",
			"54" => "홍콩상하이은행(54)",
			"55" => "도이치(55)",
			"56" => "ABN암로(56)",
			"70" => "신안상호(70)",
			"71" => "우체국(71)",
			"81" => "하나(81)",
			"87" => "신세계(87)",
			"88" => "신한(88)",
			"89" => "케이뱅크(89)",
			"90" => "카카오뱅크(90)",
			"92" => "토스뱅크(88)",
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
	function vbank_refund_request() {
		$this->check_shop_order_capability();

		PAFW_Gateway::vbank_refund( $this->get_order(), $this );

	}
	function wc_api_vbank_mobile_noti() {
		try {
			$order = null;
			$remote_ip = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );

			$remote_ips = array_map( 'trim', explode( ',', $remote_ip ) );

			if ( empty( array_intersect( $remote_ips, array( "118.129.210.25", "183.109.71.153", "203.238.37.15" ) ) ) ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-9500] 잘못된 아이피로 접근하였습니다. IP : %s', 'pgall-for-woocommerce' ), $remote_ip ) );
			}

			$P_TID     = pafw_get( $_REQUEST, 'P_TID' );
			$P_AUTH_DT = pafw_get( $_REQUEST, 'P_AUTH_DT' );
			$P_STATUS  = pafw_get( $_REQUEST, 'P_STATUS' );
			$P_OID     = pafw_get( $_REQUEST, 'P_OID' );
			$P_FN_CD1  = pafw_get( $_REQUEST, 'P_FN_CD1' );
			$P_AMT     = pafw_get( $_REQUEST, 'P_AMT' );

			$this->add_log( '[모바일] 모바일 가상계좌 입금통보 시작 : ' . $P_TID );

			if ( '02' == $P_STATUS ) {
				$oids  = explode( '_', $P_OID );
				$order = wc_get_order( $oids[0] );

				if ( ! $order ) {
					throw new Exception( __( '[PAFW-ERR-9501] 올바르지 않은 주문번호입니다.', 'pgall-for-woocommerce' ) );
				}

				//$P_RMESG1 에서 입금계좌 및 입금예정일 확인
				$vacc_info = array();
				$params    = explode( '|', pafw_get( $_REQUEST, 'P_RMESG1' ) );
				foreach ( $params as $param ) {
					$args                  = explode( '=', $param );
					$vacc_info[ $args[0] ] = $args[1];
				}

				$txnid          = $order->get_meta( '_pafw_txnid' );
				$vacc_num       = $order->get_meta( '_pafw_vacc_num' );
				$vacc_bank_code = $order->get_meta( '_pafw_vacc_bank_code' );

				if ( ! in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded' ) ) ) {
					if ( $txnid != $P_OID ) {
						throw new Exception( __( '[PAFW-ERR-9502] 거래번호 미일치.', 'pgall-for-woocommerce' ) );
					}
					if ( $vacc_bank_code != $P_FN_CD1 ) {    //입금은행 코드 체크
						throw new Exception( __( '[PAFW-ERR-9502] 입금은행 코드 미일치.', 'pgall-for-woocommerce' ) );
					}
					if ( $vacc_num != $vacc_info['P_VACCT_NO'] ) {    //입금계좌번호 체크
						throw new Exception( __( '[PAFW-ERR-9502] 입금계좌번호 미일치.', 'pgall-for-woocommerce' ) );
					}
					if ( (int) $P_AMT != (int) $order->get_total() ) {    //입금액 체크
						throw new Exception( __( '[PAFW-ERR-9502] 입금액 미일치.', 'pgall-for-woocommerce' ) );
					}

					$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
					$order->update_meta_data( '_pafw_vbank_noti_transaction_date', $P_AUTH_DT );

					$this->add_payment_log( $order, '[ 가상계좌 입금 완료 ]', array(
						'거래번호'   => $P_TID,
						'상점거래번호' => $P_OID
					) );


					$order->payment_complete( $order->get_meta( '_pafw_vacc_tid' ) );

					do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

					if ( pafw_order_need_shipping( $order ) ) {
						$order->update_status( $this->settings['order_status_after_payment'] );
					}

					$order->set_date_paid( current_time( 'timestamp', true ) );
					$order->save();

					echo 'OK';
					exit();
				} else {
					$order->add_order_note( sprintf( __( '[모바일] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $P_TID, $P_OID ) );
					$this->add_log( sprintf( __( '[모바일] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $P_TID, $P_OID ) );
					$this->add_log( print_r( wc_clean( $_REQUEST ), true ) );
					echo 'OK';
					exit();
				}
			} else {
				$this->add_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 결제 결과 이상 -  ' . $P_STATUS . "\n" . print_r( wc_clean( $_REQUEST ), true ) );
				echo "OK";
				exit();
			}
		} catch ( Exception $e ) {
			if ( $order ) {
				$order->add_order_note( $e->getMessage() );
			}

			$this->add_log( $e->getMessage() . print_r( wc_clean( $_REQUEST ), true ) );

			die( 'FAIL' );
		}
	}
	function wc_api_vbank_noti() {
		try {
			$order = null;

			$this->add_log( '가상계좌 입금통보 시작 : ' . print_r( wc_clean( $_REQUEST ), true ) );
			$remote_ip = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );

			$remote_ips = array_map( 'trim', explode( ',', $remote_ip ) );

			if ( empty( array_intersect( $remote_ips, array( "203.238.37.15", "39.115.212.9", "183.109.71.153" ) ) ) ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-9500] 잘못된 아이피로 접근하였습니다. IP : %s', 'pgall-for-woocommerce' ), $remote_ip ) );
			}

			$this->add_log( '가상계좌 입금통보 시작 : ' . $remote_ip );

			$no_tid       = sanitize_text_field( $_POST['no_tid'] );
			$no_oid       = sanitize_text_field( $_POST['no_oid'] );
			$cd_bank      = sanitize_text_field( $_POST['cd_bank'] );
			$dt_trans     = sanitize_text_field( $_POST['dt_trans'] );
			$tm_trans     = sanitize_text_field( $_POST['tm_trans'] );
			$no_vacct     = sanitize_text_field( $_POST['no_vacct'] );
			$amt_input    = sanitize_text_field( $_POST['amt_input'] );
			$nm_inputbank = pafw_convert_to_utf8( sanitize_text_field( $_POST['nm_inputbank'] ) );
			$nm_input     = pafw_convert_to_utf8( sanitize_text_field( $_POST['nm_input'] ) );

			//OID 에서 주문번호 확인
			$oids     = explode( '_', $no_oid );
			$order_id = $oids[0];

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( '[PAFW-ERR-9501] 올바르지 않은 주문번호입니다.', 'pgall-for-woocommerce' ) );
			}

			$txnid          = $order->get_meta( '_pafw_txnid' );
			$vacc_num       = $order->get_meta( '_pafw_vacc_num' );
			$vacc_bank_code = $order->get_meta( '_pafw_vacc_bank_code' );

			if ( ! in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded' ) ) ) {  //주문상태 확인
				if ( $txnid != $no_oid ) {
					throw new Exception( __( '[PAFW-ERR-9502] 거래번호 미일치.', 'pgall-for-woocommerce' ) );
				}
				if ( $cd_bank != $vacc_bank_code ) {
					throw new Exception( __( '[PAFW-ERR-9502] 입금은행 코드 미일치.', 'pgall-for-woocommerce' ) );
				}
				if ( $no_vacct != $vacc_num ) {
					throw new Exception( __( '[PAFW-ERR-9502] 입금계좌번호 미일치.', 'pgall-for-woocommerce' ) );
				}
				if ( (int) $amt_input != (int) $order->get_total() ) {
					throw new Exception( __( '[PAFW-ERR-9502] 입금액 미일치.', 'pgall-for-woocommerce' ) );
				}

				$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
				$order->update_meta_data( '_pafw_vbank_noti_transaction_date', $dt_trans . $tm_trans );
				$order->update_meta_data( '_pafw_vbank_noti_deposit_bank', $nm_inputbank );
				$order->update_meta_data( '_pafw_vbank_noti_depositor', $nm_input );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 가상계좌 입금 완료 ]', array(
					'거래번호'   => $no_tid,
					'상점거래번호' => $no_oid
				) );

				$order->payment_complete( $order->get_meta( '_pafw_vacc_tid' ) );

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

				if ( pafw_order_need_shipping( $order ) ) {
					$order->update_status( $this->settings['order_status_after_payment'] );
				}

				$order->set_date_paid( current_time( 'timestamp', true ) );
				$order->save();

				echo 'OK';
				exit();
			} else { //주문상태가 이상한 경우
				$order->add_order_note( sprintf( __( '입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
				$this->add_log( sprintf( __( '입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
				$this->add_log( print_r( wc_clean( $_REQUEST ), true ) );
				echo 'OK';
				exit();
			}
		} catch ( Exception $e ) {
			if ( $order ) {
				$order->add_order_note( $e->getMessage() );
			}

			$this->add_log( $e->getMessage() . print_r( wc_clean( $_REQUEST ), true ) );

			die( 'FAIL' );
		}
	}
}