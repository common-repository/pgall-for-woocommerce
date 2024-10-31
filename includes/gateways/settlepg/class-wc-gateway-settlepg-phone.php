<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_SettlePG_Phone' ) ) {

		class WC_Gateway_SettlePG_Phone extends WC_Gateway_SettlePG {

			public function __construct() {

				$this->id = 'settlepg_phone';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '휴대폰', 'pgall-for-woocommerce' );
					$this->description = __( '휴대폰으로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}
			public function process_approval_response( $order, $response ) {
				$order->update_meta_data( "_pafw_hpp_num", $response['phone_number'] );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'거래번호' => $response['transaction_id']
				) );
			}
		}
	}

}
