<?php
/**
 * Filter drawer (shop / category / search). Plain GET form: inc/filters.php reads the params,
 * assets/js/main.js joins the size/colour checkboxes into WooCommerce's "filter_size=28,30" format.
 */
defined( 'ABSPATH' ) || exit;

$bounds = begentle_price_bounds();
$symbol = get_woocommerce_currency_symbol();

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$min_price = isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ? absint( $_GET['min_price'] ) : '';
$max_price = isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ? absint( $_GET['max_price'] ) : '';
$in_stock  = ! empty( $_GET['in_stock'] );
$on_sale   = ! empty( $_GET['on_sale'] );
// phpcs:enable
?>
<aside id="drawer-filter" class="drawer" data-drawer="filter" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Filters', 'begentle' ); ?>">
	<div class="flex h-14 shrink-0 items-center justify-between border-b border-line pl-4 pr-2">
		<span class="text-lg font-extrabold tracking-tight"><?php esc_html_e( 'Filters', 'begentle' ); ?></span>
		<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'Close filters', 'begentle' ); ?>">
			<?php echo begentle_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<form class="filter-form" method="get" action="<?php echo esc_url( begentle_catalog_url() ); ?>">
		<?php
		// Keep sorting and the search query when filters change.
		foreach ( array( 's', 'post_type', 'orderby' ) as $keep ) {
			if ( ! empty( $_GET[ $keep ] ) && is_string( $_GET[ $keep ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $keep ), esc_attr( wc_clean( wp_unslash( $_GET[ $keep ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
		?>

		<div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4">

			<fieldset class="filter-group">
				<legend>
					<?php
					/* translators: %s: currency symbol */
					printf( esc_html__( 'Price (%s)', 'begentle' ), esc_html( html_entity_decode( $symbol ) ) );
					?>
				</legend>
				<div class="grid grid-cols-2 gap-3">
					<input type="number" inputmode="numeric" min="0" name="min_price" value="<?php echo esc_attr( $min_price ); ?>" placeholder="<?php echo esc_attr( $bounds['min'] ? sprintf( /* translators: %s: amount */ __( 'Min %s', 'begentle' ), $bounds['min'] ) : __( 'Min', 'begentle' ) ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'begentle' ); ?>">
					<input type="number" inputmode="numeric" min="0" name="max_price" value="<?php echo esc_attr( $max_price ); ?>" placeholder="<?php echo esc_attr( $bounds['max'] ? sprintf( /* translators: %s: amount */ __( 'Max %s', 'begentle' ), $bounds['max'] ) : __( 'Max', 'begentle' ) ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'begentle' ); ?>">
				</div>
			</fieldset>

			<?php foreach ( begentle_filter_attributes() as $attribute ) : ?>
				<?php $chosen = begentle_chosen_terms( $attribute['name'] ); ?>
				<fieldset class="filter-group" data-attr="<?php echo esc_attr( $attribute['name'] ); ?>">
					<legend><?php echo esc_html( $attribute['label'] ); ?></legend>
					<div class="chips">
						<?php foreach ( $attribute['terms'] as $term ) : ?>
							<label class="chip">
								<input type="checkbox" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $chosen, true ) ); ?>>
								<span><?php echo esc_html( $term->name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<fieldset class="filter-group">
				<legend><?php esc_html_e( 'Availability', 'begentle' ); ?></legend>
				<div class="chips">
					<label class="chip">
						<input type="checkbox" name="in_stock" value="1" <?php checked( $in_stock ); ?>>
						<span><?php esc_html_e( 'In stock', 'begentle' ); ?></span>
					</label>
					<label class="chip">
						<input type="checkbox" name="on_sale" value="1" <?php checked( $on_sale ); ?>>
						<span><?php esc_html_e( 'On sale', 'begentle' ); ?></span>
					</label>
				</div>
			</fieldset>
		</div>

		<div class="filter-footer">
			<a class="btn btn-outline" href="<?php echo esc_url( begentle_clear_filters_url() ); ?>"><?php esc_html_e( 'Clear all', 'begentle' ); ?></a>
			<button type="submit" class="btn flex-1"><?php esc_html_e( 'Show results', 'begentle' ); ?></button>
		</div>
	</form>
</aside>
