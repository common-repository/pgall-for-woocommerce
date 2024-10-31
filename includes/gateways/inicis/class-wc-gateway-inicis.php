<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis' ) ) {
	return;
}

class WC_Gateway_Inicis extends PAFW_Payment_Gateway {

	protected $key_for_test = array(
		'INIpayTest',
		'INIBillTst',
		'iniescrow0',
		'testcodbil'
	);

	public function __construct() {
		$this->master_id = 'inicis';

		$this->view_transaction_url = 'https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s';

		$this->pg_title     = __( 'KG 이니시스', 'pgall-for-woocommerce' );
		$this->method_title = __( 'KG 이니시스', 'pgall-for-woocommerce' );

		parent::__construct();

		if ( 'yes' == $this->enabled ) {
			add_filter( 'pafw_register_order_params_' . $this->id, array( $this, 'add_register_order_request_params' ), 10, 2 );
			add_action( 'pafw_approval_response_' . $this->id, array( $this, 'process_approval_response' ), 10, 2 );
		}

		add_filter( 'pafw_cash_receipt_params_' . $this->id, array( $this, 'add_cash_receipt_request_params' ), 10, 2 );
	}
	public function get_merchant_id() {
		return pafw_get( $this->settings, 'merchant_id' );
	}
	public function get_merchant_key_prefix() {
		return substr( $this->get_merchant_id(), 0, 3 );
	}
	public function use_integrated_sign_key() {
		return in_array( $this->get_merchant_key_prefix(), array( 'CIG', 'CIS', 'CDM', 'CBB' ) );
	}
	public function get_merchant_key() {
		if( $this->use_integrated_sign_key() ) {
			return '';
		}

		return pafw_get( $this->settings, 'signkey' );
	}
	public function add_register_order_request_params( $params, $order ) {
		$accept_methods = apply_filters( 'pafw_inicis_accept_methods', $this->get_accept_methods(), $order, $this );

		$params['inicis'] = array(
			'wpml_lang'        => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '',
			'language_code'    => pafw_get( $this->settings, 'language_code', 'ko' ),
			'accept_method'    => wp_is_mobile() ? implode( '&', $accept_methods ) : implode( ':', $accept_methods ),
			'logo_url'         => utf8_uri_encode( pafw_get( $this->settings, 'site_logo', PAFW()->plugin_url() . '/assets/images/default-logo.jpg' ) ),
			'quotabase'        => pafw_get( $this->settings, 'quotabase' ),
			'use_nointerest'   => pafw_get( $this->settings, 'use_nointerest' ),
			'nointerest'       => pafw_get( $this->settings, 'nointerest' ),
			'vbank_date_limit' => pafw_get( $this->settings, 'account_date_limit', 3 ),
			'hpp_method'       => pafw_get( $this->settings, 'hpp_method', '2' )
		);

		return $params;
	}
	public function add_cash_receipt_request_params( $params, $order ) {
		$params['inicis'] = array(
			'reg_num'      => preg_replace( '~\D~', '', $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ),
			'receipt_type' => 'ID' == $order->get_meta( '_pafw_bacs_receipt_usage' ) ? '0' : '1'
		);

		return $params;
	}
	function get_accept_methods() {
		if ( wp_is_mobile() ) {
			return array(
				'ismart_use_sign=Y',
			);
		} else {
			return array(
				'SKIN(' . pafw_get( $this->settings, 'skin_indx', '#c1272c' ) . ')',
				'popreturn',
			);
		}
	}
	function is_fully_refundable( $order, $screen = 'admin' ) {
		$repay_info = json_decode( $order->get_meta( '_inicis_repay' ), true );

		$repay_cnt = is_array( $repay_info ) ? count( $repay_info ) : 0;

		return parent::is_fully_refundable( $order, $screen ) && $repay_cnt == 0;
	}

	public function get_receipt_popup_params() {
		return array(
			'name'     => 'showreceipt',
			'features' => 'width=410,height=540, scrollbars=no,resizable=no'
		);
	}
}