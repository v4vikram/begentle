<?php
/**
 * Home / landing page. Each section lives in template-parts/home/ and hides itself when it has no content.
 */
defined( 'ABSPATH' ) || exit;

get_header();

foreach ( array( 'hero', 'marquee', 'categories', 'products', 'values', 'spotlight', 'shop-by', 'promo', 'reviews' ) as $begentle_section ) {
	get_template_part( 'template-parts/home/' . $begentle_section );
}

get_footer();
