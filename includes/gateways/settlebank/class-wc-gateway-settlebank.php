<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_Settlebank extends PAFW_Payment_Gateway {

		protected $target_recurrent_id = null;

		protected $product_code = null;

		public function __construct() {
			$this->master_id = 'settlebank';

			parent::__construct();

			$this->pg_title     = __( '헥토파이낸셜', 'pgall-for-woocommerce' );
			$this->method_title = __( '헥토파이낸셜', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
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
		public function get_subscription_meta_key( $meta_key ) {
			return '_pafw_settlebank_' . $meta_key;
		}

		function issue_bill_key_when_change_payment_method() {
			return false;
		}
		public function process_approval_response( $order, $response ) {
			$response['auth_date'] = $response['paid_date'];

			if ( pafw_is_subscription( $order ) ) {
				$response['auth_date'] = $response['paid_date'];

				PAFW_Token::save_token( $response, $order, $this, $order->get_customer_id() );

				$this->add_payment_log( $order, '[ 빌링키 발급 ]', $this->title . ' - ' . $response['payment_method_type'] );
			} else {
				$order->update_meta_data( "_pafw_txnid", $response['txnid'] );
				$order->update_meta_data( "_pafw_paid_date", $response['paid_date'] );
				$order->update_meta_data( "_pafw_total_price", $response['total_price'] );
				$order->update_meta_data( "_pafw_discount_price", $response['discount_price'] );
				$order->update_meta_data( "_pafw_pay_price", $response['pay_price'] );
				$order->update_meta_data( "_pafw_bank_acct_no", $response['bank_acct_no'] );
				$order->save_meta_data();
				if ( $this->supports( 'subscriptions' ) ) {
					$response['auth_date'] = $response['paid_date'];

					PAFW_Token::save_token( $response, $order, $this, $order->get_customer_id() );
				}

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
					'거래요청번호' => $response['transaction_id']
				) );
			}
		}
	}
}