<?php
/**
 * Contact form (page-contact.php) without a plugin: nonce, honeypot, simple rate limit, wp_mail.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_begentle_contact', 'begentle_handle_contact' );
add_action( 'admin_post_begentle_contact', 'begentle_handle_contact' );

function begentle_handle_contact(): void {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	$back = remove_query_arg( 'sent', $back );

	if ( ! isset( $_POST['_bg_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_bg_nonce'] ), 'begentle_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'sent', 'error', $back ) );
		exit;
	}

	// Bots fill the hidden field; pretend it worked.
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'sent', 'ok', $back ) );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$order   = sanitize_text_field( wp_unslash( $_POST['order'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

	if ( ! $name || ! is_email( $email ) || strlen( $message ) < 5 ) {
		wp_safe_redirect( add_query_arg( 'sent', 'invalid', $back ) );
		exit;
	}

	// One message per minute per IP.
	$ip_key = 'bg_contact_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	if ( get_transient( $ip_key ) ) {
		wp_safe_redirect( add_query_arg( 'sent', 'wait', $back ) );
		exit;
	}
	set_transient( $ip_key, 1, MINUTE_IN_SECONDS );

	$to   = begentle_mod( 'contact_email' ) ? begentle_mod( 'contact_email' ) : get_option( 'admin_email' );
	$body = "Name: {$name}\nEmail: {$email}\n" . ( $order ? "Order: {$order}\n" : '' ) . "\n{$message}\n";
	$sent = wp_mail(
		$to,
		sprintf( '[%s] Message from %s', get_bloginfo( 'name' ), $name ),
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	wp_safe_redirect( add_query_arg( 'sent', $sent ? 'ok' : 'error', $back ) );
	exit;
}
