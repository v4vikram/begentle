/**
 * Begentle front-end behaviour. Plain jQuery (already shipped by WordPress), no build step.
 *  1. Drawers (menu / search / cart)
 *  2. Size swatches for variable products
 *  3. AJAX add to cart on the single product page (opens the cart drawer)
 *  4. Sticky add-to-cart bar
 */
(function ($) {
	'use strict';

	var $body = $('body');
	var $overlay = $('#overlay');
	var openName = null;
	var lastFocus = null;

	/* ---------- 1. Drawers ---------- */

	function openDrawer(name) {
		var $drawer = $('[data-drawer="' + name + '"]');
		if (!$drawer.length) {
			return;
		}
		if (openName) {
			closeDrawer(true);
		}
		lastFocus = document.activeElement;
		openName = name;
		$drawer.addClass('is-open');
		$overlay.addClass('is-open');
		$body.addClass('drawer-open');
		$('[data-drawer-open="' + name + '"]').attr('aria-expanded', 'true');

		var $focus = $drawer.find('[data-autofocus]').first();
		if (!$focus.length) {
			$focus = $drawer.find('[data-drawer-close]').first();
		}
		// Wait for the slide-in so focus() does not jump the transition.
		setTimeout(function () {
			$focus.trigger('focus');
		}, 50);
	}

	function closeDrawer(skipFocus) {
		if (!openName) {
			return;
		}
		$('[data-drawer="' + openName + '"]').removeClass('is-open');
		$('[data-drawer-open="' + openName + '"]').attr('aria-expanded', 'false');
		$overlay.removeClass('is-open');
		$body.removeClass('drawer-open');
		openName = null;
		if (!skipFocus && lastFocus) {
			$(lastFocus).trigger('focus');
		}
	}

	$(document).on('click', '[data-drawer-open]', function (e) {
		var name = $(this).attr('data-drawer-open');
		// On cart/checkout the link should simply go there.
		if (name === 'cart' && $body.is('.woocommerce-cart, .woocommerce-checkout')) {
			return;
		}
		e.preventDefault();
		openDrawer(name);
	});

	$(document).on('click', '[data-drawer-close]', function () {
		closeDrawer();
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			closeDrawer();
		}
	});

	// Woo fires this after any AJAX add to cart.
	$(document.body).on('added_to_cart', function () {
		openDrawer('cart');
	});

	/* ---------- 1b. Home product tabs ---------- */

	$(document).on('click', '[data-tabs] [role="tab"]', function () {
		var $tab = $(this);
		var $tabs = $tab.closest('[data-tabs]');

		$tabs.find('[role="tab"]').attr('aria-selected', 'false');
		$tab.attr('aria-selected', 'true');
		$tabs.find('[role="tabpanel"]').prop('hidden', true);
		$('#' + $tab.attr('aria-controls')).prop('hidden', false);
	});

	/* ---------- 2. Size swatches ---------- */

	function initSwatches($form) {
		$form.find('.variations select').each(function () {
			var $select = $(this);
			if ($select.data('swatches')) {
				return;
			}

			var $box = $('<div class="swatches" role="group"></div>');
			$select.find('option').each(function () {
				if (!this.value) {
					return;
				}
				$('<button type="button" class="swatch"></button>')
					.text($(this).text())
					.attr('data-value', this.value)
					.appendTo($box);
			});

			$select.data('swatches', $box).after($box).closest('td').addClass('has-swatches');

			$box.on('click', '.swatch', function () {
				if (this.disabled) {
					return;
				}
				$select.val($(this).attr('data-value')).trigger('change');
			});
		});

		syncSwatches($form);
	}

	function syncSwatches($form) {
		$form.find('.variations select').each(function () {
			var $select = $(this);
			var $box = $select.data('swatches');
			if (!$box) {
				return;
			}

			// Woo removes options that no longer fit the other selections.
			var available = {};
			$select.find('option').each(function () {
				available[this.value] = true;
			});

			$box.find('.swatch').each(function () {
				var value = $(this).attr('data-value');
				var selected = $select.val() === value;
				$(this)
					.prop('disabled', !available[value])
					.toggleClass('is-selected', selected)
					.attr('aria-pressed', selected ? 'true' : 'false');
			});
		});
	}

	$('form.variations_form').each(function () {
		initSwatches($(this));
	});

	$(document).on(
		'woocommerce_update_variation_values reset_data found_variation change',
		'form.variations_form',
		function () {
			syncSwatches($(this));
		}
	);

	/* ---------- 3. AJAX add to cart (single product) ---------- */

	$(document).on('submit', 'form.cart', function (e) {
		var $form = $(this);

		// Let Woo handle external (GET) products and grouped products normally.
		if (($form.attr('method') || '').toLowerCase() !== 'post' || $form.find('input.qty[name^="quantity["]').length) {
			return;
		}

		e.preventDefault();

		var $btn = $form.find('.single_add_to_cart_button').first();
		var data = $form.serializeArray();
		var hasId = data.some(function (field) {
			return field.name === 'add-to-cart';
		});
		if (!hasId && $btn.val()) {
			data.push({ name: 'add-to-cart', value: $btn.val() });
		}

		$btn.addClass('is-loading');
		$form.closest('.summary').find('.begentle-ajax-notice').remove();

		$.post($form.attr('action') || window.location.href, data)
			.done(function (html) {
				var $error = $('<div>').append($.parseHTML(html)).find('.woocommerce-error').first();
				if ($error.length) {
					$('<div class="begentle-ajax-notice"></div>').append($error).insertBefore($form);
					return;
				}
				$(document.body).trigger('wc_fragment_refresh');
				openDrawer('cart');
			})
			.fail(function () {
				// Network/server problem: fall back to a normal (non-AJAX) submit.
				// Native submit() does not fire our handler again; the button's own
				// name/value is not sent by it, so add the product id explicitly.
				if (!hasId && $btn.val()) {
					$('<input type="hidden" name="add-to-cart">').val($btn.val()).appendTo($form);
				}
				$form[0].submit();
			})
			.always(function () {
				$btn.removeClass('is-loading');
			});
	});

	/* ---------- 3b. Shop filter form ---------- */

	// WooCommerce reads sizes/colours as one comma list (filter_size=28,30), so join the
	// checkboxes into that, and keep empty fields out of the URL.
	$(document).on('submit', '.filter-form', function () {
		var $form = $(this);

		$form.find('[data-attr]').each(function () {
			var $group = $(this);
			var values = $group
				.find('input[type="checkbox"]:checked')
				.map(function () {
					return this.value;
				})
				.get();

			if (values.length) {
				$('<input type="hidden">')
					.attr('name', 'filter_' + $group.attr('data-attr'))
					.val(values.join(','))
					.appendTo($form);
			}
			$group.find('input').prop('disabled', true);
		});

		$form.find('input[type="number"]').filter(function () {
			return this.value === '';
		}).prop('disabled', true);
	});

	// Back button restores the page from cache with our fields still disabled.
	$(window).on('pageshow', function (e) {
		if (e.originalEvent && e.originalEvent.persisted) {
			$('.filter-form input').prop('disabled', false);
			$('.filter-form input[type="hidden"][name^="filter_"]').remove();
		}
	});

	/* ---------- 4. Sticky add-to-cart bar ---------- */

	var $bar = $('#atc-bar');
	var $mainBtn = $('form.cart .single_add_to_cart_button').first();

	if ($bar.length && $mainBtn.length) {
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				$bar.toggleClass('is-visible', !entries[0].isIntersecting);
			}).observe($mainBtn[0]);
		}

		$bar.on('click', 'button', function () {
			if ($mainBtn.is('.disabled')) {
				// Variation not chosen yet: take the user to the size picker.
				var top = $mainBtn.closest('form.cart').offset().top - 90;
				window.scrollTo({ top: top, behavior: 'smooth' });
				return;
			}
			$mainBtn.trigger('click');
		});
	}
})(jQuery);
