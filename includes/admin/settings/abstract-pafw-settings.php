<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings' ) ) {
	abstract class PAFW_Settings {

		protected $master_id = '';
		protected $prefix = '';
		protected $id = null;
		protected $settings = null;

		protected static $order_statuses = null;


		public function __construct() {
			$classname = strtolower( get_called_class() );

			$this->id = str_replace( 'pafw_settings_', '', $classname );
		}
		function get_order_statuses() {
			if ( is_null( self::$order_statuses ) ) {
				self::$order_statuses = array ();

				foreach ( wc_get_order_statuses() as $status => $status_name ) {
					$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

					self::$order_statuses[ $status ] = $status_name;
				}

			}


			return self::$order_statuses;
		}
		function filter_order_statuses( $except_list ) {
			return array_diff_key( $this->get_order_statuses(), array_flip( $except_list ) );
		}
		function get_setting_values( $settings ) {
			$filtered_values = array ();
			$setting_values  = get_option( 'pafw_mshop_' . $this->master_id, array () );
			if ( empty( $setting_values ) ) {
				$setting_values = PAFW_Setting_Helper::get_settings( $settings );
			} else {
				$setting_values = PAFW_Setting_Helper::filter_setting_values( $settings, $setting_values );
			}

			foreach ( $setting_values as $key => $value ) {
				$filtered_values[ str_replace( $this->id . '_', '', $key ) ] = $value;
			}

			return apply_filters( 'pafw_get_payment_gateway_setting_values', $filtered_values, $this );
		}
		public function get_settings() {
			if ( is_null( $this->settings ) ) {

				$setting_fields = array_merge(
					$this->get_basic_setting_fields(),
					is_callable( array ( $this, 'get_advanced_setting_fields' ) ) ? $this->get_advanced_setting_fields() : array (),
					apply_filters( 'pafw_payment_method_setting', $this->get_setting_fields(), $this )
				);

				$settings = $this->get_setting_values( array ( 'elements' => $setting_fields ) );


				$global_options = array (
					'order_status_after_vbank_payment'            => array ( 'always' => true, 'default' => 'on-hold' ),
					'order_status_after_payment'                  => array ( 'always' => false, 'default' => 'processing' ),
					'order_status_after_enter_shipping_number'    => array ( 'always' => true, 'default' => 'shipped' ),
					'possible_refund_status_for_mypage'           => array ( 'always' => false, 'default' => 'pending,on-hold' ),
					'possible_escrow_confirm_status_for_customer' => array ( 'always' => true, 'default' => 'shipped,cancel-request' ),
				);

				foreach ( $global_options as $option_name => $options ) {
					if ( $options['always'] || 'yes' != pafw_get( $settings, 'use_advanced_setting', 'no' ) ) {
						$settings[ $option_name ] = get_option( 'pafw-gw-' . $option_name, $options['default'] );
					}

					if ( in_array( $option_name, array ( 'possible_refund_status_for_mypage', 'possible_escrow_confirm_status_for_customer' ) ) ) {
						$settings[ $option_name ] = explode( ',', $settings[ $option_name ] );
					}
				}

				$this->settings = $settings;
			}

			$this->settings['enabled'] = in_array( $this->id, $this->get_available_methods() ) ? 'yes' : 'no';

			return $this->settings;
		}
		function get_setting_fields() {
			return array ();
		}

		function get_basic_setting_fields() {
			return array ();
		}

		public function get_available_methods() {
			return explode( ',', $this->settings['pc_pay_method'] );
		}

		public function get_id() {
			return $this->id;
		}
	}
}
