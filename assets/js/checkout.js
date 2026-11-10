/* Block checkout: tell the server which payment method is picked, so the COD fee shows or goes away. */
(function () {
	'use strict';

	function start() {
		var data = window.wp && window.wp.data;
		var blocks = window.wc && window.wc.blocksCheckout;

		if (!data || !blocks || !blocks.extensionCartUpdate || !data.select('wc/store/payment')) {
			return false;
		}

		var last = null;

		data.subscribe(function () {
			var store = data.select('wc/store/payment');
			var method = store && store.getActivePaymentMethod ? store.getActivePaymentMethod() : '';

			if (!method || method === last) {
				return;
			}
			last = method;

			blocks.extensionCartUpdate({ namespace: 'begentle-payment', data: { method: method } });
		});

		return true;
	}

	// The checkout block scripts can load after this file: retry for a few seconds.
	var tries = 0;
	(function wait() {
		if (start() || ++tries > 40) {
			return;
		}
		setTimeout(wait, 150);
	})();
})();
