<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Inicis_Subscription' ) ) {
	class PAFW_Settings_Inicis_Subscription extends PAFW_Settings_Inicis {
		function get_quotabase() {
			$quotabase = array();
			for ( $i = 2; $i < 37; $i++ ) {
				$quotabase[ $i ] = $i . '개월';
			}

			return $quotabase;
		}
		function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => '신용카드 설정',
					'elements' => array(
						array(
							'id'        => 'inicis_subscription_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '신용카드 정기결제',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'inicis_subscription_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => "신용카드 결제를 진행 합니다.",
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						)
					)
				)
			);
		}
	}
}