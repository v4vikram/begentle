<?php
/**
 * My Account: dashboard tiles, recent orders and an order-progress tracker.
 * Hook-based (no template overrides); the look lives in src/css/main.css.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

// Clothes shop: no downloadable files.
add_filter(
	'woocommerce_account_menu_items',
	function ( $items ) {
		unset( $items['downloads'] );
		return $items;
	}
);

/**
 * Dashboard: quick links + last 3 orders, below Woo's greeting.
 */
add_action(
	'woocommerce_account_dashboard',
	function () {
		$tiles = array(
			array( 'orders', __( 'Orders', 'begentle' ), __( 'Track and review', 'begentle' ), wc_get_account_endpoint_url( 'orders' ) ),
			array( 'pin', __( 'Addresses', 'begentle' ), __( 'Shipping & billing', 'begentle' ), wc_get_account_endpoint_url( 'edit-address' ) ),
			array( 'user', __( 'Details', 'begentle' ), __( 'Name, email, password', 'begentle' ), wc_get_account_endpoint_url( 'edit-account' ) ),
			array( 'logout', __( 'Log out', 'begentle' ), __( 'See you soon', 'begentle' ), wc_logout_url() ),
		);

		echo '<div class="account-tiles">';
		foreach ( $tiles as list( $icon, $title, $hint, $url ) ) {
			printf(
				'<a class="account-tile" href="%s">%s<span class="block text-sm font-bold">%s</span><span class="block text-xs text-muted">%s</span></a>',
				esc_url( $url ),
				begentle_icon( $icon, 'mb-2 size-6' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $title ),
				esc_html( $hint )
			);
		}
		echo '</div>';

		$orders = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => 3,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);

		echo '<div class="mt-8 flex items-end justify-between gap-4"><h2 class="text-xl font-extrabold tracking-tight">' . esc_html__( 'Recent orders', 'begentle' ) . '</h2>';
		if ( $orders ) {
			echo '<a class="text-sm font-semibold underline underline-offset-4" href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '">' . esc_html__( 'View all', 'begentle' ) . '</a>';
		}
		echo '</div>';

		if ( ! $orders ) {
			echo '<div class="mt-4 rounded-2xl bg-mist p-8 text-center"><p class="text-muted">' . esc_html__( 'No orders yet. Your first one is waiting.', 'begentle' ) . '</p><a class="btn mt-4" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Start shopping', 'begentle' ) . '</a></div>';
			return;
		}

		echo '<div class="mt-4 grid gap-3">';
		foreach ( $orders as $order ) {
			$items = $order->get_item_count();

			printf(
				'<a class="order-card order-card--%1$s" href="%2$s"><span class="min-w-0"><span class="block text-sm font-bold">%3$s</span><span class="block text-xs text-muted">%4$s &middot; %5$s</span></span><span class="text-right"><span class="order-badge">%6$s</span><span class="mt-1 block text-sm font-semibold">%7$s</span></span></a>',
				esc_attr( $order->get_status() ),
				esc_url( $order->get_view_order_url() ),
				/* translators: %s: order number */
				esc_html( sprintf( __( 'Order #%s', 'begentle' ), $order->get_order_number() ) ),
				esc_html( wc_format_datetime( $order->get_date_created() ) ),
				/* translators: %d: number of items */
				esc_html( sprintf( _n( '%d item', '%d items', $items, 'begentle' ), $items ) ),
				esc_html( wc_get_order_status_name( $order->get_status() ) ),
				wp_kses_post( $order->get_formatted_order_total() )
			);
		}
		echo '</div>';
	}
);

/**
 * View order: progress steps above the details table.
 * Only for the normal flow; cancelled / refunded / failed orders just show their status text.
 */
add_action(
	'woocommerce_view_order',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$position = array(
			'pending'    => 0,
			'on-hold'    => 0,
			'processing' => 1,
			'completed'  => 2,
		);
		$status   = $order->get_status();

		if ( ! isset( $position[ $status ] ) ) {
			return;
		}

		$steps = array(
			__( 'Placed', 'begentle' ),
			__( 'Processing', 'begentle' ),
			__( 'Completed', 'begentle' ),
		);

		echo '<ol class="order-steps" aria-label="' . esc_attr__( 'Order progress', 'begentle' ) . '">';
		foreach ( $steps as $index => $label ) {
			// "Completed" means every step is finished, including the last one.
			$done  = $index < $position[ $status ] || 'completed' === $status;
			$class = $done ? 'is-done' : ( $index === $position[ $status ] ? 'is-current' : '' );
			$mark  = $done ? begentle_icon( 'check', 'size-4' ) : (string) ( $index + 1 );

			printf(
				'<li class="%s"><span class="order-steps__dot">%s</span><span class="order-steps__label">%s</span></li>',
				esc_attr( $class ),
				$mark, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $label )
			);
		}
		echo '</ol>';
	},
	5
);
