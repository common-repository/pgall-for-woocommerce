<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo $exchange_return->get_id(); ?>">
    <td class="thumb pafw-ex-thumb <?php echo $exchange_return->get_status(); ?>">
        <div></div>
    </td>

    <td class="name" colspan="3">
		<?php
		if ( $exchange_return->is_exchange() ) {
			$type = esc_attr__( '교환신청', 'pgall-for-woocommerce' );
		} else {
			$type = esc_attr__( '반품신청', 'pgall-for-woocommerce' );
		}

		echo $type . ' #' . absint( $exchange_return->get_id() ) . ' - ' . esc_attr( date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $exchange_return->get_date_created()->getOffsetTimestamp() ) );

		if ( $exchange_return->get_customer_id() > 0 ) {
			$who_exchange_return = get_userdata( $exchange_return->get_customer_id() );
			echo ' ' . esc_attr_x( 'by', 'Ex: Refund - $date >by< $username', 'woocommerce' ) . ' ' . '<abbr class="refund_by" title="' . esc_attr__( 'ID: ', 'woocommerce' ) . absint( $who_exchange_return->ID ) . '">' . esc_attr( $who_exchange_return->display_name ) . '</abbr>';
		} else {
			echo ' ' . esc_attr_x( 'by', 'Ex: Refund - $date >by< $username', 'woocommerce' ) . ' ' . '<abbr class="refund_by" title="' . esc_attr__( 'ID: ', 'woocommerce' ) . absint( 0 ) . '">' . esc_attr( 'guest' ) . '</abbr>';
		}
		?>
        <table class="exchange-return-items">
            <thead>
            <tr>
                <th><?php $exchange_return->is_exchange() ? _e( "교환 신청 상품", "pgall-for-woocommerce" ) : _e( "반품 신청 상품", "pgall-for-woocommerce" ); ?></th>
				<?php if ( $exchange_return->is_exchange() ) : ?>
                    <th><?php _e( "교환 희망 상품", "pgall-for-woocommerce" ); ?></th>
				<?php endif; ?>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach ( $exchange_return->get_items() as $key => $item ) {
				$order_item_meta  = wc_display_item_meta( $item, array( 'before' => '', 'after' => '', 'echo' => false, 'label_before' => '', 'label_after' => '' ) );
				$exchange_product = wc_get_product( $item->get_meta( '_exchange_product_id' ) );

				?>

                <tr>
                    <td>
						<?php
						printf( '<a href="%1s">%2s</a> x %3s개', get_edit_post_link( $item->get_product_id() ), $item->get_name(), $item->get_quantity() );
						if ( ! empty( $order_item_meta ) ) {
							printf( '<br><span class="item_meta">%s</span>', $order_item_meta );
						}
						?>
                    </td>
					<?php if ( $exchange_return->is_exchange() ) : ?>
                        <td>
							<?php if ( $exchange_product ) : ?>
								<?php printf( '<a href="%s">%s</a>', get_edit_post_link( $exchange_product->get_id() ), $exchange_product->get_name() ); ?>
							<?php endif; ?>
                        </td>
					<?php endif; ?>
                </tr>
				<?php
			}
			?>
            </tbody>
        </table>

		<?php if ( $exchange_return->get_reason() ) : ?>
            <p class="exchange-return-requests"><?php echo str_replace( "\n", "<br>", esc_html( $exchange_return->get_reason() ) ); ?></p>
		<?php endif; ?>
        <input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( $exchange_return->get_id() ); ?>"/>
    </td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $exchange_return, absint( $exchange_return->get_id() ) ); ?>

    <td class="pafw-actions">
		<?php
		$order_id = $exchange_return->get_parent_id();

		$order = wc_get_order( $order_id );

		if ( 'processing' == $exchange_return->get_status() && in_array( $order->get_status(), array( 'accept-exchange', 'accept-return' ) ) ) {
			$items = array();

			foreach ( $exchange_return->get_items() as $key => $item ) {
				$items[] = array(
					'item_id' => $item->get_meta( '_exchange_return_item_id' ),
					'qty'     => $item->get_quantity()
				);
			}

			if ( 'exchange' == $exchange_return->get_ex_type() ) {
				echo '<a href="#" data-items="' . esc_attr( json_encode( $items ) ) . '" class="apply-exchange button">' . __( '교환처리', 'pgall-for-woocommerce' ) . '</a>';
			} else {
				echo '<a href="#" data-items="' . esc_attr( json_encode( $items ) ) . '" class="apply-return button">' . __( '반품처리', 'pgall-for-woocommerce' ) . '</a>';
			}
		}
		?>
    </td>
    <td class="wc-order-edit-line-item">
        <div class="wc-order-edit-line-item-actions">
            <a class="delete_refund" href="#"></a>
        </div>
    </td>
</tr>
