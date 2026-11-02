<?php
/**
 * About page (slug "about"). Designed here; page content from the editor is shown at the end if added.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$hero_image = (int) begentle_mod( 'hero_image' );
$shop_url   = begentle_has_woo() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$values     = array(
	array( 'check', __( 'Honest prices', 'begentle' ), __( 'Fair pricing from day one. No fake MRP, no fake discounts.', 'begentle' ) ),
	array( 'filter', __( 'Easy to choose', 'begentle' ), __( 'Five clear moods instead of fifty confusing bottles. Not sure? Start with the discovery set.', 'begentle' ) ),
	array( 'bag', __( 'Made to be worn', 'begentle' ), __( 'Eau de parfum strength, picked for Indian weather and everyday life.', 'begentle' ) ),
);
?>
<section class="mx-auto max-w-7xl px-4 pt-6 md:pt-12">
	<div class="grid items-center gap-8 overflow-hidden rounded-3xl bg-ink text-white md:grid-cols-2">
		<div class="p-8 py-12 md:p-16">
			<p class="text-xs font-bold uppercase tracking-wider opacity-70"><?php esc_html_e( 'About Begentle', 'begentle' ); ?></p>
			<h1 class="mt-3 text-4xl font-extrabold leading-none tracking-tight md:text-6xl"><?php esc_html_e( 'Smell good. Skip the overthinking.', 'begentle' ); ?></h1>
			<p class="mt-5 max-w-md text-base opacity-80"><?php esc_html_e( 'Begentle makes perfume for people who want to smell great without spending a week\'s pocket money or decoding fancy notes.', 'begentle' ); ?></p>
			<a class="btn mt-8 !bg-white !text-ink" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop the drop', 'begentle' ); ?></a>
		</div>
		<?php if ( $hero_image ) : ?>
			<div class="aspect-[4/3] md:aspect-auto md:h-full">
				<?php echo wp_get_attachment_image( $hero_image, 'large', false, array( 'class' => 'size-full object-cover' ) ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="mx-auto max-w-3xl px-4 pt-12 md:pt-20">
	<h2 class="text-2xl font-extrabold tracking-tight md:text-4xl"><?php esc_html_e( 'Why we started', 'begentle' ); ?></h2>
	<div class="prose-lite mt-4 text-muted">
		<p><?php esc_html_e( 'Good perfume always felt like it was either too expensive or too boring. We wanted scents that feel like a mood, not a department store counter. So we built a small line-up: one scent for late nights, one for big first impressions, one for everyday, one for dates and one fresh one for everything else.', 'begentle' ); ?></p>
		<p><?php esc_html_e( 'Every bottle is eau de parfum, named after how it makes you feel. Pick the vibe, spray, done.', 'begentle' ); ?></p>
	</div>
</section>

<section class="mx-auto max-w-7xl px-4 pt-12 md:pt-20" aria-labelledby="about-values">
	<?php begentle_section_head( 'about-values', __( 'What we care about', 'begentle' ) ); ?>
	<ul class="grid gap-3 md:grid-cols-3">
		<?php foreach ( $values as list( $icon, $title, $text ) ) : ?>
			<li class="rounded-2xl border border-line p-6">
				<span class="grid size-11 place-items-center rounded-full bg-mist text-denim">
					<?php echo begentle_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<h3 class="mt-4 text-lg font-bold"><?php echo esc_html( $title ); ?></h3>
				<p class="mt-1 text-sm text-muted"><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 md:py-20">
	<div class="rounded-3xl bg-mist p-8 md:flex md:items-center md:justify-between md:p-12">
		<div>
			<h2 class="text-2xl font-extrabold tracking-tight md:text-3xl"><?php esc_html_e( 'Perfume first. Jeans next.', 'begentle' ); ?></h2>
			<p class="mt-2 text-muted"><?php esc_html_e( 'Perfume is drop 01. Clothing is on the way.', 'begentle' ); ?></p>
		</div>
		<a class="btn mt-6 md:mt-0" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop now', 'begentle' ); ?></a>
	</div>

	<?php
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<div class="prose-lite mx-auto mt-12 max-w-3xl"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
	?>
</section>
<?php
get_footer();
