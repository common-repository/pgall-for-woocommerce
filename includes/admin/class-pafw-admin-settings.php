<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Settings' ) ) :

	class PAFW_Admin_Settings {

		static $order_statuses = null;

		static $base_url = null;

		static function base_url() {
			if ( is_null( self::$base_url ) ) {
				if ( class_exists( 'MSHOP_MCommerce_Premium' ) || class_exists( 'MC_MShop' ) ) {
					self::$base_url = admin_url( '/admin.php?page=mshop_payment&tab=checkout&section=' );
				} else {
					self::$base_url = admin_url( '/admin.php?page=wc-settings&tab=checkout&section=' );
				}
			}

			return self::$base_url;
		}

		static function get_setting_url( $gateway ) {
			return self::base_url() . $gateway;
		}

		static function update_settings() {
			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( wc_clean( $_REQUEST['values'] ) ), true ) );

			PAFW_Setting_Helper::update_settings( self::get_setting_fields() );

			wp_send_json_success();
		}
		static function get_order_statuses() {
			if ( is_null( self::$order_statuses ) ) {
				self::$order_statuses = array();

				foreach ( wc_get_order_statuses() as $status => $status_name ) {
					$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

					self::$order_statuses[ $status ] = $status_name;
				}

			}

			return self::$order_statuses;
		}
		static function filter_order_statuses( $except_list ) {
			return array_diff_key( self::get_order_statuses(), array_flip( $except_list ) );
		}

		static function get_setting_fields() {
			$elements = array(
				self::get_payment_gateway_setting()
			);

			if ( ! empty( PAFW()->get_enabled_payment_gateways() ) ) {
				$elements = array_merge( $elements, array(
					self::get_cash_receipt_setting(),
					self::get_exchange_return_setting(),
					self::get_advanced_setting()
				) );

				if ( class_exists( 'WC_Subscription' ) ) {
					$elements[] = self::get_subscription_setting();
				}
			}

			return array(
				'type'     => 'Tab',
				'id'       => 'mssms-setting-tab',
				'elements' => $elements
			);
		}
		static function get_payment_gateway_setting() {
			return array(
				'type'     => 'Page',
				'class'    => 'active gateways',
				'title'    => __( '결제대행사', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '결제 대행사 선택', 'pgall-for-woocommerce' ),
						'elements' => apply_filters( 'pafw_admin_gateway_settings',
							array(
								array(
									"id"        => "pafw-gw-inicis",
									"title"     => __( "KG 이니시스", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">이니시스 결제 시스템을 이용합니다. (정기결제, 예약결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_inicis' )
									)
								),
								array(
									"id"        => "pafw-gw-tosspayments",
									"title"     => __( "토스페이먼츠", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">토스페이먼츠 결제 시스템을 이용합니다. (정기결제, 예약결제, 해외결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_tosspayments' )
									)
								),
								array(
									"id"        => "pafw-gw-nicepay",
									"title"     => __( "나이스페이", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">나이스페이 결제 시스템을 이용합니다. (정기결제, 예약결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_nicepay' )
									)
								),
								array(
									"id"        => "pafw-gw-kakaopay",
									"title"     => __( "카카오페이", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">카카오페이 결제 시스템을 이용합니다. (간편결제, 정기결제, 예약결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_kakaopay' )
									)
								),
								array(
									"id"        => "pafw-gw-kcp",
									"title"     => __( "NHN KCP", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">KCP 결제 시스템을 이용합니다.</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_kcp' )
									)
								),
								array(
									"id"        => "pafw-gw-settlepg",
									"title"     => __( "헥토파이낸셜", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">헥토파이낸셜 결제 시스템을 이용합니다. (정기결제, 예약결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_settlepg' )
									)
								),
								array(
									"id"        => "pafw-gw-settlebank",
									"title"     => __( "헥토파이낸셜 내통장결제", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">헥토파이낸셜 내통장결제 시스템을 이용합니다. (정기결제, 예약결제 지원)</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_settlebank' )
									)
								),
								array(
									"id"        => "pafw-gw-settlevbank",
									"title"     => __( "헥토파이낸셜 010가상계좌", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">헥토파이낸셜 010가상계좌 결제 시스템을 이용합니다.</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_settlevbank' )
									)
								),
								array(
									"id"        => "pafw-gw-payco",
									"title"     => __( "NHN 페이코", "pgall-for-woocommerce" ),
									"className" => "",
									"type"      => "Toggle",
									"default"   => "no",
									"desc"      => __( '<div class="desc2">페이코 결제 시스템을 이용합니다.</div>', 'pgall-for-woocommerce' ),
									"action"    => array(
										"icon"   => "cogs",
										"show"   => "yes",
										"target" => "_blank",
										"url"    => self::get_setting_url( 'mshop_payco' )
									)
								)
							)
							, self::base_url()
						),
					),
					array(
						'type'     => 'Section',
						'title'    => __( '결제 대행사 선택 ( 구버전 )', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-gw-lguplus",
								"title"     => __( "토스페이먼츠 (구. LG유플러스)", "pgall-for-woocommerce" ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">토스페이먼츠 (구. LG유플러스) 결제 시스템을 이용 합니다. (정기결제, 예약결제 지원) - 2023년 8월 1일 이전 가입자</div>', 'pgall-for-woocommerce' ),
								"action"    => array(
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => self::get_setting_url( 'mshop_lguplus' )
								)
							)
						),
					),
				)
			);
		}

		static function get_advanced_setting() {
			return array(
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '고급', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '결제폼 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-checkout-form-selector',
								'className' => 'fluid',
								'title'     => __( '체크아웃 폼 셀렉터', 'pgall-for-woocommerce' ),
								"desc2"     => __( '<div class="desc2">체크아웃 페이지의 폼 셀렉터를 지정합니다. 기본값은 <code>form.checkout</code> 입니다.</div>', 'pgall-for-woocommerce' ),
								'default'   => 'form.checkout',
								'type'      => 'Text'
							),
							array(
								'id'        => 'pafw-order-pay-form-selector',
								'className' => 'fluid',
								'title'     => __( '내계정 - 주문하기 폼 셀렉터', 'pgall-for-woocommerce' ),
								"desc2"     => __( '<div class="desc2">내계정 - 주문하기 페이지의 폼 셀렉터를 지정합니다. 기본값은 <code>form#order_review</code> 입니다.</div>', 'pgall-for-woocommerce' ),
								'default'   => 'form#order_review',
								'type'      => 'Text'
							),
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '구매자 주문처리 가능 상태 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-gw-possible_refund_status_for_mypage',
								'title'     => __( '주문취소', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'pending,on-hold',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array(
									'cancelled',
									'refunded',
								) ),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '구매자가 내계정 페이지의 주문 목록 화면에서 주문을 취소할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array(
								'id'        => 'pafw-gw-possible_escrow_confirm_status_for_customer',
								'title'     => __( '에스크로 구매 확인 및 거절', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'shipped,cancel-request',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array(
									'pending',
									'on-hold',
									'cancelled',
									'failed',
									'refunded',
								) ),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '구매자가 내계정 페이지의 주문 상세 화면에서 에스크로 결제건에 대한 구매 확인 및 거절 처리를 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array(
								'id'        => 'pafw-gw-support-cancel-guest-order',
								'title'     => __( '비회원 주문취소', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">비회원이 주문상세 페이지에서 주문을 취소할 수 있도록 "결제취소" 버튼을 표시합니다.</div>', 'pgall-for-woocommerce' ),
							),
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '재고관리', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-gw-support-cancel-unpaid-order',
								'title'     => __( '무통장입금 재고관리', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">무통장입금 ( BACS, 가상계좌 ) 결제건에 대한 재고관리 기능을 사용합니다.<br>지정된 시간내에 입금되지 않은 결제건은 자동 취소처리되며, 재고관리가 활성화 된 경우, 상품의 재고가 다시 복원됩니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								"id"        => "pafw-gw-cancel-unpaid-order-days",
								"title"     => __( "무통장입금 대기시간", 'pgall-for-woocommerce' ),
								"showIf"    => array( 'pafw-gw-support-cancel-unpaid-order' => 'yes' ),
								"className" => "",
								"type"      => "LabeledInput",
								'inputType' => 'number',
								"leftLabel" => __( "결제 후", 'pgall-for-woocommerce' ),
								"label"     => __( "일", 'pgall-for-woocommerce' ),
								"default"   => "3"
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '스크립트 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'      => 'pafw-script-footer',
								'title'   => __( '스크립트 Footer 사용', 'pgall-for-woocommerce' ),
								"desc"    => __( '<div class="desc2">스크립트를 Footer에 출력합니다. 결제시 문제가 있는 경우에만 사용하세요.</div>', 'pgall-for-woocommerce' ),
								'default' => 'no',
								'type'    => 'Toggle'
							),
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '우커머스 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'      => 'pafw-use-woocommerce-blocks',
								'title'   => __( '우커머스 블록 사용', 'pgall-for-woocommerce' ),
								"desc"    => __( '<div class="desc2">우커머스 체크아웃 블록 위젯을 사용합니다.</div>', 'pgall-for-woocommerce' ),
								'default' => 'no',
								'type'    => 'Toggle'
							),
						)
					)
				)
			);
		}

		static function get_subscription_setting() {
			return array(
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '정기결제 설정', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '결제일자 관리', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-subscription-allow-change-date',
								'className' => 'fluid',
								'title'     => __( '다음 결제일자 변경 허용', 'pgall-for-woocommerce' ),
								"desc2"     => __( '<div class="desc2">고객은 내계정 - 정기결제권 상세화면에서 다음 결제 예정일을 변경할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
								'default'   => 'no',
								'type'      => 'Toggle'
							),
							array(
								'id'        => 'pafw-subscription-force-renewal-time',
								'className' => 'fluid',
								'title'     => __( '갱신결제 시간 조정', 'pgall-for-woocommerce' ),
								"desc2"     => __( '<div class="desc2">다음 갱신 결제 시간을 지정된 시간으로 변경합니다.<br>예시> 구매자가 오후 10시에 결제를 했더라도, 다음 갱신결제는 업무시간 (09:00~18:00) 에 진행되도록 설정 할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
								'default'   => 'no',
								'type'      => 'Toggle'
							),
							array(
								"id"        => "pafw-subscription-renewal-time-begin",
								"title"     => __( "갱신결제 시작 시간", 'pgall-for-woocommerce' ),
								"showIf"    => array( 'pafw-subscription-force-renewal-time' => 'yes' ),
								"className" => "",
								"type"      => "Text",
								"default"   => "09:00"
							),
							array(
								"id"        => "pafw-subscription-renewal-time-end",
								"title"     => __( "갱신결제 끝 시간", 'pgall-for-woocommerce' ),
								"showIf"    => array( 'pafw-subscription-force-renewal-time' => 'yes' ),
								"className" => "",
								"type"      => "Text",
								"default"   => "18:00"
							)
						)
					),
					self::get_renewal_failed_notification_settings()
				)
			);
		}

		static function get_renewal_failed_notification_settings() {
			if ( class_exists( 'MSSMS_Manager' ) ) {
				return array(
					'type'     => 'Section',
					'title'    => __( '갱신결제 실패 알림', 'pgall-for-woocommerce' ),
					'elements' => array_merge(
						array(
							array(
								"id"        => "pafw-use-renewal-failed-notification",
								"title"     => __( "갱신결제 실패 알림 활성화", 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "",
								"desc"      => __( "<div class='desc2'>갱신 결제가 실패하게 되면 해당 사용자에게 카드를 재등록 할 수 있도록 알림을 발송합니다.</div>", "pgall-for-woocommerce" )
							),
							array(
								"id"        => "pafw-renewal-failed-notification-method",
								"title"     => __( "발송 수단", "pgall-for-woocommerce" ),
								'showIf'    => array( 'pafw-use-renewal-failed-notification' => 'yes' ),
								"className" => "",
								"type"      => "Select",
								'default'   => 'alimtalk',
								'options'   => array(
									'sms'      => '문자 (LMS)',
									'alimtalk' => '알림톡'
								),
							),
							array(
								"id"        => "pafw-renewal-failed-period",
								"showIf"    => array( "pafw-use-renewal-failed-notification" => "yes" ),
								"type"      => "LabeledInput",
								"inputType" => 'number',
								"title"     => __( "등록 유효 기간", 'pgall-for-woocommerce' ),
								"leftLabel" => __( '갱신 실패 후', 'pgall-for-woocommerce' ),
								"label"     => __( '일 까지', 'pgall-for-woocommerce' ),
								"default"   => "3",
								"desc2"     => __( "<div class='desc2'>갱신 실패 후 지정된 기간이 지나면 결제수단 링크를 이용할 수 없게 됩니다. </div>", "pgall-for-woocommerce" )
							),
							array(
								"id"        => "pafw-renewal-failed-notification-sms-template",
								"title"     => __( "갱신결제 실패알림 문자 템플릿", 'pgall-for-woocommerce' ),
								'showIf'    => array( array( 'pafw-use-renewal-failed-notification' => 'yes' ), array( 'pafw-renewal-failed-notification-method' => 'sms' ) ),
								"className" => "center aligned fluid",
								"type"      => "TextArea",
								"default"   => __( "[{쇼핑몰명}]\r\n\r\n{고객명}님\r\n이용중이신 {상품명}의 갱신처리가 실패되었습니다.\r\n정기결제권 번호 : {정기결제권번호}\r\n결제수단 : {결제수단}\r\n\r\n정기결제권을 계속 유지하시려면 아래 링크를 통해 카드를 재등록 해주세요.\r\n{카드등록링크}\r\n\r\n※ 기타 문의사항은 고객센터로 문의해주시길 바랍니다. 감사합니다.", "pgall-for-woocommerce" ),
								"rows"      => 12,
								"desc2"     => __( "<div class='desc2'>고객이 {카드등록링크}를 통해 접속한 후, 동일한 결제수단을 재등록하면 자동으로 결제가 진행됩니다.</div>", "pgall-for-woocommerce" )
							),
							array(
								"id"          => "pafw-renewal-failed-notification-alimtalk-template",
								'showIf'      => array( array( 'pafw-use-renewal-failed-notification' => 'yes' ), array( 'pafw-renewal-failed-notification-method' => 'alimtalk' ) ),
								"title"       => __( "갱신결제 실패알림 알림톡 템플릿", "pgall-for-woocommerce" ),
								"placeholder" => __( "갱신결제 실패알림 템플릿을 선택해주세요.", "pgall-for-woocommerce" ),
								"className"   => "",
								"type"        => "Select",
								'options'     => MSSMS_Manager::get_alimtalk_templates()
							)
						)
					)
				);
			} else {
				return array(
					'type'     => 'Page',
					'title'    => '갱신결제 실패 알림',
					'elements' => array(
						array(
							'type'           => 'Section',
							'title'          => '갱신결제 실패 알림',
							'hideSaveButton' => true,
							'elements'       => array(
								array(
									'id'      => 'pafw-renewal-failed-notification-guide',
									'type'    => 'Label',
									'default' => '갱신결제 실패 알림 기능을 이용하시려면 <a href="https://www.codemshop.com/shop/sms_out/" target="_blank">엠샵 문자 알림톡 플러그인</a>이 필요합니다.'
								)
							)
						)
					)
				);
			}
		}

		static function get_cash_receipt_setting() {
			return array(
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '현금영수증', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '무통장입금 현금영수증', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw_bacs_receipt',
								'className' => '',
								'type'      => 'Label',
								'readonly'  => 'yes',
								'default'   => '',
								'desc2'     => __( '현금영수증 자동 발급 기능은 <span style="color: #2185d0">KG 이니시스</span>, <span style="color: #2185d0">KCP</span>, <span style="color: #2185d0">나이스페이</span>, <span style="color: #2185d0">토스페이먼츠</span> 결제대행사를 사용하는 경우에만 이용할 수 있습니다.', 'pgall-for-woocommerce' ),
							),
							array(
								"id"        => "pafw_use_bacs_receipt",
								"title"     => __( "현금영수증 자동 발급", "pgall-for-woocommerce" ),
								"className" => "",
								'showIf'    => array( 'pafw-gw-inicis' => 'yes', 'pafw-gw-kcp' => 'yes', 'pafw-gw-nicepay' => 'yes', 'pafw-gw-lguplus' => 'yes', 'pafw-gw-tosspayments' => 'yes' ),
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">무통장입금 결제 시, 현금영수증 자동발급 기능을 사용합니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								"id"        => "pafw_bacs_always_issue_receipt",
								"title"     => __( "현금영수증 항상 발급", "pgall-for-woocommerce" ),
								"className" => "",
								'showIf'    => array( 'pafw-gw-inicis' => 'yes', 'pafw-gw-kcp' => 'yes', 'pafw-gw-nicepay' => 'yes', 'pafw-gw-lguplus' => 'yes', 'pafw-gw-tosspayments' => 'yes' ),
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">무통장입금 결제 시, 현급영수증 발급을 위한 정보를 필수로 입력해야 합니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								"id"        => "pafw_bacs_issue_receipt_min_amount",
								"title"     => __( "최소 결제금액", "pgall-for-woocommerce" ),
								"className" => "",
								'showIf'    => array( array( 'pafw-gw-inicis' => 'yes', 'pafw-gw-kcp' => 'yes', 'pafw-gw-nicepay' => 'yes', 'pafw-gw-lguplus' => 'yes', 'pafw-gw-tosspayments' => 'yes' ), array( 'pafw_bacs_always_issue_receipt' => 'yes' ) ),
								"type"      => "LabeledInput",
								"leftLabel" => get_woocommerce_currency_symbol(),
								"desc2"     => __( '<div class="desc2">결제 금액이 최소 결제금액 미만인 경우에는 고객이 현금영수증 발급 여부를 선택할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							),
						)
					),
					array(
						'type'     => 'Section',
						'showIf'   => array( array( 'pafw-gw-inicis' => 'yes', 'pafw-gw-kcp' => 'yes', 'pafw-gw-nicepay' => 'yes', 'pafw-gw-lguplus' => 'yes', 'pafw-gw-tosspayments' => 'yes' ), array( 'pafw_use_bacs_receipt' => 'yes' ) ),
						'title'    => __( '현금영수증 발행정보', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw_bacs_receipt_company_name',
								"title"     => __( "상호", "pgall-for-woocommerce" ),
								'className' => 'fluid',
								'type'      => 'Text',
								'default'   => '',
							),
							array(
								"id"        => "pafw_bacs_receipt_reg_number",
								"title"     => __( "사업자 번호", "pgall-for-woocommerce" ),
								'className' => 'fluid',
								'type'      => 'Text',
								'default'   => '',
							),
							array(
								"id"        => "pafw_bacs_receipt_phone_number",
								"title"     => __( "전화번호", "pgall-for-woocommerce" ),
								'className' => 'fluid',
								'type'      => 'Text',
								'default'   => '',
							),
							array(
								"id"        => "pafw_bacs_receipt_ceo_name",
								"title"     => __( "대표자", "pgall-for-woocommerce" ),
								'className' => 'fluid',
								'type'      => 'Text',
								'default'   => '',
							),
							array(
								"id"        => "pafw_bacs_receipt_address",
								"title"     => __( "주소", "pgall-for-woocommerce" ),
								'className' => 'fluid',
								'type'      => 'Text',
								'default'   => '',
							),
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '주문상태 설정', 'pgall-for-woocommerce' ),
						'showIf'   => array( array( 'pafw-gw-inicis' => 'yes', 'pafw-gw-kcp' => 'yes', 'pafw-gw-nicepay' => 'yes', 'pafw-gw-lguplus' => 'yes' ), array( 'pafw_use_bacs_receipt' => 'yes' ) ),
						'elements' => array(
							array(
								'id'        => 'pafw_bacs_receipt_issue_statuses',
								'title'     => __( '현금영수증 발급 ', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'completed',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array(
									'pending',
									'failed',
									'on-hold',
									'cancelled',
									'refunded',
								) )
							),
							array(
								'id'        => 'pafw_bacs_receipt_cancel_statuses',
								'title'     => __( '현금영수증 발급 취소 ', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'cancelled,refunded',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array(
									'completed'
								) )
							),
						)
					),
				)
			);
		}

		static function get_exchange_return_setting() {
			return array(
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '교환/반품', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '교환 및 반품', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-gw-support-exchange',
								'title'     => __( '교환 지원', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( '<div class="desc2">고객이 구매한 상품에 대해 교환을 요청할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								'id'        => 'pafw-gw-support-return',
								'title'     => __( '반품 지원', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( '<div class="desc2">고객이 구매한 상품에 대해 반품을 요청할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								'id'        => 'pafw-gw-ex-skip-virtual',
								'title'     => __( '가상상품 제외', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">가상상품은 교환 및 반품을 신청할 수 없습니다.</div>', 'pgall-for-woocommerce' ),
							),
							array(
								"id"        => "pafw-gw-ex-terms",
								"title"     => __( "교환/반품 허용 기간", 'pgall-for-woocommerce' ),
								"showIf"    => array( array( 'pafw-gw-support-exchange' => 'yes', 'pafw-gw-support-return' => 'yes' ) ),
								"className" => "",
								"type"      => "LabeledInput",
								'inputType' => 'number',
								"leftLabel" => __( "배송완료 또는 주문처리완료 후", 'pgall-for-woocommerce' ),
								"label"     => __( "일", 'pgall-for-woocommerce' ),
								"default"   => "3"
							)
						)
					)
				)
			);
		}

		static function get_setting_for_tac() {
			return array(
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						"type"      => "Accordion",
						"className" => "fluid",
						"elements"  => array(
							array(
								"id"        => "agreement1",
								"title"     => __( "워드프레스 결제 심플페이 플러그인  이용약관", 'pgall-for-woocommerce' ),
								"className" => "fluid active",
								"type"      => "Label",
								'readonly'  => 'yes',
								'rows'      => 20,
								'desc2'     => nl2br( file_get_contents( PAFW()->plugin_path() . '/assets/data/agreement.txt' ) ),
							),
						)
					),
					array(
						'type'              => 'Section',
						"hideSectionHeader" => true,
						"className"         => "aaa",
						'elements'          => array(
							array(
								'id'         => 'msm_install_page',
								'label'      => __( "이용 약관에 동의합니다.", 'pgall-for-woocommerce' ),
								'iconClass'  => '',
								'className'  => 'fluid',
								'type'       => 'Button',
								'default'    => '',
								'actionType' => 'ajax',
								'ajaxurl'    => admin_url( 'admin-ajax.php' ),
								'action'     => PAFW()->slug() . '-agree_to_tac',
							),
						)
					),
				)
			);
		}

		static function enqueue_scripts() {

			wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array(
				'underscore',
				'jquery',
				'jquery-ui-core'
			) );
		}

		public static function output() {
			if ( 'yes' == get_option( PAFW()->slug() . '-agree-to-tac', 'no' ) ) {
				self::output_settings();
			} else {
				self::output_agreements();
			}
		}

		public static function output_settings() {
			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			?>
            <style>
                div#mshop-setting-wrapper td {
                    height: 35px !important;
                }
            </style>
            <script>
                jQuery( document ).ready( function() {
                    jQuery( this ).trigger( 'mshop-setting-manager', [ 'mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_Setting_Helper::get_settings( $settings ) ); ?>, null, null ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
		}

		public static function output_agreements() {
			$settings = self::get_setting_for_tac();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			?>
            <script>
                jQuery( document ).ready( function() {
                    jQuery( this ).trigger( 'mshop-setting-manager', [ 'mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_Setting_Helper::get_settings( $settings ) ); ?>, null, null ] );
                } );
            </script>
            <style>
                #mshop-setting-wrapper textarea {
                    font-size: 1em !important;
                    line-height: 1.5 !important;
                }
            </style>
            <h3><?php _e( '워드프레스 결제 심플페이 플러그인은 약관 동의 후 이용이 가능합니다.', 'pgall-for-woocommerce' ); ?></h3>
            <div id="mshop-setting-wrapper"></div>
			<?php
		}

	}
endif;



