<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$reasons = apply_filters( 'pafw_subscription_cancel_reasons', array(
	__( '구매 의사 없어짐', 'pgall-for-woocommerce' ),
	__( '다른 상품으로 재주문', 'pgall-for-woocommerce' ),
	__( '서비스 불만족', 'pgall-for-woocommerce' ),
	__( '상품정보 상이', 'pgall-for-woocommerce' ),
), $subscription );

?>
<div class="pafw-cancel-reason-form white-popup-block mfp-hide" style="">
    <div class="form-header">
        <div class="close" style="background-image: url('<?php echo plugins_url( '/assets/images/close.png', PAFW_PLUGIN_FILE ) ?>')"></div>
    </div>

	<?php do_action( 'pafw_before_subscription_survey_form', $subscription ); ?>

    <select class="pafw-subscription-cancel-reason">
        <option selected="selected"><?php _e( '취소 사유를 입력해주세요.', 'pgall-for-woocommerce' ); ?></option>
		<?php foreach ( $reasons as $reason ) : ?>
            <option><?php echo $reason; ?></option>
		<?php endforeach; ?>
    </select>
    <textarea name="pafw-subscription-cancel-reason" class=""></textarea>

	<?php do_action( 'pafw_after_subscription_survey_form', $subscription ); ?>

    <input type="button" class="pafw-cancel-subscription button button-primary" value="<?php _e( '구독취소', 'pgall-for-woocommerce' ); ?>">
</div>