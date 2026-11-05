<?php
/**
 * Send all site email (WooCommerce orders, password resets, contact form) through SMTP,
 * without a plugin. Settings live in wp-config.php on the server, never in Git:
 *
 *   define( 'BEGENTLE_SMTP_HOST', 'smtp.hostinger.com' );
 *   define( 'BEGENTLE_SMTP_PORT', 465 );
 *   define( 'BEGENTLE_SMTP_SECURE', 'ssl' );              // 'ssl' for 465, 'tls' for 587.
 *   define( 'BEGENTLE_SMTP_USER', 'orders@begentle.shop' );
 *   define( 'BEGENTLE_SMTP_PASS', 'mailbox-password' );
 *   define( 'BEGENTLE_SMTP_FROM_NAME', 'Begentle' );      // Optional.
 *
 * Without BEGENTLE_SMTP_HOST / USER / PASS this file does nothing (e.g. on a local copy).
 * Don't run an SMTP plugin at the same time: both would configure the same mailer.
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BEGENTLE_SMTP_HOST' ) || ! defined( 'BEGENTLE_SMTP_USER' ) || ! defined( 'BEGENTLE_SMTP_PASS' ) ) {
	return;
}

add_action(
	'phpmailer_init',
	function ( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host       = BEGENTLE_SMTP_HOST;
		$mailer->Port       = defined( 'BEGENTLE_SMTP_PORT' ) ? (int) BEGENTLE_SMTP_PORT : 465;
		$mailer->SMTPSecure = defined( 'BEGENTLE_SMTP_SECURE' ) ? BEGENTLE_SMTP_SECURE : 'ssl';
		$mailer->SMTPAuth   = true;
		$mailer->Username   = BEGENTLE_SMTP_USER;
		$mailer->Password   = BEGENTLE_SMTP_PASS;

		// The mail server only accepts mail "from" the mailbox we log in with (otherwise:
		// "553 Sender address rejected: not owned by user"), so always send as that address.
		// Replies still go to whoever the email sets as Reply-To.
		$name = defined( 'BEGENTLE_SMTP_FROM_NAME' ) ? BEGENTLE_SMTP_FROM_NAME : $mailer->FromName;
		$mailer->setFrom( BEGENTLE_SMTP_USER, $name ? $name : get_bloginfo( 'name' ), false );
		$mailer->Sender = BEGENTLE_SMTP_USER; // Bounce / Return-Path address.
	}
);

// Failed sends end up in the PHP error log (and wp-content/debug.log if WP_DEBUG_LOG is on).
add_action(
	'wp_mail_failed',
	function ( $error ) {
		error_log( 'Begentle SMTP: ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
);
