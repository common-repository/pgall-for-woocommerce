<?php

$uid = uniqid( 'pafw_inicis_' );

?>
<div class="inicis-payment-fields">
    <div class="payment-method-description" style="display: <?php echo empty( $bill_key ) ? 'block' : 'none'; ?>">
		<?php echo $gateway->get_description(); ?>
    </div>
</div>
