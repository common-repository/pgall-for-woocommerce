<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Tax' ) ) {

	class PAFW_Tax {
		protected static $refund_order = null;

		public static function init() {
			add_filter( 'woocommerce_create_refund', array( __CLASS__, 'maybe_set_refund_order' ), 10, 2 );
		}
		public static function maybe_set_refund_order( $refund, $args ) {
			if ( $args['refund_payment'] ) {
				self::$refund_order = $refund;
			} else {
				self::$refund_order = null;
			}
		}
		protected static function get_refund_for_order( $order ) {
			if ( self::$refund_order && is_a( $order, 'WC_Order' ) && self::$refund_order->get_parent_id() == $order->get_id() ) {
				return self::$refund_order;
			}

			return null;
		}
		public static function get_vat_rate() {
			return apply_filters( 'pafw_get_vat_rate', array(
				'rate'     => 10.0,
				'label'    => __( '부가세', 'pgall-for-woocommerce' ),
				'shipping' => 'yes',
				'compound' => 'no'
			) );
		}
		public static function calculate_tax( $amount ) {
			if ( $amount > 0 ) {
				$taxes = WC_Tax::calc_inclusive_tax( $amount, array( self::get_vat_rate() ) );

				return wc_round_tax_total( array_sum( $taxes ) );
			}

			return 0;
		}
		public static function get_total_tax( $order, $amount = 0 ) {
			if ( $order ) {
				if ( wc_tax_enabled() ) {
					$refund = self::get_refund_for_order( $order );

					if ( $refund ) {
						$total_tax = absint( $refund->get_total_tax() );
					} else if ( $amount > 0 ) {
						$total_tax = self::calculate_tax( $amount );
					} else {
						$total_tax = $order->get_total_tax();
					}
				} else {
					if ( $amount > 0 ) {
						$total_tax = self::calculate_tax( $amount );
					} else {
						$total_tax = self::calculate_tax( $order->get_total() );
					}
				}
			} else {
				$total_tax = self::calculate_tax( $amount );
			}

			return $total_tax;
		}
		public static function get_tax_free_amount( $order, $amount = 0 ) {
			$tax_free_amount = 0;

			if ( $order && wc_tax_enabled() ) {
				$refund = self::get_refund_for_order( $order );

				if ( $refund ) {
					$order = $refund;
				} else if ( $amount > 0 ) {
					return 0;
				}
				foreach ( $order->get_items( array( 'line_item', 'shipping', 'fee' ) ) as $item ) {
					if ( $item->get_total_tax() == 0 ) {
						$tax_free_amount += floatval( $item->get_total() );
					}
				}
			}

			return absint( $tax_free_amount );
		}
		public static function get_tax_amount( $order, $amount = 0 ) {
			$the_order = $order;
			$refund    = self::get_refund_for_order( $order );

			if ( $refund ) {
				$the_order = $refund;
			}

			$order_total = $amount > 0 ? $amount : absint( $the_order->get_total() );

			return $order_total - self::get_tax_free_amount( $order, $amount ) - self::get_total_tax( $order, $amount );
		}
	}

	PAFW_Tax::init();
}