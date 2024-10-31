<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Gateway' ) ) {

	class PAFW_Gateway {
		public static function call( $command, $params, $order, $gateway ) {
			do_action( 'pafw_gateway_before_api_request' );

			$commands = explode( '/', $command );

			$params['gateway'] = array(
				'mall_name'      => get_option( 'blogname' ),
				'merchant_id'    => $gateway->get_merchant_id(),
				'merchant_key'   => $gateway->get_merchant_key(),
				'home_url'       => home_url(),
				'api_url'        => untrailingslashit( WC()->api_request_url( get_class( $gateway ), pafw_check_ssl() ) ),
				'domain'         => preg_replace( "(^https?://|/$)", '', home_url() ),
				'gateway_id'     => $gateway->id,
				'supports'       => $gateway->supports,
				'operation_mode' => pafw_get( $gateway->settings, 'operation_mode', 'sandbox' ),
				'is_mobile'      => wp_is_mobile(),
				'is_ssl'         => pafw_check_ssl(),
				'client_ip'      => pafw_get_ip_address()
			);

			$params = apply_filters( 'pafw_' . implode( '_', $commands ) . '_params_' . $gateway->id, $params, $order );

			$response = wp_remote_post( $gateway->gateway_url( $command ), array(
					'method'      => 'POST',
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'body'        => json_encode( $params, JSON_UNESCAPED_UNICODE ),
					'cookies'     => array()
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), '5001' );
			} else {
				$result = json_decode( $response['body'], true );

				if ( is_null( $result ) ) {
					throw new Exception( '결제 게이트웨이 호출 시 오류가 발생했습니다. 관리자에게 문의해주세요.', '5002' );
				} else {
					if ( '0000' == pafw_get( $result, 'code' ) ) {
						do_action( 'pafw_' . implode( '_', $commands ) . '_response_' . $gateway->id, $order, $result['data'] );

						return $result['data'];
					} else {
						throw new Exception( pafw_get( $result, 'message' ), pafw_get( $result, 'code' ) );
					}
				}
			}
		}
		static function redirect( $order, $gateway, $message = '', $success = true ) {
			header( "Content-Security-Policy: frame-ancestors 'self' " . $gateway->gateway_domain() );

			$redirect_url = home_url();

			if ( pafw_is_subscription( $order ) ) {
				$redirect_url = $order->get_view_order_url();
			} else if ( is_a( $order, 'WC_Order' ) ) {
				if ( $order ) {
					if ( in_array( $order->get_status(), array( 'pending', 'failed' ) ) ) {
						$gateway->add_log( "Redirect : Checkout" );
						$redirect_url = wc_get_checkout_url();
					} else {
						$gateway->add_log( "Redirect : Order Received" );
						$redirect_url = $order->get_checkout_order_received_url();
					}
				} else {
					if ( is_user_logged_in() ) {
						$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id', true );
						if ( ! empty( $myaccount_page_id ) ) {
							$gateway->add_log( "Redirect : My Account" );
							$redirect_url = get_permalink( $myaccount_page_id );
						}
					} else {
						$gateway->add_log( "Redirect : Referer" );
						$redirect_url = $_SERVER['HTTP_REFERER'];
					}
				}
			} else if ( is_a( $order, 'WP_User' ) ) {
				$redirect_url = get_transient( 'pafw_redirect_url_' . $order->ID );

				$redirect_url = empty( $redirect_url ) ? wc_get_account_endpoint_url( 'payment-methods' ) : $redirect_url;

				delete_transient( 'pafw_redirect_url_' . $order->ID );
			} else {
				$gateway->add_log( "Redirect : Home" );
			}

			$redirect_url = apply_filters( 'pafw_redirect_url', $redirect_url, $order );

			if ( $success ) {
				?>
                <html>
                <head>
                    <script>
						<?php if( 'iframe' == $gateway->payment_window_mode() ) : ?>
                        window.top.jQuery.fn.payment_complete( '<?php echo $redirect_url; ?>' );
						<?php elseif( 'page' == $gateway->payment_window_mode() ) : ?>
						<?php
						wp_safe_redirect( $redirect_url );
						die();
						?>
						<?php else : ?>
						<?php if( in_array( $gateway->get_master_id(), array( 'settlebank' ) ) ) : ?>
                        opener.window.top.jQuery.fn.payment_complete( '<?php echo $redirect_url; ?>' );
                        window.close();
						<?php else: ?>
                        opener.window.postMessage( { action: 'pafw_payment_complete', redirect_url: "<?php echo $redirect_url; ?>" }, '<?php echo $gateway->gateway_domain(); ?>' );
						<?php endif; ?>
						<?php endif; ?>
                    </script>
                </head>
                <body></body>
                </html>
				<?php
			} else {
				?>
                <html>
                <head>
                    <script>
						<?php if( 'iframe' == $gateway->payment_window_mode() ) : ?>
                        window.top.jQuery.fn.payment_fail( '<?php echo $message; ?>' );
						<?php elseif( 'page' == $gateway->payment_window_mode() ) : ?>
						<?php
						wc_add_notice( $message, 'error' );
						wp_safe_redirect( $redirect_url );
						die();
						?>
						<?php else : ?>
						<?php if( in_array( $gateway->get_master_id(), array( 'settlebank' ) ) ) : ?>
                        opener.window.top.jQuery.fn.payment_fail( "<?php echo esc_js( $message ); ?>" );
                        window.close();
						<?php else: ?>
                        opener.window.postMessage( { action: 'pafw_payment_fail', message: "<?php echo esc_js( $message ); ?>" }, '<?php echo $gateway->gateway_domain(); ?>' );
						<?php endif; ?>
						<?php endif; ?>
                    </script>
                </head>
                <body></body>
                </html>
				<?php
			}

			die();
		}
		static function register_order( $order, $gateway, $action = 'register_order' ) {
			try {
				pafw_set_browser_information( $order );

				$gateway->has_enough_stock( $order );

				$order_items = $gateway->get_order_items( $order );

				$params = array(
					'order'    => array(
						'id'              => $order->get_id(),
						'key'             => $order->get_order_key(),
						'txnid'           => $gateway->get_txnid( $order ),
						'amount'          => $order->get_total(),
						'currency'        => $order->get_currency(),
						'tax_amount'      => PAFW_Tax::get_tax_amount( $order ),
						'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order ),
						'vat'             => PAFW_Tax::get_total_tax( $order ),
						'item_count'      => count( $order_items ),
						'items'           => $order_items
					),
					'customer' => array(
						'user_id'    => $order->get_customer_id(),
						'user_name'  => pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() ),
						'user_phone' => pafw_get_customer_phone_number( $order ),
						'user_email' => $order->get_billing_email(),
						'client_ip'  => pafw_get_ip_address()
					)
				);

				$response = self::call( $action, $params, $order, $gateway );

				if ( ! pafw_is_subscription( $order ) ) {
					$order->set_payment_method( $gateway );
					$order->update_meta_data( 'pafw_transaction_id', $response['transaction_id'] );
					$order->save();
				}

				return $response;
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		static function get_register_form( $user, $gateway, $action = 'subscription/register_form' ) {
			try {
//				set_transient( 'pafw_redirect_url', $_SERVER['HTTP_REFERER'], 5 * MINUTE_IN_SECONDS );
				$customer_id = $user->ID;

				$txnid = 'PAFW-BILL-' . $customer_id;

				$params = array(
					'customer' => array(
						'user_id'    => $user->ID,
						'user_name'  => pafw_remove_emoji( get_user_meta( $customer_id, 'billing_last_name', true ) . get_user_meta( $customer_id, 'billing_first_name', true ) ),
						'user_phone' => pafw_get_customer_phone_number( null, $customer_id ),
						'user_email' => get_user_meta( $customer_id, 'billing_email', true ),
						'client_ip'  => pafw_get_ip_address(),
						'txnid'      => $txnid
					)
				);

				return self::call( $action, $params, $user, $gateway );
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		static function process_approval( $gateway, $order, $action = 'approval' ) {
			if ( $order && 'on-hold' != $order->get_status() ) {
				$gateway->has_enough_stock( $order );
			}

			$params = array(
				'order' => array(
					'transaction_id' => wc_clean( pafw_get( $_GET, 'transaction_id' ) ),
					'auth_token'     => wc_clean( pafw_get( $_GET, 'auth_token' ) ),
					'order_id'       => $order->get_id(),
					'key'            => $order->get_order_key(),
					'txnid'          => $gateway->get_txnid( $order ),
					'amount'         => $order->get_total(),
				)
			);

			$response = self::call( $action, $params, $order, $gateway );

			$order->update_meta_data( '_pafw_payment_method', ! empty( $response['payment_method'] ) ? $response['payment_method'] : $gateway->id );
			$order->update_meta_data( '_pafw_txnid', $response['txnid'] );
			$order->update_meta_data( '_pafw_paid_date', $response['paid_date'] );
			$order->update_meta_data( '_pafw_total_price', $response['total_price'] );
			$order->save_meta_data();

			if ( ! empty( $response['transaction_id'] ) ) {
				$gateway->payment_complete( $order, $response['transaction_id'] );
			}

			return $response;
		}
		static function request_approval( $gateway, $order, $action = 'approval' ) {
			try {
				self::process_approval( $gateway, $order, $action );

				PAFW_Gateway::redirect( $order, $gateway );

			} catch ( Exception $e ) {
				$gateway->handle_exception( $e, $order );
			}
		}
		static function request_cancel( $order, $msg, $code, $gateway, $transaction_id = '' ) {
			$params = array(
				'order'       => array(
					'id'       => $order->get_id(),
					'txnid'    => $gateway->get_txnid( $order ),
					'amount'   => $order->get_total(),
					'currency' => $order->get_currency(),
				),
				'cancel_info' => array(
					'order_id'        => $order->get_id(),
					'amount'          => $order->get_total(),
					'tax_amount'      => PAFW_Tax::get_tax_amount( $order ),
					'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order ),
					'vat'             => PAFW_Tax::get_total_tax( $order ),
					'partial_refund'  => '0',
					'transaction_id'  => empty( $transaction_id ) ? $gateway->get_transaction_id( $order ) : $transaction_id,
					'message'         => $msg,
					'is_paid'         => $order->is_paid()
				)
			);

			self::call( 'cancel', $params, $order, $gateway );

			if ( $gateway->supports( 'subscriptions' ) && class_exists( 'WC_Subscriptions_Manager' ) ) {
				WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
			}

			do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $gateway );

			return true;
		}
		static function register_shipping( $order, $gateway, $action = 'register_shipping' ) {
			try {
				$order_items = array();

				foreach ( $order->get_items() as $item_id => $item ) {
					$order_items[] = array(
						'item_id'    => $item_id,
						'product_id' => $item->get_product_id(),
						'amount'     => ( $item->get_total() / $item->get_quantity() ),
						'quantity'   => $item->get_quantity(),
						'name'       => wp_strip_all_tags( $item->get_name() ),
					);
				}

				$params = array(
					'order'    => array(
						'id'             => $order->get_id(),
						'transaction_id' => $order->get_transaction_id(),
						'txnid'          => $gateway->get_txnid( $order ),
						'amount'         => $order->get_total(),
						'item_count'     => $order->get_item_count(),
						'items'          => $order_items
					),
					'customer' => array(
						'user_id'            => $order->get_customer_id(),
						'user_name'          => pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() ),
						'user_phone'         => pafw_get_customer_phone_number( $order ),
						'user_email'         => $order->get_billing_email(),
						'shipping_name'      => pafw_remove_emoji( $order->get_shipping_last_name() . $order->get_shipping_first_name() ),
						'shipping_postcode'  => $order->get_shipping_postcode(),
						'shipping_address_1' => $order->get_shipping_address_1(),
						'shipping_address_2' => $order->get_shipping_address_2(),
						'shipping_phone'     => is_callable( array( $order, 'get_shipping_phone' ) ) ? $order->get_shipping_phone() : $order->get_meta( '_shipping_phone' )
					),
				);

				$response = self::call( $action, $params, $order, $gateway );

				$order->update_meta_data( '_pafw_escrow_tracking_number', pafw_get( $response, 'sheet_no' ) );
				$order->update_meta_data( '_pafw_escrow_register_delivery_info', 'yes' );
				$order->update_meta_data( '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );
				$order->save_meta_data();

				$order->add_order_note( __( '판매자님께서 고객님의 에스크로 결제 주문을 배송 등록 또는 수정 처리하였습니다.', 'pgall-for-woocommerce' ), true );

				return $response;
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		static function vbank_refund( $order, $gateway ) {
			try {
				$params = array(
					'order' => array(
						'id'             => $order->get_id(),
						'transaction_id' => $order->get_transaction_id(),
					),
				);

				self::call( 'vbank_refund', $params, $order, $gateway );

				$order->update_status( 'refunded', __( '관리자의 요청으로 주문건의 가상계좌 환불처리가 완료되었습니다.', 'pgall-for-woocommerce' ) );

				$order->update_meta_data( '_pafw_vbank_refunded', 'yes' );
				$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
				$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );
				$order->save_meta_data();

				$gateway->add_log( sprintf( '가상계좌 환불처리 요청 성공. 주문번호 : %s', $order->get_id() ) );

				wp_send_json_success( __( '관리자의 요청으로 주문건의 가상계좌 환불처리가 완료되었습니다.', 'pgall-for-woocommerce' ) );
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		static function purchase_decided( $order, $gateway, $action = 'purchase_decided' ) {
			try {
				$order_items = array();

				foreach ( $order->get_items() as $item_id => $item ) {
					$order_items[] = array(
						'item_id'    => $item_id,
						'product_id' => $item->get_product_id(),
						'amount'     => ( $item->get_total() / $item->get_quantity() ),
						'quantity'   => $item->get_quantity(),
						'name'       => wp_strip_all_tags( $item->get_name() ),
					);
				}

				$params = array(
					'gateway' => $gateway->get_gateway_params(),
					'order'   => array(
						'id'             => $order->get_id(),
						'transaction_id' => $order->get_transaction_id(),
						'txnid'          => $gateway->get_txnid( $order )
					),
				);

				$response = self::call( $action, $params, $order, $gateway );

				if ( 'confirm' == $response['action'] ) {
					$order->update_status( 'completed' ); //주문처리완료 상태
					$order->update_meta_data( '_pafw_escrow_order_confirm', 'yes' );
					$order->update_meta_data( '_pafw_escrow_order_confirm_time', current_time( 'mysql' ) );
					$order->add_order_note( sprintf( __( '고객님께서 에스크로 구매확인을 <font color=blue><strong>확정</strong></font>하였습니다. 거래번호 : %s, 결과코드 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $response['transaction_id'], $response['res_cd'], $response['decided_date'], $response['decided_time'] ) );
				} else {
					$order->update_status( 'cancel-request' );  //주문처리완료 상태로 변경
					$order->update_meta_data( '_pafw_escrow_order_confirm_reject', 'yes' );
					$order->update_meta_data( '_pafw_escrow_order_confirm_reject_time', current_time( 'mysql' ) );
					$order->add_order_note( sprintf( __( '고객님께서 에스크로 구매확인을 <font color=red><strong>거절</strong></font>하였습니다. 거래번호 : %s, 결과코드 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $response['transaction_id'], $response['res_cd'], $response['decided_date'], $response['decided_time'] ) );
				}

				$order->save_meta_data();

				return $response;
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		public static function issue_bill_key( $order, $gateway ) {
			if ( 'api' == $gateway->issue_bill_key_mode() ) {
				$order_items = array();

				if ( $order ) {
					foreach ( $order->get_items() as $item ) {
						$order_items[] = array(
							'id'     => $item->get_product_id(),
							'amount' => ( $item->get_total() / $item->get_quantity() ),
							'name'   => urlencode( wp_strip_all_tags( $item->get_name() ) ),
						);
					}
				}

				if ( $order ) {
					$user_id    = $order->get_customer_id();
					$user_name  = pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() );
					$user_phone = pafw_get_customer_phone_number( $order );
					$user_email = $order->get_billing_email();
				} else {
					$user_id    = get_current_user_id();
					$user_name  = pafw_remove_emoji( get_user_meta( $user_id, 'billing_last_name', true ) . get_user_meta( $user_id, 'billing_first_name', true ) );
					$user_phone = pafw_get_customer_phone_number( null, $user_id );
					$user_email = get_user_meta( $user_id, 'billing_email', true );
				}

				$params = array(
					'order'    => array(
						'id'    => $order ? $order->get_id() : date( 'YmdHis' ),
						'txnid' => $gateway->get_txnid( $order )
					),
					'customer' => array(
						'user_id'    => $user_id,
						'user_name'  => $user_name,
						'user_phone' => $user_phone,
						'user_email' => $user_email,
						'client_ip'  => pafw_get_ip_address(),
					)
				);
			} else {
				$params = array(
					'order' => array(
						'transaction_id' => wc_clean( pafw_get( $_GET, 'transaction_id' ) ),
						'auth_token'     => wc_clean( pafw_get( $_GET, 'auth_token' ) ),
					)
				);
			}

			$response = self::call( 'bill_key', $params, $order, $gateway );

			$token = PAFW_Token::save_token( $response, $order, $gateway, $order->get_customer_id() );

			$gateway->add_payment_log( $order, '[ 빌링키 발급 ]', $gateway->get_title() );

			return $token;
		}
		public static function register_complete( $user, $gateway, $action = 'subscription/register_complete' ) {
			if ( 'transaction' == $gateway->issue_bill_key_mode() ) {
				$params = array(
					'order' => array(
						'transaction_id' => wc_clean( pafw_get( $_GET, 'transaction_id' ) ),
						'auth_token'     => wc_clean( pafw_get( $_GET, 'auth_token' ) ),
					)
				);
			} else {
				$params = array();
			}

			$response = self::call( $action, $params, $user, $gateway );

			PAFW_Token::save_token( $response, null, $gateway, $user->ID );

			return $response;
		}
		public static function add_payment_method( $user, $gateway, $action = 'subscription/register_complete' ) {
			if ( 'transaction' == $gateway->issue_bill_key_mode() ) {
				$params = array(
					'order' => array(
						'transaction_id' => wc_clean( pafw_get( $_GET, 'transaction_id' ) ),
						'auth_token'     => wc_clean( pafw_get( $_GET, 'auth_token' ) ),
					)
				);
			} else {
				$params = array();
			}

			$response = self::call( $action, $params, $user, $gateway );

			return $response;
		}
		public static function request_subscription_payment( $order, $gateway, $params = array(), $token = null ) {
			$is_renewal           = pafw_get( $params, 'is_renewal', false );
			$is_additional_charge = pafw_get( $params, 'is_additional_charge', false );
			$amount_to_charge     = pafw_get( $params, 'amount_to_charge', 0 );
			$card_quota           = pafw_get( $params, 'card_quota', pafw_get( $_REQUEST, 'pafw_' . $gateway->get_master_id() . '_card_quota', '00' ) );
			$bill_key             = pafw_get( $params, 'bill_key' );

			if ( empty( $bill_key ) ) {
				if ( is_null( $token ) ) {
					$token = PAFW_Token::get_token_for_order( $order );
				}

				$bill_key = $token->get_token();
			}

			if ( ! $is_renewal ) {
				pafw_set_browser_information( $order );
			}

			if ( empty( $bill_key ) ) {
				throw new Exception( __( '[PAFW-ERR-5001] 빌키 정보가 없습니다.', 'pgall-for-woocommerce' ) );
			}

			$order_items = $gateway->get_order_items( $order );

			$params = array(
				'order'    => array(
					'transaction_id'  => pafw_get( $_GET, 'transaction_id' ),
					'auth_token'      => pafw_get( $_GET, 'auth_token' ),
					'id'              => $order->get_id(),
					'txnid'           => $is_additional_charge ? $order->get_id() . '_' . date( "ymd" ) . '_' . date( "his" ) : $gateway->get_txnid( $order ),
					'currency'        => $order->get_currency(),
					'amount'          => $is_additional_charge ? $amount_to_charge : $order->get_total(),
					'tax_amount'      => PAFW_Tax::get_tax_amount( $order, $is_additional_charge ? $amount_to_charge : 0 ),
					'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order, $is_additional_charge ? $amount_to_charge : 0 ),
					'vat'             => PAFW_Tax::get_total_tax( $order, $is_additional_charge ? $amount_to_charge : 0 ),
					'item_count'      => count( $order_items ),
					'items'           => $order_items
				),
				'customer' => array(
					'user_id'    => $order->get_customer_id(),
					'user_name'  => pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() ),
					'user_phone' => pafw_get_customer_phone_number( $order ),
					'user_email' => $order->get_billing_email(),
					'client_ip'  => pafw_get_ip_address()
				),
				'payment'  => array(
					'bill_key'             => $bill_key,
					'is_additional_charge' => $is_additional_charge,
					'card_quota'           => $card_quota,
				)
			);

			$response = self::call( 'subscription_payment', $params, $order, $gateway );

			if ( ! $is_additional_charge ) {
				$order->update_meta_data( '_pafw_payment_method', $gateway->id );
				$order->update_meta_data( '_pafw_paid_date', pafw_get( $response, 'paid_date' ) );
				$order->update_meta_data( '_pafw_card_code', pafw_get( $response, 'card_code' ) );
				$order->update_meta_data( '_pafw_card_name', pafw_get( $response, 'card_name' ) );
				$order->update_meta_data( '_pafw_card_num', pafw_get( $response, 'card_num' ) );
				$order->update_meta_data( '_pafw_card_quota', pafw_get( $response, 'card_quota' ) );
				$order->update_meta_data( '_pafw_total_price', pafw_get( $response, 'total_price' ) );
				$order->save_meta_data();

				$gateway->add_payment_log( $order, '[ 정기결제 성공 ]', array(
					'거래번호' => $response['transaction_id']
				) );

				$gateway->payment_complete( $order, $response['transaction_id'] );

				do_action( 'pafw_subscription_payment_completed', $response, $order, $gateway );
			} else {
				do_action( 'pafw_payment_action', 'completed', $amount_to_charge, $order, $gateway );
			}

			return array(
				'transaction_id' => $response['transaction_id'],
				'paid_date'      => $response['paid_date']
			);
		}
		public static function cancel_additional_charge( $order, $gateway ) {
			$params = array(
				'order'       => array(
					'id'       => $order->get_id(),
					'txnid'    => $gateway->get_txnid( $order ),
					'amount'   => wc_clean( $_REQUEST['amount'] ),
					'currency' => $order->get_currency(),
				),
				'cancel_info' => array(
					'order_id'        => $order->get_id(),
					'amount'          => wc_clean( $_REQUEST['amount'] ),
					'tax_amount'      => PAFW_Tax::get_tax_amount( $order, wc_clean( $_REQUEST['amount'] ) ),
					'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order, wc_clean( $_REQUEST['amount'] ) ),
					'vat'             => PAFW_Tax::get_total_tax( $order, wc_clean( $_REQUEST['amount'] ) ),
					'partial_refund'  => '0',
					'transaction_id'  => wc_clean( $_REQUEST['tid'] ),
					'message'         => __( '추가과금취소', 'pgall-for-woocommerce' ),
				)
			);

			self::call( 'cancel', $params, $order, $gateway );

			do_action( 'pafw_payment_action', 'cancelled', wc_clean( $_REQUEST['amount'] ), $order, $gateway );

			$gateway->add_payment_log( $order, '[ 추가 과금 취소 성공 ]', array(
				'거래요청번호' => wc_clean( $_REQUEST['tid'] ),
				'취소금액'   => wc_price( wc_clean( $_REQUEST['amount'] ), array( 'currency' => $order->get_currency() ) )
			) );

			$history = $order->get_meta( '_pafw_additional_charge_history' );

			$history[ wc_clean( $_REQUEST['tid'] ) ]['status'] = 'CANCELED';

			$order->update_meta_data( '_pafw_additional_charge_history', $history );
			$order->save_meta_data();
		}
		public static function cancel_bill_key( $bill_key, $gateway ) {
			$params = array(
				'gateway' => $gateway->get_gateway_params(),
				'payment' => array(
					'bill_key' => $bill_key
				)
			);

			self::call( 'cancel_bill_key', $params, null, $gateway );
		}
		public static function process_refund( $order_id, $amount, $reason, $gateway ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$is_fully_refund = $order->get_total() == $order->get_total_refunded() && $amount == $order->get_total_refunded();

				$params = array(
					'order'       => array(
						'id'       => $order->get_id(),
						'txnid'    => $gateway->get_txnid( $order ),
						'amount'   => $amount,
						'currency' => $order->get_currency(),
					),
					'cancel_info' => array(
						'order_id'        => $order_id,
						'amount'          => $amount,
						'tax_amount'      => PAFW_Tax::get_tax_amount( $order, $amount ),
						'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order, $amount ),
						'vat'             => PAFW_Tax::get_total_tax( $order, $amount ),
						'remain_amount'   => $order->get_total() - $order->get_total_refunded(),
						'partial_refund'  => $is_fully_refund ? '0' : '1',
						'transaction_id'  => $gateway->get_transaction_id( $order ),
						'message'         => empty( $reason ) ? __( '관리자 환불', 'pgall-for-woocommerce' ) : $reason,
					),
					'customer'    => array(
						'user_name'  => pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() ),
						'user_phone' => pafw_get_customer_phone_number( $order ),
						'user_email' => $order->get_billing_email()
					)
				);

				do_action( 'pafw_before_partial_refund', $params, $order, $gateway );

				$response = self::call( 'cancel', $params, $order, $gateway );

				do_action( 'pafw_payment_action', 'cancelled', $amount, $order, $gateway );

				if ( $order->get_total() - $order->get_total_refunded() <= 0 ) {
					$order->update_meta_data( '_pafw_order_cancelled', 'yes' );
					$order->update_meta_data( '_pafw_cancel_date', current_time( 'mysql' ) );

					if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
						WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
					}

					$order->save_meta_data();
				} else {
					do_action( 'pafw_partial_refund', $params, $order, $gateway );
				}

				$gateway->add_payment_log( $order, '[ 결제 취소 성공 ]', array(
					'거래요청번호' => $response["transaction_id"],
					'취소금액'   => wc_price( $amount, array( 'currency' => $order->get_currency() ) ),
					'취소사유'   => empty( $reason ) ? __( '관리자 환불', 'pgall-for-woocommerce' ) : $reason
				) );

				return true;

			}

			return false;
		}
		static function issue_cash_receipt( $order, $gateway, $action = 'cash/receipt' ) {
			try {
				$refund_orders = $order->get_refunds();

				$refunds = array(
					'amount'          => 0,
					'tax_amount'      => 0,
					'tax_free_amount' => 0,
					'vat'             => 0,
				);

				if ( ! empty( $refund_orders ) ) {
					foreach ( $refund_orders as $refund_order ) {
						$refunds['amount']          += absint( $refund_order->get_total() );
						$refunds['vat']             += absint( $refund_order->get_total_tax() );
						$refunds['tax_free_amount'] += PAFW_Tax::get_tax_free_amount( $refund_order );
						$refunds['tax_amount']      += $refunds['amount'] - $refunds['vat'] - $refunds['tax_free_amount'];
					}
				}

				$order_items = $gateway->get_order_items( $order );

				$params = array(
					'order'    => array(
						'id'              => $order->get_id(),
						'key'             => $order->get_order_key(),
						'txnid'           => $gateway->get_txnid( $order ),
						'amount'          => $order->get_total() - $refunds['amount'],
						'currency'        => $order->get_currency(),
						'tax_amount'      => PAFW_Tax::get_tax_amount( $order ) - $refunds['tax_amount'],
						'tax_free_amount' => PAFW_Tax::get_tax_free_amount( $order ) - $refunds['tax_free_amount'],
						'vat'             => PAFW_Tax::get_total_tax( $order ) - $refunds['vat'],
						'item_count'      => count( $order_items ),
						'items'           => $order_items
					),
					'customer' => array(
						'user_id'    => $order->get_customer_id(),
						'user_name'  => pafw_remove_emoji( $order->get_billing_last_name() . $order->get_billing_first_name() ),
						'user_phone' => pafw_get_customer_phone_number( $order ),
						'user_email' => $order->get_billing_email(),
						'client_ip'  => pafw_get_ip_address()
					)
				);

				return self::call( $action, $params, $order, $gateway );
			} catch ( Exception $e ) {
				throw new Exception( sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
			}
		}
		static function cancel_cash_receipt( $order, $msg, $gateway ) {
			$params = array(
				'cancel_info' => array(
					'order_id'        => $order->get_id(),
					'amount'          => $order->get_meta( '_pafw_bacs_receipt_total_price' ),
					'tax_amount'      => $order->get_meta( '_pafw_bacs_receipt_tax_amount' ),
					'tax_free_amount' => $order->get_meta( '_pafw_bacs_receipt_tax_free_amount' ),
					'vat'             => $order->get_meta( '_pafw_bacs_receipt_vat' ),
					'partial_refund'  => '0',
					'transaction_id'  => $order->get_meta( '_pafw_bacs_receipt_tid' ),
					'message'         => $msg
				)
			);

			self::call( 'cash/cancel', $params, $order, $gateway );

			return true;
		}
	}

}