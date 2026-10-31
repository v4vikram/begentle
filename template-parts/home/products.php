<?php
/**
 * Tabbed product grid: New drops / Best sellers / On sale. Tabs without products are skipped.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

$shop_url = wc_get_page_permalink( 'shop' );

$tabs = array(
	'new' => array(
		__( 'New drops', 'begentle' ),
		'[products limit="8" columns="4" orderby="date" order="DESC"]',
		add_query_arg( 'orderby', 'date', $shop_url ),
	),
);

$has_bestsellers = get_posts(
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

if ( $has_bestsellers ) {
	$tabs['best'] = array(
		__( 'Best sellers', 'begentle' ),
		'[products best_selling="true" limit="8" columns="4"]',
		add_query_arg( 'orderby', 'popularity', $shop_url ),
	);
}

if ( wc_get_product_ids_on_sale() ) {
	$tabs['sale'] = array(
		__( 'On sale', 'begentle' ),
		'[products on_sale="true" limit="8" columns="4"]',
		add_query_arg( 'on_sale', '1', $shop_url ),
	);
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-products" data-tabs>
	<?php begentle_section_head( 'home-products', __( 'Trending now', 'begentle' ) ); ?>

	<div class="tab-list" role="tablist" aria-labelledby="home-products">
		<?php
		$first = true;
		foreach ( $tabs as $key => list( $label ) ) :
			?>
			<button type="button" role="tab" id="tab-<?php echo esc_attr( $key ); ?>" aria-controls="panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
				<?php echo esc_html( $label ); ?>
			</button>
			<?php
			$first = false;
		endforeach;
		?>
	</div>

	<?php
	$first = true;
	foreach ( $tabs as $key => list( , $shortcode, $url ) ) :
		?>
		<div role="tabpanel" id="panel-<?php echo esc_attr( $key ); ?>" aria-labelledby="tab-<?php echo esc_attr( $key ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
			<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="mt-8 text-center">
				<a class="btn btn-outline" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View all', 'begentle' ); ?></a>
			</div>
		</div>
		<?php
		$first = false;
	endforeach;
	?>
</section>
