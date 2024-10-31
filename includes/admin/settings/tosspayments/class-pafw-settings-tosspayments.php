<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_TossPayments' ) ) {

	abstract class PAFW_Settings_TossPayments extends PAFW_Settings {

		public function __construct() {
			$this->master_id = 'tosspayments';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'tosspayments_basic' );

			return $instance->get_setting_fields();
		}
		function get_advanced_setting_fields() {
			$instance = pafw_get_settings( 'tosspayments_advanced' );

			return $instance->get_setting_fields();
		}
	}
}
