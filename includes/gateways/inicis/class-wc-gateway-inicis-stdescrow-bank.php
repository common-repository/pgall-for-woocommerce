<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis_StdEscrow_Bank' ) ) {
	return;
}

class WC_Gateway_Inicis_StdEscrow_Bank extends WC_Gateway_Inicis {

	public function __construct() {

		$this->id = 'inicis_stdescrow_bank';

		parent::__construct();

		if ( empty( $this->settings['title'] ) ) {
			$this->title       = __( '에스크로', 'pgall-for-woocommerce' );
			$this->description = __( '이니시스 결제대행사를 통해 결제합니다. 에스크로 결제의 경우 인터넷익스플로러(IE) 환경이 아닌 경우 사용이 불가능합니다. 결제 완료시 내 계정(My-Account)에서 주문을 확인하여 주시기 바랍니다.', 'pgall-for-woocommerce' );
		} else {
			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
		}
		$this->supports[] = 'pafw-cash-receipt';
		$this->supports[] = 'pafw-escrow';
		$this->supports[] = 'pafw-escrow-support-modify-delivery-info';

		add_filter( 'pafw_register_shipping_params_' . $this->id, array( $this, 'add_register_shipping_params' ), 10, 2 );
	}
	public function get_accept_methods() {
		$merchant_id = parent::get_merchant_id();

		$accept_methods = parent::get_accept_methods();

		if ( wp_is_mobile() ) {
			if ( 'no' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'bank_receipt=N';
			}

			if ( $this->use_integrated_sign_key() ) {
				$accept_methods[] = 'useescrow=Y';
			}
		} else {
			if ( 'no' == pafw_get( $this->settings, 'receipt', 'no' ) ) {
				$accept_methods[] = 'no_receipt';
			}

			if ( $this->use_integrated_sign_key() ) {
				$accept_methods[] = 'useescrow';
			}
		}

		return $accept_methods;
	}

	public function get_merchant_id() {
		$merchant_id = parent::get_merchant_id();

		$prefix = substr( $merchant_id, 0, 3 );

		if ( in_array( $prefix, array( 'CIG', 'CIS', 'CDM', 'CBB' ) )) {
			return $merchant_id;
		} else {
			return pafw_get( $this->settings, 'escrow_merchant_id' );
		}
	}

	public function get_merchant_key() {
		if( $this->use_integrated_sign_key() ) {
			return '';
		}

		return pafw_get( $this->settings, 'escrow_signkey' );
	}

	public static function get_dlv_company_list() {
		return array(
			'9999'       => __( '기타택배', 'pgall-for-woocommerce' ),
			'korex'      => __( 'CJ대한통운', 'pgall-for-woocommerce' ),
			'kgbps'      => __( 'KGB택배', 'pgall-for-woocommerce' ),
			'registpost' => __( '우편등기', 'pgall-for-woocommerce' ),
			'hanjin'     => __( '한진택배', 'pgall-for-woocommerce' ),
			'chunil'     => __( '천일택배', 'pgall-for-woocommerce' ),
			'ilyang'     => __( '일양로지스', 'pgall-for-woocommerce' ),
			'cvsnet'     => __( '편의점택배', 'pgall-for-woocommerce' ),
			'kgb'        => __( '로젠택배', 'pgall-for-woocommerce' ),
			'hyundai'    => __( '롯데택배(구.현대)', 'pgall-for-woocommerce' ),
			'EPOST'      => __( '우체국택배', 'pgall-for-woocommerce' ),
			'cjgls'      => __( 'CJ GLS', 'pgall-for-woocommerce' ),
			'kdexp'      => __( '경동택배', 'pgall-for-woocommerce' ),
			'daesin'     => __( '대신택배', 'pgall-for-woocommerce' ),
			'honam'      => __( '호남택배', 'pgall-for-woocommerce' ),
			'hdexp'      => __( '합동택배', 'pgall-for-woocommerce' ),
		);
	}

	function get_escrow_company_name() {
		$dlv_company_list = self::get_dlv_company_list();

		return sprintf( '[%s] %s', pafw_get( $this->settings, 'delivery_company_code' ), $dlv_company_list[ pafw_get( $this->settings, 'delivery_company_code' ) ] );
	}
	public function add_register_shipping_params( $params, $order ) {
		$dlv_company_list = self::get_dlv_company_list();

		$params['inicis'] = array(
			'escrow_type'      => pafw_get( $_REQUEST, 'escrow_type' ),
			'sheet_no'         => pafw_get( $_REQUEST, 'tracking_number' ),
			'dlv_company_code' => pafw_get( $this->settings, 'delivery_company_code' ),
			'dlv_company_name' => $dlv_company_list[ pafw_get( $this->settings, 'delivery_company_code' ) ],
			'sender_name'      => pafw_get( $this->settings, 'delivery_register_name' ),
			'sender_postcode'  => pafw_get( $this->settings, 'delivery_sender_postnum' ),
			'sender_address'   => pafw_get( $this->settings, 'delivery_sender_addr1' ),
			'sender_phone'     => pafw_get( $this->settings, 'delivery_sender_phone' ),
		);

		return $params;
	}
	public function process_approval_response( $order, $response ) {
		$order->update_meta_data( "_pafw_bank_code", $response['bank_code'] );
		$order->update_meta_data( "_pafw_bank_name", $response['bank_name'] );
		$order->update_meta_data( "_pafw_cash_receipts", $response['cash_receipts'] );
		$order->save_meta_data();

		$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array(
			'거래번호' => $response['transaction_id']
		) );
	}

	function is_fully_refundable( $order, $screen = 'admin' ) {
		$register_delivery_info = $order->get_meta( '_pafw_escrow_register_delivery_info' );

		return parent::is_fully_refundable( $order, $screen ) && 'yes' != $register_delivery_info;
	}
}