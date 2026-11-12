<?php
/**
 * Google Tag Manager + Google Analytics 4 — E-commerce Tracking
 *
 * GTM Container  : GTM-MV9XFFMB
 * GA4 Measurement: G-JTXJM01YNJ
 *
 * Events tracked:
 *   view_item        — single product page
 *   add_to_cart      — WC add-to-cart hook  (session-based dedup)
 *   begin_checkout   — /checkout/ page
 *   purchase         — order-received page  (one-time meta guard)
 */
defined( 'ABSPATH' ) || exit;

define( 'BEGENTLE_GTM_ID', 'GTM-MV9XFFMB' );
define( 'BEGENTLE_GA4_ID', 'G-JTXJM01YNJ' );

/* ============================================================
 * 1. GTM + GA4 BASE SCRIPT — in <head>
 * ========================================================== */
add_action( 'wp_head', 'begentle_gtm_head', 1 );
function begentle_gtm_head(): void { ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( BEGENTLE_GTM_ID ); ?>');</script>
<!-- End Google Tag Manager -->
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( BEGENTLE_GA4_ID ); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo esc_js( BEGENTLE_GA4_ID ); ?>');
</script>
<!-- End Google Analytics 4 -->
<?php }

/* ============================================================
 * 2. GTM NOSCRIPT — right after <body>
 * ========================================================== */
add_action( 'wp_body_open', 'begentle_gtm_body', 1 );
function begentle_gtm_body(): void {
	echo '<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( BEGENTLE_GTM_ID ) . '"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->';
}

/* ============================================================
 * HELPER — build GA4 items array from a WC product
 * ========================================================== */
function begentle_ga4_item( $product_id, $quantity = 1 ): array {
	$product = wc_get_product( $product_id );
	if ( ! $product ) return [];
	$cats = get_the_terms( $product_id, 'product_cat' );
	return [
		'item_id'       => (string) $product->get_id(),
		'item_name'     => $product->get_name(),
		'price'         => floatval( $product->get_price() ),
		'quantity'      => intval( $quantity ),
		'currency'      => get_woocommerce_currency(),
		'item_category' => ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '',
	];
}

/* ============================================================
 * 3. view_item — single product pages
 * ========================================================== */
add_action( 'wp_footer', 'begentle_ga4_view_item' );
function begentle_ga4_view_item(): void {
	if ( ! is_product() ) return;
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) return;

	$item     = begentle_ga4_item( $product->get_id() );
	$currency = get_woocommerce_currency();
	$value    = floatval( $product->get_price() );
	?>
	<script>
	gtag('event','view_item',{
		currency: '<?php echo esc_js( $currency ); ?>',
		value: <?php echo $value; ?>,
		items: [<?php echo wp_json_encode( $item ); ?>]
	});
	</script>
	<?php
}

/* ============================================================
 * 4. add_to_cart — WC hook fires server-side, browser reads session
 * ========================================================== */
add_action( 'woocommerce_add_to_cart', 'begentle_ga4_atc_server', 10, 6 );
function begentle_ga4_atc_server( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ): void {
	if ( ! WC()->session ) return;
	$pid  = $variation_id ?: $product_id;
	$item = begentle_ga4_item( $pid, $quantity );
	if ( empty( $item ) ) return;

	WC()->session->set( 'begentle_ga4_atc_item',     $item );
	WC()->session->set( 'begentle_ga4_atc_value',    $item['price'] * $item['quantity'] );
	WC()->session->set( 'begentle_ga4_atc_currency', get_woocommerce_currency() );
}

add_action( 'wp_footer', 'begentle_ga4_atc_browser' );
function begentle_ga4_atc_browser(): void {
	if ( ! WC()->session ) return;
	$item = WC()->session->get( 'begentle_ga4_atc_item' );
	if ( empty( $item ) ) return;

	$value    = floatval( WC()->session->get( 'begentle_ga4_atc_value' ) );
	$currency = WC()->session->get( 'begentle_ga4_atc_currency' ) ?: get_woocommerce_currency();

	WC()->session->set( 'begentle_ga4_atc_item', null );
	?>
	<script>
	gtag('event','add_to_cart',{
		currency: '<?php echo esc_js( $currency ); ?>',
		value: <?php echo $value; ?>,
		items: [<?php echo wp_json_encode( $item ); ?>]
	});
	</script>
	<?php
}

/* ============================================================
 * 5. begin_checkout — /checkout/ page
 * ========================================================== */
add_action( 'wp_footer', 'begentle_ga4_begin_checkout' );
function begentle_ga4_begin_checkout(): void {
	if ( ! is_checkout() || is_order_received_page() ) return;
	$cart = WC()->cart;
	if ( ! $cart ) return;

	$items = [];
	foreach ( $cart->get_cart() as $ci ) {
		$it = begentle_ga4_item( $ci['variation_id'] ?: $ci['product_id'], $ci['quantity'] );
		if ( $it ) $items[] = $it;
	}
	$currency = get_woocommerce_currency();
	$value    = floatval( $cart->get_cart_contents_total() );
	?>
	<script>
	gtag('event','begin_checkout',{
		currency: '<?php echo esc_js( $currency ); ?>',
		value: <?php echo $value; ?>,
		items: <?php echo wp_json_encode( $items ); ?>
	});
	</script>
	<?php
}

/* ============================================================
 * 6. purchase — order-received / thank-you page (fires once per order)
 * ========================================================== */
add_action( 'woocommerce_thankyou', 'begentle_ga4_purchase', 10, 1 );
function begentle_ga4_purchase( $order_id ): void {
	if ( ! $order_id ) return;
	if ( get_post_meta( $order_id, '_begentle_ga4_purchase_fired', true ) ) return;
	update_post_meta( $order_id, '_begentle_ga4_purchase_fired', '1' );

	$order = wc_get_order( $order_id );
	if ( ! $order ) return;

	$items = [];
	foreach ( $order->get_items() as $item ) {
		$it = begentle_ga4_item( $item->get_product_id(), $item->get_quantity() );
		if ( $it ) $items[] = $it;
	}
	$currency = $order->get_currency();
	$total    = floatval( $order->get_total() );
	$shipping = floatval( $order->get_shipping_total() );
	$tax      = floatval( $order->get_total_tax() );
	?>
	<script>
	gtag('event','purchase',{
		transaction_id: '<?php echo esc_js( $order->get_order_number() ); ?>',
		currency:       '<?php echo esc_js( $currency ); ?>',
		value:          <?php echo $total; ?>,
		shipping:       <?php echo $shipping; ?>,
		tax:            <?php echo $tax; ?>,
		items:          <?php echo wp_json_encode( $items ); ?>
	});
	</script>
	<?php
}
