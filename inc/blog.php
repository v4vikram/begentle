<?php
/**
 * Blog: listing filters (?q= search, ?sort=), table of contents, reading time, SEO / AEO / GEO output.
 *
 * Blog URL = the "Posts page" set in Settings > Reading (a page with the slug "blog").
 * SEO tags and JSON-LD are skipped automatically when an SEO plugin (Yoast, Rank Math, ...) is active.
 */
defined( 'ABSPATH' ) || exit;

/* ---------- Helpers ---------- */

/** Blog listing URL, or '' when no posts page is set. */
function begentle_blog_url(): string {
	$page_id = (int) get_option( 'page_for_posts' );

	return $page_id ? (string) get_permalink( $page_id ) : '';
}

/** Blog index or a post archive (category, tag, author, date). */
function begentle_is_blog_listing(): bool {
	return is_home() || is_category() || is_tag() || is_author() || is_date();
}

/** Search text typed into the blog search box (?q=). */
function begentle_blog_q(): string {
	return isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function begentle_blog_sorts(): array {
	return array(
		'newest' => __( 'Newest', 'begentle' ),
		'oldest' => __( 'Oldest', 'begentle' ),
		'az'     => __( 'A–Z', 'begentle' ),
	);
}

function begentle_blog_sort(): string {
	$sort = isset( $_GET['sort'] ) && is_string( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array_key_exists( $sort, begentle_blog_sorts() ) ? $sort : 'newest';
}

/** Minutes to read, at ~200 words a minute. Splits on whitespace so it also works for Hindi. */
function begentle_reading_time( $post = null ): int {
	$text  = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post ) ) );
	$words = count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );

	return max( 1, (int) ceil( $words / 200 ) );
}

/** First real category (not "Uncategorized"). */
function begentle_post_category( $post = null ): ?WP_Term {
	foreach ( get_the_category( $post ) as $category ) {
		if ( (int) get_option( 'default_category' ) !== $category->term_id ) {
			return $category;
		}
	}

	return null;
}

/** Categories that have posts, for the filter pills. */
function begentle_blog_categories(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
			'number'     => 12,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/** Meta description: manual excerpt, otherwise the first ~30 words. */
function begentle_post_description( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	// Space before every tag so "<p>end.</p><h2>Next" does not turn into "end.Next".
	$text = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( str_replace( '<', ' <', strip_shortcodes( $post->post_content ) ), 30, '…' );

	return trim( wp_strip_all_tags( $text ) );
}

/**
 * Adds ids to h2/h3 headings (keeps existing ids) and returns them as a table of contents.
 *
 * @return array{0:string,1:array<int,array{level:int,id:string,text:string}>}
 */
function begentle_toc_build( string $html ): array {
	$items = array();
	$used  = array();

	$html = preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( $m ) use ( &$items, &$used ) {
			$level = (int) $m[1];
			$attrs = $m[2];
			$text  = trim( wp_strip_all_tags( $m[3] ) );

			if ( '' === $text ) {
				return $m[0];
			}

			if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $found ) ) {
				$id = $found[2];
			} else {
				$base = sanitize_title( $text );
				$base = $base ? $base : 'section';
				$id   = $base;
				for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
					$id = $base . '-' . $n;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}

			$used[ $id ] = true;
			$items[]     = array(
				'level' => $level,
				'id'    => $id,
				'text'  => $text,
			);

			return "<h{$level}{$attrs}>{$m[3]}</h{$level}>";
		},
		$html
	);

	return array( (string) $html, $items );
}

/**
 * Questions and answers from a "FAQ" section: an h2 containing "FAQ" / "Frequently asked",
 * then each h3 is a question and the content up to the next h3 is its answer.
 *
 * @return array<int,array{q:string,a:string}>
 */
function begentle_faq_from_html( string $html ): array {
	if ( ! preg_match( '/<h2[^>]*>(?:(?!<\/h2>).)*(?:FAQ|Frequently asked)(?:(?!<\/h2>).)*<\/h2>(.*?)(?=<h2|$)/is', $html, $section ) ) {
		return array();
	}

	preg_match_all( '/<h3[^>]*>(.*?)<\/h3>(.*?)(?=<h3|$)/is', $section[1], $pairs, PREG_SET_ORDER );

	$faq = array();
	foreach ( $pairs as $pair ) {
		$question = trim( wp_strip_all_tags( $pair[1] ) );
		$answer   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $pair[2] ) ) );

		if ( $question && $answer ) {
			$faq[] = array(
				'q' => $question,
				'a' => $answer,
			);
		}
	}

	return $faq;
}

/**
 * AJAX listing (assets/js/blog.js asks for ?bg_partial=1): print only the listing and stop.
 * Same contract as the shop (woocommerce.php): the flag is hidden from every URL builder,
 * and the fragment is never cached or indexed. Call before get_header().
 */
function begentle_blog_partial(): void {
	if ( ! isset( $_GET['bg_partial'] ) || ! begentle_is_blog_listing() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	unset( $_GET['bg_partial'], $_REQUEST['bg_partial'] );
	$_SERVER['REQUEST_URI'] = remove_query_arg( 'bg_partial', wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

	get_template_part( 'template-parts/blog/listing' );
	exit;
}

/* ---------- Listing query ---------- */

add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) ) {
			return;
		}

		$search = begentle_blog_q();
		if ( '' !== $search ) {
			$query->set( 's', $search );
		}

		switch ( begentle_blog_sort() ) {
			case 'oldest':
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'ASC' );
				break;
			case 'az':
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
				break;
		}
	}
);

// "Style guides" instead of "Category: Style guides".
add_filter(
	'get_the_archive_title_prefix',
	fn( $prefix ) => begentle_is_blog_listing() ? '' : $prefix
);

