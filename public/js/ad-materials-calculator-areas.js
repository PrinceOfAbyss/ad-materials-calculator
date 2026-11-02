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

	function buttonValue( $root, key ) {

		return $root.find( '[data-ad-area-buttons="' + key + '"] .is-active' ).attr( 'data-ad-area-value' ) || '';
	}

	function isChecked( $root, key ) {

		return $root.find( '[data-ad-area-input="' + key + '"]' ).is( ':checked' ) ? 1 : 0;
	}

	function renderResult( $root, data ) {

		$root.removeClass( 'is-loading' ).attr( 'aria-busy', 'false' );
		$root.find( '[data-ad-area-items]' ).html( data.items_html || '' );
		$root.find( '[data-ad-area-breakdown]' ).html( data.breakdown_html || '' );
		$root.find( '[data-ad-area-notices]' ).html( data.notices_html || '' );
		$root.find( '[data-ad-area-price]' ).html( data.total_html || '' );
		$root.find( '[data-ad-area-rate]' ).html( data.rate_html || '' );
		$root.find( '[data-ad-area-project-description]' ).text( data.project_description || '' );
		$root.find( '[data-ad-area-cart]' ).html( data.add_to_cart_html || '' );
		$root.find( '[data-ad-email-form] button' ).prop( 'disabled', ! data.add_to_cart_html );

		if ( data.items_html ) {
			$root.find( '[data-ad-area-status]' ).hide();
		} else {
			$root.find( '[data-ad-area-status]' ).text( window.adMaterialsCalculatorAreas.i18n.empty ).show();
		}
	}

	function incompleteDimensionGroup( $root, payload ) {

		if ( payload.mode === 'known' ) {
			return '';
		}

		var config = JSON.parse( $root.attr( 'data-ad-preset' ) );
		var area = $root.attr( 'data-ad-area-calc' );
		var location = payload.location || Object.keys( config.locations || {} )[ 0 ] || '';
		var positive = function( value ) {
			var number = Number( value );

			return Number.isFinite( number ) && number > 0;
		};
		var incomplete = '';

		Object.keys( config.surfaces ).some( function( key ) {
			var surface = config.surfaces[ key ];

			if ( ! payload[ key + '_enabled' ] || ( surface.location && surface.location !== location ) || ( config.walls_layout && payload.layout === 'individual' ) || ( area === 'kitchen' && payload.system === 'Terrazzo Granitech Micromosaic' && key !== 'floor' ) || ( area === 'kitchen' && key === 'backsplash' && payload.room_enabled ) ) {
				return false;
			}

			var source = surface.field_source || key;
			var required = surface.required_fields || surface.fields.map( function( field ) {
				return field.key;
			} );

			if ( required.some( function( dimension ) {
				return ! positive( payload[ source + '_' + dimension ] );
			} ) ) {
				incomplete = surface.label;
				return true;
			}

			if ( surface.extra && payload[ key + '_extra_enabled' ] && surface.extra.fields.some( function( field ) {
				return ! positive( payload[ key + '_extra_' + field.key ] );
			} ) ) {
				incomplete = surface.extra.summary_label || surface.extra.label;
				return true;
			}

			return false;
		} );

		if ( incomplete || ! config.walls_layout ) {
			return incomplete;
		}

		[ 'walls', 'rooms' ].some( function( type ) {
			if ( ( type === 'walls' && payload.layout !== 'individual' ) || ( type === 'rooms' && area !== 'wall_ceiling' ) ) {
				return false;
			}

			var dimensions = type === 'rooms' ? [ 'length', 'width', 'height' ] : [ 'width', 'height' ];

			return ( payload[ type ] || [] ).some( function( row, index ) {
				if ( row.enabled && dimensions.some( function( dimension ) {
					return ! positive( row[ dimension ] );
				} ) ) {
					incomplete = window.adMaterialsCalculatorAreas.i18n[ type === 'walls' ? 'wallNumber' : 'roomNumber' ].replace( '%d', index + 1 );
					return true;
				}

				return false;
			} );
		} );

		if ( ! incomplete && area === 'wall_ceiling' && payload.layout === 'individual' && payload.individual_ceiling_enabled && ( ! positive( payload.individual_ceiling_length ) || ! positive( payload.individual_ceiling_width ) ) ) {
			incomplete = window.adMaterialsCalculatorAreas.i18n.ceiling;
		}

		return incomplete;
	}

	function requestState( $root ) {

		var state = $root.data( 'ad-area-request-state' );

		if ( ! state ) {
			state = {
				active: null,
				pending: null,
				timer: null,
				desiredKey: null,
				renderedKey: null,
				cache: new Map()
			};

			$root.data( 'ad-area-request-state', state );
		}

		return state;
	}

	function cachedResult( state, key ) {

		var now = Date.now();

		state.cache.forEach( function( entry, entryKey ) {
			if ( entry.expiresAt <= now ) {
				state.cache.delete( entryKey );
			}
		} );

		var entry = state.cache.get( key );

		if ( ! entry ) {
			return null;
		}

		// Promote recent entries without extending their original expiry
		state.cache.delete( key );
		state.cache.set( key, entry );

		return entry.data;
	}

	function sendPendingRequest( $root, state ) {

		window.clearTimeout( state.timer );
		state.timer = null;

		if ( state.active || ! state.pending ) {
			return;
		}

		var delay = state.pending.readyAt - Date.now();

		if ( delay > 0 ) {
			state.timer = window.setTimeout( function() {
				sendPendingRequest( $root, state );
			}, delay );
			return;
		}

		var job = state.pending;
		var config = window.adMaterialsCalculatorAreas || {};

		state.pending = null;
		state.active = job;
		job.startedAt = Date.now();
		$root.find( '[data-ad-area-status]' ).text( config.i18n.calculating ).show();

		$.post( job.url, job.data ).done( function( response ) {
			if ( response && response.success && response.data ) {
				var lifetime = Number( response.data.cache_ttl );
				var expiresAt = job.startedAt + Math.min( lifetime, 300 ) * 1000;

				if ( Number.isFinite( lifetime ) && lifetime > 0 && expiresAt > Date.now() ) {
					cachedResult( state, job.key );
					state.cache.delete( job.key );
					state.cache.set( job.key, { data: response.data, expiresAt: expiresAt } );

					while ( state.cache.size > 20 ) {
						state.cache.delete( state.cache.keys().next().value );
					}
				}

				// A completed response may be cached even when a newer selection is active
				if ( state.desiredKey === job.key ) {
					state.renderedKey = job.key;
					renderResult( $root, response.data );
				}

				return;
			}

			if ( state.desiredKey === job.key ) {
				$root.removeClass( 'is-loading' ).attr( 'aria-busy', 'false' );
				$root.find( '[data-ad-area-status]' ).text( config.i18n.error ).show();
				$root.find( '[data-ad-area-project-description]' ).empty();
			}
		} ).fail( function( xhr ) {
			if ( state.desiredKey !== job.key ) {
				return;
			}

			var response = xhr.responseJSON;
			var message = response && response.data && response.data.message ? response.data.message : config.i18n.error;

			$root.removeClass( 'is-loading' ).attr( 'aria-busy', 'false' );
			$root.find( '[data-ad-area-status]' ).text( message ).show();
			$root.find( '[data-ad-area-project-description]' ).empty();
		} ).always( function() {
			state.active = null;
			sendPendingRequest( $root, state );
		} );
	}

	function calculate( $root ) {

		var config = window.adMaterialsCalculatorAreas || {};
		var state = requestState( $root );
		var payload = readPayload( $root );
		var incomplete = incompleteDimensionGroup( $root, payload );
		var data = {
			action: 'ad_materials_calc_area_calculate',
			nonce: config.nonce,
			area: $root.attr( 'data-ad-area-calc' ),
			payload: payload
		};
		var key = JSON.stringify( [ config.ajaxUrl, data ] );
		var cached = incomplete ? null : cachedResult( state, key );

		if ( ! incomplete && state.desiredKey === key && ( ( state.active && state.active.key === key ) || ( state.pending && state.pending.key === key ) || ( state.renderedKey === key && cached ) ) ) {
			return;
		}

		$root.data( 'ad-area-revision', ( $root.data( 'ad-area-revision' ) || 0 ) + 1 );
		window.clearTimeout( state.timer );
		state.timer = null;
		state.pending = null;
		state.desiredKey = incomplete ? null : key;
		state.renderedKey = null;
		$root.find( '[data-ad-email-status]' ).empty();

		if ( cached ) {
			state.renderedKey = key;
			renderResult( $root, cached );
			return;
		}

		$root.find( '[data-ad-area-cart]' ).empty();
		$root.find( '[data-ad-area-items], [data-ad-area-breakdown], [data-ad-area-notices], [data-ad-area-price], [data-ad-area-rate]' ).empty();
		$root.find( '[data-ad-email-form] button' ).prop( 'disabled', true );

		if ( incomplete ) {
			$root.removeClass( 'is-loading' ).attr( 'aria-busy', 'false' );
			$root.find( '[data-ad-area-project-description]' ).text( config.i18n.projectIncomplete );
			$root.find( '[data-ad-area-status]' ).text( config.i18n.incompleteDimensions.replace( '%s', incomplete ) ).show();
			return;
		}

		$root.find( '[data-ad-area-project-description]' ).text( config.i18n.projectUpdating );
		$root.find( '[data-ad-area-status]' ).text( config.i18n.calculating ).show();
		$root.addClass( 'is-loading' ).attr( 'aria-busy', 'true' );

		if ( state.active && state.active.key === key ) {
			return;
		}

		// Keep only the latest valid selection, including edits made before debounce elapses
		state.pending = { key: key, url: config.ajaxUrl, data: data, readyAt: Date.now() + 450 };
		sendPendingRequest( $root, state );
	}

	function syncSystem( $root ) {

		var system = buttonValue( $root, 'system' );
		var isFine = system === 'Terrazzo Granitech Fine' || system === 'Terrazzo Granitech Micromosaic';
		var isGaia = system === 'Terrazzo Granitech Gaia';
		var isMicro = system === 'Terrazzo Granitech Microterrazzo';
		var isMicromosaic = system === 'Terrazzo Granitech Micromosaic';
		var isMonolith = system === 'Terrazzo Granitech Monolith';
		var config = JSON.parse( $root.attr( 'data-ad-preset' ) );

		Object.keys( config.surfaces ).forEach( function( key ) {
			var enabled = isChecked( $root, key + '_enabled' );
			var needsFields = enabled || Object.keys( config.surfaces ).some( function( dependent ) {
				return config.surfaces[ dependent ].field_source === key && isChecked( $root, dependent + '_enabled' );
			} );
			var $surface = $root.find( '[data-ad-preset-surface="' + key + '"]' );

			$surface.toggleClass( 'is-active', !! enabled ).find( '.ad-area-calc__optional-fields' ).prop( 'hidden', ! needsFields );
		} );

		$root.find( '[data-ad-area-pigments="medium"]' ).prop( 'hidden', isFine || isMonolith || isGaia );
		$root.find( '[data-ad-area-pigments="fine"]' ).prop( 'hidden', ! isFine || isMonolith );
		$root.find( '[data-ad-area-microterrazzo]' ).prop( 'hidden', ! isMicro );
		$root.find( '[data-ad-area-stones]' ).prop( 'hidden', ! isMicromosaic && ! isMonolith );
		$root.find( '[data-ad-area-pigments="gaia"], [data-ad-gaia-notice]' ).prop( 'hidden', ! isGaia );
		$root.find( '[data-ad-micromosaic-note], [data-ad-micromosaic-color-heading]' ).prop( 'hidden', ! isMicromosaic );
		$root.find( '[data-ad-monolith-note]' ).prop( 'hidden', ! isMonolith );
		$root.find( '[data-ad-palette-note]' ).prop( 'hidden', isMonolith || isGaia );
		$root.find( '[data-ad-micromosaic-protection-note]' ).prop( 'hidden', ! isMicromosaic );
		$root.find( '[data-ad-multiroom-note]' ).prop( 'hidden', ! $root.find( '[data-ad-area-repeater="rooms"] [data-ad-row-input="enabled"]:checked' ).length );

		var includesBacksplash = isChecked( $root, 'room_enabled' ) && $root.attr( 'data-ad-area-mode' ) !== 'known';

		$root.find( '[data-ad-overlap-note]' ).prop( 'hidden', ! includesBacksplash );
		$root.find( '[data-ad-preset-surface="backsplash"] [data-ad-surface-note]' ).prop( 'hidden', !! includesBacksplash );

		if ( $root.is( '[data-ad-preset]' ) ) {
			var area = $root.attr( 'data-ad-area-calc' );
			var location = buttonValue( $root, 'location' );
			var fixedSheen = isMonolith || isMicromosaic || location === 'exterior';
			var fixedProtection = isMonolith || ( area === 'pool' && location === 'pool' );

			$root.find( '[data-ad-location]' ).each( function() {
				$( this ).prop( 'hidden', $( this ).attr( 'data-ad-location' ) !== location );
			} );

			$root.find( '[data-ad-layout]' ).each( function() {
				$( this ).prop( 'hidden', $( this ).attr( 'data-ad-layout' ) !== ( buttonValue( $root, 'layout' ) || 'standard' ) );
			} );

			$root.find( '[data-ad-area-buttons="sheen"]' ).prop( 'hidden', fixedSheen );
			$root.find( '[data-ad-fixed-sheen]' ).prop( 'hidden', ! fixedSheen || ( location === 'exterior' && ! isMonolith && !! config.exterior_note ) );
			$root.find( '[data-ad-sheen-note]' ).prop( 'hidden', fixedSheen );
			$root.find( '[data-ad-exterior-note]' ).prop( 'hidden', location !== 'exterior' || isMonolith );
			$root.find( '[data-ad-protection-note]' ).prop( 'hidden', fixedProtection );
			$root.find( '[data-ad-pool-deck-note]' ).prop( 'hidden', location !== 'deck' || isMonolith );
			$root.find( '[data-ad-area-buttons="protection"]' ).prop( 'hidden', fixedProtection );
			$root.find( '[data-ad-fixed-protection]' ).prop( 'hidden', ! fixedProtection );
			$root.find( '[data-ad-area-value="Terrazzo Granitech Monolith"]' ).prop( 'hidden', ( area === 'floor' && location !== 'exterior' ) || ( area === 'pool' && location !== 'deck' ) );
			$root.find( '[data-ad-area-value="Terrazzo Granitech Micromosaic"]' ).prop( 'hidden', ( area === 'floor' && location !== 'interior' ) || ( area === 'kitchen' && ! isChecked( $root, 'floor_enabled' ) && $root.attr( 'data-ad-area-mode' ) !== 'known' ) );

			if ( $root.find( '[data-ad-area-buttons="system"] .is-active' ).prop( 'hidden' ) ) {
				$root.find( '[data-ad-area-buttons="system"] .ad-area-calc__card' ).removeClass( 'is-active' ).first().addClass( 'is-active' );
				syncSystem( $root );
			}
		}
	}

	function readPayload( $root ) {

		var payload = {
			mode: $root.attr( 'data-ad-area-mode' ) || 'dimensions'
		};

		$root.find( '[data-ad-area-input]' ).each( function() {
			var $input = $( this );
			var key = $input.attr( 'data-ad-area-input' );

			if ( $input.is( ':radio' ) ) {
				if ( $input.is( ':checked' ) ) {
					payload[ key ] = $input.val();
				}
			} else {
				payload[ key ] = $input.is( ':checkbox' ) ? ( $input.is( ':checked' ) ? 1 : 0 ) : $input.val();
			}
		} );

		$root.find( '[data-ad-area-buttons]' ).each( function() {
			payload[ $( this ).attr( 'data-ad-area-buttons' ) ] = $( this ).find( '.is-active' ).attr( 'data-ad-area-value' ) || '';
		} );

		$root.find( '[data-ad-area-extra]' ).each( function() {
			payload[ $( this ).attr( 'data-ad-area-extra' ) + '_extra_enabled' ] = $( this ).data( 'ad-area-extra-enabled' ) ? 1 : 0;
		} );

		$root.find( '[data-ad-area-repeater]' ).each( function() {
			var rows = [];

			$( this ).find( '[data-ad-area-row]' ).each( function() {
				var row = {};

				$( this ).find( '[data-ad-row-input]' ).each( function() {
					row[ $( this ).attr( 'data-ad-row-input' ) ] = $( this ).is( ':checkbox' ) ? ( $( this ).is( ':checked' ) ? 1 : 0 ) : $( this ).val();
				} );

				rows.push( row );
			} );

			payload[ $( this ).attr( 'data-ad-area-repeater' ) ] = rows;
		} );

		return payload;
	}

	function updateAreaPreview( $root ) {

		var config = JSON.parse( $root.attr( 'data-ad-preset' ) );
		var payload = readPayload( $root );
		var area = $root.attr( 'data-ad-area-calc' );
		var parts = {};
		var total = 0;
		var numeric = function( key ) {
			return Math.max( 0, Number( payload[ key ] ) || 0 );
		};
		var termArea = function( surface, key ) {
			key = surface.field_source || key;

			if ( surface.preview_geometry === 'shower' ) {
				var length = numeric( key + '_length' );
				var width = numeric( key + '_width' );
				var height = numeric( key + '_height' );

				return ( 2 * length + 2 * width - Math.min( length, width ) ) * height + length * width;
			}

			if ( surface.parts ) {
				return Object.keys( surface.parts ).reduce( function( sum, part ) {
					return sum + termArea( surface.parts[ part ], key );
				}, 0 );
			}

			return surface.terms.reduce( function( sum, term ) {
				return sum + term.slice( 1 ).reduce( function( value, dimension ) {
					return value * numeric( key + '_' + dimension );
				}, term[ 0 ] );
			}, 0 );
		};

		if ( payload.mode === 'known' ) {
			var knownFields = config.known_fields || {
				total: {
					key: 'known_area'
				}
			};

			Object.keys( knownFields ).forEach( function( key ) {
				parts[ key ] = numeric( knownFields[ key ].key );
			} );
		} else {
			Object.keys( config.surfaces ).forEach( function( key ) {
				var surface = config.surfaces[ key ];
				var value = 0;

				if ( payload[ key + '_enabled' ] && ( ! surface.location || surface.location === payload.location ) ) {
					value = termArea( surface, key );

					if ( surface.ceiling && payload[ key + '_ceiling' ] ) {
						value += numeric( key + '_length' ) * numeric( key + '_width' );
					}

					if ( surface.extra && payload[ key + '_extra_enabled' ] ) {
						value += termArea( surface.extra, key + '_extra' );
					}
				}

				parts[ key ] = value;
			} );

			if ( config.walls_layout ) {
				if ( payload.layout === 'individual' ) {
					parts.room = ( payload.walls || [] ).reduce( function( sum, wall ) {
						return sum + ( wall.enabled ? ( Number( wall.width ) || 0 ) * ( Number( wall.height ) || 0 ) : 0 );
					}, 0 );

					if ( area === 'wall_ceiling' && payload.individual_ceiling_enabled ) {
						parts.room += numeric( 'individual_ceiling_length' ) * numeric( 'individual_ceiling_width' );
					}
				}

				if ( area === 'wall_ceiling' ) {
					parts.room += ( payload.rooms || [] ).reduce( function( sum, room ) {
						return sum + ( room.enabled ? 2 * ( Number( room.length ) + Number( room.width ) ) * Number( room.height ) + ( room.ceiling ? Number( room.length ) * Number( room.width ) : 0 ) : 0 );
					}, 0 );
				}
			}

			if ( area === 'kitchen' && payload.room_enabled ) {
				parts.backsplash = 0;
			}
		}

		if ( area === 'kitchen' && payload.system === 'Terrazzo Granitech Micromosaic' ) {
			Object.keys( parts ).forEach( function( key ) {
				if ( key !== 'floor' ) {
					parts[ key ] = 0;
				}
			} );
		}

		Object.keys( parts ).forEach( function( key ) {
			$root.find( '[data-ad-area-preview="' + key + '"]' ).text( ( Math.round( parts[ key ] * 10 ) / 10 ) + ' ' + window.adMaterialsCalculatorAreas.i18n.squareFeet );
			total += key === 'subtract' ? -parts[ key ] : parts[ key ];
		} );

		total = Math.max( 0, total );
		$root.attr( 'data-ad-area-total', Math.round( total * 10 ) / 10 );
		$root.attr( 'data-ad-area-total-metric', Math.round( total / 10.7639 * 10 ) / 10 );
	}

	function initialize( $root ) {

		$root.attr( 'data-ad-area-mode', 'dimensions' );
		$root.find( '[data-ad-area-repeater="rooms"] [data-ad-area-row]' ).prop( 'hidden', true );
		$root.find( '[data-ad-area-repeater="rooms"] [data-ad-row-input="enabled"]' ).prop( 'checked', false );
		updateAreaPreview( $root );
		syncSystem( $root );
		calculate( $root );

		$root.on( 'click', '[data-ad-area-mode]', function() {

			var mode = $( this ).attr( 'data-ad-area-mode' );

			$root.attr( 'data-ad-area-mode', mode );
			$root.find( 'button[data-ad-area-mode]' ).attr( 'aria-pressed', 'false' ).filter( this ).attr( 'aria-pressed', 'true' );
			$root.find( '[data-ad-area-mode]' ).removeClass( 'is-active' ).filter( this ).addClass( 'is-active' );
			$root.find( '[data-ad-area-mode-panel]' ).prop( 'hidden', true ).filter( '[data-ad-area-mode-panel="' + mode + '"]' ).prop( 'hidden', false );
			syncSystem( $root );
			updateAreaPreview( $root );
			calculate( $root );
		} );

		$root.on( 'submit', '[data-ad-email-form]', function( event ) {

			event.preventDefault();

			var config = window.adMaterialsCalculatorAreas;
			var $form = $( this );
			var revision = $root.data( 'ad-area-revision' );
			var encoded = $root.find( '[name="ad_materials_calc_payload"]' ).val();

			if ( ! encoded || $root.hasClass( 'is-loading' ) ) {
				return;
			}

			$form.find( 'button' ).prop( 'disabled', true );
			$form.find( '[data-ad-email-status]' ).text( config.i18n.sending );
			$.post( config.ajaxUrl, {
				action: 'ad_materials_calc_email_area_estimate',
				nonce: config.nonce,
				email: $form.find( '[name="email"]' ).val(),
				payload: encoded,
				signature: $root.find( '[name="ad_materials_calc_payload_signature"]' ).val()
			} ).done( function( response ) {
				if ( $root.data( 'ad-area-revision' ) === revision ) {
					$form.find( '[data-ad-email-status]' ).text( response.data.message );
				}
			} ).fail( function( xhr ) {
				if ( $root.data( 'ad-area-revision' ) === revision ) {
					$form.find( '[data-ad-email-status]' ).text( xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : config.i18n.error );
				}
			} ).always( function() {
				if ( $root.data( 'ad-area-revision' ) === revision ) {
					$form.find( 'button' ).prop( 'disabled', false );
				}
			} );
		} );

		$root.on( 'click', '[data-ad-area-buttons] .ad-area-calc__card', function() {

			$( this ).addClass( 'is-active' ).siblings().removeClass( 'is-active' );
			syncSystem( $root );
			updateAreaPreview( $root );
			calculate( $root );
		} );

		$root.on( 'click', '[data-ad-add-row]', function() {

			var $repeater = $( this ).closest( '[data-ad-area-repeater]' );
			var $rows = $repeater.find( '[data-ad-area-row]' );

			if ( $rows.first().prop( 'hidden' ) ) {
				$rows.first().prop( 'hidden', false ).find( '[data-ad-row-input="enabled"]' ).prop( 'checked', true );
				updateAreaPreview( $root );
				calculate( $root );
				return;
			}

			if ( $rows.length >= Number( $repeater.attr( 'data-ad-limit' ) ) ) {
				return;
			}

			var $clone = $rows.first().clone();

			$clone.find( '[data-ad-row-number]' ).text( $rows.length + 1 );
			$clone.find( '[data-ad-row-input="enabled"]' ).prop( 'checked', true );
			$repeater.find( '[data-ad-area-rows]' ).append( $clone );
			updateAreaPreview( $root );
			calculate( $root );
		} );

		$root.on( 'click', '[data-ad-remove-row]', function() {

			var $row = $( this ).closest( '[data-ad-area-row]' );

			if ( $row.siblings().length ) {
				$row.remove();
			} else {
				$row.find( '[data-ad-row-input="enabled"]' ).prop( 'checked', false );

				if ( $row.closest( '[data-ad-area-repeater]' ).attr( 'data-ad-area-repeater' ) === 'rooms' ) {
					$row.prop( 'hidden', true );
				}
			}

			updateAreaPreview( $root );
			calculate( $root );
		} );

		$root.on( 'click', '[data-ad-area-extra-toggle]', function() {

			var key = $( this ).attr( 'data-ad-area-extra-toggle' );
			var $fields = $root.find( '[data-ad-area-extra="' + key + '"]' );
			var enabled = ! $fields.data( 'ad-area-extra-enabled' );

			$fields.data( 'ad-area-extra-enabled', enabled ).prop( 'hidden', ! enabled );
			$( this ).toggleClass( 'is-active', enabled ).attr( 'aria-expanded', enabled ? 'true' : 'false' ).text( enabled ? window.adMaterialsCalculatorAreas.i18n.removeExtra : $( this ).attr( 'data-ad-extra-add-label' ) );
			updateAreaPreview( $root );
			calculate( $root );
		} );

		$root.on( 'input change', '[data-ad-area-input], [data-ad-row-input]', function() {

			var $input = $( this );

			if ( ( $input.attr( 'data-ad-area-input' ) || '' ).slice( -8 ) === '_enabled' && $input.closest( '.ad-area-calc__surface' ).length ) {
				$input.closest( '.ad-area-calc__surface' ).toggleClass( 'is-active', $input.is( ':checked' ) ).find( '.ad-area-calc__optional-fields' ).prop( 'hidden', ! $input.is( ':checked' ) );
			}

			syncSystem( $root );
			updateAreaPreview( $root );
			calculate( $root );
		} );
	}

	$( function() {

		$( '[data-ad-area-calc]' ).each( function() {

			initialize( $( this ) );
		} );
	} );

})( jQuery );
