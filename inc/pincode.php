<?php
/**
 * India PIN code -> City + State auto-fill on checkout (block) and My Account address forms.
 *
 * Data: assets/pincodes/{first two digits}.json, built by tools/build-pincodes.mjs from
 * India Post's All India Pincode Directory (data.gov.in, GODL-India). Static files: no API key,
 * no third-party request, nothing runs on the server per lookup.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

// India: ask for the PIN before the address (as Amazon / Flipkart do), so City and State further
// down are already filled by the time the customer gets there. The label says it will happen.
// Block checkout reads the same priority (it becomes the field "index").
add_filter(
	'woocommerce_get_country_locale',
	function ( $locale ) {
		// Address line 1 defaults to 40 in the block checkout but 50 in classic forms, so pin both:
		// names 10-20, PIN 45, address 46, address_2 50/60, city 70, state 80.
		$locale['IN']['postcode']['priority']  = 45;
		$locale['IN']['address_1']['priority'] = 46;
		$locale['IN']['postcode']['label']     = __( 'PIN Code (auto-fills city & state)', 'begentle' );
		return $locale;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$on_checkout = is_checkout() && ! is_order_received_page();
		$on_address  = is_account_page() && is_wc_endpoint_url( 'edit-address' );

		if ( ! $on_checkout && ! $on_address ) {
			return;
		}

		// The block checkout keeps the address in the wc/store/cart data store.
		$deps = $on_checkout && wp_script_is( 'wc-blocks-data-store', 'registered' ) ? array( 'wp-data', 'wc-blocks-data-store' ) : array();

		wp_enqueue_script(
			'begentle-pincode',
			get_theme_file_uri( 'assets/js/pincode.js' ),
			$deps,
			begentle_asset_version( 'assets/js/pincode.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'begentle-pincode',
			'begentlePincode',
			array( 'base' => trailingslashit( get_theme_file_uri( 'assets/pincodes' ) ) )
		);
	},
	30
);
