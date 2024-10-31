<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_TossPayments_Npay' ) ) {

		class WC_Gateway_TossPayments_Npay extends WC_Gateway_TossPayments {

			public function __construct() {
				$this->id = 'tosspayments_npay';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '네이버페이', 'pgall-for-woocommerce' );
					$this->description = __( '네이버페이로 결제합니다.', 'pgall-for-woocommerce' );
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
				$order->update_meta_data( "_pafw_card_type", pafw_get( $response, 'card_type' ));
				$order->update_meta_data( "_pafw_owner_type", pafw_get( $response, 'owner_type' ));
				$order->update_meta_data( "_pafw_receipt_url", pafw_get( $response, 'receipt_url' ));
				$order->save_meta_data();

				if( ! empty( $response['card_other_pay_type']  ) ) {
					pafw_set_payment_method_title( $order, $this, $response['card_other_pay_type'] );
				}

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
					'거래번호' => $response['transaction_id']
				) );
			}
		}
	}

}
