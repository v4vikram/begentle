<?php
/**
 * Off-canvas panels: menu (mobile), search, cart. Toggled by assets/js/main.js via data-drawer attributes.
 */
defined( 'ABSPATH' ) || exit;
?>
<div id="overlay" class="overlay" data-drawer-close></div>

<aside id="drawer-menu" class="drawer" data-drawer="menu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'begentle' ); ?>">
	<div class="flex h-14 shrink-0 items-center justify-between border-b border-line pl-4 pr-2">
		<span class="text-lg font-extrabold tracking-tight"><?php bloginfo( 'name' ); ?></span>
		<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'Close menu', 'begentle' ); ?>">
			<?php echo begentle_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="<?php esc_attr_e( 'Mobile', 'begentle' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'drawer-nav',
				'depth'          => 2,
				'fallback_cb'    => 'begentle_menu_fallback',
			)
		);
		?>
	</nav>
	<?php if ( begentle_has_woo() ) : ?>
		<div class="shrink-0 border-t border-line p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
			<a class="flex items-center gap-3 text-sm font-semibold" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<?php echo begentle_icon( 'user', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'My account', 'begentle' ); ?>
			</a>
		</div>
	<?php endif; ?>
</aside>

<aside id="drawer-search" class="drawer" data-drawer="search" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'begentle' ); ?>">
	<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 p-3">
		<?php if ( begentle_has_woo() ) : ?>
			<input type="hidden" name="post_type" value="product">
		<?php endif; ?>
		<input type="search" name="s" data-autofocus placeholder="<?php esc_attr_e( 'Search jeans…', 'begentle' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'begentle' ); ?>" autocomplete="off" class="!rounded-full">
		<button type="button" class="icon-btn shrink-0" data-drawer-close aria-label="<?php esc_attr_e( 'Close search', 'begentle' ); ?>">
			<?php echo begentle_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</form>
</aside>

<?php if ( begentle_has_woo() && begentle_is_catalog() ) : ?>
	<?php get_template_part( 'template-parts/filter-drawer' ); ?>
<?php endif; ?>

<?php if ( begentle_has_woo() ) : ?>
	<aside id="drawer-cart" class="drawer" data-drawer="cart" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Your bag', 'begentle' ); ?>">
		<div class="flex h-14 shrink-0 items-center justify-between border-b border-line pl-4 pr-2">
			<span class="text-lg font-extrabold tracking-tight"><?php esc_html_e( 'Your bag', 'begentle' ); ?></span>
			<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'Close bag', 'begentle' ); ?>">
				<?php echo begentle_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
		<?php /* Woo replaces this element through cart fragments, so style it by selector in CSS, not by utility classes. */ ?>
		<div class="widget_shopping_cart_content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</aside>
<?php endif; ?>
