<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Escrow_Bank' ) ) :

	class WC_Gateway_Kcp_Escrow_Bank extends WC_Gateway_Kcp {

		const ESCROW_TYPE_DELIVERY = 'STE1';
		const ESCROW_TYPE_CANCEL_IMMEDIATELY = 'STE2';
		const ESCROW_TYPE_WITHHOLD_SETTLEMENT = 'STE3';
		const ESCROW_TYPE_CANCEL_AFTER_DELIVERY = 'STE4';

		public function __construct() {
			$this->id = 'kcp_escrow_bank';

			$this->is_escrow = true;

			parent::__construct();

			$this->settings['bills_cmd'] = 'acnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '에스크로 계좌이체', 'pgall-for-woocommerce' );
				$this->description = __( '에스크로 계좌이체를 통해 결제를 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
			$this->supports[] = 'pafw-cash-receipt';
			$this->supports[] = 'pafw-escrow';

			add_filter( 'pafw_register_shipping_params_' . $this->id, array ( $this, 'add_register_shipping_params' ), 10, 2 );
		}

		public function is_refundable( $order, $screen = 'admin' ) {
			return ! in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) && 'yes' != $order->get_meta( '_pafw_escrow_register_delivery_info' );
		}
		public function add_register_shipping_params( $params, $order ) {
			$params['kcp'] = array (
				'sheet_no'         => pafw_get( $_REQUEST, 'tracking_number' ),
				'dlv_company_name' => $this->settings['delivery_company_name'],
			);

			return $params;
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