<?php
/**
 * Perfume product details:
 * - "From ₹599" instead of a price range, sizes priced in the size picker, and the headline
 *   price following the chosen size on the product page.
 * - A "Fragrance" tab in the product editor (notes, longevity, projection, season, occasion)
 *   shown as a scent profile card on the product page and added to the Product schema.
 * Every part hides itself when its fields are empty.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

/* ---------- Field definitions ---------- */

function begentle_fragrance_choices(): array {
	return array(
		'longevity'  => array(
			1 => __( '2 to 4 hours', 'begentle' ),
			2 => __( '4 to 6 hours', 'begentle' ),
			3 => __( '6 to 8 hours', 'begentle' ),
			4 => __( '8+ hours', 'begentle' ),
		),
		'projection' => array(
			1 => __( 'Soft, close to skin', 'begentle' ),
			2 => __( 'Moderate, arm\'s length', 'begentle' ),
			3 => __( 'Strong, fills the room', 'begentle' ),
		),
		'season'     => array(
			'summer'  => __( 'Summer', 'begentle' ),
			'monsoon' => __( 'Monsoon', 'begentle' ),
			'winter'  => __( 'Winter', 'begentle' ),
			'spring'  => __( 'Spring', 'begentle' ),
		),
		'occasion'   => array(
			'day'    => __( 'Day', 'begentle' ),
			'night'  => __( 'Night', 'begentle' ),
			'office' => __( 'College / office', 'begentle' ),
			'date'   => __( 'Date', 'begentle' ),
			'party'  => __( 'Party', 'begentle' ),
			'travel' => __( 'Travel', 'begentle' ),
		),
	);
}

/** All fragrance values for a product, cleaned. */
function begentle_fragrance( WC_Product $product ): array {
	$choices = begentle_fragrance_choices();
	$id      = $product->get_id();
	$list    = function ( $key ) use ( $id, $choices ) {
		$saved = (array) get_post_meta( $id, '_bg_' . $key, true );
		return array_values( array_intersect( array_keys( $choices[ $key ] ), $saved ) );
	};
	$level   = function ( $key ) use ( $id, $choices ) {
		$value = (int) get_post_meta( $id, '_bg_' . $key, true );
		return isset( $choices[ $key ][ $value ] ) ? $value : 0;
	};

	return array(
		'top'        => trim( (string) get_post_meta( $id, '_bg_notes_top', true ) ),
		'heart'      => trim( (string) get_post_meta( $id, '_bg_notes_heart', true ) ),
		'base'       => trim( (string) get_post_meta( $id, '_bg_notes_base', true ) ),
		'longevity'  => $level( 'longevity' ),
		'projection' => $level( 'projection' ),
		'season'     => $list( 'season' ),
		'occasion'   => $list( 'occasion' ),
	);
}

/* ---------- Product editor: "Fragrance" tab ---------- */

add_filter(
	'woocommerce_product_data_tabs',
	function ( $tabs ) {
		$tabs['begentle_fragrance'] = array(
			'label'    => __( 'Fragrance', 'begentle' ),
			'target'   => 'begentle_fragrance_data',
			'priority' => 65,
		);
		return $tabs;
	}
);

