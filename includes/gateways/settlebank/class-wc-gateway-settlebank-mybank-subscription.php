<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Settlebank_Mybank_Subscription' ) ) {

		class WC_Gateway_Settlebank_Mybank_Subscription extends WC_Gateway_Settlebank {

			public function __construct() {

				$this->id = 'settlebank_mybank_subscription';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '내통장 정기결제', 'pgall-for-woocommerce' );
					$this->description = __( '내통장 정기결제로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->countries = array( 'KR' );
				$this->supports  = array(
					'products',
					'subscriptions',
					'multiple_subscriptions',
					'subscription_cancellation',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_amount_changes',
					'subscription_date_changes',
					'subscription_payment_method_change_customer',
					'pafw',
					'refunds',
					'pafw_additional_charge',
					'pafw_cancel_bill_key',
					'add_payment_method',
				);

				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'woocommerce_scheduled_subscription_payment' ), 10, 2 );

				add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
				add_filter( 'pafw_subscription_payment_params_' . $this->id, array( $this, 'add_subscription_payment_request_params' ), 10, 2 );

				add_action( 'pafw_' . $this->id . '_register', array( $this, 'wc_api_request_register' ) );

				add_filter( 'pafw_token_meta', array( $this, 'maybe_change_token_meta' ), 10, 5 );
			}

			function adjust_settings() {
				$this->settings['merchant_id']    = $this->settings['subscription_merchant_id'];
				$this->settings['merchant_key']   = $this->settings['subscription_merchant_key'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['test_user_id']   = $this->settings['test_user_id_subscription'];
			}

			public function payment_fields() {
				if ( $this->is_available() ) {
					ob_start();
					wc_get_template( 'pafw/settlebank/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
					ob_end_flush();
				}
			}

			public function get_subscription_meta_key( $meta_key ) {
				return '_pafw_settlebank_mybank_' . $meta_key;
			}
			public function add_register_order_request_params( $params, $order ) {
				$params['settlebank'] = array(
					'is_subscription' => pafw_is_subscription( $order ) ? 'yes' : 'no'
				);

				return $params;
			}
			public function add_subscription_payment_request_params( $params, $order ) {
				$params['settlebank'] = array(
					'paid_date' => current_time( 'mysql' )
				);

				return $params;
			}
			function process_payment( $order_id ) {
				return $this->process_auth_subscription_payment( $order_id );
			}
			function wc_api_request_register() {
				try {
					$user = null;

					if ( empty( $_GET['transaction_id'] ) || empty( $_GET['auth_token'] ) || empty( $_GET['user_id'] ) ) {
						throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9000' );
					}

					$user_id = str_replace( 'PAFW-BILL-', '', wc_clean( $_GET['user_id'] ) );

					$user = get_userdata( $user_id );

					PAFW_Gateway::register_complete( $user, $this );

					PAFW_Gateway::redirect( $user, $this );
				} catch ( Exception $e ) {
					PAFW_Gateway::redirect( $user, $this, $e->getMessage(), false );
				}
			}
			function add_payment_method() {
				try {
					$user = get_currentuserinfo();

					$response = PAFW_Gateway::get_register_form( $user, $this );

					wp_send_json_success( array_merge( array(
						'result' => 'success'
					), $response ) );

				} catch ( Exception $e ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
			}
			function maybe_change_token_meta( $metas, $response, $order, $gateway, $user_id ) {
				if ( $gateway->id == $this->id ) {
					$metas = array(
						'pafw_version'  => PAFW_VERSION,
						'auth_date'     => current_time( 'mysql' ),
						'card_code'     => $response['bank_code'],
						'card_name'     => ! empty( $response['bank_name'] ) ? $response['bank_name'] : __( "내통장결제", "pgall-for-woocommerce" ),
						'card_num'      => pafw_get( $response, 'card_num' ),
						'register_date' => current_time( 'mysql' )
					);
				}

				return $metas;
			}
		}
	}

}
