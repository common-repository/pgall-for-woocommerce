<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Inicis_Basic' ) ) {
	class PAFW_Settings_Inicis_Basic extends PAFW_Settings_Inicis {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'inicis_stdcard,inicis_stdbank,inicis_stdvbank',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Inicis::get_supported_payment_methods()
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
							'id'        => 'merchant_id',
							'title'     => '상점 아이디',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => 'INIpayTest',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>INIpayTest</code> 입니다.<br>실 결제용 상점 아이디는 <code>CIG</code> 또는 <code>CDM</code>으로 시작해야 합니다. 기존에 발급받은 <code>COD</code> 또는 <code>MOD</code>로 시작하는 상점 아이디도 사용하실 수 있습니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'signkey',
							'title'     => '웹표준 사인키',
							'showLike' => array ( 'merchant_id' => '!CIG,!CIS,!CDM,!CBB' ),
							'className' => 'fluid',
							'default'   => '',
							'desc2'     => __( '<div class="desc2">웹표준 사인키는 결제시 필요한 필수 값으로 이니시스 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '에스크로 결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'inicis_stdescrow_bank' ),
					'showLike' => array ( 'merchant_id' => '!CIG,!CIS,!CDM,!CBB' ),
					'elements' => array (
						array (
							'id'          => 'escrow_merchant_id',
							'title'       => '상점 아이디',
							'className'   => '',
							'placeholder' => '상점 아이디를 선택하세요.',
							'type'        => 'Text',
							'default'     => 'iniescrow0',
						),
						array (
							'id'        => 'escrow_signkey',
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'default'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
							'desc2'     => __( '<div class="desc2">웹표준 사인키는 결제시 필요한 필수 값으로 이니시스 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 iniescrow0 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '정기결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'inicis_subscription' ),
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
							'title'     => '상점 아이디',
							'className' => 'fluid',
							'default'   => 'INIBillTst',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>INIBillTst</code> 입니다.<br>실 결제용 상점 아이디는 <code>CIS</code> 또는 <code>CBB</code>로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'subscription_signkey',
							'showLike'  => array ( 'subscription_merchant_id' => '!CIS,!CBB' ),
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'default'   => '',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 웹표준 사인키는 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code> 입니다.</div>', 'pgall-for-woocommerce' ),
						)
					)
				),
			);
		}
	}
}
