<?php
$gateway = pafw_get_payment_gateway_from_order( $order );

$disabled = in_array( $order->get_status(), array( 'pending', 'failed', 'cancelled', 'refunded' ) );

$history = $order->get_meta( '_pafw_additional_charge_history' );
$idx     = 1;
?>

<div class="pafw_payment_info">
    <h4>과금 요청 금액</h4>
    <p>
        <input type="text" id="subscription_additional_charge_amount" placeholder="금액을 입력하세요." value="">
    </p>
	<?php if ( 'yes' == pafw_get( $gateway->settings, 'enable_quota', 'no' ) ) : ?>
        <select id="subscription_additional_charge_card_quota" name="subscription_additional_charge_card_quota" style="width: 100%;">
            <option value="00"><?php _e( '일시불', 'pgall-for-woocommerce' ); ?></option>
			<?php
			$quotas = explode( ',', pafw_get( $gateway->settings, 'quota' ) );
			?>
			<?php foreach ( $quotas as $quota ) : ?>
                <option value="<?php echo sprintf( "%02d", $quota ); ?>"><?php echo $quota . __( '개월', 'pgall-for-woocommerce' ); ?></option>
			<?php endforeach; ?>
        </select>
	<?php else: ?>
        <input type="hidden" id="subscription_additional_charge_card_quota" name="subscription_additional_charge_card_quota" value="00">
	<?php endif; ?>
</div>

<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-subscription-additional-charge" value="<?php _e( '과금요청', 'pgall-for-woocommerce' ); ?>" data-tip="발급된 정기결제 배치키를 이용해서 추가 과금을 요청합니다." <?php echo $disabled ? 'disabled="disabled"' : ''; ?>}>
</div>

<?php if ( ! empty( $history ) ) : ?>
    <div class="pafw_payment_info">
        <h4>추가 과금 내역</h4>
		<?php foreach ( $history as $tid => $item ) : ?>
			<?php
			$receipt_url = '';
			if ( ! empty( $gateway->view_transaction_url ) ) {
				$receipt_url = sprintf( $gateway->view_transaction_url, $tid );
			}
			?>
            <p>[#<?php echo $idx; ?>] 처리시간 : <?php echo date( 'Y-m-d H:i:s', strtotime( $item['auth_date'] ) ); ?></p>
            <p>[#<?php echo $idx; ?>] 과금금액 : <?php echo number_format( $item['charged_amount'] ); ?></p>
            <p>
            [#<?php echo $idx++; ?>] 처리상태 : <?php echo $item['status']; ?>
			<?php if ( 'PAYED' == $item['status'] ) : ?>
                <div class="pafw_button_wrapper">
                    <input type="button" class="button tips pafw_action_button pafw-subscription-cancel-additional-charge" data-tid="<?php echo $tid; ?>" data-amount="<?php echo $item['charged_amount']; ?>" value="<?php _e( '취소요청', 'pgall-for-woocommerce' ); ?>" data-tip="추가 과금을 취소합니다.">

					<?php if ( ! empty( $receipt_url ) ) : ?>
                        <input type="button" class="button pafw_action_button pafw-check-additional-charge-receipt tips" data-tip="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>" data-receipt_url="<?php echo $receipt_url; ?>" value="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>">
					<?php endif; ?>
                </div>
			<?php elseif ( ! empty( $receipt_url ) ) : ?>
                <div class="pafw_button_wrapper">
                    <input type="button" class="button pafw_action_button pafw-check-additional-charge-receipt tips" data-tip="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>" data-receipt_url="<?php echo $receipt_url; ?>" value="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>">
                </div>
			<?php endif; ?>
            </p>
            <hr>
		<?php endforeach; ?>
    </div>
<?php endif; ?>
