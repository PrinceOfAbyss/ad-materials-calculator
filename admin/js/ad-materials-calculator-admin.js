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

		$tables.find('tbody tr').each(function () {
			updateCoverageRowTotal($(this));
		});

		$tables.on('input change', '.ad-mc-settings-decimal', function () {
			updateCoverageRowTotal($(this).closest('tr'));
		});
	});
})( jQuery );
