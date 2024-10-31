<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Card' ) ) :

	class WC_Gateway_Kcp_Card extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_card';

			parent::__construct();

			$this->settings['bills_cmd'] = 'card_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '신용카드 결제', 'pgall-for-woocommerce' );
				$this->description = __( '구글크롬, IE, Safari 에서 결제 가능한 웹표준 결제 입니다. 결제를 진행해 주세요.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
		public function process_approval_response( $order, $response ) {
			$order->update_meta_data( "_pafw_card_num", pafw_get( $response, 'card_num' ) );
			$order->update_meta_data( "_pafw_card_code", pafw_get( $response, 'card_code' ) );
			$order->update_meta_data( "_pafw_card_bank_code", pafw_get( $response, 'card_bank_code' ) );
			$order->update_meta_data( "_pafw_card_name", pafw_get( $response, 'card_name' ) );
			$order->update_meta_data( "_pafw_card_other_pay_type", $response['card_other_pay_type'] );
			$order->save_meta_data();

			if( ! empty( $response['card_other_pay_type']  ) ) {
				pafw_set_payment_method_title( $order, $this, $response['card_other_pay_type'] );
			}

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'거래번호' => $response['transaction_id']
			) );
		}
	}

endif;