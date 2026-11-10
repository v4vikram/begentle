<?php
/**
 * Checkout rules for an Indian D2C store. Works with the Cart/Checkout blocks and the classic checkout.
 * - Phone number required (couriers, Razorpay prefill and COD confirmation all need it).
 * - COD: extra fee, allowed only inside an order value range, clear description.
 * - Free delivery above a set amount, friendlier delivery label.
 * - State starts empty (the PIN code fills it) instead of the store's own state.
 * - "Secure payment" line under Place Order.
 * Amounts live in Customizer > Begentle > Checkout & delivery.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

/* ---------- Settings ---------- */

function begentle_checkout_defaults(): array {
	return array(
		'cod_fee'            => 49,
		'cod_min'            => 0,
		'cod_max'            => 3000,
		'free_shipping_min'  => 999,
		'shipping_label'     => __( 'Standard delivery', 'begentle' ),
		'shipping_note'      => __( 'Ships in 1 to 2 working days', 'begentle' ),
	);
}

function begentle_checkout_mod( string $key ) {
	$defaults = begentle_checkout_defaults();
	return get_theme_mod( 'begentle_' . $key, $defaults[ $key ] ?? '' );
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$defaults = begentle_checkout_defaults();

		$wp_customize->add_section(
			'begentle_checkout',
			array(
				'title' => __( 'Checkout & delivery', 'begentle' ),
				'panel' => 'begentle',
			)
		);

		$fields = array(
			'cod_fee'           => array( __( 'COD fee in ₹ (0 = no fee)', 'begentle' ), 'number', 'absint' ),
			'cod_min'           => array( __( 'COD only for orders from ₹ (0 = no minimum)', 'begentle' ), 'number', 'absint' ),
			'cod_max'           => array( __( 'COD only for orders up to ₹ (0 = no maximum)', 'begentle' ), 'number', 'absint' ),
			'free_shipping_min' => array( __( 'Free delivery for orders from ₹ (0 = off)', 'begentle' ), 'number', 'absint' ),
			'shipping_label'    => array( __( 'Delivery name shown at checkout (replaces "Flat rate")', 'begentle' ), 'text', 'sanitize_text_field' ),
			'shipping_note'     => array( __( 'Delivery time note (empty = hidden)', 'begentle' ), 'text', 'sanitize_text_field' ),
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
					'section' => 'begentle_checkout',
					'type'    => $type,
				)
			);
		}
	},
	20
);

/** Items total after discounts, incl. tax, without shipping or fees. */
function begentle_cart_goods_total(): float {
	if ( ! WC()->cart ) {
		return 0.0;
	}
	return (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_contents_tax();
}

/* ---------- Phone required ---------- */

// Block checkout and the checkout editor read this option ("optional" / "required" / "hidden").
add_filter( 'option_woocommerce_checkout_phone_field', fn() => 'required' );

// Classic checkout + My Account address forms.
add_filter(
	'woocommerce_billing_fields',
	function ( $fields ) {
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone']['required'] = true;
		}
		return $fields;
	}
);

/* ---------- State starts empty ---------- */

// Without this, new visitors get the store's own state (Delhi) pre-selected and can ship with it
// unchanged. The PIN code auto-fill (inc/pincode.php) sets the right one.
add_filter(
	'woocommerce_customer_default_location_array',
	function ( $location ) {
		$location['state'] = '';
		return $location;
	}
);

/* ---------- Delivery: label + free above a set amount ---------- */

add_filter(
	'woocommerce_package_rates',
	function ( $rates, $package ) {
		$label    = trim( (string) begentle_checkout_mod( 'shipping_label' ) );
		$free_min = (float) begentle_checkout_mod( 'free_shipping_min' );
		$goods    = (float) ( $package['contents_cost'] ?? 0 );

		// Paid delivery methods this applies to: Woo's flat rate and Shiprocket's rate ("Flat Rate", ₹40).
		$methods = (array) apply_filters( 'begentle_paid_shipping_methods', array( 'flat_rate', 'shiprocket_woocommerce_shipping' ) );

		foreach ( $rates as $rate ) {
			if ( ! in_array( $rate->get_method_id(), $methods, true ) ) {
				continue;
			}

			if ( $label ) {
				$rate->set_label( $label );
			}

			if ( $free_min > 0 && $goods >= $free_min ) {
				$rate->set_cost( 0 );
				$rate->set_taxes( array() );
				/* translators: %s: delivery name, e.g. Standard delivery */
				$rate->set_label( sprintf( __( 'Free %s', 'begentle' ), strtolower( $label ? $label : $rate->get_label() ) ) );
			}
		}

		return $rates;
	},
	9999, // After Shiprocket, which builds its rate late.
	2
);

// "Add ₹360 more for free delivery" in the bag drawer.
add_action(
	'woocommerce_widget_shopping_cart_before_buttons',
	function () {
		$free_min = (float) begentle_checkout_mod( 'free_shipping_min' );

		if ( $free_min <= 0 || ! WC()->cart || WC()->cart->is_empty() || ! WC()->cart->needs_shipping() ) {
			return;
		}

		$goods = (float) WC()->cart->get_displayed_subtotal();
		$pct   = (int) min( 100, round( $goods / $free_min * 100 ) );

		if ( $goods >= $free_min ) {
			$text = esc_html__( 'You get free delivery 🎉', 'begentle' );
		} else {
			/* translators: %s: amount still needed, e.g. ₹360 */
			$text = sprintf( esc_html__( 'Add %s more for free delivery', 'begentle' ), wp_kses_post( wc_price( $free_min - $goods ) ) );
		}

		printf(
			'<div class="free-ship"><p class="free-ship__text">%1$s</p><div class="free-ship__bar" aria-hidden="true"><span style="width:%2$d%%"></span></div></div>',
			$text, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$pct
		);
	},
	5
);

