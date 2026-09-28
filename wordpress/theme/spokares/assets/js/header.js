/**
 * Header (parts/header.html): the phone menu gives way to the desktop header.
 *
 * The primary nav is core Navigation with overlayMenu "always"; site.css
 * shows its links in the bar from 1020px up and, below that, a Menu button
 * that opens B's drop-down. Core's view script opens the menu (class
 * is-menu-open on the container, has-modal-open on <html>, which locks
 * scrolling and traps focus in the menu) and nothing closed it when the
 * window grew past 1020px, for example a tablet turned to landscape: the
 * drop-down stayed open, Close sat on top of "Join the team" and the page
 * would not scroll (QA-066).
 *
 * So when the window reaches 1020px with the menu open, this presses the
 * menu's own Close button, which runs core's close action (scroll lock and
 * focus trap released). If focus was in the menu, it stays on the same link
 * when that link is now in the bar (a nav link or the menu's "Join the
 * team"), and otherwise moves to the first link, instead of being lost on
 * the hidden Menu button.
 *
 * The phone menu ends with its own "Join the team" (QA-103), a link to
 * /#join. On Home that is the same page, and core's overlay does not close
 * when one of its links is followed: the menu would stay open over the Join
 * section with the page scroll-locked. So a same-page link chosen in the open
 * menu presses Close first, and the browser then goes to the section.
 *
 * Without JavaScript the menu can't open, and site.css shows the links in a
 * row instead (QA-099).
 */
( function () {
	'use strict';

	const wide = window.matchMedia( '(min-width: 1020px)' );
	const OPEN_MENU = 'nav.nav .wp-block-navigation__responsive-container.is-menu-open';

	function shown( el ) {
		return !! el && el.getClientRects().length > 0 && window.getComputedStyle( el ).visibility !== 'hidden';
	}

	function closeOpenMenus() {
		if ( ! wide.matches ) {
			return;
		}
		document.querySelectorAll( OPEN_MENU ).forEach( ( menu ) => {
			const close = menu.querySelector( '.wp-block-navigation__responsive-container-close' );
			if ( ! close ) {
				return;
			}
			const focused = menu.contains( document.activeElement ) ? document.activeElement : null;
			close.click();
			if ( ! focused ) {
				return;
			}
			// Core hands focus back to the Menu button, which is hidden at this width.
			window.requestAnimationFrame( () => {
				if ( shown( document.activeElement ) && document.activeElement !== document.body ) {
					return;
				}
				const first = menu.querySelector( '.wp-block-navigation-item__content' );
				if ( focused !== close && shown( focused ) ) {
					focused.focus();
				} else if ( shown( first ) ) {
					first.focus();
				}
			} );
		} );
	}

	// A link to a section of this page, chosen in the open menu: close the
	// menu (core releases the scroll lock), then let the browser follow it.
	document.addEventListener( 'click', ( event ) => {
		const link = event.target instanceof Element ? event.target.closest( 'a[href*="#"]' ) : null;
		const menu = link ? link.closest( OPEN_MENU ) : null;
		if ( ! menu || ! link.hash || link.origin !== window.location.origin || link.pathname !== window.location.pathname || link.search !== window.location.search ) {
			return;
		}
		const close = menu.querySelector( '.wp-block-navigation__responsive-container-close' );
		if ( close ) {
			close.click();
		}
	} );

	if ( typeof wide.addEventListener === 'function' ) {
		wide.addEventListener( 'change', closeOpenMenus );
	} else if ( typeof wide.addListener === 'function' ) {
		wide.addListener( closeOpenMenus );
	}
}() );
