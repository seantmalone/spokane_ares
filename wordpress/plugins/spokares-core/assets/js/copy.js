/**
 * @spokares/copy: the Copy buttons on the radio settings (spokares/net views
 * "bar" and "settings"). Port of B's site.js §9 and its toast.
 *
 * The buttons ship with `hidden`; this module shows them. The copied text is
 * the button's data-copy-text. Messages are set with textContent.
 */

let toastEl = null;
let toastTimer = 0;

function toast( message ) {
	if ( ! toastEl ) {
		toastEl = document.createElement( 'div' );
		toastEl.className = 'toast';
		toastEl.setAttribute( 'role', 'status' );
		toastEl.setAttribute( 'aria-live', 'polite' );
		document.body.appendChild( toastEl );
	}
	toastEl.textContent = message;
	toastEl.classList.add( 'is-on' );
	clearTimeout( toastTimer );
	toastTimer = setTimeout( () => toastEl.classList.remove( 'is-on' ), 3600 );
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
	document.querySelectorAll( 'button[data-copy-text]' ).forEach( ( b ) => {
		b.hidden = false;
	} );
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
