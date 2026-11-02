<?php
/**
 * FAQ page (slug "faq"): accordion + FAQPage structured data. Edit the questions here.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$faqs = array(
	array(
		__( 'How long does the perfume last?', 'begentle' ),
		__( 'All our perfumes are eau de parfum. How long a scent lasts depends on your skin, the weather and how you apply it. Spray on pulse points and clothes, and do not rub.', 'begentle' ),
	),
	array(
		__( 'Which scent should I pick?', 'begentle' ),
		__( 'Night Shift for late nights, Main Character for big first impressions, Low Key for everyday, Soft Launch for dates and Green Flag for fresh, easy days. Still unsure? Get the Discovery Set and try all five.', 'begentle' ),
	),
	array(
		__( 'Which size should I buy?', 'begentle' ),
		__( 'New to a scent? Start with 30 ml or the Discovery Set. Already love it? 100 ml is the best value per ml.', 'begentle' ),
	),
	array(
		__( 'How long does delivery take?', 'begentle' ),
		__( 'Orders are packed within 1-2 working days. Delivery usually takes 3-7 working days depending on your pin code. Delivery charges are shown at checkout.', 'begentle' ),
	),
	array(
		__( 'Which payment options do you accept?', 'begentle' ),
		__( 'All available payment options are shown at checkout.', 'begentle' ),
	),
	array(
		__( 'Can I return a perfume?', 'begentle' ),
		__( 'For hygiene reasons we accept returns only on unopened, sealed products within 7 days of delivery. Damaged, leaking or wrong items are always replaced or refunded.', 'begentle' ),
	),
	array(
		__( 'How do I track my order?', 'begentle' ),
		__( 'You get a tracking link once your order ships. You can also see all your orders in My account.', 'begentle' ),
	),
);

$schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map(
		function ( $faq ) {
			return array(
				'@type'          => 'Question',
				'name'           => $faq[0],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq[1],
				),
			);
		},
		$faqs
	),
);
?>
<div class="mx-auto max-w-3xl px-4 py-6 md:py-12">
	<h1 class="page-title"><?php esc_html_e( 'FAQ', 'begentle' ); ?></h1>
	<p class="mt-2 text-muted"><?php esc_html_e( 'Quick answers to the questions we get most.', 'begentle' ); ?></p>

	<div class="faq-list mt-8 divide-y divide-line border-y border-line">
		<?php foreach ( $faqs as $i => list( $question, $answer ) ) : ?>
			<details class="group py-5" <?php echo 0 === $i ? 'open' : ''; ?>>
				<summary class="flex list-none items-center justify-between gap-4 text-base font-bold md:text-lg [&::-webkit-details-marker]:hidden">
					<?php echo esc_html( $question ); ?>
					<span class="grid size-8 shrink-0 place-items-center rounded-full bg-mist text-xl leading-none transition-transform group-open:rotate-45" aria-hidden="true">+</span>
				</summary>
				<p class="mt-3 text-muted"><?php echo esc_html( $answer ); ?></p>
			</details>
		<?php endforeach; ?>
	</div>

	<div class="mt-10 rounded-3xl bg-mist p-6 md:p-8">
		<h2 class="text-xl font-extrabold"><?php esc_html_e( 'Still have a question?', 'begentle' ); ?></h2>
		<p class="mt-1 text-muted"><?php esc_html_e( 'We usually reply within one working day.', 'begentle' ); ?></p>
		<a class="btn mt-5" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'begentle' ); ?></a>
	</div>
</div>
<script type="application/ld+json"><?php echo wp_json_encode( $schema ); ?></script>
<?php
get_footer();
