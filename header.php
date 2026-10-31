<?php
defined( 'ABSPATH' ) || exit;

$has_woo      = begentle_has_woo();
$announcement = trim( (string) begentle_mod( 'announcement' ) );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#ffffff">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-white" href="#content">
	<?php esc_html_e( 'Skip to content', 'begentle' ); ?>
</a>

<?php if ( $announcement ) : ?>
	<div class="bg-ink px-4 py-2 text-center text-xs font-medium text-white"><?php echo esc_html( $announcement ); ?></div>
<?php endif; ?>

<header id="site-header" class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur">
	<div class="mx-auto grid h-14 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-2 px-2 md:h-16 md:px-4">

		<div class="flex items-center">
			<button type="button" class="icon-btn md:hidden" data-drawer-open="menu" aria-controls="drawer-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'begentle' ); ?>">
				<?php echo begentle_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<nav class="hidden md:block" aria-label="<?php esc_attr_e( 'Primary', 'begentle' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'desktop-nav',
						'depth'          => 2,
						'fallback_cb'    => 'begentle_menu_fallback',
					)
				);
				?>
			</nav>
		</div>

		<div class="site-logo">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="text-xl font-extrabold tracking-tight" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="flex items-center justify-end">
			<button type="button" class="icon-btn" data-drawer-open="search" aria-controls="drawer-search" aria-expanded="false" aria-label="<?php esc_attr_e( 'Search', 'begentle' ); ?>">
				<?php echo begentle_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<?php if ( $has_woo ) : ?>
				<a class="icon-btn hidden sm:inline-grid" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'begentle' ); ?>">
					<?php echo begentle_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="icon-btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-drawer-open="cart" aria-controls="drawer-cart" aria-label="<?php esc_attr_e( 'Cart', 'begentle' ); ?>">
					<?php echo begentle_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo begentle_cart_count_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php endif; ?>
		</div>

	</div>
</header>

<main id="content">
