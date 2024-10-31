<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Settlepg_Vbank' ) ) {
	class PAFW_Settings_Settlepg_Vbank extends PAFW_Settings_SettlePG {
		function get_setting_fields() {

			return array (
				array (
					'type'     => 'Section',
					'title'    => '가상계좌 설정',
					'elements' => array (
						array (
							'id'        => 'settlepg_vbank_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '가상계좌 무통장입금',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'settlepg_vbank_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __( '가상계좌 안내 후 무통장입금을 진행 해 주세요.', 'pgall-for-woocommerce' ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							"id"        => "settlepg_vbank_account_date_limit",
							"title"     => "가상계좌 입금기한",
							"className" => "",
							"type"      => "LabeledInput",
							'inputType' => 'number',
							"leftLabel" => "계좌 발급일로부터",
							"label"     => "일",
							"default"   => "3"
						)
					)
				)
			);
		}
	}
}
