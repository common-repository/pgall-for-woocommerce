<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Order_Status_Control_Settings' ) ) :

	class PAFW_Admin_Order_Status_Control_Settings {

		static $order_statuses = null;

		static $emails = null;

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
		static function filter_order_statuses( $except_list = null ) {
			if ( is_null( $except_list ) ) {
				$except_list = array(
					'pending',
					'cancelled',
					'failed',
					'refunded',
					'exchange-request',
					'accept-exchange',
					'return-request',
					'accept-return',
					'cancel-request'
				);
			}

			return array_diff_key( self::get_order_statuses(), array_flip( $except_list ) );
		}

		static function get_setting_fields() {
			return array(
				'type'     => 'Tab',
				'id'       => 'setting-tab',
				'elements' => array(
					self::get_basic_setting(),
					self::get_auto_transition_setting(),
				)
			);
		}

		static function get_basic_setting() {
			return array(
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '결제 완료시 주문상태 설정', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '기본설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								'id'        => 'pafw-gw-order_status_after_payment',
								'title'     => __( '일반상품 결제 완료', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'processing',
								'options'   => self::filter_order_statuses(),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '배송이 필요한 일반상품의 주문이 결제(입금) 완료된 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array(
								"id"          => "pafw-gw-order_status_after_payment_for_virtual",
								"title"       => __( "가상상품 결제 완료", "pgall-for-woocommerce" ),
								"className"   => "",
								"type"        => "Select",
								'default'     => 'completed',
								'options'     => self::filter_order_statuses(),
								"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" ),
								'tooltip'     => array(
									'title' => array(
										'content' => __( '배송이 필요하지 않은 가상상품의 주문이 결제(입금) 완료된 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array(
								'id'        => 'pafw-gw-order_status_after_vbank_payment',
								'title'     => __( '가상계좌 주문접수', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'on-hold',
								'options'   => self::filter_order_statuses(),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '가상계좌 결제수단으로 주문이 접수된 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array(
								'id'        => 'pafw-gw-order_status_after_enter_shipping_number',
								'title'     => __( '에스크로 배송정보 등록', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'shipped',
								'options'   => self::filter_order_statuses(),
								'tooltip'   => array(
									'title' => array(
										'content' => __( '관리자가 에스크로 결제건의 배송정보를 등록한 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							)
						)
					),
					array(
						'type'           => 'Section',
						'title'          => __( '결제 완료시 주문상태 상세설정', 'pgall-for-woocommerce' ),
						'hideSaveButton' => true,
						'elements'       => array(
							array(
								'id'        => 'pafw-order-status-control-description',
								'title'     => '',
								'className' => '',
								'type'      => 'Label',
								'readonly'  => 'yes',
								'default'   => '',
								'desc2'     => __( '<div class="desc2">주문에 포함된 상품이 상품별 / 카테고리별 / 상품 속성별 주문상태 자동변경 정책에 적용되는 경우, 해당 정책이 적용됩니다.</div>', 'pgall-for-woocommerce' ),
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '결제(입금) 완료 시 상품별 주문상태 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-order-status-by-product",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"      => "yes",
									"products"     => "",
									"order_status" => "",
									"email"        => "none",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " center aligned one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "products",
										"title"        => __( "상품", "pgall-for-woocommerce" ),
										"className"    => " seven wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=product',
										"placeholder"  => __( "상품을 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "order_status",
										"title"       => __( "주문상태", "pgall-for-woocommerce" ),
										"className"   => " six wide column fluid",
										"type"        => "Select",
										'options'     => self::filter_order_statuses(),
										"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '결제(입금) 완료 시 상품 속성별 주문상태 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-order-status-by-attributes",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"      => "yes",
									"attributes"   => "",
									"order_status" => "",
									"email"        => "none",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " center aligned one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "attributes",
										"title"        => __( "상품 속성", "pgall-for-woocommerce" ),
										"className"    => " seven wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=attributes',
										"placeholder"  => __( "상품 속성을 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "order_status",
										"title"       => __( "주문상태", "pgall-for-woocommerce" ),
										"className"   => " six wide column fluid",
										"type"        => "Select",
										'options'     => self::filter_order_statuses(),
										"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '결제(입금) 완료 시 카테고리별 주문상태 설정', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-order-status-by-category",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"      => "yes",
									"categories"   => "",
									"order_status" => "",
									"email"        => "none",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " center aligned one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "categories",
										"title"        => __( "상품 카테고리", "pgall-for-woocommerce" ),
										"className"    => " seven wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=category',
										"placeholder"  => __( "카테고리를 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "order_status",
										"title"       => __( "주문상태", "pgall-for-woocommerce" ),
										"className"   => " six wide column fluid",
										"type"        => "Select",
										'options'     => self::filter_order_statuses(),
										"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					)
				)
			);
		}

		static function get_auto_transition_setting() {
			return array(
				'type'     => 'Page',
				'class'    => '',
				'title'    => __( '주문상태 자동변경 설정', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'     => 'Section',
						'title'    => __( '주문 상태가 "변경된 주문상태"로 변경된 후 지정된 기간이 지나면, "변경될 주문상태"로 주문상태가 자동 변경됩니다.', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-auto-transition-by-term",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"     => "yes",
									"from_status" => "",
									"term"        => "",
									"to_status"   => "",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " center aligned one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"          => "from_status",
										"title"       => __( "변경된 주문상태", "pgall-for-woocommerce" ),
										"className"   => " five wide column fluid",
										"type"        => "Select",
										'options'     => self::filter_order_statuses( array() ),
										"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"        => 'term',
										"title"     => '기간',
										"className" => " four wide column fluid",
										"type"      => 'LabeledInput',
										"label"     => "일"
									),
									array(
										"id"          => "to_status",
										"title"       => __( "변경될 주문상태", "pgall-for-woocommerce" ),
										"className"   => " five wide column fluid",
										"type"        => "Select",
										'options'     => self::filter_order_statuses( array() ),
										"placeholder" => __( "주문상태를 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					)
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
			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_order_status_control_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			PAFW_Setting_Helper::get_settings( $settings );

			?>
            <script>
                jQuery( document ).ready( function() {
                    jQuery( this ).trigger( 'mshop-setting-manager', [ 'mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_Setting_Helper::get_settings( $settings ) ); ?>, null, null ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
		}

	}
endif;



