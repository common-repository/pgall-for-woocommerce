<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Settlevbank_010' ) ) {

		class WC_Gateway_Settlevbank_010 extends WC_Gateway_Settlevbank {

			public function __construct() {

				$this->id = 'settlevbank_010';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '010가상계좌', 'pgall-for-woocommerce' );
					$this->description = __( '010가상계좌 서비스로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->supports[] = 'refunds';
				$this->supports[] = 'pafw-vbank';
			}

		}
	}

}
