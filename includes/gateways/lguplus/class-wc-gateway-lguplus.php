<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Gateway_Lguplus extends PAFW_Payment_Gateway {
		protected $key_for_test = array(
			'lgdacomxpay',
			'tlgdacomxpay',
			'tosspayments',
			'ttosspayments',
		);
		public function __construct() {
			$this->view_transaction_url = '';

			$this->master_id = 'lguplus';
			$this->pg_title     = __( '토스페이먼츠', 'pgall-for-woocommerce' );
			$this->method_title = __( '토스페이먼츠', 'pgall-for-woocommerce' );

			parent::__construct();

			if ( 'yes' == $this->enabled ) {
				add_action( 'pafw_payment_info_meta_box_action_button_' . $this->id, array( $this, 'add_meta_box_action_button' ) );
				add_action( 'pafw_view_order_receipt_button_' . $this->id, array( $this, 'add_meta_box_action_button' ) );

				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_filter( 'pafw_register_shipping_params_' . $this->id, array( $this, 'add_register_shipping_request_params' ), 10, 2 );
				add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
			}

			add_filter( 'pafw_cash_receipt_params_' . $this->id, array( $this, 'add_cash_receipt_request_params' ), 10, 2 );
		}
		public function add_register_order_request_params( $params, $order ) {
			$params[ $this->get_master_id() ] = array(
				'return_url'         => $this->get_api_url( wp_is_mobile() ? 'payment' : 'return' ),
				'cancel_url'         => $this->get_api_url( 'cancel' ),
				'noti_url'           => $this->get_api_url( 'vbank_noti' ),
				'receipt'            => pafw_get( $this->settings, 'receipt' ),
				'account_date_limit' => pafw_get( $this->settings, 'account_date_limit', 3 ),
				'quotabase'          => pafw_get( $this->settings, 'quotabase' ),
				'use_nointerest'     => pafw_get( $this->settings, 'use_nointerest' ),
				'nointerest'         => pafw_get( $this->settings, 'nointerest' ),
				'language_code'      => pafw_get( $this->settings, 'language_code' ),
				'logo_url'           => utf8_uri_encode( pafw_get( $this->settings, 'site_logo', PAFW()->plugin_url() . '/assets/images/default-logo.jpg' ) ),
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
				'mall_reg_num'      => get_option( 'pafw_bacs_receipt_reg_number' ),
				'mall_phone_number' => preg_replace( '~\D~', '', $order->get_meta( 'pafw_bacs_receipt_phone_number' ) ),
				'reg_num'           => preg_replace( '~\D~', '', $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ),
				'receipt_type'      => 'ID' == $order->get_meta( '_pafw_bacs_receipt_usage' ) ? '1' : '2'
			);

			return $params;
		}

		public static function add_script_params( $params ) {
			$options = get_option( 'pafw_mshop_lguplus' );

			$params['lguplus_mode'] = 'production' == pafw_get( $options, 'operation_mode', 'sandbox' ) ? 'service' : 'test';

			return $params;
		}
		public function add_meta_box_action_button( $order ) {
			$tid = $this->get_transaction_id( $order );

			$mid = ( 'sandbox' == pafw_get( $this->settings, 'operation_mode' ) ? 't' : '' ) . $this->get_merchant_id();
			$key = $this->get_merchant_key();

			$authdata = md5( $mid . $tid . $key );

			if ( 'sandbox' == pafw_get( $this->settings, 'operation_mode' ) ) {
				wp_enqueue_script( 'lguplus', 'https:///pgweb.tosspayments.com:7085/WEB_SERVER/js/receipt_link.js' );
			} else {
				wp_enqueue_script( 'lguplus', 'https://pgweb.tosspayments.com/WEB_SERVER/js/receipt_link.js' );
			}

			?>
            <a class="button pafw_action_button tips" style="text-align: center;" href="javascript:showReceiptByTID('<?php echo $mid; ?>', '<?php echo $tid; ?>', '<?php echo $authdata; ?>')"><?php _e( '영수증 출력', 'pgall-for-woocommerce' ); ?></a>
			<?php
		}
		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
		public function get_merchant_key() {
			return pafw_get( $this->settings, 'merchant_key' );
		}
	}
}