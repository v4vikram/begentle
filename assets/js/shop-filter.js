/**
 * Begentle AJAX shop filters. Vanilla JS, progressive enhancement:
 * every filter, sort, category pill and page link still works as a normal link/form without JS.
 *
 * - Filters in the drawer apply live (debounced); the "Show N results" button closes the drawer.
 * - Sorting, pagination, category pills and the "remove filter" chips swap the product area only.
 * - Server returns a small fragment when the URL has ?bg_partial=1 (see woocommerce.php).
 * - History (back/forward) is kept, no full page reload, previous requests are aborted.
 */
(function () {
	'use strict';

	var root = document.getElementById('shop-results');

	if (!root || !window.fetch || !window.DOMParser || !window.URL) {
		return;
	}

	var top = document.getElementById('catalog-top');
	var statusEl = document.getElementById('shop-status');
	var cache = {};
	var ctrl = null;
	var seq = 0;
	var liveTimer = null;
	var livePushed = false;
	var current = norm(location.href);

	history.replaceState({ bg: 1 }, '', location.href);

	/* ---------- helpers ---------- */

	function norm(url) {
		var u = new URL(url, location.href);
		u.hash = '';
		u.searchParams.delete('bg_partial');
		return u.pathname + (u.search || '');
	}

	function filterForm() {
		return document.querySelector('.filter-form');
	}

	/** Build the listing URL from the filter drawer, in WooCommerce's own query format. */
	function formUrl(form) {
		var u = new URL(form.getAttribute('action') || location.pathname, location.href);
		u.search = '';
		var p = u.searchParams;

		form.querySelectorAll('input[type="hidden"]').forEach(function (i) {
			if (i.name && i.value && i.name.indexOf('filter_') !== 0) {
				p.set(i.name, i.value);
			}
		});

		['min_price', 'max_price'].forEach(function (name) {
			var el = form.querySelector('input[name="' + name + '"]');
			if (el && el.value !== '') {
				p.set(name, el.value);
			}
		});

		form.querySelectorAll('[data-attr]').forEach(function (group) {
			var values = Array.prototype.map.call(group.querySelectorAll('input[type="checkbox"]:checked'), function (c) {
				return c.value;
			});
			if (values.length) {
				p.set('filter_' + group.getAttribute('data-attr'), values.join(','));
			}
		});

		['in_stock', 'on_sale'].forEach(function (name) {
			var el = form.querySelector('input[name="' + name + '"]');
			if (el && el.checked) {
				p.set(name, '1');
			}
		});

		return u.pathname + u.search;
	}

	function setBusy(on) {
		root.classList.toggle('is-loading', on);
		root.setAttribute('aria-busy', on ? 'true' : 'false');
		var btn = document.querySelector('[data-results-btn]');
		if (btn) {
			btn.classList.toggle('is-loading', on);
		}
	}

	function updateButton(found) {
		var btn = document.querySelector('[data-results-btn]');
		if (!btn) {
			return;
		}
		var label;
		if (found === 0) {
			label = btn.getAttribute('data-zero');
		} else if (found === 1) {
			label = btn.getAttribute('data-one');
		} else {
			label = (btn.getAttribute('data-tpl') || '%d').replace('%d', found);
		}
		btn.textContent = label;
	}

	function closeFilterDrawer() {
		var closeBtn = document.querySelector('#drawer-filter [data-drawer-close]');
		if (closeBtn && document.getElementById('drawer-filter').classList.contains('is-open')) {
			closeBtn.click();
		}
	}

	/* ---------- loading ---------- */

	function load(url, opts) {
		opts = opts || {};
		var key = norm(url);
		var id = ++seq;

		if (ctrl) {
			ctrl.abort();
		}
		ctrl = 'AbortController' in window ? new AbortController() : null;

		setBusy(true);

		var cached = cache[key];
		var request;

		if (cached && Date.now() - cached.t < 60000) {
			request = Promise.resolve(cached.html);
		} else {
			var u = new URL(url, location.href);
			u.searchParams.set('bg_partial', '1');
			request = fetch(u.pathname + u.search, {
				credentials: 'same-origin',
				signal: ctrl ? ctrl.signal : undefined,
				headers: { 'X-Requested-With': 'begentle' },
			}).then(function (r) {
				if (!r.ok) {
					throw new Error('http ' + r.status);
				}
				return r.text().then(function (html) {
					cache[key] = { html: html, t: Date.now() };
					return html;
				});
			});
		}

		request
			.then(function (html) {
				if (id !== seq) {
					return;
				}
				apply(html, key, opts);
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				if (id === seq) {
					// Anything unexpected: do it the old way, a normal page load.
					window.location.href = key;
				}
			});
	}

	function apply(html, key, opts) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		var newRoot = doc.getElementById('shop-results');

		if (!newRoot) {
			window.location.href = key;
			return;
		}

		root.innerHTML = newRoot.innerHTML;
		var found = parseInt(newRoot.getAttribute('data-found') || '0', 10);
		root.setAttribute('data-found', found);

		var newTop = doc.getElementById('catalog-top');
		if (top && newTop) {
			top.innerHTML = newTop.innerHTML;
		}

		// While the user is ticking chips the drawer already shows the right state: keep it
		// (and its scroll position). For every other change, refresh it from the server.
		if (!opts.live) {
			var newForm = doc.querySelector('.filter-form');
			var oldForm = filterForm();
			if (newForm && oldForm) {
				oldForm.replaceWith(document.importNode(newForm, true));
			}
		}
		updateButton(found);

		var titleEl = doc.querySelector('[data-title]');
		if (titleEl) {
			document.title = titleEl.getAttribute('data-title');
		}

		if (opts.push !== false) {
			if (opts.live && livePushed) {
				history.replaceState({ bg: 1 }, '', key);
			} else {
				history.pushState({ bg: 1 }, '', key);
				livePushed = !!opts.live;
			}
		}
		current = key;
		setBusy(false);

		if (statusEl) {
			statusEl.textContent = found === 1 ? '1 product' : found + ' products';
		}

		if (opts.scroll) {
			var rect = root.getBoundingClientRect();
			if (rect.top < 0 || rect.top > window.innerHeight * 0.6) {
				root.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}

		document.dispatchEvent(new CustomEvent('begentle:shop-updated'));
	}

	/* ---------- events ---------- */

	// Drawer filters: apply as the user taps, without closing the drawer.
	document.addEventListener('change', function (e) {
		var form = e.target.closest && e.target.closest('.filter-form');
		if (form) {
			clearTimeout(liveTimer);
			liveTimer = setTimeout(function () {
				load(formUrl(form), { live: true });
			}, 300);
			return;
		}

		// Sort dropdown (replaces WooCommerce's own submit handler).
		var sort = e.target.closest && e.target.closest('.woocommerce-ordering select.orderby');
		if (sort) {
			e.stopPropagation();
			var sortForm = sort.form;
			var u = new URL(sortForm.getAttribute('action') || location.pathname, location.href);
			u.search = '';
			new FormData(sortForm).forEach(function (value, name) {
				if (value !== '' && name !== 'paged' && name !== 'product-page') {
					u.searchParams.set(name, value);
				}
			});
			livePushed = false;
			load(u.pathname + u.search, { scroll: true });
		}
	}, true);

	// Price boxes: wait until the user pauses typing.
	document.addEventListener('input', function (e) {
		var el = e.target;
		if (el && el.matches && el.matches('.filter-form input[type="number"]')) {
			clearTimeout(liveTimer);
			liveTimer = setTimeout(function () {
				load(formUrl(el.form), { live: true });
			}, 600);
		}
	});

	// "Show results": the page is already up to date, just close the drawer and look at it.
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.matches || !form.matches('.filter-form')) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		clearTimeout(liveTimer);

		var url = formUrl(form);
		closeFilterDrawer();
		livePushed = false;

		if (norm(url) !== current) {
			load(url, { scroll: true });
		} else {
			root.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}, true);

	// Links that change the listing.
	document.addEventListener('click', function (e) {
		if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
			return;
		}
		var link = e.target.closest && e.target.closest('a[href]');
		if (!link || (link.target && link.target !== '_self')) {
			return;
		}
		if (!link.closest('#shop-results .woocommerce-pagination, #shop-results .active-filters, .cat-pills, .filter-footer')) {
			return;
		}
		var u = new URL(link.href, location.href);
		if (u.origin !== location.origin) {
			return;
		}
		e.preventDefault();
		livePushed = false;
		load(u.pathname + u.search, { scroll: !link.closest('.filter-footer') });
	});

	// Each time the drawer opens, the next change starts a new history entry.
	document.addEventListener('click', function (e) {
		if (e.target.closest && e.target.closest('[data-drawer-open="filter"]')) {
			livePushed = false;
		}
	});

	window.addEventListener('popstate', function () {
		if (norm(location.href) !== current) {
			load(location.href, { push: false, scroll: false });
		}
	});

	// Back button restoring from the bfcache with fields disabled by the no-JS fallback.
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) {
			document.querySelectorAll('.filter-form input').forEach(function (i) {
				i.disabled = false;
			});
		}
	});
})();
