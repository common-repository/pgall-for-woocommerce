<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class WC_Payment_Token_PAFW extends WC_Payment_Token {
	protected $type = 'PAFW';
	protected $extra_data = array(
		'last4'        => '',
		'expiry_year'  => '',
		'expiry_month' => '',
		'card_type'    => '',
	);
	public function get_display_name( $deprecated = '' ) {
		if ( ! str_starts_with( $this->get_gateway_id(), 'kakaopay' ) && ! empty( $this->get_meta( 'card_num' ) ) ) {
			return sprintf( esc_html__( '%1$s ending in %2$s', 'woocommerce' ),
				esc_html( $this->get_meta( 'card_name' ) ),
				esc_html( substr( $this->get_meta( 'card_num' ), -4 ) )
			);
		} else {
			return esc_html( $this->get_meta( 'card_name' ) );
		}
	}
	protected function get_hook_prefix() {
		return 'woocommerce_payment_token_card_get_';
	}
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		return true;
	}
	public function get_card_type( $context = 'view' ) {
		return $this->get_prop( 'card_type', $context );
	}
	public function set_card_type( $type ) {
		$this->set_prop( 'card_type', $type );
	}
	public function get_expiry_year( $context = 'view' ) {
		return $this->get_prop( 'expiry_year', $context );
	}
	public function set_expiry_year( $year ) {
		$this->set_prop( 'expiry_year', $year );
	}
	public function get_expiry_month( $context = 'view' ) {
		return $this->get_prop( 'expiry_month', $context );
	}
	public function set_expiry_month( $month ) {
		$this->set_prop( 'expiry_month', str_pad( $month, 2, '0', STR_PAD_LEFT ) );
	}
	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}
	public function set_last4( $last4 ) {
		$this->set_prop( 'last4', $last4 );
	}
	public function delete( $force_delete = false ) {
		try {
			$gateway = pafw_get_payment_gateway( $this->get_gateway_id() );
			$token   = $this->get_token();

			if ( ! empty( $token ) ) {
				$gateway->cancel_bill_key( $token );
			}
		} catch ( Exception $e ) {

		}

		parent::delete( $force_delete );
	}
	public function quota_field() {
		ob_start();
		wc_get_template( 'quota/token.php', array( 'payment_token' => $this ), '', PAFW()->template_path() );
		ob_end_flush();
	}
}
