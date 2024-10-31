<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_KakaoPay extends PAFW_Payment_Gateway {

		protected $key_for_test = array(
			'TC0ONETIME',
			'TCSUBSCRIP'
		);

		public function __construct() {
			$this->master_id = 'kakaopay';

			$this->pg_title     = __( '카카오페이', 'pgall-for-woocommerce' );
			$this->method_title = __( '카카오페이', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
			}
		}
		public function get_merchant_id() {
			return pafw_get( $this->settings, 'cid' );
		}
		public function get_merchant_key() {
			return '';
		}

		function payment_window_mode() {
			if ( wp_is_mobile() ) {
				return 'iframe';
			} else {
				return 'popup';
			}
		}

		function issue_bill_key_when_change_payment_method() {
			return false;
		}
		function is_vbank( $order = null ) {
			return false;
		}

		public function get_transaction_url( $order ) {
			if ( 'TC0ONETIME' == $this->get_master_id() ) {
				$transaction_url = 'https://mockup-pg-web.kakao.com/v1/confirmation/p/' . $this->get_transaction_id( $order ) . '/';
			} else {
				$transaction_url = 'https://pg-web.kakao.com/v1/confirmation/p/' . $this->get_transaction_id( $order ) . '/';
			}

			$hash = hash( 'sha256', $this->get_merchant_id() . $this->get_transaction_id( $order ) . $order->get_id() . $order->get_user_id() );

			return $transaction_url . $hash;
		}
		public function process_approval_response( $order, $response ) {
			if ( pafw_is_subscription( $order ) ) {
				$response['auth_date'] = $response['paid_date'];

				PAFW_Token::save_token( $response, $order, $this, $order->get_customer_id() );

				$this->add_payment_log( $order, '[ 빌링키 발급 ]', $this->title . ' - ' . $response['payment_method_type'] );

				pafw_set_payment_method_title( $order, $this, $response['payment_method_type'] );
			} else {
				$order->update_meta_data( "_pafw_aid", $response['aid'] );
				$order->update_meta_data( "_pafw_paid_date", $response['paid_date'] );
				$order->update_meta_data( "_pafw_total_price", $response['total_price'] );

				if ( 'CARD' == $response['payment_method_type'] ) {
					$order->update_meta_data( "_pafw_card_num", pafw_get( $response, 'card_num' ) );
					$order->update_meta_data( "_pafw_card_code", pafw_get( $response, 'card_code' ) );
					$order->update_meta_data( "_pafw_card_bank_code", pafw_get( $response, 'card_bank_code' ) );
					$order->update_meta_data( "_pafw_card_name", pafw_get( $response, 'card_name' ) );
					$order->update_meta_data( "_pafw_card_bank_name", pafw_get( $response, 'card_bank_name' ) );
					$order->update_meta_data( "_pafw_card_type", pafw_get( $response, 'card_type' ));
					$order->update_meta_data( "_pafw_install_month", pafw_get( $response, 'install_month' ) );
					$order->update_meta_data( "_pafw_approved_id", pafw_get( $response, 'approved_id' ));
				}

				$order->save_meta_data();
				if ( $this->supports( 'subscriptions' ) ) {
					$response['auth_date'] = $response['paid_date'];

					PAFW_Token::save_token( $response, $order, $this, $order->get_customer_id() );
				}

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
					'거래요청번호' => $response['aid']
				) );

				pafw_set_payment_method_title( $order, $this, $response['payment_method_type'] );
			}
		}

	}
}