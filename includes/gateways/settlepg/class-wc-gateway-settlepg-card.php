<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_SettlePG_Card' ) ) {

		class WC_Gateway_SettlePG_Card extends WC_Gateway_SettlePG{

			public function __construct() {

				$this->id = 'settlepg_card';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '신용카드', 'pgall-for-woocommerce' );
					$this->description = __( '신용카드로 결제합니다.', 'pgall-for-woocommerce' );
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
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'거래번호' => $response['transaction_id']
				) );
			}
		}
	}

}
