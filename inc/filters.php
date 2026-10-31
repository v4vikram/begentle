<?php
/**
 * Shop filters. Built on WooCommerce's own query vars, so no plugin or widget is needed:
 *   min_price / max_price           (WC_Query price filter)
 *   filter_{attribute}=a,b          (WC_Query layered nav, combined with OR below)
 *   on_sale=1, in_stock=1           (added here)
 * The UI is a drawer (template-parts/filter-drawer.php), submitted as a plain GET form.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

/** Shop page, category/tag/attribute archives and product search results. */
function begentle_is_catalog(): bool {
	return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

/** URL of the current listing without any filter params (the form action). */
function begentle_catalog_url(): string {
	if ( is_search() ) {
		return home_url( '/' );
	}

	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );

		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return wc_get_page_permalink( 'shop' );
}

/** Current listing URL with some query args changed (false/'' removes an arg). Pagination is always reset. */
function begentle_url_with( array $changes ): string {
	$params = array();

	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_string( $value ) ) {
			$params[ sanitize_key( $key ) ] = wc_clean( wp_unslash( $value ) );
		}
	}
	unset( $params['paged'], $params['page'] );

	foreach ( $changes as $key => $value ) {
		if ( false === $value || '' === $value ) {
			unset( $params[ $key ] );
		} else {
			$params[ $key ] = $value;
		}
	}

	return add_query_arg( $params, begentle_catalog_url() );
}

