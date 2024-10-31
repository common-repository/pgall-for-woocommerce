<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_KICC' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_KICC extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_kicc';

			$this->init_settings();

			$this->title              = __( 'KICC', 'pgall-for-woocommerce' );
			$this->method_title       = __( 'KICC', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">KICC 이지페이 일반결제 및 정기결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로, 정기결제)</div>';

			parent::__construct();
		}
		public static function get_supported_payment_methods() {
			return array (
				'kicc_card'         => '신용카드',
				'kicc_bank'         => '실시간 계좌이체',
				'kicc_vbank'        => '가상계좌',
				'kicc_escrow_vbank' => '에스크로',
				'kicc_subscription' => '신용카드 정기결제',
			);
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_kicc' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings = $this->get_settings( 'kicc', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_kicc_settings',
				'settings' => $settings
			) );

			?>
            <script>
                jQuery( document ).ready( function ( $ ) {
                    $( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '200', <?php echo json_encode( $this->get_setting_values( $this->id, $settings ) ); ?>, null, null] );
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