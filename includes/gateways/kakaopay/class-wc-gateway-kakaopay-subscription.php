<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_KakaoPay_Subscription' ) ) {

		class WC_Gateway_KakaoPay_Subscription extends WC_Gateway_KakaoPay {

			public function __construct() {

				$this->id = 'kakaopay_subscription';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '카카오페이 정기결제', 'pgall-for-woocommerce' );
					$this->description = __( '카카오페이 정기결제로 결제합니다.', 'pgall-for-woocommerce' );
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

				add_action( 'pafw_subscription_register_complete_response_' . $this->id, array( $this, 'process_subscription_register_complete_response' ), 10, 2 );
				add_action( 'pafw_subscription_payment_response_' . $this->id, array( $this, 'process_subscription_payment_response' ), 10, 2 );

				add_action( 'pafw_' . $this->id . '_register', array( $this, 'wc_api_request_register' ) );

				add_filter( 'pafw_payments_blocks_bill_key_info_' . $this->id, array( $this, 'maybe_change_bill_key_info' ), 10, 4 );

				add_filter( 'pafw_token_meta', array( $this, 'maybe_change_token_meta' ), 10, 5 );
			}

			function adjust_settings() {
				$this->settings['cid']            = $this->settings['cid_subscription'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['test_user_id']   = $this->settings['test_user_id_subscription'];
			}

			public function payment_fields() {
				if ( $this->is_available() ) {
					ob_start();
					wc_get_template( 'pafw/kakaopay/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
					ob_end_flush();
				}
			}

			public function get_subscription_meta_key( $meta_key ) {
				if ( 'bill_key' == $meta_key ) {
					return '_pafw_subscription_batch_key';
				}

				return '_pafw_kakaopay_' . $meta_key;
			}
			public function add_register_order_request_params( $params, $order ) {
				$params['kakaopay'] = array(
					'is_subscription' => pafw_is_subscription( $order ) ? 'yes' : 'no',
					'card_quota'      => $order->get_meta( '_pafw_card_quota' )
				);

				return $params;
			}
			function process_payment( $order_id ) {
				return $this->process_auth_subscription_payment( $order_id );
			}
			public function process_subscription_payment_response( $order, $response ) {
				if ( ! defined( 'PAFW_ADDITIONAL_CHARGE' ) ) {
					$order->update_meta_data( '_pafw_subscription_batch_key', $response['bill_key'] );
					$order->save_meta_data();
				}
			}
			function process_subscription_register_complete_response( $user, $response ) {
				update_user_meta( $user->ID, $this->get_subscription_meta_key( 'payment_method_type' ), $response['payment_method_type'] );
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
			function maybe_change_bill_key_info( $bill_key_info, $card_name, $card_num, $payment_gateway ) {
				$payment_method_type = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'payment_method_type' ), true );

				if ( 'MONEY' == $payment_method_type ) {
					$bill_key_info = __( '카카오페이 - MONEY', 'pgall-for-woocommerce' );
				}

				return $bill_key_info;
			}
			function maybe_change_token_meta( $metas, $response, $order, $gateway, $user_id ) {
				if ( $gateway->id == $this->id ) {
					if ( 'CARD' == pafw_get( $response, 'payment_method_type' ) ) {
						$metas = array(
							'pafw_version'  => PAFW_VERSION,
							'auth_date'     => current_time( 'mysql' ),
							'card_code'     => pafw_get( $response, 'card_code' ),
							'card_name'     => sprintf( __( "카카오페이 - CARD [%s]", "pgall-for-woocommerce" ), pafw_get( $response, 'card_name' ) ),
							'card_num'      => pafw_get( $response, 'card_num' ),
							'register_date' => current_time( 'mysql' )
						);
					} else if ( 'MONEY' == pafw_get( $response, 'payment_method_type' ) ) {
						$metas = array(
							'pafw_version'  => PAFW_VERSION,
							'auth_date'     => current_time( 'mysql' ),
							'card_name'     => __( "카카오페이 - MONEY", "pgall-for-woocommerce" ),
							'register_date' => current_time( 'mysql' )
						);
					} else {
						$metas = array(
							'pafw_version'  => PAFW_VERSION,
							'auth_date'     => current_time( 'mysql' ),
							'card_name'     => __( "카카오페이", "pgall-for-woocommerce" ),
							'register_date' => current_time( 'mysql' )
						);
					}
				}

				return $metas;
			}
		}
	}

}
