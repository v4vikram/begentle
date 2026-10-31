<?php
/**
 * Fallback for blog index, archives and search.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="mx-auto max-w-3xl px-4 py-6 md:py-12">
	<?php if ( is_search() ) : ?>
		<h1 class="page-title">
			<?php
			/* translators: %s: search query */
			printf( esc_html__( 'Results for “%s”', 'begentle' ), esc_html( get_search_query() ) );
			?>
		</h1>
	<?php elseif ( is_archive() ) : ?>
		<h1 class="page-title"><?php the_archive_title(); ?></h1>
	<?php elseif ( is_home() && ! is_front_page() ) : ?>
		<h1 class="page-title"><?php single_post_title(); ?></h1>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'border-b border-line py-6' ); ?>>
				<h2 class="text-xl font-bold tracking-tight">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
				<div class="mt-2 text-sm text-muted"><?php the_excerpt(); ?></div>
			</article>
		<?php endwhile; ?>

		<div class="mt-8"><?php the_posts_pagination(); ?></div>
	<?php else : ?>
		<p class="text-muted"><?php esc_html_e( 'Nothing found.', 'begentle' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
