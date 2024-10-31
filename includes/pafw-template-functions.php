<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'pafw_exchange_return_details' ) ) {
	function pafw_exchange_return_details( $order ) {

		if ( $exchange_returns = PAFW_Exchange_Return_Manager::get_exchange_return_orders( $order ) ) {

			wp_enqueue_style( 'pafw-frontend', PAFW()->plugin_url() . '/assets/css/frontend.css' );

			wc_get_template( 'order/exchange-return-details.php', array( 'exchange_returns' => $exchange_returns ), '', PAFW()->template_path() );

		}
	}
}

if ( ! function_exists( 'pafw_show_payment_details' ) ) {
	function pafw_show_payment_details( $order ) {
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway && $payment_gateway->supports( 'pafw' ) && ( ! empty( $payment_gateway->get_transaction_id( $order ) ) || ! empty( $order->get_meta( '_pafw_additional_charge_history' ) ) ) ) {
			wp_enqueue_style( 'pafw-frontend', PAFW()->plugin_url() . '/assets/css/frontend.css' );
			wc_get_template( 'order/show-payment-details.php', array( 'order' => $order ), '', PAFW()->template_path() );
		}
	}
}
if ( ! function_exists( 'pafw_exchange_return_request' ) ) {
	function pafw_exchange_return_request() {
		wc_get_template( 'myaccount/select-template.php', array(), '', PAFW()->template_path() );
		wc_get_template( 'myaccount/exchange-return-request.php', array(), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_type' ) ) {
	function pafw_exchange_return_type( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-type.php', array( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_items' ) ) {
	function pafw_exchange_return_items( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-items.php', array( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_reason' ) ) {
	function pafw_exchange_return_reason( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-reason.php', array( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_bank_account' ) ) {
	function pafw_exchange_return_bank_account( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-bank-account.php', array( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_action' ) ) {
	function pafw_exchange_return_action( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-action.php', array( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_smart_review_form' ) ) {
	function pafw_smart_review_form( $review_template = '' ) {
		if ( 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) && 'yes' == get_option( 'pafw-use-smart-review', 'no' ) && pafw_has_enabled_gateways() ) {

			if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
				return;
			}

			$rate_options = get_option( 'pafw-smart-review-rate', array() );

			if ( empty( $rate_options ) ) {
				return;
			}

			if ( empty( $review_template ) ) {
				$review_template = get_option( 'pafw-smart-review-template', 'type1' );
			}

			wc_get_template( 'checkout/smart-review-' . $review_template . '.php', array( 'rate_options' => $rate_options ), '', PAFW()->template_path() );
		}
	}
}
if ( ! function_exists( 'pafw_exchange_return_for_track_order' ) ) {
	function pafw_exchange_return_for_track_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && PAFW_Exchange_Return_Manager::support_exchange_return() && PAFW_Exchange_Return_Manager::can_exchange_return( $order ) ) {
			add_action( 'woocommerce_view_order', 'pafw_output_exchange_return_for_track_order', 10 );
		}
	}
}

if ( ! function_exists( 'pafw_output_exchange_return_for_track_order' ) ) {
	function pafw_output_exchange_return_for_track_order( $order_id ) {
		global $wp;
		$wp->query_vars['pafw-ex'] = $order_id;

		wc_get_template( 'myaccount/exchange-return-request-for-track-order.php', array(), '', PAFW()->template_path() );
	}
}


if ( ! function_exists( 'pafw_output_change_next_payment_date_form' ) ) {
	function pafw_output_change_next_payment_date_form( $subscription ) {
		if ( 'yes' != get_option( 'pafw-subscription-allow-change-date', 'no' ) ) {
			return;
		}

		if ( $subscription && 'active' == $subscription->get_status() && $subscription->can_date_be_updated( 'next_payment' ) ) {
			wp_enqueue_style( 'pafw-subscription', PAFW()->plugin_url() . '/assets/css/myaccount.css', array(), PAFW_VERSION );
			wp_enqueue_script( 'pafw-subscription', PAFW()->plugin_url() . '/assets/js/myaccount.js', array( 'jquery', 'underscore' ), PAFW_VERSION );
			wp_localize_script( 'pafw-subscription', '_pafw_subscription', array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'slug'            => PAFW()->slug(),
				'subscription_id' => $subscription->get_id(),
				'_wpnonce'        => wp_create_nonce( 'pgall-for-woocommerce' )
			) );

			wc_get_template( 'order/change-next-payment-date.php', array( 'subscription' => $subscription ), '', PAFW()->template_path() );
		}
	}
}