/* ---------- SEO / AEO / GEO ---------- */

function begentle_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
}

// Filtered / sorted / searched listings are duplicates of the real archive: keep them out of the index.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( begentle_is_blog_listing() && ( '' !== begentle_blog_q() || isset( $_GET['sort'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$robots['noindex'] = true;
			// "Discourage search engines" already adds nofollow; never contradict it.
			if ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}
);

function begentle_site_logo_url(): string {
	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id ) {
		return (string) wp_get_attachment_image_url( $logo_id, 'full' );
	}

	return file_exists( BEGENTLE_DIR . '/assets/img/logo.svg' ) ? get_theme_file_uri( 'assets/img/logo.svg' ) : '';
}

/** Description, Open Graph and Twitter tags for posts and blog listings. */
add_action(
	'wp_head',
	function () {
		if ( begentle_seo_plugin_active() ) {
			return;
		}

		$tags = array();

		if ( is_singular( 'post' ) ) {
			$post        = get_queried_object();
			$description = begentle_post_description( $post );
			$image       = get_the_post_thumbnail_url( $post, 'large' );
			$tags        = array(
				'og:type'                => 'article',
				'og:title'               => get_the_title( $post ),
				'og:url'                 => get_permalink( $post ),
				'article:published_time' => get_the_date( 'c', $post ),
				'article:modified_time'  => get_the_modified_date( 'c', $post ),
			);
		} elseif ( begentle_is_blog_listing() ) {
			if ( is_home() ) {
				$blog_page   = get_post( (int) get_option( 'page_for_posts' ) );
				$description = $blog_page && has_excerpt( $blog_page ) ? $blog_page->post_excerpt : get_bloginfo( 'description' );
			} else {
				$description = wp_strip_all_tags( (string) get_the_archive_description() );
			}

			$image = '';
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			$url   = remove_query_arg( array( 'q', 'sort' ), get_pagenum_link( $paged, false ) );
			$tags  = array(
				'og:type'  => 'website',
				'og:title' => wp_get_document_title(),
				'og:url'   => $url,
			);

			// WordPress prints a canonical only for single posts/pages.
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		} else {
			return;
		}

		$description = trim( preg_replace( '/\s+/', ' ', (string) $description ) );

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
			$tags['og:description'] = $description;
		}

		$tags['og:site_name'] = get_bloginfo( 'name' );
		$tags['og:locale']    = get_locale();

		if ( $image ) {
			$tags['og:image'] = $image;
		}

		foreach ( $tags as $property => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}

		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	},
	1
);

/** Print a JSON-LD block (skipped when an SEO plugin handles schema). */
function begentle_print_schema( array $graph ): void {
	if ( begentle_seo_plugin_active() || ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
	) . "</script>\n";
}

/** BreadcrumbList node from [ [name, url], ... ]. */
function begentle_breadcrumb_schema( array $crumbs ): array {
	$items = array();

	foreach ( array_values( $crumbs ) as $index => list( $name, $url ) ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $name,
			'item'     => $url,
		);
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/** Crumbs for a single post: Home > Blog > Category > Post. */
function begentle_post_crumbs( WP_Post $post ): array {
	$crumbs = array( array( __( 'Home', 'begentle' ), home_url( '/' ) ) );

	if ( begentle_blog_url() ) {
		$crumbs[] = array( get_the_title( (int) get_option( 'page_for_posts' ) ), begentle_blog_url() );
	}

	$category = begentle_post_category( $post );
	if ( $category ) {
		$crumbs[] = array( $category->name, get_term_link( $category ) );
	}

	$crumbs[] = array( get_the_title( $post ), get_permalink( $post ) );

	return $crumbs;
}

/** BlogPosting (+ FAQPage when the post has a FAQ section) + breadcrumbs. */
function begentle_post_schema( WP_Post $post, array $faq, int $minutes ): void {
	$author   = get_userdata( (int) $post->post_author );
	$category = begentle_post_category( $post );
	$logo     = begentle_site_logo_url();

	$article = array(
		'@type'            => 'BlogPosting',
		'@id'              => get_permalink( $post ) . '#article',
		'headline'         => get_the_title( $post ),
		'description'      => begentle_post_description( $post ),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'mainEntityOfPage' => get_permalink( $post ),
		'inLanguage'       => get_bloginfo( 'language' ),
		'wordCount'        => count( preg_split( '/\s+/u', wp_strip_all_tags( $post->post_content ), -1, PREG_SPLIT_NO_EMPTY ) ),
		'timeRequired'     => 'PT' . $minutes . 'M',
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author ? $author->display_name : get_bloginfo( 'name' ),
			'url'   => $author ? get_author_posts_url( $author->ID ) : home_url( '/' ),
		),
		'publisher'        => array_filter(
			array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
				'logo'  => $logo ? array(
					'@type' => 'ImageObject',
					'url'   => $logo,
				) : null,
			)
		),
	);

	if ( has_post_thumbnail( $post ) ) {
		$article['image'] = get_the_post_thumbnail_url( $post, 'full' );
	}

	if ( $category ) {
		$article['articleSection'] = $category->name;
	}

	$graph = array( $article, begentle_breadcrumb_schema( begentle_post_crumbs( $post ) ) );

	if ( $faq ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				fn( $item ) => array(
					'@type'          => 'Question',
					'name'           => $item['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $item['a'],
					),
				),
				$faq
			),
		);
	}

	begentle_print_schema( $graph );
}

/* ---------- Assets ---------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_singular( 'post' ) || begentle_is_blog_listing() ) {
			wp_enqueue_script(
				'begentle-blog',
				get_theme_file_uri( 'assets/js/blog.js' ),
				array(),
				begentle_asset_version( 'assets/js/blog.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	},
	30
);
