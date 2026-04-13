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
	 * Append allowed query keys from the current page URL onto the checkout (UTM, click ids).
	 * Keys must match {@see CheckoutQueryPreserve::allowed_keys()} / `checkoutPreserveQueryKeys` in `mpSccData`.
	 *
	 * @param {string} baseUrl Absolute or site-relative checkout URL.
	 * @returns {string}
	 */
	window.mpScc.mergeUrlWithLocationQuery = function (baseUrl) {
		var keys = data().checkoutPreserveQueryKeys;
		var base = baseUrl ? String(baseUrl) : '';
		if (!base || !keys || !keys.length) {
			return base;
		}
		var keyMap = {};
		for (var i = 0; i < keys.length; i++) {
			keyMap[String(keys[i])] = true;
		}
		var search = window.location.search;
		if (!search || search === '?') {
			return base;
		}
		var pageParams;
		try {
			pageParams = new URLSearchParams(search.slice(1));
		} catch (e) {
			return base;
		}
		try {
			var u = new URL(base, window.location.href);
			pageParams.forEach(function (value, key) {
				if (!keyMap[key]) {
					return;
				}
				if (!u.searchParams.has(key)) {
					u.searchParams.set(key, value);
				}
			});
			return u.toString();
		} catch (err) {
			return base;
		}
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
		var catalog = data().catalog || {};
		var behavior = catalog.imageClickBehavior || 'add_to_cart';
		if (behavior === 'theme_default') {
			return;
		}
		if (!window.mpScc.flagEnabled('product_image_add_to_cart')) {
			return;
		}
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

	var SINGLE_ADD_SUCCESS_COOLDOWN_MS = 800;
	var lastSingleAddFeedbackAt = 0;

	/**
	 * Single product page / quick-view: success copy after Woo `added_to_cart`; optional YITH sync event.
	 * Sticky count/subtotal update via scheduleRefreshFromWooEvent on `added_to_cart` (same file).
	 * @param {JQuery|HTMLElement} [$button] Third argument from jQuery `added_to_cart` (triggering button).
	 * @returns {boolean}
	 */
	function shouldShowSingleProductAddFeedback($button) {
		if ($('body').hasClass('single-product')) {
			return true;
		}
		var $b = $button ? $($button) : $();
		if (!$b.length) {
			return false;
		}
		var quickSelectors = [
			'.yith-quick-view-content',
			'#yith-quick-view-modal',
			'.yith-wcqv-wrapper',
			'.quick-view-modal',
			'.woocommerce-quick-view-modal',
			'.etheme-quick-view',
			'.fancybox-inner',
			'.featherlight-inner',
			'[data-product-quick-view-modal]'
		];
		var i;
		for (i = 0; i < quickSelectors.length; i++) {
			if ($b.closest(quickSelectors[i]).length) {
				return true;
			}
		}
		return false;
	}

	function getVariationIdFromForm($form) {
		if (!$form || !$form.length) {
			return 0;
		}
		var $vid = $form.find('input.variation_id, input[name="variation_id"]').first();
		if (!$vid.length) {
			return 0;
		}
		var n = parseInt($vid.val(), 10);
		return isNaN(n) || n <= 0 ? 0 : n;
	}

	function $variationHighlightTarget($form) {
		var $t = $form.find('.variations').first();
		if ($t.length) {
			return $t;
		}
		return $form;
	}

	function clearVariationErrorHighlight($form) {
		if (!$form || !$form.length) {
			return;
		}
		$form.find('.mp-scc-variation--error').removeClass('mp-scc-variation--error');
	}

	function showVariationRequiredFromGuard($form) {
		var msg = window.mpScc.label('variation_required');
		if (!msg) {
			msg = 'Выберите вариацию товара';
		}
		var sticky = window.mpScc.sticky;
		if (sticky && typeof sticky.showStickyInlineFeedback === 'function') {
			sticky.showStickyInlineFeedback(msg, 'error');
		}
		var $target = $variationHighlightTarget($form);
		$target.addClass('mp-scc-variation--error');
		var el = $target.get(0);
		if (el && el.scrollIntoView) {
			try {
				el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			} catch (err) {
				el.scrollIntoView(true);
			}
		}
	}

	/**
	 * Block add-to-cart when no variation is selected (capture phase — before Woo handlers).
	 * Message: mpScc.label('variation_required') from admin / translations.
	 */
	function initVariableProductVariationGuard() {
		document.body.addEventListener(
			'click',
			function (e) {
				var t = e.target;
				if (!t || typeof t.closest !== 'function') {
					return;
				}
				if (e.button !== 0) {
					return;
				}
				var btn = t.closest(
					'form.variations_form button.single_add_to_cart_button, form.variations_form input.single_add_to_cart_button'
				);
				if (!btn) {
					return;
				}
				var form = btn.closest('form.variations_form');
				if (!form) {
					return;
				}
				var $form = $(form);
				if (getVariationIdFromForm($form) > 0) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				showVariationRequiredFromGuard($form);
			},
			true
		);

		$(document.body).on(
			'change',
			'form.variations_form .variations input, form.variations_form .variations select',
			function () {
				clearVariationErrorHighlight($(this).closest('form.variations_form'));
			}
		);
		$(document.body).on('found_variation reset_data', 'form.variations_form', function () {
			clearVariationErrorHighlight($(this));
		});
	}

	function initSingleProductAddToCart() {
		$(document.body).on('added_to_cart.mpSccSingle', function (ev, fragments, cart_hash, $button) {
			if (!shouldShowSingleProductAddFeedback($button)) {
				return;
			}
			var now = Date.now();
			if (now - lastSingleAddFeedbackAt < SINGLE_ADD_SUCCESS_COOLDOWN_MS) {
				return;
			}
			lastSingleAddFeedbackAt = now;
			var msg = window.mpScc.label('single_add_success');
			if (!msg) {
				msg = 'Товар добавлен в корзину';
			}
			var sticky = window.mpScc.sticky;
			if (sticky && typeof sticky.showStickyInlineFeedback === 'function') {
				sticky.showStickyInlineFeedback(msg, 'success');
			}
		});

		$(document.body).on('submit.mpSccSingleCart', 'form.cart', function () {
			if (!$('body').hasClass('single-product')) {
				return;
			}
			// WooCommerce AJAX add-to-cart prevents default when enabled; `added_to_cart` then refreshes sticky.
			// Non-AJAX POST reloads the page and the sticky markup is re-rendered server-side.
		});

		$(document.body).on('yith_added_to_cart.mpSccYith', function (e) {
			var sticky = window.mpScc.sticky;
			if (sticky && typeof sticky.scheduleRefreshFromWooEvent === 'function') {
				sticky.scheduleRefreshFromWooEvent(e.type || 'yith_added_to_cart');
			}
		});
	}

	/** Coalesce rapid WooCommerce body events (added_to_cart + fragments + totals) into one snapshot. */
	var WOO_CART_EVENT_DEBOUNCE_MS = 100;
	/** Second snapshot if the first Woo-driven refresh did not apply payload (network/lock race). */
	var WOO_CART_SYNC_FALLBACK_MS = 2600;
	var SNAPSHOT_FAIL_BURST_WINDOW_MS = 8000;
	var SNAPSHOT_FAIL_BURST_THRESHOLD = 4;

	/**
	 * Sticky bar + drawer: lifecycle, drawer toggle, cart snapshot UI, debounced qty, request lock.
	 */
	function StickyCartController(root) {
		this.$root = $(root);
		this.$drawer = this.$root.find('[data-mp-scc-drawer]');
		this.$toggle = this.$root.find('[data-mp-scc-drawer-toggle]');
		this.$summary = this.$root.find('.mp-scc-sticky-summary');
		this.$count = this.$root.find('[data-mp-scc-cart-count]');
		this.$total = this.$root.find('[data-mp-scc-cart-total]');
		this.$qtySr = this.$root.find('[data-mp-scc-cart-qty-total]');
		this.$items = this.$root.find('[data-mp-scc-drawer-items]');
		this.$empty = this.$root.find('[data-mp-scc-drawer-empty]');
		this.$checkout = this.$root.find('[data-mp-scc-checkout]');
		this.snapshotLocked = false;
		this.pendingRefresh = false;
		this.mutationInFlight = false;
		this.mutationQueue = [];
		this.qtyTimers = {};
		this.pendingQty = {};
		this.reconcileTimer = null;
		this.wooEventDebounceTimer = null;
		this.wooFallbackTimer = null;
		this._lastSnapshotAppliedAt = 0;
		this._syncFailCount = 0;
		this._syncFailWindowStart = 0;
		this.lastSnapshotTs = 0;
		this._skipLoadingOnce = true;
		this._fallbackSubtotalHtml = this.$total.length ? this.$total.html() : '';
		this.debounceMs = parseDebounceMs();
		this.clearCartLocked = false;
		/** Last successful snapshot quantities per cart line key (for optimistic +/- rollback). */
		this.serverQty = {};
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

	StickyCartController.prototype.pulseSummaryEl = function ($el) {
		if (!$el || !$el.length) {
			return;
		}
		var el = $el.get(0);
		$el.addClass('mp-scc-summary-value--pulse');
		window.setTimeout(function () {
			if (el && el.classList) {
				el.classList.remove('mp-scc-summary-value--pulse');
			}
		}, 420);
	};

	/**
	 * @param {Array<{ key?: string, quantity?: number }>} items
	 */
	StickyCartController.prototype.syncServerQtyFromItems = function (items) {
		var next = {};
		if (items && items.length) {
			items.forEach(function (it) {
				var k = it.key ? String(it.key) : '';
				if (!k) {
					return;
				}
				var q = typeof it.quantity === 'number' ? it.quantity : parseInt(it.quantity, 10) || 0;
				next[k] = q;
			});
		}
		this.serverQty = next;
	};

	/**
	 * Restore quantity display after failed mutation (matches last successful snapshot).
	 *
	 * @param {string} cartItemKey
	 */
	StickyCartController.prototype.rollbackLineQuantityDisplay = function (cartItemKey) {
		var key = cartItemKey ? String(cartItemKey) : '';
		if (!key || this.serverQty[key] === undefined) {
			return;
		}
		var q = this.serverQty[key];
		var $line = this.$root.find('.mp-scc-line').filter(function () {
			return $(this).attr('data-cart-item-key') === key;
		});
		if (!$line.length) {
			return;
		}
		$line.find('[data-mp-scc-qty-display]').text(String(q));
		var maxAttr = $line.attr('data-mp-scc-max-qty');
		var $inc = $line.find('[data-mp-scc-qty-inc]');
		if (maxAttr) {
			var maxN = parseInt(maxAttr, 10);
			if (!isNaN(maxN) && maxN > 0 && q >= maxN) {
				$inc.prop('disabled', true).attr('aria-disabled', 'true');
			} else {
				$inc.prop('disabled', false).removeAttr('aria-disabled');
			}
		} else {
			$inc.prop('disabled', false).removeAttr('aria-disabled');
		}
	};

	StickyCartController.prototype.scheduleReconcile = function () {
		var self = this;
		if (this.reconcileTimer) {
			clearTimeout(this.reconcileTimer);
		}
		this.reconcileTimer = window.setTimeout(function () {
			self.reconcileTimer = null;
			self.refresh();
		}, 380);
	};

	StickyCartController.prototype._resetSnapshotSyncFailures = function () {
		this._syncFailCount = 0;
		this._syncFailWindowStart = 0;
	};

	/**
	 * @param {string} [reason]
	 */
	StickyCartController.prototype._recordSnapshotSyncFailure = function (reason) {
		var now = Date.now();
		var w = SNAPSHOT_FAIL_BURST_WINDOW_MS;
		if (!this._syncFailWindowStart || now - this._syncFailWindowStart > w) {
			this._syncFailWindowStart = now;
			this._syncFailCount = 0;
		}
		this._syncFailCount++;
		if (this._syncFailCount < SNAPSHOT_FAIL_BURST_THRESHOLD) {
			return;
		}
		var ctx = String(reason || '').slice(0, 120);
		if (window.mpScc && typeof window.mpScc.logClientEvent === 'function') {
			window.mpScc.logClientEvent('woo_cart_snapshot_sync_fail_burst', { context: ctx });
		}
		this._syncFailCount = 0;
		this._syncFailWindowStart = now;
	};

	/**
	 * Debounced snapshot refresh after Woo cart-related DOM events (deduplicates cascades).
	 * @param {string} [eventType] Woo event name (for future diagnostics).
	 */
	StickyCartController.prototype.scheduleRefreshFromWooEvent = function (eventType) {
		var self = this;
		if (this.wooEventDebounceTimer) {
			clearTimeout(this.wooEventDebounceTimer);
		}
		this.wooEventDebounceTimer = window.setTimeout(function () {
			self.wooEventDebounceTimer = null;
			self._onWooCartEventRefresh(eventType);
		}, WOO_CART_EVENT_DEBOUNCE_MS);
	};

	/**
	 * @param {string} [eventType]
	 */
	StickyCartController.prototype._onWooCartEventRefresh = function (eventType) {
		var scheduledAt = Date.now();
		this.refresh();
		this._armWooSyncFallback(scheduledAt);
	};

	/**
	 * If applyPayload did not run after this Woo-driven refresh, request snapshot again.
	 * @param {number} scheduledAt Value of Date.now() when the Woo debounced handler ran.
	 */
	StickyCartController.prototype._armWooSyncFallback = function (scheduledAt) {
		var self = this;
		if (this.wooFallbackTimer) {
			clearTimeout(this.wooFallbackTimer);
		}
		this.wooFallbackTimer = window.setTimeout(function () {
			self.wooFallbackTimer = null;
			if (self._lastSnapshotAppliedAt >= scheduledAt) {
				return;
			}
			self.refresh();
		}, WOO_CART_SYNC_FALLBACK_MS);
	};

	StickyCartController.prototype.applyPayload = function (p) {
		if (!p || typeof p !== 'object') {
			this.scheduleReconcile();
			return;
		}

		this._lastSnapshotAppliedAt = Date.now();
		this._resetSnapshotSyncFailures();

		var items = Array.isArray(p.items) ? p.items : [];
		var lineCount = typeof p.line_count === 'number' ? p.line_count : items.length;
		var qty = typeof p.cart_contents_count === 'number' ? p.cart_contents_count : 0;
		var empty = !!p.is_empty;

		if (items.length && lineCount !== items.length) {
			lineCount = items.length;
		}
		if (!empty && items.length && qty === 0) {
			qty = items.reduce(function (acc, it) {
				var q = typeof it.quantity === 'number' ? it.quantity : parseInt(it.quantity, 10);
				return acc + (isNaN(q) ? 0 : q);
			}, 0);
		}
		if (empty) {
			lineCount = 0;
			qty = 0;
		}

		this.syncServerQtyFromItems(items);

		var prevLineText = this.$count.text();
		var prevSubHtml = this.$total.html();

		this.$count.text(String(lineCount));

		if (this.$qtySr.length) {
			var fmt =
				data().cartQtyTotalLabel ||
				'Total quantity in cart: %d';
			this.$qtySr.text(fmt.replace('%d', String(qty)));
		}

		var subHtml = typeof p.subtotal_html === 'string' && p.subtotal_html.length ? p.subtotal_html : '';
		if (!subHtml) {
			subHtml = this._fallbackSubtotalHtml || '';
		}
		if (!subHtml) {
			subHtml = '<span class="woocommerce-Price-amount amount">&mdash;</span>';
		}
		this.$total.html(subHtml);
		this._fallbackSubtotalHtml = subHtml;

		if (String(prevLineText) !== String(lineCount)) {
			this.pulseSummaryEl(this.$count);
		}
		if (prevSubHtml !== subHtml) {
			this.pulseSummaryEl(this.$total);
		}

		if (typeof p.snapshot_ts === 'number') {
			this.lastSnapshotTs = p.snapshot_ts;
		}

		if (!empty && items.length > 0 && lineCount === 0) {
			this.scheduleReconcile();
		}

		if (empty || items.length === 0) {
			this.$items.empty();
			this.$items.attr('hidden', 'hidden').attr('aria-hidden', 'true');
			this.$empty.removeAttr('hidden');
		} else {
			this.$items.removeAttr('hidden').removeAttr('aria-hidden');
			this.$empty.attr('hidden', 'hidden');
			this.renderLineItems(items);
		}

		this.$root.attr('data-mp-scc-cart-empty', empty ? '1' : '0');
		this.$root.toggleClass('mp-scc-sticky--empty', !!empty);

		this.syncStickyActions(empty);
	};

	/**
	 * @param {boolean} empty Whether the cart has no line items.
	 */
	StickyCartController.prototype.syncStickyActions = function (empty) {
		this.syncCheckoutState(empty);
		var $clear = this.$root.find('[data-mp-scc-clear-cart]');
		if ($clear.length) {
			var clearAria = $clear.attr('data-mp-scc-clear-aria-disabled') || '';
			if (empty) {
				$clear.addClass('mp-scc-clear-cart--disabled').prop('disabled', true).attr('aria-disabled', 'true');
				if (clearAria) {
					$clear.attr('aria-label', clearAria);
				}
			} else {
				$clear.removeClass('mp-scc-clear-cart--disabled').prop('disabled', false).removeAttr('aria-disabled');
				$clear.removeAttr('aria-label');
			}
		}
		if (this.$drawer.length) {
			this.$drawer.toggleClass('mp-scc-drawer--empty', !!empty);
			var $inner = this.$drawer.find('.mp-scc-drawer-inner');
			if ($inner.length) {
				$inner.toggleClass('mp-scc-drawer-inner--empty', !!empty);
			}
		}
	};

	/**
	 * @param {boolean} empty Whether the cart has no line items.
	 */
	StickyCartController.prototype.syncCheckoutState = function (empty) {
		var $a = this.$checkout;
		if (!$a.length) {
			return;
		}
		var base = $a.attr('data-mp-scc-checkout-base') || '';
		var disabledAria = $a.attr('data-mp-scc-checkout-aria-disabled') || '';
		if (empty) {
			$a.addClass('mp-scc-checkout--disabled');
			$a.attr('href', '#');
			$a.attr('aria-disabled', 'true');
			$a.attr('tabindex', '-1');
			if (disabledAria) {
				$a.attr('aria-label', disabledAria);
			}
		} else {
			$a.removeClass('mp-scc-checkout--disabled');
			$a.removeAttr('aria-disabled');
			$a.removeAttr('tabindex');
			$a.removeAttr('aria-label');
			if (base) {
				var merged = window.mpScc.mergeUrlWithLocationQuery(base);
				$a.attr('href', merged);
			}
		}
	};

	StickyCartController.prototype.clearLineRemovingState = function (cartItemKey) {
		var key = cartItemKey ? String(cartItemKey) : '';
		if (!key) {
			return;
		}
		var $line = this.$root.find('.mp-scc-line').filter(function () {
			return $(this).attr('data-cart-item-key') === key;
		});
		$line.removeClass('mp-scc-line--removing').removeAttr('aria-busy');
		var q = parseInt($line.find('[data-mp-scc-qty-display]').text(), 10) || 0;
		$line.find('button').prop('disabled', false).removeAttr('aria-disabled');
		var maxAttr = $line.attr('data-mp-scc-max-qty');
		if (maxAttr) {
			var maxN = parseInt(maxAttr, 10);
			if (!isNaN(maxN) && maxN > 0 && q >= maxN) {
				$line.find('[data-mp-scc-qty-inc]').prop('disabled', true).attr('aria-disabled', 'true');
			}
		}
	};

	StickyCartController.prototype.drainMutationQueue = function () {
		if (!this.mutationQueue.length) {
			return;
		}
		var next = this.mutationQueue.shift();
		if (next.remove) {
			this.commitRemoveLine(next.key);
		} else {
			this.commitQuantity(next.key, next.quantity);
		}
	};

	StickyCartController.prototype.commitRemoveLine = function (cartItemKey) {
		var self = this;
		var cfg = window.mpScc.ajaxConfig();
		var action = cfg.actions.removeCartLine;
		var keyStr = cartItemKey ? String(cartItemKey) : '';
		if (!keyStr || !cfg.ajaxUrl || !action) {
			this.clearLineRemovingState(keyStr);
			return;
		}
		if (this.mutationInFlight) {
			this.mutationQueue.push({ key: keyStr, remove: true });
			return;
		}
		this.mutationInFlight = true;

		var $line = this.$root.find('.mp-scc-line').filter(function () {
			return $(this).attr('data-cart-item-key') === keyStr;
		});
		if ($line.length) {
			$line.addClass('mp-scc-line--removing').attr('aria-busy', 'true');
			$line.find('button').prop('disabled', true);
		}

		function extractErrorMessage(xhr, resp) {
			if (resp && resp.data && resp.data.message) {
				return String(resp.data.message);
			}
			if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				return String(xhr.responseJSON.data.message);
			}
			return data().networkErrorMessage ? String(data().networkErrorMessage) : '';
		}

		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			data: {
				action: action,
				_ajax_nonce: cfg.nonce,
				cart_item_key: cartItemKey
			},
			dataType: 'json'
		})
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					self.applyPayload(resp.data);
					$(document.body).trigger('removed_from_cart');
					var okMsg = window.mpScc.label('line_removed') || 'Позиция удалена';
					self.showStickyInlineFeedback(okMsg, 'success');
					return;
				}
				self.clearLineRemovingState(keyStr);
				var msg = extractErrorMessage(null, resp);
				if (msg) {
					self.showStickyInlineFeedback(msg, 'error');
				}
				self.scheduleReconcile();
			})
			.fail(function (xhr) {
				self.clearLineRemovingState(keyStr);
				var msg = extractErrorMessage(xhr, null);
				if (msg) {
					self.showStickyInlineFeedback(msg, 'error');
				}
				self.scheduleReconcile();
			})
			.always(function () {
				self.mutationInFlight = false;
				self.drainMutationQueue();
			});
	};

	StickyCartController.prototype.removeLine = function (cartItemKey) {
		var k = cartItemKey ? String(cartItemKey) : '';
		if (!k) {
			return;
		}
		if (this.qtyTimers[k]) {
			clearTimeout(this.qtyTimers[k]);
			delete this.qtyTimers[k];
		}
		delete this.pendingQty[k];

		var $line = this.$root.find('.mp-scc-line').filter(function () {
			return $(this).attr('data-cart-item-key') === k;
		});
		if ($line.hasClass('mp-scc-line--removing')) {
			return;
		}
		if ($line.length) {
			$line.addClass('mp-scc-line--removing').attr('aria-busy', 'true');
			$line.find('button').prop('disabled', true);
		}

		if (this.mutationInFlight) {
			this.mutationQueue.push({ key: k, remove: true });
			return;
		}
		this.commitRemoveLine(k);
	};

	StickyCartController.prototype.renderLineItems = function (items) {
		var self = this;
		this.$items.empty();
		var removeLabel = window.mpScc.label('drawer_remove_line') || '';

		items.forEach(function (item) {
			var key = item.key ? String(item.key) : '';
			if (!key) {
				return;
			}
			var qty = typeof item.quantity === 'number' ? item.quantity : parseInt(item.quantity, 10) || 0;
			var snapId = item.snapshot_line_id ? String(item.snapshot_line_id) : key;

			var $li = $('<li class="mp-scc-line" />')
				.attr('data-cart-item-key', key)
				.attr('data-mp-scc-line-key', key)
				.attr('data-mp-scc-snapshot-line-id', snapId)
				.attr('data-mp-scc-product-id', item.product_id != null ? String(item.product_id) : '')
				.attr('data-mp-scc-variation-id', item.variation_id != null ? String(item.variation_id) : '');

			if (item.stock_notice) {
				$li.addClass('mp-scc-line--warn');
			}

			if (item.max_quantity !== null && item.max_quantity !== undefined) {
				var maxQN = parseInt(item.max_quantity, 10);
				if (!isNaN(maxQN) && maxQN > 0) {
					$li.attr('data-mp-scc-max-qty', String(maxQN));
				}
			}

			var $thumb = $('<div class="mp-scc-line__thumb" />');
			if (item.thumbnail_html && String(item.thumbnail_html).trim()) {
				$thumb.html(item.thumbnail_html);
				$thumb.find('img').each(function () {
					var $img = $(this);
					if (!$img.attr('alt')) {
						$img.attr('alt', item.name || '');
					}
					$img.attr('loading', 'lazy');
				});
			} else {
				$thumb.append($('<span class="mp-scc-line__thumb-fallback" aria-hidden="true" />'));
			}

			var $warn = $('<p class="mp-scc-line__warning" role="status" />');
			if (item.stock_notice) {
				$warn.text(String(item.stock_notice));
			} else {
				$warn.attr('hidden', 'hidden');
			}

			var $title = item.permalink
				? $('<a class="mp-scc-line__name" />').attr('href', item.permalink).text(item.name || '')
				: $('<span class="mp-scc-line__name" />').text(item.name || '');

			var $unit = $('<div class="mp-scc-line__unit" />');
			if (item.line_price_html && String(item.line_price_html).trim()) {
				$unit.html(item.line_price_html);
			} else {
				$unit.attr('hidden', 'hidden');
			}

			var $qty = $('<div class="mp-scc-line__qty" />');
			var $dec = $('<button type="button" class="mp-scc-qty-btn" data-mp-scc-qty-dec />')
				.attr('aria-label', 'Decrease quantity')
				.text('\u2212');
			var $num = $('<span class="mp-scc-line__qty-val" data-mp-scc-qty-display />').text(String(qty));
			var $inc = $('<button type="button" class="mp-scc-qty-btn" data-mp-scc-qty-inc />')
				.attr('aria-label', 'Increase')
				.text('+');

			var maxQ = item.max_quantity;
			if (maxQ !== null && maxQ !== undefined) {
				var maxN = parseInt(maxQ, 10);
				if (!isNaN(maxN) && maxN > 0 && qty >= maxN) {
					$inc.prop('disabled', true).attr('aria-disabled', 'true');
				}
			}

			$qty.append($dec, $num, $inc);

			var $remove = $('<button type="button" class="mp-scc-line__remove mp-scc-btn mp-scc-btn--ghost" data-mp-scc-line-remove />')
				.attr('aria-label', removeLabel || 'Remove line')
				.text('\u00d7');

			var $controls = $('<div class="mp-scc-line__controls" />').append($qty, $remove);

			var $sub = $('<div class="mp-scc-line__subtotal" />');
			if (typeof item.line_subtotal_html === 'string') {
				$sub.html(item.line_subtotal_html);
			}

			var $main = $('<div class="mp-scc-line__main" />').append($warn, $title, $unit, $controls);
			var $row = $('<div class="mp-scc-line__row" />').append($thumb, $main, $sub);
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
			this.mutationQueue.push({ key: cartItemKey, quantity: quantity, remove: false });
			return;
		}
		this.mutationInFlight = true;
		var keyStr = cartItemKey ? String(cartItemKey) : '';

		function extractErrorMessage(xhr, resp) {
			if (resp && resp.data && resp.data.message) {
				return String(resp.data.message);
			}
			if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				return String(xhr.responseJSON.data.message);
			}
			return data().networkErrorMessage ? String(data().networkErrorMessage) : '';
		}

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
					return;
				}
				self.rollbackLineQuantityDisplay(keyStr);
				var msg = extractErrorMessage(null, resp);
				if (msg) {
					self.showStickyInlineFeedback(msg, 'error');
				}
				self.scheduleReconcile();
			})
			.fail(function (xhr) {
				self.rollbackLineQuantityDisplay(keyStr);
				var msg = extractErrorMessage(xhr, null);
				if (msg) {
					self.showStickyInlineFeedback(msg, 'error');
				}
				self.scheduleReconcile();
			})
			.always(function () {
				self.mutationInFlight = false;
				self.drainMutationQueue();
			});
	};

	/**
	 * @returns {boolean} True while cart snapshot or quantity mutation AJAX is in flight.
	 */
	StickyCartController.prototype.isLoading = function () {
		return this.snapshotLocked || this.mutationInFlight || this.clearCartLocked;
	};

	/**
	 * @param {string} message
	 * @param {'success'|'error'} variant
	 */
	StickyCartController.prototype.showStickyInlineFeedback = function (message, variant) {
		var text = message ? String(message) : '';
		if (!text) {
			return;
		}
		this.$root.find('.mp-scc-sticky-inline-feedback').remove();
		var cls =
			variant === 'error'
				? 'mp-scc-sticky-inline-feedback mp-scc-sticky-inline-feedback--error'
				: 'mp-scc-sticky-inline-feedback mp-scc-sticky-inline-feedback--success';
		var role = variant === 'error' ? 'alert' : 'status';
		var $p = $('<p />').addClass(cls).attr('role', role).text(text);
		this.$root.find('.mp-scc-sticky-inner').prepend($p);
		window.setTimeout(function () {
			$p.remove();
		}, variant === 'error' ? 6000 : 4000);
	};

	StickyCartController.prototype.refresh = function () {
		var self = this;
		if (this.snapshotLocked) {
			this.pendingRefresh = true;
			return;
		}
		this.snapshotLocked = true;
		if (!this._skipLoadingOnce && this.$summary.length) {
			this.$summary.addClass('mp-scc-sticky-summary--loading');
		}
		this._skipLoadingOnce = false;
		window.mpScc
			.postAjax('cartSnapshot', {})
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					self.applyPayload(resp.data);
				} else {
					self._recordSnapshotSyncFailure('bad_response');
					self.scheduleReconcile();
				}
			})
			.fail(function () {
				self._recordSnapshotSyncFailure('ajax_fail');
				self.scheduleReconcile();
			})
			.always(function () {
				self.$summary.removeClass('mp-scc-sticky-summary--loading');
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
			var maxAttr = $line.attr('data-mp-scc-max-qty');
			if (maxAttr) {
				var maxN = parseInt(maxAttr, 10);
				if (!isNaN(maxN) && maxN > 0 && q >= maxN) {
					return;
				}
			}
			q += 1;
			$disp.text(String(q));
			if (maxAttr) {
				var maxN2 = parseInt(maxAttr, 10);
				if (!isNaN(maxN2) && maxN2 > 0 && q >= maxN2) {
					$(e.currentTarget).prop('disabled', true).attr('aria-disabled', 'true');
				}
			}
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
			if (q <= 1) {
				return;
			}
			q -= 1;
			$disp.text(String(q));
			$line.find('[data-mp-scc-qty-inc]').prop('disabled', false).removeAttr('aria-disabled');
			self.scheduleQuantityCommit(key, q);
		});

		this.$root.on('click', '[data-mp-scc-line-remove]', function (e) {
			e.preventDefault();
			var $line = $(e.currentTarget).closest('.mp-scc-line');
			var key = $line.attr('data-mp-scc-line-key') || $line.attr('data-cart-item-key');
			if (!key || self.snapshotLocked || self.clearCartLocked) {
				return;
			}
			if ($line.hasClass('mp-scc-line--removing')) {
				return;
			}
			self.removeLine(key);
		});

		this.$root.on('click', '[data-mp-scc-clear-cart]', function (e) {
			e.preventDefault();
			var cfg = window.mpScc.ajaxConfig();
			if (!cfg.ajaxUrl || !cfg.actions.clearCart) {
				return;
			}
			var $btn = $(e.currentTarget);
			if ($btn.prop('disabled') || $btn.hasClass('mp-scc-clear-cart--disabled')) {
				return;
			}
			if (self.clearCartLocked || self.snapshotLocked || self.mutationInFlight) {
				return;
			}
			self.clearCartLocked = true;
			$btn.prop('disabled', true).attr('aria-busy', 'true').addClass('mp-scc-clear-cart--loading');
			window.mpScc
				.postAjax('clearCart', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						self.applyPayload(resp.data);
						$(document.body).trigger('removed_from_cart');
						var okMsg = window.mpScc.label('cart_cleared') || 'Корзина очищена';
						self.showStickyInlineFeedback(okMsg, 'success');
					} else {
						self.scheduleReconcile();
					}
				})
				.fail(function (xhr) {
					var msg = data().networkErrorMessage ? String(data().networkErrorMessage) : '';
					if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						msg = String(xhr.responseJSON.data.message);
					}
					self.showStickyInlineFeedback(msg, 'error');
					self.scheduleReconcile();
				})
				.always(function () {
					self.clearCartLocked = false;
					$btn.removeAttr('aria-busy').removeClass('mp-scc-clear-cart--loading');
					var isEmpty = self.$root.attr('data-mp-scc-cart-empty') === '1';
					if (isEmpty) {
						self.syncStickyActions(true);
					} else {
						$btn.prop('disabled', false).removeAttr('aria-disabled').removeClass('mp-scc-clear-cart--disabled');
						$btn.removeAttr('aria-label');
					}
				});
		});

		this.$root.on('click', '[data-mp-scc-checkout]', function (e) {
			var $a = $(e.currentTarget);
			if ($a.hasClass('mp-scc-checkout--disabled')) {
				e.preventDefault();
				return false;
			}
		});

		this.$root.on('keydown', '[data-mp-scc-checkout].mp-scc-checkout--disabled', function (e) {
			var k = e.key;
			if (k === ' ' || k === 'Enter') {
				e.preventDefault();
			}
		});

		$(document.body).on(
			'added_to_cart removed_from_cart wc_fragments_refreshed updated_cart_totals wc_fragments_loaded updated_wc_div',
			function (e) {
				self.scheduleRefreshFromWooEvent(e && e.type ? e.type : 'woo');
			}
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

		initVariableProductVariationGuard();
		initSingleProductAddToCart();

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
