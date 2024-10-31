<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quotas = explode( ',', pafw_get( $payment_gateway->settings, 'quota' ) );

?>

<?php if ( 'yes' == pafw_get( $payment_gateway->settings, 'enable_quota', 'no' ) ) : ?>
    <div class="pafw-card-info">
        <div class="fields-wrap">
            <select name="pafw_<?php echo $payment_gateway->get_master_id(); ?>_card_quota">
                <option value="00"><?php _e( '일시불', 'pgall-for-woocommerce' ); ?></option>
				<?php foreach ( $quotas as $quota ) : ?>
                    <option value="<?php echo sprintf( "%02d", $quota ); ?>"><?php echo $quota . __( '개월', 'pgall-for-woocommerce' ); ?></option>
				<?php endforeach; ?>
            </select>
        </div>
    </div>
<?php else: ?>
    <input type="hidden" name="pafw_<?php echo $payment_gateway->get_master_id(); ?>_card_quota" value="00">
<?php endif; ?>