<?php
/**
 * Meta Pixel + Conversions API (CAPI)
 *
 * Pixel ID  : 1068157622902070
 * Browser   : wp_head / wp_body_open hooks → PageView + WC events
 * Server    : CAPI via wp_remote_post (Graph API v19.0)
 * Dedupe    : shared event_id on every browser+server pair
 */

defined( 'ABSPATH' ) || exit;

define( 'BEGENTLE_PIXEL_ID',   '1068157622902070' );
define( 'BEGENTLE_CAPI_TOKEN', 'EAANHVqXIGHcBS52Jq77jFtD3qrMJbxS2Wh6umScxVcuygZBq5AVnlV4eJV5vyxX4uUq1hDoYjz71UDNx2hoZCWzRZAt1rDvsRVlhnHpd3CKfLj676D6nrZATjhjV2WCZCdL3uaknXQk5euZBSvWKxkYO4f2AtPC0DnfkUQqz2uQGAni5bgsxrlyjA8NqJkuIjQUAZDZD' );
define( 'BEGENTLE_CAPI_URL',   'https://graph.facebook.com/v19.0/' . BEGENTLE_PIXEL_ID . '/events' );

/* ============================================================
 * 1. BASE CODE  — fires on every page
 * ========================================================== */
add_action( 'wp_head', 'begentle_pixel_base_code', 1 );
function begentle_pixel_base_code() { ?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){
n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?php echo esc_js( BEGENTLE_PIXEL_ID ); ?>');
fbq('track','PageView');
</script>
<!-- End Meta Pixel Code -->
<?php }

add_action( 'wp_body_open', 'begentle_pixel_noscript', 1 );
function begentle_pixel_noscript() {
	echo '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='
		. esc_attr( BEGENTLE_PIXEL_ID ) . '&ev=PageView&noscript=1"/></noscript>';
}

/* ============================================================
 * 2. CAPI HELPER — send one event to Graph API (non-blocking)
 * ========================================================== */
function begentle_capi_send( string $event_name, array $custom_data = [], string $event_id = '', array $user_extra = [] ): void {

	$payload = [
		'data' => [
			[
				'event_name'       => $event_name,
				'event_time'       => time(),
				'event_id'         => $event_id ?: uniqid( 'bg_', true ),
				'event_source_url' => ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( $_SERVER['HTTP_HOST'] ?? '' ) . sanitize_text_field( $_SERVER['REQUEST_URI'] ?? '' ),
				'action_source'    => 'website',
				'user_data'        => begentle_capi_user_data( $user_extra ),
				'custom_data'      => $custom_data,
			],
		],
		'access_token' => BEGENTLE_CAPI_TOKEN,
	];

	wp_remote_post( BEGENTLE_CAPI_URL, [
		'headers'  => [ 'Content-Type' => 'application/json' ],
		'body'     => wp_json_encode( $payload ),
		'blocking' => false,  // fire-and-forget — never slows the page
		'timeout'  => 5,
	] );
}

function begentle_capi_user_data( array $extra = [] ): array {
	$d = [
		'client_ip_address' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
		'client_user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
	];
	if ( ! empty( $_COOKIE['_fbc'] ) ) $d['fbc'] = sanitize_text_field( $_COOKIE['_fbc'] );
	if ( ! empty( $_COOKIE['_fbp'] ) ) $d['fbp'] = sanitize_text_field( $_COOKIE['_fbp'] );

	// Hash PII fields  (Meta requires SHA-256, lowercase-trimmed)
	foreach ( [ 'em', 'ph', 'fn', 'ln', 'ct', 'st', 'zp', 'country' ] as $key ) {
		if ( ! empty( $extra[ $key ] ) ) {
			$d[ $key ] = hash( 'sha256', strtolower( trim( $extra[ $key ] ) ) );
		}
	}
	return $d;
}

/* ============================================================
 * 3. ViewContent — single product pages
 * ========================================================== */
add_action( 'wp_footer', 'begentle_track_view_content' );
function begentle_track_view_content(): void {
	if ( ! is_product() ) return;

	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) return;

	$event_id = uniqid( 'vc_', true );
	$ids      = [ (string) $product->get_id() ];
	$price    = floatval( $product->get_price() );
	$currency = get_woocommerce_currency();
	?>
	<script>
	fbq('track','ViewContent',{
		content_ids:<?php echo wp_json_encode( $ids ); ?>,
		content_name:'<?php echo esc_js( $product->get_name() ); ?>',
		content_type:'product',
		value:<?php echo $price; ?>,
		currency:'<?php echo esc_js( $currency ); ?>'
	},{eventID:'<?php echo esc_js( $event_id ); ?>'});
	</script>
	<?php
	begentle_capi_send( 'ViewContent', [
		'content_ids'  => $ids,
		'content_name' => $product->get_name(),
		'content_type' => 'product',
		'value'        => $price,
		'currency'     => $currency,
	], $event_id );
}

/* ============================================================
 * 4. AddToCart — server fires on woocommerce_add_to_cart,
 *                browser reads event_id from WC session
 * ========================================================== */
