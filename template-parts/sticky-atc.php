<?php
/**
 * Sticky "Add to cart" bar for phones. Shown by JS while the real button is off-screen.
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() || ! $product->is_type( array( 'simple', 'variable' ) ) ) {
	return;
}
?>
<div id="atc-bar" class="atc-bar">
	<div class="min-w-0 flex-1">
		<p class="truncate text-sm font-semibold"><?php the_title(); ?></p>
		<p class="price text-xs"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
	</div>
	<button type="button" class="btn shrink-0 !h-11 px-6"><?php esc_html_e( 'Add to cart', 'begentle' ); ?></button>
</div>
