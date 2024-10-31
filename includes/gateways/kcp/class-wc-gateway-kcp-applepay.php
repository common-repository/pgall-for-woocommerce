<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Applepay' ) ) :

	class WC_Gateway_Kcp_Applepay extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_applepay';

			parent::__construct();

			$this->settings['bills_cmd'] = 'card_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '애플페이 결제', 'pgall-for-woocommerce' );
				$this->description = __( '애플페이로 결제합니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
		public function is_available() {
			if ( wp_is_mobile() ) {
				$available = preg_match( "/iPhone|iPad/", pafw_get( $_SERVER, 'HTTP_USER_AGENT' ) );
			} else {
				$user_agent = pafw_get( $_SERVER, 'HTTP_USER_AGENT' );

				$available = ! empty( $user_agent ) && str_contains( $user_agent, 'Macintosh' ) && ! str_contains( $user_agent, 'Chrome' );
			}

			return parent::is_available() && $available;
		}
	}

endif;