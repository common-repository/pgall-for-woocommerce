<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Payment_Method_Controller' ) ) :

	class PAFW_Payment_Method_Controller {
		protected static $rules = null;
		public static function init() {
			add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_available_payment_gateways' ), 10, 2 );
			add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'woocommerce_add_to_cart_validation' ), 10, 5 );

			add_filter( 'woocommerce_get_customer_payment_tokens', array( __CLASS__, 'filter_customer_payment_tokens' ), 10, 3 );
		}
		public static function filter_customer_payment_tokens( $tokens, $customer_id, $gateway_id ) {
			if ( WC()->cart && ( is_checkout() || is_checkout_pay_page() ) ) {
				$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
				$gateway_ids        = array_keys( $available_gateways );

				if ( empty( $available_gateways ) || ( '' !== $gateway_id && ! in_array( $gateway_id, $gateway_ids ) ) ) {
					return array();
				}

				foreach ( $tokens as $token_id => $token ) {
					if ( ! in_array( $token->get_gateway_id(), $gateway_ids ) ) {
						unset( $tokens[ $token_id ] );
					}
				}
			}

			return $tokens;
		}
		protected static function get_rules() {
			if ( is_null( self::$rules ) ) {
				self::$rules = array(
					'role'       => get_option( 'pafw-payment-method-by-role', array() ),
					'product'    => get_option( 'pafw-payment-method-by-product', array() ),
					'category'   => get_option( 'pafw-payment-method-by-category', array() ),
					'attributes' => get_option( 'pafw-payment-method-by-attributes', array() ),
					'language'   => get_option( 'pafw-payment-method-by-language', array() ),
					'amount'     => get_option( 'pafw-payment-method-by-amount', array() ),
				);
			}

			return self::$rules;
		}
		public static function get_matched_rules_by_product_ids( $cart_product_ids ) {
			$matched_rules = array();
			$rules         = self::get_rules();
			if ( ! empty( $rules['role'] ) ) {
				$user_roles = pafw_get_current_user_roles();

				foreach ( $rules['role'] as $rule ) {
					if ( 'yes' == $rule['enabled'] && ! empty( $rule['roles'] ) ) {
						$roles = explode( ',', $rule['roles'] );

						if ( ! empty( array_intersect( $user_roles, $roles ) ) ) {
							$matched_rules[] = $rule;
						}
					}
				}
			}
			if ( ! empty( $rules['product'] ) ) {
				foreach ( $rules['product'] as $rule ) {
					if ( 'yes' == $rule['enabled'] && ! empty( $rule['products'] ) ) {
						$product_ids = array_keys( $rule['products'] );

						foreach ( $cart_product_ids['parent_id'] as $cart_product_id ) {
							$cart_product_id = apply_filters( 'wpml_object_id', $cart_product_id, 'product', true, pafw_get_default_language() );
							if ( in_array( $cart_product_id, $product_ids ) ) {
								$matched_rules[] = $rule;
								break;
							}
						}
					}
				}
			}

			if ( ! empty( $rules['category'] ) ) {
				foreach ( $rules['category'] as $rule ) {
					if ( 'yes' == $rule['enabled'] && ! empty( $rule['categories'] ) ) {
						foreach ( $cart_product_ids['parent_id'] as $cart_product_id ) {

							$terms = get_the_terms( $cart_product_id, 'product_cat' );

							if ( ! empty( $terms ) ) {
								$term_ids = array_flip( array_map( function ( $term ) {
									$term_id = apply_filters( 'wpml_object_id', $term->term_id, 'product_cat', true, pafw_get_default_language() );

									return $term_id;
								}, $terms ) );

								if ( ! empty( array_intersect_key( $term_ids, $rule['categories'] ) ) ) {
									$matched_rules[] = $rule;
									break;
								}
							}
						}
					}
				}
			}
			if ( ! empty( $rules['attributes'] ) ) {
				$terms = array();
				foreach ( $cart_product_ids['variations'] as $variation ) {
					$variation_terms = get_terms( $variation['attribute'], array( 'slug' => $variation['slug'] ) );

					if ( ! is_wp_error( $variation_terms ) && is_array( $variation_terms ) ) {
						$terms = array_merge( $terms, $variation_terms );
					}
				}

				$term_ids = array_flip( wp_list_pluck( $terms, 'term_id' ) );

				foreach ( $rules['attributes'] as $rule ) {
					if ( 'yes' == $rule['enabled'] && ! empty( $rule['attributes'] ) ) {
						if ( ! empty( array_intersect_key( $term_ids, pafw_get( $rule, 'attributes', array() ) ) ) ) {
							$matched_rules[] = $rule;
						}
					}
				}
			}

			if ( function_exists( 'icl_object_id' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
				foreach ( $rules['language'] as $rule ) {
					if ( 'yes' == $rule['enabled'] ) {
						if ( in_array( ICL_LANGUAGE_CODE, explode( ',', $rule['language'] ) ) ) {
							$include_country = array_filter( explode( ',', pafw_get( $rule, 'include_country' ) ) );
							$exclude_country = array_filter( explode( ',', pafw_get( $rule, 'exclude_country' ) ) );

							if ( ( ! empty( $include_country ) || ! empty( $exclude_country ) ) && function_exists( 'is_checkout' ) && is_checkout() ) {
								parse_str( wc_clean( $_REQUEST['post_data'] ), $params ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

								if ( '1' == pafw_get( $params, 'ship_to_different_address' ) ) {
									$country = pafw_get( $params, 'shipping_country' );
								} else {
									$country = pafw_get( $params, 'billing_country' );
								}

								if ( empty( $country ) || in_array( $country, $include_country ) || ! in_array( $country, $exclude_country ) ) {
									$matched_rules[] = $rule;
								}
							} else {
								$matched_rules[] = $rule;
							}
						}
					}
				}
			}


			return $matched_rules;
		}
		protected static function get_matched_rules_by_amount( $amount ) {
			$matched_rules = array();
			$rules         = self::get_rules();

			if ( ! empty( $rules['amount'] ) ) {
				foreach ( $rules['amount'] as $rule ) {
					if ( 'yes' == $rule['enabled'] ) {
						$min_amount = pafw_get( $rule, 'min_amount', 0 );
						$max_amount = pafw_get( $rule, 'max_amount', 0 );

						if ( $amount >= $min_amount && $amount <= $max_amount ) {
							$matched_rules[] = $rule;
							break;
						}
					}
				}
			}

			return $matched_rules;
		}
		static function filter_available_payment_gateways_by_product_ids( $payment_gateways, $product_ids ) {

			$rules = self::get_matched_rules_by_product_ids( $product_ids );

			if ( ! empty( $rules ) ) {
				$clear = false;

				$available_methods = array();
				foreach ( $rules as $rule ) {
					if ( ! $clear && empty( $available_methods ) ) {
						$clear = true;

						$available_methods = explode( ',', $rule['payment_methods'] );
					} else {
						$available_methods = array_intersect( $available_methods, explode( ',', $rule['payment_methods'] ) );
					}

				}
				foreach ( $payment_gateways as $gateway_id => $payment_gateway ) {
					if ( ! in_array( $gateway_id, $available_methods ) ) {
						unset( $payment_gateways[ $gateway_id ] );
					}
				}
			}

			return $payment_gateways;
		}
		static function filter_available_payment_gateways_by_amount( $payment_gateways, $amount ) {

			$rules = self::get_matched_rules_by_amount( $amount );

			if ( ! empty( $rules ) ) {
				$clear = false;

				$available_methods = array();
				foreach ( $rules as $rule ) {
					if ( ! $clear && empty( $available_methods ) ) {
						$clear = true;

						$available_methods = explode( ',', $rule['payment_methods'] );
					} else {
						$available_methods = array_intersect( $available_methods, explode( ',', $rule['payment_methods'] ) );
					}

				}
				foreach ( $payment_gateways as $gateway_id => $payment_gateway ) {
					if ( ! in_array( $gateway_id, $available_methods ) ) {
						unset( $payment_gateways[ $gateway_id ] );
					}
				}
			}

			return $payment_gateways;
		}
		static function get_cart_product_ids( $cart = null ) {
			$product_ids = array(
				'variations' => array(),
				'parent_id'  => array()
			);

			if( is_checkout_pay_page() ) {
				$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

				if( $order ) {
					foreach( $order->get_items() as $item ) {
						$product_ids['parent_id'][] = $item->get_product_id();

						foreach( $item->get_all_formatted_meta_data() as $meta ) {
							$product_ids['variations'][] = array(
								'attribute' => $meta->key,
								'slug'      => $meta->value
							);
						}
					}
				}
			}else {
				if ( is_null( $cart ) ) {
					$cart = WC()->cart;
				}

				if ( is_callable( array( $cart, 'get_cart_contents' ) ) ) {
					$cart_contents = $cart->get_cart_contents();
				} else {
					$cart_contents = $cart->cart_contents;
				}

				foreach ( $cart_contents as $content ) {
					$product = $content['data'];

					foreach ( pafw_get( $content, 'variation', array() ) as $attribute => $slug ) {
						$product_ids['variations'][] = array(
							'attribute' => str_replace( 'attribute_', '', $attribute ),
							'slug'      => $slug
						);
					}
					$product_ids['parent_id'][] = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
				}
			}

			return $product_ids;
		}
		static function filter_available_payment_gateways( $payment_gateways ) {
			if ( WC()->cart && ( is_checkout() || is_checkout_pay_page() ) ) {
				$product_ids = self::get_cart_product_ids();

				$payment_gateways = self::filter_available_payment_gateways_by_product_ids( $payment_gateways, $product_ids );

				$total = 0;

				if ( isset( $_GET['pay_for_order'], $_GET['key'] ) ) {
					$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
					$order    = wc_get_order( $order_id );
					if ( $order ) {
						$total = $order->get_total();
					}
				} else {
					$total = WC()->cart->get_cart_contents_total();
				}

				$payment_gateways = self::filter_available_payment_gateways_by_amount( $payment_gateways, $total );

				$payment_gateways = self::get_available_payment_gateways( $payment_gateways );
			}

			return $payment_gateways;
		}
		static function woocommerce_add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
			remove_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_available_payment_gateways' ), 10 );
			if ( empty( WC()->payment_gateways()->get_available_payment_gateways() ) ) {
				return $valid;
			}

			$product_ids = self::get_cart_product_ids();

			foreach ( $variations as $attribute => $slug ) {
				$product_ids['variations'][] = array(
					'attribute' => str_replace( 'attribute_', '', $attribute ),
					'slug'      => $slug
				);
			}
			$product_ids['parent_id'][] = $product_id;
			$product_ids['parent_id']   = array_filter( array_values( $product_ids['parent_id'] ) );

			$available_gateways = self::filter_available_payment_gateways_by_product_ids( WC()->payment_gateways()->get_available_payment_gateways(), $product_ids );

			if ( empty( $available_gateways ) ) {
				$valid   = false;
				$product = wc_get_product( $variation_id ? $variation_id : $product_id );
				wc_add_notice( sprintf( __( '%s 상품은 장바구니에 있는 상품과 함께 구매하실 수 없습니다.', 'pgall-for-woocommerce' ), $product->get_name() ), 'error' );
			}

			add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_available_payment_gateways' ), 10, 2 );

			return $valid;
		}
		public static function get_available_payment_gateways( $available_gateways ) {
			$has_subscription = false;

			if ( isset( $_GET['change_payment_method'] ) ) {
				return $available_gateways;
			}

			if ( apply_filters( 'pafw_use_subscription_checkout', true ) && class_exists( 'WC_Subscriptions_Admin' ) && 'yes' === get_option( WC_Subscriptions_Admin::$option_prefix . '_multiple_purchase', 'no' ) ) {
				return $available_gateways;
			}

			if ( isset( $_GET['pay_for_order'] ) ) {
				$order = wc_get_order( wc_get_order_id_by_order_key( $_GET['key'] ) );

				if ( $order ) {
					foreach ( $order->get_items() as $item ) {
						$product = $item->get_product();

						if ( is_a( $product, 'WC_Product' ) && in_array( $product->get_type(), array( 'subscription', 'variable-subscription', 'subscription_variation' ) ) ) {
							$has_subscription = true;
							break;
						}
					}
				}
			} else {
				if ( ! empty( WC()->cart->cart_contents ) ) {
					foreach ( WC()->cart->cart_contents as $cart_item ) {
						$product = $cart_item['data'];

						if ( is_a( $product, 'WC_Product' ) && in_array( $product->get_type(), array( 'subscription', 'variable-subscription', 'subscription_variation' ) ) ) {
							$has_subscription = true;
							break;
						}
					}
				}
			}

			$has_subscription = apply_filters( 'pafw_cart_has_subscription', $has_subscription, WC()->cart );

			if ( ! $has_subscription ) {
				foreach ( $available_gateways as $gateway_id => $gateway ) {
					if ( apply_filters( 'pafw_remove_payment_gateway', $gateway->supports( 'pafw' ) && $gateway->supports( 'subscriptions' ), $gateway_id, $gateway ) ) {
						unset( $available_gateways[ $gateway_id ] );
					}
				}
			}

			return $available_gateways;
		}

	}

	PAFW_Payment_Method_Controller::init();
endif;
