/**
 * @spokares/toc: the About contents (spokares/toc).
 *
 * 1. Works out the chapter you are in as you scroll, marks its link
 *    aria-current="true" (and the ones above it as read), and draws the red
 *    rail down the side contents to it. Port of B's about.html script.
 * 2. On phones (under 1024px) shows the "On this page" button once the
 *    closed <details> list has scrolled away; the button brings you back to
 *    it and opens it.
 */

function initSide( nav ) {
	const list = nav.querySelector( 'ol' );
	const rail = nav.querySelector( '.toc__rail' );
	if ( ! list ) {
		return;
	}
	const links = Array.from( list.querySelectorAll( 'a[href^="#"]' ) );
	const heads = links.map( ( a ) => document.getElementById( decodeURIComponent( a.getAttribute( 'href' ).slice( 1 ) ) ) );
	const mobileLinks = Array.from( document.querySelectorAll( '.wp-block-spokares-toc .toc-mobile a[href^="#"]' ) );

	const draw = () => {
		const line = window.innerHeight * 0.25;
		let idx = -1;
		heads.forEach( ( h, i ) => {
			if ( h && h.getBoundingClientRect().top <= line ) {
				idx = i;
			}
		} );
		links.forEach( ( a, i ) => {
			a.classList.toggle( 'is-active', i === idx );
			a.classList.toggle( 'is-read', idx > -1 && i < idx );
			if ( i === idx ) {
				a.setAttribute( 'aria-current', 'true' );
			} else {
				a.removeAttribute( 'aria-current' );
			}
		} );
		mobileLinks.forEach( ( a ) => {
			const on = idx > -1 && a.getAttribute( 'href' ) === links[ idx ].getAttribute( 'href' );
			if ( on ) {
				a.setAttribute( 'aria-current', 'true' );
			} else {
				a.removeAttribute( 'aria-current' );
			}
		} );
		if ( rail ) {
			rail.style.top = `${ list.offsetTop }px`;
			rail.style.left = `${ list.offsetLeft - 1 }px`;
			rail.style.height = idx > -1 ? `${ links[ idx ].offsetTop + links[ idx ].offsetHeight }px` : '0px';
		}
	};

	let queued = false;
	const queue = () => {
		if ( queued ) {
			return;
		}
		queued = true;
		window.requestAnimationFrame( () => {
			queued = false;
			draw();
		} );
	};
	window.addEventListener( 'scroll', queue, { passive: true } );
	window.addEventListener( 'resize', queue );
	window.addEventListener( 'load', draw );
	draw();
}

function initFab( block ) {
	const fab = block.querySelector( '.contents-fab' );
	const det = block.querySelector( 'details.toc-mobile' );
	if ( ! fab || ! det ) {
		return;
	}
	fab.hidden = false;
	fab.classList.add( 'is-parked' );
	if ( 'IntersectionObserver' in window ) {
		new IntersectionObserver( ( entries ) => {
			entries.forEach( ( e ) => {
				// Parked while the list is on screen or still below it.
				fab.classList.toggle( 'is-parked', e.isIntersecting || e.boundingClientRect.top > 0 );
			} );
		} ).observe( det );
	}
	fab.addEventListener( 'click', () => {
		det.open = true;
		det.scrollIntoView( { block: 'start' } );
		const summary = det.querySelector( 'summary' );
		if ( summary ) {
			summary.focus();
		}
	} );
}

function init() {
	document.querySelectorAll( '.wp-block-spokares-toc' ).forEach( ( block ) => {
		const side = block.querySelector( 'nav.toc--side' );
		if ( side ) {
			initSide( side );
		}
		initFab( block );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
