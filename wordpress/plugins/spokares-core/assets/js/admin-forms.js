/**
 * spokares-admin-forms: small helpers for the plugin's admin screens.
 * Plain script, no dependencies. Every form still works without it; this
 * only shows and hides fields, keeps choices in step and fills the preview.
 *
 *  - Net rota: typing a call sign picks "Call sign"; Open / Not posted yet
 *    grey the box out; "Shows" follows; "Other…" form reveals a text box.
 *  - Events: fields by kind; date mode; All day; + Add a link.
 *  - Documents: fields by source; the Privacy check unlocks the file chooser;
 *    Mark reviewed today; + Add; the Trash link follows its checkbox.
 *  - Regular meetings: Cancelled and Moved to exclude each other.
 *  - Hub tiles: choosing a document already in another slot swaps the slots.
 *  - Net details: live preview. Forms marked data-spk-confirm ask first,
 *    and so do links marked data-spk-ask (Pull this file now).
 *  - Fields outlined for a problem are tied to their sentence.
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

	/* ------------------------------------------------------------ net rota */
	function rota() {
		// One quiet status line for screen readers, said once the typing stops,
		// instead of a live region in every Shows cell.
		var status = $( '#spk-rota-status' );
		var timer = 0;
		function announce( row, text ) {
			if ( ! status ) {
				return;
			}
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				status.textContent = ( cfg.rowShows || '%1$s shows %2$s' ).replace( '%1$s', row.getAttribute( 'data-day' ) || '' ).replace( '%2$s', text );
			}, 900 );
		}
		$$( 'tr.spk-rota-row' ).forEach( function ( row ) {
			var box = $( '.spk-call', row );
			var radios = $$( 'input[type="radio"][name$="[state]"]', row );
			var shows = $( '.spk-shows', row );
			var callRadio = radios.filter( function ( x ) { return x.value === 'call'; } )[ 0 ];
			var pointer = false;
			function state() {
				var r = radios.filter( function ( x ) { return x.checked; } )[ 0 ];
				return r ? r.value : 'tbd';
			}
			function sync( tell ) {
				var s = state();
				if ( box ) {
					// Read-only and greyed, never disabled: a disabled box gets
					// no clicks, so it could never come back to life. (The server
					// ignores the box unless Call sign is chosen.)
					var off = s !== 'call' && document.activeElement !== box;
					box.readOnly = off;
					box.classList.toggle( 'is-off', off );
				}
				if ( shows ) {
					var text;
					var bad = false;
					if ( s === 'call' && box && box.value.trim() ) {
						var call = callSign( box.value );
						text = call || cfg.noCall || 'No call sign: not saved';
						bad = ! call;
					} else if ( s === 'open' ) {
						text = cfg.open || 'Open';
					} else {
						text = cfg.notYet || 'Not yet published';
					}
					shows.classList.toggle( 'is-bad', bad );
					if ( shows.textContent !== text ) {
						shows.textContent = text;
						if ( tell ) {
							announce( row, text );
						}
					}
				}
			}
			if ( box ) {
				// Clicking or tabbing into a greyed box opens it for typing;
				// typing a call sign then picks "Call sign".
				box.addEventListener( 'focus', function () {
					box.readOnly = false;
					box.classList.remove( 'is-off' );
				} );
				box.addEventListener( 'pointerdown', function () {
					box.readOnly = false;
					box.classList.remove( 'is-off' );
				} );
				box.addEventListener( 'blur', function () {
					window.setTimeout( function () { sync( false ); }, 0 );
				} );
				box.addEventListener( 'input', function () {
					if ( callRadio && box.value.trim() ) {
						callRadio.checked = true;
					}
					sync( true );
				} );
			}
			radios.forEach( function ( r ) {
				// Arrow keys choose each radio as they reach it: the focus stays in
				// the group (WCAG 3.2.2). Only a click on "Call sign" moves on to
				// its box.
				var label = r.closest( 'label' ) || r;
				label.addEventListener( 'pointerdown', function () { pointer = true; } );
				r.addEventListener( 'keydown', function () { pointer = false; } );
				r.addEventListener( 'change', function () {
					sync( true );
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
			sync( false );
		} );
	}

	/* -------------------------------------------------------------- events */
	function events() {
		var form = $( '[data-spk-kind-form]' );
		if ( ! form ) {
			return;
		}
		var kinds = $$( 'input[name="spk_kind"]' );
		var modes = $$( 'input[name="spk_date_mode"]' );
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
			if ( k !== 'public-service' && mode() === 'as-requested' ) {
				var np = modes.filter( function ( x ) { return x.value === 'not-posted'; } )[ 0 ];
				if ( np ) { np.checked = true; }
			}
			var dates = $( '.spk-dates', form );
			if ( dates ) {
				dates.hidden = mode() !== 'date';
			}
		}
		kinds.concat( modes ).forEach( function ( r ) { r.addEventListener( 'change', sync ); } );
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
		spares( '.spk-add-link', '.spk-link-row' );
		sync();
	}

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

	/* ----------------------------------------------------------- documents */
	function documents() {
		var sources = $$( 'input[name="spk_source"]' );
		if ( ! sources.length ) {
			return;
		}
		function sync() {
			var s = sources.filter( function ( x ) { return x.checked; } )[ 0 ];
			var v = s ? s.value : 'upload';
			$$( '[data-source]' ).forEach( function ( el ) { el.hidden = el.getAttribute( 'data-source' ) !== v; } );
		}
		sources.forEach( function ( r ) { r.addEventListener( 'change', sync ); } );
		sync();
		var privacy = $( '#spk-privacy' );
		var file = $( '#spk-upload' );
		if ( privacy && file ) {
			privacy.addEventListener( 'change', function () {
				file.disabled = ! privacy.checked;
				if ( ! privacy.checked ) { file.value = ''; }
			} );
			file.disabled = ! privacy.checked;
		}
		$$( '.spk-reviewed-today' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var t = document.getElementById( b.getAttribute( 'data-target' ) );
				if ( t && cfg.today ) { t.value = cfg.today; }
			} );
		} );
		spares( '.spk-add-sub', '.spk-sub-row' );
		var trashBox = $( '#spk-trash-file' );
		var trashLink = $( '#spk-trash-link' );
		if ( trashBox && trashLink ) {
			trashBox.addEventListener( 'change', function () {
				var u = new URL( trashLink.href, window.location.href );
				if ( trashBox.checked ) { u.searchParams.set( 'spk_remove_file', '1' ); } else { u.searchParams.delete( 'spk_remove_file' ); }
				trashLink.href = u.toString();
			} );
		}
	}

	/* ------------------------------------------------------------ meetings */
	function meetings() {
		$$( 'tr.spk-meeting-row' ).forEach( function ( row ) {
			var c = $( '.spk-cancel', row );
			var m = $( '.spk-moved', row );
			if ( ! c || ! m ) { return; }
			m.addEventListener( 'input', function () { if ( m.value ) { c.checked = false; } } );
			c.addEventListener( 'change', function () { if ( c.checked ) { m.value = ''; } } );
		} );
	}

	/* --------------------------------------------------------------- tiles */
	function tiles() {
		var rows = $$( 'tr.spk-tile-row' );
		if ( ! rows.length ) { return; }
		var parts = rows.map( function ( r ) {
			return { doc: $( '.spk-tile-doc', r ), label: $( '.spk-tile-label', r ), icon: $( '.spk-tile-icon', r ) };
		} );
		var prev = parts.map( function ( p ) { return p.doc.value; } );
		function hints() {
			parts.forEach( function ( p, i ) {
				$$( 'option', p.doc ).forEach( function ( o ) {
					var title = o.getAttribute( 'data-title' );
					if ( ! title ) { return; }
					var j = -1;
					parts.forEach( function ( q, k ) { if ( k !== i && q.doc.value === o.value ) { j = k; } } );
					o.textContent = j > -1 ? title + ' ' + ( cfg.nowSlot || '(now slot %d)' ).replace( '%d', String( j + 1 ) ) : title;
				} );
			} );
		}
		parts.forEach( function ( p, i ) {
			p.doc.addEventListener( 'change', function () {
				var j = -1;
				parts.forEach( function ( q, k ) { if ( k !== i && q.doc.value === p.doc.value && p.doc.value !== '0' ) { j = k; } } );
				if ( j > -1 ) {
					var o = parts[ j ];
					var label = p.label.value, icon = p.icon.value;
					o.doc.value = prev[ i ];
					p.label.value = o.label.value;
					p.icon.value = o.icon.value;
					o.label.value = label;
					o.icon.value = icon;
				}
				prev = parts.map( function ( q ) { return q.doc.value; } );
				hints();
			} );
		} );
		hints();
	}

	/* --------------------------------------------------------- net details */
	/* The preview prints what the site will print (admin-net.php
	   spokares_net_preview_lines() draws it on load from the site's own
	   formatters; this redraws it as the editor types, the same way). A call
	   sign, frequency or time the save would refuse leaves the stored one on
	   the site, so the preview shows the stored one. */
	function netPreview() {
		var box = $( '#spk-net-preview' );
		var form = $( '#spk-net-form' );
		if ( ! box || ! form ) { return; }
		var bands = cfg.bands || [];
		function val( sel ) { var el = $( sel, form ); return el ? el.value.trim() : ''; }
		function stored( sel ) { var el = $( sel, form ); return el ? ( el.getAttribute( 'data-stored' ) || '' ) : ''; }
		function weeks( name ) {
			return $$( 'input[name="nets[' + name + '][]"]:checked', form ).map( function ( x ) { return parseInt( x.value, 10 ); } ).sort();
		}
		function without( list, taken ) { return list.filter( function ( n ) { return taken.indexOf( n ) === -1; } ); }
		function time( hhmm ) {
			var m = /^(\d{2}):(\d{2})$/.exec( hhmm || '' );
			if ( ! m ) { return ''; }
			var h = parseInt( m[ 1 ], 10 );
			if ( h > 23 || parseInt( m[ 2 ], 10 ) > 59 ) { return ''; }
			if ( h === 12 && m[ 2 ] === '00' ) { return 'noon'; }
			return ( ( h % 12 ) || 12 ) + ':' + m[ 2 ] + ' ' + ( h < 12 ? 'AM' : 'PM' );
		}
		function freqOk( f ) {
			if ( ! /^\d{2,3}\.\d{3}$/.test( f ) ) { return false; }
			var x = parseFloat( f );
			return bands.some( function ( b ) { return x >= b[ 0 ] && x <= b[ 1 ]; } );
		}
		function oneCall( typed ) {
			var words = String( typed || '' ).trim().toUpperCase().split( /[^A-Z0-9\/]+/ ).filter( Boolean );
			return words.length === 1 && CALL_RE.test( words[ 0 ] ) && /[A-Z]/.test( words[ 0 ] ) ? words[ 0 ] : '';
		}
		function minus( s ) { return ( s || '' ).replace( /(^|\s)-(\d)/, '$1\u2212$2' ); }
		function ord( n, cap ) { return [ '', '1st', '2nd', '3rd', '4th', cap ? 'Fifth' : 'fifth' ][ n ] || String( n ); }
		function ords( list, cap ) {
			var w = list.map( function ( n, i ) { return ord( n, cap && i === 0 ); } );
			if ( w.length < 2 ) { return w.join( '' ); }
			var last = w.pop();
			return w.join( ', ' ) + ' and ' + last;
		}
		function set( key, text ) {
			var el = $( '[data-preview="' + key + '"]', box );
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
			set( 'bar', ( t + ' ' + ( call + ' ' + [ mhz, minus( off ), tone ].filter( Boolean ).join( ', ' ) ).trim() ).trim() );
			set( 'copy', ( call + ' ' + [ mhz, off ? off + ' offset' : '', tone ? tone + ' tone' : '' ].filter( Boolean ).join( ', ' ) ).trim() );
			set( 'settings', ( cfg.every || 'Every Tuesday' ) + ', ' + t + ' · ' + ( call + ' ' + [ mhz, minus( off ), tone ? tone + ' tone' : '' ].filter( Boolean ).join( ', ' ) ).trim() );
			set( 'from-home', 'From home: listen to the Tuesday net, ' + t + ', ' + ( mhz ? mhz + ', ' : '' ) + 'on any scanner or 2-meter radio. No license needed.' );
			var typedAlt = val( '#spk-a-freq' );
			var afreq = ( '' === typedAlt || freqOk( typedAlt ) ) ? typedAlt : stored( '#spk-a-freq' );
			var aoff = val( '#spk-a-offset' ), atone = val( '#spk-a-tone' ), show = $( '#spk-a-show', form );
			// As the How it works row prints it.
			set( 'alt', show && show.checked && afreq ? 'Alternate · ' + [ afreq + ' MHz', minus( aoff ), atone ? atone + ' tone' : '' ].filter( Boolean ).join( ', ' ) : '(not shown)' );
			// A week ticked twice counts once: simplex first, then Winlink, then GMRS.
			var sx = weeks( 'simplex_nth' );
			var wl = without( weeks( 'winlink_nth' ), sx );
			var gm = without( weeks( 'gmrs_nth' ), sx.concat( wl ) );
			var gtTyped = val( '#spk-gmrs-time' );
			var gt = '' === gtTyped ? '' : ( time( gtTyped ) || time( stored( '#spk-gmrs-time' ) ) );
			set( 'winlink', wl.length ? 'Winlink nights: ' + ords( wl ) + ' Tuesdays; net control gives a Winlink assignment during the net.' : '' );
			set( 'simplex', sx.length ? ords( sx, true ) + ' Tuesdays: the net starts on simplex, then moves to ' + call + '.' : '' );
			set( 'gmrs', gm.length ? 'ACS GMRS net: ' + ords( gm ) + ' Tuesdays, ' + ( gt ? gt + ', ' : '' ) + 'for county volunteers with GMRS licenses.' : '' );
		}
		form.addEventListener( 'input', draw );
		form.addEventListener( 'change', draw );
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
			var notice = $( '.spk-notice.notice-error' );
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

	function confirms() {
		$$( 'form[data-spk-confirm]' ).forEach( function ( f ) {
			f.addEventListener( 'submit', function ( e ) {
				if ( ! window.confirm( cfg.confirm || 'Save these changes?' ) ) { e.preventDefault(); }
			} );
		} );
	}

	function boot() {
		[ rota, events, documents, meetings, tiles, netPreview, confirms, asks, problems ].forEach( function ( fn ) {
			try { fn(); } catch ( e ) { if ( window.console ) { window.console.warn( '[spokares-admin-forms]', e ); } }
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
