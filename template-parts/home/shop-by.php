<?php
/**
 * Shop by price / size. Shortcuts into the shop filters, built from real catalogue data.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

$shop_url = wc_get_page_permalink( 'shop' );
$prices   = array();

// Same ranges as the filter drawer's price presets (inc/filters.php).
foreach ( begentle_price_buckets() as $bucket ) {
	$prices[] = array(
		$bucket['label'],
		add_query_arg( array_filter( array( 'min_price' => $bucket['min'], 'max_price' => $bucket['max'] ) ), $shop_url ),
	);
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
