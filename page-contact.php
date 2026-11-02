<?php
/**
 * Contact page (slug "contact"): details from the Customizer + a plugin-free form (inc/contact.php).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$email     = (string) begentle_mod( 'contact_email' );
$whatsapp  = (string) begentle_mod( 'contact_whatsapp' );
$instagram = ltrim( (string) begentle_mod( 'contact_instagram' ), '@' );
$hours     = (string) begentle_mod( 'contact_hours' );
$status    = isset( $_GET['sent'] ) ? sanitize_key( $_GET['sent'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$messages  = array(
	'ok'      => array( 'success', __( 'Thanks! Your message is on its way. We usually reply within one working day.', 'begentle' ) ),
	'invalid' => array( 'error', __( 'Please add your name, a valid email and a message.', 'begentle' ) ),
	'wait'    => array( 'error', __( 'You just sent a message. Please wait a minute before sending another.', 'begentle' ) ),
	'error'   => array( 'error', __( 'Something went wrong. Please email us directly.', 'begentle' ) ),
);
?>
<div class="mx-auto max-w-6xl px-4 py-6 md:py-12">
	<h1 class="page-title"><?php esc_html_e( 'Contact us', 'begentle' ); ?></h1>
	<p class="mt-2 max-w-xl text-muted"><?php esc_html_e( 'Questions about an order, a scent or a collab? Drop us a message.', 'begentle' ); ?></p>

	<div class="mt-8 grid gap-8 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] md:gap-12">
		<ul class="grid content-start gap-3">
			<?php if ( $email ) : ?>
				<li><a class="block rounded-2xl border border-line p-5 hover:border-ink" href="mailto:<?php echo esc_attr( $email ); ?>">
					<span class="block text-xs font-bold uppercase tracking-wider text-muted"><?php esc_html_e( 'Email', 'begentle' ); ?></span>
					<span class="mt-1 block break-all font-bold"><?php echo esc_html( $email ); ?></span>
				</a></li>
			<?php endif; ?>
			<?php if ( $whatsapp ) : ?>
				<li><a class="block rounded-2xl border border-line p-5 hover:border-ink" href="<?php echo esc_url( 'https://wa.me/' . $whatsapp ); ?>" target="_blank" rel="noopener">
					<span class="block text-xs font-bold uppercase tracking-wider text-muted"><?php esc_html_e( 'WhatsApp', 'begentle' ); ?></span>
					<span class="mt-1 block font-bold">+<?php echo esc_html( $whatsapp ); ?></span>
				</a></li>
			<?php endif; ?>
			<?php if ( $instagram ) : ?>
				<li><a class="block rounded-2xl border border-line p-5 hover:border-ink" href="<?php echo esc_url( 'https://instagram.com/' . $instagram ); ?>" target="_blank" rel="noopener">
					<span class="block text-xs font-bold uppercase tracking-wider text-muted"><?php esc_html_e( 'Instagram', 'begentle' ); ?></span>
					<span class="mt-1 block font-bold">@<?php echo esc_html( $instagram ); ?></span>
				</a></li>
			<?php endif; ?>
			<?php if ( $hours ) : ?>
				<li class="rounded-2xl bg-mist p-5">
					<span class="block text-xs font-bold uppercase tracking-wider text-muted"><?php esc_html_e( 'Support hours', 'begentle' ); ?></span>
					<span class="mt-1 block font-bold"><?php echo esc_html( $hours ); ?></span>
				</li>
			<?php endif; ?>
		</ul>

		<form class="contact-form grid gap-4 rounded-3xl border border-line p-5 md:p-8" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php if ( isset( $messages[ $status ] ) ) : ?>
				<p class="rounded-xl p-4 text-sm font-semibold <?php echo 'success' === $messages[ $status ][0] ? 'bg-mist text-ink' : 'bg-red-50 text-red-700'; ?>" role="status">
					<?php echo esc_html( $messages[ $status ][1] ); ?>
				</p>
			<?php endif; ?>
			<input type="hidden" name="action" value="begentle_contact">
			<?php wp_nonce_field( 'begentle_contact', '_bg_nonce' ); ?>
			<p class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<p class="grid gap-1.5">
				<label class="text-sm font-semibold" for="bg-name"><?php esc_html_e( 'Name', 'begentle' ); ?></label>
				<input id="bg-name" type="text" name="name" required autocomplete="name">
			</p>
			<p class="grid gap-1.5">
				<label class="text-sm font-semibold" for="bg-email"><?php esc_html_e( 'Email', 'begentle' ); ?></label>
				<input id="bg-email" type="email" name="email" required autocomplete="email">
			</p>
			<p class="grid gap-1.5">
				<label class="text-sm font-semibold" for="bg-order"><?php esc_html_e( 'Order number (optional)', 'begentle' ); ?></label>
				<input id="bg-order" type="text" name="order" inputmode="numeric">
			</p>
			<p class="grid gap-1.5">
				<label class="text-sm font-semibold" for="bg-message"><?php esc_html_e( 'Message', 'begentle' ); ?></label>
				<textarea id="bg-message" name="message" rows="5" required></textarea>
			</p>
			<p><button type="submit" class="btn w-full md:w-auto"><?php esc_html_e( 'Send message', 'begentle' ); ?></button></p>
		</form>
	</div>
</div>
<?php
get_footer();
