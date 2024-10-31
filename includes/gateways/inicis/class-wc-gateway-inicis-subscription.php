<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis_Subscription' ) ) {
	return;
}

class WC_Gateway_Inicis_Subscription extends WC_Gateway_Inicis {
	public function __construct() {
		$this->id = 'inicis_subscription';

		parent::__construct();

		if ( empty( $this->settings['title'] ) ) {
			$this->title       = __( '신용카드 정기결제', 'pgall-for-woocommerce' );
			$this->description = __( '신용카드 결제를 진행 합니다.', 'pgall-for-woocommerce' );
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

		add_filter( 'pafw_subscription_register_form_params_' . $this->id, array( $this, 'add_subscription_register_form_request_params' ), 10, 2 );

		add_action( 'pafw_' . $this->id . '_register', array( $this, 'wc_api_request_register' ) );
	}

	public function get_merchant_id() {
		return pafw_get( $this->settings, 'subscription_merchant_id' );
	}

	public function get_merchant_key() {
		if( $this->use_integrated_sign_key() ) {
			return '';
		}

		return pafw_get( $this->settings, 'subscription_signkey' );
	}

	public function get_subscription_meta_key( $meta_key ) {
		return '_pafw_inicis_' . $meta_key;
	}

	public function payment_fields() {
		if ( $this->is_available() ) {
			ob_start();
			wc_get_template( 'pafw/inicis/form-payment-fields.php', array( 'gateway' => $this ), '', PAFW()->template_path() );
			ob_end_flush();
		}
	}
	public function get_accept_methods() {
		$accept_methods = parent::get_accept_methods();

		if ( ! wp_is_mobile() ) {
			$accept_methods[] = 'BILLAUTH(card)';
		}
		$accept_methods[] = 'below1000';

		return $accept_methods;
	}
	public function add_subscription_register_form_request_params( $params, $user ) {
		$order_id = 'PAFW-BILL-' . strtoupper( bin2hex( openssl_random_pseudo_bytes( 6 ) ) );

		$accept_methods = apply_filters( 'pafw_inicis_accept_methods', $this->get_accept_methods(), $user, $this );

		$params['inicis'] = array(
			'api_url'       => untrailingslashit( WC()->api_request_url( get_class( $this ), pafw_check_ssl() ) ),
			'wpml_lang'     => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '',
			'language_code' => pafw_get( $this->settings, 'language_code', 'ko' ),
			'accept_method' => wp_is_mobile() ? implode( '&', $accept_methods ) : implode( ':', $accept_methods ),
			'logo_url'      => utf8_uri_encode( pafw_get( $this->settings, 'site_logo', PAFW()->plugin_url() . '/assets/images/default-logo.jpg' ) ),
			'order_id'      => $order_id,
			'txnid'         => $order_id,
		);

		return $params;
	}
	function process_payment( $order_id ) {
		return $this->process_auth_subscription_payment( $order_id );
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
}