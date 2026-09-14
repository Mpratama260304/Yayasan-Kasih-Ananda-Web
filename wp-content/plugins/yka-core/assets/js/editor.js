/**
 * Block editor helpers for YKA Core.
 *
 * Two small jobs:
 *   1. keep the publication checklist accurate while the editor types,
 *      instead of only being correct on page load;
 *   2. copy the social caption to the clipboard.
 *
 * Plain JavaScript against the `wp.data` stores that WordPress already
 * ships. No build step, no framework.
 */
( function ( wp ) {
	'use strict';

	var config = window.ykaEditor || {};
	var messages = config.messages || {};

	/* ---------------------------------------------------------- copy */

	function flash( button, text ) {
		var status = button.parentNode.querySelector( '.yka-social-caption__status' );
		if ( status ) {
			status.textContent = text;
			window.setTimeout( function () {
				status.textContent = '';
			}, 2500 );
		}
	}

	function handleCopy( event ) {
		var button = event.target.closest( '[data-yka-copy]' );
		if ( ! button ) {
			return;
		}

		var target = document.querySelector( button.getAttribute( 'data-yka-copy' ) );
		if ( ! target ) {
			return;
		}

		var done = function () {
			flash( button, messages.copied || 'Copied.' );
		};
		var failed = function () {
			flash( button, messages.copyfail || 'Copy manually.' );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( target.value ).then( done, failed );
			return;
		}

		// Older browsers: select the textarea so the editor can copy manually.
		target.removeAttribute( 'readonly' );
		target.select();
		try {
			done();
		} catch ( error ) {
			failed();
		}
		target.setAttribute( 'readonly', 'readonly' );
	}

	document.addEventListener( 'click', handleCopy );

	/* ----------------------------------------------------- checklist */

	if ( ! wp || ! wp.data || ! wp.data.select || ! wp.data.subscribe ) {
		return;
	}

	function evaluate() {
		var editor = wp.data.select( 'core/editor' );
		if ( ! editor || ! editor.getCurrentPostType || editor.getCurrentPostType() !== 'post' ) {
			return null;
		}

		var issues = [];
		var title = ( editor.getEditedPostAttribute( 'title' ) || '' ).trim();
		var content = ( editor.getEditedPostAttribute( 'content' ) || '' ).toString();
		var excerpt = ( editor.getEditedPostAttribute( 'excerpt' ) || '' ).trim();
		var featured = editor.getEditedPostAttribute( 'featured_media' );
		var categories = editor.getEditedPostAttribute( 'categories' ) || [];
		var units = config.taxonomy ? editor.getEditedPostAttribute( config.taxonomy ) || [] : [];

		var plain = content.replace( /<!--[\s\S]*?-->/g, '' ).replace( /<[^>]+>/g, ' ' ).trim();
		var words = plain ? plain.split( /\s+/ ).length : 0;

		if ( ! title ) {
			issues.push( { level: 'error', message: messages.title } );
		}
		if ( ! plain ) {
			issues.push( { level: 'error', message: messages.content } );
		} else if ( words < 25 ) {
			issues.push( { level: 'warning', message: messages.short } );
		}
		if ( ! units.length ) {
			issues.push( { level: 'error', message: messages.unit } );
		}
		if ( ! categories.length ) {
			issues.push( { level: 'warning', message: messages.category } );
		}
		if ( ! featured ) {
			issues.push( { level: 'warning', message: messages.thumbnail } );
		}
		if ( ! excerpt ) {
			issues.push( { level: 'warning', message: messages.excerpt } );
		}

		return issues;
	}

	function render( issues ) {
		var panel = document.getElementById( 'yka-checklist' );
		if ( ! panel || ! issues ) {
			return;
		}

		panel.querySelectorAll( '.yka-checklist__list, .yka-checklist__ok' ).forEach( function ( node ) {
			node.remove();
		} );

		var container;
		if ( ! issues.length ) {
			container = document.createElement( 'p' );
			container.className = 'yka-checklist__ok';
			container.textContent = messages.ok || '';
		} else {
			container = document.createElement( 'ul' );
			container.className = 'yka-checklist__list';
			issues.forEach( function ( issue ) {
				var item = document.createElement( 'li' );
				item.className = 'yka-checklist__item yka-checklist__item--' + issue.level;
				item.textContent = issue.message || '';
				container.appendChild( item );
			} );
		}

		panel.insertBefore( container, panel.firstChild );
	}

	var previous = '';
	wp.domReady( function () {
		wp.data.subscribe( function () {
			var issues = evaluate();
			if ( ! issues ) {
				return;
			}
			var signature = JSON.stringify( issues );
			if ( signature === previous ) {
				return;
			}
			previous = signature;
			render( issues );
		} );
	} );
} )( window.wp );
