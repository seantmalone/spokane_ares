/**
 * spokares-editor-guard: the block editor for editors (not administrators)
 * on pages. Plain script on the wp.* globals, no build.
 *
 *  1. Editing modes, on every block of the page (a copy of core's
 *     DisableNonPageContentBlocks, carried down the tree): blocks that hold
 *     words (paragraphs, headings, lists and their items, buttons, tables,
 *     the hero photo) are content-only; everything else (sections, groups,
 *     the Buttons row, spacers, dynamic lists) is disabled. So words can
 *     change but nothing can be moved, added, removed or restyled, and a
 *     button can't be dragged or deleted from its row. The server's layout
 *     check is the real lock; this only tidies the editor.
 *  2. Inserting: only list items. The layout check refuses every other new
 *     block, so the editor no longer offers them.
 *  3. Pasting several paragraphs into one paragraph: core would add new
 *     paragraphs, which the lock forbids, so the paste did nothing at all.
 *     Now they arrive as one paragraph with line breaks (one line in a
 *     heading or a button), and a notice says so. List items still split.
 *  4. After each save, a warning if the page text looks like something we
 *     never publish (hospital nets, channels, 800 MHz, phone numbers,
 *     personal e-mail addresses), or if a new item in a "title" list (What
 *     we do, the timeline, the message hops) has no bold first words.
 *     Saving is never blocked.
 *  5. The Page panel's Status, Publish, Slug, Author, Template and Trash
 *     rows, the Slug/Parent/Featured image panels, the Fill/Outline button
 *     styles, the Welcome Guide and the starter-pattern window are off. The
 *     server keeps status, password, date, slug and template anyway.
 */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.data || ! wp.domReady ) {
		return;
	}
	var data = wp.data;
	var cfg = window.spokaresGuard || { patterns: [], allowed: '', message: '%s', leadIns: [] };
	var BE = 'core/block-editor';

	/* Blocks that hold words an editor may change. Everything else on the
	   page is disabled (not selectable, not movable, nothing inserted in it). */
	var CONTENT = [ 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/button', 'core/table', 'core/image', 'core/cover' ];

	/* The client id whose children are the page's sections: the Post Content
	   block when the editor shows the template around the page, else the root. */
	function pageRoot( be ) {
		if ( typeof be.getBlocksByName === 'function' ) {
			var pc = be.getBlocksByName( 'core/post-content' );
			if ( pc && pc.length ) {
				return pc[ 0 ];
			}
		}
		return '';
	}

	/* Every block under the page root. The selector is memoised on its
	   arguments, so the same root array is passed each time: the result is
	   then the same array until blocks are added, removed or moved. */
	var rootArg = null;
	var rootArgFor = null;
	function descendants( be, root ) {
		if ( typeof be.getClientIdsOfDescendants === 'function' ) {
			if ( rootArgFor !== root || ! root ) {
				rootArgFor = root;
				rootArg = root ? [ root ] : be.getBlockOrder( '' );
			}
			return be.getClientIdsOfDescendants( rootArg ) || [];
		}
		var out = [];
		( function walk( id ) {
			( be.getBlockOrder( id ) || [] ).forEach( function ( child ) {
				out.push( child );
				walk( child );
			} );
		}( root ) );
		return out;
	}

	var lastIds = null;
	var lastRoot = null;
	var lastSelected = null;
	function applyModes() {
		var be = data.select( BE );
		var d = data.dispatch( BE );
		if ( ! be || ! d || typeof d.setBlockEditingMode !== 'function' ) {
			return;
		}
		var root = pageRoot( be );
		var ids = descendants( be, root );
		var selected = be.getSelectedBlockClientId();
		// Core sets the Post Content block's own mode when it shows the
		// template, so re-apply whenever that mode drifts too, and whenever a
		// block is selected (its toolbar is drawn from its mode).
		if ( ids === lastIds && root === lastRoot && selected === lastSelected && be.getBlockEditingMode( root ) === 'disabled' ) {
			return;
		}
		lastIds = ids;
		lastRoot = root;
		lastSelected = selected;
		// The page's container (the root, or Post Content when the template is
		// shown) is disabled, so nothing can be inserted between or after the
		// sections.
		if ( be.getBlockEditingMode( root ) !== 'disabled' ) {
			d.setBlockEditingMode( root, 'disabled' );
		}
		ids.forEach( function ( id ) {
			var want = CONTENT.indexOf( be.getBlockName( id ) ) === -1 ? 'disabled' : 'contentOnly';
			if ( be.getBlockEditingMode( id ) !== want ) {
				d.setBlockEditingMode( id, want );
			}
		} );
	}

	/* Core's own filter for "may this block go here?" (it hides template parts
	   from the inserter the same way). A list item is the only block the layout
	   check lets an editor add; if the filter ever goes away, the server check
	   still refuses the save. */
	function limitInserting() {
		if ( ! wp.hooks || typeof wp.hooks.addFilter !== 'function' ) {
			return;
		}
		wp.hooks.addFilter( 'blockEditor.__unstableCanInsertBlockType', 'spokares/editor-guard', function ( canInsert, blockType ) {
			if ( ! canInsert ) {
				return canInsert;
			}
			return !! blockType && 'core/list-item' === blockType.name;
		} );
		// No Fill/Outline choice on buttons (a class editors can't keep). The
		// style class already on a button stays in its saved markup.
		wp.hooks.addFilter( 'blocks.registerBlockType', 'spokares/editor-guard', function ( settings, name ) {
			if ( 'core/button' === name && settings && Array.isArray( settings.styles ) ) {
				return Object.assign( {}, settings, { styles: [] } );
			}
			return settings;
		} );
	}

	function preferences() {
		try {
			var p = data.dispatch( 'core/preferences' );
			if ( p && typeof p.set === 'function' ) {
				p.set( 'core/edit-post', 'welcomeGuide', false );
				p.set( 'core/edit-post', 'welcomeGuideTemplate', false );
				p.set( 'core', 'enableChoosePatternModal', false );
			}
		} catch ( e ) {
			// Preferences store missing: nothing to switch off.
		}
		// The pages are fixed: the server keeps status, password, publish date,
		// slug, parent, author and template, so these rows and panels only
		// invite confusion ("post-status" is the Page panel's Status, Publish,
		// Slug, Author, Template, Revisions and Move to trash rows).
		try {
			var ed = data.dispatch( 'core/editor' );
			if ( ed && typeof ed.removeEditorPanel === 'function' ) {
				[ 'post-status', 'post-link', 'page-attributes', 'featured-image', 'discussion-panel' ].forEach( function ( panel ) {
					ed.removeEditorPanel( panel );
				} );
			}
		} catch ( e ) {
			// Older editor: panels stay; nothing breaks.
		}
	}

	/* ------------------------------------------------------------- pasting */

	/* The pasted words as lines. HTML (Word, most e-mail) gives the real
	   paragraphs and line breaks; plain text splits on blank lines when it has
	   them (a single newline there is only a wrapped line), else on newlines. */
	var BLOCK_TAGS = /^(P|DIV|LI|H[1-6]|TR|BLOCKQUOTE|PRE|UL|OL|TABLE|SECTION|ARTICLE|HEADER|FOOTER)$/;
	function htmlText( html ) {
		if ( ! window.DOMParser ) {
			return '';
		}
		var doc = new window.DOMParser().parseFromString( html, 'text/html' );
		var out = '';
		( function walk( node ) {
			Array.prototype.forEach.call( node.childNodes, function ( n ) {
				if ( 3 === n.nodeType ) {
					out += n.nodeValue.replace( /\s+/g, ' ' );
				} else if ( 1 === n.nodeType && ! /^(STYLE|SCRIPT|HEAD|META|TITLE|TEMPLATE)$/.test( n.tagName ) ) {
					if ( 'BR' === n.tagName ) {
						out += '\n';
						return;
					}
					var block = BLOCK_TAGS.test( n.tagName );
					out += block ? '\n' : '';
					walk( n );
					out += block ? '\n' : '';
				}
			} );
		}( doc.body || doc ) );
		return out;
	}
	function pastedLines( cd ) {
		var html = cd.getData( 'text/html' ) || '';
		var parts;
		if ( html ) {
			parts = htmlText( html ).split( '\n' );
		} else {
			var text = ( cd.getData( 'text/plain' ) || '' ).replace( /\r\n?/g, '\n' );
			parts = /\n[ \t]*\n/.test( text )
				? text.split( /\n[ \t]*\n+/ ).map( function ( para ) { return para.replace( /\s*\n\s*/g, ' ' ); } )
				: text.split( '\n' );
		}
		return parts.map( function ( line ) {
			return line.replace( /[ \t ]+/g, ' ' ).trim();
		} ).filter( Boolean );
	}

	function onPaste( e ) {
		var cd = e.clipboardData;
		if ( ! cd || ( cd.files && cd.files.length ) ) {
			return;
		}
		var be = data.select( BE );
		var start = be.getSelectionStart();
		var end = be.getSelectionEnd();
		if ( ! start || ! start.clientId || ! end || start.clientId !== end.clientId || ! start.attributeKey || start.attributeKey !== end.attributeKey ) {
			return; // Across blocks, or no text selection: core decides.
		}
		var block = be.getBlock( start.clientId );
		if ( ! block || 'core/list-item' === block.name || 'contentOnly' !== be.getBlockEditingMode( start.clientId ) ) {
			return; // New list items are allowed, so core may split them.
		}
		var lines = pastedLines( cd );
		if ( lines.length < 2 ) {
			return; // One paragraph pastes fine as it is.
		}
		e.preventDefault();
		e.stopImmediatePropagation();
		var oneLine = 'core/heading' === block.name || 'core/button' === block.name;
		var text = lines.join( oneLine ? ' ' : '\n' );
		var key = start.attributeKey;
		var rt = wp.richText;
		if ( Object.prototype.hasOwnProperty.call( block.attributes, key ) && rt && rt.create && rt.insert && rt.toHTMLString ) {
			var current = block.attributes[ key ];
			var value = rt.create( { html: null === current || undefined === current ? '' : String( current ) } );
			var from = 'number' === typeof start.offset ? start.offset : value.text.length;
			var to = 'number' === typeof end.offset ? end.offset : from;
			if ( to < from ) {
				var t = from;
				from = to;
				to = t;
			}
			// Keep a space between the pasted words and the words around them.
			if ( from > 0 && /\S/.test( value.text.charAt( from - 1 ) ) ) {
				text = ' ' + text;
			}
			if ( to < value.text.length && /[^\s.,;:!?)]/.test( value.text.charAt( to ) ) ) {
				text += ' ';
			}
			var next = rt.insert( value, text, from, to );
			var attrs = {};
			attrs[ key ] = rt.toHTMLString( { value: next } );
			data.dispatch( BE ).updateBlockAttributes( start.clientId, attrs );
			var caret = from + text.length;
			data.dispatch( BE ).selectionChange( start.clientId, key, caret, caret );
		} else {
			// A table cell: its words live inside the table's rows, so let the
			// browser type them in as one line.
			var doc = ( e.target && e.target.ownerDocument ) || document;
			doc.execCommand( 'insertText', false, lines.join( ' ' ) );
		}
		var notices = data.dispatch( 'core/notices' );
		if ( notices ) {
			notices.createInfoNotice( oneLine ? cfg.pastedOn : cfg.pasted, { id: 'spokares-paste', type: 'snackbar' } );
		}
	}

	/* The canvas is an iframe that can be rebuilt (device preview, template
	   view), so the listener is added to whichever document is current. */
	function watchPaste() {
		[ document, ( document.querySelector( 'iframe[name="editor-canvas"]' ) || {} ).contentDocument ].forEach( function ( doc ) {
			if ( doc && ! doc.spokaresPaste ) {
				doc.spokaresPaste = true;
				doc.addEventListener( 'paste', onPaste, true );
			}
		} );
	}

	/* ------------------------------------------------------ after each save */

	var wasSaving = false;
	function checkAfterSave() {
		var ed = data.select( 'core/editor' );
		if ( ! ed || typeof ed.isSavingPost !== 'function' ) {
			return;
		}
		var saving = ed.isSavingPost() && ! ed.isAutosavingPost();
		var justSaved = wasSaving && ! saving;
		// Record the state first: reading the content below can dispatch (and
		// so call this subscriber again) before this call returns.
		wasSaving = saving;
		if ( justSaved ) {
			// Outside the subscribe callback, so the store can settle first.
			window.setTimeout( scanSavedContent, 0 );
		}
	}

	/* A new item in a list whose items start with bold words (the item's
	   title in What we do, the timeline and the message hops). */
	function missingLeadIn() {
		var be = data.select( BE );
		var lists = ( typeof be.getBlocksByName === 'function' ? be.getBlocksByName( 'core/list' ) : [] ) || [];
		for ( var i = 0; i < lists.length; i++ ) {
			var list = be.getBlock( lists[ i ] );
			var classes = ( ( list && list.attributes.className ) || '' ).split( /\s+/ );
			if ( ! ( cfg.leadIns || [] ).some( function ( c ) { return classes.indexOf( c ) !== -1; } ) ) {
				continue;
			}
			for ( var j = 0; j < list.innerBlocks.length; j++ ) {
				var html = String( list.innerBlocks[ j ].attributes.content || '' ).trim();
				var words = html.replace( /<[^>]+>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
				if ( words && ! /^<(strong|b)[\s>]/i.test( html ) ) {
					return words.length > 50 ? words.slice( 0, 50 ) + '…' : words.replace( /[.!?]+$/, '' );
				}
			}
		}
		return '';
	}

	function scanSavedContent() {
		var ed = data.select( 'core/editor' );
		if ( ! ed ) {
			return;
		}
		var html = ed.getEditedPostContent() || '';
		var text = html.replace( /<!--[\s\S]*?-->/g, ' ' ).replace( /<[^>]+>/g, ' ' );
		if ( cfg.allowed ) {
			text = text.replace( new RegExp( cfg.allowed, 'gi' ), ' ' );
		}
		var found = [];
		( cfg.patterns || [] ).forEach( function ( p ) {
			try {
				if ( new RegExp( p.re, 'iu' ).test( text ) && found.indexOf( p.what ) === -1 ) {
					found.push( p.what );
				}
			} catch ( e ) {
				// An old browser without lookbehind: skip that pattern.
			}
		} );
		var notices = data.dispatch( 'core/notices' );
		if ( ! notices ) {
			return;
		}
		if ( found.length ) {
			notices.createWarningNotice( String( cfg.message ).replace( '%s', found.join( ', ' ) ), { id: 'spokares-never-publish', isDismissible: true } );
		} else {
			notices.removeNotice( 'spokares-never-publish' );
		}
		var bare = cfg.noLeadIn ? missingLeadIn() : '';
		if ( bare ) {
			notices.createWarningNotice( String( cfg.noLeadIn ).replace( '%s', bare ), { id: 'spokares-lead-in', isDismissible: true } );
		} else {
			notices.removeNotice( 'spokares-lead-in' );
		}
	}

	limitInserting();
	wp.domReady( function () {
		preferences();
		applyModes();
		watchPaste();
		data.subscribe( function () {
			applyModes();
			watchPaste();
			checkAfterSave();
		} );
	} );
}( window.wp ) );
