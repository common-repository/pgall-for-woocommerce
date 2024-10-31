<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_SamsungPay' ) ) :

	class WC_Gateway_Kcp_SamsungPay extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_samsungpay';

			parent::__construct();

			$this->settings['bills_cmd'] = 'card_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '삼성페이 결제', 'pgall-for-woocommerce' );
				$this->description = __( '삼성페이로 결제합니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
	}

endif;