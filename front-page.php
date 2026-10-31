<?php
/**
 * Home: hero, highlights, categories, new drops, promo banner, sale, best sellers.
 * Optional sections render only when they have content.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$has_woo    = begentle_has_woo();
$shop_url   = $has_woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$hero_image = (int) begentle_mod( 'hero_image' );
$categories = begentle_top_categories( 8 );

$usps = array_filter(
	array_map(
		'trim',
		array( (string) begentle_mod( 'usp_1' ), (string) begentle_mod( 'usp_2' ), (string) begentle_mod( 'usp_3' ) )
	)
);

$promo_title = trim( (string) begentle_mod( 'promo_title' ) );
$promo_image = (int) begentle_mod( 'promo_image' );
$promo_url   = (string) begentle_mod( 'promo_url' );

$has_sale = $has_woo && wc_get_product_ids_on_sale();

$has_bestsellers = $has_woo && get_posts(
	array(
		'post_type'      => 'product',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => 0, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_compare'   => '>',
		'meta_type'      => 'NUMERIC',
	)
);
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

<?php if ( $usps ) : ?>
	<ul class="mx-auto grid max-w-7xl gap-x-6 gap-y-2 px-4 py-5 text-sm font-semibold sm:grid-cols-3">
		<?php foreach ( $usps as $usp ) : ?>
			<li class="flex items-center gap-2">
				<?php echo begentle_icon( 'check', 'size-5 shrink-0 text-denim' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( $usp ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if ( $categories ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-cats">
		<?php begentle_section_head( 'home-cats', __( 'Shop by category', 'begentle' ), $shop_url, __( 'Shop all', 'begentle' ) ); ?>
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
		<?php begentle_section_head( 'home-new', __( 'New drops', 'begentle' ), add_query_arg( 'orderby', 'date', $shop_url ), __( 'View all', 'begentle' ) ); ?>
		<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
<?php endif; ?>

<?php if ( $promo_title ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-label="<?php echo esc_attr( $promo_title ); ?>">
		<div class="relative isolate overflow-hidden rounded-3xl bg-ink text-white">
			<?php
			if ( $promo_image ) {
				echo wp_get_attachment_image( $promo_image, 'large', false, array( 'class' => 'absolute inset-0 -z-10 size-full object-cover opacity-60' ) );
			}
			?>
			<div class="max-w-xl p-8 py-14 md:p-16">
				<h2 class="text-3xl font-extrabold leading-tight tracking-tight md:text-5xl"><?php echo esc_html( $promo_title ); ?></h2>
				<?php if ( begentle_mod( 'promo_text' ) ) : ?>
					<p class="mt-3 text-base opacity-80"><?php echo esc_html( begentle_mod( 'promo_text' ) ); ?></p>
				<?php endif; ?>
				<?php if ( $promo_url ) : ?>
					<a class="btn mt-6 !bg-white !text-ink hover:!bg-mist" href="<?php echo esc_url( $promo_url ); ?>">
						<?php echo esc_html( begentle_mod( 'promo_cta' ) ); ?>
						<?php echo begentle_icon( 'arrow', 'size-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $has_sale ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-sale">
		<?php begentle_section_head( 'home-sale', __( 'On sale', 'begentle' ), add_query_arg( 'on_sale', '1', $shop_url ), __( 'View all', 'begentle' ) ); ?>
		<?php echo do_shortcode( '[products on_sale="true" limit="4" columns="4"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
<?php endif; ?>

<?php if ( $has_bestsellers ) : ?>
	<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-best">
		<?php begentle_section_head( 'home-best', __( 'Best sellers', 'begentle' ), add_query_arg( 'orderby', 'popularity', $shop_url ), __( 'View all', 'begentle' ) ); ?>
		<?php echo do_shortcode( '[products best_selling="true" limit="4" columns="4"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
<?php endif; ?>

<?php
get_footer();
