<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Gateway_PAFW_BACS extends WC_Gateway_BACS {
	public function thankyou_page( $order_id ) {
		if ( apply_filters( 'pafw_bacs_maybe_output_thankyou_page', true, $order_id, $this ) ) {
			parent::thankyou_page( $order_id );
		}

	}
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $sent_to_admin && 'bacs' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			if ( apply_filters( 'pafw_bacs_maybe_output_thankyou_page', true, $order->get_id(), $this ) ) {
				parent::email_instructions( $order, $sent_to_admin, $plain_text );
			}
		}
	}

	public function get_receipt_usage_description( $usage ) {
		$usages = array(
			'ID'  => __( '개인소득공제용', 'pgall-for-woocommerce' ),
			'POE' => __( '사업자증빙용(세금계산서용)', 'pgall-for-woocommerce' ),
		);

		return $usages[ $usage ];
	}

	public function get_receipt_issue_type_description( $issue_type ) {
		$issue_types = array(
			'phone'   => __( '전화번호', 'pgall-for-woocommerce' ),
			'social'  => __( '주민등록번호', 'pgall-for-woocommerce' ),
			'card'    => __( '현금영수증카드번호', 'pgall-for-woocommerce' ),
			'biz_reg' => __( '사업자번호', 'pgall-for-woocommerce' ),
		);

		return $issue_types[ $issue_type ];
	}
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		do_action( 'pafw_bacs_process_payment', $order, $this );

		if ( PAFW_Cash_Receipt::is_enabled() ) {
			if ( 'yes' == pafw_get( $_POST, 'pafw_bacs_receipt_use_default' ) ) {
				$receipt_usage      = get_user_meta( get_current_user_id(), '_pafw_bacs_receipt_usage', true );
				$receipt_issue_type = get_user_meta( get_current_user_id(), '_pafw_bacs_receipt_issue_type', true );
				$reg_number         = get_user_meta( get_current_user_id(), '_pafw_bacs_receipt_reg_number', true );

				$order->update_meta_data( '_pafw_bacs_receipt', 'yes' );
				$order->update_meta_data( '_pafw_bacs_receipt_usage', $receipt_usage );
				$order->update_meta_data( '_pafw_bacs_receipt_issue_type', $receipt_issue_type );
				$order->update_meta_data( '_pafw_bacs_receipt_reg_number', $reg_number );

				PAFW_Cash_Receipt::insert_receipt_request( $order );

				$order->add_order_note( sprintf( __( '<span style="font-size: 0.9em">[현금영수증 발행 정보]<br>용도 : %s<br>종류 : %s<br>정보 : %s</span>', 'pgall-for-woocommerce' ), $this->get_receipt_usage_description( $receipt_usage ), $this->get_receipt_issue_type_description( $receipt_issue_type ), $reg_number ) );
			} else if ( 'yes' == pafw_get( $_POST, 'pafw_bacs_receipt_issue' ) ) {
				$receipt_usage      = trim( $_REQUEST['pafw_bacs_receipt_usage'] );
				$receipt_issue_type = 'biz_reg';
				$reg_number         = trim( $_REQUEST['pafw_bacs_reg_number_POE'] );

				if ( 'ID' == $receipt_usage ) {
					$receipt_issue_type = trim( $_REQUEST['pafw_bacs_receipt_issue_type'] );
					$reg_number         = trim( $_REQUEST[ 'pafw_bacs_reg_number_' . $receipt_usage ] );
				}

				if ( empty( $reg_number ) ) {
					throw new Exception( __( '현금영수증 발행을 위한 정보를 입력해주세요', 'pgall-for-woocommerce' ) );
				}

				$order->update_meta_data( '_pafw_bacs_receipt', 'yes' );
				$order->update_meta_data( '_pafw_bacs_receipt_usage', $receipt_usage );
				$order->update_meta_data( '_pafw_bacs_receipt_issue_type', $receipt_issue_type );
				$order->update_meta_data( '_pafw_bacs_receipt_reg_number', $reg_number );

				PAFW_Cash_Receipt::insert_receipt_request( $order );

				if ( is_user_logged_in() ) {
					if ( 'on' == pafw_get( $_POST, 'pafw_save_bacs_receipt_info' ) ) {
						update_user_meta( get_current_user_id(), '_pafw_bacs_receipt', 'yes' );
						update_user_meta( get_current_user_id(), '_pafw_bacs_receipt_usage', $receipt_usage );
						update_user_meta( get_current_user_id(), '_pafw_bacs_receipt_issue_type', $receipt_issue_type );
						update_user_meta( get_current_user_id(), '_pafw_bacs_receipt_reg_number', $reg_number );
					} else {
						delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt' );
						delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_usage' );
						delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_issue_type' );
						delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_reg_number' );
					}
				}

				$order->add_order_note( sprintf( __( '<span style="font-size: 0.9em">[현금영수증 발행 정보]<br>용도 : %s<br>종류 : %s<br>정보 : %s</span>', 'pgall-for-woocommerce' ), $this->get_receipt_usage_description( $receipt_usage ), $this->get_receipt_issue_type_description( $receipt_issue_type ), $reg_number ) );
			} else {
				$receipt_usage      = 'ID';
				$receipt_issue_type = 'phone';
				$reg_number         = '010-000-1234';

				$order->update_meta_data( '_pafw_bacs_receipt', 'yes' );
				$order->update_meta_data( '_pafw_bacs_receipt_usage', $receipt_usage );
				$order->update_meta_data( '_pafw_bacs_receipt_issue_type', $receipt_issue_type );
				$order->update_meta_data( '_pafw_bacs_receipt_reg_number', $reg_number );

				PAFW_Cash_Receipt::insert_receipt_request( $order );

				if ( is_user_logged_in() ) {
					delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt' );
					delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_usage' );
					delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_issue_type' );
					delete_user_meta( get_current_user_id(), '_pafw_bacs_receipt_reg_number' );
				}
			}
		}

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting the payment).
			$order->update_status( apply_filters( 'woocommerce_bacs_process_payment_order_status', 'on-hold', $order ), __( 'Awaiting BACS payment', 'woocommerce' ) );
		} else {
			$order->payment_complete();
		}

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);

	}

	public function payment_fields() {
		if ( $this->is_available() ) {
			do_action( 'pafw_bacs_payment_fields', $this );

			parent::payment_fields();

			if ( PAFW_Cash_Receipt::is_enabled() ) {
				$default_receipt_info = PAFW_Cash_Receipt::get_user_default_receipt_info( get_current_user_id() );
				$use_default          = ! empty( $default_receipt_info ) && pafw_get( $_POST, 'pafw_bacs_receipt_use_default', 'yes' ) ? 'yes' : 'no';

				wc_get_template( 'pafw/bacs/cash-receipt.php', array( 'use_default' => $use_default, 'default_receipt_info' => $default_receipt_info ), '', PAFW()->template_path() );
			}
		}
	}
}
