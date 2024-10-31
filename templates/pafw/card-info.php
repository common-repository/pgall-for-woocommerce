<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$master_id = $payment_gateway->get_master_id();

wp_enqueue_style( 'pafw', PAFW()->plugin_url() . '/assets/css/payment.css', array (), PAFW_VERSION );

if ( ! empty( $bill_key ) ) {
	$issue_nm = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_name' ), true );
	$pay_id   = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_num' ), true );
	$pay_id   = substr_replace( $pay_id, '********', 4, 8 );
	$pay_id   = implode( '-', str_split( $pay_id, 4 ) );
}

?>

<div class="pafw-card pafw-<?php echo $master_id; ?>">
	<?php if ( empty( $bill_key ) ) : ?>
        <div class="pafw-not-registered" data-payment_method="<?php echo $payment_gateway->id; ?>"></div>
	<?php else: ?>
        <div class="pafw-registered">
        </div>
	<?php endif; ?>
</div>
