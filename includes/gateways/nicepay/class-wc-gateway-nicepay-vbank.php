<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Vbank' ) ) {

		class WC_Gateway_Nicepay_Vbank extends WC_Gateway_Nicepay {

			public function __construct() {
				$this->id         = 'nicepay_vbank';
				$this->has_fields = false;

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
				$this->supports[] = 'pafw-vbank-cancel';
			}

			public function is_refundable( $order, $screen = 'admin' ) {
				return parent::is_refundable( $order, $screen ) && 'yes' != $order->get_meta( '_pafw_vbank_noti_received' );
			}

			public function get_vbank_list() {
				return array(
					"001" => "한국은행(001)",
					"002" => "산업은행(002)",
					"003" => "기업은행(003)",
					"004" => "국민은행(004)",
					"005" => "외환은행(005)",
					"007" => "수협중앙회(007)",
					"008" => "수출입은행(008)",
					"011" => "농협중앙회(011)",
					"012" => "농협회원조합(012)",
					"020" => "우리은행(020)",
					"023" => "SC제일은행(023)",
					"027" => "한국씨티은행(027)",
					"031" => "대구은행(031)",
					"032" => "부산은행(032)",
					"034" => "광주은행(034)",
					"035" => "제주은행(035)",
					"037" => "전북은행(037)",
					"039" => "경남은행(039)",
					"045" => "새마을금고연합회(045)",
					"048" => "신협중앙회(048)",
					"050" => "상호저축은행(050)",
					"052" => "모건스탠리은행(052)",
					"054" => "HSBC은행(054)",
					"055" => "도이치은행(055)",
					"056" => "에이비엔암로은행(056)",
					"057" => "제이피모간체이스은행(057)",
					"058" => "미즈호코퍼레이트은행(058)",
					"059" => "미쓰비시도쿄UFJ은행(059)",
					"060" => "BOA(060)",
					"071" => "정보통신부 우체국(071)",
					"076" => "신용보증기금(076)",
					"077" => "기술신용보증기금(077)",
					"081" => "하나은행(081)",
					"088" => "신한은행(088)",
					"093" => "한국주택금융공사(093)",
					"094" => "서울보증보험(094)",
					"095" => "경찰청(095)",
					"099" => "금융결제원(099)",
					"209" => "동양종합금융증권(209)",
					"218" => "현대증권(218)",
					"230" => "미래에셋증권(230)",
					"238" => "대우증권(238)",
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

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array(
					'거래번호' => $response['vacc_tid']
				) );

				//가상계좌 주문 접수시 재고 차감여부 확인
				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				$order->set_date_paid( null );
				$order->save();
			}
			function wc_api_vbank_noti( $posted = null ) {
				$order = null;

				try {

					$this->add_log( '가상계좌 입금통보 시작 : ' . $_SERVER['REMOTE_ADDR'] );

					$PayMethod      = pafw_get( $_REQUEST, 'PayMethod' );
					$M_ID           = pafw_get( $_REQUEST, 'MID' );
					$MallUserID     = pafw_get( $_REQUEST, 'MallUserID' );
					$Amt            = pafw_get( $_REQUEST, 'Amt' );
					$name           = pafw_get( $_REQUEST, 'name' );
					$GoodsName      = pafw_get( $_REQUEST, 'GoodsName' );
					$TID            = pafw_get( $_REQUEST, 'TID' );
					$MOID           = pafw_get( $_REQUEST, 'MOID' );
					$AuthDate       = pafw_get( $_REQUEST, 'AuthDate' );
					$ResultCode     = pafw_get( $_REQUEST, 'ResultCode' );
					$ResultMsg      = pafw_get( $_REQUEST, 'ResultMsg' );
					$VbankNum       = pafw_get( $_REQUEST, 'VbankNum' );
					$FnCd           = pafw_get( $_REQUEST, 'FnCd' );
					$VbankName      = pafw_get( $_REQUEST, 'VbankName' );
					$VbankInputName = pafw_get( $_REQUEST, 'VbankInputName' );
					$RcptTID        = pafw_get( $_REQUEST, 'RcptTID' );
					$RcptType       = trim( pafw_get( $_REQUEST, 'RcptType' ) );
					$RcptAuthCode   = pafw_get( $_REQUEST, 'RcptAuthCode' );

					$RcptTypeMsg = '';
					switch ( $RcptType ) {
						case '0':
							$RcptTypeMsg = '미발행';
							break;
						case '1':
							$RcptTypeMsg = '소득공제용';
							break;
						case '2':
							$RcptTypeMsg = '지출증빙용';
							break;
						default:
							$RcptTypeMsg = '미발행';
					}

					$PG_IP  = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );

					//결제결과에 따른 처리 진행
					$orderid = explode( '_', $MOID );
					$orderid = (int) $orderid[0];
					$order   = wc_get_order( $orderid );

					if ( in_array( $PG_IP, array( '121.133.126.10', '121.133.126.11', '211.33.136.39' ) ) ) {

						//가상계좌 입금통보인지 결과 코드 확인
						if ( trim( $ResultCode ) == '4110' && trim( $PayMethod ) == 'VBANK' ) {

							if ( ! in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded' ) ) ) {  //주문상태 확인

								//가상계좌 정보 로딩처리
								$nicepay_vbank_info = $order->get_meta( '_nicepay_vbank_info', true );
								$nicepay_vbank_info = json_decode( $nicepay_vbank_info, JSON_UNESCAPED_UNICODE );

								//주문에 저장된 TID값 가져오기
								$order_tid = $this->get_transaction_id( $order );

								if ( trim( $TID ) != $order_tid ) {
									throw new Exception( 'TID 불일치' );
								}

								if ( trim( $Amt ) != (int) $order->get_total() ) {    //입금액 체크
									throw new Exception( '입금액 불일치' );
								}

								if ( trim( $VbankNum ) != $order->get_meta( '_pafw_vacc_num' ) ) {    //가상계좌 계좌번호 체크
									throw new Exception( '가상계좌번호 불일치' );
								}

								if ( trim( $FnCd ) != $order->get_meta( '_pafw_vacc_bank_code' ) ) {    //가상계좌 은행코드 체크
									throw new Exception( '은행코드 불일치' );
								}

								$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
								$order->update_meta_data( '_pafw_vbank_noti_transaction_date', '20' . wc_clean( $_REQUEST['AuthDate'] ) );
								$order->update_meta_data( '_pafw_vbank_noti_deposit_bank', mb_convert_encoding( $_REQUEST['VbankName'], "UTF-8", "CP949" ) );
								$order->update_meta_data( '_pafw_vbank_noti_depositor', mb_convert_encoding( $_REQUEST['VbankInputName'], "UTF-8", "CP949" ) );
								$order->update_meta_data( '_pafw_cash_receipts', $RcptType );

								$this->add_payment_log( $order, '[ 가상계좌 입금완료 ]', array(
									'입금시각' => $AuthDate
								) );

								//주문 완료 처리
								$order->payment_complete( $order_tid );

								do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

								if ( pafw_order_need_shipping( $order ) ) {
									$order->update_status( $this->settings['order_status_after_payment'] );
								}

								$order->set_date_paid( current_time( 'timestamp', true ) );
								$order->save();

								echo "OK";
								exit();
							} else {
								throw new Exception( sprintf( '잘못된 요청입니다. 결과코드 : %s, 결제수단 : %s', $ResultCode, $PayMethod ) );
							}
						} else {
							throw new Exception( sprintf( '주문상태(%s)가 올바르지 않습니다.', wc_get_order_status_name( $order->get_status() ) ) );
						}

					} else {
						throw new Exception( sprintf( '비정상 접근입니다. [ %s ]', $PG_IP ) );
					}
				} catch ( Exception $e ) {
					$this->add_log( "[오류] " . $e->getMessage() . "\n" . print_r( $_REQUEST, true ) );

					if ( $order ) {
						$this->add_payment_log( $order, '[ 가상계좌 입금오류 ]', $e->getMessage(), false );
					}
					echo "FAIL";
					exit();
				}
			}
			function vbank_refund_request() {
				$this->check_shop_order_capability();

				$order = $this->get_order();

				$vbank_list = $this->get_vbank_list();
				$order->update_meta_data( '_pafw_vbank_refund_bank_code', wc_clean( $_REQUEST['refund_bank_code'] ) );
				$order->update_meta_data( '_pafw_vbank_refund_bank_name', $vbank_list[ wc_clean( $_REQUEST['refund_bank_code'] ) ] );
				$order->update_meta_data( '_pafw_vbank_refund_acc_num', wc_clean( $_REQUEST['refund_acc_num'] ) );
				$order->update_meta_data( '_pafw_vbank_refund_acc_name', wc_clean( $_REQUEST['refund_acc_name'] ) );
				$order->update_meta_data( '_pafw_vbank_refund_reason', wc_clean( $_REQUEST['refund_reason'] ) );
				$order->update_meta_data( '_pafw_vbank_refunded', 'yes' );
				$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
				$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
				$order->save_meta_data();

				$order->update_status( 'refunded' );
				$order->add_order_note( __( '환불계좌 등록이 완료되었습니다. 환불처리는 해당 계좌로 직접 이체해 주셔야 합니다.', 'pgall-for-woocommerce' ) );

				wp_send_json_success( __( '환불계좌 등록이 완료되었습니다. 환불처리는 해당 계좌로 직접 이체해 주셔야 합니다.', 'pgall-for-woocommerce' ) );
			}
		}
	}

} // class_exists function end