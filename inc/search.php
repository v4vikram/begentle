<?php
/**
 * Live search (suggestions with product photos) and front-end scripts for search + AJAX filters.
 *
 * Endpoint: GET /wp-json/begentle/v1/search?q=term   (empty q = popular products)
 * No plugin: WooCommerce's own product search (title, content, SKU), cached for a couple of minutes.
 */
defined( 'ABSPATH' ) || exit;

/* ---------- Scripts (small, deferred, only what each page needs) ---------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! begentle_has_woo() ) {
			return;
		}

		$args = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);

		wp_enqueue_script( 'begentle-search', get_theme_file_uri( 'assets/js/search.js' ), array(), begentle_asset_version( 'assets/js/search.js' ), $args );
		wp_localize_script(
			'begentle-search',
			'begentleSearch',
			array(
				'url'     => esc_url_raw( rest_url( 'begentle/v1/search' ) ),
				'shopUrl' => esc_url_raw( home_url( '/' ) ),
				'i18n'    => array(
					/* translators: %s: search term */
					'seeAll'    => __( 'See all results for “%s”', 'begentle' ),
					/* translators: %s: search term */
					'noMatch'   => __( 'No matches for “%s”', 'begentle' ),
					'tryCats'   => __( 'Try a category', 'begentle' ),
					'popular'   => __( 'Popular right now', 'begentle' ),
					'recent'    => __( 'Recent searches', 'begentle' ),
					'clear'     => __( 'Clear', 'begentle' ),
					'soldOut'   => __( 'Sold out', 'begentle' ),
					'error'     => __( 'Suggestions are not available right now. Press Enter to search.', 'begentle' ),
					/* translators: %d: number of suggestions */
					'found'     => __( '%d suggestions available', 'begentle' ),
					'searching' => __( 'Searching…', 'begentle' ),
				),
			)
		);

		if ( begentle_is_catalog() ) {
			wp_enqueue_script( 'begentle-shop', get_theme_file_uri( 'assets/js/shop-filter.js' ), array(), begentle_asset_version( 'assets/js/shop-filter.js' ), $args );
		}
	},
	30
);

/* ---------- REST endpoint ---------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'begentle/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'begentle_rest_search',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
);

/** Any product change makes cached suggestions stale. */
function begentle_search_bump_cache(): void {
	update_option( 'begentle_search_ver', (string) microtime( true ), false );
}
add_action( 'save_post_product', 'begentle_search_bump_cache' );
add_action( 'woocommerce_update_product', 'begentle_search_bump_cache' );
add_action( 'woocommerce_product_set_stock_status', 'begentle_search_bump_cache' );
add_action( 'deleted_post', 'begentle_search_bump_cache' );
add_action( 'edited_product_cat', 'begentle_search_bump_cache' );

function begentle_search_item( WC_Product $product ): array {
	$image_id = $product->get_image_id();
	$cats     = get_the_terms( $product->get_id(), 'product_cat' );
	$cat_name = '';

	if ( is_array( $cats ) ) {
		foreach ( $cats as $cat ) {
			if ( (int) $cat->term_id !== (int) get_option( 'default_product_cat' ) ) {
				$cat_name = $cat->name;
				break;
			}
		}
	}

	$img = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' ) : '';

	return array(
		'id'    => $product->get_id(),
		'name'  => html_entity_decode( wp_strip_all_tags( $product->get_name() ), ENT_QUOTES, 'UTF-8' ),
		'url'   => get_permalink( $product->get_id() ),
		'img'   => $img ? $img : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
		'price' => preg_replace( '#<span class="screen-reader-text">.*?</span>#s', '', wp_kses_post( $product->get_price_html() ) ),
		'cat'   => html_entity_decode( $cat_name, ENT_QUOTES, 'UTF-8' ),
		'out'   => ! $product->is_in_stock(),
	);
}

function begentle_rest_search( WP_REST_Request $request ): WP_REST_Response {
	$q = trim( function_exists( 'mb_substr' ) ? mb_substr( (string) $request->get_param( 'q' ), 0, 60 ) : substr( (string) $request->get_param( 'q' ), 0, 60 ) );
	$q = preg_replace( '/\s+/', ' ', $q );

	if ( '' !== $q && strlen( $q ) < 2 ) {
		$q = '';
	}

	$key    = 'begentle_s_' . md5( strtolower( $q ) . '|' . get_option( 'begentle_search_ver', '0' ) );
	$cached = get_transient( $key );

	if ( false !== $cached ) {
		$response = rest_ensure_response( $cached );
		$response->header( 'Cache-Control', 'public, max-age=60' );
		return $response;
	}

	$items = array();

	if ( '' === $q ) {
		// Popular products for the empty search box.
		$products = wc_get_products(
			array(
				'status'     => 'publish',
				'limit'      => 4,
				'orderby'    => 'popularity',
				'visibility' => 'catalog',
				'return'     => 'objects',
			)
		);
		foreach ( $products as $product ) {
			$items[] = begentle_search_item( $product );
		}
	} else {
		$ids = WC_Data_Store::load( 'product' )->search_products( $q, '', false, false, 12 );

		$words   = array_filter( explode( ' ', strtolower( $q ) ) );
		$strong  = array();
		$content = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );

			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}

			// Name or SKU match ranks first; matches only found in the long description come after.
			$haystack = strtolower( $product->get_name() . ' ' . $product->get_sku() );
			$hits     = 0;
			foreach ( $words as $word ) {
				$hits += false !== strpos( $haystack, $word ) ? 1 : 0;
			}

			if ( $hits ) {
				$strong[] = begentle_search_item( $product );
			} else {
				$content[] = begentle_search_item( $product );
			}
		}

		// If anything matches by name, skip the noisy description-only matches.
		$items = array_slice( $strong ? $strong : $content, 0, 6 );
	}

	$cats  = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 4,
			'name__like' => $q,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);

	if ( '' === $q || ( ! is_wp_error( $terms ) && ! $terms ) ) {
		$terms = begentle_top_categories( 4 );
	}

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$cats[] = array(
					'name' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
					'url'  => $link,
				);
			}
		}
	}

	$data = array(
		'q'     => $q,
		'items' => $items,
		'cats'  => $cats,
		'all'   => add_query_arg(
			array(
				's'         => $q,
				'post_type' => 'product',
			),
			home_url( '/' )
		),
	);

	set_transient( $key, $data, 2 * MINUTE_IN_SECONDS );

	$response = rest_ensure_response( $data );
	$response->header( 'Cache-Control', 'public, max-age=60' );

	return $response;
}
