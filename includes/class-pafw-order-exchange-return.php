<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Order_Exchange_Return' ) ) {

	class PAFW_Order_Exchange_Return extends WC_Order {
		protected $data_store_name = 'order-exchange-return';
		protected $object_type = 'pafw_ex';
		protected $extra_data = array(
		);
		public $order_type = 'pafw_ex';
		public function get_type() {
			return 'shop_order_pafw_ex';
		}

		public function get_ex_type() {
			return apply_filters( 'pafw_exchange_return_type', $this->get_meta( '_type' ), $this );
		}

		public function set_ex_type( $type ) {
			$this->update_meta_data( '_type', $type );
		}

		public function is_exchange() {
			return 'exchange' == $this->get_ex_type();
		}

		public function is_return() {
			return 'return' == $this->get_ex_type();
		}

		public function get_reason() {
			return apply_filters( 'pafw_exchange_return_reason', $this->get_meta( '_reason' ), $this );
		}

		public function set_reason( $reason ) {
			$this->update_meta_data( '_reason', $reason );
		}

		public function set_request( $request ) {
			$this->update_meta_data( '_requests', $request );
		}
		public function get_post_title() {
			// @codingStandardsIgnoreStart
			return sprintf( __( 'Exchange or Return &ndash; %s', 'woocommerce' ), ( new DateTime( 'now' ) )->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'woocommerce' ) ) );
			// @codingStandardsIgnoreEnd
		}
	}

}