<?php
/**
 * Post archives (category, tag, author, date) share the blog listing.
 * WooCommerce archives never reach this file (woocommerce.php handles them).
 */
defined( 'ABSPATH' ) || exit;

begentle_blog_partial();

get_header();
get_template_part( 'template-parts/blog/listing' );
get_footer();
