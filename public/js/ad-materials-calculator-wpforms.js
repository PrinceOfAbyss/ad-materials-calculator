(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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

	function lockReadonlyCalculatorFields() {

		$( '.ad-materials-calculator-wpforms-form .ad-readonly-faux-locked-field input' )
			.prop( 'readonly', true )
			.attr( 'readonly', 'readonly' )
			.attr( 'aria-readonly', 'true' );
	}

	function observeCalculatorReadonlyFields() {

		if ( typeof MutationObserver === 'undefined' ) {
			return;
		}

		$( '.ad-materials-calculator-wpforms-form' ).each( function() {

			if ( this.adMaterialsCalcReadonlyObserver ) {
				return;
			}

			this.adMaterialsCalcReadonlyObserver = new MutationObserver( lockReadonlyCalculatorFields );
			this.adMaterialsCalcReadonlyObserver.observe( this, {
				attributes: true,
				attributeFilter: [ 'class', 'style' ],
				childList: true,
				subtree: true
			} );
		} );
	}

	$( function() {

		lockReadonlyCalculatorFields();
		observeCalculatorReadonlyFields();

		$( document ).on( 'wpformsReady wpformsProcessConditionals wpformsConditionalLogicFieldShown', function() {
			lockReadonlyCalculatorFields();
			observeCalculatorReadonlyFields();
		} );
	} );

})( jQuery );
