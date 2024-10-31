<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Mobx' ) ) :

	class WC_Gateway_Kcp_Mobx extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_mobx';

			parent::__construct();

			$this->settings['bills_cmd']        = 'mcash_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '휴대폰 소액결제', 'pgall-for-woocommerce' );
				$this->description = __( '휴대폰 소액결제는 14세 미만 미성년자의 경우 사용이 불가능합니다.', 'pgall-for-woocommerce' );
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

endif;