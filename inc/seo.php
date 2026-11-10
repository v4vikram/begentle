<?php
/**
 * Store SEO: description, Open Graph and Twitter tags for the home page, shop, product
 * categories, products and normal pages (the blog has its own in inc/blog.php), plus a few
 * additions to WooCommerce's own Product schema and an Organization / WebSite graph on the
 * home page. Everything here steps aside when an SEO plugin is active.
 */
defined( 'ABSPATH' ) || exit;

/** Plain text, single spaces, cut to about 160 characters on a word boundary. */
function begentle_seo_clean( $text, int $max = 160 ): string {
	$text = wp_strip_all_tags( str_replace( '<', ' <', strip_shortcodes( (string) $text ) ) );
	$text = trim( preg_replace( '/\s+/', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );

	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}

	$cut = mb_substr( $text, 0, $max - 1 );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );

	return rtrim( $cut, " ,.;:-" ) . '…';
}

/** Share image fallback: hero image, then a raster logo (OG previews do not render SVG). */
function begentle_seo_default_image(): string {
	$hero = (int) begentle_mod( 'hero_image' );

	if ( $hero ) {
		return (string) wp_get_attachment_image_url( $hero, 'large' );
	}

	$logo = begentle_site_logo_url();

	return ( $logo && '.svg' !== substr( strtolower( $logo ), -4 ) ) ? $logo : '';
}

/** Shop filters, sorting and search create duplicate listings. */
function begentle_seo_is_filtered_shop(): bool {
	if ( ! begentle_has_woo() || ! ( is_shop() || is_product_taxonomy() ) ) {
		return false;
	}

	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $key, array( 'orderby', 'min_price', 'max_price', 'on_sale', 'stock_status', 'rating_filter', 's' ), true ) || 0 === strpos( (string) $key, 'filter_' ) ) {
			return true;
		}
	}

	return false;
}

// Filtered shop listings: noindex, but let crawlers follow through to the products.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( begentle_seo_plugin_active() || ! begentle_seo_is_filtered_shop() ) {
			return $robots;
		}

		$robots['noindex'] = true;
		// "Discourage search engines" already adds nofollow; never contradict it.
		if ( empty( $robots['nofollow'] ) ) {
			$robots['follow'] = true;
		}

		return $robots;
	}
);

add_action(
	'wp_head',
	function () {
		if ( begentle_seo_plugin_active() || begentle_is_blog_listing() || is_singular( 'post' ) || is_404() || is_search() ) {
			return;
		}

		$woo         = begentle_has_woo();
		$type        = 'website';
		$title       = wp_get_document_title();
		$description = '';
		$image       = '';
		$url         = '';
		$canonical   = false; // WordPress prints its own canonical for singular pages.
		$extra       = array();

		if ( is_front_page() ) {
			$description = get_bloginfo( 'description' );
			$hero        = trim( (string) begentle_mod( 'hero_text' ) );
			if ( $hero ) {
				$description = $description ? $description . '. ' . $hero : $hero;
			}
			$url = home_url( '/' );
		} elseif ( $woo && is_product() ) {
			$product = wc_get_product( get_queried_object_id() );

			if ( ! $product ) {
				return;
			}

			$type        = 'product';
			$title       = $product->get_name();
			$description = $product->get_short_description() ? $product->get_short_description() : $product->get_description();
			$image       = (string) wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			$url         = $product->get_permalink();

			$price = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min', true ) : wc_get_price_to_display( $product );
			if ( '' !== (string) $price && (float) $price > 0 ) {
				$extra['product:price:amount']   = wc_format_decimal( $price, wc_get_price_decimals() );
				$extra['product:price:currency'] = get_woocommerce_currency();
			}
			$extra['product:availability'] = $product->is_in_stock() ? 'in stock' : 'out of stock';
			$extra['product:brand']        = get_bloginfo( 'name' );
		} elseif ( $woo && is_product_taxonomy() ) {
			$term        = get_queried_object();
			$description = term_description( $term );
			if ( ! trim( wp_strip_all_tags( (string) $description ) ) && $term instanceof WP_Term ) {
				/* translators: 1: category name, 2: site name */
				$description = sprintf( __( 'Shop %1$s at %2$s.', 'begentle' ), $term->name, get_bloginfo( 'name' ) );
			}
			$thumb = $term instanceof WP_Term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
			$image = $thumb ? (string) wp_get_attachment_image_url( $thumb, 'large' ) : '';
			$url   = get_term_link( $term );
			$url   = is_wp_error( $url ) ? '' : $url;
			$canonical = true;
		} elseif ( $woo && is_shop() ) {
			$description = (string) begentle_mod( 'shop_text' );
			$banner      = (int) begentle_mod( 'shop_banner' );
			$image       = $banner ? (string) wp_get_attachment_image_url( $banner, 'large' ) : '';
			$url         = wc_get_page_permalink( 'shop' );
			$canonical   = true;
		} elseif ( is_page() ) {
			$post        = get_queried_object();
			$description = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( (string) $post->post_content, 30, '' );
			$image       = (string) get_the_post_thumbnail_url( $post, 'large' );
			$url         = get_permalink( $post );
		} else {
			return;
		}

		// Paginated archives point at their own page, never at a filtered or sorted variant.
		if ( $canonical && $url ) {
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			if ( $paged > 1 ) {
				$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' );
			}
			if ( ! begentle_seo_is_filtered_shop() ) {
				printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
			}
		}

		$description = begentle_seo_clean( $description );
		$image       = $image ? $image : begentle_seo_default_image();

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		$tags = array(
			'og:type'      => $type,
			'og:title'     => $title,
			'og:url'       => $url,
			'og:site_name' => get_bloginfo( 'name' ),
			'og:locale'    => get_locale(),
		);

		if ( $description ) {
			$tags['og:description'] = $description;
		}
		if ( $image ) {
			$tags['og:image'] = $image;
		}

		foreach ( array_merge( $tags, $extra ) as $property => $content ) {
			if ( '' !== (string) $content ) {
				printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
			}
		}

		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	},
	1
);

