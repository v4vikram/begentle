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

/* ---------- Card actions: quick add to cart + share ---------- */

/** Parent product ids currently in the cart (static per request). */
function begentle_cart_product_ids(): array {
	static $ids = null;

	if ( null === $ids ) {
		$ids = array();
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$ids[] = (int) $item['product_id'];
			}
		}
		$ids = array_values( array_unique( $ids ) );
	}

	return $ids;
}

/** Hidden list of cart product ids; also a cart fragment, so card icons follow the cart live. */
function begentle_cart_ids_html(): string {
	return '<div id="begentle-cart-ids" hidden data-ids="' . esc_attr( implode( ',', begentle_cart_product_ids() ) ) . '"></div>';
}
add_action( 'wp_footer', fn() => print( begentle_cart_ids_html() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['div#begentle-cart-ids'] = begentle_cart_ids_html();
		return $fragments;
	}
);

/**
 * Sizes / options a variable product can be added with straight from the card.
 * Variations using "Any …" attributes are skipped (they need a choice we can't show here).
 *
 * @return array{label:string,options:array<int,array{id:int,label:string,in_stock:bool}>}|null
 */
function begentle_quick_add_options( WC_Product $product ): ?array {
	if ( ! $product->is_type( 'variable' ) ) {
		return null;
	}

	$options    = array();
	$attributes = array_keys( $product->get_variation_attributes() );

	foreach ( $product->get_available_variations() as $variation ) {
		$labels = array();

		foreach ( $variation['attributes'] as $key => $value ) {
			if ( '' === $value ) {
				return null;
			}
			$taxonomy = str_replace( 'attribute_', '', $key );
			$term     = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $value, $taxonomy ) : false;
			$labels[] = $term ? $term->name : $value;
		}

		$options[] = array(
			'id'       => (int) $variation['variation_id'],
			'label'    => implode( ' / ', $labels ),
			'in_stock' => ! empty( $variation['is_in_stock'] ) && ! empty( $variation['is_purchasable'] ),
		);
	}

	if ( ! $options || count( $options ) > 12 ) {
		return null;
	}

	$label = 1 === count( $attributes ) ? wc_attribute_label( $attributes[0], $product ) : __( 'option', 'begentle' );

	return array(
		/* translators: %s: attribute name, e.g. Size */
		'label'   => sprintf( __( 'Select %s', 'begentle' ), strtolower( $label ) ),
		'options' => $options,
	);
}

