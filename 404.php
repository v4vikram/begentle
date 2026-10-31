<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="mx-auto max-w-xl px-4 py-20 text-center">
	<p class="text-7xl font-extrabold tracking-tight">404</p>
	<h1 class="mt-4 text-xl font-bold"><?php esc_html_e( 'This page wandered off.', 'begentle' ); ?></h1>
	<a class="btn mt-8" href="<?php echo esc_url( begentle_has_woo() ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
		<?php esc_html_e( 'Back to shopping', 'begentle' ); ?>
	</a>
</div>
<?php
get_footer();
