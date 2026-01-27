/**
 * Admin JS for MCRPD Regenerate Download Permissions
 */
(function ($) {
	'use strict';

	let running = false;
	let page = 1;
	let scannedTotal = 0;
	let updatedTotal = 0;

	// Ensure our config object exists
	const config = window.mcrpdRegen || {};
	const strings = config.strings || {};

	function logLine(html, type = '') {
		let className = '';
		if (type === 'error') { className = 'mcrpd-log-error'; }
		if (type === 'success') { className = 'mcrpd-log-success'; }

		const content = className ? `<span class="${className}">${html}</span>` : html;

		$('#mcrpd-log').append(`<div>${content}</div>`);
		$('#mcrpd-log').scrollTop($('#mcrpd-log')[0].scrollHeight);
	}

	function updateUI(d) {
		$('#mcrpd-page').text(d.page || 0);
		$('#mcrpd-max-pages').text(d.max_pages || 0);
		$('#mcrpd-scanned').text(scannedTotal);
		$('#mcrpd-updated').text(updatedTotal);
	}

	function step() {
		if (!running) { return; }

		$.post(config.ajaxUrl, {
			action: 'mcrpd_regen_step',
			nonce: config.nonce,
			page: page
		}).done(function (resp) {
			if (!running) { return; }

			if (!resp || !resp.success) {
				const msg = (resp && resp.data && resp.data.message) ? resp.data.message : (strings.unknown || 'Unknown error');
				logLine(`<strong>${strings.error || 'Error:'}</strong> ${msg}`, 'error');
				running = false;
				return;
			}

			const d = resp.data;

			scannedTotal += parseInt(d.scanned || 0, 10);
			updatedTotal += parseInt(d.updated || 0, 10);

			updateUI(d);

			if (d.message) {
				logLine(d.message);
			}

			if (d.done) {
				logLine(`<strong>${strings.done || 'Done.'}</strong>`, 'success');
				running = false;
				return;
			}

			page = parseInt(d.next_page || (page + 1), 10);
			step();

		}).fail(function () {
			logLine(`<strong>${strings.ajaxFailed || 'AJAX failed.'}</strong>`, 'error');
			running = false;
		});
	}

	$(document).ready(function () {
		$(document).on('click', '#mcrpd-start', function (e) {
			e.preventDefault();
			if (running) { return; }

			running = true;
			page = 1;
			scannedTotal = 0;
			updatedTotal = 0;

			$('#mcrpd-log').empty();
			logLine(strings.starting || 'Starting...');

			step();
		});

		$(document).on('click', '#mcrpd-stop', function (e) {
			e.preventDefault();
			if (!running) {
				logLine(strings.notRunning || 'Not running.');
				return;
			}
			running = false;
			logLine(`<strong>${strings.stopped || 'Stopped.'}</strong>`);
		});

		// Status Selection UI Handlers
		$(document).on('change', '.mcrpd-status-option input[type="checkbox"]', function () {
			if ($(this).is(':checked')) {
				$(this).closest('.mcrpd-status-option').addClass('checked');
			} else {
				$(this).closest('.mcrpd-status-option').removeClass('checked');
			}
		});

	});

})(jQuery);
