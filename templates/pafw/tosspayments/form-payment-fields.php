<?php

$uid = uniqid( 'pafw_tosspayments_' );
?>

<div class="tosspayments-payment-fields">
	<?php if ( ! is_account_page() ) : ?>
		<?php $gateway->quota_field(); ?>
	<?php endif; ?>
</div>
