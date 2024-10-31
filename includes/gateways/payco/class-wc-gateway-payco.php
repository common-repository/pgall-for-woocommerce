<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_Payco extends PAFW_Payment_Gateway {

		const PAY_METHOD_VBANK = '02';
		const PAY_METHOD_CARD = '31';
		const PAY_METHOD_SIMPLE_BANK = '35';
		const PAY_METHOD_PHONE = '60';
		const PAY_METHOD_PAYCO_POINT = '98';
		const PAY_METHOD_PAYCO_COUPON = '75';
		const PAY_METHOD_CARD_COUPON = '76';
		const PAY_METHOD_MALL_COUPON = '77';
		const PAY_METHOD_DEPOSIT_REFUND = '96';

		protected $key_for_test = array (
			'S0FSJE'
		);

		public function __construct() {
			$this->master_id = 'payco';

			parent::__construct();
			$this->pg_title     = __( 'NHN 페이코', 'pgall-for-woocommerce' );
			$this->method_title = __( 'NHN 페이코', 'pgall-for-woocommerce' );

			if( 'yes' == $this->enabled ) {
				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );

				add_filter( 'pafw_cancel_params_' . $this->id, array( $this, 'add_cancel_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
			}
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'cpid', 'PARTNERTEST' );
		}

		public function get_merchant_key() {
			return pafw_get( $this->settings, 'seller_key', 'S0FSJE' );
		}

		function payment_window_mode() {
			if( wp_is_mobile() ) {
				return parent::payment_window_mode();
			}else {
				return 'popup';
			}
		}
		public function add_register_order_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array (
				'account_date_limit' => pafw_get( $this->settings, 'vbank_account_date_limit', 3 ),
				'product_id'         => pafw_get( $this->settings, 'product_id', 'PROD_EASY' ),
				'language_code'      => pafw_get( $this->settings, 'language_code', 'KR' ),
				'notification_url'   => $this->get_api_url( 'deposit_noti' ),
			);

			return $params;
		}
		public function add_approval_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] =  array (
				'reserved_order_no'          => wc_clean( $_REQUEST['reserveOrderNo'] ),
				'payment_certify_token'      => wc_clean( $_REQUEST['paymentCertifyToken'] ),
				'seller_order_reference_key' => wc_clean( $_REQUEST['sellerOrderReferenceKey'] ),
			);

			return $params;
		}
		public function add_cancel_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] =  array (
				'order_certify_key' => $order->get_meta( '_pafw_order_certify_key' ),
			);

			return $params;
		}
		function is_vbank( $order = null ) {
			return self::PAY_METHOD_VBANK == $order->get_meta( '_pafw_payment_method' );
		}
		function is_escrow( $order = null ) {
			return in_array( $order->get_meta( '_pafw_payment_method' ), array ( self::PAY_METHOD_VBANK, self::PAY_METHOD_SIMPLE_BANK ) );
		}
		public function get_transaction_url( $order ) {
			$return_url = '';

			if ( 'sandbox' == pafw_get( $this->settings, 'operation_mode', 'sandbox' ) ) {
				$receipt_url = 'https://alpha-bill.payco.com/';
			} else {
				$receipt_url = 'https://bill.payco.com';
			}

			$seller_order_reference_key = $order->get_meta( '_pafw_txnid' );
			$order_no                   = $this->get_transaction_id( $order );

			if ( ! empty( $seller_order_reference_key ) ) {
				$return_url = sprintf( "%s/seller/receipt/%s/%s/%s", $receipt_url, $this->seller_key, $seller_order_reference_key, $order_no );
			}

			return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
		}
		public function process_approval_response( $order, $response ) {
			$order->update_meta_data( '_pafw_order_certify_key', $response['order_certify_key'] );
			$order->update_meta_data( '_pafw_paid_date', $response['paid_date'] );
			$order->update_meta_data( "_pafw_txnid", $response['txnid'] );
			$order->update_meta_data( "_pafw_total_price", $response['total_price'] );
			$order->update_meta_data( "_pafw_payment_method", $response['payment_method'] );

			if ( self::PAY_METHOD_CARD == $response['payment_method'] ) {
				$order->update_meta_data( "_pafw_card_num", pafw_get( $response, 'card_num' ) );
				$order->update_meta_data( "_pafw_card_code", pafw_get( $response, 'card_code' ) );
				$order->update_meta_data( "_pafw_card_bank_code", pafw_get( $response, 'card_bank_code' ) );
				$order->update_meta_data( "_pafw_card_name", pafw_get( $response, 'card_name' ) );
				$order->update_meta_data( "_pafw_card_qouta", $response['card_qouta'] );
				$order->update_meta_data( "_pafw_card_interest", $response['card_interest'] );
				$order->update_meta_data( "_pafw_support_partial_cancel", $response['support_partial_cancel'] );
			} else if ( self::PAY_METHOD_VBANK == $response['payment_method'] ) {
				$order->update_meta_data( '_pafw_vacc_num', $response['vacc_num'] );
				$order->update_meta_data( '_pafw_vacc_bank_code', $response['vacc_bank_code'] );
				$order->update_meta_data( '_pafw_vacc_bank_name', $response['vacc_bank_name'] );
				$order->update_meta_data( '_pafw_vacc_holder', $response['vacc_holder'] );
				$order->update_meta_data( '_pafw_vacc_depositor', $response['vacc_depositor'] );
				$order->update_meta_data( '_pafw_vacc_date', $response['vacc_date'] );
			} else if ( self::PAY_METHOD_PHONE == $response['payment_method'] ) {
				$order->update_meta_data( "_pafw_hpp_num", $response['hpp_num'] );
			} else if ( self::PAY_METHOD_SIMPLE_BANK == $response['payment_method'] ) {
				$order->update_meta_data( "_pafw_bank_code", $response['bank_code'] );
				$order->update_meta_data( "_pafw_bank_name", $response['bank_name'] );
			}

			if ( is_callable( array ( $order, 'set_payment_method_title' ) ) ) {
				$order->set_payment_method_title( $this->title . ' - ' . $response['payment_method_title'] );
			} else {
				$order->update_meta_data( '_payment_method_title', $this->title . ' - ' . $response['payment_method_title'] );
			}

			if ( 'Y' == $response['payment_completion_yn'] ) {
				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'거래번호' => $response['transaction_id']
				) );
			} else {
				$this->supports[] = 'pafw-vbank';

				$order->update_meta_data( '_pafw_vacc_tid', $response['transaction_id'] );

				$this->add_payment_log( $order, '[ 무통장 입금 대기중 ]', array (
					'거래번호' => $response['transaction_id']
				) );

				$order->update_status( 'on-hold', '무통장 입금을 기다려주세요.' );
			}

			$order->save_meta_data();
		}
		function do_deposit_noti() {
			try {
				$this->add_log( "do_deposit_noti()\n" . print_r( $_REQUEST, true ) );

				$response = json_decode( stripslashes( $_REQUEST['response'] ), true );

				if ( empty( $response['sellerOrderReferenceKey'] ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '900001' );
				}

				$ids   = explode( '_', $response['sellerOrderReferenceKey'] );
				$order = wc_get_order( $ids[0] );

				if ( ! $order || $response['reserveOrderNo'] !== $order->get_meta( '_pafw_reserve_order_no' ) ) {
					throw new Exception( __( '주문정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '900002' );
				}

				if ( $order->get_total() != $response['totalPaymentAmt'] ) {
					throw new Exception( __( '주문금액과 결제금액이 틀립니다.', 'pgall-for-woocommerce' ), '900003' );
				}

				if ( 'Y' == $response['paymentCompletionYn'] ) {
					$order->update_meta_data( "_pafw_paid_date", $response['paymentCompleteYmdt'] );
				}

				$payment_details = current( $response['paymentDetails'] );

				$order->update_meta_data( '_pafw_vbank_noti_received', 'yes' );
				$order->update_meta_data( '_pafw_vbank_noti_transaction_date', $payment_details['tradeYmdt'] );
				$order->update_meta_data( '_pafw_vbank_noti_deposit_bank', '' );
				$order->update_meta_data( '_pafw_vbank_noti_depositor', '' );

				$this->add_payment_log( $order, '[ 무통장 입금완료 ]', array (
					'입금시각' => preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $payment_details['tradeYmdt'] ),
					'결제번호' => $payment_details['paymentTradeNo']
				) );

				$order->payment_complete( $this->get_transaction_id( $order ) );

				$order->set_date_paid( current_time( 'timestamp', true ) );
				$order->save();

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

				echo 'OK';
				exit();
			} catch ( Exception $e ) {
				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( $message );
				if ( $order ) {
					$order->add_order_note( $message );
				}
				echo 'FAIL';
				exit();
			}
		}
		function escrow_register_delivery_info() {

			$this->add_log( 'escrow_register_delivery_info' . print_r( $_REQUEST, true ) );

			$order = $this->get_order();

			PAFW_Gateway::register_shipping( $order, $this );

			$order->update_status( $this->order_status_after_enter_shipping_number );

			wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
		}
		function add_meta_box_escrow( $post ) {
			$order = wc_get_order( $post );

			$order_status = $order->get_status();

			$is_paid               = ! empty( $order->get_date_paid() );
			$order_cancelled        = $order->get_meta( '_pafw_escrow_order_cancelled' );
			$register_delivery_info = 'yes' == $order->get_meta( '_pafw_escrow_register_delivery_info' );
			$is_cancelled           = 'yes' == $order->get_meta( '_pafw_escrow_order_cancelled' );
			$is_confirmed           = 'yes' == $order->get_meta( '_pafw_escrow_order_confirm' ) || 'yes' == $order->get_meta( '_pafw_escrow_order_confirm_reject' );

			include( 'views/escrow.php' );
		}
	}
}