/* ---------- Cash on delivery ---------- */

/** Is COD allowed for the current cart value? */
function begentle_cod_allowed(): bool {
	$goods = begentle_cart_goods_total();
	$min   = (float) begentle_checkout_mod( 'cod_min' );
	$max   = (float) begentle_checkout_mod( 'cod_max' );

	return ! ( ( $min > 0 && $goods < $min ) || ( $max > 0 && $goods > $max ) );
}

// Hide COD outside the allowed range. The Store API sends this list with every cart update,
// so the block checkout drops / restores COD live as the cart changes.
add_filter(
	'woocommerce_available_payment_gateways',
	function ( $gateways ) {
		if ( is_admin() || ! isset( $gateways['cod'] ) || ! WC()->cart ) {
			return $gateways;
		}
		if ( ! begentle_cod_allowed() ) {
			unset( $gateways['cod'] );
		}
		return $gateways;
	}
);

// COD description (blocks read it from the settings, classic from the gateway). Woo's stock
// sentence is replaced; the fee note is added to whatever the admin wrote, so it always matches the fee.
add_filter(
	'option_woocommerce_cod_settings',
	function ( $settings ) {
		if ( ! is_array( $settings ) || is_admin() ) {
			return $settings;
		}

		$desc = trim( (string) ( $settings['description'] ?? '' ) );
		if ( '' === $desc || 'Pay with cash upon delivery.' === $desc ) {
			$desc = __( 'Pay in cash or UPI when your order arrives.', 'begentle' );
		}

		$fee = (int) begentle_checkout_mod( 'cod_fee' );
		if ( $fee > 0 ) {
			$desc .= ' ' . sprintf(
				/* translators: %s: COD fee, e.g. ₹49 */
				__( 'A %s COD fee applies. Pay online to skip it.', 'begentle' ),
				html_entity_decode( wp_strip_all_tags( wc_price( $fee ) ), ENT_QUOTES, 'UTF-8' )
			);
		}

		$settings['description'] = $desc;
		return $settings;
	}
);

/** Payment method the shopper has picked so far (block checkout: set over AJAX; classic: Woo's own session key). */
function begentle_chosen_payment_method(): string {
	if ( ! WC()->session ) {
		return '';
	}
	return (string) ( WC()->session->get( 'begentle_payment_method' ) ?: WC()->session->get( 'chosen_payment_method' ) );
}

// Show the fee in the cart totals while COD is selected.
add_action(
	'woocommerce_cart_calculate_fees',
	function ( WC_Cart $cart ) {
		$fee = (float) begentle_checkout_mod( 'cod_fee' );

		if ( $fee <= 0 || 'cod' !== begentle_chosen_payment_method() || ! begentle_cod_allowed() ) {
			return;
		}

		$cart->add_fee( __( 'COD fee', 'begentle' ), $fee, false );
	}
);

// Block checkout: the JS (assets/js/checkout.js) reports the picked payment method so totals refresh.
// On init: woocommerce_blocks_loaded has already fired by the time a theme loads.
add_action(
	'init',
	function () {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			return;
		}
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'begentle-payment',
				'callback'  => function ( $data ) {
					if ( WC()->session ) {
						WC()->session->set( 'begentle_payment_method', sanitize_key( (string) ( $data['method'] ?? '' ) ) );
					}
				},
			)
		);
	}
);

// Final safety net on the order itself (block checkout): COD always carries the fee, other methods never do,
// whatever the cart showed. Runs before the payment is processed, so Razorpay charges the right total.
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( WC_Order $order, $request ) {
		$method = (string) $request['payment_method'];
		$amount = (float) begentle_checkout_mod( 'cod_fee' );
		$label  = __( 'COD fee', 'begentle' );
		$found  = null;

		foreach ( $order->get_fees() as $item_id => $fee ) {
			if ( $fee->get_name() === $label ) {
				$found = $item_id;
			}
		}

		if ( 'cod' === $method && $amount > 0 && null === $found ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( $label );
			$fee->set_amount( $amount );
			$fee->set_total( $amount );
			$fee->set_tax_status( 'none' );
			$order->add_item( $fee );
			$order->calculate_totals( false );
		} elseif ( 'cod' !== $method && null !== $found ) {
			$order->remove_item( $found );
			$order->calculate_totals( false );
		}

		if ( WC()->session ) {
			WC()->session->set( 'begentle_payment_method', $method );
		}
	},
	10,
	2
);

// Classic checkout: totals refresh on payment change (Woo posts update_order_review).
add_action(
	'wp_footer',
	function () {
		if ( ! is_checkout() || has_block( 'woocommerce/checkout', (int) wc_get_page_id( 'checkout' ) ) ) {
			return;
		}
		echo "<script>jQuery(function($){\$(document.body).on('change','input[name=\"payment_method\"]',function(){\$(document.body).trigger('update_checkout');});});</script>\n";
	}
);

/* ---------- Assets ---------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_checkout() && ! is_cart() ) {
			// The bag drawer (free delivery bar) is on every page.
			wp_enqueue_style( 'begentle-checkout', get_theme_file_uri( 'assets/css/checkout.css' ), array( 'begentle' ), begentle_asset_version( 'assets/css/checkout.css' ) );
			return;
		}

		wp_enqueue_style( 'begentle-checkout', get_theme_file_uri( 'assets/css/checkout.css' ), array( 'begentle' ), begentle_asset_version( 'assets/css/checkout.css' ) );

		if ( is_checkout() ) {
			wp_enqueue_script( 'begentle-checkout', get_theme_file_uri( 'assets/js/checkout.js' ), array(), begentle_asset_version( 'assets/js/checkout.js' ), true );
		}
	},
	22
);
