<?php
defined( 'ABSPATH' ) || exit;

function begentle_defaults(): array {
	return array(
		'announcement' => '',
		'hero_title'   => __( "Jeans that\njust fit.", 'begentle' ),
		'hero_text'    => __( 'Fresh men\'s denim. Pick your fit, tap, done.', 'begentle' ),
		'hero_cta'     => __( 'Shop jeans', 'begentle' ),
		'hero_image'   => '',
		// Highlights strip (hidden unless at least one is filled in).
		'usp_1'        => '',
		'usp_2'        => '',
		'usp_3'        => '',
		// Promo banner (hidden unless it has a title).
		'promo_title'  => '',
		'promo_text'   => '',
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
					'hero_title'   => array( __( 'Hero title', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
					'hero_text'    => array( __( 'Hero text', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
					'hero_cta'     => array( __( 'Hero button label', 'begentle' ), 'text', 'sanitize_text_field' ),
				),
				array( 'hero_image' => __( 'Hero image (portrait works best)', 'begentle' ) ),
			),
			'begentle_usp'   => array(
				__( 'Highlights strip', 'begentle' ),
				array(
					'usp_1' => array( __( 'Highlight 1 (empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
					'usp_2' => array( __( 'Highlight 2', 'begentle' ), 'text', 'sanitize_text_field' ),
					'usp_3' => array( __( 'Highlight 3', 'begentle' ), 'text', 'sanitize_text_field' ),
				),
				array(),
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
