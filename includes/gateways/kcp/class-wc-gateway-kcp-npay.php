<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_NPay' ) ) :

	class WC_Gateway_Kcp_NPay extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_npay';

			parent::__construct();

			$this->settings['bills_cmd'] = 'card_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '네이버페이 결제', 'pgall-for-woocommerce' );
				$this->description = __( '네이버페이로 결제합니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
	}

endif;