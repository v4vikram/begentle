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

	// Woo fires this after any AJAX add to cart. Adds from a product card stay on the page
	// (icon turns filled + a toast) so browsing isn't interrupted.
	$(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
		if ($button && $button.closest && $button.closest('.card-actions').length) {
			return;
		}
		openDrawer('cart');
	});

	/* ---------- 1a. Toast, card quick add, share ---------- */

	var cartParams = window.begentleCart || { i18n: {} };
	var toastTimer = null;

	function toast(message, withCartLink) {
		var $toast = $('#begentle-toast');
		if (!$toast.length) {
			$toast = $('<div id="begentle-toast" class="toast" role="status" aria-live="polite"></div>').appendTo('body');
		}
		$toast.empty().append($('<span></span>').text(message));
		if (withCartLink) {
			$('<button type="button" class="toast__link" data-drawer-open="cart"></button>')
				.text(cartParams.i18n.view || 'View bag')
				.appendTo($toast);
		}
		$toast.addClass('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			$toast.removeClass('is-visible');
		}, 3500);
	}

	// Fill the bag icon on every card whose product is in the cart (ids come from a cart fragment).
	function syncCardCart() {
		var ids = ($('#begentle-cart-ids').attr('data-ids') || '').split(',');
		$('.card-cart[data-product-id]').each(function () {
			$(this).toggleClass('is-added', ids.indexOf($(this).attr('data-product-id')) !== -1);
		});
	}

	$(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart', function () {
		setTimeout(syncCardCart, 0);
	});

	function closeQuickAdd() {
		$('.quick-add:not([hidden])').attr('hidden', true);
		$('[data-quick-add][aria-expanded="true"]').attr('aria-expanded', 'false');
	}

	function cardAdd(productId, $button, $cartIcon) {
		if (!cartParams.addUrl) {
			return false;
		}
		$button.addClass('is-loading');

		$.post(cartParams.addUrl.toString().replace('%%endpoint%%', 'add_to_cart'), { product_id: productId, quantity: 1 })
			.done(function (res) {
				if (!res || res.error || !res.fragments) {
					if (res && res.product_url) {
						window.location.href = res.product_url;
					} else {
						toast(cartParams.i18n.error || 'Error');
					}
					return;
				}
				$.each(res.fragments, function (key, value) {
					$(key).replaceWith(value);
				});
				$(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, $cartIcon]);
				$cartIcon.addClass('is-added is-pop');
				setTimeout(function () {
					$cartIcon.removeClass('is-pop');
				}, 400);
				closeQuickAdd();
				toast(cartParams.i18n.added || 'Added', true);
			})
			.fail(function () {
				toast(cartParams.i18n.error || 'Error');
			})
			.always(function () {
				$button.removeClass('is-loading');
			});

		return true;
	}

	// Simple product: add straight away (the href still works without JS).
	$(document).on('click', 'a.card-cart[data-product-id]', function (e) {
		if (/[?&]add-to-cart=/.test($(this).attr('href') || '') && cardAdd($(this).attr('data-product-id'), $(this), $(this))) {
			e.preventDefault();
		}
	});

	// Variable product: open the size chips, then add the picked variation.
	$(document).on('click', '[data-quick-add]', function (e) {
		e.stopPropagation();
		var $panel = $('#' + $(this).attr('aria-controls'));
		var open = $panel.is('[hidden]');
		closeQuickAdd();
		if (open) {
			$panel.removeAttr('hidden');
			$(this).attr('aria-expanded', 'true');
			$panel.find('.quick-add__opt:not(:disabled)').first().trigger('focus');
		}
	});

	$(document).on('click', '.quick-add__opt', function (e) {
		e.stopPropagation();
		var $cartIcon = $(this).closest('.card-actions').find('.card-cart');
		cardAdd($(this).attr('data-add-id'), $(this), $cartIcon);
	});

	$(document).on('click', function (e) {
		if (!$(e.target).closest('.quick-add').length) {
			closeQuickAdd();
		}
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && $('.quick-add:not([hidden])').length) {
			var $trigger = $('[data-quick-add][aria-expanded="true"]');
			closeQuickAdd();
			$trigger.trigger('focus');
		}
	});

	// Bag drawer: − / + per item. The number changes at once; the server gets the final
	// value after a short pause, then the whole mini cart is refreshed from its fragments.
	var qtyTimers = {};

	function sendQty($wrap, key, qty) {
		var $content = $wrap.closest('.widget_shopping_cart_content').addClass('is-updating');

		$.post(cartParams.qtyUrl, { key: key, quantity: qty, nonce: $wrap.attr('data-nonce') })
			.done(function (res) {
				if (!res || !res.fragments) {
					toast((res && res.data && res.data.message) || cartParams.i18n.error || 'Error');
					$(document.body).trigger('wc_fragment_refresh');
					return;
				}
				var scroll = $('.woocommerce-mini-cart').scrollTop();
				$.each(res.fragments, function (selector, html) {
					$(selector).replaceWith(html);
				});
				$('.woocommerce-mini-cart').scrollTop(scroll);
				$(document.body).trigger('wc_fragments_refreshed');
			})
			.fail(function (xhr) {
				var res = xhr.responseJSON;
				toast((res && res.data && res.data.message) || cartParams.i18n.error || 'Error');
				$(document.body).trigger('wc_fragment_refresh');
			})
			.always(function () {
				$content.removeClass('is-updating');
			});
	}

	$(document).on('click', '.mini-qty__btn', function () {
		var $wrap = $(this).closest('.mini-qty');
		var key = $wrap.attr('data-key');
		var max = parseInt($wrap.attr('data-max'), 10);
		var $value = $wrap.find('.mini-qty__value');
		var next = Math.max(1, (parseInt($value.text(), 10) || 1) + parseInt($(this).attr('data-step'), 10));

		if (max > 0) {
			next = Math.min(max, next);
		}
		$value.text(next);
		$wrap.find('[data-step="-1"]').prop('disabled', next <= 1);
		$wrap.find('[data-step="1"]').prop('disabled', max > 0 && next >= max);

		clearTimeout(qtyTimers[key]);
		qtyTimers[key] = setTimeout(function () {
			sendQty($wrap, key, next);
		}, 400);
	});

	// Native share sheet where available, otherwise copy the link.
	$(document).on('click', '[data-share-url]', function () {
		var url = $(this).attr('data-share-url');
		var title = $(this).attr('data-share-title') || document.title;

		if (navigator.share) {
			navigator.share({ title: title, url: url }).catch(function () {});
			return;
		}
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(url).then(function () {
				toast(cartParams.i18n.copied || 'Link copied');
			});
			return;
		}
		window.prompt(title, url);
	});

	syncCardCart();

	// Product page: put the share icon on the main image, next to Woo's zoom button.
	$('.gallery-share').each(function () {
		var $gallery = $('.woocommerce-product-gallery').first();
		if ($gallery.length) {
			$(this).appendTo($gallery).removeAttr('hidden');
		}
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

	/* ---------- 1c. Quantity − / + ---------- */

	function qtyLimits($input) {
		var min = parseFloat($input.attr('min'));
		var max = parseFloat($input.attr('max'));
		return {
			min: isNaN(min) ? 1 : min,
			max: isNaN(max) || max <= 0 ? Infinity : max,
			step: parseFloat($input.attr('step')) || 1,
		};
	}

	function syncQty($input) {
		var limits = qtyLimits($input);
		var value = parseFloat($input.val()) || 0;
		var $box = $input.closest('.quantity');
		$box.find('[data-qty="minus"]').prop('disabled', value <= limits.min);
		$box.find('[data-qty="plus"]').prop('disabled', value >= limits.max);
	}

	$(document).on('click', '.qty-btn', function () {
		var $input = $(this).closest('.quantity').find('input.qty');
		if (!$input.length || $input.prop('readonly')) {
			return;
		}
		var limits = qtyLimits($input);
		var value = parseFloat($input.val()) || 0;
		value += $(this).attr('data-qty') === 'plus' ? limits.step : -limits.step;
		value = Math.min(limits.max, Math.max(limits.min, value));
		// Round away float noise from decimal steps (0.1 + 0.2).
		$input.val(parseFloat(value.toFixed(6))).trigger('change');
	});

	$(document).on('change input', '.quantity input.qty', function () {
		syncQty($(this));
	});

	// Variations change min/max (stock) after a size is picked.
	$(document).on('found_variation reset_data', 'form.variations_form', function () {
		var $input = $(this).find('input.qty');
		setTimeout(function () {
			syncQty($input);
		}, 0);
	});

	$('.quantity input.qty').each(function () {
		syncQty($(this));
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
