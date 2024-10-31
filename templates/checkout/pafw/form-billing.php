<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

    <div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );
		if ( 'no' == $need_shipping ) {
		    $filtered_fields = array();

			foreach ( $fields as $key => $field ) {
				if ( in_array( $key, array ( 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone', 'billing_first_name_kr', 'billing_email_kr', 'billing_phone_kr', 'billing_country' ) ) ) {
					if ( 'billing_country' == $key ) {
						$field['class'][] = 'hidden';
					}
					$filtered_fields[ $key ] = $field;
				}
			}

			$fields = $filtered_fields;
		}

		foreach ( $fields as $key => $field ) {
			if ( isset( $field['country_field'], $fields[ $field['country_field'] ] ) ) {
				$field['country'] = $checkout->get_value( $field['country_field'] );
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
    </div>

    <?php if( ! is_user_logged_in() &&  'yes' == pafw_get( $params, 'create_account', 'no' ) ) : ?>
        <input type="hidden" id="createaccount" name="createaccount" value="1"/>
    <?php endif; ?>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>

	<?php if ( 'yes' == pafw_get( $params, 'show_order_note' ) ) : ?>
		<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

		<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

            <div class="woocommerce-additional-fields__field-wrapper">
				<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
            </div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
    <?php else: ?>
        <div class="woocommerce-additional-fields__field-wrapper"></div>
	<?php endif; ?>
</div>
