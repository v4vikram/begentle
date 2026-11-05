<?php
/**
 * Begentle theme bootstrap. Logic lives in /inc, keep this file thin.
 */
defined( 'ABSPATH' ) || exit;

define( 'BEGENTLE_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'BEGENTLE_DIR', get_template_directory() );

foreach ( array( 'helpers', 'setup', 'enqueue', 'customizer', 'woo', 'filters', 'account', 'contact', 'search', 'blog', 'pincode', 'smtp' ) as $begentle_file ) {
	require_once BEGENTLE_DIR . "/inc/{$begentle_file}.php";
}
unset( $begentle_file );
