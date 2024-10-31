<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Token' ) ) {
	class PAFW_Token {
		protected static $payment_gateways = null;

		public static function init() {
			add_filter( 'pafw_get_order', array( __CLASS__, 'get_order' ), 10, 2 );
			add_filter( 'woocommerce_payment_methods_list_item', array( __CLASS__, 'maybe_change_payment_method_data' ), 10, 2 );
			add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'output_payment_tokens' ) );

			add_action( 'pafw_migrate_token', array( __CLASS__, "migrate_payment_token" ) );

			add_filter( 'woocommerce_get_credit_card_type_label', 'strtoupper', 99 );
		}
		public static function get_order( $order, $order_id ) {
			if ( str_contains( $order_id, 'PAFW-BILL' ) ) {
				$customer_id = str_replace( 'PAFW-BILL-', '', $order_id );

				if ( ! is_numeric( $customer_id ) ) {
					$customer_id = get_transient( '_pafw_' . $order_id );
				}

				$order = get_userdata( $customer_id );
			}

			return $order;
		}
		public static function get_payment_gateways() {
			if ( is_null( self::$payment_gateways ) ) {
				self::$payment_gateways = array();
				foreach ( WC()->payment_gateways()->payment_gateways() as $payment_gateway ) {
					if ( 'yes' == $payment_gateway->enabled && $payment_gateway->supports( 'add_payment_method' ) ) {
						self::$payment_gateways[] = $payment_gateway;
					}
				}
			}

			return self::$payment_gateways;
		}
		public static function get_token_for_order( $order ) {
			$token = null;

			self::maybe_migrate_payment_token_for_user( $order->get_customer_id() );
			if ( function_exists( 'wcs_is_subscription' ) && ! wcs_is_subscription( $order ) ) {
				$subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'any' ) );

				if ( ! empty( $subscriptions ) ) {
					$order = current( $subscriptions );
				}
			}

			$token_ids = $order->get_payment_tokens();

			if ( ! empty( $token_ids ) ) {
				$token_ids = array_reverse( $token_ids );

				foreach ( $token_ids as $token_id ) {
					try {
						$token = new WC_Payment_Token_PAFW( $token_id );
					} catch ( Exception $e ) {

					}

					if ( $token ) {
						break;
					}
				}
			}

			if ( empty( $token ) ) {
				$token = WC_Payment_Tokens::get_customer_default_token( $order->get_customer_id() );

				if ( empty( $token ) ) {
					throw new Exception( __( "결제 가능한 토큰이 없습니다.", "pgall-for-woocommerce" ), 7103 );
				}
			}

			return $token;
		}
		public static function update_token( $order, $token ) {
			if ( $order ) {
				pafw_maybe_set_payment_token( $order, $token );

				if ( ! pafw_is_subscription( $order ) && function_exists( 'wcs_get_subscriptions_for_order' ) ) {
					$subscriptions = wcs_get_subscriptions_for_order( $order->get_id(), array( 'order_type' => 'any' ) );

					foreach ( $subscriptions as $subscription ) {
						pafw_maybe_set_payment_token( $subscription, $token );
					}
				}
			}
		}
		public static function save_token( $response, $order, $gateway, $user_id ) {
			$token = new WC_Payment_Token_PAFW();
			$token->set_token( $response['bill_key'] );
			$token->set_gateway_id( $gateway->id );
			$token->set_user_id( $user_id );

			$metas = apply_filters( "pafw_token_meta", array(
				'pafw_version'  => PAFW_VERSION,
				'auth_date'     => $response['auth_date'],
				'card_code'     => pafw_get( $response, 'card_code' ),
				'card_name'     => pafw_get( $response, 'card_name' ),
				'card_num'      => pafw_get( $response, 'card_num' ),
				'register_date' => pafw_get( $response, 'register_date', current_time( 'mysql' ) )
			), $response, $order, $gateway, $user_id );

			foreach ( $metas as $meta_key => $meta_value ) {
				$token->update_meta_data( $meta_key, $meta_value );
			}

			$token->save();

			self::update_token( $order, $token );

			return $token;
		}
		public static function maybe_change_payment_method_data( $payment_method, $payment_token ) {
			if ( $payment_token instanceof WC_Payment_Token_PAFW ) {
				if ( ! str_starts_with( $payment_method['method']['gateway'], 'kakaopay' ) ) {
					$payment_method['method']['last4'] = substr( $payment_token->get_meta( 'card_num' ), -4 );
				}

				$payment_method['method']['brand'] = $payment_token->get_meta( 'card_name' );

			}

			return $payment_method;
		}
		public static function output_payment_tokens( $fragments ) {
			parse_str( $_POST['post_data'], $params );
			$_POST = array_merge( $_POST, $params );

			ob_start();
			wc_get_template( 'checkout/token.php', array( 'checkout' => WC()->checkout(), ), '', PAFW()->template_path() );
			$token = ob_get_clean();

			$fragments['.woocommerce-checkout-payment-token'] = $token;

			return $fragments;
		}
		public static function maybe_migrate_payment_token_for_user( $user_id ) {
			if ( version_compare( get_user_meta( $user_id, "pafw_version", true ), '5.0.0', '<' ) ) {
				if ( empty( PAFW_Token::get_payment_gateways() ) ) {
					update_user_meta( $user_id, "pafw_version", PAFW_VERSION );

					return;
				}

				$tokens    = array();
				$bill_keys = array();
				foreach ( PAFW_Token::get_payment_gateways() as $gateway ) {
					$bill_key = get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'bill_key' ), true );

					if ( ! empty( $bill_key ) ) {
						$bill_keys[ $gateway->id ] = $bill_key;

						if ( ! isset( $tokens[ $bill_key ] ) ) {
							$tokens[ $bill_key ] = array(
								'gateway'       => $gateway,
								'info'          => array(),
								'subscriptions' => array()
							);

							$tokens[ $bill_key ]['info'] = array(
								'bill_key'            => $bill_key,
								'auth_date'           => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'auth_date' ), true ),
								'card_code'           => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_code' ), true ),
								'card_name'           => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_name' ), true ),
								'card_num'            => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_num' ), true ),
								'payment_method_type' => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'payment_method_type' ), true ),
								'register_date'       => get_user_meta( $user_id, $gateway->get_subscription_meta_key( 'register_date' ), true ),
							);
						}

						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'bill_key' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'auth_date' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_code' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_name' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'card_num' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'payment_method_type' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'register_date' ) );
						delete_user_meta( $user_id, $gateway->get_subscription_meta_key( 'pafw_version' ) );
					}
				}
				$subscriptions = array_filter( wcs_get_users_subscriptions( $user_id ), function ( $subscription ) {
					return $subscription->has_status( array( 'active', 'on-hold' ) );
				} );

				$subscriptions = array_filter( $subscriptions );

				if ( ! empty( $subscriptions ) ) {
					foreach ( $subscriptions as $subscription ) {
						$gateway = pafw_get_payment_gateway_from_order( $subscription );

						if ( $gateway && $gateway->supports( 'add_payment_method' ) ) {
							$bill_key         = $subscription->get_meta( $gateway->get_subscription_meta_key( 'bill_key' ) );
							$default_bill_key = pafw_get( $bill_keys, $gateway->id );

							if ( ! empty( $bill_key ) ) {
								if ( ! isset( $tokens[ $bill_key ] ) ) {
									$tokens[ $bill_key ] = array(
										'gateway'       => $gateway,
										'info'          => array(),
										'subscriptions' => array(
											$subscription
										)
									);

									$tokens[ $bill_key ]['info'] = array(
										'bill_key'            => $bill_key,
										'auth_date'           => $subscription->get_meta( $gateway->get_subscription_meta_key( 'auth_date' ) ),
										'card_code'           => $subscription->get_meta( $gateway->get_subscription_meta_key( 'card_code' ) ),
										'card_name'           => $subscription->get_meta( $gateway->get_subscription_meta_key( 'card_name' ) ),
										'card_num'            => $subscription->get_meta( $gateway->get_subscription_meta_key( 'card_num' ) ),
										'payment_method_type' => $subscription->get_meta( $gateway->get_subscription_meta_key( 'payment_method_type' ) ),
										'register_date'       => $subscription->get_meta( $gateway->get_subscription_meta_key( 'register_date' ) )
									);
								} else {
									$tokens[ $bill_key ]['subscriptions'][] = $subscription;
								}
							} else if ( ! empty( $default_bill_key ) && isset( $tokens[ $default_bill_key ] ) ) {
								$tokens[ $default_bill_key ]['subscriptions'][] = $subscription;
							}
						}
					}
				}

				if ( ! empty( $tokens ) ) {
					foreach ( $tokens as $token_data ) {
						$token = PAFW_Token::save_token( $token_data['info'], null, $token_data['gateway'], $user_id );

						if ( ! empty( $token_data['subscriptions'] ) ) {
							foreach ( $token_data['subscriptions'] as $subscription ) {
								$subscription->add_payment_token( $token );
							}
						}
					}
				}

				update_user_meta( $user_id, "pafw_version", PAFW_VERSION );
			}
		}

		public static function migrate_payment_token( $paged ) {
			$gateways = PAFW_Token::get_payment_gateways();

			if ( ! empty( $gateways ) ) {
				$user_ids = get_users( array(
					'paged'  => $paged,
					'number' => 100,
					'fields' => 'ID'
				) );

				if ( ! empty( $user_ids ) ) {
					foreach ( $user_ids as $user_id ) {
						self::maybe_migrate_payment_token_for_user( $user_id );
					}

					as_schedule_single_action(
						time(),
						"pafw_migrate_token",
						array(
							"paged" => $paged + 1
						)
					);
				}
			}
		}
	}

	PAFW_Token::init();
}