<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_TossPayments_Subscription' ) ) {

		class WC_Gateway_TossPayments_Subscription extends WC_Gateway_TossPayments {
			public function __construct() {
				$this->id = 'tosspayments_subscription';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '신용카드 정기결제', 'pgall-for-woocommerce' );
					$this->description = __( '신용카드 정기결제를 진행합니다.', 'pgall-for-woocommerce' );
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
					'add_payment_method',
				);
				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'woocommerce_scheduled_subscription_payment' ), 10, 2 );

				add_filter( 'pafw_subscription_register_complete_params_' . $this->id, array( $this, 'add_subscription_register_complete_params' ), 10, 2 );

				add_filter( 'pafw_bill_key_params_' . $this->id, array( $this, 'add_bill_key_request_params' ), 10, 2 );
				add_filter( 'pafw_subscription_payment_params_' . $this->id, array( $this, 'add_subscription_payment_request_params' ), 10, 2 );

				add_action( 'pafw_subscription_payment_response_' . $this->id, array( $this, 'process_subscription_payment_response' ), 10, 2 );
				add_filter( 'pafw_subscription_register_form_params_' . $this->id, array( $this, 'add_subscription_register_form_request_params' ), 10, 2 );
				add_filter( 'pafw_subscription_register_complete_params_' . $this->id, array( $this, 'add_bill_key_request_params' ), 10, 2 );
				add_action( 'pafw_' . $this->id . '_register', array( $this, 'wc_api_request_register' ) );
			}

			public function payment_fields() {
				if ( $this->is_available() ) {
					ob_start();
					wc_get_template( 'pafw/kakaopay/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
					ob_end_flush();
				}
			}
			public function add_subscription_payment_request_params( $params, $order ) {
				$params[ $this->get_master_id() ] = array(
					'secret_key' => pafw_get( $this->settings, 'secret_key' ),
				);

				return $params;
			}
			public function process_subscription_payment_response( $order, $response ) {
				$order->update_meta_data( '_pafw_receipt_url', pafw_get( $response, 'receipt_url' ) );
				$order->save_meta_data();
			}
			function adjust_settings() {
				$this->settings['merchant_id']    = $this->settings['subscription_merchant_id'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['client_key']     = $this->settings['subscription_client_key'];
				$this->settings['secret_key']     = $this->settings['subscription_secret_key'];
			}

			public function get_subscription_meta_key( $meta_key ) {
				return '_pafw_tosspayments_' . $meta_key;
			}
			public function add_subscription_register_complete_params( $params, $order ) {
				$user_id      = 0;
				$payment_info = array();

				parse_str( $_POST['params'], $payment_info ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				$payment_info = apply_filters( 'pafw_subscription_payment_info', $payment_info, $this );

				if ( is_a( $order, 'WC_Order' ) ) {
					$user_id = $order->get_customer_id();
				} else if ( is_a( $order, 'WP_User' ) ) {
					$user_id = $order->ID;
				}

				$params[ $this->get_master_id() ] = array(
					'secret_key'   => pafw_get( $this->settings, 'secret_key' ),
					'order_id'     => 'PAFW-BILL-' . strtoupper( bin2hex( openssl_random_pseudo_bytes( 6 ) ) ),
					'card_no'      => $this->get_card_param( $payment_info, 'card_no' ),
					'expiry_year'  => $this->get_card_param( $payment_info, 'expiry_year' ),
					'expiry_month' => $this->get_card_param( $payment_info, 'expiry_month' ),
					'cert_no'      => $this->get_card_param( $payment_info, 'cert_no' ),
					'password'     => $this->get_card_param( $payment_info, 'card_pw' ),
					'card_type'    => $this->get_card_param( $payment_info, 'card_type' ),
					'user_id'      => $user_id
				);

				return $params;
			}
			public function add_bill_key_request_params( $params, $order ) {
				$params['order'] = array_merge( array(
					'transaction_id' => wc_clean( pafw_get( $_GET, 'transaction_id' ) ),
					'auth_token'     => wc_clean( pafw_get( $_GET, 'auth_token' ) ),
					'secret_key'     => pafw_get( $this->settings, 'secret_key' ),
				) );

				return $params;
			}
			public function add_subscription_register_form_request_params( $params, $user ) {
				$params[ $this->get_master_id() ] = array(
					'client_key' => pafw_get( $this->settings, 'client_key' ),
				);

				return $params;
			}
			function wc_api_request_payment() {
				try {
					$order = null;

					if ( empty( $_GET['transaction_id'] ) || empty( $_GET['auth_token'] ) || empty( $_GET['order_id'] ) ) {
						throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9000' );
					}

					$order = $this->get_order( wc_clean( $_GET['order_id'] ) );

					$this->validate_order_status( $order );

					$token = PAFW_Gateway::issue_bill_key( $order, $this );

					if ( ! pafw_is_subscription( $order ) ) {
						if ( $order->get_total() > 0 ) {
							PAFW_Gateway::request_subscription_payment( $order, $this, array( 'card_quota' => $order->get_meta( '_pafw_card_quota' ) ), $token );
						} else {
							$order->payment_complete();
						}
					}

					PAFW_Gateway::redirect( $order, $this );
				} catch ( Exception $e ) {
					$this->handle_exception( $e, $order );
				}
			}
			function wc_api_request_register() {
				try {
					$user = null;

					if ( empty( $_GET['transaction_id'] ) || empty( $_GET['auth_token'] ) || empty( $_GET['customer_key'] ) ) {
						throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '9000' );
					}

					$user_id = str_replace( $this->get_merchant_id() . '_', '', wc_clean( $_GET['customer_key'] ) );

					$user = get_userdata( $user_id );

					PAFW_Gateway::register_complete( $user, $this );

					PAFW_Gateway::redirect( $user, $this );
				} catch ( Exception $e ) {
					wc_add_notice( $e->getMessage(), 'error' );
					PAFW_Gateway::redirect( $user, $this );
				}
			}
			function process_payment( $order_id ) {
				return $this->process_auth_subscription_payment( $order_id );
			}

			public function subscription_payment_info() {
				$bill_key = get_user_meta( get_current_user_id(), $this->get_subscription_meta_key( 'bill_key' ), true );

				ob_start();

				wc_get_template( 'pafw/tosspayments/card-info.php', array( 'payment_gateway' => $this, 'bill_key' => $bill_key ), '', PAFW()->template_path() );

				return ob_get_clean();
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
		}

	}
}
