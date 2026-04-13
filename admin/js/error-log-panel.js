/**
 * Diagnostics tab: error log table, filters, export, detail drawer.
 */
(function ($) {
	'use strict';

	function cfg() {
		return typeof window.mpSccErrorLog !== 'undefined' ? window.mpSccErrorLog : {};
	}

	function t(key, fallback) {
		var i = cfg().i18n || {};
		return i[key] != null ? String(i[key]) : fallback;
	}

	function buildParams() {
		var level = $('#mp-scc-log-level').val();
		var dateFrom = $('#mp-scc-log-date-from').val();
		var dateTo = $('#mp-scc-log-date-to').val();
		var source = $('#mp-scc-log-source').val();
		var search = $('#mp-scc-log-search').val();
		var perPage = parseInt($('#mp-scc-log-per-page').val(), 10) || 50;
		return {
			level: level ? String(level) : '',
			date_from: dateFrom ? String(dateFrom) : '',
			date_to: dateTo ? String(dateTo) : '',
			source: source ? String(source).trim() : '',
			search: search ? String(search).trim() : '',
			limit: perPage,
			offset: window.mpSccLogState ? window.mpSccLogState.offset : 0
		};
	}

	function fetchLogs() {
		var c = cfg();
		if (!c.enabled || !c.ajaxUrl || !c.action || !c.nonce) {
			return;
		}
		var params = buildParams();
		var $status = $('#mp-scc-log-status');
		var $tbody = $('#mp-scc-log-tbody');
		$status.text(t('loading', '…'));
		$tbody.html(
			'<tr class="mp-scc-log-placeholder"><td colspan="6">' +
				$('<span/>').text(t('loading', '…')).html() +
				'</td></tr>'
		);

		$.ajax({
			url: c.ajaxUrl,
			method: 'GET',
			dataType: 'json',
			data: $.extend(
				{
					action: c.action,
					_wpnonce: c.nonce
				},
				params
			)
		})
			.done(function (resp) {
				if (!resp || !resp.success || !resp.data) {
					$status.text(t('loadError', 'Error'));
					return;
				}
				var data = resp.data;
				var entries = data.entries || [];
				var total = typeof data.total === 'number' ? data.total : entries.length;
				window.mpSccLogLastEntries = entries;

				var pages = Math.max(1, Math.ceil(total / params.limit));
				var page = Math.floor(params.offset / params.limit) + 1;
				if (page > pages) {
					page = pages;
				}
				var pagText = t('pagination', '')
					.replace('%1$s', String(page))
					.replace('%2$s', String(pages))
					.replace('%3$s', String(total));
				$status.text(pagText);

				$('#mp-scc-log-prev').prop('disabled', params.offset <= 0);
				var atEnd = total === 0 || params.offset + entries.length >= total;
				$('#mp-scc-log-next').prop('disabled', atEnd);

				if (!entries.length) {
					$tbody.html(
						'<tr><td colspan="6">' +
							$('<span/>').text(t('empty', 'Empty')).html() +
							'</td></tr>'
					);
					return;
				}

				var rows = [];
				for (var i = 0; i < entries.length; i++) {
					var e = entries[i];
					var ts = e.ts != null ? e.ts : e.t;
					var time = ts ? new Date(ts * 1000).toISOString().replace('T', ' ').slice(0, 19) : '—';
					var lvl = e.level || '';
					var src = e.source || '';
					var end = e.endpoint || '';
					var code = e.code || '';
					var msg = e.message || '';
					rows.push(
						'<tr class="mp-scc-log-row" tabindex="0" data-index="' +
							i +
							'"><td><code>' +
							escapeHtml(time) +
							'</code></td><td>' +
							escapeHtml(String(lvl)) +
							'</td><td>' +
							escapeHtml(String(src)) +
							'</td><td><code>' +
							escapeHtml(String(end)) +
							'</code></td><td><code>' +
							escapeHtml(String(code)) +
							'</code></td><td>' +
							escapeHtml(String(msg)) +
							'</td></tr>'
					);
				}
				$tbody.html(rows.join(''));
			})
			.fail(function () {
				$status.text(t('loadError', 'Error'));
				$('#mp-scc-log-tbody').html(
					'<tr><td colspan="6">' +
						$('<span/>').text(t('loadError', 'Error')).html() +
						'</td></tr>'
				);
			});
	}

	function escapeHtml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function openDrawer(index) {
		var list = window.mpSccLogLastEntries;
		if (!list || !list[index]) {
			return;
		}
		var raw = JSON.stringify(list[index], null, 2);
		$('#mp-scc-log-drawer-body').text(raw);
		$('#mp-scc-log-drawer-title').text(t('drawerTitle', 'Entry'));
		$('#mp-scc-error-log-drawer, #mp-scc-error-log-backdrop').attr('aria-hidden', 'false').addClass('is-open');
		$('#mp-scc-log-drawer-close').focus();
	}

	function closeDrawer() {
		$('#mp-scc-error-log-drawer, #mp-scc-error-log-backdrop').attr('aria-hidden', 'true').removeClass('is-open');
	}

	function submitExport(format) {
		var c = cfg();
		if (!c.ajaxUrl || !c.exportAction || !c.nonce) {
			return;
		}
		var p = buildParams();
		var $form = $('<form>', {
			method: 'POST',
			action: c.ajaxUrl,
			target: '_blank'
		});
		$form.append($('<input>', { type: 'hidden', name: 'action', value: c.exportAction }));
		$form.append($('<input>', { type: 'hidden', name: '_wpnonce', value: c.nonce }));
		$form.append($('<input>', { type: 'hidden', name: 'format', value: format }));
		Object.keys(p).forEach(function (k) {
			if (p[k] !== '' && p[k] != null) {
				$form.append(
					$('<input>', {
						type: 'hidden',
						name: k,
						value: String(p[k])
					})
				);
			}
		});
		$('body').append($form);
		$form.trigger('submit');
		$form.remove();
	}

	$(function () {
		var root = document.getElementById('mp-scc-error-log-root');
		if (!root || !cfg().enabled) {
			return;
		}

		window.mpSccLogState = { offset: 0 };

		fetchLogs();

		$('#mp-scc-log-apply').on('click', function () {
			window.mpSccLogState.offset = 0;
			fetchLogs();
		});

		$('#mp-scc-log-reset').on('click', function () {
			$('#mp-scc-log-level').val('');
			$('#mp-scc-log-date-from, #mp-scc-log-date-to').val('');
			$('#mp-scc-log-source, #mp-scc-log-search').val('');
			window.mpSccLogState.offset = 0;
			fetchLogs();
		});

		$('#mp-scc-log-per-page').on('change', function () {
			window.mpSccLogState.offset = 0;
			fetchLogs();
		});

		$('#mp-scc-log-prev').on('click', function () {
			var per = parseInt($('#mp-scc-log-per-page').val(), 10) || 50;
			window.mpSccLogState.offset = Math.max(0, window.mpSccLogState.offset - per);
			fetchLogs();
		});

		$('#mp-scc-log-next').on('click', function () {
			var per = parseInt($('#mp-scc-log-per-page').val(), 10) || 50;
			window.mpSccLogState.offset = window.mpSccLogState.offset + per;
			fetchLogs();
		});

		$('#mp-scc-log-tbody').on('click', '.mp-scc-log-row', function () {
			var idx = parseInt($(this).attr('data-index'), 10);
			if (!isNaN(idx)) {
				openDrawer(idx);
			}
		});

		$('#mp-scc-log-tbody').on('keydown', '.mp-scc-log-row', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				var idx = parseInt($(this).attr('data-index'), 10);
				if (!isNaN(idx)) {
					openDrawer(idx);
				}
			}
		});

		$('#mp-scc-log-drawer-close, #mp-scc-error-log-backdrop').on('click', closeDrawer);
		$(document).on('keydown.mpSccLog', function (e) {
			if (e.key === 'Escape') {
				closeDrawer();
			}
		});

		$('#mp-scc-log-export-csv').on('click', function () {
			submitExport('csv');
		});
		$('#mp-scc-log-export-json').on('click', function () {
			submitExport('json');
		});
	});
})(window.jQuery);
