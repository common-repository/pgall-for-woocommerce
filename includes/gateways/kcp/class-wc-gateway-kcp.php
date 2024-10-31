<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_KCP extends PAFW_Payment_Gateway {
		protected $key_for_test = array(
			'T0000',
			'T0007'
		);
		const TX_VACC_DEPOSIT = 'TX00';
		const TX_ESCROW_CONFIRM = 'TX02';
		const TX_ESCROW_DELIVERY = 'TX03';
		const TX_ESCROW_WITHHOLD_SETTLEMENT = 'TX04';
		const TX_ESCROW_CANCEL_IMMEDIATELY = 'TX05';
		const TX_ESCROW_CANCEL = 'TX06';

		public function __construct() {
			$this->master_id = 'kcp';

			$this->pg_title     = __( 'NHN KCP', 'pgall-for-woocommerce' );
			$this->method_title = __( 'NHN KCP', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
			}

			add_filter( 'pafw_cash_receipt_params_' . $this->id, array( $this, 'add_cash_receipt_request_params' ), 10, 2 );
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'site_cd' );
		}

		public function get_merchant_key() {
			return pafw_get( $this->settings, 'site_key' );
		}
		public function add_register_order_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'referer'          => $_SERVER['HTTP_REFERER'],
				'user_agent'       => pafw_get( $_SERVER, 'HTTP_USER_AGENT' ),
				'kcp_noint'        => pafw_get( $this->settings, 'kcp_noint' ),
				'kcp_noint_quota'  => pafw_get( $this->settings, 'kcp_noint_quota' ),
				'vcnt_expire_term' => pafw_get( $this->settings, 'vcnt_expire_term' ),
				'disp_tax_yn'      => pafw_get( $this->settings, 'disp_tax_yn' ),
				'site_logo'        => pafw_get( $this->settings, 'site_logo' ),
				'eng_flag'         => pafw_get( $this->settings, 'eng_flag' ),
				'skin_indx'        => pafw_get( $this->settings, 'skin_indx' )
			);

			return $params;
		}
		public function add_cash_receipt_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'reg_num'      => preg_replace( '~\D~', '', $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ),
				'receipt_type' => 'ID' == $order->get_meta( '_pafw_bacs_receipt_usage' ) ? '0' : '1'
			);

			return $params;
		}
		public function get_transaction_url( $order ) {
			$transaction_url = '';

			if ( 'sandbox' === pafw_get( $this->settings, 'operation_mode', 'sandbox' ) ) {
				$bills_url = 'https://testadmin8.kcp.co.kr/assist/bill.BillActionNew.do';
			} else {
				$bills_url = 'https://admin8.kcp.co.kr/assist/bill.BillActionNew.do';
			}

			$tno       = $order->get_transaction_id();
			$amount    = $order->get_meta( '_pafw_total_price', true );
			$bills_cmd = pafw_get( $this->settings, 'bills_cmd' );

			if ( ! empty( $tno ) ) {
				$transaction_url = sprintf( "%s?cmd=%s&tno=%s&order_no=%s&trade_mony=%s", $bills_url, $bills_cmd, $tno, $order->get_id(), $amount );
			}

			return apply_filters( 'woocommerce_get_transaction_url', $transaction_url, $order, $this );
		}

		function send_common_return_response() {
			header( 'HTTP/1.1 200 OK' );
			header( "Content-Type: text; charset=euc-kr" );
			header( "Cache-Control: no-cache" );
			header( "Pragma: no-cache" );

			echo '<html><body><form><input type="hidden" name="result" value="0000"></form></body></html>';
			die();
		}

		function process_common_return() {
		}

		public function get_receipt_popup_params() {
			return array(
				'name'     => 'showreceipt',
				'features' => 'width=470,height=815, scrollbars=no,resizable=no'
			);
		}
		public function process_approval_response( $order, $response ) {
			$order->update_meta_data( "_pafw_card_num", pafw_get( $response, 'card_num' ) );
			$order->update_meta_data( "_pafw_card_code", pafw_get( $response, 'card_code' ) );
			$order->update_meta_data( "_pafw_card_bank_code", pafw_get( $response, 'card_bank_code' ) );
			$order->update_meta_data( "_pafw_card_name", pafw_get( $response, 'card_name' ) );
			$order->update_meta_data( "_pafw_card_other_pay_type", $response['card_other_pay_type'] );
			$order->save_meta_data();

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
				'거래번호' => $response['transaction_id']
			) );
		}
	}

}