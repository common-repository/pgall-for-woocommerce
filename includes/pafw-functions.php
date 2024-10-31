<?php
add_action( 'pafw_process_payment', array( 'PAFW_Session', 'process_payment' ) );
add_action( 'pafw_thankyou_page', array( 'PAFW_Session', 'thankyou_page' ) );
add_action( 'pafw_payment_cancel', array( 'PAFW_Session', 'payment_cancel' ) );
add_action( 'pafw_payment_fail', array( 'PAFW_Session', 'payment_fail' ), 10, 3 );
add_action( 'woocommerce_before_checkout_form', array( 'PAFW_Session', 'clear_session' ) );
add_action( 'pafw_cancel_unfinished_payment_request', array( 'PAFW_Session', 'cancel_unfinished_payment_request' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_Inicis', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_Nicepay', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_Kcp', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_LGUPlus', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_Payco', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array( 'WC_Gateway_PAFW_KakaoPay', 'checkout_sections' ) );

add_action( 'woocommerce_order_status_changed', array( 'PAFW_Exchange_Return_Manager', 'woocommerce_order_status_changed' ), 10, 3 );
add_filter( 'woocommerce_my_account_my_orders_actions', array( 'PAFW_Exchange_Return_Manager', 'add_exchange_return_actions' ), 10, 2 );
add_filter( 'wc_order_is_editable', array( 'PAFW_Exchange_Return_Manager', 'pafw_order_is_editable' ), 10, 2 );
add_filter( 'woocommerce_order_is_download_permitted', array( 'PAFW_Exchange_Return_Manager', 'maybe_set_download_not_permitted' ), 10, 2 );
add_action( 'woocommerce_admin_order_items_after_line_items', 'PAFW_Meta_Box_Order_Items::output_exchange_return_request', 10, 3 );

add_filter( 'pafw_payment_script_params', array( 'WC_Gateway_Lguplus', 'add_script_params' ) );
add_filter( 'msex_get_additional_charge', array( 'PAFW_Exporter', 'get_additional_charge' ), 10, 2 );
add_filter( 'msex_get_partial_refund', array( 'PAFW_Exporter', 'get_partial_refund' ), 10, 2 );
add_filter( 'woocommerce_my_account_my_orders_actions', 'pafw_my_account_my_orders_actions', 99, 2 );
function pafw_my_account_my_orders_actions( $actions, $order ) {
	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {
		$actions = $payment_gateway->my_account_my_orders_actions( $actions, $order );
	} else if ( 'bacs' == $order->get_payment_method() || is_null( $payment_gateway ) ) {
		$cancel_order_statuses = explode( ",", get_option( 'pafw-gw-possible_refund_status_for_mypage', 'pending,on-hold' ) );

		if ( $order->has_status( $cancel_order_statuses ) ) {
			$cancel_endpoint    = get_permalink( wc_get_page_id( 'cart' ) );
			$myaccount_endpoint = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );

			$actions['cancel'] = array(
				'url'  => wp_nonce_url( add_query_arg( array(
					'pafw-cancel-order' => 'true',
					'order_key'         => $order->get_order_key(),
					'order_id'          => $order->get_id(),
					'redirect'          => $myaccount_endpoint
				), $cancel_endpoint ), 'pafw-cancel-order-' . $order->get_id() . '-' . $order->get_order_key() ),
				'name' => __( 'Cancel', 'woocommerce' )
			);
		}
	}

	return $actions;
}
add_action( 'woocommerce_view_order', 'pafw_wc_action', 5 );
add_action( 'woocommerce_email_before_order_table', 'pafw_wc_action', 5 );
add_action( 'mshop_email_customer_details', 'pafw_wc_action', 20 );
function pafw_wc_action( $order_id ) {
	$action = current_action();
	$order  = wc_get_order( $order_id );

	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array( $payment_gateway, $action ) ) ) {
		$payment_gateway->$action( $order_id, $order );
	}
}
add_filter( 'woocommerce_payment_complete_order_status', 'pafw_woocommerce_payment_complete_order_status', 10, 3 );
function pafw_woocommerce_payment_complete_order_status( $order_status, $order_id, $order = null ) {
	if ( apply_filters( 'pafw_use_payment_complete_order_status_control', true ) ) {
		if ( is_null( $order ) ) {
			$order = wc_get_order( $order_id );
		}

		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array( $payment_gateway, 'woocommerce_payment_complete_order_status' ) ) ) {
			$order_status = $payment_gateway->woocommerce_payment_complete_order_status( $order_status, $order_id, $order );
		} else if ( apply_filters( 'pafw_apply_payment_complete_order_status_control_for_all_payment_gateways', false ) ) {
			$order_status = get_option( 'pafw-gw-order_status_after_payment', 'processing' );
		}

		$order_status = PAFW_Order_Status_Controller::get_order_status( $order_status, $order_id, $order );
	}

	return $order_status;
}
add_filter( 'woocommerce_get_checkout_order_received_url', 'pafw_woocommerce_get_checkout_order_received_url', 99, 2 );
function pafw_woocommerce_get_checkout_order_received_url( $url, $order ) {
	if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {

			$checkout_pid = wc_get_page_id( 'checkout' );
			if ( ! empty( $_REQUEST['lang'] ) ) {
				if ( function_exists( 'icl_object_id' ) ) {
					$checkout_pid = icl_object_id( $checkout_pid, 'page', true, wc_clean( $_REQUEST['lang'] ) );
				}
			}

			$url = wc_get_endpoint_url( 'order-received', $order->get_id(), get_permalink( $checkout_pid ) );

			if ( pafw_check_ssl() ) {
				$url = str_replace( 'http:', 'https:', $url );
			}

			$url = add_query_arg( 'key', $order->get_order_key(), $url );
		}
	}

	return $url;
}
function pafw_get_order_cancel_url( $order, $redirect_url = '' ) {
	$cancel_url = '';

	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway->supports( 'pafw' ) && $payment_gateway->is_refundable( $order, 'mypage' ) ) {
		$cancel_endpoint = get_permalink( wc_get_page_id( 'cart' ) );

		if ( empty( $redirect_url ) ) {
			$redirect_url = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );
		}

		$redirect_url = apply_filters( 'pafw_redirect_url_after_cancel_order', $redirect_url, $order );

		$cancel_url = wp_nonce_url( add_query_arg( array(
			'pafw-cancel-order' => 'true',
			'order_key'         => $order->get_order_key(),
			'order_id'          => $order->get_id(),
			'redirect'          => $redirect_url
		), $cancel_endpoint ), 'pafw-cancel-order-' . $order->get_id() . '-' . $order->get_order_key() );
	}

	return $cancel_url;
}
function pafw_cancel_order() {
	if ( isset( $_GET['pafw-cancel-order'] ) && isset( $_GET['order_key'] ) && isset( $_GET['order_id'] ) ) {

		try {
			if ( ! is_user_logged_in() && 'no' == get_option( 'pafw-gw-support-cancel-guest-order', 'no' ) ) {
				throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '7001' );
			}

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'pafw-cancel-order-' . $_GET['order_id'] . '-' . $_GET['order_key'] ) ) {
				throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '7002' );
			}

			$order = wc_get_order( absint( wp_unslash( $_GET['order_id'] ) ) );

			if ( $order && $_GET['order_key'] == $order->get_order_key() ) {
				$customer_id = $order->get_customer_id();

				if ( ( is_user_logged_in() && $customer_id == get_current_user_id() ) || ( ! is_user_logged_in() && 'yes' == get_option( 'pafw-gw-support-cancel-guest-order', 'no' ) ) ) {
					$payment_gateway = pafw_get_payment_gateway_from_order( $order );

					if ( $order->get_total() > 0 ) {
						if ( 'bacs' == $order->get_payment_method() ) {
							if ( ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
								$order->update_status( 'cancel-request' );
							} else {
								$order->update_status( 'cancelled' );
							}
						} else if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {
							add_filter( 'pafw_force_update_order_status_to_cancel', '__return_false' );
							if ( $payment_gateway->supports( 'pafw-vbank' ) && ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
								$order->update_status( 'cancel-request' );
							} else {
								$payment_gateway->cancel_order( $order );
							}

							remove_filter( 'pafw_force_update_order_status_to_cancel', '__return_false' );
						}
					} else {
						$order->update_status( 'cancelled' );
					}
				} else {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '7003' );
				}
			} else {
				throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '7004' );
			}
		} catch ( Exception $e ) {
			wc_add_notice( sprintf( "[PAFW-ERR-%d] %s", $e->getCode(), $e->getMessage() ), 'error' );
		}

		if ( empty( $_GET['redirect'] ) ) {
			echo '<meta http-equiv="refresh" content="0; url=' . wc_get_account_endpoint_url( 'orders' ) . '" />';
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ), 302 );
		} else {
			echo '<meta http-equiv="refresh" content="0; url=' . wc_clean( $_GET['redirect'] ) . '" />';
			wp_safe_redirect( wc_clean( $_GET['redirect'] ), 302 );
		}
		die();
	}
}

