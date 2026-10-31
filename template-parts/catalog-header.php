<?php
/**
 * Title block for shop / category / search pages, with category pills.
 */
defined( 'ABSPATH' ) || exit;

$term     = is_product_taxonomy() ? get_queried_object() : null;
$thumb_id = $term instanceof WP_Term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
$desc     = $term instanceof WP_Term ? term_description( $term->term_id, $term->taxonomy ) : '';

// Highlight the top-level category the visitor is currently in.
$active_id = 0;
if ( is_product_category() && $term instanceof WP_Term ) {
	$ancestors = get_ancestors( $term->term_id, 'product_cat' );
	$active_id = $ancestors ? (int) end( $ancestors ) : $term->term_id;
}

$pills = is_search() ? array() : begentle_top_categories( 12 );

// Full banner only on the first, unfiltered shop page: otherwise it just pushes products down.
$show_banner  = is_shop() && ! is_search() && ! is_paged() && ! begentle_active_filters();
$banner_image = $show_banner ? (int) begentle_mod( 'shop_image' ) : 0;
$banner_shots = $show_banner && ! $banner_image ? begentle_latest_with_images( 3 ) : array();
$total        = (int) $GLOBALS['wp_query']->found_posts;
?>
<?php if ( $show_banner ) : ?>
	<header class="shop-banner <?php echo $banner_image ? 'has-image' : ''; ?>">
		<?php
		if ( $banner_image ) {
			echo wp_get_attachment_image(
				$banner_image,
				'full',
				false,
				array(
					'class'         => 'shop-banner__img',
					'alt'           => '',
					'sizes'         => '(min-width: 1280px) 1248px, 100vw',
					'fetchpriority' => 'high',
					'loading'       => 'eager',
				)
			);
		}
		?>
		<div class="shop-banner__body">
			<?php if ( $total ) : ?>
				<p class="text-xs font-bold uppercase tracking-wider opacity-70">
					<?php
					/* translators: %s: number of products */
					echo esc_html( sprintf( _n( '%s style', '%s styles', $total, 'begentle' ), number_format_i18n( $total ) ) );
					?>
				</p>
			<?php endif; ?>
			<h1 class="mt-2 text-4xl font-extrabold leading-none tracking-tight md:text-6xl"><?php echo esc_html( begentle_mod( 'shop_title' ) ); ?></h1>
			<?php if ( begentle_mod( 'shop_text' ) ) : ?>
				<p class="mt-3 max-w-md text-sm opacity-80 md:text-base"><?php echo esc_html( begentle_mod( 'shop_text' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $banner_shots ) : ?>
			<div class="shop-banner__shots" aria-hidden="true">
				<?php foreach ( $banner_shots as $shot ) : ?>
					<?php
					echo wp_get_attachment_image(
						$shot->get_image_id(),
						'woocommerce_thumbnail',
						false,
						array(
							'alt'     => '',
							'loading' => 'eager',
						)
					);
					?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</header>
<?php else : ?>
	<header class="catalog-header <?php echo $thumb_id ? 'has-image' : ''; ?>">
		<?php
		if ( $thumb_id ) {
			echo wp_get_attachment_image( $thumb_id, 'large', false, array( 'class' => 'catalog-header__img' ) );
		}
		?>
		<div class="catalog-header__body">
			<h1 class="page-title !mb-0">
				<?php
				if ( is_shop() && ! is_search() ) {
					echo esc_html( begentle_mod( 'shop_title' ) );
				} else {
					woocommerce_page_title();
				}
				?>
			</h1>
			<?php if ( $desc ) : ?>
				<div class="mt-2 max-w-xl text-sm opacity-80"><?php echo wp_kses_post( $desc ); ?></div>
			<?php endif; ?>
		</div>
	</header>
<?php endif; ?>

<?php if ( $pills ) : ?>
	<nav class="cat-pills" aria-label="<?php esc_attr_e( 'Categories', 'begentle' ); ?>">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" <?php echo is_shop() ? 'aria-current="page"' : ''; ?>>
			<?php esc_html_e( 'All', 'begentle' ); ?>
		</a>
		<?php foreach ( $pills as $pill ) : ?>
			<a href="<?php echo esc_url( get_term_link( $pill ) ); ?>" <?php echo $pill->term_id === $active_id ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $pill->name ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>
