<?php

if ( class_exists( 'WC_Subscription' ) && ! class_exists( 'PAFW_Subscription' ) ) {

	class PAFW_Subscription extends WC_Subscription {
		public function set_payment_token( $token ) {
			if ( empty( $token ) || ! ( $token instanceof WC_Payment_Token ) ) {
				return false;
			}

			$this->data_store->update_payment_token_ids( $this, array( $token->get_id() ) );

			do_action( 'woocommerce_payment_token_added_to_order', $this->get_id(), $token->get_id(), $token, array( $token->get_id() ) );

			return $token->get_id();
		}
	}
	
}