<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PAFW_Simple_Pay' ) ) :

	class PAFW_Order {
		protected $id = null;
		protected $title = '';
		protected $amount = 0;
		protected $payment_method = '';
		protected $billing_last_name = '홍길동';
		protected $billing_first_name = '';
		protected $billing_phone = '010-3333-3333';
		protected $billing_email = 'a@a.com';

		public function __construct( $title, $amount ) {
			$this->title  = $title;
			$this->amount = $amount;
		}

		public function get_title() {
			return $this->title;
		}

		public function get_items() {
			return array();
		}

		public function get_total() {
			return $this->amount;
		}

		public function set_payment_method( $payment_method ) {
			$this->payment_method = $payment_method;
		}

		public function save() {
			set_transient( $this->get_id(), serialize( $this ) );
		}

		public function get_billing_last_name() {
			return $this->billing_last_name;
		}

		public function get_billing_first_name() {
			return $this->billing_first_name;
		}

		public function get_billing_phone() {
			return $this->billing_phone;
		}

		public function get_billing_email() {
			return $this->billing_email;
		}

		public function get_id() {
			if ( is_null( $this->id ) ) {
				$this->id = 'SP' . strtoupper( bin2hex( openssl_random_pseudo_bytes( 6 ) ) );
			}

			return $this->id;
		}
	}

	class PAFW_Simple_Pay {

		public static function init() {
//			add_filter( 'pafw_get_order', array ( __CLASS__, 'get_order' ), 10, 2 );
			add_filter( 'pafw_product_info', array( __CLASS__, 'product_info' ), 10, 2 );
			add_filter( 'pafw_redirect_url', array( __CLASS__, 'redirect_url' ), 10, 2 );
		}

		public static function get_order( $order, $order_id = null ) {
			if ( is_null( $order_id ) ) {
				if ( 'yes' == pafw_get( $_REQUEST, 'simple_pay' ) ) {
					$title  = wc_clean( $_REQUEST['order_title'] );
					$amount = wc_clean( $_REQUEST['order_amount'] );
					$order  = new PAFW_Order( $title, $amount );
				}
			} else if ( false !== strpos( $order_id, 'SP' ) ) {
				$order = maybe_unserialize( get_transient( $order_id ) );
			}

			return $order;
		}

		public static function product_info( $product_info, $order ) {
			if ( is_a( $order, 'PAFW_Order' ) ) {
				$product_info = $order->get_title();
			}

			return $product_info;
		}

		protected static function process_customer( $data ) {
			if ( 'no' == $_REQUEST['need_shipping'] ) {
				add_filter( 'msaddr_process_billing', '__return_false' );
				add_filter( 'msaddr_process_shipping', '__return_false' );
			}

			$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

			if ( ! is_user_logged_in() && ! empty( $data['createaccount'] ) ) {
				$data = apply_filters( 'pafw_process_customer_data', $data );

				$username    = ! empty( $data['account_username'] ) ? $data['account_username'] : '';
				$password    = ! empty( $data['account_password'] ) ? $data['account_password'] : '';
				$customer_id = wc_create_new_customer(
					$data['billing_email'],
					$username,
					$password,
					array(
						'first_name' => ! empty( $data['billing_first_name'] ) ? $data['billing_first_name'] : '',
						'last_name'  => ! empty( $data['billing_last_name'] ) ? $data['billing_last_name'] : '',
					)
				);

				if ( is_wp_error( $customer_id ) ) {
					throw new Exception( $customer_id->get_error_message() );
				}

				wc_set_customer_auth_cookie( $customer_id );

				update_user_meta( $customer_id, '_mshop_acceptance_of_terms', 'yes' );

				// As we are now logged in, checkout will need to refresh to show logged in data.
				WC()->session->set( 'reload_checkout', true );

				// Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering.
				WC()->cart->calculate_totals();
			}

			// On multisite, ensure user exists on current site, if not add them before allowing login.
			if ( $customer_id && is_multisite() && is_user_logged_in() && ! is_user_member_of_blog() ) {
				add_user_to_blog( get_current_blog_id(), $customer_id, 'customer' );
			}

			if ( $customer_id && apply_filters( 'woocommerce_checkout_update_customer_data', true, WC()->checkout() ) ) {
				$customer = new WC_Customer( $customer_id );

				if ( ! empty( $data['billing_first_name'] ) ) {
					$customer->set_first_name( $data['billing_first_name'] );
				}

				if ( ! empty( $data['billing_last_name'] ) ) {
					$customer->set_last_name( $data['billing_last_name'] );
				}

				// If the display name is an email, update to the user's full name.
				if ( is_email( $customer->get_display_name() ) ) {
					$customer->set_display_name( $data['billing_first_name'] . ' ' . $data['billing_last_name'] );
				}

				foreach ( $data as $key => $value ) {
					// Use setters where available.
					if ( is_callable( array( $customer, "set_{$key}" ) ) ) {
						$customer->{"set_{$key}"}( $value );

						// Store custom fields prefixed with wither shipping_ or billing_.
					} elseif ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) ) {
						$customer->update_meta_data( $key, $value );
					}
				}
				do_action( 'woocommerce_checkout_update_customer', $customer, $data );

				$customer->save();
			}

			do_action( 'woocommerce_checkout_update_user_meta', $customer_id, $data );

			if ( 'no' == $_REQUEST['need_shipping'] ) {
				remove_filter( 'msaddr_process_billing', '__return_false' );
				remove_filter( 'msaddr_process_shipping', '__return_false' );
			}
		}
		public static function validate_params( $data ) {
			if ( empty( $_REQUEST['product_id'] ) ) {
				if ( empty( $_REQUEST['order_title'] ) ) {
					throw new Exception( __( '결제 정보를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
				if ( empty( $_REQUEST['order_amount'] ) || ! is_numeric( $_REQUEST['order_amount'] ) || floatval( $_REQUEST['order_amount'] ) <= 0 ) {
					throw new Exception( __( '결제 금액을 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			}
		}
		public static function create_order( $data, $order ) {
			try {
				self::validate_params( $data );

				if ( $order ) {
					$order->remove_order_items();
					$order_id = self::update_order( $data, $order );
				} else {
					$order_id = self::create_simple_pay_order( $data );
				}

				$order = wc_get_order( $order_id );

				$order_total = 0;

				$variation      = array();
				$cart_item_data = array();

				if ( ! empty( $_POST['variation'] ) ) {
					parse_str( $_POST['variation'], $variation ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}

				if ( ! empty( $_POST['cart_item_data'] ) ) {
					parse_str( $_POST['cart_item_data'], $cart_item_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}

				if ( empty( $_REQUEST['product_id'] ) ) {
					if ( ( 'yes' == get_option( 'woocommerce_calc_taxes', 'no' ) && 'yes' == get_option( 'woocommerce_prices_include_tax' ) ) || 'yes' == pafw_get( $_REQUEST, 'include_tax', 'no' ) ) {
						$order_total = floatval( wp_unslash( $_REQUEST['order_amount'] ) );

						$tax_total = array_sum( WC_Tax::calc_inclusive_tax( $order_total, array( PAFW_Tax::get_vat_rate() ) ) );

						$order_total = $order_total - $tax_total;
					} else {
						$order_total = floatval( wp_unslash( $_REQUEST['order_amount'] ) );
						$tax_total   = 0;
					}

					$item = new WC_Order_Item_Product();
					$item->set_props( array(
						'name'      => sanitize_text_field( $_REQUEST['order_title'] ),
						'quantity'  => absint( wp_unslash( $_REQUEST['quantity'] ) ),
						'variation' => array(),
						'subtotal'  => $order_total,
						'total'     => $order_total,
					) );;

					if ( $tax_total > 0 ) {
						$item->set_total_tax( $tax_total );
						$item->set_subtotal_tax( $tax_total );
					}

					$order->add_item( $item );

					$order->calculate_totals();

					$order->save();
				} else {
					WC()->cart->empty_cart();

					if ( is_array( $_REQUEST['product_id'] ) ) {
						for ( $i = 0; $i < count( $_REQUEST['product_id'] ); $i++ ) {
							WC()->cart->add_to_cart( wc_clean( $_REQUEST['product_id'][ $i ] ), absint( wp_unslash( $_REQUEST['quantity'][ $i ] ) ), wc_clean( $_REQUEST['variation_id'][ $i ] ), wc_clean( $_REQUEST['variation'][ $i ] ), wc_clean( $_REQUEST['cart_item_data'][ $i ] ) );
						}
					} else {
						WC()->cart->add_to_cart( wc_clean( $_REQUEST['product_id'] ), absint( wp_unslash( $_REQUEST['quantity'] ) ), wc_clean( $_REQUEST['variation_id'] ), wc_clean( $_REQUEST['variation'] ), wc_clean( $_REQUEST['cart_item_data'] ) );
					}

					$order->set_shipping_total( WC()->cart->get_shipping_total() );
					$order->set_discount_total( WC()->cart->get_discount_total() );
					$order->set_discount_tax( WC()->cart->get_discount_tax() );
					$order->set_cart_tax( WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax() );
					$order->set_shipping_tax( WC()->cart->get_shipping_tax() );
					$order->set_total( WC()->cart->get_total( 'edit' ) );
					WC()->checkout()->create_order_line_items( $order, WC()->cart );
					WC()->checkout()->create_order_fee_lines( $order, WC()->cart );
					WC()->checkout()->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
					WC()->checkout()->create_order_tax_lines( $order, WC()->cart );
					WC()->checkout()->create_order_coupon_lines( $order, WC()->cart );

					$order->save();
				}
			} catch ( Exception $e ) {
				throw $e;
			}

			return $order;
		}
		public static function update_order( $data, $order ) {
			foreach ( $data as $key => $value ) {
				if ( is_callable( array( $order, "set_{$key}" ) ) ) {
					$order->{"set_{$key}"}( $value );
				} elseif ( ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) )
				           && ! in_array( $key, array( 'shipping_method', 'shipping_total', 'shipping_tax' ) ) ) {
					$order->update_meta_data( '_' . $key, $value );
				}
			}

			$order_id = $order->save();

			do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

			return $order_id;
		}
		public static function validate_post_data( $params ) {
			$errors = array();

			$field_sets = array( 'billing', 'shipping', 'order' );

			foreach ( $field_sets as $field_set ) {
				$fields = WC()->checkout()->get_checkout_fields( $field_set );

				foreach ( $fields as $id => $field ) {
					if ( isset( $params[ $id ] ) && pafw_get( $field, 'required' ) && empty( $params[ $id ] ) ) {
						$errors[] = sprintf( __( '%s을(를) 입력해주세요.', 'pgall-for-woocommerce' ), $field['label'] );
					}
				}
			}

			if ( ! empty( $errors ) ) {
				throw new Exception( implode( "<br>", array_unique( $errors ) ) );
			}
		}
		public static function get_order_for_simple_payment( $uid = null ) {
			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			$params = WC()->checkout()->get_posted_data();

			if ( apply_filters( 'pafw_simple_pay_validate_post_data', true ) ) {
				self::validate_post_data( $params );
			}
			self::process_customer( $params );
			$order = PAFW_Simple_Pay::get_order_awaiting_payment( $uid );

			$order = self::create_order( $params, $order );

			do_action( 'woocommerce_checkout_order_processed', $order->get_id(), $params, $order );

			if ( empty( $order->get_billing_first_name() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 이름은 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( empty( $order->get_billing_email() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 이메일은 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( empty( $order->get_billing_phone() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 전화번호는 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( isset( $_REQUEST['mshop_billing_address-postnum'] ) && isset( $_REQUEST['mshop_billing_address-addr1'] ) ) {
				if ( empty( $_REQUEST['mshop_billing_address-postnum'] ) || empty( $_REQUEST['mshop_billing_address-addr1'] ) ) {
					throw new Exception( __( '주소를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			} else if ( isset( $_REQUEST['billing_postcode'] ) && isset( $_REQUEST['billing_address_1'] ) ) {
				if ( empty( $_REQUEST['billing_postcode'] ) || empty( $_REQUEST['billing_address_1'] ) ) {
					throw new Exception( __( '주소를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			}

			return $order;
		}
		public static function get_order_awaiting_payment( $uid ) {
			$orders = wc_get_orders( array(
				'status'    => array( 'wc-pending', 'wc-failed' ),
				'order_key' => $uid
			) );

			if ( ! empty( $orders ) ) {
				return current( $orders );
			}

			return false;
		}

		public static function create_simple_pay_order( $data ) {
			try {
				$order = new WC_Order();

				foreach ( $data as $key => $value ) {
					if ( is_callable( array( $order, "set_{$key}" ) ) ) {
						$order->{"set_{$key}"}( $value );
					} elseif ( ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) )
					           && ! in_array( $key, array( 'shipping_method', 'shipping_total', 'shipping_tax' ) ) ) {
						$order->update_meta_data( '_' . $key, $value );
					}
				}

				$order->set_created_via( 'checkout' );
				$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ) );
				$order->set_currency( get_woocommerce_currency() );
				$order->set_prices_include_tax( 'yes' == get_option( 'woocommerce_prices_include_tax' ) );
				$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
				$order->set_customer_user_agent( wc_get_user_agent() );
				$order->set_customer_note( isset( $data['order_comments'] ) ? $data['order_comments'] : '' );
				do_action( 'woocommerce_checkout_create_order', $order, $data );

				// Save the order.
				$order_id = $order->save();

				$order = wc_get_order( $order );

				do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

				$order->update_meta_data( '_simple_pay', 'yes' );
				$order->update_meta_data( '_simple_pay_redirect_url', $_SERVER['HTTP_REFERER'] );

				if ( isset( $_REQUEST['order_received_url'] ) ) {
					$order->update_meta_data( '_simple_pay_order_received_url', wp_sanitize_redirect( $_REQUEST['order_received_url'] ) );
				}

				$order->set_order_key( wc_clean( $_REQUEST['_pafw_uid'] ) );
				$order_id = $order->save();

				return $order_id;
			} catch ( Exception $e ) {
				throw $e;
			}
		}
		public static function redirect_url( $redirect_url, $order ) {
			if ( $order && 'yes' == $order->get_meta( '_simple_pay', 'no' ) ) {
				if ( in_array( $order->get_status(), array( 'pending', 'cancelled', 'failed' ) ) ) {
					$redirect_url = $order->get_meta( '_simple_pay_redirect_url' );
				} else {
					$order_received_url = $order->get_meta( '_simple_pay_order_received_url' );

					if ( ! empty( $order_received_url ) ) {
						$redirect_url = $order_received_url;
					}
				}
			}

			return $redirect_url;
		}
	}

	PAFW_Simple_Pay::init();

endif;