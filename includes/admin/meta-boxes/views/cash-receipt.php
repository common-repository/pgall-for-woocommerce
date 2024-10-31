<?php

$receipt_usage = $order->get_meta( '_pafw_bacs_receipt_usage' );
$receipt_usage = ! empty( $receipt_usage ) ? $receipt_usage : 'ID';

$issue_type = $order->get_meta( '_pafw_bacs_receipt_issue_type' );
$issue_type = ! empty( $issue_type ) ? $issue_type : 'phone ';

$reg_number = $order->get_meta( '_pafw_bacs_receipt_reg_number' );

$disabled = ! empty( $transaction_id ) ? 'disabled=disabled' : '';
?>

<style>
    .pafw_payment_info.bacs_receipt > div {
        display: flex;
        width: 100%;
        margin-bottom: 5px;
    }

    .pafw_payment_info.bacs_receipt > div select,
    .pafw_payment_info.bacs_receipt > div input {
        font-size: 12px !important;
        width: 100% !important;
    }

    .pafw_payment_info.bacs_receipt > div.receipt_usage select,
    .pafw_payment_info.bacs_receipt > div.receipt_usage input {
        width: 50% !important;
    }
</style>
<div class="pafw_payment_info bacs_receipt">
    <div class="receipt_type">
        <select name="pafw_bacs_receipt_usage" <?php echo $disabled; ?>>
            <option value="ID" <?php echo 'ID' == $receipt_usage ? 'selected' : ''; ?>><?php _e( '개인소득공제용', 'pgall-for-woocommerce' ); ?></option>
            <option value="POE" <?php echo 'POE' == $receipt_usage ? 'selected' : ''; ?>><?php _e( '사업자증빙용(세금계산서용)', 'pgall-for-woocommerce' ); ?></option>
        </select>
    </div>
    <div class="receipt_usage receipt_usage_ID" style="display: <?php echo 'ID' == $receipt_usage ? 'flex' : 'none'; ?>">
        <select name="pafw_bacs_receipt_issue_type" <?php echo $disabled; ?>>
            <option value="phone" <?php echo 'phone' == $issue_type ? 'selected' : ''; ?>><?php _e( '휴대폰번호', 'pgall-for-woocommerce' ); ?></option>
            <option value="social"  <?php echo 'social' == $issue_type ? 'selected' : ''; ?>><?php _e( '주민등록번호', 'pgall-for-woocommerce' ); ?></option>
            <option value="card"  <?php echo 'card' == $issue_type ? 'selected' : ''; ?>><?php _e( '현금영수증카드번호', 'pgall-for-woocommerce' ); ?></option>
        </select>
        <input type="text" name="pafw_bacs_reg_number_ID" value="<?php echo 'ID' == $receipt_usage  ? $reg_number : ''; ?>" <?php echo $disabled; ?>>
    </div>
    <div class="receipt_usage receipt_usage_POE" style="display: <?php echo 'POE' == $receipt_usage ? 'flex' : 'none'; ?>;">
        <select <?php echo $disabled; ?>>
            <option value="biz_reg" selected=""><?php _e( '사업자 등록번호', 'pgall-for-woocommerce' ); ?></option>
        </select>
        <input type="text" name="pafw_bacs_reg_number_POE" value="<?php echo 'POE' == $receipt_usage  ? $reg_number : ''; ?>" <?php echo $disabled; ?>>
    </div>
</div>
<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-update-receipt-info" disabled='disabled' data-tip="<?php _e( '현금영수증 발행 정보를 저장합니다.', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '업데이트', 'pgall-for-woocommerce' ); ?>">
	<?php if ( empty( $transaction_id ) ) : ?>
        <input type="button" class="button pafw_action_button tips" id="pafw-cash-receipt" data-tip="<?php _e( '현금영수증을 발행합니다.', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '현금영수증 발행', 'pgall-for-woocommerce' ); ?>">
	<?php else : ?>
        <input type="button" class="button pafw_action_button button-link-delete tips" id="pafw-cancel-receipt" data-tip="<?php _e( '현금영수증 발행 취소', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '현금영수증 발행 취소', 'pgall-for-woocommerce' ); ?>">
	<?php endif; ?>
</div>
<?php if ( ! empty( $transaction_id ) ) : ?>
<div class="pafw_button_wrapper" style="margin-top: 10px;">
        <input type="button" class="button pafw_action_button tips" id="pafw-view-receipt" data-tip="<?php _e( '현금영수증 조회.', 'pgall-for-woocommerce' ); ?>" value="<?php _e( '현금영수증 조회', 'pgall-for-woocommerce' ); ?>">
</div>
<?php endif; ?>
