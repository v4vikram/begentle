<?php
/**
 * Home: hero, categories, new drops.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$has_woo    = begentle_has_woo();
$shop_url   = $has_woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$hero_image = (int) begentle_mod( 'hero_image' );

$categories = array();
if ( $has_woo ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'number'     => 8,
		)
	);
	$categories = is_wp_error( $terms ) ? array() : $terms;
}
?>

<section class="bg-mist">
	<div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 md:py-24 <?php echo $hero_image ? 'md:grid-cols-2' : ''; ?>">
		<div>
			<h1 class="text-5xl font-extrabold leading-[0.95] tracking-tight md:text-7xl">
				<?php echo nl2br( esc_html( begentle_mod( 'hero_title' ) ) ); ?>
			</h1>
			<p class="mt-5 max-w-md text-base text-muted md:text-lg"><?php echo esc_html( begentle_mod( 'hero_text' ) ); ?></p>
			<a class="btn mt-8" href="<?php echo esc_url( $shop_url ); ?>">
				<?php echo esc_html( begentle_mod( 'hero_cta' ) ); ?>
				<?php echo begentle_icon( 'arrow', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
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
		<?php endif; ?>
	</div>
</section>

<?php if ( $categories ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-cats">
		<h2 id="home-cats" class="mb-4 text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'Shop by fit', 'begentle' ); ?></h2>
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
<?php endif; ?>

<?php if ( $has_woo ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-new">
		<div class="mb-4 flex items-end justify-between">
			<h2 id="home-new" class="text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'New drops', 'begentle' ); ?></h2>
			<a class="text-sm font-semibold underline underline-offset-4" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'View all', 'begentle' ); ?></a>
		</div>
		<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
<?php endif; ?>

<?php
get_footer();
