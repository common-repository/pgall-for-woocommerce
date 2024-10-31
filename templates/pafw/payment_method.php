<?php

if ( ! is_callable( array( $payment_gateway, 'subscription_payment_info' ) ) ) {
	return;
}

?>

<div class="pafw-payment-method-item <?php echo $payment_gateway->get_master_id(); ?>">
    <div class="pafw-payment-method-header">
        <div class="payment-logo" style="background-image: url('<?php echo $payment_gateway->get_logo_url(); ?>')"></div>
		<?php if ( ! empty( get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'bill_key' ), true ) ) ) : ?>
			<?php if ( 'yes' == pafw_get( $payment_gateway->settings, 'user_can_delete_batch_key', 'no' ) ) : ?>
                <div class="pafw-button card-action delete" data-payment_method="<?php echo $payment_gateway->id; ?>"><?php _e( '삭제하기', 'pgall-for-woocommerce' ); ?></div>
			<?php endif; ?>
            <div class="pafw-button card-action register" data-payment_method="<?php echo $payment_gateway->id; ?>"><?php _e( '다시등록', 'pgall-for-woocommerce' ); ?></div>
		<?php endif; ?>
    </div>
    <div class="pafw-payment-method-info">
		<?php if ( file_exists( PAFW()->template_path() . '/pafw/' . $payment_gateway->get_master_id() . '/card-info.php' ) ) : ?>
			<?php include( $payment_gateway->get_master_id() . '/card-info.php' ); ?>
		<?php else: ?>
			<?php echo $payment_gateway->subscription_payment_info(); ?>
		<?php endif; ?>
    </div>
</div>