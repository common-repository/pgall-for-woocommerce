<?php

if ( ! class_exists( 'PAFW_Exchange_Return_Manager' ) ) {

	class PAFW_Exchange_Return_Manager {

		private static $request_status = array( 'exchange-request', 'return-request' );
		private static $accept_status = array( 'accept-exchange', 'accept-return' );


		private static $valid_order_statuses_for_exchange_return = null;

		private static $cached_orders = array();
		public static function pafw_order_is_editable( $editable, $order ) {
			return $editable || in_array( $order->get_status(), array( 'return-request', 'exchange-request', 'accept-exchange', 'accept-return' ) );
		}

		public static function support_exchange_return() {
			return self::support_exchange() || self::support_return();
		}

		public static function support_exchange() {
			return 'yes' == get_option( 'pafw-gw-support-exchange', 'no' );
		}

		public static function support_return() {
			return 'yes' == get_option( 'pafw-gw-support-return', 'no' );
		}

		public static function get_label() {
			if ( self::support_exchange() && self::support_return() ) {
				return __( '교환 / 반품', 'pgall-for-woocommerce' );
			} else if ( self::support_exchange() ) {
				return __( '교환', 'pgall-for-woocommerce' );
			} else {
				return __( '반품', 'pgall-for-woocommerce' );
			}
		}
		public static function add_exchange_return_actions( $actions, $order ) {
			if ( self::support_exchange_return() && PAFW_Exchange_Return_Manager::can_exchange_return( $order ) ) {
				$actions['exchange_return_request'] = array(
					'url'  => apply_filters( 'pafw_ex_request_url', wc_get_endpoint_url( 'pafw-ex', $order->get_id() ), $order ),
					'name' => self::get_label()
				);
			}

			return $actions;
		}
		protected static function is_virtual( $item ) {
			$product = $item->get_product();

			return $product && $product->is_virtual();
		}
		public static function get_valid_exchange_return_order_items( $order ) {
			$skip_virtual = 'yes' == get_option( 'pafw-gw-ex-skip-virtual', 'no' );

			// Make order items info
			$order_items = array();
			foreach ( $order->get_items() as $key => $item ) {
				if ( ! $skip_virtual || ! self::is_virtual( $item ) ) {
					$order_items[ $key ] = intval( $item['qty'] );
				}
			}

			$exchange_return_orders = self::get_exchange_return_orders( $order );
			if ( ! empty( $exchange_return_orders ) ) {
				// Remove exchanged or returned items from order items info
				foreach ( $exchange_return_orders as $exchage_return_order ) {
					foreach ( $exchage_return_order->get_items() as $item ) {
						$order_items[ $item['exchange_return_item_id'] ] -= $item['qty'];
					}
				}

				// Filter valid order items
				$order_items = array_filter( $order_items, function ( $item ) {
					return $item > 0;
				} );
			}

			return apply_filters( 'pafw_get_valid_exchange_return_order_items', $order_items, $order );
		}
		public static function can_exchange_return( $order ) {
			if ( ! pafw_is_valid_pafw_order( $order ) ) {
				return false;
			}

			$order_statuses = self::get_valid_order_statuses_for_exchange_or_return();

			if ( ! in_array( $order->get_status(), $order_statuses ) ) {
				return false;
			}


			$order_items = self::get_valid_exchange_return_order_items( $order );
			if ( empty( $order_items ) ) {
				return false;
			}
			if ( empty( $order->get_meta( '_pafw_ex_date' ) ) ) {
				return false;
			}

			$today    = date_create( current_time( 'mysql' ) );
			$ex_date  = date_create( $order->get_meta( '_pafw_ex_date' ) );
			$interval = date_diff( $today, $ex_date )->format( '%a' );

			if ( $interval > get_option( 'pafw-gw-ex-terms', '3' ) ) {
				return false;
			}

			return true;
		}
		public static function get_valid_order_statuses_for_exchange_or_return() {
			return apply_filters( 'pafw_get_valid_order_statuses_for_exchange_or_return', array( 'shipped', 'completed' ) );
		}
		public static function get_exchange_return_orders( $order ) {
			$order_id = $order->get_id();
			$orders   = pafw_get( self::$cached_orders, $order_id, null );

			if ( is_null( $orders ) ) {
				self::$cached_orders[ $order_id ] = wc_get_orders( array(
					'type'   => 'shop_order_pafw_ex',
					'parent' => $order_id,
					'limit'  => -1,
				) );

				$orders = pafw_get( self::$cached_orders, $order_id, null );
			}

			return $orders;
		}
		public static function create_exchange_return( $args = array() ) {
			$default_args = array(
				'reason'             => null,
				'order_id'           => 0,
				'exchange_return_id' => 0,
				'order_items'        => array(),
			);

			$args = wp_parse_args( $args, $default_args );

			if ( empty( $args['order_items'] ) ) {
				throw new Exception( __( '교환 또는 반품할 상품을 선택해주세요.' ) );
			}

			if ( empty( $args['reason'] ) ) {
				throw new Exception( __( '교환 또는 반품 사유를 입력해주세요.' ) );
			}

			$order = wc_get_order( $args['order_id'] );

			if ( ! $order ) {
				throw new Exception( __( 'Invalid order ID.', 'woocommerce' ) );
			}

			if ( empty( $args['exchange_return_id'] ) ) {
				$exchange_return_order = new PAFW_Order_Exchange_Return();
				$exchange_return_order->set_download_permissions_granted( true );
				$exchange_return_order->save();

				if ( sizeof( $args['order_items'] ) > 0 ) {
					$order_items = $order->get_items();

					foreach ( array_keys( $args['order_items'] ) as $exchange_return_item_id ) {
						if ( empty( $args['exchange_return_qty'][ $exchange_return_item_id ] ) ) {
							continue;
						}
						$order_item = pafw_get( $order_items, $exchange_return_item_id, null );

						if ( ! is_null( $order_item ) ) {
							$ex_qty = intval( $args['exchange_return_qty'][ $exchange_return_item_id ] );

							$line_item = new WC_Order_Item_Product();
							$line_item->set_props( array(
								'name'         => $order_item->get_name(),
								'quantity'     => $args['exchange_return_qty'][ $exchange_return_item_id ],
								'tax_class'    => $order_item->get_tax_class(),
								'product_id'   => $order_item->get_product_id(),
								'variation_id' => $order_item->get_variation_id(),
							) );

							foreach ( $order_item->get_meta_data() as $meta_data ) {
								$data = $meta_data->get_data();

								if ( apply_filters( 'pafw_copy_meta_data_to_ex_order_item', true, $data ) ) {
									$line_item->update_meta_data( $data['key'], $data['value'] );
								}
							}

							$line_item->set_total( $order_item->get_total() * ( $ex_qty / $order_item->get_quantity() ) );
							$line_item->set_total_tax( $order_item->get_total_tax() * ( $ex_qty / $order_item->get_quantity() ) );
							$line_item->set_subtotal( $order_item->get_subtotal() * ( $ex_qty / $order_item->get_quantity() ) );
							$line_item->set_subtotal_tax( $order_item->get_subtotal_tax() * ( $ex_qty / $order_item->get_quantity() ) );

							if ( ! empty( $args['exchange_item'] ) ) {
								$line_item->update_meta_data( '_exchange_product_id', pafw_get( $args['exchange_item'], $exchange_return_item_id ) );
							}
							$line_item->update_meta_data( '_exchange_return_item_id', $exchange_return_item_id );
							$line_item->save();

							$exchange_return_order->add_item( $line_item );
						}
					}
				}
			} else {
				$exchange_return_order = new PAFW_Order_Exchange_Return( $args['refund_id'] );
			}

			$exchange_return_order->calculate_totals();

			$exchange_return_order->set_parent_id( $order->get_id() );
			$exchange_return_order->set_status( 'processing' );
			$exchange_return_order->set_customer_id( $order->get_customer_id() );
			$exchange_return_order->set_ex_type( pafw_get( $args, 'type' ) );
			$exchange_return_order->set_reason( pafw_get( $args, 'reason' ) );
			$exchange_return_order->set_request( pafw_get( $args, 'requests' ) );

			$exchange_return_order->save();
			if ( ! empty( $args['refund_bank_account'] ) ) {
				$order->add_order_note( sprintf( '[환불계좌정보]<br>교환/반품 번호 : #%d<br>%s', $exchange_return_order->get_id(), $args['refund_bank_account'] ) );
			}

			do_action( 'pafw_exchange_return_created', $exchange_return_order->get_id(), $args );

			// Clear transients
			wc_delete_shop_order_transients( $args['order_id'] );

			return new PAFW_Order_Exchange_Return( $exchange_return_order->get_id() );
		}
		public static function woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {
			if ( self::support_exchange_return() ) {
				if ( in_array( $old_status, self::$accept_status ) || ( in_array( $old_status, self::$request_status ) && ! in_array( $new_status, self::$accept_status ) ) ) {
					$order = wc_get_order( $order_id );

					$ex_orders = self::get_exchange_return_orders( $order );

					if ( ! empty( $ex_orders ) ) {
						foreach ( $ex_orders as $ex_order ) {
							if ( 'processing' == $ex_order->get_status() ) {
								$ex_order->update_status( 'completed' );
							}
						}
					}
				}
				if ( in_array( $new_status, self::get_valid_order_statuses_for_exchange_or_return() ) ) {
					$order = wc_get_order( $order_id );
					$order->update_meta_data( '_pafw_ex_date', current_time( 'mysql' ) );
					$order->save_meta_data();
				}
			}
		}
		public static function maybe_set_download_not_permitted( $is_permitted, $order ) {
			if ( 'shop_order_pafw_ex' == $order->get_type() ) {
				$is_permitted = false;
			}

			return $is_permitted;
		}
	}

}