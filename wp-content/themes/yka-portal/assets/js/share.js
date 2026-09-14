/**
 * Article sharing.
 *
 * The WhatsApp, Facebook and X links work with JavaScript disabled — they
 * are ordinary anchors. This file only adds two conveniences: the native
 * share sheet on supporting devices, and clipboard copy.
 */
( function () {
	'use strict';

	var strings = window.ykaShare || {};
	var root = document.querySelector( '[data-yka-share]' );

	if ( ! root ) {
		return;
	}

	var url = root.getAttribute( 'data-url' ) || window.location.href;
	var title = root.getAttribute( 'data-title' ) || document.title;
	var status = root.querySelector( '[data-yka-share-status]' );

	function announce( message ) {
		if ( ! status ) {
			return;
		}
		status.textContent = message;
		window.setTimeout( function () {
			if ( status.textContent === message ) {
				status.textContent = '';
			}
		}, 4000 );
	}

	/* ------------------------------------------------- copy link */

	var copyButton = root.querySelector( '[data-yka-copy-link]' );

	if ( copyButton ) {
		copyButton.addEventListener( 'click', function () {
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then(
					function () {
						announce( strings.copied || '' );
					},
					function () {
						announce( strings.copyError || '' );
					}
				);
				return;
			}

			announce( strings.copyError || '' );
		} );
	}

	/* --------------------------------------------- native sharing */

	var nativeItem = root.querySelector( '[data-yka-native-share]' );
	var nativeButton = root.querySelector( '[data-yka-share-native]' );

	if ( nativeItem && nativeButton && typeof navigator.share === 'function' ) {
		nativeItem.hidden = false;

		nativeButton.addEventListener( 'click', function () {
			navigator
				.share( { title: title, url: url } )
				.catch( function () {
					// A cancelled share sheet is not an error worth reporting.
				} );
		} );
	}
} )();
