/**
 * MP Sticky Custom Cart — storefront entry (data from {@see wp_localize_script} `mpSccData`).
 */
(function (window, $) {
	'use strict';

	window.mpScc = window.mpScc || {};

	var data = function () {
		return window.mpSccData || {};
	};

	/**
	 * @param {string} key Flag key from persisted flags / mpSccData.flags.
	 * @returns {boolean}
	 */
	window.mpScc.flagEnabled = function (key) {
		var f = data().flags;
		return !!(f && Object.prototype.hasOwnProperty.call(f, key) && f[key]);
	};

	/**
	 * Resolved UI label (aligned with PHP OptionResolver labels).
	 * @param {string} key
	 * @returns {string}
	 */
	window.mpScc.label = function (key) {
		var l = data().labels;
		return l && Object.prototype.hasOwnProperty.call(l, key) ? String(l[key]) : '';
	};

	/**
	 * Value of a runtime CSS variable as emitted for JS (same map as :root inline).
	 * @param {string} name Full name including `--`, e.g. `--mp-scc-sticky-z-index`.
	 * @returns {string}
	 */
	window.mpScc.cssVar = function (name) {
		var c = data().cssVars;
		return c && Object.prototype.hasOwnProperty.call(c, name) ? String(c[name]) : '';
	};

	/**
	 * admin-ajax.php URL, global nonce, and action names for jQuery.post / fetch.
	 * @returns {{ ajaxUrl: string, nonce: string, actions: Record<string, string> }}
	 */
	window.mpScc.ajaxConfig = function () {
		var d = data();
		return {
			ajaxUrl: d.ajaxUrl ? String(d.ajaxUrl) : '',
			nonce: d.nonce ? String(d.nonce) : '',
			actions: d.actions && typeof d.actions === 'object' ? d.actions : {}
		};
	};

	/**
	 * Whether Woo `wc-cart-fragments` was registered when the page was built (for branching).
	 * @returns {boolean}
	 */
	window.mpScc.hasWcCartFragments = function () {
		return !!data().wcCartFragments;
	};

	/**
	 * @param {string} actionKey Key under mpSccData.actions (e.g. `cartSnapshot`).
	 * @param {Record<string, *>} [payload]
	 * @returns {JQuery.jqXHR}
	 */
	window.mpScc.postAjax = function (actionKey, payload) {
		var cfg = window.mpScc.ajaxConfig();
		var action = cfg.actions[actionKey];
		if (!cfg.ajaxUrl || !action) {
			return $.Deferred().reject().promise();
		}
		var body = $.extend({ action: action, _ajax_nonce: cfg.nonce }, payload || {});
		return $.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			data: body,
			dataType: 'json'
		});
	};

	function parseDebounceMs() {
		var raw = window.mpScc.cssVar('--mp-scc-sticky-quantity-debounce-ms') || '';
		var n = parseInt(String(raw).replace(/[^\d]/g, ''), 10);
		return isNaN(n) || n < 0 ? 320 : n;
	}

	/**
	 * Sticky bar + drawer: lifecycle, drawer toggle, cart snapshot UI, debounced qty, request lock.
	 */
	function StickyCartController(root) {
		this.$root = $(root);
		this.$drawer = this.$root.find('[data-mp-scc-drawer]');
		this.$toggle = this.$root.find('[data-mp-scc-drawer-toggle]');
		this.$count = this.$root.find('[data-mp-scc-cart-count]');
		this.$total = this.$root.find('[data-mp-scc-cart-total]');
		this.$items = this.$root.find('[data-mp-scc-drawer-items]');
		this.$empty = this.$root.find('[data-mp-scc-drawer-empty]');
		this.snapshotLocked = false;
		this.pendingRefresh = false;
		this.mutationInFlight = false;
		this.mutationQueue = [];
		this.qtyTimers = {};
		this.pendingQty = {};
		this.debounceMs = parseDebounceMs();
	}

	StickyCartController.prototype.isDrawerOpen = function () {
		var el = this.$drawer.get(0);
		return el ? !el.hasAttribute('hidden') : false;
	};

	StickyCartController.prototype.setDrawerOpen = function (open) {
		var drawer = this.$drawer.get(0);
		var toggle = this.$toggle.get(0);
		if (!drawer || !toggle) {
			return;
		}
		if (open) {
			drawer.removeAttribute('hidden');
			toggle.setAttribute('aria-expanded', 'true');
		} else {
			drawer.setAttribute('hidden', '');
			toggle.setAttribute('aria-expanded', 'false');
		}
	};

	StickyCartController.prototype.toggleDrawer = function () {
		this.setDrawerOpen(!this.isDrawerOpen());
	};

	StickyCartController.prototype.applyPayload = function (p) {
		if (!p || typeof p !== 'object') {
			return;
		}

		var count = typeof p.cart_contents_count === 'number' ? p.cart_contents_count : 0;
		this.$count.text(String(count));

		if (typeof p.subtotal_html === 'string') {
			this.$total.html(p.subtotal_html);
		}

		var empty = !!p.is_empty;
		var items = Array.isArray(p.items) ? p.items : [];

		if (empty || items.length === 0) {
			this.$items.empty();
			this.$empty.removeAttr('hidden');
		} else {
			this.$empty.attr('hidden', 'hidden');
			this.renderLineItems(items);
		}
	};

	StickyCartController.prototype.renderLineItems = function (items) {
		var self = this;
		this.$items.empty();
		items.forEach(function (item) {
			var key = item.key ? String(item.key) : '';
			if (!key) {
				return;
			}
			var qty = typeof item.quantity === 'number' ? item.quantity : parseInt(item.quantity, 10) || 0;
			var $li = $('<li class="mp-scc-line" />').attr('data-cart-item-key', key);

			var $row = $('<div class="mp-scc-line__row" />');
			var $title = item.permalink
				? $('<a class="mp-scc-line__name" />').attr('href', item.permalink).text(item.name || '')
				: $('<span class="mp-scc-line__name" />').text(item.name || '');

			var $qty = $('<div class="mp-scc-line__qty" />');
			var $dec = $('<button type="button" class="mp-scc-qty-btn" data-mp-scc-qty-dec />')
				.attr('aria-label', 'Decrease quantity')
				.text('\u2212');
			var $num = $('<span class="mp-scc-line__qty-val" data-mp-scc-qty-display />').text(String(qty));
			var $inc = $('<button type="button" class="mp-scc-qty-btn" data-mp-scc-qty-inc />')
				.attr('aria-label', 'Increase')
				.text('+');

			$qty.append($dec, $num, $inc);

			var $sub = $('<div class="mp-scc-line__subtotal" />');
			if (typeof item.line_subtotal_html === 'string') {
				$sub.html(item.line_subtotal_html);
			}

			$row.append($('<div class="mp-scc-line__main" />').append($title, $qty), $sub);
			$li.append($row);
			self.$items.append($li);
		});
	};

	StickyCartController.prototype.scheduleQuantityCommit = function (cartItemKey, quantity) {
		var self = this;
		this.pendingQty[cartItemKey] = quantity;
		if (this.qtyTimers[cartItemKey]) {
			clearTimeout(this.qtyTimers[cartItemKey]);
		}
		this.qtyTimers[cartItemKey] = setTimeout(function () {
			delete self.qtyTimers[cartItemKey];
			var q = self.pendingQty[cartItemKey];
			delete self.pendingQty[cartItemKey];
			self.commitQuantity(cartItemKey, q);
		}, this.debounceMs);
	};

	StickyCartController.prototype.commitQuantity = function (cartItemKey, quantity) {
		var self = this;
		var cfg = window.mpScc.ajaxConfig();
		var action = cfg.actions.setLineQuantity;
		if (!cfg.ajaxUrl || !action) {
			return;
		}
		if (this.mutationInFlight) {
			this.mutationQueue.push({ key: cartItemKey, quantity: quantity });
			return;
		}
		this.mutationInFlight = true;
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			data: {
				action: action,
				_ajax_nonce: cfg.nonce,
				cart_item_key: cartItemKey,
				quantity: quantity
			},
			dataType: 'json'
		})
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					self.applyPayload(resp.data);
				}
			})
			.always(function () {
				self.mutationInFlight = false;
				if (self.mutationQueue.length) {
					var next = self.mutationQueue.shift();
					self.commitQuantity(next.key, next.quantity);
				}
			});
	};

	/**
	 * @returns {boolean} True while cart snapshot or quantity mutation AJAX is in flight.
	 */
	StickyCartController.prototype.isLoading = function () {
		return this.snapshotLocked || this.mutationInFlight;
	};

	StickyCartController.prototype.refresh = function () {
		var self = this;
		if (this.snapshotLocked) {
			this.pendingRefresh = true;
			return;
		}
		this.snapshotLocked = true;
		window.mpScc
			.postAjax('cartSnapshot', {})
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					self.applyPayload(resp.data);
				}
			})
			.always(function () {
				self.snapshotLocked = false;
				if (self.pendingRefresh) {
					self.pendingRefresh = false;
					self.refresh();
				}
			});
	};

	StickyCartController.prototype.bind = function () {
		var self = this;

		this.$toggle.on('click', function () {
			self.toggleDrawer();
		});

		this.$root.on('click', '[data-mp-scc-qty-inc]', function (e) {
			var $line = $(e.currentTarget).closest('.mp-scc-line');
			var key = $line.attr('data-cart-item-key');
			if (!key) {
				return;
			}
			var $disp = $line.find('[data-mp-scc-qty-display]');
			var q = parseInt($disp.text(), 10) || 0;
			q += 1;
			$disp.text(String(q));
			self.scheduleQuantityCommit(key, q);
		});

		this.$root.on('click', '[data-mp-scc-qty-dec]', function (e) {
			var $line = $(e.currentTarget).closest('.mp-scc-line');
			var key = $line.attr('data-cart-item-key');
			if (!key) {
				return;
			}
			var $disp = $line.find('[data-mp-scc-qty-display]');
			var q = parseInt($disp.text(), 10) || 0;
			q = Math.max(0, q - 1);
			$disp.text(String(q));
			self.scheduleQuantityCommit(key, q);
		});

		var wooRefresh = function () {
			self.refresh();
		};
		$(document.body).on(
			'added_to_cart removed_from_cart wc_fragments_refreshed updated_cart_totals',
			wooRefresh
		);
	};

	StickyCartController.prototype.init = function () {
		this.bind();
		this.refresh();
	};

	$(function () {
		var $root = $('#mp-scc-sticky-root');
		if ($root.length) {
			var sticky = new StickyCartController($root[0]);
			sticky.init();
			window.mpScc.sticky = sticky;
		}

		$(window.document).trigger('mpScc:ready');
	});
})(window, window.jQuery);
