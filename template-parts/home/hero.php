<?php
/**
 * Hero. Uses the Customizer image, otherwise a collage of the latest product photos.
 */
defined( 'ABSPATH' ) || exit;

$has_woo    = begentle_has_woo();
$shop_url   = $has_woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$hero_image = (int) begentle_mod( 'hero_image' );
$collage    = $hero_image ? array() : begentle_latest_with_images( 3 );
?>
<section class="bg-mist">
	<div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-10 md:grid-cols-2 md:gap-12 md:py-20">
		<div>
			<?php if ( begentle_mod( 'hero_eyebrow' ) ) : ?>
				<p class="mb-4 inline-block rounded-full bg-paper px-3 py-1 text-xs font-bold uppercase tracking-wider"><?php echo esc_html( begentle_mod( 'hero_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h1 class="text-5xl font-extrabold leading-[0.95] tracking-tight sm:text-6xl lg:text-8xl">
				<?php echo nl2br( esc_html( begentle_mod( 'hero_title' ) ) ); ?>
			</h1>
			<p class="mt-5 max-w-md text-base text-muted md:text-lg"><?php echo esc_html( begentle_mod( 'hero_text' ) ); ?></p>
			<div class="mt-8 flex flex-wrap gap-3">
				<a class="btn" href="<?php echo esc_url( $shop_url ); ?>">
					<?php echo esc_html( begentle_mod( 'hero_cta' ) ); ?>
					<?php echo begentle_icon( 'arrow', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="btn btn-outline" href="<?php echo esc_url( add_query_arg( 'orderby', 'date', $shop_url ) ); ?>"><?php esc_html_e( 'New drops', 'begentle' ); ?></a>
			</div>
		</div>

		<?php if ( $hero_image ) : ?>
			<div class="aspect-[4/5] overflow-hidden rounded-3xl bg-line">
				<?php
				echo wp_get_attachment_image(
					$hero_image,
					'large',
					false,
					array(
						'class'         => 'size-full object-cover',
						'fetchpriority' => 'high',
						'loading'       => 'eager',
					)
				);
				?>
			</div>
		<?php elseif ( $collage ) : ?>
			<div class="grid aspect-square grid-cols-2 grid-rows-2 gap-3">
				<?php foreach ( $collage as $index => $item ) : ?>
					<a href="<?php echo esc_url( $item->get_permalink() ); ?>" class="group overflow-hidden rounded-2xl bg-line <?php echo 0 === $index ? 'row-span-2' : ''; ?> <?php echo 1 === count( $collage ) ? 'col-span-2' : ''; ?>" aria-label="<?php echo esc_attr( $item->get_name() ); ?>">
						<?php
						echo wp_get_attachment_image(
							$item->get_image_id(),
							'large',
							false,
							array(
								'class'   => 'size-full object-cover transition-transform duration-500 group-hover:scale-105',
								'alt'     => '',
								'loading' => 'eager',
							)
						);
						?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
