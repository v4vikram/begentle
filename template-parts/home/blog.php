<?php
/**
 * Latest blog posts. Swipeable on phones, three columns on desktop. Hidden until there are posts.
 */
defined( 'ABSPATH' ) || exit;

$blog_url = begentle_blog_url();

if ( ! $blog_url ) {
	return;
}

$latest = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $latest->have_posts() ) {
	return;
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-blog">
	<?php begentle_section_head( 'home-blog', __( 'From the blog', 'begentle' ), $blog_url, __( 'All articles', 'begentle' ) ); ?>
	<div class="-mx-4 flex snap-x scroll-px-4 gap-4 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:gap-6 md:overflow-visible md:px-0">
		<?php
		while ( $latest->have_posts() ) :
			$latest->the_post();
			?>
			<div class="flex w-[82%] shrink-0 snap-start sm:w-[60%] md:w-auto">
				<?php get_template_part( 'template-parts/blog/card', null, array( 'heading' => 'h3' ) ); ?>
			</div>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</section>
