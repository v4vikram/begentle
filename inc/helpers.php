<?php
defined( 'ABSPATH' ) || exit;

function begentle_has_woo(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Inline SVG icon (static paths only, safe to echo).
 */
function begentle_icon( string $name, string $class = 'size-6' ): string {
	static $paths = array(
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'bag'    => '<path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
		'close'  => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'filter' => '<path d="M4 6h8M19 6h1M4 12h2M13 12h7M4 18h10M18 18h2"/><circle cx="15.5" cy="6" r="2.2"/><circle cx="9.5" cy="12" r="2.2"/><circle cx="16" cy="18" r="2.2"/>',
		'orders' => '<path d="m3 8 9-5 9 5v8l-9 5-9-5V8Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
		'pin'    => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'logout' => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
		'check'  => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Section heading row used on the home page: title on the left, optional link on the right.
 */
function begentle_section_head( string $id, string $title, string $url = '', string $link_label = '' ): void {
	echo '<div class="mb-4 flex items-end justify-between gap-4">';
	echo '<h2 id="' . esc_attr( $id ) . '" class="text-2xl font-extrabold tracking-tight md:text-3xl">' . esc_html( $title ) . '</h2>';
	if ( $url && $link_label ) {
		echo '<a class="shrink-0 text-sm font-semibold underline underline-offset-4" href="' . esc_url( $url ) . '">' . esc_html( $link_label ) . '</a>';
	}
	echo '</div>';
}

/**
 * Top-level product categories (default "Uncategorized" excluded).
 *
 * @return WP_Term[]
 */
function begentle_top_categories( int $limit = 8 ): array {
	if ( ! begentle_has_woo() ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'number'     => $limit,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Cart count badge. Also returned as a Woo cart fragment (see inc/woo.php),
 * so the markup must keep the `begentle-cart-count` class.
 */
function begentle_cart_count_html(): string {
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;

	return '<span class="begentle-cart-count" data-count="' . $count . '">' . $count . '</span>';
}

/**
 * Menu fallback so header/drawer/footer are usable before any menu is created.
 */
function begentle_menu_fallback( $args ) {
	$items = array();

	if ( begentle_has_woo() ) {
		if ( isset( $args['theme_location'] ) && 'footer' === $args['theme_location'] ) {
			$items[ __( 'Shop', 'begentle' ) ]       = wc_get_page_permalink( 'shop' );
			$items[ __( 'Cart', 'begentle' ) ]       = wc_get_cart_url();
			$items[ __( 'My account', 'begentle' ) ] = wc_get_page_permalink( 'myaccount' );
		} else {
			$items[ __( 'Shop all', 'begentle' ) ] = wc_get_page_permalink( 'shop' );

			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
					'number'     => 6,
				)
			);

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$items[ $term->name ] = get_term_link( $term );
				}
			}
		}
	}

	if ( ! $items ) {
		return;
	}

	echo '<ul class="' . esc_attr( $args['menu_class'] ?? '' ) . '">';
	foreach ( $items as $label => $url ) {
		echo '<li class="menu-item"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}