add_action( 'woocommerce_add_to_cart', 'begentle_track_add_to_cart', 10, 6 );
function begentle_track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ): void {
	$product = wc_get_product( $variation_id ?: $product_id );
	if ( ! $product || ! WC()->session ) return;

	$event_id = uniqid( 'atc_', true );
	$currency = get_woocommerce_currency();
	$value    = floatval( $product->get_price() ) * intval( $quantity );

	WC()->session->set( 'begentle_atc_event_id',   $event_id );
	WC()->session->set( 'begentle_atc_product_ids', [ (string) $product_id ] );
	WC()->session->set( 'begentle_atc_value',       $value );
	WC()->session->set( 'begentle_atc_currency',    $currency );

	begentle_capi_send( 'AddToCart', [
		'content_ids'  => [ (string) $product_id ],
		'content_name' => $product->get_name(),
		'content_type' => 'product',
		'value'        => $value,
		'currency'     => $currency,
		'num_items'    => intval( $quantity ),
	], $event_id );
}

add_action( 'wp_footer', 'begentle_atc_browser_event' );
function begentle_atc_browser_event(): void {
	if ( ! WC()->session ) return;
	$event_id = WC()->session->get( 'begentle_atc_event_id' );
	if ( ! $event_id ) return;

	$ids      = WC()->session->get( 'begentle_atc_product_ids' ) ?: [];
	$value    = WC()->session->get( 'begentle_atc_value' ) ?: 0;
	$currency = WC()->session->get( 'begentle_atc_currency' ) ?: get_woocommerce_currency();

	// Clear so it only fires once
	WC()->session->set( 'begentle_atc_event_id', null );
	?>
	<script>
	fbq('track','AddToCart',{
		content_ids:<?php echo wp_json_encode( $ids ); ?>,
		content_type:'product',
		value:<?php echo floatval( $value ); ?>,
		currency:'<?php echo esc_js( $currency ); ?>'
	},{eventID:'<?php echo esc_js( $event_id ); ?>'});
	</script>
	<?php
}

/* ============================================================
 * 5. InitiateCheckout — checkout page (not thank-you)
 * ========================================================== */
add_action( 'wp_footer', 'begentle_track_initiate_checkout' );
function begentle_track_initiate_checkout(): void {
	if ( ! is_checkout() || is_order_received_page() ) return;
	$cart = WC()->cart;
	if ( ! $cart ) return;

	$event_id = uniqid( 'ic_', true );
	$value    = floatval( $cart->get_cart_contents_total() );
	$currency = get_woocommerce_currency();
	$num      = intval( $cart->get_cart_contents_count() );
	$ids      = [];
	foreach ( $cart->get_cart() as $item ) {
		$ids[] = (string) $item['product_id'];
	}
	?>
	<script>
	fbq('track','InitiateCheckout',{
		content_ids:<?php echo wp_json_encode( $ids ); ?>,
		value:<?php echo $value; ?>,
		currency:'<?php echo esc_js( $currency ); ?>',
		num_items:<?php echo $num; ?>
	},{eventID:'<?php echo esc_js( $event_id ); ?>'});
	</script>
	<?php
	begentle_capi_send( 'InitiateCheckout', [
		'content_ids' => $ids,
		'value'       => $value,
		'currency'    => $currency,
		'num_items'   => $num,
	], $event_id );
}

/* ============================================================
 * 6. Purchase — order-received / thank-you page
 *    Fires only once per order (post_meta guard)
 * ========================================================== */
add_action( 'woocommerce_thankyou', 'begentle_track_purchase', 10, 1 );
function begentle_track_purchase( $order_id ): void {
	if ( ! $order_id ) return;
	if ( get_post_meta( $order_id, '_begentle_pixel_purchase_fired', true ) ) return;
	update_post_meta( $order_id, '_begentle_pixel_purchase_fired', '1' );

	$order = wc_get_order( $order_id );
	if ( ! $order ) return;

	$event_id = uniqid( 'pur_', true );
	$value    = floatval( $order->get_total() );
	$currency = $order->get_currency();
	$ids      = [];
	foreach ( $order->get_items() as $item ) {
		$ids[] = (string) $item->get_product_id();
	}
	?>
	<script>
	fbq('track','Purchase',{
		content_ids:<?php echo wp_json_encode( $ids ); ?>,
		content_type:'product',
		value:<?php echo $value; ?>,
		currency:'<?php echo esc_js( $currency ); ?>',
		num_items:<?php echo count( $ids ); ?>
	},{eventID:'<?php echo esc_js( $event_id ); ?>'});
	</script>
	<?php
	begentle_capi_send( 'Purchase', [
		'content_ids'  => $ids,
		'content_type' => 'product',
		'value'        => $value,
		'currency'     => $currency,
		'num_items'    => count( $ids ),
		'order_id'     => (string) $order->get_order_number(),
	], $event_id, [
		'em'      => $order->get_billing_email(),
		'ph'      => $order->get_billing_phone(),
		'fn'      => $order->get_billing_first_name(),
		'ln'      => $order->get_billing_last_name(),
		'ct'      => $order->get_billing_city(),
		'st'      => $order->get_billing_state(),
		'zp'      => $order->get_billing_postcode(),
		'country' => $order->get_billing_country(),
	] );
}