if ( ! function_exists( 'pafw_survey_form_for_cancel_subscription' ) ) {
	function pafw_survey_form_for_cancel_subscription( $subscription ) {
		if ( apply_filters( 'pafw_support_survey_form_for_cancel_subscription', true ) ) {
			if ( $subscription && 'active' == $subscription->get_status() && pafw_is_valid_pafw_order( $subscription ) ) {
				wp_enqueue_style( 'pafw-magnific-popup', PAFW()->plugin_url() . '/assets/vendor/magnific-popup/magnific-popup.css', array(), PAFW_VERSION );
				wp_enqueue_script( 'pafw-magnific-popup', PAFW()->plugin_url() . '/assets/vendor/magnific-popup/magnific-popup.min.js', array( 'jquery' ), PAFW_VERSION );

				wp_enqueue_style( 'pafw-subscription', PAFW()->plugin_url() . '/assets/css/myaccount.css', array(), PAFW_VERSION );
				wp_enqueue_script( 'pafw-subscription', PAFW()->plugin_url() . '/assets/js/myaccount.js', array( 'jquery', 'underscore' ), PAFW_VERSION );
				wp_localize_script( 'pafw-subscription', '_pafw_subscription', array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'slug'            => PAFW()->slug(),
					'subscription_id' => $subscription->get_id(),
					'is_mobile'       => wp_is_mobile(),
					'_wpnonce'        => wp_create_nonce( 'pgall-for-woocommerce' )
				) );

				wc_get_template( 'order/subscription-survey-form.php', array( 'subscription' => $subscription ), '', PAFW()->template_path() );
			}
		}
	}
}


if ( ! function_exists( 'pafw_survey_form_for_cancel_order' ) ) {
	function pafw_survey_form_for_cancel_order( $has_orders ) {
		if ( apply_filters( 'pafw_support_survey_form_for_cancel_order', $has_orders ) ) {
			wp_enqueue_style( 'pafw-magnific-popup', PAFW()->plugin_url() . '/assets/vendor/magnific-popup/magnific-popup.css', array(), PAFW_VERSION );
			wp_enqueue_script( 'pafw-magnific-popup', PAFW()->plugin_url() . '/assets/vendor/magnific-popup/magnific-popup.min.js', array( 'jquery' ), PAFW_VERSION );

			wp_enqueue_style( 'pafw-order', PAFW()->plugin_url() . '/assets/css/myaccount.css', array(), PAFW_VERSION );
			wp_enqueue_script( 'pafw-order', PAFW()->plugin_url() . '/assets/js/myaccount.js', array( 'jquery', 'underscore' ), PAFW_VERSION );
			wp_localize_script( 'pafw-order', '_pafw_order', array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'slug'      => PAFW()->slug(),
				'is_mobile' => wp_is_mobile(),
				'_wpnonce'  => wp_create_nonce( 'pgall-for-woocommerce' )
			) );

			wc_get_template( 'order/survey-form.php', array(), '', PAFW()->template_path() );
		}
	}
}

if ( ! function_exists( 'pafw_email_mshop_show_payment_details' ) ) {
	function pafw_email_mshop_show_payment_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin ) {
			return;
		}
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway && $payment_gateway->supports( 'pafw' ) && ! empty( $payment_gateway->get_transaction_id( $order ) ) ) {
			wc_get_template( 'emails/mshop-payment-details.php', array( 'order' => $order, 'email' => $email, ), '', PAFW()->template_path() );
		}
	}
}

if ( ! function_exists( 'pafw_maybe_output_cash_receipt_details' ) ) {
	function pafw_maybe_output_cash_receipt_details( $order ) {
		if ( PAFW_Cash_Receipt::is_enabled() && 'yes' == $order->get_meta( '_pafw_bacs_receipt' ) ) {
			wp_enqueue_style( 'pafw-frontend', PAFW()->plugin_url() . '/assets/css/frontend.css' );
			wc_get_template( 'order/cash-receipt-details.php', array( 'order' => $order ), '', PAFW()->template_path() );
		}
	}
}


if ( ! function_exists( 'pafw_email_maybe_output_cash_receipt_details' ) ) {
	function pafw_email_maybe_output_cash_receipt_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin ) {
			return;
		}

		if ( PAFW_Cash_Receipt::is_enabled() && 'yes' == $order->get_meta( '_pafw_bacs_receipt' ) ) {
			wc_get_template( 'emails/cash-receipt-details.php', array( 'order' => $order, 'email' => $email, ), '', PAFW()->template_path() );
		}
	}
}

if ( ! function_exists( 'pafw_output_payment_tokens' ) ) {
	function pafw_output_payment_tokens() {
		wc_get_template( 'checkout/token.php', array( 'checkout' => WC()->checkout(), ), '', PAFW()->template_path() );
	}
}