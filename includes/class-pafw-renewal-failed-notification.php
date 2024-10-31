<?php



if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PAFW_Renewal_Failed_Notification' ) ) {

    class PAFW_Renewal_Failed_Notification {
        public static function init() {
            add_action( 'init', array( __CLASS__, 'maybe_redirect_pafw_card_page' ), 10 );
            add_action( 'woocommerce_subscription_renewal_payment_failed', array( __CLASS__, 'maybe_send_failed_renewal_notification' ), 10, 3 );
            add_action( 'pafw_after_update_bill_key', array ( __CLASS__, 'maybe_process_payment_retry'), 10, 4 );
        }
        public static function get_renewal_key( $subscription, $last_order ) {
            $data = array(
                'user_id' => $last_order->get_customer_id(),
                'order_id' => $last_order->get_id()
            );

            $random_key = bin2hex( random_bytes( 20 ) );

            $expiration = intval( get_option( 'pafw-renewal-failed-period', 3 ) ) * DAY_IN_SECONDS;

            set_transient( 'pafw_renewal_failed_key_' . $random_key, $data, $expiration );

            return $random_key;
        }
        protected static function get_template_params( $subscription, $last_order ) {
	        $renewal_key = self::get_renewal_key( $subscription, $last_order );

            $template_params = array_merge(
                MSSMS_Manager::get_template_params( $last_order ),
                array(
                    '카드등록링크'   => add_query_arg( 'pafw_renewal_failed_key', $renewal_key, home_url() ),
                )
            );

            return apply_filters( 'pafw_renewal_failed_template_params', $template_params, $last_order );
        }
        protected static function send_via_sms( $subscription, $last_order ) {
            $message = get_option( 'pafw-renewal-failed-notification-sms-template' );

            $template_params = self::get_template_params( $subscription, $last_order );

            $phone_number = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $last_order ) : $last_order->get_billing_phone();

            $recipients = array(
                array(
                    'receiver'        => $phone_number,
                    'template_params' => $template_params
                )
            );

            MSSMS_SMS::send_sms( 'LMS', '', $message, $recipients, get_option( 'mssms_rep_send_no' ) );
        }
        protected static function send_via_alimtalk( $subscription, $last_order ) {
            $recipients = array();

            $recipients[] = 'yes' == get_option( 'mssms_use_shipping_info', 'no' ) ? mssms_get_shipping_phone( $last_order ) : $last_order->get_billing_phone();

            $template_code = get_option( 'pafw-renewal-failed-notification-alimtalk-template' );

            if ( empty( $template_code ) ) {
                throw new Exception( __( '알림톡 템플릿 등록 후 이용해주세요', 'pgall-for-woocommerce' ) );
            }

            $template = MSSMS_Kakao::get_template( $template_code );

            if ( empty( $template ) ) {
                throw new Exception( __( '템플릿이 존재하지 않습니다.', 'pgall-for-woocommerce' ) );
            }

            $profile = MSSMS_Kakao::get_profile( $template['plus_id'] );

            if ( 'yes' == mssms_get( $profile, 'is_resend' ) ) {
                $resend_params = array(
                    'isResend'     => 'true',
                    'resendSendNo' => $profile['resend_send_no']
                );
            } else {
                $resend_params = array( 'isResend' => 'false' );
            }

            $template_params = self::get_template_params( $subscription, $last_order );

            MSSMS_Kakao::send_alimtalk( $template_code, $recipients, $template_params, $resend_params );
        }
        public static function maybe_send_failed_renewal_notification( $subscription, $last_order ) {
            $payment_method = pafw_get_payment_gateway_from_order( $subscription );
            $method_setting = $payment_method->settings;

            if( 'yes' == get_option( 'pafw-use-renewal-failed-notification' ) && function_exists( 'MSSMS' ) && 'yes' != $last_order->get_meta( '_pafw_renewal_failed_notification_sent', true ) ) {
                try {
                    if ( 'sms' == get_option( 'pafw-renewal-failed-notification-method', 'alimtalk' ) ) {
                        self::send_via_sms( $subscription, $last_order );
                    } else {
                        self::send_via_alimtalk( $subscription, $last_order );
                    }

                    $last_order->update_meta_data( '_pafw_renewal_failed_notification_sent', 'yes' );
                    $last_order->save_meta_data();
                } catch ( Exception $e ) {

                }
            }
        }
        public static function maybe_redirect_pafw_card_page() {
            if ( ! empty( $_GET['pafw_renewal_failed_key'] ) ) {

                $data = get_transient( 'pafw_renewal_failed_key_' . $_GET['pafw_renewal_failed_key'] );

                if ( ! $data ) {
                    return;
                }

                $order = wc_get_order( $data['order_id'] );
                if ( ! is_a( $order, 'WC_Order' ) || $data['user_id'] != $order->get_customer_id() ) {
                    return;
                }

                $user = get_userdata( $data['user_id'] );

                clean_user_cache( $user->ID );
                wp_clear_auth_cookie();

                wp_set_current_user( $user->ID, $user->user_login );
                wp_set_auth_cookie( $user->ID, true, false );

                set_transient( 'pafw_renewal_failed_key_' . $user->ID , $data['order_id'], DAY_IN_SECONDS );

				update_user_caches( $user );

                if ( ! is_user_logged_in() ) {
                    do_action( 'wp_login', $user->user_login, $user );
                }

	            wp_safe_redirect( wc_get_account_endpoint_url( 'add-payment-method' ) );
	            exit();
            }
        }

        public static function maybe_process_payment_retry ( $response, $order, $gateway, $user_id ) {
            if ( ! empty( get_transient( 'pafw_renewal_failed_key_' . $user_id ) ) ) {
                $order_id = get_transient( 'pafw_renewal_failed_key_' . $user_id );
                delete_transient( 'pafw_renewal_failed_key_' . $user_id );

                $order = wc_get_order( $order_id );
                if ( $order->get_payment_method() == $gateway->id ) {
                    $scheduled = as_get_scheduled_actions(
                        array (
                            'hook'   => 'woocommerce_scheduled_subscription_payment_retry',
                            'args'   => array ( 'order_id' => $order->get_id() ),
                            'status' => ActionScheduler_Store::STATUS_PENDING
                        )
                    );

                    if ( ! empty( $scheduled ) ) {
                        ActionScheduler::runner()->process_action( array_key_first( $scheduled ) );
                    }
                }
            }
        }
    }

    PAFW_Renewal_Failed_Notification::init();
}
