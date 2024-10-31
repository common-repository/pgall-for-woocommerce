<?php

$uid = uniqid( 'pafw_nicepay_' );

?>
<script>
    jQuery( document ).ready( function ( $ ) {
        var $wrapper = $( 'div.nicepay-payment-fields' );

        $( '.pafw-card-info .pafw_card_type', $wrapper ).on( 'change', function () {
            if (this.checked) {
                $( 'input[name=pafw_nicepay_cert_no]', $wrapper )
                    .attr( 'placeholder', $( this ).data( 'placeholder' ) )
                    .attr( 'maxlength', $( this ).data( 'size' ) )
                    .attr( 'size', $( this ).data( 'size' ) )
                    .val( '' );

            }
        } );
    } );
</script>

<div class="nicepay-payment-fields">
    <input type="hidden" name="nicepay_issue_bill_key" value="<?php echo empty( $bill_key ) || is_account_page() ? 'yes' : 'no'; ?>">
    <div class="pafw-card-info">
        <div class="fields-wrap card_type">
            <div class="item">
                <input type="radio" id='nicepay_card_type_p<?php echo $uid; ?>' class='pafw_card_type' name="pafw_nicepay_card_type" value='0' data-label="<?php _e( '생년월일', 'pgall-for-woocommerce' ); ?>" data-placeholder="<?php _e( '주민번호 앞 6자리', 'pgall-for-woocommerce' ); ?>" data-size="6" checked>
                <label for="nicepay_card_type_p<?php echo $uid; ?>"><?php _e( '개인카드', 'pgall-for-woocommerce' ); ?></label>
                <div class="check"></div>
            </div>
            <div class="item">
                <input type="radio" id="nicepay_card_type_c<?php echo $uid; ?>" class='pafw_card_type' name="pafw_nicepay_card_type" value='1' data-label="<?php _e( '사업자번호', 'pgall-for-woocommerce' ); ?>" data-placeholder="<?php _e( '사업자번호 10자리', 'pgall-for-woocommerce' ); ?>" data-size="10">
                <label for="nicepay_card_type_c<?php echo $uid; ?>"><?php _e( '법인카드', 'pgall-for-woocommerce' ); ?></label>
                <div class="check"></div>
            </div>
        </div>
        <div class="pafw-card-field-wrap">
            <div class="fields-wrap">
                <div class="card_no">
                    <input inputmode="numeric" pattern="[0-9]+*" type="text" class="card-number" maxlength="16" size="16" name="pafw_nicepay_card_no" placeholder="카드번호를 입력 해 주세요" value="">
                </div>
            </div>
            <div class="fields-wrap flex">
                <input class="expiry-month" type="hidden" name="pafw_nicepay_expiry_month">
                <input class="expiry-year" type="hidden" name="pafw_nicepay_expiry_year">
            </div>
            <div class="fields-wrap flex">
                <div class="cert_no">
                    <div>
                        <input inputmode="numeric" class="name" pattern="[0-9]+*" type="text" maxlength="6" size="6" name="pafw_nicepay_cert_no" placeholder="<?php _e( '주민번호 앞 6자리', 'pgall-for-woocommerce' ); ?>" value="">
                    </div>
                </div>
                <div class="cust-type">
                    <div>
                        <input inputmode="numeric" class="cvc" pattern="[0-9]+*" type="password" maxlength="2" size="2" autocomplete="new-password" name="pafw_nicepay_card_pw" placeholder="<?php _e( '비밀번호 앞 2자리', 'pgall-for-woocommerce' ); ?>" value="">
                    </div>
                </div>
            </div>
        </div>
    </div>
	<?php if ( ! is_account_page() ) : ?>
		<?php $gateway->quota_field(); ?>
	<?php endif; ?>
</div>
