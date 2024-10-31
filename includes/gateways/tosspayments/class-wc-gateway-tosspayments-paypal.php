<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_TossPayments_Paypal' ) ) {

		class WC_Gateway_TossPayments_Paypal extends WC_Gateway_TossPayments {

			public function __construct() {
				$this->id = 'tosspayments_paypal';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '페이팔(Paypal)', 'pgall-for-woocommerce' );
					$this->description = __( '계좌에서 바로 결제하는 실시간 계좌이체 입니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->supports[] = 'pafw-cash-receipt';
			}
			public function is_available() {
				if ( current_user_can( 'manage_options' ) || in_array( get_woocommerce_currency(), apply_filters( 'pafw_tosspayments_paypal_supported_currencies', array( 'KRW', 'USD' ) ) ) ) {
					$is_available = ( 'yes' === $this->enabled );

					if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
						$is_available = false;
					}

					return $is_available;
				} else {
					return false;
				}
			}
			public function add_register_order_request_params( $params, $order ) {
				$params = parent::add_register_order_request_params( $params, $order );

				if( 'billing_only' == get_option( 'woocommerce_ship_to_destination' ) ) {
					$params[ $this->get_master_id() ] = array_merge( $params[ $this->get_master_id() ], array(
						'currency'        => $order->get_currency(),
						'country'         => $order->get_billing_country(),
						'postcode'        => $order->get_billing_postcode(),
						'city'            => $order->get_billing_city(),
						'state'           => $order->get_billing_state(),
						'address_1'       => $order->get_billing_address_1(),
						'address_2'       => $order->get_billing_address_2(),
						'billing_country' => $order->get_billing_country(),
					) );
				}else{
					$params[ $this->get_master_id() ] = array_merge( $params[ $this->get_master_id() ], array(
						'currency'        => $order->get_currency(),
						'country'         => $order->get_shipping_country(),
						'postcode'        => $order->get_shipping_postcode(),
						'city'            => $order->get_shipping_city(),
						'state'           => $order->get_shipping_state(),
						'address_1'       => $order->get_shipping_address_1(),
						'address_2'       => $order->get_shipping_address_2(),
						'billing_country' => $order->get_billing_country(),
					) );
				}

				return $params;
			}
			public function process_approval_response( $order, $response ) {
				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
					'거래번호' => $response['transaction_id']
				) );
			}
			function adjust_settings() {
				$this->settings['merchant_id'] = $this->settings['paypal_merchant_id'];
				$this->settings['client_key']  = $this->settings['paypal_client_key'];
				$this->settings['secret_key']  = $this->settings['paypal_secret_key'];
			}

		}
	}

} // class_exists function end
