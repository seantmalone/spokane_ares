/**
 * spokares-admin-events: the event form and the two meeting screens.
 * Plain script, no dependencies; loaded after spokares-admin-forms. Every
 * form still works without it: this only shows and hides fields and keeps
 * choices in step.
 *
 *  - Event form: fields by type of event; When (On a date, Postponed, Date
 *    not posted yet, As requested for Public service only); the Cancelled
 *    tick only on a date; All day; a live weekday after First day and Last
 *    day; https:// added to a web address typed without it; the Words on
 *    the button placeholder follows the address; + Add a link. On Add, a
 *    name that is already an event says so ("Already an event: … Open it").
 *  - Cancel or Move a Meeting: Cancelled and Moved to exclude each other;
 *    unticking Cancelled (with Moved to empty) clears the row's Note.
 */
( function () {
	'use strict';
	var cfg = window.spokaresEvents || {};
	var $ = function ( sel, ctx ) { return ( ctx || document ).querySelector( sel ); };
	var $$ = function ( sel, ctx ) { return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) ); };

	/* Reveal the next spare row; hide the button when none are left. */
	function spares( buttonSel, rowSel ) {
		var btn = $( buttonSel );
		if ( ! btn ) {
			return;
		}
		function left() { return $$( rowSel + '[data-spk-spare][hidden]' ); }
		btn.addEventListener( 'click', function () {
			var next = left()[ 0 ];
			if ( next ) {
				next.hidden = false;
				var input = $( 'input:not([type="hidden"])', next );
				if ( input ) { input.focus(); }
			}
			btn.hidden = left().length === 0;
		} );
		btn.hidden = left().length === 0;
	}

	/* The weekday of a Y-m-d date ("Sat"), or ''. */
	function weekday( ymd ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( ymd || '' );
		if ( ! m ) { return ''; }
		var d = new Date( Date.UTC( +m[ 1 ], +m[ 2 ] - 1, +m[ 3 ] ) );
		if ( isNaN( d.getTime() ) || d.getUTCDate() !== +m[ 3 ] ) { return ''; }
		return ( cfg.weekdays || [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ] )[ d.getUTCDay() ] || '';
	}

	/* An address typed without its scheme ("www.arrl.org/kids-day") gets
	   https://, as the save does (guards.php spokares_clean_url()). */
	function withScheme( typed ) {
		var v = String( typed || '' ).trim();
		if ( ! v || /^[a-z][a-z0-9+.\-]*:(?!\d)/i.test( v ) || v.charAt( 0 ) === '/' ) { return v; }
		var first = v.split( /[\/?#]/ )[ 0 ];
		return first.indexOf( '.' ) > -1 && ! /[\s@]/.test( first ) ? 'https://' + v : v;
	}

	function host( url ) {
		var m = /^https?:\/\/([^\/?#:]+)/i.exec( url || '' );
		return m ? m[ 1 ].toLowerCase().replace( /^www\./, '' ) : '';
	}

	/* ------------------------------------------------------------ event form */
	function events() {
		var form = $( '[data-spk-kind-form]' );
		if ( ! form ) {
			return;
		}
		var kinds = $$( 'input[name="spk_kind"]' );
		var modes = $$( 'input[name="spk_date_mode"]' );
		var start = $( '#spk-start' );
		function kind() {
			var k = kinds.filter( function ( x ) { return x.checked; } )[ 0 ];
			return k ? k.value : '';
		}
		function mode() {
			var m = modes.filter( function ( x ) { return x.checked; } )[ 0 ];
			return m ? m.value : 'date';
		}
		function sync() {
			var k = kind();
			$$( '[data-kinds]', document ).forEach( function ( el ) {
				var list = el.getAttribute( 'data-kinds' ).split( ',' );
				el.hidden = ! k || list.indexOf( k ) === -1;
			} );
			// "As requested" is Public service's only: another type means the
			// date simply isn't posted yet.
			if ( k !== 'public-service' && mode() === 'as-requested' ) {
				var np = modes.filter( function ( x ) { return x.value === 'not-posted'; } )[ 0 ];
				if ( np ) { np.checked = true; }
			}
			var onDate = mode() === 'date';
			var dates = $( '.spk-dates', form );
			if ( dates ) {
				// The dates, times and the Cancelled tick belong to "On a date".
				dates.hidden = ! onDate;
			}
			if ( start ) {
				start.required = onDate;
			}
		}
		kinds.concat( modes ).forEach( function ( r ) { r.addEventListener( 'change', sync ); } );

		// The event name is required for Publish (Save draft skips the check).
		var title = $( '#title' );
		if ( title ) {
			title.required = true;
			var label = $( '.spk-title-label' );
			var said = $( '.spk-event-form > .spk-error-text' );
			if ( label && label.classList.contains( 'spk-has-error' ) ) {
				title.setAttribute( 'aria-invalid', 'true' );
				if ( said ) {
					said.id = said.id || 'spk-title-problem';
					title.setAttribute( 'aria-describedby', said.id );
				}
			}
		}

		var allDay = $( '#spk-all-day' );
		var times = $( '.spk-times', form );
		if ( allDay && times ) {
			allDay.addEventListener( 'change', function () {
				times.hidden = allDay.checked;
				if ( allDay.checked ) {
					$$( 'input[type="time"]', times ).forEach( function ( t ) { t.value = ''; } );
				}
			} );
		}

		// The weekday after First day and Last day, as the date is picked.
		$$( '.spk-weekday[data-for]', form ).forEach( function ( out ) {
			var input = document.getElementById( out.getAttribute( 'data-for' ) );
			if ( ! input ) { return; }
			function show() { out.textContent = weekday( input.value ); }
			input.addEventListener( 'input', show );
			input.addEventListener( 'change', show );
			show();
		} );

		// A web address typed without https:// gets it when the box is left.
		$$( 'input.spk-url', form ).forEach( function ( input ) {
			input.addEventListener( 'blur', function () {
				var v = withScheme( input.value );
				if ( v !== input.value ) {
					input.value = v;
					input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				}
			} );
		} );

		// Words on the button: the placeholder is what the site prints when
		// the words are left empty.
		var url = $( '#spk-main-url' );
		var words = $( '#spk-main-label' );
		if ( url && words ) {
			var placeholder = function () {
				var groups = host( withScheme( url.value ) ) === ( cfg.groupsHost || 'spokaneares-acs.groups.io' );
				words.placeholder = groups ? ( cfg.wordsGroup || 'Exercise details on groups.io' ) : ( cfg.words || 'Details' );
			};
			url.addEventListener( 'input', placeholder );
			url.addEventListener( 'change', placeholder );
			placeholder();
		}

		spares( '.spk-add-link', '.spk-link-row' );
		sync();
	}

	/* ---------------------------------------------- cancel or move a meeting */
	function meetings() {
		$$( 'tr.spk-meeting-row' ).forEach( function ( row ) {
			var c = $( '.spk-cancel', row );
			var m = $( '.spk-moved', row );
			var note = $( 'input.spk-note', row );
			if ( ! c || ! m ) { return; }
			var stored = c.getAttribute( 'data-stored-kind' ) || '';
			// Taking back a cancellation or move: the note that went with it goes too.
			function backOn() {
				if ( note && ! c.checked && ! m.value && ( stored === 'cancelled' || stored === 'moved' ) ) {
					note.value = '';
				}
			}
			m.addEventListener( 'input', function () { if ( m.value ) { c.checked = false; } } );
			m.addEventListener( 'change', backOn );
			c.addEventListener( 'change', function () {
				if ( c.checked ) {
					m.value = '';
				} else {
					backOn();
				}
			} );
		} );
	}

	/* On Add an Event: "Already an event: Lilac Festival Armed Forces
	   Torchlight Parade (Date not posted yet). Open it" under the name, when
	   the typed name is an event's name (case and punctuation aside), starts
	   one, or has only words that start its words ("Lilac parade"). */
	function plain( name ) {
		return String( name || '' ).toLowerCase().replace( /[^\p{L}\p{N}]+/gu, ' ' ).trim();
	}
	function fmt( text, a, b ) {
		return String( text || '' ).replace( '%1$s', function () { return String( a ); } ).replace( '%2$s', function () { return String( b ); } );
	}
	function nameCheck() {
		var title = $( '#title' );
		var box = $( '#spk-name-match' );
		if ( ! title || ! box || ! cfg.events ) {
			return;
		}
		function check() {
			var t = plain( title.value );
			var hit = null;
			if ( t.length >= 3 ) {
				hit = cfg.events.filter( function ( e ) { return plain( e[ 1 ] ) === t; } )[ 0 ] || null;
				if ( ! hit && t.length >= 5 ) {
					hit = cfg.events.filter( function ( e ) { return plain( e[ 1 ] ).indexOf( t + ' ' ) === 0; } )[ 0 ] || null;
				}
				var words = t.split( ' ' );
				if ( ! hit && words.length >= 2 && t.length >= 6 ) {
					hit = cfg.events.filter( function ( e ) {
						var name = plain( e[ 1 ] ).split( ' ' );
						return words.every( function ( w ) { return name.some( function ( n ) { return n.indexOf( w ) === 0; } ); } );
					} )[ 0 ] || null;
				}
			}
			var key = hit ? String( hit[ 0 ] ) : '';
			if ( box.getAttribute( 'data-event' ) === key ) {
				return;
			}
			box.setAttribute( 'data-event', key );
			box.textContent = '';
			if ( ! hit ) {
				return;
			}
			box.appendChild( document.createTextNode( fmt( cfg.already, hit[ 1 ], hit[ 2 ] ) + ' ' ) );
			var a = document.createElement( 'a' );
			a.href = ( cfg.editBase || '' ) + hit[ 0 ];
			a.textContent = cfg.openIt || 'Open it';
			// Keep the focus in the name box (so WordPress doesn't save the
			// typed name as a draft on the way out), and don't ask "Leave site?".
			a.addEventListener( 'mousedown', function ( e ) { e.preventDefault(); } );
			a.addEventListener( 'click', function () {
				if ( window.jQuery ) {
					window.jQuery( window ).off( 'beforeunload.edit-post' );
				}
				if ( window.wp && window.wp.autosave && window.wp.autosave.server && window.wp.autosave.server.suspend ) {
					window.wp.autosave.server.suspend();
				}
			} );
			box.appendChild( a );
		}
		title.addEventListener( 'input', check );
		check();
	}

	function boot() {
		[ events, nameCheck, meetings ].forEach( function ( fn ) {
			try { fn(); } catch ( e ) { if ( window.console ) { window.console.warn( '[spokares-admin-events]', e ); } }
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
