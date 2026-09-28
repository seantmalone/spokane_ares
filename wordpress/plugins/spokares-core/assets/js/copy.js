/**
 * @spokares/copy: the Copy buttons on the radio settings (spokares/net views
 * "bar" and "settings"). Port of B's site.js §9 and its toast.
 *
 * The buttons ship with `hidden`; this module shows them. The copied text is
 * the button's data-copy-text. Messages are set with textContent.
 */

let toastEl = null;
let toastTimer = 0;

/*
 * The one status region. It is made, empty, when the module starts, so it is
 * in the page before its first message: a screen reader announces a change
 * to a live region it already knows, not a region added with its text.
 */
function region() {
	if ( ! toastEl ) {
		toastEl = document.createElement( 'div' );
		toastEl.className = 'toast';
		toastEl.setAttribute( 'role', 'status' );
		toastEl.setAttribute( 'aria-live', 'polite' );
		toastEl.setAttribute( 'aria-atomic', 'true' );
		document.body.appendChild( toastEl );
	}
	return toastEl;
}

function toast( message ) {
	const el = region();
	// The same message twice in a row is still a change (a trailing no-break
	// space), so the second copy is announced too.
	el.textContent = el.textContent === message ? `${ message }\u00A0` : message;
	el.classList.add( 'is-on' );
	clearTimeout( toastTimer );
	toastTimer = setTimeout( () => el.classList.remove( 'is-on' ), 3600 );
}

function fallbackCopy( text ) {
	const ta = document.createElement( 'textarea' );
	ta.value = text;
	ta.setAttribute( 'readonly', '' );
	ta.style.position = 'fixed';
	ta.style.top = '-1000px';
	document.body.appendChild( ta );
	ta.select();
	let ok = false;
	try {
		ok = document.execCommand( 'copy' );
	} catch ( e ) {
		ok = false;
	}
	ta.remove();
	return ok;
}

async function copyText( text ) {
	if ( navigator.clipboard && window.isSecureContext ) {
		try {
			await navigator.clipboard.writeText( text );
			return true;
		} catch ( e ) {
			return fallbackCopy( text );
		}
	}
	return fallbackCopy( text );
}

function init() {
	const buttons = document.querySelectorAll( 'button[data-copy-text]' );
	buttons.forEach( ( b ) => {
		b.hidden = false;
	} );
	if ( buttons.length ) {
		region();
	}
	document.addEventListener( 'click', async ( e ) => {
		const b = e.target.closest( 'button[data-copy-text]' );
		if ( ! b ) {
			return;
		}
		const text = b.getAttribute( 'data-copy-text' ) || '';
		const ok = await copyText( text );
		toast( ok ? `Copied: ${ text }` : `Copy did not work here. The settings are: ${ text }` );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