add_action(
	'woocommerce_product_data_panels',
	function () {
		global $post;

		$choices = begentle_fragrance_choices();
		$id      = (int) $post->ID;

		echo '<div id="begentle_fragrance_data" class="panel woocommerce_options_panel hidden">';

		echo '<div class="options_group">';
		echo '<p class="form-field"><em>' . esc_html__( 'Shown as the scent profile on the product page. Leave anything empty to hide it.', 'begentle' ) . '</em></p>';
		foreach (
			array(
				'notes_top'   => __( 'Top notes', 'begentle' ),
				'notes_heart' => __( 'Heart notes', 'begentle' ),
				'notes_base'  => __( 'Base notes', 'begentle' ),
			) as $key => $label
		) {
			woocommerce_wp_text_input(
				array(
					'id'          => '_bg_' . $key,
					'label'       => $label,
					'placeholder' => __( 'e.g. Fig leaf, bergamot', 'begentle' ),
					'desc_tip'    => true,
					'description' => __( 'Separate notes with commas.', 'begentle' ),
				)
			);
		}
		echo '</div>';

		echo '<div class="options_group">';
		foreach ( array( 'longevity' => __( 'Longevity', 'begentle' ), 'projection' => __( 'Projection', 'begentle' ) ) as $key => $label ) {
			woocommerce_wp_select(
				array(
					'id'      => '_bg_' . $key,
					'label'   => $label,
					'options' => array( '' => __( 'Not set', 'begentle' ) ) + $choices[ $key ],
				)
			);
		}
		echo '</div>';

		foreach ( array( 'season' => __( 'Best season', 'begentle' ), 'occasion' => __( 'Wear it for', 'begentle' ) ) as $key => $label ) {
			$saved = (array) get_post_meta( $id, '_bg_' . $key, true );
			echo '<div class="options_group"><fieldset class="form-field"><legend style="float:left;width:150px;margin:0 0 0 -150px;padding:0">' . esc_html( $label ) . '</legend>';
			foreach ( $choices[ $key ] as $value => $text ) {
				printf(
					'<label style="display:inline-block;width:auto;float:none;margin:0 1em 0.3em 0"><input type="checkbox" name="_bg_%1$s[]" value="%2$s" %3$s style="margin-right:4px"> %4$s</label>',
					esc_attr( $key ),
					esc_attr( $value ),
					checked( in_array( $value, $saved, true ), true, false ),
					esc_html( $text )
				);
			}
			echo '</fieldset></div>';
		}

		echo '</div>';
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	function ( WC_Product $product ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product save nonce.
		$choices = begentle_fragrance_choices();

		foreach ( array( 'notes_top', 'notes_heart', 'notes_base' ) as $key ) {
			$value = isset( $_POST[ '_bg_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ '_bg_' . $key ] ) ) : '';
			$product->update_meta_data( '_bg_' . $key, $value );
		}

		foreach ( array( 'longevity', 'projection' ) as $key ) {
			$value = isset( $_POST[ '_bg_' . $key ] ) ? absint( $_POST[ '_bg_' . $key ] ) : 0;
			$product->update_meta_data( '_bg_' . $key, isset( $choices[ $key ][ $value ] ) ? $value : '' );
		}

		foreach ( array( 'season', 'occasion' ) as $key ) {
			$value = isset( $_POST[ '_bg_' . $key ] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST[ '_bg_' . $key ] ) ) : array();
			$product->update_meta_data( '_bg_' . $key, array_values( array_intersect( array_keys( $choices[ $key ] ), $value ) ) );
		}
		// phpcs:enable
	}
);

/* ---------- Product page: scent profile card ---------- */

function begentle_fragrance_meter( string $label, int $level, int $max, string $text ): string {
	$dots = '';
	for ( $i = 1; $i <= $max; $i++ ) {
		$dots .= '<span class="scent-meter__dot' . ( $i <= $level ? ' is-on' : '' ) . '"></span>';
	}

	return sprintf(
		'<div class="scent-meter"><dt>%1$s</dt><dd><span class="scent-meter__dots" aria-hidden="true">%2$s</span><span class="scent-meter__text">%3$s</span></dd></div>',
		esc_html( $label ),
		$dots,
		esc_html( $text )
	);
}

add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$f       = begentle_fragrance( $product );
		$choices = begentle_fragrance_choices();
		$notes   = array_filter(
			array(
				__( 'Top', 'begentle' )   => $f['top'],
				__( 'Heart', 'begentle' ) => $f['heart'],
				__( 'Base', 'begentle' )  => $f['base'],
			)
		);

		if ( ! $notes && ! $f['longevity'] && ! $f['projection'] && ! $f['season'] && ! $f['occasion'] ) {
			return;
		}

		echo '<section class="scent-profile" aria-labelledby="scent-profile-title">';
		echo '<h2 id="scent-profile-title" class="scent-profile__title">' . esc_html__( 'Scent profile', 'begentle' ) . '</h2>';

		if ( $notes ) {
			echo '<dl class="scent-notes">';
			foreach ( $notes as $label => $value ) {
				printf( '<div class="scent-notes__row"><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $label ), esc_html( $value ) );
			}
			echo '</dl>';
		}

		if ( $f['longevity'] || $f['projection'] ) {
			echo '<dl class="scent-meters">';
			if ( $f['longevity'] ) {
				echo begentle_fragrance_meter( __( 'Longevity', 'begentle' ), $f['longevity'], 4, $choices['longevity'][ $f['longevity'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( $f['projection'] ) {
				echo begentle_fragrance_meter( __( 'Projection', 'begentle' ), $f['projection'], 3, $choices['projection'][ $f['projection'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</dl>';
		}

		foreach ( array( 'season' => __( 'Best in', 'begentle' ), 'occasion' => __( 'Wear it for', 'begentle' ) ) as $key => $label ) {
			if ( ! $f[ $key ] ) {
				continue;
			}
			echo '<div class="scent-tags"><p class="scent-tags__label">' . esc_html( $label ) . '</p><ul>';
			foreach ( $f[ $key ] as $value ) {
				echo '<li>' . esc_html( $choices[ $key ][ $value ] ) . '</li>';
			}
			echo '</ul></div>';
		}

		echo '</section>';
	},
	25 // After the short description (20), before the add to cart form (30).
);

// Same details in the schema, so search and AI answers can quote them.
add_filter(
	'woocommerce_structured_data_product',
	function ( $markup, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $markup;
		}

		$f       = begentle_fragrance( $product );
		$choices = begentle_fragrance_choices();
		$props   = array(
			__( 'Top notes', 'begentle' )   => $f['top'],
			__( 'Heart notes', 'begentle' ) => $f['heart'],
			__( 'Base notes', 'begentle' )  => $f['base'],
			__( 'Longevity', 'begentle' )   => $f['longevity'] ? $choices['longevity'][ $f['longevity'] ] : '',
			__( 'Projection', 'begentle' )  => $f['projection'] ? $choices['projection'][ $f['projection'] ] : '',
			__( 'Best season', 'begentle' ) => implode( ', ', array_map( fn( $v ) => $choices['season'][ $v ], $f['season'] ) ),
			__( 'Wear it for', 'begentle' ) => implode( ', ', array_map( fn( $v ) => $choices['occasion'][ $v ], $f['occasion'] ) ),
		);

		foreach ( array_filter( $props ) as $name => $value ) {
			$markup['additionalProperty'][] = array(
				'@type' => 'PropertyValue',
				'name'  => $name,
				'value' => $value,
			);
		}

		return $markup;
	},
	20,
	2
);

/* ---------- Prices: "From ₹599", sizes priced in the picker ---------- */

/** "From ₹599" (with the struck-out regular price when the cheapest size is on sale). */
add_filter(
	'woocommerce_variable_price_html',
	function ( $html, $product ) {
		$min = (float) $product->get_variation_price( 'min', true );
		$max = (float) $product->get_variation_price( 'max', true );

		if ( $min <= 0 || $min === $max ) {
			return $html;
		}

		$regular = (float) $product->get_variation_regular_price( 'min', true );
		$price   = $product->is_on_sale() && $regular > $min ? wc_format_sale_price( $regular, $min ) : wc_price( $min );

		return sprintf(
			'<span class="price-from">%1$s</span> %2$s',
			esc_html__( 'From', 'begentle' ),
			$price
		);
	},
	10,
	2
);

/** Price for each value of a single-attribute variable product: [ attribute value slug => price ]. */
function begentle_size_prices( WC_Product $product ): array {
	static $cache = array();

	$id = $product->get_id();

	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$cache[ $id ] = array();

	if ( ! $product instanceof WC_Product_Variable || 1 !== count( $product->get_variation_attributes() ) ) {
		return $cache[ $id ];
	}

	foreach ( $product->get_available_variations() as $variation ) {
		$value = reset( $variation['attributes'] );
		if ( '' !== $value && ! isset( $cache[ $id ][ $value ] ) ) {
			$cache[ $id ][ $value ] = (float) $variation['display_price'];
		}
	}

	// Every size the same price: nothing worth showing.
	if ( count( array_unique( $cache[ $id ] ) ) < 2 ) {
		$cache[ $id ] = array();
	}

	return $cache[ $id ];
}

// "30 ml · ₹599" in the size dropdown on the product page.
add_filter(
	'woocommerce_variation_option_name',
	function ( $name, $term = null, $attribute = null, $product = null ) {
		if ( is_admin() || ! $product instanceof WC_Product ) {
			return $name;
		}

		$prices = begentle_size_prices( $product );
		$slug   = $term instanceof WP_Term ? $term->slug : (string) $name;

		if ( ! isset( $prices[ $slug ] ) ) {
			return $name;
		}

		return $name . ' · ' . html_entity_decode( wp_strip_all_tags( wc_price( $prices[ $slug ] ) ), ENT_QUOTES, 'UTF-8' );
	},
	10,
	4
);

/* ---------- Small stylesheet + script for the above ---------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'begentle-product-extras',
			get_theme_file_uri( 'assets/css/product-extras.css' ),
			array( 'begentle' ),
			begentle_asset_version( 'assets/css/product-extras.css' )
		);

		if ( is_product() ) {
			wp_enqueue_script(
				'begentle-product-extras',
				get_theme_file_uri( 'assets/js/product-extras.js' ),
				array( 'jquery', 'begentle' ),
				begentle_asset_version( 'assets/js/product-extras.js' ),
				true
			);
		}
	},
	21
);
