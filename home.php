<?php
/**
 * Blog index (the "Posts page" from Settings > Reading).
 */
defined( 'ABSPATH' ) || exit;

begentle_blog_partial();

get_header();
get_template_part( 'template-parts/blog/listing' );
get_footer();
