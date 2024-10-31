<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$receipt_request = PAFW_Cash_Receipt::get_receipt_request( $order->get_id() );

if ( empty( $receipt_request ) ) {
	return;
}

?>

<div class="pafw-payment-details-section">
    <h2><?php echo __( '현금영수증', 'pgall-for-woocommerce' ); ?></h2>

    <table class="pafw-payment-details woocommerce-table woocommerce-table--order-details shop_table order_details" style="border-collapse: collapse;width: 100%;">
        <thead>
        <tr>
            <th class="woocommerce-table__ex-table usage" style="border: 1px solid #e5e5e5"><?php _e( '용도', 'pgall-for-woocommerce' ); ?></th>
            <th class="woocommerce-table__ex-table reg_number" style="border: 1px solid #e5e5e5"><?php _e( '발행정보', 'pgall-for-woocommerce' ); ?></th>
            <th class="woocommerce-table__ex-table reg_number" style="border: 1px solid #e5e5e5"><?php _e( '현금영수증번호', 'pgall-for-woocommerce' ); ?></th>
            <th class="woocommerce-table__ex-table status" style="border: 1px solid #e5e5e5"><?php _e( '상태', 'pgall-for-woocommerce' ); ?></th>
        </tr>
        </thead>
        <tr>
            <td style="border: 1px solid #e5e5e5"><?php echo PAFW_Cash_Receipt::get_usage_label( $order->get_meta( '_pafw_bacs_receipt_usage' ) ) ?></td>
            <td style="border: 1px solid #e5e5e5"><?php echo $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ?></td>
            <td style="border: 1px solid #e5e5e5"><?php echo $order->get_meta( '_pafw_bacs_receipt_receipt_number' ) ?></td>
            <td style="border: 1px solid #e5e5e5"><?php echo PAFW_Cash_Receipt::get_status_name( $receipt_request['status'] ); ?></td>
        </tr>
    </table>
</div>