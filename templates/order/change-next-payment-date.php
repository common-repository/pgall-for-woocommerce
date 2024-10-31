<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$next_payment = strtotime( $subscription->get_date('next_payment') ) + get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;

?>
<tr>
    <td><?php _e( '다음 결제일 관리', 'pgall-for-woocommerce' ); ?></td>
    <td>
        <div class="pafw-next-payment-date-wrapper" style="display: flex;">
            <p style="flex: 1; margin-right: 10px;"><?php echo sprintf( __( '다음 결제 예정일은 %s 입니다.', 'pgall-for-woocommerce' ), date('Y-m-d H:i', $next_payment ) ); ?></p>
            <input type="button" class="pafw-show-change-next-payment-date button button-primary" style="margin: 0;" value="<?php _e( '다음 결제일 변경하기', 'pgall-for-woocommerce' ); ?>">
        </div>
        <div class="pafw-change-next-payment-date-wrapper" style="display: none;">
            <div style="flex: 1; margin-right: 10px;">
                <input type="text" name="pafw-next-payment-date" value="<?php echo date('Y-m-d', $next_payment ) ; ?>" style="margin: 0 10px; width: 150px; text-align: center;">
            </div>
            <input type="button" class="pafw-change-next-payment-date button button-primary" value="<?php _e( '변경하기', 'pgall-for-woocommerce' ); ?>">
        </div>
    </td>
</tr>
