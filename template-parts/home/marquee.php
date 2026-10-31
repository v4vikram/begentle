<?php
/**
 * Scrolling ticker. The items are printed twice so the CSS loop has no visible jump.
 */
defined( 'ABSPATH' ) || exit;

$items = array_filter( array_map( 'trim', explode( '|', (string) begentle_mod( 'marquee' ) ) ) );

if ( ! $items ) {
	return;
}

// Short lists are repeated so one half of the track is always wider than the screen.
$items = array_merge( $items, $items, $items );
?>
<div class="marquee" aria-hidden="true">
	<div class="marquee__track">
		<?php foreach ( array_merge( $items, $items ) as $item ) : ?>
			<span><?php echo esc_html( $item ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
