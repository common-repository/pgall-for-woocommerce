<?php

if ( $order->get_status() == 'failed' ) {
	return;
}
$vacc_date_format = '';

$vacc_bank_name  = $order->get_meta( '_pafw_vacc_bank_name' );    //입금은행명/코드
$vacc_num        = $order->get_meta( '_pafw_vacc_num' );                //계좌번호
$vacc_name       = $order->get_meta( '_pafw_vacc_holder' );            //예금주
$vacc_input_name = $order->get_meta( '_pafw_vacc_depositor' );        //송금자
$vacc_date       = $order->get_meta( '_pafw_vacc_date' );            //입금예정일

if ( ! empty( $vacc_date ) ) {
	$vacc_date_format = date( __( 'Y년 m월 d일', 'pgall-for-woocommerce' ), strtotime( $vacc_date ) );
}

if ( 'yes' != $order->get_meta( '_pafw_sent_vacc_info' ) ) {
	do_action( 'send_vact_info', $order->get_id(), pafw_get_customer_phone_number( $order ), $vacc_bank_name, $vacc_num, $vacc_name, $vacc_input_name, $vacc_date_format );
	do_action( 'send_vact_info_v2', $order->get_id(), pafw_get_customer_phone_number( $order ), $vacc_bank_name, $vacc_num, $vacc_name, $vacc_input_name, $vacc_date_format );
	$order->update_meta_data( '_pafw_sent_vacc_info', 'yes' );
	$order->save_meta_data();
}

?>


<div class="msmp-email-section bank-account-info">
    <h4><?php _e( '가상계좌 무통장입금 안내', 'pgall-for-woocommerce' ); ?></h4>
    <p></p>

    <table>
        <tbody>
        <tr>
            <td colspan="2"><?php _e( '가상계좌 무통장입금 안내로 주문이 접수되었습니다. 아래 지정된 계좌번호로 입금기한내에 반드시 입금하셔야 하며, 송금자명으로 입금 해주셔야 주문이 정상 접수 됩니다.', 'pgall-for-woocommerce' ); ?></td>
        </tr>
		<?php if ( ! empty( $vacc_bank_name ) ) : ?>
            <tr>
                <th><?php _e( '은행명:', 'pgall-for-woocommerce' ); ?></th>
                <td data-title="<?php _e( '은행명:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_bank_name; ?></td>
            </tr>
		<?php endif; ?>
        <tr>
            <th><?php _e( '계좌번호:', 'pgall-for-woocommerce' ); ?></th>
            <td data-title="<?php _e( '계좌번호:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_num; ?></td>
        </tr>
		<?php if ( ! empty( $vacc_name ) ) : ?>
            <tr>
                <th><?php _e( '예금주:', 'pgall-for-woocommerce' ); ?></th>
                <td data-title="<?php _e( '예금주:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_name; ?></td>
            </tr>
		<?php endif; ?>
		<?php if ( ! empty( $vacc_input_name ) ) : ?>
            <tr>
                <th><?php _e( '송금자:', 'pgall-for-woocommerce' ); ?></th>
                <td data-title="<?php _e( '송금자:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_input_name; ?></td>
            </tr>
		<?php endif; ?>
		<?php if ( ! empty ( $vacc_date ) ) : ?>
            <tr>
                <th><?php _e( '입금기한:', 'pgall-for-woocommerce' ); ?></th>
                <td data-title="<?php _e( '입금기한:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_date_format; ?></td>
            </tr>
		<?php endif; ?>
        </tbody>
    </table>
</div>