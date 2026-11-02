/**
 * Begentle live search. Vanilla JS, no dependencies.
 * Suggestions come from /wp-json/begentle/v1/search (thumbnail left, text right), with
 * keyboard support, recent searches, popular products and an in-memory cache.
 */
(function () {
	'use strict';

	var cfg = window.begentleSearch;
	var drawer = document.getElementById('drawer-search');

	if (!cfg || !drawer || !window.fetch) {
		return;
	}

	var form = drawer.querySelector('[data-search-form]');
	var input = form.querySelector('input[name="s"]');
	var clearBtn = drawer.querySelector('[data-search-clear]');
	var panel = drawer.querySelector('[data-search-panel]');
	var status = drawer.querySelector('[data-search-status]');
	var i18n = cfg.i18n;

	var cache = {};
	var timer = null;
	var ctrl = null;
	var seq = 0;
	var active = -1;
	var popularHtml = '';
	var RECENT_KEY = 'begentle_recent_searches';

	/* ---------- helpers ---------- */

	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function highlight(name, q) {
		var safe = esc(name);
		if (!q) {
			return safe;
		}
		var words = q
			.split(' ')
			.filter(Boolean)
			.map(function (w) {
				return esc(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
			});
		if (!words.length) {
			return safe;
		}
		return safe.replace(new RegExp('(' + words.join('|') + ')', 'gi'), '<mark>$1</mark>');
	}

	function getRecent() {
		try {
			var list = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
			return Array.isArray(list) ? list.slice(0, 5) : [];
		} catch (e) {
			return [];
		}
	}

	function saveRecent(q) {
		q = (q || '').trim();
		if (q.length < 2) {
			return;
		}
		try {
			var list = getRecent().filter(function (x) {
				return x.toLowerCase() !== q.toLowerCase();
			});
			list.unshift(q);
			localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, 5)));
		} catch (e) {
			/* storage blocked: ignore */
		}
	}

	function clearRecent() {
		try {
			localStorage.removeItem(RECENT_KEY);
		} catch (e) {
			/* ignore */
		}
	}

	function searchUrl(q) {
		return cfg.shopUrl + '?s=' + encodeURIComponent(q) + '&post_type=product';
	}

	/* ---------- rendering ---------- */

	function row(item, q, idx) {
		return (
			'<a class="s-item" role="option" id="s-opt-' + idx + '" href="' + esc(item.url) + '" data-q="' + esc(q || '') + '">' +
			'<span class="s-thumb"><img src="' + esc(item.img) + '" alt="" width="64" height="64" loading="lazy" decoding="async"></span>' +
			'<span class="s-text">' +
			'<span class="s-name">' + highlight(item.name, q) + '</span>' +
			(item.cat ? '<span class="s-meta">' + esc(item.cat) + '</span>' : '') +
			'<span class="s-price">' + (item.out ? esc(i18n.soldOut) : item.price) + '</span>' +
			'</span></a>'
		);
	}

	function chips(cats) {
		if (!cats || !cats.length) {
			return '';
		}
		return (
			'<div class="s-chips">' +
			cats
				.map(function (c) {
					return '<a href="' + esc(c.url) + '">' + esc(c.name) + '</a>';
				})
				.join('') +
			'</div>'
		);
	}

	function setPanel(html) {
		panel.innerHTML = html;
		active = -1;
		input.removeAttribute('aria-activedescendant');
		panel.scrollTop = 0;
	}

	function say(text) {
		if (status) {
			status.textContent = text;
		}
	}

	function renderIdle(data) {
		var html = '';
		var recent = getRecent();

		if (recent.length) {
			html +=
				'<div class="s-head"><span>' + esc(i18n.recent) + '</span><button type="button" data-recent-clear>' + esc(i18n.clear) + '</button></div>' +
				'<div class="s-chips s-recent">' +
				recent
					.map(function (r) {
						return '<button type="button" data-recent="' + esc(r) + '">' + esc(r) + '</button>';
					})
					.join('') +
				'</div>';
		}

		if (data) {
			popularHtml =
				'<div class="s-head"><span>' + esc(i18n.popular) + '</span></div>' +
				'<div role="listbox">' +
				data.items
					.map(function (item, i) {
						return row(item, '', i);
					})
					.join('') +
				'</div>' +
				chips(data.cats);
		}

		setPanel(html + popularHtml);
	}

	function renderResults(data, q) {
		var html = '';

		if (data.items.length) {
			html +=
				'<div role="listbox">' +
				data.items
					.map(function (item, i) {
						return row(item, q, i);
					})
					.join('') +
				'</div>' +
				'<a class="s-all" role="option" id="s-opt-all" href="' + esc(searchUrl(q)) + '" data-q="' + esc(q) + '">' +
				esc(i18n.seeAll.replace('%s', q)) +
				' <span aria-hidden="true">&rarr;</span></a>';
			say(i18n.found.replace('%d', data.items.length));
		} else {
			html +=
				'<div class="s-empty"><p>' + esc(i18n.noMatch.replace('%s', q)) + '</p>' +
				(data.cats.length ? '<p class="s-sub">' + esc(i18n.tryCats) + '</p>' + chips(data.cats) : '') +
				'</div>';
			say(i18n.noMatch.replace('%s', q));
		}

		setPanel(html);
	}

	/* ---------- data ---------- */

	function request(q) {
		var key = q.toLowerCase();

		if (cache[key]) {
			return Promise.resolve(cache[key]);
		}

		if (ctrl) {
			ctrl.abort();
		}
		ctrl = 'AbortController' in window ? new AbortController() : null;

		return fetch(cfg.url + (cfg.url.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), {
			credentials: 'omit',
			signal: ctrl ? ctrl.signal : undefined,
		})
			.then(function (r) {
				if (!r.ok) {
					throw new Error('http ' + r.status);
				}
				return r.json();
			})
			.then(function (data) {
				cache[key] = data;
				return data;
			});
	}

	function run() {
		var q = input.value.replace(/\s+/g, ' ').trim();
		var id = ++seq;

		clearBtn.hidden = !input.value;
		input.setAttribute('aria-expanded', q.length >= 2 ? 'true' : 'false');

		if (q.length < 2) {
			if (ctrl) {
				ctrl.abort();
			}
			panel.classList.remove('is-loading');
			if (popularHtml || cache['']) {
				renderIdle(cache[''] || null);
			} else {
				loadIdle();
			}
			return;
		}

		panel.classList.add('is-loading');

		request(q)
			.then(function (data) {
				if (id !== seq) {
					return;
				}
				panel.classList.remove('is-loading');
				renderResults(data, q);
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				if (id === seq) {
					panel.classList.remove('is-loading');
					setPanel('<div class="s-empty"><p>' + esc(i18n.error) + '</p></div>');
				}
			});
	}

	function loadIdle() {
		renderIdle(null);
		request('')
			.then(function (data) {
				if (input.value.trim().length < 2) {
					renderIdle(data);
				}
			})
			.catch(function () {
				/* popular list is optional */
			});
	}

	/* ---------- events ---------- */

	input.addEventListener('input', function () {
		clearTimeout(timer);
		clearBtn.hidden = !input.value;
		timer = setTimeout(run, 180);
	});

	clearBtn.addEventListener('click', function () {
		input.value = '';
		run();
		input.focus();
	});

	// Open: load suggestions once, show recent searches.
	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-drawer-open="search"]');
		if (opener) {
			if (input.value.trim().length >= 2) {
				run();
			} else {
				loadIdle();
			}
		}
	});

	panel.addEventListener('click', function (e) {
		var recentBtn = e.target.closest('[data-recent]');
		if (recentBtn) {
			input.value = recentBtn.getAttribute('data-recent');
			run();
			input.focus();
			return;
		}
		if (e.target.closest('[data-recent-clear]')) {
			clearRecent();
			renderIdle(cache[''] || null);
			return;
		}
		var link = e.target.closest('a[data-q]');
		if (link) {
			saveRecent(link.getAttribute('data-q') || input.value);
		}
	});

	form.addEventListener('submit', function (e) {
		var q = input.value.trim();
		var current = panel.querySelector('.is-active[href]');

		if (current) {
			e.preventDefault();
			saveRecent(q);
			window.location.href = current.href;
			return;
		}
		if (q.length < 2) {
			e.preventDefault();
			input.focus();
			return;
		}
		saveRecent(q);
	});

	// Up / down / enter inside the box.
	input.addEventListener('keydown', function (e) {
		var items = panel.querySelectorAll('[role="option"]');

		if (!items.length || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp')) {
			return;
		}
		e.preventDefault();
		active = e.key === 'ArrowDown' ? (active + 1) % items.length : (active - 1 + items.length) % items.length;

		items.forEach(function (el, i) {
			el.classList.toggle('is-active', i === active);
		});
		input.setAttribute('aria-activedescendant', items[active].id);
		items[active].scrollIntoView({ block: 'nearest' });
	});

	// Hide the on-screen keyboard when the user scrolls the results on phones.
	panel.addEventListener(
		'touchmove',
		function () {
			if (document.activeElement === input) {
				input.blur();
			}
		},
		{ passive: true }
	);
})();
