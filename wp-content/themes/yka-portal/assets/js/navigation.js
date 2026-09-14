/**
 * Header navigation.
 *
 * Three small jobs: the mobile menu, the search panel, and submenu
 * disclosure for keyboard and touch users. Everything degrades to plain
 * links when JavaScript is unavailable.
 */
( function () {
	'use strict';

	var strings = window.ykaNav || {};

	/**
	 * Wires a button to a panel it shows and hides.
	 */
	function createDisclosure( button, panel, labels ) {
		if ( ! button || ! panel ) {
			return null;
		}

		var api = {
			isOpen: function () {
				return button.getAttribute( 'aria-expanded' ) === 'true';
			},
			open: function () {
				button.setAttribute( 'aria-expanded', 'true' );
				panel.hidden = false;
				if ( labels && labels.close ) {
					setLabel( button, labels.close );
				}
			},
			close: function () {
				button.setAttribute( 'aria-expanded', 'false' );
				panel.hidden = true;
				if ( labels && labels.open ) {
					setLabel( button, labels.open );
				}
			},
			toggle: function () {
				if ( api.isOpen() ) {
					api.close();
				} else {
					api.open();
				}
			}
		};

		button.addEventListener( 'click', function () {
			api.toggle();
		} );

		return api;
	}

	function setLabel( button, text ) {
		var label = button.querySelector( '.screen-reader-text' );
		if ( label ) {
			label.textContent = text;
		}
	}

	var navToggle = document.querySelector( '.yka-nav-toggle' );
	var mobileNav = document.getElementById( 'yka-mobile-nav' );
	var searchToggle = document.querySelector( '.yka-search-toggle' );
	var searchPanel = document.getElementById( 'yka-search-panel' );

	var menu = createDisclosure( navToggle, mobileNav, {
		open: strings.openMenu,
		close: strings.closeMenu
	} );

	var search = createDisclosure( searchToggle, searchPanel, {
		open: strings.openSearch,
		close: strings.closeSearch
	} );

	// Opening one closes the other, and the search field takes focus so a
	// keyboard user can start typing immediately.
	if ( search && searchToggle ) {
		searchToggle.addEventListener( 'click', function () {
			if ( menu && menu.isOpen() ) {
				menu.close();
			}
			if ( search.isOpen() ) {
				var field = searchPanel.querySelector( 'input[type="search"]' );
				if ( field ) {
					field.focus();
				}
			}
		} );
	}

	if ( menu && navToggle ) {
		navToggle.addEventListener( 'click', function () {
			if ( search && search.isOpen() ) {
				search.close();
			}
		} );
	}

	/* --------------------------------------------------- submenus */

	function setupSubmenu( toggle ) {
		var parent = toggle.closest( 'li' );
		if ( ! parent ) {
			return;
		}

		toggle.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var open = parent.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	}

	document.querySelectorAll( '.yka-submenu-toggle' ).forEach( setupSubmenu );

	function closeAllSubmenus() {
		document.querySelectorAll( '.yka-nav__list li.is-open' ).forEach( function ( item ) {
			item.classList.remove( 'is-open' );
			var toggle = item.querySelector( '.yka-submenu-toggle' );
			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	/* ----------------------------------------- global dismissal */

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' ) {
			return;
		}

		closeAllSubmenus();

		if ( menu && menu.isOpen() ) {
			menu.close();
			navToggle.focus();
		}

		if ( search && search.isOpen() ) {
			search.close();
			searchToggle.focus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest( '.yka-nav__list' ) ) {
			closeAllSubmenus();
		}
	} );

	// Returning to a desktop width leaves the mobile panel stranded open.
	var desktop = window.matchMedia( '(min-width: 1024px)' );
	var onBreakpointChange = function ( query ) {
		if ( query.matches && menu && menu.isOpen() ) {
			menu.close();
		}
	};

	if ( typeof desktop.addEventListener === 'function' ) {
		desktop.addEventListener( 'change', onBreakpointChange );
	} else if ( typeof desktop.addListener === 'function' ) {
		desktop.addListener( onBreakpointChange );
	}

	/* ------------------------------------------- consent-gated map */

	// The Google Maps iframe is only requested once a visitor asks for it,
	// so opening the contact page makes no third-party request at all.
	document.querySelectorAll( '[data-yka-map]' ).forEach( function ( container ) {
		var button = container.querySelector( '[data-yka-map-load]' );
		var src = container.getAttribute( 'data-src' );

		if ( ! button || ! src ) {
			return;
		}

		button.addEventListener( 'click', function () {
			var frame = document.createElement( 'iframe' );
			frame.src = src;
			frame.loading = 'lazy';
			frame.referrerPolicy = 'no-referrer-when-downgrade';
			frame.title = button.textContent.trim();
			frame.setAttribute( 'allowfullscreen', '' );

			container.innerHTML = '';
			container.appendChild( frame );
		} );
	} );
} )();
