/**
 * spokares-admin-documents: the document form and the Most Used screen (UX
 * spec §3.6, §3.7). Plain script, no dependencies; loaded after
 * spokares-admin-forms. The forms still save without it.
 *
 *  - Document form: "Where the file is" shows its own box. The privacy tick
 *    unlocks the file chooser, and unticks when the source changes (the
 *    chooser locks again), when a different file is chosen, or when the web
 *    address changes. On Add, a name that is already a document says so.
 *    "{n} left" in the Short note's last 10 characters. Place in section
 *    follows the section. A new file puts the cursor in Version. A web
 *    address typed without https:// gets it.
 *  - Most Used: a document that is already another button swaps the two; a
 *    different one empties its words (the site then shows the document's
 *    name); the "Replace or change it" link follows the choice.
 */
( function () {
	'use strict';
	var admin = window.spokaresAdmin || {};
	var cfg = window.spokaresDocs || {};
	var $ = function ( sel, ctx ) { return ( ctx || document ).querySelector( sel ); };
	var $$ = function ( sel, ctx ) { return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) ); };

	/* "%1$s", "%2$s", "%s" and "%d" filled in, as sprintf() does. (Function
	   replacers, so a "$" in a document's name is kept as it is.) */
	function fmt( text, a, b ) {
		var one = function () { return String( a ); };
		var two = function () { return b === undefined ? '' : String( b ); };
		return String( text || '' ).replace( '%1$s', one ).replace( '%2$s', two ).replace( /%[sd]/, one );
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

	/* The save's rule (guards.php spokares_clean_url()): an address typed
	   without its scheme ("www.arrl.org/kids-day") gets https:// when the part
	   before the first "/" has a dot and no spaces or "@". */
	function withScheme( typed ) {
		var t = String( typed || '' ).trim();
		if ( ! t || /^[a-z][a-z0-9+.\-]*:(?!\d)/i.test( t ) || t.charAt( 0 ) === '/' ) {
			return t;
		}
		var first = t.split( /[\/?#]/ )[ 0 ];
		return first.indexOf( '.' ) > -1 && ! /[\s@]/.test( first ) ? 'https://' + t : t;
	}

	/* The site's name for an address, as "Site name shown" is filled on save. */
	function siteName( url ) {
		var h = '';
		try { h = new URL( url ).hostname.toLowerCase(); } catch ( e ) { return ''; }
		h = h.indexOf( 'www.' ) === 0 ? h.slice( 4 ) : h;
		return ( cfg.hosts && cfg.hosts[ h ] ) || h;
	}

	/* A name as the check compares it: lower case, punctuation as spaces. */
	function plain( name ) {
		return String( name || '' ).toLowerCase().replace( /[^\p{L}\p{N}]+/gu, ' ' ).trim();
	}

	/* ----------------------------------------------------------- the form */
	function documents() {
		var form = $( '.spk-doc-form' );
		if ( ! form ) {
			return;
		}
		var sources = $$( 'input[name="spk_source"]' );
		var tick = $( '#spk-privacy' );
		var file = $( '#spk-upload' );
		var linkTick = $( '#spk-privacy-link' );
		var url = $( '#spk-url' );
		var siteLabel = $( '#spk-label' );
		var version = $( '#spk-version' );
		var tickRequired = tick ? tick.required : false;

		function source() {
			var r = sources.filter( function ( x ) { return x.checked; } )[ 0 ];
			return r ? r.value : 'upload';
		}
		/* The file chooser is open only while "I checked this file" is ticked. */
		function lockFile() {
			if ( ! file ) {
				return;
			}
			var off = ! tick || ! tick.checked || source() !== 'upload';
			file.disabled = off;
			if ( off && file.value ) {
				file.value = '';
				if ( tick ) { tick.required = tickRequired; }
			}
		}
		/* Show the chosen source's box. Only its tick is sent (a hidden box's
		   tick is disabled), and a new source needs a new tick. */
		function showSource( fresh ) {
			var s = source();
			$$( '[data-source]', form ).forEach( function ( el ) { el.hidden = el.getAttribute( 'data-source' ) !== s; } );
			if ( tick ) {
				tick.disabled = s !== 'upload';
				if ( fresh ) { tick.checked = false; }
			}
			if ( linkTick ) {
				linkTick.disabled = s !== 'link';
				if ( fresh ) { linkTick.checked = false; }
			}
			lockFile();
		}
		sources.forEach( function ( r ) {
			r.addEventListener( 'change', function () { showSource( true ); } );
		} );
		showSource( false );

		if ( tick ) {
			tick.addEventListener( 'change', lockFile );
		}
		if ( file ) {
			var chosen = '';
			file.addEventListener( 'change', function () {
				var name = file.files && file.files.length ? file.files[ 0 ].name : '';
				if ( ! name ) {
					chosen = '';
					if ( tick ) { tick.required = tickRequired; }
					return;
				}
				if ( tick ) {
					// A different file than the one just chosen hasn't been
					// checked: the tick is needed again (the file stays chosen).
					if ( chosen && chosen !== name ) { tick.checked = false; }
					tick.required = true;
				}
				chosen = name;
				if ( version ) {
					version.focus();
					version.select();
				}
			} );
		}

		if ( url ) {
			// The address the link tick stands for: the stored one, or the one
			// in the box when it was ticked.
			var checked = linkTick && linkTick.checked ? withScheme( url.getAttribute( 'data-stored' ) || url.value ) : null;
			if ( linkTick ) {
				linkTick.addEventListener( 'change', function () {
					checked = linkTick.checked ? withScheme( url.value ) : null;
				} );
			}
			var follow = function () {
				if ( linkTick && linkTick.checked && withScheme( url.value ) !== checked ) {
					linkTick.checked = false;
					checked = null;
				}
				if ( siteLabel ) {
					siteLabel.placeholder = siteName( withScheme( url.value ) );
				}
			};
			url.addEventListener( 'input', follow );
			url.addEventListener( 'change', follow );
		}
		[ url, $( '#spk-howto-url' ) ].forEach( function ( box ) {
			if ( ! box ) {
				return;
			}
			box.addEventListener( 'blur', function () {
				var full = withScheme( box.value );
				if ( full !== box.value.trim() ) {
					box.value = full;
				}
			} );
		} );

		// The name box is required for Publish (Save draft skips the check).
		var title = $( '#title' );
		if ( title ) {
			title.required = true;
			var problem = $( '#spk-title-problem' );
			if ( problem ) {
				title.classList.add( 'spk-field-error' );
				title.setAttribute( 'aria-invalid', 'true' );
				title.setAttribute( 'aria-describedby', problem.id );
			}
		}
		var match = $( '#spk-name-match' );
		if ( title && match && cfg.isNew ) {
			var check = function () { nameMatch( title.value, match ); };
			title.addEventListener( 'input', check );
			check();
		}

		var note = $( '#spk-note' );
		var left = $( '#spk-note-left' );
		if ( note && left ) {
			var max = parseInt( note.getAttribute( 'maxlength' ), 10 ) || 60;
			var count = function () {
				var n = max - note.value.length;
				var text = n <= 10 ? fmt( cfg.left || '%d left', String( n ) ) : '';
				if ( left.textContent !== text ) { left.textContent = text; }
			};
			note.addEventListener( 'input', count );
			count();
		}

		var place = $( '#spk-place' );
		if ( place ) {
			$$( 'input[name="spk_section"]' ).forEach( function ( r ) {
				r.addEventListener( 'change', function () {
					if ( r.checked ) { placeChoices( place, r.value ); }
				} );
			} );
		}

		$$( '.spk-reviewed-today' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var t = document.getElementById( b.getAttribute( 'data-target' ) );
				if ( t && admin.today ) { t.value = admin.today; }
			} );
		} );
		spares( '.spk-add-sub', '.spk-sub-row' );
	}

	/* On Add: "Already a document: ICS 309 Communications Log (Soon). Open it"
	   under the name, when the name is one that exists (case and punctuation
	   aside), starts one, or has only words that start its words; or "Already
	   on the site: IS-100.c, under FEMA courses. Open it" when it names one of
	   a document's course links. A document with no section is a name that
	   WordPress saved on its own from an Add that went no further: skipped. */
	function nameMatch( typed, box ) {
		var t = plain( typed );
		var docs = ( cfg.docs || [] ).filter( function ( d ) { return d[ 0 ] !== cfg.id && d[ 2 ]; } );
		var hit = null;
		var sub = null;
		if ( t.length >= 3 ) {
			hit = docs.filter( function ( d ) { return plain( d[ 1 ] ) === t; } )[ 0 ] || null;
			if ( ! hit && t.length >= 5 ) {
				hit = docs.filter( function ( d ) { return plain( d[ 1 ] ).indexOf( t + ' ' ) === 0; } )[ 0 ] || null;
			}
			// "Net script (weekly)" for "Net scripts: weekly, simplex and GMRS":
			// every typed word starts a word of the name.
			var words = t.split( ' ' );
			if ( ! hit && words.length >= 2 && t.length >= 6 ) {
				hit = docs.filter( function ( d ) {
					var name = plain( d[ 1 ] ).split( ' ' );
					return words.every( function ( w ) { return name.some( function ( n ) { return n.indexOf( w ) === 0; } ); } );
				} )[ 0 ] || null;
			}
			// "FEMA IS-100" or "IS-100.c: Introduction to the Incident Command
			// System": a course link's words (without a last letter or two, so
			// "IS-100" finds "IS-100.c") start, or are in, the name; or its title is.
			if ( ! hit && t.length >= 5 ) {
				var padded = ' ' + t + ' ';
				var within = function ( w ) { return w.length >= 5 && ( w.indexOf( t ) === 0 || padded.indexOf( ' ' + w + ' ' ) !== -1 ); };
				docs.some( function ( d ) {
					return ( d[ 4 ] || [] ).some( function ( l ) {
						var words2 = plain( l[ 0 ] );
						var core = words2.replace( /^(.+\S) \S{1,2}$/, '$1' );
						var title = plain( l[ 1 ] );
						var named = within( words2 ) || within( core ) ||
							( title && ( title === t || padded.indexOf( ' ' + title + ' ' ) !== -1 ) );
						if ( named ) {
							hit = d;
							sub = l;
						}
						return named;
					} );
				} );
			}
		}
		var key = hit ? String( hit[ 0 ] ) + ( sub ? ':' + sub[ 0 ] : '' ) : '';
		if ( box.getAttribute( 'data-doc' ) === key ) {
			return;
		}
		box.setAttribute( 'data-doc', key );
		box.textContent = '';
		if ( ! hit ) {
			return;
		}
		box.appendChild( document.createTextNode( ( sub ? fmt( cfg.alreadyLink, sub[ 0 ] || sub[ 1 ], hit[ 1 ] ) : fmt( cfg.already, hit[ 1 ], hit[ 3 ] ? cfg.soon : '' ) ) + ' ' ) );
		var a = document.createElement( 'a' );
		a.href = ( cfg.editBase || '' ) + hit[ 0 ];
		a.textContent = cfg.openIt || 'Open it';
		// Keep the focus in the name box, so WordPress doesn't save the typed
		// name as a draft on its way out, and don't ask "Leave site?": nothing
		// here is worth keeping once the document is found.
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

	/* Place in section for a newly picked section: its documents, and "At the
	   end" chosen. */
	function placeChoices( select, section ) {
		select.textContent = '';
		function add( value, words ) {
			var o = document.createElement( 'option' );
			o.value = value;
			o.textContent = words;
			select.appendChild( o );
		}
		add( 'top', cfg.top || 'At the top' );
		( cfg.docs || [] ).forEach( function ( d ) {
			if ( d[ 2 ] === section && d[ 0 ] !== cfg.id ) {
				add( String( d[ 0 ] ), fmt( cfg.after || 'After “%s”', d[ 1 ] ) );
			}
		} );
		add( 'end', cfg.end || 'At the end' );
		select.value = 'end';
	}

	/* ---------------------------------------------------------- Most Used */
	function tiles() {
		var rows = $$( 'tr.spk-tile-row' );
		if ( ! rows.length ) {
			return;
		}
		var parts = rows.map( function ( r ) {
			return { doc: $( '.spk-tile-doc', r ), label: $( '.spk-tile-label', r ), icon: $( '.spk-tile-icon', r ), edit: $( '.spk-tile-edit', r ) };
		} );
		var prev = parts.map( function ( p ) { return p.doc.value; } );
		function chosen( p ) {
			return p.doc.options[ p.doc.selectedIndex ] || null;
		}
		/* The link to the chosen document's form, and its name as the words'
		   placeholder (what the site shows when the words are empty). */
		function follow( p ) {
			var o = chosen( p );
			var href = o ? o.getAttribute( 'data-edit' ) || '' : '';
			if ( p.edit ) {
				p.edit.hidden = ! href;
				if ( href ) { p.edit.href = href; }
			}
			if ( p.label ) {
				p.label.placeholder = o ? o.getAttribute( 'data-title' ) || '' : '';
			}
		}
		/* "(now button 2)" after a document that is another button. */
		function hints() {
			parts.forEach( function ( p, i ) {
				$$( 'option', p.doc ).forEach( function ( o ) {
					var title = o.getAttribute( 'data-title' );
					if ( ! title || o.hasAttribute( 'data-off' ) ) {
						return;
					}
					var j = -1;
					parts.forEach( function ( q, k ) { if ( k !== i && q.doc.value === o.value ) { j = k; } } );
					var text = j > -1 ? title + ' ' + fmt( cfg.nowButton || '(now button %d)', String( j + 1 ) ) : title;
					if ( o.textContent !== text ) { o.textContent = text; }
				} );
			} );
		}
		parts.forEach( function ( p, i ) {
			p.doc.addEventListener( 'change', function () {
				var j = -1;
				parts.forEach( function ( q, k ) { if ( k !== i && q.doc.value === p.doc.value && p.doc.value !== '0' ) { j = k; } } );
				if ( j > -1 ) {
					// Already another button: the two swap, words and icons too.
					var o = parts[ j ];
					var label = p.label.value, icon = p.icon.value;
					o.doc.value = prev[ i ];
					p.label.value = o.label.value;
					p.icon.value = o.icon.value;
					o.label.value = label;
					o.icon.value = icon;
					follow( o );
				} else {
					// A different document: the old words described the old one.
					p.label.value = '';
				}
				follow( p );
				prev = parts.map( function ( q ) { return q.doc.value; } );
				hints();
			} );
		} );
		hints();
	}

	function boot() {
		[ documents, tiles ].forEach( function ( fn ) {
			try { fn(); } catch ( e ) { if ( window.console ) { window.console.warn( '[spokares-admin-documents]', e ); } }
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
