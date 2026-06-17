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

	// localStorage key and TTL (5 hours in milliseconds).
	const STORAGE_KEY = 'mcrpd_progress';
	const STORAGE_TTL = 5 * 60 * 60 * 1000;

	/**
	 * Save progress to localStorage with a timestamp.
	 */
	function saveProgress() {
		const data = {
			page: page,
			scannedTotal: scannedTotal,
			updatedTotal: updatedTotal,
			timestamp: Date.now()
		};
		try {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
		} catch (e) {
			// localStorage may be unavailable; fail silently.
		}
	}

	/**
	 * Load progress from localStorage.
	 * Returns null if not found or expired (>5 hours).
	 */
	function loadProgress() {
		try {
			const raw = localStorage.getItem(STORAGE_KEY);
			if (!raw) { return null; }

			const data = JSON.parse(raw);
			if (!data || !data.timestamp) { return null; }

			// Check TTL.
			if ((Date.now() - data.timestamp) > STORAGE_TTL) {
				clearProgress();
				return null;
			}

			return data;
		} catch (e) {
			return null;
		}
	}

	/**
	 * Clear saved progress from localStorage.
	 */
	function clearProgress() {
		try {
			localStorage.removeItem(STORAGE_KEY);
		} catch (e) {
			// Fail silently.
		}
	}

	/**
	 * Show or hide the Continue button based on saved progress.
	 */
	function toggleContinueButton() {
		const progress = loadProgress();
		if (progress && progress.page > 1) {
			$('#mcrpd-continue').removeClass('mcrpd-continue-hidden');
		} else {
			$('#mcrpd-continue').addClass('mcrpd-continue-hidden');
		}
	}

	function logLine(html, type = '') {
		let className = '';
		if (type === 'error') { className = 'mcrpd-log-error'; }
		if (type === 'success') { className = 'mcrpd-log-success'; }
		if (type === 'warning') { className = 'mcrpd-log-warning'; }

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
				saveProgress();
				toggleContinueButton();
				logLine(strings.progressSaved || 'Progress saved.', 'warning');
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
				clearProgress();
				toggleContinueButton();
				return;
			}

			page = parseInt(d.next_page || (page + 1), 10);

			// Save progress after each successful step.
			saveProgress();

			step();

		}).fail(function () {
			logLine(`<strong>${strings.ajaxFailed || 'AJAX failed.'}</strong>`, 'error');
			running = false;
			saveProgress();
			toggleContinueButton();
		});
	}

	$(document).ready(function () {

		// On page load, check for saved progress and toggle Continue button.
		toggleContinueButton();

		// Start button — fresh start, clears any saved progress.
		$(document).on('click', '#mcrpd-start', function (e) {
			e.preventDefault();
			if (running) { return; }

			running = true;
			page = 1;
			scannedTotal = 0;
			updatedTotal = 0;

			clearProgress();
			toggleContinueButton();

			$('#mcrpd-log').empty();
			logLine(strings.starting || 'Starting...');

			step();
		});

		// Continue button — resume from saved progress.
		$(document).on('click', '#mcrpd-continue', function (e) {
			e.preventDefault();
			if (running) { return; }

			const progress = loadProgress();

			if (!progress || !progress.page) {
				logLine(strings.continueNoProgress || 'No saved progress found.', 'warning');
				return;
			}

			running = true;
			page = parseInt(progress.page, 10);
			scannedTotal = parseInt(progress.scannedTotal || 0, 10);
			updatedTotal = parseInt(progress.updatedTotal || 0, 10);

			// Restore counters in UI.
			$('#mcrpd-scanned').text(scannedTotal);
			$('#mcrpd-updated').text(updatedTotal);

			const continueMsg = (strings.continuing || 'Continuing from page %s...')
				.replace('%s', page);
			logLine(`<strong>${continueMsg}</strong>`);

			step();
		});

		// Stop button.
		$(document).on('click', '#mcrpd-stop', function (e) {
			e.preventDefault();
			if (!running) {
				logLine(strings.notRunning || 'Not running.');
				return;
			}
			running = false;
			saveProgress();
			toggleContinueButton();
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
