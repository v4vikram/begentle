/* Product page: the headline price (and the mobile sticky bar) follow the chosen size. */
(function ($) {
	'use strict';

	$(function () {
		var $form = $('form.variations_form');

		if (!$form.length) {
			return;
		}

		var $price = $form.closest('.summary').find('> .price').first();
		var $bar = $('#atc-bar .price');
		var original = $price.html();
		var originalBar = $bar.html();

		$form.on('found_variation', function (e, variation) {
			if (!variation || !variation.price_html) {
				return;
			}
			var html = $('<div>').html(variation.price_html).find('.price').html() || variation.price_html;
			$price.html(html);
			$bar.html(html);
			// The headline already shows it; avoid a second copy under the picker.
			$form.find('.woocommerce-variation-price').hide();
		});

		$form.on('reset_data hide_variation', function () {
			$price.html(original);
			$bar.html(originalBar);
		});
	});
})(jQuery);
