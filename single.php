<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="mx-auto max-w-3xl px-4 py-6 md:py-12">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<p class="mb-2 text-xs text-muted"><?php echo esc_html( get_the_date() ); ?></p>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="mb-6 overflow-hidden rounded-2xl"><?php the_post_thumbnail( 'large', array( 'class' => 'w-full' ) ); ?></div>
			<?php endif; ?>
			<div class="entry-content prose-lite"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
