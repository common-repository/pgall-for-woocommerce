<?php

$cart_total = 0;

if ( WC()->cart ) {
	$cart_total = wc_prices_include_tax() ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : WC()->cart->get_cart_contents_total();
}

$always_issue_receipt = 'yes' == get_option( 'pafw_bacs_always_issue_receipt', 'no' );
?>

<div class="pafw_bacs_receipt_wrapper">
    <input type="hidden" name="pafw_bacs_receipt_use_default" value="<?php echo $use_default; ?>">

	<?php if ( 'yes' == $use_default ) : ?>
        <div class="pafw_bacs_default_info">
            <span><?php echo sprintf( __( '현금영수증 - %s', 'pgall-for-woocommerce' ), $default_receipt_info ); ?></span>
            <div style="text-align: center;">
                <input type="button" class="button button-primary" name="pafw_change_bacs_receipt_info" value="<?php _e( '현금영수증정보 변경', 'pgall-for-woocommerce' ); ?>">
            </div>
        </div>
	<?php endif; ?>
    <div class="pafw_bacs_receipt" style="<?php echo 'yes' == $use_default ? 'display: none;' : ''; ?>">
        <div class="pafw_bacs_receipt_issue_yn">
            <span>현금영수증 : </span>
            <input type="radio" id="receipt_issue_yes" name="pafw_bacs_receipt_issue" value="yes" checked="checked"><label for="receipt_issue_yes"><?php _e( '신청하기', 'pgall-for-woocommerce' ); ?></label>
			<?php if ( ! $always_issue_receipt || $cart_total <= floatval( get_option( 'pafw_bacs_issue_receipt_min_amount', 0 ) ) ) : ?>
                <input type="radio" id="receipt_issue_no" name="pafw_bacs_receipt_issue" value="no"><label for="receipt_issue_no"><?php _e( '신청안함', 'pgall-for-woocommerce' ); ?></label>
			<?php endif; ?>
        </div>
        <div class="receipt_issue receipt_issue_yes">
            <div class="pafw_bacs_receipt_type">
                <input type="radio" id="receipt_type_ID" name="pafw_bacs_receipt_usage" value="ID" checked="checked"><label for="receipt_type_ID"><?php _e( '개인소득공제용', 'pgall-for-woocommerce' ); ?></label>
                <input type="radio" id="receipt_type_POE" name="pafw_bacs_receipt_usage" value="POE"><label for="receipt_type_POE"><?php _e( '사업자증빙용(세금계산서용)', 'pgall-for-woocommerce' ); ?></label>
            </div>
            <div class="receipt_usage receipt_usage_ID" style="display: flex;">
                <select name="pafw_bacs_receipt_issue_type">
                    <option value="phone" selected=""><?php _e( '휴대폰번호', 'pgall-for-woocommerce' ); ?></option>
                    <option value="social"><?php _e( '주민등록번호', 'pgall-for-woocommerce' ); ?></option>
                    <option value="card"><?php _e( '현금영수증카드번호', 'pgall-for-woocommerce' ); ?></option>
                </select>
                <input type="text" name="pafw_bacs_reg_number_ID" value="010-000-1234">
            </div>
            <div class="receipt_usage receipt_usage_POE" style="display: none;">
                <select>
                    <option value="biz_reg" selected=""><?php _e( '사업자 등록번호', 'pgall-for-woocommerce' ); ?></option>
                </select>
                <input type="text" name="pafw_bacs_reg_number_POE">
            </div>
			<?php if ( is_user_logged_in() ) : ?>
                <div class="pafw_save_bacs_receipt_info">
                    <input type="checkbox" id="pafw_save_bacs_receipt_info" name="pafw_save_bacs_receipt_info"><label for="pafw_save_bacs_receipt_info"><?php _e( '현금영수증 신청정보를 저장합니다.', 'pgall-for-woocommerce' ); ?></label>
                </div>
			<?php endif; ?>
        </div>
    </div>
</div>