// Replaces the default loop button (removed above). Kept outside the card link: no nested links.
add_action(
	'woocommerce_after_shop_loop_item',
	function () {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$name    = $product->get_name();
		$in_cart = in_array( $product->get_id(), begentle_cart_product_ids(), true ) ? ' is-added' : '';
		$bag     = begentle_icon( 'bag', 'size-5' );

		echo '<div class="card-actions">';

		printf(
			'<button type="button" class="card-btn card-share" data-share-url="%1$s" data-share-title="%2$s" aria-label="%3$s">%4$s</button>',
			esc_url( $product->get_permalink() ),
			esc_attr( $name ),
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Share %s', 'begentle' ), $name ) ),
			begentle_icon( 'share', 'size-[18px]' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$quick = begentle_quick_add_options( $product );

			if ( $product->is_type( 'simple' ) ) {
				printf(
					'<a href="%1$s" rel="nofollow" class="card-btn card-cart%2$s" data-product-id="%3$d" aria-label="%4$s">%5$s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( $in_cart ),
					(int) $product->get_id(),
					/* translators: %s: product name */
					esc_attr( sprintf( __( 'Add %s to bag', 'begentle' ), $name ) ),
					$bag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			} elseif ( $quick ) {
				$panel_id = 'quick-add-' . $product->get_id() . '-' . wp_unique_id();

				printf(
					'<button type="button" class="card-btn card-cart%1$s" data-product-id="%2$d" data-quick-add aria-expanded="false" aria-controls="%3$s" aria-label="%4$s">%5$s</button>',
					esc_attr( $in_cart ),
					(int) $product->get_id(),
					esc_attr( $panel_id ),
					/* translators: %s: product name */
					esc_attr( sprintf( __( 'Add %s to bag', 'begentle' ), $name ) ),
					$bag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);

				echo '<div class="quick-add" id="' . esc_attr( $panel_id ) . '" hidden>';
				echo '<p class="quick-add__title">' . esc_html( $quick['label'] ) . '</p><div class="quick-add__opts">';
				foreach ( $quick['options'] as $option ) {
					printf(
						'<button type="button" class="quick-add__opt" data-add-id="%1$d" %2$s>%3$s</button>',
						(int) $option['id'],
						$option['in_stock'] ? '' : 'disabled',
						esc_html( $option['label'] )
					);
				}
				echo '</div></div>';
			} else {
				printf(
					'<a href="%1$s" class="card-btn card-cart%2$s" data-product-id="%3$d" aria-label="%4$s">%5$s</a>',
					esc_url( $product->get_permalink() ),
					esc_attr( $in_cart ),
					(int) $product->get_id(),
					/* translators: %s: product name */
					esc_attr( sprintf( __( 'Choose options for %s', 'begentle' ), $name ) ),
					$bag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
		}

		echo '</div>';
	},
	10
);

// Share icon on the product image, beside the zoom button. Printed right after the gallery
// (priority 20) and moved into it by assets/js/main.js; hidden until then, since sharing needs JS.
add_action(
	'woocommerce_before_single_product_summary',
	function () {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		printf(
			'<button type="button" class="gallery-share" data-share-url="%1$s" data-share-title="%2$s" aria-label="%3$s" hidden>%4$s</button>',
			esc_url( $product->get_permalink() ),
			esc_attr( $product->get_name() ),
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Share %s', 'begentle' ), $product->get_name() ) ),
			begentle_icon( 'share', 'size-[18px]' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	},
	21
);

/* ---------- Shop toolbar: result count + sorting on one row ---------- */

add_action( 'woocommerce_before_shop_loop', fn() => print( '<div class="shop-toolbar">' ), 15 );
add_action( 'woocommerce_before_shop_loop', fn() => print( '</div>' ), 35 );

/* ---------- Single product ---------- */

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

// − / + buttons around the quantity box (behaviour in assets/js/main.js). Product page only.
function begentle_qty_button( string $direction ): void {
	if ( ! is_product() ) {
		return;
	}

	printf(
		'<button type="button" class="qty-btn" data-qty="%1$s" aria-label="%2$s">%3$s</button>',
		esc_attr( $direction ),
		'plus' === $direction ? esc_attr__( 'Increase quantity', 'begentle' ) : esc_attr__( 'Decrease quantity', 'begentle' ),
		begentle_icon( $direction, 'size-4' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
add_action( 'woocommerce_before_quantity_input_field', fn() => begentle_qty_button( 'minus' ) );
add_action( 'woocommerce_after_quantity_input_field', fn() => begentle_qty_button( 'plus' ) );

// Mobile sticky "Add to cart" bar (markup in template-parts/sticky-atc.php).
add_action(
	'woocommerce_after_single_product',
	function () {
		get_template_part( 'template-parts/sticky-atc' );
	}
);

/* ---------- Mini-cart drawer ---------- */

// − qty + stepper and line total instead of "1 × ₹799". The nonce is printed here (inside the
// cart fragment) because a guest's session, and so their nonce, only starts with the first add.
add_filter(
	'woocommerce_widget_cart_item_quantity',
	function ( $html, $cart_item, $cart_item_key ) {
		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof WC_Product || $product->is_sold_individually() ) {
			return $html;
		}

		$qty  = (int) $cart_item['quantity'];
		$max  = (int) $product->get_max_purchase_quantity();
		$name = wp_strip_all_tags( $product->get_name() );

		return sprintf(
			'<div class="mini-qty" data-key="%1$s" data-max="%2$d" data-nonce="%3$s">
				<div class="mini-qty__stepper">
					<button type="button" class="mini-qty__btn" data-step="-1" aria-label="%4$s" %5$s>%6$s</button>
					<span class="mini-qty__value" aria-live="polite">%7$d</span>
					<button type="button" class="mini-qty__btn" data-step="1" aria-label="%8$s" %9$s>%10$s</button>
				</div>
				<span class="mini-qty__price">%11$s</span>
			</div>',
			esc_attr( $cart_item_key ),
			$max,
			esc_attr( wp_create_nonce( 'begentle-cart' ) ),
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Decrease quantity of %s', 'begentle' ), $name ) ),
			$qty <= 1 ? 'disabled' : '',
			begentle_icon( 'minus', 'size-4' ),
			$qty,
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Increase quantity of %s', 'begentle' ), $name ) ),
			$max > 0 && $qty >= $max ? 'disabled' : '',
			begentle_icon( 'plus', 'size-4' ),
			wp_kses_post( WC()->cart->get_product_subtotal( $product, $qty ) )
		);
	},
	10,
	3
);

// AJAX: ?wc-ajax=begentle_update_qty  (key, quantity, nonce) -> fresh cart fragments.
add_action(
	'wc_ajax_begentle_update_qty',
	function () {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified just below.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		$key   = isset( $_POST['key'] ) ? wc_clean( wp_unslash( $_POST['key'] ) ) : '';
		$qty   = isset( $_POST['quantity'] ) ? (int) wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0;
		// phpcs:enable

		if ( ! wp_verify_nonce( $nonce, 'begentle-cart' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page.', 'begentle' ) ), 403 );
		}

		$item = WC()->cart->get_cart_item( $key );

		if ( $item ) {
			$max = (int) $item['data']->get_max_purchase_quantity();
			$qty = max( 1, $max > 0 ? min( $qty, $max ) : $qty );

			if ( apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $qty ) ) {
				WC()->cart->set_quantity( $key, $qty, true );
			}
		}

		WC_AJAX::get_refreshed_fragments(); // Sends JSON and exits.
	}
);

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['span.begentle-cart-count'] = begentle_cart_count_html();
		return $fragments;
	}
);
