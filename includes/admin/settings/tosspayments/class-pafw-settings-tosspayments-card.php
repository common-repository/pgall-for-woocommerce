<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_TossPayments_Card' ) ) {

	class PAFW_Settings_TossPayments_Card extends PAFW_Settings_TossPayments {
		function get_installment_plan() {
			$quotabase = array(
				0 => __( '제한없음', 'pgall-for-woocommerce' )
			);

			for ( $i = 2; $i <= 12; $i ++ ) {
				$quotabase[ $i ] = $i . '개월';
			}

			return $quotabase;
		}

		function get_card_company() {
			return array(
				'3K' => __( '기업BC', 'pgall-for-woocommerce' ),
				'46' => __( '광주은행', 'pgall-for-woocommerce' ),
				'71' => __( '롯데카드', 'pgall-for-woocommerce' ),
				'30' => __( 'KDB산업은행', 'pgall-for-woocommerce' ),
				'31' => __( 'BC카드', 'pgall-for-woocommerce' ),
				'51' => __( '삼성카드', 'pgall-for-woocommerce' ),
				'38' => __( '새마을금고', 'pgall-for-woocommerce' ),
				'41' => __( '신한카드', 'pgall-for-woocommerce' ),
				'62' => __( '신협', 'pgall-for-woocommerce' ),
				'36' => __( '씨티카드', 'pgall-for-woocommerce' ),
				'33' => __( '우리BC카드(BC 매입)', 'pgall-for-woocommerce' ),
				'W1' => __( '우리카드(우리 매입)', 'pgall-for-woocommerce' ),
				'37' => __( '우체국예금보험', 'pgall-for-woocommerce' ),
				'39' => __( '저축은행중앙회', 'pgall-for-woocommerce' ),
				'35' => __( '전북은행', 'pgall-for-woocommerce' ),
				'42' => __( '제주은행', 'pgall-for-woocommerce' ),
				'15' => __( '카카오뱅크', 'pgall-for-woocommerce' ),
				'3A' => __( '케이뱅크', 'pgall-for-woocommerce' ),
				'24' => __( '토스뱅크', 'pgall-for-woocommerce' ),
				'21' => __( '하나카드', 'pgall-for-woocommerce' ),
				'61' => __( '현대카드', 'pgall-for-woocommerce' ),
				'11' => __( 'KB국민카드', 'pgall-for-woocommerce' ),
				'91' => __( 'NH농협카드', 'pgall-for-woocommerce' ),
				'34' => __( 'Sh수협은행', 'pgall-for-woocommerce' ),
				'6D' => __( '다이너스 클럽', 'pgall-for-woocommerce' ),
				'4M' => __( '마스터카드', 'pgall-for-woocommerce' ),
				'3C' => __( '유니온페이', 'pgall-for-woocommerce' ),
				'7A' => __( '아메리칸 익스프레스', 'pgall-for-woocommerce' ),
				'4J' => __( 'JCB', 'pgall-for-woocommerce' ),
				'4V' => __( 'VISA', 'pgall-for-woocommerce' ),
			);
		}

		function get_setting_fields() {
			return array(
				array(
					'type'     => 'Section',
					'title'    => '신용카드 설정',
					'elements' => array(
						array(
							'id'        => 'tosspayments_card_title',
							'title'     => __( '결제수단 이름', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => __( '신용카드', 'pgall-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'tosspayments_card_description',
							'title'     => __( '결제수단 설명', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __( '신용카드 결제를 진행 합니다.', 'pgall-for-woocommerce' ),
							'tooltip'   => array(
								'title' => array(
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array(
							'id'        => 'tosspayments_card_max_installment_plan',
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
							'id'        => 'tosspayments_card_app_scheme',
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
							'id'        => 'tosspayments_card_use_advanced_setting',
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
							'id'        => 'tosspayments_card_order_status_after_payment',
							'title'     => __( '결제완료시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'showIf'    => array( 'tosspayments_card_use_advanced_setting' => 'yes' ),
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
							'id'        => 'tosspayments_card_possible_refund_status_for_mypage',
							'title'     => __( '구매자 주문취소 가능상태', 'pgall-for-woocommerce' ),
							'showIf'    => array( 'tosspayments_card_use_advanced_setting' => 'yes' ),
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