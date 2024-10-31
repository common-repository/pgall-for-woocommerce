<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class PAFW_Meta_Box_Payment_Info {
	protected static $_enqueued = false;
	static function add_meta_boxes( $post_type, $post ) {
		$order = PAFW_HPOS::get_order( $post );

		if ( is_a( $order, 'WC_Order' ) ) {
			$payment_gateway = pafw_get_payment_gateway_from_order( $order );

			if ( $payment_gateway && $payment_gateway instanceof PAFW_Payment_Gateway ) {
				self::enqueue_script( $order );

				if ( ! in_array( $order->get_status(), array( 'pending', 'failed' ) ) ) {
					if ( $payment_gateway->supports( 'subscriptions' ) ) {
						add_meta_box(
							'pafw-order-refund',
							$payment_gateway->get_pg_title() . ' ' . __( '결제내역', 'pgall-for-woocommerce' ),
							array( __CLASS__, 'add_meta_box_subscription_payment_info' ),
							PAFW_HPOS::get_shop_order_screen( array_diff( wc_get_order_types(), array( 'shop_subscription' ) ) ),
							'side',
							'high'
						);
					} else {
						add_meta_box(
							'pafw-order-refund',
							$payment_gateway->get_pg_title() . ' ' . __( '결제내역', 'pgall-for-woocommerce' ),
							array( __CLASS__, 'add_meta_box_payment_info' ),
							PAFW_HPOS::get_shop_order_screen( array_diff( wc_get_order_types(), array( 'shop_subscription' ) ) ),
							'side',
							'high'
						);
					}
				}
				if ( $payment_gateway->is_vbank( $order ) && $payment_gateway->supports( 'pafw-vbank-refund' ) && 'yes' == $order->get_meta( '_pafw_vbank_noti_received' ) && $payment_gateway->is_refundable( $order ) ) {
					add_meta_box(
						'pafw-order-vbank',
						__( '가상계좌 환불', 'pgall-for-woocommerce' ),
						array( __CLASS__, 'add_meta_box_vbank' ),
						PAFW_HPOS::get_shop_order_screen( array_diff( wc_get_order_types(), array( 'shop_subscription' ) ) ),
						'side',
						'high'
					);
				}

				if ( $payment_gateway->is_escrow( $order ) && ! in_array( $order->get_status(), array( 'pending', 'on-hold', 'failed', 'completed', 'cancelled', 'refunded' ) ) ) {
					add_meta_box(
						'pafw-order-escrow',
						__( '에스크로 배송등록', 'pgall-for-woocommerce' ),
						array( __CLASS__, 'add_meta_box_escrow' ),
						PAFW_HPOS::get_shop_order_screen( array_diff( wc_get_order_types(), array( 'shop_subscription' ) ) ),
						'side',
						'high'
					);
				}

				if ( in_array( $order->get_type(), apply_filters( 'pafw_order_types_for_additional_charge', array( 'shop_subscription' ) ) ) && $payment_gateway->supports( 'pafw_additional_charge' ) ) {
					add_meta_box(
						'pafw-order-additional-charge',
						sprintf( __( '%s 추가과금', 'pgall-for-woocommerce' ), $payment_gateway->get_pg_title() ),
						array( __CLASS__, 'add_meta_box_additional_charge' ),
						array( PAFW_HPOS::get_shop_order_screen(), PAFW_HPOS::get_shop_order_screen( 'shop_subscription' ) ),
						'side',
						'high'
					);
				}
			}
		}
	}
	static function add_meta_box_payment_info( $post ) {
		$order = PAFW_HPOS::get_order( $post );
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway ) {
			$transaction_id = $payment_gateway->get_transaction_id( $order );
			$receipt_url    = $payment_gateway->get_transaction_url( $order );

			$is_refundable       = $payment_gateway->is_refundable( $order );
			$is_fully_refundable = $payment_gateway->is_fully_refundable( $order );

			if ( $payment_gateway->is_vbank( $order ) ) {
				$payment_data = self::get_vbank_payment_data( $order, $payment_gateway );
			} else if ( $payment_gateway->is_escrow( $order ) ) {
				$payment_data = self::get_escrow_bank_payment_data( $order, $payment_gateway );
			} else {
				$payment_data = self::get_payment_data( $order, $payment_gateway );
			}
			$cancel_data = self::get_cancel_data( $order, $payment_gateway );

			include( 'views/payment-info.php' );
		}
	}
	static function add_meta_box_subscription_payment_info( $post ) {
		$order = PAFW_HPOS::get_order( $post );
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway ) {
			$transaction_id = $payment_gateway->get_transaction_id( $order );
			$receipt_url    = $payment_gateway->get_transaction_url( $order );

			$is_refundable = $payment_gateway->is_refundable( $order );

			$is_fully_refundable = $payment_gateway->is_fully_refundable( $order );

			$payment_data = self::get_subscription_payment_data( $order, $payment_gateway );

			$cancel_data = self::get_cancel_data( $order, $payment_gateway );

			include( 'views/payment-info.php' );
		}
	}
	static function add_meta_box_escrow( $post ) {
		$order = PAFW_HPOS::get_order( $post );
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway ) {
			$order_status = $order->get_status();

			$delivery_company_name = $payment_gateway->get_escrow_company_name();

			$delivery_register_name = pafw_get( $payment_gateway->settings, 'delivery_register_name' );
			$tracking_number        = $order->get_meta( '_pafw_escrow_tracking_number' );
			$order_cancelled        = $order->get_meta( '_pafw_escrow_order_cancelled' );
			$register_delivery_info = 'yes' == $order->get_meta( '_pafw_escrow_register_delivery_info' );
			$is_cancelled           = 'yes' == $order->get_meta( '_pafw_escrow_order_cancelled' );
			$is_confirmed           = 'yes' == $order->get_meta( '_pafw_escrow_order_confirm' ) || 'yes' == $order->get_meta( '_pafw_escrow_order_confirm_reject' );
			$support_modify_delivery_info = $payment_gateway->supports( 'pafw-escrow-support-modify-delivery-info' );

			include( 'views/escrow.php' );
		}
	}
	static function add_meta_box_vbank( $post ) {
		$order = PAFW_HPOS::get_order( $post );
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway ) {
			include( 'views/vbank.php' );
		}
	}
	static function add_meta_box_additional_charge( $post ) {
		$order = PAFW_HPOS::get_order( $post );
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway ) {
			include( 'views/additional_charge.php' );
		}
	}
	protected static function get_payment_data( $order, $payment_gateway ) {
		$card_info = '';
		$paid_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $order->get_meta( '_pafw_paid_date' ) );
		$card_name = $order->get_meta( '_pafw_card_name' );
		$cart_num  = trim( $order->get_meta( '_pafw_card_num' ) );

		if ( 12 == strlen( $cart_num ) ) {
			$card_num = preg_replace( '/([0-9]{4})([0-9]{4})([0-9]{4})/', '$1-$2-$3-0000', $cart_num );
		} else if ( 16 == strlen( $cart_num ) ) {
			$card_num = implode( '-', str_split( $cart_num, 4 ) );
		}
		if ( ! empty( $card_name ) && ! empty( $card_num ) ) {
			$card_info = sprintf( '%s (%s)', $card_name, $card_num );
		}

		$bank_info = '';
		$bank_code = $order->get_meta( '_pafw_bank_code' );
		$bank_name = $order->get_meta( '_pafw_bank_name' );
		if ( ! empty( $bank_code ) && ! empty( $bank_name ) ) {
			$bank_info = sprintf( '%s [%s]', $bank_name, $bank_code );
		}

		$total_price = $order->get_meta( '_pafw_total_price' );
		if ( empty( $total_price ) ) {
			$total_price = $order->get_total();
		}

		return array(
			array(
				'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), $order->get_payment_method_title() ),
				'data'  => array_filter( array(
					'승인일시'   => $paid_date,
					'카드정보'   => $card_info,
					'은행명'    => $bank_info,
					'이동통신사'  => $order->get_meta( '_pafw_hpp_carrier' ),
					'전화번호'   => $order->get_meta( '_pafw_hpp_num' ),
					'결제금액'   => wc_price( $total_price, array( 'currency' => $order->get_currency() ) ),
					'결제장치'   => $order->get_meta( '_pafw_device_type' ),
					'현금영수증'  => $payment_gateway->get_cash_receipts( $order ),
					'간편결제수단' => $order->get_meta( '_pafw_card_other_pay_type' )
				) )
			)
		);
	}
	protected static function get_subscription_payment_data( $order, $payment_gateway ) {
		$card_info = '';
		$paid_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $order->get_meta( '_pafw_paid_date' ) );
		$card_name = $order->get_meta( '_pafw_card_name' );
		$card_num  = trim( $order->get_meta( '_pafw_card_num' ) );

		if ( 12 == strlen( $card_num ) ) {
			$card_num = preg_replace( '/([0-9]{4})([0-9]{4})([0-9]{4})/', '$1-$2-$3-0000', $card_num );
		} else if ( 16 == strlen( $card_num ) ) {
			$card_num = implode( '-', str_split( $card_num, 4 ) );
		}
		if ( ! empty( $card_name ) && ! empty( $card_num ) ) {
			$card_info = sprintf( '%s (%s)', $card_name, $card_num );
		}

		$bank_info = '';
		$bank_code = $order->get_meta( '_pafw_bank_code' );
		$bank_name = $order->get_meta( '_pafw_bank_name' );
		if ( ! empty( $bank_code ) && ! empty( $bank_name ) ) {
			$bank_info = sprintf( '%s [%s]', $bank_name, $bank_code );
		}

		$total_price = $order->get_meta( '_pafw_total_price' );
		if ( empty( $total_price ) ) {
			$total_price = $order->get_total();
		}

		$subscription_relation = ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) ? __( '갱신', 'pgall-for-woocommerce' ) : __( '신규', 'pgall-for-woocommerce' );

		return array(
			array(
				'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), $order->get_payment_method_title() ),
				'data'  => array_filter( array(
					'정기결제'  => $subscription_relation,
					'승인일시'  => $paid_date,
					'카드정보'  => $card_info,
					'은행명'   => $bank_info,
					'이동통신사' => $order->get_meta( '_pafw_hpp_carrier' ),
					'전화번호'  => $order->get_meta( '_pafw_hpp_num' ),
					'결제금액'  => wc_price( $total_price, array( 'currency' => $order->get_currency() ) ),
					'결제장치'  => $order->get_meta( '_pafw_device_type' ),
					'현금영수증' => $payment_gateway->get_cash_receipts( $order ),
				) )
			)
		);
	}
	protected static function get_cancel_data( $order, $payment_gateway ) {
		if ( 'yes' == $order->get_meta( '_pafw_order_cancelled' ) ) {
			$cancel_date = $order->get_meta( '_pafw_cancel_date' );

			if ( $payment_gateway->is_vbank( $order ) ) {
				return array_filter( array(
					'환불일시' => $cancel_date,
					'환불은행' => $order->get_meta( '_pafw_vbank_refund_bank_name' ),
					'환불계좌' => $order->get_meta( '_pafw_vbank_refund_acc_num' ),
					'예금주'  => $order->get_meta( '_pafw_vbank_refund_acc_name' ),
					'환불사유' => $order->get_meta( '_pafw_vbank_refund_reason' ),
				) );
			} else {
				return array_filter( array(
					'취소일시' => $cancel_date
				) );
			}
		}

		return array();
	}
	protected static function get_escrow_bank_payment_data( $order, $payment_gateway ) {
		$payment_data = array();
		$paid_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $order->get_meta( '_pafw_paid_date' ) );
		$bank_code = $order->get_meta( '_pafw_bank_code' );
		$bank_name = $order->get_meta( '_pafw_bank_name' );

		$payment_data[] = array(
			'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), $order->get_payment_method_title() ),
			'data'  => array(
				'승인일시'  => $paid_date,
				'은행명'   => sprintf( '%s [%s]', $bank_name, $bank_code ),
				'결제장치'  => $order->get_meta( '_pafw_device_type' ),
				'현금영수증' => $payment_gateway->get_cash_receipts( $order )
			)
		);
		$register_delivery_info = 'yes' == $order->get_meta( '_pafw_escrow_register_delivery_info' );
		$delivery_info          = array(
			'배송정보' => $register_delivery_info ? '<span style="color:blue;">등록완료</span>' : '<span style="color:red;">미등록</span>',
		);

		if ( $register_delivery_info ) {
			$delivery_company_name  = $payment_gateway->get_escrow_company_name();
			$delivery_register_name = pafw_get( $payment_gateway->settings, 'delivery_register_name' );
			$tracking_number        = $order->get_meta( '_pafw_escrow_tracking_number' );

			$delivery_info = array_merge( $delivery_info, array(
				'배송정보 등록자' => $delivery_register_name,
				'택배사명'     => $delivery_company_name,
				'송장번호'     => $tracking_number
			) );
		}

		$payment_data[] = array(
			'title' => __( '에스크로 배송정보', 'pgall-for-woocommerce' ),
			'data'  => $delivery_info
		);
		if ( $register_delivery_info ) {
			$is_confirmed = 'yes' == $order->get_meta( '_pafw_escrow_order_confirm' );
			$confirm_time = $order->get_meta( '_pafw_escrow_order_confirm_time' );
			$is_rejected  = 'yes' == $order->get_meta( '_pafw_escrow_order_confirm_reject' );
			$reject_time  = $order->get_meta( '_pafw_escrow_order_confirm_reject_time' );

			if ( $is_confirmed ) {
				$confirm_info['상태']   = __( '<span style="color:blue;">구매확인</span>', 'pgall-for-woocommerce' );
				$confirm_info['확인일시'] = $confirm_time;
			} else if ( $is_rejected ) {
				$confirm_info['상태']   = __( '<span style="color:red;">구매거절</span>', 'pgall-for-woocommerce' );
				$confirm_info['거절일시'] = $reject_time;
			} else {
				$confirm_info['상태'] = __( '<span style="color:red;">구매결정 대기</span>', 'pgall-for-woocommerce' );
			}

			$payment_data[] = array(
				'title' => __( '구매 확인/거절', 'pgall-for-woocommerce' ),
				'data'  => $confirm_info
			);
		}

		return $payment_data;
	}
	protected static function get_vbank_payment_data( $order, $payment_gateway ) {
		$vact_bank_code_name = $order->get_meta( '_pafw_vacc_bank_name' );
		$vact_num            = $order->get_meta( '_pafw_vacc_num' );
		$vact_holder         = $order->get_meta( '_pafw_vacc_holder' );
		$vact_depositor      = $order->get_meta( '_pafw_vacc_depositor' );
		$vact_date           = $order->get_meta( '_pafw_vacc_date' );
		$vact_date_format    = ! empty( $vact_date ) ? date( __( 'Y년 m월 d일', 'pgall-for-woocommerce' ), strtotime( $vact_date ) ) : '';
		$vbank_noti_received = $order->get_meta( '_pafw_vbank_noti_received' );
		$vbank_noti_data     = array(
			'입금상태' => 'yes' == $vbank_noti_received ? '<span style="color:blue;">입금완료</span>' : '<span style="color:red;">입금대기중</span>',
		);
		if ( 'yes' == $vbank_noti_received ) {
			$tranaction_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5', $order->get_meta( '_pafw_vbank_noti_transaction_date' ) );
			$deposit_bank    = $order->get_meta( '_pafw_vbank_noti_deposit_bank' );
			$depositor       = $order->get_meta( '_pafw_vbank_noti_depositor' );

			$vbank_noti_data = array_merge( $vbank_noti_data, array(
				'입금일시' => $tranaction_date,
				'입금은행' => $deposit_bank,
				'입금자'  => $depositor
			) );

			$vbank_noti_data = array_filter( $vbank_noti_data );
		}

		return array(
			array(
				'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), $order->get_payment_method_title() ),
				'data'  => array_filter( array(
					'입금은행'  => $vact_bank_code_name,
					'입금계좌'  => $vact_num,
					'예금주'   => $vact_holder,
					'송금자'   => $vact_depositor,
					'입금기한'  => $vact_date_format,
					'결제장치'  => $order->get_meta( '_pafw_device_type' ),
					'현금영수증' => $payment_gateway->get_cash_receipts( $order )
				) )
			),
			array(
				'title' => __( '입금정보', 'pgall-for-woocommerce' ),
				'data'  => $vbank_noti_data
			)
		);
	}

	protected static function enqueue_script( $order ) {
		if ( ! self::$_enqueued ) {
			$payment_gateway = pafw_get_payment_gateway_from_order( $order );

			if ( $payment_gateway ) {
				$transaction_id = $payment_gateway->get_transaction_id( $order );
				$receipt_url    = $payment_gateway->get_transaction_url( $order );

				wp_enqueue_style( 'pafw-admin', PAFW()->plugin_url() . '/assets/css/admin.css', array(), PAFW_VERSION );

				wp_register_script( 'pafw-admin-js', PAFW()->plugin_url() . '/assets/js/admin.js', array(), PAFW_VERSION );
				wp_enqueue_script( 'pafw-admin-js' );
				wp_localize_script( 'pafw-admin-js', '_pafw_admin', array(
					'action'               => 'refund_request_' . $payment_gateway->id,
					'order_id'             => $order->get_id(),
					'tid'                  => $transaction_id,
					'receipt_url'          => $receipt_url,
					'receipt_popup_params' => $payment_gateway->get_receipt_popup_params(),
					'payment_method'       => $payment_gateway->id,
					'slug'                 => PAFW()->slug(),
					'order_total'          => $order->get_total(),
					'_wpnonce'             => wp_create_nonce( 'pgall-for-woocommerce' )
				) );

				self::$_enqueued = true;
			}
		}
	}
}
