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
?>
<header class="catalog-header <?php echo $thumb_id ? 'has-image' : ''; ?>">
	<?php
	if ( $thumb_id ) {
		echo wp_get_attachment_image( $thumb_id, 'large', false, array( 'class' => 'catalog-header__img' ) );
	}
	?>
	<div class="catalog-header__body">
		<h1 class="page-title !mb-0"><?php woocommerce_page_title(); ?></h1>
		<?php if ( $desc ) : ?>
			<div class="mt-2 max-w-xl text-sm opacity-80"><?php echo wp_kses_post( $desc ); ?></div>
		<?php endif; ?>
	</div>
</header>

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
