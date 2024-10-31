<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Nicepay_Escrow_Bank extends WC_Gateway_Nicepay {
	public function __construct() {
		$this->id = 'nicepay_escrow_bank';

		parent::__construct();

		if ( empty( $this->settings['title'] ) ) {
			$this->title       = __( '실시간계좌이체(에스크로)', 'pgall-for-woocommerce' );
			$this->description = __( '에스크로 방식으로 계좌에서 바로 결제하는 에스크로 실시간 계좌이체 입니다.', 'pgall-for-woocommerce' );
		} else {
			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
		}
		$this->supports[] = 'pafw-cash-receipt';
		$this->supports[] = 'pafw-escrow';
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
