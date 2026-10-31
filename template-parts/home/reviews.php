<?php
/**
 * Real customer reviews (approved, 4 stars or more). Hidden until the store has some.
 */
defined( 'ABSPATH' ) || exit;

if ( ! begentle_has_woo() ) {
	return;
}

$reviews = get_comments(
	array(
		'post_type'  => 'product',
		'status'     => 'approve',
		'type'       => 'review',
		'number'     => 6,
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => 'rating',
				'value'   => 4,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			),
		),
	)
);

if ( ! $reviews ) {
	return;
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-labelledby="home-reviews">
	<?php begentle_section_head( 'home-reviews', __( 'What customers say', 'begentle' ) ); ?>
	<div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:overflow-visible md:px-0">
		<?php foreach ( $reviews as $review ) : ?>
			<figure class="w-72 shrink-0 snap-start rounded-2xl border border-line p-5 md:w-auto">
				<?php echo wc_get_rating_html( (float) get_comment_meta( $review->comment_ID, 'rating', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<blockquote class="mt-3 text-sm leading-relaxed"><?php echo esc_html( wp_trim_words( $review->comment_content, 30 ) ); ?></blockquote>
				<figcaption class="mt-3 text-xs text-muted">
					<span class="font-bold text-ink"><?php echo esc_html( $review->comment_author ); ?></span>
					&middot;
					<a class="underline underline-offset-2" href="<?php echo esc_url( get_permalink( $review->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $review->comment_post_ID ) ); ?></a>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</section>
