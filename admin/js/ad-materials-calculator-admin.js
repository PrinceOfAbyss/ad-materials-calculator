(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	function updateCoverageRowTotal($row) {
		var total = 0;
		var filledCount = 0;
		var $sumCell = $row.find('[data-row-sum]');

		$row.find('.ad-mc-settings-decimal').each(function () {
			var rawValue = $(this).val();
			var normalized = String(rawValue).replace(',', '.').trim();
			var parsed;

			if (normalized === '') {
				return;
			}

			parsed = parseFloat(normalized);

			if (Number.isNaN(parsed)) {
				return;
			}

			total += parsed;
			filledCount += 1;
		});

		if (filledCount === 0) {
			$sumCell.text('');
			return;
		}

		$sumCell.text(total.toFixed(3));
	}

	$(function () {
		var $tables = $('.ad-mc-settings-table');
		var $userSelect = $('.ad-mc-settings-user-select');
		var $shareLinksModal = $('.ad-mc-share-links-modal');
		var $shareLinksModalContent = $shareLinksModal.find('.ad-mc-share-links-modal__content');

		$tables.find('tbody tr').each(function () {
			updateCoverageRowTotal($(this));
		});

		$tables.on('input change', '.ad-mc-settings-decimal', function () {
			updateCoverageRowTotal($(this).closest('tr'));
		});

		if ($userSelect.length && typeof $.fn.selectWoo === 'function') {
			$userSelect.selectWoo();
		}

		$(document).on('click', '.ad-mc-share-links-preview-button', function () {
			var previewId = $(this).data('ad-mc-share-links-preview');
			var $template = $('#ad-mc-share-links-preview-' + previewId);

			if (!$shareLinksModal.length || !$template.length) {
				return;
			}

			$shareLinksModalContent.html($template.html());
			$shareLinksModal.removeAttr('hidden');
		});

		$(document).on('click', '[data-ad-mc-share-links-close]', function () {
			$shareLinksModal.attr('hidden', 'hidden');
			$shareLinksModalContent.empty();
		});

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape' && !$shareLinksModal.is('[hidden]')) {
				$shareLinksModal.attr('hidden', 'hidden');
				$shareLinksModalContent.empty();
			}
		});
	});
})( jQuery );
