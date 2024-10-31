<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_SettlePG' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_SettlePG extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_settlepg';

			$this->init_settings();

			$this->title              = __( '헥토파이낸셜 전자결제(PG)', 'pgall-for-woocommerce' );
			$this->method_title       = __( '헥토파이낸셜 전자결제(PG)', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">헥토파이낸셜  전자결제(PG) 서비스를 이용합니다.</div>';

			parent::__construct();
		}
		public static function get_supported_payment_methods() {
			return array(
				'settlepg_card'         => __( '신용카드', 'pgall-for-woocommerce' ),
				'settlepg_bank'         => __( '실시간 계좌이체', 'pgall-for-woocommerce' ),
				'settlepg_vbank'        => __( '가상계좌', 'pgall-for-woocommerce' ),
				'settlepg_phone'        => __( '휴대폰', 'pgall-for-woocommerce' ),
				'settlepg_kakaopay'     => __( '카카오페이', 'pgall-for-woocommerce' ),
				'settlepg_npay'         => __( '네이버페이', 'pgall-for-woocommerce' ),
//				'kcp_tosspay'           => __( '토스페이', 'pgall-for-woocommerce' ),
//				'kcp_applepay'          => __( '애플페이', 'pgall-for-woocommerce' ),
				'settlepg_samsungpay'        => __( '삼성페이', 'pgall-for-woocommerce' ),
				'settlepg_payco'             => __( 'PAYCO', 'pgall-for-woocommerce' ),
//				'kcp_ssgpay'            => __( 'SSG Pay', 'pgall-for-woocommerce' ),
//				'kcp_lpay'              => __( 'LPay', 'pgall-for-woocommerce' ),
				'settlepg_subscription' => __( '정기결제', 'pgall-for-woocommerce' ),
			);
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_settlepg' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings = $this->get_settings( 'settlepg', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_settlepg_settings',
				'settings' => $settings
			) );

			?>
            <script>
                jQuery( document ).ready( function( $ ) {
                    $( this ).trigger( 'mshop-setting-manager', [ 'mshop-setting-wrapper', '200', <?php echo json_encode( $this->get_setting_values( $this->id, $settings ) ); ?>, null, null ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php

		}

		protected function get_key() {
			return pafw_get( $_REQUEST, 'merchant_id' );
		}
	}
}