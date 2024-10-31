<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'pafw-before-exchange-return-items' ); ?>

<div class="field">
    <label><?php echo sprintf( __( '%s할 상품을 선택하세요.', 'pgall-for-woocommerce' ), PAFW_Exchange_Return_Manager::get_label() ); ?></label>
    <label><?php _e( '상품, 수량 단위로 반품/교환 요청하실 수 있습니다.', 'pgall-for-woocommerce' ); ?></label>
</div>

<div class="field">
    <div id="mser_item_container">
        <div class="cart-item-wrap">
            <div class="cart-item-header">
                <div class="product-name"><?php _e( '상품명', 'pgall-for-woocommerce' ); ?></div>
                <div class="product-price"><?php _e( '총가격', 'pgall-for-woocommerce' ); ?></div>
                <div class="product-quantity"><?php _e( '신청수량', 'pgall-for-woocommerce' ); ?></div>
            </div>
            <div class="cart-item-contents">
				<?php
				$order = wc_get_order( $order_id );

				if ( ! empty( $order ) ) {
					$order_items = $order->get_items();
					$valid_items = PAFW_Exchange_Return_Manager::get_valid_exchange_return_order_items( $order );
					foreach ( $valid_items as $key => $qty ) {
						if ( ! empty( $order_items[ $key ] ) ) {
							$item = $order_items[ $key ];

							$product_id      = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
							$product         = wc_get_product( $product_id );
							$order_item_meta = wc_display_item_meta( $item, array( 'before' => '', 'after' => '', 'echo' => false ) );

							$image_id = $product->get_image_id();
							?>
                            <div class="cart-item-list" data-key="<?php echo $key; ?>">
                                <div class="product-checkbox">
                                    <input type="checkbox" name="order_items[<?php echo $key; ?>]"/>
                                    <label for="order_items[<?php echo $key; ?>]"></label>
                                </div>
                                <div class="product-thumbnail">
                                    <div style="background-image: url('<?php echo wp_get_attachment_image_url( $image_id ); ?>');"></div>
                                </div>
                                <div class="product-info-wrap">
                                    <div class="product-name">
                                        <span class="item_name"><?php echo $item['name']; ?></span><br>
                                        <span class="price_qty">
                                        <?php
                                        if ( $item->get_total() > 0 ) {
	                                        echo wc_price( ( $item->get_total() + $item->get_total_tax() ) / $item->get_quantity(), array( 'currency' => $order->get_currency() ) ) . ' X ' . $qty;
                                        } else {
	                                        echo ' X ' . $qty;
                                        }
                                        ?>
									    </span>
                                        <span class="item_meta">
										<?php if ( ! empty( $order_item_meta ) ) {
											echo $order_item_meta;
										} ?>
									</span>
                                    </div>
                                    <div class="product-price">
										<?php echo wc_price( $item->get_total() + $item->get_total_tax() ); ?>
                                    </div>
                                    <div class="product-quantity refund-count">
										<?php
										if ( $product->is_sold_individually() ) {
											$product_quantity = sprintf( '1 <input type="hidden" name="exchange_return_qty[%s]" value="1" />', $key );
										} else {
											$product_quantity = woocommerce_quantity_input( array(
												'input_name'  => "exchange_return_qty[" . $key . "]",
												'input_value' => $qty,
												'max_value'   => $qty,
												'min_value'   => '0'
											), $product, false );
										}

										echo $product_quantity;
										?>
                                    </div>
                                </div>
                            </div>
                            <div class="cart-item-list exchange-item exchange-item-<?php echo $key; ?>" style="display:none;">
								<?php
								add_filter( 'msms_skip_price_calculation', '__return_true' );
								$ex_products = wc_get_products( array(
									'type'   => 'simple',
									'number' => -1,
									'price'  => $product->get_price(),
								) );
								remove_filter( 'msms_skip_price_calculation', '__return_true' );

								$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

								$ex_items = array();

								foreach ( $ex_products as $ex_product ) {
									if ( $product_id == $ex_product->get_id() ) {
										continue;
									}

									$ex_items[] = array(
										'id'          => $ex_product->get_id(),
										'title'       => $ex_product->get_name(),
										'description' => str_replace( "\r\n", "<br>", $ex_product->get_short_description() ),
										'thumbnail'   => wp_get_attachment_image_url( $ex_product->get_image_id(), 'thumbnail' ) ? wp_get_attachment_image_url( $ex_product->get_image_id(), 'thumbnail' ) : wc_placeholder_img_src()
									);
								}
								?>
								<?php if ( ! empty( $ex_items ) ) : ?>
                                    <div class="item" style="flex: 1; margin: 10px 0 10px 130px;">
                                        <select name="exchange_item[<?php echo $key; ?>]" style="width: 100%; height: auto;">
                                            <option value=""><?php _e( "교환할 상품을 선택하세요.", "pgall-for-woocommerce" ) ?></option>
											<?php foreach ( $ex_items as $ex_item ) : ?>
                                                <option value="<?php echo $ex_item['id']; ?>" data-params="<?php echo esc_attr( json_encode( $ex_item ) ); ?>"><?php echo $ex_item['title']; ?></option>
											<?php endforeach; ?>
                                        </select>
                                    </div>
								<?php endif; ?>
                            </div>
							<?php
						}
					}
				}
				?>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'pafw-after-exchange-return-items' ); ?>
