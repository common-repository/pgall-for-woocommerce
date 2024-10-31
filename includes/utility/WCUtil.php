<?php


namespace CODEM\PGALL\Utility;


use WC_Order;
use WC_Product;
use WC_Product_Subscription;
use WC_Shipping;
use WC_Subscriptions_Product;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCUtil' ) ) {

	class WCUtil {
		static function get_product_id( $product, $apply_wpml = false ) {
			if ( is_scalar( $product ) ) {
				$product = wc_get_product( $product );
			}

			$product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();

			if ( $apply_wpml ) {
				$product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true, self::wpml_get_default_language() );
			}

			return $product_id;
		}
		static function get_shipping_class( $product, $apply_wpml = false ) {
			if ( is_scalar( $product ) ) {
				$product = wc_get_product( self::get_product_id( $product, $apply_wpml ) );
			}

			$shipping_class_id = $product->get_shipping_class_id();

			if ( $apply_wpml ) {
				$shipping_class_id = apply_filters( 'wpml_object_id', $shipping_class_id, 'product_shipping_class', true, self::wpml_get_default_language() );
			}

			return $shipping_class_id;
		}
		static function get_round_of_renewal_order( $subscription, $order = null ) {
			$round = 1;

			if ( function_exists( 'wcs_is_subscription' ) ) {
				$valid_order_statuses = apply_filters( 'cdm_order_status_for_calculate_renewal_order_round', array( 'completed' ) );

				if ( is_null( $subscription ) ) {
					$subscriptions = wcs_get_subscriptions_for_order( $order );
					$subscription  = reset( $subscriptions );
				}

				$ids = $subscription->get_related_orders( 'ids', array( 'renewal' ) );

				if ( $order ) {
					$ids = array_filter( $ids, function ( $id ) use ( $order ) {
						return $id < $order->get_id();
					} );
				}

				foreach ( $ids as $id ) {
					$related_order = wc_get_order( $id );
					if ( $related_order && apply_filters( 'cdm_is_renewal_order', $related_order->has_status( $valid_order_statuses ), $related_order ) ) {
						$round++;
					}
				}
			}

			return $round;
		}
		static function cart_round_discount( $value, $precision ) {
			if ( ! function_exists( 'wc_cart_round_discount' ) ) {
				include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			}

			return wc_cart_round_discount( $value, $precision );
		}
		static function cart_contains_renewal( $cart ) {
			return function_exists( 'wcs_cart_contains_renewal' ) && ( wcs_cart_contains_renewal() || property_exists( $cart, 'recurring_cart_key' ) );
		}
		static function get_product_price( $product ) {
			if ( wc_tax_enabled() ) {
				if ( $product && $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {
					$trial_length = WC_Subscriptions_Product::get_trial_length( $product );

					if ( $trial_length > 0 ) {
						if ( WC()->cart->tax_display_cart == 'excl' ) {
							$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee_excluding_tax( $product ) );
						} else {
							$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee_including_tax( $product ) );
						}
					} else {
						if ( WC()->cart->tax_display_cart == 'excl' ) {
							$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee_excluding_tax( $product ) ) + floatval( wc_get_price_excluding_tax( $product ) );
						} else {
							$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee_including_tax( $product ) ) + floatval( wc_get_price_including_tax( $product ) );
						}
					}
				} else {
					if ( WC()->cart->tax_display_cart == 'excl' ) {
						$product_price = wc_get_price_excluding_tax( $product );
					} else {
						$product_price = wc_get_price_including_tax( $product );
					}
				}
			} else {
				if ( $product && $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {
					$trial_length = WC_Subscriptions_Product::get_trial_length( $product );

					if ( $trial_length > 0 ) {
						$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee( $product ) );
					} else {
						$product_price = floatval( WC_Subscriptions_Product::get_sign_up_fee( $product ) ) + floatval( $product->get_price() );
					}
				} else {
					$product_price = $product->get_price();
				}
			}

			return floatval( $product_price );
		}
		static function get_category_ids( $product, $apply_wpml = false ) {
			$category_ids = array();

			$terms = get_the_terms( self::get_product_id( $product, $apply_wpml ), 'product_cat' );
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_id = $term->term_id;

					if ( $apply_wpml ) {
						$term_id = apply_filters( 'wpml_object_id', $term_id, 'product_cat', true, self::wpml_get_default_language() );
					}

					$category_ids = array_merge( $category_ids, array( $term_id ), get_ancestors( $term_id, 'product_cat' ) );
				}
			}

			return $category_ids;
		}
		static function target_search_posts_title_like( $where, &$wp_query ) {
			global $wpdb;
			if ( $posts_title = $wp_query->get( 'posts_title' ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE '%%%s%%'", $posts_title );
			}

			return $where;
		}
		static function target_search_product( $product_type = '' ) {
			$keyword = ! empty( $_REQUEST['args'] ) ? sanitize_text_field( $_REQUEST['args'] ) : '';

			add_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ), 10, 2 );

			$args = apply_filters( 'msms_target_search_product', array(
				'post_type'      => 'product',
				'posts_title'    => $keyword,
				'post_status'    => array( 'publish', 'private' ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => 20
			), $keyword );

			if ( ! empty( $product_type ) ) {
				if ( 'subscription' == $product_type ) {
					$product_type = array(
						'subscription',
						'variable-subscription'
					);
				}

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => $product_type,
					),
				);
			}

			$query = new WP_Query( $args );

			remove_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ) );

			$results = array();

			foreach ( $query->posts as $post ) {
				$results[] = array(
					"name"  => '[#' . $post->ID . '] ' . $post->post_title,
					"value" => $post->ID
				);
			}

			return $results;
		}
		static function target_search_shipping_classes() {
			$results          = array();
			$shipping_classes = WC_Shipping::instance()->get_shipping_classes();

			foreach ( $shipping_classes as $shipping_class ) {
				$results[] = array(
					"name"  => $shipping_class->name,
					"value" => $shipping_class->term_id
				);
			}

			return $results;
		}
		static function wpml_get_default_language() {
			if ( has_filter( 'wpml_object_id' ) ) {
				global $sitepress;

				return $sitepress->get_default_language();
			} else {
				return '';
			}
		}
		static function wpml_get_default_language_args() {
			if ( has_filter( 'wpml_object_id' ) ) {
				global $sitepress;

				return 'lang=' . $sitepress->get_default_language() . '&';
			} else {
				return '';
			}
		}
	}
}