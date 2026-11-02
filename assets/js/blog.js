/**
 * Blog front-end. Vanilla JS, progressive enhancement: everything still works as plain links/forms.
 *
 * Listing (AJAX): category pills, pagination, sort and search swap #blog-head + #blog-results
 * without a page load. The server returns just the listing for ?bg_partial=1 (inc/blog.php).
 * History is kept, older requests are aborted, any failure falls back to a normal page load.
 *
 * Single post: reading progress, active table-of-contents link, floating "Contents" button.
 * Drawers themselves are handled by main.js.
 */
(function () {
	'use strict';

	/* ================= Listing ================= */

	var results = document.getElementById('blog-results');

	if (results && window.fetch && window.DOMParser && window.URL) {
		var head = document.getElementById('blog-head');
		var statusEl = document.getElementById('blog-status');
		var cache = {};
		var ctrl = null;
		var seq = 0;
		var typingTimer = null;
		var typingPushed = false;

		history.replaceState({ blog: 1 }, '', location.href);

		var norm = function (url) {
			var u = new URL(url, location.href);
			u.hash = '';
			u.searchParams.delete('bg_partial');
			return u.pathname + u.search;
		};

		var searchForm = function () {
			return document.querySelector('.blog-search');
		};

		/** Listing URL from the search box + sort select (the forms' action is the current listing). */
		var controlsUrl = function () {
			var form = searchForm();
			var u = new URL(form.getAttribute('action') || location.pathname, location.href);
			u.search = '';
			var q = form.querySelector('input[name="q"]').value.trim();
			var sort = document.getElementById('blog-sort');
			if (q) {
				u.searchParams.set('q', q);
			}
			if (sort && sort.value !== 'newest') {
				u.searchParams.set('sort', sort.value);
			}
			return u.pathname + u.search;
		};

		var setBusy = function (on) {
			results.classList.toggle('is-loading', on);
			results.setAttribute('aria-busy', on ? 'true' : 'false');
		};

		var apply = function (html, key, opts) {
			var doc = new DOMParser().parseFromString(html, 'text/html');
			var newResults = doc.getElementById('blog-results');

			if (!newResults) {
				window.location.href = key;
				return;
			}

			results.innerHTML = newResults.innerHTML;
			results.setAttribute('data-found', newResults.getAttribute('data-found') || '0');
			document.title = newResults.getAttribute('data-title') || document.title;

			var newHead = doc.getElementById('blog-head');
			if (head && newHead) {
				head.innerHTML = newHead.innerHTML;
			}

			// The controls stay in place (typing keeps focus); only refresh their target and values.
			var newSearch = doc.querySelector('.blog-search');
			var newSort = doc.getElementById('blog-sort');
			var input = searchForm().querySelector('input[name="q"]');
			document.querySelectorAll('.blog-search, .blog-sort').forEach(function (form) {
				if (newSearch) {
					form.setAttribute('action', newSearch.getAttribute('action'));
				}
			});
			if (newSort) {
				document.getElementById('blog-sort').value = newSort.value;
			}
			// Back/forward always restores the box; otherwise leave it alone while the user is in it.
			if (!opts.typing && newSearch && (opts.push === false || document.activeElement !== input)) {
				input.value = newSearch.querySelector('input[name="q"]').value;
			}

			if (opts.push !== false) {
				if (opts.typing && typingPushed) {
					history.replaceState({ blog: 1 }, '', key);
				} else {
					history.pushState({ blog: 1 }, '', key);
				}
				typingPushed = !!opts.typing;
			}

			setBusy(false);

			var found = parseInt(results.getAttribute('data-found'), 10) || 0;
			if (statusEl) {
				statusEl.textContent = found === 1 ? '1 article' : found + ' articles';
			}

			if (opts.scroll) {
				var top = (head || results).getBoundingClientRect().top;
				if (top < 0) {
					(head || results).scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			}
		};

		var load = function (url, opts) {
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
				var u = new URL(key, location.href);
				u.searchParams.set('bg_partial', '1');
				request = fetch(u.pathname + u.search, {
					credentials: 'same-origin',
					signal: ctrl ? ctrl.signal : undefined,
					headers: { 'X-Requested-With': 'begentle' },
				}).then(function (response) {
					if (!response.ok) {
						throw new Error('http ' + response.status);
					}
					return response.text().then(function (html) {
						cache[key] = { html: html, t: Date.now() };
						return html;
					});
				});
			}

			request
				.then(function (html) {
					if (id === seq) {
						apply(html, key, opts);
					}
				})
				.catch(function (err) {
					if (err && err.name === 'AbortError') {
						return;
					}
					if (id === seq) {
						window.location.href = key; // Do it the old way.
					}
				});
		};

		// Category pills, pagination, "Clear" / "See all".
		document.addEventListener('click', function (e) {
			var link = e.target.closest && e.target.closest('#blog-listing .cat-pills a, #blog-listing .pagination a, #blog-listing [data-blog-link]');
			if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
				return;
			}
			var u = new URL(link.href, location.href);
			if (u.origin !== location.origin) {
				return;
			}
			e.preventDefault();

			if (link.hasAttribute('data-blog-clear')) {
				searchForm().querySelector('input[name="q"]').value = '';
			}
			load(link.href, { scroll: true });
		});

		// Sort.
		document.addEventListener('change', function (e) {
			if (e.target && e.target.id === 'blog-sort') {
				load(controlsUrl(), { scroll: true });
			}
		});

		// Search: live while typing (one history entry), immediate on Enter.
		document.addEventListener('input', function (e) {
			if (e.target && e.target.matches && e.target.matches('.blog-search input[name="q"]')) {
				clearTimeout(typingTimer);
				typingTimer = setTimeout(function () {
					load(controlsUrl(), { typing: true });
				}, 350);
			}
		});

		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (form.matches && form.matches('.blog-search, .blog-sort')) {
				e.preventDefault();
				clearTimeout(typingTimer);
				typingPushed = false;
				load(controlsUrl(), { scroll: true });
			}
		});

		window.addEventListener('popstate', function () {
			typingPushed = false;
			load(location.href, { push: false });
		});
	} else {
		// No AJAX support: the sort select still submits on change.
		document.querySelectorAll('select[data-autosubmit]').forEach(function (select) {
			select.addEventListener('change', function () {
				select.form.submit();
			});
		});
	}

	/* ================= Single post ================= */

	var body = document.querySelector('[data-post-body]');
	if (!body) {
		return;
	}

	// Reading progress across the article body.
	var bar = document.querySelector('.read-progress span');
	if (bar) {
		var update = function () {
			var rect = body.getBoundingClientRect();
			var total = rect.height - window.innerHeight;
			var done = total > 0 ? Math.min(1, Math.max(0, -rect.top / total)) : 1;
			bar.style.transform = 'scaleX(' + done + ')';
		};
		window.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);
		update();
	}

	// Picking a section in the phone drawer closes it, then the browser scrolls to the heading.
	document.querySelectorAll('[data-drawer="toc"] a').forEach(function (link) {
		link.addEventListener('click', function () {
			var close = document.querySelector('[data-drawer="toc"] [data-drawer-close]');
			if (close) {
				close.click();
			}
		});
	});

	if (!('IntersectionObserver' in window)) {
		return;
	}

	// Floating button appears once the inline contents box has scrolled away.
	var fab = document.querySelector('.toc-fab');
	var inline = document.querySelector('.toc-inline');
	if (fab && inline) {
		new IntersectionObserver(function (entries) {
			var entry = entries[0];
			fab.classList.toggle('is-visible', !entry.isIntersecting && entry.boundingClientRect.top < 0);
		}).observe(inline);
	}

	// Highlight the section being read in every table of contents.
	var links = document.querySelectorAll('.toc a[href^="#"]');
	var setActive = function (id) {
		links.forEach(function (link) {
			if (link.getAttribute('href') === '#' + id) {
				link.setAttribute('aria-current', 'true');
			} else {
				link.removeAttribute('aria-current');
			}
		});
	};

	var observer = new IntersectionObserver(
		function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					setActive(entry.target.id);
				}
			});
		},
		{ rootMargin: '-80px 0px -70% 0px' }
	);

	body.querySelectorAll('h2[id], h3[id]').forEach(function (heading) {
		observer.observe(heading);
	});
})();
