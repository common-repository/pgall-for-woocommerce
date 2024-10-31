<?php

?>

<?php if ( ! empty( $payment_data ) ) : ?>
	<?php foreach ( $payment_data as $item ) : ?>
        <div class="pafw_payment_info">
            <h4><?php echo $item['title']; ?></h4>
			<?php foreach ( $item['data'] as $label => $desc ) : ?>
                <p><?php printf( '%s : %s', $label, $desc ); ?></p>
			<?php endforeach; ?>
        </div>
	<?php endforeach; ?>
<?php endif; ?>

<?php if ( ! empty( $cancel_data ) ) : ?>
    <div class="pafw_cancel_info">
        <h4><?php _e( '취소정보', 'pgall-for-woocommerce' ); ?></h4>
		<?php foreach ( $cancel_data as $label => $desc ) : ?>
            <p><?php printf( '%s : %s', $label, $desc ); ?></p>
		<?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-refund-request" name="refund-request" data-tip="<?php _e( '전체취소', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '전체취소', 'pgall-for-woocommerce' ); ?>" <?php echo $is_fully_refundable ? '' : 'disabled'; ?>>
	<?php if ( ! empty( $transaction_id ) && ! empty( $receipt_url ) ) : ?>
        <input type="button" class="button pafw_action_button tips" id="pafw-check-receipt" name="refund-request-check-receipt" data-tip="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>">
	<?php endif; ?>
	<?php do_action( 'pafw_payment_info_meta_box_action_button_' . $order->get_payment_method(), $order ); ?>
	<?php do_action( 'pafw_payment_info_meta_box_action_button', $order); ?>
</div>
