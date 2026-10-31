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
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
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
