<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class Abstract_PAFW_Payment_Blocks_Support extends AbstractPaymentMethodType {
	protected $gateway;
	protected $settings;
	protected static $is_enqueued = false;
	public function initialize() {
		$gateways = WC()->payment_gateways->payment_gateways();

		$this->gateway = $gateways[ $this->name ];

		$this->settings = $this->gateway->settings;
	}
	public function is_active() {
		return $this->gateway->is_available();
	}

	public function get_supported_payment_methods() {
		$supported_payment_methods = array();

		foreach ( PAFW()->get_supported_gateways() as $gateway_id ) {
			$pafw_class_name = 'WC_Gateway_PAFW_' . ucfirst( $gateway_id );

			if ( 'yes' == get_option( 'pafw-gw-' . $gateway_id, 'no' ) ) {
				$supported_payment_methods = array_merge( $supported_payment_methods, array_keys( $pafw_class_name::get_supported_payment_methods() ) );
			}
		}

		return $supported_payment_methods;
	}
	public function get_payment_method_script_handles() {
		if ( ! self::$is_enqueued ) {
			$script_path       = '/assets/blocks/js/frontend/blocks.js';
			$script_asset_path = PAFW_PLUGIN_DIR . '/assets/blocks/js/frontend/blocks.asset.php';

			$script_asset = file_exists( $script_asset_path )
				? require( $script_asset_path )
				: array(
					'dependencies' => array(),
					'version'      => PAFW_VERSION
				);

			wp_register_script(
				'pafw-payments-blocks',
				plugins_url( $script_path, PAFW_PLUGIN_FILE ),
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);

			wp_localize_script( 'pafw-payments-blocks', '_pafw_payment_blocks', array(
				'supported_payment_methods' => $this->get_supported_payment_methods(),
				'gateway_domain'            => PAFW_Payment_Gateway::gateway_domain()
			) );

			wp_enqueue_style( 'pafw-payments-blocks', PAFW()->plugin_url() . '/assets/css/payment.css', array(), PAFW_VERSION );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'pafw-payments-blocks', 'pgall-for-woocommerce', PAFW_PLUGIN_DIR . '/languages/' );
			}

			self::$is_enqueued = true;
		}

		return [ 'pafw-payments-blocks' ];
	}
	public function get_payment_method_data() {
		$payment_gateway = $this->gateway;

		$bill_key_info = '';

		if ( ! is_account_page() && is_user_logged_in() && $payment_gateway->supports( 'subscriptions' ) && ! $payment_gateway->supports( 'add_payment_method' ) ) {
			$bill_key = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'bill_key' ), true );

			if ( ! empty( $bill_key ) ) {
				$card_name = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_name' ), true );
				$card_num  = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_num' ), true );
				$card_num  = substr_replace( $card_num, '00000000', 4, 8 );
				$card_num  = implode( '-', str_split( $card_num, 4 ) );

				$bill_key_info = sprintf( "%s (%s)", preg_replace( '/[\[\]]/', '', $card_name ), $card_num );

				$bill_key_info = apply_filters( 'pafw_payments_blocks_bill_key_info_' . $payment_gateway->id, $bill_key_info, $card_name, $card_num, $payment_gateway );
			}
		}

		return [
			'title'         => $payment_gateway->get_title(),
			'description'   => $this->get_setting( 'description' ),
			'name'          => $this->name,
			'master_id'     => $payment_gateway->get_master_id(),
			'supports'      => array_filter( $payment_gateway->supports, [ $payment_gateway, 'supports' ] ),
			'uuid'          => wp_generate_uuid4(),
			'enable_quota'  => 'yes' == pafw_get( $payment_gateway->settings, 'enable_quota', 'no' ),
			'quotas'        => array_merge( array( "00" ), explode( ',', pafw_get( $payment_gateway->settings, 'quota' ) ) ),
			'bill_key_info' => $bill_key_info
		];
	}
}

class PAFW_Payment_Gateway_Blocks_Support extends Abstract_PAFW_Payment_Blocks_Support {
	public function __construct( $payment_method ) {
		$this->name = $payment_method;
	}
}