/* ---------- Product schema additions (WooCommerce prints the base Product markup) ---------- */

add_filter(
	'woocommerce_structured_data_product',
	function ( $markup, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $markup;
		}

		// Brand: Google lists it as a recommended Product property.
		if ( empty( $markup['brand'] ) ) {
			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => get_bloginfo( 'name' ),
			);
		}

		// All product photos, not only the main one.
		$images = array();
		foreach ( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) as $image_id ) {
			$src = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
			if ( $src ) {
				$images[] = $src;
			}
		}
		if ( $images ) {
			$markup['image'] = array_values( array_unique( $images ) );
		}

		// Visible attributes (Size, Scent, ...) as additionalProperty.
		$props = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			$value = $attribute->is_taxonomy()
				? implode( ', ', wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) )
				: implode( ', ', $attribute->get_options() );
			if ( '' !== $value ) {
				$props[] = array(
					'@type' => 'PropertyValue',
					'name'  => wc_attribute_label( $attribute->get_name(), $product ),
					'value' => $value,
				);
			}
		}
		if ( $props ) {
			$markup['additionalProperty'] = $props;
		}

		// Return policy and shipping region on each offer (Google merchant listing fields).
		if ( ! empty( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
			$country = WC()->countries ? WC()->countries->get_base_country() : 'IN';
			foreach ( $markup['offers'] as $i => $offer ) {
				$markup['offers'][ $i ]['shippingDetails'] = array(
					'@type'               => 'OfferShippingDetails',
					'shippingDestination' => array(
						'@type'          => 'DefinedRegion',
						'addressCountry' => $country,
					),
				);
				$markup['offers'][ $i ]['hasMerchantReturnPolicy'] = array(
					'@type'                => 'MerchantReturnPolicy',
					'applicableCountry'    => $country,
					'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
					'merchantReturnDays'   => (int) apply_filters( 'begentle_return_days', 7 ),
					'returnMethod'         => 'https://schema.org/ReturnByMail',
				);
			}
		}

		return $markup;
	},
	10,
	2
);

/* ---------- Home page: Organization + WebSite ---------- */

add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() || ! function_exists( 'begentle_print_schema' ) ) {
			return;
		}

		$home = home_url( '/' );
		$org  = array(
			'@type' => 'Organization',
			'@id'   => $home . '#organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => $home,
		);

		$logo = begentle_site_logo_url();
		if ( $logo ) {
			$org['logo'] = $logo;
		}

		$email = (string) begentle_mod( 'contact_email' );
		if ( $email ) {
			$org['contactPoint'] = array(
				'@type'       => 'ContactPoint',
				'contactType' => 'customer support',
				'email'       => $email,
			);
		}

		$same_as   = array();
		$instagram = trim( (string) begentle_mod( 'contact_instagram' ) );
		if ( $instagram ) {
			$same_as[] = 'https://www.instagram.com/' . ltrim( $instagram, '@' ) . '/';
		}
		if ( $same_as ) {
			$org['sameAs'] = $same_as;
		}

		$site = array(
			'@type'     => 'WebSite',
			'@id'       => $home . '#website',
			'name'      => get_bloginfo( 'name' ),
			'url'       => $home,
			'publisher' => array( '@id' => $home . '#organization' ),
		);

		if ( begentle_has_woo() ) {
			$site['potentialAction'] = array(
				'@type'       => 'SearchAction',
				'target'      => add_query_arg( array( 's' => '{search_term_string}', 'post_type' => 'product' ), $home ),
				'query-input' => 'required name=search_term_string',
			);
		}

		begentle_print_schema( array( $org, $site ) );
	},
	2
);
