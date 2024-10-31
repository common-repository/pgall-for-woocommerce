<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Subscription' ) ) {

		class WC_Gateway_Lguplus_Subscription extends WC_Gateway_Lguplus {
			public function __construct() {
				$this->id = 'lguplus_subscription';

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

				add_action( 'pafw_bill_key_response_' . $this->id, array( $this, 'maybe_complete_subscription_payment' ), 10, 2 );
			}
			function maybe_complete_subscription_payment( $order, $response ) {
				$order->update_meta_data( '_pafw_payment_method', $this->id );
				$order->update_meta_data( '_pafw_paid_date', pafw_get( $response, 'paid_date' ) );
				$order->update_meta_data( '_pafw_card_code', pafw_get( $response, 'card_code' ) );
				$order->update_meta_data( '_pafw_card_name', pafw_get( $response, 'card_name' ) );
				$order->update_meta_data( '_pafw_card_num', pafw_get( $response, 'card_num' ) );
				$order->update_meta_data( '_pafw_card_quota', pafw_get( $response, 'card_quota' ) );
				$order->update_meta_data( '_pafw_total_price', pafw_get( $response, 'total_price' ) );
				$order->save_meta_data();

				$this->add_payment_log( $order, '[ 정기결제 성공 ]', array(
					'거래번호' => $response['transaction_id']
				) );

				$this->payment_complete( $order, $response['transaction_id'] );

				do_action( 'pafw_subscription_payment_completed', $response, $order, $this );
			}

			function issue_bill_key_mode() {
				return 'api';
			}

			function adjust_settings() {
				$this->settings['merchant_id']    = $this->settings['subscription_merchant_id'];
				$this->settings['merchant_key']   = $this->settings['subscription_merchant_key'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
			}

			public function get_subscription_meta_key( $meta_key ) {
				return '_pafw_lguplus_' . $meta_key;
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
					'card_type'    => $this->get_card_param( $post_params, 'card_type' ),
					'charge_amount' => pafw_is_subscription( $order ) ? 0 : $order->get_total()
				);

				$params['order'] = array_merge( $params['order'], array(
					'amount'          => pafw_is_subscription( $order ) ? 0 : $order->get_total(),
					'tax_amount'      => pafw_is_subscription( $order ) ? 0 : PAFW_Tax::get_tax_amount( $order ),
					'tax_free_amount' => pafw_is_subscription( $order ) ? 0 : PAFW_Tax::get_tax_free_amount( $order ),
					'vat'             => pafw_is_subscription( $order ) ? 0 : PAFW_Tax::get_total_tax( $order ),
					'items'           => $this->get_order_items( $order ),
				) );

				return $params;
			}

			public function payment_fields() {
				if ( $this->is_available() ) {
					ob_start();
					wc_get_template( 'pafw/lguplus/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
					ob_end_flush();
				}
			}
			function process_payment( $order_id ) {
				return $this->process_key_in_subscription_payment( $order_id );
			}

			public function subscription_payment_info() {
				$bill_key = get_user_meta( get_current_user_id(), $this->get_subscription_meta_key( 'bill_key' ), true );

				ob_start();

				wc_get_template( 'pafw/lguplus/card-info.php', array( 'payment_gateway' => $this, 'bill_key' => $bill_key ), '', PAFW()->template_path() );

				return ob_get_clean();
			}
			function add_payment_method() {
				try {
					$user = get_currentuserinfo();

					PAFW_Gateway::register_complete( $user, $this );
					wc_add_notice( __( "결제 수단이 정상적으로 등록되었습니다.", "pgall-for-woocommerce" ) );
					wp_safe_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
				} catch ( Exception $e ) {
					wc_add_notice( $e->getMessage(), 'error' );
					wp_safe_redirect( wc_get_account_endpoint_url( 'add-payment-method' ) );
				}

				die();
			}
		}

	}
}
