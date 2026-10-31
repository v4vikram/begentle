<?php
/**
 * Three value cards, edited in the Customizer.
 */
defined( 'ABSPATH' ) || exit;

$icons = array( 1 => 'check', 2 => 'filter', 3 => 'bag' );
$cards = array();

foreach ( $icons as $number => $icon ) {
	$title = trim( (string) begentle_mod( "value_{$number}_title" ) );

	if ( $title ) {
		$cards[] = array( $icon, $title, trim( (string) begentle_mod( "value_{$number}_text" ) ) );
	}
}

if ( ! $cards ) {
	return;
}
?>
<section class="mx-auto max-w-7xl px-4 pt-10 md:pt-16" aria-label="<?php esc_attr_e( 'Why shop with us', 'begentle' ); ?>">
	<ul class="grid gap-3 md:grid-cols-3">
		<?php foreach ( $cards as list( $icon, $title, $text ) ) : ?>
			<li class="flex items-start gap-4 rounded-2xl border border-line p-5">
				<span class="grid size-11 shrink-0 place-items-center rounded-full bg-mist text-denim">
					<?php echo begentle_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span>
					<span class="block font-bold"><?php echo esc_html( $title ); ?></span>
					<?php if ( $text ) : ?>
						<span class="mt-1 block text-sm text-muted"><?php echo esc_html( $text ); ?></span>
					<?php endif; ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
