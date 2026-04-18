/**
 * Tri-state shell for sticky cart (dp.md §17.1): hidden | A | B | C.
 *
 * - Single transition table + dispatch(); cart UI syncs via attachSticky().
 * - Escape: C → B (if panel B exists) → A; B → A.
 * - No session persistence: reset on pagehide; hydrate from DOM on load / pageshow.
 * - Elevated z-index + focus trap while C (drawer open).
 *
 * @package MpStickyCustomCart
 */
(function (window, document) {
	'use strict';

	var STATES = {
		HIDDEN: 'hidden',
		A: 'A',
		B: 'B',
		C: 'C',
	};

	var ACTION = {
		TOGGLE_C: 'TOGGLE_C',
		OPEN_C: 'OPEN_C',
		CLOSE_C: 'CLOSE_C',
		OPEN_B: 'OPEN_B',
		CLOSE_B: 'CLOSE_B',
		ESCAPE: 'ESCAPE',
		HYDRATE: 'HYDRATE',
		FORCE_A: 'FORCE_A',
	};

	/**
	 * @param {string} from
	 * @param {string} to
	 * @param {{ hasPanelB: boolean, drawerEnabled: boolean }} ctx
	 * @returns {boolean}
	 */
	function canTransition(from, to, ctx) {
		if (from === to) {
			return true;
		}
		if (to === STATES.HIDDEN) {
			return from === STATES.A || from === STATES.B || from === STATES.C;
		}
		if (to === STATES.A) {
			return from === STATES.B || from === STATES.C || from === STATES.HIDDEN;
		}
		if (to === STATES.B) {
			if (!ctx.hasPanelB) {
				return false;
			}
			return from === STATES.A || from === STATES.C;
		}
		if (to === STATES.C) {
			if (!ctx.drawerEnabled) {
				return false;
			}
			return from === STATES.A || from === STATES.B;
		}
		return false;
	}

	/**
	 * @param {string} state
	 * @param {string} action
	 * @param {{ hasPanelB: boolean, drawerEnabled: boolean }} ctx
	 * @returns {string|null} next state or null if noop
	 */
	function reduce(state, action, ctx) {
		switch (action) {
			case ACTION.HYDRATE:
				return state;
			case ACTION.FORCE_A:
				return STATES.A;
			case ACTION.OPEN_B:
				if (!ctx.hasPanelB) {
					return null;
				}
				if (state === STATES.A && canTransition(STATES.A, STATES.B, ctx)) {
					return STATES.B;
				}
				return null;
			case ACTION.CLOSE_B:
				if (state === STATES.B && canTransition(STATES.B, STATES.A, ctx)) {
					return STATES.A;
				}
				return null;
			case ACTION.OPEN_C:
				if (!ctx.drawerEnabled) {
					return null;
				}
				if (state === STATES.A || state === STATES.B) {
					return STATES.C;
				}
				return null;
			case ACTION.CLOSE_C:
				if (state !== STATES.C) {
					return null;
				}
				if (ctx.hasPanelB) {
					return STATES.B;
				}
				return STATES.A;
			case ACTION.TOGGLE_C:
				if (!ctx.drawerEnabled) {
					return null;
				}
				if (state === STATES.C) {
					return ctx.hasPanelB ? STATES.B : STATES.A;
				}
				if (state === STATES.A || state === STATES.B) {
					return STATES.C;
				}
				return null;
			case ACTION.ESCAPE:
				if (state === STATES.C) {
					return ctx.hasPanelB ? STATES.B : STATES.A;
				}
				if (state === STATES.B) {
					return STATES.A;
				}
				return null;
			default:
				return null;
		}
	}

	function hasPanelBEl(root) {
		return !!(root && root.querySelector && root.querySelector('[data-mp-scc-shell-panel-b]'));
	}

	function CartUiShellStateMachine(sticky) {
		this.sticky = sticky;
		this.$root = sticky.$root;
		this.rootEl = sticky.$root && sticky.$root.length ? sticky.$root[0] : null;
		this._state = STATES.A;
		this._listeners = [];
		this._onDocKey = this._onDocKey.bind(this);
		this._onPageHide = this._onPageHide.bind(this);
		this._onPageShow = this._onPageShow.bind(this);
		this._trapBound = false;
		this._preFocus = null;
		this._elevated = false;
	}

	CartUiShellStateMachine.prototype._ctx = function () {
		var drawerEnabled = !!(this.sticky.$drawer && this.sticky.$drawer.length);
		return {
			hasPanelB: hasPanelBEl(this.rootEl),
			drawerEnabled: drawerEnabled,
		};
	};

	CartUiShellStateMachine.prototype.getState = function () {
		return this._state;
	};

	CartUiShellStateMachine.prototype.subscribe = function (fn) {
		if (typeof fn !== 'function') {
			return function () {};
		}
		this._listeners.push(fn);
		var self = this;
		return function () {
			self._listeners = self._listeners.filter(function (f) {
				return f !== fn;
			});
		};
	};

	CartUiShellStateMachine.prototype._emit = function (prev, next) {
		var self = this;
		this._listeners.forEach(function (fn) {
			try {
				fn(next, prev, self);
			} catch (e) {
				/* swallow subscriber errors */
			}
		});
	};

	CartUiShellStateMachine.prototype._setDomStateAttr = function () {
		if (!this.rootEl) {
			return;
		}
		this.rootEl.setAttribute('data-mp-scc-shell-state', this._state);
	};

	CartUiShellStateMachine.prototype._syncDrawerDom = function () {
		var wantOpen = this._state === STATES.C;
		if (this.sticky && typeof this.sticky.setDrawerOpen === 'function') {
			this.sticky.setDrawerOpen(wantOpen);
		}
	};

	CartUiShellStateMachine.prototype._syncPanelBPlaceholder = function () {
		/* Phase 17.3: mount B panel; until then hasPanelB is false. */
	};

	CartUiShellStateMachine.prototype._setElevated = function (on) {
		var html = document.documentElement;
		if (!html) {
			return;
		}
		if (on === this._elevated) {
			return;
		}
		this._elevated = on;
		if (on) {
			html.setAttribute('data-mp-scc-cart-shell-elevated', '1');
			try {
				html.style.setProperty('--mp-scc-cart-shell-z-boost', '12000');
			} catch (e) {
				html.style.setProperty('--mp-scc-cart-shell-z-boost', '12000');
			}
		} else {
			html.removeAttribute('data-mp-scc-cart-shell-elevated');
			try {
				html.style.removeProperty('--mp-scc-cart-shell-z-boost');
			} catch (e2) {
				html.style.removeProperty('--mp-scc-cart-shell-z-boost');
			}
		}
	};

	CartUiShellStateMachine.prototype._focusTrapStart = function () {
		var drawer = this.rootEl ? this.rootEl.querySelector('[data-mp-scc-drawer]') : null;
		if (!drawer || drawer.hasAttribute('hidden')) {
			return;
		}
		this._preFocus = document.activeElement;
		var nodes = [];
		try {
			nodes = Array.prototype.slice.call(
				drawer.querySelectorAll(
					'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
				)
			);
		} catch (e) {
			nodes = [];
		}
		nodes = nodes.filter(function (el) {
			return el && el.offsetParent !== null && el.getAttribute('aria-hidden') !== 'true';
		});
		if (nodes.length) {
			try {
				nodes[0].focus();
			} catch (e2) {
				/* ignore */
			}
		}
		if (this._trapBound) {
			return;
		}
		this._trapBound = true;
		document.addEventListener('keydown', this._onDocKey, true);
	};

	CartUiShellStateMachine.prototype._focusTrapStop = function () {
		if (this._trapBound) {
			this._trapBound = false;
			document.removeEventListener('keydown', this._onDocKey, true);
		}
		if (this._preFocus && typeof this._preFocus.focus === 'function') {
			try {
				this._preFocus.focus();
			} catch (e) {
				/* ignore */
			}
		}
		this._preFocus = null;
	};

	CartUiShellStateMachine.prototype._onDocKey = function (e) {
		if (this._state !== STATES.C) {
			return;
		}
		var drawer = this.rootEl ? this.rootEl.querySelector('[data-mp-scc-drawer]') : null;
		if (!drawer || drawer.hasAttribute('hidden')) {
			return;
		}
		var active = document.activeElement;
		if (!drawer.contains(active)) {
			return;
		}
		if (e.key === 'Tab') {
			var nodes = [];
			try {
				nodes = Array.prototype.slice.call(
					drawer.querySelectorAll(
						'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
					)
				);
			} catch (err) {
				nodes = [];
			}
			nodes = nodes.filter(function (el) {
				return el && el.offsetParent !== null && el.getAttribute('aria-hidden') !== 'true';
			});
			if (nodes.length < 1) {
				return;
			}
			var first = nodes[0];
			var last = nodes[nodes.length - 1];
			var active = document.activeElement;
			if (e.shiftKey) {
				if (active === first || !drawer.contains(active)) {
					e.preventDefault();
					try {
						last.focus();
					} catch (e2) {
						/* ignore */
					}
				}
			} else if (active === last) {
				e.preventDefault();
				try {
					first.focus();
				} catch (e3) {
					/* ignore */
				}
			}
		}
	};

	CartUiShellStateMachine.prototype._applyVisualLayer = function () {
		var elevated = this._state === STATES.B || this._state === STATES.C;
		this._setElevated(elevated);
		if (this._state === STATES.C) {
			this._focusTrapStart();
		} else {
			this._focusTrapStop();
		}
	};

	CartUiShellStateMachine.prototype.dispatch = function (actionType) {
		var ctx = this._ctx();
		var next = reduce(this._state, actionType, ctx);
		if (next === null || typeof next === 'undefined') {
			return this._state;
		}
		if (!canTransition(this._state, next, ctx)) {
			return this._state;
		}
		var prev = this._state;
		this._state = next;
		this._setDomStateAttr();
		this._syncPanelBPlaceholder();
		this._syncDrawerDom();
		this._applyVisualLayer();
		this._emit(prev, next);
		return this._state;
	};

	CartUiShellStateMachine.prototype.hydrateFromDom = function () {
		var ctx = this._ctx();
		var drawerOpen = !!(this.sticky && typeof this.sticky.isDrawerOpen === 'function' && this.sticky.isDrawerOpen());
		var next = STATES.A;
		if (drawerOpen && ctx.drawerEnabled) {
			next = STATES.C;
		} else if (ctx.hasPanelB && this.rootEl && this.rootEl.getAttribute('data-mp-scc-shell-state') === STATES.B) {
			next = STATES.B;
		}
		this._state = next;
		this._setDomStateAttr();
		this._syncDrawerDom();
		this._applyVisualLayer();
	};

	CartUiShellStateMachine.prototype.onStickyPayloadApplied = function (payload) {
		if (payload && payload.empty) {
			this.dispatch(ACTION.FORCE_A);
		}
	};

	CartUiShellStateMachine.prototype._onPageHide = function () {
		this._focusTrapStop();
		this._setElevated(false);
		this._state = STATES.A;
		if (this.rootEl) {
			this.rootEl.removeAttribute('data-mp-scc-shell-state');
		}
	};

	CartUiShellStateMachine.prototype._onPageShow = function () {
		this.hydrateFromDom();
	};

	CartUiShellStateMachine.prototype._onEscape = function (e) {
		if (e.key !== 'Escape' && e.keyCode !== 27) {
			return;
		}
		if (this._state !== STATES.B && this._state !== STATES.C) {
			return;
		}
		var body = document.body;
		if (body && (body.classList.contains('modal-open') || body.classList.contains('popup-overlay-open'))) {
			return;
		}
		var prev = this._state;
		this.dispatch(ACTION.ESCAPE);
		if (this._state !== prev) {
			e.preventDefault();
		}
	};

	CartUiShellStateMachine.prototype.install = function () {
		document.addEventListener('keydown', this._onEscape, false);
		window.addEventListener('pagehide', this._onPageHide, false);
		window.addEventListener('pageshow', this._onPageShow, false);
		this.hydrateFromDom();
	};

	CartUiShellStateMachine.prototype.destroy = function () {
		document.removeEventListener('keydown', this._onEscape, false);
		window.removeEventListener('pagehide', this._onPageHide, false);
		window.removeEventListener('pageshow', this._onPageShow, false);
		this._focusTrapStop();
		this._setElevated(false);
	};

	window.mpSccCartUiShell = {
		STATES: STATES,
		ACTION: ACTION,
		canTransition: canTransition,
		reduce: reduce,
		/**
		 * @param {*} sticky StickyCartController instance (jQuery-backed)
		 * @returns {CartUiShellStateMachine}
		 */
		attachSticky: function (sticky) {
			var shell = new CartUiShellStateMachine(sticky);
			sticky.cartUiShell = shell;
			shell.install();
			return shell;
		},
	};
})(window, document);
