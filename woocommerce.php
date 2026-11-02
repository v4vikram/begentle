<?php
/**
 * Single wrapper for every WooCommerce catalogue page (shop, category, tag, product).
 * Cart / Checkout / My account are normal pages and use page.php.
 *
 * Catalogue pages are split into #catalog-top (banner + category pills) and #shop-results
 * (toolbar, products, pagination) so assets/js/shop-filter.js can swap them without a page load.
 * It asks for a small fragment with ?bg_partial=1; that URL is never cached or indexed.
 */
defined( 'ABSPATH' ) || exit;

$is_catalog = begentle_is_catalog();

// phpcs:disable WordPress.Security.NonceVerification.Recommended
if ( $is_catalog && isset( $_GET['bg_partial'] ) ) {
	// Hide the flag from every URL builder (pagination, sorting form, filter links).
	unset( $_GET['bg_partial'], $_REQUEST['bg_partial'] );
	$_SERVER['REQUEST_URI'] = remove_query_arg( 'bg_partial', wp_unslash( $_SERVER['REQUEST_URI'] ) );

	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

	printf( '<div data-title="%s" hidden></div>', esc_attr( wp_get_document_title() ) );
	echo '<div id="catalog-top">';
	get_template_part( 'template-parts/catalog-header' );
	echo '</div>';
	printf( '<div id="shop-results" data-found="%d">', (int) $GLOBALS['wp_query']->found_posts );
	woocommerce_content();
	echo '</div>';
	get_template_part( 'template-parts/filter-drawer' );
	exit;
}
// phpcs:enable

get_header();
?>
<div class="mx-auto max-w-7xl px-4 py-5 md:py-10">
	<?php if ( $is_catalog ) : ?>
		<div id="catalog-top">
			<?php get_template_part( 'template-parts/catalog-header' ); ?>
		</div>
		<div id="shop-results" data-found="<?php echo (int) $GLOBALS['wp_query']->found_posts; ?>">
			<?php woocommerce_content(); ?>
		</div>
		<p id="shop-status" class="sr-only" role="status" aria-live="polite"></p>
	<?php else : ?>
		<?php woocommerce_breadcrumb(); ?>
		<?php woocommerce_content(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
