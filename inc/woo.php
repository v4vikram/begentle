<?php
/**
 * WooCommerce integration. Hooks only, no template overrides: keeps Woo updates painless.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

// The theme owns all shop styling (src/css/main.css). Cart/Checkout *blocks* keep their own block CSS.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

add_filter( 'loop_shop_per_page', fn() => 12 );
add_filter( 'loop_shop_columns', fn() => 4 );
add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

// Fewer checkout fields = fewer drop-offs on mobile.
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );
		return $fields;
	}
);

/* ---------- Product card (shop loop) ---------- */

// Tap-to-open card: no rating, no add-to-cart button (jeans need a size first).
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'begentle_loop_media', 10 );

function begentle_sale_badge( WC_Product $product ): string {
	if ( ! $product->is_on_sale() ) {
		return '';
	}

	$label = __( 'Sale', 'begentle' );

	if ( $product->is_type( 'simple' ) ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular > 0 && $sale > 0 ) {
			$label = '-' . round( ( ( $regular - $sale ) / $regular ) * 100 ) . '%';
		}
	}

	return '<span class="badge-sale">' . esc_html( $label ) . '</span>';
}

// Same badge on the single product gallery.
add_filter(
	'woocommerce_sale_flash',
	fn( $html, $post, $product ) => begentle_sale_badge( $product ),
	10,
	3
);

function begentle_loop_media() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$gallery = $product->get_gallery_image_ids();

	echo '<span class="card-media">';
	echo woocommerce_get_product_thumbnail(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	// Second image on hover (hover-capable devices only, see CSS).
	if ( $gallery ) {
		echo wp_get_attachment_image(
			$gallery[0],
			'woocommerce_thumbnail',
			false,
			array(
				'class'   => 'card-media__alt',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
	}

	if ( ! $product->is_in_stock() ) {
		echo '<span class="badge-sale badge-soldout">' . esc_html__( 'Sold out', 'begentle' ) . '</span>';
	} else {
		echo begentle_sale_badge( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</span>';
}

/* ---------- Shop toolbar: result count + sorting on one row ---------- */

add_action( 'woocommerce_before_shop_loop', fn() => print( '<div class="shop-toolbar">' ), 15 );
add_action( 'woocommerce_before_shop_loop', fn() => print( '</div>' ), 35 );

/* ---------- Single product ---------- */

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

// Mobile sticky "Add to cart" bar (markup in template-parts/sticky-atc.php).
add_action(
	'woocommerce_after_single_product',
	function () {
		get_template_part( 'template-parts/sticky-atc' );
	}
);

/* ---------- Mini-cart drawer ---------- */

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['span.begentle-cart-count'] = begentle_cart_count_html();
		return $fragments;
	}
);
