<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$payment_gateway = pafw_get_payment_gateway( $payment_token->get_gateway_id() );

$quotas = explode( ',', pafw_get( $payment_gateway->settings, 'quota' ) );

?>

<?php if ( WC()->cart && floatval( WC()->cart->get_total( 'edit' ) ) >= apply_filters( 'pafw_minimum_installment_amount', 50000 ) && 'yes' == pafw_get( $payment_gateway->settings, 'enable_quota', 'no' ) ) : ?>
    <div class="installment-wrapper">
        <span class="installment-label"><?php _e( "할부 개월", "pgall-for-woocommerce" ); ?></span>
        <select name="pafw_token_card_quota_<?php echo esc_attr( $payment_token->get_id() ); ?>">
            <option value="00"><?php _e( '일시불', 'pgall-for-woocommerce' ); ?></option>
			<?php foreach ( $quotas as $quota ) : ?>
                <option value="<?php echo sprintf( "%02d", $quota ); ?>"><?php echo $quota . __( '개월', 'pgall-for-woocommerce' ); ?></option>
			<?php endforeach; ?>
        </select>
    </div>
<?php else: ?>
    <input type="hidden" name="pafw_token_card_quota_<?php echo esc_attr( $payment_token->get_id() ); ?>" value="00">
<?php endif; ?>