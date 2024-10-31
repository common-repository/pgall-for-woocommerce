<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Subscription' ) ) {

		class WC_Gateway_Nicepay_Subscription extends WC_Gateway_Nicepay {
			public function __construct() {
				$this->id = 'nicepay_subscription';

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
					'pafw_cancel_bill_key',
					'pafw_key_in_payment',
					'add_payment_method',
				);

				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'woocommerce_scheduled_subscription_payment' ), 10, 2 );

				add_filter( 'pafw_subscription_register_complete_params_' . $this->id, array( $this, 'add_subscription_register_complete_params' ), 10, 2 );
				add_filter( 'pafw_bill_key_params_' . $this->id, array( $this, 'add_bill_key_request_params' ), 10, 2 );
			}

			function issue_bill_key_mode() {
				return 'api';
			}

			function adjust_settings() {
				$this->settings['merchant_id']    = $this->settings['subscription_merchant_id'];
				$this->settings['merchant_key']   = $this->settings['subscription_merchant_key'];
				$this->settings['cancel_pw']      = $this->settings['subscription_cancel_pw'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['test_user_id']   = $this->settings['test_user_id_subscription'];
			}
			public function add_subscription_register_complete_params( $params, $order ) {
				$params[ $this->get_master_id() ] = array(
					'order_id'     => 'PAFW-BILL-' . strtoupper( bin2hex( openssl_random_pseudo_bytes( 6 ) ) ),
					'card_no'      => $this->get_card_param( $_POST, 'card_no' ),
					'expiry_year'  => $this->get_card_param( $_POST, 'expiry_year' ),
					'expiry_month' => $this->get_card_param( $_POST, 'expiry_month' ),
					'cert_no'      => $this->get_card_param( $_POST, 'cert_no' ),
					'password'     => $this->get_card_param( $_POST, 'card_pw' ),
					'card_type'    => $this->get_card_param( $_POST, 'card_type' )
				);

				return $params;
			}
			public function add_bill_key_request_params( $params, $order ) {
				if ( 'process_order_pay' == pafw_get( $_POST, 'payment_action' ) && ! empty( pafw_get( $_POST, 'data' ) ) ) {
					$post_params = array();
					parse_str( pafw_get( $_POST, 'data' ), $post_params );
				} else {
					$post_params = wc_clean( $_POST );
				}

				$post_params = apply_filters( 'pafw_subscription_payment_info', $post_params, $this );

				$params[ $this->get_master_id() ] = array(
					'card_quota'   => $this->get_card_param( $post_params, 'card_quota', '00' ),
					'card_no'      => $this->get_card_param( $post_params, 'card_no' ),
					'expiry_year'  => $this->get_card_param( $post_params, 'expiry_year' ),
					'expiry_month' => $this->get_card_param( $post_params, 'expiry_month' ),
					'cert_no'      => $this->get_card_param( $post_params, 'cert_no' ),
					'password'     => $this->get_card_param( $post_params, 'card_pw' ),
					'card_type'    => $this->get_card_param( $post_params, 'card_type' )
				);

				return $params;
			}
			public function payment_fields() {
				if ( $this->is_available() ) {
					ob_start();
					wc_get_template( 'pafw/nicepay/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
					ob_end_flush();
				}
			}
			function process_payment( $order_id ) {
				return $this->process_key_in_subscription_payment( $order_id );
			}

			public function subscription_payment_info() {
				$bill_key = get_user_meta( get_current_user_id(), $this->get_subscription_meta_key( 'bill_key' ), true );

				ob_start();

				wc_get_template( 'pafw/nicepay/card-info.php', array( 'payment_gateway' => $this, 'bill_key' => $bill_key ), '', PAFW()->template_path() );

				return ob_get_clean();
			}
			function add_payment_method() {
				try {
					$user = get_currentuserinfo();

					PAFW_Gateway::register_complete( $user, $this );

					if ( is_ajax() ) {
						wp_send_json_success();
					} else {
						wc_add_notice( __( "결제 수단이 정상적으로 등록되었습니다.", "pgall-for-woocommerce" ) );
						wp_safe_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
					}
				} catch ( Exception $e ) {
					if ( is_ajax() ) {
						wp_send_json_error( $e->getMessage() );
					} else {
						wc_add_notice( $e->getMessage(), 'error' );
						wp_safe_redirect( wc_get_account_endpoint_url( 'add-payment-method' ) );
					}
				}

				die();
			}
		}

	}
}
