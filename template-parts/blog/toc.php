<?php
/**
 * Table of contents list. $args['items'] from begentle_toc_build().
 */
defined( 'ABSPATH' ) || exit;

$items = $args['items'] ?? array();
?>
<ol class="toc-list">
	<?php foreach ( $items as $item ) : ?>
		<li class="toc-l<?php echo (int) $item['level']; ?>">
			<a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
		</li>
	<?php endforeach; ?>
</ol>
