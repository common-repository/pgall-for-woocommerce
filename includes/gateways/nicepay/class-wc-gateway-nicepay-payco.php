<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Nicepay_Payco' ) ) :

	class WC_Gateway_Nicepay_Payco extends WC_Gateway_Nicepay {

		public function __construct() {
			$this->id = 'nicepay_payco';

			parent::__construct();

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( 'PAYCO 결제', 'pgall-for-woocommerce' );
				$this->description = __( 'PAYCO로 결제합니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
	}

endif;