<?php
/**
 * Shop by price / size. Shortcuts into the shop filters, built from real catalogue data.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

$shop_url = wc_get_page_permalink( 'shop' );
$symbol   = html_entity_decode( get_woocommerce_currency_symbol() );
$bounds   = begentle_price_bounds();
$prices   = array();

// Buckets at 25 / 50 / 75% of the top price, rounded to one significant digit (3999 -> 1000, 2000, 3000).
if ( $bounds['max'] >= 10 ) {
	$limits = array();

	foreach ( array( 0.25, 0.5, 0.75 ) as $share ) {
		$value    = $bounds['max'] * $share;
		$step     = 10 ** floor( log10( $value ) );
		$limits[] = (int) ( round( $value / $step ) * $step );
	}

	$limits = array_values( array_unique( array_filter( $limits, fn( $limit ) => $limit > $bounds['min'] && $limit < $bounds['max'] ) ) );

	foreach ( $limits as $limit ) {
		$prices[] = array(
			/* translators: %s: price */
			sprintf( __( 'Under %s', 'begentle' ), $symbol . number_format_i18n( $limit ) ),
			add_query_arg( 'max_price', $limit, $shop_url ),
		);
	}

	if ( $limits ) {
		$top      = end( $limits );
		$prices[] = array( $symbol . number_format_i18n( $top ) . '+', add_query_arg( 'min_price', $top, $shop_url ) );
	}
}

$sizes = array();
foreach ( begentle_filter_attributes() as $attribute ) {
	if ( 'size' === $attribute['name'] ) {
		$sizes = $attribute['terms'];
	}
}

if ( ! $prices && ! $sizes ) {
	return;
}
?>
<section class="mx-auto grid max-w-7xl gap-3 px-4 pt-10 md:grid-cols-2 md:pt-16">
	<?php if ( $prices ) : ?>
		<div class="rounded-3xl border border-line p-6">
			<h2 class="text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'Shop by price', 'begentle' ); ?></h2>
			<div class="shop-chips">
				<?php foreach ( $prices as list( $label, $url ) ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $sizes ) : ?>
		<div class="rounded-3xl border border-line p-6">
			<h2 class="text-2xl font-extrabold tracking-tight"><?php esc_html_e( 'Shop by size', 'begentle' ); ?></h2>
			<div class="shop-chips">
				<?php foreach ( $sizes as $size ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'filter_size', $size->slug, $shop_url ) ); ?>"><?php echo esc_html( $size->name ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
