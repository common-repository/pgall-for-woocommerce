<?php
class WC_Gateway_PAFW extends WC_Payment_Gateway {
	public function __construct() {
		$this->enabled = PAFW()->is_wc_setting_page() ? 'yes' : 'no';
	}
	public static function get_supported_payment_methods() {
		return array ();
	}
	public static function checkout_sections( $sections ) {
		$sections = array_diff_key( $sections, static::get_supported_payment_methods() );

		return $sections;
	}
	public static function check_default_vbank_noti_url( $value ) {
		return untrailingslashit( WC()->api_request_url( 'WC_Gateway_Inicis_StdVbank?type=vbank_noti', pafw_check_ssl() ) );
	}

	protected function get_key() {
	}
	public static function update_settings() {
		$called_class = get_called_class();
		$instance = new $called_class();

		include_once PAFW()->plugin_path() . '/includes/admin/setting-manager/pafw-setting-helper.php';
		$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

		$values = PAFW_Setting_Helper::get_setting_values( self::get_settings( str_replace( 'mshop_', '', $instance->id ), $instance->get_supported_payment_methods() ) );

		update_option( 'pafw_' . $instance->id, $values );

		wp_send_json_success();
	}
	public static function enqueue_frontend_script() {
	}

	public function enqueue_script() {

		wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
		wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array (
			'jquery',
			'jquery-ui-core'
		) );
	}
	static function get_setting( $type, $payment_type ) {
		$setting = array ();

		$setting_object = pafw_get_settings( $type . '_basic' );

		array_push( $setting, array (
				'id'       => 'basic-setting-tab',
				'title'    => '기본설정',
				'class'    => 'active',
				'type'     => 'Page',
				'elements' => $setting_object->get_setting_fields()
			)
		);

		$setting_object = pafw_get_settings( $type . '_advanced' );
		if ( $setting_object ) {
			array_push( $setting, array (
					'id'       => 'advanced-setting-tab',
					'title'    => '고급설정',
					'type'     => 'Page',
					'elements' => $setting_object->get_setting_fields()
				)
			);
		}

		foreach ( $payment_type as $id => $title ) {
			$setting_object = pafw_get_settings( $id );

			array_push( $setting, array (
					'id'       => $id . '-setting',
					'title'    => $title,
					'type'     => 'Page',
					'showIf'   => array ( 'pc_pay_method' => $id ),
					'elements' => apply_filters( 'pafw_payment_method_setting', $setting_object->get_setting_fields(), $setting_object )
				)
			);
		}

		return $setting;
	}

	static function get_settings( $type, array $payment_type ) {

		$settings = apply_filters( 'pafw_get_settings_for_' . $type, self::get_setting( $type, $payment_type ) );

		return
			array (
				'type'     => 'Tab',
				'id'       => $type . '-setting-tab',
				'elements' => $settings
			);

	}
	static function get_setting_values( $id, $settings ) {
		$setting_values = get_option( 'pafw_' . $id, array () );
		if ( empty( $setting_values ) ) {
			$setting_values = PAFW_Setting_Helper::get_settings( $settings );
			update_option( 'pafw_' . $id, $setting_values );
		}
		if ( empty( $setting_values['operation_mode'] ) ) {
			$setting_values['operation_mode'] = 'production';
		}

		return apply_filters( 'pafw_get_setting_values', $setting_values, $settings, $id, get_called_class() );
	}

	function admin_options() {
		echo '<h2 style="font-size: 1.3em;">' . esc_html( $this->get_method_title() );
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
	}
}