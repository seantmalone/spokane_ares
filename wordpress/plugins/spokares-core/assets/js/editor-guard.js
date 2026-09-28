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
 *     block, so the editor no longer offers them. Headings offer no level
 *     switcher (their variations are dropped), buttons no Fill/Outline.
 *  3. Pasting several paragraphs into one paragraph: core would add new
 *     paragraphs, which the lock forbids, so the paste did nothing at all.
 *     Now they arrive as one paragraph with line breaks (one line in a
 *     heading or a button), and a notice says so. List items still split.
 *  4. After each save, a warning if the page text looks like something we
 *     never publish (hospital nets, channels, 800 MHz, phone numbers,
 *     personal e-mail addresses), naming what was found; if a new item in a
 *     "title" list (What we do, the timeline, the message hops) has no bold
 *     first words; if a list item is empty (the server leaves empty items out
 *     of the saved page); if a new or changed link doesn't start with
 *     https://; or if a link shows its web address as its words. Saving is
 *     never blocked. A save the server refuses shows only the server's
 *     sentence (not core's "Updating failed." in front of it).
 *  5. The Page panel's Status, Publish, Slug, Author, Template and Trash
 *     rows, the Slug/Parent/Featured image/Excerpt panels, the Welcome Guide
 *     and the starter-pattern window are off. The server keeps status,
 *     password, date, slug and template anyway, and tells the editor that
 *     pages have no page attributes, title or notes for editors
 *     (governance.php), so the page card's ⋮ menu offers no Order, Rename or
 *     Trash and the block menu no "Add note". A style (governance.php) hides
 *     the ⋮ Options menu, list Indent/Outdent, the sidebar's Content list,
 *     the hero photo's extra Replace choices, the fields of the media window
 *     that do nothing here, and, while a Button is selected (this script
 *     marks the page), Unlink and Remove link.
 *  6. The settings sidebar starts closed, except on About, where the Page
 *     review box is. The media window's "Alt Text" reads "Describe the photo
 *     in a few words", its way in and its title read "Choose a photo", and
 *     a photo that isn't JPEG, PNG or WebP (an iPad's HEIC) gets one plain
 *     sentence.
 *  7. After a new photo is saved, the editor no longer says the page has
 *     unsaved changes: the Cover works out the photo's colour after the
 *     upload and marks that as a change not worth an undo step, and at the
 *     end of the save the editor sends the same blocks again as a new edit.
 *     That edit changes nothing, so it is dropped.
 */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.data || ! wp.domReady ) {
		return;
	}
	var data = wp.data;
	var cfg = window.spokaresGuard || { patterns: [], allowed: '', message: '%1$s (%2$s)', leadIns: [], refusals: [] };
	var BE = 'core/block-editor';

	/* Plain words for the photo window's way in: "Open Media Library" and
	   the window's title read "Choose a photo" (cfg.words). Added before the
	   editor draws anything, so every use of the words gets ours. */
	if ( cfg.words && wp.hooks && typeof wp.hooks.addFilter === 'function' ) {
		wp.hooks.addFilter( 'i18n.gettext', 'spokares/editor-guard/words', function ( translation, text, domain ) {
			return ( ! domain || 'default' === domain ) && Object.prototype.hasOwnProperty.call( cfg.words, text ) ? cfg.words[ text ] : translation;
		} );
	}

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
		// style class already on a button stays in its saved markup. No
		// heading levels: in 7.1 the level switcher in the toolbar and the
		// sidebar are the heading's variations (h1-h6), and a new level is a
		// layout change the server refuses. The level already set stays.
		wp.hooks.addFilter( 'blocks.registerBlockType', 'spokares/editor-guard', function ( settings, name ) {
			if ( 'core/button' === name && settings && Array.isArray( settings.styles ) ) {
				return Object.assign( {}, settings, { styles: [] } );
			}
			if ( 'core/heading' === name && settings && Array.isArray( settings.variations ) ) {
				return Object.assign( {}, settings, { variations: [] } );
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
		// Slug, Author, Template, Revisions and Move to trash rows). The
		// excerpt is the search-engine description, the webmaster's job.
		try {
			var ed = data.dispatch( 'core/editor' );
			if ( ed && typeof ed.removeEditorPanel === 'function' ) {
				[ 'post-status', 'post-link', 'page-attributes', 'featured-image', 'discussion-panel', 'post-excerpt' ].forEach( function ( panel ) {
					ed.removeEditorPanel( panel );
				} );
			}
		} catch ( e ) {
			// Older editor: panels stay; nothing breaks.
		}
	}

	/* The settings sidebar starts closed (on an iPad it covers half the page),
	   except on About, where the Page review box is. Set once, when the
	   editor has the page. */
	var sidebarSet = false;
	function sidebarOnOpen() {
		var ed = data.select( 'core/editor' );
		var ep = data.dispatch( 'core/edit-post' );
		if ( sidebarSet || ! ed || ! ep || typeof ed.getCurrentPostId !== 'function' || ! ed.getCurrentPostId() ) {
			return;
		}
		sidebarSet = true;
		try {
			if ( cfg.reviewBox ) {
				ep.openGeneralSidebar( 'edit-post/document' );
			} else {
				ep.closeGeneralSidebar();
			}
		} catch ( e ) {
			// Older editor: the sidebar stays as it was.
		}
	}

	/* While a Button is selected the page carries a class, so the style
	   (governance.php) can hide Unlink and Remove link for buttons only. */
	var buttonSelected = null;
	function markButton() {
		var be = data.select( BE );
		var id = be && be.getSelectedBlockClientId();
		var on = !! id && 'core/button' === be.getBlockName( id );
		if ( on !== buttonSelected && document.body ) {
			buttonSelected = on;
			document.body.classList.toggle( 'spk-button-selected', on );
		}
	}

	/* The media window's templates are read the first time the window opens,
	   so the alt text's label is changed in them before that. */
	function mediaWindow() {
		if ( cfg.altLabel ) {
			var label = String( cfg.altLabel ).replace( /[&<>"]/g, function ( c ) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ];
			} );
			[ 'tmpl-attachment-details', 'tmpl-attachment-details-two-column' ].forEach( function ( id ) {
				var tpl = document.getElementById( id );
				if ( tpl && ! tpl.spokaresAlt ) {
					tpl.spokaresAlt = true;
					tpl.textContent = tpl.textContent.replace( /(<label for="attachment-details(?:-two-column)?-alt-text"[^>]*>)[^<]*(<\/label>)/, function ( all, open, close ) {
						return open + label + close;
					} );
				}
			} );
		}
		// The window's own uploader: a photo type it refuses.
		if ( cfg.photoType && wp.Uploader && wp.Uploader.errorMap ) {
			wp.Uploader.errorMap.FILE_EXTENSION_ERROR = cfg.photoType;
		}
	}

	/* Two error notices say it better in one plain sentence: a save the
	   server refused (core puts "Updating failed." in front of the server's
	   sentence), and a photo that isn't JPEG, PNG or WebP (an iPad's HEIC).
	   The notice is replaced under its own id. */
	var PHOTO_TYPE = /\.(heic|heif)\b|not allowed to upload this file type|file type is not permitted/i;
	var lastNotices = null;
	function tidyNotices() {
		var sel = data.select( 'core/notices' );
		var list = sel && sel.getNotices();
		if ( ! list || list === lastNotices ) {
			return;
		}
		lastNotices = list;
		var swaps = [];
		list.forEach( function ( n ) {
			var text = String( n.content || '' );
			if ( 'error' !== n.status ) {
				return;
			}
			var refusal = ( cfg.refusals || [] ).filter( function ( r ) {
				return r && text !== r && text.indexOf( r ) !== -1;
			} )[ 0 ];
			if ( refusal ) {
				swaps.push( [ n, refusal ] );
			} else if ( cfg.photoType && text !== cfg.photoType && PHOTO_TYPE.test( text ) ) {
				swaps.push( [ n, cfg.photoType ] );
			}
		} );
		if ( swaps.length ) {
			// Outside the subscribe callback, so the store can settle first.
			window.setTimeout( function () {
				var notices = data.dispatch( 'core/notices' );
				swaps.forEach( function ( swap ) {
					notices.createErrorNotice( swap[ 1 ], { id: swap[ 0 ].id, type: swap[ 0 ].type, isDismissible: true } );
				} );
			}, 0 );
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
	var sentContent = null;
	var settleBy = 0;
	function checkAfterSave() {
		var ed = data.select( 'core/editor' );
		if ( ! ed || typeof ed.isSavingPost !== 'function' ) {
			return;
		}
		var saving = ed.isSavingPost() && ! ed.isAutosavingPost();
		var started = saving && ! wasSaving;
		var justSaved = wasSaving && ! saving;
		// Record the state first: reading the content below can dispatch (and
		// so call this subscriber again) before this call returns.
		wasSaving = saving;
		if ( started ) {
			sentContent = null;
			window.setTimeout( function () {
				sentContent = ed.getEditedPostContent();
			}, 0 );
		}
		if ( justSaved ) {
			settleBy = ed.didPostSaveRequestFail() ? 0 : Date.now() + 5000;
			// Outside the subscribe callback, so the store can settle first.
			window.setTimeout( scanSavedContent, 0 );
		} else if ( settleBy && ! saving && ed.isEditedPostDirty() ) {
			if ( settleBy > Date.now() ) {
				window.setTimeout( dropEmptyEdit, 0 );
			}
			settleBy = 0;
		}
	}

	/* Just after a save, an edit that changes nothing: the page as it is now
	   is the page that was saved, and the only unsaved thing is the page's
	   content (core resent the same blocks when it marked the Cover's colour
	   change as kept). Drop it, so leaving doesn't ask "Leave site?". */
	function dropEmptyEdit() {
		var ed = data.select( 'core/editor' );
		var core = data.select( 'core' );
		if ( ! ed.isEditedPostDirty() || null === sentContent || ! core || typeof core.getEntityRecordNonTransientEdits !== 'function' ) {
			return;
		}
		var type = ed.getCurrentPostType();
		var id = ed.getCurrentPostId();
		var edits = core.getEntityRecordNonTransientEdits( 'postType', type, id ) || {};
		if ( Object.keys( edits ).join() !== 'content' || ed.getEditedPostContent() !== sentContent ) {
			return;
		}
		data.dispatch( 'core' ).editEntityRecord( 'postType', type, id, { content: undefined }, { undoIgnore: true } );
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

	/* Is any list item on the page empty (no words, no inner list)? Pressing
	   Enter at the start or end of an item leaves one; the server leaves it
	   out of the saved page, so the editor shows an item the page doesn't. */
	function hasEmptyItem() {
		var be = data.select( BE );
		var items = ( typeof be.getBlocksByName === 'function' ? be.getBlocksByName( 'core/list-item' ) : [] ) || [];
		for ( var i = 0; i < items.length; i++ ) {
			var item = be.getBlock( items[ i ] );
			if ( ! item || ( item.innerBlocks && item.innerBlocks.length ) ) {
				continue;
			}
			var words = String( item.attributes.content || '' ).replace( /<[^>]+>/g, ' ' ).replace( /&nbsp;|\u00a0/gi, ' ' ).trim();
			if ( ! words ) {
				return true;
			}
		}
		return false;
	}

	/* Some markup's text, entities decoded. */
	var decoder = document.createElement( 'textarea' );
	function plainText( html ) {
		decoder.innerHTML = String( html || '' ).replace( /<!--[\s\S]*?-->/g, ' ' ).replace( /<[^>]+>/g, ' ' );
		return decoder.value.replace( /\s+/g, ' ' ).trim();
	}

	/* The links in some block markup: each address (decoded) and its words. */
	function linksIn( html ) {
		var out = [];
		var re = /<a\s[^>]*?href="([^"]*)"[^>]*>([\s\S]*?)<\/a>/gi;
		var m;
		while ( ( m = re.exec( html ) ) ) {
			out.push( { href: m[ 1 ].replace( /&amp;/g, '&' ).trim(), words: plainText( m[ 2 ] ) } );
		}
		return out;
	}

	/* The links the page had when the editor opened: only links added or
	   changed since are checked, so an old link doesn't warn on every save. */
	var linksAtStart = null;
	function rememberLinks() {
		var ed = data.select( 'core/editor' );
		if ( null !== linksAtStart || ! ed || typeof ed.getEditedPostContent !== 'function' || ! ed.getCurrentPostId() ) {
			return;
		}
		var html = ed.getEditedPostContent() || '';
		if ( html ) {
			linksAtStart = linksIn( html ).map( function ( l ) {
				return l.href + '\n' + l.words;
			} );
		}
	}
	function isNewLink( link ) {
		return ( linksAtStart || [] ).indexOf( link.href + '\n' + link.words ) === -1;
	}
	function short( text ) {
		return text.length > 60 ? text.slice( 0, 60 ) + '…' : text;
	}

	/* A new or changed link that isn't https://, mailto:, or a link within
	   the site (/…, #…). A "javascript:" link is saved without its protocol,
	   and "www.example.org" without https:// is a link to a page on this
	   site, so both break silently. */
	function badLink( html ) {
		var links = linksIn( html ).filter( isNewLink );
		for ( var i = 0; i < links.length; i++ ) {
			if ( ! /^(https:\/\/|mailto:|\/|#|\?)/i.test( links[ i ].href ) ) {
				return short( links[ i ].href );
			}
		}
		return '';
	}

	/* A new or changed link whose words are a web address (a pasted address
	   becomes a link to itself): visitors should read what they will get. */
	function addressLink( html ) {
		var links = linksIn( html ).filter( isNewLink );
		for ( var i = 0; i < links.length; i++ ) {
			if ( /^(https?:\/\/|www\.)\S+$/i.test( links[ i ].words ) ) {
				return short( links[ i ].words );
			}
		}
		return '';
	}

	/* A warning notice when found, or remove it. */
	function toggleNotice( notices, id, text ) {
		if ( text ) {
			notices.createWarningNotice( text, { id: id, isDismissible: true } );
		} else {
			notices.removeNotice( id );
		}
	}

	/* What the never-publish, phone and e-mail patterns find in some page
	   text or markup (block comments and tags left out; role addresses
	   @spokares.org are fine): each kind once, with the first words that
	   matched. */
	function neverPublish( html ) {
		var text = plainText( html );
		if ( cfg.allowed ) {
			text = text.replace( new RegExp( cfg.allowed, 'gi' ), ' ' );
		}
		var found = [];
		( cfg.patterns || [] ).forEach( function ( p ) {
			try {
				var m = new RegExp( p.re, 'iu' ).exec( text );
				if ( m && ! found.some( function ( f ) { return f.what === p.what; } ) ) {
					found.push( { what: p.what, match: m[ 0 ].trim().slice( 0, 60 ) } );
				}
			} catch ( e ) {
				// An old browser without lookbehind: skip that pattern.
			}
		} );
		return found;
	}

	/* "a, b and c". */
	function andList( items ) {
		if ( items.length < 2 ) {
			return items[ 0 ] || '';
		}
		return items.slice( 0, -1 ).join( ', ' ) + ' ' + ( cfg.and || 'and' ) + ' ' + items[ items.length - 1 ];
	}

	function scanSavedContent() {
		var ed = data.select( 'core/editor' );
		if ( ! ed ) {
			return;
		}
		var html = ed.getEditedPostContent() || '';
		var found = neverPublish( html );
		var notices = data.dispatch( 'core/notices' );
		if ( ! notices ) {
			return;
		}
		var warning = found.length ? String( cfg.message )
			.replace( '%1$s', andList( found.map( function ( f ) { return f.what; } ) ) )
			.replace( '%2$s', found.map( function ( f ) { return f.match; } ).join( ', ' ) ) : '';
		toggleNotice( notices, 'spokares-never-publish', warning );
		var bare = cfg.noLeadIn ? missingLeadIn() : '';
		toggleNotice( notices, 'spokares-lead-in', bare ? String( cfg.noLeadIn ).replace( '%s', bare ) : '' );
		toggleNotice( notices, 'spokares-empty-item', cfg.emptyItem && hasEmptyItem() ? String( cfg.emptyItem ) : '' );
		var link = cfg.badLink ? badLink( html ) : '';
		toggleNotice( notices, 'spokares-bad-link', link ? String( cfg.badLink ).replace( '%s', link ) : '' );
		var address = cfg.addressLink ? addressLink( html ) : '';
		toggleNotice( notices, 'spokares-address-link', address ? String( cfg.addressLink ).replace( '%s', address ) : '' );
	}

	limitInserting();
	wp.domReady( function () {
		preferences();
		mediaWindow();
		applyModes();
		watchPaste();
		rememberLinks();
		sidebarOnOpen();
		data.subscribe( function () {
			applyModes();
			watchPaste();
			rememberLinks();
			sidebarOnOpen();
			markButton();
			tidyNotices();
			checkAfterSave();
		} );
	} );
}( window.wp ) );