/** Selected term slugs for an attribute (e.g. "size"). */
function begentle_chosen_terms( string $attribute ): array {
	$key = 'filter_' . $attribute;

	if ( empty( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return array();
	}

	return array_values( array_filter( array_map( 'sanitize_title', explode( ',', wp_unslash( $_GET[ $key ] ) ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Lowest / highest price in the catalogue, used as input placeholders. Cached for an hour.
 *
 * @return array{min:int,max:int}
 */
function begentle_price_bounds(): array {
	$bounds = get_transient( 'begentle_price_bounds' );

	if ( false === $bounds ) {
		global $wpdb;

		$row    = $wpdb->get_row( "SELECT MIN(min_price) AS lo, MAX(max_price) AS hi FROM {$wpdb->prefix}wc_product_meta_lookup WHERE min_price > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$bounds = array(
			'min' => $row && $row->lo ? (int) floor( $row->lo ) : 0,
			'max' => $row && $row->hi ? (int) ceil( $row->hi ) : 0,
		);
		set_transient( 'begentle_price_bounds', $bounds, HOUR_IN_SECONDS );
	}

	return $bounds;
}
add_action( 'woocommerce_update_product', fn() => delete_transient( 'begentle_price_bounds' ) );
add_action( 'woocommerce_new_product', fn() => delete_transient( 'begentle_price_bounds' ) );

/**
 * Product attributes that have at least one used term (size, colour, ...).
 *
 * @return array<int,array{name:string,label:string,terms:WP_Term[]}>
 */
function begentle_filter_attributes(): array {
	$out = array();

	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$terms = get_terms(
			array(
				'taxonomy'   => wc_attribute_taxonomy_name( $attribute->attribute_name ),
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $terms ) && $terms ) {
			$out[] = array(
				'name'  => $attribute->attribute_name,
				'label' => $attribute->attribute_label,
				'terms' => $terms,
			);
		}
	}

	return $out;
}

/**
 * Filters currently applied, each with a URL that removes just that one.
 *
 * @return array<int,array{label:string,url:string}>
 */
function begentle_active_filters(): array {
	$items = array();

	foreach ( begentle_filter_attributes() as $attribute ) {
		$chosen = begentle_chosen_terms( $attribute['name'] );

		foreach ( $attribute['terms'] as $term ) {
			if ( in_array( $term->slug, $chosen, true ) ) {
				$items[] = array(
					'label' => $term->name,
					'url'   => begentle_url_with( array( 'filter_' . $attribute['name'] => implode( ',', array_diff( $chosen, array( $term->slug ) ) ) ) ),
				);
			}
		}
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$min = isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ? absint( $_GET['min_price'] ) : null;
	$max = isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ? absint( $_GET['max_price'] ) : null;
	// phpcs:enable

	if ( null !== $min || null !== $max ) {
		$symbol  = html_entity_decode( get_woocommerce_currency_symbol() );
		$items[] = array(
			'label' => null !== $min && null !== $max ? "{$symbol}{$min} – {$symbol}{$max}" : ( null !== $min ? "{$symbol}{$min}+" : "≤ {$symbol}{$max}" ),
			'url'   => begentle_url_with(
				array(
					'min_price' => false,
					'max_price' => false,
				)
			),
		);
	}

	if ( ! empty( $_GET['in_stock'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$items[] = array(
			'label' => __( 'In stock', 'begentle' ),
			'url'   => begentle_url_with( array( 'in_stock' => false ) ),
		);
	}

	if ( ! empty( $_GET['on_sale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$items[] = array(
			'label' => __( 'On sale', 'begentle' ),
			'url'   => begentle_url_with( array( 'on_sale' => false ) ),
		);
	}

	return $items;
}

/** URL that drops every filter but keeps sorting / search. */
function begentle_clear_filters_url(): string {
	$changes = array(
		'min_price' => false,
		'max_price' => false,
		'in_stock'  => false,
		'on_sale'   => false,
	);

	foreach ( begentle_filter_attributes() as $attribute ) {
		$changes[ 'filter_' . $attribute['name'] ] = false;
	}

	return begentle_url_with( $changes );
}

/* ---------- Query ---------- */

// Selecting several sizes / colours should widen results (28 OR 30), not narrow them.
add_filter( 'woocommerce_layered_nav_default_query_type', fn() => 'or' );

add_action(
	'woocommerce_product_query',
	function ( $query ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['on_sale'] ) ) {
			$ids      = wc_get_product_ids_on_sale();
			$existing = array_filter( (array) $query->get( 'post__in' ) );
			$ids      = $existing ? array_intersect( $existing, $ids ) : $ids;
			$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		}

		if ( ! empty( $_GET['in_stock'] ) ) {
			$meta   = (array) $query->get( 'meta_query' );
			$meta[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
			$query->set( 'meta_query', $meta );
		}
		// phpcs:enable
	}
);

/* ---------- Listing page UI ---------- */

// We print our own title / description (template-parts/catalog-header.php).
add_filter( 'woocommerce_show_page_title', '__return_false' );
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );

add_filter(
	'woocommerce_catalog_orderby',
	function ( $options ) {
		$labels = array(
			'menu_order' => __( 'Featured', 'begentle' ),
			'popularity' => __( 'Best selling', 'begentle' ),
			'rating'     => __( 'Top rated', 'begentle' ),
			'date'       => __( 'Newest', 'begentle' ),
			'price'      => __( 'Price: low to high', 'begentle' ),
			'price-desc' => __( 'Price: high to low', 'begentle' ),
		);

		foreach ( $labels as $key => $label ) {
			if ( isset( $options[ $key ] ) ) {
				$options[ $key ] = $label;
			}
		}

		return $options;
	}
);

// Toolbar: [Filter button] [result count] [sort]. Open/close wrappers live in inc/woo.php (priorities 15 / 35).
add_action(
	'woocommerce_before_shop_loop',
	function () {
		$count = count( begentle_active_filters() );

		printf(
			'<button type="button" class="filter-btn" data-drawer-open="filter" aria-controls="drawer-filter" aria-expanded="false">%s<span>%s</span>%s</button>',
			begentle_icon( 'filter', 'size-5' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Filter', 'begentle' ),
			$count ? '<b>' . (int) $count . '</b>' : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	},
	16
);

function begentle_render_active_filters(): void {
	$items = begentle_active_filters();

	if ( ! $items ) {
		return;
	}

	echo '<div class="active-filters">';
	foreach ( $items as $item ) {
		printf(
			'<a class="chip-remove" href="%s" aria-label="%s">%s <span aria-hidden="true">&times;</span></a>',
			esc_url( $item['url'] ),
			/* translators: %s: filter name */
			esc_attr( sprintf( __( 'Remove filter %s', 'begentle' ), $item['label'] ) ),
			esc_html( $item['label'] )
		);
	}
	printf( '<a class="clear-all" href="%s">%s</a>', esc_url( begentle_clear_filters_url() ), esc_html__( 'Clear all', 'begentle' ) );
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop', 'begentle_render_active_filters', 36 );

// Zero results: the toolbar is not rendered, so give the user a way back.
add_action( 'woocommerce_no_products_found', 'begentle_render_active_filters', 20 );
