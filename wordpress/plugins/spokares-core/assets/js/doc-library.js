/**
 * @spokares/doc-library: instant filtering of the document library
 * (spokares/docs view "library").
 *
 * The server already applied ?q= and ?section= (rows and groups carry
 * `hidden`, the count is right), so the page works without this module.
 * Here: every word typed must appear in a row's data-search; chips filter by
 * section (or "Most used"); the URL keeps ?q= and ?section= in step with
 * history.replaceState; a #<section> hash on load picks that chip. Typed text
 * is written back with textContent only.
 */

function init() {
	const lib = document.querySelector( '[data-doc-library]' );
	if ( ! lib ) {
		return;
	}
	const form = lib.querySelector( 'form.lib__search' );
	const input = form ? form.querySelector( 'input[name="q"]' ) : null;
	const rows = Array.from( lib.querySelectorAll( 'tr[data-doc]' ) );
	const groups = Array.from( lib.querySelectorAll( 'section.doc-group' ) );
	const chips = Array.from( lib.querySelectorAll( '.lib__cats a.chip' ) );
	const count = lib.querySelector( '.doc-count' );
	const empty = lib.querySelector( '.doc-empty' );
	const emptyQ = lib.querySelector( '[data-doc-empty-q]' );
	const sections = new Set( chips.map( ( c ) => c.getAttribute( 'data-section' ) ).filter( Boolean ) );

	const params = new URLSearchParams( window.location.search );
	const state = {
		q: input ? input.value.trim() : '',
		section: sections.has( params.get( 'section' ) ) ? params.get( 'section' ) : '',
	};
	const hash = window.location.hash.slice( 1 );
	if ( ! state.section && sections.has( hash ) ) {
		state.section = hash;
	}

	function syncHidden() {
		if ( ! form ) {
			return;
		}
		let hidden = form.querySelector( 'input[type="hidden"][name="section"]' );
		if ( state.section ) {
			if ( ! hidden ) {
				hidden = document.createElement( 'input' );
				hidden.type = 'hidden';
				hidden.name = 'section';
				form.appendChild( hidden );
			}
			hidden.value = state.section;
		} else if ( hidden ) {
			hidden.remove();
		}
	}

	function apply( updateUrl ) {
		const words = state.q.toLowerCase().split( /\s+/ ).filter( Boolean );
		let shown = 0;
		rows.forEach( ( row ) => {
			const hay = row.getAttribute( 'data-search' ) || '';
			let on = words.every( ( w ) => hay.includes( w ) );
			if ( on && state.section === 'most-used' ) {
				on = row.hasAttribute( 'data-most-used' );
			} else if ( on && state.section ) {
				const group = row.closest( 'section.doc-group' );
				on = !! group && group.getAttribute( 'data-section' ) === state.section;
			}
			row.hidden = ! on;
			if ( on ) {
				shown++;
			}
		} );
		groups.forEach( ( g ) => {
			g.hidden = ! g.querySelector( 'tr[data-doc]:not([hidden])' );
		} );
		chips.forEach( ( c ) => {
			const s = c.getAttribute( 'data-section' ) || '';
			if ( s === state.section ) {
				c.setAttribute( 'aria-current', 'true' );
			} else {
				c.removeAttribute( 'aria-current' );
			}
		} );
		const total = rows.length;
		const noun = total === 1 ? 'document' : 'documents';
		const filtered = !! ( state.q || state.section );
		if ( count ) {
			count.textContent = filtered ? `Showing ${ shown } of ${ total } ${ noun }.` : `Showing all ${ total } ${ noun }.`;
		}
		if ( empty ) {
			empty.hidden = ! ( filtered && shown === 0 );
		}
		if ( emptyQ ) {
			emptyQ.textContent = state.q;
		}
		syncHidden();
		if ( updateUrl ) {
			try {
				const u = new URL( window.location.href );
				u.hash = '';
				if ( state.q ) {
					u.searchParams.set( 'q', state.q );
				} else {
					u.searchParams.delete( 'q' );
				}
				if ( state.section ) {
					u.searchParams.set( 'section', state.section );
				} else {
					u.searchParams.delete( 'section' );
				}
				history.replaceState( null, '', u.toString() );
			} catch ( e ) {
				// Old browser: the page still works; the URL just doesn't follow.
			}
		}
	}

	let timer = 0;
	if ( input ) {
		input.addEventListener( 'input', () => {
			clearTimeout( timer );
			timer = setTimeout( () => {
				state.q = input.value.trim().slice( 0, 100 );
				apply( true );
			}, 160 );
		} );
	}
	if ( form ) {
		form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			state.q = input ? input.value.trim().slice( 0, 100 ) : '';
			apply( true );
		} );
	}
	chips.forEach( ( c ) => {
		c.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			state.section = c.getAttribute( 'data-section' ) || '';
			apply( true );
		} );
	} );
	lib.querySelectorAll( '[data-doc-clear]' ).forEach( ( b ) => {
		b.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			state.q = '';
			state.section = '';
			if ( input ) {
				input.value = '';
			}
			apply( true );
			if ( input ) {
				input.focus();
			}
		} );
	} );

	apply( false );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
