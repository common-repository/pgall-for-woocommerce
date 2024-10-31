<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Cash_Receipts' ) ) :

	class PAFW_Admin_Cash_Receipts {
		public static $number_per_page = 20;
		public static $navigation_size = 5;

		public static function get_setting_fields() {
			return array(
				'type'         => 'ListPage',
				'id'           => 'pafw_cash_receipts',
				'searchConfig' => array(
					'action'   => PAFW()->slug() . '-get_cash_receipts',
					'pageSize' => self::$number_per_page
				),
				'elements'     => array(
					array(
						'type'              => 'MShopListTableFilter',
						'id'                => 'pafw_cash_receipts_filter',
						'hideSectionHeader' => true,
						'elements'          => array(
							array(
								"id"          => 'customer',
								"title"       => __( "사용자", 'pgall-for-woocommerce' ),
								"placeHolder" => __( "사용자 선택", 'pgall-for-woocommerce' ),
								"className"   => "fluid search",
								'multiple'    => true,
								'search'      => true,
								'action'      => 'action=' . PAFW()->slug() . '-pafw_search_user&keyword=',
								"type"        => "SearchSelect",
								'options'     => array()
							),
							array(
								"id"        => "status",
								"title"     => __( "발급 상태", 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Select",
								"default"   => "all",
								'options'   => array_merge( array(
									'ALL' => __( '모두', 'pgall-for-woocommerce' )
								), PAFW_Cash_Receipt::get_statuses() )
							),
							array(
								"id"        => "receipt_number",
								"title"     => __( "현금영수증 번호", 'pgall-for-woocommerce' ),
								"className" => "fluid",
								"type"      => "Text",
								"eventType" => "blur",
								"default"   => "",
							),
							array(
								'id'        => 'term',
								"type"      => "DateRange",
								"title"     => __( "조회기간", 'pgall-for-woocommerce' ),
								"className" => "mshop-daterange",
							),
							array(
								'id'         => 'pafw_export_cash_receipt_logs',
								'title'      => 'CSV 다운로드',
								'label'      => '다운로드',
								'iconClass'  => 'icon settings',
								'className'  => '',
								'type'       => 'Button',
								'default'    => '',
								'actionType' => 'notification',
								'command'    => 'download',
								'args'       => array(
									'ajaxurl' => admin_url( 'admin-ajax.php' ),
									'action'  => PAFW()->slug() . '-export_cash_receipt_logs'
								)
							),
						)
					),
					array(
						'type' => 'MShopListTableNavigator',
					),
					array(
						'type'     => 'MShopListTable',
						'id'       => 'pafw_cash_receipts_target',
						'default'  => array(),
						"repeater" => true,
						'elements' => array(
							'type'        => 'SortableTable',
							'className'   => 'sortable',
							'noResultMsg' => __( '검색 결과가 없습니다.', 'pgall-for-woocommerce' ),
							"repeater"    => true,
							"elements"    => array(
								array(
									"id"            => "no",
									"title"         => __( "", 'pgall-for-woocommerce' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "no"
								),
								array(
									"id"            => "order",
									"title"         => __( "주문번호", 'pgall-for-woocommerce' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "id"
								),
								array(
									"id"            => "customer",
									"title"         => __( "고객번호", 'pgall-for-woocommerce' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
									"sortKey"       => "id"
								),
								array(
									"id"            => "date",
									"title"         => __( "주문일자", 'pgall-for-woocommerce' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"            => "total_price",
									"title"         => __( "주문금액", 'pgall-for-woocommerce' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"            => "status_label",
									"title"         => __( "상태", 'pgall-for-woocommerce' ),
									"className"     => "center aligned one wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"            => "usage",
									"title"         => __( "발행정보", 'pgall-for-woocommerce' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"            => "receipt_number",
									"title"         => __( "현금영수증 번호", 'pgall-for-woocommerce' ),
									"className"     => "center aligned two wide column",
									"cellClassName" => "center aligned",
									"type"          => "Label",
								),
								array(
									"id"        => "message",
									"title"     => __( "메시지", 'pgall-for-woocommerce' ),
									"className" => "center aligned three wide column",
									"type"      => "Label",
									"sortKey"   => "amount"
								)
							)
						)
					),
					array(
						'type' => 'MShopListTableNavigator',
					),
				)
			);
		}

		static function enqueue_scripts() {
			wp_enqueue_script( 'underscore' );
			wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array(
				'jquery',
				'jquery-ui-core'
			) );
		}
		public static function output() {
			include_once PAFW()->plugin_path() . '/includes/admin/setting-manager/mssms-helper.php';

			$settings = self::get_setting_fields();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => 'mshop_point_update_settings',
				'settings' => $settings,
				'values'   => PAFW_Setting_Helper::get_settings( $settings ),
			) );

			?>
            <style>
                .mshop-setting-section .ui.table.sortable td {
                    height: 50px;
                }
            </style>
            <script>
                jQuery( document ).ready( function () {
                    jQuery( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_Setting_Helper::get_settings( $settings ) ); ?>  ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>

			<?php
		}
	}

endif;
