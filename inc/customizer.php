<?php
defined( 'ABSPATH' ) || exit;

function begentle_defaults(): array {
	return array(
		'announcement' => '',
		'hero_title'   => __( "Jeans that\njust fit.", 'begentle' ),
		'hero_text'    => __( 'Fresh men\'s denim. Pick your fit, tap, done.', 'begentle' ),
		'hero_cta'     => __( 'Shop jeans', 'begentle' ),
		'hero_image'   => '',
	);
}

function begentle_mod( string $key ) {
	$defaults = begentle_defaults();

	return get_theme_mod( 'begentle_' . $key, $defaults[ $key ] ?? '' );
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_section(
			'begentle_home',
			array(
				'title'    => __( 'Begentle: banner & home', 'begentle' ),
				'priority' => 30,
			)
		);

		$fields = array(
			'announcement' => array( __( 'Announcement bar (leave empty to hide)', 'begentle' ), 'text', 'sanitize_text_field' ),
			'hero_title'   => array( __( 'Hero title', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
			'hero_text'    => array( __( 'Hero text', 'begentle' ), 'textarea', 'sanitize_textarea_field' ),
			'hero_cta'     => array( __( 'Hero button label', 'begentle' ), 'text', 'sanitize_text_field' ),
		);

		$defaults = begentle_defaults();

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
					'section' => 'begentle_home',
					'type'    => $type,
				)
			);
		}

		$wp_customize->add_setting(
			'begentle_hero_image',
			array(
				'default'           => '',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'begentle_hero_image',
				array(
					'label'     => __( 'Hero image (portrait works best)', 'begentle' ),
					'section'   => 'begentle_home',
					'mime_type' => 'image',
				)
			)
		);
	}
);
