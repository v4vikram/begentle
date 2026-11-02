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

// The photo banner shows on the shop and on search / filtered / paged results too (shorter there).
$show_banner  = true; // Same banner size everywhere: shop, categories, search, filters.
$compact      = is_search() || is_paged() || is_product_taxonomy() || (bool) begentle_active_filters();
$banner_image = $show_banner ? (int) begentle_mod( 'shop_banner' ) : 0;
$banner_shots = array();
$banner_title = is_search() ? sprintf( /* translators: %s: search term */ __( 'Results for “%s”', 'begentle' ), get_search_query() ) : ( $term instanceof WP_Term ? $term->name : begentle_mod( 'shop_title' ) );
$total        = (int) $GLOBALS['wp_query']->found_posts;
?>
<?php if ( $show_banner ) : ?>
	<header class="shop-banner has-image <?php echo $compact ? 'is-compact' : ''; ?>">
		<?php
		if ( ! $banner_image ) {
			printf(
				'<img class="shop-banner__img" src="%s" width="2000" height="494" alt="" fetchpriority="high" decoding="async">',
				esc_url( get_theme_file_uri( 'assets/img/shop-banner.jpg' ) )
			);
		} else {
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
			<?php if ( $total && ! $compact ) : ?>
				<p class="text-xs font-bold uppercase tracking-wider opacity-70">
					<?php
					/* translators: %s: number of products */
					echo esc_html( sprintf( _n( '%s product', '%s products', $total, 'begentle' ), number_format_i18n( $total ) ) );
					?>
				</p>
			<?php endif; ?>
			<h1 class="mt-2 font-extrabold leading-none tracking-tight <?php echo $compact ? 'text-3xl md:text-4xl' : 'text-4xl md:text-6xl'; ?>"><?php echo esc_html( $banner_title ); ?></h1>
			<?php if ( $compact ) : ?>
				<p class="mt-2 text-sm opacity-80">
					<?php /* translators: %s: number of products */ echo esc_html( sprintf( _n( '%s product', '%s products', $total, 'begentle' ), number_format_i18n( $total ) ) ); ?>
				</p>
			<?php elseif ( begentle_mod( 'shop_text' ) ) : ?>
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
