<?php
/**
 * Trust signals: badge row under Add to cart, contact + payment chips in the footer,
 * floating WhatsApp chat button. Everything follows the store settings (COD on/off,
 * free delivery amount, WhatsApp number) and hides itself when the data is missing.
 */
defined( 'ABSPATH' ) || exit;

function begentle_trust_icon( string $name ): string {
	static $paths = array(
		'truck'  => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="17" r="1.8"/><circle cx="17" cy="17" r="1.8"/>',
		'cash'   => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
		'return' => '<path d="M4 12a8 8 0 1 0 2.3-5.6"/><path d="M4 4v4h4"/>',
		'lock'   => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
		'chat'   => '<path d="M4 20l1.3-3.9A8 8 0 1 1 8.5 19.3z"/><path d="M9 10.5c.5 2 2 3.6 4.5 4.5l1.3-1.2 1.7.8-.4 1.6c-3.6.3-7.7-3.6-7.6-7.2l1.6-.4.8 1.7z" fill="currentColor" stroke="none"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'insta'  => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

function begentle_cod_enabled(): bool {
	if ( ! begentle_has_woo() || ! WC()->payment_gateways() ) {
		return false;
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	return isset( $gateways['cod'] ) && 'yes' === $gateways['cod']->enabled;
}

function begentle_returns_url(): string {
	foreach ( array( 'refund-returns', 'returns', 'refund-policy' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			return (string) get_permalink( $page );
		}
	}
	return '';
}

/** Badge list shared by the product page (and reusable elsewhere). */
function begentle_trust_items(): array {
	$items    = array();
	$free_min = function_exists( 'begentle_checkout_mod' ) ? (int) begentle_checkout_mod( 'free_shipping_min' ) : 0;

	$items[] = array(
		'truck',
		$free_min > 0
			/* translators: %s: amount, e.g. ₹999 */
			? sprintf( __( 'Free delivery above %s', 'begentle' ), html_entity_decode( wp_strip_all_tags( wc_price( $free_min ) ), ENT_QUOTES, 'UTF-8' ) )
			: __( 'Ships across India', 'begentle' ),
		'',
	);

	if ( begentle_cod_enabled() ) {
		$items[] = array( 'cash', __( 'Cash on delivery available', 'begentle' ), '' );
	}

	$items[] = array( 'return', __( 'Easy returns on sealed packs', 'begentle' ), begentle_returns_url() );
	$items[] = array( 'lock', __( 'Secure payment: UPI, cards', 'begentle' ), '' );

	return $items;
}

// Product page: badges right under the Add to cart form.
add_action(
	'woocommerce_single_product_summary',
	function () {
		echo '<ul class="trust-row" aria-label="' . esc_attr__( 'Why shop with us', 'begentle' ) . '">';
		foreach ( begentle_trust_items() as list( $icon, $text, $url ) ) {
			$inner = begentle_trust_icon( $icon ) . '<span>' . esc_html( $text ) . '</span>';
			echo '<li>' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $inner . '</a>' : $inner ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
	},
	35
);

/** Footer: contact links (email / WhatsApp / Instagram) under the brand blurb. */
function begentle_footer_contact(): void {
	$email     = (string) begentle_mod( 'contact_email' );
	$whatsapp  = (string) begentle_mod( 'contact_whatsapp' );
	$instagram = ltrim( (string) begentle_mod( 'contact_instagram' ), '@' );

	if ( ! $email && ! $whatsapp && ! $instagram ) {
		return;
	}

	echo '<ul class="footer-contact">';
	if ( $email ) {
		printf( '<li><a href="mailto:%1$s">%2$s<span>%3$s</span></a></li>', esc_attr( $email ), begentle_trust_icon( 'mail' ), esc_html( $email ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $whatsapp ) {
		printf( '<li><a href="%1$s" target="_blank" rel="noopener">%2$s<span>+%3$s</span></a></li>', esc_url( 'https://wa.me/' . $whatsapp ), begentle_trust_icon( 'chat' ), esc_html( $whatsapp ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $instagram ) {
		printf( '<li><a href="%1$s" target="_blank" rel="noopener">%2$s<span>@%3$s</span></a></li>', esc_url( 'https://instagram.com/' . $instagram ), begentle_trust_icon( 'insta' ), esc_html( $instagram ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ul>';
}

/** Footer bottom bar: accepted payment methods as text chips (no third-party logos). */
function begentle_footer_payments(): void {
	if ( ! begentle_has_woo() ) {
		return;
	}

	$chips = array( __( 'UPI', 'begentle' ), __( 'Cards', 'begentle' ), __( 'Netbanking', 'begentle' ) );
	if ( begentle_cod_enabled() ) {
		$chips[] = __( 'Cash on delivery', 'begentle' );
	}

	echo '<ul class="pay-chips" aria-label="' . esc_attr__( 'We accept', 'begentle' ) . '">';
	echo '<li class="pay-chips__lock">' . begentle_trust_icon( 'lock' ) . '<span>' . esc_html__( 'Secure payments', 'begentle' ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	foreach ( $chips as $chip ) {
		echo '<li>' . esc_html( $chip ) . '</li>';
	}
	echo '</ul>';
}

// Floating "Chat with us" button (only when a WhatsApp number is set in the Customizer).
add_action(
	'wp_footer',
	function () {
		$whatsapp = (string) begentle_mod( 'contact_whatsapp' );

		if ( ! $whatsapp || ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) ) {
			return;
		}

		$text = is_singular( 'product' )
			/* translators: %s: product name */
			? sprintf( __( 'Hi! I have a question about %s', 'begentle' ), get_the_title() )
			: __( 'Hi! I have a question', 'begentle' );

		printf(
			'<a class="wa-float" href="%1$s" target="_blank" rel="noopener" aria-label="%2$s">%3$s</a>',
			esc_url( 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode( $text ) ),
			esc_attr__( 'Chat with us on WhatsApp', 'begentle' ),
			begentle_trust_icon( 'chat' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	},
	20
);
