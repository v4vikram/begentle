<?php
defined( 'ABSPATH' ) || exit;

$footer_cats = array_slice( begentle_top_categories( 6 ), 0, 6 );
?>
</main>

<footer class="mt-16 border-t border-line">
	<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-4">
		<div class="sm:col-span-2">
			<p class="text-lg font-extrabold tracking-tight"><?php bloginfo( 'name' ); ?></p>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="mt-2 max-w-xs text-sm text-muted"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
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
	</div>
	<div class="border-t border-line">
		<p class="mx-auto max-w-7xl px-4 py-4 text-xs text-muted">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
		</p>
	</div>
</footer>

<?php get_template_part( 'template-parts/drawers' ); ?>

<?php wp_footer(); ?>
</body>
</html>
