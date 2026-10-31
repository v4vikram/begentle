<?php
defined( 'ABSPATH' ) || exit;

get_header();

// Cart / checkout / account need room for tables and two-column layouts.
$wide = begentle_has_woo() && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="mx-auto px-4 py-6 md:py-12 <?php echo $wide ? 'max-w-6xl' : 'max-w-3xl'; ?>">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="entry-content <?php echo $wide ? '' : 'prose-lite'; ?>">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
