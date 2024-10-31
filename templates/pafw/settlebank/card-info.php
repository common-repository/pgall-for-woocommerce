<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$master_id = $payment_gateway->get_master_id();

$bill_key = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'bill_key' ), true );

?>

<div class="pafw-card pafw-<?php echo $master_id; ?>">
	<?php if ( empty( $bill_key ) ) : ?>
        <div class="pafw-not-registered" data-payment_method="<?php echo $payment_gateway->id; ?>"></div>
	<?php else: ?>
        <div class="pafw-registered">
			<?php
			$register_date   = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'register_date' ), true );
			$bank_account_no = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_num' ), true );
			?>
            <div class="payment_method_type"></div>
            <div class="card_num"><?php echo $bank_account_no; ?></div>
			<?php if ( ! empty( $register_date ) ): ?>
                <div class="register_date"><?php echo sprintf( __( "등록일 : %s", "pgall-for-woocommerce" ), date( 'Y-m-d', strtotime( $register_date ) ) );; ?></div>
			<?php endif; ?>
        </div>
	<?php endif; ?>
</div>
