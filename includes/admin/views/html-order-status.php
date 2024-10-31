<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( PAFW_HPOS::enabled() ) {
    global $wpdb;

	$sql = "SELECT status, count( orders.id ) count
            FROM {$wpdb->prefix}wc_orders orders
            WHERE
                orders.type = 'shop_order'
            GROUP BY status
            ";

	$result = $wpdb->get_results( $sql, ARRAY_A );
    $order_counts = array_combine( array_column( $result, 'status' ), array_column( $result, 'count' ) );
} else {
	$order_counts = (array) wp_count_posts( 'shop_order' );
}

$on_hold_count        = pafw_get( $order_counts, 'wc-on-hold', 0 );
$order_received_count = pafw_get( $order_counts, 'wc-order-received', 0 );
$processing_count     = pafw_get( $order_counts, 'wc-processing', 0 );
$shipping_count       = pafw_get( $order_counts, 'wc-shipping', 0 );
$delayed_count        = pafw_get( $order_counts, 'wc-delayed', 0 );
$shipped_count        = pafw_get( $order_counts, 'wc-shipped', 0 );

?>

<div id="grap_warp">
    <div class="grap deepblue">
        <span><i class="icon-eye-open"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-on-hold&post_type=shop_order' ); ?>">입금확인중</a><br/><?php echo number_format( $on_hold_count ) . "건"; ?></p>
    </div>
    <div class="grap dkred">
        <span><i class="icon-shopping-cart"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-order-received&post_type=shop_order' ); ?>">주문접수</a><br/><?php echo number_format( $order_received_count ) . "건"; ?></p>
    </div>
    <div class="grap dkgreen last">
        <span><i class="icon-tags"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-processing&post_type=shop_order' ); ?>">발주확인</a><br/><?php echo number_format( $processing_count ) . "건"; ?></p>
    </div>
</div>

<div id="grap_warp">
    <div class="grap dkpurple">
        <span><i class="icon-plane"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-shipping&post_type=shop_order' ); ?>">출고처리</a><br/><?php echo number_format( $shipping_count ) . "건"; ?></p>
    </div>
    <div class="grap dkorange">
        <span><i class="icon-warning-sign"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-delayed&post_type=shop_order' ); ?>">출고지연</a><br/><?php echo number_format( $delayed_count ) . "건"; ?></p>
    </div>
    <div class="grap dkblue last">
        <span><i class="icon-flag"></i></span>
        <p class="grap_txt"><a href="<?php echo admin_url( 'edit.php?post_status=wc-shipped&post_type=shop_order' ); ?>">출고완료</a><br/><?php echo number_format( $shipped_count ) . "건"; ?></p>
    </div>
</div>
