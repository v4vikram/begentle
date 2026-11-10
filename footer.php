<?php
defined( 'ABSPATH' ) || exit;

$footer_cats = array_slice( begentle_top_categories( 6 ), 0, 6 );
$blog_url    = begentle_blog_url();
$blog_topics = $blog_url ? array_slice( begentle_blog_categories(), 0, 4 ) : array();
?>
</main>

<footer class="mt-16 border-t border-line">
	<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-5">
		<div class="sm:col-span-2">
			<p class="text-lg font-extrabold tracking-tight"><?php bloginfo( 'name' ); ?></p>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="mt-2 max-w-xs text-sm text-muted"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
			<?php if ( function_exists( 'begentle_footer_contact' ) ) { begentle_footer_contact(); } ?>
		</div>

		<?php if ( $footer_cats ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Shop', 'begentle' ); ?>">
				<p class="footer-title"><?php esc_html_e( 'Shop', 'begentle' ); ?></p>
				<ul class="footer-nav">
					<?php foreach ( $footer_cats as $footer_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $footer_cat ) ); ?>"><?php echo esc_html( $footer_cat->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<nav aria-label="<?php esc_attr_e( 'Footer', 'begentle' ); ?>">
			<p class="footer-title"><?php esc_html_e( 'Help', 'begentle' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'footer-nav',
					'depth'          => 1,
					'fallback_cb'    => 'begentle_menu_fallback',
				)
			);
			?>
		</nav>

		<?php if ( $blog_url ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Blog', 'begentle' ); ?>">
				<p class="footer-title"><?php echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ); ?></p>
				<ul class="footer-nav">
					<li><a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'All articles', 'begentle' ); ?></a></li>
					<?php foreach ( $blog_topics as $blog_topic ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $blog_topic ) ); ?>"><?php echo esc_html( $blog_topic->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>
	<div class="border-t border-line">
		<div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4">
			<p class="text-xs text-muted">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
			<?php if ( function_exists( 'begentle_footer_payments' ) ) { begentle_footer_payments(); } ?>
		</div>
	</div>
</footer>

<?php get_template_part( 'template-parts/drawers' ); ?>

<?php wp_footer(); ?>
</body>
</html>
