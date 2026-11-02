<?php
defined( 'ABSPATH' ) || exit;

/**
 * Cache-bust with file mtime so a rebuilt CSS/JS is picked up immediately.
 */
function begentle_asset_version( string $relative ): string {
	$path = BEGENTLE_DIR . '/' . $relative;

	return file_exists( $path ) ? (string) filemtime( $path ) : BEGENTLE_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'begentle',
			get_theme_file_uri( 'assets/css/main.css' ),
			array(),
			begentle_asset_version( 'assets/css/main.css' )
		);

		wp_enqueue_script(
			'begentle',
			get_theme_file_uri( 'assets/js/main.js' ),
			array( 'jquery' ),
			begentle_asset_version( 'assets/js/main.js' ),
			true
		);

		// Mini-cart drawer + header count depend on these. Registered by WooCommerce.
		if ( begentle_has_woo() ) {
			wp_enqueue_script( 'wc-cart-fragments' );
			wp_enqueue_script( 'wc-add-to-cart' );

			// Card quick add + share (assets/js/main.js).
			wp_localize_script(
				'begentle',
				'begentleCart',
				array(
					'addUrl' => WC_AJAX::get_endpoint( 'add_to_cart' ),
					'qtyUrl' => WC_AJAX::get_endpoint( 'begentle_update_qty' ),
					'i18n'   => array(
						'added'  => __( 'Added to bag', 'begentle' ),
						'view'   => __( 'View bag', 'begentle' ),
						'error'  => __( 'Could not add this item. Please try again.', 'begentle' ),
						'copied' => __( 'Link copied', 'begentle' ),
					),
				)
			);
		}
	},
	20
);

// Preload the only webfont (self-hosted) to avoid a late font swap.
add_action(
	'wp_head',
	function () {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2' ) )
		);
	},
	1
);
