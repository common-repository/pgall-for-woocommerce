<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Gateway_Inicis_StdHpp' ) ) {
	return;
}

class WC_Gateway_Inicis_StdHpp extends WC_Gateway_Inicis {

	public function __construct() {
		$this->id = 'inicis_stdhpp';

		parent::__construct();

		if ( empty( $this->settings['title'] ) ) {
			$this->title       = __( '휴대폰 소액결제', 'pgall-for-woocommerce' );
			$this->description = __( '휴대폰 소액결제는 14세 미만 미성년자의 경우 사용이 불가능합니다.', 'pgall-for-woocommerce' );
		} else {
			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
		}
	}
	public function get_accept_methods() {
		$accept_methods = parent::get_accept_methods();

		if ( ! wp_is_mobile() ) {
			$accept_methods[] = sprintf( 'HPP(%s)', pafw_get( $this->settings, 'hpp_method', '2' ) );
		}

		return $accept_methods;
	}
	public function process_approval_response( $order, $response ) {
		$order->update_meta_data( "_pafw_hpp_num", $response['phone_number'] );
		$order->save_meta_data();

		$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
			'거래번호' => $response['transaction_id']
		) );
	}
}