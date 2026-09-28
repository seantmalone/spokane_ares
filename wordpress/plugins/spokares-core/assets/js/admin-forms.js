/**
 * spokares-admin-forms: small helpers shared by the plugin's admin screens.
 * Plain script, no dependencies. Every form still works without it.
 *
 *  - Net Control Schedule: typing a call sign picks it; Volunteer needed and
 *    No net grey the box out; a red line while the box holds no call sign;
 *    "Other…" form reveals a text box; "Show 13 more Tuesdays" shows them
 *    in place.
 *  - Net Settings: the "Members see" lines follow the form; a frequency the
 *    save would refuse says why under the box.
 *  - Forms marked data-spk-guard ask before you leave with unsaved changes.
 *  - Links marked data-spk-ask ask first (Pull this file now).
 *  - Fields outlined for a problem are tied to their sentence.
 *
 * Events, meetings, documents and Most Used have their own scripts.
 */
( function () {
	'use strict';
	var cfg = window.spokaresAdmin || {};
	var $ = function ( sel, ctx ) { return ( ctx || document ).querySelector( sel ); };
	var $$ = function ( sel, ctx ) { return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) ); };

	/* The server's call-sign rule (guards.php spokares_call_sign()): the first
	   word that looks like a call sign and has a letter. */
	var CALL_RE = /^[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,4}(\/[A-Z0-9]+)?$/;
	function callSign( typed ) {
		var words = String( typed || '' ).trim().toUpperCase().split( /[^A-Z0-9\/]+/ ).filter( Boolean );
		for ( var i = 0; i < words.length; i++ ) {
			if ( CALL_RE.test( words[ i ] ) && /[A-Z]/.test( words[ i ] ) ) {
				return words[ i ];
			}
		}
		return '';
	}

	/* Add or remove one id in a field's aria-describedby. */
	function describedBy( field, id, on ) {
		var ids = ( field.getAttribute( 'aria-describedby' ) || '' ).split( ' ' ).filter( function ( x ) { return x && x !== id; } );
		if ( on ) { ids.push( id ); }
		if ( ids.length ) { field.setAttribute( 'aria-describedby', ids.join( ' ' ) ); } else { field.removeAttribute( 'aria-describedby' ); }
	}

	/* ------------------------------------------------ Net Control Schedule */
	function rota() {
		var form = $( '#spk-rota-form' );
		if ( ! form ) {
			return;
		}
		var words = window.spokaresRota || {};
		$$( 'tr.spk-rota-row', form ).forEach( function ( row ) {
			var box = $( '.spk-call', row );
			var radios = $$( 'input[type="radio"][name$="[state]"]', row );
			var callRadio = radios.filter( function ( x ) { return x.value === 'call'; } )[ 0 ];
			var winlink = $$( '.spk-wl-task, .spk-form-select, .spk-form-other', row );
			var check = $( '.spk-call-check', row ); // The server's line after a refused save.
			var pointer = false;
			function state() {
				var r = radios.filter( function ( x ) { return x.checked; } )[ 0 ];
				return r ? r.value : 'tbd';
			}
			// Greyed and read-only, never disabled: a disabled box gets no
			// clicks, so it could never come back to life, and isn't sent.
			function grey( el, off ) {
				off = off && document.activeElement !== el;
				el.classList.toggle( 'is-off', off );
				if ( el.tagName !== 'SELECT' ) {
					el.readOnly = off;
				}
			}
			function wake( el ) {
				el.classList.remove( 'is-off' );
				if ( el.tagName !== 'SELECT' ) {
					el.readOnly = false;
				}
			}
			// The red line under the box, only while its text has no call sign.
			function checkCall() {
				var bad = state() === 'call' && box.value.trim() !== '' && ! callSign( box.value );
				if ( bad && ! check ) {
					check = document.createElement( 'span' );
					check.className = 'spk-error-text spk-call-check';
					check.textContent = words.noCall || 'Type a call sign, like NZ2S, not a name.';
					var fieldset = box.closest( 'fieldset' );
					fieldset.parentNode.insertBefore( check, fieldset.nextSibling );
				}
				if ( ! check ) {
					return;
				}
				check.id = check.id || 'spk-call-check-' + row.getAttribute( 'data-date' );
				check.hidden = ! bad;
				box.classList.toggle( 'spk-field-error', bad );
				describedBy( box, check.id, bad );
				if ( bad ) { box.setAttribute( 'aria-invalid', 'true' ); } else { box.removeAttribute( 'aria-invalid' ); }
			}
			function sync() {
				var s = state();
				if ( box ) {
					grey( box, s === 'open' || s === 'none' );
					checkCall();
				}
				winlink.forEach( function ( el ) { grey( el, s === 'none' ); } );
			}
			if ( box ) {
				// Clicking or tabbing into a greyed box opens it for typing;
				// typing then picks the call-sign choice.
				box.addEventListener( 'focus', function () { wake( box ); } );
				box.addEventListener( 'pointerdown', function () { wake( box ); } );
				box.addEventListener( 'blur', function () { window.setTimeout( sync, 0 ); } );
				box.addEventListener( 'input', function () {
					if ( callRadio && box.value.trim() ) {
						callRadio.checked = true;
					}
					sync();
				} );
			}
			winlink.forEach( function ( el ) {
				el.addEventListener( 'focus', function () { wake( el ); } );
				el.addEventListener( 'pointerdown', function () { wake( el ); } );
				el.addEventListener( 'blur', function () { window.setTimeout( sync, 0 ); } );
			} );
			radios.forEach( function ( r ) {
				// Arrow keys choose each radio as they reach it: the focus stays in
				// the group (WCAG 3.2.2). Only a click on the call-sign choice
				// moves on to its box.
				var label = r.closest( 'label' ) || r;
				label.addEventListener( 'pointerdown', function () { pointer = true; } );
				r.addEventListener( 'keydown', function () { pointer = false; } );
				r.addEventListener( 'change', function () {
					sync();
					if ( pointer && r.value === 'call' && box ) {
						box.focus();
					}
					pointer = false;
				} );
			} );
			var sel = $( '.spk-form-select', row );
			var other = $( '.spk-form-other', row );
			if ( sel && other ) {
				// Reveal the Form name box; the focus stays on the select (a
				// keyboard can choose "Other…" on its way down the list).
				sel.addEventListener( 'change', function () {
					other.hidden = sel.value !== '__other';
				} );
			}
			sync();
		} );

		// "Show 13 more Tuesdays": the rows are all on the page; show the next
		// 13 in place, so nothing typed is lost. The weeks fields keep them
		// showing after Save or Undo.
		var more = $( '.spk-more-link', form );
		if ( more ) {
			more.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var hidden = $$( 'tr.spk-rota-row[hidden]', form );
				hidden.slice( 0, 13 ).forEach( function ( r ) { r.hidden = false; } );
				var shown = $$( 'tr.spk-rota-row', form ).filter( function ( r ) { return ! r.hidden; } ).length;
				$$( '#spk-rota-form input[name="weeks"], #spk-rota-undo input[name="weeks"]' ).forEach( function ( input ) { input.value = String( shown ); } );
				if ( hidden.length <= 13 ) {
					var end = document.createElement( 'span' );
					end.tabIndex = -1;
					end.textContent = words.end || 'That’s as far ahead as you can post.';
					more.parentNode.replaceChild( end, more );
					end.focus();
				} else {
					var url = new URL( more.href, window.location.href );
					url.searchParams.set( 'weeks', String( Math.min( 52, shown + 13 ) ) );
					more.href = url.toString();
				}
			} );
		}
	}

	/* ------------------------------------------------------- Net Settings */
	/* The "Members see" lines print what the site will print (admin-net.php
	   spokares_net_preview_lines() draws them on load from the site's own
	   formatters; this redraws them as the editor types, the same way). A
	   call sign, frequency or time the save would refuse leaves the stored
	   one on the site, so the lines show the stored one. */
	function netPreview() {
		var form = $( '#spk-net-form' );
		if ( ! form ) { return; }
		var words = window.spokaresNet || {};
		var bands = cfg.bands || [];
		function val( sel ) { var el = $( sel, form ); return el && typeof el.value === 'string' ? el.value.trim() : ''; }
		function stored( sel ) { var el = $( sel, form ); return el ? ( el.getAttribute( 'data-stored' ) || '' ) : ''; }
		function weeks( kind ) {
			return $$( 'select[name^="nets[week]"]', form ).filter( function ( s ) { return s.value === kind; } ).map( function ( s ) {
				return parseInt( s.name.replace( /\D+/g, '' ), 10 );
			} ).sort();
		}
		function time( hhmm ) {
			var m = /^(\d{2}):(\d{2})$/.exec( hhmm || '' );
			if ( ! m ) { return ''; }
			var h = parseInt( m[ 1 ], 10 );
			if ( h > 23 || parseInt( m[ 2 ], 10 ) > 59 ) { return ''; }
			if ( h === 12 && m[ 2 ] === '00' ) { return 'noon'; }
			return ( ( h % 12 ) || 12 ) + ':' + m[ 2 ] + ' ' + ( h < 12 ? 'AM' : 'PM' );
		}
		function shapeOk( f ) { return /^\d{2,3}\.\d{3}$/.test( f ); }
		function freqOk( f ) {
			if ( ! shapeOk( f ) ) { return false; }
			var x = parseFloat( f );
			return bands.some( function ( b ) { return x >= b[ 0 ] && x <= b[ 1 ]; } );
		}
		function oneCall( typed ) {
			var list = String( typed || '' ).trim().toUpperCase().split( /[^A-Z0-9\/]+/ ).filter( Boolean );
			return list.length === 1 && CALL_RE.test( list[ 0 ] ) && /[A-Z]/.test( list[ 0 ] ) ? list[ 0 ] : '';
		}
		function minus( s ) { return ( s || '' ).replace( /(^|\s)-(\d)/, '$1−$2' ); }
		function ord( n, cap ) { return [ '', '1st', '2nd', '3rd', '4th', cap ? 'Fifth' : 'fifth' ][ n ] || String( n ); }
		function ords( list, cap ) {
			var w = list.map( function ( n, i ) { return ord( n, cap && i === 0 ); } );
			if ( w.length < 2 ) { return w.join( '' ); }
			var last = w.pop();
			return w.join( ', ' ) + ' and ' + last;
		}
		function set( key, text ) {
			var el = $( '[data-preview="' + key + '"]', form );
			if ( ! el ) { return; }
			if ( el.textContent !== text ) { el.textContent = text; }
			el.hidden = ! text;
		}
		function draw() {
			var call = oneCall( val( '#spk-p-call' ) ) || stored( '#spk-p-call' );
			var typedFreq = val( '#spk-p-freq' );
			var freq = freqOk( typedFreq ) ? typedFreq : stored( '#spk-p-freq' );
			var off = val( '#spk-p-offset' ), tone = val( '#spk-p-tone' );
			var t = time( val( '#spk-net-time' ) ) || time( stored( '#spk-net-time' ) );
			var mhz = freq ? freq + ' MHz' : '';
			set( 'bar', [ t, ( call + ' ' + [ mhz, minus( off ), tone ].filter( Boolean ).join( ', ' ) ).trim() ].filter( Boolean ).join( ' · ' ) );
			var typedAlt = val( '#spk-a-freq' );
			var afreq = ( '' === typedAlt || freqOk( typedAlt ) ) ? typedAlt : stored( '#spk-a-freq' );
			var aoff = val( '#spk-a-offset' ), atone = val( '#spk-a-tone' ), show = $( '#spk-a-show', form );
			// As the How it works row prints it.
			set( 'alt', show && show.checked && afreq ? ( words.alt || 'Alternate' ) + ' · ' + [ afreq + ' MHz', minus( aoff ), atone ? atone + ' tone' : '' ].filter( Boolean ).join( ', ' ) : ( words.notShown || 'Not shown on How it works.' ) );
			var wl = weeks( 'winlink' ), sx = weeks( 'simplex' ), gm = weeks( 'gmrs' );
			var gtTyped = val( '#spk-gmrs-time' );
			var gt = '' === gtTyped ? '' : ( time( gtTyped ) || time( stored( '#spk-gmrs-time' ) ) );
			set( 'winlink', wl.length ? 'Winlink nights: ' + ords( wl ) + ' Tuesdays; net control gives a Winlink assignment during the net.' : '' );
			set( 'simplex', sx.length ? ords( sx, true ) + ' Tuesdays: the net starts on simplex, then moves to ' + call + '.' : '' );
			set( 'gmrs', gm.length ? 'ACS GMRS net: ' + ords( gm ) + ' Tuesdays, ' + ( gt ? gt + ', ' : '' ) + 'for county volunteers with GMRS licenses.' : '' );
			var list = $( '.spk-members-see-list', form );
			var others = list && list.closest( 'tr' );
			if ( others ) {
				others.hidden = ! ( wl.length || sx.length || gm.length );
			}
		}
		form.addEventListener( 'input', draw );
		form.addEventListener( 'change', draw );

		// A frequency the save would refuse: one line in place of its hint,
		// said once the shape is complete (or on leaving the box), and gone
		// as soon as it is right.
		$$( 'input.spk-freq', form ).forEach( function ( input ) {
			var hint = document.getElementById( ( input.getAttribute( 'aria-describedby' ) || '' ).split( ' ' )[ 0 ] );
			if ( ! hint ) { return; }
			function problem() {
				var f = input.value.trim();
				if ( '' === f ) { return input.required ? ( words.format || '' ) : ''; }
				if ( ! shapeOk( f ) ) { return words.format || ''; }
				if ( freqOk( f ) ) { return ''; }
				var keep = input.getAttribute( 'data-stored' ) || '';
				return keep ? ( words.band || '%s' ).replace( '%s', keep ) : ( words.bandNone || '' );
			}
			function show( text ) {
				hint.textContent = text || hint.getAttribute( 'data-hint' ) || '';
				hint.className = text ? 'spk-error-text' : 'description';
				input.classList.toggle( 'spk-field-error', !! text );
				if ( text ) { input.setAttribute( 'aria-invalid', 'true' ); } else { input.removeAttribute( 'aria-invalid' ); }
			}
			input.addEventListener( 'input', function () {
				if ( hint.classList.contains( 'spk-error-text' ) || shapeOk( input.value.trim() ) ) {
					show( problem() );
				}
			} );
			input.addEventListener( 'blur', function () { show( problem() ); } );
		} );
	}

	/* A settings form (data-spk-guard) asks before the page is left with
	   changes that weren't saved: a menu link, Undo, the browser's Back. Its
	   own Save doesn't ask. A form drawn with typing that wasn't saved
	   (data-spk-dirty, after a refused save) counts as changed. */
	function guard() {
		function snapshot( form ) {
			return Array.prototype.map.call( form.elements, function ( el ) {
				if ( ! el.name || /^(hidden|submit|button|file|reset|image)$/.test( el.type ) ) { return ''; }
				if ( el.type === 'checkbox' || el.type === 'radio' ) { return el.checked ? el.name + '=' + el.value : ''; }
				return el.name + '=' + el.value;
			} ).join( '&' );
		}
		$$( 'form[data-spk-guard]' ).forEach( function ( form ) {
			var start = snapshot( form );
			var sending = false;
			form.addEventListener( 'submit', function () { sending = true; } );
			window.addEventListener( 'pageshow', function () { sending = false; } );
			window.addEventListener( 'beforeunload', function ( e ) {
				if ( sending || ! ( form.hasAttribute( 'data-spk-dirty' ) || snapshot( form ) !== start ) ) {
					return;
				}
				e.preventDefault();
				e.returnValue = '';
			} );
		} );
	}

	/* A link that does something that can't be undone asks first
	   (data-spk-ask, e.g. "Pull this file now"). */
	function asks() {
		document.addEventListener( 'click', function ( e ) {
			var a = e.target && e.target.closest ? e.target.closest( 'a[data-spk-ask]' ) : null;
			if ( a && ! window.confirm( a.getAttribute( 'data-spk-ask' ) ) ) {
				e.preventDefault();
			}
		} );
	}

	/* A field outlined for a problem says so to assistive technology: its
	   sentence is tied to it (aria-describedby) and it is marked invalid. The
	   first error notice takes the focus after the save, so it is read first. */
	function problems() {
		var n = 0;
		$$( 'input.spk-field-error, select.spk-field-error, textarea.spk-field-error' ).forEach( function ( field ) {
			field.setAttribute( 'aria-invalid', 'true' );
			var scope = field.closest( 'td, .spk-field, .spk-dates, fieldset, p' );
			var msgs = scope ? $$( '.spk-error-text', scope ) : [];
			var msg = msgs.filter( function ( m ) {
				return field.compareDocumentPosition( m ) & Node.DOCUMENT_POSITION_FOLLOWING;
			} )[ 0 ] || msgs[ 0 ];
			if ( ! msg && field.parentNode ) {
				msg = $$( '.spk-error-text', field.parentNode.parentNode || field.parentNode )[ 0 ];
			}
			if ( msg ) {
				if ( ! msg.id ) {
					n++;
					msg.id = 'spk-problem-' + n;
				}
				var by = ( field.getAttribute( 'aria-describedby' ) || '' ).split( ' ' ).filter( Boolean );
				if ( by.indexOf( msg.id ) === -1 ) {
					by.push( msg.id );
					field.setAttribute( 'aria-describedby', by.join( ' ' ) );
				}
			}
		} );
		// After WordPress has moved the notices under the heading (moving an
		// element drops its focus), so once the page has loaded.
		function focusNotice() {
			// A refusal first; else a partial save ("Saved, except …").
			var notice = $( '.spk-notice.notice-error' ) || $( '.spk-notice.notice-warning' );
			if ( notice && ( ! document.activeElement || document.activeElement === document.body ) ) {
				notice.setAttribute( 'tabindex', '-1' );
				notice.focus( { preventScroll: true } );
			}
		}
		if ( document.readyState === 'complete' ) {
			window.setTimeout( focusNotice, 0 );
		} else {
			window.addEventListener( 'load', function () { window.setTimeout( focusNotice, 0 ); } );
		}
	}

	function boot() {
		[ rota, netPreview, guard, asks, problems ].forEach( function ( fn ) {
			try { fn(); } catch ( e ) { if ( window.console ) { window.console.warn( '[spokares-admin-forms]', e ); } }
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
