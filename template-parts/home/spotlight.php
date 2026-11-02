<?php
/**
 * One big product: the first featured product with a photo, otherwise the latest one.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

$spotlight  = null;
$candidates = array_filter( array_map( 'wc_get_product', array_slice( wc_get_featured_product_ids(), 0, 10 ) ) );

if ( ! $candidates ) {
	$candidates = wc_get_products(
		array(
			'limit'   => 10,
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
}

foreach ( $candidates as $candidate ) {
	if ( $candidate->is_visible() && $candidate->get_image_id() ) {
		$spotlight = $candidate;
		break;
	}
}

if ( ! $spotlight ) {
	return;
}

$summary = $spotlight->get_short_description() ? $spotlight->get_short_description() : $spotlight->get_description();
$sizes   = $spotlight->get_attribute( 'pa_size' );
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-spotlight">
	<div class="grid items-center overflow-hidden rounded-3xl bg-mist md:grid-cols-2">
		<a href="<?php echo esc_url( $spotlight->get_permalink() ); ?>" class="group block aspect-square overflow-hidden md:aspect-[4/5]" tabindex="-1" aria-hidden="true">
			<?php
			$img_class = 'size-full object-cover transition-transform duration-500 group-hover:scale-105';
			$custom_id = (int) begentle_mod( 'spotlight_image' );
			$bundled   = get_theme_file_path( 'assets/img/spotlight.jpg' );

			if ( $custom_id ) {
				echo wp_get_attachment_image( $custom_id, 'large', false, array( 'class' => $img_class, 'alt' => '' ) );
			} elseif ( file_exists( $bundled ) ) {
				echo '<img class="' . esc_attr( $img_class ) . '" src="' . esc_url( get_theme_file_uri( 'assets/img/spotlight.jpg' ) ) . '?v=' . esc_attr( (string) filemtime( $bundled ) ) . '" width="1200" height="1500" alt="" loading="lazy" decoding="async">';
			} else {
				echo wp_get_attachment_image( $spotlight->get_image_id(), 'large', false, array( 'class' => $img_class, 'alt' => '' ) );
			}
			?>
		</a>
		<div class="p-6 md:p-12">
			<p class="text-xs font-bold uppercase tracking-wider text-denim"><?php esc_html_e( 'Spotlight', 'begentle' ); ?></p>
			<h2 id="home-spotlight" class="mt-2 text-3xl font-extrabold leading-tight tracking-tight md:text-5xl"><?php echo esc_html( $spotlight->get_name() ); ?></h2>
			<?php if ( $summary ) : ?>
				<p class="mt-3 text-muted"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $summary ), 28 ) ); ?></p>
			<?php endif; ?>
			<div class="price !mt-4 !text-xl"><?php echo wp_kses_post( $spotlight->get_price_html() ); ?></div>
			<?php if ( $sizes ) : ?>
				<p class="mt-2 text-sm text-muted">
					<?php
					/* translators: %s: comma separated sizes */
					printf( esc_html__( 'Sizes: %s', 'begentle' ), esc_html( $sizes ) );
					?>
				</p>
			<?php endif; ?>
			<a class="btn mt-6" href="<?php echo esc_url( $spotlight->get_permalink() ); ?>">
				<?php esc_html_e( 'Shop now', 'begentle' ); ?>
				<?php echo begentle_icon( 'arrow', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</section>
