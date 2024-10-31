<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Gateway_TossPayments extends PAFW_Payment_Gateway {
		protected $key_for_test = array(
			'tosspayments',
			'tvivarepublica',
		);
		public function __construct() {
			$this->view_transaction_url = '';

			$this->master_id = 'tosspayments';
			$this->pg_title     = __( '토스페이먼츠', 'pgall-for-woocommerce' );
			$this->method_title = __( '토스페이먼츠', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_filter( 'pafw_approval_params_' . $this->id, array( $this, 'add_approval_request_params' ), 10, 2 );
				add_filter( 'pafw_cancel_params_' . $this->id, array( $this, 'add_cancel_request_params' ), 10, 2 );
				add_filter( 'pafw_register_shipping_params_' . $this->id, array( $this, 'add_register_shipping_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
			}

			add_filter( 'pafw_cash_receipt_params_' . $this->id, array( $this, 'add_cash_receipt_request_params' ), 10, 2 );
			add_filter( 'pafw_cash_cancel_params_' . $this->id, array( $this, 'add_cash_cancel_request_params' ), 10, 2 );
		}

		function payment_window_mode() {
			return 'page';
		}
		public function add_register_order_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'client_key'              => pafw_get( $this->settings, 'client_key' ),
				'international_card_only' => 'tosspayments_foreign_card' == $this->id ? 'yes' : 'no',
				'receipt'                 => pafw_get( $this->settings, 'receipt' ),
				'max_installment_plan'    => pafw_get( $this->settings, 'max_installment_plan' ),
				'app_scheme'              => pafw_get( $this->settings, 'app_scheme' ),
				'account_date_limit'      => pafw_get( $this->settings, 'account_date_limit', 3 ),
			);

			return $params;
		}
		public function add_approval_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'secret_key' => pafw_get( $this->settings, 'secret_key' ),
			);

			return $params;
		}
		public function add_cancel_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'secret_key' => pafw_get( $this->settings, 'secret_key' ),
			);

			return $params;
		}
		public function add_register_shipping_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'mall_ip'          => $_SERVER['SERVER_ADDR'],
				'sheet_no'         => wc_clean( $_POST['tracking_number'] ),
				'dlv_company_name' => pafw_get( $this->settings, 'delivery_company_name' ),
				'sender_name'      => pafw_get( $this->settings, 'delivery_register_name' )
			);

			return $params;
		}
		public function add_cash_receipt_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'secret_key'   => pafw_get( $this->settings, 'secret_key' ),
				'reg_num'      => preg_replace( '~\D~', '', $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ),
				'receipt_type' => $order->get_meta( '_pafw_bacs_receipt_usage' )
			);

			return $params;
		}
		public function add_cash_cancel_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'secret_key' => pafw_get( $this->settings, 'secret_key' )
			);

			return $params;
		}
		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
		public function get_merchant_key() {
			return pafw_get( $this->settings, 'merchant_key' );
		}

		static function enqueue_frontend_script() {
			wp_enqueue_script( "pafw-tosspayment", "https://js.tosspayments.com/v1/payment" );
		}
		public function get_transaction_url( $order ) {
			return $order->get_meta( '_pafw_receipt_url' );
		}
	}
}