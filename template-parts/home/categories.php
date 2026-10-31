<?php
/**
 * Category tiles: swipeable on phones, grid on desktop.
 */
defined( 'ABSPATH' ) || exit;

$categories = begentle_top_categories( 8 );

if ( ! $categories ) {
	return;
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-cats">
	<?php begentle_section_head( 'home-cats', __( 'Shop by category', 'begentle' ), wc_get_page_permalink( 'shop' ), __( 'Shop all', 'begentle' ) ); ?>
	<div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0">
		<?php
		foreach ( $categories as $term ) :
			$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			?>
			<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="group relative aspect-[4/5] w-40 shrink-0 snap-start overflow-hidden rounded-2xl bg-mist md:w-auto">
				<?php
				if ( $thumb_id ) {
					echo wp_get_attachment_image(
						$thumb_id,
						'woocommerce_thumbnail',
						false,
						array( 'class' => 'absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-105' )
					);
				}
				?>
				<span class="absolute inset-x-0 bottom-0 p-3 text-sm font-bold <?php echo $thumb_id ? 'bg-linear-to-t from-black/60 to-transparent pt-10 text-white' : 'text-ink'; ?>">
					<?php echo esc_html( $term->name ); ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
