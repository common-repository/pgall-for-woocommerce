<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Settlebank' ) ) {
	abstract class PAFW_Settings_Settlebank extends PAFW_Settings {

		public function __construct() {
			$this->master_id = 'settlebank';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'settlebank_basic' );

			return $instance->get_setting_fields();
		}
		function get_advanced_setting_fields() {
			$instance = pafw_get_settings( 'settlebank_advanced' );

			return $instance->get_setting_fields();
		}
	}
}
