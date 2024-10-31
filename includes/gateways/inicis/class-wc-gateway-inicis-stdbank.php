<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis_Stdbank' ) ) {
	return;
}

class WC_Gateway_Inicis_Stdbank extends WC_Gateway_Inicis {

	public function __construct() {

		$this->id = 'inicis_stdbank';

		parent::__construct();

		if ( empty( $this->settings['title'] ) ) {
			$this->title       = __( '실시간 계좌이체', 'pgall-for-woocommerce' );
			$this->description = __( '구글크롬, IE, Safari 에서 결제 가능한 웹표준 결제 입니다. 결제를 진행해 주세요.', 'pgall-for-woocommerce' );
		} else {
			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
		}

		$this->supports[] = 'refunds';
		$this->supports[] = 'pafw-cash-receipt';
	}
	public function get_accept_methods() {
		$accept_methods = parent::get_accept_methods();

		if ( wp_is_mobile() ) {
			if ( 'no' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'bank_receipt=N';
			}
		} else {
			if ( 'no' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'no_receipt';
			}
		}

		return $accept_methods;
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
