<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$payment_gateway = pafw_get_payment_gateway_from_order( $order );

if ( empty( $payment_gateway ) ) {
	return;
}

$tid                       = $payment_gateway->get_transaction_id( $order );
$receipt_url               = $payment_gateway->get_transaction_url( $order );
$receipt_popup_params      = $payment_gateway->get_receipt_popup_params();
$additional_charge_history = $order->get_meta( '_pafw_additional_charge_history' );

$paid_date = $order->get_date_paid();

if ( ( empty( $tid ) || empty( $paid_date ) ) && empty( $additional_charge_history ) ) {
	return;
}

$order_cancel_url = pafw_get_order_cancel_url( $order, is_user_logged_in() ? $order->get_view_order_url() : pafw_get( $_SERVER, 'HTTP_REFERER' ) );
?>

<div class="pafw-payment-details-section">
	<?php if ( ! empty( $tid ) && ! empty( $paid_date ) ) : ?>
        <h2><?php echo __( '결제 상세 정보', 'pgall-for-woocommerce' ); ?></h2>

        <table class="pafw-payment-details woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
            <tr>
                <th class="woocommerce-table__ex-table ex-payment-method"><?php _e( '결제수단', 'pgall-for-woocommerce' ); ?></th>
                <th class="woocommerce-table__ex-table ex-payment-date"><?php _e( '결제일시', 'pgall-for-woocommerce' ); ?></th>
                <th class="woocommerce-table__ex-table ex-action"></th>
            </tr>
            </thead>
            <tr>
                <td><?php echo $payment_gateway->get_title(); ?></td>
                <td><?php echo $paid_date->date( 'Y-m-d H:i:s' ); ?></td>
                <td>
					<?php if ( ! empty( $receipt_url ) ) : ?>
						<?php if ( ! empty( $receipt_popup_params ) ) : ?>
                            <script>
                                jQuery(document).ready(function($) {
                                    $('a.pafw-view-receipt').on('click', function() {
                                        window.open("<?php echo $receipt_url; ?>", "<?php echo $receipt_popup_params['name']; ?>", "<?php echo $receipt_popup_params['features']; ?>");
                                        return false;
                                    });
                                });
                            </script>
                            <a href="<?php echo $receipt_url; ?>" target="_blank" class="button pafw-view-receipt"><?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?></a>
						<?php else : ?>
                            <a href="<?php echo $receipt_url; ?>" target="_blank" class="button"><?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?></a>
						<?php endif; ?>
					<?php else: ?>
						<?php do_action( 'pafw_view_order_receipt_button_' . $payment_gateway->id, $order ); ?>
					<?php endif; ?>

					<?php if ( ! empty( $order_cancel_url ) ) : ?>
                        <a href="<?php echo $order_cancel_url; ?>" class="button button-primary cancel"><?php $payment_gateway->supports( 'pafw-vbank' ) ? _e( "주문취소요청", "pgall-for-woocommerce" ) : _e( "주문취소", "pgall-for-woocommerce" ); ?></a>
					<?php endif; ?>
                </td>
            </tr>
        </table>
	<?php endif; ?>

	<?php if ( ! empty( $additional_charge_history ) ) : ?>
        <script>
            jQuery(document).ready(function($) {
                $('.pafw-check-additional-charge-receipt').on('click', function() {
                    window.open($(this).data('receipt_url'), "<?php echo $receipt_popup_params['name']; ?>", "<?php echo $receipt_popup_params['features']; ?>");
                });
            });
        </script>
        <h2><?php echo __( '추가과금 내역', 'pgall-for-woocommerce' ); ?></h2>

        <table class="pafw-payment-details woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
            <tr>
                <th class="woocommerce-table__ex-table ex-payment-date"><?php _e( '결제일시', 'pgall-for-woocommerce' ); ?></th>
                <th class="woocommerce-table__ex-table ex-payment-method"><?php _e( '결제금액', 'pgall-for-woocommerce' ); ?></th>
                <th class="woocommerce-table__ex-table ex-payment-method"><?php _e( '상태', 'pgall-for-woocommerce' ); ?></th>
                <th class="woocommerce-table__ex-table ex-action"></th>
            </tr>
            </thead>
			<?php foreach ( $additional_charge_history as $tid => $item ) : ?>
				<?php
				$receipt_url = '';
				if ( ! empty( $payment_gateway->view_transaction_url ) ) {
					$receipt_url = sprintf( $payment_gateway->view_transaction_url, $tid );
				}
				?>
                <tr>
                    <td><?php echo date( 'Y-m-d H:i:s', strtotime( $item['auth_date'] ) ); ?></td>
                    <td><?php echo number_format( $item['charged_amount'] ); ?></td>
                    <td><?php echo $item['status']; ?></td>
                    <td>
						<?php if ( ! empty( $receipt_url ) ) : ?>
                            <input type="button" class="button pafw_action_button pafw-check-additional-charge-receipt tips" data-tip="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>" data-receipt_url="<?php echo $receipt_url; ?>" value="<?php _e( '영수증 확인', 'pgall-for-woocommerce' ); ?>">
						<?php endif; ?>
                    </td>
                </tr>
			<?php endforeach; ?>
        </table>
	<?php endif; ?>
</div>