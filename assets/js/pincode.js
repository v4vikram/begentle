/**
 * India PIN code -> City + State auto-fill.
 *
 * - Block checkout: watches the address in the wc/store/cart data store and updates it there
 *   (its inputs are React-controlled, so writing to the DOM would be undone).
 * - Classic forms (My Account > Addresses): plain inputs + select2 state.
 *
 * State is always set from the PIN. City is only filled when empty, or when it still holds the
 * value we filled before, so anything the customer typed is never overwritten.
 * Lookups read small static JSON shards (assets/pincodes/NN.json), cached per page.
 */
(function () {
	'use strict';

	var cfg = window.begentlePincode;
	if (!cfg || !window.fetch) {
		return;
	}

	var shards = {};

	function clean(pin) {
		return String(pin || '').replace(/\s+/g, '');
	}

	function lookup(pin) {
		if (!/^[1-9]\d{5}$/.test(pin)) {
			return Promise.resolve(null);
		}
		var key = pin.slice(0, 2);
		if (!shards[key]) {
			shards[key] = fetch(cfg.base + key + '.json', { credentials: 'same-origin' })
				.then(function (r) {
					return r.ok ? r.json() : {};
				})
				.catch(function () {
					delete shards[key]; // Try again next time.
					return {};
				});
		}
		return shards[key].then(function (map) {
			return map[pin] || null; // [city, stateCode]
		});
	}

	/* ---------- Block checkout ---------- */

	function initBlocks() {
		var data = window.wp && window.wp.data;
		if (!data || !data.select('wc/store/cart')) {
			return false;
		}

		var store = 'wc/store/cart';
		var seen = { shipping: null, billing: null };
		var autoCity = { shipping: '', billing: '' };

		var check = function (type) {
			var address = data.select(store).getCustomerData()[type + 'Address'];
			if (!address) {
				return;
			}
			var pin = clean(address.postcode);
			var stamp = address.country + ':' + pin;
			if (stamp === seen[type]) {
				return;
			}
			seen[type] = stamp;
			if (address.country !== 'IN') {
				return;
			}

			lookup(pin).then(function (hit) {
				if (!hit) {
					return;
				}
				var now = data.select(store).getCustomerData()[type + 'Address'];
				if (clean(now.postcode) !== pin || now.country !== 'IN') {
					return; // Changed while we were loading.
				}

				var update = {};
				if (now.state !== hit[1]) {
					update.state = hit[1];
				}
				if ((!now.city || now.city === autoCity[type]) && now.city !== hit[0]) {
					update.city = hit[0];
				}
				if (!now.city || now.city === autoCity[type]) {
					autoCity[type] = hit[0];
				}
				if (!Object.keys(update).length) {
					return;
				}

				var action = type === 'shipping' ? 'setShippingAddress' : 'setBillingAddress';
				data.dispatch(store)[action](Object.assign({}, now, update));
			});
		};

		data.subscribe(function () {
			check('shipping');
			check('billing');
		});
		return true;
	}

	/* ---------- Classic forms ---------- */

	function initClassic() {
		var autoCity = {};

		document.addEventListener('input', function (e) {
			var input = e.target;
			if (!input || !input.matches || !input.matches('#billing_postcode, #shipping_postcode')) {
				return;
			}
			var type = input.id.split('_')[0];
			var country = document.getElementById(type + '_country');
			var pin = clean(input.value);

			if ((country && country.value !== 'IN') || pin.length !== 6) {
				return;
			}

			lookup(pin).then(function (hit) {
				if (!hit || clean(input.value) !== pin) {
					return;
				}
				var city = document.getElementById(type + '_city');
				var state = document.getElementById(type + '_state');

				if (city && (!city.value || city.value === autoCity[type])) {
					city.value = hit[0];
					autoCity[type] = hit[0];
					city.dispatchEvent(new Event('change', { bubbles: true }));
				}
				if (state && state.value !== hit[1]) {
					state.value = hit[1];
					if (window.jQuery) {
						window.jQuery(state).trigger('change'); // select2 + WooCommerce listeners.
					} else {
						state.dispatchEvent(new Event('change', { bubbles: true }));
					}
				}
			});
		});
	}

	if (!initBlocks()) {
		initClassic();
	}
})();
