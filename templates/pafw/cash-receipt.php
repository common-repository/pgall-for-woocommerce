<?php

$customer = new WC_Customer( $order->get_customer_id() );

$products = array();

foreach ( $order->get_items() as $item ) {
	$products[] = $item->get_name();
}

$product_name = count( $products ) == 1 ? $products[0] : sprintf( "%s 외 %d건", $products[0], count( $products ) - 1 );

function pafw_masking( $str ) {
	$length = mb_strlen( $str );

	$mask_length = intval( $length / 3 );

	return mb_substr( $str, 0, $mask_length ) . str_repeat( '*', $length - 2 * $mask_length ) . mb_substr( $str, -1 * $mask_length );
}

?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko" lang="ko">
<head>
    <meta http-equiv="content-type" content="text/html; charset=euc-kr">
	<?php wp_print_scripts( 'jquery' ); ?>
    <title><?php _e( '현금영수증', 'pgall-for-woocommerce' ); ?></title>
    <style>
        body {
            font-size: 14px;
            font-family: Dotum, sans-serif;
            margin: 0;
        }

        h1 {
            font-size: 22px;
        }

        button {
            display: inline-block;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 12px;
            line-height: 18px;
            letter-spacing: .05em;
            padding: 8px 12px;
            border: none;
            cursor: pointer;
        }

        table {
            width: 100%;
            font-family: Dotum, sans-serif;
            font-size: inherit;
            line-height: 1.74;
        }

        table tr th,
        table tr td {
            padding: 0 15px;
            vertical-align: text-top;
        }

        table tr th {
            font-size: 17px;
            font-weight: 400;
            line-height: 1.6;
            padding-bottom: 10px;
        }

        table tr td:first-child {
            width: 95px;
            white-space: nowrap;
            padding-right: 0;
        }

        table tr td:last-child {
            text-align: right;
            padding-left: 0;
        }

        div.wrapper {
            background-color: #f7f7fb;
            padding: 55px 0;
        }

        div.invoice-container {
            max-width: 375px;
            background-color: #fff;
            padding: 22px 0 0;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 0 5px 0 rgb(10 22 70 / 6%), 7px 9px 10px -1px rgb(10 22 70 / 10%);
        }

        div.section {
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
            border-bottom-style: dashed;
            margin-bottom: 10px;
        }

        div.section:last-child {
            border-bottom: none;
        }

        div.print-button-wrapper {
            position: fixed;
            right: 10px;
            top: 10px;
        }

        .bacs_receipt_via {
            font-size: 0.8em;
            text-align: right;
            padding: 10px 10px;
        }

        @media print {
            div.wrapper {
                background-color: #f7f7fb !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
    <style media="print">
        @page {
            margin: 0;
            background-color: #f7f7fb ! inherit;
        }

        a {
            text-decoration: none !important;
        }

        .print-button-wrapper {
            display: none;
        }
    </style>

    <script type="text/javascript">
        function prt() {
            window.print();
        }
    </script>
</head>
<body>
<div class="wrapper">
    <div class="invoice-container">
        <div class="print-button-wrapper">
            <button class="button button-primary" onclick="prt();return false;">출력하기</button>
        </div>
        <div style="text-align: center; margin-bottom: 5px;">
            <h1 style="margin-bottom: 5px;">현금영수증</h1>
            <span class="description">(<?php echo $payment_gateway->get_receipt_usage_description( $order->get_meta( '_pafw_bacs_receipt_usage' ) ) . ' - ' . pafw_masking( $order->get_meta( '_pafw_bacs_receipt_reg_number' ) ); ?>)</span>
        </div>
        <div class="section">
            <table>
                <tbody>
                <tr>
                    <td>상호</td>
                    <td><?php echo get_option( 'pafw_bacs_receipt_company_name' ); ?></td>
                </tr>
                <tr>
                    <td>대표자</td>
                    <td><?php echo get_option( 'pafw_bacs_receipt_ceo_name' ); ?></td>
                </tr>
                <tr>
                    <td>사업자등록번호</td>
                    <td><?php echo get_option( 'pafw_bacs_receipt_reg_number' ); ?></td>
                </tr>
                <tr>
                    <td>전화번호</td>
                    <td><?php echo get_option( 'pafw_bacs_receipt_phone_number' ); ?></td>
                </tr>
                <tr>
                    <td>주소</td>
                    <td><?php echo get_option( 'pafw_bacs_receipt_address' ); ?></td>
                </tr>
                <tr>
                    <td>URL</td>
                    <td><?php echo home_url(); ?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="section">
            <table>
                <thead>
                <tr>
                    <th colspan="2">결제정보</th>
                </tr>
                </thead>
                <tbody>
                <tr class="order-total">
                    <td>총 결제금액</td>
                    <td><?php echo wc_price( $order->get_total() ); ?></td>
                </tr>
                <tr>
                    <td>과세금액</td>
                    <td><?php echo wc_price( PAFW_Tax::get_tax_amount( $order ) ); ?></td>
                </tr>
                <tr>
                    <td>부가세</td>
                    <td><?php echo wc_price( PAFW_Tax::get_total_tax( $order ) ); ?></td>
                </tr>
                <tr>
                    <td>면세금액</td>
                    <td><?php echo wc_price( PAFW_Tax::get_tax_free_amount( $order ) ); ?></td>
                </tr>
                <tr>
                    <td>구매자</td>
                    <td><?php echo pafw_masking( $order->get_billing_last_name() . $order->get_billing_first_name() ); ?></td>
                </tr>
                <tr>
                    <td>상품명</td>
                    <td><?php echo $product_name; ?></td>
                </tr>
                <tr>
                    <td>거래일시</td>
                    <td><?php echo wc_format_datetime( $order->get_date_paid() ); ?></td>
                </tr>
                <tr>
                    <td>현금영수증 번호</td>
                    <td><?php echo $order->get_meta( '_pafw_bacs_receipt_receipt_number' ); ?></td>
                </tr>
                </tbody>
            </table>
        </div>
		<?php if ( ! empty( $order->get_meta( '_pafw_bacs_receipt_via' ) ) ) : ?>
            <div class="section">
                <div class="bacs_receipt_via">현금영수증 사업자 : <?php echo $order->get_meta( '_pafw_bacs_receipt_via' ); ?></div>
            </div>
		<?php endif; ?>
    </div>
</div>
</body>
</html>