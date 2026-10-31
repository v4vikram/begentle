<?php
defined( 'ABSPATH' ) || exit;

function begentle_defaults(): array {
	return array(
		'announcement' => '',
		'hero_eyebrow' => __( 'Men\'s fashion', 'begentle' ),
		'hero_title'   => __( "Jeans that\njust fit.", 'begentle' ),
		'hero_text'    => __( 'Fresh men\'s denim. Pick your fit, tap, done.', 'begentle' ),
		'hero_cta'     => __( 'Shop jeans', 'begentle' ),
		'hero_image'   => '',
		// Scrolling ticker, items separated by "|" (empty = hidden).
		'marquee'      => __( 'New drops | Men\'s fashion | Find your fit | Shop the latest', 'begentle' ),
		// Value cards. Placeholder copy: confirm it matches the real store before launch.
		'value_1_title' => __( 'Secure checkout', 'begentle' ),
		'value_1_text'  => __( 'Pay safely at checkout.', 'begentle' ),
		'value_2_title' => __( 'Find your fit fast', 'begentle' ),
		'value_2_text'  => __( 'Filter by size, colour and price in a tap.', 'begentle' ),
		'value_3_title' => __( 'Fresh drops', 'begentle' ),
		'value_3_text'  => __( 'See the newest styles first.', 'begentle' ),
		// Shop page banner.
		'shop_title'   => __( 'Shop all', 'begentle' ),
		'shop_text'    => __( 'Every fit in one place. Filter by size, colour and price.', 'begentle' ),
		'shop_image'   => '',
		// Promo banner (hidden if the title is emptied).
		'promo_title'  => __( 'Fresh fits just landed.', 'begentle' ),
		'promo_text'   => __( 'Explore the latest styles in the shop.', 'begentle' ),
		'promo_cta'    => __( 'Shop now', 'begentle' ),
		'promo_url'    => '',
		'promo_image'  => '',
	);
}

function begentle_mod( string $key ) {
	$defaults = begentle_defaults();

	return get_theme_mod( 'begentle_' . $key, $defaults[ $key ] ?? '' );
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$defaults = begentle_defaults();

		$wp_customize->add_panel(
			'begentle',
			array(
				'title'    => __( 'Begentle', 'begentle' ),
				'priority' => 30,
			)
		);

		// section => [ title, [ key => [ label, control type, sanitize callback ] ], image keys ].
		$sections = array(
			'begentle_hero'  => array(
				__( 'Banner & hero', 'begentle' ),
				array(
					'announcement' => array( __( 'Announcement bar (empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
					'marquee'      => array( __( 'Scrolling ticker (separate items with |, empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
					'hero_eyebrow' => array( __( 'Hero small label', 'begentle' ), 'text', 'sanitize_text_field' ),
					'hero_title'   => array( __( 'Hero title', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
					'hero_text'    => array( __( 'Hero text', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
					'hero_cta'     => array( __( 'Hero button label', 'begentle' ), 'text', 'sanitize_text_field' ),
				),
				array( 'hero_image' => __( 'Hero image (portrait works best)', 'begentle' ) ),
			),
			'begentle_usp'   => array(
				__( 'Value cards', 'begentle' ),
				array(
					'value_1_title' => array( __( 'Card 1 title (empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
					'value_1_text'  => array( __( 'Card 1 text', 'begentle' ), 'text', 'sanitize_text_field' ),
					'value_2_title' => array( __( 'Card 2 title', 'begentle' ), 'text', 'sanitize_text_field' ),
					'value_2_text'  => array( __( 'Card 2 text', 'begentle' ), 'text', 'sanitize_text_field' ),
					'value_3_title' => array( __( 'Card 3 title', 'begentle' ), 'text', 'sanitize_text_field' ),
					'value_3_text'  => array( __( 'Card 3 text', 'begentle' ), 'text', 'sanitize_text_field' ),
				),
				array(),
			),
			'begentle_shop'  => array(
				__( 'Shop page banner', 'begentle' ),
				array(
					'shop_title' => array( __( 'Title', 'begentle' ), 'text', 'sanitize_text_field' ),
					'shop_text'  => array( __( 'Text', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
				),
				array( 'shop_image' => __( 'Banner image (wide, at least 1600px; empty = product photos)', 'begentle' ) ),
			),
			'begentle_promo' => array(
				__( 'Promo banner', 'begentle' ),
				array(
					'promo_title' => array( __( 'Title (empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
					'promo_text'  => array( __( 'Text', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
					'promo_cta'   => array( __( 'Button label', 'begentle' ), 'text', 'sanitize_text_field' ),
					'promo_url'   => array( __( 'Button link', 'begentle' ), 'url', 'esc_url_raw' ),
				),
				array( 'promo_image' => __( 'Background image', 'begentle' ) ),
			),
		);

		foreach ( $sections as $section_id => list( $title, $fields, $images ) ) {
			$wp_customize->add_section(
				$section_id,
				array(
					'title' => $title,
					'panel' => 'begentle',
				)
			);

			foreach ( $fields as $key => list( $label, $type, $sanitize ) ) {
				$wp_customize->add_setting(
					'begentle_' . $key,
					array(
						'default'           => $defaults[ $key ],
						'sanitize_callback' => $sanitize,
					)
				);
				$wp_customize->add_control(
					'begentle_' . $key,
					array(
						'label'   => $label,
						'section' => $section_id,
						'type'    => $type,
					)
				);
			}

			foreach ( $images as $key => $label ) {
				$wp_customize->add_setting(
					'begentle_' . $key,
					array(
						'default'           => '',
						'sanitize_callback' => 'absint',
					)
				);
				$wp_customize->add_control(
					new WP_Customize_Media_Control(
						$wp_customize,
						'begentle_' . $key,
						array(
							'label'     => $label,
							'section'   => $section_id,
							'mime_type' => 'image',
						)
					)
				);
			}
		}
	}
);
