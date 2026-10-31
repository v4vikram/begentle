<?php
/**
 * Single wrapper for every WooCommerce catalogue page (shop, category, tag, product).
 * Cart / Checkout / My account are normal pages and use page.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="mx-auto max-w-7xl px-4 py-5 md:py-10">
	<?php woocommerce_breadcrumb(); ?>
	<?php woocommerce_content(); ?>
</div>
<?php
get_footer();
