<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PAFW_Meta_Box_Cash_Receipt {
	static function add_meta_boxes( $post_type, $post ) {
		$order = PAFW_HPOS::get_order( $post );

		if ( is_a( $order, 'WC_Order' ) ) {

			$payment_gateway = pafw_get_payment_gateway_from_order( $order );

			if ( $payment_gateway && 'bacs' == $payment_gateway->id && PAFW_Cash_Receipt::is_enabled() ) {
				add_meta_box(
					'pafw-order-cash-receipt',
					__( '현금영수증', 'pgall-for-woocommerce' ),
					array( __CLASS__, 'add_meta_box_cash_receipt' ),
					PAFW_HPOS::get_shop_order_screen(),
					'side',
					'high'
				);
			}
		}
	}
	static function add_meta_box_cash_receipt( $post ) {
		$order = PAFW_HPOS::get_order( $post );

		if ( $order ) {
			wp_enqueue_style( 'pafw-admin', PAFW()->plugin_url() . '/assets/css/admin.css', array(), PAFW_VERSION );

			wp_register_script( 'pafw-admin-js', PAFW()->plugin_url() . '/assets/js/admin.js', array(), PAFW_VERSION );
			wp_enqueue_script( 'pafw-admin-js' );
			wp_localize_script( 'pafw-admin-js', '_pafw_admin', array(
				'order_id' => $order->get_id(),
				'slug'     => PAFW()->slug(),
				'_wpnonce' => wp_create_nonce( 'pgall-for-woocommerce' )
			) );

			require_once PAFW()->plugin_path() . '/includes/gateways/bacs/class-wc-gateway-pafw-bacs.php';

			$payment_gateway = new WC_Gateway_PAFW_BACS();

			$transaction_id = $order->get_meta( '_pafw_bacs_receipt_tid' );
			$payment_data   = self::get_receipt_data( $order, $payment_gateway );
			$status_data    = self::get_status_data( $order, $payment_gateway );

			include( 'views/cash-receipt.php' );
		}
	}
	protected static function get_receipt_data( $order, $payment_gateway ) {
		$receipt_usage = $payment_gateway->get_receipt_usage_description( $order->get_meta( '_pafw_bacs_receipt_usage' ) );
		$issue_type    = $order->get_meta( '_pafw_bacs_receipt_issue_type' );
		$reg_number    = preg_replace( '~\D~', '', $order->get_meta( '_pafw_bacs_receipt_reg_number' ) );
		if ( 'social' == $issue_type ) {
			$reg_number = preg_replace( '/([0-9]{6})([0-9]{1})([0-9]{6})/', '$1-$2******', $reg_number );
		} else if ( 'phone' == $issue_type ) {
			$reg_number = preg_replace( '/([0-9]{3})([0-9]{4})([0-9]{4})/', '$1-$2-****', $reg_number );
		} else if ( 'card' == $issue_type ) {
			$reg_number = preg_replace( '/([0-9]{4})([0-9]{4})([0-9]{4})([0-9]{4})/', '$1-****-****-$4', $reg_number );
		} else if ( 'biz_reg' == $issue_type ) {
			$reg_number = preg_replace( '/([0-9]{3})([0-9]{2})([0-9]{5})/', '$1-$2-$3', $reg_number );
		}

		return array(
			array(
				'title' => __( '[발급 정보]', 'pgall-for-woocommerce' ),
				'data'  => array_filter( array(
					'용도' => $receipt_usage,
					'종류' => $payment_gateway->get_receipt_issue_type_description( $issue_type ),
					'정보' => $reg_number
				) )
			)
		);
	}
	protected static function get_status_data( $order, $payment_gateway ) {

		$tid = $order->get_meta( '_pafw_bacs_receipt_tid' );

		$issue_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $order->get_meta( '_pafw_bacs_receipt_issue_date' ) );

		return array(
			array(
				'title' => __( '[현금영수증 정보]', 'pgall-for-woocommerce' ),
				'data'  => array_filter( array(
					'발행일'     => $issue_date,
					'현금영수증번호' => $order->get_meta( '_pafw_bacs_receipt_receipt_number' ),
					'결제금액'    => ! empty( $tid ) ? wc_price( $order->get_meta( '_pafw_bacs_receipt_total_price' ) ) : '',
				) )
			)
		);
	}
}
