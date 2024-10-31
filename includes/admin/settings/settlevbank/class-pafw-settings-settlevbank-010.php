<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Settlevbank_010' ) ) {
	class PAFW_Settings_Settlevbank_010 extends PAFW_Settings_Settlevbank {
		function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => '내통장결제 설정',
					'elements' => array(
						array(
							'id'        => 'settlevbank_010_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '010가상계좌',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'settlevbank_010_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => '010가상계좌 서비스로 결제합니다.',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							"id"        => "settlevbank_010_account_date_limit",
							"title"     => "가상계좌 입금기한",
							"className" => "",
							"type"      => "LabeledInput",
							'inputType' => 'number',
							"leftLabel" => "계좌 발급일로부터",
							"label"     => "시간",
							"default"   => "72",
							"desc2"     => __( '<div class="desc2">입금기한이 24시간 이상인 경우, 일단위로 입금기한이 설정됩니다. 예를 들어, 입금 기한이 72시간인 경우, 3일 후 23:59분으로 설정됩니다.</div>', 'pgall-for-woocommerce' )
						),
					)
				),
			);
		}
	}
}
