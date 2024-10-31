<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Bank' ) ) :

	class WC_Gateway_Kcp_Bank extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_bank';

			parent::__construct();

			$this->settings['bills_cmd']        = 'acnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '실시간 계좌이체', 'pgall-for-woocommerce' );
				$this->description = __( '실시간 계좌이체를 통해 결제를 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'pafw-cash-receipt';
		}
		public function process_approval_response( $order, $response ) {
			$order->update_meta_data( "_pafw_bank_code", $response['bank_code'] );
			$order->update_meta_data( "_pafw_bank_name", $response['bank_name'] );
			$order->update_meta_data( "_pafw_cash_receipts", $response['cash_receipts'] );
			$order->save_meta_data();

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'거래번호' => $response['transaction_id']
			) );
		}
	}

endif;