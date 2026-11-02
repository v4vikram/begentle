<?php
/**
 * Single blog post: breadcrumbs, summary box, table of contents (sidebar on desktop,
 * collapsible + floating button on phones), author box, related posts, JSON-LD.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_obj = get_post();
	list( $content, $toc ) = begentle_toc_build( (string) apply_filters( 'the_content', get_the_content() ) );
	$minutes  = begentle_reading_time();
	$category = begentle_post_category();
	$crumbs   = begentle_post_crumbs( $post_obj );
	$has_toc  = count( $toc ) >= 2;
	$updated  = get_the_modified_time( 'U' ) - get_the_time( 'U' ) > DAY_IN_SECONDS;
	$author   = get_the_author_meta( 'ID' );
	?>
	<div class="read-progress" aria-hidden="true"><span></span></div>

	<div class="mx-auto max-w-6xl px-4 py-6 md:py-10">

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

		<article <?php post_class( 'post-article' ); ?>>
			<header class="post-header">
				<?php if ( $category ) : ?>
					<a class="blog-card__cat" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endif; ?>
				<h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight md:text-5xl"><?php the_title(); ?></h1>
				<div class="post-meta">
					<?php echo get_avatar( $author, 36, '', '', array( 'class' => 'rounded-full' ) ); ?>
					<span>
						<?php esc_html_e( 'By', 'begentle' ); ?>
						<a rel="author" href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>"><?php the_author(); ?></a>
					</span>
					<span aria-hidden="true">&middot;</span>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php if ( $updated ) : ?>
						<span aria-hidden="true">&middot;</span>
						<span>
							<?php esc_html_e( 'Updated', 'begentle' ); ?>
							<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="post-cover">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="post-layout <?php echo $has_toc ? 'has-toc' : ''; ?>">
				<?php if ( $has_toc ) : ?>
					<aside class="toc-side" aria-label="<?php esc_attr_e( 'Table of contents', 'begentle' ); ?>">
						<div class="toc-side__inner">
							<p class="toc-title"><?php esc_html_e( 'On this page', 'begentle' ); ?></p>
							<nav class="toc"><?php get_template_part( 'template-parts/blog/toc', null, array( 'items' => $toc ) ); ?></nav>
						</div>
					</aside>
				<?php endif; ?>

				<div class="post-main">
					<?php if ( has_excerpt() ) : ?>
						<div class="post-summary">
							<p class="post-summary__label"><?php esc_html_e( 'In short', 'begentle' ); ?></p>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( $has_toc ) : ?>
						<details class="toc-inline">
							<summary>
								<span>
									<?php
									/* translators: %d: number of sections */
									echo esc_html( sprintf( __( 'On this page (%d)', 'begentle' ), count( $toc ) ) );
									?>
								</span>
							</summary>
							<nav class="toc" aria-label="<?php esc_attr_e( 'Table of contents', 'begentle' ); ?>"><?php get_template_part( 'template-parts/blog/toc', null, array( 'items' => $toc ) ); ?></nav>
						</details>
					<?php endif; ?>

					<div class="entry-content prose-blog" data-post-body>
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
					</div>

					<?php
					$tags = get_the_tags();
					if ( $tags ) :
						?>
						<ul class="post-tags" aria-label="<?php esc_attr_e( 'Tags', 'begentle' ); ?>">
							<?php foreach ( $tags as $post_tag ) : ?>
								<li><a href="<?php echo esc_url( get_term_link( $post_tag ) ); ?>">#<?php echo esc_html( $post_tag->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<section class="author-box" aria-label="<?php esc_attr_e( 'About the author', 'begentle' ); ?>">
						<?php echo get_avatar( $author, 64, '', '', array( 'class' => 'rounded-full' ) ); ?>
						<div>
							<p class="text-xs font-bold uppercase tracking-wider text-muted"><?php esc_html_e( 'Written by', 'begentle' ); ?></p>
							<p class="mt-1 font-bold"><a rel="author" href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>"><?php the_author(); ?></a></p>
							<?php if ( get_the_author_meta( 'description' ) ) : ?>
								<p class="mt-1 text-sm text-muted"><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
							<?php endif; ?>
						</div>
					</section>
				</div>
			</div>
		</article>

		<?php
		$related = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => 3,
				'post__not_in'        => array( get_the_ID() ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'cat'                 => $category ? $category->term_id : 0,
			)
		);

		if ( $related->have_posts() ) :
			?>
			<section class="mt-16 border-t border-line pt-10" aria-labelledby="related-posts">
				<?php begentle_section_head( 'related-posts', __( 'Keep reading', 'begentle' ), begentle_blog_url(), __( 'All articles', 'begentle' ) ); ?>
				<div class="blog-grid">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						get_template_part( 'template-parts/blog/card', null, array( 'heading' => 'h3' ) );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php if ( $has_toc ) : ?>
		<button type="button" class="toc-fab" data-drawer-open="toc" aria-controls="drawer-toc" aria-expanded="false">
			<?php echo begentle_icon( 'menu', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Contents', 'begentle' ); ?>
		</button>
		<aside id="drawer-toc" class="drawer" data-drawer="toc" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Table of contents', 'begentle' ); ?>">
			<div class="flex h-14 shrink-0 items-center justify-between border-b border-line pl-4 pr-2">
				<span class="text-lg font-extrabold tracking-tight"><?php esc_html_e( 'On this page', 'begentle' ); ?></span>
				<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'Close contents', 'begentle' ); ?>">
					<?php echo begentle_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<nav class="toc min-h-0 flex-1 overflow-y-auto px-4 py-3 pb-[max(1rem,env(safe-area-inset-bottom))]"><?php get_template_part( 'template-parts/blog/toc', null, array( 'items' => $toc ) ); ?></nav>
		</aside>
	<?php endif; ?>

	<?php
	begentle_post_schema( $post_obj, begentle_faq_from_html( $content ), $minutes );
endwhile;

get_footer();
