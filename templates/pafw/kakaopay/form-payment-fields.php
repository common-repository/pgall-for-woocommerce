<?php

$uid = uniqid( 'pafw_kakaopay_' );

?>
<div class="kakaopay-payment-fields">
	<?php echo $gateway->get_description(); ?>
	<?php if ( ! is_account_page() ) : ?>
		<?php $gateway->quota_field(); ?>
	<?php endif; ?>
</div>
