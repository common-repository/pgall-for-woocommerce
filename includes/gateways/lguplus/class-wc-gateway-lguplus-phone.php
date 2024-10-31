<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Phone' ) ) {

		class WC_Gateway_Lguplus_Phone extends WC_Gateway_Lguplus {
			public function __construct() {
				$this->id = 'lguplus_phone';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '휴대폰 소액결제', 'pgall-for-woocommerce' );
					$this->description = __( '휴대폰으로 결제합니다', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}
			public function process_approval_response( $order, $response ) {
				$order->update_meta_data( "_pafw_hpp_num", $response['phone_number'] );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
					'거래번호'  => $response['transaction_id'],
					'휴대폰번호' => $response['phone_number']
				) );
			}
		}
	}

} // class_exists function end