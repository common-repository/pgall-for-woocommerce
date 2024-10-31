<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$master_id = $payment_gateway->get_master_id();

$bill_key = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'bill_key' ), true );

wp_enqueue_style( 'pafw-payment', PAFW()->plugin_url() . '/assets/css/payment.css', array (), PAFW_VERSION );
?>

<div class="pafw-card pafw-<?php echo $master_id; ?>">
	<?php if ( empty( $bill_key ) ) : ?>
        <div class="pafw-not-registered custom-handler" data-payment_method="<?php echo $payment_gateway->id; ?>"></div>
	<?php else: ?>
        <div class="pafw-registered">
			<?php
			$register_date  = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'register_date' ), true );
			$card_num       = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_num' ), true );
			$card_name      = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_name' ), true );
			$card_bank_name = get_user_meta( get_current_user_id(), $payment_gateway->get_subscription_meta_key( 'card_bank_name' ), true );

			$card_name = sprintf( __( "%s카드", "pgall-for-woocommerce" ), preg_replace( '/[\[\]]/', '', $card_name ) );
			$card_num  = substr_replace( $card_num, '00000000', 4, 8 );
			$card_num  = implode( '-', str_split( $card_num, 4 ) );
			?>
            <div class="payment_method_type"></div>
            <div class="card_name"><?php echo $card_name; ?></div>
            <div class="card_num"><?php echo $card_num; ?></div>
	        <?php if ( ! empty( $register_date ) ): ?>
                <div class="register_date"><?php echo sprintf( __( "등록일 : %s", "pgall-for-woocommerce" ), date( 'Y-m-d', strtotime( $register_date ) ) );; ?></div>
	        <?php endif; ?>
        </div>
	<?php endif; ?>
    <div class="pafw_card_form" style="display: none;">
        <form>
			<?php wc_get_template( 'pafw/' . $payment_gateway->get_master_id() . '/form-payment-fields.php', array ( 'gateway' => $payment_gateway ), '', PAFW()->template_path() ); ?>
            <div class="settlepg-register-card pafw-register-custom-handler" data-payment_method="<?php echo $payment_gateway->id; ?>"><?php _e( "등록하기", "pgall-for-woocommerce" ); ?></div>
        </form>
    </div>
</div>
