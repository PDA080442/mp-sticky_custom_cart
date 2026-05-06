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
	 * Ensures catalog cart icon CSS variables apply (missing :root block, theme CSS, cache optimizers).
	 * Values mirror {@see mpSccData.cssVars} (same source as admin preview).
	 */
	function applyCatalogCartIconCssVarsFromPayload() {
		var d = window.mpSccData;
		if (!d || !d.cssVars || typeof d.cssVars !== 'object') {
			return;
		}
		var root = document.documentElement;
		var p = '--mp-scc-catalog-cart-icon-';
		var k;
		for (k in d.cssVars) {
			if (!Object.prototype.hasOwnProperty.call(d.cssVars, k)) {
				continue;
			}
			if (String(k).indexOf(p) !== 0) {
				continue;
			}
			try {
				root.style.setProperty(String(k), String(d.cssVars[k]), 'important');
			} catch (e) {
				root.style.setProperty(String(k), String(d.cssVars[k]));
			}
		}
		stampAllCatalogCartIconButtonsFromCssVars();
	}

	function stampOneCatalogCartIconButton(btn) {
		if (!btn || !btn.style) {
			return;
		}
		var v = data().cssVars;
		if (!v || typeof v !== 'object') {
			return;
		}
		var col = v['--mp-scc-catalog-cart-icon-color'];
		var bg = v['--mp-scc-catalog-cart-icon-background'];
		var br = v['--mp-scc-catalog-cart-icon-border-radius'];
		var padKey = '--mp-scc-catalog-cart-icon-inner-padding';
		var hasPad = Object.prototype.hasOwnProperty.call(v, padKey);
		var pad = hasPad ? String(v[padKey]) : '';
		try {
			if (col) {
				btn.style.setProperty('color', String(col), 'important');
			}
			if (bg) {
				btn.style.setProperty('background-color', String(bg), 'important');
			}
			if (br) {
				btn.style.setProperty('border-radius', String(br), 'important');
			}
			if (hasPad) {
				btn.style.setProperty('padding', pad, 'important');
				btn.style.setProperty('box-sizing', 'border-box', 'important');
			}
			btn.style.setProperty('background-image', 'none', 'important');
		} catch (err) {
			if (col) {
				btn.style.color = String(col);
			}
			if (bg) {
				btn.style.backgroundColor = String(bg);
			}
			if (br) {
				btn.style.borderRadius = String(br);
			}
			if (hasPad) {
				btn.style.padding = pad;
				btn.style.boxSizing = 'border-box';
			}
		}
	}

	function stampAllCatalogCartIconButtonsFromCssVars() {
		var nodes = document.querySelectorAll('button.mp-scc-catalog-cart-icon-btn');
		var i;
		for (i = 0; i < nodes.length; i++) {
			stampOneCatalogCartIconButton(nodes[i]);
		}
	}

	var __mpSccCartIconPaintHoverBound = false;

	function initCatalogCartIconPaintHammer() {
		if (__mpSccCartIconPaintHoverBound) {
			return;
		}
		__mpSccCartIconPaintHoverBound = true;
		$(document.body)
			.on('mouseenter.mpSccCiPaint', '.mp-scc-catalog-cart-icon-btn', function () {
				var v = data().cssVars;
				if (!v) {
					return;
				}
				var h = v['--mp-scc-catalog-cart-icon-background-hover'];
				var ch = v['--mp-scc-catalog-cart-icon-color-hover'];
				if (h) {
					try {
						this.style.setProperty('background-color', String(h), 'important');
					} catch (e2) {
						this.style.backgroundColor = String(h);
					}
				}
				if (ch) {
					try {
						this.style.setProperty('color', String(ch), 'important');
					} catch (e3) {
						this.style.color = String(ch);
					}
				}
			})
			.on('mouseleave.mpSccCiPaint', '.mp-scc-catalog-cart-icon-btn', function () {
				stampOneCatalogCartIconButton(this);
			});
	}

	/**
	 * Whether the catalog cart icon preset is `tristate_panel_a` (same glyph as tri-state FAB
	 * `.mp-scc-drawer-toggle-icon`).
	 * @returns {boolean}
	 */
	function isCatalogCartIconPresetTristatePanelA() {
		var cat = data().catalog || {};
		var k = String(cat.catalogCartIconPreset || '')
			.trim()
			.replace(/[^a-z0-9_-]/gi, '');
		return k === 'tristate_panel_a';
	}

	/**
	 * Updates per-product qty badge on loop cart buttons when preset is `tristate_panel_a`
	 * (mirrors FAB badge styling via CSS).
	 * @param {Array<{ product_id?: number|string, quantity?: number|string }>} items
	 * @param {boolean} empty
	 */
	window.mpScc.syncCatalogCartProductBadges = function (items, empty) {
		if (!isCatalogCartIconPresetTristatePanelA()) {
			return;
		}
		var map = {};
		if (!empty && items && items.length) {
			items.forEach(function (it) {
				var pid =
					typeof it.product_id === 'number'
						? it.product_id
						: parseInt(it.product_id, 10);
				if (!pid || isNaN(pid) || pid < 1) {
					return;
				}
				var q =
					typeof it.quantity === 'number' ? it.quantity : parseInt(it.quantity, 10) || 0;
				map[pid] = (map[pid] || 0) + q;
			});
		}
		var nodes = document.querySelectorAll(
			'.mp-scc-catalog-cart-icon-btn--panel-a[data-mp-scc-loop-product-id]'
		);
		var i;
		for (i = 0; i < nodes.length; i++) {
			var btn = nodes[i];
			var pidN = parseInt(btn.getAttribute('data-mp-scc-loop-product-id'), 10);
			var n = !isNaN(pidN) && pidN > 0 ? map[pidN] || 0 : 0;
			var badge = btn.querySelector('[data-mp-scc-catalog-cart-badge]');
			if (!badge) {
				continue;
			}
			if (n > 0) {
				badge.textContent = n > 99 ? '99+' : String(n);
				badge.removeAttribute('hidden');
				badge.style.display = '';
			} else {
				badge.textContent = '';
				badge.setAttribute('hidden', 'hidden');
				badge.style.display = 'none';
			}
		}
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

	var __mpSccStickyDeferredTimer = null;
	var __mpSccStickyDeferredMounting = false;

	function mpSccStickyHideWhenEmptyEnabled() {
		return !!(window.mpScc.flagEnabled && window.mpScc.flagEnabled('sticky_hide_when_empty_enabled'));
	}

	function mpSccScheduleDeferredFloatingStickySnapshot() {
		if (__mpSccStickyDeferredTimer) {
			clearTimeout(__mpSccStickyDeferredTimer);
		}
		__mpSccStickyDeferredTimer = setTimeout(function () {
			__mpSccStickyDeferredTimer = null;
			mpSccRunDeferredFloatingStickySnapshot();
		}, 90);
	}

	function mpSccRegisterDeferredFloatingStickyListener() {
		if (!mpSccStickyHideWhenEmptyEnabled() || !window.mpScc.flagEnabled('sticky_cart_enabled')) {
			return;
		}
		if ($('#mp-scc-sticky-root').length) {
			return;
		}
		$(document.body).off('.mpSccStickyDeferred');
		$(document.body).on(
			'added_to_cart.mpSccStickyDeferred removed_from_cart.mpSccStickyDeferred wc_fragments_refreshed.mpSccStickyDeferred updated_cart_totals.mpSccStickyDeferred wc_fragments_loaded.mpSccStickyDeferred updated_wc_div.mpSccStickyDeferred',
			function () {
				mpSccScheduleDeferredFloatingStickySnapshot();
			}
		);
	}

	function mpSccRunDeferredFloatingStickySnapshot() {
		if ($('#mp-scc-sticky-root').length || !mpSccStickyHideWhenEmptyEnabled()) {
			return;
		}
		if (__mpSccStickyDeferredMounting) {
			return;
		}
		__mpSccStickyDeferredMounting = true;
		window.mpScc
			.postAjax('cartSnapshot', { include_sticky_shell: 1 })
			.done(function (resp) {
				if (!resp || !resp.success || !resp.data) {
					return;
				}
				var td = resp.data;
				if (td.is_empty || !td.sticky_shell_html) {
					return;
				}
				$(document.body).off('.mpSccStickyDeferred');
				$(document.body).append(td.sticky_shell_html);
				var $root = $('#mp-scc-sticky-root');
				if (!$root.length) {
					mpSccRegisterDeferredFloatingStickyListener();
					return;
				}
				var bcls = Array.isArray(td.sticky_body_classes) ? td.sticky_body_classes : [];
				window.mpScc.__stickyShellBodyClassesApplied = bcls.slice();
				var bi;
				for (bi = 0; bi < bcls.length; bi++) {
					document.body.classList.add(String(bcls[bi]));
				}
				var stickyLocal = null;
				try {
					stickyLocal = new StickyCartController($root[0]);
					if (window.mpSccCartUiShell && typeof window.mpSccCartUiShell.attachSticky === 'function') {
						window.mpSccCartUiShell.attachSticky(stickyLocal);
					}
					stickyLocal.bind();
					stickyLocal.applyPayload(td);
					window.mpScc.sticky = stickyLocal;
				} catch (e2) {
					try {
						if (stickyLocal && typeof stickyLocal.destroy === 'function') {
							stickyLocal.destroy();
						}
					} catch (e3) {
						/* ignore nested teardown errors */
					}
					window.mpScc.sticky = null;
					if (typeof window.mpScc.teardownFloatingStickyShell === 'function') {
						window.mpScc.teardownFloatingStickyShell();
					}
					if (window.mpScc.reportStickyError) {
						window.mpScc.reportStickyError(
							'sticky_deferred_mount_failed',
							e2 && e2.message ? String(e2.message) : String(e2),
							e2 && e2.stack ? String(e2.stack).slice(0, 500) : ''
						);
					}
					mpSccRegisterDeferredFloatingStickyListener();
				}
			})
			.fail(function () {
				mpSccRegisterDeferredFloatingStickyListener();
			})
			.always(function () {
				__mpSccStickyDeferredMounting = false;
			});
	}

	window.mpScc.teardownFloatingStickyShell = function () {
		var cls = window.mpScc.__stickyShellBodyClassesApplied;
		var i;
		if (window.mpScc.sticky && typeof window.mpScc.sticky.destroy === 'function') {
			window.mpScc.sticky.destroy();
		}
		window.mpScc.sticky = null;
		var r = document.getElementById('mp-scc-sticky-root');
		if (r && r.parentNode) {
			r.parentNode.removeChild(r);
		}
		if (cls && cls.length) {
			for (i = 0; i < cls.length; i++) {
				document.body.classList.remove(String(cls[i]));
			}
		}
		window.mpScc.__stickyShellBodyClassesApplied = [];
		mpSccRegisterDeferredFloatingStickyListener();
	};

	var clientDiagBuffer = [];
	var clientDiagFlushTimer = null;
	var clientDiagReporting = false;

	function clientDiagPageMeta() {
		return {
			page_url: window.location.href ? String(window.location.href).slice(0, 500) : '',
			user_agent:
				typeof navigator !== 'undefined' && navigator.userAgent
					? String(navigator.userAgent).slice(0, 400)
					: ''
		};
	}

	function clientDiagFlushMs() {
		var d = data();
		var n = parseInt(d.clientLogFlushMs, 10);
		return isNaN(n) || n < 200 ? 1200 : Math.min(n, 10000);
	}

	function clientDiagMaxBatch() {
		var d = data();
		var n = parseInt(d.clientLogMaxBatch, 10);
		return isNaN(n) || n < 1 ? 12 : Math.min(n, 25);
	}

	function isLogClientAjaxRequest(settings) {
		var logAct = window.mpScc.ajaxConfig().actions.logClientEvent;
		if (!logAct || !settings || !settings.data) {
			return false;
		}
		var d = settings.data;
		if (typeof d === 'string') {
			return d.indexOf('action=' + logAct) !== -1 || d.indexOf('&action=' + logAct) !== -1;
		}
		if (typeof d === 'object' && d !== null && d.action === logAct) {
			return true;
		}
		return false;
	}

	/**
	 * Errors from other scripts on the page (often WooCommerce Blocks) — not actionable for this plugin.
	 * @param {string} message
	 * @returns {boolean}
	 */
	function shouldSuppressClientGlobalErrorMessage(message) {
		var m = String(message || '');
		if (!m) {
			return false;
		}
		// WooCommerce: wcSettings / data store not ready — common, usually harmless.
		if (m.indexOf("reading 'setSettings'") !== -1 || m.indexOf('reading "setSettings"') !== -1) {
			return true;
		}
		// Theme / inline preloader: missing DOM node (e.g. hidePreloader) — not from this plugin.
		if (/Cannot read properties of null \(reading ['"]style['"]\)/.test(m)) {
			return true;
		}
		return false;
	}

	window.mpScc.queueClientDiagnostic = function (row) {
		if (!data().clientLogging) {
			return;
		}
		if (!row || typeof row.event !== 'string' || !row.event) {
			return;
		}
		var meta = clientDiagPageMeta();
		var item = $.extend(
			{
				level: 'info',
				message: '',
				context: '',
				detail: '',
				product_id: 0
			},
			row,
			meta
		);
		item.event = String(item.event).slice(0, 80);
		if (item.message) {
			item.message = String(item.message).slice(0, 500);
		}
		if (item.context) {
			item.context = String(item.context).slice(0, 300);
		}
		if (item.detail) {
			item.detail = String(item.detail).slice(0, 500);
		}
		clientDiagBuffer.push(item);
		if (clientDiagBuffer.length >= clientDiagMaxBatch()) {
			window.mpScc.flushClientDiagnostics();
			return;
		}
		if (clientDiagFlushTimer) {
			clearTimeout(clientDiagFlushTimer);
		}
		clientDiagFlushTimer = window.setTimeout(function () {
			clientDiagFlushTimer = null;
			window.mpScc.flushClientDiagnostics();
		}, clientDiagFlushMs());
	};

	window.mpScc.flushClientDiagnostics = function () {
		if (!data().clientLogging) {
			return;
		}
		if (clientDiagFlushTimer) {
			clearTimeout(clientDiagFlushTimer);
			clientDiagFlushTimer = null;
		}
		if (!clientDiagBuffer.length) {
			return;
		}
		var batch = clientDiagBuffer.splice(0, clientDiagMaxBatch());
		var cfg = window.mpScc.ajaxConfig();
		if (!cfg.ajaxUrl || !cfg.actions.logClientEvent) {
			return;
		}
		clientDiagReporting = true;
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			data: {
				action: cfg.actions.logClientEvent,
				_ajax_nonce: cfg.nonce,
				batch: JSON.stringify(batch)
			},
			dataType: 'json'
		})
			.done(function () {
				if (clientDiagBuffer.length) {
					window.mpScc.flushClientDiagnostics();
				}
			})
			.fail(function () {
				clientDiagBuffer = batch.concat(clientDiagBuffer);
			})
			.always(function () {
				clientDiagReporting = false;
			});
	};

	window.mpScc.reportStickyError = function (code, message, detail) {
		window.mpScc.queueClientDiagnostic({
			event: String(code || 'sticky_error').slice(0, 80),
			level: 'error',
			message: message ? String(message).slice(0, 500) : '',
			detail: detail ? String(detail).slice(0, 500) : ''
		});
	};

	/**
	 * Fire-and-forget client diagnostics (respects `diagnostics.client_error_logging` on the server).
	 * @param {string} event Sanitized key, e.g. `catalog_image_out_of_stock`.
	 * @param {Record<string, *>} [payload]
	 */
	window.mpScc.logClientEvent = function (event, payload) {
		if (!data().clientLogging) {
			return;
		}
		var p = payload || {};
		var row = {
			event: String(event),
			level: 'info',
			message: 'client_' + String(event)
		};
		if (p.context !== undefined) {
			row.context = String(p.context).slice(0, 300);
		}
		if (p.product_id) {
			var pid = parseInt(p.product_id, 10);
			if (!isNaN(pid) && pid > 0) {
				row.product_id = pid;
			}
		}
		if (p.message) {
			row.message = String(p.message).slice(0, 500);
		}
		if (p.surface !== undefined) {
			row.detail = 'surface=' + String(p.surface).slice(0, 32);
		}
		window.mpScc.queueClientDiagnostic(row);
	};

	function initClientDiagnostics() {
		if (window.__mpSccClientDiagInit) {
			return;
		}
		if (!data().clientLogging) {
			return;
		}
		window.__mpSccClientDiagInit = true;

		var origOnError = window.onerror;
		window.onerror = function (message, source, lineno, colno, error) {
			if (typeof origOnError === 'function') {
				try {
					origOnError.apply(window, arguments);
				} catch (e) {}
			}
			if (!data().clientLogging || clientDiagReporting) {
				return false;
			}
			var msgStr = String(message || '');
			if (shouldSuppressClientGlobalErrorMessage(msgStr)) {
				return false;
			}
			var detail =
				String(source || '') +
				':' +
				String(lineno || 0) +
				':' +
				String(colno || 0);
			if (error && error.stack) {
				detail += ' ' + String(error.stack).slice(0, 400);
			}
			window.mpScc.queueClientDiagnostic({
				event: 'js_error',
				level: 'error',
				message: msgStr.slice(0, 500),
				detail: detail.slice(0, 500)
			});
			return false;
		};

		window.addEventListener(
			'unhandledrejection',
			function (ev) {
				if (!data().clientLogging || clientDiagReporting) {
					return;
				}
				var r = ev.reason;
				var msg =
					r && typeof r === 'object' && r.message
						? String(r.message)
						: String(r);
				if (shouldSuppressClientGlobalErrorMessage(msg)) {
					return;
				}
				window.mpScc.queueClientDiagnostic({
					event: 'unhandledrejection',
					level: 'error',
					message: msg.slice(0, 500)
				});
			},
			true
		);

		$(document).ajaxError(function (event, jqXHR, settings, thrownError) {
			if (!data().clientLogging || clientDiagReporting) {
				return;
			}
			var url = settings && settings.url ? String(settings.url) : '';
			if (!url || url.indexOf('admin-ajax.php') === -1) {
				return;
			}
			if (isLogClientAjaxRequest(settings)) {
				return;
			}
			var status = jqXHR && typeof jqXHR.status !== 'undefined' ? jqXHR.status : 0;
			var ctx = thrownError ? String(thrownError).slice(0, 120) : '';
			window.mpScc.queueClientDiagnostic({
				event: 'xhr_ajax_error',
				level: 'warn',
				message: 'HTTP ' + String(status),
				context: ctx,
				detail: url.slice(0, 300)
			});
		});

		if (typeof window.fetch === 'function') {
			var origFetch = window.fetch;
			var logAct = window.mpScc.ajaxConfig().actions.logClientEvent;
			window.fetch = function (input, init) {
				return origFetch.apply(this, arguments).then(
					function (resp) {
						if (!data().clientLogging || clientDiagReporting) {
							return resp;
						}
						var urlStr = '';
						try {
							urlStr =
								typeof input === 'string'
									? input
									: input && input.url
										? String(input.url)
										: '';
						} catch (e) {}
						if (!urlStr || urlStr.indexOf('admin-ajax.php') === -1 || !resp || resp.ok) {
							return resp;
						}
						try {
							var u = new URL(urlStr, window.location.href);
							if (logAct && u.searchParams.get('action') === logAct) {
								return resp;
							}
						} catch (e2) {}
						window.mpScc.queueClientDiagnostic({
							event: 'fetch_http',
							level: 'warn',
							message: 'HTTP ' + String(resp.status),
							context: urlStr.slice(0, 200)
						});
						return resp;
					},
					function (err) {
						if (!data().clientLogging || clientDiagReporting) {
							return Promise.reject(err);
						}
						var urlStr2 = '';
						try {
							urlStr2 =
								typeof input === 'string'
									? input
									: input && input.url
										? String(input.url)
										: '';
						} catch (e3) {}
						if (!urlStr2 || urlStr2.indexOf('admin-ajax.php') === -1) {
							return Promise.reject(err);
						}
						try {
							var u2 = new URL(urlStr2, window.location.href);
							if (logAct && u2.searchParams.get('action') === logAct) {
								return Promise.reject(err);
							}
						} catch (e4) {}
						window.mpScc.queueClientDiagnostic({
							event: 'fetch_network',
							level: 'error',
							message: String(err && err.message ? err.message : err).slice(0, 500),
							context: urlStr2.slice(0, 200)
						});
						return Promise.reject(err);
					}
				);
			};
		}
	}

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

	/**
	 * Themes often use `div.product` / `article.product` (Elementor, XStore) instead of `li.product`.
	 *
	 * @param {EventTarget|null} el
	 * @param {Record<string, *>} [catalog]
	 * @returns {Element|null}
	 */
	function findProductCardElement(el, catalog) {
		var t = el;
		if (!t || !t.nodeType) {
			return null;
		}
		if (t.nodeType === 3 && t.parentElement) {
			t = t.parentElement;
		}
		if (!t || !t.closest) {
			return null;
		}
		var pref =
			catalog && catalog.cardRootSelector != null
				? String(catalog.cardRootSelector).trim()
				: '';
		var fallbacks = [
			'li.product',
			'div.product.type-product',
			'div.product',
			'article.product',
			'.wc-block-grid__product'
		];
		var seen = {};
		var order = [];
		var i;
		if (pref) {
			order.push(pref);
		}
		for (i = 0; i < fallbacks.length; i++) {
			if (fallbacks[i] !== pref) {
				order.push(fallbacks[i]);
			}
		}
		for (i = 0; i < order.length; i++) {
			var s = order[i];
			if (!s || seen[s]) {
				continue;
			}
			seen[s] = true;
			var node = t.closest(s);
			if (node && resolveCatalogProductId($(node)) > 0) {
				return node;
			}
		}
		return null;
	}

	/**
	 * jQuery selector for catalog cards (loop) — includes `div.product` when admin leaves default `li.product`.
	 *
	 * @param {Record<string, *>} catalog
	 * @returns {string}
	 */
	function catalogLoopCardSelector(catalog) {
		var raw =
			catalog && catalog.cardRootSelector != null ? String(catalog.cardRootSelector).trim() : '';
		if (raw) {
			return raw;
		}
		return (
			'ul.products li.product, ul.products div.product, div.products div.product, .woocommerce .products li.product, .woocommerce .products div.product'
		);
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
	 * Main catalog thumbnail (not the first img in DOM — themes often add badges, sprites, or lazy placeholders).
	 * Must stay in sync with {@see findCatalogOverlayThumbnailHost} so overlay, hit layer, and cart icon share one box.
	 *
	 * @param {JQuery} $card
	 * @param {Record<string, *>} catalog
	 * @returns {JQuery}
	 */
	function findCatalogLayoutReferenceImg($card, catalog) {
		catalog = catalog || {};
		/**
		 * @param {JQuery} $box
		 * @returns {JQuery}
		 */
		function pickLargestImgInBox($box) {
			if (!$box || !$box.length) {
				return $();
			}
			var $imgs = $box.find('img');
			if ($imgs.length === 1) {
				return $imgs.first();
			}
			var bestEl = null;
			var bestArea = 0;
			$imgs.each(function () {
				var $im = $(this);
				var w = $im.outerWidth();
				var h = $im.outerHeight();
				if (w < 32 || h < 32) {
					return;
				}
				var area = w * h;
				if (area > bestArea) {
					bestArea = area;
					bestEl = this;
				}
			});
			if (bestEl) {
				return $(bestEl);
			}
			return $imgs.first();
		}

		var themeImgHosts = [
			'.product-image',
			'.product-content-image',
			'.content-product > .product-image',
			'.product-images',
			'.product-main-image',
			'.hover-product .product-image',
		];
		var ti;
		for (ti = 0; ti < themeImgHosts.length; ti++) {
			var $th = $card.find(themeImgHosts[ti]).first();
			if ($th.length && $th.find('img').length) {
				var $tp = pickLargestImgInBox($th);
				if ($tp.length) {
					return $tp;
				}
			}
		}

		var $host = findCatalogOverlayThumbnailHost($card, catalog);
		if ($host.length) {
			var $picked = pickLargestImgInBox($host);
			if ($picked.length) {
				return $picked;
			}
		}
		var fallbacks = [
			'a.woocommerce-LoopProduct-link img',
			'a.woocommerce-loop-product__link img',
			'.woocommerce-LoopProduct-link img',
		];
		var fi;
		for (fi = 0; fi < fallbacks.length; fi++) {
			var $f = $card.find(fallbacks[fi]).first();
			if ($f.length) {
				return $f;
			}
		}
		var bestOuter = null;
		var bestArea2 = 0;
		$card.find('img').each(function () {
			var $im = $(this);
			var w = $im.outerWidth();
			var h = $im.outerHeight();
			if (w < 32 || h < 32) {
				return;
			}
			var area = w * h;
			if (area > bestArea2) {
				bestArea2 = area;
				bestOuter = this;
			}
		});
		if (bestOuter) {
			return $(bestOuter);
		}
		return $card.find('img').first();
	}

	/**
	 * Positions «Подробнее» band + invisible add-to-cart hit layer from the first loop image geometry.
	 * Band height matches the «Подробнее» strip (same formula as {@see syncCatalogCardLayouts} overlay block).
	 *
	 * @param {JQuery} $card
	 */
	function syncCatalogCardLayouts($card) {
		if (!$card || !$card.length) {
			return;
		}
		var cat = data().catalog || {};
		var $img = findCatalogLayoutReferenceImg($card, cat);
		if (!$img.length) {
			return;
		}
		var io = $img.offset();
		var co = $card.offset();
		if (!io || !co) {
			return;
		}
		var ih = $img.outerHeight();
		var iw = $img.outerWidth();
		var band = Math.max(40, Math.min(56, Math.round(ih * 0.26)));
		var topRel = io.top - co.top;
		var leftRel = io.left - co.left;

		var offTop = parseInt(cat.catalogCartIconOffsetTopPx, 10);
		if (isNaN(offTop) || offTop < 0) {
			offTop = 8;
		}
		var offLeft = parseInt(cat.catalogCartIconOffsetLeftPx, 10);
		if (isNaN(offLeft) || offLeft < 0) {
			offLeft = 8;
		}
		var hitSz = parseInt(cat.catalogCartIconHitSizePx, 10);
		if (isNaN(hitSz) || hitSz < 28) {
			hitSz = 36;
		}
		if (hitSz > 56) {
			hitSz = 56;
		}
		if (isCatalogCartIconTouchUi()) {
			var hitTouch = parseInt(cat.catalogCartIconHitSizeTouchPx, 10);
			if (!isNaN(hitTouch) && hitTouch >= 28 && hitTouch <= 56) {
				hitSz = hitTouch;
			}
		}
		var delayMs = parseInt(cat.catalogCartIconTransitionDelayMs, 10);
		if (isNaN(delayMs) || delayMs < 0) {
			delayMs = 0;
		}

		var $overlay = $card.find('.mp-scc-catalog-overlay').first();
		if ($overlay.length) {
			$overlay.css({
				top: topRel + ih - band,
				left: leftRel,
				width: iw,
				height: band
			});
		}

		var $hit = $card.find('.mp-scc-catalog-atc-hit').first();
		if ($hit.length) {
			var hitH = $overlay.length ? Math.max(0, ih - band) : ih;
			$hit.css({
				top: topRel,
				left: leftRel,
				width: iw,
				height: hitH
			});
		}

		var $cartSlot = $card.find('.mp-scc-catalog-cart-icon-slot').first();
		if ($cartSlot.length) {
			$cartSlot.css({
				top: topRel + offTop,
				left: leftRel + offLeft,
				width: hitSz,
				height: hitSz,
				transitionDelay: delayMs + 'ms'
			});
		}
	}

	/**
	 * Re-run layout when the card or first image box changes (resize, lazy-load, filters).
	 *
	 * @param {JQuery} $card
	 */
	function attachCatalogCardResizeSync($card) {
		var cat = data().catalog || {};
		var $img = findCatalogLayoutReferenceImg($card, cat);
		if (!$img.length) {
			return;
		}
		var imgEl = $img.get(0);
		var cardEl = $card.get(0);
		function sync() {
			syncCatalogCardLayouts($card);
		}
		sync();
		var ro = $card.data('mpSccCatalogChromeRo');
		if (ro && typeof ro.disconnect === 'function') {
			ro.disconnect();
		}
		if (window.ResizeObserver && cardEl && imgEl) {
			ro = new ResizeObserver(sync);
			ro.observe(cardEl);
			ro.observe(imgEl);
			$card.data('mpSccCatalogChromeRo', ro);
		}
		if (imgEl && !imgEl.getAttribute('data-mp-scc-catalog-layout-load')) {
			imgEl.setAttribute('data-mp-scc-catalog-layout-load', '1');
			imgEl.addEventListener('load', sync);
		}
	}

	/**
	 * Desktop: invisible hit layer over the first loop image (theme-agnostic).
	 * Prefer stretching the theme’s add-to-cart link when present; otherwise inject a plugin-owned
	 * {@see HTMLButtonElement} — same capture handler + {@see postAjax} as for theme links.
	 */
	function initCatalogAtcHitLayer() {
		var catalog = data().catalog || {};
		var cardSel = catalogLoopCardSelector(catalog);

		function cleanupAtcHitUi() {
			$(cardSel).each(function () {
				var $c = $(this);
				$c.removeClass('mp-scc-catalog-card--atc-hit');
				$c.find('.mp-scc-catalog-atc-hit--proxy').remove();
				$c.find('.mp-scc-catalog-atc-hit').removeClass('mp-scc-catalog-atc-hit').removeAttr('style');
				var ro = $c.data('mpSccCatalogChromeRo');
				if (ro && typeof ro.disconnect === 'function') {
					ro.disconnect();
				}
				$c.removeData('mpSccCatalogChromeRo');
			});
		}

		if (!window.mpScc.flagEnabled('product_image_add_to_cart')) {
			cleanupAtcHitUi();
			return;
		}

		if ((catalog.catalogAddSurface || 'image_click') === 'cart_icon') {
			cleanupAtcHitUi();
			return;
		}

		if (typeof window.matchMedia === 'function' && !window.matchMedia('(min-width: 769px)').matches) {
			cleanupAtcHitUi();
			return;
		}

		var behavior = catalog.imageClickBehavior || 'add_to_cart';
		if (behavior === 'theme_default') {
			cleanupAtcHitUi();
			return;
		}

		$(cardSel).each(function () {
			var $card = $(this);
			if (!findCatalogLayoutReferenceImg($card, catalog).length) {
				return;
			}
			$card.find('.mp-scc-catalog-atc-hit--proxy').remove();

			var $hit = $card
				.find('a.add_to_cart_button, a.ld-sp-add-to-cart')
				.filter(function () {
					var $a = $(this);
					var href = $a.attr('href') || '';
					return href.indexOf('add-to-cart') !== -1 || $a.is('[data-product_id]');
				})
				.first();

			if (!$hit.length) {
				var pid = resolveCatalogProductId($card);
				if (!pid || !$card.hasClass('product-type-simple')) {
					$card.removeClass('mp-scc-catalog-card--atc-hit');
					return;
				}
				var $proxy = $(
					'<button type="button" class="mp-scc-catalog-atc-hit mp-scc-catalog-atc-hit--proxy" />'
				);
				$proxy.attr('aria-label', 'Добавить в корзину');
				$proxy.attr('data-mp-scc-proxy', '1');
				$card.append($proxy);
				$hit = $proxy;
			}

			$card.addClass('mp-scc-catalog-card--atc-hit');
			if (!$hit.hasClass('mp-scc-catalog-atc-hit')) {
				$hit.addClass('mp-scc-catalog-atc-hit');
			}
			attachCatalogCardResizeSync($card);
		});
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
		var cardSel = catalogLoopCardSelector(catalog);
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
		var showMoreLabel = catalog.catalogMoreInfoShowLabel !== false;

		$(cardSel).each(function () {
			var $card = $(this);
			if (!findCatalogLayoutReferenceImg($card, catalog).length) {
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
				if (label) {
					$existing.attr('aria-label', label);
				} else {
					$existing.removeAttr('aria-label');
				}
				var $exLab = $existing.find('.mp-scc-catalog-overlay__label');
				if (showMoreLabel) {
					if (!$exLab.length) {
						$existing.append($('<span class="mp-scc-catalog-overlay__label" />').text(label));
					} else {
						$exLab.text(label);
					}
				} else {
					$exLab.remove();
				}
				attachCatalogCardResizeSync($card);
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
			if (showMoreLabel) {
				$ov.append($('<span class="mp-scc-catalog-overlay__label" />').text(label));
			}
			$card.append($ov);
			attachCatalogCardResizeSync($card);
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

	/**
	 * Coarse pointer / narrow viewport — touch UI rules for catalog cart icon.
	 *
	 * @returns {boolean}
	 */
	function isCatalogCartIconTouchUi() {
		if (typeof window.matchMedia !== 'function') {
			return window.innerWidth <= 768;
		}
		if (window.matchMedia('(max-width: 768px)').matches) {
			return true;
		}
		return window.matchMedia('(pointer: coarse)').matches;
	}

	/**
	 * Mobile visibility override: `force_visible` shows the icon on touch/narrow regardless of tap_reveal.
	 */
	function applyCatalogCartIconMobileModeAttr() {
		var cat = data().catalog || {};
		var mode = cat.catalogCartIconMobileMode || 'inherit';
		if (document.documentElement) {
			document.documentElement.setAttribute('data-mp-scc-cart-icon-mobile-mode', mode);
		}
	}

	/**
	 * Prevent theme/link handlers from seeing icon presses (mousedown/touchstart bubble).
	 *
	 * @param {HTMLElement} el
	 */
	function attachCatalogCartIconPointerGuards(el) {
		if (!el || el.getAttribute('data-mp-scc-cart-pointer-guards')) {
			return;
		}
		el.setAttribute('data-mp-scc-cart-pointer-guards', '1');
		el.addEventListener(
			'mousedown',
			function (e) {
				e.stopPropagation();
			},
			true
		);
		el.addEventListener(
			'touchstart',
			function (e) {
				e.stopPropagation();
			},
			{ capture: true, passive: true }
		);
	}

	/**
	 * Touch: optional first tap on card (not on link/button) reveals the icon.
	 */
	function initCatalogCartIconTouchReveal() {
		if (window.__mpSccCatalogCartIconTouchReveal) {
			return;
		}
		window.__mpSccCatalogCartIconTouchReveal = true;
		document.addEventListener(
			'pointerup',
			function (e) {
				var d = data();
				var cat = d.catalog || {};
				if ((cat.catalogAddSurface || 'image_click') !== 'cart_icon') {
					return;
				}
				if ((cat.catalogCartIconTouch || 'always') !== 'tap_reveal') {
					return;
				}
				if (!isCatalogCartIconTouchUi()) {
					return;
				}
				var t = e.target;
				if (!t || typeof t.closest !== 'function') {
					return;
				}
				var card = t.closest('.mp-scc-catalog-card--cart-icon');
				if (!card) {
					return;
				}
				if (
					t.closest(
						'a, button, input, select, textarea, [data-mp-scc-overlay], .mp-scc-catalog-overlay, .mp-scc-catalog-cart-icon-btn'
					)
				) {
					return;
				}
				card.classList.add('mp-scc-catalog-cart-icon--revealed-touch');
			},
			true
		);
	}

	var catalogChromeLayoutTimer = null;
	function scheduleCatalogChromeLayouts() {
		if (catalogChromeLayoutTimer) {
			clearTimeout(catalogChromeLayoutTimer);
		}
		catalogChromeLayoutTimer = window.setTimeout(function () {
			catalogChromeLayoutTimer = null;
			initCatalogCartIconLayer();
			initCatalogAtcHitLayer();
			initCatalogMoreInfoOverlay();
		}, 80);
	}

	/**
	 * Injects loop «cart» control (catalog_add_surface=cart_icon). Slot: PHP {@see ShopLoopCartIconHost} or JS fallback.
	 * Layout: {@see syncCatalogCardLayouts} positions `.mp-scc-catalog-cart-icon-slot` over the first image box.
	 */
	function initCatalogCartIconLayer() {
		var catalog = data().catalog || {};
		var cardSel = catalogLoopCardSelector(catalog);
		var surface = catalog.catalogAddSurface || 'image_click';

		function cleanupCartIconUi() {
			$(cardSel).each(function () {
				var $c = $(this);
				$c.removeClass(
					'mp-scc-catalog-card--cart-icon mp-scc-catalog-cart-icon--revealed-touch mp-scc-card--hover-intent'
				);
				$c.removeAttr('data-mp-scc-cart-icon-desktop');
				$c.removeAttr('data-mp-scc-cart-icon-touch');
				$c.find('.mp-scc-catalog-cart-icon-btn').remove();
				$c.find('.mp-scc-catalog-cart-icon-slot').remove();
			});
		}

		if (surface !== 'cart_icon' || !window.mpScc.flagEnabled('product_image_add_to_cart')) {
			cleanupCartIconUi();
			return;
		}

		var cfg = window.mpScc.ajaxConfig();
		if (!cfg.ajaxUrl || !cfg.actions.addSimpleProduct) {
			cleanupCartIconUi();
			return;
		}

		var label = window.mpScc.label('catalog_cart_icon');
		if (!String(label || '').trim()) {
			label = 'Добавить в корзину';
		}

		var desk = catalog.catalogCartIconDesktop || 'hover';
		var touch = catalog.catalogCartIconTouch || 'always';

		var presetKey = String(catalog.catalogCartIconPreset || 'classic')
			.trim()
			.replace(/[^a-z0-9_-]/gi, '');
		if (!presetKey) {
			presetKey = 'classic';
		}
		var presetInners = catalog.catalogCartIconPresetInners;
		if (!presetInners || typeof presetInners !== 'object') {
			presetInners = {};
		}
		var innerTpl = presetInners[presetKey] || presetInners.classic || '';
		if (!innerTpl) {
			innerTpl =
				'<circle cx="9" cy="21" r="1" fill="currentColor"/><circle cx="20" cy="21" r="1" fill="currentColor"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" fill="none" stroke="currentColor" stroke-width="__MP_SCC_SW__" stroke-linecap="round" stroke-linejoin="round"/>';
		}

		var strokePx = parseFloat(catalog.catalogCartIconStrokeWidth);
		if (isNaN(strokePx) || strokePx < 1) {
			strokePx = 1.75;
		}
		if (strokePx > 3) {
			strokePx = 3;
		}
		strokePx = Math.round(strokePx * 100) / 100;
		innerTpl = String(innerTpl).split('__MP_SCC_SW__').join(String(strokePx));

		var cartIconSvg =
			'<svg class="mp-scc-catalog-cart-icon-btn__svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">' +
			innerTpl +
			'</svg>';

		$(cardSel).each(function () {
			var $card = $(this);
			if (!findCatalogLayoutReferenceImg($card, catalog).length) {
				return;
			}
			var pid = resolveCatalogProductId($card);
			var isSimple = $card.hasClass('product-type-simple');
			if (!pid || !isSimple) {
				$card.find('.mp-scc-catalog-cart-icon-slot').remove();
				$card.find('.mp-scc-catalog-cart-icon-btn').remove();
				$card.removeClass('mp-scc-catalog-card--cart-icon');
				return;
			}

			$card.attr('data-mp-scc-cart-icon-desktop', desk);
			$card.attr('data-mp-scc-cart-icon-touch', touch);

			var $slot = $card.find('.mp-scc-catalog-cart-icon-slot').first();
			if (!$slot.length) {
				$slot = $(
					'<div class="mp-scc-catalog-cart-icon-slot mp-scc-catalog-cart-icon-slot--js" data-mp-scc-cart-icon-slot="1" aria-hidden="true"></div>'
				);
				$card.append($slot);
			}

			var $btn = $slot.find('.mp-scc-catalog-cart-icon-btn').first();
			if (!$btn.length) {
				$btn = $('<button type="button" class="mp-scc-catalog-cart-icon-btn" data-mp-scc-cart-icon="1" />');
				$btn.attr('aria-label', label);
				$btn.append($('<span class="mp-scc-catalog-cart-icon-btn__icon" aria-hidden="true" />'));
				$slot.append($btn);
			} else {
				$btn.attr('aria-label', label);
			}

			var $iconWrap = $btn.find('.mp-scc-catalog-cart-icon-btn__icon').first();
			if (!$iconWrap.length) {
				$iconWrap = $('<span class="mp-scc-catalog-cart-icon-btn__icon" aria-hidden="true" />');
				$btn.append($iconWrap);
			}
			$iconWrap.empty();
			$iconWrap.html(cartIconSvg);
			$btn.toggleClass('mp-scc-catalog-cart-icon-btn--custom-img', false);

			var isPanelA = presetKey === 'tristate_panel_a';
			$btn.toggleClass('mp-scc-catalog-cart-icon-btn--panel-a', isPanelA);
			if (isPanelA) {
				if (pid) {
					$btn.attr('data-mp-scc-loop-product-id', String(pid));
				} else {
					$btn.removeAttr('data-mp-scc-loop-product-id');
				}
				var $badge = $btn.find('[data-mp-scc-catalog-cart-badge]');
				if (!$badge.length) {
					$badge = $(
						'<span class="mp-scc-catalog-cart-icon-btn__badge" data-mp-scc-catalog-cart-badge="1" hidden="hidden" aria-hidden="true"></span>'
					);
					$btn.append($badge);
				}
			} else {
				$btn.removeClass('mp-scc-catalog-cart-icon-btn--panel-a');
				$btn.removeAttr('data-mp-scc-loop-product-id');
				$btn.find('[data-mp-scc-catalog-cart-badge]').remove();
			}

			var btnEl = $btn.get(0);
			if (btnEl) {
				attachCatalogCartIconPointerGuards(btnEl);
				stampOneCatalogCartIconButton(btnEl);
			}

			$card.addClass('mp-scc-catalog-card--cart-icon');
			attachCatalogCardResizeSync($card);
		});

		stampAllCatalogCartIconButtonsFromCssVars();

		var stickyRef = window.mpScc && window.mpScc.sticky;
		if (stickyRef && typeof window.mpScc.syncCatalogCartProductBadges === 'function') {
			window.mpScc.syncCatalogCartProductBadges(
				stickyRef._lastSnapshotItems || [],
				!!stickyRef._lastSnapshotEmpty
			);
		}
	}

	function initCatalogCartIconHoverIntent() {
		if (window.__mpSccCatalogCartIconHoverIntent) {
			return;
		}
		window.__mpSccCatalogCartIconHoverIntent = true;
		$(document.body)
			.on('mouseenter.mpSccCartIcon', '.mp-scc-catalog-card--cart-icon', function () {
				$(this).addClass('mp-scc-card--hover-intent');
			})
			.on('mouseleave.mpSccCartIcon', '.mp-scc-catalog-card--cart-icon', function () {
				$(this).removeClass('mp-scc-card--hover-intent');
			});
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

	/**
	 * Default + fallback selectors: some themes omit `.woocommerce` wrapper or use `div.products`.
	 */
	var CATALOG_IMAGE_CLICK_SELECTOR_DEFAULT =
		'ul.products li.product img, ul.products div.product img, .woocommerce ul.products li.product img, .woocommerce ul.products div.product img, .products li.product img, .products div.product img, div.products div.product img';

	function isCatalogImageZoneTarget(t) {
		if (!t || !t.closest) {
			return false;
		}
		return !!t.closest(
			'.ld-sp-img, .ld-sp-img-gallery, .ld-sp-img-gal-trigger, a.woocommerce-LoopProduct-link, a.woocommerce-loop-product__link'
		);
	}

	/**
	 * Resolve product thumbnail {@link HTMLImageElement} from click target (img, picture, anchor wrapping img, theme wrappers).
	 *
	 * @param {EventTarget|null} rawTarget
	 * @param {Record<string, *>} catalog
	 * @returns {HTMLImageElement|null}
	 */
	function resolveCatalogImageFromClickTarget(rawTarget, catalog) {
		if ((catalog.catalogAddSurface || 'image_click') === 'cart_icon') {
			return null;
		}
		var t = rawTarget;
		if (!t || !t.nodeType) {
			return null;
		}
		if (t.nodeType === 3 && t.parentElement) {
			t = t.parentElement;
		}
		if (!t || !t.closest) {
			return null;
		}
		if (t.closest('.add_to_cart_button, a.add_to_cart_button, button.single_add_to_cart_button')) {
			return null;
		}
		if (t.closest('a[href*="add-to-cart"]')) {
			return null;
		}
		if (t.matches && t.matches('a[href*="add-to-cart"]')) {
			return null;
		}
		if (t.matches && t.matches('button, input, textarea, select')) {
			return null;
		}
		if (t.closest('.footer-product, .content-product-hover, .product-hover')) {
			return null;
		}
		var card = findProductCardElement(t, catalog);
		if (!card) {
			return null;
		}
		var img = null;
		if (t.nodeName === 'IMG') {
			img = t;
		} else {
			var pic = t.closest('picture');
			if (pic) {
				img = pic.querySelector('img');
			}
			if (!img && t.nodeName === 'A') {
				img = t.querySelector('img');
			}
			if (!img) {
				var loopLink = t.closest('a.woocommerce-LoopProduct-link');
				if (loopLink) {
					img = loopLink.querySelector('img');
				}
			}
			if (!img) {
				var ph = t.closest('.product-image, .product-content-image, .content-product');
				if (
					ph &&
					card.contains(ph) &&
					!t.closest('.footer-product, .content-product-hover, .product-hover')
				) {
					img = ph.querySelector('img');
				}
			}
			if (!img && isCatalogImageZoneTarget(t)) {
				img =
					card.querySelector('figure.ld-sp-img img') ||
					card.querySelector('.ld-sp-img img') ||
					card.querySelector('a.woocommerce-LoopProduct-link img') ||
					card.querySelector('img');
			}
		}
		if (!img || img.nodeName !== 'IMG' || !card.contains(img)) {
			return null;
		}
		return img;
	}

	/**
	 * @param {HTMLImageElement} img
	 * @param {Record<string, *>} catalog
	 * @returns {boolean}
	 */
	function catalogImageMatchesConfiguredSelector(img, catalog) {
		if ((catalog.catalogAddSurface || 'image_click') === 'cart_icon') {
			return false;
		}
		var raw = catalog.imageClickSelector != null ? String(catalog.imageClickSelector).trim() : '';
		var sel = raw || CATALOG_IMAGE_CLICK_SELECTOR_DEFAULT;
		try {
			if (img.matches(sel)) {
				return true;
			}
		} catch (err) {}
		var card = findProductCardElement(img, catalog);
		return !!(card && card.contains(img) && resolveCatalogProductId($(card)) > 0);
	}

	/**
	 * Shared AJAX path for catalog loop add-to-cart (same endpoint as image-click capture).
	 *
	 * @param {JQuery} $card
	 * @param {number} productId
	 * @param {JQuery} $triggerForAddedEvent
	 * @param {Record<string, *>} cat
	 * @param {Record<string, *>} d mpSccData root
	 * @param {string} [telemetrySurface] Optional: image | cart_icon | atc_hit (client diagnostics).
	 */
	function executeCatalogLoopAddSimpleAjax($card, productId, $triggerForAddedEvent, cat, d, telemetrySurface) {
		setCatalogCardLoading($card, true);
		$card.removeClass('mp-scc-card--error');

		window.mpScc
			.postAjax('addSimpleProduct', { product_id: productId, quantity: 1 })
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					setCatalogCardLoading($card, false);
					$card.data('mpSccAtcCooldownUntil', Date.now() + CATALOG_ATC_POST_SUCCESS_COOLDOWN_MS);
					triggerCatalogAddedAnimation($card);
					$(document.body).trigger('added_to_cart', [{}, '', $triggerForAddedEvent]);
					if (telemetrySurface && window.mpScc.logClientEvent) {
						window.mpScc.logClientEvent('catalog_loop_add_success', {
							product_id: productId,
							surface: telemetrySurface
						});
					}
					return;
				}
				setCatalogCardLoading($card, false);
				$card.addClass('mp-scc-card--error');
				window.setTimeout(function () {
					$card.removeClass('mp-scc-card--error');
				}, 500);
				var respD = resp && resp.data ? resp.data : {};
				var code = respD.code ? String(respD.code) : '';
				var errMsg = respD.message ? String(respD.message) : window.mpScc.label('out_of_stock');
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
				var msg = d.networkErrorMessage ? String(d.networkErrorMessage) : '';
				if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					msg = String(xhr.responseJSON.data.message);
				}
				showCatalogToast($card, msg, { variant: 'error', assertive: true });
			});
	}

	/**
	 * Invisible stretched theme ATC link ({@see initCatalogAtcHitLayer}): do NOT rely on Woo/theme JS —
	 * it often never fires; we handle here with the same plugin AJAX as the image capture path.
	 *
	 * @returns {boolean} True if the click was consumed (caller must stop propagation).
	 */
	function handleCatalogAtcHitLayerClick(e, cat, d) {
		var t = e.target;
		if (t && t.nodeType === 3 && t.parentElement) {
			t = t.parentElement;
		}
		if (!t || !t.closest) {
			return false;
		}
		var hit = t.closest('.mp-scc-catalog-atc-hit');
		if (!hit) {
			return false;
		}
		if (typeof window.matchMedia === 'function' && !window.matchMedia('(min-width: 769px)').matches) {
			return false;
		}
		var cardEl = findProductCardElement(hit, cat);
		if (!cardEl) {
			return false;
		}
		var $card = $(cardEl);
		if ($card.attr('data-mp-scc-atc-busy') === '1') {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}
		if ($(t).closest('[data-mp-scc-overlay], .mp-scc-catalog-overlay').length) {
			return false;
		}
		var coolUntil = $card.data('mpSccAtcCooldownUntil');
		if (typeof coolUntil === 'number' && Date.now() < coolUntil) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}

		var productId = resolveCatalogProductId($card);
		if (!productId) {
			var resolveMsg = cat.resolveErrorMessage ? String(cat.resolveErrorMessage) : '';
			showCatalogToast($card, resolveMsg, { variant: 'error', assertive: true });
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
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
					context: 'atc_hit'
				});
			}
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}

		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();

		executeCatalogLoopAddSimpleAjax($card, productId, $(hit), cat, d, 'atc_hit');
		return true;
	}

	/**
	 * Cart icon on loop card (catalog_add_surface=cart_icon): same AJAX as image / atc hit.
	 *
	 * @returns {boolean} True if the click was consumed.
	 */
	function handleCatalogCartIconClick(e, cat, d) {
		var t = e.target;
		if (t && t.nodeType === 3 && t.parentElement) {
			t = t.parentElement;
		}
		if (!t || !t.closest) {
			return false;
		}
		var btn = t.closest('.mp-scc-catalog-cart-icon-btn');
		if (!btn) {
			return false;
		}
		var cardEl = findProductCardElement(btn, cat);
		if (!cardEl) {
			return false;
		}
		var $card = $(cardEl);
		if ($card.attr('data-mp-scc-atc-busy') === '1') {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}
		if ($(t).closest('[data-mp-scc-overlay], .mp-scc-catalog-overlay').length) {
			return false;
		}
		var coolUntil = $card.data('mpSccAtcCooldownUntil');
		if (typeof coolUntil === 'number' && Date.now() < coolUntil) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}

		var productId = resolveCatalogProductId($card);
		if (!productId) {
			var resolveMsg = cat.resolveErrorMessage ? String(cat.resolveErrorMessage) : '';
			showCatalogToast($card, resolveMsg, { variant: 'error', assertive: true });
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
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
					context: 'cart_icon'
				});
			}
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			return true;
		}

		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();

		executeCatalogLoopAddSimpleAjax($card, productId, $(btn), cat, d, 'cart_icon');
		return true;
	}

	/**
	 * Registers one `window` capture listener for catalog loop add: image path (v1), icon button (v2),
	 * or desktop ATC hit layer — all call {@see executeCatalogLoopAddSimpleAjax}. In v2, image clicks
	 * are never intercepted (handler returns after optional icon handling).
	 */
	function initCatalogImageAddToCart() {
		var catalog = data().catalog || {};
		var behavior = catalog.imageClickBehavior || 'add_to_cart';
		var surface = catalog.catalogAddSurface || 'image_click';
		if (surface !== 'cart_icon' && behavior === 'theme_default') {
			return;
		}
		if (!window.mpScc.flagEnabled('product_image_add_to_cart')) {
			return;
		}
		var cfg = window.mpScc.ajaxConfig();
		if (!cfg.ajaxUrl || !cfg.actions.addSimpleProduct) {
			return;
		}

		if (window.__mpSccCatalogImgCapture) {
			return;
		}
		window.__mpSccCatalogImgCapture = true;

		/**
		 * Capture on `window` (not `document`) so we run before other capture listeners on `document`
		 * (Elementor / theme scripts often use `document.addEventListener(..., true)` + stopImmediatePropagation).
		 * Also registered synchronously at script load (see call site below), not only on jQuery ready.
		 */
		window.addEventListener(
			'click',
			function mpSccCatalogImageCapture(e) {
				// Some Chromium forks may omit `button` on synthesized click events.
				if (typeof e.button === 'number' && e.button !== 0) {
					return;
				}
				var d = data();
				var cat = d.catalog || {};
				var beh = cat.imageClickBehavior || 'add_to_cart';
				var surf = cat.catalogAddSurface || 'image_click';
				if (surf !== 'cart_icon' && beh === 'theme_default') {
					return;
				}
				if (!window.mpScc.flagEnabled('product_image_add_to_cart')) {
					return;
				}
				var cfg2 = window.mpScc.ajaxConfig();
				if (!cfg2.ajaxUrl || !cfg2.actions.addSimpleProduct) {
					return;
				}

				if (surf === 'cart_icon') {
					handleCatalogCartIconClick(e, cat, d);
					return;
				}

				if (handleCatalogAtcHitLayerClick(e, cat, d)) {
					return;
				}

				var imgEl = resolveCatalogImageFromClickTarget(e.target, cat);
				if (!imgEl) {
					return;
				}
				var inImageZone = isCatalogImageZoneTarget(e.target);
				var selectorMatches = catalogImageMatchesConfiguredSelector(imgEl, cat);
				if (!selectorMatches && !inImageZone) {
					return;
				}

				// Gallery trigger overlays often sit above the image anchor; stop navigation unconditionally here.
				if (inImageZone) {
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
				}

				var $img = $(imgEl);
				var cardEl = findProductCardElement(imgEl, cat);
				if (!cardEl) {
					return;
				}
				var $card = $(cardEl);
				if ($card.attr('data-mp-scc-atc-busy') === '1') {
					return;
				}

				var imgTitleSels = cat.imageTitleBlockSelectors || [];
				var ib;
				for (ib = 0; ib < imgTitleSels.length; ib++) {
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
					var resolveMsg = cat.resolveErrorMessage ? String(cat.resolveErrorMessage) : '';
					showCatalogToast($card, resolveMsg, { variant: 'error', assertive: true });
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
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
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
					return;
				}

				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();

				executeCatalogLoopAddSimpleAjax($card, productId, $img, cat, d, 'image');
			},
			true
		);
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
				return;
			}
			if (!$('#mp-scc-sticky-root').length && mpSccStickyHideWhenEmptyEnabled()) {
				mpSccScheduleDeferredFloatingStickySnapshot();
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
	 * Show/hide the drawer empty-state block. Uses `!important` inline display so theme CSS on
	 * `#mp-scc-drawer-empty` cannot keep it visible when the cart has lines.
	 *
	 * @param {JQuery} $empty
	 * @param {boolean} visible
	 */
	function setDrawerEmptyBlockVisible($empty, visible) {
		if (!$empty || !$empty.length) {
			return;
		}
		$empty.each(function () {
			var el = this;
			if (visible) {
				el.classList.remove('mp-scc-drawer-empty--off');
				el.removeAttribute('hidden');
				el.removeAttribute('aria-hidden');
				el.style.removeProperty('display');
			} else {
				el.classList.add('mp-scc-drawer-empty--off');
				el.setAttribute('hidden', 'hidden');
				el.setAttribute('aria-hidden', 'true');
				el.style.setProperty('display', 'none', 'important');
			}
		});
	}

	/**
	 * Sticky bar + drawer: lifecycle, drawer toggle, cart snapshot UI, debounced qty, request lock.
	 */
	function StickyCartController(root) {
		this.$root = $(root);
		this.$drawer = this.$root.find('[data-mp-scc-drawer]');
		this.$toggle = this.$root.find('[data-mp-scc-drawer-toggle]');
		this.$summary = this.$root.find('.mp-scc-sticky-summary');
		this.$lineCount = this.$root.find('[data-mp-scc-cart-line-count]');
		this.$qtyCount = this.$root.find('[data-mp-scc-cart-qty-count]');
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
		this._mpSccDestroyed = false;
		/** Last cart snapshot lines (for catalog FAB-style badges). */
		this._lastSnapshotItems = [];
		this._lastSnapshotEmpty = true;
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
			drawer.setAttribute('aria-modal', 'true');
			toggle.setAttribute('aria-expanded', 'true');
		} else {
			drawer.setAttribute('hidden', '');
			drawer.removeAttribute('aria-modal');
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
		if (this._mpSccDestroyed) {
			return;
		}
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
		if (this._mpSccDestroyed) {
			return;
		}
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
		if (this._mpSccDestroyed) {
			return;
		}
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
			if (self._mpSccDestroyed) {
				return;
			}
			if (self._lastSnapshotAppliedAt >= scheduledAt) {
				return;
			}
			self.refresh();
		}, WOO_CART_SYNC_FALLBACK_MS);
	};

	StickyCartController.prototype.applyPayload = function (p) {
		if (this._mpSccDestroyed) {
			return;
		}
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

		var prevLineText = this.$lineCount.length ? this.$lineCount.first().text() : '';
		var prevQtyText = this.$qtyCount.length ? this.$qtyCount.first().text() : '';
		var prevSubHtml = this.$total.html();

		this.$lineCount.text(String(lineCount));
		this.$qtyCount.text(String(qty));

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
			this.pulseSummaryEl(this.$lineCount);
		}
		if (String(prevQtyText) !== String(qty)) {
			this.pulseSummaryEl(this.$qtyCount);
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
			this.$items.attr('hidden', 'hidden').attr('aria-hidden', 'true').css('display', 'none');
			setDrawerEmptyBlockVisible(this.$empty, true);
			this.$drawer.addClass('mp-scc-drawer--show-empty').removeClass('mp-scc-drawer--show-items');
		} else {
			this.$items.removeAttr('hidden').removeAttr('aria-hidden').css('display', '');
			setDrawerEmptyBlockVisible(this.$empty, false);
			this.renderLineItems(items);
			this.$drawer.addClass('mp-scc-drawer--show-items').removeClass('mp-scc-drawer--show-empty');
		}

		this.$root.attr('data-mp-scc-cart-empty', empty ? '1' : '0');
		this.$root.toggleClass('mp-scc-sticky--empty', !!empty);

		this._lastSnapshotItems = items;
		this._lastSnapshotEmpty = !!empty;
		if (typeof window.mpScc.syncCatalogCartProductBadges === 'function') {
			window.mpScc.syncCatalogCartProductBadges(items, !!empty);
		}

		this.syncStickyActions(empty);

		if (this.cartUiShell && typeof this.cartUiShell.onStickyPayloadApplied === 'function') {
			this.cartUiShell.onStickyPayloadApplied({ empty: empty });
		}

		if (mpSccStickyHideWhenEmptyEnabled() && empty && typeof window.mpScc.teardownFloatingStickyShell === 'function') {
			window.mpScc.teardownFloatingStickyShell();
		}
	};

	/**
	 * @param {boolean} empty Whether the cart has no line items.
	 */
	StickyCartController.prototype.syncStickyActions = function (empty) {
		this.syncCheckoutState(empty);
		var $clear = this.$root.find('[data-mp-scc-clear-cart]');
		if ($clear.length) {
			if (empty) {
				$clear.each(function () {
					var $b = $(this);
					var clearAria = $b.attr('data-mp-scc-clear-aria-disabled') || '';
					$b.addClass('mp-scc-clear-cart--disabled').prop('disabled', true).attr('aria-disabled', 'true');
					if (clearAria) {
						$b.attr('aria-label', clearAria).attr('title', clearAria);
					}
				});
			} else {
				$clear.each(function () {
					var $b = $(this);
					$b.removeClass('mp-scc-clear-cart--disabled').prop('disabled', false).removeAttr('aria-disabled');
					var lbl = $b.attr('data-mp-scc-clear-label');
					if (lbl) {
						$b.attr('aria-label', lbl).attr('title', lbl);
					} else {
						$b.removeAttr('aria-label').removeAttr('title');
					}
				});
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
		if (empty) {
			$a.each(function () {
				var $el = $(this);
				var disabledAria = $el.attr('data-mp-scc-checkout-aria-disabled') || '';
				$el.addClass('mp-scc-checkout--disabled');
				$el.attr('href', '#');
				$el.attr('aria-disabled', 'true');
				$el.attr('tabindex', '-1');
				if (disabledAria) {
					$el.attr('aria-label', disabledAria).attr('title', disabledAria);
				}
			});
		} else {
			$a.each(function () {
				var $el = $(this);
				var base = $el.attr('data-mp-scc-checkout-base') || '';
				$el.removeClass('mp-scc-checkout--disabled');
				$el.removeAttr('aria-disabled');
				$el.removeAttr('tabindex');
				if (base) {
					var merged = window.mpScc.mergeUrlWithLocationQuery(base);
					$el.attr('href', merged);
				}
				var lbl = $el.attr('data-mp-scc-checkout-label');
				if (lbl) {
					$el.attr('aria-label', lbl).attr('title', lbl);
				} else {
					$el.removeAttr('aria-label').removeAttr('title');
				}
			});
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
		if (this._mpSccDestroyed) {
			this.mutationQueue = [];
			return;
		}
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
		if (this._mpSccDestroyed) {
			return;
		}
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
					var d = resp.data;
					var hideEmpty = mpSccStickyHideWhenEmptyEnabled();
					var okMsg = window.mpScc.label('line_removed') || 'Позиция удалена';
					if (d.is_empty && hideEmpty) {
						self.showStickyInlineFeedback(okMsg, 'success');
					}
					self.applyPayload(d);
					$(document.body).trigger('removed_from_cart');
					if (!d.is_empty || !hideEmpty) {
						self.showStickyInlineFeedback(okMsg, 'success');
					}
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
				if (!self._mpSccDestroyed) {
					self.drainMutationQueue();
				}
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
			if (self._mpSccDestroyed) {
				delete self.pendingQty[cartItemKey];
				return;
			}
			var q = self.pendingQty[cartItemKey];
			delete self.pendingQty[cartItemKey];
			self.commitQuantity(cartItemKey, q);
		}, this.debounceMs);
	};

	StickyCartController.prototype.commitQuantity = function (cartItemKey, quantity) {
		if (this._mpSccDestroyed) {
			return;
		}
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
				if (!self._mpSccDestroyed) {
					self.drainMutationQueue();
				}
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
		if (this._mpSccDestroyed) {
			return;
		}
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
		if (this._mpSccDestroyed) {
			return;
		}
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
				if (self._mpSccDestroyed) {
					self.snapshotLocked = false;
					self.pendingRefresh = false;
					return;
				}
				self.$summary.removeClass('mp-scc-sticky-summary--loading');
				self.snapshotLocked = false;
				if (self.pendingRefresh) {
					self.pendingRefresh = false;
					self.refresh();
				}
			});
	};

	StickyCartController.prototype.destroy = function () {
		this._mpSccDestroyed = true;
		if (this.reconcileTimer) {
			clearTimeout(this.reconcileTimer);
			this.reconcileTimer = null;
		}
		if (this.wooEventDebounceTimer) {
			clearTimeout(this.wooEventDebounceTimer);
			this.wooEventDebounceTimer = null;
		}
		if (this.wooFallbackTimer) {
			clearTimeout(this.wooFallbackTimer);
			this.wooFallbackTimer = null;
		}
		var qk;
		for (qk in this.qtyTimers) {
			if (Object.prototype.hasOwnProperty.call(this.qtyTimers, qk)) {
				clearTimeout(this.qtyTimers[qk]);
			}
		}
		this.qtyTimers = {};
		if (this.cartUiShell && typeof this.cartUiShell.destroy === 'function') {
			this.cartUiShell.destroy();
			this.cartUiShell = null;
		}
		$(document.body).off('.mpSccStickyWoo');
		if (this.$toggle && this.$toggle.length) {
			this.$toggle.off();
		}
		if (this.$root && this.$root.length) {
			this.$root.off();
		}
	};

	StickyCartController.prototype.bind = function () {
		var self = this;

		this.$toggle.on('click', function () {
			if (self.cartUiShell && window.mpSccCartUiShell && window.mpSccCartUiShell.ACTION) {
				self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.TOGGLE_C);
				return;
			}
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

		this.$root.on('click', '[data-mp-scc-toggle-c]', function (e) {
			e.preventDefault();
			if (self.cartUiShell && window.mpSccCartUiShell && window.mpSccCartUiShell.ACTION) {
				var mode = $(e.currentTarget).attr('data-mp-scc-toggle-c-mode') || '';
				if (mode === 'close') {
					self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.CLOSE_C);
				} else if (mode === 'open') {
					self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.OPEN_C);
				} else {
					self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.TOGGLE_C);
				}
			}
		});

		this.$root.on('click', '[data-mp-scc-shell-dismiss]', function (e) {
			e.preventDefault();
			if (!self.cartUiShell || !window.mpSccCartUiShell || !window.mpSccCartUiShell.ACTION) {
				return;
			}
			var which = ($(e.currentTarget).attr('data-mp-scc-shell-dismiss') || '').toLowerCase();
			if (which === 'b') {
				self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.CLOSE_B);
			} else if (which === 'c') {
				self.cartUiShell.dispatch(window.mpSccCartUiShell.ACTION.CLOSE_C);
			}
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
			var $allClear = self.$root.find('[data-mp-scc-clear-cart]').filter(function () {
				var $b = $(this);
				return !$b.hasClass('mp-scc-clear-cart--disabled') && !$b.prop('disabled');
			});
			if (!$allClear.length) {
				return;
			}
			self.clearCartLocked = true;
			var loadingMsg =
				(typeof window.mpScc.label === 'function' && window.mpScc.label('clear_cart_in_progress')) ||
				'Очистка корзины…';
			$allClear
				.prop('disabled', true)
				.attr('aria-busy', 'true')
				.addClass('mp-scc-clear-cart--loading')
				.attr('aria-label', loadingMsg)
				.attr('title', loadingMsg);
			window.mpScc
				.postAjax('clearCart', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						var d = resp.data;
						var hideEmpty = mpSccStickyHideWhenEmptyEnabled();
						var okMsg = window.mpScc.label('cart_cleared') || 'Корзина очищена';
						if (d.is_empty && hideEmpty) {
							self.showStickyInlineFeedback(okMsg, 'success');
						}
						self.applyPayload(d);
						$(document.body).trigger('removed_from_cart');
						if (!d.is_empty || !hideEmpty) {
							self.showStickyInlineFeedback(okMsg, 'success');
						}
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
					if (self._mpSccDestroyed) {
						return;
					}
					self.$root.find('[data-mp-scc-clear-cart]').each(function () {
						var $b = $(this);
						$b.removeAttr('aria-busy').removeClass('mp-scc-clear-cart--loading');
					});
					var isEmpty = self.$root.attr('data-mp-scc-cart-empty') === '1';
					self.syncStickyActions(isEmpty);
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
			'added_to_cart.mpSccStickyWoo removed_from_cart.mpSccStickyWoo wc_fragments_refreshed.mpSccStickyWoo updated_cart_totals.mpSccStickyWoo wc_fragments_loaded.mpSccStickyWoo updated_wc_div.mpSccStickyWoo',
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
		applyCatalogCartIconCssVarsFromPayload();
		initCatalogCartIconPaintHammer();
		initClientDiagnostics();
		initWishlistIntegrationBodyClass();
		applyCatalogCartIconMobileModeAttr();
		initCatalogCartIconTouchReveal();
		initCatalogCartIconHoverIntent();

		// XStore/etheme: image block moves on :hover; re-sync chrome so the cart slot stays on the photo.
		$(document.body).on(
			'mouseenter.mpSccCatalogLayoutHover mouseleave.mpSccCatalogLayoutHover',
			'.mp-scc-catalog-card--cart-icon',
			function () {
				var $c = $(this);
				syncCatalogCardLayouts($c);
				if (window.requestAnimationFrame) {
					window.requestAnimationFrame(function () {
						syncCatalogCardLayouts($c);
					});
				}
			}
		);

		var $root = $('#mp-scc-sticky-root');
		if ($root.length) {
			try {
				var sticky = new StickyCartController($root[0]);
				if (window.mpSccCartUiShell && typeof window.mpSccCartUiShell.attachSticky === 'function') {
					window.mpSccCartUiShell.attachSticky(sticky);
				}
				sticky.init();
				window.mpScc.sticky = sticky;
			} catch (e) {
				if (window.mpScc.reportStickyError) {
					window.mpScc.reportStickyError(
						'sticky_init_failed',
						e && e.message ? String(e.message) : String(e),
						e && e.stack ? String(e.stack).slice(0, 500) : ''
					);
				}
			}
		}

		initVariableProductVariationGuard();
		initSingleProductAddToCart();

		initCatalogOverlayPropagation();
		initCatalogTitleClickHook();
		initCatalogOverlayKeyboard();
		scheduleCatalogChromeLayouts();
		runCatalogTitleLinkSanity();
		warnDuplicateWishlistButtonsInCard();

		$(document.body).on(
			'wc_fragments_refreshed updated_wc_div etheme_ajax_loaded post-load',
			scheduleCatalogChromeLayouts
		);

		var catalogChromeResizeTimer = null;
		$(window).on('resize.mpSccCatalogChrome orientationchange.mpSccCatalogChrome', function () {
			if (catalogChromeResizeTimer) {
				clearTimeout(catalogChromeResizeTimer);
			}
			catalogChromeResizeTimer = window.setTimeout(function () {
				catalogChromeResizeTimer = null;
				scheduleCatalogChromeLayouts();
			}, 120);
		});

		window.mpScc.refreshCatalogOverlay = scheduleCatalogChromeLayouts;

		$(window.document).trigger('mpScc:ready');

		mpSccRegisterDeferredFloatingStickyListener();
	});

	initCatalogImageAddToCart();
})(window, window.jQuery);
