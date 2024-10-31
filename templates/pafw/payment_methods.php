<?php

//wc_print_notices();

wp_enqueue_style( 'pafw', plugins_url( '/assets/css/card.css', PAFW_PLUGIN_FILE ), array(), PAFW_VERSION );

$dependencies = array( 'jquery', 'underscore', 'pafw-card' );

$supported_payment_methods = array();
$gateway_payment_methods   = array();

foreach ( $payment_gateways as $payment_gateway ) {
	$dependencies[] = $payment_gateway::enqueue_frontend_script();

	$supported_payment_methods = array_merge( $supported_payment_methods, array( $payment_gateway->id ) );

	$gateway_payment_methods[ $payment_gateway->get_master_id() ] = array( $payment_gateway->id );
}

$dependencies = array_filter( $dependencies );

wp_enqueue_script( 'pafw-card', PAFW()->plugin_url() . '/assets/js/card-input.js', array( 'jquery' ), PAFW_VERSION, 'yes' == get_option( 'pafw-script-footer', 'no' ) );
wp_enqueue_script( 'pafw', PAFW()->plugin_url() . '/assets/js/card.js', $dependencies, PAFW_VERSION, 'yes' == get_option( 'pafw-script-footer', 'no' ) );

wp_localize_script( 'pafw', '_pafw', apply_filters( 'pafw_payment_script_params', array(
	'gateway_domain'            => PAFW_Payment_Gateway::gateway_domain(),
	'checkout_form_selector'    => is_checkout_pay_page() ? get_option( 'pafw-order-pay-form-selector', 'form#order_review' ) : get_option( 'pafw-checkout-form-selector', 'form.checkout' ),
	'supported_payment_methods' => $supported_payment_methods,
	'slug'                      => PAFW()->slug(),
	'gateway'                   => $gateway_payment_methods,
	'is_mobile'                 => wp_is_mobile(),
	'_wpnonce'                  => wp_create_nonce( 'pgall-for-woocommerce' ),
	'simple_pay'                => true,
	'i18n'                      => array(
		'popup_block_message' => __( '팝업이 차단되어 있습니다. 팝업설정을 변경하신 후 다시 시도해주세요.', 'pgall-for-woocommerce' )
	)
) ) );

?>
<div class="pafw-payment-methods">
	<?php
	foreach ( $payment_gateways as $payment_gateway ) {
		include( 'payment_method.php' );
	}
	?>
</div>