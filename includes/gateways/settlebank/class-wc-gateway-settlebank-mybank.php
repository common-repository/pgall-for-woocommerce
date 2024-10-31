<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Settlebank_Mybank' ) ) {

		class WC_Gateway_Settlebank_Mybank extends WC_Gateway_Settlebank{

			public function __construct() {

				$this->id = 'settlebank_mybank';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '내통장결제', 'pgall-for-woocommerce' );
					$this->description = __( '내통장결제 서비스로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->supports[] = 'refunds';
			}
		}
	}

}
