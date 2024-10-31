<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_KakaoPay_Subscription' ) ) {
	class PAFW_Settings_KakaoPay_Subscription extends PAFW_Settings_KakaoPay {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '카카오페이 정기결제 설정',
					'elements' => array (
						array (
							'id'        => 'kakaopay_subscription_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '카카오페이 정기결제',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => '카카오페이 정기결제로 결제합니다.',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_enable_quota',
							'title'     => __( '할부 지원', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => '',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '신용카드 결제 시 고객이 할부여부를 지정할 수 있습니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_quota',
							'title'     => __( '할부 개월수', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'kakaopay_subscription_enable_quota' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => '',
							'multiple'  => true,
							'options'   => pafw_get_quotas(),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '할부 구매를 허용할 개월수를 선택합니다. 카드사 및 가맹점 정책에 따라 할부 개월수가 제한될 수 있습니다. 할부 구매 미선택시 일시불 결제만 가능합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				),
			);
		}
	}
}
