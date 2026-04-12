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

	/**
	 * Fire-and-forget client diagnostics (respects `diagnostics.client_error_logging` on the server).
	 * @param {string} event Sanitized key, e.g. `catalog_image_out_of_stock`.
	 * @param {Record<string, *>} [payload]
	 */
	window.mpScc.logClientEvent = function (event, payload) {
		var cfg = window.mpScc.ajaxConfig();
		if (!cfg.ajaxUrl || !cfg.actions.logClientEvent) {
			return;
		}
		window.mpScc
			.postAjax('logClientEvent', $.extend({ event: event }, payload || {}))
			.fail(function () {});
	};

	function parseDebounceMs() {
		var raw = window.mpScc.cssVar('--mp-scc-sticky-quantity-debounce-ms') || '';
		var n = parseInt(String(raw).replace(/[^\d]/g, ''), 10);
		return isNaN(n) || n < 0 ? 320 : n;
	}

	function resolveCatalogProductId($card) {
		var $btn = $card.find('.add_to_cart_button[data-product_id]').first();
		if ($btn.length) {
			var id = parseInt($btn.attr('data-product_id'), 10);
			if (!isNaN(id) && id > 0) {
				return id;
			}
		}
		var $hrefBtn = $card.find('a[href*="add-to-cart="]').first();
		var href = $hrefBtn.attr('href') || '';
		var m = href.match(/[?&]add-to-cart=(\d+)/);
		if (m) {
			return parseInt(m[1], 10);
		}
		var cls = $card.attr('class') || '';
		var m2 = cls.match(/\bpost-(\d+)\b/);
		if (m2) {
			return parseInt(m2[1], 10);
		}
		var $anyId = $card.find('[data-product_id]').first();
		if ($anyId.length) {
			var id0 = parseInt($anyId.attr('data-product_id'), 10);
			if (!isNaN(id0) && id0 > 0) {
				return id0;
			}
		}
		return 0;
	}

	var CATALOG_ATC_POST_SUCCESS_COOLDOWN_MS = 480;

	var CATALOG_STOCK_TOAST_COOLDOWN_MS = 3600;

	/**
	 * Heuristic: href points to a normal product/single page (plain, pretty, or ?p=ID permalinks).
	 * @param {string} href
	 * @param {number} productId Resolved Woo product ID (0 if unknown).
	 * @returns {boolean}
	 */
	function hrefLooksLikeProductPage(href, productId) {
		if (!href || typeof href !== 'string') {
			return false;
		}
		var trimmed = href.trim();
		if (
			!trimmed ||
			trimmed.indexOf('javascript:') === 0 ||
			trimmed.indexOf('data:') === 0 ||
			trimmed === '#'
		) {
			return false;
		}
		try {
			var u = new URL(trimmed, window.location.href);
			if (u.protocol !== 'http:' && u.protocol !== 'https:') {
				return false;
			}
			var qp = u.searchParams.get('p');
			if (qp && productId > 0 && parseInt(qp, 10) === productId) {
				return true;
			}
			if (/\/product\//i.test(u.pathname) || /\/shop\//i.test(u.pathname)) {
				return true;
			}
			if (u.pathname && u.pathname !== '/' && u.pathname.toLowerCase().indexOf('add-to-cart') === -1) {
				return true;
			}
			return false;
		} catch (err) {
			return false;
		}
	}

	/**
	 * First anchor in the card that looks like the product permalink (title link or LoopProduct link).
	 * @param {JQuery} $card
	 * @param {Record<string, *>} catalog mpSccData.catalog
	 * @returns {JQuery}
	 */
	function findCatalogPermalinkAnchor($card, catalog) {
		var list = catalog.titleLinkSelectors || [];
		for (var i = 0; i < list.length; i++) {
			var $a = $card.find(list[i]).first();
			if ($a.length && $a.attr('href')) {
				return $a;
			}
		}
		return $();
	}

	/**
	 * @param {JQuery} $card
	 * @param {Record<string, *>} catalog
	 * @returns {JQuery} Host that wraps the thumbnail (used to detect non-standard loops).
	 */
	function findCatalogOverlayThumbnailHost($card, catalog) {
		var list = catalog.overlayHostSelectors || [];
		var i;
		for (i = 0; i < list.length; i++) {
			var $h = $card.find(list[i]).first();
			if ($h.length && $h.find('img').length) {
				return $h;
			}
		}
		return $();
	}

	/**
	 * Place the overlay as a bottom band over the first product image (sibling inside the card, no nested anchors).
	 * @param {JQuery} $card
	 * @param {JQuery} $overlay
	 */
	function layoutCatalogMoreInfoOverlayBand($card, $overlay) {
		var $img = $card.find('img').first();
		if (!$img.length) {
			return;
		}
		var imgEl = $img.get(0);
		var cardEl = $card.get(0);
		function sync() {
			if (!imgEl || !cardEl || !$overlay.parent().length) {
				return;
			}
			var io = $img.offset();
			var co = $card.offset();
			if (!io || !co) {
				return;
			}
			var ih = $img.outerHeight();
			var band = Math.max(40, Math.min(56, Math.round(ih * 0.26)));
			var topRel = io.top - co.top + ih - band;
			var leftRel = io.left - co.left;
			$overlay.css({
				top: topRel,
				left: leftRel,
				width: $img.outerWidth(),
				height: band
			});
		}
		sync();
		var ro = $card.data('mpSccOverlayRo');
		if (ro && typeof ro.disconnect === 'function') {
			ro.disconnect();
		}
		if (window.ResizeObserver && cardEl && imgEl) {
			ro = new ResizeObserver(sync);
			ro.observe(cardEl);
			ro.observe(imgEl);
			$card.data('mpSccOverlayRo', ro);
		}
	}

	/**
	 * target="_blank" + rel when opening in a new tab (admin: catalog.more_info_new_tab).
	 * @param {JQuery} $a
	 * @param {boolean} newTab
	 */
	function applyMoreInfoLinkAttrs($a, newTab) {
		if (newTab) {
			$a.attr({ target: '_blank', rel: 'noopener noreferrer' });
		} else {
			$a.removeAttr('target');
			$a.removeAttr('rel');
		}
	}

	/**
	 * Space activates the link like a button (Enter is native for {@see HTMLAnchorElement}).
	 */
	function initCatalogOverlayKeyboard() {
		$(document.body).on('keydown.mpSccOverlayKb', 'a.mp-scc-catalog-overlay', function (e) {
			var code = e.keyCode || 0;
			var key = e.key || '';
			if (key !== ' ' && key !== 'Spacebar' && code !== 32) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			if (e.currentTarget && typeof e.currentTarget.click === 'function') {
				e.currentTarget.click();
			}
		});
	}

	/**
	 * Injects the «Подробнее» band + wires layout when {@see FeatureFlagsDefaults::KEY_HOVER_MORE_INFO_ENABLED} is on.
	 * Refreshes {@see href} when the loop is replaced by layered nav / AJAX filters.
	 */
	function initCatalogMoreInfoOverlay() {
		if (!window.mpScc.flagEnabled('hover_more_info_enabled')) {
			return;
		}
		var catalog = data().catalog || {};
		var cardSel = catalog.cardRootSelector || 'li.product';
		var label = window.mpScc.label('more_info');
		if (!String(label || '').trim()) {
			label = 'Подробнее о товаре';
		}
		var mobileAlways = !!catalog.hoverOverlayMobileAlways;
		var motion = catalog.hoverMotionPreset || 'fade_slide';
		if (motion !== 'fade' && motion !== 'slide' && motion !== 'fade_slide') {
			motion = 'fade_slide';
		}
		var newTab = !!catalog.moreInfoNewTab;

		$(cardSel).each(function () {
			var $card = $(this);
			if (!$card.find('img').length) {
				return;
			}
			var $perm = findCatalogPermalinkAnchor($card, catalog);
			if (!$perm.length) {
				return;
			}
			var productHref = $perm.attr('href');
			if (!productHref || productHref === '#') {
				return;
			}

			var $existing = $card.find('.mp-scc-catalog-overlay').first();
			if ($existing.length) {
				if ($existing.attr('href') !== productHref) {
					$existing.attr('href', productHref);
				}
				applyMoreInfoLinkAttrs($existing, newTab);
				return;
			}

			if (!findCatalogOverlayThumbnailHost($card, catalog).length) {
				$card.addClass('mp-scc-catalog-card--overlay-fallback');
			}

			var cls =
				'mp-scc-catalog-overlay mp-scc-catalog-overlay--floating mp-scc-catalog-overlay--motion-' + motion;
			if (mobileAlways) {
				cls += ' mp-scc-catalog-overlay--mobile-always';
			}

			var $ov = $('<a />', {
				class: cls,
				href: productHref,
				'data-mp-scc-overlay': ''
			});
			applyMoreInfoLinkAttrs($ov, newTab);
			if (label) {
				$ov.attr('aria-label', label);
			}
			$ov.append($('<span class="mp-scc-catalog-overlay__label" />').text(label));
			$card.append($ov);
			layoutCatalogMoreInfoOverlayBand($card, $ov);
		});
	}

	/**
	 * Enables wishlist coexistence CSS ({@see assets/css/frontend.css}) when the feature flag is on.
	 */
	function initWishlistIntegrationBodyClass() {
		if (!window.mpScc.flagEnabled('wishlist_icon_integration_enabled')) {
			return;
		}
		document.body.classList.add('mp-scc-wishlist-integration-on');
	}

	/**
	 * Append ?mp_scc_wishlist_debug=1 to catch duplicate wishlist widgets in one card (theme/plugin issue).
	 */
	function warnDuplicateWishlistButtonsInCard() {
		if (!window.mpScc.flagEnabled('wishlist_icon_integration_enabled')) {
			return;
		}
		if (window.location.search.indexOf('mp_scc_wishlist_debug=1') === -1) {
			return;
		}
		var catalog = data().catalog || {};
		var cardSel = catalog.cardRootSelector || 'li.product';
		var sel =
			'.yith-wcwl-add-button, a.tinvwl_add_to_wishlist_button, .woosw-btn';
		$(cardSel).each(function () {
			var n = $(this).find(sel).length;
			if (n > 1) {
				window.console.warn('[mp-scc] Multiple wishlist controls in one product card', this);
			}
		});
	}

	var catalogOverlayInitTimer = null;
	function scheduleCatalogMoreInfoOverlay() {
		if (catalogOverlayInitTimer) {
			clearTimeout(catalogOverlayInitTimer);
		}
		catalogOverlayInitTimer = window.setTimeout(function () {
			catalogOverlayInitTimer = null;
			initCatalogMoreInfoOverlay();
		}, 80);
	}

	/**
	 * Optional dev aid: append ?mp_scc_catalog_debug=1 to the shop URL to log suspicious title links.
	 */
	function runCatalogTitleLinkSanity() {
		if (window.location.search.indexOf('mp_scc_catalog_debug=1') === -1) {
			return;
		}
		var catalog = data().catalog || {};
		var cardSel = catalog.cardRootSelector || 'li.product';
		$('ul.products')
			.find(cardSel)
			.each(function () {
				var $card = $(this);
				var id = resolveCatalogProductId($card);
				var $a = findCatalogPermalinkAnchor($card, catalog);
				if (!$a.length) {
					return;
				}
				var href = $a.attr('href');
				if (id && href && !hrefLooksLikeProductPage(href, id)) {
					window.console.warn('[mp-scc] Catalog title/permalink link may not look like a product URL', {
						productId: id,
						href: href
					});
				}
			});
	}

	/**
	 * Lets theme/analytics listen without blocking default navigation: $(document.body).on('mpScc:catalogTitleClick', fn).
	 */
	function initCatalogTitleClickHook() {
		var catalog = data().catalog || {};
		var list = catalog.titleAnalyticsSelectors || [];
		if (!list.length) {
			return;
		}
		var delegateSel = list.join(', ');
		$(document.body).on('click.mpSccCatalogTitle', delegateSel, function (e) {
			if (e.button !== 0) {
				return;
			}
			if ($(e.target).closest('img').length) {
				return;
			}
			$(document.body).trigger('mpScc:catalogTitleClick', [e.currentTarget, e]);
		});
	}

	/**
	 * Future overlay / "more info" layer: stop bubbling so delegated handlers on ancestors do not run.
	 */
	function initCatalogOverlayPropagation() {
		$(document.body).on(
			'click.mpSccCatalogOverlay',
			'[data-mp-scc-overlay], .mp-scc-catalog-overlay',
			function (e) {
				e.stopPropagation();
			}
		);
	}

	/**
	 * Best-effort DOM signal that the catalog card is not purchasable due to stock (before AJAX).
	 * @param {JQuery} $card
	 * @returns {boolean}
	 */
	function isCatalogCardOutOfStock($card) {
		if ($card.hasClass('outofstock') || $card.hasClass('out-of-stock')) {
			return true;
		}
		if ($card.find('.stock.out-of-stock').length) {
			return true;
		}
		if ($card.find('.woocommerce-LoopProduct-link .out-of-stock').length) {
			return true;
		}
		return false;
	}

	/**
	 * @param {JQuery} $card
	 * @returns {boolean} False when the last stock toast was shown recently (anti-spam).
	 */
	function allowCatalogStockToast($card) {
		var last = $card.data('mpSccStockToastAt');
		var now = Date.now();
		if (last && now - last < CATALOG_STOCK_TOAST_COOLDOWN_MS) {
			return false;
		}
		$card.data('mpSccStockToastAt', now);
		return true;
	}

	function setCatalogCardLoading($card, on) {
		if (on) {
			$card.addClass('mp-scc-card--loading');
			$card.attr('data-mp-scc-atc-busy', '1');
		} else {
			$card.removeClass('mp-scc-card--loading');
			$card.removeAttr('data-mp-scc-atc-busy');
		}
	}

	function triggerCatalogAddedAnimation($card) {
		var reduce =
			window.matchMedia &&
			window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduce) {
			return;
		}
		$card.addClass('mp-scc-card--added');
		var done = function () {
			$card.removeClass('mp-scc-card--added');
			$card.off('animationend.mpSccAdded', done);
		};
		$card.on('animationend.mpSccAdded', done);
		window.setTimeout(function () {
			$card.removeClass('mp-scc-card--added');
			$card.off('animationend.mpSccAdded', done);
		}, 900);
	}

	/**
	 * @param {JQuery} $card
	 * @param {string} text
	 * @param {{ variant?: string, durationMs?: number, assertive?: boolean }} [options]
	 */
	function showCatalogToast($card, text, options) {
		options = options || {};
		var msg = text ? String(text) : '';
		if (!msg) {
			return;
		}
		var variant = options.variant || 'default';
		var durationMs =
			typeof options.durationMs === 'number' && options.durationMs >= 0 ? options.durationMs : 4000;
		var assertive = !!options.assertive;
		var prev = $card.data('mpSccToastTimer');
		if (prev) {
			clearTimeout(prev);
		}
		$card.find('.mp-scc-card__toast').remove();
		var role = assertive ? 'alert' : 'status';
		var live = assertive ? 'assertive' : 'polite';
		var cls = 'mp-scc-card__toast';
		if (variant === 'stock') {
			cls += ' mp-scc-card__toast--stock';
		} else if (variant === 'error') {
			cls += ' mp-scc-card__toast--error';
		}
		var $t = $('<span />')
			.addClass(cls)
			.attr('role', role)
			.attr('aria-live', live)
			.attr('aria-atomic', 'true')
			.text(msg);
		$card.append($t);
		var t = window.setTimeout(function () {
			$t.remove();
			$card.removeData('mpSccToastTimer');
		}, durationMs);
		$card.data('mpSccToastTimer', t);
	}

	function initCatalogImageAddToCart() {
		if (!window.mpScc.flagEnabled('product_image_add_to_cart')) {
			return;
		}
		var catalog = data().catalog || {};
		var sel = catalog.imageClickSelector || '';
		var cardClosest = catalog.cardRootSelector || 'li.product';
		if (!sel) {
			return;
		}
		var cfg = window.mpScc.ajaxConfig();
		if (!cfg.ajaxUrl || !cfg.actions.addSimpleProduct) {
			return;
		}

		$(document.body).on('click.mpSccCatalog', sel, function (e) {
			var $img = $(this);
			if (!$img.is('img')) {
				return;
			}
			if (e.button !== 0) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();

			var $card = $img.closest(cardClosest);
			if (!$card.length) {
				return;
			}
			if ($card.attr('data-mp-scc-atc-busy') === '1') {
				return;
			}

			var imgTitleSels = catalog.imageTitleBlockSelectors || [];
			for (var ib = 0; ib < imgTitleSels.length; ib++) {
				if ($img.closest(imgTitleSels[ib]).length) {
					return;
				}
			}
			if ($(e.target).closest('[data-mp-scc-overlay], .mp-scc-catalog-overlay').length) {
				return;
			}
			var coolUntil = $card.data('mpSccAtcCooldownUntil');
			if (typeof coolUntil === 'number' && Date.now() < coolUntil) {
				return;
			}

			var productId = resolveCatalogProductId($card);
			if (!productId) {
				var resolveMsg = catalog.resolveErrorMessage ? String(catalog.resolveErrorMessage) : '';
				showCatalogToast($card, resolveMsg, { variant: 'error', assertive: true });
				return;
			}

			if (isCatalogCardOutOfStock($card)) {
				if (allowCatalogStockToast($card)) {
					var stockLabel = window.mpScc.label('out_of_stock');
					showCatalogToast($card, stockLabel, {
						variant: 'stock',
						assertive: true,
						durationMs: 4500
					});
					window.mpScc.logClientEvent('catalog_image_out_of_stock', {
						product_id: productId,
						context: 'dom'
					});
				}
				return;
			}

			setCatalogCardLoading($card, true);
			$card.removeClass('mp-scc-card--error');

			window.mpScc
				.postAjax('addSimpleProduct', { product_id: productId, quantity: 1 })
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						setCatalogCardLoading($card, false);
						$card.data(
							'mpSccAtcCooldownUntil',
							Date.now() + CATALOG_ATC_POST_SUCCESS_COOLDOWN_MS
						);
						triggerCatalogAddedAnimation($card);
						$(document.body).trigger('added_to_cart', [{}, '', $img]);
						return;
					}
					setCatalogCardLoading($card, false);
					$card.addClass('mp-scc-card--error');
					window.setTimeout(function () {
						$card.removeClass('mp-scc-card--error');
					}, 500);
					var d = resp && resp.data ? resp.data : {};
					var code = d.code ? String(d.code) : '';
					var errMsg = d.message ? String(d.message) : window.mpScc.label('out_of_stock');
					var toastOpts = { variant: 'error', assertive: true };
					if (code === 'out_of_stock') {
						errMsg = window.mpScc.label('out_of_stock') || errMsg;
						toastOpts = { variant: 'stock', assertive: true, durationMs: 4500 };
					}
					showCatalogToast($card, errMsg, toastOpts);
				})
				.fail(function (xhr) {
					setCatalogCardLoading($card, false);
					$card.addClass('mp-scc-card--error');
					window.setTimeout(function () {
						$card.removeClass('mp-scc-card--error');
					}, 500);
					var msg = data().networkErrorMessage ? String(data().networkErrorMessage) : '';
					if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						msg = String(xhr.responseJSON.data.message);
					}
					showCatalogToast($card, msg, { variant: 'error', assertive: true });
				});
		});
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
		initWishlistIntegrationBodyClass();

		var $root = $('#mp-scc-sticky-root');
		if ($root.length) {
			var sticky = new StickyCartController($root[0]);
			sticky.init();
			window.mpScc.sticky = sticky;
		}

		initCatalogOverlayPropagation();
		initCatalogTitleClickHook();
		initCatalogOverlayKeyboard();
		initCatalogMoreInfoOverlay();
		initCatalogImageAddToCart();
		runCatalogTitleLinkSanity();
		warnDuplicateWishlistButtonsInCard();

		$(document.body).on(
			'wc_fragments_refreshed updated_wc_div etheme_ajax_loaded post-load',
			scheduleCatalogMoreInfoOverlay
		);

		window.mpScc.refreshCatalogOverlay = initCatalogMoreInfoOverlay;

		$(window.document).trigger('mpScc:ready');
	});
})(window, window.jQuery);
