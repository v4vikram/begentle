<?php
/**
 * Blog post card. $args['featured'] makes it a wide, image-left card.
 * $args['heading'] sets the title tag (h2 in listings, h3 under another h2).
 */
defined( 'ABSPATH' ) || exit;

$featured = ! empty( $args['featured'] );
$tag      = $args['heading'] ?? 'h2';
$category = begentle_post_category();
?>
<article <?php post_class( $featured ? 'blog-card is-featured' : 'blog-card' ); ?>>
	<a class="blog-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail(
				$featured ? 'large' : 'medium_large',
				array(
					'alt'     => '',
					'loading' => $featured ? 'eager' : 'lazy',
				)
			);
		}
		?>
	</a>
	<div class="blog-card__body">
		<?php if ( $category ) : ?>
			<a class="blog-card__cat" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		<?php endif; ?>
		<<?php echo tag_escape( $tag ); ?> class="blog-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</<?php echo tag_escape( $tag ); ?>>
		<p class="blog-card__excerpt"><?php echo esc_html( begentle_post_description() ); ?></p>
		<div class="blog-card__foot">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<a class="blog-card__more" href="<?php the_permalink(); ?>">
				<?php esc_html_e( 'Read more', 'begentle' ); ?><span class="screen-reader-text">: <?php the_title(); ?></span>
				<?php echo begentle_icon( 'arrow', 'size-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</article>
