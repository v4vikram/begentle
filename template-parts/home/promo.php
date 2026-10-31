<?php
/**
 * Promo banner. Text comes from the Customizer; the link falls back to the shop.
 */
defined( 'ABSPATH' ) || exit;

$title = trim( (string) begentle_mod( 'promo_title' ) );

if ( ! $title ) {
	return;
}

$image = (int) begentle_mod( 'promo_image' );
$url   = (string) begentle_mod( 'promo_url' );

if ( ! $url && begentle_has_woo() ) {
	$url = wc_get_page_permalink( 'shop' );
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-label="<?php echo esc_attr( $title ); ?>">
	<div class="relative isolate overflow-hidden rounded-3xl bg-ink text-white">
		<?php
		if ( $image ) {
			echo wp_get_attachment_image( $image, 'large', false, array( 'class' => 'absolute inset-0 -z-10 size-full object-cover opacity-60' ) );
		}
		?>
		<div class="max-w-2xl p-8 py-14 md:p-16">
			<h2 class="text-4xl font-extrabold leading-none tracking-tight md:text-6xl"><?php echo esc_html( $title ); ?></h2>
			<?php if ( begentle_mod( 'promo_text' ) ) : ?>
				<p class="mt-4 text-base opacity-80"><?php echo esc_html( begentle_mod( 'promo_text' ) ); ?></p>
			<?php endif; ?>
			<?php if ( $url ) : ?>
				<a class="btn mt-6 !bg-white !text-ink hover:!bg-mist" href="<?php echo esc_url( $url ); ?>">
					<?php echo esc_html( begentle_mod( 'promo_cta' ) ); ?>
					<?php echo begentle_icon( 'arrow', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
