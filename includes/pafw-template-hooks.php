<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action( 'woocommerce_account_pafw-ex_endpoint', 'pafw_exchange_return_request' );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_show_payment_details', 5 );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_maybe_output_cash_receipt_details', 6 );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_exchange_return_details' );

add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_type', 10 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_items', 20 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_reason', 30 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_bank_account', 40 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_action', 50 );

add_action( 'woocommerce_checkout_after_order_review', 'pafw_smart_review_form' );

add_action( 'woocommerce_track_order', 'pafw_exchange_return_for_track_order', 100 );

add_action( 'woocommerce_subscription_after_actions', 'pafw_survey_form_for_cancel_subscription', 30 );
add_action( 'woocommerce_subscription_after_actions', 'pafw_output_change_next_payment_date_form', 50 );

add_action( 'woocommerce_after_account_orders', 'pafw_survey_form_for_cancel_order', 30 );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_survey_form_for_cancel_order', 30 );

add_action( 'mshop_email_order_details', 'pafw_email_mshop_show_payment_details', 30, 4 );
add_action( 'woocommerce_email_customer_details', 'pafw_email_mshop_show_payment_details', 99, 4 );

add_action( 'mshop_email_order_details', 'pafw_email_maybe_output_cash_receipt_details', 40, 4 );
add_action( 'woocommerce_email_customer_details', 'pafw_email_maybe_output_cash_receipt_details', 100, 4 );

add_action( 'woocommerce_checkout_order_review', 'pafw_output_payment_tokens', 15 );
add_action( 'woocommerce_pay_order_before_payment', 'pafw_output_payment_tokens', 15 );