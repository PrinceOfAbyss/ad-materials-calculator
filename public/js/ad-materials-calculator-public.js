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

	function getText( key ) {

		if ( window.adMaterialsCalculatorPublic && window.adMaterialsCalculatorPublic.i18n && window.adMaterialsCalculatorPublic.i18n[ key ] ) {
			return window.adMaterialsCalculatorPublic.i18n[ key ];
		}

		return '';
	}

	function getShareModal() {

		var $modal = $( '#materials-calc-share-modal' );

		if ( $modal.length ) {
			return $modal;
		}

		$modal = $(
			'<div id="materials-calc-share-modal" class="materials-calc-share-modal" hidden>' +
				'<div class="materials-calc-share-modal__backdrop" data-materials-calc-share-close></div>' +
				'<div class="materials-calc-share-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="materials-calc-share-modal-title">' +
					'<button type="button" class="materials-calc-share-modal__close" data-materials-calc-share-close aria-label="' + getText( 'modalCloseLabel' ) + '">&times;</button>' +
					'<h3 id="materials-calc-share-modal-title">' + getText( 'modalTitle' ) + '</h3>' +
					'<p class="materials-calc-share-modal__description">' + getText( 'modalDescription' ) + '</p>' +
					'<div class="materials-calc-share-modal__link-row">' +
						'<input type="text" class="materials-calc-share-modal__link" readonly>' +
						'<button type="button" class="button materials-calc-share-modal__copy">' + getText( 'copyButtonLabel' ) + '</button>' +
					'</div>' +
					'<div class="materials-calc-share-modal__status" aria-live="polite"></div>' +
				'</div>' +
			'</div>'
		);

		$( 'body' ).append( $modal );

		return $modal;
	}

	function openShareModal( url ) {

		var $modal = getShareModal();

		$modal.find( '.materials-calc-share-modal__link' ).val( url );
		$modal.find( '.materials-calc-share-modal__status' ).text( '' );
		$modal.removeAttr( 'hidden' );
		$modal.find( '.materials-calc-share-modal__link' ).trigger( 'focus' ).trigger( 'select' );
	}

	function closeShareModal() {

		$( '#materials-calc-share-modal' ).attr( 'hidden', 'hidden' );
	}

	function copyShareLink( $modal ) {

		var $input = $modal.find( '.materials-calc-share-modal__link' );
		var link = $input.val();
		var setCopied = function() {
			$modal.find( '.materials-calc-share-modal__status' ).text( getText( 'copiedMessage' ) );
		};
		var fallbackCopy = function() {
			$input.trigger( 'focus' ).trigger( 'select' );
			document.execCommand( 'copy' );
			setCopied();
		};

		if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
			window.navigator.clipboard.writeText( link ).then( setCopied ).catch( fallbackCopy );
			return;
		}

		fallbackCopy();
	}

	$( function() {

		$( document ).on( 'click', '.ad-materials-calc-share-cart-button', function() {

			var $button = $( this );
			var nonce = $button.data( 'share-nonce' );
			var ajaxUrl = window.adMaterialsCalculatorPublic && window.adMaterialsCalculatorPublic.ajaxUrl ? window.adMaterialsCalculatorPublic.ajaxUrl : '';
			var originalText = $button.text();

			if ( ! ajaxUrl || ! nonce ) {
				return;
			}

			$button.prop( 'disabled', true ).text( getText( 'creatingLinkLabel' ) );

			$.post(
				ajaxUrl,
				{
					action: 'ad_materials_calc_create_cart_share_link',
					nonce: nonce
				}
			).done( function( response ) {
				if ( response && response.success && response.data && response.data.url ) {
					openShareModal( response.data.url );
					return;
				}

				window.alert( response && response.data && response.data.message ? response.data.message : getText( 'createLinkError' ) );
			} ).fail( function( xhr ) {
				var response = xhr.responseJSON;
				window.alert( response && response.data && response.data.message ? response.data.message : getText( 'createLinkError' ) );
			} ).always( function() {
				$button.prop( 'disabled', false ).text( originalText );
			} );
		} );

		$( document ).on( 'click', '[data-materials-calc-share-close]', closeShareModal );

		$( document ).on( 'click', '.materials-calc-share-modal__copy', function() {
			copyShareLink( $( this ).closest( '.materials-calc-share-modal' ) );
		} );

		$( document ).on( 'keydown', function( event ) {
			if ( event.key === 'Escape' ) {
				closeShareModal();
			}
		} );
	} );

})( jQuery );
