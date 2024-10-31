<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Kicc_Basic' ) ) {
	class PAFW_Settings_Kicc_Basic extends PAFW_Settings_Kicc {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'kicc_card,kicc_bank,kicc_vbank',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Kicc::get_supported_payment_methods()
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '일반 결제 설정',
					'elements' => array (
						array (
							'id'         => 'operation_mode',
							'title'      => '운영 모드',
							'className'  => '',
							'type'       => 'Select',
							'default'    => 'production',
							'allowEmpty' => false,
							'options'    => array (
								'sandbox'    => '개발 환경 (Sandbox)',
								'production' => '운영 환경 (Production)'
							)
						),
						array (
							'id'          => 'test_user_id',
							'title'       => '테스트 사용자 아이디',
							'className'   => 'fluid',
							'placeholder' => '테스트 사용자 아이디를 선택하세요.',
							'showIf'      => array ( 'operation_mode' => 'sandbox' ),
							'type'        => 'Text',
							'default'     => 'pgall_test_user',
							'desc2'       => __( '<div class="desc2">개발 환경 (Sandbox) 모드에서는 관리자 및 테스트 사용자에게만 결제수단이 노출됩니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'          => 'merchant_id',
							'title'       => '상점 아이디',
							'className'   => 'fluid',
							'placeholder' => '상점 아이디를 선택하세요.',
							'type'        => 'Text',
							'desc2'       => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>T5102001</code> 입니다.<br>실 결제용 상점 아이디는 <code>CO</code>으로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '정기결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'kicc_subscription' ),
					'elements' => array (
						array (
							'id'        => 'operation_mode_subscription',
							'title'     => '동작모드',
							'className' => '',
							'type'      => 'Select',
							'default'   => 'sandbox',
							'options'   => array (
								'sandbox'    => '개발 환경 (Sandbox)',
								'production' => '운영 환경 (Production)'
							)
						),
						array (
							'id'          => 'test_user_id_subscription',
							'title'       => '테스트 사용자 아이디',
							'className'   => 'fluid',
							'placeHolder' => '테스트 사용자 아이디를 선택하세요.',
							'showIf'      => array ( 'operation_mode_subscription' => 'sandbox' ),
							'type'        => 'Text',
							'default'     => 'pgall_test_user',
							'desc2'       => __( '<div class="desc2">개발 환경 (Sandbox) 모드에서는 관리자 및 테스트 사용자에게만 결제수단이 노출됩니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'subscription_merchant_id',
							'title'     => '상점아이디',
							'className' => 'fluid',
							'default'   => 'T5102001',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>T5102001</code> 입니다.<br>실 결제용 상점 아이디는 <code>CO</code>로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						)
					)
				),
			);
		}
	}
}
