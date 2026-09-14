/**
 * Media picker for term meta and settings fields.
 *
 * Uses the WordPress media modal that is already loaded on these screens.
 */
( function () {
	'use strict';

	var strings = window.ykaMediaField || {};

	function setupField( field ) {
		var input = field.querySelector( '[data-yka-media-input]' );
		var preview = field.querySelector( '[data-yka-media-preview]' );
		var selectButton = field.querySelector( '[data-yka-media-select]' );
		var removeButton = field.querySelector( '[data-yka-media-remove]' );
		var frame;

		if ( ! input || ! selectButton || ! window.wp || ! window.wp.media ) {
			return;
		}

		selectButton.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					title: strings.title || 'Select image',
					button: { text: strings.button || 'Use this image' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var size = ( attachment.sizes && ( attachment.sizes.medium || attachment.sizes.full ) ) || null;

					input.value = attachment.id;

					if ( preview ) {
						preview.innerHTML = '';
						if ( size ) {
							var img = document.createElement( 'img' );
							img.src = size.url;
							img.alt = '';
							preview.appendChild( img );
						}
					}

					if ( removeButton ) {
						removeButton.hidden = false;
					}
				} );
			}

			frame.open();
		} );

		if ( removeButton ) {
			removeButton.addEventListener( 'click', function () {
				input.value = '';
				if ( preview ) {
					preview.innerHTML = '';
				}
				removeButton.hidden = true;
			} );
		}
	}

	function init() {
		document.querySelectorAll( '[data-yka-media]' ).forEach( setupField );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
