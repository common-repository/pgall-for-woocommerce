<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Kicc_Escrow_VBank' ) ) {
	class PAFW_Settings_Kicc_Escrow_VBank extends PAFW_Settings_KICC {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => __( '에스크로 설정', 'pgall-for-woocommerce' ),
					'elements' => array (
						array (
							'id'        => 'kicc_escrow_vbank_title',
							'title'     => __( '결제수단 이름', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => __( '에스크로 가상계좌', 'pgall-for-woocommerce' ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_description',
							'title'     => __( '결제수단 설명', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __( '구매안전 서비스인 에스크로 계좌이체 결제를 진행 합니다.', 'pgall-for-woocommerce' ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '구매자들이 체크아웃(결제진행)시에 나타나는 설명글로 구매자들에게 보여지는 내용입니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_receipt',
							'title'     => __( '현금 영수증', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '현금 영수증 발행 여부를 설정 할 수 있습니다. 현금 영수증 발행은 결제 대행사와 별도 계약이 되어 있어야 이용이 가능합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => __( '배송설정', 'pgall-for-woocommerce' ),
					'elements' => array (
						array (
							'id'          => 'kicc_escrow_vbank_delivery_company_code',
							'title'       => __( '택배사', 'pgall-for-woocommerce' ),
							'className'   => '',
							'type'        => 'Select',
							'default'     => '',
							'placeholder' => __( '택배사를 선택하세요.', 'pgall-for-woocommerce' ),
							'options'     => array (
								'DC01' => __( '대한통운', 'pgall-for-woocommerce' ),
								'DC02' => __( 'CJGLS', 'pgall-for-woocommerce' ),
							)
						),
						array (
							'id'          => 'kicc_escrow_vbank_delivery_register_name',
							'title'       => __( '등록자명', 'pgall-for-woocommerce' ),
							'className'   => '',
							'type'        => 'Text',
							'default'     => '',
							'placeholder' => __( '홍길동', 'pgall-for-woocommerce' )
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '에스크로 고급 설정',
					'elements' => array (
						array (
							'id'        => 'kicc_escrow_vbank_use_advanced_setting',
							'title'     => '사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '고급 설정 사용 시, 기본 설정에 우선합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_order_status_after_payment',
							'showIf'    => array ( 'kicc_escrow_vbank_use_advanced_setting' => 'yes' ),
							'title'     => __( '결제완료시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'processing',
							'options'   => $this->filter_order_statuses( array (
								'cancelled',
								'failed',
								'on-hold',
								'refunded'
							) ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '에스크로 결제건에 한해서, 결제(입금)이 완료되면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_order_status_after_enter_shipping_number',
							'showIf'    => array ( 'kicc_escrow_vbank_use_advanced_setting' => 'yes' ),
							'title'     => __( '배송정보 등록후 변경될 주문상태', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'shipped',
							'options'   => $this->filter_order_statuses( array (
								'completed',
								'on-hold',
								'pending',
								'processing'
							) ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '에스크로 결제건에 한해서, 관리자가 배송정보를 등록하면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_possible_refund_status_for_mypage',
							'showIf'    => array ( 'kicc_escrow_vbank_use_advanced_setting' => 'yes' ),
							'title'     => __( '구매자 주문취소 가능상태', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending',
							'multiple'  => true,
							'options'   => $this->get_order_statuses(),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '에스크로 결제건에 한해서, 구매자가 내계정 페이지에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kicc_escrow_vbank_possible_escrow_confirm_status_for_customer',
							'showIf'    => array ( 'kicc_escrow_vbank_use_advanced_setting' => 'yes' ),
							'title'     => __( '구매자 주문확인 및 거절 가능상태', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'shipped,cancel-request',
							'multiple'  => true,
							'options'   => $this->get_order_statuses(),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '에스크로 결제건에 한해서, 구매자가 내계정 - 주문상세 페이지에서 주문확인 및 거절을 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),

								)
							)
						)
					)
				)
			);
		}
	}
}
