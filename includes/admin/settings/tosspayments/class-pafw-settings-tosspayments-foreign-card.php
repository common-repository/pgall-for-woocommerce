<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_TossPayments_Foreign_Card' ) ) {

	class PAFW_Settings_TossPayments_Foreign_Card extends PAFW_Settings_TossPayments_Card {
		function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => '신용카드 설정',
					'elements' => array(
						array(
							'id'        => 'tosspayments_foreign_card_title',
							'title'     => __( '결제수단 이름', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => __( '해외카드', 'pgall-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'tosspayments_foreign_card_description',
							'title'     => __( '결제수단 설명', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __( '해외카드(Visa, MasterCard, JCB, UnionPay, AMEX)로 결제합니다.', 'pgall-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'tosspayments_foreign_card_max_installment_plan',
							'title'     => __( '최대 할부 개월', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => '0',
							'options'   => $this->get_installment_plan(),
							'desc2'     => __( '<div class="desc2">카드 결제에서 선택할 수 있는 최대 할부 개월 수를 제한합니다. 결제 금액이 5만원 이상일 때만 사용할 수 있습니다. <br>만약 값을 6개월로 선택하시면 결제창에서 일시불~6개월 사이로 할부 개월을 선택할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '카드 결제에서 선택할 수 있는 최대 할부 개월 수를 제한합니다. 결제 금액(amount)이 5만원 이상일 때만 사용할 수 있습니다. 2부터 12사이의 값을 사용할 수 있고, 0이 들어가면 할부가 아닌 일시불로 결제됩니다. 만약 값을 6으로 설정한다면 결제창에서 일시불~6개월 사이로 할부 개월을 선택할 수 있습니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'tosspayments_foreign_card_app_scheme',
							'title'     => '앱 스킴',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '',
							'desc2'     => __( '<div class="desc2">페이북/ISP 앱에서 상점 앱으로 돌아올 때 사용됩니다. 상점의 앱 스킴을 지정하면 됩니다</div>', 'pgall-for-woocommerce' ),
						),
					)
				),
				array(
					'type'     => 'Section',
					'title'    => '신용카드 고급 설정',
					'elements' => array(
						array(
							'id'        => 'tosspayments_foreign_card_use_advanced_setting',
							'title'     => '사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array(
								'title' => array(
									'content' => __( '고급 설정 사용 시, 기본 설정에 우선합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'tosspayments_foreign_card_order_status_after_payment',
							'title'     => __( '결제완료시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'showIf'    => array( 'tosspayments_foreign_card_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'processing',
							'options'   => $this->filter_order_statuses( array(
								'cancelled',
								'failed',
								'on-hold',
								'refunded'
							) ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '신용카드 결제건에 한해서, 결제(입금)이 완료되면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array(
							'id'        => 'tosspayments_foreign_card_possible_refund_status_for_mypage',
							'title'     => __( '구매자 주문취소 가능상태', 'pgall-for-woocommerce' ),
							'showIf'    => array( 'tosspayments_foreign_card_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending,on-hold',
							'multiple'  => true,
							'options'   => $this->get_order_statuses(),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '신용카드 결제건에 한해서, 구매자가 내계정 페이지에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				)
			);
		}
	}
}