add_action( 'init', 'pafw_cancel_order', 20 );
function pafw_get( $array, $key, $default = '' ) {
	return ! empty( $array[ $key ] ) ? wc_clean( $array[ $key ] ) : $default;
}
function pafw_get_ip_address() {
	if ( class_exists( 'WC_Geolocation' ) && is_callable( array( 'WC_Geolocation', 'get_ip_address' ) ) ) {
		$ip_address = WC_Geolocation::get_ip_address();
	} else {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}

	if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$ip_address = '0.0.0.0';
	}

	return $ip_address;
}
function pafw_get_gmdate( $date ) {
	return date( 'Y-m-d H:i:s', strtotime( $date . ' - ' . get_option( 'gmt_offset', 0 ) . ' HOURS' ) );
}
function pafw_has_enabled_gateways() {
	$enabled_gateways = PAFW()->get_enabled_payment_gateways();

	if ( empty( $enabled_gateways ) ) {
		return false;
	}

	$available_gateways = WC()->payment_gateways()->payment_gateways();

	foreach ( $available_gateways as $available_gateway ) {
		if ( $available_gateway->supports( 'pafw' ) && 'yes' == $available_gateway->enabled ) {
			return true;
		}
	}

	return false;
}
function pafw_get_payment_gateway_from_order( $order ) {
	if ( $order ) {
		return pafw_get_payment_gateway( $order->get_payment_method() );
	}

	return null;
}
function pafw_is_valid_pafw_order( $order ) {
	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	return empty( $payment_gateway ) || $payment_gateway instanceof PAFW_Payment_Gateway || ( $payment_gateway && 'bacs' == $payment_gateway->id );
}
function pafw_get_payment_gateway( $payment_method ) {
	$class              = 'WC_Gateway_' . ucwords( $payment_method, '_' );
	$available_gateways = WC()->payment_gateways()->payment_gateways();

	if ( ! empty( $available_gateways[ $payment_method ] ) ) {
		return $available_gateways[ $payment_method ];
	} else if ( class_exists( $class, true ) ) {
		return new $class;
	}

	return null;
}
function pafw_get_settings( $id ) {
	$class = 'PAFW_Settings_' . ucwords( $id, '_' );

	if ( class_exists( $class, true ) ) {
		return new $class;
	}

	return null;
}
function pafw_reduce_order_stock( $order ) {
	if ( $order && ! $order->get_data_store()->get_stock_reduced( $order->get_id() ) ) {
		do_action( 'pafw_before_reduce_order_stock', $order );

		wc_reduce_stock_levels( $order->get_id() );

		do_action( 'pafw_after_reduce_order_stock', $order );
	}
}
function pafw_set_browser_information( $order ) {
	$order->update_meta_data( '_pafw_device_type', wp_is_mobile() ? __( 'MOBILE', 'pgall-for-woocommerce' ) : __( 'PC', 'pgall-for-woocommerce' ) );
	$order->update_meta_data( '_pafw_user_agent', pafw_get( $_SERVER, 'HTTP_USER_AGENT' ) );
	$order->save_meta_data();
}
function pafw_check_ssl() {
	return apply_filters( 'pafw_check_ssl', is_ssl() || 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) );
}
function pafw_get_ajax_url() {
	if ( function_exists( 'icl_object_id' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
		$ajax_url = admin_url( 'admin-ajax.php?lang=' . ICL_LANGUAGE_CODE, pafw_check_ssl() ? 'https' : 'http' );
	} else {
		$ajax_url = admin_url( 'admin-ajax.php', pafw_check_ssl() ? 'https' : 'http' );
	}

	return $ajax_url;
}
function pafw_order_need_shipping( $order ) {
	foreach ( $order->get_items() as $item ) {

		$product = $item->get_product();
		if ( ! $product || ! $product->is_virtual() ) {
			return true;
		}
	}

	return false;
}
function pafw_update_payment_method( $order, $gateway ) {
	$order->set_payment_method( $gateway );
	$order->save();
}
function pafw_set_payment_method_title( $order, $gateway, $description = '' ) {
	$order->set_payment_method_title( empty( $description ) ? $gateway->title : $gateway->title . ' - ' . $description );
}

add_action( 'woocommerce_settings_start', 'pafw_woocommerce_settings_start' );
function pafw_woocommerce_settings_start() {
	add_filter( 'admin_url', 'pafw_admin_url', 10, 3 );
}

function pafw_admin_url( $url, $path, $blog_id ) {
	$supported_gateways = PAFW()->get_supported_gateways();

	foreach ( $supported_gateways as $gateway ) {
		$pos = strpos( $url, 'section=' . $gateway . '_' );
		if ( $pos !== false ) {
			$url = substr( $url, 0, $pos ) . 'section=mshop_' . $gateway;

			return $url;
		}
	}

	return $url;
}
function pafw_get_user_roles() {
	return array_diff_key( array_merge( wp_roles()->role_names, array( 'guest' => __( 'Guest', 'pgall-for-woocommerce' ) ) ), array( 'administrator' => '' ) );
}
function pafw_get_current_user_roles() {
	if ( is_user_logged_in() ) {
		$user = get_userdata( get_current_user_id() );

		return $user->roles;
	} else {
		return array( 'guest' );
	}
}
function pafw_convert_to_utf8( $str ) {
	if ( 'EUC-KR' == mb_detect_encoding( $str, array( 'UTF-8', 'EUC-KR' ) ) ) {
		$str = mb_convert_encoding( $str, 'UTF-8', 'EUC-KR' );
	}

	return $str;
}
add_action( 'woocommerce_checkout_order_processed', array( 'PAFW_Review', 'save_review_info' ), 10, 3 );
add_action( 'woocommerce_order_status_changed', array( 'PAFW_Review', 'register_review' ), 10, 3 );
if ( ! function_exists( 'pafw_get_default_language_args' ) ) {
	function pafw_get_default_language_args() {
		if ( function_exists( 'icl_object_id' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
			return 'lang=' . ICL_LANGUAGE_CODE . '&';
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'pafw_get_default_language' ) ) {
	function pafw_get_default_language() {
		if ( function_exists( 'icl_object_id' ) ) {
			global $sitepress;

			return $sitepress ? $sitepress->get_default_language() : '';
		} else {
			return '';
		}
	}
}

add_action( 'woocommerce_order_status_changed', array( 'PAFW_Order_Status_Controller', 'maybe_register_scheduled_action' ), 10, 3 );
add_action( 'pafw_order_status_transition', array( 'PAFW_Order_Status_Controller', 'maybe_change_order_status' ), 10, 3 );
add_action( 'woocommerce_cancel_unpaid_orders', array( 'PAFW_Order_Status_Controller', 'maybe_cancel_failed_orders' ) );
add_filter( 'msm_submit_action', 'PAFW_MShop_Members::submit_action' );
add_filter( 'msm_form_classes', 'PAFW_MShop_Members::add_form_classes', 10, 2 );
add_filter( 'mfd_output_forms_pafw_payment', 'PAFW_MShop_Members::output_unique_id' );
add_filter( 'msm_get_field_rules', 'PAFW_MShop_Members::add_field_rules', 10, 2 );

function pafw_get_customer_info() {
	$customer_info = array();

	if ( is_user_logged_in() ) {
		try {
			$user = new WC_Customer( get_current_user_id() );

			if ( $user ) {
				$customer_info = array(
					'name'     => $user->get_billing_last_name() . $user->get_billing_first_name(),
					'phone'    => $user->get_billing_phone(),
					'email'    => $user->get_billing_email(),
					'postcode' => $user->get_billing_postcode(),
					'address1' => $user->get_billing_address_1(),
					'address2' => $user->get_billing_address_2()
				);
			}
		} catch ( Exception $e ) {

		}
	}

	return apply_filters( 'pafw_get_customer_info', $customer_info );
}
function pafw_get_customer_phone_number( $order, $user_id = 0 ) {

	if ( $order ) {
		$phone_number = preg_replace( "/[^0-9]*/s", "", $order->get_billing_phone() );
	} else {
		$phone_number = preg_replace( "/[^0-9]*/s", "", get_user_meta( $user_id, 'billing_phone', true ) );
	}

	return apply_filters( 'pafw_get_customer_phone_number', $phone_number, $order );
}

function pafw_remove_emoji( $clean_text ) {
	//step #1
	$clean_text = preg_replace( '/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $clean_text );

	//step #2
	// Match Emoticons
	$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
	$clean_text     = preg_replace( $regexEmoticons, '', $clean_text );

	// Match Miscellaneous Symbols and Pictographs
	$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
	$clean_text   = preg_replace( $regexSymbols, '', $clean_text );

	// Match Transport And Map Symbols
	$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
	$clean_text     = preg_replace( $regexTransport, '', $clean_text );

	// Match Miscellaneous Symbols
	$regexMisc  = '/[\x{2600}-\x{26FF}]/u';
	$clean_text = preg_replace( $regexMisc, '', $clean_text );

	// Match Dingbats
	$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
	$clean_text    = preg_replace( $regexDingbats, '', $clean_text );

	return $clean_text;
}

function pafw_get_quotas( $max = 36 ) {
	$quotas = array();

	for ( $i = 2; $i <= $max; $i++ ) {
		$quotas[ $i ] = $i . '개월';
	}

	return $quotas;
}
function pafw_is_subscription( $order ) {
	return function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order );
}
function pafw_get_renewal_time( $renewal_time = '' ) {
	if ( 'yes' == get_option( 'pafw-subscription-force-renewal-time', 'no' ) ) {
		$time_begin = get_option( 'pafw-subscription-renewal-time-begin', '09:00' );
		$time_end   = get_option( 'pafw-subscription-renewal-time-end', '18:00' );

		if ( $time_begin != $time_end ) {
			$time_begin = strtotime( $time_begin );
			$time_end   = strtotime( $time_end );

			$next_payment_time = rand( $time_begin, $time_end );
		} else {
			$next_payment_time = strtotime( $time_begin );
		}

		$renewal_time = date( 'H:i:00', $next_payment_time );
	}

	return $renewal_time;
}
function pafw_subscription_adjust_renewal_time( $subscription_id, $from_status, $to_status, $subscription ) {
	if ( 'active' == $to_status ) {
		$renewal_time = pafw_get_renewal_time();

		if ( ! empty( $renewal_time ) ) {
			$next_payment_date = strtotime( $subscription->get_date( 'next_payment' ) ) + get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;

			$next_payment_date = strtotime( date( 'Y-m-d', $next_payment_date ) . ' ' . $renewal_time ) - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;

			$subscription->update_dates( array( 'next_payment' => date( 'Y-m-d H:i:s', $next_payment_date ) ) );
			$subscription->save_dates();
		}
	}
}

add_action( 'woocommerce_subscription_status_changed', 'pafw_subscription_adjust_renewal_time', 10, 4 );

function pafw_get_review_templates() {
	return apply_filters( 'pafw-review-template', array(
		'type1' => "Smile",
		'type2' => "Rating Black",
		'type3' => "Rating White",
		'type4' => "Count",
	) );
}

add_action( 'woocommerce_order_status_changed', array( 'PAFW_Cash_Receipt', 'maybe_process_cash_receipt' ), 10, 3 );

add_filter( 'wcs_renewal_order_created', function ( $renewal_order, $subscription ) {

	$renewal_order->delete_meta_data( '_pafw_txnid' );
	$renewal_order->delete_meta_data( '_pafw_write_smart_review' );
	$renewal_order->delete_meta_data( '_pafw_smart_review_rate' );
	$renewal_order->delete_meta_data( '_pafw_smart_review_content' );
	$renewal_order->delete_meta_data( '_pafw_smart_review_registered' );

	$renewal_order->save_meta_data();

	return $renewal_order;
}, 10, 2 );

add_action( 'woocommerce_subscription_date_updated', function ( $subscription, $date_type, $datetime ) {
	if ( is_user_logged_in() && 'yes' == get_option( 'pafw-subscription-allow-change-date', 'no' ) && 'next_payment' == $date_type ) {
		$user = get_userdata( get_current_user_id() );
		$subscription->add_order_note( sprintf( __( '다음 결제일 변경됨 : %s by (#%d, %s)', 'pgall-for-woocommerce' ), date( 'Y-m-d', strtotime( $datetime ) ), get_current_user_id(), $user->display_name ), false, ! current_user_can( 'manage_woocommerce' ) );
	}
}, 10, 3 );

add_filter( 'pafw_enqueue_scripts', function ( $enqueue ) {
	return $enqueue && ( defined( 'PAFW_SIMPLE_PAYMENT' ) || 'yes' != get_option( 'pafw-use-woocommerce-blocks', 'no' ) );
} );
function pafw_maybe_set_payment_token( $order, $token ) {
	try {
		$gateway = pafw_get_payment_gateway( $token->get_gateway_id() );

		if ( $gateway ) {
			if ( pafw_is_subscription( $order ) ) {
				$pafw_order = new PAFW_Subscription( $order );
				$pafw_order->set_payment_token( $token );
				$pafw_order->set_payment_method( $token->get_gateway_id() );
				$pafw_order->set_payment_method_title( $gateway->get_title() );
				$pafw_order->save();
			} else {
				$order->set_payment_method( $token->get_gateway_id() );
				$order->set_payment_method_title( $gateway->get_title() );
				$order->save();
			}
		}
	} catch ( Exception $e ) {
		$order->add_order_note( $e->getMessage() );
	}
}