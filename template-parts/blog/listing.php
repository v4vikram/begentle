<?php
/**
 * Blog listing: title, search, category pills, sort, featured post, grid, pagination.
 * Filters are plain GET params (?q=, ?sort=) handled in inc/blog.php; categories are real archive URLs.
 */
defined( 'ABSPATH' ) || exit;

global $wp_query;

$blog_url = begentle_blog_url();
$search   = begentle_blog_q();
$sort     = begentle_blog_sort();
$paged    = max( 1, (int) get_query_var( 'paged' ) );
$filtered = '' !== $search || 'newest' !== $sort;
$here     = remove_query_arg( array( 'q', 'sort', 'paged' ), get_pagenum_link( 1, false ) );

if ( is_home() ) {
	$blog_page   = get_post( (int) get_option( 'page_for_posts' ) );
	$title       = $blog_page ? get_the_title( $blog_page ) : __( 'Blog', 'begentle' );
	$description = $blog_page && has_excerpt( $blog_page ) ? $blog_page->post_excerpt : '';
} else {
	$title       = get_the_archive_title();
	$description = get_the_archive_description();
}

$active_cat = is_category() ? (int) get_queried_object_id() : 0;
$categories = begentle_blog_categories();
$crumbs     = array( array( __( 'Home', 'begentle' ), home_url( '/' ) ) );

if ( $blog_url ) {
	$crumbs[] = array( get_the_title( (int) get_option( 'page_for_posts' ) ), $blog_url );
}
if ( ! is_home() ) {
	$crumbs[] = array( wp_strip_all_tags( $title ), $here );
}
?>
<div class="mx-auto max-w-6xl px-4 py-6 md:py-10" id="blog-listing">

	<?php /* #blog-head and #blog-results are swapped by assets/js/blog.js; the search/sort controls stay put so typing keeps focus. */ ?>
	<div id="blog-head">
	<nav class="crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'begentle' ); ?>">
		<ol>
			<?php foreach ( $crumbs as $index => list( $name, $url ) ) : ?>
				<li>
					<?php if ( count( $crumbs ) - 1 === $index ) : ?>
						<span aria-current="page"><?php echo esc_html( $name ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<header class="mb-6 md:mb-8">
		<h1 class="text-4xl font-extrabold leading-none tracking-tight md:text-6xl"><?php echo wp_kses_post( $title ); ?></h1>
		<?php if ( $description ) : ?>
			<div class="mt-3 max-w-2xl text-muted md:text-lg"><?php echo wp_kses_post( $description ); ?></div>
		<?php endif; ?>
	</header>
	</div>

	<div class="blog-controls">
		<form role="search" method="get" action="<?php echo esc_url( $here ); ?>" class="blog-search">
			<label class="screen-reader-text" for="blog-q"><?php esc_html_e( 'Search articles', 'begentle' ); ?></label>
			<?php echo begentle_icon( 'search', 'size-5 shrink-0 text-muted' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input id="blog-q" type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search articles…', 'begentle' ); ?>">
			<?php if ( 'newest' !== $sort ) : ?>
				<input type="hidden" name="sort" value="<?php echo esc_attr( $sort ); ?>">
			<?php endif; ?>
		</form>

		<form method="get" action="<?php echo esc_url( $here ); ?>" class="blog-sort">
			<?php if ( '' !== $search ) : ?>
				<input type="hidden" name="q" value="<?php echo esc_attr( $search ); ?>">
			<?php endif; ?>
			<label class="screen-reader-text" for="blog-sort"><?php esc_html_e( 'Sort articles', 'begentle' ); ?></label>
			<select id="blog-sort" name="sort" data-autosubmit>
				<?php foreach ( begentle_blog_sorts() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit" class="btn !h-10 px-4"><?php esc_html_e( 'Sort', 'begentle' ); ?></button></noscript>
		</form>
	</div>

	<p id="blog-status" class="screen-reader-text" aria-live="polite"></p>

	<div id="blog-results" data-found="<?php echo (int) $wp_query->found_posts; ?>" data-title="<?php echo esc_attr( wp_get_document_title() ); ?>">
	<?php if ( $categories && $blog_url ) : ?>
		<nav class="cat-pills" aria-label="<?php esc_attr_e( 'Topics', 'begentle' ); ?>">
			<a href="<?php echo esc_url( $blog_url ); ?>" <?php echo is_home() ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'All', 'begentle' ); ?></a>
			<?php foreach ( $categories as $category ) : ?>
				<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" <?php echo $category->term_id === $active_cat ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $category->name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $filtered ) : ?>
		<p class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
			<span>
				<?php
				$count = (int) $wp_query->found_posts;
				if ( '' !== $search ) {
					/* translators: 1: number of articles, 2: search text */
					printf( esc_html( _n( '%1$s article for “%2$s”', '%1$s articles for “%2$s”', $count, 'begentle' ) ), esc_html( number_format_i18n( $count ) ), esc_html( $search ) );
				} else {
					/* translators: %s: number of articles */
					printf( esc_html( _n( '%s article', '%s articles', $count, 'begentle' ) ), esc_html( number_format_i18n( $count ) ) );
				}
				?>
			</span>
			<a class="font-semibold text-ink underline underline-offset-4" href="<?php echo esc_url( $here ); ?>" data-blog-link data-blog-clear><?php esc_html_e( 'Clear', 'begentle' ); ?></a>
		</p>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="blog-grid">
			<?php
			$first = true;
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/blog/card', null, array( 'featured' => $first && 1 === $paged && ! $filtered ) );
				$first = false;
			endwhile;
			?>
		</div>

		<div class="mt-10">
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Previous', 'begentle' ),
					'next_text' => __( 'Next', 'begentle' ),
				)
			);
			?>
		</div>
	<?php else : ?>
		<div class="rounded-3xl bg-mist p-10 text-center">
			<p class="text-lg font-bold">
				<?php echo '' !== $search ? esc_html__( 'No articles match that search.', 'begentle' ) : esc_html__( 'No articles here yet.', 'begentle' ); ?>
			</p>
			<?php if ( $filtered || ! is_home() ) : ?>
				<a class="btn mt-5" href="<?php echo esc_url( $blog_url ? $blog_url : home_url( '/' ) ); ?>" data-blog-link data-blog-clear><?php esc_html_e( 'See all articles', 'begentle' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	</div>
</div>
<?php
if ( 1 === $paged && ! $filtered && have_posts() ) {
	rewind_posts();
	$items = array();
	while ( have_posts() ) {
		the_post();
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'url'      => get_permalink(),
			'name'     => get_the_title(),
		);
	}
	wp_reset_postdata();

	begentle_print_schema(
		array(
			array(
				'@type'      => 'CollectionPage',
				'name'       => wp_strip_all_tags( $title ),
				'url'        => $here,
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'itemListElement' => $items,
				),
			),
			begentle_breadcrumb_schema( $crumbs ),
		)
	);
}
